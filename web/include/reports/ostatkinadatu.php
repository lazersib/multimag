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
		<form action='' method='post'>
		<input type='hidden' name='mode' value='ostatkinadatu'>
		<input type='hidden' name='opt' value='make'>
		Дата:<br>
		<input type='text' name='date' value='$curdate'><br>
		Склад:<br>
		<select name='sklad'>");
		$res=mysql_query("SELECT `id`, `name` FROM `doc_sklady`");
		while($nxt=mysql_fetch_row($res))
			$tmpl->AddText("<option value='$nxt[0]'>$nxt[1]</option>");		
		$tmpl->AddText("</select>
		Группа товаров:<br>");
		GroupSelBlock();
		$tmpl->AddText("<button type='submit'>Создать отчет</button></form>");	
	}
	
	function MakeHTML()
	{
		global $tmpl;
		$sklad=rcv('sklad');
		$date=rcv('date');
		$unixtime=strtotime($date." 23:59:59");
		$pdate=date("Y-m-d",$unixtime);
		$gs=rcv('gs');
		$g=@$_POST['g'];
		$tmpl->LoadTemplate('print');
		$tmpl->SetText("<h1>Остатки товара на складе N$sklad на дату $pdate</h1>
		<table width=100%><tr><th>N<th>Наименование<th>Количество<th>Базовая цена<th>Сумма по базовой");
		$sum=$zeroflag=$bsum=$summass=0;
		$res_group=mysql_query("SELECT `id`, `name` FROM `doc_group` ORDER BY `id`");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список групп");
		while($group_line=mysql_fetch_assoc($res_group))
		{
			if($gs && is_array($g))
				if(!in_array($group_line['id'],$g))	continue;
			
			$tmpl->AddText("<tr><td colspan='8' class='m1'>{$group_line['id']}. {$group_line['name']}</td></tr>");		
		
			$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`cost`,	`doc_base_dop`.`mass`
			FROM `doc_base`
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
			WHERE `doc_base`.`group`='{$group_line['id']}'
			ORDER BY `doc_base`.`name`");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список наименований");
			
			while($nxt=mysql_fetch_row($res))
			{
				$count=getStoreCntOnDate($nxt[0], $sklad, $unixtime);
				if($count==0) 	continue;
				if($count<0)	$zeroflag=1;
				$cost_p=sprintf("%0.2f",$nxt[2]);
				$bsum_p=sprintf("%0.2f",$nxt[2]*$count);
				$bsum+=$nxt[2]*$count;
				if($count<0) $count='<b>'.$count.'</b/>';
				$summass+=$count*$nxt[3];
				
				$tmpl->AddText("<tr><td>$nxt[0]<td>$nxt[1]<td>$count<td>$cost_p р.<td>$bsum_p р.");
			}
		}
		$tmpl->AddText("<tr><td colspan='4'><b>Итого:</b><td>$bsum р.</table>");
		if(!$zeroflag)	$tmpl->AddText("<h3>Общая масса склада: $summass кг.</h3>");
		else		$tmpl->AddText("<h3>Общая масса склада: невозможно определить из-за отрицательных остатков</h3>");
	}
	
	function Run($opt)
	{
		if($opt=='')	$this->Form();
		else		$this->MakeHTML();	
	}
};

$active_report=new Report_OstatkiNaDatu();
?>

