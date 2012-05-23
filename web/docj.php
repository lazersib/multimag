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
include_once("include/doc.s.nulltype.php");
SafeLoadTemplate($CONFIG['site']['inner_skin']);
$tmpl->HideBlock('left');

function GetRootDocument($doc)
{
	while($doc)
	{
		$res=mysql_query("SELECT `p_doc` FROM `doc_list` WHERE `id`='$doc'");
		echo mysql_error();
		$pdoc=mysql_result($res,0,0);
		if(!$pdoc) return $doc;
		$doc=$pdoc;
	}
	return $doc;
}

function DrawSubTreeDocument($doc,$cur_doc)
{
	global $tmpl;
	$sql="SELECT `doc_list`.`id`, `doc_list`.`ok`, `doc_list`.`date`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`sum`, `doc_agent`.`name`, `doc_types`.`name`
	FROM `doc_list`
	LEFT JOIN `doc_agent` ON `doc_list`.`agent`=`doc_agent`.`id`
	LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
	WHERE `doc_list`.`id`='$doc'
	ORDER by `doc_list`.`date` DESC";
	echo mysql_error();
	$res=mysql_query($sql);
	$cnt=mysql_num_rows($res);
	$i=1;
	$r='';
	if($nxt=mysql_fetch_row($res))
	{
		$dt=date("Y.m.d H:i:s",$nxt[2]);
		$pp="Непроведённый";
		if($nxt[1]) $pp="Проведённый";
		if($i>=$cnt) $r=" IsLast";
		$tmpl->AddText("<ul class='Container'>");
		$tmpl->AddText("<li class='Node'><div class='Expand'></div><div class='Content'>");
		if($doc==$cur_doc) $tmpl->AddText("<b>");
		$tmpl->AddText("<a href='doc.php?mode=body&doc=$doc'>$pp $nxt[7]</a> N $nxt[3]$nxt[4] от $dt. Агент: $nxt[6], на сумму $nxt[5] ");
		if($doc==$cur_doc) $tmpl->AddText("</b>");
		$tmpl->AddText("<ul class='Container'>");
		DrawSubTreeDocumentNode($doc,$cur_doc);
		$tmpl->AddText("</ul>");
		$tmpl->AddText("</div></li>");
		$tmpl->AddText("</ul>");
		$i++;
	}
}

function DrawSubTreeDocumentNode($doc,$cur_doc)
{
	global $tmpl;
	$sql="SELECT `doc_list`.`id`, `doc_list`.`ok`, `doc_list`.`date`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`sum`, `doc_agent`.`name`, `doc_types`.`name`
	FROM `doc_list`
	LEFT JOIN `doc_agent` ON `doc_list`.`agent`=`doc_agent`.`id`
	LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
	WHERE `doc_list`.`p_doc`='$doc'
	ORDER by `doc_list`.`date` DESC";
	echo mysql_error();
	$res=mysql_query($sql);
	$cnt=mysql_num_rows($res);
	$i=1;
	$r='';
	while($nxt=mysql_fetch_row($res))
	{
		$dt=date("Y.m.d H:i:s",$nxt[2]);
		$pp="Непроведённый";
		if($nxt[1]) $pp="Проведённый";
		if($i>=$cnt) $r=" IsLast";

		$tmpl->AddText("<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>");
		if($doc==$cur_doc) $tmpl->AddText("<b>");
		$tmpl->AddText("<a href='doc.php?mode=body&doc=$nxt[0]'>$pp $nxt[7]</a> N $nxt[3]$nxt[4] от $dt. Агент: $nxt[6], на сумму $nxt[5] ");
		if($doc==$cur_doc) $tmpl->AddText("</b>");
		//$tmpl->AddText("</li>");
		$tmpl->AddText("<ul class='Container'>");
		DrawSubTreeDocumentNode($nxt[0],$cur_doc);
		$tmpl->AddText("</ul>");
		$tmpl->AddText("</div></li>");
		$i++;
	}

}

