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


class Report_Komplekt_Zp
{
	function getName($short=0)
	{
		if($short)	return "По комплектующим с З/П";
		else		return "Отчёт по комплектующим (с зарплатой)";
	}
	

	function Form()
	{
		global $tmpl;
		$tmpl->SetText("<h1>".$this->getName()."</h1>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='komplekt_zp'>
		<input type='hidden' name='opt' value='make'>
		Группа товаров:<br>
		<select name='group'>
		<option value='0' selected>-- не выбрана</option>");
		$res=mysql_query("SELECT `id`, `name` FROM `doc_group` ORDER BY `name`");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<option value='$nxt[0]'>$nxt[1] ($nxt[0])</option>");
		}
		$tmpl->AddText("</select><button type='submit'>Создать отчет</button></form>");	
	}
	
	function MakeHTML()
	{
		global $tmpl;
		$tmpl->LoadTemplate('print');
		$group=rcv('group');
		settype($group,'int');
		$date=date('Y-m-d');
		$sel=$group?"AND `group`='$group'":'';
		// Получение id свойства зарплаты
		$res=mysql_query("SELECT `id` FROM `doc_base_params` WHERE `param`='ZP'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить выборку доп.информации");
		if(mysql_num_rows($res)==0)	throw new Exception("Данные о зарплате за сборку в базе не найдены. Необходим дополнительный параметр 'ZP'");
		$zp_id=mysql_result($res,0,0);
		
		$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`vc`, `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base_values`.`value` AS `zp`
		FROM `doc_base`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `doc_base_values` ON `doc_base_values`.`id`=`doc_base`.`id` AND `doc_base_values`.`param_id`='$zp_id'
		WHERE 1 $sel
		ORDER BY `doc_base`.`name`");
		if(mysql_errno())	throw new MysqlException("Не удалось получить выборку наименований");
		$tmpl->AddText("<h1>Отчёт по комплектующим с зарплатой для группы $group на $date</h1><table width='100%'>
		<tr><th rowspan='2'>ID<th rowspan='2'>Код<br>произв.<th rowspan='2'>Наименование<th rowspan='2'>Зар. плата<th colspan='4'>Комплектующие<th rowspan='2'>Стоимость сборки<th rowspan='2'>Стоимость с зарплатой
		<tr><th>Наименование<th>Цена<th>Количество<th>Стоимость");
		$zp_sum=$kompl_sum=$all_sum=0;
		while($nxt=mysql_fetch_assoc($res))
		{
			settype($nxt['zp'], 'double');
			$cnt=$sum=0;
			$kompl_data1=$kompl_data='';
			$rs=mysql_query("SELECT `doc_base_kompl`.`kompl_id` AS `id`, `doc_base`.`name`, `doc_base`.`cost`, `doc_base_kompl`.`cnt`
			FROM `doc_base_kompl`
			LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_base_kompl`.`kompl_id`
			WHERE `doc_base_kompl`.`pos_id`='{$nxt['id']}'");
			echo mysql_error();
			if(mysql_errno())	throw new MysqlException("Не удалось получить выборку комплектующих");
			while($nx=mysql_fetch_row($rs))
			{
				$cnt++;
				$cost=sprintf("%0.2f",GetInCost($nx[0]));
				$cc=$cost*$nx[3];
				$sum+=$cc;
				if(!$kompl_data1)	$kompl_data1="<td>$nx[1]<td>$cost<td>$nx[3]<td>$cc";
				else			$kompl_data.="<tr><td>$nx[1]<td>$cost<td>$nx[3]<td>$cc";
			}
			$span=($cnt>1)?"rowspan='$cnt'":'';
			if(!$kompl_data1)	$kompl_data1="<td><td><td><td>";
			$zsum=$nxt['zp']+$sum;
			$tmpl->AddText("<tr><td $span>{$nxt['id']}<td $span>{$nxt['vc']}<td $span>{$nxt['printname']} {$nxt['name']} / {$nxt['proizv']}<td $span>{$nxt['zp']} $kompl_data1<td $span>$sum<td $span>$zsum
			$kompl_data");
			$zp_sum+=$nxt['zp'];
			$kompl_sum+=$sum;
			$all_sum+=$zsum;
		}
		$tmpl->AddText("
		<tr><td colspan='3'><b>Итого:</b><td>$zp_sum<td colspan='4'><td>$kompl_sum<td>$all_sum
		</table>");
	}
	
	function Run($opt)
	{
		if($opt=='')	$this->Form();
		else		$this->MakeHTML();	
	}
};

$active_report=new Report_Store();
?>

