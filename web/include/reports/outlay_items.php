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
/// Отчёт по статьям расходов
class Report_Outlay_Items extends BaseReport {

	function getName($short = 0) {
		if ($short)	return "По статьям расходов";
		else		return "Отчёт по статьям расходов";
	}

	function Form() {
		global $tmpl, $db;
		$curdate = date("Y-m-d");
		$tmpl->addContent("<h1>" . $this->getName() . "</h1>
		<script src='/js/calendar.js'></script>
		<form action=''>
		<input type='hidden' name='mode' value='outlay_items'>
		Выберите фирму:<br>
		<select name='firm'>");
		$res = $db->query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `id`");
		$tmpl->addContent("<option value='0'>--не выбрана--</option>");
		while ($nxt = $res->fetch_row())
			$tmpl->addContent("<option value='$nxt[0]'>".html_out($nxt[1])."</option>");
		$tmpl->addContent("</select><br>
		Начальная дата:<br>
		<input type='text' name='date_f' id='datepicker_f' value='$curdate'><br>
		Конечная дата:<br>
		<input type='text' name='date_t' id='datepicker_t' value='$curdate'><br>
		Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
		<button type='submit'>Сформировать</button></form>
		<script type=\"text/javascript\">
		initCalendar('datepicker_f',false);
		initCalendar('datepicker_t',false);
		</script>
		");
	}

	function Make($engine) {
		global $db;
		$this->loadEngine($engine);

		$dt_f = rcvdate('date_f');
		$dt_t = rcvdate('date_t');
		$firm = rcvint('firm');

		$this->header($this->getName() . " с $dt_f по $dt_t");

		$daystart = strtotime("$dt_f 00:00:00");
		$dayend = strtotime("$dt_t 23:59:59");

		$widths = array(5, 18, 47, 20, 10);
		$headers = array('ID', 'Дата, время', 'Агент', 'Документ', 'Сумма');

		$this->col_cnt = count($widths);
		$this->tableBegin($widths);
		$this->tableHeader($headers);
		
		$sql_add = $firm?" AND `doc_list`.`firm_id` = '$firm'":'';
		
		$res_vr = $db->query("SELECT `id`, `name` FROM `doc_rasxodi` ORDER BY `id`");
		while ($vr = $res_vr->fetch_row()) {
			$this->tableAltStyle();
			$this->tableSpannedRow(array($this->col_cnt), array("$vr[0]. $vr[1]"));
			$this->tableAltStyle(false);
			$sum = 0;
			$res = $db->query("SELECT `doc_list`.`id` AS `doc_id`, `doc_list`.`date`, `doc_list`.`sum`, `doc_types`.`name` AS `doc_name`,
				`doc_agent`.`name` AS `agent_fullname`
			FROM `doc_list`
			INNER JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list`.`id` AND `doc_dopdata`.`param`='rasxodi' AND `doc_dopdata`.`value`='$vr[0]'
			LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
			LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
			WHERE `doc_list`.`ok`>'0' AND ( `doc_list`.`type`='5' OR `doc_list`.`type`='7') AND `doc_list`.`date`>='$daystart'
				AND `doc_list`.`date`<='$dayend' $sql_add
			ORDER BY `doc_list`.`date`");
			while ($nxt = $res->fetch_assoc()) {
				$dt = date("Y-m-d H:i:s", $nxt['date']);
				$this->tableRow(array($nxt['doc_id'], $dt, $nxt['agent_fullname'], $nxt['doc_name'], $nxt['sum']));
				$sum+=$nxt['sum'];
			}
			$this->tableSpannedRow(array(2, 2, 1), array('', 'Итого по статье:', sprintf("%0.2f", $sum)));
		}
		$this->tableEnd();
		$this->output();
		exit(0);
	}
}
?>