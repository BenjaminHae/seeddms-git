<?php
/**
 * Implementation of ApproveDocument view
 *
 * @category   DMS
 * @package    LetoDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
require_once("class.BlueStyle.php");

/**
 * Class which outputs the html page for ApproveDocument view
 *
 * @category   DMS
 * @package    LetoDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class LetoDMS_View_ApproveDocument extends LetoDMS_Blue_Style {

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];

		$latestContent = $document->getLatestContent();
		$approvals = $latestContent->getApprovalStatus();

		foreach($approvals as $approval) {
			if($approval['approveID'] == $_GET['approveid']) {
				$approvalStatus = $approval;
				break;
			}
		}

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->pageNavigation(getFolderPathHTML($folder, true, $document), "view_document");
		$this->contentHeading(getMLText("submit_approval"));
?>
<script language="JavaScript">
function checkIndForm()
{
	msg = "";
	if (document.form1.approvalStatus.value == "") msg += "<?php printMLText("js_no_approval_status");?>\n";
	if (document.form1.comment.value == "") msg += "<?php printMLText("js_no_comment");?>\n";
	if (msg != "")
	{
		alert(msg);
		return false;
	}
	else
		return true;
}
function checkGrpForm()
{
	msg = "";
//	if (document.form1.approvalGroup.value == "") msg += "<?php printMLText("js_no_approval_group");?>\n";
	if (document.form1.approvalStatus.value == "") msg += "<?php printMLText("js_no_approval_status");?>\n";
	if (document.form1.comment.value == "") msg += "<?php printMLText("js_no_comment");?>\n";
	if (msg != "")
	{
		alert(msg);
		return false;
	}
	else
		return true;
}
</script>

<?php

		$this->contentContainerStart();

		// Display the Approval form.
		if ($approvalStatus['type'] == 0) {
			if($approvalStatus["status"]!=0) {

				print "<table class=\"folderView\"><thead><tr>";
				print "<th>".getMLText("status")."</th>";
				print "<th>".getMLText("comment")."</th>";
				print "<th>".getMLText("last_update")."</th>";
				print "</tr></thead><tbody><tr>";
				print "<td>";
				printApprovalStatusText($approvalStatus["status"]);
				print "</td>";
				print "<td>".htmlspecialchars($approvalStatus["comment"])."</td>";
				$indUser = $dms->getUser($approvalStatus["userID"]);
				print "<td>".$approvalStatus["date"]." - ". $indUser->getFullname() ."</td>";
				print "</tr></tbody></table><br>\n";
			}
?>
	<form method="POST" action="../op/op.ApproveDocument.php" name="form1" onsubmit="return checkIndForm();">
	<table>
	<tr><td><?php printMLText("comment")?>:</td>
	<td><textarea name="comment" cols="80" rows="4"></textarea>
	</td></tr>
	<tr><td><?php printMLText("approval_status")?>:</td>
	<td><select name="approvalStatus">
	<option value=''></option>
	<option value='1'><?php printMLText("status_approved")?></option>
	<option value='-1'><?php printMLText("rejected")?></option>
	</select>
	</td></tr><tr><td></td><td>
	<input type='hidden' name='approvalType' value='ind'/>
	<input type='hidden' name='documentid' value='<?php echo $document->getId() ?>'/>
	<input type='hidden' name='version' value='<?php echo $version ?>'/>
	<input type='submit' name='indApproval' value='<?php printMLText("submit_approval")?>'/>
	</td></tr></table>
	</form>
<?php
		}
		else if ($approvalStatus['type'] == 1) {

			if($approvalStatus["status"]!=0) {

				print "<table class=\"folderView\"><thead><tr>";
				print "<th>".getMLText("status")."</th>";
				print "<th>".getMLText("comment")."</th>";
				print "<th>".getMLText("last_update")."</th>";
				print "</tr></thead><tbody><tr>";
				print "<td>";
				printApprovalStatusText($approvalStatus["status"]);
				print "</td>";
				print "<td>".htmlspecialchars($approvalStatus["comment"])."</td>";
				$indUser = $dms->getUser($approvalStatus["userID"]);
				print "<td>".$approvalStatus["date"]." - ". htmlspecialchars($indUser->getFullname()) ."</td>";
				print "</tr></tbody></table><br>\n";
			}

?>
	<form method="POST" action="../op/op.ApproveDocument.php" name="form1" onsubmit="return checkGrpForm();">
	<table>
	<tr><td><?php printMLText("comment")?>:</td>
	<td><textarea name="comment" cols="80" rows="4"></textarea>
	</td></tr>
	<tr><td><?php printMLText("approval_status")?>:</td>
	<td>
	<select name="approvalStatus">
	<option value=''></option>
	<option value='1'><?php printMLText("status_approved")?></option>
	<option value='-1'><?php printMLText("rejected")?></option>
	</select>
	</td></tr>
	<tr><td></td><td>
	<input type='hidden' name='approvalGroup' value="<?php echo $approvalStatus['required']; ?>" />
	<input type='hidden' name='approvalType' value='grp'/>
	<input type='hidden' name='documentid' value='<?php echo $document->getId() ?>'/>
	<input type='hidden' name='version' value='<?php echo $version ?>'/>
	<input type='submit' name='groupApproval' value='<?php printMLText("submit_approval")?>'/></td></tr>
	</table>
	</form>
<?php
		}

		$this->contentContainerEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
