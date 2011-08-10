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
$tmpl->SetTitle("Администрирование коментариев");
if(!isAccess('admin_users','view'))	throw new AccessException("Недостаточно привилегий");

if($mode=='')
{
	$res=mysql_query("SELECT * FROM `users`");
	if(mysql_errno())			throw new MysqlException("Не удалось получить данные пользователей");
	$tmpl->AddText("<h1 id='page-title'>Список пользователей</h1>
	<table class='list' width='100%'>
	<tr><th>ID</th><th>Имя</th><th>email</th><th>Подтверждён?</th><th>Дата регистрации</th><th>Подписан?</th><th>Последнее посещение</th><th>Сотрудник?</th></tr>");
	while($line=mysql_fetch_assoc($res))
	{
		$passch=$line['passch']?'Да':'Нет';
		$confirm=$line['confirm']==='0'?'Да':'Нет';
		$subscribe=$line['subscribe']?'Да':'Нет';
		$worker=$line['worker']?'Да':'Нет';
		$tmpl->AddText("<tr><td><a href='?mode=view&amp;id={$line['id']}'>{$line['id']}</a></td><td>{$line['name']}</td><td><a href='mailto:{$line['email']}'>{$line['email']}</a></td><td>$confirm</td> <td>{$line['date_reg']}</td><td>$subscribe</td><td>{$line['lastlogin']}</td><td>$worker</td></tr>");
	}
	$tmpl->AddText("</table>");
}
else if($mode=='view')
{
	if(!isAccess('admin_users','view'))	throw new AccessException("Недостаточно привилегий");
	$id=rcv('id');
	$res=mysql_query("SELECT * FROM `users` WHERE `id`='$id'");
	if(mysql_errno())			throw new MysqlException("Не удалось получить данные пользователя");
	if(mysql_num_rows($res)<=0)		throw new Exception("Пользователь не найден!");
	$line=mysql_fetch_assoc($res);
	
	$passch=$line['passch']?'Да':'Нет';
	$confirm=$line['confirm']==='0'?'Да':'Нет';
	$subscribe=$line['subscribe']?'Да':'Нет';
	$worker=$line['worker']?'Да':'Нет';
	
	$tmpl->AddText("<h1 id='page-title'>Данные пользователя</h1>
	<table class='list'>
	<tr><th colspan='2'>Основная информация</th></tr>
	<tr><td>ID</td><td>{$line['id']}</td></tr>
	<tr><td>Имя</td><td>{$line['name']}</td></tr>
	<tr><td>Меняет пароль?</td><td>$passch</td></tr>
	<tr><td>email</td><td><a href='mailto:{$line['email']}'>{$line['email']}</a></td></tr>
	<tr><td>emal подтверждён?</td><td>$confirm</td></tr>
	<tr><td>Дата регистрации</td><td>{$line['date_reg']}</td></tr>
	<tr><td>Подписан на уведомления и рассылки?</td><td>$subscribe</td></tr>
	<tr><td>Дата последнего посещения</td><td>{$line['lastlogin']}</td></tr>
	<tr><td>Настоящее имя</td><td>{$line['rname']}</td></tr>
	<tr><td>Контактный телефон</td><td>{$line['tel']}</td></tr>
	<tr><td>Адрес доставки заказов</td><td>{$line['adres']}</td></tr>
	<tr><td>Является сотрудником?</td><td>$worker</td></tr>
	<tr><th colspan='2'>Дополнительная информация</th></tr>");
	$res=mysql_query("SELECT `param`, `value` FROM `users_data` WHERE `uid`='$id'");
	if(mysql_errno())			throw new MysqlException("Не удалось получить дополнительные данные пользователя");
	while($line=mysql_fetch_row($res))
	{
		$tmpl->AddText("<tr><td>$line[0]</td><td>$line[1]</td></tr>");
	}
	$tmpl->AddText("</table>");
}

$tmpl->write();

}
catch(Exception $e)
{
	mysql_query("ROLLBACK");
	$tmpl->AddText("<br><br>");
	$tmpl->logger($e->getMessage());
}

?>