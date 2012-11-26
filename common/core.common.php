<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2012, BlackLight, TND Team, http://tndproject.org
//
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU Affero General Public License as
//	published by the Free Software Foundation, either version 3 of the
//	License, or (at your option) any later version.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU Affero General Public License for more details.
//
//	You should have received a copy of the GNU Affero General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

define("MULTIMAG_REV", "447");
define("MULTIMAG_VERSION", "0.1r".MULTIMAG_REV);

/// Файл содержит код, используемый как web, так и cli скриптами

function mailto($email, $subject, $msg, $from="")
{
	global $CONFIG;
	require_once($CONFIG['location'].'/common/email_message.php');

	$email_message=new email_message_class();
	$email_message->default_charset="UTF-8";
	$email_message->SetEncodedEmailHeader("To", $email, $email);
	$email_message->SetEncodedHeader("Subject", $subject);
	if($from)	$email_message->SetEncodedEmailHeader("From", $from, $from);
	else		$email_message->SetEncodedEmailHeader("From", $CONFIG['site']['admin_email'], "Почтовый робот {$CONFIG['site']['name']}");
	$email_message->SetHeader("Sender",$CONFIG['site']['admin_email']);
	$email_message->AddQuotedPrintableTextPart($msg);
	$error=$email_message->Send();

	if(strcmp($error,""))	throw new Exception($error);
	else			return 0;
}
/// @param times - время в секундах
/// возвращает строковое представление интервала
function sectostrinterval($times)
{
	$ret=($times%60).' с.';
	$times=round($times/60);
	if(!$times)	return $ret;
	$ret=($times%60).' м. '.$ret;
	$times=round($times/60);
	if(!$times)	return $ret;
	$ret=$times.' ч. '.$ret;
	return $ret;
}


?>