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

include_once('core.php');
include_once('include/doc.core.php');

set_time_limit(120);

need_auth();

SafeLoadTemplate($CONFIG['site']['inner_skin']);

$tmpl->HideBlock('left');

$firm_id=0;
$num_name=1;
$num_cost=2;
$num_art=3;
$line_cnt=0;

$line = array();
$line_pos = 0;

function topmenu($s='')
{
	global $tmpl;
	if(!$tmpl->ajax)
	{
		doc_menu($s,0);
	}
}

function draw_groups_tree($level, $firm)
{
	$ret='';
	$res=mysql_query("SELECT `doc_group`.`id`, `doc_group`.`name`, `firm_info_group`.`id` FROM `doc_group`
	LEFT JOIN `firm_info_group`	ON `firm_info_group`.`firm_id`='$firm' AND `firm_info_group`.`group_id`=`doc_group`.`id`
	WHERE `doc_group`.`pid`='$level' ORDER BY `doc_group`.`name`");
	$i=0;
	$r='';
	if($level==0) $r='IsRoot';
	$cnt=mysql_num_rows($res);
	while($nxt=mysql_fetch_row($res))
	{
		if($nxt[0]==0) continue;
		$checked=$nxt[2]?'checked':'';
		$item="<label><input type='checkbox' name='g[]' value='$nxt[0]' id='cb$nxt[0]' class='cb' $checked onclick='CheckCheck($nxt[0])'>$nxt[1]</label>";
		if($i>=($cnt-1)) $r.=" IsLast";
		$tmp=draw_groups_tree($nxt[0], $firm); // рекурсия
		if($tmp)
			$ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div><ul class='Container' id='cont$nxt[0]'>".$tmp.'</ul></li>';
        	else
        		$ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div></li>";
		$i++;
	}
	return $ret;
}


function firmAddForm($id=0)
{
	global $tmpl;
	$nxt=array();
	if($id)
	{
		$res=mysql_query("SELECT `id`, `name`, `signature`, `currency`, `coeff`, `type`, `delivery_info` FROM `firm_info` WHERE `id`='$id'");
		if(mysql_errno())	throw new MysqlException("Не удалось выбрать данные фирмы");
		$nxt=mysql_fetch_row($res);
	}

	$disp=$nxt[5]==2?'block':'none';

	$tmpl->AddStyle(".scroll_block
	{
		max-height:		250px;
		overflow:		auto;
	}

	div#sb
	{
		display:		$disp;
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

	$tmpl->AddText("<h1>Данные фирмы</h1>
	<form action='' method='post'>
	<input type='hidden' name=mode value='firms'>");
	if($id) $tmpl->AddText("<input type=hidden name=id value='$nxt[0]'>");
	$tmpl->AddText("Наименование:<br>
	<input type='text' name='nm' value='$nxt[1]'><br>
	Сигнатура:<br>
	<input type='text' name='sign' value='$nxt[2]'><br>
	Валюта:<br>
	<select name='curr'>");
	$res=mysql_query("SELECT `id`, `name`, `coeff` FROM `currency` ORDER BY `id`");
	while($nx=mysql_fetch_row($res))
	{
		if($nx[0]==$nxt[3])
			$tmpl->AddText("<option style='background-color: #8f8;' selected value='$nx[0]'>$nx[1]</option>");
		else
			$tmpl->AddText("<option value='$nx[0]'>$nx[1]</option>");
	}

	$typesel=array( 0=>'', 1=>'', 2=>'');
	$typesel[$nxt[5]]='selected';

	$tmpl->AddText("</select><br>
	Валютный коэффициент:<br>
	<input type='text' name='coeff' value='$nxt[4]'><br>
	Информация о доставке:<br>
	<input type='text' name='delivery_info' value='$nxt[6]'><br>

	<script type='text/javascript'>
	function gstoggle()
	{
		var seltype=document.getElementById('seltype').value;
		if(seltype=='2')
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
		//alert(cb.checked);
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

	function FillTextBoxes(l_id, t_name, c_art, c_name, c_cost, c_nal, c_curr, c_info)
	{
		document.getElementById('line_id').value=l_id;
		document.getElementById('table_name').value=t_name;
		document.getElementById('col_art').value=c_art;
		document.getElementById('col_name').value=c_name;
		document.getElementById('col_cost').value=c_cost;
		document.getElementById('col_nal').value=c_nal;
		document.getElementById('col_curr').value=c_curr;
		document.getElementById('col_info').value=c_info;
	}

	</script><br>
	Результаты анализа:<br>
	<select name='type' id='seltype' onchange='gstoggle()'>
	<option value='0' $typesel[0]>Не меняют цены</option>
	<option value='1' $typesel[1]>Меняют все цены</option>
	<option value='2' $typesel[2]>Меняют цены выбранных групп товаров</option>
	</select><br>


	<div class='scroll_block' id='sb'>
	<ul class='Container'>
	<div class='selmenu'><a onclick='SelAll(true)'>Выбрать всё<a> | <a onclick='SelAll(false)'>Снять всё</a></div>
	".draw_groups_tree(0,$id)."</ul>");



	$tmpl->AddText("</div>");
	if(!$nxt)
	{
		$tmpl->AddText("<h2>Структура прайса</h2>
		<table>
		<thead>Номера колонок
		<tr><th>Имя листа<th>С кодом производителя<th>С названиями<th>С ценами<th>С наличием
		<tr><td><input type='text' name='table_name'>
		<td><input type='text' name='col_art'>
		<td><input type='text' name='col_name'>
		<td><input type='text' name='col_cost'>
		<td><input type='text' name='col_nal'>
		<td><input type='text' name='col_curr'>
		<td><input type='text' name='col_info'>
		</table>");

	}
	$tmpl->AddText("<input type=submit value='Записать!'></form>");
	if($nxt)
	{
		$tmpl->AddText("<h2>Структура прайса</h2>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='firmss'>
		<input type='hidden' name='firm_id' value='$nxt[0]'>
		<input type='hidden' name='line_id' value='0' id='line_id'>
		<table>
		<tr><th rowspan='2'>Имя листа<th colspan='6'>Номера колонок
		<tr><th>С кодом производителя<th>С названиями<th>С ценами<th>С наличием<th>С валютой<th>С информацией");
		$res=mysql_query("SELECT `id`, `table_name`, `art`, `name`, `cost`, `nal`, `currency`, `info` FROM `firm_info_struct`
		WHERE `firm_id`='$nxt[0]'");
		if(mysql_errno())	throw new MysqlException("не удалось получить настройки столбцов!");
		while($nx=mysql_fetch_row($res))
		{
			$tmpl->AddText("<tr><td>
			<a href='?mode=firmsd&p=$nx[0]'><img src='/img/i_del.png' alt='Удалить'></a>
			<a onclick=\"FillTextBoxes('$nx[0]', '$nx[1]', '$nx[2]', '$nx[3]', '$nx[4]', '$nx[5]', '$nx[6]', '$nx[7]');\"><img src='/img/i_edit.png'  alt='Правка'></a>
			$nx[1]<td>$nx[2]<td>$nx[3]<td>$nx[4]<td>$nx[5]<td>$nx[6]<td>$nx[7]");
		}
		$tmpl->AddText("<tr><th colspan='7'>Новый лист<tr>
		<td><input type='text' name='table_name' id='table_name'>
		<td><input type='text' name='col_art' id='col_art'>
		<td><input type='text' name='col_name' id='col_name'>
		<td><input type='text' name='col_cost' id='col_cost'>
		<td><input type='text' name='col_nal' id='col_nal'>
		<td><input type='text' name='col_curr' id='col_curr'>
		<td><input type='text' name='col_info' id='col_info'>
		</table>
		<input type=submit value='Записать!'></form>");

	}
}

try
{

if(!isAccess('generic_price_an','view'))	throw new AccessException("Недостаточно привилегий");
topmenu();
$tmpl->SetTitle("Анализатор прайсов");
if($mode=='')
{
	$i=0;
	$tmpl->AddText("
	<h1>Редактор организаций</h1>
	<table width='100%'>
	<tr><th>ID<th>Наименование<th>Сигнатура<th>Валюта<th>Дата обновления<th>Отчёты");
	$res=mysql_query("SELECT `firm_info`.`id`, `firm_info`.`name`, `firm_info`.`signature`, `currency`.`name`, `firm_info`.`coeff`, `firm_info`.`last_update`  FROM `firm_info`
	LEFT JOIN `currency` ON `currency`.`id`=`firm_info`.`currency`
	ORDER BY `firm_info`.`last_update` DESC");
	echo mysql_error();
	while($nxt=mysql_fetch_row($res))
	{
		$tmpl->AddText("<tr class='lin$i'><td><a href='?mode=firme&amp;id=$nxt[0]'>$nxt[0]</a>
		<td>$nxt[1]<td>$nxt[2]<td>$nxt[3], $nxt[4]<td>$nxt[5]<td>
		<a href='?mode=r_noparsed&amp;f=$nxt[0]'>Необработанные</a> |
		<a href='?mode=r_parsed&amp;f=$nxt[0]'>Обработанные</a> |
		<a href='?mode=r_multiparsed&amp;f=$nxt[0]'>Дублирующиеся</a>
		");
		$i=1-$i;
	}

	$tmpl->AddText("</table>");
}
else if($mode=='load')
{
	$m_upl_size=get_max_upload_filesize();
	$tmpl->AddText("
	<form method=post enctype='multipart/form-data'>
	<input type=hidden name=mode value='parse'>
	<h1>Загрузить прайс в базу</h1>
	Файл прайса (таблица ODF или XLS, до $m_upl_size байт, файл должен иметь корректное расширение)<br>
	<input type='hidden' name='MAX_FILE_SIZE' value='$m_upl_size'>
	<input name='file' type='file'><br>
	Организация будет выбрана автоматически на основе списка сигнатур. Если организации нет в списке, Вам будет предложено её добавить.<br>
	<label><input type='checkbox' name='bhtml' value='1'>Показать загруженные таблицы</label><br>
	<input type=submit value='Загрузить'>
	</form>
	<p><b>Важно!</b> Загруженный прайс заменит уже существующую информацию в базе по соответствующей организации. Загрузка будет выполнена немедленно, но проанализированны данные будут при следующем запуске анализатора (обычно в течение одного часа).</p>");
}
else if($mode=="parse")
{
	$bhtml=rcv('bhtml');
	if(!is_uploaded_file($_FILES['file']['tmp_name']))
		throw new Exception("Файл не получен. Возможно, его забыли выбрать, либо он слишком большой.");
	if($_FILES['file']['size']>(4000*1024))
		throw new Exception("Слишком большой файл!");
	
	require_once($CONFIG['location']."/common/priceloader.xls.php");
	require_once($CONFIG['location']."/common/priceloader.ods.php");
	
	$path_info = pathinfo($_FILES['file']['name']);
	switch(strtolower($path_info['extension']))
	{
		case 'xls':	$loader=new XLSPriceLoader($_FILES['file']['tmp_name']);
				break;
		case 'ods':	$loader=new ODSPriceLoader($_FILES['file']['tmp_name']);
				break;
		default:	throw new Exception("Неверное расширение файла!");
	}
	
	if($firm=$loader->detectFirm())
	{
		$loader->setInsertToDatabase();
		if($bhtml)	$loader->setBuildHTMLData();
		$count=$loader->Run();
		$tmpl->msg("Успешно обработано $count строк фирмы $firm","ok");
		if($bhtml)	$tmpl->AddText("<h3>Загруженные данные:</h3>".$loader->getHTML());
	}
	else
	{
		$tmpl->msg("Фирма не определена!","info");
		if($bhtml)
		{
			$loader->setBuildHTMLData();
			$loader->Run();
			$tmpl->AddText("<h3>Загруженные данные:</h3>".$loader->getHTML());
		}

		firmAddForm();
	}
}
else if($mode=='firme')
{
	$id=rcv('id');
	firmAddForm($id);
}
else if($mode=='firms')
{
	$id=rcv('id');
	$nm=rcv('nm');
	$sign=rcv('sign');
	$curr=rcv('curr');
	$coeff=rcv('coeff');
	$type=rcv('type');
	$table_name=rcv('table_name');
	$delivery_info=rcv('delivery_info');
	if(!$id)
	{
		$col_art=rcv('col_art');
		$col_name=rcv('col_name');
		$col_cost=rcv('col_cost');
		$col_nal=rcv('col_nal');		
		$col_curr=rcv('col_curr');
		$col_info=rcv('col_info');
		$res=mysql_query("INSERT INTO `firm_info` (`name`, `signature`, `currency`, `coeff`, `type`, `delivery_info`)
		VALUES ('$nm', '$sign', '$curr', '$coeff', '$type', '$delivery_info')");
		if(mysql_errno())	throw new MysqlException("Не удалось добавить новую фирму");

		$firm_id=mysql_insert_id();
		mysql_query("INSERT INTO `firm_info_struct` (`firm_id`, `table_name`, `art`, `name`, `cost`, `nal`, `currency`, `info`)
		VALUES ('$firm_id', '$table_name', '$col_art', '$col_name', '$col_cost', '$col_nal', '$col_curr', '$col_info')");
		if(mysql_errno())	throw new MysqlException("Не удалось добавить структуру прайса");
		$tmpl->msg("Фирма добавлена!",'ok');
	}
	else
	{
		$res=mysql_query("UPDATE `firm_info` SET `name`='$nm', `signature`='$sign', `currency`='$curr', `coeff`='$coeff', `type`='$type', `delivery_info`='$delivery_info' WHERE `id`='$id'");
		if(mysql_errno())	throw new MysqlException("Не удалось обновить данные фирмы");
		$tmpl->msg("Фирма обновлена!",'ok');
	}
	if($type==2)	// Влияние цен для заданных групп товаров
	{
		$g=@$_POST['g'];
		mysql_query("DELETE FROM `firm_info_group` WHERE `firm_id`='$id'");
		if(mysql_errno())	throw new MysqlException("Не удалось удалить устаревшие данные");
		if(is_array($g))
		foreach($g as $line)
		{
			mysql_query("INSERT INTO `firm_info_group` (`firm_id`, `group_id`) VALUES ('$id', '$line')");
			if(mysql_errno())	throw new MysqlException("Не удалось обновить привязки к группам");
		}
		$tmpl->msg("Привязки к группам обновлены!",'ok');
	}
}
else if($mode=='firmss')
{
	$line_id=rcv('line_id');
	$firm_id=rcv('firm_id');
	$table_name=rcv('table_name');
	$col_art=rcv('col_art');
	$col_name=rcv('col_name');
	$col_cost=rcv('col_cost');
	$col_nal=rcv('col_nal');
	$col_curr=rcv('col_curr');
	$col_info=rcv('col_info');
	if(!$line_id)
	{
		mysql_query("INSERT INTO `firm_info_struct` (`firm_id`, `table_name`, `art`, `name`, `cost`, `nal`, `currency`, `info`)
		VALUES ('$firm_id', '$table_name', '$col_art', '$col_name', '$col_cost', '$col_nal', '$col_curr', '$col_info')");
		if(mysql_errno())	throw new MysqlException("Не удалось вставить строку");
	}
	else
	{
		mysql_query("UPDATE `firm_info_struct` SET `table_name`='$table_name', `art`='$col_art', `name`='$col_name', `cost`='$col_cost', `nal`='$col_nal', `currency`='$col_curr', `info`='$col_info' WHERE `id`='$line_id'");
		if(mysql_errno())	throw new MysqlException("Не удалось обновить данные");
		if(mysql_affected_rows()==0)	$tmpl->msg("Ничего не изменено","info");
	}

	$tmpl->msg("Операция выполнена успешно!",'ok');
}
else if($mode=='firmsd')
{
	$p=rcv('p');
	$res=mysql_query("DELETE FROM `firm_info_struct` WHERE `id`='$p'");
	if($res) $tmpl->msg("Удалено!","ok");
	else $tmpl->msg("Не удалось удалть!","err");
}
else if($mode=='viewall')
{
	$s=rcv('s');
	if($rv=rcv('rv'))
	{
		$ch=' checked';
		$ss='';
	}
	else
	{
		$ch='';
		$ss="WHERE `price`.`name` LIKE '%$s%' OR `price`.`art` LIKE '%$s%'";
	}
	$tmpl->AddText("<h3>Поиск по критерию</h3>
	<form action='' method=post>
	<input type=hidden nmae=mode value=viewall>
	Строка поиска:<br>
	<input type=text name=s value='$s'><br>
	<label><input type=checkbox name=rv value=1 $ch>Регулярное выражение</label><br>
	<input type=submit value='Выполнить отбор'>
	</form>");
	$res=mysql_query("SELECT `price`.`name`, `price`.`cost`, `price`.`art`, `firm_info`.`name`
	FROM `price`
	LEFT JOIN `firm_info` ON `firm_info`.`id`=`price`.`firm`
	$ss
	ORDER BY `price`.`name`");
	echo $ss;
	$tmpl->AddText("<table width=100%><tr><th>Наименование<th>Цена<th>Артикул<th>Фирма");
	while($nxt=mysql_fetch_row($res))
	{
		if($rv)
		{
			if(preg_match("/$s/",$nxt[0]))
			{
				$tmpl->AddText("<tr><td>$nxt[0]<td>$nxt[1]<td>$nxt[2]<td>$nxt[3]");
			}
		}
		else $tmpl->AddText("<tr><td>$nxt[0]<td>$nxt[1]<td>$nxt[2]<td>$nxt[3]");
	}
	$tmpl->AddText("</table>");
}
else if($mode=='viewsort')
{
	$tmpl->AddText("<h3>Сортированная выборка</h3>");

	$header="<tr><th>Name";
	$res=mysql_query("SELECT `name` FROM `firm_info` WHERE `id`!='0' ORDER BY `id`");
	$f_max=mysql_num_rows($res);
	while($nxt=mysql_fetch_row($res))
		$header.="<th>$nxt[0]";

	$tmpl->AddText("<table width=100%>$header");
	$res=mysql_query("SELECT `seekdata`.`name`,`seekdata`.`sql`,`seekdata`.`regex`,`seekdata`.`id`, `doc_group`.`name` FROM `seekdata`
	LEFT JOIN `doc_group` ON `doc_group`.`id`=`seekdata`.`group`
	ORDER BY `seekdata`.`name`");
	$c=0;
	while($nxt=mysql_fetch_row($res))
	{
		$costar=array();
		$rs=mysql_query("SELECT `name`,`cost`,`firm` FROM `price`
		WHERE `name` LIKE '%$nxt[1]%' ORDER BY `cost` LIMIT 1000");
		while($nx=mysql_fetch_row($rs))
		{
			if(preg_match("/$nxt[2]/",$nx[0]))
			{
				if($costar[$nx[2]])
					$costar[$nx[2]].=" / <a title='$nx[0]'>".$nx[1]."</a>";
				else
					$costar[$nx[2]]="<a title='$nx[0]'>".$nx[1]."</a>";
			}
		}
		$tmpl->AddText("<tr><td><a title='$nxt[2]' href='?mode=regve&amp;id=$nxt[3]'>$nxt[4] $nxt[0]</a>");
		for($i=1;$i<=$f_max;$i++)
			$tmpl->AddText("<td>$costar[$i]");
		$c++;
		if($c>=15)
		{
			$tmpl->AddText($header);
			$c=0;
		}
	}
	$tmpl->AddText("</div></table>");
}
else if($mode=='search')
{
	$s=rcv('s');
	$g=rcv('g');
	$tmpl->AddText("<h3>Поиск по строке</h3>
	<form action='' mode='get'>
	<input type='hidden' name='mode' value='search'>
	<input type='text' name='s' value='$s'>
	<input type='submit' value='Найти'></form></b>");
	if($s)
	{
		$tmpl->AddText("<h3>Результаты:</h3>");
		if(strlen($g)==0)
		{
			$tmpl->AddText("<h3>Интересующие Вас товары найдены в группах:</h3>");
			$res=mysql_query("SELECT `doc_group`.`id`, `doc_group`.`name` FROM `seekdata`
			LEFT JOIN `doc_group` ON `doc_group`.`id`=`seekdata`.`group`
			WHERE `seekdata`.`name` LIKE '%$s%'
			GROUP BY `seekdata`.`group`");
			while($nxt=mysql_fetch_row($res))
			{
				if($nxt[1]=='')
				{
					$nxt[1]='==Группа не указана==';
					$nxt[0]=0;
				}
				$tmpl->AddText("<a href='?mode=search&amp;s=$s&amp;g=$nxt[0]'>$nxt[1]</a><br>");
			}

		}
		else
		{
			$tmpl->AddText("<h3>Результаты в выбранной группе</h3>");
			$res=mysql_query("SELECT `seekdata`.`id`, `seekdata`.`name` FROM `seekdata`
			LEFT JOIN `doc_group` ON `doc_group`.`id`=`seekdata`.`group`
			WHERE `seekdata`.`name` LIKE '%$s%' AND `seekdata`.`group`='$g'");
			while($nxt=mysql_fetch_row($res))
			{
				$tmpl->AddText("$nxt[1]<br>");
			}


		}

	}

}
else if($mode=='regve')
{
	exit();
	$id=rcv('id');
	$nxt=array();
	$tmpl->AddText("<h3>Правка условия выборки</h3>
	<form action='' method=post>
	<input type=hidden name=mode value=regvs>");
	if($id)
	{
		$tmpl->AddText("<input type=hidden name=id value='$id'>");
		$res=mysql_query("SELECT `name`,`sql`,`regex`, `group` FROM `seekdata` WHERE `id`='$id'");
		$nxt=mysql_fetch_row($res);
	}
	$tmpl->AddText("
	Наименование:<br>
	<input type=text name=nm value='$nxt[0]'><br>
	Группа:<br>
	<select name='group'>");
	$res=mysql_query("SELECT `id`, `name` FROM `doc_group` ORDER BY `name`");
	if(!$nxt[3])
		$tmpl->AddText("<option style='background-color: #8f8;' selected disabled value='0'>--- не выбрана ---</option>");
	while($nx=mysql_fetch_row($res))
	{
		if($nx[0]==$nxt[3])
			$tmpl->AddText("<option style='background-color: #8f8;' selected value='$nx[0]'>$nx[1] ($nx[0])</option>");
		else
			$tmpl->AddText("<option value='$nx[0]'>$nx[1] ($nx[0])</option>");
	}

	$tmpl->AddText("</select>
	Строка отбора (можно использовать символ %):<br>
	<input type=text name=ss value='$nxt[1]'><br>
	Регулярное выражение поиска:<br>
	<input type=text name=rv value='$nxt[2]' id='re' onkeydown=\"DelayedSave('/priceload.php?mode=regvt','regex_text', 're'); return true;\" ><br>
	<input type=submit value='Записать'>
	</form>
	<div id='regex_text'>ss</div>");
}
else if($mode=='regvt')
{
	$tmpl->ajax=1;
	$s=@$_GET['s'];
	if($s=='')
	{
		echo"Пустой запрос!";
		exit();

	}
	//$s='/'.$s.'/';

	$costar=array();
	$rs=@mysql_query("SELECT `name`,`cost`,`firm` FROM `price`");
	$cnt=mysql_num_rows($rs);
	echo mysql_error();

	$tmpl->AddText("<h3>Результаты отбора $s ($cnt совпадений, 100 максимум):</h3>");
	$tmpl->AddText("<table width=100%><tr>");
	$res=mysql_query("SELECT `name` FROM `firm_info` WHERE `id`!='0' ORDER BY `id`");
	$f_max=mysql_num_rows($res);
	while(@$nxt=mysql_fetch_row($res))
		$tmpl->AddText("<th>$nxt[0]");

	while(@$nx=mysql_fetch_row($rs))
	{
		if($a=preg_match("/$s/",$nx[0]))
		{

			if($costar[$nx[2]])
				$costar[$nx[2]].="<hr>$nx[0] ($nx[1])";
			else
				$costar[$nx[2]]="$nx[0] ($nx[1])";
		}
		if($a===FALSE) break;
	}
	$tmpl->AddText("<tr valign=top>");
	for($i=1;$i<=$f_max;$i++)
		$tmpl->AddText("<td>$costar[$i]");

	$tmpl->AddText("</table>");
}
else if($mode=='regvs')
{
	$id=rcv('id');
	$nm=rcv('nm');
	$ss=rcv('ss');
	$g=rcv('group');
	$rv=@$_POST['rv'];
	if($id)
	{
		$res=mysql_query("UPDATE `seekdata` SET `name`='$nm', `sql`='$ss', `regex`='$rv', `group`='$g' WHERE `id`='$id'");
		if($res) $tmpl->msg("Данные обновлены!",'ok');
		else $tmpl->msg("Данные НЕ обновлены!",'err');
	}
	else
	{
		$res=mysql_query("INSERT INTO `seekdata` (`name`, `sql`, `regex`, `group`)
		VALUES ('$nm', '$ss', '$rv', '$g')");
		if($res) $tmpl->msg("Данные обновлены!",'ok');
		else $tmpl->msg("Данные НЕ обновлены!",'err');
	}

	$costar=array();
	$rs=@mysql_query("SELECT `name`,`cost`,`firm` FROM `price`
	WHERE `name` LIKE '$ss' ORDER BY `cost` LIMIT 100");
	$cnt=mysql_num_rows($rs);
	echo mysql_error();

	$tmpl->AddText("<h3>Результаты отбора $rv ($cnt совпадений, 100 максимум):</h3>");
	$tmpl->AddText("<table width=100%><tr>");
	$res=mysql_query("SELECT `name` FROM `firm_info` WHERE `id`!='0' ORDER BY `id`");
	$f_max=mysql_num_rows($res);
	while(@$nxt=mysql_fetch_row($res))
		$tmpl->AddText("<th>$nxt[0]");

	while(@$nx=mysql_fetch_row($rs))
	{
		if(preg_match("/$rv/",$nx[0]))
		{
			if($costar[$nx[2]])
				$costar[$nx[2]].="<hr>$nx[0] ($nx[1])";
			else
				$costar[$nx[2]]="$nx[0] ($nx[1])";
		}
	}
	$tmpl->AddText("<tr valign=top>");
	for($i=1;$i<=$f_max;$i++)
		$tmpl->AddText("<td>$costar[$i]");

	$tmpl->AddText("</table>");
}
else if($mode=='r_noparsed')
{
	$f=rcv('f');
	$s1=rcv('s1');
	$s2=rcv('s2');
	$s3=rcv('s3');
	$tmpl->AddText("<h1 id='page-title'>Необработанные позиции</h1>
	<div id='page-info'>Отметьте галочками нужные, и добавте на склад. Можно воспользоваться фильтром.</div>");
	if($f) 	$sql_add=" AND `price`.`firm`='$f'";
	else	$sql_add='';
	if($s1)	$sql_add.="AND (`price`.`name` LIKE '%$s1%' OR `price`.`info` LIKE '%$s1%')";
	if($s2)	$sql_add.="AND (`price`.`name` LIKE '%$s2%' OR `price`.`info` LIKE '%$s2%')";
	if($s3)	$sql_add.="AND (`price`.`name` LIKE '%$s3%' OR `price`.`info` LIKE '%$s3%')";
	$res=mysql_query("SELECT `price`.`id`, `price`.`art`, `price`.`name`, `firm_info`.`name`, `price`.`info`
	FROM `price`
	LEFT JOIN `firm_info` ON `firm_info`.`id`=`price`.`firm`
	WHERE `seeked`='0' $sql_add
	LIMIT 100000");
	if(mysql_num_rows($res))
	{
		$i=0;
		$tmpl->AddText("
		<script type='text/javascript'>

		function SelAll(flag)
		{
			var elems = document.getElementsByName('p[]');
			var l = elems.length;
			for(var i=0; i<l; i++)
			{
				elems[i].checked=flag;
				if(flag)	elems[i].disabled = false;
			}
		}

		</script>
		<form action='' method='get'>
		<input type='hidden' name='mode' value='r_noparsed'>
		<input type='hidden' name='f' value='$f'>
		<input type='text' name='s1' value='$s1'> - <input type='text' name='s2' value='$s2'> - <input type='text' name='s3' value='$s3'>
		<button>Отфильтровать</button>
		</form>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='adding'>
		<div class='selmenu'><a onclick='SelAll(true)'>Выбрать всё<a> | <a onclick='SelAll(false)'>Снять всё</a></div>
		<table width='100%'><tr><th>ID<th>Код произв.<th>Наименование<th>Инфо<th>Фирма");
		while($nxt=mysql_fetch_row($res))
		{
			$i=1-$i;
			$tmpl->AddText("<tr class='lin$i'><td><label><input type='checkbox' name='p[]' value='$nxt[0]'>$nxt[0]</label><td>$nxt[1]<td>$nxt[2]<td>$nxt[4]<td>$nxt[3]");
		}
		$tmpl->AddText("</table><button type='submit'>Далее</button></form>");
	}
	else $tmpl->msg("Необработанных позиций не обнаружено!");
}
else if($mode=='adding')
{
	$tmpl->AddText("<h1 id='page-title'>Добавление позиций на склад</h1>
	<div id='page-info'>Назовите позиции так, как они должны называться в вашем прайсе</div>");
	$p=@$_POST['p'];
	if(!is_array($p))	throw new Exception("Список позиций не получен");
	$res=mysql_query("SELECT `price`.`id`, `price`.`art`, `price`.`name`
	FROM `price`
	WHERE `seeked`='0'");
	if(mysql_errno())	throw new MysqlException("Не удалось получить данные прайсов");
	$tmpl->AddText("<form action='' method='post'>
	<input type='hidden' name='mode' value='adding_s'>
	<table width='100%'><tr><th>N<th>Код произв.<th>Оригинальное наименование<th style='width: 50%'>Наше наименование");
	$i=0;
	while($nxt=mysql_fetch_row($res))
	{
		if(!in_array($nxt[0],$p))	continue;
		$i++;
		$nxt[1]=trim($nxt[1]);
		$nxt[2]=trim($nxt[2]);
		$tmpl->AddText("<tr class='lin$i'><td>$i<td><input type='text' name='vc[$nxt[0]]' value='$nxt[1]'><td>$nxt[2]<td><input type='text' name='n[$nxt[0]]' value='$nxt[2]' style='width: 95%'>");
	}
	$tmpl->AddText("</table>Группа:<br><select name='group'>");
	$res=mysql_query("SELECT `id`, `name` FROM `doc_group`");
	while($nxt=mysql_fetch_row($res))
	{
		$tmpl->AddText("<option value='$nxt[0]'>$nxt[1] ($nxt[0])</option>");
	}
	$tmpl->AddText("</select><br>Единицы измерения:<br><select name='units'>");
	$res2=mysql_query("SELECT `id`, `name` FROM `class_unit_group` ORDER BY `id`");
	while($nx2=mysql_fetch_row($res2))
	{
		$tmpl->AddText("<option disabled style='color:#fff; background-color:#000'>$nx2[1]</option>\n");
		$res=mysql_query("SELECT `id`, `name`, `rus_name1` FROM `class_unit` WHERE `class_unit_group_id`='$nx2[0]'");
		while($nx=mysql_fetch_row($res))
		{
			$tmpl->AddText("<option value='$nx[0]'>$nx[1] ($nx[2])</option>");
		}
	}
	$tmpl->AddText("</select><br><button type='submit'>Добавить</button></form>");
}
else if($mode=='adding_s')
{
	$group=rcv('group');
	$units=rcv('units');
	$tmpl->AddText("<h1 id='page-title'>Добавление позиций на склад - сохранение</h1>");
	$n=@$_POST['n'];
	if(!is_array($n))	throw new Exception("Список позиций не получен");
	foreach($n as $id => $value)
	{
		$vc=mysql_real_escape_string(@$_POST['vc'][$id]);
		$value=mysql_real_escape_string($value);
		mysql_query("INSERT INTO `doc_base` (`group`, `name`, `vc`, `unit`) VALUES ('$group', '$value', '$vc', '$units')");
		if(mysql_errno())	throw new MysqlException("Не удалось добавить наименование $value");
		$pos_id=mysql_insert_id();
		$tmpl->AddText("Добавлено $pos_id: $vc - $value<br>");
		if($vc)
		{
			mysql_query("INSERT INTO `seekdata` (`id`, `sql`) VALUES ('$pos_id', '$vc')");
			if(mysql_errno())	throw new MysqlException("Не удалось добавить данные анализатора для наименования $value");
		}
	}
	$tmpl->msg("Все операции выполнены!","ok");
}
else if($mode=='r_parsed')
{
	$f=rcv('f');
	$tmpl->AddText("<h1>Отчёт по обработаным позициям</h1>");
	if($f) $f=" AND `price`.`firm`='$f'";
	$res=mysql_query("SELECT `price`.`id`, `price`.`name`, `price`.`art`, `firm_info`.`name`
	FROM `price`
	LEFT JOIN `firm_info` ON `firm_info`.`id`=`price`.`firm`
	WHERE `seeked`='1' $f
	LIMIT 100000");
	if(mysql_num_rows($res))
	{
		$i=0;
		$tmpl->AddText("<table width='100%'><tr><th>ID<th>Наименование<th>Артикул<th>Фирма");
		while($nxt=mysql_fetch_row($res))
		{
			$i=1-$i;
			$tmpl->AddText("<tr class='lin$i'><td><a href='?mode=multi_view&amp;p=$nxt[0]'>$nxt[0]</a><td>$nxt[1]<td>$nxt[2]<td>$nxt[3]");
		}
		$tmpl->AddText("</table>");
	}
	else $tmpl->msg("Обработанных позиций не обнаружено!");
}
else if($mode=='r_multiparsed')
{
	$f=rcv('f');
	$tmpl->AddText("<h1>Отчёт по многократно обработанным позициям</h1>");
	if($f) $f=" AND `price`.`firm`='$f'";
	$res=mysql_query("SELECT `price`.`id`, `price`.`name`, `price`.`art`, `firm_info`.`name`, `price`.`seeked`
	FROM `price`
	LEFT JOIN `firm_info` ON `firm_info`.`id`=`price`.`firm`
	WHERE `seeked`>'1' $f
	LIMIT 1000");
	if(mysql_num_rows($res))
	{
		$i=0;
		$tmpl->AddText("<table width='100%'><tr><th>ID<th>Наименование<th>Артикул<th>Фирма<th>Срабатываний");
		while($nxt=mysql_fetch_row($res))
		{
			$i=1-$i;
			$tmpl->AddText("<tr class='lin$i'><td><a href='?mode=multi_view&amp;p=$nxt[0]'>$nxt[0]</a><td>$nxt[1]<td>$nxt[2]<td>$nxt[3]<td>$nxt[4]");
		}
		$tmpl->AddText("</table>");
	}
	else $tmpl->msg("Многократно обработанных позиций не обнаружено!");
}
else if($mode=='multi_view')
{
	$price_id=rcv('p');
	$tmpl->AddText("<h1>Информация о совпадениях выбранной позиции прайса</h1>");
	$res=mysql_query("SELECT `parsed_price`.`pos`, `doc_group`.`name`, `doc_base`.`name`, `seekdata`.`sql`, `seekdata`.`regex` FROM `parsed_price`
	LEFT JOIN `seekdata` ON `seekdata`.`id`=`parsed_price`.`pos`
	LEFT JOIN `doc_base` ON `doc_base`.`id`=`parsed_price`.`pos`
	LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
	WHERE `parsed_price`.`from`='$price_id'");
	$tmpl->AddText("<table width='100%'><tr><th>ID<th>Наименование<th>Строка поиска<th>Регулярное выражение");
	while($nxt=mysql_fetch_row($res))
	{
		$i=1-$i;
		$tmpl->AddText("<tr class='lin$i'><td><a href='/docs.php?l=pran&mode=srv&opt=ep&pos=$nxt[0]'>$nxt[0]</a><td>$nxt[1] - $nxt[2]<td>$nxt[3]<td>$nxt[4]");
	}
	$tmpl->AddText("</table>");
}
else if($mode=='replaces')
{
	$tmpl->AddText("<h1>Подстановки для регулярных выражений</h1>
	<table width='100%'><tr><th>ID<th>Поиск<th>Замена");
	$res=mysql_query("SELECT `id`, `search_str`, `replace_str` FROM `prices_replaces` ORDER BY `search_str`");
	if(mysql_errno())	throw new MysqlException('Не удалось получить список подстановок!');
	while($nxt=mysql_fetch_row($res))
	{
		$tmpl->AddText("<tr><td><a href='?mode=replacese&amp;p=$nxt[0]'>$nxt[0]</a> <a href='?mode=replacesd&amp;p=$nxt[0]' title='Удалить'><img src='/img/i_del.png' alt='Удалить'></a><td>{{{$nxt[1]}}}<td>$nxt[2]");
	}
	$tmpl->AddText("</table><br>
	<a href='?mode=replacese&amp;p=0'><img src='/img/i_add.png' alt='Добавить'> Добавить</a>");
}
else if($mode=='replacese')
{
	$p=rcv('p');
	$res=mysql_query("SELECT `id`, `search_str`, `replace_str` FROM `prices_replaces` WHERE `id`='$p'");
	if(mysql_errno())	throw new MysqlException('Не удалось получить данные подстановки!');
	$nxt=@mysql_fetch_row($res);
	$tmpl->AddText("<h1>Правка подстановки</h1>
	<form action='' method='post'>
	<input type='hidden' name='mode' value='replacess'>
	<input type='hidden' name='p' value='$nxt[0]'>
	Поиск:<br>
	<input type='text' name='search_str' value='$nxt[1]'><br>
	Замена:<br>
	<input type='text' name='replace_str' value='$nxt[2]'><br>
	<button>Сохранить</button>
	</form>");
}
else if($mode=='replacess')
{
	$p=rcv('p');
	$search_str=rcv('search_str');
	$replace_str=rcv('replace_str');

	if($p=='')
	{
		mysql_query("INSERT INTO `prices_replaces` (`search_str`, `replace_str`) VALUES ('$search_str', '$replace_str')");
		if(mysql_errno())	throw new MysqlException('Не удалось добавить данные подстановки!');
		$p=mysql_insert_id();
	}
	else
	{
		mysql_query("UPDATE `prices_replaces` SET `search_str`='$search_str', `replace_str`='$replace_str' WHERE `id`='$p'");
		if(mysql_errno())	throw new MysqlException('Не удалось обновить данные подстановки!');
	}

	$tmpl->msg("Выполнено!<br><a href='?mode=replaces'>Вернуться к таблице</a> | <a href='?mode=replacese&amp;p=$p'>Продолжить редактирование</a>","ok","Сохранение подстановки");
}
else if($mode=='menu')
{
	$tmpl->ajax=1;
	$tmpl->SetText("
	<div onclick=\"window.location='/docs.php?l=pran'\">Результаты анализа</div>
	<div onclick=\"window.location='/priceload.php'\">Редактор организаций</div>
	<div onclick=\"window.location='/priceload.php?mode=load'\">Загрузить прайс</div>
	<div onclick=\"window.location='/priceload.php?mode=viewall'\">Просмотреть общий список</div>
	<div onclick=\"window.location='/priceload.php?mode=search'\">Поиск</div>
	<div onclick=\"window.location='/priceload.php?mode=replaces'\">Подстановки</div>
	<div onclick=\"window.location='/priceload.php?mode=r_noparsed'\">Ошибки: необработанные</div>
	<div onclick=\"window.location='/priceload.php?mode=r_multiparsed'\">Ошибки: дублирующиеся</div>");
}
else $tmpl->logger('Запрошен неверный режим! Возможно, вы указали неверные параметры, или же ссылка, по которой Вы обратились, неверна.');


}
catch(MysqlException $e)
{
	$tmpl->ajax=0;
	$tmpl->msg($e->getMessage()."<br>Сообщение передано администратору",'err',"Ошибка в базе данных");
}
catch(Exception $e)
{
	$tmpl->msg($e->getMessage(),'err');
}

$tmpl->write();
?>