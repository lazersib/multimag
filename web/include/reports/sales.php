<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2012, BlackLight, TND Team, http://tndproject.org
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


class Report_Sales extends BaseGSReport
{
	function GroupSelBlock()
	{
		global $tmpl;
		$tmpl->AddStyle("		
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
		$tmpl->AddText("<script type='text/javascript'>
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
		".$this->draw_groups_tree(0)."</ul></div>");
	}

	function getName($short=0)
	{
		if($short)	return "По движению товара (эксперим.)";
		else		return "Отчёт по движению товара (экспериментальный)";
	}
	
	function Form()
	{
		global $tmpl;
		$d_t=date("Y-m-d");
		$d_f=date("Y-m-d",time()-60*60*24*31);
		$tmpl->AddText("<h1>".$this->getName()."</h1>
		<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='sales'>
		<input type='hidden' name='opt' value='make'>
		<fieldset><legend>Дата</legend>
		С:<input type=text id='dt_f' name='dt_f' value='$d_f'><br>
		По:<input type=text id='dt_t' name='dt_t' value='$d_t'>
		</fieldset>
		Склад:<br>
		<select name='sklad'>");
		//$tmpl->AddText("<option value='0'>--не выбран--</option>");
		$res=mysql_query("SELECT `id`, `name` FROM `doc_sklady`");
		while($nxt=mysql_fetch_row($res))
			$tmpl->AddText("<option value='$nxt[0]'>$nxt[1]</option>");		
		$tmpl->AddText("</select><br>
		<label><input type='checkbox' name='w_docs' value='1' checked>С документами</label><br>
		<label><input type='checkbox' name='div_dt' value='1' checked disabled>Разделить по типам документов</label><br>
		<br>
		<fieldset><legend>Отчёт по</legend>
		<select name='sel_type' id='sel_type'>
		<option value='all'>Всей номенклатуре</option>
		<option value='group'>Выбранной группе</option>
		<option value='pos'>Выбранному наименованию</option>
		</select>
		");
		$this->GroupSelBlock();
		$tmpl->AddText("
		<div id='pos_sel' style='display: none;'>
		<input type='hidden' name='pos_id' id='pos_id' value=''>
		<input type='text' id='posit' style='width: 400px;' value=''>
		</div>
		</fieldset>
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
			if(this.value=='pos')
				document.getElementById('pos_sel').style.display='block';
			else	document.getElementById('pos_sel').style.display='none';			
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
		
		</script>
		");
	}
	
	function outPos($pos_id, $sklad, $w_docs, $div_dt, $dt_f, $dt_t)
	{
		global $tmpl;
		$start_cnt=getStoreCntOnDate($pos_id, $sklad, $dt_f);
		
		if($w_docs)	$tmpl->AddText("<tr><td><td>На начало периода:<td><td>$start_cnt<td><td><tr><th colspan='6' class='m1'>Приходы");
		
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`agent`, `doc_agent`.`name` AS `agent_name`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `ns`.`value` AS `na_sklad`, `doc_sklady`.`name` AS `sklad_name`, `doc_types`.`name` AS `doc_name`, `doc_list`.`date`, CONCAT(`doc_list`.`altnum`, `doc_list`.`subtype`) AS `snum`
		FROM `doc_list_pos`
		INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
		INNER JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		LEFT JOIN `doc_dopdata` AS `ns` ON `ns`.`doc`=`doc_list_pos`.`doc` AND `ns`.`param`='na_sklad'
		LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
		LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
		WHERE `doc_list_pos`.`tovar`='$pos_id' AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<'$dt_t' AND (
		(`doc_list`.`type`='1' AND `doc_list`.`sklad`='$sklad') OR
		(`doc_list`.`type`='8' AND `ns`.`value`='$sklad') OR
		(`doc_list`.`type`='17' AND `doc_list`.`sklad`='$sklad' AND `doc_list_pos`.`page`='0') ) AND `doc_list`.`ok`>0");
		if(mysql_errno())	throw new MysqlException("Не удалось выбрать документы приходов!");
		$sum_cnt=$start_cnt;
		$prix_cnt=$prix_sum=0;
		while($nxt=mysql_fetch_assoc($res))
		{
			$from='Сборка';
			if($nxt['type']==1)		$from=$nxt['agent_name'];
			else if($nxt['type']==8)	$from=$nxt['sklad_name'];
			$date=date("Y-m-d H:i:s",$nxt['date']);
			$sumline=$nxt['cnt']*$nxt['cost'];
			if($w_docs)	$tmpl->AddText("<tr><td>$date<td>{$nxt['doc_name']} {$nxt['snum']} ({$nxt['id']})<td>$from<td>{$nxt['cnt']}<td>{$nxt['cost']}<td>$sumline");
			$prix_cnt+=$nxt['cnt'];
			$prix_sum+=$sumline;
		}
		if($w_docs)	$tmpl->AddText("<tr><td><td>Всего приход:<td><td>$prix_cnt<td><td>$prix_sum");
		$r_cnt=$r_sum=0;
		
		if($w_docs)	$tmpl->AddText("<tr><th colspan='6' class='m1'>Расходы
				<tr><td colspan='6' class='m2'>Реализации");
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`agent`, `doc_agent`.`name` AS `agent_name`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `ns`.`value` AS `na_sklad`, `doc_sklady`.`name` AS `sklad_name`, `doc_types`.`name` AS `doc_name`, `doc_list`.`date`, CONCAT(`doc_list`.`altnum`, `doc_list`.`subtype`) AS `snum`
		FROM `doc_list_pos`
		INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
		INNER JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		LEFT JOIN `doc_dopdata` AS `ns` ON `ns`.`doc`=`doc_list_pos`.`doc` AND `ns`.`param`='na_sklad'
		LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
		LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`ns`.`value`
		WHERE `doc_list_pos`.`tovar`='$pos_id' AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<'$dt_t' AND `doc_list`.`sklad`='$sklad' AND
		`doc_list`.`type`='2' AND `doc_list`.`ok`>0");
		if(mysql_errno())	throw new MysqlException("Не удалось выбрать документы приходов!");
		$realiz_cnt=$sum=0;
		while($nxt=mysql_fetch_assoc($res))
		{
			if($w_docs)
			{
				$from='Сборка';
				if($nxt['type']==2)		$from=$nxt['agent_name'];
				else if($nxt['type']==8)	$from=$nxt['sklad_name'];
				$date=date("Y-m-d H:i:s",$nxt['date']);
				$sumline=$nxt['cnt']*$nxt['cost'];
				$tmpl->AddText("<tr><td>$date<td>{$nxt['doc_name']} {$nxt['snum']} ({$nxt['id']})<td>$from<td>{$nxt['cnt']}<td>{$nxt['cost']}<td>$sumline");
				$sum+=$sumline;
			}
			$realiz_cnt+=$nxt['cnt'];
		}
		if($w_docs)	$tmpl->AddText("<tr><td class='m4'><td class='m4'>По реализациям:<td class='m4'><td class='m4'>$realiz_cnt<td class='m4'><td class='m4'>$sum");
		$r_cnt+=$realiz_cnt;
		$r_sum+=$sum;
		if($w_docs)	$tmpl->AddText("<tr><td colspan='6' class='m2'>Перемещения");
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`agent`, `doc_agent`.`name` AS `agent_name`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `ns`.`value` AS `na_sklad`, `doc_sklady`.`name` AS `sklad_name`, `doc_types`.`name` AS `doc_name`, `doc_list`.`date`, CONCAT(`doc_list`.`altnum`, `doc_list`.`subtype`) AS `snum`
		FROM `doc_list_pos`
		INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
		INNER JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		LEFT JOIN `doc_dopdata` AS `ns` ON `ns`.`doc`=`doc_list_pos`.`doc` AND `ns`.`param`='na_sklad'
		LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
		LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`ns`.`value`
		WHERE `doc_list_pos`.`tovar`='$pos_id' AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<'$dt_t' AND `doc_list`.`sklad`='$sklad' AND `doc_list`.`type`='8' AND `doc_list`.`ok`>0");
		if(mysql_errno())	throw new MysqlException("Не удалось выбрать документы приходов!");
		$perem_cnt=$sum=0;
		while($nxt=mysql_fetch_assoc($res))
		{
			if($w_docs)
			{
				$from='Сборка';
				if($nxt['type']==2)		$from=$nxt['agent_name'];
				else if($nxt['type']==8)	$from=$nxt['sklad_name'];
				$date=date("Y-m-d H:i:s",$nxt['date']);
				$sumline=$nxt['cnt']*$nxt['cost'];
				$tmpl->AddText("<tr><td>$date<td>{$nxt['doc_name']} {$nxt['snum']} ({$nxt['id']})<td>$from<td>{$nxt['cnt']}<td>{$nxt['cost']}<td>$sumline");
				$sum+=$sumline;
			}
			$perem_cnt+=$nxt['cnt'];
		}
		if($w_docs)	$tmpl->AddText("<tr class='m4'><td class='m4'><td class='m4'>По перемещениям:<td class='m4'><td class='m4'>$perem_cnt<td class='m4'><td class='m4'>$sum");
		$r_cnt+=$cnt;
		$r_sum+=$sum;
		if($w_docs)	$tmpl->AddText("<tr><td colspan='6' class='m2'>Сборки");
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`agent`, `doc_agent`.`name` AS `agent_name`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `ns`.`value` AS `na_sklad`, `doc_sklady`.`name` AS `sklad_name`, `doc_types`.`name` AS `doc_name`, `doc_list`.`date`, CONCAT(`doc_list`.`altnum`, `doc_list`.`subtype`) AS `snum`
		FROM `doc_list_pos`
		INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
		INNER JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		LEFT JOIN `doc_dopdata` AS `ns` ON `ns`.`doc`=`doc_list_pos`.`doc` AND `ns`.`param`='na_sklad'
		LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
		LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`ns`.`value`
		WHERE `doc_list_pos`.`tovar`='$pos_id' AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<'$dt_t' AND `doc_list`.`sklad`='$sklad' AND (`doc_list`.`type`='17' AND `doc_list_pos`.`page`!='0') AND `doc_list`.`ok`>0");
		if(mysql_errno())	throw new MysqlException("Не удалось выбрать документы приходов!");
		$sbor_cnt=$sum=0;
		while($nxt=mysql_fetch_assoc($res))
		{
			if($w_docs)
			{
				$from='Сборка';
				if($nxt['type']==2)		$from=$nxt['agent_name'];
				else if($nxt['type']==8)	$from=$nxt['sklad_name'];
				$date=date("Y-m-d H:i:s",$nxt['date']);
				$sumline=$nxt['cnt']*$nxt['cost'];
				$tmpl->AddText("<tr><td>$date<td>{$nxt['doc_name']} {$nxt['snum']} ({$nxt['id']})<td>$from<td>{$nxt['cnt']}<td>{$nxt['cost']}<td>$sumline");
				$sum+=$sumline;
			}
			$sbor_cnt+=$nxt['cnt'];
		}
		$r_cnt+=$sbor_cnt;
		if($w_docs)
		{
			$tmpl->AddText("<tr><td class='m4'><td class='m4'>По сборкам:<td class='m4'><td class='m4'>$sbor_cnt<td class='m4'><td class='m4'>$sum");			
			$r_sum+=$sum;
			$tmpl->AddText("<tr><td><td>Всего расход:<td><td>$r_cnt<td><td>$r_sum");
			
			$end_cnt=$start_cnt+$p_cnt-$r_cnt;
			
			$tmpl->AddText("<tr><td><td>На конец периода:<td><td>$end_cnt<td><td>");
		}
		else
		{
			$end_cnt=$start_cnt+$prix_cnt-$r_cnt;
			$tmpl->AddText("<td>$start_cnt<td>$prix_cnt<td>$realiz_cnt<td>$perem_cnt<td>$sbor_cnt<td>$end_cnt");
		}
	}
	
	function MakeHTML()
	{
		global $tmpl, $CONFIG;
		$tmpl->LoadTemplate('print');
		$dt_f=strtotime(rcv('dt_f'));
		$dt_t=strtotime(rcv('dt_t'));
		$g=@$_POST['g'];
		$sel_type=rcv('sel_type');
		$sklad=rcv('sklad');
		$w_docs=rcv('w_docs');
		$div_dt=rcv('div_dt');
		
		$print_df=date('Y-m-d', $dt_f);
		$print_dt=date('Y-m-d', $dt_t);
		$tmpl->SetText("<h1>Отчёт по движению товара с $print_df по $print_dt</h1>
		<table width='100%'>");
		
		if(!$w_docs)	$tmpl->AddText("<tr><th>ID<th>Код<th>Наименование<th>Начальное кол-во<th>Приход<th>Реализ.<th>Перем.<th>Сборка<th>Итог");
		
		if($sel_type=='pos')
		{
			$pos_id=rcv('pos_id');
			$res=mysql_query("SELECT `vc`, `name` FROM `doc_base` WHERE `id`='$pos_id'");
			if(mysql_errno())		throw MysqlException("Не удалось получить информацию о товаре");
			if(mysql_num_rows($res)==0)	throw new Exception("Товар не выбран!");
			$tov_data=mysql_fetch_row($res);
			if(!$w_docs)
			{
				$tmpl->AddText("<tr><td>$tov_data[0]<td>$tov_data[1]");
			}
			if($w_docs)	$tmpl->AddText("<tr><th colspan='6'>$tov_data[0] $tov_data[1]</tr><tr><th>Дата<th>Документ<th>Источник<th>Кол-во<th>Цена<th>Сумма");
			$this->outPos($pos_id, $sklad, $w_docs, $div_dt, $dt_f, $dt_t);
		}
		else if($sel_type=='all')
		{
			$res=mysql_query("SELECT `id`, `vc`, CONCAT(`doc_base`.`name`, ' - ', `doc_base`.`proizv`) AS `name` FROM `doc_base` ORDER BY `name`");
			if(mysql_errno())		throw MysqlException("Не удалось получить информацию о товарах");

			while($nxt=mysql_fetch_row($res))
			{
				if(!$w_docs)
					$tmpl->AddText("<tr><td>$nxt[0]<td>$nxt[1]<td>$nxt[2]");
				else	$tmpl->AddText("<tr><th colspan='6'>$nxt[1] $nxt[2]</tr><tr><th>Дата<th>Документ<th>Источник<th>Кол-во<th>Цена<th>Сумма");
				$this->outPos($nxt[0], $sklad, $w_docs, $div_dt, $dt_f, $dt_t);
			}
		}
		else if($sel_type=='group')
		{
			$res_group=mysql_query("SELECT `id`, `name` FROM `doc_group` ORDER BY `id`");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список групп");
			while($group_line=mysql_fetch_assoc($res_group))
			{
				if(is_array($g))
					if(!in_array($group_line['id'],$g))	continue;
				
				$tmpl->AddText("<tr><td colspan='9' class='m3'>{$group_line['id']}. {$group_line['name']}</td></tr>");		
			
				$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`vc`, CONCAT(`doc_base`.`name`, ' - ', `doc_base`.`proizv`) AS `name`
				FROM `doc_base`
				WHERE `doc_base`.`group`='{$group_line['id']}'
				ORDER BY `doc_base`.`name`");
				if(mysql_errno())	throw new MysqlException("Не удалось получить список наименований");
				
				while($nxt=mysql_fetch_row($res))
				{
					if(!$w_docs)
					$tmpl->AddText("<tr><td>$nxt[0]<td>$nxt[1]<td>$nxt[2]");
					else	$tmpl->AddText("<tr><th colspan='6'>$nxt[1] $nxt[2]</tr><tr><th>Дата<th>Документ<th>Источник<th>Кол-во<th>Цена<th>Сумма");
					$this->outPos($nxt[0], $sklad, $w_docs, $div_dt, $dt_f, $dt_t);
				}
			}
		}
		
		
// 		$tmpl->AddText("<tr><th>ID");
// 		if($CONFIG['poseditor']['vc'])
// 		{
// 			$tmpl->AddText("<th>Код");
// 			$col_count++;
// 		}
// 		
// 		$tmpl->AddText("<th>Наименование<th>Ликвидность<th>Приход (кол-во)<th>Расход (кол-во)<th>Сумма по приходам<th>Сумма продаж<th>Прибыль по АЦП");
// 		$in_cntsum=$out_cntsum=$in_sumsum=$out_sumsum=$pribsum=0;
// 		$res_group=mysql_query("SELECT `id`, `name`, `printname` FROM `doc_group` ORDER BY `id`");
// 		while($group_line=mysql_fetch_assoc($res_group))
// 		{
// 			if($gs && is_array($g))
// 				if(!in_array($group_line['id'],$g))	continue;
// 			
// 			$tmpl->AddText("<tr><td colspan='$col_count' class='m1'>{$group_line['id']}. {$group_line['name']}</td></tr>");
// 		
// 			$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`vc`, `doc_base`.`proizv`, `doc_base`.`likvid`, 
// 			( 	SELECT SUM(`doc_list_pos`.`cnt`) FROM `doc_list_pos` 
// 				INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<='$dt_t' AND (`doc_list`.`type`=1 OR `doc_list`.`type`=17 ) AND `doc_list`.`ok`>'0'
// 				WHERE `doc_list_pos`.`tovar`=`doc_base`.`id` AND `doc_list_pos`.`page`=0 ) AS `in_cnt`,
// 			( 	SELECT SUM(`doc_list_pos`.`cnt`*`doc_list_pos`.`cost`) FROM `doc_list_pos` 
// 				INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<='$dt_t' AND (`doc_list`.`type`=1 OR `doc_list`.`type`=17 ) AND `doc_list`.`ok`>'0'
// 				WHERE `doc_list_pos`.`tovar`=`doc_base`.`id`  AND `doc_list_pos`.`page`=0) AS `in_sum`,
// 			( 	SELECT SUM(`doc_list_pos`.`cnt`) FROM `doc_list_pos` 
// 				INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<='$dt_t' AND (`doc_list`.`type`=2 OR `doc_list`.`type`=17 ) AND `doc_list`.`ok`>'0'
// 				WHERE `doc_list_pos`.`tovar`=`doc_base`.`id` AND ( `doc_list_pos`.`page`=0 OR `doc_list`.`type`='2' )) AS `out_cnt`,
// 			( 	SELECT SUM(`doc_list_pos`.`cnt`*`doc_list_pos`.`cost`) FROM `doc_list_pos` 
// 				INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<='$dt_t' AND (`doc_list`.`type`=2 OR `doc_list`.`type`=17 ) AND `doc_list`.`ok`>'0'
// 				WHERE `doc_list_pos`.`tovar`=`doc_base`.`id` AND ( `doc_list_pos`.`page`=0 OR `doc_list`.`type`='2' )) AS `out_sum`
// 			FROM `doc_base`
// 			WHERE `doc_base`.`group`='{$group_line['id']}'
// 			ORDER BY `doc_base`.`name`");
// 			
// 			while($nxt=mysql_fetch_assoc($res))
// 			{
// 				$prib=sprintf('%0.2f', $nxt['out_sum']-GetInCost($nxt['id'])*$nxt['out_cnt']);	
// 				
// 				$in_cntsum+=$nxt['in_cnt'];
// 				$out_cntsum+=$nxt['out_cnt'];
// 				$in_sumsum+=$nxt['in_sum'];
// 				$out_sumsum+=$nxt['out_sum'];
// 				
// 				$nxt['in_sum']=sprintf('%0.2f',$nxt['in_sum']);
// 				$nxt['out_sum']=sprintf('%0.2f',$nxt['out_sum']);
// 
// 				$pribsum+=$prib;
// 				
// 				$prib_style=$prib<0?"style='color: #f00'":'';
// 				$tmpl->AddText("<tr align='right'><td>{$nxt['id']}");
// 				if($CONFIG['poseditor']['vc'])
// 				{
// 					$tmpl->AddText("<td>{$nxt['vc']}");
// 				}
// 				$tmpl->AddText("<td align='left'>{$group_line['printname']} {$nxt['name']} / {$nxt['proizv']}<td>{$nxt['likvid']} %<td>{$nxt['in_cnt']}<td>{$nxt['out_cnt']}<td>{$nxt['in_sum']}<td>{$nxt['out_sum']}<td $prib_style>$prib");
// 			}
// 		}
// 		$prib_style=$pribsum<0?"style='color: #f00'":'';
// 		$tmpl->AddTExt("
// 		<tr><td colspan='4'>Итого:<td>$in_cntsum<td>$out_cntsum<td>$in_sumsum<td>$out_sumsum<td $prib_style>$pribsum руб.
		$tmpl->AddTExt("</table>");
	}
	
	function Run($opt)
	{
		if($opt=='')	$this->Form();
		else		$this->MakeHTML();	
	}
};

?>

