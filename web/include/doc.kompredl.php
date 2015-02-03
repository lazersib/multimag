<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2014, BlackLight, TND Team, http://tndproject.org
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


/// Документ *коммерческое предложение*
class doc_Kompredl extends doc_Nulltype
{
	// Создание нового документа или редактирование заголовка старого
	function __construct($doc = 0) {
		parent::__construct($doc);
		$this->doc_type = 13;
		$this->doc_name = 'kompredl';
		$this->doc_viewname = 'Коммерческое предложение';
		$this->sklad_editor_enable = true;
		$this->header_fields = 'bank sklad separator agent cena';
		$this->PDFForms = array(
		    array('name' => 'kp', 'desc' => 'Коммерческое предложение', 'method' => 'KomPredlPDF'),
		    array('name' => 'kpad', 'desc' => 'Коммерческое предложение c описанием товара', 'method' => 'KomPredlDescPDF'),
		    array('name' => 'kpc', 'desc' => 'Коммерческое предложение с количеством', 'method' => 'KomPredlPDF_Cnt'),
                    array('name' => 'kpcnn', 'desc' => 'Коммерческое предложение с количеством без НДС', 'method' => 'KomPredlPDF_Cntnds'),
		    array('name' => 'csv', 'desc' => 'Экспорт в csv', 'method' => 'CSVExport')		    
		);
	}
	
	function initDefDopdata() {
		$this->def_dop_data = array('shapka'=>'', 'cena'=>0);
	}

	function DopHead() {
		global $tmpl;
		$tmpl->addContent("Текст шапки:<br><textarea name='shapka'>{$this->dop_data['shapka']}</textarea><br>");
	}

	function DopSave() {
		$new_data = array(
		    'shapka' => request('shapka')
		);
		$old_data = array_intersect_key($new_data, $this->dop_data);

		$log_data = '';
		if ($this->doc)
			$log_data = getCompareStr($old_data, $new_data);
		$this->setDopDataA($new_data);
		if ($log_data)
			doc_log("UPDATE {$this->doc_name}", $log_data, 'doc', $this->doc);
	}

	function DopBody() {
		global $tmpl;
		if ($this->dop_data['shapka'])
			$tmpl->addContent("<b>Текст шапки:</b> {$this->dop_data['shapka']}");
		else
			$tmpl->addContent("<br><b style='color: #f00'>ВНИМАНИЕ! Текст шапки не указан!</b><br>");
		$tmpl->addContent("Срок поставки можно указать в комментариях наименования<br>");
	}

	// Формирование другого документа на основании текущего
	function MorphTo($target_type)
	{
		global $tmpl, $db;
		if($target_type=='') {
			$tmpl->ajax=1;
			$tmpl->addContent("<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=3'\">Заявка покупателя</div>");
		}
		else if($target_type==3) {
			$db->startTransaction();
			if (!isAccess('doc_zayavka', 'create'))
				throw new AccessException();
			$new_doc = new doc_Zayavka();
			$dd = $new_doc->createFromP($this);
			$new_doc->setDopData('cena', $this->dop_data['cena']);
			$db->commit();
			header("Location: doc.php?mode=body&doc=$dd");
		}
	}

