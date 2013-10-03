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
need_auth();

/// Класс, реализующий простейший треккер задач
class TaskTracker
{
/// Меню треккера задач
function PMenu($dop='')
{
	global $tmpl;
	$tmpl->addContent("<h1>Планировщик задач - $dop</h1>");
	$tmpl->setTitle("Планировщик задач - $dop");
	$tmpl->addContent("<a href='?mode='>Невыполненные задачи для меня</a> | <a href='?mode=new'>Новая задача</a> | <a href='?mode=viewall'>Все задачи</a> | <a href='?mode=my'>Мои задачи</a>");
}

/// Показать задачу
/// @param n Номер задачи
function ShowTicket($n)
{
	global $tmpl, $db;
	settype($n, 'int');
	$res=$db->query("SELECT `tickets`.`id`, `tickets`.`date`, `tickets`.`theme`, `tickets_priority`.`name`, `a`.`name`, `tickets`.`to_date`, `tickets_state`.`name`, `t`.`name`, `tickets`.`text`, `tickets`.`state`
	FROM `tickets`
	LEFT JOIN `users` AS `a` ON `a`.`id`=`tickets`.`autor`
	LEFT JOIN `users` AS `t` ON `t`.`id`=`tickets`.`to_uid`
	LEFT JOIN `tickets_priority` ON `tickets_priority`.`id`=`tickets`.`priority`
	LEFT JOIN `tickets_state` ON `tickets_state`.`id`=`tickets`.`state`
	WHERE `tickets`.`id`='$n'");
	if(!$res)	throw new MysqlException("Не удалось получить данные задачи");
	$nxt=$res->fetch_row();
	if(!$nxt)	$tmpl->msg("Задача не найдена!","err");
	else
	{
		$tmpl->addContent("<h2>".html_out($nxt[2])."</h2>
		<b>Дата создания:</b> $nxt[1]<br>
		<b>Важность:</b> $nxt[3]<br>
		<b>Автор:</b> $nxt[4]<br>
		<b>Исполнитель:</b> ".html_out($nxt[7])."<br>
		<b>Срок:</b> $nxt[5]<br>
		<b>Состояние:</b> $nxt[6]<br>
		<b>Описание:</b> ".html_out($nxt[8])."<br>
		<ul>");
		$res=$db->query("SELECT `users`.`name`, `tickets_log`.`date`, `tickets_log`.`text` FROM `tickets_log`
		LEFT JOIN `users` ON `users`.`id`=`tickets_log`.`uid`
		WHERE `ticket`='$nxt[0]'");
		if(!$res)	throw new MysqlException("Не удалось порлучить историю задачи");
		while($nx=$res->fetch_row())
			$tmpl->addContent("<li><i>$nx[1]</i>, <b>$nx[0]:</b> $nx[2]</li>");

		$tmpl->addContent("</ul><br><br><fieldset><legend>Установить статус</legend>
		<form action=''>
		<input type='hidden' name='mode' value='set'>
		<input type='hidden' name='opt' value='state'>
		<input type='hidden' name='n' value='$nxt[0]'>
		<select name='state'>");
		$res=$db->query("SELECT `id`, `name` FROM `tickets_state` WHERE `id`!='$nxt[9]'");
		if(!$res)	throw new MysqlException("Не удалось получить данные состояний");
		while($nx=$res->fetch_row())
			$tmpl->addContent("<option value='$nx[0]'>$nx[1]</option>");

		$tmpl->addContent("</select><input type='submit' value='Сменить'></form></fieldset>
		<fieldset><legend>Добавить коментарий:</legend>
		<form action=''>
		<input type='hidden' name='mode' value='set'>
		<input type='hidden' name='opt' value='comment'>
		<input type='hidden' name='n' value='$nxt[0]'>
		<textarea name='comment'></textarea><br>
		<input type='submit' value='Добавить'></form></fieldset>

		<fieldset><legend>Изменить срок:</legend>
		<form action=''>
		<input type='hidden' name='mode' value='set'>
		<input type='hidden' name='opt' value='to_date'>
		<input type='hidden' name='n' value='$nxt[0]'>
		<input type='text' name='to_date' class='vDateField' value='$nxt[5]'>
		<input type='submit' value='Изменить'></form></fieldset>

		<fieldset><legend>Переназначить задачу на:</legend>
		<form action=''>
		<input type='hidden' name='mode' value='set'>
		<input type='hidden' name='opt' value='to_user'>
		<input type='hidden' name='n' value='$nxt[0]'>
		<select name='user_id'>");

		$res=$db->query("SELECT `users`.`id`, `users`.`name`, `users_worker_info`.`worker_real_name`
		FROM `users`
		INNER JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`users`.`id`
		WHERE `users_worker_info`.`worker`>'0' ORDER BY `users`.`name`");
		if(!$res)	throw new MysqlException("Не удалось получить данные сотрудников");
		while($nxt=$res->fetch_row())
		{
			if($nxt[0]==0) continue;
			$tmpl->addContent("<option value='$nxt[0]'>$nxt[1] - $nxt[2] ($nxt[0])</option>");
		}
		$tmpl->addContent("</select>
		<input type='submit' value='Изменить'></form></fieldset>

		<fieldset><legend>Изменить приоритет:</legend>
		<form action=''>
		<input type='hidden' name='mode' value='set'>
		<input type='hidden' name='opt' value='prio'>
		<input type='hidden' name='n' value='$nxt[0]'>
		<select name='prio'>");
		$res=$db->query("SELECT `id`, `name`, `color` FROM `tickets_priority` ORDER BY `id`");
		if(!$res)	throw new MysqlException("Не удалось получить данные приоритетов");
		while($nxt=$res->fetch_row())
			$tmpl->addContent("<option value='$nxt[0]' style='color: #$nxt[2]'>$nxt[1] ($nxt[0])</option>");
		$tmpl->addContent("</select><input type='submit' value='Изменить'></form></fieldset>");
	}
}

/// Формирует список задач текущего пользователя
function ShowMyTickets()
{
	global $tmpl, $db;
	$this->PMenu("Задачи для меня");
	$tmpl->addContent("<table width='100%' class='list'><tr><th>N<th>Дата задачи<th>Тема<th>Важность<th>Автор<th>Срок<th>Статус");
	$res=$db->query("SELECT `tickets`.`id`, `tickets`.`date`, `tickets`.`theme`, `tickets_priority`.`name`, `users`.`name`, `tickets`.`to_date`, `tickets_state`.`name`, `tickets_priority`.`color` FROM `tickets`
	LEFT JOIN `users` ON `users`.`id`=`tickets`.`autor`
	LEFT JOIN `tickets_priority` ON `tickets_priority`.`id`=`tickets`.`priority`
	LEFT JOIN `tickets_state` ON `tickets_state`.`id`=`tickets`.`state`
	WHERE `to_uid`='{$_SESSION['uid']}' AND `tickets`.`state`<'2'
	ORDER BY `tickets`.`priority` DESC, `tickets`.`to_date` DESC, `tickets`.`date`");
	if(!$res)	throw new MysqlException("Не удалось получить данные задач");
	$i=0;
	while($nxt=$res->fetch_row($res))
	{
		$tmpl->addContent("<tr class='lin$i pointer' style='color: #$nxt[7]'><td><a href='?mode=view&n=$nxt[0]'>$nxt[0]</a><td>$nxt[1]<td>$nxt[2]<td>$nxt[3]<td>$nxt[4]<td>$nxt[5]<td>$nxt[6]");
		$i=1-$i;
	}
	$tmpl->addContent("</table>");
}

/// Формирует форму создания задачи
function ShowNewTicketForm()
{
	global $tmpl, $db;
	if(!isAccess('generic_tickets','create'))	throw new AccessException("Недостаточно привилегий");
	$this->PMenu("Новая задача");
	$tmpl->addContent("<form action='' method='post'>
	<input type='hidden' name='mode' value='add'>
	Задача для:<br>
	<select name='to_uid'>");
	$res=$db->query("SELECT `users`.`id`, `users`.`name`, `users_worker_info`.`worker_real_name`
	FROM `users`
	INNER JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`users`.`id`
	WHERE `users_worker_info`.`worker`>'0' ORDER BY `users`.`name`");
	if(!$res)	throw new MysqlException("Не удалось получить данные сотрудников");
	while($nxt=$res->fetch_row())
	{
		if($nxt[0]==0) continue;
		$tmpl->addContent("<option value='$nxt[0]'>$nxt[1] - $nxt[2] ($nxt[0])</option>");
	}
	$tmpl->addContent("</select><br>Название:<br><input type='text' name='theme'><br>
	Важность, приоритет:<br><select name='prio'>");
	$res=$db->query("SELECT `id`, `name`, `color` FROM `tickets_priority` ORDER BY `id`");
	if(!$res)	throw new MysqlException("Не удалось получить данные приоритетов");
	while($nxt=$res->fetch_row())
		$tmpl->addContent("<option value='$nxt[0]' style='color: #$nxt[2]'>$nxt[1] ($nxt[0])</option>");

	$tmpl->addContent("</select><br>
	Срок (указывать не обязательно):<br>
	<input type='text' name='to_date'  class='vDateField'><br>
	Описание задачи:<br>
	<textarea name='text'></textarea><br>
	<input type='submit' value='Назначить задачу'>
	</form>");
}
};

if(!isAccess('generic_tickets','view'))	throw new AccessException("Недостаточно привилегий");

$tt=new TaskTracker();

if($mode=='')
	$tt->ShowMyTickets();
else if($mode=='new')
{


}
else if($mode=='add')
{
	if(!isAccess('generic_tickets','create'))	throw new AccessException("Недостаточно привилегий");
	$this->PMenu("Сохранение задачи");
	$uid=@$_SESSION['uid'];
	$to_uid=rcv('to_uid');
	$theme=rcv('theme');
	$prio=rcv('prio');
	$to_date=rcv('to_date');
	$text=rcv('text');

	mysql_query("INSERT INTO `tickets` (`date`, `autor`, `priority`, `theme`, `text`, `to_uid`, `to_date`)
	VALUES ( NOW(), '$uid', '$prio', '$theme', '$text', '$to_uid', '$to_date')");
	if(!mysql_errno())
		$tmpl->msg("Задание назначено!","ok");
	else $tmpl->msg("Ошибка добавления!","err");
	$n=mysql_insert_id();

	$res=mysql_query("SELECT `reg_email` FROM `users` WHERE `id`='$to_uid'");
	if(mysql_errno())	throw new MysqlException("Не удалось получить данные пользователей");
	$email=mysql_result($res,0,0);

	$msg="Для Вас новое задание от $uid: $theme - $text\n";
	if($to_date) $msg.="Выполнить до $to_date\n";
	$msg.="Посмотреть задание можно здесь: http://{$CONFIG['site']['name']}/tickets.php/mode=view&n=$n";

	mailto($email, "У Вас Новое задание - $theme", $msg);

	ShowTicket($n);

}
else if($mode=='my')
{
	p_menu("Мои задачи");

	$tmpl->addContent("<table width='100%' class='list'><tr><th>N<th>Дата задачи<th>Тема<th>Важность<th>Для<th>Срок<th>Статус");
	$res=mysql_query("SELECT `tickets`.`id`, `tickets`.`date`, `tickets`.`theme`, `tickets_priority`.`name`, `users`.`name`, `tickets`.`to_date`, `tickets_state`.`name`, `tickets_priority`.`color` FROM `tickets`
	LEFT JOIN `users` ON `users`.`id`=`tickets`.`to_uid`
	LEFT JOIN `tickets_priority` ON `tickets_priority`.`id`=`tickets`.`priority`
	LEFT JOIN `tickets_state` ON `tickets_state`.`id`=`tickets`.`state`
	WHERE `autor`='{$_SESSION['uid']}'
	ORDER BY `tickets`.`priority` DESC, `tickets`.`to_date`, `tickets`.`date`");
	if(mysql_errno())	throw new MysqlException("Не удалось получить данные задачи");
	$i=0;
	while($nxt=mysql_fetch_row($res))
	{
		$tmpl->addContent("<tr class='lin$i pointer' style='color: #$nxt[7]'><td><a href='?mode=view&n=$nxt[0]'>$nxt[0]</a><td>$nxt[1]<td>$nxt[2]<td>$nxt[3]<td>$nxt[4]<td>$nxt[5]<td>$nxt[6]");
		$i=1-$i;
	}
	$tmpl->addContent("</table>");
}
else if($mode=='viewall')
{
	p_menu("Все задачи");

	$tmpl->addContent("<table width='100%' class='list'><tr><th>N<th>Дата задачи<th>Тема<th>Важность<th>Автор<th>Для<th>Срок<th>Статус");
	$res=mysql_query("SELECT `tickets`.`id`, `tickets`.`date`, `tickets`.`theme`, `tickets_priority`.`name`, `a`.`name`, `tickets`.`to_date`, `tickets_state`.`name`, `tickets_priority`.`color`, `t`.`name` FROM `tickets`
	LEFT JOIN `users` AS `a` ON `a`.`id`=`tickets`.`autor`
	LEFT JOIN `users` AS `t` ON `t`.`id`=`tickets`.`to_uid`
	LEFT JOIN `tickets_priority` ON `tickets_priority`.`id`=`tickets`.`priority`
	LEFT JOIN `tickets_state` ON `tickets_state`.`id`=`tickets`.`state`
	ORDER BY `tickets`.`priority` DESC, `tickets`.`to_date`, `tickets`.`date`");
	if(mysql_errno())	throw new MysqlException("Не удалось получить данные задачи");
	$i=0;
	while($nxt=mysql_fetch_row($res))
	{
		$tmpl->addContent("<tr class='lin$i pointer' style='color: #$nxt[7]'><td><a href='?mode=view&n=$nxt[0]'>$nxt[0]</a><td>$nxt[1]<td>$nxt[2]<td>$nxt[3]<td>$nxt[4]<td>$nxt[8]<td>$nxt[5]<td>$nxt[6]");
		$i=1-$i;
	}
	$tmpl->addContent("</table>");
}
else if($mode=='view')
{
	$n=rcv('n');
	p_menu("Просмотр задачи N$n");
	ShowTicket($n);

}
else if($mode=='set')
{
	if(!isAccess('generic_tickets','edit'))	throw new AccessException("Недостаточно привилегий");
	$opt=rcv('opt');
	$n=rcv('n');
	$txt='';
	if($opt=='state')
	{
		$state=rcv('state');
		$res=mysql_query("SELECT `name` FROM `tickets_state` WHERE `id`='$state'");
		$st_text=mysql_result($res,0,0);

		mysql_query("UPDATE `tickets` SET `state`='$state' WHERE `id`='$n'");
		$txt="Установил статус *$st_text*";
	}
	if($opt=='comment')
	{
		$comment=rcv('comment');
		$txt="сказал: $comment";
	}
	if($opt=='to_date')
	{
		$to_date=rcv('to_date');

		mysql_query("UPDATE `tickets` SET `to_date`='$to_date' WHERE `id`='$n'");
		$txt="Установил срок *$to_date*";
	}
	if($opt=='prio')
	{
		$prio=rcv('prio');
		$res=mysql_query("SELECT `name` FROM `tickets_priority` WHERE `id`='$prio'");
		$st_text=mysql_result($res,0,0);

		mysql_query("UPDATE `tickets` SET `priority`='$prio' WHERE `id`='$n'");
		$txt="Установил приоритет *$st_text ($prio)*";
	}
	if($opt=='to_user')
	{
		$user_id=rcv('user_id');
		$res=mysql_query("SELECT `name` FROM `users` WHERE `id`='$user_id'");
		$user_name=mysql_result($res,0,0);

		mysql_query("UPDATE `tickets` SET `to_uid`='$user_id' WHERE `id`='$n'");
		$txt=" переназначил задачу на пользователя $user_name ID $user_id";
	}
	if($txt)
	{
		mysql_query("INSERT INTO `tickets_log` (`uid`, `ticket`, `date`, `text`)
		VALUES ('$uid', '$n', NOW(), '$txt')");


		$res=mysql_query("SELECT `users`.`reg_email`, `users`.`jid`, `tickets`.`theme` FROM `tickets`
		LEFT JOIN `users` AS `users` ON `users`.`id`=`tickets`.`autor`
		WHERE `tickets`.`id`='$n'");
		list($email,$jid,$theme)=mysql_fetch_row($res);

		$msg="Изменение состояния Вашего задания: $theme\n{$_SESSION['name']} $txt\n\n";
		$msg.="Посмотреть задание можно здесь: http://{$CONFIG['site']['name']}/ticket.php/mode=view&n=$n";

		try
		{
			mailto($email, "Change ticket - $theme", $msg);
		}
		catch(Exception $e)
		{
			$tmpl->logger("Невозможно отправить сообщение email!");
		}

		if($jid)
		{
			try
			{
				require_once($CONFIG['location'].'/common/XMPPHP/XMPP.php');
				$xmppclient = new XMPPHP_XMPP( $CONFIG['xmpp']['host'], $CONFIG['xmpp']['port'], $CONFIG['xmpp']['login'], $CONFIG['xmpp']['pass'], 'xmpphp', '');
				$xmppclient->connect();
				$xmppclient->processUntil('session_start');
				$xmppclient->presence();
				$xmppclient->message($jid, $msg);
				$xmppclient->disconnect();
				$tmpl->msg("Сообщение было отправлено!","ok");
			}
			catch(Exception $e)
			{
				$tmpl->logger("Невозможно отправить сообщение XMPP!");
			}
		}
	}

	p_menu("Корректировка задачи N$n");
	$tmpl->msg("Сделано!");
	ShowTicket($n);

}





$tmpl->write();
?>
