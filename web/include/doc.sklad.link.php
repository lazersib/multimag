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

include_once('include/doc.poseditor.php');

/// Работа со связанными товарами.
class LinkPosList extends PosEditor {

	var $linked_pos;  ///< ID наименования, для которого формируется список связей

	/// Конструктор.
	/// @param pos_id ID наименования, для которого требуется просмотр/редактирование списка связанных наименований
	function __construct($pos_id) {
		$this->linked_pos = $pos_id;
		if( @$CONFIG['poseditor']['sn_enable'] && ($doc_data['type']==1 || $doc_data['type']==2))	$this->show_sn=1;
		if( @$CONFIG['poseditor']['true_gtd'] && $doc_data['type']==1)					$this->show_gtd=1;
	}

	/// Установить ID связанного товара
	/// @param pos_id ID наименования, для которого требуется просмотр/редактирование списка связанных наименований
	public function setLinkedPos($pos_id) {
		$this->linked_pos = $pos_id;
	}
	
	/// Загрузить список товаров. Повторно не загружает.
	protected function loadList() {
		global $db;
		if(is_array($this->list))
			return;
		$this->list = array();
		$res = $db->query("SELECT `doc_base_links`.`id` AS `line_id`, `doc_base`.`id` AS `pos_id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base`.`cost`,
			`doc_base`.`proizv`, `doc_base`.`id`,
			(SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `sklad_cnt`
			FROM `doc_base_links`
			LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_base_links`.`pos2_id`
			WHERE `doc_base_links`.`pos1_id`='{$this->linked_pos}'");
		while ($nxt = $res->fetch_assoc())
			$this->list[$nxt['line_id']] = $nxt;
	}

/// Показать редактор.
/// @return HTML код редактора
	public function Show($param = '') {
		global $CONFIG;
		
		$ret="
		<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<script type='text/javascript' src='/css/jquery/jquery.alerts.js'></script>
		<script src='/css/poseditor.js' type='text/javascript'></script>
		<link href='/css/poseditor.css' rel='stylesheet' type='text/css' media='screen'>
		<link href='/css/jquery/jquery.alerts.css' rel='stylesheet' type='text/css' media='screen'>
		<div id='poseditor_div'></div>
		<div id='storeview_container'></div>";

		$p_setup = array(
		    'base_url'	=> '/docs.php?l=sklad&mode=srv&opt=ep&param=l&pos='.$this->linked_pos,
		    'editable'	=> $this->editable,
		    'container'	=> 'poseditor_div',
		    'store_container'	=> 'storeview_container',
		    'fastadd_line'=> 1,		// Показывать строку быстрого подбора
		);

		$cols = array();
		$col_names = array();
		if($this->show_vc) {
			$cols[] = 'vc';
			$col_names[] = 'Код';
		}
		$cols[] = 'name';
		$col_names[] = 'Наименование';
		$cols[] = 'store_cnt';
		$col_names[] = 'Остаток';
		$cols[] = 'place';
		$col_names[] = 'Место';

		$p_setup['columns'] = $cols;
		$p_setup['col_names'] = $col_names;

		if($this->show_vc)
			$p_setup['store_columns'] = array(
			    'vc', 'name', 'vendor', 'price', 'liquidity'
			);
		else	$p_setup['store_columns'] = array(
			    'name', 'vendor', 'price', 'liquidity'
			);
		
		if($this->show_tdb) {
			$p_setup['store_columns'][] = 'type';
			$p_setup['store_columns'][] = 'd_int';
			$p_setup['store_columns'][] = 'd_ext';
			$p_setup['store_columns'][] = 'size';
			$p_setup['store_columns'][] = 'mass';
		}

		if($this->show_rto) {
			$p_setup['store_columns'][] = 'transit';
			$p_setup['store_columns'][] = 'reserve';
			$p_setup['store_columns'][] = 'offer';
		}

		$p_setup['store_columns'][] = 'cnt';
		$p_setup['store_columns'][] = 'allcnt';
		$p_setup['store_columns'][] = 'place';


		$ret.="<script type=\"text/javascript\">
		var poslist = PosEditorInit(".json_encode($p_setup, JSON_UNESCAPED_UNICODE).");
		</script>";

		return $ret;
	}

/// Получить список наименований, связанных с выбранным наименованием.
/// @return json-строка с данными о наименованиях
	function GetAllContent() {
		global $CONFIG, $db;
		$this->loadList();

		$pos_array = array();
		foreach ($this->list as $nxt) {

			if(! @$CONFIG['doc']['no_print_vendor'])
				$nxt['name'].=' - '.$nxt['vendor'];
			$pos_array[] = $nxt;
		}

		$ret_data = array (
		    'response'	=> 'loadlist',
		    'content'	=> $pos_array,
		    'sum'	=> 0,
		);
		return json_encode($ret_data, JSON_UNESCAPED_UNICODE);
	}
	
	/// Получить информацию о наименовании
	function GetPosInfo($pos) {
		global $db, $CONFIG;

		$res = $db->query("SELECT `doc_base_links`.`id` AS `line_id`, `doc_base`.`id` AS `pos_id`, `doc_base`.`vc`, `doc_base`.`name`,
			`doc_base`.`proizv` AS `vendor`, `doc_base`.`cost` AS `base_price`, `doc_base`.`bulkcnt`, `doc_base`.`group`
			FROM `doc_base`
			LEFT JOIN `doc_base_links` ON `doc_base`.`id`=`doc_base_links`.`pos2_id`
			WHERE `doc_base`.`id`='$pos'");

		$ret = '';
		if ($res->num_rows) {
			$nxt = $res->fetch_assoc();
			
			if(! @$CONFIG['doc']['no_print_vendor'])
				$nxt['name'].=' - '.$nxt['vendor'];
			
			$ret = "{response: 3, data:".json_encode($nxt, JSON_UNESCAPED_UNICODE)."}";
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
			$ret = "{ response: '1', add: { line_id: '$pos_line', pos_id: '{$line['id']}', vc: '{$line['vc']}', name: '{$line['name']} - {$line['proizv']}', cnt: '{$line['cnt']}', scost: '$cost', cost: '{$line['cost']}', sklad_cnt: '{$line['sklad_cnt']}', mesto: '{$line['mesto']}', gtd: '' }, sum: '$doc_sum' }";
		}

		return $ret;
	}

	/// Получить список номенклатуры заданной группы
	function GetSkladList($group) {
		global $db;
		settype($group, 'int');
		$sql = "SELECT `doc_base`.`id`,`doc_base`.`vc`,`doc_base`.`group`,`doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`likvid`,
			`doc_base`.`cost` AS `base_price`, `doc_base`.`bulkcnt`,
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
		$sql = "SELECT `doc_base`.`id`,`doc_base`.`vc`,`doc_base`.`group`,`doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`likvid`,
			`doc_base`.`cost` AS `base_price`, `doc_base`.`bulkcnt`,
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

	
	protected function FormatResult($res, $ret = '') {
		if ($res->num_rows) {
			while ($nxt = $res->fetch_assoc()) {
				$dcc = strtotime($nxt['price_date']);
				if ($dcc > (time() - 60 * 60 * 24 * 30 * 3))		$nxt['price_cat'] = "c1";
				else if ($dcc > (time() - 60 * 60 * 24 * 30 * 6))	$nxt['price_cat'] = "c2";
				else if ($dcc > (time() - 60 * 60 * 24 * 30 * 9))	$nxt['price_cat'] = "c3";
				else if ($dcc > (time() - 60 * 60 * 24 * 30 * 12))	$nxt['price_cat'] = "c4";
				if ($this->show_rto) {
					$nxt['reserve'] = DocRezerv($nxt['id'], $this->doc);
					$nxt['offer'] = DocPodZakaz($nxt['id'], $this->doc);
					$nxt['transit'] = DocVPuti($nxt['id'], $this->doc);
				}
				$pc = PriceCalc::getInstance();
				if($this->cost_id)
					$nxt['price'] = $pc->getPosSelectedPriceValue($nxt['id'], $this->cost_id, $nxt);
				else	$nxt['price'] = $pc->getPosDefaultPriceValue($nxt['id']);
				
				if ($ret != '')
					$ret.=', ';

				$ret .= json_encode($nxt, JSON_UNESCAPED_UNICODE);
			}
		}
		return $ret;
	}
};


?>