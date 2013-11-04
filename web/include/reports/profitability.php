<?php

//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2013, BlackLight, TND Team, http://tndproject.org
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
/// Отчёт по рентабельности и прибыли
/// Алгоритм расчёта основан на алгоритме вычисления актуальной цены поступления
class Report_Profitability extends BaseGSReport {

	function GroupSelBlock() {
		global $tmpl;
		$tmpl->addStyle("		
		div#sb
		{
			display:		none;
			border:			1px solid #888;
			max-height:		250px;
			overflow:		auto;
		}
		
		.selmenu
		{
			background-color:	#888;
			width:			auto;
			font-weight:		bold;
			padding-left:		20px;
		}
		
		.selmenu a
		{
			color:			#fff;
			cursor:			pointer;	
		}
		
		.cb
		{
			width:			14px;
			height:			14px;
			border:			1px solid #ccc;
		}
		
		");
		$tmpl->addContent("<script type='text/javascript'>
		function SelAll(flag)
		{
			var elems = document.getElementsByName('g[]');
			var l = elems.length;
			for(var i=0; i<l; i++)
			{
				elems[i].checked=flag;
				if(flag)	elems[i].disabled = false;
			}
		}
		
		function CheckCheck(ids)
		{
			var cb = document.getElementById('cb'+ids);
			var cont=document.getElementById('cont'+ids);
			if(!cont)	return;
			var elems=cont.getElementsByTagName('input');
			var l = elems.length;
			for(var i=0; i<l; i++)
			{
				if(!cb.checked)		elems[i].checked=false;
				elems[i].disabled =! cb.checked;
			}
		}
		
		</script>
		<div class='groups_block' id='sb'>
		<ul class='Container'>
		<div class='selmenu'><a onclick='SelAll(true)'>Выбрать всё<a> | <a onclick='SelAll(false)'>Снять всё</a></div>
		" . $this->draw_groups_tree(0) . "</ul></div>");
	}

	function getName($short = 0) {
		if ($short)	return "По рентабельности и прибыли";
		else		return "Отчёт по рентабельности и прибыли";
	}

	function Form() {
		global $tmpl;
		$d_t = date("Y-m-d");
		$d_f = date("Y-m-d", time() - 60 * 60 * 24 * 31);
		$tmpl->addContent("<h1>" . $this->getName() . "</h1>
		<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='profitability'>
		<fieldset><legend>Дата</legend>
		С:<input type=text id='dt_f' name='dt_f' value='$d_f'><br>
		По:<input type=text id='dt_t' name='dt_t' value='$d_t'>
		</fieldset>
		Не показывать с прибылью менее <input type='text' name='ren_min_abs'> руб.<br>
		Не показывать с рентабельностью менее <input type='text' name='ren_min_pp'> %<br>
		<label><input type='checkbox' name='neg_pos' checked>Поместить наименования с отрицательной прибылью в начало списка</label>
		<br>
		<fieldset><legend>Отчёт по</legend>
		<select name='sel_type' id='sel_type'>
		<option value='all'>Всей номенклатуре</option>
		<option value='group'>Выбранной группе</option>
		</select>
		");
		$this->GroupSelBlock();
		$tmpl->addContent("
		</fieldset>
		Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
		<button type='submit'>Сформировать отчёт</button>
		</form>
		
		<script type=\"text/javascript\">
		function dtinit()
		{
			initCalendar('dt_f',false)
			initCalendar('dt_t',false)
		}
		function selectChange(event)
		{
			if(this.value=='group')
				document.getElementById('sb').style.display='block';
			else	document.getElementById('sb').style.display='none';
		}
		
		
		addEventListener('load',dtinit,false)	
		document.getElementById('sel_type').addEventListener('change',selectChange,false)	
		
		</script>
		");
	}

	/// Вычисляет прибыль по заданному товару за выбранный период
	function calcPosT($pos_id, $date_from, $date_to) {
		global $db;
		settype($pos_id, 'int');
//		settype($date_from, 'int');
//		settype($date_to, 'int');
		$cnt = $out_cnt = $cost = $profit = 0;
		$sum_extra = 0;

		$res = $db->query("SELECT `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_list`.`type`, `doc_list_pos`.`page`,
			`doc_dopdata`.`value` AS `ret_flag`, `doc_list`.`date`
		FROM `doc_list_pos`
		INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND (`doc_list`.`type`<='2' OR `doc_list`.`type`='17')
		LEFT JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list_pos`.`doc` AND `doc_dopdata`.`param`='return'
		WHERE `doc_list_pos`.`tovar`='$pos_id' AND `doc_list`.`ok`>'0' AND `doc_list`.`date`<='$date_to' ORDER BY `doc_list`.`date`");
		while ($nxt = $res->fetch_assoc()) {
			if (($nxt['type'] == 2) || ($nxt['type'] == 17) && ($nxt['page'] != '0'))
				$nxt['cnt'] = $nxt['cnt'] * (-1);
			
			if ($nxt['cnt'] > 0 && (!$nxt['ret_flag']) && (($cnt + $nxt['cnt']) != 0) )
				$cost = ( ($cnt * $cost) + ($nxt['cnt'] * $nxt['cost'])) / ($cnt + $nxt['cnt']);
			
			if ($nxt['type'] == 2 && $nxt['date'] >= $date_from && (!$nxt['ret_flag'])) {
				$profit -= $nxt['cnt'] * ($nxt['cost'] - $cost);
				if ($cost)
					$sum_extra -= ($nxt['cost'] - $cost) * 100 * $nxt['cnt'] / $cost;
				$out_cnt -= $nxt['cnt'];
			}
			$cnt += $nxt['cnt'];
			if ($cnt < 0)
				return array(0xFFFFBADF00D, 0, 0); // Невозможно расчитать прибыль, если остатки уходили в минус
			
		}
		if ($out_cnt)
			$avg_extra_pp = round($sum_extra / $out_cnt, 1);
		else
			$avg_extra_pp = 0;
		return array($profit, $out_cnt, $avg_extra_pp);
	}

	/// Вычисляет прибыль по заданной услуге за выбранный период
	function calcPosS($pos_id, $date_from, $date_to) {
		global $db;
		settype($pos_id, 'int');
//		settype($date_from, 'int');
//		settype($date_to, 'int');
		$out_cnt = $profit = 0;

		$res = $db->query("SELECT `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_list`.`type`, `doc_list_pos`.`page`, `doc_dopdata`.`value`, `doc_list`.`date`
		FROM `doc_list_pos`
		INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND (`doc_list`.`type`<='2' OR `doc_list`.`type`='17')
		LEFT JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list_pos`.`doc` AND `doc_dopdata`.`param`='return'
		WHERE `doc_list_pos`.`tovar`='$pos_id' AND `doc_list`.`ok`>'0' AND `doc_list`.`date`>='$date_from' AND `doc_list`.`date`<='$date_to' ORDER BY `doc_list`.`date`");
		while ($nxt = $res->fetch_row()) {
			if (($nxt[2] == 2) || ($nxt[2] == 17) && ($nxt[3] != '0'))
				$nxt[0] = $nxt[0] * (-1);
			if (!$nxt[4])
				$profit+=-1 * $nxt[0] * $nxt[1];
			if ($nxt[2] == 2 && (!$nxt[4]))
				$out_cnt-=$nxt[0];
		}
		return array($profit, $out_cnt, 0);
	}

	function Make($engine) {
		global $db;
		$this->loadEngine($engine);

		$dt_f = strtotime(rcvdate('dt_f'));
		$dt_t = strtotime(rcvdate('dt_t'));
		$g = request('g', array());
		$sel_type = rcvint('sel_type');
		$ren_min_abs = rcvint('ren_min_abs');
		$ren_min_pp = rcvint('ren_min_pp');
		$neg_pos = rcvint('neg_pos');

		$max_profit = 0;

		$print_df = date('Y-m-d', $dt_f);
		$print_dt = date('Y-m-d', $dt_t);

		$this->header($this->getName() . " с $print_df по $print_dt");

		$widths = array(5, 8, 45, 8, 9, 9, 8, 8);
		$headers = array('ID', 'Код', 'Наименование', 'Б. цена', 'Продано', 'Прибыль', 'Рентаб.', 'Ср.нац.');

		$this->col_cnt = count($widths);
		$this->tableBegin($widths);
		$this->tableHeader($headers);

		$db->query("CREATE TEMPORARY TABLE `temp_report_profit` (`pos_id` INT NOT NULL , `profit` DECIMAL( 16, 2 ) NOT NULL , `count` INT( 11 ) NOT NULL, `avg_extra_pp` DECIMAL( 6, 2 ) NOT NULL) ENGINE = MEMORY");
		if ($sel_type == 'all') {
			$res = $db->query("SELECT `id`, `pos_type` FROM `doc_base`");
			while ($nxt = $res->fetch_row()) {
				if ($nxt[1] == 0)
					list($profit, $count, $avg_extra_pp) = $this->calcPosT($nxt[0], $dt_f, $dt_t);
				else	list($profit, $count, $avg_extra_pp) = $this->calcPosS($nxt[0], $dt_f, $dt_t);
				if ($max_profit < $profit && $profit != 0xFFFFBADF00D)
					$max_profit = $profit;
				$db->query("INSERT INTO `temp_report_profit` VALUES ( $nxt[0], $profit, $count, $avg_extra_pp)");
			}
		}
		else if ($sel_type == 'group') {
			$res_group = $db->query("SELECT `id`, `name`, `pos_type` FROM `doc_group` ORDER BY `id`");
			while ($group_line = $res_group->fetch_assoc()) {
				if (!in_array($group_line['id'], $g))	continue;

				$res = $db->query("SELECT `doc_base`.`id` FROM `doc_base` WHERE `doc_base`.`group`='{$group_line['id']}'");
				while ($nxt = $res->fetch_row()) {
					if ($nxt[2] == 0)
						list($profit, $count, $avg_extra_pp) = $this->calcPosT($nxt[0], $dt_f, $dt_t);
					else	list($profit, $count, $avg_extra_pp) = $this->calcPosS($nxt[0], $dt_f, $dt_t);

					if ($max_profit < $profit && $profit != 0xFFFFBADF00D)
						$max_profit = $profit;
					$db->query("INSERT INTO `temp_report_profit` VALUES ( $nxt[0], $profit, $count, $avg_extra_pp)");
				}
			}
		}

		if ($neg_pos) {
			$res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`vc`, CONCAT(`doc_base`.`name`, ' - ', `doc_base`.`proizv`) AS `name`, `doc_base`.`cost`, `temp_report_profit`.`profit`, `temp_report_profit`.`count`, `temp_report_profit`.`avg_extra_pp` FROM `temp_report_profit`
			LEFT JOIN `doc_base` ON `temp_report_profit`.`pos_id`=`doc_base`.`id`
			WHERE `temp_report_profit`.`profit`<'0'
			ORDER BY `temp_report_profit`.`profit` ASC");
			while ($nxt = $res->fetch_row()) {
				$profitability = round($nxt[4] * 100 / $max_profit, 2);
				$this->tableRow(array($nxt[0], $nxt[1], $nxt[2], $nxt[3], $nxt[5], "$nxt[4] р.", "$profitability %", $nxt[6] . ' %'));
			}
		}

		$res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`vc`, CONCAT(`doc_base`.`name`, ' - ', `doc_base`.`proizv`) AS `name`, `doc_base`.`cost`, `temp_report_profit`.`profit`, `temp_report_profit`.`count`, `temp_report_profit`.`avg_extra_pp` FROM `temp_report_profit`
		LEFT JOIN `doc_base` ON `temp_report_profit`.`pos_id`=`doc_base`.`id`
		WHERE `temp_report_profit`.`profit`>'$ren_min_abs'
		ORDER BY `temp_report_profit`.`profit` DESC");
		$sum = 0;
		while ($nxt = $res->fetch_row()) {
			if ($nxt[4] == 0xFFFFBADF00D) {
				$this->tableRow(array($nxt[0], $nxt[1], $nxt[2], $nxt[3], 'ошибка', 'ошибка', "conut < 0", 'ошибка'));
			} else {
				$sum+=$nxt[4];
				$profitability = round($nxt[4] * 100 / $max_profit, 2);
				if ($profitability < $ren_min_pp)
					continue;
				$this->tableRow(array($nxt[0], $nxt[1], $nxt[2], $nxt[3], $nxt[5], "$nxt[4] р.", "$profitability %", $nxt[6] . ' %'));
			}
		}
		$this->tableRow(array("", "", "Всего", "", "", "$sum р.", ""));
		$this->tableEnd();
		$this->output();
		exit(0);
	}
}
?>