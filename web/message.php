<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2015, BlackLight, TND Team, http://tndproject.org
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

require_once("core.php");
$to	= request('to');
$opt	= request('opt');
$mode = request('mode');

if ($opt != 'email') {
    $opt = 'jabber';
}
if ($opt == 'jabber') {
    //if($to!=$CONFIG['site']['doc_adm_email'])
    $to = $CONFIG['site']['doc_adm_jid'];
} else {
    //if($CONFIG['site']['doc_adm_jid'])
    $to = $CONFIG['site']['doc_adm_email'];
}

if($mode=="")
{
	$nm=@$_SESSION['name'];
	$desc='при помощи мгновенных сообщений Jabber (XMPP)';
	if($opt=='email')	$desc='при помощи эелектронной почты (e-mail)';
	$tmpl->addContent("<h1 id='page-title'>Написать сообщение</h1>
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
else if($mode=='call_request')
{
	$ok=0;
	$name		= request('name',@$_SESSION['name']);
	$phone		= request('phone');
	$call_date	= request('call_date');
	if($opt)
	{
		if($name && $phone && $call_date)
		{
			if( @$CONFIG['call_request']['captcha'] && (@$_REQUEST['img']=='' ||
				strtoupper($_SESSION['captcha_keystring']) != strtoupper(@$_REQUEST['img'])))
				$tmpl->msg("Не верно введён код с картинки!","err");
			else
			{
				try
				{
					$name_s		= $db->real_escape_string($name);
					$phone_s	= $db->real_escape_string($phone);
					$call_date_s	= $db->real_escape_string($call_date);
					$ip_s		= $db->real_escape_string(getenv("REMOTE_ADDR"));
					$res=$db->query("INSERT INTO `log_call_requests` (`name`, `phone`, `call_date`, `ip`, `request_date`)
					VALUES ('$name_s', '$phone_s', '$call_date_s', '$ip_s', NOW())");
					$text="Посетитель сайта {$CONFIG['site']['name']} $name просит перезвонить на $phone в $call_date";

					if(@$CONFIG['call_request']['email'])
					{
						mailto($CONFIG['call_request']['email'],"Запрос звонка с сайта {$CONFIG['site']['name']}", $text);
					}

					if(@$CONFIG['call_request']['xmpp'])
					{
						require_once($CONFIG['location'].'/common/XMPPHP/XMPP.php');
						$xmppclient = new XMPPHP_XMPP( $CONFIG['xmpp']['host'], $CONFIG['xmpp']['port'], $CONFIG['xmpp']['login'], $CONFIG['xmpp']['pass'], 'MultiMag r'.MULTIMAG_REV);
						$xmppclient->connect();
						$xmppclient->processUntil('session_start');
						$xmppclient->presence();
						$xmppclient->message($CONFIG['call_request']['xmpp'], $text);
						$xmppclient->disconnect();
					}

					if(@$CONFIG['call_request']['sms'])
					{
						require_once('include/sendsms.php');
						$sender=new SMSSender();
						$sender->setNumber($CONFIG['call_request']['sms']);
						$sender->setContent($text);
						$sender->send();

					}
					$tmpl->msg("Ваш запрос передан. Вам обязательно перезвонят.","ok");
					$ok=1;
				}
				catch(Exception $e)
				{
                                    writeLogException($e);
                                    $tmpl->errorMessage("Невозможно отправить запрос. Попробуйте позднее.");
				}
			}
		}
		else $tmpl->msg("Не заполнено одно из полей!","err");
	}
	if(!$ok)
	{
		$tmpl->addContent("<h1>Запрос звонка</h1>
		<div id='page-info'>Заполните форму - и вам перезвонят! Все поля обязательны к заполнению.</div>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='call_request'>
		<input type='hidden' name='opt' value='ok'>
		Ваше имя:<br>
		<input type='text' name='name' value='$name'><br>
		Контактный телефон (лучше мобильный или sip):<br>
		<input type='text' name='phone' value='$phone'><br>
		Желаемая дата и время звонка:<br>
		<small>Желательно запрашивать звонок в рабочее время магазина</small><br>
		<input type='text' name='call_date' value='$call_date'><br>");
		if(@$CONFIG['call_request']['captcha'])
		{
			$tmpl->addContent("Подтвердите что вы не робот, введя текст с картинки:<br><img src='/kcaptcha/index.php'><br><input type='text' name='img'><br>");
		}
		$tmpl->addContent("<button type='submit'>Отправить запрос</button>
		</form>");
	}
}
else if($mode=='send') {
    $nm = request('nm');
    $backadr = request('backadr');
    $text = request('text');
    $text = "Нам написал сообщение $nm($backadr)с сайта {$CONFIG['site']['name']}\n-------------------\n$text\n";
    $text .= "-------------------\nIP отправителя: " . getenv("REMOTE_ADDR") . "\nSESSION ID:" . session_id();
    $text .= "\nБроузер:  ".getenv("HTTP_USER_AGENT");
    if(@$_SESSION['name']) $text.="\nLogin отправителя: ".$_SESSION['name'];

	if($opt=='jabber')
	{
		try
		{
			require_once($CONFIG['location'].'/common/XMPPHP/XMPP.php');
			$xmppclient = new XMPPHP_XMPP( $CONFIG['xmpp']['host'], $CONFIG['xmpp']['port'], $CONFIG['xmpp']['login'], $CONFIG['xmpp']['pass'], 'MultiMag r'.MULTIMAG_REV);
			$xmppclient->connect();
			$xmppclient->processUntil('session_start');
			$xmppclient->presence();
			$xmppclient->message($to, $text);
			$xmppclient->disconnect();
			$tmpl->msg("Сообщение было отправлено!","ok");
		}
		catch(XMPPHP_Exception $e)
		{
                    writeLogException($e);
                    $tmpl->errorMessage("Невозможно отправить сообщение XMPP!");
		}
	}
	else
	{
		try
		{
			mailto($to, "Сообщение с сайта {$CONFIG['site']['name']}", $text);
			$tmpl->msg("Сообщение было отправлено!","ok");
		}
		catch(Exception $e)
		{
                    writeLogException($e);
                    $tmpl->errorMessage("Невозможно отправить сообщение email!");
		}
	}
}
else if($mode=='petition')
{
	$doc	= rcvint('doc');
	$tmpl->addContent("<form action='/message.php' method='post'><input type='hidden' name='mode' value='petitions'>
	<input type='hidden' name='doc' value='$doc'><fieldset><legend>Запрос на отмену документа</legend>
	Опишите причину необходимости отмены документа:<br><textarea name='comment'></textarea><br>
	<input type='submit' value='Послать запрос'></fieldset></form>");
}
else if($mode=='petitions')
{
	need_auth();
	$doc	= rcvint('doc');
	$comment= request('comment');

	if(mb_strlen($comment)>8) {
		$res = $db->query("SELECT `users`.`reg_email`, `users_worker_info`.`worker_email` FROM `users`
			LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`users`.`id`
			WHERE `id`='{$_SESSION['uid']}'");
		$user_info = $res->fetch_array();
		if($user_info['worker_email'] != '')	$from = $user_info['worker_email'];
		else if($user_info['reg_email'] != '')	$from = $user_info['reg_email'];
		else $from = $CONFIG['site']['doc_adm_email'];

		$res = $db->query("SELECT `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`sum`, `doc_list`.`date`, `doc_agent`.`name`, `doc_types`.`name`
		FROM `doc_list`
		LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
		LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		WHERE `doc_list`.`id`='$doc'");
		$nxt = $res->fetch_row();
		if(!$nxt)	throw new Exception("Документ не найден");
		
                $date = date("d.m.Y H:i:s", $nxt[3]);
                $proto = @$_SERVER['HTTPS'] ? 'https' : 'http';
                $ip = getenv("REMOTE_ADDR");
                $txt="Здравствуйте!\nПользователь {$_SESSION['name']} просит Вас отменить проводку документа *$nxt[5]* с ID: $doc, $nxt[0]$nxt[1] от $date на сумму $nxt[2]. Клиент $nxt[4].\n{$proto}://{$CONFIG['site']['name']}/doc.php?mode=body&doc=$doc \nЦель отмены: $comment.\n IP: $ip\nПожалуйста, дайте ответ на это письмо на $from, как в случае отмены документа, так и об отказе отмены!";

		if($CONFIG['site']['doc_adm_email'])
			mailto($CONFIG['site']['doc_adm_email'], 'Запрос на отмену проведения документа' ,$txt, $from);

		if($CONFIG['site']['doc_adm_jid'])
		{
			try
			{
				require_once($CONFIG['location'].'/common/XMPPHP/XMPP.php');
				$xmppclient = new XMPPHP_XMPP( $CONFIG['xmpp']['host'], $CONFIG['xmpp']['port'], $CONFIG['xmpp']['login'], $CONFIG['xmpp']['pass'], 'MultiMag r'.MULTIMAG_REV);
				$xmppclient->connect();
				$xmppclient->processUntil('session_start');
				$xmppclient->presence();
				$xmppclient->message($CONFIG['site']['doc_adm_jid'], $txt);
				$xmppclient->disconnect();
				$tmpl->msg("Сообщение было отправлено уполномоченному лицу! Ответ о снятии проводки придёт вам на e-mail!","ok");
			}
			catch(XMPPHP_Exception $e)
			{
                            writeLogException($e);
                            $tmpl->errorMessage("Невозможно отправить сообщение по XMPP!","err");
			}
		}
	}
	else $tmpl->msg("Опишите причину подробнее!");
}

$tmpl->write();
