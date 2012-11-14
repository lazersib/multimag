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

include_once("core.php");

try
{

need_auth($tmpl);
$tmpl->SetTitle("Администрирование пользователей");
if(!isAccess('admin_users','view'))	throw new AccessException("Недостаточно привилегий");

if($mode=='')
{
	$res=mysql_query("SELECT `users`.`id`, `users`.`name`, `users`.`reg_email`, `users`.`reg_email_confirm`, `users`.`reg_email_subscribe`, `users`.`reg_phone`, `users`.`reg_phone_confirm`, `users`.`reg_phone_subscribe`, `users`.`reg_date`, `users_worker_info`.`worker`,
	( SELECT `date` FROM `users_login_history` WHERE `user_id`=`users`.`id` ORDER BY `date` DESC LIMIT 1) AS `lastlogin_date`,
	( SELECT `user_id` FROM `users_openid` WHERE `user_id`=`users`.`id` LIMIT 1) AS `openid`
	FROM `users`
	LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`users`.`id`
	");
	if(mysql_errno())			throw new MysqlException("Не удалось получить данные пользователей");
	$tmpl->AddText("<h1 id='page-title'>Список пользователей</h1>
	<table class='list' width='100%'>
	<tr><th rowspan='2'>ID</th>
	<th rowspan='2'>Имя</th>
	<th colspan='3'>email</th>
	<th colspan='3'>Телефон</th>
	<th rowspan='2'>OpenID</th>
	<th rowspan='2'>Последнее посещение</th>
	<th rowspan='2'>Сотрудник?</th>
	<th rowspan='2'>Дата регистрации</th>
	</tr>
	<tr>
	<th>адрес</th><th>С</th><th>S</th>
	<th>номер</th><th>С</th><th>S</th>
	</tr>
	");
	while($line=mysql_fetch_assoc($res))
	{
		$econfirm=$line['reg_email_confirm']=='1'?'Да':'Нет';
		$esubscribe=$line['reg_email_subscribe']?'Да':'Нет';

		$p_email=$line['reg_email']?"<a href='mailto:{$line['reg_email']}'>{$line['reg_email']}</a>":'';
		$pconfirm=$line['reg_phone_confirm']=='1'?'Да':'Нет';
		$psubscribe=$line['reg_phone_subscribe']?'Да':'Нет';

		$openid=$line['openid']?'Да':'Нет';

		$worker=$line['worker']?'Да':'Нет';

		@$tmpl->AddText("<tr><td><a href='?mode=view&amp;id={$line['id']}'>{$line['id']}</a></td><td>{$line['name']}</td>
		<td>$p_email</td><td>$econfirm</td><td>$esubscribe</td>
		<td>{$line['reg_phone']}</td><td>$pconfirm</td><td>$psubscribe</td>
		<td>$openid</td>
		<td>{$line['lastlogin_date']}</td><td>$worker</td><td>{$line['reg_date']}</td></tr>");
	}
	$tmpl->AddText("</table>");
}
else if($mode=='view')
{
	if(!isAccess('admin_users','view'))	throw new AccessException("Недостаточно привилегий");
	$id=rcv('id');
	$res=mysql_query("SELECT * FROM `users`
	LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`users`.`id`
	WHERE `id`='$id'");
	if(mysql_errno())			throw new MysqlException("Не удалось получить данные пользователя");
	if(mysql_num_rows($res)<=0)		throw new Exception("Пользователь не найден!");
	$line=mysql_fetch_assoc($res);

	$passch=$line['pass_change']?'Да':'Нет';
	$passexp=$line['pass_expired']?'Да':'Нет';
	switch($line['pass_type'])
	{
		case 'CRYPT':	$pass_hash='Сильная';	break;
		case 'SHA1':	$pass_hash='Средняя';	break;
		default: {
			if($line['pass']=='0')	$pass_hash='Пароль не задан';
			else			$pass_hash='Слабая';
		}
	}
	$bifact=$line['bifact_auth']?'Да':'Нет';
	$econfirm=$line['reg_email_confirm']=='1'?'Да':'Нет';
	$esubscribe=$line['reg_email_subscribe']?'Да':'Нет';
	$p_email=$line['reg_email']?"<a href='mailto:{$line['reg_email']}'>{$line['reg_email']}</a>":'';
	$pconfirm=$line['reg_phone_confirm']=='1'?'Да':'Нет';
	$psubscribe=$line['reg_phone_subscribe']?'Да':'Нет';
	$diasbled=$line['disabled']?('Да, '.$line['disabled_reason']):'Нет';



	$worker=$line['worker']?'Да':'Нет';

	$tmpl->AddText("<h1 id='page-title'>Данные пользователя</h1>
	<table class='list'>
	<tr><th colspan='2'>Основная информация</th></tr>
	<tr><td>ID</td><td>{$line['id']}</td></tr>
	<tr><td>Имя</td><td>{$line['name']}</td></tr>
	<tr><td>Дата регистрации</td><td>{$line['reg_date']}</td></tr>
	<tr><td>Заблокирован (забанен)</td><td>$diasbled</td></tr>
	<tr><td>Меняет пароль?</td><td>$passch</td></tr>
	<tr><td>Смна пароля при след. входе?</td><td>$passexp</td></tr>
	<tr><td>Дата смены пароля</td><td>{$line['pass_date_change']}</td></tr>
	<tr><td>Стойкость хэша пароля</td><td>$pass_hash</td></tr>
	<tr><td>Двухфакторная аутентификация</td><td>$bifact</td></tr>
	<tr><td>Регистрационный email</td><td><a href='mailto:{$line['reg_email']}'>{$line['reg_email']}</a></td></tr>
	<tr><td>emal подтверждён?</td><td>$econfirm</td></tr>
	<tr><td>email подписан?</td><td>$esubscribe</td></tr>
	<tr><td>Регистрационный телефон</td><td><a href='mailto:{$line['reg_phone']}'>{$line['reg_phone']}</a></td></tr>
	<tr><td>телефон подтверждён?</td><td>$pconfirm</td></tr>
	<tr><td>телефон подписан?</td><td>$psubscribe</td></tr>
	<tr><td>Jabber ID</td><td>{$line['jid']}</td></tr>
	<tr><td>Настоящее имя</td><td>{$line['real_name']}</td></tr>
	<tr><td>Адрес доставки заказов</td><td>{$line['real_address']}</td></tr>
	<tr><th colspan='2'>Карточка сотрудника</th></tr>
	<tr><td>Является сотрудником</td><td>$worker</td></tr>
	<tr><td>Рабочий email</td><td><a href='mailto:{$line['worker_email']}'>{$line['worker_email']}</a></td></tr>
	<tr><td>Рабочий телефон</td><td>{$line['worker_phone']}</td></tr>
	<tr><td>Рабочий Jabber</td><td>{$line['worker_jid']}</td></tr>
	<tr><td>Рабочее имя</td><td>{$line['worker_real_name']}</td></tr>
	<tr><td>Рабочий адрес</td><td>{$line['worker_real_address']}</td></tr>
	<tr><th colspan='2'>Дополнительная информация</th></tr>");
	$res=mysql_query("SELECT `param`, `value` FROM `users_data` WHERE `uid`='$id'");
	if(mysql_errno())			throw new MysqlException("Не удалось получить дополнительные данные пользователя");
	while($line=mysql_fetch_row($res))
	{
		$tmpl->AddText("<tr><td>$line[0]</td><td>$line[1]</td></tr>");
	}
	$tmpl->AddText("<tr><th colspan='2'>История входов</th></tr>");
	$res=mysql_query("SELECT `date`, CONCAT(`ip`,' - ',`method`) FROM `users_login_history` WHERE `user_id`='$id' ORDER BY `date` DESC");
	if(mysql_errno())			throw new MysqlException("Не удалось получить информацию об истории входов");
	while($line=mysql_fetch_row($res))
	{
		$tmpl->AddText("<tr><td>$line[0]</td><td>$line[1]</td></tr>");
	}

	$tmpl->AddText("</table>");
}



}
catch(Exception $e)
{
	mysql_query("ROLLBACK");
	$tmpl->AddText("<br><br>");
	$tmpl->logger($e->getMessage());
}

$tmpl->write();

?>