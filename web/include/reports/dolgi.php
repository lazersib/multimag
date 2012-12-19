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


class Report_Dolgi
{

	function getName($short=0)
	{
		if($short)	return "Долги";
		else		return "Отчёт по задолженностям по агентам";
	}

	function Form()
	{
		global $tmpl;
		$curdate=date("Y-m-d");
		$tmpl->AddText("<h1>".$this->getName()."</h1>
		<form action=''>
		<input type='hidden' name='mode' value='dolgi'>
		<input type='hidden' name='opt' value='ok'>
		Организация:<br>
		<select name='firm_id'>
		<option value='0'>--все--</option>");
		$res=mysql_query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `id`");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<option value='$nxt[0]'>$nxt[1]</option>");
		}
		$tmpl->AddText("</select><br>
		Группа агентов:<br>
		<select name='agroup'>
		<option value='0'>--все--</option>");
		$res=mysql_query("SELECT `id`, `name` FROM `doc_agent_group` ORDER BY `name`");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<option value='$nxt[0]'>$nxt[1]</option>");
		}
		$tmpl->AddText("</select><br>
		<fieldset><legend>Вид задолженности</legend>
		<label><input type='radio' name='vdolga' value='1' checked>Нам должны</label><br>
		<label><input type='radio' name='vdolga' value='2'>Мы должны</label>
		</fieldset>
		<button type='submit'>Сформировать</button></form>");
	}

	function MakeHTML($vdolga=0)
	{
		global $tmpl;
		$vdolga=rcv('vdolga');
		$agroup=rcv('agroup');
		$firm_id=rcv('firm_id');
		$tmpl->LoadTemplate('print');
		if($vdolga==2) $tmpl->SetText("<h1>Мы должны (от ".date('d.m.Y').")</h1>");
		else $tmpl->SetText("<h1>Долги партнёров (от ".date('d.m.Y').")</h1>");
		$tmpl->AddText("<table width=100%><tr><th>N<th>Агент - партнер<th>Дата сверки<th>Сумма<th>Дата посл. касс. док-та<th>Дата посл. банк. док-та");
		$sql_add=$agroup?" AND `group`='$agroup'":'';
		$res=mysql_query("SELECT `id` AS `agent_id`, `name`, `data_sverki`
		FROM `doc_agent` WHERE 1 $sql_add ORDER BY `name`");
		$i=0;
		$sum_dolga=0;
		while($nxt=mysql_fetch_array($res))
		{
			$dolg=DocCalcDolg($nxt[0],0,$firm_id);
			if( (($dolg>0)&&($vdolga==1))|| (($dolg<0)&&($vdolga==2)) )
			{
				$d_res=mysql_query("SELECT `date` FROM `doc_list`
				WHERE `agent`={$nxt['agent_id']} AND (`type`=4 OR `type`=5) ORDER BY `date` DESC LIMIT 1");
				if(mysql_num_rows($d_res))
				{
					$k_date=mysql_result($d_res,0,0);
				}
				else	$k_date='';
				$d_res=mysql_query("SELECT `date` FROM `doc_list`
				WHERE `agent`={$nxt['agent_id']} AND (`type`=6 OR `type`=7) ORDER BY `date` DESC LIMIT 1");
				if(mysql_num_rows($d_res))
				{
					$b_date=mysql_result($d_res,0,0);
				}
				else	$b_date='';

				$i++;
				$dolg=abs($dolg);
				$sum_dolga+=$dolg;
				$dolg=number_format ($dolg, 2, '.', ' ');
				$k_date=$k_date?date("Y-m-d",$k_date):'';
				$b_date=$b_date?date("Y-m-d",$b_date):'';
				//$dolg=sprintf("%0.2f",$dolg);
				$tmpl->AddText("<tr><td>$i<td>$nxt[1]<td align='right'>$nxt[2]<td align='right'>$dolg руб.<td align='right'>$k_date<td align='right'>$b_date");
			}
		}
		$sum_dolga=number_format ($sum_dolga, 2, '.', ' ');
		$tmpl->AddText("</table>
		<p>Итого: $i должников с общей суммой долга $sum_dolga  руб.<br> (".num2str($sum_dolga).")</p>");
	}

	function Run($opt)
	{
		if($opt=='')	$this->Form();
		else		$this->MakeHTML();
	}
};

?>

