<?php
/**
 * Implementation of various file system operations
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class to represent a user in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_File {
	static function renameFile($old, $new) { /* {{{ */
		return @rename($old, $new);
	} /* }}} */

	static function removeFile($file) { /* {{{ */
		return @unlink($file);
	} /* }}} */

	static function copyFile($source, $target) { /* {{{ */
		return @copy($source, $target);
	} /* }}} */

	static function moveFile($source, $target) { /* {{{ */
		if (!@copyFile($source, $target))
			return false;
		return @removeFile($source);
	} /* }}} */

	static function fileSize($file) { /* {{{ */
		if(!$a = fopen($file, 'r'))
			return false;
		fseek($a, 0, SEEK_END);
		$filesize = ftell($a);
		fclose($a);
		return $filesize;
	} /* }}} */

	static function format_filesize($size, $sizes = array('Bytes', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB')) { /* {{{ */
		if ($size == 0) return('0 Bytes');
		return (round($size/pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $sizes[$i]);
	} /* }}} */

	static function parse_filesize($str) { /* {{{ */
		preg_replace('/\s\s+/', ' ', $str);
		if(strtoupper(substr($str, -1)) == 'B') {
			$value = (int) substr($str, 0, -2);
			$unit = substr($str, -2, 1);
		} else {
			$value = (int) substr($str, 0, -1);
			$unit = substr($str, -1);
		}
		switch(strtoupper($unit)) {
			case 'G':
				return $value * 1024 * 1024 * 1024;
				break;
			case 'M':
				return $value * 1024 * 1024;
				break;
			case 'K':
				return $value * 1024;
				break;
			default;
				return $value;
				break;
		}
		return false;
	} /* }}} */

	static function checksum($file) { /* {{{ */
		return md5_file($file);
	} /* }}} */

	static function renameDir($old, $new) { /* {{{ */
		return @rename($old, $new);
	} /* }}} */

	static function makeDir($path) { /* {{{ */
		
		if( !is_dir( $path ) ){
			$res=@mkdir( $path , 0777, true);
			if (!$res) return false;
		}

		return true;

/* some old code 
		if (strncmp($path, DIRECTORY_SEPARATOR, 1) == 0) {
			$mkfolder = DIRECTORY_SEPARATOR;
		}
		else {
			$mkfolder = "";
		}
		$path = preg_split( "/[\\\\\/]/" , $path );
		for(  $i=0 ; isset( $path[$i] ) ; $i++ )
		{
			if(!strlen(trim($path[$i])))continue;
			$mkfolder .= $path[$i];

			if( !is_dir( $mkfolder ) ){
				$res=@mkdir( "$mkfolder" ,  0777);
				if (!$res) return false;
			}
			$mkfolder .= DIRECTORY_SEPARATOR;
		}

		return true;

		// patch from alekseynfor safe_mod or open_basedir

		global $settings;
		$path = substr_replace ($path, "/", 0, strlen($settings->_contentDir));
		$mkfolder = $settings->_contentDir;

		$path = preg_split( "/[\\\\\/]/" , $path );

		for(  $i=0 ; isset( $path[$i] ) ; $i++ )
		{
			if(!strlen(trim($path[$i])))continue;
			$mkfolder .= $path[$i];

			if( !is_dir( $mkfolder ) ){
				$res= @mkdir( "$mkfolder" ,  0777);
				if (!$res) return false;
			}
			$mkfolder .= DIRECTORY_SEPARATOR;
		}

		return true;
*/
	} /* }}} */

	static function removeDir($path) { /* {{{ */
		$handle = @opendir($path);
		while ($entry = @readdir($handle) )
		{
			if ($entry == ".." || $entry == ".")
				continue;
			else if (is_dir($path . $entry))
			{
				if (!self::removeDir($path . $entry . "/"))
					return false;
			}
			else
			{
				if (!@unlink($path . $entry))
					return false;
			}
		}
		@closedir($handle);
		return @rmdir($path);
	} /* }}} */

	static function copyDir($sourcePath, $targetPath) { /* {{{ */
		if (mkdir($targetPath, 0777)) {
			$handle = @opendir($sourcePath);
			while ($entry = @readdir($handle) ) {
				if ($entry == ".." || $entry == ".")
					continue;
				else if (is_dir($sourcePath . $entry)) {
					if (!self::copyDir($sourcePath . $entry . "/", $targetPath . $entry . "/"))
						return false;
				} else {
					if (!@copy($sourcePath . $entry, $targetPath . $entry))
						return false;
				}
			}
			@closedir($handle);
		}
		else
			return false;

		return true;
	} /* }}} */

	static function moveDir($sourcePath, $targetPath) { /* {{{ */
		if (!copyDir($sourcePath, $targetPath))
			return false;
		return removeDir($sourcePath);
	} /* }}} */

	// code by Kioob (php.net manual)
	static function gzcompressfile($source,$level=false) { /* {{{ */
		$dest=$source.'.gz';
		$mode='wb'.$level;
		$error=false;
		if($fp_out=@gzopen($dest,$mode)) {
			if($fp_in=@fopen($source,'rb')) {
				while(!feof($fp_in))
					@gzwrite($fp_out,fread($fp_in,1024*512));
				@fclose($fp_in);
			}
			else $error=true;
			@gzclose($fp_out);
		}
		else $error=true;

		if($error) return false;
		else return $dest;
	} /* }}} */
}
?>
