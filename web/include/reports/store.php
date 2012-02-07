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


class Report_Store
{

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
	
	
	function getName($short=0)
	{
		if($short)	return "Остатки на складе";
		else		return "Остатки товара на складе";
	}
	

	function Form()
	{
		global $tmpl;
		$tmpl->AddText("<h1>".$this->getName()."</h1>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='store'>
		<input type='hidden' name='opt' value='make'>
		<fieldset><legend>Отобразить цены</legend>");
		$res=mysql_query("SELECT `id`, `name` FROM `doc_cost` ORDER BY `id`");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список цен");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<label><input type='checkbox' name='cost[$nxt[0]]' value='$nxt[0]'>$nxt[1]</label><br>");
		}
		$tmpl->AddText("</fieldset><br>
		Группа товаров:<br>");
		$this->GroupSelBlock();
		$tmpl->AddText("<button type='submit'>Создать отчет</button></form>");	
	}
	
	function MakeHTML()
	{
		global $tmpl;
		$gs=rcv('gs');
		$g=@$_POST['g'];
		$cost=@$_POST['cost'];
		$tmpl->LoadTemplate('print');
		$tmpl->SetText("<h1>".$this->getName()."</h1>
		<table width=100%><tr><th>N<th>Наименование<th>Количество<th>Актуальная цена<br>поступления<th>Базовая цена<th>Наценка<th>Сумма по АЦП<th>Сумма по базовой");
		$col_count=8;
		if(is_array($cost))
		{
			$res=mysql_query("SELECT `id`, `name` FROM `doc_cost` ORDER BY `name`");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список цен");
			$costs=array();
			while($nxt=mysql_fetch_row($res))	$costs[$nxt[0]]=$nxt[1];
			foreach($cost as $id => $value)
			{
				$tmpl->AddText("<th>".$costs[$id]);
				$col_count++;
			}
		}
		$sum=$bsum=$summass=0;
		$res_group=mysql_query("SELECT `id`, `name` FROM `doc_group` ORDER BY `id`");
		while($group_line=mysql_fetch_assoc($res_group))
		{
			if($gs && is_array($g))
				if(!in_array($group_line['id'],$g))	continue;
			
			$tmpl->AddText("<tr><td colspan='$col_count' class='m1'>{$group_line['id']}. {$group_line['name']}</td></tr>");
		
		
			$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`cost`,
			(SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `count`,
			`doc_base_dop`.`mass`
			FROM `doc_base`
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
			WHERE `doc_base`.`group`='{$group_line['id']}'
			ORDER BY `doc_base`.`name`");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список наименований");
			
			while($nxt=mysql_fetch_row($res))
			{
				if($nxt[3]<=0) continue;
				$act_cost=sprintf('%0.2f',GetInCost($nxt[0]));
				$cost_p=sprintf("%0.2f",$nxt[2]);
				$sum_p=sprintf("%0.2f",$act_cost*$nxt[3]);
				$bsum_p=sprintf("%0.2f",$nxt[2]*$nxt[3]);
				$sum+=$act_cost*$nxt[3];
				$bsum+=$nxt[2]*$nxt[3];
				if($nxt[3]<0) $nxt[3]='<b>'.$nxt[3].'</b/>';
				$summass+=$nxt[3]*$nxt[4];
				
				$nac=sprintf("%0.2f р. (%0.2f%%)",$cost_p-$act_cost,($cost_p/$act_cost)*100-100);
				
				$tmpl->AddText("<tr><td>$nxt[0]<td>$nxt[1]<td>$nxt[3]<td>$act_cost р.<td>$cost_p р.<td>$nac<td>$sum_p р.<td>$bsum_p р.");
				if(is_array($cost))
				{
					foreach($cost as $id => $value)
					{
						$tmpl->AddText("<td>".GetCostPos($nxt[0], $id));
					}
				}
			}
		}
		$tmpl->AddText("<tr><td colspan='6'><b>Итого:</b><td>$sum р.<td>$bsum р.
		</table><h3>Общая масса склада: $summass кг.</h3>");
	}
	
	function Run($opt)
	{
		if($opt=='')	$this->Form();
		else		$this->MakeHTML();	
	}
};

?>

