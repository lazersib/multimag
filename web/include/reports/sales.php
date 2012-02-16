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


class Report_Sales
{
	function getName($short=0)
	{
		if($short)	return "По движению товара";
		else		return "Отчёт по движению товара";
	}
	
	function draw_groups_tree($level)
	{
		$ret='';
		$res=mysql_query("SELECT `id`, `name`, `desc` FROM `doc_group` WHERE `pid`='$level' AND `hidelevel`='0' ORDER BY `name`");
		$i=0;
		$r='';
		if($level==0) $r='IsRoot';
		$cnt=mysql_num_rows($res);
		while($nxt=mysql_fetch_row($res))
		{
			if($nxt[0]==0) continue;
			$item="<label><input type='checkbox' name='g[]' value='$nxt[0]' id='cb$nxt[0]' class='cb' checked onclick='CheckCheck($nxt[0])'>$nxt[1]</label>";
			if($i>=($cnt-1)) $r.=" IsLast";
			$tmp=$this->draw_groups_tree($nxt[0]); // рекурсия
			if($tmp)
				$ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div><ul class='Container' id='cont$nxt[0]'>".$tmp.'</ul></li>';
			else
				$ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div></li>";
			$i++;
		}
		return $ret;
	}


	function GroupSelBlock()
	{
		global $tmpl;
		$tmpl->AddStyle(".scroll_block
		{
			max-height:		250px;
			overflow:		auto;	
		}
		
		div#sb
		{
			display:		none;
			border:			1px solid #888;
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
		function gstoggle()
		{
			var gs=document.getElementById('cgs').checked;
			if(gs==true)
				document.getElementById('sb').style.display='block';
			else	document.getElementById('sb').style.display='none';
		}
		
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
		<label><input type=checkbox name='gs' id='cgs' value='1' onclick='gstoggle()'>Выбрать группы</label><br>
		<div class='scroll_block' id='sb'>
		<ul class='Container'>
		<div class='selmenu'><a onclick='SelAll(true)'>Выбрать всё<a> | <a onclick='SelAll(false)'>Снять всё</a></div>
		".$this->draw_groups_tree(0)."</ul></div>");
	}
	
	function Form()
	{
		global $tmpl;
		$d_t=date("Y-m-d");
		$d_f=date("Y-m-d",time()-60*60*24*31);
		$tmpl->AddText("<h1>".$this->getName()."</h1>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='sales'>
		<input type='hidden' name='opt' value='make'>
		<p class='datetime'>
		<fieldset><legend>Дата</legend>
		С:<input type=text id='id_pub_date_date' class='vDateField required' name='dt_f' value='$d_f'><br>
		По:<input type=text id='id_pub_date_date' class='vDateField required' name='dt_t' value='$d_t'>
		</fieldset>
		</p><br>
		Группа товаров:<br>");
		$this->GroupSelBlock();
		$tmpl->AddText("
		<button type='submit'>Сформировать отчёт</button>
		</form>");
	}
	
	function MakeHTML()
	{
		global $tmpl, $CONFIG;
		$tmpl->LoadTemplate('print');
		$dt_f=strtotime(rcv('dt_f'));
		$dt_t=strtotime(rcv('dt_t'));
		$gs=rcv('gs');
		$g=@$_POST['g'];
		
		$col_count=9;
		
		$print_df=date('Y-m-d', $dt_f);
		$print_dt=date('Y-m-d', $dt_t);
		$tmpl->SetText("
		<h1>Отчёт по движению товара с $print_df по $print_dt</h1>
		<table width='100%'>
		<tr><th>ID");
		if($CONFIG['poseditor']['vc'])
		{
			$tmpl->AddText("<th>Код");
			$col_count++;
		}
		
		$tmpl->AddText("<th>Наименование<th>Ликвидность<th>Приход (кол-во)<th>Расход (кол-во)<th>Сумма по приходам<th>Сумма продаж<th>Прибыль по АЦП");
		$in_cntsum=$out_cntsum=$in_sumsum=$out_sumsum=$pribsum=0;
		$res_group=mysql_query("SELECT `id`, `name`, `printname` FROM `doc_group` ORDER BY `id`");
		while($group_line=mysql_fetch_assoc($res_group))
		{
			if($gs && is_array($g))
				if(!in_array($group_line['id'],$g))	continue;
			
			$tmpl->AddText("<tr><td colspan='$col_count' class='m1'>{$group_line['id']}. {$group_line['name']}</td></tr>");
		
			$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`vc`, `doc_base`.`proizv`, `doc_base`.`likvid`, 
			( 	SELECT SUM(`doc_list_pos`.`cnt`) FROM `doc_list_pos` 
				INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<='$dt_t' AND (`doc_list`.`type`=1 OR `doc_list`.`type`=17 ) AND `doc_list`.`ok`>'0'
				WHERE `doc_list_pos`.`tovar`=`doc_base`.`id` AND `doc_list_pos`.`page`=0 ) AS `in_cnt`,
			( 	SELECT SUM(`doc_list_pos`.`cnt`*`doc_list_pos`.`cost`) FROM `doc_list_pos` 
				INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<='$dt_t' AND (`doc_list`.`type`=1 OR `doc_list`.`type`=17 ) AND `doc_list`.`ok`>'0'
				WHERE `doc_list_pos`.`tovar`=`doc_base`.`id`  AND `doc_list_pos`.`page`=0) AS `in_sum`,
			( 	SELECT SUM(`doc_list_pos`.`cnt`) FROM `doc_list_pos` 
				INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<='$dt_t' AND (`doc_list`.`type`=2 OR `doc_list`.`type`=17 ) AND `doc_list`.`ok`>'0'
				WHERE `doc_list_pos`.`tovar`=`doc_base`.`id` AND ( `doc_list_pos`.`page`=0 OR `doc_list`.`type`='2' )) AS `out_cnt`,
			( 	SELECT SUM(`doc_list_pos`.`cnt`*`doc_list_pos`.`cost`) FROM `doc_list_pos` 
				INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<='$dt_t' AND (`doc_list`.`type`=2 OR `doc_list`.`type`=17 ) AND `doc_list`.`ok`>'0'
				WHERE `doc_list_pos`.`tovar`=`doc_base`.`id` AND ( `doc_list_pos`.`page`=0 OR `doc_list`.`type`='2' )) AS `out_sum`
			FROM `doc_base`
			WHERE `doc_base`.`group`='{$group_line['id']}'
			ORDER BY `doc_base`.`name`");
			
			while($nxt=mysql_fetch_assoc($res))
			{
				$prib=sprintf('%0.2f', $nxt['out_sum']-GetInCost($nxt['id'])*$nxt['out_cnt']);	
				
				$in_cntsum+=$nxt['in_cnt'];
				$out_cntsum+=$nxt['out_cnt'];
				$in_sumsum+=$nxt['in_sum'];
				$out_sumsum+=$nxt['out_sum'];
				
				$nxt['in_sum']=sprintf('%0.2f',$nxt['in_sum']);
				$nxt['out_sum']=sprintf('%0.2f',$nxt['out_sum']);

				$pribsum+=$prib;
				
				$prib_style=$prib<0?"style='color: #f00'":'';
				$tmpl->AddText("<tr align='right'><td>{$nxt['id']}");
				if($CONFIG['poseditor']['vc'])
				{
					$tmpl->AddText("<td>{$nxt['vc']}");
				}
				$tmpl->AddText("<td align='left'>{$group_line['printname']} {$nxt['name']} / {$nxt['proizv']}<td>{$nxt['likvid']} %<td>{$nxt['in_cnt']}<td>{$nxt['out_cnt']}<td>{$nxt['in_sum']}<td>{$nxt['out_sum']}<td $prib_style>$prib");
			}
		}
		$prib_style=$pribsum<0?"style='color: #f00'":'';
		$tmpl->AddTExt("
		<tr><td colspan='4'>Итого:<td>$in_cntsum<td>$out_cntsum<td>$in_sumsum<td>$out_sumsum<td $prib_style>$pribsum руб.
		</table>");
	}
	
	function Run($opt)
	{
		if($opt=='')	$this->Form();
		else		$this->MakeHTML();	
	}
};

?>

