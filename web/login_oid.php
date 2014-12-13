<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2014, BlackLight, TND Team, http://tndproject.org
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
require_once('include/openid.php');

function regMsg($login, $pass, $conf)
{
	global $CONFIG;
	$proto='http';
	if($CONFIG['site']['force_https_login'] || $CONFIG['site']['force_https'])	$proto='https';
return "Вы получили это письмо потому, что в заявке на регистрацию на сайте http://{$CONFIG['site']['name']} был указан Ваш адрес электронной почты. Для продолжения регистрации введите следующий код подтверждения:
$conf
или перейдите по ссылке $proto://{$CONFIG['site']['name']}/login.php?mode=conf&login=$login&e=$conf .
Если не переходить по ссылке (например, если заявка подана не Вами), то регистрационные данные будут автоматически удалены через неделю.

Ваш аккаунт:
Логин: $login
Пароль: $pass

После подтверждения регистрации Вы сможете получить доступ к расширенным функциям сайта. Неактивные аккаунты удаляются через 6 месяцев.

------------------------------------------------------------------------------------------

You have received this letter because in the form of registration in a site http://{$CONFIG['site']['name']} your e-mail address has been entered. For continuation of registration enter this key:
$conf
or pass under the link $proto://{$CONFIG['site']['name']}/login.php?mode=conf&login=$login&e=$conf .  If not going under the reference (for example if the form is submitted not by you) registration data will be automatically removed after a week.

Your account:
Login: $login
Pass: $pass

After confirmatoin of registration you can get access to the expanded functions of a site. Inactive accounts leave in 6 months.

------------------------------------------------------------------------------------------
Сообщение сгенерировано автоматически, отвечать на него не нужно!
The message is generated automatically, to answer it is not necessary!";
}


