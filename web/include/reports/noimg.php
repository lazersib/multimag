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
/// Отчёт по товарам без изображений
class Report_Noimg extends BaseGSReport {

	function getName($short = 0) {
		if ($short)	return "Товары без изображений";
		else		return "Отчёт по товарам без изображений";
	}

	function Form() {
		global $tmpl;
		$tmpl->addContent("<h1>" . $this->getName() . "</h1>
		<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='noimg'>
		Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
		<button type='submit'>Сформировать отчёт</button>
		</form>");
	}

	function groupsProcess($pgroup_id, $group_list) {
		global $db;
		settype($pgroup_id, 'int');
		$res = $db->query("SELECT `id`, `name` FROM `doc_group` WHERE `pid`='$pgroup_id' ORDER BY `id`");
		while ($group_line = $res->fetch_assoc()) {
			if (is_array($group_list))
				if (!in_array($group_line['id'], $group_list))
					continue;
			$h_print = 0;
			$pres = $db->query("SELECT `id` AS `pos_id`, `name`, `vc`, (
				SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `pos_id`=`id`) AS `cnt`
			FROM `doc_base` WHERE `group`='{$group_line['id']}' ORDER BY {$this->order}");
			while ($pos_line = $pres->fetch_assoc()) {
				$r_res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_agent`.`name` AS `agent_name`
				FROM `doc_list_pos`
				INNER JOIN `doc_list` ON `doc_list`.`type`='3' AND `doc_list`.`ok`>'0' AND `doc_list`.`id`=`doc_list_pos`.`doc` 
				AND `doc_list`.`id` NOT IN (
					SELECT DISTINCT `p_doc` FROM `doc_list`
					INNER JOIN `doc_list_pos` ON `doc_list`.`id`=`doc_list_pos`.`doc`
					WHERE `ok` != '0' AND `type`='2' AND `doc_list_pos`.`tovar`='{$pos_line['pos_id']}' )
				LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
				WHERE `doc_list_pos`.`tovar`='{$pos_line['pos_id']}'");
				if ($r_res->num_rows) {
					if (!$h_print) {
						$h_print = 1;
						$this->tableAltStyle();
						$this->tableSpannedRow(array(1, $this->col_cnt - 1), array($group_line['id'], $group_line['name']));
						$this->tableAltStyle(false);
					}
					$r = 0;
					while ($nxt = $r_res->fetch_assoc()) {
						$r+=$nxt['cnt'];
					}
					$r_res->data_seek(0);
					$this->tableRow(array($pos_line['pos_id'], $pos_line['vc'], $pos_line['name'], $r, $pos_line['cnt']));
					while ($nxt = $r_res->fetch_assoc()) {
						$date = date("Y-m-d", $nxt['date']);
						$this->tableSpannedRow(array(2, 1, 2), array("{$nxt['id']} / $date", $nxt['agent_name'], $nxt['cnt']));
					}
				}
			}

			$this->groupsProcess($group_line['id'], $group_list);
		}
	}

	function Make($engine) {
		global $CONFIG, $db;
		$this->loadEngine($engine);

		$g = @$_POST['g'];

		$this->header($this->getName());
		$widths = array(5, 8, 60, 5, 8, 7, 7);
		$headers = array('ID', 'Код', 'Наименование', 'Пр-ль', 'Ликв', 'Кол-во', 'Место');

		$this->col_cnt = count($widths);
		$this->tableBegin($widths);
		$this->tableHeader($headers);
		
		$res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`likvid`,
				`doc_base_cnt`.`cnt`, `doc_base_cnt`.`mesto`
			FROM `doc_base`
			INNER JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`cnt`>0
			INNER JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
			LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id`
			WHERE `doc_base`.`pos_type`=0 AND `doc_base_img`.`img_id` IS NULL
			ORDER BY `doc_base`.`likvid` DESC");
		while($line = $res->fetch_assoc()) {
			$this->tableRow(array(
			    $line['id'],
			    $line['vc'],
			    $line['name'],
			    $line['proizv'],
			    $line['likvid'],
			    $line['cnt'],
			    $line['mesto'],
				));
		}
		
		$this->tableEnd();
		$this->output();
		exit(0);
	}
}
?>