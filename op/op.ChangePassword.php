<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005 Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010-2011 Uwe Steinmann
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
include("../inc/inc.LogInit.php");
include("../inc/inc.Utils.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassSession.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassEmail.php");

function _printMessage($heading, $message) {

	UI::htmlStartPage($heading, "password");
	UI::globalBanner();
	UI::pageNavigation($heading);
	UI::contentContainer($message."<p><a href=\"../out/out.Login.php\">" . getMLText("login") . "</a></p>\n");
			UI::htmlEndPage();
	return;
}

if (isset($_POST["hash"])) {
	$hash = sanitizeString($_POST["hash"]);
}
if (isset($_POST["newpassword"])) {
	$newpassword = sanitizeString($_POST["newpassword"]);
}
if (isset($_POST["newpasswordrepeat"])) {
	$newpasswordrepeat = sanitizeString($_POST["newpasswordrepeat"]);
}

if (empty($newpassword) || empty($newpasswordrepeat) || $newpassword != $newpasswordrepeat) {
	_printMessage(getMLText("password_mismatch_error_title"),	"<p>".getMLText("password_mismatch_error")."</p>\n");
	exit;
}

$user = $dms->checkPasswordRequest($hash);
if($user) {
	$user->setPwd(md5($newpassword));
	$dms->deletePasswordRequest($hash);
}

_printMessage(getMLText("change_password"), "<p>".getMLText("change_password_message")."</p>");
exit;
?>

