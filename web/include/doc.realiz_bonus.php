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


/// Документ *Реализация за бонусы*
class doc_Realiz_bonus extends doc_Realizaciya
{

	function __construct($doc=0) {
		parent::__construct($doc);
		$this->doc_type				= 20;
		$this->doc_name				= 'realiz_bonus';
		$this->doc_viewname			= 'Реализация товара за бонусы';
		$this->sklad_editor_enable		= true;
		$this->sklad_modify			= -1;
		$this->header_fields			= 'sklad cena separator agent';
		$this->dop_menu_buttons			= "<a href='' onclick=\"ShowPopupWin('/doc.php?mode=srv&amp;opt=dov&amp;doc=$doc'); return false;\" title='Доверенное лицо'><img src='img/i_users.png' alt='users'></a>";
		settype($this->doc,'int');
		$this->PDFForms=array(
			array('name'=>'nak','desc'=>'Накладная','method'=>'PrintNaklPDF')
		);
	}

	function DocApply($silent=0) {
		global $db;
		if(!$silent) {
			$res = $db->query("SELECT `no_bonuses` FROM `doc_agent` WHERE `id`=".intval($this->doc_data['agent']));
			if(!$res->num_rows)
				throw new Exception ("Агент не найден");
			$agent_info = $res->fetch_row();
			if($agent_info[0])
				throw new Exception ("Агент не участвует в бонусной программе");
			$bonus = docCalcBonus($this->doc_data['agent']);
			if($this->doc_data['sum']>$bonus)		throw new Exception("У агента недостаточно бонусов");
		}
		parent::DocApply($silent);
	}

/// Обычная накладная в PDF формате
/// @param to_str Вернуть строку, содержащую данные документа (в противном случае - отправить файлом)
	function PrintNaklPDF($to_str=false) {
		global $tmpl, $CONFIG, $db;

		if(!$to_str) $tmpl->ajax=1;
		
		require('fpdf/fpdf_mc.php');
		$pdf=new PDF_MC_Table('P');
		$pdf->Open();
		$pdf->SetAutoPageBreak(0,10);
		$pdf->AddFont('Arial','','arial.php');
		$pdf->tMargin=10;
		$pdf->AddPage();
		$pdf->SetFont('Arial','',10);
		$pdf->SetFillColor(255);

		$dt=date("d.m.Y",$this->doc_data['date']);

		$pc = PriceCalc::getInstance();
		$def_cost = $pc->getDefaultPriceId();

		$pdf->SetFont('','',16);
		$str="Бонусная накладная N {$this->doc_data['altnum']}{$this->doc_data['subtype']} ({$this->doc}), от $dt";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,8,$str,0,1,'C',0);
		$pdf->SetFont('','',10);
		$str="Поставщик: {$this->firm_vars['firm_name']}, тел: {$this->firm_vars['firm_telefon']}";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,0,1,'L',0);
		$str="Покупатель: {$this->doc_data['agent']}";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,0,1,'L',0);
		$pdf->Ln();

		$pdf->SetLineWidth(0.5);
		$t_width=array(8);
		if($CONFIG['poseditor']['vc'])
		{
			$t_width[]=20;
			$t_width[]=91;
		}
		else	$t_width[]=111;
		$t_width=array_merge($t_width, array(12,15,23,23));

		$t_text=array('№');
		if($CONFIG['poseditor']['vc'])
		{
			$t_text[]='Код';
			$t_text[]='Наименование';
		}
		else	$t_text[]='Наименование';
		$t_text=array_merge($t_text, array('Место', 'Кол-во', 'Стоимость', 'Сумма'));

		foreach($t_width as $id=>$w)
		{
			$str = iconv('UTF-8', 'windows-1251', $t_text[$id]);
			$pdf->Cell($w,6,$str,1,0,'C',0);
		}
		$pdf->Ln();
		$pdf->SetWidths($t_width);
		$pdf->SetHeight(3.8);

		$aligns=array('R');
		if($CONFIG['poseditor']['vc'])
		{
			$aligns[]='L';
			$aligns[]='L';
		}
		else	$aligns[]='L';
		$aligns=array_merge($aligns, array('C','R','R','R'));

		$pdf->SetAligns($aligns);
		$pdf->SetLineWidth(0.2);
		$pdf->SetFont('','',8);

		$res = $db->query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`mesto`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`id`, `doc_base`.`vc`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->doc_data['sklad']}'
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		$ii=1;
		$sum=0;
		while($nxt = $res->fetch_row()) {
			$sm=$nxt[3]*$nxt[4];
			$cost = sprintf("%01.2f бонусов", $nxt[4]);
			$cost2 = sprintf("%01.2f бонусов", $sm);
			if(!@$CONFIG['doc']['no_print_vendor'] && $nxt[2])	$nxt[1].=' / '.$nxt[2];

			$row=array($ii);
			if($CONFIG['poseditor']['vc']) {
				$row[]=$nxt[8];
				$row[]="$nxt[0] $nxt[1]";
			}
			else	$row[]="$nxt[0] $nxt[1]";
			$row=array_merge($row, array($nxt[5], "$nxt[3] $nxt[6]", $cost, $cost2));

			$pdf->RowIconv($row);
			$ii++;
			$sum+=$sm;
		}
		$ii--;
		$cost = sprintf("%01.2f бонусов", $sum);

		$pdf->Ln();

		$str="Всего $ii наименований на сумму $cost";
		if($this->dop_data['mest'])	$str.=", мест: ".$this->dop_data['mest'];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,0,1,'L',0);

		$prop="Бонусный баланс: ".docCalcBonus($this->doc_data['agent']);
		$str = iconv('UTF-8', 'windows-1251', $prop);
		$pdf->Cell(0,5,$str,0,1,'L',0);		

		$str="Товар получил, претензий к качеству товара и внешнему виду не имею.";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,0,1,'L',0);
		$str="Покупатель: ____________________________________";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,0,1,'L',0);
		$str="Поставщик:_____________________________________";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,0,1,'L',0);

		if($to_str)
			return $pdf->Output('blading.pdf','S');
		else
			$pdf->Output('blading.pdf','I');
	}
};
?>