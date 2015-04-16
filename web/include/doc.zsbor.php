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
/// Документ *Заявка на производство*
class doc_ZSbor extends doc_Nulltype {

	/// Конструктор
	/// @param doc id документа
	function __construct($doc = 0) {
		global $CONFIG, $db;
		$this->def_dop_data = array();
		parent::__construct($doc);
		$this->doc_type = 21;
		$this->typename = 'zsbor';
		$this->viewname = 'Заявка на производство';
		$this->sklad_editor_enable = true;
		$this->sklad_modify = 0;
		$this->header_fields = 'sklad cena';
		settype($this->id, 'int');
		$this->PDFForms = array(
		    array('name' => 'z_kompl', 'desc' => 'Заявка на комплектующие', 'method' => 'PrintKompl')
		);
	}

	/// Формирование другого документа на основании текущего
	function MorphTo($target_type) {
		global $tmpl, $db;

		if ($target_type == '') {
			$tmpl->ajax = 1;
			$tmpl->addContent("<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->id}&amp;tt=8'\">Перемещение</div>");
		}
		else if ($target_type == '8') {
			if (!isAccess('doc_peremeshenie', 'create'))
				throw new AccessException();
			$new_doc = new doc_Peremeshenie();
			$dd = $new_doc->createFrom($this);
			$new_doc->setDopData('na_sklad', $this->doc_data['sklad']);
			$res = $db->query("SELECT `doc_base_kompl`.`kompl_id`, SUM(`doc_base_kompl`.`cnt`*`doc_list_pos`.`cnt`) AS `cnt`,
				`doc_base_cnt`.`cnt` AS `sklad_cnt`, `doc_base`.`cost`
			FROM `doc_list_pos`
			INNER JOIN `doc_base_kompl` ON `doc_base_kompl`.`pos_id` = `doc_list_pos`.`tovar`
			INNER JOIN `doc_base` ON `doc_base_kompl`.`kompl_id` = `doc_base`.`id`
			LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id` = `doc_base_kompl`.`kompl_id` AND `doc_base_cnt`.`sklad` = '{$this->doc_data['sklad']}'
			WHERE `doc_list_pos`.`doc`='{$this->id}' AND `doc_base`.`pos_type`=0
			GROUP BY  `doc_base_kompl`.`kompl_id`
			ORDER BY `doc_list_pos`.`id`");
			while($nxt = $res->fetch_assoc()) {
				if($nxt['cnt'] > $nxt['sklad_cnt'])
					$need_cnt = $nxt['cnt'] - $nxt['sklad_cnt'];
				else	$need_cnt = 0;
			
				$db->insertA( 'doc_list_pos',  array('doc'=>$dd, 'tovar'=>$nxt['kompl_id'], 'cnt'=>$need_cnt, 'cost'=>$nxt['cost']));
			}
			
			
			header("Location: doc.php?mode=body&doc=$dd");
		}
	}

	/// Заявка на комплектующие
	/// @param to_str	Вернуть в виде строки (иначе - вывести в броузер)
	function PrintKompl($to_str = 0) {
		require('fpdf/fpdf_mc.php');
		global $tmpl, $CONFIG, $db;
		
		if (!$to_str)	$tmpl->ajax = 1;

		$pdf = new PDF_MC_Table('P');
		$pdf->Open();
		$pdf->SetAutoPageBreak(0, 10);
		$pdf->AddFont('Arial', '', 'arial.php');
		$pdf->tMargin = 5;
		$pdf->AddPage();
		$pdf->SetFont('Arial', '', 10);
		$pdf->SetFillColor(255);

		$dt = date("d.m.Y", $this->doc_data['date']);
		
		$pdf->SetFont('', '', 16);
		$str="Заявка на комплектующие к заявке на сборку\nN {$this->doc_data['altnum']}{$this->doc_data['subtype']} ({$this->id}), от $dt";
		$pdf->MultiCellIconv(0, 7, $str, 0, 'C');
		$pdf->Ln();

		$pdf->SetLineWidth(0.5);
		
		$t_width = array(10, 110, 20, 20, 20, 10);
		$t_text = array('№', 'Наименование', 'Заявка', 'Есть', 'Нужно', 'Ед.');
		$aligns = array('R', 'L', 'R', 'R', 'R', 'L');
		$pdf->SetFont('', '', 12);
		foreach($t_width as $id=>$w) {
			$pdf->CellIconv($w, 6, $t_text[$id], 1, 0, 'C', 0);
		}

		$pdf->Ln();
		$pdf->SetWidths($t_width);
		$pdf->SetAligns($aligns);
		$pdf->SetHeight(3.8);
		$pdf->SetLineWidth(0.2);
		$pdf->SetFont('', '', 8);

		$res = $db->query("SELECT `doc_base_kompl`.`kompl_id`, SUM(`doc_base_kompl`.`cnt`*`doc_list_pos`.`cnt`) AS `cnt`,
				`doc_group`.`printname` AS `group_printname`, `doc_base`.`name`, `doc_base`.`proizv` AS `vendor`, `doc_base`.`vc`,
				`class_unit`.`rus_name1` AS `units`, `doc_base_cnt`.`cnt` AS `sklad_cnt`
			FROM `doc_list_pos`
			LEFT JOIN `doc_base_kompl` ON `doc_base_kompl`.`pos_id` = `doc_list_pos`.`tovar`
			INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_base_kompl`.`kompl_id`
			INNER JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
			INNER JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
			LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id` = `doc_base_kompl`.`kompl_id` AND `doc_base_cnt`.`sklad` = '{$this->doc_data['sklad']}'
			WHERE `doc_list_pos`.`doc`='{$this->id}' AND `doc_base`.`pos_type`=0
			GROUP BY  `doc_base_kompl`.`kompl_id`
			ORDER BY `doc_list_pos`.`id`");
		$i = 0;
		$sum = $summass = 0;
		while ($nxt = $res->fetch_assoc()) {
			$i++;
			$name = '';
			if($nxt['vc']!=='' && @$CONFIG['poseditor']['vc'])
				$name .= $nxt['vc'].' ';
			if($nxt['group_printname']!=='')
				$name .= $nxt['group_printname'].' ';
			$name .= $nxt['name'];
			if($nxt['vendor']!=='' && !@$CONFIG['doc']['no_print_vendor'])
				$name .= ' ('.$nxt['vendor'].')';

			if ($pdf->h <= ($pdf->GetY() + 40 ))
				$pdf->AddPage();
			$cnt = sprintf("%0.3f", $nxt['cnt']);
			$sklad_cnt = sprintf("%0.3f", $nxt['sklad_cnt']);
			if($nxt['cnt'] > $nxt['sklad_cnt'])
				$need_cnt = sprintf("%0.3f", $nxt['cnt'] - $nxt['sklad_cnt']);
			else	$need_cnt = '0.000';
			
			$row = array($i, $name, $cnt, $sklad_cnt, $need_cnt, $nxt['units']);
			
			$pdf->RowIconv($row);
		}

		

		$res = $db->query("SELECT `worker_real_name`, `worker_phone`, `worker_email` FROM `users_worker_info` WHERE `user_id`='{$this->doc_data['user']}'");
		if ($res->num_rows) {
			$worker_info = $res->fetch_assoc();
			$pdf->SetAutoPageBreak(0, 10);
			$pdf->SetY($pdf->h - 18);
			$pdf->Ln(1);
			$pdf->SetFont('', '', 10);
			$str = "Отв. оператор " . $worker_info['worker_real_name'];
			$pdf->CellIconv(0, 4, $str, 0, 1, 'R', 0);
			$str = "Контактный телефон: " . $worker_info['worker_phone'];
			$pdf->CellIconv(0, 4, $str, 0, 1, 'R', 0);
			$str = "Электронная почта: " . $worker_info['worker_email'];
			$pdf->CellIconv(0, 4, $str, 0, 1, 'R', 0);
		} else {
			$pdf->SetAutoPageBreak(0, 10);
			$pdf->SetY($pdf->h - 12);
			$pdf->Ln(1);
			$pdf->SetFont('', '', 10);
			$str = "Login автора: " . $_SESSION['name'];
			$pdf->CellIconv(0, 4, $str, 0, 1, 'R', 0);
		}

		if ($to_str)
			return $pdf->Output('zayavka.pdf', 'S');
		else
			$pdf->Output('zayavka.pdf', 'I');
	}

}

;
?>