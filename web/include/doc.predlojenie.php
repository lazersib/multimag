<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2015, BlackLight, TND Team, http://tndproject.org
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

/// Документ *Предложение поставщика*
class doc_Predlojenie extends doc_Nulltype
{
	// Создание нового документа или редактирование заголовка старого
	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				=11;
		$this->doc_name				='predlojenie';
		$this->doc_viewname			='Предложение поставщика';
		$this->sklad_editor_enable		=true;
		$this->header_fields			='sklad cena separator agent';
		$this->PDFForms=array(
			array('name'=>'req','desc'=>'Заявка на поставку','method'=>'PrintPDF')
		);
	}

	// Формирование другого документа на основании текущего
	function MorphTo($target_type) {
		global $tmpl;

		if($target_type=='') {
			$tmpl->ajax=1;
			$tmpl->addContent("<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=1'\">Поступление</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=12'\">Товар в пути</div>");
		}
		else if($target_type==1) {
			if(!isAccess('doc_postuplenie','create'))	throw new AccessException();
			$base = $this->Postup();
			header('Location: doc.php?mode=body&doc='.$base);
		}
		else if($target_type==12) {
			if(!isAccess('doc_v_puti','create'))	throw new AccessException();
			$base = $this->Vputi();
			header('Location: doc.php?mode=body&doc='.$base);
		}
	}

