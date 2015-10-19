<?php
//    MyDMS. Document Management System
//    Copyright (C) 2010 Matteo Lucarelli
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
include("../inc/inc.DBInit.php");
include("../inc/inc.Utils.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}
$rootfolder = $dms->getFolder($settings->_rootFolderID);

if(!empty($_GET['fromdate'])) {
	$from = makeTsFromLongDate($_GET['fromdate'].' 00:00:00');
} else {
	$from = time()-7*86400;
}
if(!empty($_GET['todate'])) {
	$to = makeTsFromLongDate($_GET['todate'].' 23:59:59');
} else {
	$to = time();
}

if(isset($_GET['skip']))
	$skip = $_GET['skip'];
else
	$skip = array();

if(isset($_GET['documentid']) && $_GET['documentid'] && is_numeric($_GET['documentid'])) {
	$document = $dms->getDocument($_GET["documentid"]);
	if (!is_object($document)) {
		$view->exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
	}
} else
	$document = null;

if(isset($_GET['version']) && $_GET['version'] && is_numeric($_GET['version'])) {
	$content = $document->getContentByVersion($_GET['version']);
} else
	$content = null;

$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
$view = UI::factory($theme, $tmp[1], array('dms'=>$dms, 'user'=>$user));
if($view) {
	$view->setParam('fromdate', isset($_GET['fromdate']) ? $_GET['fromdate'] : '');
	$view->setParam('todate', isset($_GET['todate']) ? $_GET['todate'] : '');
	$view->setParam('skip', $skip);
	$view->setParam('document', $document);
	$view->setParam('version', $content);
	$view($_GET);
	exit;
}

?>
