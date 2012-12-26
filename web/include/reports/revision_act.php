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


class Report_Revision_Act
{
	function getName($short=0)
	{
		if($short)	return "Акт сверки";
		else		return "Акт сверки взаимных расчетов";
	}


	function Form()
	{
		global $tmpl, $CONFIG;
		$date_end=date("Y-m-d");
		$tmpl->AddText("<h1>".$this->getName()."</h1>
		<script src='/css/jquery/jquery.js' type='text/javascript'></script>
		<script src='/css/jquery/jquery.alerts.js' type='text/javascript'></script>
		<link href='/css/jquery/jquery.alerts.css' rel='stylesheet' type='text/css' media='screen'>
		<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<link rel='stylesheet' href='/css/jquery/ui/themes/base/jquery.ui.all.css'>
		<script src='/css/jquery/ui/jquery.ui.core.js'></script>
		<script src='/css/jquery/ui/jquery.ui.widget.js'></script>
		<script src='/css/jquery/ui/jquery.ui.datepicker.js'></script>
		<script src='/css/jquery/ui/i18n/jquery.ui.datepicker-ru.js'></script>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='revision_act'>
		Агент-партнёр:<br>
		<input type='hidden' name='agent_id' id='agent_id' value=''>
		<input type='text' id='ag' name='agent_name' style='width: 400px;' value=''><br>
		<p class='datetime'>
		Дата от:<br><input type='text' id='datepicker_f' name='date_st' value='1970-01-01' maxlength='10'><br>
		Дата до:<br><input type='text' id='datepicker_t' name='date_end' value='$date_end' maxlength='10'></p><br>
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
		<button type='submit'>Сформировать отчет</button></form>

		<script type='text/javascript'>

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
			$( '#datepicker_t' ).datepicker( 'setDate' , '$date_end' );
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

	function Make($opt='html')
	{
		global $tmpl,$CONFIG;
		if($opt=='html')
		{
			$tmpl->LoadTemplate('print');
		}
		else if($opt=='pdf')
		{
			global $CONFIG;
			$tmpl->ajax=1;
			$tmpl->SetText('');
			ob_start();
			define('FPDF_FONT_PATH',$CONFIG['site']['location'].'/fpdf/font/');
			require('fpdf/fpdf.php');
			$pdf=new FPDF('P');
			$pdf->Open();
			$pdf->SetAutoPageBreak(1,12);
			$pdf->AddFont('Arial','','arial.php');
			$pdf->tMargin=10;
			$pdf->AddPage('P');
		}

		$firm_id=rcv('firm_id');
		$subtype=rcv('subtype');
		$date_st=strtotime(rcv('date_st'));
		$date_end=strtotime(rcv('date_end'))+60*60*24-1;
		$agent_id=rcv('agent_id');

		settype($firm_id,'int');
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

		$sql_add='';
		if($firm_id>0) $sql_add.=" AND `doc_list`.`firm_id`='$firm_id'";
		if($subtype!='') $sql_add.=" AND `doc_list`.`subtype`='$subtype'";

		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`date`, `doc_list`.`sum`, `doc_list`.`altnum`, `doc_types`.`name`
		FROM `doc_list`
		LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		WHERE `doc_list`.`agent`='$agent' AND `doc_list`.`ok`!='0' AND `doc_list`.`date`<='$date_end' ".$sql_add." ORDER BY `doc_list`.`date`" );
		if(mysql_errno())		throw new MysqlException("Не удалось получить список документов");
		if($opt=='html')
		{
			$tmpl->SetText("<h1>".$this->getName()."</h1>
			<center>от ".$firm_vars['firm_name']."<br>за период c ".date("d.m.Y",$date_st)." по ".date("d.m.Y",$date_end)."
			$fn</center>
			Мы, нижеподписавшиеся, директор ".$firm_vars['firm_name']." ".$firm_vars['firm_director']."
			c одной стороны, и директор $fn $dir_fio с другой стороны,
			составили настоящий акт сверки в том, что состояние взаимных расчетов по
			данным учёта следующее:<br><br>
			<table width=100%>
			<tr>
			<td colspan=4 width='50%'>по данным ".$firm_vars['firm_name']."
			<td colspan=4 width='50%'>по данным $fn
			<tr>
			<th>Дата<th>Операция<th>Дебет<th>Кредит
			<th>Дата<th>Операция<th>Дебет<th>Кредит");
		}
		else if($opt=='pdf')
		{
			$firm_vars['firm_name']=unhtmlentities($firm_vars['firm_name']);
			$agent['fullname']=unhtmlentities($agent['fullname']);
			$fn=unhtmlentities($fn);
			$dir_fio=unhtmlentities($dir_fio);
			$pdf->SetFont('Arial','',16);
			$str = iconv('UTF-8', 'windows-1251', $this->getName());
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
		}
		$pr=$ras=0;
		$f_print=false;
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

				if($opt=='html')
				{
					$tmpl->AddText("<tr><td colspan=2>Сальдо на начало периода<td>$ras<td>$pr<td><td><td><td>");
				}
				else if($opt=='pdf')
				{
					$str=iconv('UTF-8', 'windows-1251', "Сальдо на начало периода");
					$pdf->Cell($t_width[0]+$t_width[1],4,$str,1,0,'L',0);
					$pdf->Cell($t_width[2],4,$ras,1,0,'R',0);
					$pdf->Cell($t_width[3],4,$pr,1,0,'R',0);
					$pdf->Cell($t_width[4]+$t_width[5],4,'',1,0,'L',0);
					$pdf->Cell($t_width[6],4,'',1,0,'L',0);
					$pdf->Cell($t_width[7],4,'',1,0,'L',0);
					$pdf->Ln();
				}
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

				if($opt=='html')	$tmpl->AddText("<tr><td>$dt<td>$nxt[5] N$nxt[4]<td>$deb<td>$kr<td><td><td><td>");
				else if($opt=='pdf')
				{
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
		}

		$razn=$pr-$ras;
		$razn_p=abs($razn);
		$razn_p=sprintf("%01.2f", $razn_p);

		$pr=sprintf("%01.2f", $pr);
		$ras=sprintf("%01.2f", $ras);

		if($opt=='html')
		{
			$tmpl->AddText("<tr><td colspan=2>Обороты за период<td>$ras<td>$pr<td><td><td><td>");
		}
		else if($opt=='pdf')
		{
			$str=iconv('UTF-8', 'windows-1251', "Обороты за период");
			$pdf->Cell($t_width[0]+$t_width[1],4,$str,1,0,'L',0);
			$pdf->Cell($t_width[2],4,$ras,1,0,'R',0);
			$pdf->Cell($t_width[3],4,$pr,1,0,'R',0);
			$pdf->Cell($t_width[4]+$t_width[5],4,'',1,0,'L',0);
			$pdf->Cell($t_width[6],4,'',1,0,'L',0);
			$pdf->Cell($t_width[7],4,'',1,0,'L',0);
			$pdf->Ln();
		}

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

		if($opt=='html')
		{
			$tmpl->AddText("<tr><td colspan=2>Сальдо на конец периода<td>$ras<td>$pr<td colspan=4>
			<tr><td colspan=4>По данным {$firm_vars['firm_name']} на ".date("d.m.Y",$date_end)."<td colspan=4>
			<tr><td colspan=4>");
			if($razn>0)		$tmpl->AddText("переплата в пользу ".$firm_vars['firm_name']." $razn_p руб.");
			else	if($razn<0) 	$tmpl->AddText("задолженность в пользу ".$firm_vars['firm_name']." $razn_p руб.");
			else			$tmpl->AddText("переплат и задолженностей нет!");
			$tmpl->AddText("<td colspan=4>
			<tr><td colspan=4>От ".$firm_vars['firm_name']."<br>
			директор<br>____________________________ (".$firm_vars['firm_director'].")<br><br>м.п.<br>
			<td colspan=4>От $fn<br>
			директор<br> ____________________________ ($dir_fio)<br><br>м.п.<br>
			</table>");

		}
		else if($opt=='pdf')
		{
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

		if($CONFIG['site']['doc_shtamp'])
		{
			$delta=-15;
			$shtamp_img=str_replace('{FN}', $firm_id, $CONFIG['site']['doc_shtamp']);
			if(file_exists($shtamp_img))
			$pdf->Image($shtamp_img, 3,$pdf->GetY()+$delta, 120);
		}

			$pdf->Ln();
			$pdf->Output('akt_sverki.pdf','I');
		}


	}

	function Run($opt)
	{
		if($opt=='')		$this->Form();
		else if(($opt=='html')||($opt=='pdf'))	$this->Make($opt);
	}
};

?>

