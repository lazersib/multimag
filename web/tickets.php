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

include_once("core.php");
need_auth();

/// Класс, реализующий простейший треккер задач
class TaskTracker {

	/// Меню треккера задач
	function PMenu($dop = '') {
		global $tmpl;
		$tmpl->addContent("<h1>Планировщик задач - $dop</h1>");
		$tmpl->setTitle("Планировщик задач - $dop");
		$tmpl->addContent("<a href='?mode='>Невыполненные задачи для меня</a> | <a href='?mode=new'>Новая задача</a> | <a href='?mode=viewall'>Все задачи</a> | <a href='?mode=my'>Мои задачи</a>");
	}

/// Показать задачу
/// @param n Номер задачи
	function ShowTicket($n) {
		global $tmpl, $db;
		settype($n, 'int');
		$res = $db->query("SELECT `tickets`.`id`, `tickets`.`date`, `tickets`.`theme`, `tickets_priority`.`name` AS `prio_name`,
			`a`.`name` AS `author_name`, `tickets`.`to_date`, `tickets_state`.`name` AS `state_name`, `tickets`.`text`, `tickets`.`state`
		FROM `tickets`
		LEFT JOIN `users` AS `a` ON `a`.`id`=`tickets`.`autor`
		LEFT JOIN `tickets_priority` ON `tickets_priority`.`id`=`tickets`.`priority`
		LEFT JOIN `tickets_state` ON `tickets_state`.`id`=`tickets`.`state`
		WHERE `tickets`.`id`='$n'");
		$nxt = $res->fetch_assoc();
		if (!$nxt)	$tmpl->msg("Задача не найдена!", "err");
		else {
			//<b>Исполнитель:</b> " . html_out($nxt[7]) . "<br>
			$tmpl->addContent("<h2>" . html_out($nxt['theme']) . "</h2>
			<b>Дата создания:</b> {$nxt['date']}<br>
			<b>Важность:</b> {$nxt['prio_name']}<br>
			<b>Автор:</b> {$nxt['author_name']}<br>");
			$res = $db->query("SELECT `users`.`name`, `users_worker_info`.`worker_real_name` FROM `tickets_responsibles`
				INNER JOIN `users` ON `users`.`id`=`tickets_responsibles`.`user_id`
				LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`tickets_responsibles`.`user_id`
				WHERE `tickets_responsibles`.`ticket_id` = $n");
			while ($_line = $res->fetch_assoc()) {
				$tmpl->addContent("<b>Исполнитель:</b> " . html_out($_line['worker_real_name']) . " (" . html_out($_line['name']) . ")<br>");
			}
			$tmpl->addContent("<b>Срок:</b> {$nxt['to_date']}<br>
			<b>Состояние:</b> {$nxt['state_name']}<br>
			<b>Описание:</b> " . html_out($nxt['text']) . "<br>
			<b>История:</b>
			<ul>");
			$res = $db->query("SELECT `users`.`name`, `tickets_log`.`date`, `tickets_log`.`text` FROM `tickets_log`
			LEFT JOIN `users` ON `users`.`id`=`tickets_log`.`uid`
			WHERE `ticket`='{$nxt['id']}'");
			while ($nx = $res->fetch_row())
				$tmpl->addContent("<li><i>".html_out($nx[1])."</i>, <b>$nx[0]:</b> ".html_out($nx[2])."</li>");

			$tmpl->addContent("</ul><br>
			<form action='' method='post'>
			<input type='hidden' name='mode' value='set'>
			<input type='hidden' name='opt' value='accept'>
			<input type='hidden' name='n' value='{$nxt['id']}'>
			<input type='submit' value='Принять'></form>
			
			<br><fieldset><legend>Установить статус</legend>
			<form action='' method='post'>
			<input type='hidden' name='mode' value='set'>
			<input type='hidden' name='opt' value='state'>
			<input type='hidden' name='n' value='{$nxt['id']}'>
			<select name='state'>");
			$res = $db->query("SELECT `id`, `name` FROM `tickets_state` WHERE `id`!='{$nxt['state']}'");
			while ($nx = $res->fetch_row())
				$tmpl->addContent("<option value='$nx[0]'>$nx[1]</option>");

			$tmpl->addContent("</select><input type='submit' value='Сменить'></form></fieldset>
			<fieldset><legend>Добавить коментарий:</legend>
			<form action='' method='post'>
			<input type='hidden' name='mode' value='set'>
			<input type='hidden' name='opt' value='comment'>
			<input type='hidden' name='n' value='{$nxt['id']}'>
			<textarea name='comment'></textarea><br>
			<input type='submit' value='Добавить'></form></fieldset>

			<fieldset><legend>Изменить срок:</legend>
			<form action='' method='post'>
			<input type='hidden' name='mode' value='set'>
			<input type='hidden' name='opt' value='to_date'>
			<input type='hidden' name='n' value='{$nxt['id']}'>
			<input type='text' name='to_date' class='vDateField' value='{$nxt['to_date']}'>
			<input type='submit' value='Изменить'></form></fieldset>

			<fieldset><legend>Добавить исполнителя:</legend>
			<form action='' method='post'>
			<input type='hidden' name='mode' value='set'>
			<input type='hidden' name='opt' value='add_user'>
			<input type='hidden' name='n' value='{$nxt['id']}'>
			<select name='user_id'>");

			$res = $db->query("SELECT `users`.`id`, `users`.`name`, `users_worker_info`.`worker_real_name`
			FROM `users`
			INNER JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`users`.`id`
			WHERE `users_worker_info`.`worker`>'0' AND `users`.`id` NOT IN 
				(SELECT `user_id` FROM `tickets_responsibles` WHERE `tickets_responsibles`.`ticket_id` = $n) 
			ORDER BY `users`.`name`");
			while ($nx = $res->fetch_row()) {
				if ($nx[0] == 0)	continue;
				$tmpl->addContent("<option value='$nx[0]'>$nx[1] - $nx[2] ($nx[0])</option>");
			}
			$tmpl->addContent("</select>
			<input type='submit' value='Изменить'></form></fieldset>


			<fieldset><legend>Убрать исполнителя:</legend>
			<form action='' method='post'>
			<input type='hidden' name='mode' value='set'>
			<input type='hidden' name='opt' value='del_user'>
			<input type='hidden' name='n' value='{$nxt['id']}'>
			<select name='user_id'>");

			$res = $db->query("SELECT `users`.`id`, `users`.`name`, `users_worker_info`.`worker_real_name` FROM `tickets_responsibles`
				INNER JOIN `users` ON `users`.`id`=`tickets_responsibles`.`user_id`
				LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`tickets_responsibles`.`user_id`
				WHERE `tickets_responsibles`.`ticket_id` = $n");
			while ($nx = $res->fetch_row()) {
				if ($nx[0] == 0)	continue;
				$tmpl->addContent("<option value='$nx[0]'>$nx[1] - $nx[2] ($nx[0])</option>");
			}
			$tmpl->addContent("</select>
			<input type='submit' value='Изменить'></form></fieldset>

			<fieldset><legend>Изменить приоритет:</legend>
			<form action='' method='post'>
			<input type='hidden' name='mode' value='set'>
			<input type='hidden' name='opt' value='prio'>
			<input type='hidden' name='n' value='{$nxt['id']}'>
			<select name='prio'>");
			$res = $db->query("SELECT `id`, `name`, `color` FROM `tickets_priority` ORDER BY `id`");
			while ($nx = $res->fetch_row())
				$tmpl->addContent("<option value='$nx[0]' style='color: #$nx[2]'>$nx[1] ($nx[0])</option>");
			$tmpl->addContent("</select><input type='submit' value='Изменить'></form></fieldset>");
		}
	}

	/// Формирует список задач текущего пользователя
	function ShowMyTickets() {
		global $tmpl, $db;
		$this->PMenu("Задачи для меня");
		$tmpl->addContent("<table width='100%' class='list'><tr><th>N<th>Дата задачи<th>Тема<th>Важность<th>Автор<th>Срок<th>Статус");
		$res = $db->query("SELECT `tickets`.`id`, `tickets`.`date`, `tickets`.`theme`, `tickets_priority`.`name`, `users`.`name`,
			`tickets`.`to_date`, `tickets_state`.`name`, `tickets_priority`.`color` FROM `tickets`
			INNER JOIN `tickets_responsibles` ON `tickets_responsibles`.`ticket_id` = `tickets`.`id` AND `tickets_responsibles`.`user_id`='{$_SESSION['uid']}'
			LEFT JOIN `users` ON `users`.`id`=`tickets`.`autor`
			LEFT JOIN `tickets_priority` ON `tickets_priority`.`id`=`tickets`.`priority`
			LEFT JOIN `tickets_state` ON `tickets_state`.`id`=`tickets`.`state`
			WHERE `tickets`.`state`<'2'
			ORDER BY `tickets`.`priority` DESC, `tickets`.`to_date` DESC, `tickets`.`date`");
		$i = 0;
		while ($nxt = $res->fetch_row()) {
			$tmpl->addContent("<tr class='lin$i pointer' style='color: #$nxt[7]'><td><a href='?mode=view&n=$nxt[0]'>$nxt[0]</a><td>$nxt[1]<td>".html_out($nxt[2])."<td>$nxt[3]<td>".html_out($nxt[4])."<td>$nxt[5]<td>$nxt[6]");
			$i = 1 - $i;
		}
		$tmpl->addContent("</table>");
	}

	/// Формирует форму создания задачи
	function ShowNewTicketForm() {
		global $tmpl, $db;
		if (!isAccess('generic_tickets', 'create'))	throw new AccessException();
		$this->PMenu("Новая задача");
		$tmpl->addContent("<form action='' method='post'>
		<input type='hidden' name='mode' value='add'>
		Задача для:<br>
		<select name='to_uid'>");
		$res = $db->query("SELECT `users`.`id`, `users`.`name`, `users_worker_info`.`worker_real_name`
		FROM `users`
		INNER JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`users`.`id`
		WHERE `users_worker_info`.`worker`>'0' ORDER BY `users`.`name`");
		while ($nxt = $res->fetch_row()) {
			if ($nxt[0] == 0)	continue;
			$tmpl->addContent("<option value='$nxt[0]'>$nxt[1] - $nxt[2] ($nxt[0])</option>");
		}
		$tmpl->addContent("</select><br>Название:<br><input type='text' name='theme'><br>
		Важность, приоритет:<br><select name='prio'>");
		$res = $db->query("SELECT `id`, `name`, `color` FROM `tickets_priority` ORDER BY `id`");
		while ($nxt = $res->fetch_row())
			$tmpl->addContent("<option value='$nxt[0]' style='color: #$nxt[2]'>$nxt[1] ($nxt[0])</option>");

		$tmpl->addContent("</select><br>
		Срок (указывать не обязательно):<br>
		<input type='text' name='to_date'  class='vDateField'><br>
		Описание задачи:<br>
		<textarea name='text'></textarea><br>
		<input type='submit' value='Назначить задачу'>
		</form>");
	}

}

try {

if (!isAccess('generic_tickets', 'view'))	throw new AccessException();

$tt = new TaskTracker();
$mode = request('mode');

if ($mode == '')
	$tt->ShowMyTickets();
else if ($mode == 'new') {
	$tt->ShowNewTicketForm();
	
} else if ($mode == 'add') {
	if (!isAccess('generic_tickets', 'create'))	throw new AccessException();
	$tt->PMenu("Сохранение задачи");
	$uid = @$_SESSION['uid'];
	$to_uid = rcvint('to_uid');
	$theme = request('theme');
	$prio = rcvint('prio');
	$to_date = rcvdate('to_date');
	$text = request('text');

	$theme_sql = $db->real_escape_string($theme);
	$text_sql = $db->real_escape_string($text);

	$db->query("INSERT INTO `tickets` (`date`, `autor`, `priority`, `theme`, `text`, `to_date`)
	VALUES ( NOW(), '$uid', '$prio', '$theme_sql', '$text_sql', '$to_date')");
	$n = $db->insert_id;
	$db->query("INSERT INTO `tickets_responsibles` (`ticket_id`, `user_id`) VALUES ($n, $to_uid)");
	$tmpl->msg("Задание назначено!", "ok");
	

	$res = $db->query("SELECT `reg_email` FROM `users` WHERE `id`='$to_uid'");
	list($email) = $res->fetch_row();

	$msg = "Для Вас новое задание от $uid: $theme - $text\n";
	if ($to_date)
		$msg.="Выполнить до $to_date\n";
	$msg.="Посмотреть задание можно здесь: http://{$CONFIG['site']['name']}/tickets.php/mode=view&n=$n";

	mailto($email, "У Вас Новое задание - $theme", $msg);

	$tt->ShowTicket($n);
}
else if ($mode == 'my') {
	$tt->PMenu("Мои задачи");

	$tmpl->addContent("<table width='100%' class='list'><tr><th>N<th>Дата задачи<th>Тема<th>Важность<th>Для<th>Срок<th>Статус");
	$res = $db->query("SELECT `tickets`.`id`, `tickets`.`date`, `tickets`.`theme`, `tickets_priority`.`name`, '', `tickets`.`to_date`, `tickets_state`.`name`, `tickets_priority`.`color` FROM `tickets`
	LEFT JOIN `tickets_priority` ON `tickets_priority`.`id`=`tickets`.`priority`
	LEFT JOIN `tickets_state` ON `tickets_state`.`id`=`tickets`.`state`
	WHERE `autor`='{$_SESSION['uid']}'
	ORDER BY `tickets`.`priority` DESC, `tickets`.`to_date`, `tickets`.`date`");
	$i = 0;
	while ($nxt = $res->fetch_row()) {
		$tmpl->addContent("<tr class='lin$i pointer' style='color: #$nxt[7]'><td><a href='?mode=view&n=$nxt[0]'>$nxt[0]</a><td>$nxt[1]<td>".html_out($nxt[2])."<td>$nxt[3]<td>".html_out($nxt[4])."<td>$nxt[5]<td>$nxt[6]");
		$i = 1 - $i;
	}
	$tmpl->addContent("</table>");
}
else if ($mode == 'viewall') {
	$tt->PMenu("Все задачи");

	$tmpl->addContent("<table width='100%' class='list'><tr><th>N<th>Дата задачи<th>Тема<th>Важность<th>Автор<th>Для<th>Срок<th>Статус");
	$res = $db->query("SELECT `tickets`.`id`, `tickets`.`date`, `tickets`.`theme`, `tickets_priority`.`name`, `a`.`name`, `tickets`.`to_date`, `tickets_state`.`name`, `tickets_priority`.`color`, '' FROM `tickets`
	LEFT JOIN `users` AS `a` ON `a`.`id`=`tickets`.`autor`
	LEFT JOIN `tickets_priority` ON `tickets_priority`.`id`=`tickets`.`priority`
	LEFT JOIN `tickets_state` ON `tickets_state`.`id`=`tickets`.`state`
	ORDER BY `tickets`.`priority` DESC, `tickets`.`to_date`, `tickets`.`date`");
	$i = 0;
	while ($nxt = $res->fetch_row()) {
		$tmpl->addContent("<tr class='lin$i pointer' style='color: #$nxt[7]'><td><a href='?mode=view&n=$nxt[0]'>$nxt[0]</a><td>$nxt[1]<td>".html_out($nxt[2])."<td>$nxt[3]<td>".html_out($nxt[4])."<td>".html_out($nxt[8])."<td>$nxt[5]<td>$nxt[6]");
		$i = 1 - $i;
	}
	$tmpl->addContent("</table>");
}
else if ($mode == 'view') {
	$n = rcvint('n');
	$tt->PMenu("Просмотр задачи N$n");
	$tt->ShowTicket($n);
}
else if ($mode == 'set') {
	if (!isAccess('generic_tickets', 'edit'))	throw new AccessException();
	$opt = request('opt');
	$n = rcvint('n');
	$txt = '';
	if ($opt == 'accept') {
		$txt = "принял задачу";
	}
	if ($opt == 'state') {
		$state = rcvint('state');
		$res = $db->query("SELECT `name` FROM `tickets_state` WHERE `id`='$state'");
		list($st_text) = $res->fetch_row();

		$db->query("UPDATE `tickets` SET `state`='$state' WHERE `id`='$n'");
		$txt = "Установил статус *$st_text*";
	}
	if ($opt == 'comment') {
		$comment = request('comment');
		$txt = "прокоментировал: $comment";
	}
	if ($opt == 'to_date') {
		$to_date = rcvdate('to_date');

		$db->query("UPDATE `tickets` SET `to_date`='$to_date' WHERE `id`='$n'");
		$txt = "Установил срок *$to_date*";
	}
	if ($opt == 'prio') {
		$prio = rcvint('prio');
		$res = $db->query("SELECT `name` FROM `tickets_priority` WHERE `id`='$prio'");
		list($st_text) = $res->fetch_row();

		$db->query("UPDATE `tickets` SET `priority`='$prio' WHERE `id`='$n'");
		$txt = "Установил приоритет *$st_text ($prio)*";
	}
	if ($opt == 'add_user') {
		$user_id = rcvint('user_id');
		$res = $db->query("SELECT `name` FROM `users` WHERE `id`='$user_id'");
		list($user_name) = $res->fetch_row();

		$db->query("INSERT INTO `tickets_responsibles` (`ticket_id`, `user_id`) VALUES ($n, $user_id)");
		$txt = "добавил исполнителя $user_name ID $user_id";
	}
	if ($opt == 'del_user') {
		$user_id = rcvint('user_id');
		$res = $db->query("SELECT `name` FROM `users` WHERE `id`='$user_id'");
		list($user_name) = $res->fetch_row();

		$db->query("DELETE FROM `tickets_responsibles` WHERE `ticket_id`=$n AND `user_id`=$user_id");
		$txt = "убрал исполнителя $user_name ID $user_id";
	}
        else {
            throw new \NotFoundException('Неверная опция');
        }
	if ($txt) {
		$txt_sql = $db->real_escape_string($txt);
		$db->query("INSERT INTO `tickets_log` (`uid`, `ticket`, `date`, `text`)
		VALUES ('$uid', '$n', NOW(), '$txt_sql')");

		$res = $db->query("SELECT `users`.`reg_email`, `users`.`jid`, `tickets`.`theme` FROM `tickets`
		LEFT JOIN `users` AS `users` ON `users`.`id`=`tickets`.`autor`
		WHERE `tickets`.`id`='$n'");
		list($email, $jid, $theme) = $res->fetch_row();

		$msg = "Изменение состояния Вашего задания: $theme\n{$_SESSION['name']} $txt\n\n";
		$msg.="Посмотреть задание можно здесь: http://{$CONFIG['site']['name']}/ticket.php/mode=view&n=$n";

		try {
			if($email) mailto($email, "Change ticket - $theme", $msg);
		} catch (Exception $e) {
                    writeLogException($e);
                    $tmpl->errorMessage("Невозможно отправить сообщение email!");
		}

		if ($jid && @$CONFIG['xmpp']['host']) {
			try {
				require_once($CONFIG['location'] . '/common/XMPPHP/XMPP.php');
				$xmppclient = new XMPPHP_XMPP($CONFIG['xmpp']['host'], $CONFIG['xmpp']['port'], $CONFIG['xmpp']['login'], $CONFIG['xmpp']['pass'], 'xmpphp', '');
				$xmppclient->connect();
				$xmppclient->processUntil('session_start');
				$xmppclient->presence();
				$xmppclient->message($jid, $msg);
				$xmppclient->disconnect();
				$tmpl->msg("Сообщение было отправлено!", "ok");
			} catch (Exception $e) {
                            writeLogException($e);
                            $tmpl->errorMessage("Невозможно отправить сообщение XMPP!");
			}
		}
	}

	$tt->PMenu("Корректировка задачи N$n");
	$tmpl->msg("Сделано!");
	$tt->ShowTicket($n);
}
    else {
        throw new \NotFoundException('Неверный параметр');
    }
}
catch(mysqli_sql_exception $e) {
    $tmpl->ajax=0;
    $id = writeLogException($e);
    $tmpl->errorMessage("Порядковый номер ошибки: $id<br>Сообщение передано администратору", "Ошибка в базе данных");
}
catch(Exception $e) {
    writeLogException($e);
    $tmpl->errorMessage($e->getMessage());
}

$tmpl->write();
