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
include_once("core.php");
include_once("include/doc.core.php");
try
{
if(!isAccess('doc_fabric','view'))	throw new AccessException('');
need_auth($tmpl);
$tmpl->HideBlock('left');
$tmpl->SetTitle("Производственный учёт (в разработке)");
if($mode=='')
{
	$tmpl->SetText("<h1 id='page-title'>Производственный учёт</h1>
	<ul>
	<li><a href='?mode=builders'>Список сборщиков</a></li>
	<li><a href='?mode=prepare'>Внесение данных</a></li>
	</ul>");
}
else if($mode=='builders')
{
	$tmpl->SetText("<h1 id='page-title'>Производственный учёт - сборщики</h1>
	<div id='page-info'><a href='/fabric.php'>Назад</a></div>
	<form method='post'>
	<input type='hidden' name='mode' value='builders'>
	<input type='hidden' name='opt' value='save'>");
		
	if(isset($_POST['name']))
	if(is_array($_POST['name']))
	{
		$res=mysql_query("SELECT `id`, `active`, `name` FROM `fabric_builders` ORDER BY `id`");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список сборщиков");
		$f=0;
		while($line=mysql_fetch_row($res))
		{
			$upd='';
			$active=@$_POST['active'][$line[0]]?1:0;
			$name=@$_POST['name'][$line[0]];
			
			if($name!=$line[2])	$upd="`name`='".mysql_real_escape_string($name)."'";
			if($active!=$line[1])
			{
				if($upd)	$upd.=',';
				$upd.="`active`=$active";
			}
			if($upd)
			{
				if(!isAccess('doc_fabric','edit'))	throw new AccessException('');
				mysql_query("UPDATE `fabric_builders` SET $upd WHERE `id`=$line[0]");
				if(mysql_errno())	throw new MysqlException("Не удалось обновить список сборщиков");
				$f=1;
			}
		}
		if($f)	$tmpl->msg("Данные обновлены","ok");
	}
	if(@$_POST['name_new'])
	{
		if(!isAccess('doc_fabric','edit'))	throw new AccessException('');
		$active=@$_POST['active_new']?1:0;
		$name=mysql_real_escape_string($_POST['name_new']);
		mysql_query("INSERT INTO `fabric_builders` (`active`,`name`) VALUES ($active, '$name')");
		if(mysql_errno())	throw new MysqlException("Не удалось добавить сборщика");
		if($f)	$tmpl->msg("Сборщик добавлен","ok");
	}
	$res=mysql_query("SELECT `id`, `active`, `name` FROM `fabric_builders` ORDER BY `id`");
	if(mysql_errno())	throw new MysqlException("Не удалось получить список сборщиков");
	$tmpl->AddText("<table class='list'>
	<tr><th>ID</th><th>&nbsp;</th><th>Имя</th></tr>");
	while($line=mysql_fetch_row($res))
	{
		$checked=$line[1]?'checked':'';
		$tmpl->AddText("<tr><td>$line[0]</td><td><input type='checkbox' name='active[$line[0]]' value='1' $checked></td><td><input type='text' name='name[$line[0]]' value='$line[2]' maxlength='32'></td></tr>");
	}
	$tmpl->AddText("<tr><td>новый</td><td><input type='checkbox' name='active_new' value='1' checked></td><td><input type='text' name='name_new' value='' maxlength='32'></td></tr>");
	$tmpl->AddText("</table><button type='submit'>Сохранить</button></form>");
}
else if($mode=='prepare')
{
	$tmpl->SetText("<h1 id='page-title'>Производственный учёт - ввод данных</h1>
	<div id='page-info'><a href='/fabric.php'>Назад</a></div>
	<script type='text/javascript' src='js/calendar.js'></script>
	<link rel='stylesheet' type='text/css' href='/css/core.calendar.css'>
	<form method='post'>
	<input type='hidden' name='mode' value='enter_day'>
	Дата:<br>
	<input type='text' name='date' id='date_input' value='".date('Y-m-d')."'><br>
	<script>
	initCalendar('date_input')
	</script>	
	Склад сборки:<br>
	<select name='sklad'>");
	$res=mysql_query("SELECT `id`, `name` FROM `doc_sklady` ORDER BY `name`");
	while($line=mysql_fetch_row($res))
	{
		$tmpl->AddText("<option value='$line[0]'>$line[1]</option>");
	}
	$tmpl->AddText("</select><br>
	<button type='submit'>Далее</button>
	</form>");
}
else if($mode=='enter_day')
{
	$sklad=round(@$_REQUEST['sklad']);
	$date=date("Y-m-d",strtotime(@$_REQUEST['date']));
	$tmpl->SetText("<h1 id='page-title'>Производственный учёт - ввод данных</h1>
	<div id='page-info'><a href='/fabric.php?mode=prepare'>Назад</a></div>");
	$res=mysql_query("SELECT `id`, `name` FROM `fabric_builders` WHERE `active`=1 ORDER BY `name`");
	if(mysql_errno())	throw new MysqlException("Не удалось получить список сборщиков");
	$tmpl->AddText("<table class='list'>
	<tr><th>Сборщик</th><th>Собрано единиц</th><th>Из них различных</th><th>Вознаграждение</th></tr>");
	$sv=$sc=0;
	while($line=mysql_fetch_row($res))
	{
		$line[1]=htmlentities($line[1],ENT_QUOTES,"UTF-8");
		$result=mysql_query("SELECT `fabric_data`.`id`, `fabric_data`.`cnt`, `doc_base_values`.`value` AS `zp` FROM `fabric_data`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`fabric_data`.`pos_id`
		LEFT JOIN `doc_base_params` ON `doc_base_params`.`param`='ZP'
		LEFT JOIN `doc_base_values` ON `doc_base_values`.`id`=`doc_base`.`id` AND `doc_base_values`.`param_id`=`doc_base_params`.`id`
		WHERE `fabric_data`.`builder_id`=$line[0] AND `fabric_data`.`sklad_id`=$sklad AND `fabric_data`.`date`='$date'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список наименований");
		$i=$sum=$cnt=0;
		while($nxt=mysql_fetch_assoc($result))
		{
			$i++;
			$sum+=$nxt['cnt']*$nxt['zp'];
			$cnt+=$nxt['cnt'];
		}
		$sv+=$sum;
		$sc+=$cnt;
		$tmpl->AddText("<tr><td><a href='/fabric.php?mode=enter_pos&amp;sklad=$sklad&amp;date=$date&amp;builder=$line[0]'>$line[1]</a></td><td>$cnt</td><td>$i</td><td>$sum</td></tr>");
	}
	$tmpl->AddText("
	<tr><th>Итого:</th><th>$sc</th><th></th><th>$sv</th></table>");
}
else if($mode=='enter_pos')
{
	$builder=round(@$_REQUEST['builder']);
	$sklad=round(@$_REQUEST['sklad']);
	$date=date("Y-m-d",strtotime(@$_REQUEST['date']));
	$tmpl->SetText("<h1 id='page-title'>Производственный учёт - ввод данных</h1>
	<div id='page-info'><a href='/fabric.php?mode=enter_day&amp;sklad=$sklad&amp;date=$date'>Назад</a></div>");
	if(isset($_REQUEST['vc']))
	{
		$vc=mysql_real_escape_string($_REQUEST['vc']);
		$cnt=round(@$_REQUEST['cnt']);
		
		$res=mysql_query("SELECT `id`, `name` FROM `doc_base` WHERE `vc`='$vc'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить id наименования");
		if(mysql_num_rows($res)==0)	$tmpl->msg("Наименование с таким кодом отсутствует в базе",'err');
		else
		{
			if(!isAccess('doc_fabric','edit'))	throw new AccessException('');
			$pos_id=mysql_result($res,0,0);
			mysql_query("REPLACE INTO `fabric_data` (`sklad_id`, `builder_id`, `date`, `pos_id`, `cnt`)
			VALUES ($sklad, $builder, '$date', $pos_id, $cnt)");
			if(mysql_errno())	throw new MysqlException("Не удалось добавить наименование");
		}
	}
	$res=mysql_query("SELECT `fabric_data`.`id`, `fabric_data`.`pos_id`, `fabric_data`.`cnt`, `doc_base`.`name`, `doc_base`.`vc`, `doc_base_values`.`value` AS `zp` FROM `fabric_data`
	LEFT JOIN `doc_base` ON `doc_base`.`id`=`fabric_data`.`pos_id`
	LEFT JOIN `doc_base_params` ON `doc_base_params`.`param`='ZP'
	LEFT JOIN `doc_base_values` ON `doc_base_values`.`id`=`doc_base`.`id` AND `doc_base_values`.`param_id`=`doc_base_params`.`id`
	WHERE `fabric_data`.`builder_id`=$builder AND `fabric_data`.`sklad_id`=$sklad AND `fabric_data`.`date`='$date'");
	if(mysql_errno())	throw new MysqlException("Не удалось получить список наименований");
	
	$tmpl->AddText("<table class='list'>
	<thead>
	<tr><th>N</th><th>Код</th><th>Наименование</th><th>Кол-во</th><th>Вознаграждение</th><th>Сумма</th></tr>
	</thead>

	
	
	<tbody>");
	$i=$sum=$allcnt=0;
	while($line=mysql_fetch_assoc($res))
	{
		$i++;
		$line['vc']=htmlentities($line['vc'],ENT_QUOTES,"UTF-8");
		$line['name']=htmlentities($line['name'],ENT_QUOTES,"UTF-8");
		$sumline=$line['cnt']*$line['zp'];
		$sum+=$sumline;
		$allcnt+=$line['cnt'];
		$tmpl->AddText("<tr><td>$i</td><td>{$line['vc']}</td><td>{$line['name']}</td><td>{$line['cnt']}</td><td>{$line['zp']}</td><td>$sumline</td></tr>");
	}
	$tmpl->AddText("</tbody>
	<form method='post'>
	<input type='hidden' name='mode' value='enter_pos'>
	<input type='hidden' name='builder' value='$builder'>
	<input type='hidden' name='sklad' value='$sklad'>
	<input type='hidden' name='date' value='$date'>	
	<tfoot>
	<tr><th colspan='3'>Итого:</th><th>$allcnt</th><th></th><th>$sum</th></tr>
	<tr><td>+</td><td><input type='text' name='vc'></td><td></td><td><input type='text' name='cnt'></td><td></td><td><button type='submit'>Записать</button></td></tr>
	</tfoot>
	</form>
	</table>");
}



}
catch(AccessException $e)
{
	$tmpl->msg($e->getMessage(),'err',"Нет доступа");
}
catch(MysqlException $e)
{
	mysql_query("ROLLBACK");
	$tmpl->AddText("<br><br>");
	$tmpl->msg($e->getMessage(),"err");
}
catch(Exception $e)
{
	mysql_query("ROLLBACK");
	$tmpl->AddText("<br><br>");
	$tmpl->logger($e->getMessage());
}

$tmpl->write();
?>