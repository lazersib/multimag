<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2010, BlackLight, TND Team, http://tndproject.org
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


class Report_OstatkiNaDatu
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
		if($short)	return "Остатки на выбранную дату";
		else		return "Остатки товара на складе на выбранную дату";
	}
	
	function Form()
	{
		global $tmpl;
		$curdate=date("Y-m-d");
		$tmpl->AddText("<h1>".$this->getName()."</h1>");
		$tmpl->AddText("
		<script type=\"text/javascript\">
		function dtinit()
		{
			initCalendar('dt',false)
		}
		addEventListener('load',dtinit,false)	
		</script>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='ostatkinadatu'>
		<input type='hidden' name='opt' value='make'>
		Дата:<br>
		<input type='text' name='date' id='dt' value='$curdate'><br>
		Склад:<br>
		<select name='sklad'>");
		$res=mysql_query("SELECT `id`, `name` FROM `doc_sklady`");
		while($nxt=mysql_fetch_row($res))
			$tmpl->AddText("<option value='$nxt[0]'>$nxt[1]</option>");		
		$tmpl->AddText("</select><br>
		Группа товаров:<br>");
		$this->GroupSelBlock();
		$tmpl->AddText("<button type='submit'>Создать отчет</button></form>");	
	}
	
	function MakeHTML()
	{
		global $tmpl, $CONFIG;
		$sklad=rcv('sklad');
		$date=rcv('date');
		$unixtime=strtotime($date." 23:59:59");
		$pdate=date("Y-m-d",$unixtime);
		$gs=rcv('gs');
		$g=@$_POST['g'];
		
		$res=mysql_query("SELECT `name` FROM `doc_sklady` WHERE `id`='$sklad'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить наименование склада");
		if(mysql_num_rows($res)<1)	throw new Exception("Склад не найден!");
		list($sklad_name)=mysql_fetch_row($res);
		
		$tmpl->LoadTemplate('print');
		$tmpl->SetText("<h1>Остатки товара на складе N$sklad ($sklad_name) на дату $pdate</h1>
		<table width=100%><tr><th>N");
		$col_count=1;
		if($CONFIG['poseditor']['vc'])
		{
			$tmpl->AddText("<th>Код");
			$col_count++;
		}
		switch($CONFIG['doc']['sklad_default_order'])
		{
			case 'vc':	$order='`doc_base`.`vc`';	break;
			case 'cost':	$order='`doc_base`.`cost`';	break;
			default:	$order='`doc_base`.`name`';
		}
		$tmpl->AddText("<th>Наименование<th>Количество<th>Базовая цена<th>Сумма по базовой");
		$col_count+=4;
		$sum=$zeroflag=$bsum=$summass=0;
		$res_group=mysql_query("SELECT `id`, `name` FROM `doc_group` ORDER BY `id`");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список групп");
		while($group_line=mysql_fetch_assoc($res_group))
		{
			if($gs && is_array($g))
				if(!in_array($group_line['id'],$g))	continue;
			
			$tmpl->AddText("<tr><td colspan='$col_count' class='m1'>{$group_line['id']}. {$group_line['name']}</td></tr>");		
		
			$res=mysql_query("SELECT `doc_base`.`id`, CONCAT(`doc_base`.`name`, ' - ', `doc_base`.`proizv`) AS `name` , `doc_base`.`cost`,	`doc_base_dop`.`mass`, `doc_base`.`vc`
			FROM `doc_base`
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
			WHERE `doc_base`.`group`='{$group_line['id']}'
			ORDER BY $order");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список наименований");
			
			while($nxt=mysql_fetch_row($res))
			{
				$count=getStoreCntOnDate($nxt[0], $sklad, $unixtime, 1);
				if($count==0) 	continue;
				if($count<0)	$zeroflag=1;
				$cost_p=sprintf("%0.2f",$nxt[2]);
				$bsum_p=sprintf("%0.2f",$nxt[2]*$count);
				$bsum+=$nxt[2]*$count;
				if($count<0) $count='<b>'.$count.'</b/>';
				$summass+=$count*$nxt[3];
				
				$tmpl->AddText("<tr><td>$nxt[0]");
				if($CONFIG['poseditor']['vc'])	$tmpl->AddText("<td>{$nxt[4]}");

				$tmpl->AddText("<td>$nxt[1]<td>$count<td>$cost_p р.<td>$bsum_p р.");
			}
		}
		$cs=$col_count-1;
		$bsum=sprintf("%0.2f",$bsum);
		$tmpl->AddText("<tr><td colspan='$cs'><b>Итого:</b><td>$bsum р.</table>");
		if(!$zeroflag)	$tmpl->AddText("<h3>Общая масса склада: $summass кг.</h3>");
		else		$tmpl->AddText("<h3>Общая масса склада: невозможно определить из-за отрицательных остатков</h3>");
	}
	
	function Run($opt)
	{
		if($opt=='')	$this->Form();
		else		$this->MakeHTML();	
	}
};

?>

