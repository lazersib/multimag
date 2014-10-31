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

/// Отчёт по задолженностям по агентам
class Report_Dolgi extends BaseReport {

	function getName($short = 0) {
		if ($short)	return "Долги";
		else		return "Отчёт по задолженностям по агентам";
	}

	function Form() {
		global $tmpl, $db;
		$curdate = date("Y-m-d");
		$tmpl->addContent("<h1>" . $this->getName() . "</h1>
		<form action=''>
		<input type='hidden' name='mode' value='dolgi'>
		<input type='hidden' name='opt' value='ok'>
		Дата:<br>
		<input type='text' name='date' id='date' value='$curdate'><br>
		Организация:<br>
		<select name='firm_id'>
		<option value='0'>--все--</option>");
		$fres = $db->query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `id`");
		while ($nxt = $fres->fetch_row()) {
			$tmpl->addContent("<option value='$nxt[0]'>".html_out($nxt[1])."</option>");
		}
		$tmpl->addContent("</select><br>
		Группа агентов:<br>
		<select name='agroup'>
		<option value='0'>--все--</option>");
		$res = $db->query("SELECT `id`, `name` FROM `doc_agent_group` ORDER BY `name`");
		while ($nxt = $res->fetch_row()) {
			$tmpl->addContent("<option value='$nxt[0]'>".html_out($nxt[1])."</option>");
		}
		$tmpl->addContent("</select><br>
		<fieldset><legend>Вид задолженности</legend>
		<label><input type='radio' name='vdolga' value='1' checked>Нам должны</label><br>
		<label><input type='radio' name='vdolga' value='2'>Мы должны</label>
		</fieldset><br>
		Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
		<button type='submit'>Сформировать</button></form>
		<script>
		initCalendar('date',false);
		</script>");
	}

	function Make($engine) {
		global $db;
		$vdolga = request('vdolga');
		$agroup = rcvint('agroup');
		$firm_id = rcvint('firm_id');
		$date = intval(strtotime(request('date')));	// Для безопасной передачи в БД
		$this->loadEngine($engine);
		
		$date_p = date("Y-m-d", $date);
                $date = strtotime($date_p.' 23:59:59');
				
		if ($vdolga == 2)	$header = "Информация по нашей задолженности на $date_p от " . date('d.m.Y');
		else			$header = "Информация о задолженности перед нашей организацией на $date_p от " . date('d.m.Y');
		$this->header($header);
		
		$widths = array(6, 46, 12, 12, 12, 12);
		$headers = array('N', 'Агент - партнер', 'Дата сверки', 'Сумма', 'Дата посл. касс. док-та', 'Дата посл. банк. док-та');
		$this->tableBegin($widths);
		$this->tableHeader($headers);
		
		$sql_add = $agroup ? " AND `group`='$agroup'" : '';
		$res = $db->query("SELECT `id` AS `agent_id`, `name`, `data_sverki`
			FROM `doc_agent` WHERE 1 $sql_add ORDER BY `name`");
		$date_limit = " AND `date`<=$date";
		$i = 0;
		$sum_dolga = 0;
		while ($nxt = $res->fetch_array()) {
			$dolg = agentCalcDebt($nxt[0], 0, $firm_id, $db, $date);
			if ((($dolg > 0) && ($vdolga == 1)) || (($dolg < 0) && ($vdolga == 2))) {
				$d_res = $db->query("SELECT `date` FROM `doc_list`
				WHERE `agent`={$nxt['agent_id']} AND (`type`=4 OR `type`=5) $date_limit ORDER BY `date` DESC LIMIT 1");
				if ($d_res->num_rows)
					list($k_date) = $d_res->fetch_row();
				else	$k_date = '';
				$d_res = $db->query("SELECT `date` FROM `doc_list`
				WHERE `agent`={$nxt['agent_id']} AND (`type`=6 OR `type`=7) $date_limit ORDER BY `date` DESC LIMIT 1");
				if ($d_res->num_rows)
					list($b_date) = $d_res->fetch_row();
				else	$b_date = '';

				$i++;
				$dolg = abs($dolg);
				$sum_dolga += $dolg;
				$dolg = number_format($dolg, 2, '.', ' ');
				$k_date = $k_date ? date("Y-m-d", $k_date) : '';
				$b_date = $b_date ? date("Y-m-d", $b_date) : '';
				$this->tableRow( array($i, $nxt[1], $nxt[2], $dolg.' руб.', $k_date, $b_date) );				
			}
		}
		$sum_dolga_p = number_format($sum_dolga, 2, '.', ' ');
		
		$this->tableAltStyle(true);
		$this->tableSpannedRow(array(6), array("Итого: $i должников с общей суммой долга $sum_dolga_p  руб.\n" . num2str($sum_dolga) . ")"));
		$this->tableAltStyle(false);
		$this->tableEnd();
		$this->output();
		exit(0);
	}
}

?>