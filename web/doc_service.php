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
include_once("include/doc.core.php");
need_auth();

SafeLoadTemplate($CONFIG['site']['inner_skin']);

$uid=@$_SESSION['uid'];
if(!isAccess('doc_service','view'))	throw new AccessException("Недостаточно привилегий");

try
{
$uid=@$_SESSION['uid'];
if(!isAccess('doc_service','view'))	throw new AccessException("Нет доступа к странице");

if($mode=='')
{
	$tmpl->AddText("<h1>Служебные функции</h1><ul>
	<li><a href='?mode=merge_agent'>Группировка агентов</a></li>
	<li><a href='?mode=merge_tovar'>Группировка складской номенклатуры</a></li>
	<li><a href='?mode=doc_log'>Журнал изменений</a></li>
	<li><a href='?mode=cost'>Управление ценами</a></li>
	<li><a href='?mode=firm'>Настройки организаций</a></li>
	<li><a href='?mode=vrasx'>Настройки видов расходов</a></li>
	<li><a href='?mode=store'>Настройки складов</a></li>
	</ul>");
}
else if($mode=='merge_agent')
{
	$tmpl->AddText("<h1>Группировка агентов</h1>
	Данная функция перепривязывает все документы и доверенных лиц от агента с большим ID на агента с меньшим ID. После этого, имя агента с большим ID получает префикс old, и агент перемещается в указанную группу.<h2 style='color: #f00'>ВНИМАНИЕ! Данное действие необратимо, и может привести к ошибкам в документах! Перед выполнением убедитесь в том, что у Вас есть резервная копия базы данных! После выполнения действия рекомендуется выполнить процедуру оптимизации!</h2>
	<form method='post'><input type='hidden' name='mode' value='merge_agent_ok'>
	<fieldset><legend>Данные, необходимые для объединения</legend>
	ID первого агента:<br><input type='text' name='ag1'><br>
	ID второго агента:<br><input type='text' name='ag2'><br>
	Группа для перемещения:<br><select name='gr'>");
	$res=mysql_query("SELECT `id`, `name` FROM `doc_agent_group` ORDER BY `name`");
	while($nxt=mysql_fetch_row($res))	$tmpl->AddText("<option value='$nxt[0]'>$nxt[1] (id:$nxt[0])</option>");
	$tmpl->AddText("</select><br><br>
	<button>Выполнить запрошенную операцию</button>
	</fieldset></form>");
}
else if($mode=='merge_agent_ok')
{
	$ag1=rcv('ag1');
	$ag2=rcv('ag2');
	$gr=rcv('gr');
	settype($ag1,'int');
	settype($ag2,'int');
	settype($gr,'int');
	if( ($ag1==0) || ($ag2==0) )	throw new Exception("не указан ID агента!");
	if($ag1==$ag2)			throw new Exception("ID агентов должны быть разные!");
	if($ag2<$ag1)	{$ag=$ag1;$ag1=$ag2;$ag2=$ag;}
	mysql_query("UPDATE `doc_list` SET `agent`='$ag1' WHERE `agent`='$ag2'");
	if(mysql_error())		throw new MysqlException("Не удалось перенести документы на указанного агента!");
	$af_doc=mysql_affected_rows();
	mysql_query("UPDATE `doc_agent_dov` SET `ag_id`='$ag1' WHERE `ag_id`='$ag2'");
	if(mysql_error())		throw new MysqlException("Не удалось перенести доверенных лиц на указанного агента!");
	$af_dov=mysql_affected_rows();
	mysql_query("UPDATE `doc_agent` SET `name`=CONCAT('old ',`name`), `group`='$gr' WHERE `id`='$ag2'");
	if(mysql_error())		throw new MysqlException("Не удалось обновить данные агента!");
	$tmpl->msg("Операция выполнена - обновлено $af_doc документов и $af_dov доверенных лиц","ok");
	
	mysql_query("COMMIT");
}
else if($mode=='merge_tovar')
{
	$tmpl->AddText("<h1>Группировка складской номенклатуры</h1>
	Данная функция перепривязывает всю номенклатуру в документах от объекта с большим ID на объект с меньшим ID. После этого, имя объекта с большим ID получает префикс old, и объекта перемещается в указанную группу.<h2 style='color: #f00'>ВНИМАНИЕ! Данное действие необратимо, и может привести к ошибкам в документах! Перед выполнением убедитесь в том, что у Вас есть резервная копия базы данных! После выполнения действия ОБЯЗАТЕЛЬНО выполнить процедуру оптимизации, иначе остатки на складе будут неверны!</h2>
	<form method='post'><input type='hidden' name='mode' value='merge_tovar_ok'>
	<fieldset><legend>Данные, необходимые для объединения</legend>
	ID первого объекта:<br><input type='text' name='tov1'><br>
	ID второго объекта:<br><input type='text' name='tov2'><br>
	Группа для перемещения:<br><select name='gr'>");
	$res=mysql_query("SELECT `id`, `name` FROM `doc_group` ORDER BY `name`");
	while($nxt=mysql_fetch_row($res))	$tmpl->AddText("<option value='$nxt[0]'>$nxt[1] (id:$nxt[0])</option>");
	$tmpl->AddText("</select><br><br>
	<button>Выполнить запрошенную операцию</button>
	</fieldset></form>");
}
else if($mode=='merge_tovar_ok')
{
	$tov1=rcv('tov1');
	$tov2=rcv('tov2');
	$gr=rcv('gr');
	settype($tov1,'int');
	settype($tov2,'int');
	settype($gr,'int');
	if( ($tov1==0) || ($tov2==0) )	throw new Exception("не указан ID объекта!");
	if($tov1==$tov2)		throw new Exception("ID объектов должны быть разные!");
	if($tov2<$tov1)	{$tov=$tov1;$tov1=$tov2;$tov2=$tov;}
	mysql_query("UPDATE `doc_list_pos` SET `tovar`='$tov1' WHERE `tovar`='$tov2'");
	if(mysql_error())		throw new MysqlException("Не удалось перенести документы на указанный объект!");
	$af_doc=mysql_affected_rows();
	mysql_query("UPDATE `doc_base` SET `name`=CONCAT('old ',`name`), `group`='$gr' WHERE `id`='$tov2'");
	if(mysql_error())		throw new MysqlException("Не удалось обновить данные товара!");
	$tmpl->msg("Операция выполнена - обновлено $af_doc документов","ok");
	
	mysql_query("COMMIT");
}
else if($mode=='doc_log')
{
	$motions=$targets=array();
	$res=mysql_query("SELECT DISTINCT `motion` FROM `doc_log`");
	while($nxt=mysql_fetch_row($res))
	{
		$nxt[0]=str_replace(':','',$nxt[0]);
		list($motions[],$targets[])=explode(' ', $nxt[0]);
	}
	$motions=array_unique($motions);
	$targets=array_unique($targets);
	$tmpl->msg("Разработка функции временно приостановлена. Функция неработоспособна.","err");
	$tmpl->AddText("<h1>Журнал изменений</h1>
	Данная функция позволяет получить информацию по изменениям в базе документов, отобранной по заданным критериям.
	<form method='post'><input type='hidden' name='mode' value=doc_log_ok'>
	<table width='100%'>
	<tr><th>Дата<th>Типы объектов<th>Действие<th>IP адрес
	<tr>
	<td>
	От: <input type=text id='id_pub_date_date' class='vDateField required' name='dt_from' value='$dt_from'><br>
	До: <input type=text id='id_pub_date_date' class='vDateField required' name='dt_to' value='$dt_to'><br>
	<td>
	<label><input type='radio' name='obj_type' value='all'>Все</label><br>
	<label><input type='radio' name='obj_type' value='sel'>Выбранные:</label><br>");
	$res=mysql_query("SELECT DISTINCT `object` FROM `doc_log`");
	while($nxt=mysql_fetch_row($res))
	{
		switch($nxt[0])
		{
			case '':	$desc='{не задан}';	break;
			case 'agent':	$desc='Агент';		break;
			case 'doc':	$desc='Документ';	break;
			case 'tovar':	$desc='Товар';		break;
			default:	$desc=$nxt[0];
		}
		$tmpl->AddText("<label><input type='checkbox' name='obj' value='agent'>$desc</label><br>");
	}
	$tmpl->AddText("
	<label><input type='radio' name='obj_type' value='def'>Свой</label><br>
	<input type='text' name='obj_name' value='$obj_name'>
	<td><label><input type='radio' name='motion' value='all'>Все</label><br>");
	foreach($motions as $id=> $val)
	{
		$tmpl->AddText("<label><input type='radio' name='motion' value='$val'>$val</label><br>");
	}
	
	$tmpl->AddText("<td><label><input type='radio' name='motion' value='all'>Все</label><br>");
	foreach($targets as $id=> $val)
	{
		$tmpl->AddText("<label><input type='radio' name='motion' value='$val'>$val</label><br>");
	}
	
	$tmpl->AddText("</table>
	<button>Отобразить</button>
	</fieldset></form>");
}
else if($mode=='cost')
{
	$tmpl->AddText("<h1>Управление ценами</h1>");
	$res=mysql_query("SELECT `id`, `name`, `type`, `value`, `vid`, `accuracy`, `direction` FROM `doc_cost`");
	if(mysql_errno())	throw new MysqlException("Не удалось список цен");
	
	$tmpl->AddText("<table><tr><th>ID<th>Наименование<th>Тип<th>Значение<th>Вид<th>Точность<th>Округление<th>Действие");
	$vidi=array('-2' => 'Интернет-цена (объём)', '-1' => 'Интернет-цена', '0' => 'Обычная', '1' => 'По умолчанию' );
	$cost_types=array('pp' => 'Процент', 'abs' => 'Абсолютная наценка', 'fix' => 'Фиксированная цена');
	$direct=array(0=>'Вниз', 1=>'K ближайшему', 2=>'Вверх');
	while($nxt=mysql_fetch_row($res))
	{
		$tmpl->AddText("<form><input type='hidden' name='mode' value='costs'><input type='hidden' name='n' value='$nxt[0]'>
		<tr><td>$nxt[0]<td><input type='text' name='nm' value='$nxt[1]'>
		<td><select name='cost_type'>");
		foreach($cost_types as $id => $type)
		{
			$sel=($id==$nxt[2])?' selected':'';
			$tmpl->AddText("<option value='$id'$sel>$type</option>");
		}
		$tmpl->AddText("</select>
		<td><input type='text' name='coeff' value='$nxt[3]'>
		<td><select name='vid'>");
		foreach($vidi as $id => $vid)
		{
			$sel=$id==$nxt[4]?'selected':'';
			$tmpl->AddText("<option value='$id' $sel>$vid</option>");
		}
		$tmpl->AddText("</select>
		<td><select name='accur'>");
		for($i=-3;$i<3;$i++)
		{
			$a=sprintf("%0.2f",pow(10,$i*(-1)));
			$sel=$nxt[5]==$i?'selected':'';
			$tmpl->AddText("<option value='$i' $sel>$a</option>");
		}
		$tmpl->AddText("</select>
		<td><select name='direct'>");
		for($i=0;$i<3;$i++)
		{
			$sel=$nxt[6]==$i?'selected':'';
			$tmpl->AddText("<option value='$i' $sel>{$direct[$i]}</option>");
		}
		$tmpl->AddText("</select>
		<td><input type='submit' value='Сохранить'></form>");
	}
	$tmpl->AddText("<form><input type='hidden' name='mode' value='costs'><input type='hidden' name='n' value='0'>
	<tr><td>Новая<td><input type='text' name='nm' value=''>
	<td><select name='cost_type'>");
	foreach($cost_types as $id => $type)
	{
		$sel=$id=='pp'?' selected':'';
		$tmpl->AddText("<option value='$id'$sel>$type</option>");
	}
	$tmpl->AddText("</select>
	<td><input type='text' name='coeff' value=''>
	<td><select name='vid'>");
	foreach($vidi as $id => $vid)
	{
		$sel=$id==0?'selected':'';
		$tmpl->AddText("<option value='$id' $sel>$vid</option>");
	}
	$tmpl->AddText("</select>
	<td><select name='accur'>");
	for($i=-3;$i<3;$i++)
	{
		$a=sprintf("%0.2f",pow(10,$i*(-1)));
		$sel=2==$i?'selected':'';
		$tmpl->AddText("<option value='$i' $sel>$a</option>");
	}
	$tmpl->AddText("</select>
	<td><select name='direct'>");
	for($i=0;$i<3;$i++)
	{
		$sel=1==$i?'selected':'';
		$tmpl->AddText("<option value='$i' $sel>{$direct[$i]}</option>");
	}
	$tmpl->AddText("</select><td><input type='submit' value='Добавить'></form></table>
	<fieldset><legend>Виды цен</legend>
	<ul>
	<li><b>По умолчанию</b> - устанавливается при создании нового документа по умолчанию. Так же по этой цене отображаются товары на витрине для неаутентифицированных пользователей. Относительно цены по умолчанию отображается размер скидки.</li>
	<li><b>Интернет-цена</b> - применяется для всех аутентифицированных пользователей</li>
	<li><b>Интернет-цена (объём)</b> - применяется для аутентифицированных пользователей, набравших в корзину товара на сумму болше пороговой</li>
	<li><b>Обычная</b> - не обладает особыми свойствами. Можно использовать при создании документов или при формировании прайс-листа.</li>
	</ul>
	</fieldset>");
}
else if($mode=='costs')
{
	if(!$rights['edit'])	throw new AccessException("Недостаточно привилегий!");
	$n=rcv('n');
	$nm=rcv('nm');
	$cost_type=rcv('cost_type');
	$coeff=rcv('coeff');
	$accur=rcv('accur');
	$direct=rcv('direct');
	$vid=rcv('vid');
	if($n)
	{
		mysql_query("UPDATE `doc_cost` SET `name`='$nm', `type`='$cost_type', `value`='$coeff', `vid`='$vid', `accuracy`='$accur', `direction`='$direct' WHERE `id`='$n'");
	}
	else
	{
		mysql_query("INSERT INTO `doc_cost` (`name`, `type`, `value`, `vid`, `accuracy`, `direction`) VALUES ('$nm', '$type', '$coeff', '$vid', '$accur', '$direct')");
	}
	if(mysql_errno())	throw new MysqlException("Не удалось сохранить цену!");
	header("Location: doc_service.php?mode=cost");
}
else if($mode=='firm')
{
	$tmpl->AddText("<h1>Настройки фирм</h1>
	<form action='' method='post'>
	<input type='hidden' name='mode' value='firme'>
	Выберите фирму:<br>
	<select name='firm_id'>");
	$rs=mysql_query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `firm_name`");
	if(mysql_errno())	throw new MysqlException("Не удалось получить список фирм!");
	while($nx=mysql_fetch_row($rs))
	{
		$tmpl->AddText("<option value='$nx[0]'>$nx[1]</option>");		
	}		
	$tmpl->AddText("<option value='0'>--Создаьть новую--</option></select><br>
	<input type='submit' value='Далее'>
	</form>");
}
else if($mode=='firme')
{
	$tmpl->SetTitle("Настройки фирмы");
	$tmpl->AddStyle("input.dw{width:300px;}");
	$firm_id=rcv('firm_id');
	$res=mysql_query("SELECT * FROM `doc_vars` WHERE `id`='$firm_id'");
	if(mysql_errno())	throw new MysqlException("Не удалось получить данные фирмы!");
	$nxt=mysql_fetch_row($res);
	$fields = mysql_list_fields($CONFIG['mysql']['db'], "doc_vars");
	if(mysql_errno())	throw new MysqlException("Не удалось структуру таблицы!");
	$columns = mysql_num_fields($fields);
	
	$tmpl->AddText("
	<form action='doc_service.php' method='post'>
	<input type='hidden' name='mode' value='firms'>
	<input type='hidden' name='firm_id' value='$firm_id'>");
	for ($i = 0; $i < $columns; $i++)
	{
		$fn=mysql_field_name($fields, $i);
		if($fn=='id') continue;
		$tmpl->AddText("$fn<br><input type='text' class='dw' name='$fn' value='".$nxt[$i]."'><br>");
	}
	$tmpl->AddText("<input type='submit' value='Сохранить'></form>");

}
else if($mode=='firms')
{
	$firm_id=rcv('firm_id');

	$fields = mysql_list_fields($CONFIG['mysql']['db'], "doc_vars");
	$columns = mysql_num_fields($fields);
	if($firm_id)
	{
		$ss="UPDATE `doc_vars` SET ";
		for ($i = 0; $i < $columns; $i++)
		{
			$fn=mysql_field_name($fields, $i);
			if($fn=='id') continue;
			$dd=rcv($fn);
			$ss.="`$fn`='$dd'";
			if(($i+1)<$columns) $ss.=", ";
		}
		$ss.=" WHERE `id`='$firm_id'";
	}
	else
	{
		for ($i = 0; $i < $columns; $i++)
		{
			$fn=mysql_field_name($fields, $i);
			if($fn=='id') continue;
			$dd=rcv($fn);
			$s1.="`$fn`";
			$s2.="'$dd'";
			if(($i+1)<$columns) $s1.=", ";
			if(($i+1)<$columns) $s2.=", ";
		}
		
		$ss="INSERT INTO `doc_vars` ($s1) VALUES ($s2)";
		
	}
	$res=mysql_query($ss);
	if(mysql_errno())	throw new MysqlException("Не удалось сохранить данные! $ss");
	$tmpl->msg("Данные сохранены!","ok");
}    
else if($mode=='vrasx')
{
	$tmpl->AddText("<h1>Редактор видов расходов</h1>");
	$opt=rcv('opt');
	if($opt!='')
	{
		if(!$rights['edit'])	throw new AccessException("Недостаточно привилегий!");
		$res=mysql_query("SELECT `id`, `name`, `adm` FROM `doc_rasxodi` ORDER BY `id`");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список расходов");
		while($nxt=mysql_fetch_row($res))
		{
			$name=rcv('nm'.$nxt[0]);
			$adm=rcv('ch'.$nxt[0]);
			settype($adm,'int');
			if( ($name!=$nxt[1]) || ($adm!=$nxt[2]))
			mysql_query("UPDATE `doc_rasxodi` SET `name`='$name', `adm`='$adm' WHERE `id`='$nxt[0]'");
			if(mysql_errno())	throw new MysqlException("Не удалось изменить список расходов");
		}
		$name=rcv('nm_new');
		$adm=rcv('ch_new');
		if($name)
		{
			mysql_query("INSERT INTO `doc_rasxodi` (`name`, `adm`) VALUES ('$name', '$adm')");
			if(mysql_errno())	throw new MysqlException("Не удалось пополнить список расходов");
		}
		$tmpl->msg("Информация обновлена!");
	}
	
	$res=mysql_query("SELECT `id`, `name`, `adm` FROM `doc_rasxodi` ORDER BY `id`");
	if(mysql_errno())	throw new MysqlException("Не удалось получить список расходов");
	$tmpl->AddText("
	<form action='' method='post'>
	<input type='hidden' name='mode' value='vrasx'>
	<input type='hidden' name='opt'  value='save'>
	<table>
	<tr><th>ID<th>Наименование<th>Административный");
	$i=0;
	while($nxt=mysql_fetch_row($res))
	{
		$checked=$nxt[2]?'checked':'';
		$tmpl->AddText("<tr class='lin$i'><td>$nxt[0]<td><input type='text' name='nm$nxt[0]' value='$nxt[1]' style='width: 400px;'><td><label><input type='checkbox' name='ch$nxt[0]' $checked value='1'> Да</label>");
		$i=1-$i;
	}
	$tmpl->AddText("<tr><td>Новый<td><input type='text' name='nm_new' value='' style='width: 400px;'><td><label><input type='checkbox' name='ch_new' value='1'> Да</label>");
	
	$tmpl->AddText("</table>
	<button type='submit'>Записать</button>
	</form>");
}
else if($mode=='store')
{
	if(rcv('opt'))
	{
		$res=mysql_query("SELECT `id`, `name`, `dnc` FROM `doc_sklady`");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список складов");
		while($nxt=mysql_fetch_row($res))
		{
			if(!isset($_POST['sname'][$nxt[0]]))	continue;
			$name=mysql_real_escape_string($_POST['sname'][$nxt[0]]);
			$dnc=isset($_POST['dnc'][$nxt[0]])?1:0;
			$desc='';
			if($_POST['sname'][$nxt[0]]!=$nxt[1])	$desc.="name:(".mysql_real_escape_string($nxt[1])." => $name), ";
			if($dnc!=$nxt[2])			$desc.="dnc: ($nxt[2] => $dnc)";
			if($desc=='')	continue;			
			
			mysql_query("UPDATE `doc_sklady` SET `name`='$name', `dnc`='$dnc' WHERE `id`='$nxt[0]'");
			doc_log('UPDATE',$desc,'sklad',$nxt[0]);
		}
		$tmpl->msg("Данные обновлены","ok");
	}
	
	$tmpl->AddText("<h1>Редактор складов</h1>
	<form action='' method='post'>
	<input type='hidden' name='mode' value='store'>
	<input type='hidden' name='opt' value='save'>
	<table><tr><th>N</th><th>Наименование</th><th>Не контролировать остатки</th></tr>");
	$res=mysql_query("SELECT `id`, `name`, `dnc` FROM `doc_sklady` ORDER BY `id`");
	if(mysql_errno())	throw new MysqlException("Не удалось получить список складов");
	while($line=mysql_fetch_row($res))
	{
		$c=$line[2]?'checked':'';
		$tmpl->AddText("<tr><td>$line[0]</td><td><input type='text' name='sname[$line[0]]' value='$line[1]'></td><td><input type='checkbox' name='dnc[$line[0]]' value='1' $c></td></tr>");
	}
	$tmpl->AddText("<tr><td>Новый</td><td><input type='text' name='sname[0]' value=''></td><td><input type='checkbox' name='dnc[0]' value='1'></td></tr>
	</table>
	<button>Сохранить</button>
	</form>");
}
else $tmpl->logger("Запрошена несуществующая опция!");
}
catch(MysqlException $e)
{
	mysql_query("ROLLBACK");
	$tmpl->msg($e->getMessage(),'err','Ошибка базы данных');
	$tmpl->logger($e->getMessage());
}
catch(AccessException $e)
{
	mysql_query("ROLLBACK");
	$tmpl->msg($e->getMessage(),"err","У Вас недостаточно привилегий!");
}
catch(Exception $e)
{
	mysql_query("ROLLBACK");
	$tmpl->msg($e->getMessage(),'err','Ошибка выполнения операции');
}

$tmpl->write();
?>
