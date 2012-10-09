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

include_once("core.php");
$to=rcv('to');
$opt=rcv('opt');

if($opt!='email') $opt='jabber';
if($opt=='jabber')
{
	//if($to!=$CONFIG['site']['doc_adm_email'])
		$to=$CONFIG['site']['doc_adm_jid'];
}
else
{
	//if($CONFIG['site']['doc_adm_jid'])
		$to=$CONFIG['site']['doc_adm_email'];
}


if($mode=="")
{
	$nm=@$_SESSION['name'];
	$desc='при помощи Jabber (XMPP)';
	if($opt=='email')	$desc='при помощи эелектронной почты (e-mail)';
	$tmpl->AddText("
	<h1 id='page-title'>Написать сообщение</h1>
	<div id='page-info'>Сообщение будет доставлено на адрес $to $desc</div>
	<form action='' method='post'>
	<input type='hidden' name='mode' value='send'>
	<input type='hidden' name='to' value='$to'>
	<input type='hidden' name='opt' value='$opt'>
	Ваше имя:<br>
	<input type='text' name='nm' value='$nm'><br>
	Адрес для обратной связи (e-mail или jid)<br>
	<input type='text' name='backadr' value=''><br>
	Текст сообщения:<br>
	<textarea name='text' rows='5' cols='40'></textarea><br>
	<b>Не забудте указать информацию для обратной связи!</b><br>
	<input type='submit' value='Отправить'>
	</form>");
}
else if($mode=='send')
{
	$nm=rcv('nm');
	$backadr=rcv('backadr');
	$text=rcv('text');
	$text="Нам написал сообщение $nm($backadr)с сайта {$CONFIG['site']['name']}\n-------------------\n$text\n";
	$text.="-------------------\nIP отправителя: ".getenv("REMOTE_ADDR")."\nSESSION ID:".session_id();
	if(@$_SESSION['name']) $text.="\nLogin отправителя: ".$_SESSION['name'];

	if($opt=='jabber')
	{
		try
		{
			$xmppclient->connect();
			$xmppclient->processUntil('session_start');
			$xmppclient->presence();
			$xmppclient->message($to, $text);
			$xmppclient->disconnect();
			$tmpl->msg("Сообщение было отправлено!","ok");
		}
		catch(XMPPHP_Exception $e)
		{
			$tmpl->logger("Невозможно отправить сообщение XMPP!");
		}
	}
	else
	{
		try
		{
			mailto($to,"Сообщение с сайта {$CONFIG['site']['name']}", $text);
			$tmpl->msg("Сообщение было отправлено!","ok");
		}
		catch(Exception $e)
		{
			$tmpl->logger("Невозможно отправить сообщение email!");
		}
	}
}
else if($mode=='petition')
{
	$doc=rcv('doc');
	settype($doc,"int");
	$tmpl->AddText("<form action='/message.php' method='post'><input type='hidden' name='mode' value='petitions'>
	<input type='hidden' name='doc' value='$doc'><fieldset><legend>Запрос на отмену документа</legend>
	Опишите причину необходимости отмены документа:<br><textarea name='comment'></textarea><br>
	<input type='submit' value='Послать запрос'></fieldset></form>");
}
else if($mode=='petitions')
{
	$doc=rcv('doc');
	settype($doc,"int");
	$comment=rcv('comment');

	if(strlen($comment)>8)
	{
		$res=mysql_query("SELECT `reg_email` FROM `users` WHERE `id`='{$_SESSION['uid']}'");
		echo mysql_error();
		$from=mysql_result($res,0,0);
		if($from=='')	$from=$CONFIG['site']['doc_adm_email'];

		$res=mysql_query("SELECT `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`sum`, `doc_list`.`date`, `doc_agent`.`name`, `doc_types`.`name`
		FROM `doc_list`
		LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
		LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		WHERE `doc_list`.`id`='$doc'");
		echo mysql_error();
		$nxt=mysql_fetch_row($res);

		$date=date("d.m.Y H:i:s",$nxt[3]);

		$txt="Здравствуйте!\nПользователь {$_SESSION['name']} просит Вас отменить проводку документа *$nxt[5]* с ID: $doc, $nxt[0]$nxt[1] от $date на сумму $nxt[2]. Клиент $nxt[4].\n
		{$CONFIG['site']['name']}/doc.php?mode=body&doc=$doc \n
		Цель отмены: $comment.\n IP: $ip\n
		Пожалуйста, дайте ответ на это письмо на $from, как в случае отмены документа, так и об отказе отмены!";

		if($CONFIG['site']['doc_adm_email'])
			mailto($CONFIG['site']['doc_adm_email'], 'Запрос на отмену проведения документа' ,$txt, $from);

		if($CONFIG['site']['doc_adm_jid'])
		{
			try
			{
				$xmppclient->connect();
				$xmppclient->processUntil('session_start');
				$xmppclient->presence();
				$xmppclient->message($CONFIG['site']['doc_adm_jid'], $txt);
				$xmppclient->disconnect();
				$tmpl->msg("Сообщение было отправлено уполномоченному лицу! Ответ о снятии проводки придёт вам на e-mail!","ok");
			}
			catch(XMPPHP_Exception $e)
			{
				$tmpl->logger("Невозможно отправить сообщение по XMPP!","err");
			}
		}
	}
	else $tmpl->msg("Опишите причину поподробнее!");
}
else if($mode=='qmsgr')
{
	$tmpl->ajax=1;
	if($uid)
	{
		$res=mysql_query("SELECT `id`, `sender`, `head`, `msg` FROM `messages` WHERE `user`='$uid' AND `ok`='0' LIMIT 1");
		if($nxt=mysql_fetch_row($res))
		{
			$json=" { \"response\": \"1\", \"sender\": \"$nxt[1]\", \"head\": \"$nxt[2]\", \"message\": \"$nxt[3]\" }";
			mysql_query("UPDATE `messages` SET `ok`='1' WHERE `id`='$nxt[0]'");
		}
		else	$json=" { \"response\": \"0\" }";
		$tmpl->SetText($json);
	}
}


$tmpl->write();


?>