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
/// Закупочная стоимость товаров
/// Алгоритм расчёта основан на алгоритме вычисления актуальной цены поступления
class Report_Incost extends BaseGSReport {

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
		if ($short)	return "По себестоимости проданных товаров";
		else		return "Отчёт по себестоимости проданных товаров";
	}

	function Form() {
		global $tmpl;
		$d_t = date("Y-m-d");
		$d_f = date("Y-m-d", time() - 60 * 60 * 24 * 31);
		$tmpl->addContent("<h1>" . $this->getName() . "</h1>
		<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='incost'>
		<fieldset><legend>Дата</legend>
		С:<input type=text id='dt_f' name='dt_f' value='$d_f'><br>
		По:<input type=text id='dt_t' name='dt_t' value='$d_t'>
		</fieldset>
		<br>
                <select name='grp'>
                <option selected value=''>не группитовать</option>
                <option value='d'>Группировать по дням</option>
                <option value='m'>Группировать по месяцам</option>
                </select>
		Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
		<button type='submit'>Сформировать отчёт</button>
		</form>		
		</script>
		");
	}

	function Make($engine) {
		global $db;
		$this->loadEngine($engine);

		$dt_f = strtotime(rcvdate('dt_f'));
		$dt_t = strtotime(rcvdate('dt_t'));
                
                $grp = request('grp');

		$print_df = date('Y-m-d', $dt_f);
		$print_dt = date('Y-m-d', $dt_t);

		$this->header($this->getName() . " с $print_df по $print_dt");

		$widths = array(15, 75, 10);
		$headers = array('Дата', 'Объект', 'Сумма');

		$this->col_cnt = count($widths);
		$this->tableBegin($widths);
		$this->tableHeader($headers);
                
		$res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`altnum`"
                        . "FROM `doc_list` "
                        . "WHERE `doc_list`.`type`=2 AND `doc_list`.`ok`>0 AND `doc_list`.`date`>={$dt_f} AND `doc_list`.`date`<={$dt_t} "
                        . "ORDER BY `doc_list`.`date`");
                $sum = 0;
                while($doc_info = $res->fetch_assoc()) {
                        switch ($grp) {
                                case 'd':
                                        break;
                                default:
                                        $line_sum = 0;
                        }
                        
                        $l_res = $db->query("SELECT `tovar` FROM `doc_list_pos` WHERE `doc`={$doc_info['id']}");
                        while($pos_info = $l_res->fetch_assoc()) {
                                $line_sum += getInCost($pos_info['tovar'], $pos_info['date'], $serv_mode=0);
                        }
                        $date_p = date("Y-m-d", $doc_info['date']);
                        $doc_sum_p = number_format($line_sum, 2, '.', ' ');
                        $this->tableRow(array($date_p, "Реализация {$doc_info['altnum']} ({$doc_info['id']})", $doc_sum_p));
                        
                        
                        $sum += $line_sum;                        
                }
                
		//$this->tableRow(array($nxt[0], $nxt[1], $nxt[2], $nxt[3], $nxt[5], "$nxt[4] р.", "$profitability %", $nxt[6] . ' %'));
		$sum_p = number_format($sum, 2, '.', ' ');
		$this->tableRow(array("", "Всего", "$sum_p р."));
		$this->tableEnd();
		$this->output();
		exit(0);
	}
}
?>