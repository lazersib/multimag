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

/// Редактор списка наименований
class PosEditor {
	var $editable;		///< Разрешено ли редактирование и показ складского блока
	var $cost_id;		///< id выбранной цены. 0 - базовая
	var $sklad_id;		///< id склада
	var $show_vc;		///< Показывать код производителя
	var $show_tdb;		///< Показывать тип/размеры/массу
	var $show_rto;		///< Показывать резерв/в пути/предложения
	var $list;	// Список наименований

	/// Конструктор
	function __construct(){
		global $CONFIG;
		$this->editable=0;
		$this->show_vc=@$CONFIG['poseditor']['vc'];
		$this->show_tdb=@$CONFIG['poseditor']['tdb'];
		$this->show_rto=@$CONFIG['poseditor']['rto'];
	}

	/// Разрешить или запретить изменение данных в списке наименований
	/// @param editable 0: запретить, 1: разрешить
	function SetEditable($editable)
	{
		$this->editable=$editable;
	}


	function SetVC($vc) {
		$this->show_vc = $vc;
	}

	function getGroupsTree()
	{
		return "Отбор:<input type='text' id='sklsearch'><br>
		<div onclick='tree_toggle()'>
		<div><a href='' onclick=\"\">Группы</a></div>
		<ul class='Container'>".$this->getGroupLevel(0)."</ul>
		</div>";
	}

	function getGroupLevel($level)
	{
		global $db;
		settype($level, 'int');
		$ret='';
		$res = $db->query("SELECT `id`, `name`, `desc` FROM `doc_group` WHERE `pid`='$level' ORDER BY `id`");
		$i=0;
		$r='';
		if($level==0) $r='IsRoot';
		$cnt = $res->num_rows;
		while($nxt = $res->fetch_row()){
			if($nxt[0] == 0) continue;
			$item="<a href='' title='$nxt[2]' onclick=\"return getSkladList(event, '$nxt[0]')\" >".html_out($nxt[1])."</a>";
			if($i>=($cnt-1)) $r.=" IsLast";
			$tmp=$this->getGroupLevel($nxt[0]); // рекурсия
			if($tmp)	$ret.="<li class='Node ExpandClosed $r'><div class='Expand'></div><div class='Content'>$item</div><ul class='Container'>$tmp</ul></li>\n";
			else   		$ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div></li>\n";
			$i++;
		}
		return $ret;
	}

	function getGroupData($pid) {
		global $db;
		settype($pid, 'int');
		$data = array();
		$res = $db->query("SELECT `id`, `name` FROM `doc_group` WHERE `pid`='$pid' ORDER BY `id`");
		while($nxt = $res->fetch_row()){
			if($nxt[0] == 0) continue;
			$data[] = array(
			    'id'	=> $nxt[0],
			    'name'	=> $nxt[1],
			    'childs'	=> $this->getGroupData($nxt[0])
			);
		}
		return $data;
	}
	
	/// Получить список групп в виде json
	public function getGroupList() {
		$ret_data = array (
			'response'	=> 'group_list',
			'content'	=> $this->getGroupData(0)
		);

		return json_encode($ret_data, JSON_UNESCAPED_UNICODE);
	}

	function getOrder(){
		global $CONFIG;
		switch(@$CONFIG['doc']['sklad_default_order'])
		{
			case 'vc':	$order='`doc_base`.`vc`';	break;
			case 'cost':	$order='`doc_base`.`cost`';	break;
			default:	$order='`doc_base`.`name`';
		}
		return $order;
	}

};

