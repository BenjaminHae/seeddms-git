<?php
/**
 * Implementation of the workflow object in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

//ToDo: alle Bewegungen innerhalb des git-repos mit git machen!
//ToDo: Logging in eigene Datei
//ToDo: Attribute Objekt bekommen!
 
/**
 * Class to represent an workflow in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
 
setlocale(LC_CTYPE, "en_US.UTF-8");
 
class SeedDMS_Core_Git_Export { /* {{{ */
	/**
	 * @const bool if true in the git directory every file from the DMS is inside another folder
	 */
	const _ROOTCONTAINSMAINFOLDER = false;
	/**
	 * @const string name of attribute which decides whether a file should be in repo 
	 */
	 const _REPOATTRIBUTE = "ignoreInGit";
	/**
	 * @var string path of git directory, including trailing path delimiter
	 *
	 * @access protected
	 */
	var $_path;

	/**
	 * @var object reference to the dms instance this attribute belongs to
	 *
	 * @access protected
	 */
	var $_dms;
	
	
	/**
	 *@var object reference to the git instance
	 *
	 *@access protected
	 */
	var $_gitRepo;
	
	
	/**
	 *@var boolean true if files in git repository have been added/removed
	 *
	 *@access protected
	 */
	var $_gitChanged;
	

	/**
	 *@var string Commit Message for Git Repository
	 *
	 *@access protected
	 */
	var $_gitCommitMessage;
	
	/**
	 * @var string path path of git directory, including trailingPathDelimiter
	*/
	function SeedDMS_Core_Git_Export($path) { /* {{{ */
		$this->_path = $path.'/';
		$this->_dms = null;
		$this->_gitChanged = false;
		$this->_gitCommitMessage = date("Y-m-d\r\n");
	} /* }}} */

	function setDMS($dms) { /* {{{ */
		$this->_dms = $dms;
	} /* }}} */
	
	function endsWith($haystack, $needle){
		return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
	}
	
	function DocumentGetGitPath($document){
		return $this->FolderGetGitFullPath($document->getFolder());
	}
	
	function DocumentGetGitFileName($document, $latestContent=NULL){
		if($latestContent==NULL)
			$latestContent = $document->getLatestContent();
		if ($this->endsWith($document->getName(), $latestContent->getFileType()))
			return $document->getName();
		return $document->getName().$latestContent->getFileType();
	}
	
	function DocumentGetGitFullPath($document, $latestContent=NULL){
		return $this->DocumentGetGitPath($document).'/'.$this->DocumentGetGitFileName($document,$latestContent);
	}
	
	function DocumentGetCorePath($document){
		$latestContent = $document->getLatestContent();
		if (is_object($latestContent))
			return $this->_dms->contentDir.$latestContent->getPath();
		else
			return false;
	}
	
	function FolderGetGitFullPath($folder){
		return $this->_path.$this->FolderGetRelativePath($folder);
	}
	
	function FolderGetRelativePath($folder){
		$path="";
		$folderPath = $folder->getPath();
		$start = 0;
		if(!self::_ROOTCONTAINSMAINFOLDER)
			$start = 1;
		for ($i = 1; $i < count($folderPath); $i++) {
			$path .= $folderPath[$i]->getName();
			if ($i +1 < count($folderPath)){
				$path .= "/";
			}
		}
		printf($folderPath);
		return $path;
	}
	
	function belongsFileToRepository($document){
	  if ($document->getAttributeValue(self::_REPOATTRIBUTE) == "true")
	    return false;
	  $curr = $document->getFolder();
	  return $this->belongsFolderToRepository($curr);
	}
	
	function belongsFolderToRepository($folder){
	  $curr = $folder;
	  while (true){
	    if (!$curr)
	      break;
	    if ($curr->getAttributeValue(self::_REPOATTRIBUTE) == "true")
	      return false;
	    if (!isset($curr->_parentID) || ($curr->_parentID == "") || ($curr->_parentID == 0) || ($curr->_id == $curr->_dms->rootFolderID)) 
	      break;
	    $curr = $curr->getParent();
	  }
	  return true;
	}
	
	function addDocument($document){
		//datei speichern und xml informationen schreiben
		return $this->addDocumentContent($document);
	}
	
	function addDocumentContent($document){//todo: alter content bleibt beibehalten, wenn sich Dateityp ändert
		if (!$this->belongsFileToRepository($document))
		  return false;
		$destinationPath = $this->DocumentGetGitPath($document);
		$this->forceDirectories($destinationPath);
		$this->log("Adding Document ".$document->getName());
		if (file_exists($destinationPath)){		
			$this->log("copying file ".$this->DocumentGetCorePath($document)." to ".$this->DocumentGetGitFullPath($document));
			if (copy($this->DocumentGetCorePath($document),$this->DocumentGetGitFullPath($document))){
				$this->gitAdd($this->DocumentGetGitFullPath($document));
				$this->_gitCommitMessage .= "added File ".$document->getName()."\r\n";
				return true;
			}
			else{
				$this->log(print_r(error_get_last(), true), PEAR_LOG_ERR);
			}
		}
		return false;
	}
	
	function renameDocument($document, $oldname, $newname){
		if (!$this->belongsFileToRepository($document))
		  return false;
		//$this->log($this->getGitStatus());
		$documentPath = $this->DocumentGetGitPath($document);
		$oldGitFile = $documentPath.'/'.$oldname.$document->getLatestContent()->getFileType();
		if (file_exists($oldGitFile)){
			if(rename($oldGitFile, $this->DocumentGetGitFullPath($document))){
				$this->_gitCommitMessage .= "renamed File ".$document->getName()."\r\n";
				$this->gitAdd($this->DocumentGetGitFullPath($document));
				$this->gitRemove($oldGitFile);
				return true;
			}
			else{
				$this->log(print_r(error_get_last(), true), PEAR_LOG_ERR);
			}
		}
		else{
			return $this->addDocumentContent($document);
		}
		return false;
	}
	
	function renameFolder($folder, $oldname, $newname){
		if (!$this->belongsFolderToRepository($folder))
		  return false;
		$newpath = $this->FolderGetRelativePath($folder);//ist das inklusive ordnernamen selbst?
		$oldpath = dirname($newpath)."/".$newname;
		$this->log("Renaming Folder ".$oldpath." to ".$newpath);
		if (is_dir($oldpath)){
			if (rename($oldpath, $newpath)){
				$this->gitAdd($newpath.'/'.'.');
				$this->gitRemove($oldpath,true);
				$this->_gitCommitMessage .= "renamed Folder ".$newname."\r\n";
				return true;
			}
			else{
				$this->log(print_r(error_get_last(), true), PEAR_LOG_ERR);
			}
		}
		else{
			$this->log("Kann noch nicht komplette Ordner neu exportieren", PEAR_LOG_WARN);
			//ToDo alle unterverzeichnisse abspeichern!
		}
	}
	
	function removeDocument($document, $latestContent){
		if (!$this->belongsFileToRepository($document))
		  return false;
		if(unlink($this->DocumentGetGitFullPath($document,$latestContent))){
			$this->gitRemove($this->DocumentGetGitFullPath($document,$latestContent));
			$this->_gitCommitMessage .= "removed File ".$document->getName()."\r\n";
			return true;
		}
		else{
			$this->log(print_r(error_get_last(), true), PEAR_LOG_ERR);
		}
		return false;
	}
	
	function removeFolder($folder){
		if (!$this->belongsFolderToRepository($folder))
		  return false;
		if(unlink($this->FolderGetGitFullPath($folder))){
			$this->gitRemove($this->FolderGetGitFullPath($folder), true);
			$this->_gitCommitMessage .= "removed Folder".$folder->getName()."\r\n";
			return true;
		}
		else{
			$this->log(print_r(error_get_last(), true), PEAR_LOG_ERR);
		}
		return false;
	}
	
	/*
	 * @var object document instance of document
	 * @var integer oldparent id of old parent folder
	 * @var integer newparent id of new parent folder
	*/
	function moveDocument($document, $oldparent, $newparent){
		if (!$this->belongsFileToRepository($document))
		  return false;
		$oldFolder = $this->_dms->getFolder($oldparent);
		$newFolder = $this->_dms->getFolder($newparent);
		$destinationPath = $this->FolderGetGitFullPath($newFolder);
		$this->forceDirectories($destinationPath);
		$originPath = $this->FolderGetGitFullPath($oldFolder);
		$oldGitFile = $originPath.'/'.$this->DocumentGetGitFileName($document);
		$this->log("Moving ".$document->getName()." : from ".$oldGitFile." to ".$this->DocumentGetGitFullPath($document));
		if (file_exists($oldGitFile)){
			$this->log("Moving File Exists ".$document->getName());
			if(rename($oldGitFile, $this->DocumentGetGitFullPath($document))){
				$this->gitRemove($oldGitFile);
				$this->gitAdd($this->DocumentGetGitFullPath($document));
				$this->_gitCommitMessage .= "moved File ".$document->getName()."\r\n";
				return true;
			}
			else{
				$this->log(print_r(error_get_last(), true), PEAR_LOG_ERR);
			}
		}
		else
			return $this->addDocumentContent($document);
		return false;
	}
	
	function moveFolder($folder, $oldparent, $newparent){
		$this->log("Kann Ordner noch nicht bewegen", PEAR_LOG_WARN);
		return false;
		if (!$this->belongsFolderToRepository($folder))
		  return false;
		//rename();
		//xml setzen auch für alle child elemente
	}
	
	function setAttribute($object, $attribName, $attribValue){
		
	}
		
	private function log($msg, $priority = null){
		global $logger;
		if(trim($msg)!=""){
			if(is_object($logger))
				$logger->log("Git"." (".$_SERVER['REMOTE_ADDR'].") ".basename($_SERVER["REQUEST_URI"], ".php")." ".$msg, $priority);
		}
	}
	
	private function gitCommand(){
		return "git --git-dir=".escapeshellarg($this->_path."/.git")." --work-tree=".escapeshellarg($this->_path)." ";
	}
	
	function gitAdd($path){
		$this->log(system($this->gitCommand()."add ".escapeshellarg($path)));
		$this->_gitChanged = true;
	}
	
	function gitRemove($path, $recurse=false){
		if ($recurse==false){
			$rec = "";
		}
		else
			$res = "-r ";
		$this->log(system($this->gitCommand()."rm ".$rec.escapeshellarg($path)));
		$this->_gitChanged = true;
	}
	
	function gitCommit($commitMessage){
		// git commit
		$this->log(system($this->gitCommand()."commit -m ".escapeshellarg($commitMessage)));
		$this->_gitChanged = false;
	}
	
	function gitAbort(){
		system($this->gitCommand()."rm -r --cached ".escapeshellarg($this->_path."/."));
		$this->_gitCommitMessage = date("Y-m-d\r\n");
		$this->_gitChanged = false;
	}
	
	function forceDirectories($path){
		if (!file_exists($path)) {
			mkdir($path, 0777, true);//ToDo Berechtigungen
		}
	}
	
	function __destruct() {
		if ($this->_gitChanged){
			$this->gitCommit($this->_gitCommitMessage);
		}
   }
}
?>
