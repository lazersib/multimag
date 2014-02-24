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

$colors = array('000', 'C40', '0C0', '00C', 'C90', 'C04', '80C', '08C', 'CF0');

try {

	$tmpl->SetTitle("Голосования");
	if (@$_REQUEST['mode'] == '') {
		$tmpl->addContent("<h1>Голосования</h1>");
		$res = $db->query("SELECT `id`, `name`, `end_date` FROM `votings` WHERE `start_date`<=NOW() AND `end_date`>=NOW()");
		if ($res->num_rows) {
			$tmpl->addContent("<h2>Активные голосования</h2><ul class='items'>");
			while ($nxt = $res->fetch_row())
				$tmpl->addContent("<li><a href='/voting.php?mode=vv&amp;vote_id=$nxt[0]'>$nxt[1]</a> - <i><b>Закончится:</b> $nxt[2]</i></li>");
			$tmpl->addContent("</ul>");
		}
		else
			$tmpl->addContent("<h2>Активные голосования на данный момент отсутствуют!</h2>");

		$res = $db->query("SELECT `id`, `name`, `end_date` FROM `votings` WHERE `end_date`<=NOW()");
		if ($res->nun_rows) {
			$tmpl->addContent("<h2>Прошедшие голосования</h2><ul class='items'>");
			while ($nxt = $res->fetch_row())
				$tmpl->addContent("<li><a href='/voting.php?mode=vv&amp;vote_id=$nxt[0]'>$nxt[1]</a> - <i><b>Закончилось:</b> $nxt[2]</i></li>");
			$tmpl->addContent("</ul>");
		}
	} else if (@$_REQUEST['mode'] == 'vv') {
		$vote_id = rcvint('vote_id');
		$res = $db->query("SELECT `id`, `name`, `start_date`, `end_date` FROM `votings` WHERE `id`='$vote_id'");
		if (!$res->num_rows)		throw new Exception("Голосование не найдено");
		$vote_data = $res->fetch_assoc();
		$tmpl->SetTitle("{$vote_data['name']} - голосование");
		$tmpl->addContent("<h1>Голосование: {$vote_data['name']}</h1><div id='page-info'>Проходит с {$vote_data['start_date']} по {$vote_data['end_date']}</div>");

		if (isset($_REQUEST['opt'])) {
			$variant = @$_REQUEST['variant'];
			settype($variant, 'int');
			if (time() < strtotime($vote_data['start_date']))
				$tmpl->msg("Голосование ещё не началось", 'err');
			else if (time() > strtotime($vote_data['end_date'] . ' 23:59:59'))
				$tmpl->msg("Голосование уже закончилось", 'err');
			else if ($variant < 1)
				$tmpl->msg("Вы не выбрани вариант ответа", 'err');
			else {
				$res = $db->query("SELECT `variant_id`, `text` FROM `votings_vars` WHERE `voting_id`='$vote_id' AND `variant_id`='$variant'");
				if (!$res->num_rows)	throw new Exception("Вариант не найден!");
				$ip = $db->real_escape_string(getenv("REMOTE_ADDR"));
				if (@$_SESSION['uid'])	$uid = intval($_SESSION['uid']);
				else			$uid = 'NULL';
				$db->query("INSERT INTO `votings_results` (`voting_id`, `variant_id`, `user_id`, `ip_addr`) VALUES ($vote_id, $variant, $uid, '$ip')");
			}
		}

		if (@$_SESSION['uid']) {
			$uid = intval($_SESSION['uid']);
			$where = "`votings_results`.`user_id`='$uid'";
		} else {
			$ip = $db->real_escape_string(getenv("REMOTE_ADDR"));
			$where = "`votings_results`.`ip_addr`='$ip'";
		}
		$res = $db->query("SELECT `id` FROM `votings_results` WHERE $where");
		if (! $res->num_rows && (time() < strtotime($vote_data['end_date'] . ' 23:59:59')) && (time() > strtotime($vote_data['start_date']))) { //Выводим форму голосования
			$tmpl->addContent("<form action='/voting.php' method='post'>
			<input type='hidden' name='mode' value='vv'>
			<input type='hidden' name='opt' value='ok'>
			<input type='hidden' name='vote_id' value='$vote_id'>");
			$res = $db->query("SELECT `variant_id`, `text` FROM `votings_vars` WHERE `voting_id`='$vote_id'");
			while ($nxt = $res->fetch_row())
				$tmpl->addContent("<label><input type='radio' name='variant' value='$nxt[0]'>$nxt[1]</label><br>");
			$tmpl->addContent("<button type='submit'>Голосовать</button></form>");
		} else { //Выводим результаты
			$tmpl->addContent("<table class='list'>");
			$res = $db->query("SELECT `votings_vars`.`variant_id`, `votings_vars`.`text`,
		( SELECT COUNT(`votings_results`.`id`) FROM `votings_results` WHERE `votings_results`.`voting_id`='$vote_id' AND `votings_results`.`variant_id`=`votings_vars`.`variant_id`) AS `rate`,
		( SELECT COUNT(`votings_results`.`id`) FROM `votings_results` WHERE `votings_results`.`voting_id`='$vote_id') AS `all`
		FROM `votings_vars` WHERE `voting_id`='$vote_id'");
			while ($nxt = $res->fetch_row()) {
				$pp = intval($nxt[2] / $nxt[3] * 100);
				$tmpl->addContent("<tr style='height: 35px'><td>$nxt[1]<br><div style='border: 1px solid #000000;background:#{$colors[$nxt[0]]};margin:0;height: 5px;width:$pp%;'></td><td>&nbsp;</td><td>$pp %</td></tr>");
			}
			$tmpl->addContent("</table>");
		}
	}
} catch (Exception $e) {
	$tmpl->addContent("<br><br>");
	$tmpl->logger($e->getMessage());
}


$tmpl->write();
?>