/// Редактор списка наименований документа.
/// При создании экземпляра класса нужно указать ID существующеего документа
class DocPosEditor extends PosEditor {
	var $doc;	// Id документа
	var $doc_obj;	// Объект ассоциированного документа
	var $show_sn;	// Показать серийные номера
	var $show_gtd;	// Показывать номер ГТД в поступлении
	var $list;	// Список товаров

public function __construct($doc) {
	global $CONFIG;
	parent::__construct();
	$this->doc = $doc->getDocNum();
	$this->show_sn = 0;
	$this->doc_obj = &$doc;
	$doc_data = $this->doc_obj->getDocDataA();
	$dop_data = $this->doc_obj->getDopDataA();
	if( @$CONFIG['poseditor']['sn_enable'] && ($doc_data['type']==1 || $doc_data['type']==2))	$this->show_sn=1;
	if( @$CONFIG['poseditor']['true_gtd'] && $doc_data['type']==1)					$this->show_gtd=1;
	$pc = PriceCalc::getInstance();
	$pc->setAgentId($doc_data['agent']);
	$pc->setFromSiteFlag(@$dop_data['ishop']);
}

/// Загрузить список товаров документа. Повторно не загружает.
protected function loadList() {
	global $db;
	if(is_array($this->list))
		return;
	$this->list = array();
	$res = $db->query("SELECT `doc_list_pos`.`id` AS `line_id`, `doc_base`.`id` AS `pos_id`, `doc_base`.`vc`, `doc_base`.`name`,
			`doc_base`.`proizv` AS `vendor`, `doc_base`.`cost` AS `base_price`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`,
			`doc_base_cnt`.`cnt` AS `sklad_cnt`, `doc_base_cnt`.`mesto` AS `place`, `doc_list_pos`.`gtd`, `doc_list_pos`.`comm`,
			`doc_base`.`bulkcnt`, `doc_base`.`group`
		FROM `doc_list_pos`
		INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
		WHERE `doc_list_pos`.`doc`='{$this->doc}' AND `doc_list_pos`.`page`='0'
		ORDER BY `doc_list_pos`.`id`");
	while ($nxt = $res->fetch_assoc())
		$this->list[$nxt['line_id']] = $nxt;
}

/// Пересчитывает авто-цены, обновляет их в базе, возвращает true, если хотя бы одна цена была обновлена
/// initPriceCalc и loadList должны быть вызваны заранее.
protected function recalcPrices() {
	global $db;
	if ($this->cost_id)
		return false;
	if(!$this->editable)
		return false;
	
	$updated = false;	
	$pc = PriceCalc::getInstance();
	foreach ($this->list as $line_id => $line) {
		$need_price = $pc->getPosAutoPriceValue($line['pos_id'], $line['cnt'], $line);
		if($line['cost'] != $need_price ) {
			$updated = true;
			$this->list[$line_id]['cost'] = $need_price;					
			$db->update('doc_list_pos', $line_id, 'cost', $need_price);
		}
	}
	return $updated;
}

/// Загрузить в калькулятор цен базовую стоимтость заказа
/// @return Экземпляр PriceCalc
protected function initPriceCalc() {
	$this->doc_base_sum = 0;
	$this->loadList();
	$pc = PriceCalc::getInstance();
	foreach ($this->list as $line) {
		if ($this->cost_id)
			$price = $pc->getPosSelectedPriceValue($line['pos_id'], $this->cost_id, $line);
		else	$price = $pc->getPosDefaultPriceValue($line['pos_id']);
		$this->doc_base_sum += $price*$line['cnt'];
	}
	$pc->setOrderSum($this->doc_base_sum);
	return $pc;
}

/// Формирует html код списка товаров документа
function Show($param='') {
	global $CONFIG;
	// Список товаров
	/// @note TODO: возможность отключения редактирования в зависимости от статуса документа, настройка отображаемых столбцов из конфига. Не забыть про серийные номера.
	/// Возможность отключения строки быстрого ввода
	/// В итоге - сделать базовый класс, от которого наследуется редактор документов, редактор комплектующих, итп.
	$ret="
	<script src='/js/poseditor.js' type='text/javascript'></script>
	<link href='/css/poseditor.css' rel='stylesheet' type='text/css' media='screen'>
	<div id='poseditor_div'></div>
	<div id='storeview_container'></div>";
	
	$p_setup = array(
	    'base_url'	=> '/doc.php?doc='.$this->doc.'&mode=srv',
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
	$cols[] = 'sprice';
	$col_names[] = 'Выб. цена';
	$cols[] = 'price';
	$col_names[] = 'Цена';
	$cols[] = 'cnt';
	$col_names[] = 'Кол-во';
	$cols[] = 'sum';
	$col_names[] = 'Сумма';
	$cols[] = 'store_cnt';
	$col_names[] = 'Остаток';
	$cols[] = 'place';
	$col_names[] = 'Место';
	if($this->show_gtd) {
		$cols[] = 'gtd';
		$col_names[] = 'ГТД';
	}
	if($this->show_sn) {
		$cols[] = 'sn';
		$col_names[] = 'SN';
	}
	
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

/// Получить весь текущий список товаров (документа)
function GetAllContent() {
	global $CONFIG, $db;

	$pc = $this->initPriceCalc();	// И loadList заодно
	$sum = 0;
		
	$retail_price_id = $pc->getRetailPriceId();
	$this->recalcPrices();
	
	$pos_array = array();
	foreach ($this->list as $nxt) {
		if ($this->cost_id)
			$nxt['scost'] = $pc->getPosSelectedPriceValue($nxt['pos_id'], $this->cost_id, $nxt);
		else {
			$nxt['scost'] = $pc->getPosUserPriceValue($nxt['pos_id'], $nxt);
			
			$auto_price_id = $pc->getPosAutoPriceID($nxt['pos_id'], $nxt['cnt'], $nxt);
			if($auto_price_id == $retail_price_id)
				$nxt['retail'] = 1;
		}
		
		$sum += $nxt['cost']*$nxt['cnt'];
		
		$nxt['cost'] = sprintf("%0.2f", $nxt['cost']);
		
		if(! @$CONFIG['doc']['no_print_vendor'])
			$nxt['name'].=' - '.$nxt['vendor'];

		if ($this->show_sn) {
			$doc_data = $this->doc_obj->getDocDataA();
			if ($doc_data[1] == 1)		$column = 'prix_list_pos';
			else if ($doc_data[1] == 2)	$column = 'rasx_list_pos';
			else	throw new Exception("Документ не поддерживает работу с серийными номерами");
			$rs = $db->query("SELECT `doc_list_sn`.`id`, `doc_list_sn`.`num`, `doc_list_sn`.`rasx_list_pos` FROM `doc_list_sn` WHERE `$column`='{$nxt['line_id']}'");
			$nxt['sn'] = $rs->num_rows;
		}
		$pos_array[] = $nxt;
	}
	
	$ret_data = array (
	    'response'	=> 'loadlist',
	    'content'	=> $pos_array,
	    'base_sum'	=> $this->doc_base_sum,
	    'sum'	=> $sum,
	);
	if($this->cost_id) {
		$ret_data['price_name'] = '';
	}
	else {
		$ret_data['price_name']	= $pc->getCurrentPriceName();
		$ret_data['nbp_info'] = $pc->getNextPriceInfo();
		$ret_data['npp_info'] = $pc->getNextPeriodicPriceInfo();
		$ret_data['auto_price'] = 1;
	}
	// Не забыть обновить сумму документа
	
	return json_encode($ret_data, JSON_UNESCAPED_UNICODE);
}

/// Получить информацию о наименовании
	function GetPosInfo($pos) {
		global $db, $CONFIG;

		$res = $db->query("SELECT `doc_list_pos`.`id` AS `line_id`, `doc_base`.`id` AS `pos_id`, `doc_base`.`vc`, `doc_base`.`name`,
			`doc_base`.`proizv` AS `vendor`, `doc_base`.`cost` AS `base_price`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`,
			`doc_base_cnt`.`cnt` AS `sklad_cnt`, `doc_base_cnt`.`mesto` AS `place`, `doc_list_pos`.`gtd`, `doc_base`.`bulkcnt`, `doc_base`.`group`
			FROM `doc_base`
			LEFT JOIN `doc_list_pos` ON `doc_base`.`id`=`doc_list_pos`.`tovar` AND `doc_list_pos`.`doc`='{$this->doc}'
			LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
			WHERE `doc_base`.`id`='$pos'");

		$ret = '';
		if ($res->num_rows) {
			$nxt = $res->fetch_assoc();
			$pc = $this->initPriceCalc();
			
			if ($this->cost_id)
				$nxt['scost'] = $pc->getPosSelectedPriceValue($nxt['pos_id'], $this->cost_id, $nxt);
			else {
				$nxt['scost'] = $pc->getPosUserPriceValue($nxt['pos_id'], $nxt['cnt']);
				if($this->editable) {
					$need_cost = $pc->getPosAutoPriceValue($nxt['pos_id'], $nxt['cnt']);
					if($nxt['cost'] != $need_cost ) {
						$nxt['cost'] = $need_cost;					
						$db->update('doc_list_pos', $nxt['line_id'], 'cost', $need_cost);
					}
				}
			}
			if (!$nxt['cnt'])	$nxt['cnt'] = 1;
			if (!$nxt['cost'])	$nxt['cost'] = $nxt['scost'];
			$nxt['cost'] = sprintf("%0.2f", $nxt['cost']);
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
	$cost = rcvrounded('cost', 2);
	
	$this->loadList();

	$add = 0;
	$found = 0;
	$ret = '';

	if(!$pos)	throw new Exception("ID позиции не задан!");
	if($cnt<=0)	throw new Exception("Количество должно быть положительным!");
	
	foreach($this->list as $line_id=>$f_line) {
		if($f_line['pos_id']==$pos) {
			$found = 1;
			break;
		}
	}
	
	$ret_data = array();
	$pc = $this->initPriceCalc();
	if(!$found) {
		$line_id = $db->insertA('doc_list_pos', array('doc'=>$this->doc, 'tovar'=>$pos, 'cnt'=>$cnt, 'cost'=>$cost) );
		doc_log("UPDATE","add pos: pos:$pos",'doc',$this->doc);

		$res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base`.`proizv` AS `vendor`, `doc_list_pos`.`cnt`,
			`doc_list_pos`.`cost`, `doc_base_cnt`.`cnt` AS `sklad_cnt`, `doc_base_cnt`.`mesto` AS `place`, `doc_base`.`cost` AS `base_price`,
			`doc_base`.`bulkcnt`, `doc_base`.`group`
			FROM `doc_list_pos`
			INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
			LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
			WHERE `doc_list_pos`.`id`='$line_id'");
		$line = $res->fetch_assoc();
		$pc = PriceCalc::getInstance();
		$line['scost'] = $this->cost_id?$pc->getPosSelectedPriceValue($line['id'], $this->cost_id, $line):$line['cost'];
		$line['line_id'] = $line_id;
		$line['pos_id'] = $line['id'];
		$line['gtd'] = '';
		if(! @$CONFIG['doc']['no_print_vendor'])
				$line['name'].=' - '.$line['vendor'];
		
		if(!$this->cost_id) {
			$retail_price_id = $pc->getRetailPriceId();
			$auto_price_id = $pc->getPosAutoPriceID($line['pos_id'], $cnt);
			if($auto_price_id == $retail_price_id)
				$line['retail'] = 1;
			else	$line['retail'] = 0;
			$need_cost = $pc->getPosSelectedPriceValue($line['pos_id'], $auto_price_id, $line);
			if($line['cost'] != $need_cost ) {
				$line['cost'] = $need_cost;					
				$db->update('doc_list_pos', $line_id, 'cost', $need_cost);
			}
			$this->list[$line_id] = $line;
			// retail метки!
			if($this->recalcPrices()) {
				$new_list = array();
				foreach($this->list as $line_id => $line) {
					$new_list[] = array('line_id'=> $line_id, 'cost'=>$line['cost'], 'cnt'=>$line['cnt'], 'sklad_cnt'=>$line['sklad_cnt']);
				}
				$ret_data['update_list'] = $new_list;
			}
		}
		
		$ret_data['response'] = 'add';
		$ret_data['line'] = $line;
	}
	else {
		$ret_data['response'] = 'update';
		$ret_data['update_line'] = $this->list[$line_id];
	}
	
	if($this->cost_id) {
		$ret_data['price_name'] = '';
	}
	else {
		$ret_data['price_name']	= $pc->getCurrentPriceName();
		$ret_data['nbp_info'] = $pc->getNextPriceInfo();
		$ret_data['npp_info'] = $pc->getNextPeriodicPriceInfo();
		$ret_data['auto_price'] = 1;
	}
	
	$this->updateDocSum();
	$ret_data['sum'] = $this->doc_obj->getDocData('sum');
	$ret_data['base_sum'] = $this->doc_base_sum;
	
	return json_encode($ret_data, JSON_UNESCAPED_UNICODE);
}

/// Удалить из списка строку с указанным ID
function RemoveLine($line_id)
{
	global $db;
	$pc = $this->initPriceCalc();
	if(array_key_exists($line_id, $this->list)) {
		$db->delete('doc_list_pos', $line_id);
		doc_log("UPDATE","del line: pos: {$this->list[$line_id]['pos_id']}, line_id:$line_id, cnt:{$this->list[$line_id]['cnt']}, cost:{$this->list[$line_id]['cost']}",'doc',$this->doc);
		unset($this->list[$line_id]);
		$this->updateDocSum();
	}
	
	
	$ret_data = array (
	    'response'	=> '5',
	    'remove'	=> array('line_id'=>$line_id),
	    'base_sum'	=> $this->doc_base_sum,
	    'sum'	=> $this->doc_obj->getDocData('sum')
	);
	if($this->cost_id) {
		$ret_data['price_name'] = '';
	}
	else {
		$ret_data['price_name']	= $pc->getCurrentPriceName();
		$ret_data['nbp_info'] = $pc->getNextPriceInfo();
		$ret_data['npp_info'] = $pc->getNextPeriodicPriceInfo();
		$ret_data['auto_price'] = 1;
	}
	// Не забыть обновить сумму документа
	
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
		$db->update('doc_list_pos', $line_id, 'cnt', $value);
		$this->list[$line_id]['cnt'] = $value;
		
		if(!$this->cost_id) {
			$pc = $this->initPriceCalc();
			$retail_price_id = $pc->getRetailPriceId();
			$auto_price_id = $pc->getPosAutoPriceID($this->list[$line_id]['pos_id'], $value);
			if($auto_price_id == $retail_price_id)
				$this->list[$line_id]['retail'] = 1;
			else	$this->list[$line_id]['retail'] = 0;
			$need_cost = $pc->getPosSelectedPriceValue($this->list[$line_id]['pos_id'], $auto_price_id, $this->list[$line_id]);
			if($this->list[$line_id]['cost'] != $need_cost ) {
				$this->list[$line_id]['cost'] = $need_cost;					
				$db->update('doc_list_pos', $line_id, 'cost', $need_cost);
			}
			// retail метки!
			if($this->recalcPrices()) {
				$new_list = array();
				foreach($this->list as $line_id => $line) {
					$new_list[] = array('line_id'=> $line_id, 'cost'=>$line['cost'], 'cnt'=>$line['cnt'], 'sklad_cnt'=>$line['sklad_cnt']);
				}
				$ret_data['update_list'] = $new_list;
			}
		}		
		doc_log("UPDATE","change cnt: pos:{$this->list[$line_id]['pos_id']}, line_id:$line_id, cnt:$old_cnt => $value",'doc',$this->doc);
		$this->updateDocSum();
	}
	else if($type=='cost' && $value != $this->list[$line_id]['cost'] && $this->cost_id) {
		if($value <= 0) $value = 1;
		$db->update('doc_list_pos', $line_id, 'cost', $value);
		
		doc_log("UPDATE","change cost: pos:{$this->list[$line_id]['pos_id']}, line_id:$line_id, cost:{$this->list[$line_id]['cost']} => $value",'doc',$this->doc);
		$this->list[$line_id]['cost'] = $value;
		$this->updateDocSum();
	}
	else if($type=='sum' && $value!=($this->list[$line_id]['cost']*$this->list[$line_id]['cnt']) && $this->cost_id) {
		if($value <= 0) $value = 1;
		$value = round($value/$this->list[$line_id]['cnt'], 2);
		$db->update('doc_list_pos', $line_id, 'cost', $value);

		doc_log("UPDATE","change cost: pos:{$this->list[$line_id]['pos_id']}, line_id:$line_id, cost:{$this->list[$line_id]['cost']} => $value",'doc',$this->doc);
		$this->list[$line_id]['cost'] = $value;
		$this->updateDocSum();
	}
	else if($type=='gtd' && $value!=$this->list[$line_id]['gtd']) {
		$db->update('doc_list_pos', $line_id, 'gtd', $value);
		doc_log("UPDATE","change gtd: pos:{$this->list[$line_id]['pos_id']}, line_id:$line_id, gtd:{$this->list[$line_id]['gtd']} => $value",'doc',$this->doc);
		$this->list[$line_id]['gtd'] = $value;
	}
	else if($type=='comm' && $value!=$this->list[$line_id]['comm'])
	{
		$db->update('doc_list_pos', $line_id, 'comm', $value);
		doc_log("UPDATE","change comm: pos:{$this->list[$line_id]['pos_id']}, line_id:$line_id, comm:{$this->list[$line_id]['comm']} => $value",'doc',$this->doc);
		$this->list[$line_id]['comm'] = $value;
	}
	
	if(!$this->cost_id) {
		$pc = $this->initPriceCalc();
	
		$ret_data['price_name']	= $pc->getCurrentPriceName();
		$ret_data['nbp_info'] = $pc->getNextPriceInfo();
		$ret_data['npp_info'] = $pc->getNextPeriodicPriceInfo();
		$ret_data['auto_price'] = 1;
		$ret_data['base_sum'] = $this->doc_base_sum;
	}
	else	$ret_data['auto_price'] = 0;
	
	if(!isset($ret_data['update_list']))
		$ret_data['update_line'] = $this->list[$line_id];
	$ret_data['sum'] = $this->doc_obj->getDocData('sum');
	return json_encode($ret_data, JSON_UNESCAPED_UNICODE);
}

function SerialNum($action, $line_id, $data)
{
	global $db;
	$doc_data=$this->doc_obj->getDocDataA();
	if($action=='l')	// List
	{
		if($doc_data['type']==1)	$column='prix_list_pos';
		else if($doc_data['type']==2)	$column='rasx_list_pos';
		else				throw new Exception("В данном документе серийные номера не используются!");
		$res = $db->query("SELECT `doc_list_sn`.`id`, `doc_list_sn`.`num` AS `sn` FROM `doc_list_sn` WHERE `$column`='$line_id'");
		$ret='';
		while($nxt = $res->fetch_assoc()) {
			if($ret)	$ret.=', ';
			$ret .= json_encode($nxt, JSON_UNESCAPED_UNICODE);
			//$ret.="{ id: '$nxt[0]', sn: '$nxt[1]' }";
		}
		return "{response: 'sn_list', list: [ $ret ]}";
	}
	else if($action=='d')	// delete
	{
		if($doc_data['type']==1)	$db->query("DELETE FROM `doc_list_sn` WHERE `id`='$line_id' AND  `rasx_list_pos` IS NULL");
		else if($doc_data['type']==2)	$db->query("UPDATE `doc_list_sn` SET `rasx_list_pos`=NULL  WHERE `id`='$line_id'");
		else				throw new Exception("В данном документе серийные номера не используются!");
		if($db->affected_rows)		return "{response: 'deleted' }";
		else				return "{response: 'not_deleted', message: 'Номер уже удалён, или используется в реализации' }";
	}
}

	function reOrder($by='name') {
		global $db;
		if($by!=='name' && $by!=='price' && $by!=='vc'&& $by!=='place')
			$by='name';
		if($by=='place')
			$by='doc_base_cnt`.`mesto';
		else if($by=='price')
			$by='doc_list_pos`.`cost';
		$db->startTransaction();
		$res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_list_pos`.`gtd`, `doc_list_pos`.`comm`, `doc_list_pos`.`cost`, `doc_list_pos`.`page`, `doc_base`.`name`, `doc_base`.`vc`, `doc_base_cnt`.`mesto`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `$by`");
		$db->query("DELETE FROM `doc_list_pos` WHERE `doc`='{$this->doc}'");
		while($nxt = $res->fetch_row())
		{
			$db->query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `gtd`, `comm`, `cost`, `page`)
				VALUES ('{$this->doc}', '$nxt[0]', '$nxt[1]', '$nxt[2]', '$nxt[3]', '$nxt[4]', '$nxt[5]')");
		}
		$db->commit();
		doc_log("UPDATE","ORDER poslist BY $by",'doc',$this->doc);
	}

	// Сбросить вручную заданные цены документа к выбранным ценам
	public function resetPrices() {
		global $db;
		$updated = false;
		if(!$this->editable)
			return false;
		if(!$this->cost_id) {
			$pc = $this->initPriceCalc();	// И loadList заодно
			$updated = $this->recalcPrices();
		}
		else {
			$db->startTransaction();
			$this->loadList();
			$pc = PriceCalc::getInstance();
			foreach ($this->list as $line_id=>$line) {
				$need_price = $pc->getPosSelectedPriceValue($line['pos_id'], $this->cost_id, $line);
				if($line['cost'] != $need_price ) {
					$updated = true;
					$this->list[$line_id]['cost'] = $need_price;					
					$db->update('doc_list_pos', $line_id, 'cost', $need_price);
				}
			}
			$db->commit();
		}
		$this->updateDocSum();
		return $updated;
	}
	
	/// Перерасчёт суммы документа и обновление её в баз, при необходимости
	/// @return true, если обновление выполнено, false если обновление не требовалось
	public function updateDocSum() {
		$doc_sum = 0;
		$this->loadList();
		foreach ($this->list as $line_id=>$line) {
			$doc_sum += $line['cost'] * $line['cnt'];
		}
		if(round($this->doc_obj->getDocData('sum'), 2) != round($doc_sum, 2)) {
			$this->doc_obj->setDocData('sum', $doc_sum);
			return true;
		}
		return false;
	}
	
/// Получить список номенклатуры заданной группы
	function GetSkladList($group) {
		global $db;
		settype($group, 'int');
		$sql = "SELECT `doc_base`.`id`, `doc_base`.`vc`, `doc_base`.`group`, `doc_base`.`name`, `doc_base`.`proizv` AS `vendor`,
			`doc_base`.`likvid` AS `liquidity`, `doc_base`.`cost` AS `base_price`, `doc_base`.`cost_date` AS `price_date`, `doc_base_dop`.`analog`,
			`doc_base_dop`.`type`, `doc_base_dop`.`d_int`,	`doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base`.`mass`,
			`doc_base_cnt`.`mesto` AS `place`, `doc_base_cnt`.`cnt`,
			(SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `allcnt`, `doc_base`.`bulkcnt`
			FROM `doc_base`
			LEFT JOIN `doc_base_cnt`  ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
			WHERE `doc_base`.`group`='$group'
			ORDER BY " . $this->getOrder();
		$res = $db->query($sql);
		return $this->FormatResult($res);
	}

/// Получить список номенклатуры, содержащей в названии заданную строку
	function SearchSkladList($s) {
		global $db;
		$s_sql = $db->real_escape_string($s);
		$s_json = json_encode($s, JSON_UNESCAPED_UNICODE);
                $found_ids = '0';   // Для NOT IN
               
                $result = array();
		$ret = '';
		$sql = "SELECT SQL_CALC_FOUND_ROWS `doc_base`.`id`, `doc_base`.`vc`, `doc_base`.`group`, `doc_base`.`name`, `doc_base`.`proizv` AS `vendor`,
                    `doc_base`.`likvid` AS `liquidity`, `doc_base`.`cost` AS `base_price`, `doc_base`.`cost_date` AS `price_date`,
                    `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`,	`doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base`.`mass`,
                    `doc_base_cnt`.`mesto` AS `place`, `doc_base_cnt`.`cnt`, `doc_base`.`analog_group`,
                    (SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `allcnt`,
                    `doc_base`.`bulkcnt`
                    FROM `doc_base`
                    LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
                    LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id` ";

                $sqla = $sql . "WHERE `doc_base`.`name` = '$s_sql' OR `doc_base`.`vc` = '$s_sql' ORDER BY " . $this->getOrder();
                $res = $db->query($sqla);
                if ($cnt = $res->num_rows) {
                    $result[] = array('id'=>'header', 'name'=>"Поиск совпадений с $s_json - $cnt наименований найдено");
                    $groups_analog_list = '';
                    while($line = $res->fetch_assoc()) {
                        $result[] = $this->formatLine($line);
                        $found_ids.=','.$line['id'];
                        if($line['analog_group']) {
                            if($groups_analog_list) {
                                $groups_analog_list.=',';
                            }
                            $groups_analog_list.="'".$db->real_escape_string($line['analog_group'])."'";
                        }
                    }
                    if($groups_analog_list) {
                        $sqla = $sql . "WHERE `doc_base`.`id` NOT IN ($found_ids) AND `doc_base`.`analog_group` IN ($groups_analog_list) ORDER BY " . $this->getOrder();
                        $res = $db->query($sqla);
                        
                        if ($cnt = $res->num_rows) {
                            $result[] = array('id'=>'header', 'name'=>"Поиск аналогов $s_json - $cnt наименований найдено");
                            $groups_analog_list = '';
                            while($line = $res->fetch_assoc()) {
                                $result[] = $this->formatLine($line);
                                $found_ids.=','.$line['id'];
                            }
                        }
                    }
                }
                
                $sqla = $sql . "WHERE (`doc_base`.`name` LIKE '$s_sql%' OR `doc_base`.`vc` LIKE '$s_sql%') AND `doc_base`.`id` NOT IN ($found_ids) "
                    . "ORDER BY " . $this->getOrder() . " LIMIT 50";
                $res = $db->query($sqla);
                if ($cnt = $res->num_rows) {
                    $rows_res = $db->query("SELECT FOUND_ROWS()");
                    list($found_cnt) = $rows_res->fetch_row();
                    $result[] = array('id'=>'header', 'name'=>"Поиск по названию, начинающемуся на $s_json - показано $cnt из $found_cnt");
                    $groups_analog_list = '';
                    while($line = $res->fetch_assoc()) {
                        $result[] = $this->formatLine($line);
                        $found_ids.=','.$line['id'];
                        if($line['analog_group']) {
                            if($groups_analog_list) {
                                $groups_analog_list.=',';
                            }
                            $groups_analog_list.="'".$db->real_escape_string($line['analog_group'])."'";
                        }
                    }
                    if($groups_analog_list) {
                        $sqla = $sql . "WHERE `doc_base`.`id` NOT IN ($found_ids) AND `doc_base`.`analog_group` IN ($groups_analog_list) "
                                . "ORDER BY " . $this->getOrder() ." LIMIT 50";
                        $res = $db->query($sqla);
                        
                        if ($cnt = $res->num_rows) {
                            $rows_res = $db->query("SELECT FOUND_ROWS()");
                            list($found_cnt) = $rows_res->fetch_row();
                            $result[] = array('id'=>'header', 'name'=>"Поиск аналогов предыдущего блока - показано $cnt из $found_cnt");
                            $groups_analog_list = '';
                            while($line = $res->fetch_assoc()) {
                                $result[] = $this->formatLine($line);
                                $found_ids.=','.$line['id'];
                            }
                        }
                    }
                }
                
                $sqla = $sql . "WHERE (`doc_base`.`name` LIKE '%$s_sql%' OR `doc_base`.`vc` LIKE '%$s_sql%') AND `doc_base`.`id` NOT IN ($found_ids) "
                    . "ORDER BY " . $this->getOrder() . " LIMIT 50";
                $res = $db->query($sqla);
                if ($cnt = $res->num_rows) {
                    $rows_res = $db->query("SELECT FOUND_ROWS()");
                    list($found_cnt) = $rows_res->fetch_row();
                    $result[] = array('id'=>'header', 'name'=>"Поиск по вхождению $s_json - показано $cnt из $found_cnt");
                    while($line = $res->fetch_assoc()) {
                        $result[] = $this->formatLine($line);
                    }
                }
                
                return json_encode($result, JSON_UNESCAPED_UNICODE);
                
//		$sqla = $sql . "WHERE `doc_base`.`name` LIKE '$s_sql%' OR `doc_base`.`vc` LIKE '$s_sql%' ORDER BY " . $this->getOrder() . " LIMIT 200";
//		$res = $db->query($sqla);
//		if ($cnt = $res->num_rows) {
//			if ($ret != '')
//				$ret.=', ';
//			$ret.="{id: 'header', name: 'Поиск по названию, начинающемуся на $s_json - $cnt наименований найдено'}";
//			$ret = $this->FormatResult($res, $ret);
//		}
//		$sqla = $sql . "WHERE (`doc_base`.`name` LIKE '%$s_sql%' OR `doc_base`.`vc` LIKE '%$s_sql%') AND `doc_base`.`name` NOT LIKE '$s_sql%'
//			AND `doc_base`.`vc` NOT LIKE '$s_sql%' ORDER BY " . $this->getOrder() . " LIMIT 100";
//		$res = $db->query($sqla);
//		if ($cnt = $res->num_rows) {
//			if ($ret != '')
//				$ret.=', ';
//			$ret.="{id: 'header', name: 'Поиск по названию, содержащему $s_json - $cnt наименований найдено'}";
//			$ret = $this->FormatResult($res, $ret);
//		}
//		$sqla = $sql . "WHERE `doc_base_dop`.`analog` LIKE '%$s_sql%' AND `doc_base`.`name` NOT LIKE '%$s_sql%' AND `doc_base`.`vc` NOT LIKE '%$s_sql%'
//			ORDER BY " . $this->getOrder() . " LIMIT 100";
//		$res = $db->query($sqla);
//		if ($cnt = $res->num_rows) {
//			if ($ret != '')
//				$ret.=', ';
//			$ret.="{id: 'header', name: 'Поиск по аналогу($s_json) - $cnt наименований найдено'}";
//			$ret = $this->FormatResult($res, $ret);
//		}
//		return $ret;
	}

    /// Форматирует данные строки списка наименований перед последующей конвертацией в json
    /// @param $line Массив с ниформацией о наименовании
    protected function formatLine($line) {
        $dcc = strtotime($line['price_date']);
        if ($dcc > (time() - 60 * 60 * 24 * 30 * 3)) {
            $line['price_cat'] = "c1";
        } elseif ($dcc > (time() - 60 * 60 * 24 * 30 * 6)) {
            $line['price_cat'] = "c2";
        } elseif ($dcc > (time() - 60 * 60 * 24 * 30 * 9)) {
            $line['price_cat'] = "c3";
        } elseif ($dcc > (time() - 60 * 60 * 24 * 30 * 12)) {
            $line['price_cat'] = "c4";
        }
        if ($this->show_rto) {
            $line['reserve'] = DocRezerv($line['id'], $this->doc);
            $line['offer'] = DocPodZakaz($line['id'], $this->doc);
            $line['transit'] = DocVPuti($line['id'], $this->doc);
        }
        $pc = PriceCalc::getInstance();
        if ($this->cost_id) {
            $line['price'] = $pc->getPosSelectedPriceValue($line['id'], $this->cost_id, $line);
        } else {
            $line['price'] = $pc->getPosDefaultPriceValue($line['id']);
        }
        return $line;
    }

        protected function FormatResult($res, $ret = '') {
        if ($res->num_rows) {
            while ($nxt = $res->fetch_assoc()) {
                $nxt = $this->formatLine($nxt);

                if ($ret != '') {
                    $ret.=', ';
                }

                $ret .= json_encode($nxt, JSON_UNESCAPED_UNICODE);
            }
        }
        return $ret;
    }

}