// ================== Функции только этого класса ======================================================
	function Postup() {
		global $db;
		$target_type=1;
		$db->startTransaction();
		$res = $db->query("SELECT `id` FROM `doc_list` WHERE `p_doc`='$this->doc' AND `type`='$target_type'");
		if(! $res->num_rows) {
			DocSumUpdate($this->doc);
			$new_doc = new doc_Postuplenie();
			$x_doc_num = $new_doc->createFromP($this);
			$new_doc->setDopData('cena', $this->dop_data['cena']);
		}
		else
		{
			$x_doc_info = $res->fetch_row();
			$x_doc_num = $x_doc_info[0];
			$new_id = 0;
			$res = $db->query("SELECT `a`.`tovar`, `a`.`cnt`, `a`.`comm`, `a`.`cost`,
			( SELECT SUM(`b`.`cnt`) FROM `doc_list_pos` AS `b`
			  INNER JOIN `doc_list` ON `b`.`doc`=`doc_list`.`id` AND `doc_list`.`p_doc`='{$this->doc}'
			  WHERE `b`.`tovar`=`a`.`tovar` )
			FROM `doc_list_pos` AS `a`
			WHERE `a`.`doc`='{$this->doc}'
			ORDER BY `doc_list_pos`.`id`");
			while($nxt = $res->fetch_row()) {
				if($nxt[4]<$nxt[1]) {
					if(!$new_id) {
						$new_doc = new doc_Postuplenie();
						$new_id = $new_doc->createFrom($this);
						$new_doc->setDopData('cena', $this->dop_data['cena']);
					}
					$n_cnt=$nxt[1]-$nxt[4];
					$db->query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `comm`, `cost`)
 					VALUES ('$new_id', '$nxt[0]', '$n_cnt', '$nxt[2]', '$nxt[3]' )");
				}
			}
			if($new_id) $x_doc_num=$new_id;
		}
		$db->commit();
		return $x_doc_num;
	}

	//	================== Функции только этого класса ======================================================
	function Vputi() {
		global $db;
		$target_type=1;
		$db->startTransaction();
		$res = $db->query("SELECT `id` FROM `doc_list` WHERE `p_doc`='$this->doc' AND `type`='$target_type'");
		if(! $res->num_rows) {
			DocSumUpdate($this->doc);
			$new_doc = new doc_v_puti();
			$x_doc_num = $new_doc->createFromP($this);
			$new_doc->setDopData('cena', $this->dop_data['cena']);
		}
		else
		{
			$x_doc_info = $res->fetch_row();
			$x_doc_num = $x_doc_info[0];
			$new_id = 0;
			$res = $db->query("SELECT `a`.`tovar`, `a`.`cnt`, `a`.`comm`, `a`.`cost`,
			( SELECT SUM(`b`.`cnt`) FROM `doc_list_pos` AS `b`
			  INNER JOIN `doc_list` ON `b`.`doc`=`doc_list`.`id` AND `doc_list`.`p_doc`='{$this->doc}'
			  WHERE `b`.`tovar`=`a`.`tovar` )
			FROM `doc_list_pos` AS `a`
			WHERE `a`.`doc`='{$this->doc}'
			ORDER BY `doc_list_pos`.`id`");
			while($nxt = $res->fetch_row()) {
				if($nxt[4]<$nxt[1]) {
					if(!$new_id) {
						$new_doc = new doc_v_puti();
						$new_id = $new_doc->createFrom($this);
						$new_doc->setDopData('cena', $this->dop_data['cena']);
					}
					$n_cnt=$nxt[1]-$nxt[4];
					$db->query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `comm`, `cost`)
 					VALUES ('$new_id', '$nxt[0]', '$n_cnt', '$nxt[2]', '$nxt[3]' )");
				}
			}
			if($new_id) $x_doc_num=$new_id;
		}
		$db->commit();
		return $x_doc_num;
	}

	function PrintPDF($to_str=0) {
		global $tmpl, $CONFIG, $db;

//		$res=mysql _query("SELECT `adres`, `tel` FROM `doc_agent` WHERE `id`='{$this->doc_data[2]}'");
		$agent_data = $db->selectRow('doc_agent', $this->doc_data['agent']);
		$dt=date("d.m.Y",$this->doc_data['date']);
		if(!$to_str) $tmpl->ajax=1;
		
		require('fpdf/fpdf.php');
		$pdf=new FPDF('P');
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

		$str = 'Просим рассмотреть возможность поставки следующей продукции:';
		$pdf->SetFont('','U',14);
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,0,1,'C',0);

		$old_x=$pdf->GetX();
		$old_y=$pdf->GetY();
		$old_margin=$pdf->lMargin;
		$table_c=110;
		$table_c2=15;

		$pdf->SetFont('','',16);
		$str='Заявка поставщику № '.$this->doc_data['altnum'].', от '.$dt;
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,8,$str,0,1,'L',0);
		$pdf->SetFont('','',8);
		$str='Заказчик: '.$this->firm_vars['firm_name'].', '.$this->firm_vars['firm_adres'].', тел:'.$this->firm_vars['firm_telefon'];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->MultiCell(0,5,$str,0,1,'L',0);
		$str="Поставщик: ".$this->doc_data['agent_name'].", адрес: {$agent_data['adres']}, телефон: {$agent_data['tel']}";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->MultiCell(0,5,$str,0,1,'L',0);

		$t_width=array(8,110,20,25,0);
		$pdf->SetFont('','',12);
		$str='№';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[0],5,$str,1,0,'C',0);
		$str='Наименование';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[1],5,$str,1,0,'C',0);
		$str='Кол-во';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[2],5,$str,1,0,'C',0);
		$str='Цена';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[3],5,$str,1,0,'C',0);
		$str='Сумма';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[4],5,$str,1,0,'C',0);
		$pdf->Ln();

		$pdf->SetFont('','',8);

		$res = $db->query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`,
                    `doc_base`.`mass`, `doc_base`.`nds`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		$i=0;
		$sum=$summass=$sum_nds=0;
		while($nxt = $res->fetch_assoc()) {
                        if($nxt['nds']!==null) {
                            $ndsp = $nxt['nds'];
                        } else {
                            $ndsp = $this->firm_vars['param_nds'];
                        }            
                        
			$i++;
			$sm=$nxt['cnt']*$nxt['cost'];
			$sum+=$sm;
			$summass+=$nxt['mass']*$nxt['cnt'];
                        $sum_nds = $sm/(100+$ndsp)*$ndsp;
                        
			$cost = sprintf("%01.2f р.", $nxt['cost']);
			$smcost = sprintf("%01.2f р.", $sm);

			$pdf->Cell($t_width[0],5,$i,1,0,'R',0);
			$str=$nxt['printname'].' '.$nxt['name'];
			if($nxt['proizv']) $str.='('.$nxt['proizv'].')';
			$str = iconv('UTF-8', 'windows-1251', $str);
			$pdf->Cell($t_width[1],5,$str,1,0,'L',0);
			$pdf->Cell($t_width[2],5,$nxt['cnt'],1,0,'C',0);
			$str = iconv('UTF-8', 'windows-1251', $cost);
			$pdf->Cell($t_width[3],5,$str,1,0,'R',0);
			$str = iconv('UTF-8', 'windows-1251', $smcost);
			$pdf->Cell($t_width[4],5,$str,1,0,'R',0);
			$pdf->Ln();
		}

		$cost = num2str($sum);
		$sumcost = sprintf("%01.2f", $sum);
		$summass = sprintf("%01.3f", $summass);


		if($pdf->h<=($pdf->GetY()+60)) $pdf->AddPage();

		$delta=$pdf->h-($pdf->GetY()+55);
		if($delta>7) $delta=7;

		if($CONFIG['site']['doc_shtamp']) {
			$shtamp_img=str_replace('{FN}', $this->doc_data['firm_id'], $CONFIG['site']['doc_shtamp']);
			$pdf->Image($shtamp_img, 4,$pdf->GetY()+$delta, 120);
		}

		$pdf->SetFont('','',8);
		$str="Масса товара: $summass кг.";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,6,$str,0,0,'L',0);

		$nds = sprintf("%01.2f", $sum_nds);
		$pdf->SetFont('','',12);
		$str="Итого: $sumcost руб.";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,7,$str,0,1,'R',0);

		$pdf->SetFont('','',8);
		$str="Всего $i наименований, на сумму $sumcost руб. ($cost)";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,4,$str,0,1,'L',0);

		if($to_str)
			return $pdf->Output('request.pdf','S');
		else
			$pdf->Output('request.pdf','I');
	}
};
?>