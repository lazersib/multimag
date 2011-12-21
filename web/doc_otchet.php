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
$tmpl->SetTitle('Отчёты');

SafeLoadTemplate($CONFIG['site']['inner_skin']);
$tmpl->HideBlock('left');

function get_otch_links()
{
	return array(
	'doc_otchet.php?mode=agent_bez_prodaj' => 'Агенты без продаж',
	'doc_otchet.php?mode=sverka' => 'Акт сверки',
	'doc_otchet.php?mode=sverka_op' => 'Акт сверки (оперативный)',
	'doc_otchet.php?mode=balance' => 'Баланс',
	'doc_otchet.php?mode=dolgi' => 'Долги',
	'doc_otchet.php?mode=ostatki' => 'Остатки на складе',
	'doc_otchet.php?mode=ostatkinadatu' => 'Остатки на складе на дату',
	'doc_otchet.php?mode=agent_otchet' => 'Отчет по агенту',
	'doc_otchet.php?mode=bankday' => 'Отчёт за день по банку',
	'doc_otchet.php?mode=kassday' => 'Отчёт за день по кассе',
	'doc_otchet.php?mode=img_otchet' => 'Отчет по изображениям',
	'doc_otchet.php?mode=komplekt' => 'Отчет по комплектующим',
	'doc_otchet.php?mode=proplaty' => 'Отчет по проплатам',
	'doc_otchet.php?mode=prod' => 'Отчёт по продажам',
	'doc_otchet.php?mode=bezprodaj' => 'Отчёт по товарам без продаж',
	'doc_otchet.php?mode=doc_reestr' => 'Реестр документов',
	'doc_otchet.php?mode=fin_otchet' => 'Сводный финансовый отчёт',
	'doc_otchet.php' => 'Другие отчёты');
}

function otch_list()
{
	return "
	<a href='doc_otchet.php?mode=bezprodaj'><div>Агенты без продаж</div></a>
	<a href='doc_otchet.php?mode=sverka'><div>Акт сверки</div></a>
	<a href='doc_otchet.php?mode=balance'><div>Баланс</div></a>
	<a href='doc_otchet.php?mode=dolgi'><div>Долги</div></a>
	<a href='doc_otchet.php?mode=ostatki'><div>Остатки на складе</div></a>
	<a href='doc_otchet.php?mode=ostatkinadatu'><div>Остатки на складе на дату</div></a>
	<a href='doc_otchet.php?mode=agent_otchet'><div>Отчет по агенту</div></a>
	<a href='doc_otchet.php?mode=bankday'><div>Отчёт за день по банку</div></a>
	<a href='doc_otchet.php?mode=kassday'><div>Отчёт за день по кассе</div></a>
	<a href='doc_otchet.php?mode=komplekt'><div>Отчет по комплектующим</div></a>
	<a href='doc_otchet.php?mode=prod'><div>Отчёт по продажам</div></a>
	<a href='doc_otchet.php?mode=proplaty'><div>Отчет по проплатам</div></a>
	<a href='doc_otchet.php?mode=bezprodaj'><div>Отчёт по товарам без продаж</div></a>
	<a href='doc_otchet.php?mode=cost'><div>Отчёт по ценам</div></a>
	<a href='doc_otchet.php?mode=doc_reestr'><div>Реестр документов</div></a>
	<a href='doc_otchet.php?mode=fin_otchet'><div>Сводный финансовый отчёт</div></a>
	<hr>
	<a href='doc_otchet.php'><div>Другие отчёты</div></a>";
}

function otch_divs()
{
	$str='';
	foreach(get_otch_links() as $link => $text)
		$str.="<div onclick='window.location=\"$link\"'>$text</div>";
	return $str;
}