function FilterMenu()
{
	global $tmpl;
//	return 0;
	$tmpl->AddStyle("
		#doc_sel
		{
			width:	280px;
			height:	17px;
			border:	1px solid #ccc;
			background: url('img/win/droplist.png') no-repeat right;
			overflow: hidden;
			padding:	0px;
			font-size:	6px;
			vertical-align:	center;
		}

		#doc_sel_popup
		{
			width:	280px;
			border:	1px solid #ccc;
			display:none;
			background: #fefefe;
		}
		");

		$tmpl->AddTMenu("<script type='text/javascript' src='/css/doc_script.js'></script>
		<script src='/css/jquery/jquery.js' type='text/javascript'></script>
		<!-- Core files -->
		<script src='/css/jquery/jquery.alerts.js' type='text/javascript'></script>
		<link href='/css/jquery/jquery.alerts.css' rel='stylesheet' type='text/css' media='screen' />
		<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>

		<link rel='stylesheet' href='/css/jquery/ui/themes/base/jquery.ui.all.css'>
		<script src='/css/jquery/ui/jquery.ui.core.js'></script>
		<script src='/css/jquery/ui/jquery.ui.widget.js'></script>
		<script src='/css/jquery/ui/jquery.ui.datepicker.js'></script>
		<script src='/css/jquery/ui/i18n/jquery.ui.datepicker-ru.js'></script>

		<!--
		<div id='jq_popup' class='context_menu' style='display: none'>
		<a href='#' onClick='$(\"#jq_popup\").hide()'>[x] Скрыть</a><br><br><br><center>{L_NEW_MESSAGE}<br><br></b></center>
		</div>
		-->

		<div id='popup_container'>
		<h1 id='popup_title'>Фильтры журнала</h1>
		<div id='popup_content' class='noicon'>
		<form action='docj.php' method='post'>
		<input type='hidden' name='mode' value='filter'>
		<input type='hidden' name='opt' value='fsn'>
		<table width='400px'>
		<tr><td>

		");

		$doc_names=$doc_sel=$doc_cb='';
		$res=mysql_query("SELECT `id`, `name` FROM `doc_types` ORDER BY `name`");
		while($nxt=mysql_fetch_row($res))
		{
			if(@$_SESSION['j_need_doctypes'][$nxt[0]])
			{
				$ss='checked';
				$doc_sel.="$nxt[1]; ";
			}
			else						$ss='';
			$doc_cb.="<label><input type='checkbox' id='dt$nxt[0]' name='dt[$nxt[0]]' value='$nxt[0]' $ss onclick='DtCheck(this);'>$nxt[1]</label><br>";
			$doc_names.="dn[$nxt[0]]='$nxt[1]';";

		}

		$tmpl->AddTMenu("
		Отбор по типу документа:<br>
		<div id='doc_sel' onClick='ShowDocTypes(this)'>$doc_sel</div>
		<div id='doc_sel_popup'>$doc_cb</div>
		");

		$sklad_options="<option value='0'>-</option>";
		$res=mysql_query("SELECT `id`, `name` FROM `doc_sklady` ORDER BY `id`");
		while($nxt=mysql_fetch_row($res))
		{
			if(@$_SESSION['j_select_sklad']==$nxt[0])
			{
				$ss='selected';
				$_SESSION['j_select_sklad_name']=$nxt[1];
			}
			else	$ss='';
			$sklad_options.="<option value='$nxt[0]' $ss>$nxt[0]: $nxt[1]</option>";
		}
		$bank_options="<option value='0'>-</option>";
		$res=mysql_query("SELECT `num`, `name` FROM `doc_kassa` WHERE `ids`='bank' ORDER BY `num`");
		while($nxt=mysql_fetch_row($res))
		{
			if(@$_SESSION['j_select_bank']==$nxt[0])
			{
				$ss='selected';
				$_SESSION['j_select_bank_name']=$nxt[1];
			}
			else	$ss='';
			$bank_options.="<option value='$nxt[0]' $ss>$nxt[0]: $nxt[1]</option>";
		}
		$kassa_options="<option value='0'>-</option>";
		$res=mysql_query("SELECT `num`, `name` FROM `doc_kassa` WHERE `ids`='kassa' ORDER BY `num`");
		while($nxt=mysql_fetch_row($res))
		{
			if(@$_SESSION['j_select_kassa']==$nxt[0])
			{
				$ss='selected';
				$_SESSION['j_select_kassa_name']=$nxt[1];
			}
			else	$ss='';
			$kassa_options.="<option value='$nxt[0]' $ss>$nxt[0]: $nxt[1]</option>";
		}
		$firm_options="<option value='0'>-</option>";
		$res=mysql_query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `id`");
		while($nxt=mysql_fetch_row($res))
		{
			if(@$_SESSION['j_select_firm']==$nxt[0])
			{
				$ss='selected';
				$_SESSION['j_select_firm_name']=$nxt[1];
			}
			else $ss='';
			$firm_options.="<option value='$nxt[0]' $ss>$nxt[0]: $nxt[1]</option>";
		}

		$date_f=$date_t=date("Y-m-d");

		@$tmpl->AddTMenu("
		</td><td align='right'>
		Альт.н.<br>
		<input type='text' name='altnum' style='width: 50px;' value='{$_SESSION['j_select_altnum']}'><br>
		</td><td align='right'>
		Подтип<br>
		<input type='text' name='subtype' style='width: 50px;' value='{$_SESSION['j_select_subtype']}'><br>
		</td></tr>
		</table>
		<table width='400px'>
		<tr><td>Дата от:</td><td align='right'>Дата до:</td></tr>
		<tr><td><input type='text' name='date_from' id='datepicker_f' value='{$_SESSION['j_date_from']}'></td>
		<td align='right'><input type='text' name='date_to' id='datepicker_t' value='{$_SESSION['j_date_to']}'></td></tr>
		</table>
		Агент:<br>
		<input type='hidden' name='agent_id' id='agent_id' value='{$_SESSION['j_agent']}'>
		<input type='text' id='ag' name='agent_name' style='width: 400px;' value='{$_SESSION['j_agent_name']}'><br>
		Товар:<br>
		<input type='hidden' name='tov_id' id='tov_id' value='{$_SESSION['j_select_tov']}'>
		<input type='text' id='tov' name='tov_name' style='width: 400px;' value='{$_SESSION['j_select_tov_name']}'><br>
		Организация:<br>
		<select name='firm' style='width: 400px;'>$firm_options</select><br>
		Банк:<br>
		<select name='bank' style='width: 400px;'>$bank_options</select>
		<table width='400px'>
		<tr><td>Склад</td><td align='right'>Касса</td></tr>
		<tr><td><select name='sklad'>$sklad_options</select></td>
		<td align='right'><select name='kassa'>$kassa_options</select></td></tr>
		</table>
		Автор:<br>
		<input type='hidden' name='autor_id' id='autor_id' value='{$_SESSION['j_select_autor_id']}'>
		<input type='text' id='au' name='autor_name' style='width: 400px;' value='{$_SESSION['j_select_autor_name']}'><br>
		<label><input type='checkbox'>Сохранить как настройки по умолчанию</label>
		<div id='popup_panel'> <button type='button' onClick='$(\"#popup_container\").hide(); return false;'>Отмена</button> <button type='submit'>Отфильтровать</button></div>
		</form>
		</div>
		</div>


		<script type='text/javascript'>

		function DtCheck(t)
		{
			var dn=new Array();
			$doc_names
			var popup=document.getElementById('doc_sel_popup');
			var list=popup.getElementsByTagName('input');
			var str='';
			for(var i=0; i<list.length; i++)
			{
				if(list[i].checked)
					str+=dn[list[i].value]+'; ';
			}
			document.getElementById('doc_sel').innerHTML=str;
		}

		$(document).ready(function(){
			$('#popup_container').hide();
			$(\"#ag\").autocomplete(\"/docs.php\", {
			delay:300,
			minChars:1,
			matchSubset:1,
			autoFill:false,
			selectFirst:true,
			matchContains:1,
			cacheLength:10,
			maxItemsToShow:15,
			formatItem:agliFormat,
			onItemSelect:agselectItem,
			extraParams:{'l':'agent','mode':'srv','opt':'ac'}
			});
			$(\"#tov\").autocomplete(\"/docs.php\", {
			delay:300,
			minChars:1,
			matchSubset:1,
			autoFill:false,
			selectFirst:true,
			matchContains:1,
			cacheLength:10,
			maxItemsToShow:15,
			formatItem:tovliFormat,
			onItemSelect:tovselectItem,
			extraParams:{'l':'sklad','mode':'srv','opt':'ac'}
			});

			$(\"#au\").autocomplete(\"/rights.php\", {
			delay:300,
			minChars:1,
			matchSubset:1,
			autoFill:false,
			selectFirst:true,
			matchContains:1,
			cacheLength:10,
			maxItemsToShow:15,
			formatItem:tovliFormat,
			onItemSelect:auselectItem,
			extraParams:{'mode':'upl'}
			});

			initCalendar('datepicker_f',false)
			initCalendar('datepicker_t',false)
		});

		function agliFormat (row, i, num) {
			var result = row[0] + \"<em class='qnt'>тел. \" +
			row[2] + \"</em> \";
			return result;
		}


		function agselectItem(li) {
			if( li == null ) var sValue = \"Ничего не выбрано!\";
			if( !!li.extra ) var sValue = li.extra[0];
			else var sValue = li.selectValue;
			document.getElementById('agent_id').value=sValue;

		}

		function tovliFormat (row, i, num) {
			var result = row[0] + \"<em class='qnt'>\" +
			row[2] + \"</em> \";
			return result;
		}

		function tovselectItem(li) {
			if( li == null ) var sValue = \"Ничего не выбрано!\";
			if( !!li.extra ) var sValue = li.extra[0];
			else var sValue = li.selectValue;
			document.getElementById('tov_id').value=sValue;

		}

		function auselectItem(li) {
			if( li == null ) var sValue = \"Ничего не выбрано!\";
			if( !!li.extra ) var sValue = li.extra[0];
			else var sValue = li.selectValue;
			document.getElementById('autor_id').value=sValue;

		}

		function ShowJournalFilter(ths)
		{
			ths.style.color='#f00';
			var left=$(ths).offset().left;
			var top=$(ths).offset().top;
			$('#popup_container').css({
			'top': top,
			'position': 'absolute',
			'left': left
			});
			$('#popup_container').show(200);
		}

		var hideDocTypesTimer = null;

		function ShowDocTypes(ths)
		{
			var left=$('#doc_sel').offset().left;
			var top=$('#doc_sel').offset().top;
			$('#doc_sel_popup').css({
			'top': top-$('#popup_container').css.top,
			'position': 'absolute',
			'left': left-$('#popup_container').css.left,
			'display': 'inline'
			});
			$('#doc_sel_popup').mouseout(function ()
			{
				if (hideDocTypesTimer) clearTimeout(hideDocTypesTimer);
				hideDocTypesTimer = setTimeout(function ()
				{
					$('#doc_sel_popup').hide(200);
				}, 200);
			});
			$('#doc_sel_popup').mouseover( function()
			{
				if (hideDocTypesTimer) clearTimeout(hideDocTypesTimer);
			});
		}

		</script>");
}

need_auth();
$GLOBALS['m_left']=0;
$type_doc=@$_POST['type_doc'];
$sn=rcv('sn');
$fam=rcv('fam');
$cnam=rcv('cnam');


if(!isAccess('doc_list','view'))	throw new AccessException("");

if($mode=="")
{
	$info='';
	$dp="";
	$ds="";
	$tmpl->SetTitle("Список документов");
	doc_menu("<a onclick=\"ShowJournalFilter(this); return false;\" href='' title='Фильтр'><img src='img/i_filter.png' alt='Фильтр документов' border='0'></a>
	<a href='?mode=print' title='Печать реестра'><img src='img/i_print.png' alt='Реестр документов' border='0'></a>");

	if(!@$_SESSION['j_date_from'])	$_SESSION['j_date_from']=date("Y-m-d");
	if(!@$_SESSION['j_date_to'])	$_SESSION['j_date_to']=date("Y-m-d");

	FilterMenu();

	$t_from=strtotime($_SESSION['j_date_from']);
	$t_to=strtotime($_SESSION['j_date_to'])+60*60*24-1;

	$info.='<b>С</b> '.$_SESSION['j_date_from'].' <b>по</b> '.$_SESSION['j_date_to'];

	$asel=@$_SESSION['j_agent'];
	settype($asel,"int");
	if($asel)
	{
		$ds.=" AND `doc_list`.`agent`='$asel'";
		$info.=", <b>агент:</b> {$_SESSION['j_agent_name']}";
	}

	if(is_array(@$_SESSION['j_need_doctypes']))
	{
		$info.=", <b>док-ты:</b> ";
		$ts='';
		foreach($_SESSION['j_need_doctypes'] as $id => $line)
		{
			if(!$ts)	$ts="`doc_list`.`type`='$line'";
			else		$ts.="OR `doc_list`.`type`='$line'";
			$info.="$line/";
		}
		$ds.=" AND ($ts) ";
	}
	if(@$_SESSION['j_select_subtype'])
	{
		$ds.=" AND `doc_list`.`subtype`='{$_SESSION['j_select_subtype']}'";
		$info.=", <b>подтип:</b> {$_SESSION['j_select_subtype']}";
	}
	if(@$_SESSION['j_select_altnum'])
	{
		$ds.=" AND `doc_list`.`altnum`='{$_SESSION['j_select_altnum']}'";
		$info.=", <b>альт.номер:</b> {$_SESSION['j_select_altnum']}";
	}
	if(@$_SESSION['j_select_sklad'])
	{
		$ds.="AND `doc_list`.`sklad`='{$_SESSION['j_select_sklad']}'";
		$info.=", <b>склад:</b> {$_SESSION['j_select_sklad_name']}";
	}
	if(@$_SESSION['j_select_bank'])
	{
		$ds.="AND `doc_list`.`bank`='{$_SESSION['j_select_bank']}'";
		$info.=", <b>банк:</b> {$_SESSION['j_select_bank_name']}";
	}
	if(@$_SESSION['j_select_kassa'])
	{
		$ds.="AND `doc_list`.`kassa`='{$_SESSION['j_select_kassa']}'";
		$info.=", <b>касса:</b> {$_SESSION['j_select_kassa_name']}";
	}
	if(@$_SESSION['j_select_firm'])
	{
		$ds.="AND `doc_list`.`firm_id`='{$_SESSION['j_select_firm']}'";
		$info.=", <b>организация:</b> {$_SESSION['j_select_firm_name']}";

		$res=mysql_query("SELECT `firm_skin` FROM `doc_vars` WHERE `id`='{$_SESSION['j_select_firm']}'");
		$firm_vars=mysql_fetch_assoc($res);
		if($firm_vars['firm_skin'])
			$tmpl->LoadTemplate($firm_vars['firm_skin']);
	}
	if(@$_SESSION['j_select_autor_id'])
	{
		$ds.="AND `doc_list`.`user`='{$_SESSION['j_select_autor_id']}'";
		$info.=", <b>автор:</b> {$_SESSION['j_select_autor_name']}";

	}

	$sel=@$_SESSION['j_select_tov'];
	if(!$sel)
	{
		$sql="SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`ok`, `doc_list`.`date`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`user`, `doc_list`.`sum`, `doc_list`.`mark_del`, `doc_agent`.`name`, `users`.`name`, `doc_types`.`name`, `doc_list`.`p_doc`, `doc_list`.`kassa`, `doc_list`.`bank`, `doc_list`.`sklad`, `doc_list`.`err_flag`
		FROM `doc_list`
		LEFT JOIN `doc_agent` ON `doc_list`.`agent`=`doc_agent`.`id`
		LEFT JOIN `users` ON `users`.`id`=`doc_list`.`user`
		LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		WHERE `doc_list`.`date`>='$t_from' AND `doc_list`.`date`<='$t_to' $ds
		ORDER by `doc_list`.`date` DESC";
	}
	else
	{
		$sql="SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`ok`, `doc_list`.`date`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`user`, `doc_list`.`sum`, `doc_list`.`mark_del`, `doc_agent`.`name`, `users`.`name`, `doc_types`.`name`, `doc_list`.`p_doc`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_list`.`kassa`, `doc_list`.`bank`, `doc_list`.`sklad`, `doc_list`.`err_flag`
		FROM `doc_list`
		LEFT JOIN `doc_agent` ON `doc_list`.`agent`=`doc_agent`.`id`
		LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		LEFT JOIN `users` ON `users`.`id`=`doc_list`.`user`
		INNER JOIN `doc_list_pos` ON `doc_list_pos`.`tovar`='$sel' AND `doc_list`.`id`=`doc_list_pos`.`doc`
		WHERE  `doc_list`.`date`>='$t_from' AND `doc_list`.`date`<='$t_to'  $ds
		ORDER by `doc_list`.`date` DESC";
		$dp="<th>Кол-во<th>Цена<th>Сумма";
		$info.=", <b>товар:</b> {$_SESSION['j_select_tov_name']}";
	}
	$res=mysql_query($sql);
	if(mysql_errno())	throw new MysqlException("Не удалось получить список документов!".mysql_error());
	$row=mysql_num_rows($res);

	$i=0;
	$pr=$ras=0;

	$tmpl->AddText("<h1 id='page-title'>Список документов</h1><div id='page-info'>$info</div>");

	$tmpl->AddText("<table width='100%' cellspacing='1' onclick='hlThisRow(event)'><tr>
	<th width='75'>№<th width='20'>&nbsp;<th width='20'>&nbsp;<th>Тип<th>Доп.$dp<th>Агент<th>Сумма<th>Дата<th>Автор");
	while($nxt=mysql_fetch_array($res))
	{
		$dop=$cl='';
		$dt=date("d.m.Y H:i:s",$nxt[3]);
		$cc="lin$i";
		if(@$uid==$nxt[6])	$cc.='1';
		// Доп. информация
		switch($nxt['type'])
		{
			case 1:
			case 2:
			case 3:
			case 8:
			case 12:
			case 15:
			case 17:
				$r=mysql_query("SELECT `id`, `name` FROM `doc_sklady` WHERE `id`='{$nxt['sklad']}'");
				$data=mysql_fetch_row($r);
				mysql_free_result($r);
				$dop="Склад: $data[1] /$data[0]";
				break;
			case 4:
			case 5:
				$r=mysql_query("SELECT `num`, `name` FROM `doc_kassa` WHERE `num`='{$nxt['bank']}' AND `ids`='bank'");
				$data=mysql_fetch_row($r);
				mysql_free_result($r);
				$dop="Банк: $data[1] /$data[0]";
				break;
			case 6:
			case 7:
			case 9:
				$r=mysql_query("SELECT `num`, `name` FROM `doc_kassa` WHERE `num`='{$nxt['kassa']}' AND `ids`='kassa'");
				$data=mysql_fetch_row($r);
				mysql_free_result($r);
				$dop="Касса: $data[1] /$data[0]";
				break;
			case 10:
			case 11:
			case 13:
			case 14:
				break;
		}

		switch($nxt['type'])
		{
			case 3:
				$r=mysql_query("SELECT `doc_list_pos`.`doc` AS `doc_id`, `doc_list_pos`.`tovar` AS `pos_id`, `doc_list_pos`.`cnt`, (	SELECT SUM(`doc_list_pos`.`cnt`) FROM `doc_list_pos`
				INNER JOIN `doc_list` ON `doc_list_pos`.`doc`=`doc_list`.`id`
				WHERE `doc_list_pos`.`tovar`=`pos_id` AND `doc_list`.`p_doc`=`doc_id` AND `doc_list`.`type`='2' AND `doc_list`.`ok`>'0'
				) AS `r_cnt`
				FROM `doc_list_pos`
				WHERE `doc_list_pos`.`doc`='$nxt[0]'");
				$f=0;
				while($nx=mysql_fetch_row($r))
				{
					if($nx[3]<=0)	continue;
					$f=1;
					if($nx[2]>$nx[3])
					{
						$f=2;
						break;
					}
				}
				if($f==1)	$cl='f_green';
				if($f==2)	$cl='f_brown';
				mysql_free_result($r);
				break;
			case 8:
				$r=mysql_query("SELECT `doc_sklady`.`name` FROM `doc_dopdata`
				LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_dopdata`.`value`
				WHERE `doc_dopdata`.`doc`='$nxt[0]' AND `doc_dopdata`.`param`='na_sklad'");
				$nxt[9]="На ".@mysql_result($r,0,0);
				mysql_free_result($r);
				break;
		}


		if($nxt[2])
		{
			if($nxt[1]==1) 		$pr+=$nxt[7];
			else if($nxt[1]==2)	$ras+=$nxt[7];
			else if($nxt[1]==4)	$pr+=$nxt[7];
			else if($nxt[1]==5)	$ras+=$nxt[7];
			else if($nxt[1]==6) 	$pr+=$nxt[7];
			else if($nxt[1]==7) 	$ras+=$nxt[7];
			else if($nxt[1]==18) 	$ras+=$nxt[7];
		}

		// Проплаты

		if(($nxt[1]==2)&&($nxt[7]>0))
		{
			$add='';
			if($nxt[12]) $add=" OR (`p_doc`='$nxt[12]' AND (`type`='4' OR `type`='6'))";
			$rs=mysql_query("SELECT SUM(`sum`) FROM `doc_list` WHERE
			(`p_doc`='$nxt[0]' AND (`type`='4' OR `type`='6'))
			$add
				AND `ok`>0 AND `p_doc`!='0' GROUP BY `p_doc`");
			if(@$prop=mysql_result($rs,0,0))
			{
				$prop=sprintf("%0.2f",$prop);
				if($prop==$nxt[7])	$cl='f_green';
				else if($prop>$nxt[7])	$cl='f_purple';
				else $cl='f_brown';
			}
			else $cl='f_red';
		}


		$i=1-$i;
		$dp=$motions="";
		if($sel)
		{
			$sm=$nxt[13]*$nxt[14];
			$sm=sprintf("%0.2f",$sm);
			$dp="<td>$nxt[13]<td>$nxt[14]<td>$sm";
		}

		if($nxt[8]) $motions="<a href='' title='На удаление' onclick=\"EditThis('/docj.php?mode=undel&_id=$nxt[0]','mo$nxt[0]'); return false;\"><img src='/img/i_alert.png' alt='На удаление'></a>";
		if($nxt[2]) $motions.=" <img src='/img/i_suc.png' alt='Проведен'>";
		if(!$motions) $motions="<a href='' title='Удалить' onclick=\"EditThis('/docj.php?mode=del&_id=$nxt[0]','mo$nxt[0]'); return false;\"> <img src='/img/i_del.png' alt='Удалить'></a>";

		$nxt[7]=sprintf("%01.2f", $nxt[7]);

		if(!$nxt[4]) $nxt[4]=$nxt[0];

		// Подсветка site
		if($nxt['err_flag'])	$cc.=' f_red';
		else if($nxt[5]=='site')	$cc.=' f_green';


		$deflink="doc.php?mode=body&amp;doc=$nxt[0]";

		$tmpl->AddText("<tr class='$cc pointer'>
		<td align='right' onclick='' class='$cl'>$nxt[4]$nxt[5]<a href='docj.php?mode=tree&amp;doc=$nxt[0]' title='Связи'><img src='img/i_tree.png' alt='Связи'></a>
		<td><a href='$deflink' title='Изменить'><img src='img/i_edit.png' alt='Изменить'></a>
		<td align='center' id='mo$nxt[0]'>$motions<td>$nxt[11]<td>$dop $dp<td>$nxt[9]<td align='right'>$nxt[7]<td>$dt<td>
		<a href='/adm_users.php?mode=view&amp;id=$nxt[6]'>$nxt[10]</a>");
	}
	$tmpl->AddText("</table>");
	$razn=$pr-$ras;
	$pr=sprintf("%0.2f руб.",$pr);
	$ras=sprintf("%0.2f руб.",$ras);
	if($razn<0)
		$razn=sprintf("<span class='c_red'>%0.2f руб.</span>",$razn);
	else
		$razn=sprintf("%0.2f руб.",$razn);

	$tmpl->AddText("Итого: приход: $pr, расход: $ras. Баланс: $razn<br>
	<b>Легенда</b>: строка - <span class='f_green'>с сайта</span>, <span class='f_red'>с ошибкой</span><br>Номер реализации - <span class='f_green'>Оплачено</span>, <span class='f_red'>Не оплачено</span>, <span class='f_brown'>Частично оплачено</span>, <span class='f_purple'>Переплата</span><br>
	Номер заявки - <span class='f_green'>Отгружено</span>, <span class='f_brown'>Частично отгружено</span>
	");

}
else if($mode=="filter")
{
	$tmpl->ajax=1;
	$opt=rcv('opt');
	if($opt=='')
	{
		$date_st=$_SESSION['j_date_st'];
		$date_end=$_SESSION['j_date_end'];

		$sel=$_SESSION['j_select_tov'];
		if($sel==0) $tov="отсутствует";
		else
		{
			$res=@mysql_query("SELECT `doc_base`.`name`,`doc_group`.`name` FROM `doc_base`
			LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
			WHERE `doc_base`.`id`='$sel'");
			$tov=": ".mysql_result($res,0,1)." ".mysql_result($res,0,0);
		}

		$asel=$_SESSION['j_agent'];
		settype($asel,"int");
		if($asel==0) $av='';
		else
		{
			$res=mysql_query("SELECT `name` FROM `doc_agent` WHERE `id`='$asel'");
			$av=mysql_result($res,0,0);
		}

		$tov=$_SESSION['j_select_tov'];
		if($tov==0) $tv="";
		else
		{
			$res=mysql_query("SELECT `name`,`proizv` FROM `doc_base` WHERE `id`='$tov'");
			$tv=mysql_result($res,0,0).":".mysql_result($res,0,1);
		}
		$dsel=$_SESSION['j_select_doc'];
		$subtype=$_SESSION['j_select_subtype'];


		$tmpl->AddText("
		<h1>Фильтры</h1>
		<form action='docj.php'>
		<input type='hidden' name='mode' value='filter'>
		<input type='hidden' name='opt' value='fs'>
		<p class='datetime'>Дата<br>
		от:<input type='text' id='id_pub_date_date' class='vDateField required' name='date_st' size='10' value='$date_st' maxlength='10'><br>
		до:<input type='text' id='id_pub_date_date' class='vDateField required' name='date_end' size='10' value='$date_end' maxlength='10'></p>
		Отбор по типу документа:<br>
		<select name='doc_types' id='dtt'>");
			$ss='';
		if($dsel==0) $ss='selected';
		$tmpl->AddText("<option value='0' $ss>-- без отбора --</option>");
		$res=mysql_query("SELECT * FROM `doc_types` ORDER BY `name`");
		while($nxt=mysql_fetch_row($res))
		{
			$ss='';
			if($dsel==$nxt[0]) $ss='selected';
			$tmpl->AddText("<option value='$nxt[0]' $ss>$nxt[1]</option>");

		}
		$tmpl->AddText("</select>
		<br>
		Подтип:<br>
		<input type='text' id='pds' name='ptip' value='$subtype' onkeydown=\"return AutoFill('/docj.php?mode=filter&opt=pds','pds','ddp')\">
		<a onclick=\"ClearText('pds'); return false;\" href=''><img src='/img/i_del.png' alt='Очистить'></a>
		<div id='ddp' class='dd'></div><br>

		Агент-партнёр:<br>
		<input type='text' id='aga' name='ag' value='$av' onkeydown=\"return AutoFill('/docj.php?mode=filter&opt=ags','aga','dda')\">
		<a onclick=\"ClearText('aga'); return false;\" href=''><img src='/img/i_del.png' alt='Очистить'></a>
		<div id='dda' class='dd'></div><br>

		Товар:<br>
		<input type='text' id='ts' name='tov' value='$tv' onkeydown=\"return AutoFill('/docj.php?mode=filter&opt=ts','ts','ddt')\">
		<a onclick=\"ClearText('ts'); return false;\" href=''><img src='/img/i_del.png' alt='Очистить'></a>
		<div id='ddt' class='dd' style='width:450px;'></div><br>



		<div id='info'></div>
		<br>
		<input type='submit' value='Установить'>
		</form>");
	}
	else if($opt=='fs')
	{
		$tov=rcv('tov');
		if($tov)
		{
			$res=mysql_query("SELECT `id`, `name`, `proizv` FROM `doc_base` WHERE `id`='$tov'");
			$tovar=mysql_fetch_row($res);
			$_SESSION['j_select_tov']=$tovar[0];
			$_SESSION['j_select_tov_name']=$tovar[1];
		}
		else
		{
			$_SESSION['j_select_tov']=0;
			$_SESSION['j_select_tov_name']='';
		}



		header('location: docj.php');
	}

	else if($opt=='fsn')
	{
		$dt=@$_POST['dt'];
		if(is_array($dt))
		{
			$_SESSION['j_need_doctypes']=$dt;
			foreach($_SESSION['j_need_doctypes'] as $id => $line)
			{
				settype($_SESSION['j_need_doctypes'][$id], 'int');
			}
		}
		else $_SESSION['j_need_doctypes']='';

		$date_from=rcv('date_from');
		$_SESSION['j_date_from']=date("Y-m-d",strtotime($date_from));

		$date_to=rcv('date_to');
		$_SESSION['j_date_to']=date("Y-m-d",strtotime($date_to)+(24*60*60-1));

		$_SESSION['j_select_altnum']=rcv('altnum');
		$_SESSION['j_select_subtype']=rcv('subtype');

		$agent_id=rcv('agent_id');
		$agent_name=rcv('agent_name');
		if($agent_name)
		{
			$res=mysql_query("SELECT `id`, `name` FROM `doc_agent` WHERE `id`='$agent_id'");
			$agent=mysql_fetch_row($res);
			$_SESSION['j_agent']=$agent[0];
			$_SESSION['j_agent_name']=$agent[1];
		}
		else
		{
			$_SESSION['j_agent']=0;
			$_SESSION['j_agent_name']='';
		}

		$tov_id=rcv('tov_id');
		if(rcv('tov_name'))
		{
			$res=mysql_query("SELECT `id`, `name`, `proizv` FROM `doc_base` WHERE `id`='$tov_id'");
			$tovar=mysql_fetch_row($res);
			$_SESSION['j_select_tov']=$tovar[0];
			$_SESSION['j_select_tov_name']=$tovar[1];
		}
		else
		{
			$_SESSION['j_select_tov']=0;
			$_SESSION['j_select_tov_name']='';
		}

		$autor_id=rcv('autor_id');
		if(rcv('autor_name'))
		{
			$res=mysql_query("SELECT `id`, `name` FROM `users` WHERE `id`='$autor_id'");
			$autor=mysql_fetch_row($res);
			$_SESSION['j_select_autor_id']=$autor[0];
			$_SESSION['j_select_autor_name']=$autor[1];
		}
		else
		{
			$_SESSION['j_select_autor_id']=0;
			$_SESSION['j_select_autor_name']='';
		}

		$_SESSION['j_select_firm']=round(rcv('firm'));
		$_SESSION['j_select_bank']=round(rcv('bank'));
		$_SESSION['j_select_kassa']=round(rcv('kassa'));
		$_SESSION['j_select_sklad']=round(rcv('sklad'));

		header('location: docj.php');
	}

	else if($opt=='dts')
	{
			$s=rcv('s');
			if($date_st) $_SESSION['j_date_st']=$s;
			$tmpl->AddText("Дата установлена!");

	}
	else if($opt=='ags')
	{
			$s=rcv('s');
			$res=mysql_query("SELECT `id`,`name` FROM `doc_agent` WHERE LOWER(`name`) LIKE LOWER('%$s%') ORDER BY `name` LIMIT 100");
			$row=mysql_numrows($res);
			$tmpl->AddText("<div class='pointer' onclick=\"return AutoFillClick('aga','','dda');\">-- Убрать --</div>");
			while($nxt=mysql_fetch_row($res))
			{
				$i=1;
				$tmpl->AddText("<div class='pointer' onclick=\"return AutoFillClick('aga','$nxt[1]','dda');\">$nxt[1]</div>");
			}
			if(!$i) $tmpl->AddText("<b>Искомая комбинация не найдена!");

	}
	else if($opt=='ts')
	{
			$s=rcv('s');
			$tov=split(':',$s);
			$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`cost`, `doc_base_dop`.`analog`
			FROM `doc_base`
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
			WHERE `doc_base`.`name` LIKE '%$tov[0]%' AND `doc_base`.`proizv` LIKE '%$tov[1]%' ORDER BY `doc_base`.`name` LIMIT 100");

			echo mysql_error();
			$tmpl->AddText("<table width='100%'>
			<tr><th>наим.<th>произв.<th>Цена<th>Аналог
			<tr class='pointer' onclick=\"return AutoFillClick('ts','','ddt');\"><td colspan='4'>-- Убрать --");
			while($nxt=mysql_fetch_row($res))
			{
				$i=1;
				$tmpl->AddText("<tr class='pointer' onclick=\"return AutoFillClick('ts','$nxt[1]:$nxt[2]','ddt');\"><td>$nxt[1]<td>$nxt[2]<td>$nxt[3]<td>$nxt[4]");
			}
			if(!$i) $tmpl->AddText("<b>Искомая комбинация не найдена!");

	}
	else if($opt=='pds')
	{
			$s=rcv('s');
			$res=mysql_query("SELECT `id`,`subtype` FROM `doc_list` WHERE LOWER(`subtype`) LIKE LOWER('%$s%')GROUP BY `subtype`  ORDER BY `subtype`  LIMIT 100");
			$row=mysql_numrows($res);
			$tmpl->AddText("<div class='pointer' onclick=\"return AutoFillClick('pds','','ddp');\">-- Убрать --</div>");
			while($nxt=mysql_fetch_row($res))
			{
				$i=1;
				$tmpl->AddText("<div class='pointer' onclick=\"return AutoFillClick('pds','$nxt[1]','ddp');\">$nxt[1]</div>");
			}
			if(!$i) $tmpl->AddText("<b>Искомая комбинация не найдена!");
	}
	else if($opt=='firm')
	{
		$_SESSION['firm']=$firm=rcv('firm');
		if($firm)
			mysql_query("REPLACE `users_data` (`uid`, `param`, `value`) VALUES ('$uid', 'firm_id', '$firm') ");
		header('location: docj.php');
	}
}
else if($mode=="del")
{
	$tmpl->ajax=1;
	$_id=rcv('_id');
	$ok=rcv('ok');
	settype($_id,"int");
	$tim=time();
	if(!isAccess('doc_list','delete'))	throw new AccessException("");
	$res=mysql_query("SELECT `id` FROM `doc_list` WHERE `p_doc`='$_id' AND `mark_del`='0'");
	if(!mysql_num_rows($res)) // Если есть потомки - нельзя удалять
	{
		if(!$ok)	$tmpl->AddText("Удалить?<a href=''  onclick=\"EditThis('/docj.php?mode=del&_id=$_id&ok=1','mo$_id'); return false;\">Да!</a>");
		else
		{
			$res=mysql_query("UPDATE `doc_list` SET `mark_del`='$tim' WHERE `id`='$_id'");
			if($res)
			$tmpl->AddText("Установлена пометка на удаление!");
			else $tmpl->msg("Не удалось отметить!","err");
			doc_log("MARKDELETE doc:$_id","doc:$_id");

		}
	}
	else	$tmpl->AddText("Есть подчинённые не удалённые документы. Удаление невозможно.");
}
else if($mode=="undel")
{
	$tmpl->ajax=1;
	$_id=rcv('_id');
	$ok=rcv('ok');
	settype($_id,"int");
	if(!isAccess('doc_list','delete'))	throw new AccessException("");
	if(!$ok)
	$tmpl->AddText("Отменить удаление?<br><a href='' onclick=\"EditThis('/docj.php?mode=undel&_id=$_id&ok=1','mo$_id'); return false;\">Да, отменить!</a>");
	else
	{
		$res=mysql_query("UPDATE `doc_list` SET `mark_del`='0' WHERE `id`='$_id'");
		if($res)
		$tmpl->AddText("Убрана пометка!");
		else $tmpl->msg("Не удалось убрать метку!","err");
		doc_log("UNDELETE doc:$_id","doc:$_id");

	}
}
else if($mode=='log')
{
	$doc=rcv('doc');
	$res=mysql_query("SELECT `doc_log`.`motion`, `doc_log`.`desc`, `doc_log`.`time`, `users`.`name`, `doc_log`.`ip`
	FROM `doc_log`
	LEFT JOIN `users` ON `users`.`id`=`doc_log`.`user`
	WHERE `doc_log`.`object`='doc' AND `doc_log`.`object_id`='$doc'");
	$tmpl->AddText("<h1>История документа $doc</h1>
	<table width=100%>
	<tr><th>Выполненное действие<th>Описание действия<th>Дата<th>Пользователь<th>IP");
	$i=0;
	while($nxt=mysql_fetch_row($res))
	{
		$i=1-$i;
		$tmpl->AddText("<tr class='lin$i'><td>$nxt[0]<td>$nxt[1]<td>$nxt[2]<td>$nxt[3]<td>$nxt[4]");

	}
	$tmpl->AddText("</table>");

}
else if($mode=="tree")
{
	doc_menu("");
	$doc=rcv('doc');
	$pdoc=GetRootDocument($doc);
	$tmpl->AddText("<h1>Структура для $doc с $pdoc</h1>");
	DrawSubTreeDocument($pdoc,$doc);
}
else if($mode=='print')
{
	$tmpl->LoadTemplate('print');
	$tmpl->SetText("<h1>Реестр документов</h1>");

	$t_from=strtotime($_SESSION['j_date_from']);
	$t_to=strtotime($_SESSION['j_date_to'])+60*60*24-1;

	$info='<b>Параметры:</b> Только проведённые, <b>С</b> '.$_SESSION['j_date_from'].' <b>по</b> '.$_SESSION['j_date_to'];
	$ds='';
	$asel=@$_SESSION['j_agent'];
	settype($asel,"int");
	if($asel)
	{
		$ds.=" AND `doc_list`.`agent`='$asel'";
		$info.=", <b>агент:</b> {$_SESSION['j_agent_name']}";
	}

	if(is_array(@$_SESSION['j_need_doctypes']))
	{
		$res=mysql_query("SELECT `id`, `name` FROM `doc_types` ORDER BY `id`");
		$doc_names=array();
		while($nxt=mysql_fetch_row($res))	$doc_names[$nxt[0]]=$nxt[1];

		$info.=", <b>документы: </b> ";
		$ts='';
		foreach($_SESSION['j_need_doctypes'] as $id => $line)
		{
			if(!$ts)	$ts="`doc_list`.`type`='$line'";
			else		$ts.="OR `doc_list`.`type`='$line'";

			$info.="{$doc_names[$line]} / ";
		}
		$ds.=" AND ($ts) ";
	}
	if(@$_SESSION['j_select_subtype'])
	{
		$ds.=" AND `doc_list`.`subtype`='{$_SESSION['j_select_subtype']}'";
		$info.=", <b>подтип:</b> {$_SESSION['j_select_subtype']}";
	}
	if(@$_SESSION['j_select_altnum'])
	{
		$ds.=" AND `doc_list`.`altnum`='{$_SESSION['j_select_altnum']}'";
		$info.=", <b>альт.номер:</b> {$_SESSION['j_select_altnum']}";
	}
	if(@$_SESSION['j_select_sklad'])
	{
		$ds.="AND `doc_list`.`sklad`='{$_SESSION['j_select_sklad']}'";
		$info.=", <b>склад:</b> {$_SESSION['j_select_sklad_name']}";
	}
	if(@$_SESSION['j_select_bank'])
	{
		$ds.="AND `doc_list`.`bank`='{$_SESSION['j_select_bank']}'";
		$info.=", <b>банк:</b> {$_SESSION['j_select_bank_name']}";
	}
	if(@$_SESSION['j_select_kassa'])
	{
		$ds.="AND `doc_list`.`kassa`='{$_SESSION['j_select_kassa']}'";
		$info.=", <b>касса:</b> {$_SESSION['j_select_kassa_name']}";
	}
	if(@$_SESSION['j_select_firm'])
	{
		$ds.="AND `doc_list`.`firm_id`='{$_SESSION['j_select_firm']}'";
		$info.=", <b>организация:</b> {$_SESSION['j_select_firm_name']}";

		$res=mysql_query("SELECT `firm_skin` FROM `doc_vars` WHERE `id`='{$_SESSION['j_select_firm']}'");
		$firm_vars=mysql_fetch_assoc($res);
		if($firm_vars['firm_skin'])
			$tmpl->LoadTemplate($firm_vars['firm_skin']);
	}
	if(@$_SESSION['j_select_autor_id'])
	{
		$ds.="AND `doc_list`.`user`='{$_SESSION['j_select_autor_id']}'";
		$info.=", <b>автор:</b> {$_SESSION['j_select_autor_name']}";

	}

	$sel=@$_SESSION['j_select_tov'];
	if(!$sel)
	{
		$sql="SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`ok`, `doc_list`.`date`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`user`, `doc_list`.`sum`, `doc_list`.`mark_del`, `doc_agent`.`name`, `users`.`name`, `doc_types`.`name`, `doc_list`.`p_doc`, `doc_list`.`kassa`, `doc_list`.`bank`, `doc_list`.`sklad`, `doc_list`.`err_flag`
		FROM `doc_list`
		LEFT JOIN `doc_agent` ON `doc_list`.`agent`=`doc_agent`.`id`
		LEFT JOIN `users` ON `users`.`id`=`doc_list`.`user`
		LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		WHERE `doc_list`.`date`>='$t_from' AND `doc_list`.`date`<='$t_to' AND `doc_list`.`ok`>0 $ds
		ORDER by `doc_list`.`date` DESC";
		$dp='';
	}
	else
	{
		$sql="SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`ok`, `doc_list`.`date`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`user`, `doc_list`.`sum`, `doc_list`.`mark_del`, `doc_agent`.`name`, `users`.`name`, `doc_types`.`name`, `doc_list`.`p_doc`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_list`.`kassa`, `doc_list`.`bank`, `doc_list`.`sklad`, `doc_list`.`err_flag`
		FROM `doc_list`
		LEFT JOIN `doc_agent` ON `doc_list`.`agent`=`doc_agent`.`id`
		LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		LEFT JOIN `users` ON `users`.`id`=`doc_list`.`user`
		INNER JOIN `doc_list_pos` ON `doc_list_pos`.`tovar`='$sel' AND `doc_list`.`id`=`doc_list_pos`.`doc`
		WHERE  `doc_list`.`date`>='$t_from' AND `doc_list`.`date`<='$t_to' AND `doc_list`.`ok`>0  $ds
		ORDER by `doc_list`.`date` DESC";
		$dp="<th>Кол-во<th>Цена<th>Сумма";
		$info.=", <b>товар:</b> {$_SESSION['j_select_tov_name']}";
	}
	$res=mysql_query($sql);
	if(mysql_errno())	throw new MysqlException("Не удалось получить список документов!".mysql_error());
	$row=mysql_num_rows($res);

	$i=0;
	$pr=$ras=0;

	$tmpl->AddText("<h4'>$info</h4>");

	$tmpl->AddText("<table width='100%' cellspacing='1'><tr>
	<th width='75'>Id<th width='20'>№<th>Документ<th>Дата<th>Агент<th>Сумма<th>Автор<th>Информация $dp");
	while($nxt=mysql_fetch_array($res))
	{
		$dop=$cl='';
		$dt=date("d.m.Y H:i:s",$nxt[3]);
		$cc="lin$i";
		if(@$uid==$nxt[6])	$cc.='1';
		// Доп. информация
		switch($nxt['type'])
		{
			case 1:
			case 2:
			case 3:
			case 8:
			case 12:
			case 15:
			case 17:
				$r=mysql_query("SELECT `id`, `name` FROM `doc_sklady` WHERE `id`='{$nxt['sklad']}'");
				$data=mysql_fetch_row($r);
				mysql_free_result($r);
				$dop="Склад: $data[1] /$data[0]";
				break;
			case 4:
			case 5:
				$r=mysql_query("SELECT `num`, `name` FROM `doc_kassa` WHERE `num`='{$nxt['bank']}' AND `ids`='bank'");
				$data=mysql_fetch_row($r);
				mysql_free_result($r);
				$dop="Банк: $data[1] /$data[0]";
				break;
			case 6:
			case 7:
			case 9:
				$r=mysql_query("SELECT `num`, `name` FROM `doc_kassa` WHERE `num`='{$nxt['kassa']}' AND `ids`='kassa'");
				$data=mysql_fetch_row($r);
				mysql_free_result($r);
				$dop="Касса: $data[1] /$data[0]";
				break;
			case 10:
			case 11:
			case 13:
			case 14:
				break;
		}

		switch($nxt['type'])
		{
			case 3:
				$r=mysql_query("SELECT `doc_list_pos`.`doc` AS `doc_id`, `doc_list_pos`.`tovar` AS `pos_id`, `doc_list_pos`.`cnt`, (	SELECT SUM(`doc_list_pos`.`cnt`) FROM `doc_list_pos`
				INNER JOIN `doc_list` ON `doc_list_pos`.`doc`=`doc_list`.`id`
				WHERE `doc_list_pos`.`tovar`=`pos_id` AND `doc_list`.`p_doc`=`doc_id` AND `doc_list`.`type`='2' AND `doc_list`.`ok`>'0'
				) AS `r_cnt`
				FROM `doc_list_pos`
				WHERE `doc_list_pos`.`doc`='$nxt[0]'");
				$f=0;
				while($nx=mysql_fetch_row($r))
				{
					if($nx[3]<=0)	continue;
					$f=1;
					if($nx[2]>$nx[3])
					{
						$f=2;
						break;
					}
				}
				if($f==1)	$cl='f_green';
				if($f==2)	$cl='f_brown';
				mysql_free_result($r);
				break;
			case 8:
				$r=mysql_query("SELECT `doc_sklady`.`name` FROM `doc_dopdata`
				LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_dopdata`.`value`
				WHERE `doc_dopdata`.`doc`='$nxt[0]' AND `doc_dopdata`.`param`='na_sklad'");
				$nxt[9]="На ".@mysql_result($r,0,0);
				mysql_free_result($r);
				break;
		}


		if($nxt[2])
		{
			if($nxt[1]==1) 		$pr+=$nxt[7];
			else if($nxt[1]==2)	$ras+=$nxt[7];
			else if($nxt[1]==4)	$pr+=$nxt[7];
			else if($nxt[1]==5)	$ras+=$nxt[7];
			else if($nxt[1]==6) 	$pr+=$nxt[7];
			else if($nxt[1]==7) 	$ras+=$nxt[7];
			else if($nxt[1]==18) 	$ras+=$nxt[7];
		}

		$dp="";
		if($sel)
		{
			$sm=$nxt[13]*$nxt[14];
			$sm=sprintf("%0.2f",$sm);
			$dp="<td>$nxt[13]<td>$nxt[14]<td>$sm";
		}

		$nxt[7]=sprintf("%01.2f", $nxt[7]);

		if(!$nxt[4]) $nxt[4]=$nxt[0];

		$tmpl->AddText("<tr><td>$nxt[0]<td align='right'>$nxt[4]$nxt[5]<td>$nxt[11]<td>$dt<td>$nxt[9]<td align='right'>$nxt[7]<td>$nxt[10]<td>$dop $dp</tr>");
	}
	$tmpl->AddText("</table>");
	$razn=$pr-$ras;
	$pr=sprintf("%0.2f руб.",$pr);
	$ras=sprintf("%0.2f руб.",$ras);
	if($razn<0)
		$razn=sprintf("<span class='c_red'>%0.2f руб.</span>",$razn);
	else
		$razn=sprintf("%0.2f руб.",$razn);

	$tmpl->AddText("Итого: приход: $pr, расход: $ras. Баланс: $razn<br>");

}
else doc_log("ERROR","docj.php: Неверный mode!");




$tmpl->write();


?>
