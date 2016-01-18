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

// Это временное решение для работы с комплектацией товаров

include_once('include/doc.poseditor.php');

/// Работа со связанными товарами.
class KomplPosList extends PosEditor {

	var $linked_pos;  ///< ID наименования, для которого формируется список связей
	var $list_sum = 0; //< Сумма позиций из loadlist()
	/// Конструктор.
	/// @param pos_id ID наименования, для которого требуется просмотр/редактирование списка связанных наименований
	function __construct($pos_id) {
		parent::__construct();
		$this->linked_pos = intval($pos_id);
	}

	/// Установить ID связанного товара
	/// @param pos_id ID наименования, для которого требуется просмотр/редактирование списка связанных наименований
	public function setLinkedPos($pos_id) {
		$this->linked_pos = intval($pos_id);
	}
	
	/// Загрузить список товаров. Повторно не загружает.
	protected function loadList() {
		global $db;
		if(is_array($this->list))
			return;
		$this->list = array();
		$res = $db->query("SELECT `doc_base_kompl`.`id` AS `line_id`, `doc_base`.`id` AS `pos_id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base`.`cost`,
			`doc_base`.`proizv` AS `vendor`, `doc_base_kompl`.`cnt`,
			(SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `sklad_cnt`
		FROM `doc_base_kompl`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_base_kompl`.`kompl_id`
		WHERE `doc_base_kompl`.`pos_id`='{$this->linked_pos}'");
		$this->list_sum = 0;
		while ($nxt = $res->fetch_assoc()) {
			$nxt['cost'] = getInCost($nxt['pos_id'], 0, 1);
			$this->list_sum += $nxt['cost'] * $nxt['cnt'];
			$this->list[$nxt['line_id']] = $nxt;
		}
	}
	
	/// Получить сумму товаров в списке
	protected function calcListSum() {
		if(!is_array($this->list))
			$this->loadList();
		$this->list_sum = 0;
		foreach ($this->list as $nxt) {
			$this->list_sum += $nxt['cost'] * $nxt['cnt'];
		}
		return $this->list_sum;
	}

/// Показать редактор.
/// @return HTML код редактора
	public function Show($param, $zp) {
		$ret="
		<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<script type='text/javascript' src='/css/jquery/jquery.alerts.js'></script>
		<script src='/js/poseditor.js' type='text/javascript'></script>
		<link href='/css/poseditor.css' rel='stylesheet' type='text/css' media='screen'>
		<link href='/css/jquery/jquery.alerts.css' rel='stylesheet' type='text/css' media='screen'>
		<div id='poseditor_div'></div>
		<form action='docs.php' method='post'>
		<input type='hidden' name='mode' value='esave'>
		<input type='hidden' name='l' value='sklad'>
		<input type='hidden' name='pos' value='{$this->linked_pos}'>
		<input type='hidden' name='param' value='k'>
		Зарплата за сборку: <input type='text' name='zp' value='$zp'> руб. <button>Сохранить</button>
		</form>
		<div id='storeview_container'></div>";

		$p_setup = array(
		    'base_url'	=> '/docs.php?l=sklad&mode=srv&opt=ep&param=k&pos='.$this->linked_pos,
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
		$cols[] = 'price';
		$col_names[] = 'Цена';
		$cols[] = 'cnt';
		$col_names[] = 'Кол-во';
		$cols[] = 'sum';
		$col_names[] = 'Стоимость';
		$cols[] = 'store_cnt';
		$col_names[] = 'Остаток';

		$p_setup['columns'] = $cols;
		$p_setup['col_names'] = $col_names;

                if ($this->show_vc) {
                    $sc = array(
                        'vc', 'name', 'vendor', 'price', 'liquidity'
                    );
                    $sc_names = array ('Код', 'Название', 'Произв.', 'Цена', 'Ликвидность');
                } else {
                    $sc = array(
                        'name', 'vendor', 'price', 'liquidity'
                    );
                    $sc_names = array ('Название', 'Произв.', 'Цена', 'Ликв.');
                }
                if($this->show_tdb) {
                        $sc[] = 'type';
                        $sc[] = 'd_int';
                        $sc[] = 'd_ext';
                        $sc[] = 'size';
                        $sc[] = 'mass';
                        $sc_names[] = 't';
                        $sc_names[] = 'd';
                        $sc_names[] = 'D';
                        $sc_names[] = 'l';
                        $sc_names[] = 'm';
                }
                if($this->show_rto) {
                        $sc[] = 'transit';
                        $sc[] = 'reserve';
                        $sc[] = 'offer';
                        $sc_names[] = 'Транзит';
                        $sc_names[] = 'Резерв';
                        $sc_names[] = 'П/зак.';
                }
                $sc[] = 'allcnt';
                $sc_names[] = 'Всего';

                $p_setup['store_columns'] = $sc;
                $p_setup['store_col_names'] = $sc_names;

		$ret.="<script type=\"text/javascript\">
		var poslist = PosEditorInit(".json_encode($p_setup, JSON_UNESCAPED_UNICODE).");
		</script>";

		return $ret;
	}

/// Получить список наименований, связанных с выбранным наименованием.
/// @return json-строка с данными о наименованиях
	function GetAllContent() {
		global $CONFIG;
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
		    'sum'	=> $this->list_sum,
		);
		return json_encode($ret_data, JSON_UNESCAPED_UNICODE);
	}
	
	/// Получить информацию о наименовании
	function GetPosInfo($pos) {
		global $db, $CONFIG;

		$res = $db->query("SELECT `doc_base_links`.`id` AS `line_id`, `doc_base`.`id` AS `pos_id`, `doc_base`.`vc`, `doc_base`.`name`,
			`doc_base`.`proizv` AS `vendor`, `doc_base`.`cost`, `doc_base`.`bulkcnt`, `doc_base`.`group`, 1 AS `cnt`,
			(SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `sklad_cnt`
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
		settype($pos, 'int');
		$cnt = rcvrounded('cnt', 5);
		$ret_data = array();
		$this->loadList();
		if (!$pos)		throw new Exception("ID позиции не задан!");
		if($cnt<=0)	throw new Exception("Количество должно быть положительным!");
		if($pos==$this->linked_pos) {
			$ret_data['response'] = 'err';
			$ret_data['message'] = "Нельзя добавить себя!";
			return json_encode($ret_data, JSON_UNESCAPED_UNICODE);
		}
		
		$res = $db->query("SELECT `id`, `kompl_id`, `cnt` FROM `doc_base_kompl` WHERE `pos_id`='{$this->linked_pos}' AND `kompl_id`='$pos'");
		if ($res->num_rows == 0) {
			$line_id = $db->insertA('doc_base_kompl', array('pos_id'=>$this->linked_pos, 'kompl_id'=>$pos, 'cnt'=>$cnt) );
			doc_log("UPDATE komplekt", "add kompl: pos_id:$pos, cnt:$cnt", 'pos', $this->linked_pos);
			
			$res = $db->query("SELECT `doc_base`.`id` AS `pos_id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base`.`proizv` AS `vendor`, $cnt AS `cnt`,
				`doc_base`.`cost`,
				(SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `sklad_cnt`
				FROM `doc_base`
				WHERE `doc_base`.`id`='$pos'");
			$line = $res->fetch_assoc();
			$line['cost'] = getInCost($line['pos_id'], 0, 1);;
			$line['line_id'] = $line_id;
			$ret_data['response'] = 'add';
			$ret_data['line'] = $line;
			$ret_data['sum'] = $this->list_sum + $cnt * $line['cost'];
		}
		else {
			$ret_data['response'] = 'err';
			$ret_data['message'] = "Уже есть в списке!";
		}
		
		return json_encode($ret_data, JSON_UNESCAPED_UNICODE);
	}
	
	/// Обновить строку документа с указанным ID
	/// @param $line_id id строки
	/// @param $type Идентификатор колонки
	/// @param $value Записываемое значение
	function UpdateLine($line_id, $type, $value) {
		global $db;
		$this->loadList();
		// Тут надо removeline!
		if(!isset($this->list[$line_id]))
			throw new Exception("Строка не найдена. Вероятно, она была удалена другим пользователем или Вами в другом окне.");

		$ret_data = array (
			'response'	=> 'update'
		);

		if($type == 'cnt' && $value != $this->list[$line_id]['cnt']) {
			if($value <= 0) $value = 1;
			$value = round($value, 4);

			$old_cnt = $this->list[$line_id]['cnt'];
			$db->update('doc_base_kompl', $line_id, 'cnt', $value);
			$this->list[$line_id]['cnt'] = $value;
		
			doc_log("UPDATE","change kompl cnt: pos:{$this->list[$line_id]['pos_id']}, line_id:$line_id, cnt:$old_cnt => $value",'pos',$this->linked_pos);
		}
		else if($type=='sum' && $value!=($this->list[$line_id]['cost']*$this->list[$line_id]['cnt'])) {
			if($value <= 0) $value = 1;
			$value = round($value/$this->list[$line_id]['cost'], 5);
			$db->update('doc_base_kompl', $line_id, 'cnt', $value);
			$old_cnt = $this->list[$line_id]['cnt'];
			$this->list[$line_id]['cnt'] = $value;
			doc_log("UPDATE","change kompl cnt: pos:{$this->list[$line_id]['pos_id']}, line_id:$line_id, cnt:$old_cnt => $value",'pos',$this->linked_pos);
		}
		$ret_data['sum'] = $this->calcListSum();
		$ret_data['update_line'] = $this->list[$line_id];
		return json_encode($ret_data, JSON_UNESCAPED_UNICODE);
	}
	
	/// Удалить из списка строку с указанным ID
	function RemoveLine($line_id) {
		global $db;
		$this->loadList();
		if(array_key_exists($line_id, $this->list)) {
			$db->delete('doc_base_kompl', $line_id);
			doc_log("UPDATE","del komplekt: pos: {$this->list[$line_id]['pos_id']}, line_id:$line_id, name:{$this->list[$line_id]['name']}", 'pos', $this->linked_pos);
			unset($this->list[$line_id]);
		}

		$ret_data = array (
		    'response'	=> '5',
		    'remove'	=> array('line_id'=>$line_id),
		    'sum'	=> $this->calcListSum()
		);
		return json_encode($ret_data, JSON_UNESCAPED_UNICODE);
	}

	/// Получить список номенклатуры заданной группы
	function GetSkladList($group) {
		global $db;
		settype($group, 'int');
		$sql = "SELECT `doc_base`.`id`,`doc_base`.`vc`,`doc_base`.`group`,`doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`likvid`,
			`doc_base`.`cost` AS `base_price`, `doc_base`.`bulkcnt`,
			`doc_base`.`cost_date`,	`doc_base_dop`.`koncost`,  `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`,
			`doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base`.`mass`,
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
			`doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base`.`mass`, `doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt`,
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
			$pc = PriceCalc::getInstance();
			while ($nxt = $res->fetch_assoc()) {				
				$nxt['price'] = $pc->getPosDefaultPriceValue($nxt['id']);
				
				if ($ret != '')
					$ret.=', ';

				$ret .= json_encode($nxt, JSON_UNESCAPED_UNICODE);
			}
		}
		return $ret;
	}
};

?>