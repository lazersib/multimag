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
	'doc_otchet.php?mode=prod' => 'Отчёт по продажам',
	'doc_otchet.php?mode=bezprodaj' => 'Отчёт по товарам без продаж',
	'doc_otchet.php?mode=doc_reestr' => 'Реестр документов',
	'doc_reports.php' => 'Новые отчёты');
}

function otch_list()
{
	return "
	<a href='doc_otchet.php?mode=bezprodaj'><div>Агенты без продаж</div></a>
	<a href='doc_otchet.php?mode=prod'><div>Отчёт по продажам</div></a>
	<a href='doc_otchet.php?mode=bezprodaj'><div>Отчёт по товарам без продаж</div></a>
	<a href='doc_otchet.php?mode=cost'><div>Отчёт по ценам</div></a>
	<a href='doc_otchet.php?mode=doc_reestr'><div>Реестр документов</div></a>
	<hr>
	<a href='doc_reports.php'><div>Новые отчёты</div></a>";
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

