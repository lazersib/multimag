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


$doc_types[7]="Расходный кассовый ордер";

class doc_Rko extends doc_Nulltype
{
	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				=7;
		$this->doc_name				='rko';
		$this->doc_viewname			='Расходный кассовый ордер';
		$this->sklad_editor_enable		=false;
		$this->ksaas_modify			=-1;
		$this->header_fields			='kassa sum separator agent';
		settype($this->doc,'int');
	}

	function DopHead()
	{
		global $tmpl;
		$tmpl->AddText("Вид расхода:<br><select name=v_rasx>");
		$res=mysql_query("SELECT * FROM `doc_rasxodi` WHERE `id`>'0'");
		while($nxt=mysql_fetch_row($res))
			if($nxt[0]==@$this->dop_data['rasxodi'])
				$tmpl->AddText("<option value='$nxt[0]' selected>$nxt[1]</option>");
			else
				$tmpl->AddText("<option value='$nxt[0]'>$nxt[1]</option>");

		$tmpl->AddText("</select>");
	}

	function DopSave()
	{
		$v_rasx=rcv('v_rasx');

		mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)
			VALUES ('{$this->doc}','rasxodi','$v_rasx')");
	}

	function DopBody()
	{
		global $tmpl;
		$res=mysql_query("SELECT `doc_rasxodi`.`name` FROM `doc_rasxodi`
		WHERE `doc_rasxodi`.`id`='{$this->dop_data['rasxodi']}'");

        	$nxt=mysql_fetch_row($res);
		$tmpl->AddText("<b>Статья расходов:</b> $nxt[0]");
	}

	// Провести
	function DocApply($silent=0)
	{
		$tim=time();
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`kassa`, `doc_list`.`ok`, `doc_list`.`sum`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)	throw new MysqlException('Ошибка выборки данных документа при проведении!');
		$nx=@mysql_fetch_row($res);
		if(!$nx)	throw new Exception('Документ не найден!');
		if( $nx[3] && (!$silent) )	throw new Exception('Документ уже был проведён!');

		$res=mysql_query("SELECT `ballance` FROM `doc_kassa` WHERE `ids`='kassa' AND `num`='$nx[2]'");
		if(!$res)	throw new MysqlException('Ошибка запроса суммы кассы!');
		$nxt=mysql_fetch_row($res);
		if(!$nxt)	throw new Exception('Ошибка получения суммы кассы!');
		if($nxt[0]<$nx[4])	throw new Exception("Не хватает денег в кассе N$nx[2] ($nxt[0]<$nx[4])!");

		$res=mysql_query("UPDATE `doc_kassa` SET `ballance`=`ballance`-'$nx[4]'
		WHERE `ids`='kassa' AND `num`='$nx[2]'");
		if(!$res)			throw new MysqlException("Ошибка обновления суммы $nx[4] в кассе $nx[2]!");
		if(! mysql_affected_rows())	throw new MysqlException("Cумма в кассе $nx[2] не изменилась!");
		if($silent)	return;
		$res=mysql_query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->doc}'");
		if(!$res)	throw new MysqlException('Ошибка установки даты проведения документа!');
	}

	function DocCancel()
	{
		$tim=time();
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`kassa`, `doc_list`.`ok`, `doc_list`.`sum`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка выборки данных документа при проведении!');
		if(!($nx=@mysql_fetch_row($res)))	throw new Exception('Документ не найден!');
		if(!$nx[3])				throw new Exception('Документ не проведён!');
		$res=mysql_query("UPDATE `doc_kassa` SET `ballance`=`ballance`+'$nx[4]' WHERE `ids`='kassa' AND `num`='$nx[2]'");
		if(! mysql_affected_rows())		throw new MysqlException("Cумма в кассе $nx[2] не изменилась!");
		$res=mysql_query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка установки флага!');
	}

	// Отменить проведение
	function Cancel($doc)
	{
		global $tmpl;
		global $uid;

		$tmpl->ajax=1;

 		mysql_query("START TRANSACTION");
 		mysql_query("LOCK TABLE `doc_list`, `doc_kassa` READ ");
		$err='';
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`kassa`, `doc_list`.`ok`, `doc_list`.`sum`
		FROM `doc_list` WHERE `doc_list`.`id`='$doc'");
		if($nx=@mysql_fetch_row($res))
		{
			if(($nx[3])||$silent)
			{
				$tim=time();
				$res=mysql_query("UPDATE `doc_kassa` SET `ballance`=`ballance`+'$nx[4]'
				WHERE `ids`='kassa' AND `num`='$nx[2]'");
				if(mysql_affected_rows())
				{
					$res=mysql_query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='$doc'");
					if(!$res)
						 $err="Ошибка обновления 2!";
				}
				else $err="Ошибка обновления 1!";
			}
			else $err="Документ НЕ проведён!";
		}
		if(!$err)
		{
			mysql_query("COMMIT");
			if(!$silent)
			{
				doc_log("Cancel rko","$doc");
				$tmpl->AddText("<h3>Докумен успешно отменён!</h3>");
			}
		}
		else
		{
			mysql_query("ROLLBACK");
			if(!$silent)
			{
				doc_log("ERROR: Cancel rko - $err","$doc");
				$tmpl->AddText("<h3>$err</h3>");
			}
		}
		mysql_query("UNLOCK TABLE `doc_list`, `doc_kassa`");
		return $err;
	}
	// Печать документа
	function Printform($doc, $opt='')
	{
		global $tmpl;

		if(!$this->doc_data['ok'])
		{
			doc_menu(0,0);
			$tmpl->AddText("<h1>Расходный кассовый ордер</h1>");
			$tmpl->msg("Сначала нужно провести документ!","err");
		}
		else if($opt=='')
		{
			global $tmpl;
			$tmpl->ajax=1;
			$tmpl->AddText("
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=rko_pdf'\">Расходный ордер (PDF)</div>");
		}
		else
		if($opt=='rko_pdf')
			$this->PrintRKOPDF();

	}

	function PrintRKOPDF($to_str=false)
	{
		define('FPDF_FONT_PATH','/var/www/gate/fpdf/font/');
		require('fpdf/fpdf.php');
		global $tmpl, $CONFIG, $uid;
		if(!$to_str) $tmpl->ajax=1;

		$pdf=new FPDF('P');
		$pdf->Open();
		$pdf->SetAutoPageBreak(0,10);
		$pdf->AddFont('Arial','','arial.php');
		$pdf->tMargin=10;
		$pdf->AddPage();
		$pdf->SetFont('Arial','',10);
		$pdf->SetFillColor(255);

		$pdf->Rect(136, 10, 3, 130 );

		$pdf->lMargin=5;
		$pdf->rMargin=75;

		$pdf->SetFont('','',6);
		$str = "Унифицированная форма № КО-2\nУтверждена постановлением Госкомстата\nРоссии от 18.08.1998г. №88";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->MultiCell(0,3,$str,0,'R',0);

		$pdf->SetX(120);
		$str = iconv('UTF-8', 'windows-1251', "Код");
		$pdf->Cell(0,4,$str,1,1,'C',0);
		$y=$pdf->GetY();
		$pdf->SetLineWidth(0.5);
		$pdf->SetX(120);
		$pdf->Cell(0,16,'',1,1,'C',0);
		$pdf->SetLineWidth(0.2);
		$pdf->SetY($y);

		$str = iconv('UTF-8', 'windows-1251', "Форма по ОКУД");
		$pdf->Cell(115,4,$str,0,0,'R',0);
		$pdf->Cell(0,4,'0310001',1,1,'C',0);

		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($this->firm_vars['firm_name']));
		$pdf->Cell(95,4,$str,0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', "по ОКПО");
		$pdf->Cell(20,4,$str,0,0,'R',0);
		$pdf->Cell(0,4,$this->firm_vars['firm_okpo'],1,1,'C',0);

		$pdf->SetFont('','',5);
		$pdf->Line(5, $pdf->GetY(), 100, $pdf->GetY());
		$str = iconv('UTF-8', 'windows-1251', "организация");
		$pdf->Cell(115,2,$str,0,0,'C',0);
		$pdf->Cell(0,4,'',1,1,'C',0);

		$pdf->Cell(115,4,'',0,1,'C',0);
		$pdf->Line(5, $pdf->GetY(), 100, $pdf->GetY());
		$str = iconv('UTF-8', 'windows-1251', "структурное подразделение");
		$pdf->Cell(115,2,$str,0,1,'C',0);


		$pdf->Cell(85,4,'',0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', "Номер документа");
		$pdf->Cell(18,3,$str,1,0,'C',0);
		$str = iconv('UTF-8', 'windows-1251', "Дата составления");
		$pdf->Cell(0,3,$str,1,1,'C',0);

		$pdf->SetLineWidth(0.5);
		$pdf->SetFont('','',14);
		$str = iconv('UTF-8', 'windows-1251', "Расходный кассовый ордер");
		$pdf->Cell(85,4,$str,0,0,'C',0);
		$pdf->SetFont('','',7);
		$pdf->Cell(18,4,$this->doc_data['altnum'],1,0,'C',0);
		$date=date("d.m.Y",$this->doc_data['date']);
		$pdf->Cell(0,4,$date,1,1,'C',0);
		$pdf->SetLineWidth(0.2);
		$pdf->Ln();


		$y=$pdf->GetY();

		$t_all_offset=array();
		$pdf->SetFont('','',10);
		$t_width=array(88,16,18,8);
		$t_ydelta=array(1,4,2,0);
		$t_text=array(
		'Дебет',
		'Сумма руб, коп',
		'Код целевого назначения',
		'');

		foreach($t_width as $w)
		{
			$pdf->Cell($w,16,'',1,0,'C',0);
		}
		$pdf->Ln();
		$pdf->Ln(0.5);
		$pdf->SetFont('','',8);
		$offset=0;
		foreach($t_width as $i => $w)
		{
			$t_all_offset[$offset]=$offset;
			$pdf->SetY($y+$t_ydelta[$i]+0.2);
			$pdf->SetX($offset+$pdf->lMargin);
			$str = iconv('UTF-8', 'windows-1251', $t_text[$i] );
			$pdf->MultiCell($w,3,$str,0,'C',0);
			$offset+=$w;
		}

		$t2_width=array(8, 24, 20, 20, 16);
		$t2_start=array(0,0,0,0,0);
		$t2_ydelta=array(2,1,1,1,3);
		$t2_text=array(
		'',
		'код структурного подразделения',
		'корреспондирующий счёт, субсчёт',
		'код аналитичес- кого учёта',
		'кредит');
		$offset=0;
		$c_id=0;
		$old_col=0;
		$y+=5;

		foreach($t2_width as $i => $w2)
		{
			while($c_id<$t2_start[$i])
			{
				$t_a[$offset]=$offset;
				$offset+=$t_width[$c_id++];
			}

			if($old_col==$t2_start[$i] && $i>0)	$off2+=$t2_width[$i-1];
			else					$off2=0;
			$old_col=$t2_start[$i];
			$t_all_offset[$offset+$off2]=$offset+$off2;
			$pdf->SetY($y);
			$pdf->SetX($offset+$off2+$pdf->lMargin);
			$pdf->Cell($w2,11,'',1,0,'C',0);

			$pdf->SetY($y+$t2_ydelta[$i]);
			$pdf->SetX($offset+$off2+$pdf->lMargin);
			$str = iconv('UTF-8', 'windows-1251', $t2_text[$i] );
			$pdf->MultiCell($w2,3,$str,0,'C',0);
		}

		sort ( $t_all_offset, SORT_NUMERIC );
		$pdf->SetY($y+11);
		$t_all_width=array();
		$old_offset=0;
		foreach($t_all_offset as $offset)
		{
			if($offset==0)	continue;
			$t_all_width[]=	$offset-$old_offset;
			$old_offset=$offset;
		}
		$t_all_width[]=0;
		$i=1;
		$pdf->SetLineWidth(0.4);
		foreach($t_all_width as $id => $w)
		{
			if($id==5)	$str=$this->doc_data['sum'];
			else		$str='';
			$pdf->Cell($w,4,$str,1,0,'C',0);
			$i++;
		}
		$pdf->SetLineWidth(0.2);
		$pdf->Ln(6);
		$pdf->SetFont('','',7);
		$res=mysql_query("SELECT `doc_agent`.`fullname`	FROM `doc_agent` WHERE `doc_agent`.`id`='{$this->doc_data[2]}'	");
		if(mysql_errno())		throw new MysqlException("Невозможно получить данные агента!");
		$agent_info=mysql_fetch_array($res);
		if(!$agent_info)		throw new Exception('Агент не найден');

		$str = iconv('UTF-8', 'windows-1251', "Выдать");
		$pdf->Cell(20,4,$str,0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($agent_info['fullname']));
		$pdf->Cell(0,4,$str,'B',1,'L',0);

		if($this->doc_data['p_doc'])
		{
			$res=mysql_query("SELECT `doc_types`.`name`, `doc_list`.`altnum`, `doc_list`.`date`  FROM `doc_list`
			INNER JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
			WHERE `doc_list`.`id`='{$this->doc_data['p_doc']}'");
			$data=mysql_fetch_array($res);
			$ddate=date("d.m.Y",$data['date']);
			$str_osn="Оплата за {$data['name']} №{$data['altnum']} от $ddate";
			$str_osn = iconv('UTF-8', 'windows-1251', $str_osn);
		}
		else $str_osn='';
		$str = iconv('UTF-8', 'windows-1251', "Основание:");
		$pdf->Cell(20,4,$str,'B',0,'L',0);
		$pdf->Cell(0,4,$str_osn,'B',1,'L',0);

		$str = iconv('UTF-8', 'windows-1251', "Сумма");
		$pdf->Cell(15,4,$str,'B',0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', num2str($this->doc_data['sum']));
		$pdf->Cell(0,4,$str,'B',1,'L',0);

		$sum_r=round($this->doc_data['sum']);
		$sum_c=round(($this->doc_data['sum']-$sum_r)*100);
		$str = iconv('UTF-8', 'windows-1251', "Сумма");
		$pdf->Cell(90,4,'','B',0,'L',0);
		$pdf->Cell(20,4,$sum_r,'B',0,'R',0);
		$str = iconv('UTF-8', 'windows-1251', "руб.");
		$pdf->Cell(10,4,$str,0,0,'C',0);
		$pdf->Cell(5,4,$sum_c,'B',0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', "коп.");
		$pdf->Cell(0,4,$str,0,1,'L',0);

		$str = iconv('UTF-8', 'windows-1251', "Приложение");
		$pdf->Cell(20,4,$str,0,0,'L',0);
		$pdf->Cell(0,4,'','B',1,'L',0);

		$pdf->Ln(3);
		$str = iconv('UTF-8', 'windows-1251', "Руководитель организации");
		$pdf->Cell(40,4,$str,0,0,'L',0);
		$pdf->Cell(40,4,'','B',0,'L',0);
		$pdf->Cell(5,4,'',0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', $this->firm_vars['firm_director'] );
		$pdf->Cell(0,4,$str,'B',1,'L',0);

		$pdf->SetFont('','',5);
		$pdf->Cell(40,2,'',0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', "(подпись)");
		$pdf->Cell(40,2,$str,0,0,'C',0);
		$pdf->Cell(5,2,'',0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', "(расшифровка подписи)");
		$pdf->Cell(0,2,$str,0,1,'C',0);
		$pdf->SetFont('','',7);

		$str = iconv('UTF-8', 'windows-1251', "Главный (старший) бухгалтер");
		$pdf->Cell(40,4,$str,0,0,'L',0);
		$pdf->Cell(40,4,'','B',0,'L',0);
		$pdf->Cell(5,4,'',0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', $this->firm_vars['firm_buhgalter'] );
		$pdf->Cell(0,4,$str,'B',1,'L',0);

		$pdf->SetFont('','',5);
		$pdf->Cell(40,2,'',0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', "(подпись)");
		$pdf->Cell(40,2,$str,0,0,'C',0);
		$pdf->Cell(5,2,'',0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', "(расшифровка подписи)");
		$pdf->Cell(0,2,$str,0,1,'C',0);
		$pdf->SetFont('','',7);

		$str = iconv('UTF-8', 'windows-1251', "Получил");
		$pdf->Cell(20,4,$str,0,0,'L',0);
		$pdf->Cell(0,4,'','B',1,'L',0);
		$pdf->SetFont('','',5);
		$str = iconv('UTF-8', 'windows-1251', "(сумма прописью)");
		$pdf->Cell(0,2,$str,0,1,'C',0);
		$pdf->SetFont('','',7);

		$str = iconv('UTF-8', 'windows-1251', "Сумма");
		$pdf->Cell(90,4,'','B',0,'L',0);
		$pdf->Cell(20,4,'','B',0,'R',0);
		$str = iconv('UTF-8', 'windows-1251', "руб.");
		$pdf->Cell(10,4,$str,0,0,'C',0);
		$pdf->Cell(5,4,'','B',0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', "коп.");
		$pdf->Cell(0,4,$str,0,1,'L',0);

		$pdf->Ln(1);

		$pdf->Cell(7,4,'"',0,0,'R',0);
		$pdf->Cell(5,4,'','B',0,'R',0);
		$pdf->Cell(3,4,'"',0,0,'L',0);
		$pdf->Cell(30,4,'','B',0,'R',0);
		$pdf->Cell(20,4,'',0,0,'R',0);

		$str = iconv('UTF-8', 'windows-1251', "Подпись");
		$pdf->Cell(10,4,$str,0,0,'R',0);
		$pdf->Cell(25,4,'','B',0,'L',0);
		$pdf->Cell(0,4,'',0,1,'L',0);

		$str = iconv('UTF-8', 'windows-1251', "По");
		$pdf->Cell(5,4,$str,0,0,'L',0);
		$pdf->Cell(0,4,'','B',1,'L',0);
		$pdf->SetFont('','',5);
		$str = iconv('UTF-8', 'windows-1251', "(наименование, номер, дата и место выдачи документа, удостоверяющего личность получателя)");
		$pdf->Cell(0,2,$str,0,1,'C',0);
		$pdf->SetFont('','',7);

		$pdf->Ln(2);

		$res=mysql_query("SELECT `rname` FROM `users` WHERE `id`='{$this->doc_data[8]}'");
		$name=@mysql_result($res,0,0);
		if(!$name) $name=$this->firm_vars['firm_buhgalter'];

		$str = iconv('UTF-8', 'windows-1251', "Выдал кассир");
		$pdf->Cell(20,4,$str,0,0,'L',0);
		$pdf->Cell(40,4,'','B',0,'L',0);
		$pdf->Cell(5,4,'',0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', $name );
		$pdf->Cell(0,4,$str,'B',1,'L',0);

		$pdf->SetFont('','',5);
		$pdf->Cell(20,2,'',0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', "(подпись)");
		$pdf->Cell(40,2,$str,0,0,'C',0);
		$pdf->Cell(5,2,'',0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', "(расшифровка подписи)");
		$pdf->Cell(0,2,$str,0,1,'C',0);
		$pdf->SetFont('','',7);

		$pdf->lMargin=140;
		$pdf->rMargin=5;
		$pdf->SetY(5);
		$pdf->Ln();



		if($to_str)
			return $pdf->Output('pko.pdf','S');
		else
			$pdf->Output('pko.pdf','I');
	}
	// Формирование другого документа на основании текущего
	function MorphTo($doc, $target_type)
	{
		global $tmpl;
		$tmpl->ajax=1;
		$tmpl->AddText("<div class='disabled'>Не поддерживается для</div><div class='disabled'>данного типа документа</div>");
	}

	// Служебные опции
	function Service($doc)
	{
		global $tmpl;
        $tmpl->msg("В процессе разработки!",err);
	}

};


?>