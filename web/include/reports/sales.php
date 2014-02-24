<?php

//	MultiMag v0.1 - Complex sales system
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


/// Отчёт по движению товара
class Report_Sales extends BaseGSReport {

	var $sklad = 0; // ID склада
	var $w_docs = 0; // Отображать документы
	var $div_dt = 0; // Разделить приходы и расходы

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
		if ($short)	return "По движению товара";
		else		return "Отчёт по движению товара";
	}

	function Form() {
		global $tmpl, $db;
		$d_t = date("Y-m-d");
		$d_f = date("Y-m-d", time() - 60 * 60 * 24 * 31);
		$tmpl->addContent("<h1>" . $this->getName() . "</h1>
		<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='sales'>
		<fieldset><legend>Дата</legend>
		С:<input type=text id='dt_f' name='dt_f' value='$d_f'><br>
		По:<input type=text id='dt_t' name='dt_t' value='$d_t'>
		</fieldset>
		Склад:<br>
		<select name='sklad'>");
		$res = $db->query("SELECT `id`, `name` FROM `doc_sklady`");
		while ($nxt = $res->fetch_row())
			$tmpl->addContent("<option value='$nxt[0]'>".html_out($nxt[1])."</option>");
		$tmpl->addContent("</select><br>
		<label><input type='checkbox' name='w_docs' value='1' checked>С документами</label><br>
		<label><input type='checkbox' name='div_dt' value='1' checked>Разделить по типам документов</label><br>
		<br>
		<fieldset><legend>Отчёт по</legend>
		<select name='sel_type' id='sel_type'>
		<option value='all'>Всей номенклатуре</option>
		<option value='group'>Выбранной группе</option>
		<option value='pos'>Выбранному наименованию</option>
		<option value='agent'>Выбранному поставщику</option>
		</select>
		");
		$this->GroupSelBlock();
		$tmpl->addContent("
		<div id='pos_sel' style='display: none;'>
		<input type='hidden' name='pos_id' id='pos_id' value=''>
		<input type='text' id='posit' style='width: 400px;' value=''>
		</div>
		<div id='agent_sel' style='display: none;'>
		<input type='hidden' name='agent' id='agent_id' value=''>
		<input type='text' id='ag' name='agent_name' style='width: 400px;' value=''><br>
		</div>
		
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
			var sb=document.getElementById('sb');
			var ps=document.getElementById('pos_sel');
			var as=document.getElementById('agent_sel');
			sb.style.display='none';
			ps.style.display='none';
			as.style.display='none';
			
			switch(this.value)
			{
				case 'group':	sb.style.display='block';	break;
				case 'pos':	ps.style.display='block';	break;
				case 'agent':	as.style.display='block';	break;
			}
		}
		
		
		addEventListener('load',dtinit,false)	
		document.getElementById('sel_type').addEventListener('change',selectChange,false)	
		
		$(\"#posit\").autocomplete(\"/docs.php\", {
			delay:300,
			minChars:1,
			matchSubset:1,
			autoFill:false,
			selectFirst:true,
			matchContains:1,
			cacheLength:10,
			maxItemsToShow:15, 	
			formatItem:tovliFormat, 
			onItemSelect:tovselectItem,
			extraParams:{'l':'sklad','mode':'srv','opt':'ac'}
		});
		
		function tovliFormat (row, i, num) {
			var result = row[0] + \"<em class='qnt'>\" +
			row[2] + \"</em> \";
			return result;
		}
		
		function tovselectItem(li) {
			if( li == null ) var sValue = \"Ничего не выбрано!\";
			if( !!li.extra ) var sValue = li.extra[0];
			else var sValue = li.selectValue;
			document.getElementById('pos_id').value=sValue;
			
		}
		
		$(\"#ag\").autocomplete(\"/docs.php\", {
			delay:300,
			minChars:1,
			matchSubset:1,
			autoFill:false,
			selectFirst:true,
			matchContains:1,
			cacheLength:10,
			maxItemsToShow:15, 	 
			formatItem:agliFormat,
			onItemSelect:agselectItem,
			extraParams:{'l':'agent','mode':'srv','opt':'ac'}
			});
		
		function agliFormat (row, i, num) {
			var result = row[0] + \"<em class='qnt'>тел. \" +
			row[2] + \"</em> \";
			return result;
		}
		function agselectItem(li) {
			if( li == null ) var sValue = \"Ничего не выбрано!\";
			if( !!li.extra ) var sValue = li.extra[0];
			else var sValue = li.selectValue;
			document.getElementById('agent_id').value=sValue;
		}
		
		</script>
		");
	}

	function dividedOutPos($pos_id, $vc, $name, $dt_f, $dt_t, $base_cost) {
		global $db;
		$start_cnt = getStoreCntOnDate($pos_id, $this->sklad, $dt_f, 1);

		if ($this->w_docs) {
			$this->tableSpannedRow(array($this->col_cnt), array("$vc $name ($pos_id)"));
			$this->tableRow(array('', 'На начало периода', '', $start_cnt, '', ''));
			$this->tableAltStyle();
			$this->tableSpannedRow(array($this->col_cnt), array('Приходы'));
			$this->tableAltStyle(false);
		}
		$res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`agent`, `doc_agent`.`name` AS `agent_name`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `ns`.`value` AS `na_sklad`, `doc_sklady`.`name` AS `sklad_name`, `doc_types`.`name` AS `doc_name`, `doc_list`.`date`, CONCAT(`doc_list`.`altnum`, `doc_list`.`subtype`) AS `snum`
		FROM `doc_list_pos`
		INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
		INNER JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		LEFT JOIN `doc_dopdata` AS `ns` ON `ns`.`doc`=`doc_list_pos`.`doc` AND `ns`.`param`='na_sklad'
		LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
		LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
		WHERE `doc_list_pos`.`tovar`='$pos_id' AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<'$dt_t' AND (
		(`doc_list`.`type`='1' AND `doc_list`.`sklad`='{$this->sklad}') OR
		(`doc_list`.`type`='8' AND `ns`.`value`='{$this->sklad}') OR
		(`doc_list`.`type`='17' AND `doc_list`.`sklad`='{$this->sklad}' AND `doc_list_pos`.`page`='0') ) AND `doc_list`.`ok`>0
		ORDER BY `doc_list`.`date`");
		$sum_cnt = $start_cnt;
		$prix_cnt = $prix_sum = 0;
		while ($nxt = $res->fetch_assoc()) {
			$from = 'Сборка';
			if ($nxt['type'] == 1)
				$from = $nxt['agent_name'];
			else if ($nxt['type'] == 8)
				$from = $nxt['sklad_name'];
			$date = date("Y-m-d H:i:s", $nxt['date']);
			$sumline = $nxt['cnt'] * $nxt['cost'];
			if ($this->w_docs)
				$this->tableRow(array($date, "{$nxt['doc_name']} {$nxt['snum']} ({$nxt['id']})", $from, $nxt['cnt'], $nxt['cost'], $sumline));
			$prix_cnt+=$nxt['cnt'];
			$prix_sum+=$sumline;
		}
		if ($this->w_docs)
			$this->tableRow(array('', 'Всего приход:', '', $prix_cnt, '', $prix_sum));
		$r_cnt = $r_sum = 0;

		if ($this->w_docs) {
			$this->tableAltStyle();
			$this->tableSpannedRow(array($this->col_cnt), array('Реализации'));
			$this->tableAltStyle(false);
		}
		$res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`agent`, `doc_agent`.`name` AS `agent_name`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `ns`.`value` AS `na_sklad`, `doc_sklady`.`name` AS `sklad_name`, `doc_types`.`name` AS `doc_name`, `doc_list`.`date`, CONCAT(`doc_list`.`altnum`, `doc_list`.`subtype`) AS `snum`
		FROM `doc_list_pos`
		INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
		INNER JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		LEFT JOIN `doc_dopdata` AS `ns` ON `ns`.`doc`=`doc_list_pos`.`doc` AND `ns`.`param`='na_sklad'
		LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
		LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`ns`.`value`
		WHERE `doc_list_pos`.`tovar`='$pos_id' AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<'$dt_t' AND `doc_list`.`sklad`='{$this->sklad}' AND
		(`doc_list`.`type`='2' OR `doc_list`.`type`='20') AND `doc_list`.`ok`>0
		ORDER BY `doc_list`.`date`");
		$realiz_cnt = $sum = 0;
		while ($nxt = $res->fetch_assoc()) {
			if ($this->w_docs) {
				$from = 'Сборка';
				if ($nxt['type'] == 2)
					$from = $nxt['agent_name'];
				else if ($nxt['type'] == 8)
					$from = $nxt['sklad_name'];
				$date = date("Y-m-d H:i:s", $nxt['date']);
				$sumline = $nxt['cnt'] * $nxt['cost'];

				$this->tableRow(array($date, "{$nxt['doc_name']} {$nxt['snum']} ({$nxt['id']})", $from, $nxt['cnt'], $nxt['cost'], $sumline));
				$sum+=$sumline;
			}
			$realiz_cnt+=$nxt['cnt'];
		}
		if ($this->w_docs)
			$this->tableRow(array('', 'По реализациям:', '', $realiz_cnt, '', $sum));
		$r_cnt+=$realiz_cnt;
		$r_sum+=$sum;
		if ($this->w_docs) {
			$this->tableAltStyle();
			$this->tableSpannedRow(array($this->col_cnt), array('Перемещения'));
			$this->tableAltStyle(false);
		}
		$res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`agent`, `doc_agent`.`name` AS `agent_name`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `ns`.`value` AS `na_sklad`, `doc_sklady`.`name` AS `sklad_name`, `doc_types`.`name` AS `doc_name`, `doc_list`.`date`, CONCAT(`doc_list`.`altnum`, `doc_list`.`subtype`) AS `snum`
		FROM `doc_list_pos`
		INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
		INNER JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		LEFT JOIN `doc_dopdata` AS `ns` ON `ns`.`doc`=`doc_list_pos`.`doc` AND `ns`.`param`='na_sklad'
		LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
		LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`ns`.`value`
		WHERE `doc_list_pos`.`tovar`='$pos_id' AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<'$dt_t' AND `doc_list`.`sklad`='{$this->sklad}' AND `doc_list`.`type`='8' AND `doc_list`.`ok`>0
		ORDER BY `doc_list`.`date`");
		$perem_cnt = $sum = 0;
		while ($nxt = $res->fetch_assoc()) {
			if ($this->w_docs) {
				$from = 'Сборка';
				if ($nxt['type'] == 2)
					$from = $nxt['agent_name'];
				else if ($nxt['type'] == 8)
					$from = $nxt['sklad_name'];
				$date = date("Y-m-d H:i:s", $nxt['date']);
				$sumline = $nxt['cnt'] * $nxt['cost'];
				$this->tableRow(array($date, "{$nxt['doc_name']} {$nxt['snum']} ({$nxt['id']})", $from, $nxt['cnt'], $nxt['cost'], $sumline));
				$sum+=$sumline;
			}
			$perem_cnt+=$nxt['cnt'];
		}
		if ($this->w_docs)
			$this->tableRow(array('', 'По перемещениям:', '', $perem_cnt, '', $sum));
		$r_cnt+=$perem_cnt;
		$r_sum+=$sum;
		if ($this->w_docs) {
			$this->tableAltStyle();
			$this->tableSpannedRow(array($this->col_cnt), array('Сборки'));
			$this->tableAltStyle(false);
		}
		$res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`agent`, `doc_agent`.`name` AS `agent_name`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `ns`.`value` AS `na_sklad`, `doc_sklady`.`name` AS `sklad_name`, `doc_types`.`name` AS `doc_name`, `doc_list`.`date`, CONCAT(`doc_list`.`altnum`, `doc_list`.`subtype`) AS `snum`
		FROM `doc_list_pos`
		INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
		INNER JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		LEFT JOIN `doc_dopdata` AS `ns` ON `ns`.`doc`=`doc_list_pos`.`doc` AND `ns`.`param`='na_sklad'
		LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
		LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`ns`.`value`
		WHERE `doc_list_pos`.`tovar`='$pos_id' AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<'$dt_t' AND `doc_list`.`sklad`='{$this->sklad}' AND (`doc_list`.`type`='17' AND `doc_list_pos`.`page`!='0') AND `doc_list`.`ok`>0
		ORDER BY `doc_list`.`date`");
		$sbor_cnt = $sum = 0;
		while ($nxt = $res->fetch_assoc()) {
			if ($this->w_docs) {
				$from = 'Сборка';
				if ($nxt['type'] == 2)
					$from = $nxt['agent_name'];
				else if ($nxt['type'] == 8)
					$from = $nxt['sklad_name'];
				$date = date("Y-m-d H:i:s", $nxt['date']);
				$sumline = $nxt['cnt'] * $nxt['cost'];
				$this->tableRow(array($date, "{$nxt['doc_name']} {$nxt['snum']} ({$nxt['id']})", $from, $nxt['cnt'], $nxt['cost'], $sumline));
				$sum+=$sumline;
			}
			$sbor_cnt+=$nxt['cnt'];
		}
		$r_cnt+=$sbor_cnt;
		if ($this->w_docs) {
			$this->tableAltStyle();
			$this->tableSpannedRow(array($this->col_cnt), array(''));
			$this->tableAltStyle(false);
			$this->tableRow(array('', 'По сборкам:', '', $sbor_cnt, '', $sum));

			$r_sum+=$sum;
			$this->tableRow(array('', 'Всего расход:', '', $r_cnt, '', $r_sum));
			$end_cnt = $start_cnt + $prix_cnt - $r_cnt;
			$this->tableRow(array('', 'На конец периода:', '', $end_cnt, '', ''));
		} else {
			$end_cnt = $start_cnt + $prix_cnt - $r_cnt;
			if($prix_cnt || $realiz_cnt || $perem_cnt || $sbor_cnt)
				$this->tableRow(array($pos_id, $vc, $name, $base_cost, $start_cnt, $prix_cnt, $realiz_cnt, $perem_cnt, $sbor_cnt, $end_cnt));
		}
	}

	function serialOutPos($pos_id, $vc, $name, $dt_f, $dt_t) {
		global $tmpl, $db;
		$cur_cnt = getStoreCntOnDate($pos_id, $this->sklad, $dt_f, 1);

		if ($this->w_docs) {
			$this->tableAltStyle();
			$this->tableSpannedRow(array($this->col_cnt), array("$vc $name ($pos_id)"));
			$this->tableAltStyle(false);
			$this->tableSpannedRow(array($this->col_cnt - 1, 1), array('На начало периода:', $cur_cnt));
		}
		$res = $db->query("SELECT `doc_list`.`id` AS `doc_id`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list_pos`.`page`,
		`doc_agent`.`name` AS `agent_name`, `doc_list_pos`.`cnt`, `ds`.`name` AS `sklad_name`, `nsn`.`name` AS `nasklad_name`, `doc_types`.`name` AS `doc_name`, `doc_list`.`date`, CONCAT(`doc_list`.`altnum`, `doc_list`.`subtype`) AS `snum`
		FROM `doc_list_pos`
		INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
		INNER JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
		LEFT JOIN `doc_dopdata` AS `ns` ON `ns`.`doc`=`doc_list_pos`.`doc` AND `ns`.`param`='na_sklad'
		LEFT JOIN `doc_sklady` AS `ds` ON `ds`.`id`=`doc_list`.`sklad`
		LEFT JOIN `doc_sklady` AS `nsn` ON `nsn`.`id`=`ns`.`value`
		WHERE `doc_list_pos`.`tovar`='$pos_id' AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<'$dt_t' AND (
		(`doc_list`.`type`='1' AND `doc_list`.`sklad`='{$this->sklad}') OR
		((`doc_list`.`type`='2' OR `doc_list`.`type`='20') AND `doc_list`.`sklad`='{$this->sklad}') OR
		(`doc_list`.`type`='8' AND (`doc_list`.`sklad`='{$this->sklad}' OR `ns`.`value`='{$this->sklad}')) OR
		(`doc_list`.`type`='17' AND `doc_list`.`sklad`='{$this->sklad}') ) AND `doc_list`.`ok`>0
		ORDER BY `doc_list`.`date`");
		$sp = $sr = 0;
		while ($nxt = $res->fetch_assoc()) {
			$p = $r = '';
			$link = '';
			switch ($nxt['type']) {
				case 1: $p = $nxt['cnt'];
					$link = 'От ' . $nxt['agent_name'];
					break;
				case 2: 
				case 20:$r = $nxt['cnt'];
					$link = 'Для ' . $nxt['agent_name'];
					break;
				case 8: {
						if ($nxt['sklad'] == $this->sklad) {
							$r = $nxt['cnt'];
							$link = 'На ' . $nxt['nasklad_name'];
						} else {
							$p = $nxt['cnt'];
							$link = 'С ' . $nxt['sklad_name'];
						}
					}break;
				case 17: {
						if ($nxt['page'] == 0)
							$p = $nxt['cnt'];
						else
							$r = $nxt['cnt'];
					}
					break;
				default:$p = $r = 'fff-' . $nxt['type'];
			}
			$cur_cnt+=$p - $r;
			$date = date("Y-m-d H:i:s", $nxt['date']);
			$this->tableRow(array($date, "{$nxt['doc_name']} {$nxt['snum']} / {$nxt['doc_id']}", $link, $p, $r, $cur_cnt));
			$sp+=$p;
			$sr+=$r;
		}
		$this->tableSpannedRow(array($this->col_cnt - 3, 1, 1, 1), array('На конец периода:', $sp, $sr, $cur_cnt));
	}

	function outPos($pos_id, $vc, $name, $dt_f, $dt_t, $base_cost) {
		if ($this->div_dt || !$this->w_docs)
			$this->dividedOutPos($pos_id, $vc, $name, $dt_f, $dt_t, $base_cost);
		else	$this->serialOutPos($pos_id, $vc, $name, $dt_f, $dt_t);
	}

	function Make($engine) {
		global $CONFIG, $db;
		$this->loadEngine($engine);

		$dt_f = strtotime(rcvdate('dt_f'));
		$dt_t = strtotime(rcvdate('dt_t') . " 23:59:59");
		$g = request('g', array());
		$sel_type = request('sel_type');
		$this->sklad = rcvint('sklad');
		$this->w_docs = rcvint('w_docs');
		$this->div_dt = rcvint('div_dt');
		$agent_id = rcvint('agent');
		
		if(!$this->sklad ) $this->sklad = 1;

		$print_df = date('Y-m-d', $dt_f);
		$print_dt = date('Y-m-d', $dt_t);

		$res = $db->query("SELECT `id`, `name` FROM `doc_sklady` WHERE `id`='{$this->sklad}'");
		if (!$res->num_rows)	throw new Exception("Склад не найден");
		list($sklad_id, $sklad_name) = $res->fetch_row();

		$this->header($this->getName() . " с $print_df по $print_dt, склад: $sklad_name($sklad_id)");

		if (!$this->w_docs) {
			$widths = array(5, 8, 38, 7, 7, 7, 7, 7, 7, 7);
			$headers = array('ID', 'Код', 'Наименование', 'Базов. цена', 'Нач. кол-во', 'Приход', 'Реализ.', 'Перем.', 'Сборка', 'Итог');
		} else if ($this->div_dt) {
			$widths = array(15, 25, 40, 7, 7, 7);
			$headers = array('Дата', 'Документ', 'Источник', 'Кол-во', 'Цена', 'Сумма');
		} else {
			$widths = array(15, 21, 40, 8, 8, 8);
			$headers = array('Дата', 'Документ', '', 'Приход', 'Расход', 'Кол-во');
		}
		$this->col_cnt = count($widths);
		$this->tableBegin($widths);
		$this->tableHeader($headers);
		switch (@$CONFIG['doc']['sklad_default_order']) {
			case 'vc': $order = '`doc_base`.`vc`';
				break;
			case 'cost': $order = '`doc_base`.`cost`';
				break;
			default: $order = '`doc_base`.`name`';
		}
		if ($sel_type == 'pos') {
			$pos_id = rcvint('pos_id');
			$res = $db->query("SELECT `vc`, `name`, `doc_base`.`cost`  FROM `doc_base` WHERE `id`='$pos_id'");
			if ($res->num_rows == 0)	throw new Exception("Товар не найден!");
			$tov_data = $res->fetch_row();
			$this->outPos($pos_id, $tov_data[0], $tov_data[1], $dt_f, $dt_t, $tov_data[2]);
		}
		else if ($sel_type == 'all') {
			$res = $db->query("SELECT `id`, `vc`, CONCAT(`doc_base`.`name`, ' - ', `doc_base`.`proizv`) AS `name`, `doc_base`.`cost` FROM `doc_base` ORDER BY $order");
			while ($nxt = $res->fetch_row())
				$this->outPos($nxt[0], $nxt[1], $nxt[2], $dt_f, $dt_t, $nxt[3]);
		} else if ($sel_type == 'group') {
			$res_group = $db->query("SELECT `id`, `name` FROM `doc_group` ORDER BY `id`");
			while ($group_line = $res_group->fetch_assoc()) {
				if (is_array($g))
					if (!in_array($group_line['id'], $g))
						continue;

				$this->tableAltStyle();
				$this->tableSpannedRow(array($this->col_cnt), array($group_line['id'] . '. ' . $group_line['name']));
				$this->tableAltStyle(false);
				$res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`vc`, CONCAT(`doc_base`.`name`, ' - ', `doc_base`.`proizv`) AS `name`, `doc_base`.`cost`
				FROM `doc_base`
				WHERE `doc_base`.`group`='{$group_line['id']}'
				ORDER BY $order");
				while ($nxt = $res->fetch_row())
					$this->outPos($nxt[0], $nxt[1], $nxt[2], $dt_f, $dt_t, $nxt[3]);
			}
		} else if ($sel_type == 'agent') {
			$res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`vc`, CONCAT(`doc_base`.`name`, ' - ', `doc_base`.`proizv`) AS `name`, `doc_base`.`cost`
			FROM `doc_list_pos`
			INNER JOIN `doc_base` ON  `doc_base`.`id`=`doc_list_pos`.`tovar`
			INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`agent`='$agent_id' AND `doc_list`.`type`='1'
			GROUP BY `doc_list_pos`.`tovar` ORDER BY $order ");
			while ($nxt = $res->fetch_row())
				$this->outPos($nxt[0], $nxt[1], $nxt[2], $dt_f, $dt_t, $nxt[3]);
		}
		$this->tableEnd();
		$this->output();
		exit(0);
	}
}
?>