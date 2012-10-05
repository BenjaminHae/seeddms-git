<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2011 Uwe Steinmann
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

include("../inc/inc.Settings.php");
include("../inc/inc.Utils.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

// Redirect to the search page if the navigation search button has been
// selected without supplying any search terms.
if (isset($_GET["navBar"])) {
	if (!isset($_GET["folderid"]) || !is_numeric($_GET["folderid"]) || intval($_GET["folderid"])<1) {
		$folderid=$settings->_rootFolderID;
	}
	else {
		$folderid = $_GET["folderid"];
	}
	if(strlen($_GET["query"])==0) {
		header("Location: ../out/out.SearchForm.php?folderid=".$folderid);
	} else {
		if($_GET["fullsearch"]) {
			header("Location: ../op/op.SearchFulltext.php?folderid=".$folderid."&query=".$_GET["query"]);
		}
	}
}


function getTime() {
	if (function_exists('microtime')) {
		$tm = microtime();
		$tm = explode(' ', $tm);
		return (float) sprintf('%f', $tm[1] + $tm[0]);
	}
	return time();
}

function markQuery($str, $tag = "b") {

	GLOBAL $query;
	$querywords = preg_split("/ /", $query);
	
	foreach ($querywords as $queryword)
		$str = str_ireplace("($queryword)", "<" . $tag . ">\\1</" . $tag . ">", $str);
	
	return $str;
}


//
// Parse all of the parameters for the search
//

// Create the keyword search string. This search spans up to three columns
// in the database: keywords, name and comment.

if (isset($_GET["query"]) && is_string($_GET["query"])) {
	$query = $_GET["query"];
}
else {
	$query = "";
}

$mode = "AND";
if (isset($_GET["mode"]) && is_numeric($_GET["mode"]) && $_GET["mode"]==0) {
		$mode = "OR";
}

$searchin = array();
if (isset($_GET['searchin']) && is_array($_GET["searchin"])) {
	foreach ($_GET["searchin"] as $si) {
		if (isset($si) && is_numeric($si)) {
			switch ($si) {
				case 1: // keywords
				case 2: // name
				case 3: // comment
					$searchin[$si] = $si;
					break;
			}
		}
	}
}

// if none is checkd search all
if (count($searchin)==0) $searchin=array( 0, 1, 2, 3);

// Check to see if the search has been restricted to a particular sub-tree in
// the folder hierarchy.
if (isset($_GET["targetidform1"]) && is_numeric($_GET["targetidform1"]) && $_GET["targetidform1"]>0) {
	$targetid = $_GET["targetidform1"];
	$startFolder = $dms->getFolder($targetid);
}
else {
	$targetid = $settings->_rootFolderID;
	$startFolder = $dms->getFolder($targetid);
}
if (!is_object($startFolder)) {
	UI::exitError(getMLText("search_results"),getMLText("invalid_folder_id"));
}

// Now that the target folder has been identified, it is possible to create
// the full navigation bar.
$folderPathHTML = getFolderPathHTML($startFolder, true);
UI::htmlStartPage(getMLText("search_results"));
UI::globalNavigation($startFolder);
UI::pageNavigation($folderPathHTML, "", $startFolder);
UI::contentHeading(getMLText("search_results"));

// Check to see if the search has been restricted to a particular
// document owner.
$owner = null;
if (isset($_GET["ownerid"]) && is_numeric($_GET["ownerid"]) && $_GET["ownerid"]!=-1) {
	$owner = $dms->getUser($_GET["ownerid"]);
	if (!is_object($owner)) {
		UI::contentContainer(getMLText("unknown_owner"));
		UI::htmlEndPage();
		exit;
	}
}

// Is the search restricted to documents created between two specific dates?
$startdate = array();
$stopdate = array();
if (isset($_GET["creationdate"]) && $_GET["creationdate"]!=null) {
	$startdate = array('year'=>$_GET["createstartyear"], 'month'=>$_GET["createstartmonth"], 'day'=>$_GET["createstartday"], 'hour'=>0, 'minute'=>0, 'second'=>0);
	if (!checkdate($startdate['month'], $startdate['day'], $startdate['year'])) {
		UI::contentContainer(getMLText("invalid_create_date_start"));
		UI::htmlEndPage();
		exit;
	}
	$stopdate = array('year'=>$_GET["createendyear"], 'month'=>$_GET["createendmonth"], 'day'=>$_GET["createendday"], 'hour'=>23, 'minute'=>59, 'second'=>59);
	if (!checkdate($stopdate['month'], $stopdate['day'], $stopdate['year'])) {
		UI::contentContainer(getMLText("invalid_create_date_end"));
		UI::htmlEndPage();
		exit;
	}
}

// status
$status = array();
if (isset($_GET["pendingReview"])){
	$status[] = S_DRAFT_REV;
}
if (isset($_GET["pendingApproval"])){
	$status[] = S_DRAFT_APP;
}
if (isset($_GET["released"])){
	$status[] = S_RELEASED;
}
if (isset($_GET["rejected"])){
	$status[] = S_REJECTED;
}
if (isset($_GET["obsolete"])){
	$status[] = S_OBSOLETE;
}
if (isset($_GET["expired"])){
	$status[] = S_EXPIRED;
}

// category
$categories = array();
if(isset($_GET['categoryids']) && $_GET['categoryids']) {
	foreach($_GET['categoryids'] as $catid) {
		if($catid > 0)
			$categories[] = $dms->getDocumentCategory($catid);
	}
}

//
// Get the page number to display. If the result set contains more than
// 25 entries, it is displayed across multiple pages.
//
// This requires that a page number variable be used to track which page the
// user is interested in, and an extra clause on the select statement.
//
// Default page to display is always one.
$pageNumber=1;
$limit = 20;
if (isset($_GET["pg"])) {
	if (is_numeric($_GET["pg"]) && $_GET["pg"]>0) {
		$pageNumber = (int) $_GET["pg"];
	}
	elseif (!strcasecmp($_GET["pg"], "all")) {
		$limit = 0;
	}
}


// ---------------- Start searching -----------------------------------------
$startTime = getTime();
$resArr = $dms->search($query, $limit, ($pageNumber-1)*$limit, $mode, $searchin, $startFolder, $owner, $status, $startdate, $stopdate, array(), array(), $categories);
$searchTime = getTime() - $startTime;
$searchTime = round($searchTime, 2);

$entries = array();
if($resArr['folders']) {
	foreach ($resArr['folders'] as $entry) {
		if ($entry->getAccessMode($user) >= M_READ) {
			$entries[] = $entry;
		}
	}
}
if($resArr['docs']) {
	foreach ($resArr['docs'] as $entry) {
		if ($entry->getAccessMode($user) >= M_READ) {
			$entries[] = $entry;
		}
	}
}
// -------------- Output results --------------------------------------------

UI::contentContainerStart();
UI::pageList($pageNumber, $resArr['totalPages'], "../op/op.Search.php", $_GET);

print "<table class=\"folderView\">";
print "<thead>\n<tr>\n";
print "<th></th>\n";
print "<th>".getMLText("name")."</th>\n";
print "<th>".getMLText("owner")."</th>\n";
print "<th>".getMLText("status")."</th>\n";
print "<th>".getMLText("version")."</th>\n";
print "<th>".getMLText("comment")."</th>\n";
//print "<th>".getMLText("reviewers")."</th>\n";
//print "<th>".getMLText("approvers")."</th>\n";
print "</tr>\n</thead>\n<tbody>\n";

$resultsFilteredByAccess = false;
$foldercount = $doccount = 0;
foreach ($entries as $entry) {
	if(get_class($entry) == 'LetoDMS_Core_Document') {
		$document = $entry;
			$doccount++;
			$lc = $document->getLatestContent();
			print "<tr>";
			//print "<td><img src=\"../out/images/file.gif\" class=\"mimeicon\"></td>";
			if (in_array(2, $searchin)) {
				$docName = markQuery(htmlspecialchars($document->getName()), "i");
			} else {
				$docName = htmlspecialchars($document->getName());
			}
			print "<td><a class=\"standardText\" href=\"../out/out.ViewDocument.php?documentid=".$document->getID()."\"><img class=\"mimeicon\" src=\"../out/images/icons/".UI::getMimeIcon($lc->getFileType())."\" title=\"".$lc->getMimeType()."\"></a></td>";
			print "<td><a class=\"standardText\" href=\"../out/out.ViewDocument.php?documentid=".$document->getID()."\">/";
			$folder = $document->getFolder();
			$path = $folder->getPath();
			for ($i = 1; $i  < count($path); $i++) {
				print htmlspecialchars($path[$i]->getName())."/";
			}
			print $docName;
			print "</a></td>";
			
			$owner = $document->getOwner();
			print "<td>".htmlspecialchars($owner->getFullName())."</td>";
			$display_status=$lc->getStatus();
			print "<td>".getOverallStatusText($display_status["status"]). "</td>";

			print "<td class=\"center\">".$lc->getVersion()."</td>";
			
			if (in_array(3, $searchin)) $comment = markQuery(htmlspecialchars($document->getComment()));
			else $comment = htmlspecialchars($document->getComment());
			if (strlen($comment) > 50) $comment = substr($comment, 0, 47) . "...";
			print "<td>".$comment."</td>";
			print "</tr>\n";
	} elseif(get_class($entry) == 'LetoDMS_Core_Folder') {
		$folder = $entry;
			$foldercount++;
			if (in_array(2, $searchin)) {
				$folderName = markQuery(htmlspecialchars($folder->getName()), "i");
			} else {
				$folderName = htmlspecialchars($folder->getName());
			}
			print "<td><a class=\"standardText\" href=\"../out/out.ViewFolder.php?folderid=".$folder->getID()."\"><img src=\"../out/images/folder_closed.gif\" width=18 height=18 border=0></a></td>";
			print "<td><a class=\"standardText\" href=\"../out/out.ViewFolder.php?folderid=".$folder->getID()."\">";
			$path = $folder->getPath();
			for ($i = 1; $i  < count($path); $i++) {
				print "/".htmlspecialchars($path[$i]->getName());
			}
			print $folderName;
			print "</a></td>";
			
			$owner = $folder->getOwner();
			print "<td>".htmlspecialchars($owner->getFullName())."</td>";
			print "<td></td>";
			print "<td></td>";
			if (in_array(3, $searchin)) $comment = markQuery(htmlspecialchars($folder->getComment()));
			else $comment = htmlspecialchars($folder->getComment());
			if (strlen($comment) > 50) $comment = substr($comment, 0, 47) . "...";
			print "<td>".$comment."</td>";
			print "</tr>\n";
	}
}
if (0 && $resultsFilteredByAccess) {
	print "<tr><td colspan=\"7\">". getMLText("search_results_access_filtered") . "</td></tr>";
}

print "</tbody></table>\n";
$numResults = $doccount + $foldercount;
if ($numResults == 0) {
	print "<p>".getMLText("search_no_results")."</p>";
} else {
//	print "<p>".getMLText("search_report", array("doccount" => $doccount, "foldercount" => $foldercount, 'searchtime'=>$searchTime))."</p>";
}

UI::pageList($pageNumber, $resArr['totalPages'], "../op/op.Search.php", $_GET);

UI::contentContainerEnd();
UI::htmlEndPage();
?>
