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


class Report_Costs extends BaseGSReport
{
	function getName($short=0)
	{
		if($short)	return "По ценам";
		else		return "Отчёт по ценам";
	}
	

	function Form()
	{
		global $tmpl;
		$d_t=date("Y-m-d");
		$d_f=date("Y-m-d",time()-60*60*24*31);
		$tmpl->AddText("<h1>".$this->getName()."</h1>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='costs'>
		<input type='hidden' name='opt' value='make'>
		Отображать следующие расчётные цены:<br>");
		$res=mysql_query("SELECT `id`, `name` FROM `doc_cost` ORDER BY `id");
		if(mysql_errno())	throw new MysqlException("Не удалось выбрать список цен");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<label><input type='checkbox' name='cost$nxt[0]' value='1' checked>$nxt[1]</label><br>");			
		}
		$tmpl->AddText("
		Группа товаров:<br>");
		$this->GroupSelBlock();
		$tmpl->AddText("<button type='submit'>Сформировать отчёт</button>
		</form>");
	}
	
	function MakeHTML()
	{
		global $tmpl;
		$g=@$_POST['g'];
		$gs=rcv('gs');
		$tmpl->LoadTemplate('print');
		$tmpl->SetText("<h1>".$this->getName()."</h1>");
		$costs=array();
		$res=mysql_query("SELECT `id`, `name` FROM `doc_cost` ORDER BY `id");
		if(mysql_errno())	throw new MysqlException("Не удалось выбрать список цен");
		$cost_cnt=0;
		while($nxt=mysql_fetch_row($res))
		{
			if(!rcv('cost'.$nxt[0]))	continue;
			$costs[$nxt[0]]=$nxt[1];
			$cost_cnt++;
		}
		
		$tmpl->AddText("<table width='100%'>
		<tr><th rowspan='2'>N<th rowspan='2'>Код<th rowspan='2'>Наименование<th rowspan='2'>Базовая цена<th rowspan='2'>АЦП<th colspan='$cost_cnt'>Расчётные цены
		<tr>");
		$col_count=6;
		foreach($costs as $cost_name)
		{
			$tmpl->AddText("<th>$cost_name");
			$col_count++;
		}
		$res_group=mysql_query("SELECT `id`, `name` FROM `doc_group` ORDER BY `id`");
		while($group_line=mysql_fetch_assoc($res_group))
		{
			if($gs && is_array($g))
				if(!in_array($group_line['id'],$g))	continue;
			$tmpl->AddText("<tr><td colspan='$col_count' class='m1'>{$group_line['id']}. {$group_line['name']}</td></tr>");
			
			$res=mysql_query("SELECT `id`, `vc`, `name`, `proizv`, `cost` FROM `doc_base`
			WHERE `doc_base`.`group`='{$group_line['id']}'
			ORDER BY `name`");
			if(mysql_errno())	throw new MysqlException("Не удалось выбрать список позиций");
			while($nxt=mysql_fetch_row($res))
			{
				$act_cost=sprintf('%0.2f',GetInCost($nxt[0]));
				$tmpl->AddText("<tr><td>$nxt[0]<td>$nxt[1]<td>$nxt[2] / $nxt[3]<td align='right'>$nxt[4]<td align='right'>$act_cost");
				foreach($costs as $cost_id => $cost_name)
				{
					$cost=GetCostPos($nxt[0], $cost_id);
					$tmpl->AddText("<td align='right'>$cost");
				}
			}
		}
		$tmpl->AddText("</table>");
	}
	
	function Run($opt)
	{
		if($opt=='')	$this->Form();
		else		$this->MakeHTML();	
	}
};

?>

