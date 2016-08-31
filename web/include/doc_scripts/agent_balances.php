<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2016, BlackLight, TND Team, http://tndproject.org
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

/// Сценарий автоматизации:  Получение балансов агентов c движением
class ds_agent_balances {

	function Run($mode) {
		global $tmpl, $db;
		$tmpl->hideBlock('left');
		if ($mode == 'view') {
			$date_from = date("Y-m-d", time() - 60 * 60 * 24 * 31);
			$date_to = date("Y-m-d");
			$tmpl->addContent("<form action='' method='post' enctype='multipart/form-data'>
			<input type='hidden' name='mode' value='create'>
			<input type='hidden' name='param' value='i'>
			<input type='hidden' name='sn' value='agent_balances'>
			Дата от:<br>
			<input type='text' name='date_from' value='$date_from'><br>
			Дата до:<br>
			<input type='text' name='date_to' value='$date_to'><br>
			Агент:<br>
			<input type='text' name='agent'><br>
			Договор:<br>
			<input type='text' name='dоg' disabled><br>
			<button>Рассчитать</button>
			</form>");
		} 
		else {
			$date_from = rcvdate('date_from');
			$date_to = rcvdate('date_to');
			$d_from = strtotime($date_from . " 00:00:00");
			$d_to = strtotime($date_to . " 23:59:59");
			$agent = rcvint('agent');
			$dog = rcvint('dog');
			$tmpl->addContent("<h1 id='page-title'>" . $this->getName() . "</h1>
		<table width='100%' class='list'>
		<tr><th width='5%'>ID<th width='10%'>Дата, время</th><th>Документ<th width='10%'>Приход<th width='10%'>Расход<th width='10%'>Остаток");
			if ($agent)	$this->WriteAgentDocList($d_from, $d_to, $agent, $dog);
			else {
				$res = $db->query("SELECT `id`, `name` FROM `doc_agent` ORDER BY `name`");
				while ($nxt = $res->fetch_row()) {
					$tmpl->addContent("<tr><td colspan='6' style='font-size: 1.15em; color: #000; background-color: #beb;'>".html_out($nxt[1])."</td></tr>");
					$this->WriteAgentDocList($d_from, $d_to, $nxt[0], $dog);
				}
			}
			$tmpl->addContent("</table>");
		}
	}

	function WriteAgentDocList($d_from, $d_to, $agent, $dog) {
		global $tmpl, $db;
		$res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`sum`, `doc_types`.`name` AS `doc_name`, `doc_list`.`date`,
			`doc_list`.`altnum`, `doc_list`.`subtype`
		FROM `doc_list`
		INNER JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		WHERE `doc_list`.`date`>='$d_from' AND `doc_list`.`date`<='$d_to' AND `doc_list`.`ok`>'0' AND `doc_list`.`mark_del`='0'
			AND `doc_list`.`agent`='$agent'");
		$sum = 0;
		while ($nxt = $res->fetch_row()) {
			$prix = $rasx = '';
			switch ($nxt[1]) {
				case 1:
				case 4:
				case 6: $prix = $nxt[2];
					$sum+=$nxt[2];
					break;
				case 2:
				case 5:
				case 7: $rasx = $nxt[2];
					$sum-=$nxt[2];
					break;
				case 18:$sum-=$nxt[2];
					if ($nxt[2] > 0)
						$rasx = $nxt[2];
					else
						$prix = $nxt[2];
			}
			$date_p = date("Y-m-d", $nxt[4]) . "<br><small>" . date("H:i:s", $nxt[4]) . "</small>";
			$tmpl->addContent("<tr class='pointer'><td align='right'><a href='/doc.php?mode=body&doc=$nxt[0]'>$nxt[0]</a></td><td align='right'>$date_p</td><td>".html_out("{$nxt[3]} N{$nxt[5]}{$nxt[6]}")."</td> <td>$prix</td><td>$rasx</td><td>$sum</td></tr>");
		}
	}

	function getName() {
		return "Балансы агентов c движением";
	}
}