try
{
$tmpl->setContent("<h1 id='page-title'>Вход по openid</h1>");
$openid = new LightOpenID($CONFIG['site']['name']);
if(!$openid->mode)
{
	if(isset($_REQUEST['oid']))
	{
		$openid->identity = $_REQUEST['oid'];
		$openid->required = array('contact/email');
		$openid->optional = array('namePerson', 'namePerson/friendly', 'contact/phone/cell', 'contact/postaladdress/home', 'contact/IM/Jabber','contact/internet/email');
		header('Location: ' . $openid->authUrl());
	}
	$tmpl->addContent("<table style='width: 800px'>
	<tr><th colspan='4'><center>Войти через</center></th></tr>
	<tr>
	<td><a href='/login_oid.php?oid=https://www.google.com/accounts/o8/id'><img src='/img/oid/google.png' alt='Войти через Google'></a></td>
	<td><a href='/login_oid.php?oid=ya.ru'><img src='/img/oid/yandex.png' alt='Войти через Яндекс'></a></td>
	<td><a href='/login_oid.php?oid=vkontakteid.ru'><img src='/img/oid/vkontakte.png' alt='Войти через ВконтактеID'></a></td>
	<td><a href='/login_oid.php?oid=loginza.ru'><img src='/img/oid/loginza.png' alt='Войти через Loginza'></a></td>
	</tr>
	</table>
	<form method='post' action='login.php'>
	<input type='hidden' name='mode' value='openid'>
	<b>или введите ваш openid</b><br>
	<input type='text' name='oid' value=''><br>
	<button type='submit'>Войти</button>
	</form>");
}
elseif($openid->mode == 'cancel')
{
	$tmpl->msg("Вход отменён пользователем","err");
}
elseif($openid->mode)
{
	if($openid->validate())
	{
		$db->query("START TRANSACTION");

		$oid_attr=$openid->getAttributes();
		$sql_oid=$db->real_escape_string($openid->identity);
		$res=$db->query("SELECT `users_openid`.`user_id`, `users_openid`.`openid_identify`, `users`.`name`, `users`.`reg_email`, `users`.`disabled`, `users`.`disabled_reason` FROM `users_openid`
		LEFT JOIN `users` ON `users`.`id`=`users_openid`.`user_id`
		WHERE `openid_identify`='$sql_oid'");

		if($user_info=@$res->fetch_assoc())
		{
			if($user_info['disabled'])	throw new Exception("Пользователь заблокирован (забанен). Причина блокировки: ".$user_info['disabled_reason']);
			$ip=$db->real_escape_string(getenv("REMOTE_ADDR"));
			$ua=$db->real_escape_string($_SERVER['HTTP_USER_AGENT']);
			$db->query("INSERT INTO `users_login_history` (`user_id`, `date`, `ip`, `useragent`, `method`)
			VALUES ({$user_info['user_id']}, NOW(), '$ip', '$ua', 'openid')");
			$db->commit();
			$_SESSION['uid']=$user_info['user_id'];
			$_SESSION['name']=$user_info['name'];
			if($_SESSION['redir_to'])	header("Location: ".$_SESSION['redir_to']);
			else				header("Location: user.php");
		}
		else
		{
			$login='';
			// Пробуем использовать ник в качестве логина
			if(@$oid_attr['namePerson/friendly'])
			{
				$sql_login=$db->real_escape_string(translitIt($oid_attr['namePerson/friendly']));
				$res=$db->query("SELECT `id` FROM `users` WHERE `name`='$sql_login'");
				if(!$res->num_rows)	$login=$oid_attr['namePerson/friendly'];
			}

			// Пробуем использовать namePerson в качестве логина
			if(!$login && @$oid_attr['namePerson'])
			{
				$np=translitIt(str_replace(' ','_',$oid_attr['namePerson']));
				$sql_login=$db->real_escape_string($np);
				$res=$db->query("SELECT `id` FROM `users` WHERE `name`='$sql_login'");
				if(!$res->num_rows)	$login=$np;
			}

			// Пробуем использовать email в качестве логина
			if(!$login && @$oid_attr['contact/email'])
			{
				$sql_login=$db->real_escape_string($oid_attr['contact/email']);
				$res=$db->query("SELECT `id` FROM `users` WHERE `name`='$sql_login'");
				if(!$res->num_rows)	$login=$oid_attr['contact/email'];
			}

			// Пробуем использовать oid в качестве логина
			if(!$login && $openid->identity)
			{
				$sql_login=$db->real_escape_string($openid->identity);
				$res=$db->query("SELECT `id` FROM `users` WHERE `name`='$sql_login'");
				if(!$res->num_rows)	$login=$openid->identity;
			}

			if(!$login)	$login=substr(md5(time()),8);
			$sql_login=$db->real_escape_string($login);
			$sql_email=$db->real_escape_string(@$oid_attr['contact/email']);
			$sql_phone=$db->real_escape_string(@$oid_attr['contact/phone/cell']);
			$email_conf=$sql_email?substr(MD5(time()+rand(0,1000000)),0,8):'';
			$phone_conf=$sql_phone?rand(1000,99999):'';
			$pass=keygen_unique(0,8,11);
			if(@$CONFIG['site']['pass_type']=='MD5')
			{
				$sql_pass_hash=MD5($pass);
				$sql_pass_type='MD5';
			}
			else if(@$CONFIG['site']['pass_type']=='SHA1')
			{
				$sql_pass_hash=SHA1($pass);
				$sql_pass_type='SHA1';
			}
			else
			{
				if(CRYPT_SHA256 == 1)
				{
					$sql_pass_hash=crypt($pass, '$5$'.substr(MD5($login.rand(0,1000000)),0,16).'$');
				}
				else	$sql_pass_hash=crypt($pass);
				$sql_pass_type='CRYPT';
			}

			$res=$db->query("INSERT INTO `users` (`name`, `pass`, `pass_type`, `pass_date_change`, `reg_email`, `reg_email_confirm`, `reg_phone`, `reg_phone_confirm`, `reg_date`, `reg_email_subscribe`)
			VALUES ('$sql_login',  '$sql_pass_hash', '$sql_pass_type',  NOW(), '$sql_email', '$email_conf', '$sql_phone', '$phone_conf', NOW(), 1 )");
			$user_id=$db->insert_id;
			$res=$db->query("INSERT INTO `users_openid` (`user_id`, `openid_identify`, `openid_type`) VALUES ($user_id, '$sql_oid', '')");
			$ip=$db->real_escape_string(getenv("REMOTE_ADDR"));
			$ua=$db->real_escape_string($_SERVER['HTTP_USER_AGENT']);
			$res=$db->query("INSERT INTO `users_login_history` (`user_id`, `date`, `ip`, `useragent`, `method`)
			VALUES ($user_id, NOW(), '$ip', '$ua', 'openid')");

			if($email_conf)
			{
				$msg=regMsg($login, $pass, $email_conf);
				mailto(@$oid_attr['contact/email'], "Регистрация на ".$CONFIG['site']['name'], $msg);
			}

			$_SESSION['uid']=$user_id;
			$_SESSION['name']=$login;
			$tmpl->msg("Регистрация завершена! Теперь Вам доступны новые возможности!","ok");
			$db->commit();
		}

	}
	else	throw new Exception("Ошибка входа!");
}

}
catch(mysqli_sql_exception $e)
{
    $id = writeLogException($e);
    $tmpl->msg("Ошибка при регистрации. Порядковый номер - $id<br>Сообщение передано администратору",'err',"Ошибка при регистрации");
    mailto($CONFIG['site']['admin_email'],"ВАЖНО! Ошибка регистрации на ".$CONFIG['site']['name'].". номер в журнале - $id", $e->getMessage());
	
}
catch(Exception $e)
{
        writeLogException($e);
	$tmpl->msg($e->getMessage(),"err","Ошибка");
}

$tmpl->write();