	function KomPredlPDF($to_str=0)	{
		global $tmpl, $CONFIG, $db;

		$dt=date("d.m.Y",$this->doc_data['date']);
		if(!$to_str) $tmpl->ajax=1;

		require('fpdf/fpdf_mysql.php');
		$pdf=new FPDF('P');
		$pdf->Open();
		$pdf->SetAutoPageBreak(1,12);
		$pdf->AddFont('Arial','','arial.php');
		$pdf->tMargin=5;
		$pdf->AddPage();
		$pdf->SetFont('Arial','',10);
		$pdf->SetFillColor(255);

		if(@$CONFIG['site']['doc_header']) {
			$header_img=str_replace('{FN}', $this->doc_data['firm_id'], $CONFIG['site']['doc_header']);
			$pdf->Image($header_img,8,10, 190);
			$pdf->Sety(54);
		}

		$res = $db->query("SELECT `name`, `bik`, `rs`, `ks` FROM `doc_kassa` WHERE `ids`='bank' AND `num`='{$this->doc_data['bank']}'");
		if(!$res->num_rows)	throw new Exception("Информация о банке не найдена");
		$bank_data = $res->fetch_row();

		$str='Банковские реквизиты:';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->SetFont('','',11);
		$pdf->Cell(0,5,$str,0,1,'C',0);

		$old_x=$pdf->GetX();
		$old_y=$pdf->GetY();
		$old_margin=$pdf->lMargin;
		$table_c=110;
		$table_c2=15;

		$pdf->SetFont('','',12);
		$str=$bank_data[0];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c,10,$str,1,1,'L',0);
		$str='ИНН '.$this->firm_vars['firm_inn'].' КПП';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c,5,$str,1,1,'L',0);
		$str=$this->firm_vars['firm_name'];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$tx=$pdf->GetX();
		$ty=$pdf->GetY();
		$pdf->Cell($table_c,10,'',1,1,'L',0);

		$pdf->lMargin=$old_x+1;
		$pdf->SetX($tx+1);
		$pdf->SetY($ty+1);
		$pdf->SetFont('','',9);
		$pdf->MultiCell($table_c,3,$str,0,1,'L',0);

		$pdf->SetFont('','',12);
		$pdf->lMargin=$old_x+$table_c;
		$pdf->SetY($old_y);
		$str='БИК';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c2,5,$str,1,1,'L',0);
		$str='корр/с';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c2,10,$str,1,1,'L',0);
		$str='р/с N';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c2,10,$str,1,1,'L',0);

		$pdf->lMargin=$old_x+$table_c+$table_c2;
		$pdf->SetY($old_y);
		$str=$bank_data[1];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,1,1,'L',0);
		$str=$bank_data[3];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,1,1,'L',0);
		$str=$bank_data[2];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,15,$str,1,1,'L',0);

		$pdf->lMargin=$old_margin;
		$pdf->SetY($old_y+30);

		$pdf->SetFont('','',20);
		$str='Коммерческое предложение № '.$this->doc_data['altnum'].' от '.$dt;
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,0,1,'C',0);
		$pdf->Ln(10);
		$pdf->SetFont('','',10);
		$str='Поставщик: '.$this->firm_vars['firm_name'].', '.$this->firm_vars['firm_telefon'];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->MultiCell(0,5,$str,0,1,'L',0);
		$pdf->Ln(10);

		$str=$this->dop_data['shapka'];
		if($str)
		{
			$pdf->SetFont('','',16);
			$str = iconv('UTF-8', 'windows-1251', $str);
			$pdf->MultiCell(0,7,$str,0,'C',0);
		}

		$t_width=array(8,125,30,0);
		$pdf->SetFont('','',12);
		$str='№';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[0],5,$str,1,0,'C',0);
		$str='Наименование';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[1],5,$str,1,0,'C',0);
		$str='Срок поставки';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[2],5,$str,1,0,'C',0);
		$str='Цена за ед.';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[3],5,$str,1,0,'C',0);
		$pdf->Ln();

		$pdf->SetFont('','',10);

		$res = $db->query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`,
                    `doc_base`.`mass`, `doc_list_pos`.`comm`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		$i=0;
		while($nxt = $res->fetch_row())	{
			$i++;
			$cost = sprintf("%01.2f р.", $nxt[4]);
			$pdf->Cell($t_width[0], 5, $i, 1, 0, 'R', 0);
			$str = $nxt[0] . ' ' . $nxt[1];
			if($nxt[2]) $str.='('.$nxt[2].')';
			$str = iconv('UTF-8', 'windows-1251', $str);
			$pdf->Cell($t_width[1], 5, $str, 1, 0, 'L', 0);
			$str = iconv('UTF-8', 'windows-1251', $nxt[6]);
			$pdf->Cell($t_width[2], 5, $str, 1, 0, 'R', 0);
			$str = iconv('UTF-8', 'windows-1251', $cost);
			$pdf->Cell($t_width[3], 5, $str, 1, 0, 'R', 0);
			$pdf->Ln();
		}

		if ($pdf->h <= ($pdf->GetY() + 40))
			$pdf->AddPage();

		$pdf->SetFont('','',12);
		$str="Цены указаны с учётом НДС, за 1 ед. товара";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,8,$str,0,1,'C',0);
		$pdf->ln(6);

		if($this->doc_data['comment'])	{
			$pdf->SetFont('','',10);
			$str = iconv('UTF-8', 'windows-1251', $this->doc_data['comment']);
			$pdf->MultiCell(0,5,$str,0,1,'R',0);
			$pdf->ln(6);
		}

		$pdf->SetFont('','',12);
		$str="Система интернет-заказов для постоянных клиентов доступна на нашем сайте";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,0,1,'C',0);

		$pdf->SetTextColor(0,0,192);
		$pdf->SetFont('','UI',20);

		$pdf->Cell(0,7,'http://'.$CONFIG['site']['name'],0,1,'C',0,'http://'.$CONFIG['site']['name']);

		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('','',12);
		$str="При оформлении заказа через сайт предоставляется скидка!";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,8,$str,0,1,'C',0);

		$res = $db->query("SELECT `worker_real_name`, `worker_phone`, `worker_email` FROM `users_worker_info` WHERE `user_id`='{$this->doc_data['user']}'");
		if($res->num_rows) {
			$worker_info = $res->fetch_assoc();
			$pdf->SetAutoPageBreak(0,10);
			$pdf->SetY($pdf->h-18);
			$pdf->Ln(1);
			$pdf->SetFont('','',10);
			$str="Отв. оператор ".$worker_info['worker_real_name'];
			$pdf->CellIconv(0,4,$str,0,1,'R',0);
			$str="Контактный телефон: ".$worker_info['worker_phone'];
			$pdf->CellIconv(0,4,$str,0,1,'R',0);
			$str="Электронная почта: ".$worker_info['worker_email'];
			$pdf->CellIconv(0,4,$str,0,1,'R',0);
		}
		else {
			$pdf->SetAutoPageBreak(0,10);
			$pdf->SetY($pdf->h-12);
			$pdf->Ln(1);
			$pdf->SetFont('','',10);
			$str="Login автора: ".$_SESSION['name'];
			$pdf->CellIconv(0,4,$str,0,1,'R',0);
		}
		
		if($to_str)
			return $pdf->Output('buisness_offer.pdf','S');
		else
			$pdf->Output('buisness_offer.pdf','I');
	}
	/// Коммерческое предложение с описанием товара в PDF формате
	function KomPredlDescPDF($to_str=0) {
		global $tmpl, $CONFIG, $db;

		$dt = date("d.m.Y",$this->doc_data['date']);
		if(!$to_str) $tmpl->ajax=1;
		
		require('fpdf/fpdf_mc.php');
		$pdf=new PDF_MC_Table('P');
		$pdf->Open();
		$pdf->SetAutoPageBreak(1,12);
		$pdf->AddFont('Arial','','arial.php');
		$pdf->tMargin=5;
		$pdf->AddPage();
		$pdf->SetFont('Arial','',10);
		$pdf->SetFillColor(255);

		if($CONFIG['site']['doc_header']) {
			$header_img=str_replace('{FN}', $this->doc_data['firm_id'], $CONFIG['site']['doc_header']);
			$pdf->Image($header_img,8,10, 190);
			$pdf->Sety(54);
		}

		$res = $db->query("SELECT `name`, `bik`, `rs`, `ks` FROM `doc_kassa` WHERE `ids`='bank' AND `num`='{$this->doc_data['bank']}'");
		if(!$res->num_rows)	throw new Exception("Информация о банке не найдена");
		$bank_data = $res->fetch_row();

		$str='Банковские реквизиты:';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->SetFont('','',11);
		$pdf->Cell(0,5,$str,0,1,'C',0);

		$old_x=$pdf->GetX();
		$old_y=$pdf->GetY();
		$old_margin=$pdf->lMargin;
		$table_c=110;
		$table_c2=15;

		$pdf->SetFont('','',12);
		$str=$bank_data[0];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c,10,$str,1,1,'L',0);
		$str='ИНН '.$this->firm_vars['firm_inn'].' КПП';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c,5,$str,1,1,'L',0);
		$str=$this->firm_vars['firm_name'];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$tx=$pdf->GetX();
		$ty=$pdf->GetY();
		$pdf->Cell($table_c,10,'',1,1,'L',0);

		$pdf->lMargin=$old_x+1;
		$pdf->SetX($tx+1);
		$pdf->SetY($ty+1);
		$pdf->SetFont('','',9);
		$pdf->MultiCell($table_c,3,$str,0,1,'L',0);

		$pdf->SetFont('','',12);
		$pdf->lMargin=$old_x+$table_c;
		$pdf->SetY($old_y);
		$str='БИК';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c2,5,$str,1,1,'L',0);
		$str='корр/с';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c2,10,$str,1,1,'L',0);
		$str='р/с N';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c2,10,$str,1,1,'L',0);

		$pdf->lMargin=$old_x+$table_c+$table_c2;
		$pdf->SetY($old_y);
		$str=$bank_data[1];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,1,1,'L',0);
		$str=$bank_data[3];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,1,1,'L',0);
		$str=$bank_data[2];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,15,$str,1,1,'L',0);

		$pdf->lMargin=$old_margin;
		$pdf->SetY($old_y+30);

		$pdf->SetFont('','',20);
		$str='Коммерческое предложение № '.$this->doc_data['altnum'].' от '.$dt;
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,0,1,'C',0);
		$pdf->Ln(10);
		$pdf->SetFont('','',10);
		$str='Поставщик: '.$this->firm_vars['firm_name'].', '.$this->firm_vars['firm_telefon'];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->MultiCell(0,5,$str,0,1,'L',0);
		$pdf->Ln(10);

		if($this->dop_data['shapka'])	{
			$pdf->SetFont('','',16);
			$str = iconv('UTF-8', 'windows-1251', $this->dop_data['shapka']);
			$pdf->MultiCell(0,7,$str,0,'C',0);
		}

		$t_width=array(8,40,120,20);
		$pdf->SetWidths($t_width);
		$pdf->SetFont('','',12);
		$str='№';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[0],5,$str,1,0,'C',0);
		$str='Наименование';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[1],5,$str,1,0,'C',0);
		$str='Описание';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[2],5,$str,1,0,'C',0);
		$str='Цена';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[3],5,$str,1,0,'C',0);
		$pdf->Ln();

		$pdf->SetFont('','',10);
		$pdf->SetHeight(4);

		$res = $db->query("SELECT `doc_group`.`printname` AS `group_printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cost`, `doc_base`.`desc`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		$i=0;
		while($line = $res->fetch_assoc()) {
			$i++;
			$cost = sprintf("%01.2f р.", $line['cost']);
			$name = '';
                        if($line['group_printname']) {
                            $name .= $line['group_printname'];
                        }
                        $name .= $line['name'];
			if ($line['proizv']) {
                            $name .= '(' . $line['proizv'] . ')';
                        }                        
			$pdf->RowIconv(array($i, $name, $line['desc'], $cost));
		}

		if ($pdf->h <= ($pdf->GetY() + 40)) {
                    $pdf->AddPage();
                }

                $pdf->ln(10);

		if($this->doc_data['comment']) {
			$pdf->SetFont('','',10);
			$str = iconv('UTF-8', 'windows-1251', $this->doc_data['comment']);
			$pdf->MultiCell(0,5,$str,0,1,'R',0);
			$pdf->ln(6);
		}


		$pdf->SetFont('','',12);
		$str="Система интернет-заказов для постоянных клиентов доступна на нашем сайте";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,0,1,'C',0);

		$pdf->SetTextColor(0,0,192);
		$pdf->SetFont('','UI',20);

		$pdf->Cell(0,7,'http://'.$CONFIG['site']['name'],0,1,'C',0,'http://'.$CONFIG['site']['name']);

		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('','',12);
		$str="При оформлении заказа через сайт предоставляется скидка!";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,8,$str,0,1,'C',0);

		$res = $db->query("SELECT `worker_real_name`, `worker_phone`, `worker_email` FROM `users_worker_info` WHERE `user_id`='{$this->doc_data['user']}'");
		if($res->num_rows) {
			$worker_info = $res->fetch_assoc();
			$pdf->SetAutoPageBreak(0,10);
			$pdf->SetY($pdf->h-18);
			$pdf->Ln(1);
			$pdf->SetFont('','',10);
			$str="Отв. оператор ".$worker_info['worker_real_name'];
			$pdf->CellIconv(0,4,$str,0,1,'R',0);
			$str="Контактный телефон: ".$worker_info['worker_phone'];
			$pdf->CellIconv(0,4,$str,0,1,'R',0);
			$str="Электронная почта: ".$worker_info['worker_email'];
			$pdf->CellIconv(0,4,$str,0,1,'R',0);
		}
		else {
			$pdf->SetAutoPageBreak(0,10);
			$pdf->SetY($pdf->h-12);
			$pdf->Ln(1);
			$pdf->SetFont('','',10);
			$str="Login автора: ".$_SESSION['name'];
			$pdf->CellIconv(0,4,$str,0,1,'R',0);
		}

		if($to_str)
			return $pdf->Output('buisness_offer.pdf','S');
		else
			$pdf->Output('buisness_offer.pdf','I');
	}

        // Коммерческое предложение с количеством
	function KomPredlPDF_Cnt($to_str=0) {
		global $tmpl, $CONFIG, $db;

		$dt = date("d.m.Y",$this->doc_data['date']);
		if(!$to_str) $tmpl->ajax=1;
		
		require('fpdf/fpdf_mc.php');
		$pdf=new PDF_MC_Table('P');
		$pdf->Open();
		$pdf->SetAutoPageBreak(1,12);
		$pdf->AddFont('Arial','','arial.php');
		$pdf->tMargin=5;
		$pdf->AddPage();
		$pdf->SetFont('Arial','',10);
		$pdf->SetFillColor(255);

		if($CONFIG['site']['doc_header']) {
			$pdf->Image($CONFIG['site']['doc_header'],8,10, 190);
			$pdf->Sety(54);
		}

		$res = $db->query("SELECT `name`, `bik`, `rs`, `ks` FROM `doc_kassa` WHERE `ids`='bank' AND `num`='{$this->doc_data['bank']}'");
		if(!$res->num_rows)	throw new Exception("Информация о банке не найдена");
		$bank_data = $res->fetch_row();

		$str='Банковские реквизиты:';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->SetFont('','',11);
		$pdf->Cell(0,5,$str,0,1,'C',0);

		$old_x=$pdf->GetX();
		$old_y=$pdf->GetY();
		$old_margin=$pdf->lMargin;
		$table_c=110;
		$table_c2=15;

		$pdf->SetFont('','',12);
		$str=$bank_data[0];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c,10,$str,1,1,'L',0);
		$str='ИНН '.$this->firm_vars['firm_inn'].' КПП';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c,5,$str,1,1,'L',0);
		$str=$this->firm_vars['firm_name'];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$tx=$pdf->GetX();
		$ty=$pdf->GetY();
		$pdf->Cell($table_c,10,'',1,1,'L',0);

		$pdf->lMargin=$old_x+1;
		$pdf->SetX($tx+1);
		$pdf->SetY($ty+1);
		$pdf->SetFont('','',9);
		$pdf->MultiCell($table_c,3,$str,0,1,'L',0);

		$pdf->SetFont('','',12);
		$pdf->lMargin=$old_x+$table_c;
		$pdf->SetY($old_y);
		$str='БИК';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c2,5,$str,1,1,'L',0);
		$str='корр/с';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c2,10,$str,1,1,'L',0);
		$str='р/с N';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c2,10,$str,1,1,'L',0);

		$pdf->lMargin=$old_x+$table_c+$table_c2;
		$pdf->SetY($old_y);
		$str=$bank_data[1];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,1,1,'L',0);
		$str=$bank_data[3];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,1,1,'L',0);
		$str=$bank_data[2];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,15,$str,1,1,'L',0);

		$pdf->lMargin=$old_margin;
		$pdf->SetY($old_y+30);

		$pdf->SetFont('','',20);
		$str='Коммерческое предложение № '.$this->doc_data['altnum'].' от '.$dt;
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,0,1,'C',0);
		$pdf->Ln(10);
		$pdf->SetFont('','',10);
		$str='Поставщик: '.$this->firm_vars['firm_name'].', '.$this->firm_vars['firm_telefon'];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->MultiCell(0,5,$str,0,1,'L',0);
		$pdf->Ln(10);

		if($this->dop_data['shapka']) {
			$pdf->SetFont('', '', 16);
			$str = iconv('UTF-8', 'windows-1251', $this->dop_data['shapka']);
			$pdf->MultiCell(0, 7, $str, 0, 'C', 0);
		}

		$pdf->SetFont('','',11);
		$t_width=array(8,105,15,30,30);
		$t_text=array("№","Наименование","Кол-во","Срок поставки, рабочих дней","Цена за 1 ед.");
		$t_aligns=array('C','C','C','C','C');
		$pdf->SetWidths($t_width);
		$pdf->SetHeight(5);
		$pdf->SetAligns($t_aligns);
		$pdf->RowIconv($t_text);

		$pdf->SetFont('','',10);

		$res = $db->query("SELECT `doc_group`.`printname` AS `group_pname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`,
                    `doc_base`.`mass`, `doc_list_pos`.`comm`, `class_unit`.`rus_name1` AS `unit_name`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		$i=0;
		$aligns=array('R','L','C','R','R');
		$pdf->SetAligns($aligns);
		$all_sum = 0;
                $sum_mass = 0;
		while($line = $res->fetch_assoc())	{
                    $i++;
                    $cost = sprintf("%01.2f р.", $line['cost']);
                    $name = $line['group_pname'].' '.$line['name'];
                    if($line['proizv']) $name.='('.$line['proizv'].')';
                    $a = array($i, $name, $line['cnt'].' '.$line['unit_name'], $line['comm'], $cost);
                    $pdf->RowIconv($a);
                    $all_sum += $line['cnt']*$line['cost'];
                    $sum_mass += $line['cnt']*$line['mass'];
		}
                $pdf->SetFont('','',14);
		$str = sprintf("Итого: %0.2f руб.", $all_sum);
		$pdf->CellIconv(0,8,$str,0,1,'R',0);
                $pdf->SetFont('','',10);
                $str = sprintf("Масса: %0.3f кг.", $sum_mass);
		$pdf->CellIconv(0,8,$str,0,1,'R',0);
		if($pdf->h<=($pdf->GetY()+40)) $pdf->AddPage();

		$pdf->SetFont('','',12);
		$str="Цены указаны с учётом НДС, за 1 ед. товара";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,8,$str,0,1,'C',0);
		$pdf->ln(6);

		if($this->doc_data['comment']) {
			$pdf->SetFont('', '', 10);
			$str = iconv('UTF-8', 'windows-1251', $this->doc_data['comment']);
			$pdf->MultiCell(0, 5, $str, 0, 1, 'R', 0);
			$pdf->ln(6);
		}


		$pdf->SetFont('','',12);
		$str="Система интернет-заказов для постоянных клиентов доступна на нашем сайте";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,0,1,'C',0);

		$pdf->SetTextColor(0,0,192);
		$pdf->SetFont('','UI',20);

		$pdf->Cell(0,7,'http://'.$CONFIG['site']['name'],0,1,'C',0,'http://'.$CONFIG['site']['name']);

		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('','',12);
		$str="При оформлении заказа через сайт предоставляется скидка!";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,8,$str,0,1,'C',0);

		$res = $db->query("SELECT `worker_real_name`, `worker_phone`, `worker_email` FROM `users_worker_info` WHERE `user_id`='{$this->doc_data['user']}'");
		if($res->num_rows) {
			$worker_info = $res->fetch_assoc();
			$pdf->SetAutoPageBreak(0,10);
			$pdf->SetY($pdf->h-18);
			$pdf->Ln(1);
			$pdf->SetFont('','',10);
			$str="Отв. оператор ".$worker_info['worker_real_name'];
			$pdf->CellIconv(0,4,$str,0,1,'R',0);
			$str="Контактный телефон: ".$worker_info['worker_phone'];
			$pdf->CellIconv(0,4,$str,0,1,'R',0);
			$str="Электронная почта: ".$worker_info['worker_email'];
			$pdf->CellIconv(0,4,$str,0,1,'R',0);
		}
		else {
			$pdf->SetAutoPageBreak(0,10);
			$pdf->SetY($pdf->h-12);
			$pdf->Ln(1);
			$pdf->SetFont('','',10);
			$str="Login автора: ".$_SESSION['name'];
			$pdf->CellIconv(0,4,$str,0,1,'R',0);
		}

		if($to_str)
			return $pdf->Output('buisness_offer.pdf','S');
		else
			$pdf->Output('buisness_offer.pdf','I');
	}

                // Коммерческое предложение с количеством
	function KomPredlPDF_Cntnds($to_str=0) {
		global $tmpl, $CONFIG, $db;

		$dt = date("d.m.Y",$this->doc_data['date']);
		if(!$to_str) $tmpl->ajax=1;
		
		require('fpdf/fpdf_mc.php');
		$pdf=new PDF_MC_Table('P');
		$pdf->Open();
		$pdf->SetAutoPageBreak(1,12);
		$pdf->AddFont('Arial','','arial.php');
		$pdf->tMargin=5;
		$pdf->AddPage();
		$pdf->SetFont('Arial','',10);
		$pdf->SetFillColor(255);

		if($CONFIG['site']['doc_header']) {
			$pdf->Image($CONFIG['site']['doc_header'],8,10, 190);
			$pdf->Sety(54);
		}

		$res = $db->query("SELECT `name`, `bik`, `rs`, `ks` FROM `doc_kassa` WHERE `ids`='bank' AND `num`='{$this->doc_data['bank']}'");
		if(!$res->num_rows)	throw new Exception("Информация о банке не найдена");
		$bank_data = $res->fetch_row();

		$str='Банковские реквизиты:';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->SetFont('','',11);
		$pdf->Cell(0,5,$str,0,1,'C',0);

		$old_x=$pdf->GetX();
		$old_y=$pdf->GetY();
		$old_margin=$pdf->lMargin;
		$table_c=110;
		$table_c2=15;

		$pdf->SetFont('','',12);
		$str=$bank_data[0];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c,10,$str,1,1,'L',0);
		$str='ИНН '.$this->firm_vars['firm_inn'].' КПП';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c,5,$str,1,1,'L',0);
		$str=$this->firm_vars['firm_name'];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$tx=$pdf->GetX();
		$ty=$pdf->GetY();
		$pdf->Cell($table_c,10,'',1,1,'L',0);

		$pdf->lMargin=$old_x+1;
		$pdf->SetX($tx+1);
		$pdf->SetY($ty+1);
		$pdf->SetFont('','',9);
		$pdf->MultiCell($table_c,3,$str,0,1,'L',0);

		$pdf->SetFont('','',12);
		$pdf->lMargin=$old_x+$table_c;
		$pdf->SetY($old_y);
		$str='БИК';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c2,5,$str,1,1,'L',0);
		$str='корр/с';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c2,10,$str,1,1,'L',0);
		$str='р/с N';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c2,10,$str,1,1,'L',0);

		$pdf->lMargin=$old_x+$table_c+$table_c2;
		$pdf->SetY($old_y);
		$str=$bank_data[1];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,1,1,'L',0);
		$str=$bank_data[3];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,1,1,'L',0);
		$str=$bank_data[2];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,15,$str,1,1,'L',0);

		$pdf->lMargin=$old_margin;
		$pdf->SetY($old_y+30);

		$pdf->SetFont('','',20);
		$str='Коммерческое предложение № '.$this->doc_data['altnum'].' от '.$dt;
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,0,1,'C',0);
		$pdf->Ln(10);
		$pdf->SetFont('','',10);
		$str='Поставщик: '.$this->firm_vars['firm_name'].', '.$this->firm_vars['firm_telefon'];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->MultiCell(0,5,$str,0,1,'L',0);
		$pdf->Ln(10);

		if($this->dop_data['shapka']) {
			$pdf->SetFont('', '', 16);
			$str = iconv('UTF-8', 'windows-1251', $this->dop_data['shapka']);
			$pdf->MultiCell(0, 7, $str, 0, 'C', 0);
		}

		$pdf->SetFont('','',11);
		$t_width=array(8,87,15,30,25,25);
		$t_text=array("№","Наименование","Кол-во","Срок поставки, рабочих дней","Цена за 1 ед.","Сумма");
		$t_aligns=array('C','C','C','C','C','C');
		$pdf->SetWidths($t_width);
		$pdf->SetHeight(5);
		$pdf->SetAligns($t_aligns);
		$pdf->RowIconv($t_text);

		$pdf->SetFont('','',10);

		$res = $db->query("SELECT `doc_group`.`printname` AS `group_pname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, 
                    `doc_list_pos`.`cost`, `doc_base`.`mass`, `doc_list_pos`.`comm`, `class_unit`.`rus_name1` AS `unit_name`, `doc_base`.`nds`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		$i=0;
		$aligns=array('R','L','C','R','R','R');
		$pdf->SetAligns($aligns);
		$all_sum = 0;
                $sum_mass = 0;
		while($line = $res->fetch_assoc())	{
                    if($line['nds']!==null) {
                        $ndsp = $line['nds'];
                    } else {
                        $ndsp = $this->firm_vars['param_nds'];
                    }            
                    $nds = $ndsp / 100;
                    
                    $i++;
                    if ($nds) {
                        $cost = $line['cost'] / (1 + $nds);
                       
                    } else {
                        $cost = $line['cost'];
                    }
                    $sum = sprintf("%01.2f р.", $cost * $line['cnt']);
                    $cost = sprintf("%01.2f р.", $cost);
                    $name = $line['group_pname'].' '.$line['name'];
                    if($line['proizv']) $name.='('.$line['proizv'].')';
                    $a = array($i, $name, $line['cnt'].' '.$line['unit_name'], $line['comm'], $cost, $sum);
                    $pdf->RowIconv($a);
                    $all_sum += $sum;
                    $sum_mass += $line['cnt']*$line['mass'];
		}
                $pdf->SetFont('','',14);
		$str = sprintf("Итого: %0.2f руб.", $all_sum);
		$pdf->CellIconv(0,8,$str,0,1,'R',0);
                $pdf->SetFont('','',10);
                $str = sprintf("Масса: %0.3f кг.", $sum_mass);
		$pdf->CellIconv(0,8,$str,0,1,'R',0);
		if($pdf->h<=($pdf->GetY()+40)) $pdf->AddPage();

		$pdf->SetFont('','',12);
		$str="Цены указаны без НДС, за 1 ед. товара";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,8,$str,0,1,'C',0);
		$pdf->ln(6);

		if($this->doc_data['comment']) {
			$pdf->SetFont('', '', 10);
			$str = iconv('UTF-8', 'windows-1251', $this->doc_data['comment']);
			$pdf->MultiCell(0, 5, $str, 0, 1, 'R', 0);
			$pdf->ln(6);
		}


		$pdf->SetFont('','',12);
		$str="Система интернет-заказов для постоянных клиентов доступна на нашем сайте";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,0,1,'C',0);

		$pdf->SetTextColor(0,0,192);
		$pdf->SetFont('','UI',20);

		$pdf->Cell(0,7,'http://'.$CONFIG['site']['name'],0,1,'C',0,'http://'.$CONFIG['site']['name']);

		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('','',12);
		$str="При оформлении заказа через сайт предоставляется скидка!";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,8,$str,0,1,'C',0);

		$res = $db->query("SELECT `worker_real_name`, `worker_phone`, `worker_email` FROM `users_worker_info` WHERE `user_id`='{$this->doc_data['user']}'");
		if($res->num_rows) {
			$worker_info = $res->fetch_assoc();
			$pdf->SetAutoPageBreak(0,10);
			$pdf->SetY($pdf->h-18);
			$pdf->Ln(1);
			$pdf->SetFont('','',10);
			$str="Отв. оператор ".$worker_info['worker_real_name'];
			$pdf->CellIconv(0,4,$str,0,1,'R',0);
			$str="Контактный телефон: ".$worker_info['worker_phone'];
			$pdf->CellIconv(0,4,$str,0,1,'R',0);
			$str="Электронная почта: ".$worker_info['worker_email'];
			$pdf->CellIconv(0,4,$str,0,1,'R',0);
		}
		else {
			$pdf->SetAutoPageBreak(0,10);
			$pdf->SetY($pdf->h-12);
			$pdf->Ln(1);
			$pdf->SetFont('','',10);
			$str="Login автора: ".$_SESSION['name'];
			$pdf->CellIconv(0,4,$str,0,1,'R',0);
		}

		if($to_str)
			return $pdf->Output('buisness_offer.pdf','S');
		else
			$pdf->Output('buisness_offer.pdf','I');
	}

	function CSVExport($to_str = 0) {
		global $tmpl, $db;

		if (!$to_str) {
			$tmpl->ajax = 1;
			header("Content-type: 'application/octet-stream'");
			header("Content-Disposition: 'attachment'; filename=predlojenie.csv;");
			echo"PosNum;ID;Name;Proizv;Cnt;Cost;Sum\r\n";
		}
		else	$str = "PosNum;ID;Name;Proizv;Cnt;Cost;Sum\r\n";

		$res = $db->query("SELECT `doc_base`.`id`, `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		$i = 0;
		while ($nxt = $res->fetch_row()) {
			$i++;
			$sm = $nxt[5] * $nxt[4];
			if (!$to_str)	echo "$i;$nxt[0];\"$nxt[1] $nxt[2]\";\"$nxt[3]\";$nxt[4];$nxt[5];$sm\n";
			else		$str.="$i;$nxt[0];\"$nxt[1] $nxt[2]\";\"$nxt[3]\";$nxt[4];$nxt[5];$sm\n";
		}
		if ($to_str)
			return $str;
	}
}
