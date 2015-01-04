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
//ToDo: dateinamen einheitlich erzeugen(insbes. mit erweiterung!)
 
/**
 * Class to save documents to a git repository
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Benjamin Häublein <benjaminhaeublein@gmail.com>
 * @copyright  Copyright (C) 2014 Benjamin Häublein
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
	 * @const string pipe for shell_exec
	 */
	const _PIPE = " 2>&1";
	/**
	 * @const bool are git repositorys setup in the main directory (e.g. /var/local/seeddms/git/) = false or for every subdirectory individually = true
	 */
	const _GITINSUB = true;
	/**
	 * @const bool be verbose in log for debugging
	 */
	const _VERBOSE = true;
	
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
	 *@var object reference to attribute containing ignore information
	 *
	 *@access protected
	 */
	var $_attributObject = NULL;
	
	/**
	 *@var array of string array of paths used for git commands
	 *
	 *@access protected
	 */
	var $_paths = array();
	
	/**
	 * @var string path path of git directory, including trailingPathDelimiter
	*/
	function SeedDMS_Core_Git_Export($path) { /* {{{ */
		$this->_path = $path.'/';
		$this->_dms = null;
		$this->_gitChanged = false;
		$this->_gitCommitMessage = date("Y-m-d\r\n");
		if (!self::_GITINSUB)
			$this->_paths[] = "";
	} /* }}} */

	function setDMS($dms) { /* {{{ */
		$this->_dms = $dms;
	} /* }}} */
	
	function setGitChanged($value){
		$this->_gitChanged = $value;
		if ($this->_gitChanged)
			$this->gitCommit($this->_gitCommitMessage);
	}
	
	function endsWith($haystack, $needle){
		return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
	}
	
	function DocumentGetGitPath($document){
		return $this->FolderGetGitFullPath($document->getFolder());
	}
	
	function DocumentGetGitFileName($document, $latestContent=NULL){
		return DocumentGetGitFileNameX($document->getName(),$document,$latestContent);
	}
	
	function DocumentGetGitFileNameX($name, $document, $latestContent=NULL){
		if($latestContent==NULL)
			$latestContent = $document->getLatestContent();
		//Independent of case
		if ($this->endsWith(strtolower($name), strtolower($latestContent->getFileType())))
			return $name;
		return $name.$latestContent->getFileType();
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
	  if ($document->getAttributeValue($this->Attribute()) == "true")
	    return false;
	  $curr = $document->getFolder();
	  return $this->belongsFolderToRepository($curr);
	}
	
	function belongsFolderToRepository($folder){
	  $curr = $folder;
	  while (true){
	    if (!$curr)
	      break;
	    if ($curr->getAttributeValue($this->Attribute()) == "true")
	      return false;
	    if (!isset($curr->_parentID) || ($curr->_parentID == "") || ($curr->_parentID == 0) || ($curr->_id == $curr->_dms->rootFolderID)) 
	      break;
	    $curr = $curr->getParent();
	  }
	  return true;
	}	
	
	private function Attribute(){
	  if ($this->_attributObject == NULL){
	    $this->_attributObject = $this->_dms->getAttributeDefinitionByName(self::_REPOATTRIBUTE);
	  }
	  return $this->_attributObject;
	}
	
	function addDocument($document){
		//datei speichern und xml informationen schreiben
		return $this->addDocumentContent($document);
	}
	
	function addDocumentContent($document){//todo: alter content bleibt beibehalten, wenn sich Dateityp ändert
		if (!$this->belongsFileToRepository($document)){
			$this->log($this->DocumentGetCorePath($document)." is set to ignoreInGit");
			return false;
		}
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
		else{
				$this->log("addDocumentContent: destinationPath doesn't exist", PEAR_LOG_ERR);
		}
		return false;
	}
	
	function renameDocument($document, $oldname, $newname){
		if (!$this->belongsFileToRepository($document)){
			$this->log($this->DocumentGetCorePath($document)." is set to ignoreInGit");
			return false;
		}
		//$this->log($this->getGitStatus());
		$documentPath = $this->DocumentGetGitPath($document);
		$oldGitFile = $documentPath.'/'.$this->DocumentGetGitFileNameX($oldname, $document);
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
			$this->log("renameDocument failed, source file doesn't exist. Now adding document fresh.");
			return $this->addDocumentContent($document);
		}
		return false;
	}
	
	function renameFolder($folder, $oldname, $newname){
		if (!$this->belongsFolderToRepository($folder)){
			$this->log($this->DocumentGetCorePath($folder)." is set to ignoreInGit");
			return false;
		}
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
		if (!$this->belongsFileToRepository($document)){
			$this->log($this->DocumentGetCorePath($document)." is set to ignoreInGit");
			return false;
		}
		$this->gitRemove($this->DocumentGetGitFullPath($document,$latestContent));
		$this->_gitCommitMessage .= "removed File ".$document->getName()."\r\n";
		return true;
	}
	
	function removeFolder($folder){
		if (!$this->belongsFolderToRepository($folder)){
			$this->log($this->DocumentGetCorePath($folder)." is set to ignoreInGit");
			return false;
		}
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
		if (!$this->belongsFileToRepository($document)){
			$this->log($this->DocumentGetCorePath($document)." is set to ignoreInGit");
			return false;
		}
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
		else{
			$this->log("moveDocument failed, source file doesn't exist. Now adding document fresh.");
			return $this->addDocumentContent($document);
		}
		return false;
	}
	
	function moveFolder($folder, $oldparent, $newparent){
		$this->log("Kann Ordner noch nicht bewegen", PEAR_LOG_WARN);
		return false;
		if (!$this->belongsFolderToRepository($folder))
		  return false;
		//rename();
	}
	
	function setAttribute($object, $attribName, $attribValue){
		return false;
		if ($attribName!=self::_REPOATTRIBUTE)
		  return false;
		if ($attribValue=="true"){
		  $path = "TODO";
		  $this->gitRemove($path, true);
		}
		else{
		  //ToDo: Git Add Folder/File if belongs
		}
	}
		
	private function log($msg, $priority = null){
		global $logger;
		if(trim($msg)!=""){
			if(is_object($logger))
				$logger->log("Git"." (".$_SERVER['REMOTE_ADDR'].") ".basename($_SERVER["REQUEST_URI"], ".php")." ".$msg, $priority);
		}
	}
	
	private function gitCommand($path){
		$sub = "";
		if(self::_GITINSUB){
			$p_len = strlen($this->_path);
			$sub = substr($path,$p_len,strpos($path,"/",$p_len)-$p_len);
			if(!in_array($sub,$this->_paths)){
				$this->_paths[] = $sub;
				if (self::_VERBOSE)
					$this->log("added ".$sub." to _paths");
			}
		}
		return $this->gitCommandAbs($sub);
	}
	
	private function gitCommandAbs($sub){
		return "git --git-dir=".escapeshellarg($this->_path.$sub."/.git")." --work-tree=".escapeshellarg($this->_path.$sub)." ";
	}
	
	function gitAdd($path){
		$this->log(shell_exec($this->gitCommand($path)."add ".escapeshellarg($path).self::_PIPE));
		$this->setGitChanged(true);
	}
	
	function gitRemove($path, $recurse=false){
		if ($recurse==false){
			$rec = "";
		}
		else
			$res = "-r ";
		$this->log(shell_exec($this->gitCommand($path)."rm ".$rec.escapeshellarg($path).self::_PIPE));
		$this->setGitChanged(true);
	}
	
	function gitCommit($commitMessage){
		// git commit
		$this->log(print_r($this->_paths));
		foreach($this->_paths as $path){
			$gc = $this->gitCommandAbs($path);
			if(self::_VERBOSE){
				$this->log("committing ".$path);
				$this->log($gc);
			}
			$this->log(shell_exec($gc."commit -m ".escapeshellarg($commitMessage).self::_PIPE));
		}
		$this->setGitChanged(false);
	}
	
	function gitAbort(){
		$this->log(shell_exec($this->gitCommand()."rm -r --cached ".escapeshellarg($this->_path."/.").self::_PIPE));
		$this->_gitCommitMessage = date("Y-m-d\r\n");
		$this->setGitChanged(false);
	}
	
	function forceDirectories($path){
		if (!file_exists($path)) {
			mkdir($path, 0777, true);//ToDo Berechtigungen
		}
	}
	
	function __destruct() {
		if (self::_VERBOSE)
			$this->log("desctruct");
		if ($this->_gitChanged){
			$this->gitCommit($this->_gitCommitMessage);
		}
   }
}
?>
