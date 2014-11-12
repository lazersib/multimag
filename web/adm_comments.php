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

include_once("core.php");

try
{

need_auth($tmpl);
$tmpl->setTitle("Администрирование коментариев");
if(!isAccess('admin_comments','view'))	throw new AccessException("Недостаточно привилегий");

$mode=request('mode');

if($mode=='')
{
	$res=$db->query("SELECT `comments`.`id`, `date`, `object_name`, `object_id`, `autor_name`, `autor_email`, `autor_id`, `text`, `rate`, `ip`, `user_agent`, `comments`.`response`, `users`.`name` AS `user_name`, `users`.`reg_email` AS `user_email`
	FROM `comments`
	INNER JOIN `users` ON `users`.`id`=`comments`.`autor_id`
	ORDER BY `comments`.`id` DESC");

	$tmpl->addContent("<h1 id='page-title'>Последние коментарии</h1>
	<table class='list' width='100%'>
	<tr><th>ID</th><th>Дата</th><th>Объект</th><th>Автор</th><th>e-mail</th><th>Текст коментария</th><th>Оценка</th><th>Ответ</th><th>IP адрес</th><th>user-agent</th></tr>");
	while($line=$res->fetch_assoc())
	{
		$object="{$line['object_name']}:{$line['object_id']}";
		if($line['object_name']=='product')	$object="<a href='/vitrina.php?mode=product&amp;p={$line['object_id']}'>$object</a>";
		$email=$line['autor_id']?$line['user_email']:$line['autor_email'];
		$email="<a href='mailto:$email'>$email</a>";
		$autor=$line['autor_id']?"{$line['autor_id']}:<a href='/adm_users.php?mode=view&amp;id={$line['autor_id']}'>{$line['user_name']}</a>":$line['autor_name'];
		$response=$line['response']?html_out($line['response'])."<br><a href='?mode=response&amp;id={$line['id']}'>Правка</a>":"<a href='?mode=response&amp;id={$line['id']}'>Ответить</a>";
		$html_text=html_out($line['text']);
		$tmpl->addContent("<tr>
		<td>{$line['id']} <a href='?mode=rm&amp;id={$line['id']}'><img src='/img/i_del.png' alt='Удалить'></a></td>
		<td>{$line['date']}</td><td>$object</td><td>$autor</td> <td>$email</td><td>{$line['text']}</td><td>{$line['rate']}</td><td>$response</td><td>{$line['ip']}</td><td>{$line['user_agent']}</td></tr>");
	}
	$tmpl->addContent("</table>");
}
else if($mode=='rm')
{
	if(!isAccess('admin_comments','delete'))	throw new AccessException("Недостаточно привилегий");
	$id=rcvint('id');

	$db->query("DELETE FROM `comments` WHERE `id`='$id'");
	$tmpl->msg("Строка удалена.<br><a href='/adm_comments.php'>Назад</a>","ok");
}
else if($mode=='response')
{
	$id=rcvint('id');
	$opt=request('opt');

	if($opt)
	{
		$sql_text=$db->real_escape_string(request('text'));
		$res=$db->query("UPDATE `comments` SET `response`='$sql_text', `responser`='{$_SESSION['uid']}' WHERE `id`='$id'");
		$tmpl->msg("Коментарий сохранён успешно",'ok');
	}
	$res=$db->query("SELECT `comments`.`id`, `date`, `object_name`, `object_id`, `autor_name`, `autor_email`, `autor_id`, `text`, `rate`, `ip`, `user_agent`, `comments`.`response`, `users`.`name` AS `user_name`, `users`.`reg_email` AS `user_email`
	FROM `comments`
	INNER JOIN `users` ON `users`.`id`=`comments`.`autor_id`
	WHERE `comments`.`id`='$id'");
	$line=$res->fetch_assoc();
	if(!$line)		throw new Exception("Коментарий не найден!");
	$autor=$line['autor_id']?"{$line['autor_id']}:<a href='/adm_users.php?mode=view&amp;id={$line['autor_id']}'>{$line['user_name']}</a>":$line['autor_name'];
	$object="{$line['object_name']}:{$line['object_id']}";
	$html_text=html_out($line['text']);
	$html_response=html_out($line['response']);
	$tmpl->addContent("<h1 id='page-title'>Ответ на коментарий N{$line['id']}</h1>
	<div>{$line['date']} $autor для $object пишет:<br>$html_text</div>
	<form action='' method='post'>
	<input type='hidden' name='id' value='{$line['id']}'>
	<input type='hidden' name='opt' value='save'>
	Ваш ответ (500 символов максимум):<br>
	<textarea name='text' class='text'>$html_response</textarea><br>
	<button type='submit'>Сохранить</button>
	</form><br>
	<a href='/adm_comments.php'>Вернуться к общему списку коментариев</a>");
}



}
catch(Exception $e)
{
    global $db, $tmpl;
    $db->rollback();
    $tmpl->addContent("<br><br>");
    writeLogException($e);
    $tmpl->errorMessage($e->getMessage());
}

$tmpl->write();

?>