function draw_groups_tree($level)
{
	$ret='';
	$res=mysql_query("SELECT `id`, `name`, `desc` FROM `doc_group` WHERE `pid`='$level' AND `hidelevel`='0' ORDER BY `name`");
	$i=0;
	$r='';
	if($level==0) $r='IsRoot';
	$cnt=mysql_num_rows($res);
	while($nxt=mysql_fetch_row($res))
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
	$tmpl->AddStyle(".scroll_block
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
	$tmpl->AddText("<script type='text/javascript'>
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

class Report_Store
{
	function Form()
	{
		global $tmpl;
		$tmpl->SetText("<h1>Остатки товара на складе</h1>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='ostatki'>
		<input type='hidden' name='opt' value='make'>
		<fieldset><legend>Отобразить цены</legend>");
		$res=mysql_query("SELECT `id`, `name` FROM `doc_cost` ORDER BY `id`");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список цен");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<label><input type='checkbox' name='cost[$nxt[0]]' value='$nxt[0]'>$nxt[1]</label><br>");
		}
		$tmpl->AddText("</fieldset><br>
		Группа товаров:<br>");
		GroupSelBlock();
		$tmpl->AddText("<button type='submit'>Создать отчет</button></form>");
	}

	function MakeHTML()
	{
		global $tmpl;
		$gs=rcv('gs');
		$g=@$_POST['g'];
		$cost=@$_POST['cost'];
		$tmpl->LoadTemplate('print');
		$tmpl->SetText("<h1>Остатки товара на складе</h1>
		<table width=100%><tr><th>N<th>Наименование<th>Количество<th>Актуальная цена<br>поступления<th>Базовая цена<th>Наценка<th>Сумма по АЦП<th>Сумма по базовой");
		$col_count=8;
		if(is_array($cost))
		{
			$res=mysql_query("SELECT `id`, `name` FROM `doc_cost` ORDER BY `name`");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список цен");
			$costs=array();
			while($nxt=mysql_fetch_row($res))	$costs[$nxt[0]]=$nxt[1];
			foreach($cost as $id => $value)
			{
				$tmpl->AddText("<th>".$costs[$id]);
				$col_count++;
			}
		}
		$sum=$bsum=$summass=0;
		$res_group=mysql_query("SELECT `id`, `name` FROM `doc_group` ORDER BY `id`");
		while($group_line=mysql_fetch_assoc($res_group))
		{
			if($gs && is_array($g))
				if(!in_array($group_line['id'],$g))	continue;

			$tmpl->AddText("<tr><td colspan='$col_count' class='m1'>{$group_line['id']}. {$group_line['name']}</td></tr>");


			$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`cost`,
			(SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `count`,
			`doc_base_dop`.`mass`
			FROM `doc_base`
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
			WHERE `doc_base`.`group`='{$group_line['id']}'
			ORDER BY `doc_base`.`name`");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список наименований");

			while($nxt=mysql_fetch_row($res))
			{
				if($nxt[3]<=0) continue;
				$act_cost=sprintf('%0.2f',GetInCost($nxt[0]));
				$cost_p=sprintf("%0.2f",$nxt[2]);
				$sum_p=sprintf("%0.2f",$act_cost*$nxt[3]);
				$bsum_p=sprintf("%0.2f",$nxt[2]*$nxt[3]);
				$sum+=$act_cost*$nxt[3];
				$bsum+=$nxt[2]*$nxt[3];
				if($nxt[3]<0) $nxt[3]='<b>'.$nxt[3].'</b/>';
				$summass+=$nxt[3]*$nxt[4];

				$nac=sprintf("%0.2f р. (%0.2f%%)",$cost_p-$act_cost,($cost_p/$act_cost)*100-100);

				$tmpl->AddText("<tr><td>$nxt[0]<td>$nxt[1]<td>$nxt[3]<td>$act_cost р.<td>$cost_p р.<td>$nac<td>$sum_p р.<td>$bsum_p р.");
				if(is_array($cost))
				{
					foreach($cost as $id => $value)
					{
						$tmpl->AddText("<td>".GetCostPos($nxt[0], $id));
					}
				}
			}
		}
		$tmpl->AddText("<tr><td colspan='6'><b>Итого:</b><td>$sum р.<td>$bsum р.
		</table><h3>Общая масса склада: $summass кг.</h3>");
	}

	function Run($opt)
	{
		if($opt=='')	$this->Form();
		else		$this->MakeHTML();
	}
};

class Report_KassDay
{
	function Form()
	{
		global $tmpl;
		$curdate=date("Y-m-d");
		$tmpl->AddText("<h1>Отчёт по кассе за текущий день</h1>
		<link rel='stylesheet' href='/css/jquery/ui/themes/base/jquery.ui.all.css'>
		<script src='/css/jquery/ui/jquery.ui.core.js'></script>
		<script src='/css/jquery/ui/jquery.ui.widget.js'></script>
		<script src='/css/jquery/ui/jquery.ui.datepicker.js'></script>
		<script src='/css/jquery/ui/i18n/jquery.ui.datepicker-ru.js'></script>
		<form action=''>
		<input type='hidden' name='mode' value='kassday'>
		<input type='hidden' name='opt' value='ok'>
		Выберите кассу:<br>
		<select name='kass'>");
		$res=mysql_query("SELECT `num`, `name` FROM `doc_kassa` WHERE `ids`='kassa'  ORDER BY `num`");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<option value='$nxt[0]'>$nxt[1]</option>");
		}
		$tmpl->AddText("</select><br>
		Выберите дату:<br>
		<input type='text' name='date' id='datepicker_f' value='$curdate'><br>
		<button type='submit'>Сформировать</button></form>");
	}

	function MakeHTML()
	{
		global $tmpl;
		$tmpl->LoadTemplate('print');
		$dt=rcv('date');
		$kass=rcv('kass');
		$res=mysql_query("SELECT `num`, `name` FROM `doc_kassa` WHERE `ids`='kassa'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список касс");
		$kass_list=array();
		while($nxt=mysql_fetch_row($res))	$kass_list[$nxt[0]]=$nxt[1];
		$tmpl->AddText("<h1>Отчёт по кассе {$kass_list[$kass]} за $dt</h1>");
		$daystart=strtotime("$dt 00:00:00");
		$dayend=strtotime("$dt 23:59:59");
		$tmpl->AddText("<table width='100%'><tr><th>ID<th>Время<th>Документ<th>Приход<th>Расход<th>В кассе");
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`sum`, `doc_list`.`date`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_types`.`name`, `doc_agent`.`name`, `doc_list`.`p_doc`, `t`.`name`, `p`.`altnum`, `p`.`subtype`, `p`.`date`, `p`.`sum`, `doc_list`.`kassa`, `doc_dopdata`.`value` AS `vk_value`
		FROM `doc_list`
		LEFT JOIN `doc_agent`		ON `doc_agent`.`id` = `doc_list`.`agent`
		INNER JOIN `doc_types`		ON `doc_types`.`id` = `doc_list`.`type`
		LEFT JOIN `doc_list` AS `p`	ON `p`.`id`=`doc_list`.`p_doc`
		LEFT JOIN `doc_types` AS `t`	ON `t`.`id` = `p`.`type`
		LEFT JOIN `doc_dopdata`		ON `doc_dopdata`.`doc`=`doc_list`.`id` AND `doc_dopdata`.`param`='v_kassu'
		WHERE `doc_list`.`ok`>'0' AND ( `doc_list`.`type`='6' OR `doc_list`.`type`='7' OR `doc_list`.`type`='9')
		AND (`doc_list`.`kassa`='$kass' OR `doc_dopdata`.`value`='$kass')
		ORDER BY `doc_list`.`date`");
		$sum=$daysum=$prix=$rasx=0;
		$flag=0;
		while($nxt=mysql_fetch_array($res))
		{
			$csum_p=$csum_r='';
			if( !$flag && $nxt[3]>=$daystart && $nxt[3]<=$dayend)
			{
				$flag=1;
				$sum_p=sprintf("%0.2f руб.",$sum);
				$tmpl->AddText("<tr><td colspan=5><b>На начало дня</b><td align='right'><b>$sum_p</b>");
			}
			if($nxt[1]==6)		$sum+=$nxt[2];
			else if($nxt[1]==7)	$sum-=$nxt[2];
			else if($nxt[1]==9)
			{
				if($nxt['kassa']==$kass)
					$sum-=$nxt[2];
				else	$sum+=$nxt[2];
			}
			if($nxt[3]>=$daystart && $nxt[3]<=$dayend)
			{
				if($nxt[1]==6)
				{
					$daysum+=$nxt[2];
					$prix+=$nxt[2];
					$csum_p=sprintf("%0.2f руб.",$nxt[2]);
				}
				else if($nxt[1]==7)
				{
					$daysum-=$nxt[2];
					$rasx+=$nxt[2];
					$csum_r=sprintf("%0.2f руб.",$nxt[2]);
				}
				else
				{
					if($nxt['kassa']==$kass)
					{
						$daysum-=$nxt[2];
						$rasx+=$nxt[2];
						$csum_r=sprintf("%0.2f руб.",$nxt[2]);
					}
					else
					{
						$daysum+=$nxt[2];
						$prix+=$nxt[2];
						$csum_p=sprintf("%0.2f руб.",$nxt[2]);
					}
				}
				if($nxt[8])	$sadd="<br><i>к $nxt[9] N$nxt[10]$nxt[11] от ".date("d-m-Y H:i:s",$nxt[12])." на сумму ".sprintf("%0.2f руб",$nxt[13])."</i>";
				else		$sadd='';
				if($nxt[1]==6)		$sadd.="<br>от $nxt[7]";
				else if($nxt[1]==7)	$sadd.="<br>для $nxt[7]";
				else if($nxt[1]==9)
				{
					if($nxt['kassa']==$kass)	$sadd.="<br>в кассу {$kass_list[$nxt['vk_value']]}";
					else				$sadd.="<br>из кассы {$kass_list[$nxt['kassa']]}";
				}
				$dt=date("H:i:s",$nxt[3]);
				$sum_p=sprintf("%0.2f руб.",$sum);

				$tmpl->AddText("<tr><td>$nxt[0]<td>$dt<td>$nxt[6] N$nxt[4]$nxt[5]   $sadd<td align='right'>$csum_p<td align='right'>$csum_r<td align='right'>$sum_p</tr>");
			}
		}
		if( !$flag)
		{
				$sum_p=sprintf("%0.2f руб.",$sum);
				$tmpl->AddText("<tr><td colspan=5><b>На начало дня</b><td align='right'><b>$sum_p</b>");
		}
		if($flag)
		{
			$dsum_p=sprintf("%0.2f руб.",$daysum);
			$psum_p=sprintf("%0.2f руб.",$prix);
			$rsum_p=sprintf("%0.2f руб.",$rasx);
			$tmpl->AddText("<tr><td>-<td>-<td><b>На конец дня</b><td align='right'><b>$psum_p</b><td align='right'><b>$rsum_p</b><td align='right'><b>$sum_p</b>");
			$tmpl->AddText("<tr><td>-<td>-<td><b>Разница за смену</b><td align='right' colspan=3><b>$dsum_p</b>");
 		}
 		else	$tmpl->AddText("<tr><td>-<td>-<td><b>Нет данных по балансу на выбранную дату</b><td align='right'><b>нет данных</b><td align='right'><b>нет данных</b><td align='right'><b>нет данных</b>");


 		$res=mysql_query("SELECT `name` FROM `users` WHERE `id`='{$_SESSION['uid']}'");
 		$nm=mysql_result($res,0,0);

 		$tmpl->AddText("</table><br><br>
 		Cоответствие сумм подтверждаю ___________________ ($nm)");
	}

	function Run($opt)
	{
		if($opt=='')	$this->Form();
		else		$this->MakeHTML();
	}
};

class Report_BankDay
{
	function Form()
	{
		global $tmpl;
		$curdate=date("Y-m-d");
		$tmpl->AddText("<h1>Отчёт по банку за текущий день (вариант 2)</h1>
		<link rel='stylesheet' href='/css/jquery/ui/themes/base/jquery.ui.all.css'>
		<script src='/css/jquery/ui/jquery.ui.core.js'></script>
		<script src='/css/jquery/ui/jquery.ui.widget.js'></script>
		<script src='/css/jquery/ui/jquery.ui.datepicker.js'></script>
		<script src='/css/jquery/ui/i18n/jquery.ui.datepicker-ru.js'></script>
		<form action=''>
		<input type='hidden' name='mode' value='bankday'>
		<input type='hidden' name='opt' value='ok'>
		Выберите кассу:<br>
		<select name='kass'>");
		$res=mysql_query("SELECT `num`, `name` FROM `doc_kassa` WHERE `ids`='bank'  ORDER BY `num`");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<option value='$nxt[0]'>$nxt[1]</option>");
		}
		$tmpl->AddText("</select><br>
		Выберите дату:<br>
		<input type='text' name='date' id='datepicker_f' value='$curdate'><br>
		<button type='submit'>Сформировать</button></form>");
	}

	function MakeHTML()
	{
		global $tmpl;
		$tmpl->LoadTemplate('print');
		$dt=rcv('date');
		$kass=rcv('kass');
		$res=mysql_query("SELECT `num`, `name` FROM `doc_kassa` WHERE `ids`='bank'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список банок");
		$kass_list=array();
		while($nxt=mysql_fetch_row($res))	$kass_list[$nxt[0]]=$nxt[1];
		$tmpl->AddText("<h1>Отчёт по банку {$kass_list[$kass]} за $dt</h1>");
		$daystart=strtotime("$dt 00:00:00");
		$dayend=strtotime("$dt 23:59:59");
		$tmpl->AddText("<table width='100%'><tr><th>ID<th>Время<th>Документ<th>Приход<th>Расход<th>В банке");
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`sum`, `doc_list`.`date`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_types`.`name`, `doc_agent`.`name`, `doc_list`.`p_doc`, `t`.`name`, `p`.`altnum`, `p`.`subtype`, `p`.`date`, `p`.`sum`, `doc_list`.`bank`
		FROM `doc_list`
		LEFT JOIN `doc_agent`		ON `doc_agent`.`id` = `doc_list`.`agent`
		INNER JOIN `doc_types`		ON `doc_types`.`id` = `doc_list`.`type`
		LEFT JOIN `doc_list` AS `p`	ON `p`.`id`=`doc_list`.`p_doc`
		LEFT JOIN `doc_types` AS `t`	ON `t`.`id` = `p`.`type`
		WHERE `doc_list`.`ok`>'0' AND ( `doc_list`.`type`='4' OR `doc_list`.`type`='5')
		AND `doc_list`.`bank`='$kass'
		ORDER BY `doc_list`.`date`");
		if(mysql_errno())	throw new MysqlException("Не удалось получить данные отчёта".mysql_error());
		$sum=$daysum=$prix=$rasx=0;
		$flag=0;
		$lastdate=0;
		while($nxt=mysql_fetch_array($res))
		{
			$lastdate=$nxt[3];
			$csum_p=$csum_r='';
			if( !$flag && $nxt[3]>=$daystart && $nxt[3]<=$dayend)
			{
				$flag=1;
				$sum_p=sprintf("%0.2f руб.",$sum);
				$tmpl->AddText("<tr><td colspan=5><b>На начало дня</b><td align='right'><b>$sum_p</b>");
			}
			if($nxt[1]==4)		$sum+=$nxt[2];
			else if($nxt[1]==5)	$sum-=$nxt[2];
			if($nxt[3]>=$daystart && $nxt[3]<=$dayend)
			{
				if($nxt[1]==4)
				{
					$daysum+=$nxt[2];
					$prix+=$nxt[2];
					$csum_p=sprintf("%0.2f руб.",$nxt[2]);
				}
				else if($nxt[1]==5)
				{
					$daysum-=$nxt[2];
					$rasx+=$nxt[2];
					$csum_r=sprintf("%0.2f руб.",$nxt[2]);
				}
				if($nxt[8])	$sadd="<br><i>к $nxt[9] N$nxt[10]$nxt[11] от ".date("d-m-Y H:i:s",$nxt[12])." на сумму ".sprintf("%0.2f руб",$nxt[13])."</i>";
				else		$sadd='';
				if($nxt[1]==4)		$sadd.="<br>от $nxt[7]";
				else if($nxt[1]==5)	$sadd.="<br>для $nxt[7]";

				$dt=date("H:i:s",$nxt[3]);
				$sum_p=sprintf("%0.2f руб.",$sum);

				$tmpl->AddText("<tr><td>$nxt[0]<td>$dt<td>$nxt[6] N$nxt[4]$nxt[5]   $sadd<td align='right'>$csum_p<td align='right'>$csum_r<td align='right'>$sum_p</tr>");
			}
		}
		if( !$flag && $lastdate<=$dayend)
		{
				$sum_p=sprintf("%0.2f руб.",$sum);
				$tmpl->AddText("<tr><td colspan=5><b>На начало дня</b><td align='right'><b>$sum_p</b>");
		}
		if($flag)
		{
			$dsum_p=sprintf("%0.2f руб.",$daysum);
			$psum_p=sprintf("%0.2f руб.",$prix);
			$rsum_p=sprintf("%0.2f руб.",$rasx);
			$tmpl->AddText("<tr><td>-<td>-<td><b>На конец дня</b><td align='right'><b>$psum_p</b><td align='right'><b>$rsum_p</b><td align='right'><b>$sum_p</b>");
			$tmpl->AddText("<tr><td>-<td>-<td><b>Разница за смену</b><td align='right' colspan=3><b>$dsum_p</b>");
 		}
 		else	$tmpl->AddText("<tr><td>-<td>-<td><b>Нет данных по балансу на выбранную дату</b><td align='right'><b>нет данных</b><td align='right'><b>нет данных</b><td align='right'><b>нет данных</b>");

 		$res=mysql_query("SELECT `name` FROM `users` WHERE `id`='{$_SESSION['uid']}'");
 		$nm=mysql_result($res,0,0);

 		$tmpl->AddText("</table><br><br>
 		Cоответствие сумм подтверждаю ___________________ (банкир $nm)");
	}

	function Run($opt)
	{
		if($opt=='')	$this->Form();
		else		$this->MakeHTML();
	}
};

class Report_Dolgi
{
	function Form()
	{
		global $tmpl;
		$curdate=date("Y-m-d");
		$tmpl->AddText("<h1>Отчёт по долгам</h1>
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

	function MakeHTML($vdolga)
	{
		global $tmpl;
		$vdolga=rcv('vdolga');
		$agroup=rcv('agroup');
		$firm_id=rcv('firm_id');
		$tmpl->LoadTemplate('print');
		if($vdolga==2) $tmpl->SetText("<h1>Мы должны (от ".date('d.m.Y').")</h1>");
		else $tmpl->SetText("<h1>Долги партнёров (от ".date('d.m.Y').")</h1>");
		$tmpl->AddText("<table width=100%><tr><th>N<th>Агент - партнер<th>Дата сверки<th>Сумма");
		$sql_add=$agroup?"WHERE `group`='$agroup'":'';
		$res=mysql_query("SELECT `id`, `name`, `data_sverki` FROM `doc_agent` $sql_add ORDER BY `name`");
		$i=0;
		$sum_dolga=0;
		while($nxt=mysql_fetch_row($res))
		{
			$dolg=DocCalcDolg($nxt[0],0,$firm_id);
			if( (($dolg>0)&&($vdolga==1))|| (($dolg<0)&&($vdolga==2)) )
			{
				$i++;
				$dolg=abs($dolg);
				$sum_dolga+=$dolg;
				$dolg=sprintf("%0.2f",$dolg);
				$tmpl->AddText("<tr><td>$i<td>$nxt[1]<td>$nxt[2]<td align='right'>$dolg руб.");

			}
		}
		$tmpl->AddText("</table>
		<p>Итого: $i должников с общей суммой долга $sum_dolga  руб.<br> (".num2str($sum_dolga).")</p>");
	}

	function Run($opt)
	{
		if($opt=='')	$this->Form();
		else		$this->MakeHTML();
	}
};



if($mode=='')
{
	doc_menu();
	$tmpl->AddText("<h1>Отчёты</h1>
	<p>Внимание! Отчёты создают высокую нагрузку на сервер, поэтому не рекомендуеся генерировать отчёты во время интенсивной работы с базой данных, а так же не рекомендуется частое использование генератора отчётов по этой же причине!</p>
	<h3>Доступные виды отчётов</h3>
	".otch_list()."<br><br><br><br><br>");
}
else if($mode=='pmenu')
{
	$tmpl->ajax=1;
	$tmpl->AddText(otch_divs());
}
else if($mode=='kassday')
{
	$opt=rcv('opt');
	$otchet=new Report_KassDay();
	$otchet->Run($opt);
}
else if($mode=='bankday')
{
	$opt=rcv('opt');
	$otchet=new Report_BankDay();
	$otchet->Run($opt);
}
else if($mode=='ostatki')
{
	$opt=rcv('opt');
	$otchet=new Report_Store();
	$otchet->Run($opt);
}
else if($mode=='ostatkinadatu')
{
	$opt=rcv('opt');

$otchet=new Report_OstatkiNaDatu();	// Ext
	$otchet->Run($opt);
}
else if($mode=='dolgi')
{
	$opt=rcv('opt');
	$otchet=new Report_Dolgi();
	$otchet->Run($opt);
}
else if($mode=='balance')
{
	doc_menu();
	$tmpl->AddText("<h2 id='page-title'>Балланс: состояние касс и банков</h2>
		<div id='page-info'>Отображается текущее количество средств во всех кассах и банках</div>
		<table width=50% cellspacing=0 cellpadding=0 border=0>
	<tr><th>Тип<th>Название<th>Балланс");
	$i=0;
	$res=mysql_query("SELECT `ids`,`name`,`ballance` FROM `doc_kassa`");
	while($nxt=mysql_fetch_row($res))
	{
		$i=1-$i;
		$pr=sprintf("%0.2f руб.",$nxt[2]);
		$tmpl->AddText("<tr class=lin$i><td>$nxt[0]<td>$nxt[1]<td align=right>$pr");
	}
	$dt=date("Y-m-d");
	$tmpl->AddText("</table>
	<form action='' method=post>
	<input type=hidden name=mode value=balcalc>
	Вычислить балланс на дату:
	<input type=text id='id_pub_date_date' class='vDateField required' name='dt' value='$dt'><br>
	<label><input type=checkbox name=v value=1>Считать на вечер</label><br>
	<input type=submit value='Вычислить'>
	</form>");
}
else if($mode=='balcalc')
{
	$dt=rcv('dt');
	$v=rcv('v');
	doc_menu();
	$tmpl->AddText("<h1>Состояние счетов и касс на $dt</h1>");
	$tm=strtotime($dt);
	if($v) $tm+=60*60*24-1;
	$res=mysql_query("SELECT SUM(`sum`), `bank` FROM `doc_list` WHERE `type`='4' AND `ok`>'0' AND `date`<'$tm' GROUP BY `bank`");
	while($nxt=mysql_fetch_row($res))
	$bank_p[$nxt[1]]=$nxt[0];
	$res=mysql_query("SELECT SUM(`sum`), `bank` FROM `doc_list` WHERE `type`='5' AND `ok`>'0' AND `date`<'$tm' GROUP BY `bank`");
	while($nxt=mysql_fetch_row($res))
	$bank_r[$nxt[1]]=$nxt[0];
	$res=mysql_query("SELECT SUM(`sum`), `kassa` FROM `doc_list` WHERE `type`='6' AND `ok`>'0' AND `date`<'$tm' GROUP BY `kassa`");
	while($nxt=mysql_fetch_row($res))
	$kassa_p[$nxt[1]]=$nxt[0];
	$res=mysql_query("SELECT SUM(`sum`), `kassa` FROM `doc_list` WHERE `type`='7' AND `ok`>'0' AND `date`<'$tm' GROUP BY `kassa`");
	while($nxt=mysql_fetch_row($res))
	$kassa_r[$nxt[1]]=$nxt[0];

//     	$bank=$bank_p-$bank_r;
//     	$kassa=$kassa_p-$kassa_r;

	$tmpl->AddText("<table width=50% cellspacing=0 cellpadding=0 border=0>
	<tr><th>N<th>Приход<th>Расход<th>Балланс
	<tr><th colspan=4>Банки (все)");
	foreach($bank_p as $id => $v)
	{
		$sum=$v-$bank_r[$id];
		$tmpl->AddText("<tr><td>$id<td>$v<td>$bank_r[$id]<td>$sum");
	}
	$tmpl->AddText("
	<tr><th colspan=4>Кассы (все)");
	foreach($kassa_p as $id => $v)
	{
		$sum=$v-$kassa_r[$id];
		$tmpl->AddText("<tr><td>$id<td>$v<td>$kassa_r[$id]<td>$sum");
	}
	$tmpl->AddText("</table>");
}
else if($mode=='balcal')
{
	$dt=rcv('dt');
	$v=rcv('v');
	doc_menu();

	$tm=strtotime($dt);
	if($v) $tm+=60*60*24-1;
	$tt=date("d.m.Y H:i:s",$tm);
	$tmpl->AddText("<h1>Состояние счетов и касс на $dt</h1> $tm $tt");
	$res=mysql_query("SELECT SUM(`sum`) FROM `doc_list` WHERE `type`='4' AND `ok`>'0' AND `date`>'$tm'");
	$bank_p=mysql_result($res,0,0);
		$res=mysql_query("SELECT SUM(`sum`) FROM `doc_list` WHERE `type`='5' AND `ok`>'0' AND `date`>'$tm'");
	$bank_r=mysql_result($res,0,0);
		$res=mysql_query("SELECT SUM(`sum`) FROM `doc_list` WHERE `type`='6' AND `ok`>'0' AND `date`>'$tm'");
	$kassa_p=mysql_result($res,0,0);
		$res=mysql_query("SELECT SUM(`sum`) FROM `doc_list` WHERE `type`='7' AND `ok`>'0' AND `date`>'$tm'");
	$kassa_r=mysql_result($res,0,0);

	$res=mysql_query("SELECT SUM(`ballance`) FROM `doc_kassa` WHERE `ids`='bank'");
	$bank_m=mysql_result($res,0,0);

	$bank=$bank_m-($bank_p-$bank_r);
	$kassa=$kassa_p-$kassa_r;


	$tmpl->AddText("<table width=50% cellspacing=0 cellpadding=0 border=0>
	<tr><th>Тип<th>Приход<th>Расход<th>Балланс
	<tr class=lin1><td>Банки (все)<td>$bank_p<td>$bank_r<td>$bank ($bank_m)
	<tr class=lin0><td>Кассы (все)<td>$kassa_p<td>$kassa_r<td>$kassa
	</table>");
}
else if($mode=='komplekt')
{
	$opt=rcv('opt');
	if($opt=='')
	{
		$tmpl->SetText("<h1>Отчёт по комплектующим (с зарплатой)</h1>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='komplekt'>
		<input type='hidden' name='opt' value='make'>
		Группа товаров:<br>
		<select name='group'>
		<option value='0' selected>-- не выбрана</option>");
		$res=mysql_query("SELECT `id`, `name` FROM `doc_group` ORDER BY `name`");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<option value='$nxt[0]'>$nxt[1] ($nxt[0])</option>");
		}
		$tmpl->AddText("</select>
		<button type='submit'>Создать отчет</button>
		</form>");
	}
	else
	{
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
}
else if($mode=='fin_otchet')
{
	$tmpl->SetTitle("Сводный финансовый отчет");
	doc_menu();
	$tmpl->AddText("<h1>Сводный финансовый отчёт</h1>");
	$date_st=date("Y-m-01");
	$date_end=date("Y-m-d");
	$tmpl->AddText("
	<form method='post'>
	<input type='hidden' name='mode' value='fin_otchet_g'>
	<table width=400>
	<tr><th>
	Задание начальных условий:
	<tr><td class=lin0>
	<p class='datetime'>
	Дата от:<input type='text' id='id_pub_date_date' class='vDateField required' name='date_st' size='10' value='$date_st' maxlength='10' /><br>
	до:<input type='text' id='id_pub_date_date' class='vDateField required' name='date_end' size='10' value='$date_end' maxlength='10' />
	</p>
	<input type=submit value='Сделать отчёт!'>
	</table></form>");
}
else if($mode=='fin_otchet_g')
{
	$date_st=strtotime(rcv('date_st'));
	$date_end=strtotime(rcv('date_end'))+60*60*24-1;
	if(!$date_end) $date_end=time();
	$agent=rcv('agent');

	$date_st_print=date("d.m.Y H:i:s",$date_st);
	$date_end_print=date("d.m.Y H:i:s",$date_end);

	$tmpl->LoadTemplate('print');


	$tmpl->AddText("<h1>Сводный финансовый отчёт</h1>
	<h4>С $date_st_print по $date_end_print</h4>");

// Счётчики для обработки
	$rasxody_nal="";
	$rasxody_bn="";
	$podotchet=0;
	$prixody_nal=0;
	$prixody_bn=0;
	$doc_null='';
	$doc_otchet='';
// Обработка ПРОВЕДЁННЫХ документов за указанный период

	$res=mysql_query("SELECT `doc_list`.`id`,`doc_list`.`type`,`doc_list`.`date`,`doc_list`.`sum`,
	`doc_list`.`altnum`
	FROM `doc_list`
	WHERE `doc_list`.`ok`!='0' AND `doc_list`.`date`>='$date_st' AND `doc_list`.`date`<='$date_end'");

	while($nxt=mysql_fetch_row($res))
	{
		$dopdata="";
		$rr=mysql_query("SELECT `param`,`value` FROM `doc_dopdata` WHERE `doc`='$nxt[0]'");
		while($nx=mysql_fetch_row($rr))
		{
			$dopdata["$nx[0]"]=$nx[1];
		}

		if($nxt[1]==4) // Банковский приход
		{
			$prixody_bn+=$nxt[3];
		}
		if($nxt[1]==5)	// Банковский расход
		{
			$vid=$dopdata['rasxodi'];
			if(!$vid) $vid=0;
			$rasxody_bn[$vid]+=$nxt[3];
			if($vid==12) $podotchet+=$nxt[3];
			if($vid==0)	$doc_null.="банк:$nxt[0], ";


		}
		else if($nxt[1]==6) // Кассовый приход
		{
			$prixody_nal+=$nxt[3];
		}
		else if($nxt[1]==7)	// Кассовый расход
		{
                        $vid=$dopdata['rasxodi'];
                        if(!$vid) $vid=0;
                        $rasxody_nal[$vid]+=$nxt[3];
                        if($vid==12) $podotchet+=$nxt[3];
                        if($vid==0)     $doc_null.="касса:$nxt[0], ";
                        if($vid==12)    $doc_otchet.="касса:$nxt[0], ";
		}
	}

	$adm=0;
	$tovar=0;
	$sum_nal=0;
	$sum_bn=0;
	$sum_prixod=$prixody_bn+$prixody_nal-$podotchet;

	$nal_prn=sprintf("%01.2f", $prixody_nal);
	$bn_prn=sprintf("%01.2f", $prixody_bn);
	$sum_prn=sprintf("%01.2f", $sum_prixod);

	$pod_prn=sprintf("%01.2f",(-1)*$podotchet);
	$nalsum_prn=sprintf("%01.2f", $prixody_nal-$podotchet);


	$tmpl->AddText("
	<h4>Движения денежных средств</h4>
	<table class=right_align>
	<tr><th>N<th>Вид<th>Наличные средства<th>Безналичные средства<th>Сумма
	<tr><th colspan=5>Поступления
	<tr><td>1<td id=lf>Поступления на р/счёт<td>0.00<td>$bn_prn<td>$bn_prn
	<tr><td>2<td id=lf>Поступления в кассу<td>$nal_prn<td>$pod_prn<td>$nalsum_prn
	<tr><th colspan=2>Получено:<th>$nal_prn<th>$bn_prn<th>$sum_prn
	<tr><th colspan=5>Затраты");
	$res=mysql_query("SELECT * FROM `doc_rasxodi`");
	while($nxt=mysql_fetch_row($res))
	{
		$nal=$rasxody_nal[$nxt[0]];
		$bn=$rasxody_bn[$nxt[0]];
		$cur_sum=$nal+$bn;

		$nal_prn=sprintf("%01.2f", $nal);
		$bn_prn=sprintf("%01.2f", $bn);
		$cur_sum_prn=sprintf("%01.2f", $cur_sum);
		$tmpl->AddText("<tr><td>$nxt[0]<td id=lf>$nxt[1]<td>$nal_prn<td>$bn_prn<td>$cur_sum_prn");
		// Суммирование
		$sum_nal+=$nal;
		$sum_bn+=$bn;
		if($nxt[2]) $adm+=$cur_sum;
		else $tovar+=$cur_sum;
	}

	$nal_prn=sprintf("%01.2f", $sum_nal);
	$bn_prn=sprintf("%01.2f", $sum_bn);
	$sum_prn=sprintf("%01.2f", $sum_nal+$sum_bn-$rasxody_bn[12]);
	$adm_prn=sprintf("%01.2f", $adm);
	$tovar_prn=sprintf("%01.2f", $sum_nal+$sum_bn-$adm-$rasxody_bn[12]);

	if($sum_prixod==0) $adm_proc_prn="бесконечность";
	else $adm_proc_prn=sprintf("%01.4f", ($adm/$sum_prixod*100));


	$tmpl->AddText("
	<tr><th colspan=2>Итого:<th>$nal_prn<th>$bn_prn<th>$sum_prn
	<tr><th colspan=4>Административные затраты:<th>$adm_prn
	<tr><th colspan=4>В процентах от прихода:<th>$adm_proc_prn
	<tr><th colspan=4>За товар:<th>$tovar_prn
	<tr><th colspan=4>Итого:<th>$sum_prn
	</table>
	Расходники со статусом *другие расходы*: $doc_null, под отчет: $doc_otchet");
}
else if($mode=='proplaty')
{
	$tmpl->SetTitle("Отчёт по проплатам за период");
	doc_menu();
	$dat=date("Y-m-d");
	$tmpl->AddText("<h1>Отчёт по проплатам за период</h1>
	<form method='post'>
	<input type=hidden name=mode value='proplaty_g'>
	<table width=400>
	<tr><th>
	Задание начальных условий:
	<tr><td class=lin0>
	<p class='datetime'>
	Дата от:<input type='text' id='id_pub_date_date' class='vDateField required' name='date_st' size='10' value='1970-01-01' maxlength='10' /><br>
	до:<input type='text' id='id_pub_date_date' class='vDateField required' name='date_end' size='10' value='$dat' maxlength='10' />
	</p>
	<label><input type=checkbox name=tov value=1>Товары в документах</label><br>
	<input type=submit value='Сделать отчет!'>
	</table>
	</form>");
}
else if($mode=='proplaty_g')
{
	$tov=rcv("tov");
	$agent=rcv('agent');
	$date_st=strtotime(rcv('date_st'));
	$date_end=strtotime(rcv('date_end'))+60*60*24-1;
	if(!$date_end) $date_end=time();
	$tmpl->LoadTemplate('print');

	$tmpl->AddText("<h1>Отчет по проплатам</h1>
	c ".date("d.m.Y",$date_st)." по ".date("d.m.Y",$date_end));

	$res=mysql_query("SELECT `doc_list`.`id`,`doc_list`.`type`,`doc_list`.`date`,`doc_list`.`sum`,
	`doc_list`.`altnum`, `doc_agent`.`name`
	FROM `doc_list`
	LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
	WHERE `doc_list`.`ok`!='0' AND `doc_list`.`date`>='$date_st' AND `doc_list`.`date`<='$date_end'");

	$tmpl->AddText("<table width=100%>
	<tr><th width=30%>N док-та, дата, партнер<th>Операция<th>Дебет<th>Кредит");
	$pr=$ras=0;
	while($nxt=mysql_fetch_row($res))
	{
		$deb=$kr="";

		if($nxt[1]==1)
		{
			$tp="Поступление";
			$pr+=$nxt[3];
			$deb=$nxt[3];
		}
		else if($nxt[1]==2)
		{
			$tp="Реализация";
			$ras+=$nxt[3];
			$kr=$nxt[3];
		}
		if($nxt[1]==3)
		{
			$tp="-";
			continue;
		}
		if($nxt[1]==4)
		{
			$tp="Оплата";
			$pr+=$nxt[3];
			$deb=$nxt[3];
		}
		if($nxt[1]==5)
		{
			$tp="Возврат";
			$ras+=$nxt[3];
			$kr=$nxt[3];
		}
		if($nxt[1]==6)
		{
			$tp="Оплата";
			$pr+=$nxt[3];
			$deb=$nxt[3];
		}
		if($nxt[1]==7)
		{
			$tp="Возврат";
			$ras+=$nxt[3];
			$kr=$nxt[3];
		}

		if($tov)
		{
			$rs=mysql_query("SELECT `doc_base`.`name`,`doc_list_pos`.`cnt`,`doc_list_pos`.`cost` FROM `doc_list_pos`
			LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
			WHERE `doc_list_pos`.`doc`='$nxt[0]'");
			if(mysql_num_rows($rs))
			{
				$tp="<b>$tp</b><table width=100%><tr><th>Товар<th width=20%>Кол-во<th width=20%>Цена";
				while($nx=mysql_fetch_row($rs))
					$tp.="<tr><td>$nx[0]<td>$nx[1] шт.<td>$nx[2] руб.";
				$tp.="</table>";
			}
		}
		if($deb) $deb=sprintf("%01.2f", $deb);
		if($kr) $kr=sprintf("%01.2f", $kr);
		$dt=date("d.m.Y",$nxt[2]);
		$tmpl->AddText("<tr>
		<td>$nxt[4] ($nxt[0])<br>$dt<br>$nxt[5]<td>$tp<td>$deb<td>$kr");
	}

	$razn=sprintf("%01.2f", $pr-$ras);
	$pr=sprintf("%01.2f", $pr);
	$ras=sprintf("%01.2f", $ras);

	$tmpl->AddText("<tr><td>-<td>Обороты за период<td>$pr<td>$ras
	<tr><td colspan=4>");
	if($razn>0)
		$tmpl->AddText("переплата ".$dv['firm_name']."$razn руб.");
	else $tmpl->AddText("задолженность ".$dv['firm_name']."	$razn руб.");

	$tmpl->AddText("<tr><td colspan=4>".$dv['firm_name']."<br>
	директор<br>____________________________ (".$dv['firm_director'].")<br><br>м.п.<br>
	</table>");
}
else if($mode=='sverka')
{
	global $CONFIG;
	$opt=rcv('opt');
	if($opt=='')
	{
		doc_menu();
		$tmpl->SetTitle("Акт сверки");
		$dat=date("Y-m-d");
		$tmpl->AddText("
		<script src='/css/jquery/jquery.js' type='text/javascript'></script>
		<script src='/css/jquery/jquery.alerts.js' type='text/javascript'></script>
		<link href='/css/jquery/jquery.alerts.css' rel='stylesheet' type='text/css' media='screen' />
		<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<link rel='stylesheet' href='/css/jquery/ui/themes/base/jquery.ui.all.css'>
		<script src='/css/jquery/ui/jquery.ui.core.js'></script>
		<script src='/css/jquery/ui/jquery.ui.widget.js'></script>
		<script src='/css/jquery/ui/jquery.ui.datepicker.js'></script>
		<script src='/css/jquery/ui/i18n/jquery.ui.datepicker-ru.js'></script>
		<h1><b>Акт сверки</b></h1>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='sverka'>
		Агент-партнёр:<br>
		<input type='hidden' name='agent_id' id='agent_id' value=''>
		<input type='text' id='ag' name='agent_name' style='width: 400px;' value=''><br>
		<p class='datetime'>
		Дата от:<br><input type='text' id='datepicker_f' name='date_st' value='1970-01-01' maxlength='10' /><br>
		Дата до:<br><input type='text' id='datepicker_t' name='date_end' value='$dat' maxlength='10' /></p><br>
		Организация:<br><select name='firm_id'>
		<option value='0'>--- Любая ---</option>");
		$rs=mysql_query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `firm_name`");
		while($nx=mysql_fetch_row($rs))
		{
			if($CONFIG['site']['default_firm']==$nx[0]) $s=' selected'; else $s='';
			$tmpl->AddText("<option value='$nx[0]' $s>$nx[1]</option>");
		}
		$tmpl->AddText("</select><br>
		Подтип документа (оставьте пустым, если учитывать не требуется):<br>
		<input type='text' name='subtype'><br>
		<label><input type='radio' name='opt' value='html'>Выводить в виде HTML</label><br>
		<label><input type='radio' name='opt' value='pdf' checked>Выводить в виде PDF</label><br>
		<input type=submit value='Сделать сверку!'>
		</form>

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
			$.datepicker.setDefaults( $.datepicker.regional[ 'ru' ] );

			$( '#datepicker_f' ).datepicker({showButtonPanel: true	});
			$( '#datepicker_f' ).datepicker( 'option', 'dateFormat', 'yy-mm-dd' );
			$( '#datepicker_f' ).datepicker( 'setDate' , '1970-01-01' );
			$( '#datepicker_t' ).datepicker({showButtonPanel: true	});
			$( '#datepicker_t' ).datepicker( 'option', 'dateFormat', 'yy-mm-dd' );
			$( '#datepicker_t' ).datepicker( 'setDate' , '$dat' );
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

		</script>");
	}
	else if($opt=='html')
	{
		$firm_id=rcv('firm_id');
		$subtype=rcv('subtype');

		$date_st=strtotime(rcv('date_st'));
		$date_end=strtotime(rcv('date_end'))+60*60*24-1;
		$ag=rcv('agent_id');

		if($firm_id)
		{
			$res=mysql_query("SELECT * FROM `doc_vars` WHERE `id`='$firm_id'");
			$dv=mysql_fetch_assoc($res);
		}
		if(!$date_end) $date_end=time();
		$tmpl->LoadTemplate('print');

		$res=mysql_query("SELECT `id`, `fullname`, `dir_fio` FROM `doc_agent` WHERE `id`='$ag'");
		if(mysql_errno())		throw new MysqlException("Не удалось получить данные агента");
		if(mysql_num_rows($res)==0)	throw new Exception("Не указан агент!");
		list($agent, $fn, $dir_fio)=mysql_fetch_row($res);

		$tmpl->SetText("<center>Акт сверки<br>
		взаимных расчетов<br>".$dv['firm_name']."<br>
		c ".date("d.m.Y",$date_st)." по ".date("d.m.Y",$date_end)."
		$fn</center>
		Мы, нижеподписавшиеся, директор ".$dv['firm_name']." ".$dv['firm_director']."
		c одной стороны, и директор $fn $dir_fio с другой стороны,
		составили настоящий акт сверки в том, что состояние взаимных расчетов по
		данным учёта следующее:<br><br>");

		$sql_add='';
		if($firm_id>0) $sql_add.=" AND `doc_list`.`firm_id`='$firm_id'";
		if($subtype!='') $sql_add.=" AND `doc_list`.`subtype`='$subtype'";

		$res=mysql_query("SELECT `doc_list`.`id`,`doc_list`.`type`,`doc_list`.`date`,`doc_list`.`sum`,
		`doc_list`.`altnum`, `doc_types`.`name`
		FROM `doc_list`
		LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		WHERE `doc_list`.`agent`='$agent' AND `doc_list`.`ok`!='0' AND `doc_list`.`date`<='$date_end' ".$sql_add." ORDER BY `doc_list`.`date`" );

		$tmpl->AddText("<table width=100%>
		<tr>
		<td colspan=4 width='50%'>по данным ".$dv['firm_name']."
		<td colspan=4 width='50%'>по данным $fn
		<tr>
		<th>Дата<th>Операция<th>Дебет<th>Кредит
		<th>Дата<th>Операция<th>Дебет<th>Кредит");
		$pr=$ras=0;
		$f_print=false;
		while($nxt=mysql_fetch_row($res))
		{
			$deb=$kr="";

			if( ($nxt[2]>=$date_st) && (!$f_print) )
			{
				$f_print=true;
				if($pr>$ras)
				{
					$pr-=$ras;
					$ras='';
				}
				else if($pr<$ras)
				{
					$ras-=$pr;
					$pr='';
				}
				else  $pr=$ras='';
				if($pr)	$pr=sprintf("%01.2f", $pr);
				if($ras)$ras=sprintf("%01.2f", $ras);
				$tmpl->AddText("<tr><td colspan=2>Сальдо на начало периода<td>$ras<td>$pr<td><td><td><td>");
			}

			if($nxt[1]==1)
			{
				$pr+=$nxt[3];
				$kr=$nxt[3];
			}
			else if($nxt[1]==2)
			{
				$ras+=$nxt[3];
				$deb=$nxt[3];
			}
			else if($nxt[1]==4)
			{
				$pr+=$nxt[3];
				$kr=$nxt[3];
			}
			else if($nxt[1]==5)
			{
				$ras+=$nxt[3];
				$deb=$nxt[3];
			}
			else if($nxt[1]==6)
			{
				$pr+=$nxt[3];
				$kr=$nxt[3];
			}
			else if($nxt[1]==7)
			{
				$ras+=$nxt[3];
				$deb=$nxt[3];
			}
			else if($nxt[1]==18)
			{
				if($nxt[3]>0)
				{
					$ras+=$nxt[3];
					$deb=$nxt[3];
				}
				else
				{
					$pr+=abs($nxt[3]);
					$kr=abs($nxt[3]);
				}
			}
			else continue;

			if($f_print)
			{
				if(!$nxt[4]) $nxt[4]=$nxt[0];
				if($deb) $deb=sprintf("%01.2f", $deb);
				if($kr) $kr=sprintf("%01.2f", $kr);
				$dt=date("d.m.Y",$nxt[2]);
				$tmpl->AddText("<tr><td>$dt<td>$nxt[5] N$nxt[4]<td>$deb<td>$kr<td><td><td><td>");
			}
		}

		$razn=$pr-$ras;
		$razn_p=abs($razn);
		$razn_p=sprintf("%01.2f", $razn_p);

		$pr=sprintf("%01.2f", $pr);
		$ras=sprintf("%01.2f", $ras);

		$tmpl->AddText("<tr><td colspan=2>Обороты за период<td>$ras<td>$pr<td><td><td><td>");
		if($pr>$ras)
		{
			$pr-=$ras;
			$ras='';
		}
		else if($pr<$ras)
		{
			$ras-=$pr;
			$pr='';
		}
		else  $pr=$ras='';
		if($pr)	$pr=sprintf("%01.2f", $pr);
		if($ras)$ras=sprintf("%01.2f", $ras);

		$tmpl->AddText("<tr><td colspan=2>Сальдо на конец периода<td>$ras<td>$pr<td colspan=4>
		<tr><td colspan=4>");
		if($razn>0)		$tmpl->AddText("переплата в пользу ".$dv['firm_name']." $razn_p руб.");
		else	if($razn<0) 	$tmpl->AddText("задолженность в пользу ".$dv['firm_name']." $razn_p руб.");
		else			$tmpl->AddText("$razn переплат и задолженностей нет!");

		$tmpl->AddText("<td colspan=4>
		<tr><td colspan=4>От ".$dv['firm_name']."<br>
		директор<br>____________________________ (".$dv['firm_director'].")<br><br>м.п.<br>
		<td colspan=4>От $fn<br>
		директор<br> ____________________________ ($dir_fio)<br><br>м.п.<br>
		</table>");
	}
	else if($opt=='pdf')
	{
		$firm_id=rcv('firm_id');
		$subtype=rcv('subtype');
		$date_st=strtotime(rcv('date_st'));
		$date_end=strtotime(rcv('date_end'))+60*60*24-1;
		$agent_id=rcv('agent_id');

		if($firm_id)
		{
			$res=mysql_query("SELECT * FROM `doc_vars` WHERE `id`='$firm_id'");
			if(mysql_errno())	throw new Exception("Не удалось выбрать данные фирмы");
			$firm_vars=mysql_fetch_assoc($res);
		}
		if(!$date_end) $date_end=time();

		$res=mysql_query("SELECT `id`, `fullname`, `dir_fio` FROM `doc_agent` WHERE `id`='$agent_id'");
		if(mysql_errno())		throw new MysqlException("Не удалось получить данные агента");
		if(mysql_num_rows($res)==0)	throw new Exception("Не указан агент $agent_id!");
		list($agent, $fn, $dir_fio)=mysql_fetch_row($res);

		$fn=unhtmlentities($fn);
		$firm_vars['firm_name']=unhtmlentities($firm_vars['firm_name']);

		define('FPDF_FONT_PATH',$CONFIG['site']['location'].'/fpdf/font/');
		require('fpdf/fpdf.php');
		$pdf=new FPDF('P');
		$pdf->Open();
		$pdf->SetAutoPageBreak(1,12);
		$pdf->AddFont('Arial','','arial.php');
		$pdf->tMargin=10;
		$pdf->AddPage('P');

		$pdf->SetFont('Arial','',16);
		$str = iconv('UTF-8', 'windows-1251', "Акт сверки взаимных расчетов");
		$pdf->Cell(0,6,$str,0,1,'C',0);

		$str="от {$firm_vars['firm_name']}\nза период с ".date("d.m.Y",$date_st)." по ".date("d.m.Y",$date_end);
		$pdf->SetFont('Arial','',10);
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->MultiCell(0,4,$str,0,'C',0);
		$pdf->Ln(2);
		$str="Мы, нижеподписавшиеся, директор {$firm_vars['firm_name']} {$firm_vars['firm_director']} c одной стороны, и директор $fn $dir_fio, с другой стороны, составили настоящий акт сверки о том, что состояние взаимных расчетов по данным учёта следующее:";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Write(5,$str,'');

		$pdf->Ln(8);
		$y=$pdf->GetY();
		$base_x=$pdf->GetX();
		$pdf->SetLineWidth(0.5);
		$t_width=array(17,44,17,17,17,44,17,0);
		$t_text=array('Дата', 'Операция', 'Дебет', 'Кредит', 'Дата', 'Операция', 'Дебет', 'Кредит');

		$h_width=$t_width[0]+$t_width[1]+$t_width[2]+$t_width[3];
		$str1=iconv('UTF-8', 'windows-1251', "По данным {$firm_vars['firm_name']}");
		$str2=iconv('UTF-8', 'windows-1251', "По данным $fn");

		$pdf->MultiCell($h_width,5,$str1,0,'L',0);
		$max_h=$pdf->GetY()-$y;
		$pdf->SetY($y);
		$pdf->SetX($base_x+$h_width);
		$pdf->MultiCell(0,5,$str2,0,'L',0);
		if( ($pdf->GetY()-$y) > $max_h)	$max_h=$pdf->GetY()-$y;
		//$pdf->Cell(0,5,$str2,1,0,'L',0);
		$pdf->SetY($y);
		$pdf->SetX($base_x);
		$pdf->Cell($h_width,$max_h,'',1,0,'L',0);
		$pdf->Cell(0,$max_h,'',1,0,'L',0);
		$pdf->Ln();
		foreach($t_width as $i => $w)
		{
			$str = iconv('UTF-8', 'windows-1251', $t_text[$i]);
			$pdf->Cell($w,5,$str,1,0,'C',0);
		}
		$pdf->SetLineWidth(0.2);
		$pdf->Ln();
		$pdf->SetFont('','',8);
		$pr=$ras=0;
		$f_print=false;

		$sql_add='';
		if($firm_id>0) $sql_add.=" AND `doc_list`.`firm_id`='$firm_id'";
		if($subtype!='') $sql_add.=" AND `doc_list`.`subtype`='$subtype'";

		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`date`, `doc_list`.`sum`,
		`doc_list`.`altnum`, `doc_types`.`name`
		FROM `doc_list`
		LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		WHERE `doc_list`.`agent`='$agent' AND `doc_list`.`ok`!='0' AND `doc_list`.`date`<='$date_end' AND `doc_list`.`type`!='3' ".$sql_add." ORDER BY `doc_list`.`date`" );
		while($nxt=mysql_fetch_array($res))
		{
			$deb=$kr="";
			if( ($nxt[2]>=$date_st) && (!$f_print) )
			{
				$f_print=true;
				if($pr>$ras)
				{
					$pr-=$ras;
					$ras='';
				}
				else if($pr<$ras)
				{
					$ras-=$pr;
					$pr='';
				}
				else  $pr=$ras='';
				if($pr)	$pr=sprintf("%01.2f", $pr);
				if($ras)$ras=sprintf("%01.2f", $ras);

				$str=iconv('UTF-8', 'windows-1251', "Сальдо на начало периода");
				$pdf->Cell($t_width[0]+$t_width[1],4,$str,1,0,'L',0);
				$pdf->Cell($t_width[2],4,$ras,1,0,'R',0);
				$pdf->Cell($t_width[3],4,$pr,1,0,'R',0);
				$pdf->Cell($t_width[4]+$t_width[5],4,'',1,0,'L',0);
				$pdf->Cell($t_width[6],4,'',1,0,'L',0);
				$pdf->Cell($t_width[7],4,'',1,0,'L',0);
				$pdf->Ln();
			}

			if($nxt[1]==1)
			{
				$pr+=$nxt[3];
				$kr=$nxt[3];
			}
			else if($nxt[1]==2)
			{
				$ras+=$nxt[3];
				$deb=$nxt[3];
			}
			else if($nxt[1]==4)
			{
				$pr+=$nxt[3];
				$kr=$nxt[3];
			}
			else if($nxt[1]==5)
			{
				$ras+=$nxt[3];
				$deb=$nxt[3];
			}
			else if($nxt[1]==6)
			{
				$pr+=$nxt[3];
				$kr=$nxt[3];
			}
			else if($nxt[1]==7)
			{
				$ras+=$nxt[3];
				$deb=$nxt[3];
			}
			else if($nxt[1]==18)
			{
				if($nxt[3]>0)
				{
					$ras+=$nxt[3];
					$deb=$nxt[3];
				}
				else
				{
					$pr+=abs($nxt[3]);
					$kr=abs($nxt[3]);
				}
			}
			else continue;

			if($f_print)
			{
				if(!$nxt[4]) $nxt[4]=$nxt[0];
				if($deb) $deb=sprintf("%01.2f", $deb);
				if($kr) $kr=sprintf("%01.2f", $kr);
				$dt=date("d.m.Y",$nxt[2]);
				$str=iconv('UTF-8', 'windows-1251', "$nxt[5] N$nxt[4]");
				$pdf->Cell($t_width[0],4,$dt,1,0,'L',0);
				$pdf->Cell($t_width[1],4,$str,1,0,'L',0);
				$pdf->Cell($t_width[2],4,$deb,1,0,'R',0);
				$pdf->Cell($t_width[3],4,$kr,1,0,'R',0);
				$pdf->Cell($t_width[4],4,'',1,0,'L',0);
				$pdf->Cell($t_width[5],4,'',1,0,'L',0);
				$pdf->Cell($t_width[6],4,'',1,0,'L',0);
				$pdf->Cell($t_width[7],4,'',1,0,'L',0);
				$pdf->Ln();
			}
		}

		$razn=$pr-$ras;
		$razn_p=abs($razn);
		$razn_p=sprintf("%01.2f", $razn_p);

		$pr=sprintf("%01.2f", $pr);
		$ras=sprintf("%01.2f", $ras);

		$str=iconv('UTF-8', 'windows-1251', "Обороты за период");
		$pdf->Cell($t_width[0]+$t_width[1],4,$str,1,0,'L',0);
		$pdf->Cell($t_width[2],4,$ras,1,0,'R',0);
		$pdf->Cell($t_width[3],4,$pr,1,0,'R',0);
		$pdf->Cell($t_width[4]+$t_width[5],4,'',1,0,'L',0);
		$pdf->Cell($t_width[6],4,'',1,0,'L',0);
		$pdf->Cell($t_width[7],4,'',1,0,'L',0);
		$pdf->Ln();

		if($pr>$ras)
		{
			$pr-=$ras;
			$ras='';
		}
		else if($pr<$ras)
		{
			$ras-=$pr;
			$pr='';
		}
		else  $pr=$ras='';
		if($pr)	$pr=sprintf("%01.2f", $pr);
		if($ras)$ras=sprintf("%01.2f", $ras);

		$str=iconv('UTF-8', 'windows-1251', "Сальдо на конец периода");
		$pdf->Cell($t_width[0]+$t_width[1],4,$str,1,0,'L',0);
		$pdf->Cell($t_width[2],4,$ras,1,0,'L',0);
		$pdf->Cell($t_width[3],4,$pr,1,0,'L',0);
		$pdf->Cell($t_width[4]+$t_width[5],4,'',1,0,'L',0);
		$pdf->Cell($t_width[6],4,'',1,0,'L',0);
		$pdf->Cell($t_width[7],4,'',1,0,'L',0);
		$pdf->Ln(7);

		$str=iconv('UTF-8', 'windows-1251', "По данным {$firm_vars['firm_name']} на ".date("d.m.Y",$date_end));
		$pdf->Write(4,$str);
		$pdf->Ln();
		if($razn>0)		$str="переплата в пользу ".$firm_vars['firm_name']." $razn_p руб.";
		else	if($razn<0) 	$str="задолженность в пользу ".$firm_vars['firm_name']." $razn_p руб.";
		else			$str="переплат и задолженностей нет!";

		$str=iconv('UTF-8', 'windows-1251', $str);
		$pdf->Write(4,$str);
		$pdf->Ln(7);
		$x=$pdf->getX()+$t_width[0]+$t_width[1]+$t_width[2]+$t_width[3];
		$y=$pdf->getY();
		$str=iconv('UTF-8', 'windows-1251', "От {$firm_vars['firm_name']}\n\nДиректор ____________________________ ({$firm_vars['firm_director']})\n\n           м.п.");
		$pdf->MultiCell($t_width[0]+$t_width[1]+$t_width[2]+$t_width[3],5,$str,0,'L',0);
		$str=iconv('UTF-8', 'windows-1251', "От $fn\n\n           ____________________________ ($dir_fio)\n\n           м.п.");
		$pdf->lMargin=$x;
		$pdf->setX($x);

		$pdf->setY($y);
		$pdf->MultiCell(0,5,$str,0,'L',0);
		$pdf->Ln();

		$pdf->Output('akt_sverki.pdf','I');
	}
}
else if($mode=='sverka_op')
{
	global $CONFIG;
	$opt=rcv('opt');
	if($opt=='')
	{
		doc_menu();
		$tmpl->SetTitle("Акт сверки");
		$dat=date("Y-m-d");
		$tmpl->AddText("
		<script src='/css/jquery/jquery.js' type='text/javascript'></script>
		<script src='/css/jquery/jquery.alerts.js' type='text/javascript'></script>
		<link href='/css/jquery/jquery.alerts.css' rel='stylesheet' type='text/css' media='screen' />
		<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<link rel='stylesheet' href='/css/jquery/ui/themes/base/jquery.ui.all.css'>
		<script src='/css/jquery/ui/jquery.ui.core.js'></script>
		<script src='/css/jquery/ui/jquery.ui.widget.js'></script>
		<script src='/css/jquery/ui/jquery.ui.datepicker.js'></script>
		<script src='/css/jquery/ui/i18n/jquery.ui.datepicker-ru.js'></script>
		<h1><b>Акт сверки</b></h1>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='sverka_op'>
		Агент-партнёр:<br>
		<input type='hidden' name='agent_id' id='agent_id' value=''>
		<input type='text' id='ag' name='agent_name' style='width: 400px;' value=''><br>
		<p class='datetime'>
		Дата от:<br><input type='text' id='datepicker_f' name='date_st' value='1970-01-01' maxlength='10' /><br>
		Дата до:<br><input type='text' id='datepicker_t' name='date_end' value='$dat' maxlength='10' /></p><br>
		Организация:<br><select name='firm_id'>
		<option value='0'>--- Любая ---</option>");
		$rs=mysql_query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `firm_name`");
		while($nx=mysql_fetch_row($rs))
		{
			if($CONFIG['site']['default_firm']==$nx[0]) $s=' selected'; else $s='';
			$tmpl->AddText("<option value='$nx[0]' $s>$nx[1]</option>");
		}
		$tmpl->AddText("</select><br>
		Подтип документа (оставьте пустым, если учитывать не требуется):<br>
		<input type='text' name='subtype'><br>
		<label><input type='radio' name='opt' value='html'>Выводить в виде HTML</label><br>
		<label><input type='radio' name='opt' value='pdf' checked>Выводить в виде PDF</label><br>
		<input type=submit value='Сделать сверку!'>
		</form>

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
			$.datepicker.setDefaults( $.datepicker.regional[ 'ru' ] );

			$( '#datepicker_f' ).datepicker({showButtonPanel: true	});
			$( '#datepicker_f' ).datepicker( 'option', 'dateFormat', 'yy-mm-dd' );
			$( '#datepicker_f' ).datepicker( 'setDate' , '1970-01-01' );
			$( '#datepicker_t' ).datepicker({showButtonPanel: true	});
			$( '#datepicker_t' ).datepicker( 'option', 'dateFormat', 'yy-mm-dd' );
			$( '#datepicker_t' ).datepicker( 'setDate' , '$dat' );
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

		</script>");
	}
	else if($opt=='html')
	{
		$firm_id=rcv('firm_id');
		$subtype=rcv('subtype');

		$date_st=strtotime(rcv('date_st'));
		$date_end=strtotime(rcv('date_end'))+60*60*24-1;
		$ag=rcv('agent_id');

		if($firm_id)
		{
			$res=mysql_query("SELECT * FROM `doc_vars` WHERE `id`='$firm_id'");
			$dv=mysql_fetch_assoc($res);
		}
		if(!$date_end) $date_end=time();
		$tmpl->LoadTemplate('print');

		$res=mysql_query("SELECT `id`, `fullname` FROM `doc_agent` WHERE `name`='$ag'");
		$agent=mysql_result($res,0,0);
		$fn=mysql_result($res,0,1);

		$tmpl->SetText("<center>Акт сверки<br>
		взаимных расчетов<br>".$dv['firm_name']."<br>
		c ".date("d.m.Y",$date_st)." по ".date("d.m.Y",$date_end)."
		$fn</center>
		Мы, нижеподписавшиеся, директор ".$dv['firm_name']." ".$dv['firm_director']."
		c одной стороны, и _____________ $fn ____________________ с другой стороны,
		составили настоящий акт сверки в том, что состояние взаимных расчетов по
		данным учёта следующее:<br><br>");

		$sql_add='';
		if($firm_id>0) $sql_add.=" AND `doc_list`.`firm_id`='$firm_id'";
		if($subtype!='') $sql_add.=" AND `doc_list`.`subtype`='$subtype'";

		$res=mysql_query("SELECT `doc_list`.`id`,`doc_list`.`type`,`doc_list`.`date`,`doc_list`.`sum`,
		`doc_list`.`altnum`, `doc_types`.`name`
		FROM `doc_list`
		LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		WHERE `doc_list`.`agent`='$agent' AND `doc_list`.`ok`!='0' AND `doc_list`.`date`<='$date_end' ".$sql_add." ORDER BY `doc_list`.`date`" );

		$tmpl->AddText("<table width=100%>
		<tr>
		<td colspan=4 width='50%'>по данным ".$dv['firm_name']."
		<td colspan=4 width='50%'>по данным $fn
		<tr>
		<th>Дата<th>Операция<th>Дебет<th>Кредит
		<th>Дата<th>Операция<th>Дебет<th>Кредит");
		$pr=$ras=0;
		$f_print=false;
		while($nxt=mysql_fetch_row($res))
		{
			$deb=$kr="";

			if( ($nxt[2]>=$date_st) && (!$f_print) )
			{
				$f_print=true;
				if($pr>$ras)
				{
					$pr-=$ras;
					$ras='';
				}
				else if($pr<$ras)
				{
					$ras-=$pr;
					$pr='';
				}
				else  $pr=$ras='';
				if($pr)	$pr=sprintf("%01.2f", $pr);
				if($ras)$ras=sprintf("%01.2f", $ras);
				$tmpl->AddText("<tr><td colspan=2>Сальдо на начало периода<td>$ras<td>$pr<td><td><td><td>");
			}

			if($nxt[1]==1)
			{
				$pr+=$nxt[3];
				$kr=$nxt[3];
			}
			else if($nxt[1]==15)
			{
				$ras+=$nxt[3];
				$deb=$nxt[3];
			}
			if( ($nxt[1]==3) || ($nxt[1]==12))
			{
				continue;
			}
			if($nxt[1]==4)
			{
				$pr+=$nxt[3];
				$kr=$nxt[3];
			}
			if($nxt[1]==5)
			{
				$ras+=$nxt[3];
				$deb=$nxt[3];
			}
			if($nxt[1]==6)
			{
				$pr+=$nxt[3];
				$kr=$nxt[3];
			}
			if($nxt[1]==7)
			{
				$ras+=$nxt[3];
				$deb=$nxt[3];
			}

			if($f_print)
			{
				if(!$nxt[4]) $nxt[4]=$nxt[0];
				if($deb) $deb=sprintf("%01.2f", $deb);
				if($kr) $kr=sprintf("%01.2f", $kr);
				$dt=date("d.m.Y",$nxt[2]);
				$tmpl->AddText("<tr><td>$dt<td>$nxt[5] N$nxt[4]<td>$deb<td>$kr<td><td><td><td>");
			}
		}

		$razn=$pr-$ras;
		$razn_p=abs($razn);
		$razn_p=sprintf("%01.2f", $razn_p);

		$pr=sprintf("%01.2f", $pr);
		$ras=sprintf("%01.2f", $ras);

		$tmpl->AddText("<tr><td colspan=2>Обороты за период<td>$ras<td>$pr<td><td><td><td>");
		if($pr>$ras)
		{
			$pr-=$ras;
			$ras='';
		}
		else if($pr<$ras)
		{
			$ras-=$pr;
			$pr='';
		}
		else  $pr=$ras='';
		if($pr)	$pr=sprintf("%01.2f", $pr);
		if($ras)$ras=sprintf("%01.2f", $ras);

		$tmpl->AddText("<tr><td colspan=2>Сальдо на конец периода<td>$ras<td>$pr<td colspan=4>
		<tr><td colspan=4>");
		if($razn>0)		$tmpl->AddText("переплата в пользу ".$dv['firm_name']." $razn_p руб.");
		else	if($razn<0) 	$tmpl->AddText("задолженность в пользу ".$dv['firm_name']." $razn_p руб.");
		else			$tmpl->AddText("$razn переплат и задолженностей нет!");

		$tmpl->AddText("<td colspan=4>
		<tr><td colspan=4>От ".$dv['firm_name']."<br>
		директор<br>____________________________ (".$dv['firm_director'].")<br><br>м.п.<br>
		<td colspan=4>От $fn<br>
		директор<br> ____________________________ (_____________)<br><br>м.п.<br>
		</table>");
	}
	else if($opt=='pdf')
	{
		$firm_id=rcv('firm_id');
		$subtype=rcv('subtype');
		$date_st=strtotime(rcv('date_st'));
		$date_end=strtotime(rcv('date_end'))+60*60*24-1;
		$agent_id=rcv('agent_id');

		if($firm_id)
		{
			$res=mysql_query("SELECT * FROM `doc_vars` WHERE `id`='$firm_id'");
			if(mysql_errno())	throw new Exception("Не удалось выбрать данные фирмы");
			$firm_vars=mysql_fetch_assoc($res);
		}
		if(!$date_end) $date_end=time();

		$res=mysql_query("SELECT `id`, `fullname`, `pdol`, `pfio` FROM `doc_agent` WHERE `id`='$agent_id'");
		if(mysql_errno())	throw new Exception("Не удалось выбрать агента");
		$agent=mysql_fetch_assoc($res);

		$firm_vars['firm_name']=unhtmlentities($firm_vars['firm_name']);
		$agent['fullname']=unhtmlentities($agent['fullname']);

		define('FPDF_FONT_PATH',$CONFIG['site']['location'].'/fpdf/font/');
		require('fpdf/fpdf.php');
		$pdf=new FPDF('P');
		$pdf->Open();
		$pdf->SetAutoPageBreak(1,12);
		$pdf->AddFont('Arial','','arial.php');
		$pdf->tMargin=10;
		$pdf->AddPage('P');

		$pdf->SetFont('Arial','',16);
		$str = iconv('UTF-8', 'windows-1251', "Акт сверки взаимных расчетов");
		$pdf->Cell(0,6,$str,0,1,'C',0);

		$str="от {$firm_vars['firm_name']}\nза период с ".date("d.m.Y",$date_st)." по ".date("d.m.Y",$date_end);
		$pdf->SetFont('Arial','',10);
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->MultiCell(0,4,$str,0,'C',0);
		$pdf->Ln(2);
		$str="Мы, нижеподписавшиеся, директор {$firm_vars['firm_name']} {$firm_vars['firm_director']} c одной стороны, и              {$agent['fullname']}                 , с другой стороны, составили настоящий акт сверки о том, что состояние взаимных расчетов по данным учёта следующее:";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Write(5,$str,'');

		$pdf->Ln(8);
		$y=$pdf->GetY();
		$base_x=$pdf->GetX();
		$pdf->SetLineWidth(0.5);
		$t_width=array(17,44,17,17,17,44,17,0);
		$t_text=array('Дата', 'Операция', 'Дебет', 'Кредит', 'Дата', 'Операция', 'Дебет', 'Кредит');

		$h_width=$t_width[0]+$t_width[1]+$t_width[2]+$t_width[3];
		$str1=iconv('UTF-8', 'windows-1251', "По данным {$firm_vars['firm_name']}");
		$str2=iconv('UTF-8', 'windows-1251', "По данным {$agent['fullname']}");

		$pdf->MultiCell($h_width,5,$str1,0,'L',0);
		$max_h=$pdf->GetY()-$y;
		$pdf->SetY($y);
		$pdf->SetX($base_x+$h_width);
		$pdf->MultiCell(0,5,$str2,0,'L',0);
		if( ($pdf->GetY()-$y) > $max_h)	$max_h=$pdf->GetY()-$y;
		//$pdf->Cell(0,5,$str2,1,0,'L',0);
		$pdf->SetY($y);
		$pdf->SetX($base_x);
		$pdf->Cell($h_width,$max_h,'',1,0,'L',0);
		$pdf->Cell(0,$max_h,'',1,0,'L',0);
		$pdf->Ln();
		foreach($t_width as $i => $w)
		{
			$str = iconv('UTF-8', 'windows-1251', $t_text[$i]);
			$pdf->Cell($w,5,$str,1,0,'C',0);
		}
		$pdf->SetLineWidth(0.2);
		$pdf->Ln();
		$pdf->SetFont('','',8);
		$pr=$ras=0;
		$f_print=false;

		$sql_add='';
		if($firm_id>0) $sql_add.=" AND `doc_list`.`firm_id`='$firm_id'";
		if($subtype!='') $sql_add.=" AND `doc_list`.`subtype`='$subtype'";

		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`date`, `doc_list`.`sum`,
		`doc_list`.`altnum`, `doc_types`.`name`
		FROM `doc_list`
		LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		WHERE `doc_list`.`agent`='{$agent['id']}' AND `doc_list`.`ok`!='0' AND `doc_list`.`date`<='$date_end' AND `doc_list`.`type`<'8' AND `doc_list`.`type`!='3' ".$sql_add." ORDER BY `doc_list`.`date`" );
		while($nxt=mysql_fetch_array($res))
		{
			$deb=$kr="";
			if( ($nxt[2]>=$date_st) && (!$f_print) )
			{
				$f_print=true;
				if($pr>$ras)
				{
					$pr-=$ras;
					$ras='';
				}
				else if($pr<$ras)
				{
					$ras-=$pr;
					$pr='';
				}
				else  $pr=$ras='';
				if($pr)	$pr=sprintf("%01.2f", $pr);
				if($ras)$ras=sprintf("%01.2f", $ras);

				$str=iconv('UTF-8', 'windows-1251', "Сальдо на начало периода");
				$pdf->Cell($t_width[0]+$t_width[1],4,$str,1,0,'L',0);
				$pdf->Cell($t_width[2],4,$ras,1,0,'R',0);
				$pdf->Cell($t_width[3],4,$pr,1,0,'R',0);
				$pdf->Cell($t_width[4]+$t_width[5],4,'',1,0,'L',0);
				$pdf->Cell($t_width[6],4,'',1,0,'L',0);
				$pdf->Cell($t_width[7],4,'',1,0,'L',0);
				$pdf->Ln();
			}

			if($nxt[1]==1)
			{
				$pr+=$nxt[3];
				$kr=$nxt[3];
			}
			else if($nxt[1]==15)
			{
				$ras+=$nxt[3];
				$deb=$nxt[3];
			}
			if( ($nxt[1]==2) || ($nxt[1]==12))
			{
				continue;
			}
			if($nxt[1]==4)
			{
				$pr+=$nxt[3];
				$kr=$nxt[3];
			}
			if($nxt[1]==5)
			{
				$ras+=$nxt[3];
				$deb=$nxt[3];
			}
			if($nxt[1]==6)
			{
				$pr+=$nxt[3];
				$kr=$nxt[3];
			}
			if($nxt[1]==7)
			{
				$ras+=$nxt[3];
				$deb=$nxt[3];
			}

			if($f_print)
			{
				if(!$nxt[4]) $nxt[4]=$nxt[0];
				if($deb) $deb=sprintf("%01.2f", $deb);
				if($kr) $kr=sprintf("%01.2f", $kr);
				$dt=date("d.m.Y",$nxt[2]);
				$str=iconv('UTF-8', 'windows-1251', "$nxt[5] N$nxt[4]");
				$pdf->Cell($t_width[0],4,$dt,1,0,'L',0);
				$pdf->Cell($t_width[1],4,$str,1,0,'L',0);
				$pdf->Cell($t_width[2],4,$deb,1,0,'R',0);
				$pdf->Cell($t_width[3],4,$kr,1,0,'R',0);
				$pdf->Cell($t_width[4],4,'',1,0,'L',0);
				$pdf->Cell($t_width[5],4,'',1,0,'L',0);
				$pdf->Cell($t_width[6],4,'',1,0,'L',0);
				$pdf->Cell($t_width[7],4,'',1,0,'L',0);
				$pdf->Ln();
			}
		}

		$razn=$pr-$ras;
		$razn_p=abs($razn);
		$razn_p=sprintf("%01.2f", $razn_p);

		$pr=sprintf("%01.2f", $pr);
		$ras=sprintf("%01.2f", $ras);

		$str=iconv('UTF-8', 'windows-1251', "Обороты за период");
		$pdf->Cell($t_width[0]+$t_width[1],4,$str,1,0,'L',0);
		$pdf->Cell($t_width[2],4,$ras,1,0,'R',0);
		$pdf->Cell($t_width[3],4,$pr,1,0,'R',0);
		$pdf->Cell($t_width[4]+$t_width[5],4,'',1,0,'L',0);
		$pdf->Cell($t_width[6],4,'',1,0,'L',0);
		$pdf->Cell($t_width[7],4,'',1,0,'L',0);
		$pdf->Ln();

		if($pr>$ras)
		{
			$pr-=$ras;
			$ras='';
		}
		else if($pr<$ras)
		{
			$ras-=$pr;
			$pr='';
		}
		else  $pr=$ras='';
		if($pr)	$pr=sprintf("%01.2f", $pr);
		if($ras)$ras=sprintf("%01.2f", $ras);

		$str=iconv('UTF-8', 'windows-1251', "Сальдо на конец периода");
		$pdf->Cell($t_width[0]+$t_width[1],4,$str,1,0,'L',0);
		$pdf->Cell($t_width[2],4,$ras,1,0,'L',0);
		$pdf->Cell($t_width[3],4,$pr,1,0,'L',0);
		$pdf->Cell($t_width[4]+$t_width[5],4,'',1,0,'L',0);
		$pdf->Cell($t_width[6],4,'',1,0,'L',0);
		$pdf->Cell($t_width[7],4,'',1,0,'L',0);
		$pdf->Ln(7);

		$str=iconv('UTF-8', 'windows-1251', "По данным {$firm_vars['firm_name']} на ".date("d.m.Y",$date_end));
		$pdf->Write(4,$str);
		$pdf->Ln();
		if($razn>0)		$str="переплата в пользу ".$firm_vars['firm_name']." $razn_p руб.";
		else	if($razn<0) 	$str="задолженность в пользу ".$firm_vars['firm_name']." $razn_p руб.";
		else			$str="переплат и задолженностей нет!";

		$str=iconv('UTF-8', 'windows-1251', $str);
		$pdf->Write(4,$str);
		$pdf->Ln(7);
		$x=$pdf->getX()+$t_width[0]+$t_width[1]+$t_width[2]+$t_width[3];
		$y=$pdf->getY();
		$str=iconv('UTF-8', 'windows-1251', "От {$firm_vars['firm_name']}\n\nДиректор ____________________________ ({$firm_vars['firm_director']})\n\n           м.п.");
		$pdf->MultiCell($t_width[0]+$t_width[1]+$t_width[2]+$t_width[3],5,$str,0,'L',0);
		$str=iconv('UTF-8', 'windows-1251', "От {$agent['fullname']}\n\n           ____________________________ (                )\n\n           м.п.");
		$pdf->lMargin=$x;
		$pdf->setX($x);

		$pdf->setY($y);
		$pdf->MultiCell(0,5,$str,0,'L',0);
		$pdf->Ln();

// 			$tmpl->AddText("<td colspan=4>
// 			<tr><td colspan=4>От ".$dv['firm_name']."<br>
// 			директор<br>____________________________ (".$dv['firm_director'].")<br><br>м.п.<br>
// 			<td colspan=4>От $fn<br>
// 			директор<br> ____________________________ (_____________)<br><br>м.п.<br>
// 			</table>");

		$pdf->Output('akt_sverki.pdf','I');
	}
}
else if($mode=='agent_otchet')
{
	$tmpl->AddText("<h1>отчёт по агенту</h1>
	<form action=''>
	<input type=hidden name=mode value='agent_otchet_ex'>
	Агент:<br>
	<input type='hidden' name='agent' id='agent_id' value=''>
	<input type='text' id='agent_nm'  style='width: 450px;' value=''><br>
	<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<script type=\"text/javascript\">
		$(document).ready(function(){
			$(\"#agent_nm\").autocomplete(\"/docs.php\", {
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
		</script>
	<input type=submit value='Сгенерировать'>
	</form>
	<br><br><br>");
}
else if($mode=='agent_otchet_ex')
{
	$agent=rcv('agent');
	$tmpl->LoadTemplate('print');
	$res=mysql_query("SELECT `name` FROM `doc_agent` WHERE `id`='$agent'");
	$ag_name=mysql_result($res,0,0);
	$tmpl->AddText("<h1>Отчет по: $ag_name</h1>
	<table><tr>
	<th>Документ<th>Приход<th>Расход<th>Остаток");
	$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`sum`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`, `doc_types`.`name`
	FROM `doc_list`
	LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
	WHERE `doc_list`.`ok`>'0' AND `doc_list`.`mark_del`='0' AND `doc_list`.`agent`='$agent'
	ORDER BY `doc_list`.`date`");
	$sum=0;
	echo mysql_error();
	while($nxt=mysql_fetch_row($res))
	{
		$prix=$rasx=$tovar=0;
		switch($nxt[1])
		{
			case 1: $prix=$nxt[2]; $tovar=1; break;
			case 2: $rasx=$nxt[2]; $tovar=1; break;
			case 4: $prix=$nxt[2]; break;
			case 5: $rasx=$nxt[2]; break;
			case 6: $prix=$nxt[2]; break;
			case 7: $rasx=$nxt[2]; break;
		}
		$sum=$sum+$prix-$rasx;
		$sum_p=$prix_p=$rasx_p='';
		if($sum) $sum_p=sprintf("%0.2f",$sum);
		if($prix) $prix_p=sprintf("%0.2f",$prix);
		if($rasx) $rasx_p=sprintf("%0.2f",$rasx);
		$dt=date("d.m.Y H:i:s",$nxt[5]);

		if($tovar)
		{
			$tovar='';
			$rs=mysql_query("SELECT `doc_base`.`name`, `doc_base`.`proizv`,  `doc_list_pos`.`cnt`
			FROM `doc_list_pos`
			LEFT JOIN `doc_base` ON `doc_base`.`id`= `doc_list_pos`.`tovar`
			WHERE `doc_list_pos`.`doc`='$nxt[0]'");
			while($nx=mysql_fetch_row($rs))
			{
				if(!$tovar) $tovar="$nx[0]/$nx[1]:$nx[2]";
				else $tovar.=", $nx[0]/$nx[1]:$nx[2]";
			}
			$tovar="<br>Товары: $tovar";
		}
		else $tovar='';

		$tmpl->AddText("<tr><td>$nxt[6] N$nxt[3]$nxt[4] ($nxt[0])<br>от $dt $tovar<td>$prix_p<td>$rasx_p<td>$sum_p");
	}
	$tmpl->AddText("</table><br>");
}
else if($mode=='img_otchet')
{
	$tmpl->LoadTemplate('print');
	$tmpl->SetText("<h1>Отчёт по изображениям</h1>");
	$res=mysql_query("SELECT `doc_base_img`.`img_id`, `doc_img`.`name`, `doc_img`.`type`, `doc_base_img`.`default`, `doc_base_img`.`pos_id`, `doc_base`.`name` AS `pos_name`, `doc_base`.`proizv`, `doc_base`.`vc`, `doc_group`.`printname`
	FROM `doc_base_img`
	INNER JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
	INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_base_img`.`pos_id`
	INNER JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
	ORDER BY `doc_base_img`.`img_id`");
	if(mysql_errno())	throw new MysqlException("Не удалось выбрать список изображений");

	$tmpl->AddText("<table width='100%'>
	<tr><th>ID<th>Изображение<th>Умолч.<th>ID товара<th>Код<th>Наименование / произв.");
	while($nxt=mysql_fetch_array($res))
	{
		$tmpl->AddText("<tr><td>{$nxt['img_id']}<td>{$nxt['name']} ({$nxt['type']})<td>{$nxt['default']}<td>{$nxt['pos_id']}<td>{$nxt['vc']}<td>{$nxt['printname']} {$nxt['pos_name']} / {$nxt['proizv']}");
	}
	$tmpl->AddText("</table>");

}
else if($mode=='prod')
{
	$tmpl->SetTitle("Отчёт по продажам");
	$opt=rcv('opt');
	if($opt=='')
	{
		$d_t=date("Y-m-d");
		$d_f=date("Y-m-d",time()-60*60*24*31);
		$tmpl->AddText("<h1>Отчёт по продажам</h1>
		<form method='get'>
		<input type='hidden' name='mode' value='prod'>
		<input type='hidden' name='opt' value='get'>
		<p class='datetime'>
		<fieldset><legend>Дата</legend>
		С:<input type=text id='id_pub_date_date' class='vDateField required' name='dt_f' value='$d_f'><br>
		По:<input type=text id='id_pub_date_date' class='vDateField required' name='dt_t' value='$d_t'>
		</fieldset>
		Фильтр по наименованию:<br>
		<input type='text' name='f_name'><br>
		Фильтр по производителю:<br>
		<input type='text' name='f_proizv'><br>
		Фильтр по коду:<br>
		<input type='text' name='f_vc'><br>
		</p>
		<button type='submit'>Сформировать отчёт</button>
		</form>");
	}
	else
	{
		$tmpl->LoadTemplate('print');
		$dt_f=strtotime(rcv('dt_f'));
		$dt_t=strtotime(rcv('dt_t'));
		$f_name=rcv('f_name');
		$f_proizv=rcv('f_proizv');
		$f_vc=rcv('f_vc');
		$sql_add='';
		if($f_name)	$sql_add.=" AND `doc_base`.`name` LIKE '%$f_name%' ";
		if($f_proizv)	$sql_add.=" AND `doc_base`.`proizv` LIKE '%$f_proizv%' ";
		if($f_vc)	$sql_add.=" AND `doc_base`.`vc` LIKE '%$f_vc%' ";
		if($sql_add)	$sql_add="WHERE 1 ".$sql_add;
		$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`likvid`, SUM(`doc_list_pos`.`cnt`), SUM(`doc_list_pos`.`cnt`*`doc_list_pos`.`cost`)
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<='$dt_t' AND `doc_list`.`type`='2' AND `doc_list`.`ok`>'0'
		$sql_add
		GROUP BY `doc_list_pos`.`tovar`
		ORDER BY `doc_base`.`name`");

		$print_df=date('Y-m-d', $dt_f);
		$print_dt=date('Y-m-d', $dt_t);
		$tmpl->AddText("
		<h1>Отчёт по продажам с $print_df по $print_dt</h1>
		<table width='100%'>
		<tr><th>ID<th>Наименование<th>Ликвидность<th>Кол-во проданного<th>Сумма по поступлениям<th>Сумма продаж<th>Прибыль");
		$cntsum=$postsum=$prodsum=$pribsum=0;
		while($nxt=mysql_fetch_row($res))
		{
			$insum=sprintf('%0.2f.', GetInCost($nxt[0])*$nxt[3]);
			$prib=sprintf('%0.2f', $nxt[4]-$insum);
			$cntsum+=$nxt[3];
			$postsum+=$insum;
			$prodsum+=$nxt[4];
			$pribsum+=$prib;
			$prib_style=$prib<0?"style='color: #f00'":'';
			$tmpl->AddText("<tr><td>$nxt[0]<td>$nxt[1]<td>$nxt[2] %<td>$nxt[3]<td>$insum руб.<td>$nxt[4] руб.<td $prib_style>$prib руб.");
		}
		$prib_style=$pribsum<0?"style='color: #f00'":'';
		$tmpl->AddTExt("
		<tr><td colspan='3'>Итого:<td>$cntsum<td>$postsum руб.<td>$prodsum руб.<td $prib_style>$pribsum руб.
		</table>");
	}
}
else if($mode=='bezprodaj')
{
	$tmpl->SetTitle("Отчёт по товарам без продаж");
	$opt=rcv('opt');
	if($opt=='')
	{
		$d_t=date("Y-m-d");
		$d_f=date("Y-m-d",time()-60*60*24*31);
		$tmpl->AddText("<h1>Отчёт по товарам без продаж за заданный период</h1>
		<form method='get'>
		<input type='hidden' name='mode' value='bezprodaj'>
		<input type='hidden' name='opt' value='get'>
		<p class='datetime'>
		<fieldset><legend>Дата</legend>
		С:<input type=text id='id_pub_date_date' class='vDateField required' name='dt_f' value='$d_f'><br>
		По:<input type=text id='id_pub_date_date' class='vDateField required' name='dt_t' value='$d_t'>
		</fieldset>
		</p>
		<button type='submit'>Сформировать отчёт</button>
		</form>");
	}
	else
	{
		$tmpl->LoadTemplate('print');
		$dt_f=strtotime(rcv('dt_f'));
		$dt_t=strtotime(rcv('dt_t'));
		$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`likvid`
		FROM `doc_base`
		WHERE `doc_base`.`id` NOT IN (
		SELECT `doc_list_pos`.`tovar` FROM `doc_list_pos`
		INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<='$dt_t' AND `doc_list`.`type`='2' AND `doc_list`.`ok`>'0'
		)
		ORDER BY `doc_base`.`name`");

		$print_df=date('Y-m-d', $dt_f);
		$print_dt=date('Y-m-d', $dt_t);
		$tmpl->AddText("
		<h1>Отчёт по продажам с $print_df по $print_dt</h1>
		<table width='100%'>
		<tr><th>ID<th>Наименование<th>Ликвидность");
		$cnt=0;
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<tr><td>$nxt[0]<td>$nxt[1]<td>$nxt[2] %");
			$cnt++;
		}
		$prib_style=$pribsum<0?"style='color: #f00'":'';
		$tmpl->AddTExt("
		<tr><td>Итого:<td colspan='2'>$cnt товаров без продаж
		</table>");
	}
}
else if($mode=='doc_reestr')
{
	$opt=rcv('opt');
	$date=date("Y-m-d");
	if($opt=='')
	{
		$tmpl->AddText("<h1>Реестр документов</h1>
		<form action='' method=post><input type=hidden name=mode value=doc_reestr>
		<input type=hidden name=opt value=pdf>
		<p class='datetime'>
		<fieldset><legend>Дата</legend>
		С:<input type=text id='id_pub_date_date' class='vDateField required' name='dt_f' value='$date'><br>
		По:<input type=text id='id_pub_date_date' class='vDateField required' name='dt_t' value='$date'>
		</fieldset>
		</p><br>Вид документов:<br>
		<select name='doc_type'>
		<option value='0'>-- без отбора --</option>");
		$res=mysql_query("SELECT * FROM `doc_types` ORDER BY `name`");
		while($nxt=mysql_fetch_row($res))
		{
			$ss='';
			if($dsel==$nxt[0]) $ss='selected';
			$tmpl->AddText("<option value='$nxt[0]' $ss>$nxt[1]</option>");
		}
		$tmpl->AddText("
		</select><br>
		Организация:<br>
		<select name='firm_id'>
		<option value='0'>-- без отбора --</option>");
		$res=mysql_query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `firm_name`");
		while($nxt=mysql_fetch_row($res))
		{
			$ss='';
			if($dsel==$nxt[0]) $ss='selected';
			$tmpl->AddText("<option value='$nxt[0]' $ss>$nxt[1]</option>");
		}
		$tmpl->AddText("
		</select><br>
		Подтип документов:<br>
		<input type='text' name='subtype'><br>
		<input type=submit value='Показать'></form>");
	}
	else
	{
		$dt_f=strtotime(rcv('dt_f'));
		$dt_t=strtotime(rcv('dt_t')." 23:59:59");
		$firm_id=rcv('firm_id');
		$doc_type=rcv('doc_type');
		$subtype=rcv('subtype');
		DocReestrPDF('',$dt_f, $dt_t, $doc_type, $firm_id, $subtype);
	}
}
else if($mode=='cost')
{
	$tmpl->SetTitle("Отчёт по ценам");
	$opt=rcv('opt');
	if($opt=='')
	{
		$tmpl->AddText("<h1>Отчёт по ценам</h1>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='cost'>
		<input type='hidden' name='opt' value='get'>
		Отображать следующие расчётные цены:<br>");
		$res=mysql_query("SELECT `id`, `name` FROM `doc_cost` ORDER BY `id");
		if(mysql_errno())	throw new MysqlException("Не удалось выбрать список цен");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<label><input type='checkbox' name='cost$nxt[0]' value='1' checked>$nxt[1]</label><br>");
		}
		$tmpl->AddText("<button type='submit'>Сформировать отчёт</button>
		</form>
		");
	}
	else
	{
		$tmpl->LoadTemplate('print');
		$tmpl->AddText("<h1>Отчёт по ценам</h1>");
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
		foreach($costs as $cost_name)
			$tmpl->AddText("<th>$cost_name");

		$res=mysql_query("SELECT `id`, `vc`, `name`, `proizv`, `cost` FROM `doc_base`
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

		$tmpl->AddText("</table>");
	}
}
else if($mode=='agent_bez_prodaj')
{
	$opt=rcv('opt');
	if($opt=='')
	{
		$tmpl->AddText("<h1>Агенты без продаж за заданный период</h1>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='agent_bez_prodaj'>
		<input type='hidden' name='opt' value='html'>
		<p class='datetime'>
		<fieldset><legend>Дата</legend>
		<input type=text id='id_pub_date_date' class='vDateField required' name='dt_f' value='$date'>
		</fieldset>
		<label><input type='checkbox' name='fix' value='1'>Только с назначенным ответственным лицом</label><br>
		<input type=submit value='Показать'></form>");
	}
	else
	{
		$sql_add= (rcv('fix')==1) ? " AND `doc_agent`.`responsible`>'0' " : '';
		$tmpl->AddText("<h1>Агенты без продаж с $dt_f по текущий момент</h1><ul>");

		$res=mysql_query("SELECT `doc_agent`.`id`, `doc_agent`.`name`, `doc_agent`.`responsible`, `users`.`name` FROM `doc_agent`
		LEFT JOIN `users` ON `users`.`id`=`doc_agent`.`responsible`
		WHERE `doc_agent`.`id` NOT IN (SELECT `agent` FROM `doc_list` WHERE `date`>='$dt_sql' ) $sql_add");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<li>id:$nxt[0] - $nxt[1] ($nxt[3], id:$nxt[2])</li>");
		}
		$tmpl->AddText("</ul>");
	}
}
else $tmpl->msg("ERROR $mode","err");


$tmpl->write();



function DocReestrPDF($to_str='', $from_date=0, $to_date=0, $doc_type=0, $firm_id=0, $subtype='')
{
	settype($from_date,'int');
	settype($to_date,'int');
	settype($doc_type,'int');
	settype($firm_id,'int');
	define('FPDF_FONT_PATH',$CONFIG['site']['location'].'/fpdf/font/');
	require('fpdf/fpdf_mysql.php');
	global $tmpl;
	$tmpl->ajax=1;
	$pdf=new FPDF('P');
	$pdf->Open();
	$pdf->SetAutoPageBreak(1,12);
	$pdf->AddFont('Arial','','arial.php');
	$pdf->tMargin=5;
	$pdf->AddPage('L');
	$pdf->SetFillColor(255);

	$pdf->SetFont('Arial','',16);

	$str = iconv('UTF-8', 'windows-1251', "Реестр документов");
	$pdf->Cell(0,6,$str,0,1,'C');

	$str='Показаны';
	if($doc_type)
	{
		$res=mysql_query("SELECT `name` FROM `doc_types` WHERE `id`='$doc_type'");
		$doc_name=mysql_result($res,0,0);
		$str.=' документы типа "'.$doc_name.'"';
	}	else $str.=' все документы';

	$str.=' за период c '.date("Y-m-d",$from_date)." по ".date("Y-m-d",$to_date);

	if($firm_id)
	{
		$res=mysql_query("SELECT `firm_name` FROM `doc_vars` WHERE `id`='$firm_id'");
		$firm_name=mysql_result($res,0,0);
		$str.=', по организации "'.$firm_name.'"';
	}

	if($subtype)
	{
		$str.=", с подтипом $subtype";
	}

	$pdf->SetFont('Arial','',10);
	$str = iconv('UTF-8', 'windows-1251', $str);
	$str=unhtmlentities($str);
	$pdf->MultiCell(0,3,$str,0,'C');
	$pdf->Ln(5);

	$pdf->SetFont('','',8);
	$pdf->SetLineWidth(0.5);
	$t_width=array(10,15,40,13,18,19,18,8,0);
	$t_text=array('N п/п', 'Дата', 'Документ', 'Номер', 'Автор', 'Статус', 'Сумма', 'Вал.', 'Информация');
	foreach($t_width as $i => $w)
	{
		$str = iconv('UTF-8', 'windows-1251', $t_text[$i]);
		$pdf->Cell($w,5,$str,1,0,'C',0);
	}
	$pdf->Ln();
	mysql_query("SET character_set_results = cp1251");
	$step=4;
	$pdf->SetFont('','',7);
	$pdf->SetLineWidth(0.2);

	$sqla='';
	if($doc_type)	$sqla.=" AND `doc_list`.`type`='$doc_type'";
	if($firm_id)	$sqla.=" AND `doc_list`.`firm_id`='$firm_id'";

	$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_types`.`name`, CONCAT(`doc_list`.`altnum`,`doc_list`.`subtype`) , `users`.`name`, `doc_list`.`ok`, `doc_list`.`sum`, 'р', `doc_agent`.`name`
	FROM `doc_list`
	LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
	LEFT JOIN `users` ON `users`.`id` = `doc_list`.`user`
	LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
	WHERE `date`>='$from_date' AND `date`<='$to_date' $sqla
	ORDER BY `doc_list`.`altnum`");
	echo mysql_error();
	$i=1;
	while($nxt=mysql_fetch_row($res))
	{
		$date_p=date('Y-m-d',$nxt[1]);
		if($nxt[5])	$status=iconv('UTF-8', 'windows-1251', 'Проведён');
		else		$status=iconv('UTF-8', 'windows-1251', 'Не проведен');
		$nxt[6]=sprintf("%0.2f",$nxt[6]);
		$nxt[8]=unhtmlentities($nxt[8]);
		$pdf->Cell($t_width[0],$step,$i,1,0,'C',0);
		$pdf->Cell($t_width[1],$step,$date_p,1,0,'C',0);
		$pdf->Cell($t_width[2],$step,$nxt[2],1,0,'L',0);
		$pdf->Cell($t_width[3],$step,$nxt[3],1,0,'R',0);
		$pdf->Cell($t_width[4],$step,$nxt[4],1,0,'R',0);
		$pdf->Cell($t_width[5],$step,$status,1,0,'R',0);
		$pdf->Cell($t_width[6],$step,$nxt[6],1,0,'R',0);
		$pdf->Cell($t_width[7],$step,$nxt[7],1,0,'C',0);
		$pdf->Cell($t_width[8],$step,$nxt[8],1,0,'L',0);
		$pdf->Ln();
		$i++;
	}


	mysql_query("SET character_set_results = utf8");
	if($to_str)
		return $pdf->Output('doc_reestr.pdf','S');
	else
		$pdf->Output('doc_reestr.pdf','I');
}



?>

