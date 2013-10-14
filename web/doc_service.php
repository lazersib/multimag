<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2013, BlackLight, TND Team, http://tndproject.org
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
$tmpl->hideBlock('left');

try
{
$uid=@$_SESSION['uid'];
if(!isAccess('doc_service','view'))	throw new AccessException("Нет доступа к странице");

if($mode=='')
{
	doc_menu();
	$tmpl->addContent("<h1>Служебные функции</h1>
	<ul class='items'>
	<li><a href='?mode=merge_agent'>Группировка агентов</a></li>
	<li><a href='?mode=merge_tovar'>Группировка складской номенклатуры</a></li>
	<li><a href='?mode=doc_log'>Журнал изменений</a></li>
	<li><a href='?mode=cost'>Управление ценами</a></li>
	<li><a href='?mode=firm'>Настройки организаций</a></li>
	<li><a href='?mode=vrasx'>Настройки видов расходов</a></li>
	<li><a href='?mode=store'>Редактор складов</a></li>
	<li><a href='?mode=params'>Настройки свойств складской номенклатуры</a></li>
	<li><a href='?mode=param_collections'>Настройки наборов свойств складской номенклатуры</a></li>
	<li><a href='?mode=auinfo'>Документы, изменённые после проведения</a></li>
	<li><a href='?mode=pcinfo'>Информация по изменениям в номеклатуре</a></li>
	</ul>");
}
else if($mode=='merge_agent')
{
	doc_menu();
	$tmpl->addContent("<h1>Группировка агентов</h1>
	Данная функция перепривязывает все документы и доверенных лиц от агента с большим ID на агента с меньшим ID. После этого, имя агента с большим ID получает префикс old, и агент перемещается в указанную группу.<h2 style='color: #f00'>ВНИМАНИЕ! Данное действие необратимо, и может привести к ошибкам в документах! Перед выполнением убедитесь в том, что у Вас есть резервная копия базы данных! После выполнения действия рекомендуется выполнить процедуру оптимизации!</h2>
	<form method='post'><input type='hidden' name='mode' value='merge_agent_ok'>
	<fieldset><legend>Данные, необходимые для объединения</legend>
	ID первого агента:<br><input type='text' name='ag1'><br>
	ID второго агента:<br><input type='text' name='ag2'><br>
	Группа для перемещения:<br><select name='gr'>");
	$res=$db->query("SELECT `id`, `name` FROM `doc_agent_group` ORDER BY `name`");
	while($nxt=$res->fetch_row())
		$tmpl->addContent("<option value='$nxt[0]'>".html_out($nxt[1])." (id:$nxt[0])</option>");
	$tmpl->addContent("</select><br><br>
	<button>Выполнить запрошенную операцию</button>
	</fieldset></form>");
}
else if ($mode == 'merge_agent_ok') {
	doc_menu();
	$ag1 = rcvint('ag1');
	$ag2 = rcvint('ag2');
	$gr = rcvint('gr');
	if (($ag1 == 0) || ($ag2 == 0))
		throw new Exception("не указан ID агента!");
	if ($ag1 == $ag2)
		throw new Exception("ID агентов должны быть разные!");
	if ($ag2 < $ag1) {
		$ag = $ag1;
		$ag1 = $ag2;
		$ag2 = $ag;
	}
	$db->startTransaction();
	$res = $db->query("UPDATE `doc_list` SET `agent`='$ag1' WHERE `agent`='$ag2'");
	$af_doc = $res->affected_rows;
	$res = $db->query("UPDATE `doc_agent_dov` SET `ag_id`='$ag1' WHERE `ag_id`='$ag2'");
	$af_dov = $res->affected_rows;
	$res = $db->query("UPDATE `doc_agent` SET `name`=CONCAT('old ',`name`), `group`='$gr' WHERE `id`='$ag2'");
	$res = $db->commit();

	$tmpl->msg("Операция выполнена - обновлено $af_doc документов и $af_dov доверенных лиц", "ok");
}
else if($mode=='merge_tovar')
{
	doc_menu();
	$tmpl->addContent("<h1>Группировка складской номенклатуры</h1>
	Данная функция перепривязывает всю номенклатуру в документах и комплектующих от объекта с большим ID на объект с меньшим ID. После этого, имя объекта с большим ID получает префикс old, и объекта перемещается в указанную группу.<h2 style='color: #f00'>ВНИМАНИЕ! Данное действие необратимо, и может привести к ошибкам в документах и на складе! Перед выполнением убедитесь в том, что Вы осознаёте, что делаете, и что у Вас есть резервная копия базы данных! После выполнения действия ОБЯЗАТЕЛЬНО выполнить процедуру оптимизации, иначе остатки на складе будут неверны!</h2>
	<form method='post'><input type='hidden' name='mode' value='merge_tovar_ok'>
	<fieldset><legend>Данные, необходимые для объединения</legend>
	ID первого объекта:<br><input type='text' name='tov1'><br>
	ID второго объекта:<br><input type='text' name='tov2'><br>
	Группа для перемещения:<br><select name='gr'>");
	$res = $db->query("SELECT `id`, `name` FROM `doc_group` ORDER BY `name`");
	while($nxt=$res->fetch_row())
		$tmpl->addContent("<option value='$nxt[0]'>".html_out($nxt[1])." (id:$nxt[0])</option>");
	$tmpl->addContent("</select><br><br>
	<button>Выполнить запрошенную операцию</button>
	</fieldset></form>");
}
else if($mode=='merge_tovar_ok')
{
	doc_menu();
	$tov1=rcvint('tov1');
	$tov2=rcvint('tov2');
	$gr=rcvint('gr');
	if( ($tov1==0) || ($tov2==0) )	throw new Exception("не указан ID объекта!");
	if($tov1==$tov2)		throw new Exception("ID объектов должны быть разные!");
	if($tov2<$tov1)			{$tov=$tov1;$tov1=$tov2;$tov2=$tov;}
	$db->startTransaction();
	// Меняем товары в документах
	$res=$db->query("UPDATE `doc_list_pos` SET `tovar`='$tov1' WHERE `tovar`='$tov2'");
	$af_doc=$res->affected_rows;
	// Меняем информацию в комплектующих
	$res=$db->query("UPDATE `doc_base_kompl` SET `pos_id`='$tov1' WHERE `pos_id`='$tov2'");
	$af_cb=$res->affected_rows;
	$res=$db->query("UPDATE `doc_base_kompl` SET `kompl_id`='$tov1' WHERE `kompl_id`='$tov2'");
	$af_cc=$res->affected_rows;
	$res=$db->query("UPDATE `doc_base` SET `name`=CONCAT('old ',`name`), `group`='$gr' WHERE `id`='$tov2'");

	$res=$db->commit();

	$tmpl->msg("Операция выполнена - обновлено $af_doc документов, $af_cb / $af_cc комплектующих","ok");
}
else if($mode=='doc_log')
{
	doc_menu();
	$motions=$targets=array();
	$res=$db->query("SELECT DISTINCT `motion` FROM `doc_log`");
	while($nxt=$res->fetch_row())
	{
		$nxt[0]=str_replace(':','',$nxt[0]);
		list($motions[],$targets[])=explode(' ', $nxt[0]);
	}
	$motions=array_unique($motions);
	$targets=array_unique($targets);
	$tmpl->msg("Разработка функции временно приостановлена. Функция неработоспособна.","err");
	$tmpl->addContent("<h1>Журнал изменений</h1>
	Данная функция позволяет получить информацию по изменениям в базе документов, отобранной по заданным критериям.
	<form method='post'><input type='hidden' name='mode' value=doc_log_ok'>
	<table width='100%'>
	<tr><th>Дата<th>Типы объектов<th>Действие
	<tr>
	<td>
	От: <input type=text id='id_pub_date_date' class='vDateField required' name='dt_from' value='$dt_from'><br>
	До: <input type=text id='id_pub_date_date' class='vDateField required' name='dt_to' value='$dt_to'><br>
	<td>
	<label><input type='radio' name='obj_type' value='all'>Все</label><br>
	<label><input type='radio' name='obj_type' value='sel'>Выбранные:</label><br>");
	$res=$db->query("SELECT DISTINCT `object` FROM `doc_log`");
	while($nxt=$res->fetch_row())
	{
		switch($nxt[0])
		{
			case '':	$desc='{не задан}';	break;
			case 'agent':	$desc='Агент';		break;
			case 'doc':	$desc='Документ';	break;
			case 'tovar':	$desc='Товар';		break;
			default:	$desc=$nxt[0];
		}
		$tmpl->addContent("<label><input type='checkbox' name='obj' value='agent'>$desc</label><br>");
	}
	$tmpl->addContent("
	<label><input type='radio' name='obj_type' value='def'>Свой</label><br>
	<input type='text' name='obj_name' value='$obj_name'>
	<td><label><input type='radio' name='motion' value='all'>Все</label><br>");
	foreach($motions as $id=> $val)
	{
		$tmpl->addContent("<label><input type='radio' name='motion' value='$val'>$val</label><br>");
	}


	$tmpl->addContent("</table>
	<button>Отобразить</button>
	</fieldset></form>");
}
else if($mode=='cost')
{
	doc_menu();
	$tmpl->addContent("<h1>Управление ценами</h1>");
	$res=$db->query("SELECT `id`, `name`, `type`, `value`, `vid`, `accuracy`, `direction` FROM `doc_cost`");

	$tmpl->addContent("<table><tr><th>ID<th>Наименование<th>Тип<th>Значение<th>Вид<th>Точность<th>Округление<th>Действие");
	$vidi=array('-2' => 'Интернет-цена (объём)', '-1' => 'Интернет-цена', '0' => 'Обычная', '1' => 'По умолчанию' );
	$cost_types=array('pp' => 'Процент', 'abs' => 'Абсолютная наценка', 'fix' => 'Фиксированная цена');
	$direct=array(-1=>'Вниз', 0=>'K ближайшему', 1=>'Вверх');
	while($nxt=$res->fetch_row())
	{
		$tmpl->addContent("<form><input type='hidden' name='mode' value='costs'><input type='hidden' name='n' value='$nxt[0]'>
		<tr><td>$nxt[0]<td><input type='text' name='nm' value='".html_out($nxt[1])."'>
		<td><select name='cost_type'>");
		foreach($cost_types as $id => $type)
		{
			$sel=($id==$nxt[2])?' selected':'';
			$tmpl->addContent("<option value='$id'$sel>$type</option>");
		}
		$tmpl->addContent("</select>
		<td><input type='text' name='coeff' value='$nxt[3]'>
		<td><select name='vid'>");
		foreach($vidi as $id => $vid)
		{
			$sel=$id==$nxt[4]?'selected':'';
			$tmpl->addContent("<option value='$id' $sel>$vid</option>");
		}
		$tmpl->addContent("</select>
		<td><select name='accur'>");
		for($i=-3;$i<3;$i++)
		{
			$a=sprintf("%0.2f",pow(10,$i*(-1)));
			$sel=$nxt[5]==$i?'selected':'';
			$tmpl->addContent("<option value='$i' $sel>$a</option>");
		}
		$tmpl->addContent("</select>
		<td><select name='direct'>");
		for($i=(-1);$i<2;$i++)
		{
			$sel=$nxt[6]==$i?'selected':'';
			$tmpl->addContent("<option value='$i' $sel>{$direct[$i]}</option>");
		}
		$tmpl->addContent("</select>
		<td><input type='submit' value='Сохранить'></form>");
	}
	$tmpl->addContent("<form><input type='hidden' name='mode' value='costs'><input type='hidden' name='n' value='0'>
	<tr><td>Новая<td><input type='text' name='nm' value=''>
	<td><select name='cost_type'>");
	foreach($cost_types as $id => $type)
	{
		$sel=$id=='pp'?' selected':'';
		$tmpl->addContent("<option value='$id'$sel>$type</option>");
	}
	$tmpl->addContent("</select>
	<td><input type='text' name='coeff' value=''>
	<td><select name='vid'>");
	foreach($vidi as $id => $vid)
	{
		$sel=$id==0?'selected':'';
		$tmpl->addContent("<option value='$id' $sel>$vid</option>");
	}
	$tmpl->addContent("</select>
	<td><select name='accur'>");
	for($i=-3;$i<3;$i++)
	{
		$a=sprintf("%0.2f",pow(10,$i*(-1)));
		$sel=2==$i?'selected':'';
		$tmpl->addContent("<option value='$i' $sel>$a</option>");
	}
	$tmpl->addContent("</select>
	<td><select name='direct'>");
	for($i=0;$i<3;$i++)
	{
		$sel=1==$i?'selected':'';
		$tmpl->addContent("<option value='$i' $sel>{$direct[$i]}</option>");
	}
	$tmpl->addContent("</select><td><input type='submit' value='Добавить'></form></table>
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
	doc_menu();
	$n=rcvint('n');
	$nm=request('nm');
	$cost_type=request('cost_type');
	$coeff=rcvrounded('coeff');
	$accur=rcvint('accur');
	$direct=rcvint('direct');
	$vid=rcvint('vid');

	$name_sql=$db->real_escape_string($nm);
	$cost_type_sql=$db->real_escape_string($cost_type);
	if($n)
	{
		if(!isAccess('doc_service','edit'))	throw new AccessException("Нет доступа к странице");
		$res=$db->query("UPDATE `doc_cost` SET `name`='$name_sql', `type`='$cost_type_sql', `value`='$coeff', `vid`='$vid', `accuracy`='$accur', `direction`='$direct' WHERE `id`='$n'");
	}
	else
	{
		if(!isAccess('doc_service','edit'))	throw new AccessException("Нет доступа к странице");
		$res=$db->query("INSERT INTO `doc_cost` (`name`, `type`, `value`, `vid`, `accuracy`, `direction`) VALUES ('$name_sql', '$cost_type_sql', '$coeff', '$vid', '$accur', '$direct')");
	}
	header("Location: doc_service.php?mode=cost");
}
else if($mode=='firm')
{
	$tmpl->addContent("<h1>Настройки фирм</h1>
	<form action='' method='post'>
	<input type='hidden' name='mode' value='firme'>
	Выберите фирму:<br>
	<select name='firm_id'>");
	$res=$db->query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `firm_name`");
	while($nx=$res->fetch_row())
	{
		$tmpl->addContent("<option value='$nx[0]'>".html_out($nx[1])."</option>");
	}
	$tmpl->addContent("<option value='0'>--Создаьть новую--</option></select><br>
	<input type='submit' value='Далее'>
	</form>");
}
else if($mode=='firme') {
	$tmpl->setTitle("Настройки фирмы");
	$tmpl->addStyle("input.dw{width:300px;}");
	$firm_id = rcvint('firm_id');
	if($firm_id) {
		$res = $db->query("SELECT * FROM `doc_vars` WHERE `id`='$firm_id'");
		$values = $res->fetch_assoc();
	}
	else {
		$res = $db->query("SELECT * FROM `doc_vars` LIMIT 1");
		$values = $res->fetch_assoc();
		foreach($values as $id => $value)
			$values[$id]='';
	}

	$tmpl->addContent("
	<form action='doc_service.php' method='post'>
	<input type='hidden' name='mode' value='firms'>
	<input type='hidden' name='firm_id' value='$firm_id'>");
	foreach($values as $id => $value) {
		if($id=='id') continue;
		$tmpl->addContent("$id<br><input type='text' class='dw' name='v[$id]' value='$value'><br>");
	}
	$tmpl->addContent("<input type='submit' value='Сохранить'></form>");

}
else if($mode=='firms')
{
	$firm_id=rcvint('firm_id');

	if($firm_id)
	{
		$ss='';
		foreach($_REQUEST['v'] as $id => $value)
		{
			if($id=='id')	continue;
			$sql_id=$db->real_escape_string($id);
			$sql_value=$db->real_escape_string($value);
			if($ss)	$ss.=', ';
			$ss.="`$id`='$value'";
		}
		$ss="UPDATE `doc_vars` SET $ss WHERE `id`='$firm_id'";
	}
	else
	{
		$s1=$s2='';
		foreach($_REQUEST['v'] as $id => $value)
		{
			if($id=='id')	continue;
			$sql_id=$db->real_escape_string($id);
			$sql_value=$db->real_escape_string($value);
			if($s1)
			{
				$s1.=', ';
				$s2.=', ';
			}
			$s1="`$id`";
			$s2="'$value'";
		}
		$ss="INSERT INTO `doc_vars` ($s1) VALUES ($s2)";

	}
	$res=$db->query($ss);
	$tmpl->msg("Данные сохранены!","ok");
}
else if($mode=='vrasx')
{
	$tmpl->addContent("<h1>Редактор видов расходов</h1>");

	if(isset($_REQUEST['opt']))
	{
		if(!isAccess('doc_service','edit'))	throw new AccessException("Недостаточно привилегий!");
		$res=$db->query("SELECT `id`, `name`, `adm` FROM `doc_rasxodi` ORDER BY `id`");
		while($nxt=$res->fetch_row()) {
			$name=request('nm'.$nxt[0]);
			$adm=rcvint('ch'.$nxt[0]);
			$name_sql=$db->real_escape_string($name);
			if( ($name!=$nxt[1]) || ($adm!=$nxt[2]))
			$res=$db->query("UPDATE `doc_rasxodi` SET `name`='$name_sql', `adm`='$adm' WHERE `id`='$nxt[0]'");
		}
		$name=request('nm_new');
		$adm=rcvint('ch_new');
		if($name)
		{
			$res=$db->query("INSERT INTO `doc_rasxodi` (`name`, `adm`) VALUES ('$name', '$adm')");
		}
		$tmpl->msg("Информация обновлена!");
	}

	$res=$db->query("SELECT `id`, `name`, `adm` FROM `doc_rasxodi` ORDER BY `id`");
	$tmpl->addContent("<form action='' method='post'>
	<input type='hidden' name='mode' value='vrasx'>
	<input type='hidden' name='opt'  value='save'>
	<table class='list'>
	<tr><th>ID<th>Наименование<th>Административный");
	$i=0;
	while($nxt=$res->fetch_row())
	{
		$checked=$nxt[2]?'checked':'';
		$tmpl->addContent("<tr class='lin$i'><td>$nxt[0]<td><input type='text' name='nm$nxt[0]' value='$nxt[1]' style='width: 400px;'><td><label><input type='checkbox' name='ch$nxt[0]' $checked value='1'> Да</label>");
		$i=1-$i;
	}
	$tmpl->addContent("<tr><td>Новый<td><input type='text' name='nm_new' value='' style='width: 400px;'><td><label><input type='checkbox' name='ch_new' value='1'> Да</label>");

	$tmpl->addContent("</table>
	<button type='submit'>Записать</button>
	</form>");
}
else if($mode=='store') {
	doc_menu();
	if (request('opt')) {
		if (!isAccess('doc_service', 'edit'))	throw new AccessException();
		$res = $db->query("SELECT `id`, `name`, `dnc` FROM `doc_sklady`");
		while ($nxt = $res->fetch_row()) {
			if (!isset($_POST['sname'][$nxt[0]]))	continue;
			$name = $db->real_escape_string($_POST['sname'][$nxt[0]]);
			$dnc = isset($_POST['dnc'][$nxt[0]]) ? 1 : 0;
			$desc = '';
			if ($_POST['sname'][$nxt[0]] != $nxt[1])
				$desc.="name:(" . $db->real_escape_string($nxt[1]) . " => $name), ";
			if ($dnc != $nxt[2])	$desc.="dnc: ($nxt[2] => $dnc)";
			if ($desc == '')	continue;

			$db->query("UPDATE `doc_sklady` SET `name`='$name', `dnc`='$dnc' WHERE `id`='$nxt[0]'");
			doc_log('UPDATE', $desc, 'sklad', $nxt[0]);
		}
		$tmpl->msg("Данные обновлены","ok");
	}
	
	$tmpl->addContent("<h1>Редактор складов</h1>
	<form action='' method='post'>
	<input type='hidden' name='mode' value='store'>
	<input type='hidden' name='opt' value='save'>
	<table><tr><th>N</th><th>Наименование</th><th>Не контролировать остатки</th></tr>");
	$res=$db->query("SELECT `id`, `name`, `dnc` FROM `doc_sklady` ORDER BY `id`");
	while($line=$res->fetch_row()) {
		$c=$line[2]?'checked':'';
		$tmpl->addContent("<tr><td>$line[0]</td><td><input type='text' name='sname[$line[0]]' value='$line[1]'></td><td><input type='checkbox' name='dnc[$line[0]]' value='1' $c></td></tr>");
	}
	$tmpl->addContent("<tr><td>Новый</td><td><input type='text' name='sname[0]' value=''></td><td><input type='checkbox' name='dnc[0]' value='1'></td></tr>
	</table>
	<button>Сохранить</button>
	</form>");
}
else if($mode=='params') {
	$opt = request('opt');
	$cur_group = rcvint('group', 1);
	$types = array('text' => 'Текстовый', 'int' => 'Целый', 'bool' => 'Логический', 'float' => 'С плавающей точкой');
	doc_menu();
	$tmpl->addContent("<h1 id='page-title'>Настройки параметров складской номенклатуры</h1>");
	if ($opt == 'newg') {
		if (!isAccess('doc_service', 'edit'))	throw new AccessException();
		if (isset($_POST['name'])) {
			$name = $db->real_escape_string($_POST['name']);
			if (strlen($name) > 0) {
				$res = $db->query("INSERT INTO `doc_base_gparams` (`name`) VALUES ('$name')");
				$cur_group = $db->insert_id;
				$newg = 1;
			}
		}
		if ($newg)	$tmpl->msg("Группа создана", "ok");
	}
	if ($opt == 'save') {
		if (!isAccess('doc_service', 'edit'))	throw new AccessException();
		$res = $db->query("SELECT `id`, `param`, `type`, `pgroup_id` FROM `doc_base_params` WHERE `pgroup_id`='$cur_group'");
		$save = $newg = $newp = 0;
		while ($nxt = $res->fetch_row()) {
			$param = $db->real_escape_string($_POST['param'][$nxt[0]]);
			$type = $db->real_escape_string($_POST['type'][$nxt[0]]);
			if (!array_key_exists($type, $types)) {
				echo "id: $nxt[0], $type: $type<br>";
				$type = 'text';
			}
			$desc = '';
			if ($_POST['param'][$nxt[0]] != $nxt[1])
				$desc.="param:(" . $db->real_escape_string($nxt[1]) . " => $param), ";
			if ($type != $nxt[2])
				$desc.="type: ($nxt[2] => $type)";
			if ($desc == '')
				continue;
			$save = 1;
			$res = $db->query("UPDATE `doc_base_params` SET `param`='$param', `type`='$type' WHERE `id`='$nxt[0]'");
			doc_log('UPDATE', $desc, 'base_params', $nxt[0]);
		}
		if ($save)
			$tmpl->msg("Данные обновлены", "ok");

		$param = $db->real_escape_string($_POST['param'][0]);
		if (strlen($param) > 0) {
			$type = $db->real_escape_string($_POST['type'][0]);
			if (!array_key_exists($type, $types))
				$type = 'text';

			$res = $db->query("INSERT INTO `doc_base_params` (`param`, `type`, `pgroup_id`) VALUES ('$param', '$type', '$cur_group')");
			doc_log('INSERT', "param: $param, type: $type", 'base_params', $cur_group);
			$tmpl->msg("Параметр создан", "ok");
		}
	}

	$tmpl->addStyle("
div.tabeditor
{
	border:	#00c solid 1px;
}

div.tabeditor form
{
	margin: 0;
	padding:0;
}

div.group_menu
{
	background-color:	#66f;
	width:			300px;
	float:			left;
}

div.group_item
{
	height:			25px;
	border-bottom:		1px solid #fff;
	font-size:		18px;
	color:			#fff;
	text-align:		right;
	padding:		2px 10px 2px 10px;
	cursor:			pointer;
}

div.group_content
{
	margin-left:		310px;
	display:		none;
}

div.clear
{
	clear:			both;
}
");



	$tmpl->addContent("<div class='tabeditor'><div class='group_menu' onclick='menuclick(event)'>");
	$rgroups=$db->query("SELECT `id`, `name` FROM `doc_base_gparams` ORDER BY `name`");
	$content='';
	while($group = $res->fetch_row()) {
		if($group[0] == $cur_group) {
			$gi="style='background-color: #fff; color: #66f;'";
			$gc="style='display: block;'";
		}
		else $gi=$gc='';
		$tmpl->addContent("<div class='group_item' id='g{$group[0]}' $gi>$group[1]</div>");
		$content.="<div class='group_content' id='g{$group[0]}c' $gc>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='params'>
		<input type='hidden' name='opt' value='save'>
		<input type='hidden' name='group' value='{$group[0]}'>
		<table><tr><th>ID</th><th>Название</th><th>Тип данных</th><th>Ассоциация с Яндекс.Маркет</th></tr>";
		$rparams=$db->query("SELECT `id`, `param`, `type`, `pgroup_id`, `ym_assign` FROM  `doc_base_params` WHERE `pgroup_id`='$group[0]' ORDER BY `param`");
		while($param = $db->fetch_row()) {
			$content.="<tr><td>$param[0]:</td>
			<td><input type='text' name='param[$param[0]]' value='$param[1]'></td>
			<td><select name='type[$param[0]]'>";
			foreach($types as $t=>$n)
			{
				$sel=$param[2]==$t?'selected':'';
				$content.="<option value='$t' $sel>$n</option>";
			}
			$content.="</select></td>
			<td><input type='text' name='ym[$param[0]]' value='$param[4]' style='width:400px'></td></tr>";
		}
		$content.="<tr><td><b>+</b></td>
		<td><input type='text' name='param[0]' value=''></td>
		<td><select name='type[0]'>";
		foreach($types as $t=>$n)
		{
			$content.="<option value='$t'>$n</option>";
		}
		$content.="</select></td><td><input type='text' name='ym[0]' value='' style='width:400px'></td></tr></table>
		<button type='submit'>Сохранить</button></form></div>";
	}
	$tmpl->addContent("
	<div class='group_item'>
	<form action='' method='post'>
	<input type='hidden' name='mode' value='params'>
	<input type='hidden' name='opt' value='newg'>
	+ <input type='text' name='name' value=''><button type='submit'>&gt;&gt;</button>
	</form>
	</div>
	</div>
	$content
	<div class='clear'></div>
	</div>
	<script type='text/javascript'>
	var old_item=0;
	var old_cont=0;
	if($cur_group)
	{
		old_item=document.getElementById('g'+$cur_group)
		old_cont=document.getElementById('g'+$cur_group+'c')
	}
	function menuclick(event)
	{
		if(event.target.className!='group_item')	return;
		if(old_item)
		{
			old_item.style.backgroundColor='';
			old_item.style.color='';
		}
		event.target.style.backgroundColor='#fff';
		event.target.style.color='#66f';
		old_item=event.target
		var cont=document.getElementById(event.target.id+'c')

		if(cont)
		{
			if(old_cont)	old_cont.style.display='none'
			cont.style.display='block'
			old_cont=cont
		}
	}

	</script>
	");

}
else if($mode=='param_collections')
{
	$opt=request('opt');
	doc_menu();
	if($opt=='save')
	{
		if(!isAccess('doc_service','edit'))	throw new AccessException("Недостаточно привилегий!");
 		$newp=$newc=0;

		if(isset($_POST['name']))
		{
			$name=$db->real_escape_string($_POST['name']);
			if(strlen($name)>0)
			{
				$res=$db->query("INSERT INTO `doc_base_pcollections_list` (`name`) VALUES ('$name')");
				$collection_id=$db->insert_id;
				doc_log('CREATE', "name: $name", 'base_pcollections', $collection_id);
				$newc=1;
			}
		}
		if($newc)	$tmpl->msg("Набор создан","ok");

		if(isset($_POST['add']))
		if(is_array($_POST['add']))
		{
			foreach($_POST['add'] as $collection_id => $param_id)
			{
				settype($collection_id,'int');
				settype($param_id,'int');
				if($param_id<1)	continue;

				$res=$db->query("INSERT INTO `doc_base_pcollections_set` (`param_id`, `collection_id`) VALUES ('$param_id', '$collection_id')");
				doc_log('INSERT',"param_id: $param_id",'base_pcollections',$collection_id);
				$newp=1;
			}
		}
		if($newp)	$tmpl->msg("Параметр добавлен","ok");
		if((!$newc) && (!$newp))	$tmpl->msg("Ничего не изменено!","info");
	}
	else if($opt=='del')
	{
		if(!isAccess('doc_service','delete'))	throw new AccessException("Недостаточно привилегий!");
		$p=rcvint('p');
		$c=rcvint('c');
		$res=$db->query("DELETE FROM `doc_base_pcollections_set` WHERE `param_id`='$p' AND `collection_id`='$c'");
		$tmpl->msg("Параметр удалён","ok");
	}

	$tmpl->addContent("<h1>Настройки наборов свойств складской номенклатуры</h1>");
	$tmpl->addContent("<form action='' method='post'>
	<input type='hidden' name='mode' value='param_collections'>
	<input type='hidden' name='opt' value='save'>");
	$rgroups=$db->query("SELECT `id`, `name` FROM `doc_base_pcollections_list` ORDER BY `name`");
	while($group=$rgroups->fetch_row()) {
		$tmpl->addContent("<fieldset><legend>$group[1]</legend><table>");
		$rparams=$db->query("SELECT `doc_base_pcollections_set`.`param_id`, `doc_base_params`.`param`
		FROM `doc_base_pcollections_set`
		INNER JOIN `doc_base_params` ON `doc_base_params`.`id`=`doc_base_pcollections_set`.`param_id`
		WHERE `collection_id`='$group[0]'");
		while($param = $rparams->fetch_row())
		{
			$tmpl->addContent("<tr><td><a href='/doc_service.php?mode=param_collections&amp;opt=del&amp;p=$param[0]&amp;c=$group[0]'><img alt='Удалить' src='/img/i_del.png'></a></td><td>$param[1]</td></tr>");
		}
		$tmpl->addContent("<tr><td><b>+</b></td><td><select name='add[$group[0]]'>
		<option value='0' selected>--не выбрано--</option>");
		$res_group=$db->query("SELECT `id`, `name` FROM `doc_base_gparams` ORDER BY `name`");
		while($group = $res_group->fetch_row())	{
			$tmpl->addContent("<option value='-1' disabled>$group[1]</option>");
			$res=$db->query("SELECT `id`, `param` FROM `doc_base_params` WHERE `pgroup_id`='$group[0]' ORDER BY `param`");
			while($param=$res->fetch_row())	{
				$tmpl->addContent("<option value='$param[0]'>- $param[1]</option>");
			}
		}
		$tmpl->addContent("</select></td></tr>");
		$tmpl->addContent("</table></fieldset>");
	}

	$tmpl->addContent("Новый набор: <input type='text' name='name' value=''><br>");
	$tmpl->addContent("<button>Сохранить</button></form>");
}
else if($mode=='auinfo')
{
	$dt_apply = strtotime(rcvdate('dt_apply', date("Y-m-d",time()-60*60*24*31)));
	$dt_update = strtotime(rcvdate('dt_update',date("Y-m-d",time()-60*60*24*31)));
	$print_dt_apply = date('Y-m-d', $dt_apply);
	$print_dt_update = date('Y-m-d', $dt_update);
	$ndd = rcvint('ndd');
	$ndd_check=$ndd?'checked':'';
	doc_menu();
	$tmpl->addContent("<h1 id='page-title'>Информация по документам, изменённым после проведения</h1>
	<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
	<form action='' method='post'>
	<input type='hidden' name='mode' value='auinfo'>
	Проведен не ранее: <input type=text id='dt_apply' name='dt_apply' value='$print_dt_apply'><br>
	Изменён не ранее: <input type=text id='dt_update' name='dt_update' value='$print_dt_update'><br>
	<label><input type='checkbox' name='ndd' value='1' $ndd_check>Не показывать правки, сделанные в день проведения</label><br>
	<button type='submit'>Отобразить</button>
	</form>

	<script type=\"text/javascript\">
	function dtinit()
	{
		initCalendar('dt_apply',false)
		initCalendar('dt_update',false)
	}

	addEventListener('load',dtinit,false)
	</script>
	<table class='list'>
	<tr><th rowspan='2'>ID док.</th><th rowspan='2'>Название</th><th colspan='2'>Проведен до изменения</th><th rowspan='2'>Кто правил</th><th colspan='2'>Последняя правка</th><th rowspan='2'>Окончательно проведён</th></tr>
	<tr><th>Когда</th><th>Кем</th><th>Когда</th><th>Кто</th></tr>");

	$res=$db->query("SELECT `doc_log`.`object_id` AS `doc_id`, `doc_log`.`time`, `doc_log`.`user`, `users`.`name` AS `user_name`, `doc_list`.`ok`, `doc_types`.`name` AS `doc_type`
	FROM `doc_log`
	LEFT JOIN `users` ON `users`.`id`=`doc_log`.`user`
	LEFT JOIN `doc_list` ON `doc_list`.`id`=`doc_log`.`object_id`
	LEFT JOIN `doc_types` ON `doc_list`.`type`=`doc_types`.`id`
	WHERE `doc_log`.`object`='doc' AND `time`>='$print_dt_apply 00:00:00' AND `motion` LIKE 'APPLY%'
	ORDER BY `doc_log`.`time`");
	$docs=array();
	while($nxt=$res->fetch_assoc()) {
		if(in_array($nxt['doc_id'],$docs))	continue;
		$update_res=$db->query("SELECT `doc_log`.`object_id` AS `doc_id`, `doc_log`.`time`, `doc_log`.`user`, `users`.`name` AS `user_name`, `doc_log`.`desc`
		FROM `doc_log`
		LEFT JOIN `users` ON `users`.`id`=`doc_log`.`user`
		WHERE `doc_log`.`object`='doc' AND `doc_log`.`object_id`='{$nxt['doc_id']}' AND `time`>='$print_dt_update 00:00:00' AND `time`>='{$nxt['time']}' AND `motion` LIKE 'UPDATE%'");
		if($update_res->num_rows==0)	continue;
		else {
			$c_users=array();
			$lastchange=$lastuser=$lastdesc='';
			$datec=date('Y-m-d',strtotime($nxt['time']));
			while($updates = $update_res->fetch_array())
			{
				if($ndd)	if(date('Y-m-d',strtotime($updates['time']))==$datec)	continue;

				$c_users[$updates['user_name']]=1;
				$lastchange=$updates['time'];
				$lastuser=$updates['user_name'];
				$lastdesc=$updates['desc'];
			}
			if($lastchange=='')	continue;
			$users='';
			foreach($c_users as $user => $v)
			{
				$users.=$user.', ';
			}
			if($nxt['ok'])	$a_date=date("Y-m-d H:i:s",$nxt['ok']);
			else		$a_date='не проведён';
			$tmpl->addContent("<tr><td><a href='/doc.php?mode=body&amp;doc={$nxt['doc_id']}'>{$nxt['doc_id']}</a></td><td>{$nxt['doc_type']}</td><td>{$nxt['time']}</td><td>{$nxt['user_name']}</td><td>$users</td><td>$lastchange</td><td>$lastuser</td><td>$a_date</td></tr>");
 		}
 		$docs[]=$nxt['doc_id'];
	}

	$tmpl->addContent("</table>");
}
else if($mode=='pcinfo')
{
	doc_menu();
	$from=request('from', '1970-01-01');
	$to=request('to', date('Y-m-d'));
	$tmpl->addContent("<h1 id='page-title'>Информация по изменениям в номенклатуре</h1>
	<form method='get' action=''>
	<input type='hidden' name='mode' value='pcinfo'>
	С: <input type='text' name='from' value='$from'><br>
	По: <input type='text' name='to' value='$to'><br>
	<button>Показать</button>
	</form>
	<table class='list'><tr><th>Пользователь</th><th>Затраченное время</th><th>Отредактировано наименований</th></tr>");
	$res=$db->query("SELECT `id`, `name` FROM `users` ORDER BY `name`");
	while($user_data=$res->fetch_row())
	{
		$oldtime=$totaltime=0;
		$pos=array();
		$res_log=$db->query("SELECT `time`, `object_id` FROM `doc_log` WHERE `user`='{$user_data[0]}' AND `object`='pos' AND `time`>='$from' AND `time`<='$to' ORDER BY `time`");
		while($logline=$res_log->fetch_row())
		{
			$curtime=strtotime($logline[0]);
			if($curtime<=($oldtime+60*15))	$totaltime+=$curtime-$oldtime;
			else				$totaltime+=180;	// по 180 сек на наименование, если оно первое или единственное в серии редактирования
			$oldtime=$curtime;
			$pos[$logline[1]]=1;
		}
		if(!$totaltime)	continue;
		$ptotaltime=sectostrinterval($totaltime);
		$poscnt=count($pos);
		$tmpl->addContent("<tr><td>{$user_data[1]}</td><td>$ptotaltime</td><td>$poscnt</td></tr>");
	}
	$tmpl->addContent("</table>");
}
else throw new NotFoundException("Несуществующая опция");
}
catch(mysqli_sql_exception $e) {
	$tmpl->ajax=0;
	$id = $tmpl->logger($e->getMessage(), 1);
	$tmpl->msg("Порядковый номер ошибки: $id<br>Сообщение передано администратору", 'err', "Ошибка в базе данных");
}
catch(AccessException $e) {
	$tmpl->msg($e->getMessage(),"err","У Вас недостаточно привилегий!");
}
catch(Exception $e) {
	$db->rollback();
	$tmpl->msg($e->getMessage(),'err','Ошибка выполнения операции');
}

$tmpl->write();
?>
