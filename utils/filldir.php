<?php
include("keeppath.php");

/* Set alternative config file */
if(isset($options['config'])) {
	$settings = new Settings($options['config']);
} else {
	$settings = new Settings();
}

if(isset($settings->_extraPath))
	ini_set('include_path', $settings->_extraPath. PATH_SEPARATOR .ini_get('include_path'));
	
require_once("SeedDMS/Core.php");

$db = new SeedDMS_Core_DatabaseAccess($settings->_dbDriver, $settings->_dbHostname, $settings->_dbUser, $settings->_dbPass, $settings->_dbDatabase);
$db->connect() or die ("Could not connect to db-server \"" . $settings->_dbHostname . "\"");
//$db->_conn->debug = 1;

$dms = new SeedDMS_Core_DMS($db, $settings->_contentDir.$settings->_contentOffsetDir);
if(!$dms->checkVersion()) {
	echo "Database update needed.";
	exit;
}

$dms->setRootFolderID($settings->_rootFolderID);
$dms->setMaxDirID($settings->_maxDirID);
$dms->setEnableConverting($settings->_enableConverting);
$dms->setViewOnlineFileTypes($settings->_viewOnlineFileTypes);

/* Create a global user object */
$user = $dms->getUser(1);

$folderid = 1;//ToDo irgendwo auslesen, damit man auch nur unterordner exportieren kann
$outputFolder = "/var/local/seeddms/git/";//ToDo irgendwo auslesen
$gitRootContainsMainFolder = false;

$folder = $dms->getFolder($folderid);
if (!is_object($folder)) {
	echo "invalid_folder_id";
	exit;
	//UI::exitError(getMLText("admin_tools"),getMLText("invalid_folder_id"));
}
if($gitRootContainsMainFolder)
	$outputFolder.= $folder->getName().'/';

function InsertFile($outputPath, $file, $newName)
{
	echo " copying File: ".$outputPath.$newName;
	return copy($file, $outputPath.$newName);
}

function endsWith($haystack, $needle){
		return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
	}

function fillGitFolder($dmsFolder, $outputPath)
{
	global $dms;
	echo "doing folder ".$dmsFolder->getName()." absolute: ".$outputPath."\r\n";
	if (!file_exists($outputPath)) {
		mkdir($outputPath, 0777, true);
	}
	$documents=$dmsFolder->getDocuments();
	
	foreach ($documents as $document){
		if (file_exists($dms->contentDir.$document->getDir())){
			// create an archive containing the files with original names and DMS path
			// thanks to Doudoux
			$latestContent = $document->getLatestContent();
			if (is_object($latestContent)) {
				if(endsWith($document->getName(),$latestContent->getFileType()))
					$name = $document->getName();
				else
					$name = $document->getName().$latestContent->getFileType();
				if (!InsertFile($outputPath, $dms->contentDir.$latestContent->getPath(),$name))
					return false;
			}
		}
	}

	$subFolders=$dmsFolder->getSubfolders();
	foreach ($subFolders as $folder)
		if (!fillGitFolder($folder,$outputPath.basename($folder->getName()).'/'))
			return false;
	return true;
}
if (!fillGitFolder($folder,$outputFolder)) {
	echo "error_occured";
	print_r(error_get_last()."\r\n");
	exit();
	//UI::exitError(getMLText("admin_tools"),getMLText("error_occured"));
}
print_r("\r\n");
?>