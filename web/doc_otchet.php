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

function otch_list()
{
	return "
	<a href='doc_otchet.php?mode=bezprodaj'><div>Агенты без продаж</div></a>
	<a href='doc_otchet.php?mode=sverka'><div>Акт сверки</div></a>
	<a href='doc_otchet.php?mode=balance'><div>Балланс</div></a>
	<a href='doc_otchet.php?mode=dolgi'><div>Долги: партнёров</div></a>
	<a href='doc_otchet.php?mode=dolgi&amp;opt=1'><div>Долги: наши</div></a>
	<a href='doc_otchet.php?mode=kassday'><div>Кассовый отчёт за день</div></a>
	<a href='doc_otchet.php?mode=ostatki'><div>Остатки на складе</div></a>
	<a href='doc_otchet.php?mode=agent_otchet'><div>Отчет по агенту</div></a>
	<a href='doc_otchet.php?mode=proplaty'><div>Отчет по проплатам</div></a>
	<a href='doc_otchet.php?mode=prod'><div>Отчёт по продажам</div></a>
	<a href='doc_otchet.php?mode=bezprodaj'><div>Отчёт по товарам без продаж</div></a>
	<a href='doc_otchet.php?mode=doc_reestr'><div>Реестр документов</div></a>
	<a href='doc_otchet.php?mode=fin_otchet'><div>Сводный финансовый отчёт</div></a>
	<a href='doc_otchet.php?mode=bank_comp'><div>Сверка банка</div></a>	
	<hr>
	<a href='doc_otchet.php'><div>Другие отчёты</div></a>";
}

