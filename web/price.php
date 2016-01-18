<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2016, BlackLight, TND Team, http://tndproject.org
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

require_once("core.php");
require_once("include/doc.core.php");

function draw_groups_tree($level)
{
	global $db;
	$ret='';
	$res=$db->query("SELECT `id`, `name`, `desc` FROM `doc_group` WHERE `pid`='$level' AND `hidelevel`='0' ORDER BY `name`");
	$i=0;
	$r='';
	if($level==0) $r='IsRoot';
	$cnt=$res->num_rows;
	while($nxt=$res->fetch_row())
	{
		if($nxt[0]==0) continue;
		$item="<label><input type='checkbox' name='g[]' value='$nxt[0]' id='cb$nxt[0]' class='cb' checked onclick='CheckCheck($nxt[0])'>$nxt[1]</label>";
		if($i>=($cnt-1)) $r.=" IsLast";
		$tmp=draw_groups_tree($nxt[0]); // рекурсия
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
	$tmpl->addStyle(".scroll_block
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
	$tmpl->addContent("<script type='text/javascript'>
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
	".draw_groups_tree(0)."</ul></div>");

}
	
try {
    $mode = request('mode');
    
    if($mode=="")
    {
	$tmpl->setTitle("Прайс-лист");
	$tmpl->addContent("<h1 id='page-title'>Прайс-лист</h1><div id='page-info'>Формирование прайс-листа по вашим требованиям</div>
	Для тех, кому не удобно просматривать товары в режиме онлайн, сделана возможность сформировать прайс - лист. Специально для Вас мы сделали возможность получить прайс-лист в наиболее удобном для Вас формате. Сейчас доступны <a class='wiki' href='/price.php?mode=gen&amp;f=pdf'>PDF</a>, <a class='wiki' href='/price.php?mode=gen&amp;f=csv'>CSV</a>, <a class='wiki' href='/price.php?mode=gen&amp;f=html'>HTML</a> и <a class='wiki' href='/price.php?mode=gen&amp;f=xls'>XLS</a> форматы. В ближайшее время планируется реализовать ODF. Для получения прайса выберите формат:<br>
	<ul>
	<li><a class='wiki' href='/price.php?mode=gen&amp;f=pdf'>Прайс-лист в формате pdf</a> (для просмотра и печати в программах Foxit reader, Adobe reader, Okular, и <a class='wiki_ext' href='http://pdfreaders.org/'>другие</a>...)</li>
	<li><a class='wiki' href='/price.php?mode=gen&amp;f=csv'>Прайс-лист в формате csv</a>  (для просмотра в текстоовых редакторах, Openoffice Calc и Microsoft office Excel)</li>
	<li><a class='wiki' href='/price.php?mode=gen&amp;f=html'>Прайс-лист в формате html</a> (для просмотра в любом html броузере: Mozilla, Opera, Internet explorer)</li>
	<li><a class='wiki' href='/price.php?mode=gen&amp;f=xls'>Прайс-лист в формате xls</a> (для просмотра в табличных редакторах Microsoft office Excel, Openoffice Calc, и подобных)</li>
	<li style='color: #f00;'>Если не знаете, что именно Вам выбрать - выбирайте <a class='wiki' href='/price.php?mode=gen&amp;f=html'>Прайс-лист в формате html</a>!</li>
	</ul>");
}
else if($mode=="gen")
{
	$f	= request('f');
	$tmpl->setTitle("Прайс-лист: задание параметров");

	if($f=="csv")
	{
		$tmpl->addContent("<h1 id='page-title'>Загрузка прайс - листа</h1><div id='page-info'>Используется csv формат</div>
		В файле содержится электронная таблица. Формат удобен для случаев, когда Вам необходимо что-либо изменить в полученном прайсе, или если Вам привычнее пользоваться табличным редактором.<br>
		Загруженный файл можно будет открыть при помощи:
		<ul>
		<li>OpenOffice Calc (рекомендуется, <a class='wiki_ext' href='http://download.openoffice.org' rel='nofollow'>скачать программу</a>)</li>
		<li>Microsoft office Excel</li>
		</ul>
		Внимание! Редактор Microsoft office Excel требует для правильного открытия таких файлов указать кодировку UTF-8!<br>
		<br>
		<form action='price.php' method='post'>
		<input type='hidden' name='mode' value='get'>
		<input type='hidden' name='f' value='$f'>
		<table width='100%'>
		<tr>
		<th>Количество колонок:
		<th>Разделитель:
		<th>Ограничитель текста:
		<th>Дополнительные параметры

		<tr class=lin0>
		<td><select name='kol'><option value='1'>1</option><option selected value='2'>2</option>
		<option value='3'>3</option><option selected value='4'>4</option></select><br>

		<td><select name='divider'><option value=','>,</option><option selected value=';'>;</option>
		<option value=':'>:</option></select><br>

		<td><select name='shielder'><option value='\''>'</option><option selected value='\"'>\"</option>
		<option value='*'>*</option></select><br>
		<td><input type='checkbox' name='proizv' value='1' checked> - Указать производителя<br>
		</table><br>");
		GroupSelBlock();
		$tmpl->addContent("<div style='color: #f00; text-align: center;'>Если не знаете, какие параметры выбрать - просто нажмите кнопку *Загрузить прайс-лист*!<br>
		<span style='color: #090'>Внимание! Для вывода прайс листа на печать рекомендуется использовать <a class='wiki' href='?mode=gen&f=pdf'>формат PDF</a>!</span><br>
		<button type='submit'>Загрузить прайс-лист!</button>
		</div>
		</div></form>");
	}
	else if($f=="html")
	{
		$ag=getenv("HTTP_USER_AGENT");
		$link['opera']="<a class='wiki_ext' href='http://opera.com' rel='nofollow'>скачать здесь</a>";
		$link['mozilla']="<a class='wiki_ext' href='http://mozilla.com' rel='nofollow'>скачать здесь</a>";
		$link['ie']="не рекомендуется";
		$link['other']='';

		if(stripos(' '.$ag,'opera'))
			$link['opera']='<span style="color: #090">Используется Вами в данный момент</span>';
		else if(stripos(' '.$ag,'MSIE'))
			$link['ie'].=', <span style="color: #090">Используется Вами в данный момент</span>';
		else if(stripos(' '.$ag,'mozilla'))
			$link['mozilla']='<span style="color: #090">Используется Вами в данный момент</span>';
		else
			$link['other']='<span style="color: #090">Используется Вами в данный момент</span>';
		foreach($link as $id => $l)
		{
			if($l)	$link[$id]='('.$l.')';
		}

		$tmpl->addContent("<h2 id='page-title'>Загрузка прайс - листа</h2><div id='page-info'>Используется HTML формат</div>
		Прайс в виде обычной веб-страницы. Для просмотра можно использовать обычные веб броузеры, например:
		<ul>
		<li>Opera {$link['opera']}</li>
		<li>Mozilla, Fierfox {$link['mozilla']}</li>
		<li>Microsoft Internrt Exploerer {$link['ie']}</li>
		<li>Любую другую прграмму просмотра сайтов {$link['other']}</li>
		</ul>
		<br>

		<form action='price.php' method='post'>
		<input type=hidden name=mode value=get>
		<input type=hidden name=f value=$f>

		<table width='100%'>
		<tr>
		<th>Количество колонок
		<th>Количество строк на \"странице\"
		<th>Дополнительные параметры

		<tr class=lin0>
		<td><select name=kol><option value=1>1</option><option selected value=2>2</option>
		<option value=3>3</option><option selected value=4>4</option>
		<option value=5>5</option><option value=6>6</option></select><br>

		<td><input type=text name=str value=50>
		<td><input type=checkbox name=proizv value=1 checked> - Указать производителя<br>
		</table>

		<div style='color: #f00; text-align: center;'>Если не знаете, какие параметры выбрать - просто нажмите кнопку *Загрузить прайс-лист*!<br><span style='color: #090'>Внимание! Для вывода прайс листа на печать рекомендуется использовать <a class='wiki' href='?mode=gen&f=pdf'>формат PDF</a>!</span><br>
		<button type='submit'>Загрузить прайс-лист!</button>
		</div>
		</div>
		</form>");

	}
	else if($f=="pdf")
	{
		$ag=getenv("HTTP_USER_AGENT");
		$list='';

		if(!stripos(' '.$ag,'Windows'))
			$list.="<li><a class='wiki_ext' href='http://okular.kde.org/' rel='nofollow'>Okular (KPDF)</a> (рекомендуется)</li><li>Adobe reader</li><li>KGhostView</li>";
		if(!stripos(' '.$ag,'Linux'))
			$list.="<li><a class='wiki_ext' href='http://www.foxitsoftware.com/pdf/reader/' rel='nofollow'>Foxit reader</a> (рекомендуется)</li><li><a class='wiki_ext' herf='http://get.adobe.com/reader/' rel='nofollow'>Adobe reader</a></li><li>Djvu reader</li>";

		$list.="<li><a class='wiki_ext' href='http://pdfreaders.org/'>Другие</a></li>";



		$tmpl->addContent("<h2 id='page-title'>Загрузка прайс - листа</h2><div id='page-info'>Используется PDF формат</div>");
		$tmpl->addContent("
		Идеальный формат для вывода на печать. Для просмотра можно использовать любые PDF просмотрщики, например:
		<ul>$list</ul>
		<br>

		<form action='price.php' method='post'>
		<input type=hidden name=mode value=get>
		<input type=hidden name=f value=$f>

		<table width='100%'>
		<tr>
		<th>Дополнительные параметры

		<tr class=lin0>
		<td><label><input type=checkbox name=proizv value=1 checked> Указать производителя</label></table>");

		GroupSelBlock();

		$tmpl->addContent("

		<div style='color: #f00;'>Если не знаете, какие параметры выбрать - просто нажмите кнопку *Загрузить прайс-лист*!<br>
		<button type='submit'>Загрузить прайс-лист!</button>
		</div>
		</form>");

	}
	else if($f=='xls')
	{
		$tmpl->addContent("<h2 id='page-title'>Загрузка прайс - листа</h2><div id='page-info'>Используется xls формат</div>
		В файле содержится электронная таблица Microsoft Excel. Формат удобен только для пользователей этой программы, желающих вносить изменения в прайс. Для просмотра и печати рекомендуется <a class='wiki' href='?mode=gen&f=pdf'>формат PDF</a>.<br>
		Загруженный файл можно будет открыть при помощи:
		<ul>
		<li>Microsoft office Excel (рекомендуется)</li>
		<li>OpenOffice Calc (<a class='wiki_ext' href='http://download.openoffice.org' rel='nofollow'>скачать программу</a>)</li>

		</ul>
		<br>
		<form action='price.php' method='post'>
		<input type=hidden name=mode value=get>
		<input type=hidden name=f value=$f>
		<table width='100%'>
		<tr>
		<th>Дополнительные параметры

		<tr class=lin0>
		<td><label><input type='checkbox' name='proizv' value='1' checked> Указать производителя</label><br>
		</table><br>");
		GroupSelBlock();
		$tmpl->addContent("<div style='color: #f00; text-align: center;'>Если не знаете, какие параметры выбрать - просто нажмите кнопку *Загрузить прайс-лист*!<br>
		<span style='color: #090'>Внимание! Для вывода прайс листа на печать рекомендуется использовать <a class='wiki' href='?mode=gen&f=pdf'>формат PDF</a>!</span><br>
		<button type='submit'>Загрузить прайс-лист!</button>
		</div>
		</div></form>");


	}
	else $tmpl->msg("Извините, но данный формат не поддрерживается! Возможно, его поддержка будет реализована позднее!","err");
}
else if($mode=="get")
{
	$proizv	= request('proizv');
	$kol	= request('kol');
	$f	= request('f');
	$tmpl->ajax=1;
	$tdata="";
	
	switch( $f ) {
		case 'pdf': 
                    $price = new pricewriter\pdf($db);	
                    break;
		case 'csv': 
                    $price = new pricewriter\csv($db);	
                    break;
		case 'xls':
                    $price = new pricewriter\xls($db);
                    break;
		case 'html':
                    $price = new pricewriter\html($db); 
                    break;
		default:
                    throw new Exception("Запрошенный формат прайс-лиска пока не поддерживается");
	}	
	$price->showProizv($proizv);
	$price->setColCount($kol);
	$pc = PriceCalc::getInstance();
        $pref = \pref::getInstance();
        $pc->setFirmId($pref->site_default_firm_id);
        
	$price->SetCost( $pc->getDefaultPriceId() );	
	if($f=='csv')	{
		$price->setDivider( request('divider') );
		$price->setShielder( request('shielder') );
	}	
	if(request('gs') && is_array($_REQUEST['g']))	{
		$price->setViewGroups($_REQUEST['g']);
	}
	
	$price->run();
	exit();
}

}
catch(mysqli_sql_exception $e)
{
	$db->rollback();
	$id =  writeLogException($e);
	$tmpl->addContent("<br><br>");
	$tmpl->msg("Ошибка базы данных, $id","err");
}
catch(Exception $e)
{
    writeLogException($e);
    $tmpl->errorMessage($e->getMessage());
}


$tmpl->Write();
