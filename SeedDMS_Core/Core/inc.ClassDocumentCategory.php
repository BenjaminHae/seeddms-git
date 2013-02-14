<?php
/**
 * Implementation of document categories in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class to represent a document category in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C)2011 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_DocumentCategory {
	/**
	 * @var integer $_id id of document category
	 * @access protected
	 */
	protected $_id;

	/**
	 * @var string $_name name of category
	 * @access protected
	 */
	protected $_name;

	/**
	 * @var object $_dms reference to dms this category belongs to
	 * @access protected
	 */
	protected $_dms;

	function SeedDMS_Core_DocumentCategory($id, $name) { /* {{{ */
		$this->_id = $id;
		$this->_name = $name;
		$this->_dms = null;
	} /* }}} */

	function setDMS($dms) { /* {{{ */
		$this->_dms = $dms;
	} /* }}} */

	function getID() { return $this->_id; }

	function getName() { return $this->_name; }

	function setName($newName) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblCategory SET name = ".$db->qstr($newName)." WHERE id = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_name = $newName;
		return true;
	} /* }}} */

	function isUsed() { /* {{{ */
		$db = $this->_dms->getDB();
		
		$queryStr = "SELECT * FROM tblDocumentCategory WHERE categoryID=".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_array($resArr) && count($resArr) == 0)
			return false;
		return true;
	} /* }}} */

	function getCategories() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM tblCategory";
		return $db->getResultArray($queryStr);
	} /* }}} */

	function addCategory($keywords) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "INSERT INTO tblCategory (category) VALUES (".$db->qstr($keywords).")";
		return $db->getResult($queryStr);
	} /* }}} */

	function remove() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "DELETE FROM tblCategory WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		return true;
	} /* }}} */

	function getDocumentsByCategory() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM tblDocumentCategory where categoryID=".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$documents = array();
		foreach ($resArr as $row) {
			array_push($documents, $this->_dms->getDocument($row["id"]));
		}
		return $documents;
	} /* }}} */

}

?>