$rights=getright('doc_otchet',$uid);
if($rights['read'])
{
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
		$tmpl->AddText(otch_list());
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
    
	else if($mode=='dolgi')
	{
		$opt=rcv('opt');
		$tmpl->LoadTemplate('print');
		if($opt) $tmpl->SetText("<h1>Мы должны (от ".date('d.m.Y').")</h1>");
		else $tmpl->SetText("<h1>Долги партнёров (от ".date('d.m.Y').")</h1>");
		$tmpl->AddText("<table width=100%><tr><th>N<th>Агент - партнер<th>Дата сверки<th>Сумма");
	 	$res=mysql_query("SELECT `id`, `name`, `data_sverki` FROM `doc_agent` ORDER BY `name`");
	 	$i=0;
	 	$sum_dolga=0;
	 	while($nxt=mysql_fetch_row($res))
	 	{
	 		$dolg=DocCalcDolg($nxt[0],0);
	 		if( (($dolg>0)&&(!$opt))|| (($dolg<0)&&($opt)) )
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
	else if($mode=='ostatki')
	{
		$tmpl->LoadTemplate('print');
		$tmpl->SetText("<h1>Остатки товара на складе</h1>
		<table width=100%><tr><th>N<th>Наименование<th>Количество<th>Актуальная цена<br>поступления<th>Базовая цена<th>Сумма по АЦП<th>Сумма по базовой");
		$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`cost`,
		(SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`),
		`doc_base_dop`.`mass`
		FROM `doc_base`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
		ORDER BY `doc_base`.`name`");
		echo mysql_error();
		$sum=$bsum=$summass=0;
		while($nxt=mysql_fetch_row($res))
		{
			if($nxt[3]==0) continue;
			$act_cost=sprintf('%0.2f',GetInCost($nxt[0]));
			$cost_p=sprintf("%0.2f",$nxt[2]);
			$sum_p=sprintf("%0.2f",$act_cost*$nxt[3]);
			$bsum_p=sprintf("%0.2f",$nxt[2]*$nxt[3]);
			$sum+=$act_cost*$nxt[3];
			$bsum+=$nxt[2]*$nxt[3];
			if($nxt[3]<0) $nxt[3]='<b>'.$nxt[3].'</b/>';
			$summass+=$nxt[3]*$nxt[4];
			
			$tmpl->AddText("<tr><td>$nxt[0]<td>$nxt[1]<td>$nxt[3]<td>$act_cost<td>$cost_p<td>$sum_p<td>$bsum_p");
		}
		$tmpl->AddText("<tr><td colspan='5'><b>Итого:</b><td>$sum<td>$bsum
		</table><h3>Общая масса склада: $summass кг.</h3>");
	
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
		<input type=hidden name=mode value='fin_otchet_g'>
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
				if($vid==0)	$doc_null.="касса:$nxt[0], ";
				if($vid==12)	$doc_otchet.="касса:$nxt[0], ";
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
		doc_menu();
		$tmpl->SetTitle("Акт сверки");
		$dat=date("Y-m-d");
		
		$tmpl->AddText("<h1><b>Акт сверки</b></h1>
		<form action='' method='post'>
		<input type=hidden name=mode value='sverka_g'>
		<div class=group300>
		Задание начальных условий:
		<div>
		Агент-партнёр:<br>
		<input type=text id='aga' name='ag' value='$av' onkeydown=\"return AutoFill('/docj.php?mode=filter&opt=ags','aga','dda')\">
		<a onclick=\"ClearText('aga'); return false;\" href=''><img src='img/icon_del.gif'></a>
		<div id='dda' class='dd'></div><br>
		<br>
		<p class='datetime'>
		Дата от:<input type='text' id='id_pub_date_date' class='vDateField required' name='date_st' size='10' value='1970-01-01' maxlength='10' /><br>
		до:<input type='text' id='id_pub_date_date' class='vDateField required' name='date_end' size='10' value='$dat' maxlength='10' /></p><br>
		Организация:<br><select name='firm_id'>
		<option value='0'>--- Любая ---</option>");
		$rs=mysql_query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `firm_name`");
		while($nx=mysql_fetch_row($rs))
		{
			if($_SESSION['firm']==$nx[0]) $s=' selected'; else $s='';
			$tmpl->AddText("<option value='$nx[0]' $s>$nx[1]</option>");		
		}		
		$tmpl->AddText("</select><br>
		Подтип документа (оставьте пустым, если учитывать не требуется):<br>
		<input type='text' name='subtype'><br>
		<input type=submit value='Сделать сверку!'>
		</div>
		</div>
		</form>");	
	}
	else if($mode=="sverka_g")
	{
		$firm_id=rcv('firm_id');
		$subtype=rcv('subtype');
		
		$date_st=strtotime(rcv('date_st'));
		$date_end=strtotime(rcv('date_end'))+60*60*24-1;
		$ag=rcv('ag');
		
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
	
		$tmpl->AddText("<center>Акт сверки<br>
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
			else if($nxt[1]==2)
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
	else if($mode=='agent_otchet')
	{
		$tmpl->AddText("<h1>отчёт по агенту</h1>
		<form action=''>
		<input type=hidden name=mode value='agent_otchet_ex'>
		Агент-партнёр:<br>
		<input type=hidden name=agent value='$doc_data[2]' id='sid' >
		<input type=text id='sdata' value='$doc_data[3]' onkeydown=\"return RequestData('/docs.php?l=agent&mode=srv&opt=popup')\">
		<div id='popup'></div>
		<div id=status></div>
		<input type=submit value='Сгенерировать'>
		</form>");
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
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`sum`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`
		FROM `doc_list`
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
			
			$tmpl->AddText("<tr><td>".$doc_types[$nxt[1]]." N$nxt[3]$nxt[4] ($nxt[0])<br>от $dt $tovar<td>$prix_p<td>$rasx_p<td>$sum_p");
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
			</p>
			<button type='submit'>Сформировать отчёт</button>
			</form>");
		}
		else
		{
			$tmpl->LoadTemplate('print');
			$dt_f=strtotime(rcv('dt_f'));
			$dt_t=strtotime(rcv('dt_t'));
			$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`likvid`, SUM(`doc_list_pos`.`cnt`), SUM(`doc_list_pos`.`cnt`*`doc_list_pos`.`cost`)
			FROM `doc_list_pos`
			LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
			INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<='$dt_t' AND `doc_list`.`type`='2' AND `doc_list`.`ok`>'0'
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
			</p><br>Вид документиов:<br>
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
			$dt_t=strtotime(rcv('dt_t'));
			$firm_id=rcv('firm_id');
			$doc_type=rcv('doc_type');
			$subtype=rcv('subtype');
			DocReestrPDF('',$dt_f, $dt_t, $doc_type, $firm_id, $subtype);
		}
	}
	else if($mode=='bezprodaj')
	{
		$opt=rcv('opt');
		$date=date("Y-m-d",time()-60*60*24*90);
		$dt_f=rcv('dt_f');
		$dt_sql=strtotime($dt_f);
		if($opt=='')
		{
			$tmpl->AddText("<h1>Агенты без продаж за заданный период</h1>
			<form action='' method='post'>
			<input type='hidden' name='mode' value='bezprodaj'>
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
	else if($mode=='kassday')
	{
		$opt=rcv('opt');
		if($opt=='')
		{
			$tmpl->AddText("<h1>Отчёт по кассе за текущий день</h1>
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
			<button type='submit'>Сформировать</button>	
			</form>");
			
		}
		else
		{
			$tmpl->LoadTemplate('print');
			$dt=date("Y-m-d");
			$kass=rcv('kass');
			$tmpl->AddText("<h1>Отчёт по кассе за текущий день - $dt</h1>");		
			$daytime=strtotime(date("d M Y 00:00:00"));
			
			$tmpl->AddText("<table width='100%'><tr><th>ID<th>Время<th>Документ<th>Сумма документа<th>В кассе");			
			$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`sum`, `doc_list`.`date`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_types`.`name`, `doc_agent`.`name`, `doc_list`.`p_doc`, `t`.`name`, `p`.`altnum`, `p`.`subtype`, `p`.`date`, `p`.`sum`		
			FROM `doc_list`
			INNER JOIN `doc_agent`		ON `doc_agent`.`id` = `doc_list`.`agent`
			INNER JOIN `doc_types`		ON `doc_types`.`id` = `doc_list`.`type`
			LEFT JOIN `doc_list` AS `p`	ON `p`.`id`=`doc_list`.`p_doc`
			LEFT JOIN `doc_types` AS `t`	ON `t`.`id` = `p`.`type`		
			WHERE `doc_list`.`ok`>'0' AND ( `doc_list`.`type`='6' OR `doc_list`.`type`='7') AND `doc_list`.`kassa`='$kass'
			ORDER BY `doc_list`.`date`");
			$sum=$daysum=$prix=$rasx=0;
			$flag=0;
			while($nxt=mysql_fetch_row($res))
			{			
				if( !$flag && $nxt[3]>$daytime)
				{
					$flag=1;
					$sum_p=sprintf("%0.2f руб.",$sum);
					$tmpl->AddText("<tr><td>-<td>-<td><b>На начало дня</b><td>-<td align='right'><b>$sum_p</b>");
				}
				if($nxt[1]==6)		$sum+=$nxt[2];
				else 			$sum-=$nxt[2];
				if($nxt[3]>$daytime)
				{
					if($nxt[1]==6)
					{
						$daysum+=$nxt[2];
						$prix+=$nxt[2];
					}
					else
					{
						$daysum-=$nxt[2];
						$rasx+=$nxt[2];
					}
					if($nxt[8])	$sadd="<br><i>к $nxt[9] N$nxt[10]$nxt[11] от ".date("d-m-Y H:i:s",$nxt[12])." на сумму ".sprintf("%0.2f руб",$nxt[13])."</i>";
					else		$sadd='';
					$dt=date("H:i:s",$nxt[3]);
					$sum_p=sprintf("%0.2f руб.",$sum);
					$csum_p=sprintf("%0.2f руб.",$nxt[2]);
					$tmpl->AddText("<tr><td>$nxt[0]<td>$dt<td>$nxt[6] N$nxt[4]$nxt[5] $sadd<br>от $nxt[7]<td align='right'>$csum_p<td align='right'>$sum_p");	
				}
			}
			$dsum_p=sprintf("%0.2f руб.",$daysum);
			$psum_p=sprintf("%0.2f руб.",$prix);
			$rsum_p=sprintf("%0.2f руб.",$rasx);
			$tmpl->AddText("<tr><td>-<td>-<td><b>На конец дня</b><td>-<td align='right'><b>$sum_p</b>");
			$tmpl->AddText("<tr><td>-<td>-<td><b>Приход за смену</b><td>-<td align='right'><b>$psum_p</b>");
			if($rasx)
			{
				$tmpl->AddText("<tr><td>-<td>-<td><b>Расход за смену</b><td>-<td align='right'><b>$rsum_p</b>");
				$tmpl->AddText("<tr><td>-<td>-<td><b>Разница за смену</b><td>-<td align='right'><b>$dsum_p</b>");
			}
			
			$res=mysql_query("SELECT `name` FROM `users` WHERE `id`='$uid'");
			$nm=mysql_result($res,0,0);
			
			$tmpl->AddText("</table><br><br>
			Cоответствие суммы подтверждаю ___________________ ($nm)");
		}
	}
	else $tmpl->msg("ERROR $mode","err");
}
else if($mode=='bank_comp')
{


}
else $tmpl->msg("Недостаточно привилегий для выполнения операции!","err");

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


