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


class Report_DocReestr
{
	function getName($short=0)
	{
		if($short)	return "Реестр документов";
		else		return "Реестр документов";
	}
	

	function Form()
	{
		global $tmpl;
		$d_t=date("Y-m-d");
		$d_f=date("Y-m-d",time()-60*60*24*31);
		$tmpl->AddText("<h1>".$this->getName()."</h1>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='docreestr'>
		<input type='hidden' name='opt' value='make'>
		<p class='datetime'>
		<fieldset><legend>Дата</legend>
		С:<input type=text id='id_pub_date_date' class='vDateField required' name='dt_f' value='$d_f'><br>
		По:<input type=text id='id_pub_date_date' class='vDateField required' name='dt_t' value='$d_t'>
		</fieldset>
		</p><br>Вид документов:<br>
		<select name='doc_type'>
		<option value='0'>-- без отбора --</option>");
		$res=mysql_query("SELECT * FROM `doc_types` ORDER BY `name`");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<option value='$nxt[0]'>$nxt[1]</option>");	
		}
		$tmpl->AddText("
		</select><br>
		Организация:<br>
		<select name='firm_id'>
		<option value='0'>-- без отбора --</option>");
		$res=mysql_query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `firm_name`");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<option value='$nxt[0]'>$nxt[1]</option>");	
		}
		$tmpl->AddText("
		</select><br>
		Подтип документов:<br>
		<input type='text' name='subtype'><br>
		<button type='submit'>Сформировать отчёт</button>
		</form>");
	}
	
	function MakePDF()
	{
		global $tmpl, $CONFIG;
		$tmpl->ajax=1;
		$tmpl->SetText('');
		ob_start();
		$dt_f=strtotime(rcv('dt_f'));
		$dt_t=strtotime(rcv('dt_t')." 23:59:59");
		$firm_id=rcv('firm_id');
		$doc_type=rcv('doc_type');
		$subtype=rcv('subtype');
		
		settype($from_date,'int');
		settype($to_date,'int');
		settype($doc_type,'int');
		settype($firm_id,'int');
		
		define('FPDF_FONT_PATH',$CONFIG['site']['location'].'/fpdf/font/');
		require('fpdf/fpdf_mysql.php');

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
		
		
		$pdf->Output('doc_reestr.pdf','I');
	}
	
	function Run($opt)
	{
		if($opt=='')	$this->Form();
		else		$this->MakePDF();	
	}
};

?>

