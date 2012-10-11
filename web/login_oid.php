<?php

//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2010, BlackLight, TND Team, http://tndproject.org
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
try
{
$tmpl->SetText("<h1 id='page-title'>Вход по openid</h1>");
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
	$tmpl->AddText("
	<table style='width: 800px'>
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
		mysql_query("START TRANSACTION");
		
		$oid_attr=$openid->getAttributes();
		$sql_oid=mysql_real_escape_string($openid->identity);
		$res=mysql_query("SELECT `users_openid`.`user_id`, `users_openid`.`openid_identify`, `users`.`name`, `users`.`reg_email`, `users`.`disabled`, `users`.`disabled_reason` FROM `users_openid`
		LEFT JOIN `users` ON `users`.`id`=`users_openid`.`user_id`
		WHERE `openid_identify`='$sql_oid'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить регистрационные данные");
		if($user_info=@mysql_fetch_assoc($res))
		{
			if($user_info['disabled'])	throw new Exception("Пользователь заблокирован (забанен). Причина блокировки: ".$user_info['disabled_reason']);
			$ip=mysql_real_escape_string(getenv("REMOTE_ADDR"));
			$ua=mysql_real_escape_string($_SERVER['HTTP_USER_AGENT']);
			mysql_query("INSERT INTO `users_login_history` (`user_id`, `date`, `ip`, `useragent`, `method`)
			VALUES ({$user_info['user_id']}, NOW(), '$ip', '$ua', 'openid')");			
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
				$sql_login=mysql_real_escape_string(translitIt($oid_attr['namePerson/friendly']));
				$res=mysql_query("SELECT `id` FROM `users` WHERE `name`='$sql_login'");
				if(mysql_errno())	throw new MysqlException("Не удалось получить данные уникальности");
				if(!mysql_num_rows($res))	$login=$oid_attr['namePerson/friendly'];
			}
			
			// Пробуем использовать namePerson в качестве логина
			if(!$login && @$oid_attr['namePerson'])
			{
				$np=translitIt(str_replace(' ','_',$oid_attr['namePerson']));
				$sql_login=mysql_real_escape_string($np);
				$res=mysql_query("SELECT `id` FROM `users` WHERE `name`='$sql_login'");
				if(mysql_errno())	throw new MysqlException("Не удалось получить данные уникальности");
				if(!mysql_num_rows($res))	$login=$np;
			}
			
			// Пробуем использовать email в качестве логина
			if(!$login && @$oid_attr['contact/email'])
			{
				$sql_login=mysql_real_escape_string($oid_attr['contact/email']);
				$res=mysql_query("SELECT `id` FROM `users` WHERE `name`='$sql_login'");
				if(mysql_errno())	throw new MysqlException("Не удалось получить данные уникальности");
				if(!mysql_num_rows($res))	$login=$oid_attr['contact/email'];
			}
			
			// Пробуем использовать oid в качестве логина
			if(!$login && $openid->identity)
			{
				$sql_login=mysql_real_escape_string($openid->identity);
				$res=mysql_query("SELECT `id` FROM `users` WHERE `name`='$sql_login'");
				if(mysql_errno())	throw new MysqlException("Не удалось получить данные уникальности");
				if(!mysql_num_rows($res))	$login=$openid->identity;
			}
			
			if(!$login)	$login=substr(md5(time()),8);
			$sql_login=mysql_real_escape_string($login);
			$sql_email=mysql_real_escape_string(@$oid_attr['contact/email']);
			$sql_phone=mysql_real_escape_string(@$oid_attr['contact/phone/cell']);
			$res=mysql_query("INSERT INTO `users` (`name`, `pass`, `pass_date_change`, `reg_email`, `reg_email_confirm`, `reg_phone`, `reg_date`, `reg_email_subscribe`)
			VALUES ('$sql_login', '0',  NOW(), '$sql_email', '1', '$sql_phone', NOW(), 1 )");
			if(mysql_errno())	throw new MysqlException("Не удалось добавить пользователя! Попробуйте позднее!");
			$user_id=mysql_insert_id();
			mysql_query("INSERT INTO `users_openid` (`user_id`, `openid_identify`, `openid_type`) VALUES ($user_id, '$sql_oid', '')");
			if(mysql_errno())	throw new MysqlException("Не удалось добавить openid! Попробуйте позднее!");
			$ip=mysql_real_escape_string(getenv("REMOTE_ADDR"));
			$ua=mysql_real_escape_string($_SERVER['HTTP_USER_AGENT']);
			mysql_query("INSERT INTO `users_login_history` (`user_id`, `date`, `ip`, `useragent`, `method`)
			VALUES ($user_id, NOW(), '$ip', '$ua', 'openid')");
			$_SESSION['uid']=$user_id;
			$_SESSION['name']=$login;
			$tmpl->msg("Регистрация завершена! Теперь Вам доступны новые возможности!","ok");
			mysql_query("COMMIT");
		}
	
	}
	else	throw new Exception("Ошибка входа!");
}

}
catch(MysqlException $e)
{
	$tmpl->msg($e->getMessage()."<br>Сообщение передано администратору",'err',"Ошибка при регистрации");
	mailto($CONFIG['site']['admin_email'],"ВАЖНО! Ошибка регистрации на ".$CONFIG['site']['name'], $e->getMessage());
}
catch(Exception $e)
{
	mysql_query("ROLLBACK");
	$tmpl->msg($e->getMessage(),"err","Ошибка");
}

$tmpl->write();

?>