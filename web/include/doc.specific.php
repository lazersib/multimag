<?php
//	MultiMag v0.1 - Complex sales system
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


/// Документ *спецификация*
class doc_Specific extends doc_Nulltype {

	function __construct($doc = 0) {
		parent::__construct($doc);
		$this->doc_type = 16;
		$this->doc_name = 'specific';
		$this->doc_viewname = 'Спецификация';
		$this->sklad_editor_enable = true;
		$this->header_fields = 'agent cena';
		settype($this->doc, 'int');
		$this->PDFForms = array(
		    array('name' => 'prn', 'desc' => 'Спецификация', 'method' => 'PrintPDF')
		);
	}

	function initDefDopdata() {
		$this->def_dop_data = array('received'=>0, 'cena'=>1);
	}
	
	function DopHead() {
		global $tmpl;
		$checked = $this->dop_data['received'] ? 'checked' : '';
		$tmpl->addContent("<label><input type='checkbox' name='received' value='1' $checked>Документы подписаны и получены</label><br>");
	}

	function DopSave() {
		$new_data = array(
		    'received' => rcvint('received')
		);
		$old_data = array_intersect_key($new_data, $this->dop_data);

		$log_data = '';
		if ($this->doc)	$log_data = getCompareStr($old_data, $new_data);
		$this->setDopDataA($new_data);
		if ($log_data)	doc_log("UPDATE {$this->doc_name}", $log_data, 'doc', $this->doc);
	}

	// Формирование другого документа на основании текущего
	function MorphTo($target_type) {
		global $tmpl, $db;
		if ($target_type == '') {
			$tmpl->ajax = 1;
			$tmpl->addContent("<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=3'\">Заявка покупателя</div>");
		} else if ($target_type == 3) {
			$db->startTransaction();
			$new_doc = new doc_Zayavka();
			$dd = $new_doc->createFromP($this);
			$new_doc->setDopData('cena', $this->dop_data['cena']);
			$db->commit();
			$ref = "Location: doc.php?mode=body&doc=$dd";
			header($ref);
		}
	}

