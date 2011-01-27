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

include_once("core.php");
$analog=rcv('analog');
$proizv=rcv('proizv');
$mesto=rcv('mesto');
$di_min=rcv('di_min');
$di_max=rcv('di_max');
$de_min=rcv('de_min');
$de_max=rcv('de_max');
$size_min=rcv('size_min');
$size_max=rcv('size_max');
$m_min=rcv('m_min');
$m_max=rcv('m_max');
$cost_min=rcv('cost_min');
$cost_max=rcv('cost_max');
$name=rcv('name');

if(!$name)
{	
	$name=rcv('s');
	if($name)
	{
		$mode='s';
		$analog=1;
	}
}

$cstr=rcv('cstr');
if(($cstr<=0)||($cstr>500)) $cstr=500;

$tmpl->SetTitle("Поиск товара ".$name);

if($analog) $an_ch='checked'; else $an_ch='';

$tmpl->AddText("<h1>Поиск товаров</h1>
<form action='/adv_search.php' method='get'>
<input type='hidden' name='mode' value='s'>
<table width='100%'>
<tr><th colspan=2>Наименование
<th colspan=2>Производитель
<th>Место на складе
<tr class='lin1'>
<td colspan=2><input type='text' name='name' value='$name'><br>
<td colspan=2>
<input type=text name='proizv' value='$proizv'><br>
<td><input type='text' name='mesto' value='$mesto'>
<tr>
<th>Внутренний диаметр
<th>Внешний диаметр
<th>Высота
<th>Масса
<th>Цена
<tr class='lin1'>
<td>От: <input type='text' name='di_min' value='$di_min'><br>до: <input type='text' name='di_max' value='$di_max'>
<td>От: <input type='text' name='de_min' value='$de_min'><br>до: <input type='text' name='de_max' value='$de_max'>
<td>От: <input type='text' name='size_min' value='$size_min'><br>до: <input type='text' name='size_max' value='$size_max'>
<td>От: <input type='text' name='m_min' value='$m_max'><br>до: <input type='text' name='m_max' value='$m_max'>
<td>От: <input type='text' name='cost_min' value='$cost_min'><br>до: <input type='text' name='cost_max' value='$cost_max'>
<tr>
<td colspan=5 align='center'><input type='submit' value='Найти'>
</table>
</form>");

if($mode)
{
	$sql="SELECT `doc_base`.`id`, `doc_group`.`printname`, `doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`cost`, `doc_base`.`cost_date`, `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`, (SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`), `doc_base_dop`.`tranzit`
	FROM `doc_base`
	LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$sklad'
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
	WHERE 1 ";
	
	if($name)
	{		
		$sql.="AND (`doc_base_dop`.`analog` LIKE '%$name%' OR `doc_base`.`name` LIKE '%$name%' OR `doc_base`.`desc` LIKE '%$name%')";
			
	}
	if($proizv)		$sql.="AND `doc_base`.`proizv` LIKE '%$proizv%'";
	if($mesto)		$sql.="AND `doc_base_cnt`.`mesto` LIKE '$mesto'";
	if($di_min)		$sql.="AND `doc_base_dop`.`d_int` >= '$di_min'";
	if($di_max)		$sql.="AND `doc_base_dop`.`d_int` <= '$di_max'";
	if($de_min)		$sql.="AND `doc_base_dop`.`d_ext` >= '$de_min'";
	if($de_max)		$sql.="AND `doc_base_dop`.`d_ext` <= '$de_max'";
	if($size_min)	$sql.="AND `doc_base_dop`.`size` >= '$size_min'";
	if($size_max)	$sql.="AND `doc_base_dop`.`size` <= '$size_max'";
	if($m_min)		$sql.="AND `doc_base_dop`.`mass` >= '$m_min'";
	if($m_max)		$sql.="AND `doc_base_dop`.`mass` <= '$m_max'";
	if($cost_min)	$sql.="AND `doc_base`.`cost` >= '$cost_min'";
	if($cost_max)	$sql.="AND `doc_base`.`cost` <= '$cost_max'";

	$sql.=" LIMIT 1000";
	$res=mysql_query($sql);
    if($row=mysql_numrows($res))
    {
		$s="По запросу найдено $row товаров";
		if($row>=1000) 	$s="Показаны только первые $row товаров, используйте более строгий запрос!";
		$tmpl->AddText("<h1 id='page-title'>Результаты поиска</h1><div id='page-info'>$s</div>
		<table width=100% cellspacing=0 border=0><tr><th>Наименование<th>Производитель<th>Аналог<th>Наличие
		<th>Цена, руб<th>d, мм<th>D, мм<th>B, мм<th>m, кг<th>");
		$i=0;
		$cl="lin0";
		while($nxt=mysql_fetch_row($res))
		{
			$i=1-$i;
			$cost = sprintf("%01.2f руб.", $nxt[4]);
			
			if($nxt[12]<=0)
			{
				if($rrr[13]) $nal='в пути';
				else	$nal="";
			}
			else if($nxt[12]==1) $nal="мало";
			else if($nxt[12]<10) $nal="есть";
			else if($nxt[12]<100) $nal="много";
			else $nal="оч.много";
			
			$dcc=strtotime($nxt[5]);
			$cce='';
			if($dcc<(time()-60*60*24*30*6)) $cce="style='color:#888'";
			$tmpl->AddText("<tr class=lin$i><td><a href='vitrina.php?mode=info&amp;p=$nxt[0]'>$nxt[1] $nxt[2]</a>
			<td>$nxt[3]<td>$nxt[6]<td>$nal<td $cce>$cost<td>$nxt[8]<td>$nxt[9]<td>$nxt[10]<td>$nxt[11]<td>
			<a href='vitrina.php?mode=korz_add&amp;p=$nxt[0]&amp;cnt=1'><img src='img/i_korz.png' alt='В корзину!'></a>");
			$sf++;
			$cc=1-$cc;
			$cl="lin".$cc;
		}
		$tmpl->AddText("</table><span style='color:#888'>Серая цена</span> требует уточнения<br>");
	}
	else $tmpl->msg("По Вашему запросу ничего не найдено! Попробуйте использовать более мягкие условия поиска!");
}

$tmpl->write();
?>
