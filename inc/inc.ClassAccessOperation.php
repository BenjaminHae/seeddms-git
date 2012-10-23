<?php
/**
 * Implementation of access restricitions
 *
 * @category   DMS
 * @package    LetoDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class to check certain access restrictions
 *
 * @category   DMS
 * @package    LetoDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class LetoDMS_AccessOperation {
	/**
	 * @var object $obj object being accessed
	 * @access protected
	 */
	private $obj;

	/**
	 * @var object $user user requesting the access
	 * @access protected
	 */
	private $user;

	/**
	 * @var object $settings LetoDMS Settings
	 * @access protected
	 */
	private $settings;

	function __construct($obj, $user, $settings) { /* {{{ */
		$this->obj = $obj;
		$this->user = $user;
		$this->settings = $settings;
	} /* }}} */

	/**
	 * Check if removal of version is allowed
	 *
	 * This check can only be done for documents. Removal of versions is
	 * only allowed if this is turned on in the settings and there are
	 * at least 2 versions avaiable. Everybody with write access on the
	 * document may delete versions. The admin may even delete a version
	 * even if is disallowed in the settings.
	 */
	function mayRemoveVersion() { /* {{{ */
		if(get_class($this->obj) == 'LetoDMS_Core_Document') {
			$versions = $this->obj->getContent();
			if ((($this->settings->_enableVersionDeletion && ($this->obj->getAccessMode($this->user) == M_ALL)) || $this->user->isAdmin() ) && (count($versions) > 1)) {
				return true;
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if document status may be overwritten
	 *
	 * This check can only be done for documents. Overwriting the document
	 * status is
	 * only allowed if this is turned on in the settings and the current
	 * status is either 'releaѕed' or 'obsoleted'.
	 * The admin may even modify the status
	 * even if is disallowed in the settings.
	 */
	function mayOverwriteStatus() { /* {{{ */
		if(get_class($this->obj) == 'LetoDMS_Core_Document') {
			$latestContent = $this->obj->getLatestContent();
			$status = $latestContent->getStatus();
			if ((($this->settings->_enableVersionModification && ($this->obj->getAccessMode($this->user) == M_ALL)) || $this->user->isAdmin()) && ($status["status"]==S_RELEASED || $status["status"]==S_OBSOLETE )) {
				return true;
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if reviewers/approvers may be edited
	 *
	 * This check can only be done for documents. Overwriting the document
	 * reviewers/approvers is only allowed if version modification is turned on
	 * in the settings and the document is in 'draft review' status.  The
	 * admin may even set reviewers/approvers even if is disallowed in the
	 * settings.
	 */
	function maySetReviewersApprovers() { /* {{{ */
		if(get_class($this->obj) == 'LetoDMS_Core_Document') {
			$latestContent = $this->obj->getLatestContent();
			$status = $latestContent->getStatus();
			if ((($this->settings->_enableVersionModification && ($this->obj->getAccessMode($this->user) == M_ALL)) || $this->user->isAdmin()) && ($status["status"]==S_DRAFT_REV)) {
				return true;
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if expiration date may be set
	 *
	 * This check can only be done for documents. Setting the documents
	 * expiration date is only allowed if version modification is turned on in
	 * the settings and the document has not been obsoleted.
	 * The admin may set the expiration date even if is
	 * disallowed in the settings.
	 */
	function maySetExpires() { /* {{{ */
		if(get_class($this->obj) == 'LetoDMS_Core_Document') {
			$latestContent = $this->obj->getLatestContent();
			$status = $latestContent->getStatus();
			if ((($this->settings->_enableVersionModification && ($this->obj->getAccessMode($this->user) == M_ALL)) || $this->user->isAdmin()) && ($status["status"]!=S_OBSOLETE)) {
				return true;
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if comment may be edited
	 *
	 * This check can only be done for documents. Setting the documents
	 * comment date is only allowed if version modification is turned on in
	 * the settings and the document has not been obsoleted.
	 * The admin may set the comment even if is
	 * disallowed in the settings.
	 */
	function mayEditComment() { /* {{{ */
		if(get_class($this->obj) == 'LetoDMS_Core_Document') {
			$latestContent = $this->obj->getLatestContent();
			$status = $latestContent->getStatus();
			if ((($this->settings->_enableVersionModification && ($this->obj->getAccessMode($this->user) >= M_READWRITE)) || $this->user->isAdmin()) && ($status["status"]!=S_OBSOLETE)) {
				return true;
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if attributes may be edited
	 *
	 * Setting the object attributes
	 * is only allowed if version modification is turned on in
	 * the settings and the document has not been obsoleted.
	 * The admin may set the comment even if is
	 * disallowed in the settings.
	 */
	function mayEditAttributes() { /* {{{ */
		if(get_class($this->obj) == 'LetoDMS_Core_Document') {
			$latestContent = $this->obj->getLatestContent();
			$status = $latestContent->getStatus();
			if ((($this->settings->_enableVersionModification && ($this->obj->getAccessMode($this->user) >= M_READWRITE)) || $this->user->isAdmin()) && ($status["status"]==S_DRAFT_REV)) {
				return true;
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if document content may be reviewed
	 *
	 * Reviewing a document content is only allowed if the document was not
	 * obsoleted. There are other requirements which are not taken into
	 * account here.
	 */
	function mayReview() { /* {{{ */
		if(get_class($this->obj) == 'LetoDMS_Core_Document') {
			$latestContent = $this->obj->getLatestContent();
			$status = $latestContent->getStatus();
			if ($status["status"]!=S_OBSOLETE) {
				return true;
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if document content may be approved
	 *
	 * Approving a document content is only allowed if the document was not
	 * obsoleted. There are other requirements which are not taken into
	 * account here.
	 */
	function mayApprove() { /* {{{ */
		if(get_class($this->obj) == 'LetoDMS_Core_Document') {
			$latestContent = $this->obj->getLatestContent();
			$status = $latestContent->getStatus();
			if ($status["status"]!=S_OBSOLETE) {
				return true;
			}
		}
		return false;
	} /* }}} */
}
?>
