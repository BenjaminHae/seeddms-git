<?php
/**
 * Implementation of Login view
 *
 * @category   DMS
 * @package    SeedDMS
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
require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for Login view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Login extends SeedDMS_Bootstrap_Style {

	function show() { /* {{{ */
		$enableguestlogin = $this->params['enableguestlogin'];
		$enablepasswordforgotten = $this->params['enablepasswordforgotten'];
		$refer = $this->params['referrer'];
		$themes = $this->params['themes'];
		$languages = $this->params['languages'];
		$enableLanguageSelector = $this->params['enablelanguageselector'];
		$enableThemeSelector = $this->params['enablethemeselector'];

		$this->htmlStartPage(getMLText("sign_in"), "login");
		$this->globalBanner();
		$this->contentStart();
		$this->pageNavigation(getMLText("sign_in"));
?>
<script language="JavaScript">
function checkForm()
{
	msg = new Array()
	if (document.form1.login.value == "") msg.push("<?php printMLText("js_no_login");?>");
	if (document.form1.pwd.value == "") msg.push("<?php printMLText("js_no_pwd");?>");
	if (msg != "") {
  	noty({
  		text: msg.join('<br />'),
  		type: 'error',
      dismissQueue: true,
  		layout: 'topRight',
  		theme: 'defaultTheme',
			_timeout: 1500,
  	});
		return false;
	}
	else
		return true;
}

function guestLogin()
{
	url = "../op/op.Login.php?login=guest" + 
		"&sesstheme=" + document.form1.sesstheme.options[document.form1.sesstheme.options.selectedIndex].value +
		"&lang=" + document.form1.lang.options[document.form1.lang.options.selectedIndex].value;
	if (document.form1.referuri) {
		url += "&referuri=" + escape(document.form1.referuri.value);
	}
	document.location.href = url;
}

</script>
<?php $this->contentContainerStart(); ?>
<form class="form-horizontal" action="../op/op.Login.php" method="post" name="form1" onsubmit="return checkForm();">
<?php
		if ($refer) {
			echo "<input type='hidden' name='referuri' value='".sanitizeString($refer)."'/>";
		}
?>
	<div class="control-group">
		<label class="control-label" for="login"><?php printMLText("user_login");?>:</label>
		<div class="controls">
			<input type="text" id="login" name="login" placeholder="login">
		</div>
	</div>
	<div class="control-group">
		<label class="control-label" for="pwd"><?php printMLText("password");?>:</label>
		<div class="controls">
			<input type="Password" id="pwd" name="pwd">
		</div>
	</div>
<?php if($enableLanguageSelector) { ?>
	<div class="control-group">
		<label class="control-label" for="pwd"><?php printMLText("language");?>:</label>
		<div class="controls">
<?php
			print "<select name=\"lang\">";
			print "<option value=\"\">-";
			foreach ($languages as $currLang) {
				print "<option value=\"".$currLang."\">".getMLText($currLang)."</option>";
			}
			print "</select>";
?>
		</div>
	</div>
<?php
	}
	if($enableThemeSelector) {
?>
	<div class="control-group">
		<label class="control-label" for="pwd"><?php printMLText("theme");?>:</label>
		<div class="controls">
			
<?php
			print "<select name=\"sesstheme\">";
			print "<option value=\"\">-";
			foreach ($themes as $currTheme) {
				print "<option value=\"".$currTheme."\">".$currTheme;
			}
			print "</select>";
?>
		</div>
	</div>
<?php
	}
?>
	<div class="control-group">
		<div class="controls">
		<button type="submit" class="btn"><?php printMLText("submit_login") ?></button>
		</div>
	</div>
		
</form>
<?php
		$this->contentContainerEnd();
		$tmpfoot = array();
		if ($enableguestlogin)
			$tmpfoot[] = "<a href=\"javascript:guestLogin()\">" . getMLText("guest_login") . "</a>\n";
		if ($enablepasswordforgotten)
			$tmpfoot[] = "<a href=\"../out/out.PasswordForgotten.php\">" . getMLText("password_forgotten") . "</a>\n";
		if($tmpfoot) {
			print "<p>";
			print implode(' | ', $tmpfoot);
			print "</p>\n";
		}
?>
<script language="JavaScript">document.form1.login.focus();</script>
<?php
		$this->htmlEndPage();
	} /* }}} */
}
?>
