<?php

include_once('include/doc.poseditor.php');

class SZapPosEditor extends DocPosEditor
{

	function Show($param='') {
	global $CONFIG;
	// Список товаров
	$ret="
	<script src='/css/poseditor.js' type='text/javascript'></script>
	<link href='/css/poseditor.css' rel='stylesheet' type='text/css' media='screen'>
	<table width='100%' id='poslist'><thead><tr>
	<th width='60px' align='left'>№</th>";
	if($this->show_vc>0)	$ret.="<th width='90px' align='left' title='Код изготовителя'><div class='order_button' id='pl_order_vc'></div> Код</th>";
	$ret.="<th><div class='order_button' id='pl_order_name'></div> Наименование</th>
	<th width='90px' title='Выбранная цена'>Выбр. цена</th>
	<th width='90px' class='hl'><div class='order_button' id='pl_order_cost'></div> Цена</th>
	<th width='60px' class='hl'>Кол-во</th>
	<th width='90px' class='hl'>Стоимость</th>
	<th width='60px' title='Остаток товара на складе'>Остаток</th>
	<th width='90px'>Зарплата</th>";
	$ret.="</tr>
	</thead>
	<tfoot>
	<tr id='pladd'>
	<td><input type='text' id='pos_id' autocomplete='off' tabindex='1'></td>";
	if($this->show_vc>0)	$ret.="<td><input type='text' id='pos_vc' autocomplete='off' tabindex='2'></td>";
	$ret.="<td><input type='text' id='pos_name' autocomplete='off' tabindex='3'></td>
	<td id='pos_scost'></td>
	<td><input type='text' id='pos_cost' autocomplete='off' tabindex='4'></td>
	<td><input type='text' id='pos_cnt' autocomplete='off' tabindex='5'></td>
	<td id='pos_sum'></td>
	<td id='pos_sklad_cnt'></td>
	<td id='pos_mesto'></td>";
	
	$ret.="
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
	if($this->show_vc>0)	$ret.="<th>Код";
	$ret.="<th>Наименование<th>Марка<th>Цена, р.<th>Ликв.<th>Р.цена, р.<th>Аналог";
	if($this->show_tdb>0)	$ret.="<th>Тип<th>d<th>D<th>B<th>Масса";
	if($this->show_rto>0)	$ret.="<th><img src='/img/i_lock.png' alt='В резерве'><th><img src='/img/i_alert.png' alt='Предложений'><th><img src='/img/i_truck.png' alt='В пути'>";
	$ret.="<th>Склад<th>Всего<th>Место
	</thead>
	<tbody id='sklad_list'>
	</tbody>
	</table>
	</td></tr>
	</table>";
	if(!@$CONFIG['poseditor']['need_dialog'])	$CONFIG['poseditor']['need_dialog']=0;
	else						$CONFIG['poseditor']['need_dialog']=1;
	$ret.="<script type=\"text/javascript\">
	var poslist=PosEditorInit('/doc_sc.php?mode=srv&sn=sborka_zap&doc={$this->doc}',{$this->editable})
	poslist.show_column['vc']='{$this->show_vc}'

	var skladview=document.getElementById('sklad_view')
	skladview.show_column['vc']='{$this->show_vc}'
	
	skladlist=document.getElementById('sklad_list').needDialog={$CONFIG['poseditor']['need_dialog']};
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
			$rs = $db->query("SELECT `doc_base_params`.`id`, `doc_base_values`.`value` FROM `doc_base_params`
				LEFT JOIN `doc_base_values` ON `doc_base_values`.`param_id`=`doc_base_params`.`id` AND `doc_base_values`.`id`='{$nxt['pos_id']}'
				WHERE `doc_base_params`.`param`='ZP'");
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
			`doc_base`.`cost` AS `bcost`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`cnt` AS `sklad_cnt`, `doc_base_cnt`.`mesto`,
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
				WHERE `doc_base_params`.`param`='ZP'");
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
				WHERE `doc_base_params`.`param`='ZP'");
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