	function PrintPDF($to_str = 0) {
		global $tmpl, $CONFIG, $db;
		
		$dt = date("d.m.Y", $this->doc_data['date']);
		if (!$to_str)	$tmpl->ajax = 1;
		
		require('fpdf/fpdf_mysql.php');
		$pdf = new FPDF('P');
		$pdf->Open();
		$pdf->SetAutoPageBreak(1, 12);
		$pdf->AddFont('Arial', '', 'arial.php');
		$pdf->tMargin = 5;
		$pdf->AddPage();
		$pdf->SetFont('Arial', '', 10);
		$pdf->SetFillColor(255);

		if ($CONFIG['site']['doc_header']) {
			$header_img = str_replace('{FN}', $this->doc_data['firm_id'], $CONFIG['site']['doc_header']);
			$pdf->Image($header_img, 8, 10, 190);
			$pdf->Sety(54);
		}

		$dres = $db->query("SELECT `altnum`, `date` FROM `doc_list` WHERE `id`='{$this->doc_data['p_doc']}'");
		$dog = $dres->fetch_assoc();
		if($dog) {
			$dog['date'] = date("Y-m-d", $dog['date']);
			$pdf->SetFont('', '', 12);
			$str = "К договору N{$dog['altnum']} от {$dog['date']}";
			$str = iconv('UTF-8', 'windows-1251', $str);
			$pdf->Cell(0, 5, $str, 0, 1, 'R', 0);
			$pdf->Ln(5);
		}
		$pdf->SetFont('', '', 20);
		$str = 'Спецификация № ' . $this->doc_data['altnum'] . ' от ' . $dt;
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0, 6, $str, 0, 1, 'C', 0);
		$str = "на поставку продукции";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0, 6, $str, 0, 1, 'C', 0);
		$pdf->Ln(10);

		$pdf->SetLineWidth(0.5);

		$t_width = array(7, 85, 14, 15, 25, 22, 0);
		$pdf->SetFont('', '', 9);
		$str = '№';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[0], 5, $str, 1, 0, 'C', 0);

		$str = 'Наименование продукции';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[1], 5, $str, 1, 0, 'C', 0);

		$str = 'Ед.изм.';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[2], 5, $str, 1, 0, 'C', 0);

		$str = 'Кол-во';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[3], 5, $str, 1, 0, 'C', 0);

		$str = "Цена без НДС";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[4], 5, $str, 1, 0, 'C', 0);

		$str = "Цена c НДС";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[5], 5, $str, 1, 0, 'C', 0);

		$str = "Cумма c НДС";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[6], 5, $str, 1, 0, 'C', 0);

		$pdf->Ln();
		$pdf->SetLineWidth(0.2);
		$pdf->SetFont('', '', 7);

		$res = $db->query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_dop`.`mass`, `class_unit`.`rus_name1` AS `unit_print`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		$i = $allsum = $nds_sum = 0;
		while ($nxt = $res->fetch_row()) {
			$i++;

			if ($this->doc_data['nds']) { // Включать НДС
				$c_bez_nds = sprintf("%01.2f", $nxt[4] / (100 + $this->firm_vars['param_nds']) * 100);
				$c_s_nds = $nxt[4];
			} else {
				$c_bez_nds = $nxt[4];
				$c_s_nds = sprintf("%01.2f", $nxt[4] * (100 + $this->firm_vars['param_nds']) / 100);
			}
			$s_s_nds = $c_s_nds * $nxt[3];
			$allsum+=$c_s_nds * $nxt[3];
			$nds_sum+=($c_s_nds - $c_bez_nds) * $nxt[3];

			$c_bez_nds = sprintf("%01.2f р.", $c_bez_nds);
			$c_s_nds = sprintf("%01.2f р.", $c_s_nds);
			$s_s_nds = sprintf("%01.2f р.", $s_s_nds);

			$pdf->Cell($t_width[0], 4, $i, 1, 0, 'R', 0);
			$str = $nxt[0] . ' ' . $nxt[1];
			if ($nxt[2])
				$str.='(' . $nxt[2] . ')';
			$str = iconv('UTF-8', 'windows-1251', $str);
			$pdf->Cell($t_width[1], 4, $str, 1, 0, 'L', 0);

			$str = iconv('UTF-8', 'windows-1251', $nxt[6]);
			$pdf->Cell($t_width[2], 4, $str, 1, 0, 'R', 0);

			$pdf->Cell($t_width[3], 4, $nxt[3], 1, 0, 'R', 0);

			$str = iconv('UTF-8', 'windows-1251', $c_bez_nds);
			$pdf->Cell($t_width[4], 4, $str, 1, 0, 'R', 0);

			$str = iconv('UTF-8', 'windows-1251', $c_s_nds);
			$pdf->Cell($t_width[5], 4, $str, 1, 0, 'R', 0);

			$str = iconv('UTF-8', 'windows-1251', $s_s_nds);
			$pdf->Cell($t_width[6], 4, $str, 1, 0, 'R', 0);

			$pdf->Ln();
		}

		if ($pdf->h <= ($pdf->GetY() + 40))
			$pdf->AddPage();

		$pdf->ln(10);

		if ($this->doc_data['comment']) {
			$pdf->SetFont('', '', 10);
			$str = iconv('UTF-8', 'windows-1251', str_replace("<br>", ", ", $this->doc_data['comment']));
			$pdf->MultiCell(0, 5, $str, 0, 1, 'R', 0);
			$pdf->ln(6);
		}

		$pdf->SetFont('', '', 11);
		$allsum_p = sprintf("%01.2f", $allsum);
		$str = "Общая сумма спецификации N {$this->doc_data['altnum']} с учетом НДС составляет $allsum_p рублей.";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->MultiCell(0, 5, $str, 0, 1, 'L', 0);
		$nds_sum_p = sprintf("%01.2f", $nds_sum);
		$str = "Сумма НДС составляет $nds_sum_p рублей.";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->MultiCell(0, 5, $str, 0, 1, 'L', 0);
		$pdf->Ln(7);

		$pdf->SetFont('', '', 16);
		$str = "Покупатель";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(90, 6, $str, 0, 0, 'L', 0);
		$str = "Поставщик";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0, 6, $str, 0, 0, 'L', 0);

		$pdf->Ln(5);
		$pdf->SetFont('', '', 10);
		$res = $db->query("SELECT `doc_agent`.`id`, `doc_agent`.`fullname`, `doc_agent`.`adres`,  `doc_agent`.`tel`, `doc_agent`.`inn`, `doc_agent`.`okpo`, `doc_agent`.`okevd`, `doc_agent`.`bik`, `doc_agent`.`rs`, `doc_agent`.`ks`, `doc_agent`.`bank`, `doc_agent`.`pfio`, `doc_agent`.`pdol`
		FROM `doc_agent` WHERE `doc_agent`.`id`='{$this->doc_data['agent']}'");

		$agent_info = $res->fetch_array();

		$str = "$agent_info[1]\n$agent_info[2], тел. $agent_info[3]\nИНН/КПП $agent_info[4], ОКПО $agent_info[5], ОКВЭД $agent_info[6]\nР/С $agent_info[8], в банке $agent_info[10]\nК/С $agent_info[9], БИК $agent_info[7]\n__________________ / _________________ /\n\n      М.П.";
		$str = iconv('UTF-8', 'windows-1251', $str);

		$y = $pdf->GetY();

		$pdf->MultiCell(90, 5, $str, 0, 'L', 0);
		$pdf->SetY($y);
		$pdf->SetX(100);

		$str = "{$this->firm_vars['firm_name']}\n{$this->firm_vars['firm_adres']}\nИНН/КПП {$this->firm_vars['firm_inn']}\nР/С {$this->firm_vars['firm_schet']}, в банке {$this->firm_vars['firm_bank']}\nК/С {$this->firm_vars['firm_bank_kor_s']}, БИК {$this->firm_vars['firm_bik']}\n__________________ / {$this->firm_vars['firm_director']} /\n\n      М.П.";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->MultiCell(0, 5, $str, 0, 'L', 0);

		if ($CONFIG['site']['doc_shtamp']) {
			$delta = -15;
			$shtamp_img = str_replace('{FN}', $this->doc_data['firm_id'], $CONFIG['site']['doc_shtamp']);
			$pdf->Image($shtamp_img, 95, $pdf->GetY() + $delta, 120);
		}

		$res = $db->query("SELECT `worker_real_name`, `worker_phone`, `worker_email` FROM `users_worker_info` WHERE `user_id`='{$this->doc_data['user']}'");
		if ($res->num_rows) {
			list($name, $tel, $email) = $res->fetch_row();
			if (!$name)	$name = '(' . $_SESSION['name'] . ')';

			$pdf->SetAutoPageBreak(0, 10);
			$pdf->SetY($pdf->h - 18);
			$pdf->Ln(1);
			$pdf->SetFont('', '', 10);
			$str = "Исп. менеджер $name";
			$str = iconv('UTF-8', 'windows-1251', $str);
			$pdf->Cell(0, 4, $str, 0, 1, 'R', 0);
			$str = "Контактный телефон: $tel";
			$str = iconv('UTF-8', 'windows-1251', $str);
			$pdf->Cell(0, 4, $str, 0, 1, 'R', 0);
			$str = "Электронная почта: $email";
			$str = iconv('UTF-8', 'windows-1251', $str);
			$pdf->Cell(0, 4, $str, 0, 1, 'R', 0);
		}
		else {
			$pdf->SetAutoPageBreak(0, 10);
			$pdf->SetY($pdf->h - 12);
			$pdf->Ln(1);
			$pdf->SetFont('', '', 10);
			$str = "Login автора: " . $_SESSION['name'];
			$str = iconv('UTF-8', 'windows-1251', $str);
			$pdf->Cell(0, 4, $str, 0, 1, 'R', 0);
		}

		if ($to_str)	return $pdf->Output('buisness_offer.pdf', 'S');
		else		$pdf->Output('specific.pdf', 'I');
	}

}

;
?>