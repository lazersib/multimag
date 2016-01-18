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

include_once('include/doc.poseditor.php');

class SZapPosEditor extends DocPosEditor
{

	function Show($param='') {
		global $CONFIG;
		// Список товаров
		$ret="
		<script src='/js/poseditor.js' type='text/javascript'></script>
		<link href='/css/poseditor.css' rel='stylesheet' type='text/css' media='screen'>
		<div id='poseditor_div'></div>
		<div id='storeview_container'></div>";

		$p_setup = array(
		    'base_url'	=> '/doc_sc.php?mode=srv&sn=sborka_zap&doc='.$this->doc,
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
		$col_names[] = 'Сумма';
		$cols[] = 'store_cnt';
		$col_names[] = 'Остаток';
		$cols[] = 'place';
		$col_names[] = 'Зарплата';

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
                if($this->show_rto) {
                        $sc[] = 'transit';
                        $sc[] = 'reserve';
                        $sc[] = 'offer';
                        $sc_names[] = 'Транзит';
                        $sc_names[] = 'Резерв';
                        $sc_names[] = 'П/зак.';
                }
                $sc[] = 'cnt';
                $sc[] = 'allcnt';
                $sc[] = 'place';
                $sc_names[] = 'Склад';
                $sc_names[] = 'Всего';
                $sc_names[] = 'Место';

                $p_setup['store_columns'] = $sc;
                $p_setup['store_col_names'] = $sc_names;

		$ret.="<script type=\"text/javascript\">
		var poslist = PosEditorInit(".json_encode($p_setup, JSON_UNESCAPED_UNICODE).");
		</script>";
		
		return $ret;
	}

// Получить весь текущий список товаров (документа)
	function GetAllContent() {
		global $db;
		$res = $db->query("SELECT `doc_list_pos`.`id` AS `line_id`, `doc_base`.`id` AS `pos_id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base`.`proizv`,
			`doc_base`.`cost` AS `base_price`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`cnt` AS `sklad_cnt`, `doc_base_cnt`.`mesto`,
			`doc_list_pos`.`gtd`, `doc_base`.`bulkcnt`, `doc_base`.`group`
			FROM `doc_list_pos`
			INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
			LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
			WHERE `doc_list_pos`.`doc`='{$this->doc}' AND `doc_list_pos`.`page`='0'");
		$ret = '';
		$pc = PriceCalc::getInstance();
		while ($nxt = $res->fetch_assoc()) {
			if ($this->cost_id)	$scost = $pc->getPosSelectedPriceValue($nxt['pos_id'], $this->cost_id, $nxt);
			else			$scost = sprintf("%0.2f", $nxt['base_cost']);
			$nxt['cost'] = sprintf("%0.2f", $nxt['cost']);
			if ($ret)		$ret.=', ';

			// Расчитываем зарплату и выводим как место
			$rs = $db->query("SELECT `doc_base_params`.`id`, `doc_base_values`.`value` 
                            FROM `doc_base_params`
                            LEFT JOIN `doc_base_values` ON `doc_base_values`.`param_id`=`doc_base_params`.`id` AND `doc_base_values`.`id`='{$nxt['pos_id']}'
                            WHERE `doc_base_params`.`codename`='ZP'");
			if ($rs->num_rows) {
				$rs_data = $rs->fetch_row();
				$zp = sprintf("%0.2f", $rs_data[1]);
			}
			else			$zp = 'НЕТ';

			$ret.="{line_id: '{$nxt['line_id']}', pos_id: '{$nxt['pos_id']}', vc: '{$nxt['vc']}', name: '{$nxt['name']} - {$nxt['proizv']}', cnt: '{$nxt['cnt']}', cost: '{$nxt['cost']}', scost: '$scost', sklad_cnt: '{$nxt['sklad_cnt']}', place: '$zp', gtd: '{$nxt['gtd']}'";

			$ret.="}";
		}
		return $ret;
	}

	function GetPosInfo($pos) {
		global $db;
		settype($pos, 'int');
		$res = $db->query("SELECT `doc_list_pos`.`id` AS `line_id`, `doc_base`.`id` AS `pos_id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base`.`proizv`,
			`doc_base`.`cost` AS `base_price`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`cnt` AS `sklad_cnt`, `doc_base_cnt`.`mesto`,
			`doc_list_pos`.`gtd`, `doc_base`.`bulkcnt`, `doc_base`.`group`
			FROM `doc_base`
			LEFT JOIN `doc_list_pos` ON `doc_base`.`id`=`doc_list_pos`.`tovar` AND `doc_list_pos`.`doc`='{$this->doc}'
			LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
			WHERE `doc_base`.`id`='$pos'");
		$ret = '';
		$pc = PriceCalc::getInstance();
		if ($nxt = $res->fetch_assoc()) {

			if ($this->cost_id)	$scost = $pc->getPosSelectedPriceValue($nxt['pos_id'], $this->cost_id, $nxt);
			else			$scost = sprintf("%0.2f", $nxt['base_cost']);
			if (!$nxt['cnt'])	$nxt['cnt'] = 1;
			if (!$nxt['cost'])	$nxt['cost'] = $scost;
			$nxt['cost'] = sprintf("%0.2f", $nxt['cost']);

			// Расчитываем зарплату и выводим как место
			$rs = $db->query("SELECT `doc_base_params`.`id`, `doc_base_values`.`value` FROM `doc_base_params`
                            LEFT JOIN `doc_base_values` ON `doc_base_values`.`param_id`=`doc_base_params`.`id` AND `doc_base_values`.`id`='{$nxt['pos_id']}'
                            WHERE `doc_base_params`.`codename`='ZP'");
			if ($rs->num_rows) {
				$rs_data = $rs->fetch_row();
				$zp = sprintf("%0.2f", $rs_data[0]);
			}
			else			$zp = 'НЕТ';

			$ret = "{response: 3, data: {
		line_id: '{$nxt['line_id']}', pos_id: '{$nxt['pos_id']}', vc: '{$nxt['vc']}', name: '{$nxt['name']} - {$nxt['proizv']}', cnt: '{$nxt['cnt']}', cost: '{$nxt['cost']}', scost: '$scost', sklad_cnt: '{$nxt['sklad_cnt']}', place: '$zp', gtd: '{$nxt['gtd']}'
		} }";
		}

		return $ret;
	}

/// Добавляет указанную складскую позицию в список
	function AddPos($pos) {
		global $db;
		$cnt = rcvrounded('cnt', 4);
		$cost = rcvrounded('cost', 2);
		settype($pos, 'int');
		$add = 0;
		$ret = '';

		$res = $db->query("SELECT `id`, `tovar`, `cnt`, `cost` FROM `doc_list_pos` WHERE `doc`='{$this->doc}' AND `tovar`='$pos'");
		if (!$res->num_rows) {
			$db->query("INSERT INTO doc_list_pos (`doc`,`tovar`,`cnt`,`cost`) VALUES ('{$this->doc}','$pos','$cnt','$cost')");
			$pos_line = $db->insert_id;
			doc_log("UPDATE", "add pos: pos:$pos", 'doc', $this->doc);
			doc_log("UPDATE", "add pos: pos:$pos", 'pos', $pos);
			$add = 1;
		}
		else {
			$nxt = $res->fetch_row();
			$pos_line = $nxt[0];
			$db->query("UPDATE `doc_list_pos` SET `cnt`='$cnt', `cost`='$cost' WHERE `id`='$nxt[0]'");
			doc_log("UPDATE", "change cnt: pos:$nxt[1], doc_list_pos:$nxt[0], cnt:$nxt[2]+1", 'doc', $this->doc);
			doc_log("UPDATE", "change cnt: pos:$nxt[1], doc_list_pos:$nxt[0], cnt:$nxt[2]+1, doc:{$this->doc}", 'pos', $nxt[1]);
		}
		$doc_sum = $this->doc_obj->recalcSum();

		if ($add) {
			$res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`,
				`doc_list_pos`.`cost`, `doc_base_cnt`.`cnt` AS `sklad_cnt`, `doc_base_cnt`.`mesto`, `doc_base`.`bulkcnt`, `doc_base`.`group`,
				`doc_base`.`cost` AS `base_price`
				FROM `doc_list_pos`
				INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
				LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
				WHERE `doc_list_pos`.`id`='$pos_line'");
			$line = $res->fetch_assoc();

			$rs = $db->query("SELECT `doc_base_params`.`id`, `doc_base_values`.`value` FROM `doc_base_params`
				LEFT JOIN `doc_base_values` ON `doc_base_values`.`param_id`=`doc_base_params`.`id` AND `doc_base_values`.`id`='{$line['id']}'
				WHERE `doc_base_params`.`codename`='ZP'");
			if ($rs->num_rows) {
				$rs_data = $rs->fetch_row();
				$zp = sprintf("%0.2f", $rs_data[0]);
			}
			else			$zp = 'НЕТ';
			
			$pc = PriceCalc::getInstance();
			$cost = $this->cost_id ? $pc->getPosSelectedPriceValue($line['id'], $this->cost_id, $line) : $line['cost'];
			$ret = "{ response: 'add', line: { line_id: '$pos_line', pos_id: '{$line['id']}', vc: '{$line['vc']}', name: '{$line['name']} - {$line['proizv']}', cnt: '{$line['cnt']}', scost: '$cost', cost: '{$line['cost']}', sklad_cnt: '{$line['sklad_cnt']}', place: '$zp', gtd: '' }, sum: '$doc_sum' }";
		}
		else {
			$cost = sprintf("%0.2f", $cost);
			$ret = "{ response: 'update', update_line: { line_id: '$pos_line', cnt: '{$cnt}', cost: '{$cost}'}, sum: '$doc_sum' }";
		}
		return $ret;
	}
};
?>
