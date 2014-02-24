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


/// Документ *товар в пути*
class doc_v_puti extends doc_Nulltype {

	function __construct($doc = 0) {
		parent::__construct($doc);
		$this->doc_type = 12;
		$this->doc_name = 'v_puti';
		$this->doc_viewname = 'Товары в пути';
		$this->sklad_editor_enable = true;
		$this->header_fields = 'sklad cena separator agent';
		settype($this->doc, 'int');
		$this->PDFForms = array(
		    array('name' => 'prn', 'desc' => 'Заявка', 'method' => 'PrintPDF')
		);
	}

	function initDefDopdata() {
		$this->def_dop_data = array('dataprib'=>'', 'transkom'=>0);
	}
	
	
	function DopHead() {
		global $tmpl, $db;
		if (!$this->doc)
			$this->dop_data['dataprib'] = date("Y-m-d");
		$tmpl->addContent("Ориентировочная дата прибытия:<br><input type='text' name='dataprib'  class='vDateField' value='{$this->dop_data['dataprib']}'>");

		$cur_agent = $this->doc_data['agent'];
		if (!$cur_agent)	$cur_agent = 1;

		if (!$this->dop_data['transkom'])
			$this->dop_data['transkom'] = $cur_agent;

		$res = $db->query("SELECT `name` FROM `doc_agent` WHERE `id`='{$this->dop_data['transkom']}'");
		if($res->num_rows)
			list($transkom_name) = $res->fetch_row();
		else	$transkom = '';

		$tmpl->addContent("<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<br>Транспортная компания:<br>
		<input type='hidden' name='transkom' id='transkom_id' value='{$this->dop_data['transkom']}'>
		<input type='text' id='transkom'  style='width: 100%;' value='$transkom_name'><br>
		<script type=\"text/javascript\">
		$(document).ready(function(){
			$(\"#transkom\").autocomplete(\"/docs.php\", {
			delay:300,
			minChars:1,
			matchSubset:1,
			autoFill:false,
			selectFirst:true,
			matchContains:1,
			cacheLength:10,
			maxItemsToShow:15,
			formatItem:agliFormat,
			onItemSelect:transkomselectItem,
			extraParams:{'l':'agent','mode':'srv','opt':'ac'}
			});
		});

		function transkomselectItem(li) {
		if( li == null ) var sValue = \"Ничего не выбрано!\";
		if( !!li.extra ) var sValue = li.extra[0];
		else var sValue = li.selectValue;
		document.getElementById('transkom_id').value=sValue;
		}
		</script>");
	}

	function DopSave() {
		$new_data = array(
		    'dataprib' => rcvdate('dataprib'),
		    'transkom' => request('transkom')
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
			$tmpl->addContent("<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=1'\">Поступление</div>");
		} else if ($target_type == 1) {
			if (!isAccess('doc_postuplenie', 'create'))
				throw new AccessException("");
			$db->startTransaction();
			$new_doc = new doc_Postuplenie();
			$dd = $new_doc->createFromP($this);
			$new_doc->setDopData('cena', $this->dop_data['cena']);
			$db->commit();
			$ref = "Location: doc.php?mode=body&doc=$dd";
			header($ref);
		}
		else	$tmpl->msg("В разработке", "info");
	}

	function PrintPDF($to_str = 0) {
		global $tmpl, $CONFIG, $db;

		$agent_data = $db->selectRow('doc_agent', $this->doc_data['agent'], array('adres', 'tel'));
		$dt = date("d.m.Y", $this->doc_data['date']);

		if (!$to_str)	$tmpl->ajax = 1;

		require('fpdf/fpdf.php');
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

		$str = 'Просим рассмотреть возможность поставки следующей продукции:';
		$pdf->SetFont('', 'U', 14);
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0, 5, $str, 0, 1, 'C', 0);

		$old_x = $pdf->GetX();
		$old_y = $pdf->GetY();
		$old_margin = $pdf->lMargin;
		$table_c = 110;
		$table_c2 = 15;

