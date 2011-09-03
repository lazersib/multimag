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
if(!isAccess('admin_comments','view'))	throw new AccessException("Недостаточно привилегий");

if($mode=='')
{
	$res=mysql_query("SELECT `comments`.`id`, `date`, `object_name`, `object_id`, `autor_name`, `autor_email`, `autor_id`, `text`, `rate`, `ip`, `user_agent`, `users`.`name` AS `user_name`, `users`.`email` AS `user_email`
	FROM `comments`
	INNER JOIN `users` ON `users`.`id`=`comments`.`autor_id`
	ORDER BY `comments`.`id` DESC");
	if(mysql_errno())	throw new MysqlException("Не удалось получить коментарии");
	$tmpl->AddText("<h1 id='page-title'>Последние коментарии</h1>
	<table class='list' width='100%'>
	<tr><th>ID</th><th>Дата</th><th>Объект</th><th>Автор</th><th>e-mail</th><th>Текст коментария</th><th>Оценка</th><th>IP адрес</th><th>user-agent</th></tr>");
	while($line=mysql_fetch_assoc($res))
	{
		$object="{$line['object_name']}:{$line['object_id']}";
		if($line['object_name']=='product')	$object="<a href='/vitrina.php?mode=product&amp;p={$line['object_id']}'>$object</a>";
		$email=$line['autor_id']?$line['user_email']:$line['autor_email'];
		$email="<a href='mailto:$email'>$email</a>";
		$autor=$line['autor_id']?"{$line['autor_id']}:<a href='/adm_users.php?mode=view&amp;id={$line['autor_id']}'>{$line['user_name']}</a>":$line['autor_name'];
		$tmpl->AddText("<tr>
		<td>{$line['id']} <a href='?mode=rm&amp;id={$line['id']}'><img src='/img/i_del.png' alt='Удалить'></a></td>
		<td>{$line['date']}</td><td>$object</td><td>$autor</td> <td>$email</td><td>{$line['text']}</td><td>{$line['rate']}</td><td>{$line['ip']}</td><td>{$line['user_agent']}</td></tr>");
	}
	$tmpl->AddText("</table>");
}
else if($mode=='rm')
{
	if(!isAccess('admin_comments','delete'))	throw new AccessException("Недостаточно привилегий");
	$id=rcv('id');
	mysql_query("DELETE FROM `comments` WHERE `id`='$id'");
	if(mysql_errno())				throw new MysqlException("Не удалось удалить строку");
	$tmpl->msg("Строка удалена.<br><a href='/adm_comments.php'>Назад</a>","ok");
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