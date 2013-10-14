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

include_once('include/doc.poseditor.php');

/// Работа со связанными товарами.
/// В работе использует javascript файл js/link_poslist.js
class LinkPosList extends PosEditor {

	var $linked_pos = 0;  ///< ID наименования, для которого формируется список связей

/// Конструктор.
/// @param pos_id ID наименования, для которого требуется просмотр/редактирование списка связанных наименований
	function __construct($pos_id) {
		$this->linked_pos = $pos_id;
	}

/// Установить ID связанного товара
/// @param pos_id ID наименования, для которого требуется просмотр/редактирование списка связанных наименований
	public function SetLinkedPos($pos_id) {
		$this->linked_pos = $pos_id;
	}

/// Показать редактор.
/// @return HTML код редактора
	public function Show($param = '') {
		global $CONFIG;
		/// TODO: возможность отключения редактирования в зависимости от статуса документа, настройка отображаемых столбцов из конфига. Не забыть про серийные номера.
		/// Возможность отключения строки быстрого ввода
		/// В итоге - сделать базовый класс, от которого наследуется редактор документов, редактор комплектующих, итп.
		$ret = "
	<script src='/js/link_poslist.js' type='text/javascript'></script>
	<link href='/css/poseditor.css' rel='stylesheet' type='text/css' media='screen'>
	<script src='/css/jquery/jquery.alerts.js' type='text/javascript'></script>
	<link href='/css/jquery/jquery.alerts.css' rel='stylesheet' type='text/css' media='screen' />

	<table width='100%' id='poslist'><thead><tr>
	<th width='60px' align='left'>№</th>";
		if ($CONFIG['poseditor']['vc'] > 0)
			$ret.="<th width='90px' align='left' title='Код изготовителя'><div class='order_button' id='pl_order_vc'></div> Код</th>";
		$ret.="<th><div class='order_button' id='pl_order_name'></div> Наименование</th>
	<th width='90px' class='hl'><div class='order_button' id='pl_order_cost'></div> Цена</th>
	<th width='60px' title='Остаток товара на складе'>Остаток</th>
	</tr>
	</thead>
	<tfoot>
	<tr id='pladd'>
	<td><input type='text' id='pos_id' autocomplete='off' tabindex='1'></td>";
		if ($CONFIG['poseditor']['vc'] > 0)
			$ret.="<td><input type='text' id='pos_vc' autocomplete='off' tabindex='2'></td>";
		$ret.="<td><input type='text' id='pos_name' autocomplete='off' tabindex='3'></td>
	<td id='pos_cost'></td>
	<td id='pos_sklad_cnt'></td>
	</tr>
	</tfoot>
	<tbody>
	<tr><td colspan='9' style='text-align: center;'><img src='/img/icon_load.gif' alt='Загрузка...'>
	</tbody>
	</table>
	<p align='right' id='sum'></p>";

		$ret.="
	<table id='sklad_view'>
	<tr><td id='groups_list' width='200' valign='top' class='lin0'>";
		$ret.=$this->getGroupsTree();
		$ret.="</td><td valign='top' class='lin1'>	
	<table width='100%' cellspacing='1' cellpadding='2'>
	<tr><thead>
	<th>№";
		if ($CONFIG['poseditor']['vc'] > 0)
			$ret.="<th>Код";
		$ret.="<th>Наименование<th>Марка<th>Цена, р.<th>Ликв.<th>Р.цена, р.<th>Аналог";
		if ($CONFIG['poseditor']['tdb'] > 0)
			$ret.="<th>Тип<th>d<th>D<th>B<th>Масса";
		if ($CONFIG['poseditor']['rto'] > 0)
			$ret.="<th><img src='/img/i_lock.png' alt='В резерве'><th><img src='/img/i_alert.png' alt='Предложений'><th><img src='/img/i_truck.png' alt='В пути'>";
		$ret.="<th>Склад<th>Всего<th>Место
	</thead>
	<tbody id='sklad_list'>
	</tbody>
	</table>
	</td></tr>
	</table>";
		if (!@$CONFIG['poseditor']['need_dialog'])
			$CONFIG['poseditor']['need_dialog'] = 0;
		else
			$CONFIG['poseditor']['need_dialog'] = 1;
		$ret.=@"<script type=\"text/javascript\">
	var poslist=LinkPosListInit('/docs.php?l=sklad&mode=srv&opt=ep&param=l&pos={$this->linked_pos}','{$this->editable}')
	poslist.show_column['vc']='{$CONFIG['poseditor']['vc']}'

	var skladview=document.getElementById('sklad_view')
	skladview.show_column['vc']='{$CONFIG['poseditor']['vc']}'
	skladview.show_column['tdb']='{$CONFIG['poseditor']['tdb']}'
	skladview.show_column['rto']='{$CONFIG['poseditor']['rto']}'
	
	skladlist=document.getElementById('sklad_list').needDialog='{$CONFIG['poseditor']['need_dialog']}';
	</script>";

		return $ret;
	}

/// Получить список наименований, связанных с выбранным наименованием.
/// @return json-строка с данными о наименованиях
	function GetAllContent() {
		global $db;
		$res = $db->query("SELECT `doc_base_links`.`id` AS `line_id`, `doc_base`.`id` AS `pos_id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base`.`cost`,
			`doc_base`.`proizv`, `doc_base`.`id`,
			(SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `sklad_cnt`
			FROM `doc_base_links`
			LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_base_links`.`pos2_id`
			WHERE `doc_base_links`.`pos1_id`='{$this->linked_pos}'");
		$ret = '';
		while ($nxt = $res->fetch_assoc()) {
			$nxt['cost'] = sprintf("%0.2f", $nxt['cost']);
			if ($ret) $ret.=', ';
			$ret.="{line_id: '{$nxt['line_id']}', pos_id: '{$nxt['pos_id']}', vc: '{$nxt['vc']}', name: '{$nxt['name']} - {$nxt['proizv']}', cost: '{$nxt['cost']}', sklad_cnt: '{$nxt['sklad_cnt']}'";
			$ret.="}";
		}
		return $ret;
	}

/// Получить список номенклатуры заданной группы
	function GetSkladList($group) {
		global $db;
		settype($group, 'int');
		$sql = "SELECT `doc_base`.`id`,`doc_base`.`vc`,`doc_base`.`group`,`doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`likvid`, `doc_base`.`cost`,
			`doc_base`.`cost_date`,	`doc_base_dop`.`koncost`,  `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`,
			`doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`,
			(SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `allcnt`
			FROM `doc_base`
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
			WHERE `doc_base`.`group`='$group'
			ORDER BY `doc_base`.`name`";
		$res = $db->query($sql);
		return $this->FormatResult($res);
	}

/// Получить список номенклатуры, содержащей в названии заданную строку
	function SearchSkladList($s) {
		global $db;
		$ret = '';
		$sql = "SELECT `doc_base`.`id`,`doc_base`.`vc`,`doc_base`.`group`,`doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`likvid`, `doc_base`.`cost`,
			`doc_base`.`cost_date`, `doc_base_dop`.`koncost`,  `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`,
			`doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`, `doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt`,
			(SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `allcnt`";
		$s_sql = $db->real_escape_string($s);
		$sqla = $sql . "FROM `doc_base`
			LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
			WHERE `doc_base`.`name` LIKE '$s%' OR `doc_base`.`vc` LIKE '$s_sql%' ORDER BY `doc_base`.`name` LIMIT 200";
		$res = $db->query($sqla);
		if ($res->num_rows) {
			if ($ret != '')
				$ret.=', ';
			$ret.="{id: 'header', name: 'Поиск по названию, начинающемуся на $s - {$res->num_rows} наименований найдено'}";
			$ret = $this->FormatResult($res, $ret);
		}
		$sqla = $sql . "FROM `doc_base`
			LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
			WHERE (`doc_base`.`name` LIKE '%$s_sql%' OR `doc_base`.`vc` LIKE '%$s_sql%') AND `doc_base`.`name` NOT LIKE '$s_sql%'
			AND `doc_base`.`vc` NOT LIKE '$s_sql%' ORDER BY `doc_base`.`name` LIMIT 100";
		$res = $db->query($sqla);
		if ($res->num_rows) {
			if ($ret != '')
				$ret.=', ';
			$ret.="{id: 'header', name: 'Поиск по названию, содержащему $s - {$res->num_rows} наименований найдено'}";
			$ret = $this->FormatResult($res, $ret);
		}
		$sqla = $sql . "FROM `doc_base`
			LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
			WHERE `doc_base_dop`.`analog` LIKE '%$s_sql%' AND `doc_base`.`name` NOT LIKE '%$s_sql%' AND `doc_base`.`vc` NOT LIKE '%$s%'
			ORDER BY `doc_base`.`name` LIMIT 100";
		$res = $db->query($sqla);
		if ($res->num_rows) {
			if ($ret != '')
				$ret.=', ';
			$ret.="{id: 'header', name: 'Поиск по аналогу($s) - {$res->num_rows} наименований найдено'}";
			$ret = $this->FormatResult($res, $ret);
		}
		return $ret;
	}

/// Добавляет указанную складскую позицию в список
	function AddPos($pos) {
		global $db;
		$ret = '';
		settype($pos, 'int');
		if (!$pos)		throw new Exception("ID позиции не задан!");
		
		$res = $db->query("SELECT `id`, `pos1_id`, `pos2_id` FROM `doc_base_links`
		WHERE (`pos1_id`='{$this->linked_pos}' AND `pos2_id`='$pos') OR (`pos1_id`='{$this->linked_pos}' AND `pos2_id`='$pos')");
		if (! $res->num_rows) {
			$db->query("INSERT INTO `doc_base_links` (`pos1_id`, `pos2_id`) VALUES ('{$this->linked_pos}','$pos')");
			$pos_line = $db->insert_id;
			doc_log("UPDATE", "add link: pos:$pos", 'pos', $this->linked_pos);
			$add = 1;

			$res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`,
				`doc_list_pos`.`cost`, `doc_base_cnt`.`cnt` AS `sklad_cnt`, `doc_base_cnt`.`mesto`
				FROM `doc_base_links`
				INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_base_links`.`pos2_id`
				LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
				WHERE `doc_list_pos`.`id`='$pos_line'");
			$line = $res->fetch_assoc();
			$cost = $this->cost_id ? getCostPos($line['id'], $this->cost_id) : $line['cost'];
			$ret = "{ response: '1', add: { line_id: '$pos_line', pos_id: '{$line['id']}', vc: '{$line['vc']}', name: '{$line['name']} - {$line['proizv']}', cnt: '{$line['cnt']}', scost: '$cost', cost: '{$line['cost']}', sklad_cnt: '{$line['sklad_cnt']}', mesto: '{$line['mesto']}', gtd: '' }, sum: '$doc_sum' }";
		}

		return $ret;
	}

/// Формирует json строку с данными о наименованиях на основании результата выполнения sql запроса
/// @param res Результат sql запроса - выборка наименований по заданным критериям
/// @param ret json строка, к которой надо добавить данные
	protected function FormatResult($res, $ret = '') {
		if ($res->num_rows) {
			while ($nxt = $res->fetch_assoc()) {
				$dcc = strtotime($nxt['cost_date']);
				$cc = "";
				if ($dcc > (time() - 60 * 60 * 24 * 30 * 3))
					$cc = "c1";
				else if ($dcc > (time() - 60 * 60 * 24 * 30 * 6))
					$cc = "c2";
				else if ($dcc > (time() - 60 * 60 * 24 * 30 * 9))
					$cc = "c3";
				else if ($dcc > (time() - 60 * 60 * 24 * 30 * 12))
					$cc = "c4";
				$reserve = DocRezerv($nxt['id'], 0);
				$offer = DocPodZakaz($nxt['id'], 0);
				$transit = DocVPuti($nxt['id'], 0);
				$cost = $nxt['cost'];
				$rcost = sprintf("%0.2f", $nxt['koncost']);
				if ($ret != '')
					$ret.=', ';
				$ret.=@"{ id: '{$nxt['id']}', name: '{$nxt['name']}', vc: '{$nxt['vc']}', vendor: '{$nxt['proizv']}', liquidity: '{$nxt['likvid']}', cost: '$cost', cost_class: '$cc', rcost: '$rcost', analog: '{$nxt['analog']}', type: '{$nxt['type']}', d_int: '{$nxt['d_int']}', d_ext: '{$nxt['d_ext']}', size: '{$nxt['size']}', mass: '{$nxt['mass']}', place: '{$nxt['mesto']}', cnt: '{$nxt['cnt']}', allcnt: '{$nxt['allcnt']}', reserve: '$reserve', offer: '$offer', transit: '$transit' }";
			}
		}
		return $ret;
	}

};


?>