		$pdf->SetFont('', '', 16);
		$str = 'Заявка поставщику № ' . $this->doc_data['altnum'] . ', от ' . $dt;
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0, 8, $str, 0, 1, 'L', 0);
		$pdf->SetFont('', '', 8);
		$str = 'Заказчик: ' . $this->firm_vars['firm_name'] . ', ' . $this->firm_vars['firm_adres'] . ', тел:' . $this->firm_vars['firm_telefon'];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->MultiCell(0, 5, $str, 0, 1, 'L', 0);
		$str = "Поставщик: {$this->doc_data['agent_name']}, адрес: {$agent_data['adres']}, телефон: {$agent_data['tel']}";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->MultiCell(0, 5, $str, 0, 1, 'L', 0);

		$t_width = array(8, 110, 20, 25, 0);
		$pdf->SetFont('', '', 12);
		$str = '№';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[0], 5, $str, 1, 0, 'C', 0);
		$str = 'Наименование';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[1], 5, $str, 1, 0, 'C', 0);
		$str = 'Кол-во';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[2], 5, $str, 1, 0, 'C', 0);
		$str = 'Цена';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[3], 5, $str, 1, 0, 'C', 0);
		$str = 'Сумма';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[4], 5, $str, 1, 0, 'C', 0);
		$pdf->Ln();

		$pdf->SetFont('', '', 8);

		$res = $db->query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_dop`.`mass`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		$i = 0;
		$sum = $summass = 0;
		while ($nxt = $res->fetch_row()) {
			$i++;
			$sm = $nxt[3] * $nxt[4];
			$sum+=$sm;
			$summass+=$nxt[5] * $nxt[3];
			$cost = sprintf("%01.2f р.", $nxt[4]);
			$smcost = sprintf("%01.2f р.", $sm);

			$pdf->Cell($t_width[0], 5, $i, 1, 0, 'R', 0);
			$str = $nxt[0] . ' ' . $nxt[1];
			if ($nxt[2])
				$str.='(' . $nxt[2] . ')';
			$str = iconv('UTF-8', 'windows-1251', $str);
			$pdf->Cell($t_width[1], 5, $str, 1, 0, 'L', 0);
			$pdf->Cell($t_width[2], 5, $nxt[3], 1, 0, 'C', 0);
			$str = iconv('UTF-8', 'windows-1251', $cost);
			$pdf->Cell($t_width[3], 5, $str, 1, 0, 'R', 0);
			$str = iconv('UTF-8', 'windows-1251', $smcost);
			$pdf->Cell($t_width[4], 5, $str, 1, 0, 'R', 0);
			$pdf->Ln();
		}

		$cost = num2str($sum);
		$sumcost = sprintf("%01.2f", $sum);
		$summass = sprintf("%01.3f", $summass);


		if ($pdf->h <= ($pdf->GetY() + 60))
			$pdf->AddPage();

		$delta = $pdf->h - ($pdf->GetY() + 55);
		if ($delta > 7)
			$delta = 7;

		if ($CONFIG['site']['doc_shtamp']) {
			$shtamp_img = str_replace('{FN}', $this->doc_data['firm_id'], $CONFIG['site']['doc_shtamp']);
			$pdf->Image($shtamp_img, 4, $pdf->GetY() + $delta, 120);
		}

		$pdf->SetFont('', '', 8);
		$str = "Масса товара: $summass кг.";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0, 6, $str, 0, 0, 'L', 0);

		$nds = $sum / (100 + $this->firm_vars['param_nds']) * $this->firm_vars['param_nds'];
		$nds = sprintf("%01.2f", $nds);
		$pdf->SetFont('', '', 12);
		$str = "Итого: $sumcost руб.";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0, 7, $str, 0, 1, 'R', 0);

		$pdf->SetFont('', '', 8);
		$str = "Всего $i наименований, на сумму $sumcost руб. ($cost)";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0, 4, $str, 0, 1, 'L', 0);

		if ($to_str)	return $pdf->Output('zayavka.pdf', 'S');
		else		$pdf->Output('zayavka.pdf', 'I');
	}

};
?>