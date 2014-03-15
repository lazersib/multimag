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

/// Отчёт по проплатам за период
class Report_Payments {

	function getName($short = 0) {
		if ($short)	return "По проплатам";
		else		return "Отчёт по проплатам за период";
	}

	function Form() {
		global $tmpl;
		$date_end = date("Y-m-d");
		$tmpl->addContent("<h1>" . $this->getName() . "</h1>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='payments'>
		<input type='hidden' name='opt' value='make'>
		<p class='datetime'>
		Дата от:<input type='text' id='id_pub_date_date' class='vDateField required' name='date_st' size='10' value='1970-01-01' maxlength='10'><br>
		до:<input type='text' id='id_pub_date_date' class='vDateField required' name='date_end' size='10' value='$date_end' maxlength='10'>
		</p>
		<label><input type=checkbox name=tov value=1>Товары в документах</label><br>
		<button type='submit'>Создать отчет</button></form>");
	}

	function MakeHTML() {
		global $tmpl, $db;
		$tov = rcvint("tov");
		$agent = rcvint('agent');
		$date_st = strtotime(rcvdate('date_st'));
		$date_end = strtotime(rcvdate('date_end')) + 60 * 60 * 24 - 1;
		if (!$date_end)		$date_end = time();
		$tmpl->loadTemplate('print');

		$tmpl->setContent("<h1>" . $this->getName() . "</h1>
		c " . date("d.m.Y", $date_st) . " по " . date("d.m.Y", $date_end));

		$res = $db->query("SELECT `doc_list`.`id`,`doc_list`.`type`,`doc_list`.`date`,`doc_list`.`sum`,
		`doc_list`.`altnum`, `doc_agent`.`name`
		FROM `doc_list`
		LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
		WHERE `doc_list`.`ok`!='0' AND `doc_list`.`date`>='$date_st' AND `doc_list`.`date`<='$date_end'");

		$tmpl->addContent("<table width='100%'>
		<tr><th width='30%'>N док-та, дата, партнер</th><th>Операция</th><th>Дебет</th><th>Кредит</th></tr>");
		$pr = $ras = 0;
		while ($nxt = $res->fetch_row()) {
			$deb = $kr = "";

			if ($nxt[1] == 1) {
				$tp = "Поступление";
				$pr+=$nxt[3];
				$deb = $nxt[3];
			} else if ($nxt[1] == 2) {
				$tp = "Реализация";
				$ras+=$nxt[3];
				$kr = $nxt[3];
			}
			if ($nxt[1] == 3) {
				$tp = "-";
				continue;
			}
			if ($nxt[1] == 4) {
				$tp = "Оплата";
				$pr+=$nxt[3];
				$deb = $nxt[3];
			}
			if ($nxt[1] == 5) {
				$tp = "Возврат";
				$ras+=$nxt[3];
				$kr = $nxt[3];
			}
			if ($nxt[1] == 6) {
				$tp = "Оплата";
				$pr+=$nxt[3];
				$deb = $nxt[3];
			}
			if ($nxt[1] == 7) {
				$tp = "Возврат";
				$ras+=$nxt[3];
				$kr = $nxt[3];
			}

			if ($tov) {
				$rs = $db->query("SELECT `doc_base`.`name`,`doc_list_pos`.`cnt`,`doc_list_pos`.`cost` FROM `doc_list_pos`
				LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
				WHERE `doc_list_pos`.`doc`='$nxt[0]'");
				if ($rs->num_rows) {
					$tp = "<b>$tp</b><table width=100%><tr><th>Товар<th width=20%>Кол-во<th width=20%>Цена";
					while ($nx = $rs->fetch_row())
						$tp.="<tr><td>".html_out($nx[0])."<td>$nx[1] шт.<td>$nx[2] руб.";
					$tp.="</table>";
				}
			}
			if ($deb)	$deb = sprintf("%01.2f", $deb);
			if ($kr)	$kr = sprintf("%01.2f", $kr);
			$dt = date("d.m.Y", $nxt[2]);
			$tmpl->addContent("<tr><td>$nxt[4] ($nxt[0])<br>$dt<br>".html_out($nxt[5])."<td>$tp<td>$deb<td>$kr");
		}

		$razn = sprintf("%01.2f", $pr - $ras);
		$pr = sprintf("%01.2f", $pr);
		$ras = sprintf("%01.2f", $ras);

		$tmpl->addContent("<tr><td>-<td>Обороты за период<td>$pr<td>$ras
		<tr><td colspan=4>");
		if ($razn > 0)	$tmpl->addContent("переплата $razn руб.");
		else		$tmpl->addContent("задолженность $razn руб.");

		$tmpl->addContent("</table>");
	}

	function Run($opt) {
		if ($opt == '')	$this->Form();
		else		$this->MakeHTML();
	}
}
?>