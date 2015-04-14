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
/// Документ *Поступление*
class doc_Postuplenie extends doc_Nulltype {

	// Создание нового документа или редактирование заголовка старого
	function __construct($doc = 0) {
		parent::__construct($doc);
		$this->doc_type = 1;
		$this->doc_name = 'postuplenie';
		$this->doc_viewname = 'Поступление товара на склад';
		$this->sklad_editor_enable = true;
		$this->sklad_modify = 1;
		$this->header_fields = 'sklad cena separator agent';
		$this->PDFForms = array(
		    array('name' => 'blading', 'desc' => 'Накладная', 'method' => 'PrintNaklPDF')
		);
	}
	
	function initDefDopdata() {
		$this->def_dop_data = array('kladovshik'=>$this->firm_vars['firm_kladovshik_id'], 'input_doc'=>'', 'input_date'=>'', 'return'=>0, 'cena'=>1);
	}

	function dopHead() {
		global $tmpl, $db;
		$klad_id = $this->dop_data['kladovshik'];
		if (!$klad_id)
			$klad_id = $this->firm_vars['firm_kladovshik_id'];
                $tmpl->addContent("<hr>");
		$tmpl->addContent("Ном. вх. документа:<br><input type='text' name='input_doc' value='{$this->dop_data['input_doc']}'><br>");
                $tmpl->addContent("Дата. вх. документа:<br><input type='text' name='input_date' value='{$this->dop_data['input_date']}'><br>");
		$checked = $this->dop_data['return'] ? 'checked' : '';
		$tmpl->addContent("<label><input type='checkbox' name='return' value='1' $checked>Возвратный документ</label><hr>
		Кладовщик:<br><select name='kladovshik'>
		<option value='0'>--не выбран--</option>");
		$res = $db->query("SELECT `user_id`, `worker_real_name` FROM `users_worker_info` WHERE `worker`='1' ORDER BY `worker_real_name`");
		while ($nxt = $res->fetch_row()) {
			$s = ($klad_id == $nxt[0]) ? 'selected' : '';
			$tmpl->addContent("<option value='$nxt[0]' $s>".html_out($nxt[1])."</option>");
		}
		$tmpl->addContent("</select><br>");
	}

	function dopSave() {
		$new_data = array(
		    'input_doc' => request('input_doc'),
                    'input_date'=> rcvdate('input_date'),
		    'return' => rcvint('return'),
		    'kladovshik' => rcvint('kladovshik')
		);
		$old_data = array_intersect_key($new_data, $this->dop_data);

		$log_data = '';
		if ($this->doc)
			$log_data = getCompareStr($old_data, $new_data);
		$this->setDopDataA($new_data);
		if ($log_data)
			doc_log("UPDATE {$this->doc_name}", $log_data, 'doc', $this->doc);
	}

    public function docApply($silent = 0) {
        global $CONFIG, $db;
        if(!$this->isAltNumUnique() && !$silent) {
            throw new Exception("Номер документа не уникален!");
        }
        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`, `doc_list`.`firm_id`,
                `doc_sklady`.`dnc`, `doc_sklady`.`firm_id` AS `store_firm_id`, `doc_vars`.`firm_store_lock`
            FROM `doc_list`
            INNER JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
            INNER JOIN `doc_vars` ON `doc_list`.`firm_id` = `doc_vars`.`id`
            WHERE `doc_list`.`id`='{$this->doc}'");
        $doc_params = $res->fetch_assoc();
        $res->free();
        
        if (!$doc_params) {
            throw new Exception('Документ ' . $this->doc . ' не найден');
        }
        if ($doc_params['ok'] && (!$silent)) {
            throw new Exception('Документ уже проведён!');
        }
        
        // Запрет на списание со склада другой фирмы
        if($doc_params['store_firm_id']!=null && $doc_params['store_firm_id']!=$doc_params['firm_id']) {
            throw new Exception("Выбранный склад принадлежит другой организации!");
        }
        // Ограничение фирмы списком своих складов
        if($doc_params['firm_store_lock'] && $doc_params['store_firm_id']!=$doc_params['firm_id']) {
            throw new Exception("Выбранная организация может работать только со своими складами!");
        }
        
        $res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base`.`pos_type`, `doc_list_pos`.`id`, `doc_list_pos`.`cost`, `doc_base`.`cost`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		WHERE `doc_list_pos`.`doc`='{$this->doc}' AND `doc_base`.`pos_type`='0'");
        while ($nxt = $res->fetch_row()) {
            $db->query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`+'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='{$doc_params['sklad']}'");
            // Если это первое поступление
            if ($db->affected_rows == 0) {
                $db->query("INSERT INTO `doc_base_cnt` (`id`, `sklad`, `cnt`) VALUES ('$nxt[0]', '{$doc_params['sklad']}', '$nxt[1]')");
            }
            if (@$CONFIG['poseditor']['sn_restrict']) {
                $r = $db->query("SELECT COUNT(`doc_list_sn`.`id`) FROM `doc_list_sn` WHERE `prix_list_pos`='$nxt[3]'");
                $sn_data = $r->fetch_row();
                if ($sn_data[0] != $nxt[1]) {
                    throw new Exception("Количество серийных номеров товара $nxt[0] ($nxt[1]) не соответствует количеству серийных номеров ($sn_data[0])");
                }
            }
            if (@$CONFIG['doc']['update_in_cost'] == 1 && (!$silent)) {
                if ($nxt[4] != $nxt[5]) {
                    $db->query("UPDATE `doc_base` SET `cost`='$nxt[4]', `cost_date`=NOW() WHERE `id`='$nxt[0]'");
                    doc_log("UPDATE", "cost:($nxt[4] => $nxt[5])", 'pos', $nxt[0]);
                }
            }
        }
        if ($silent) {
            return;
        }
        $db->update('doc_list', $this->doc, 'ok', time());

        if (@$CONFIG['doc']['update_in_cost'] == 2) {
            $res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base`.`pos_type`, `doc_list_pos`.`id`, `doc_list_pos`.`cost`, `doc_base`.`cost`
			FROM `doc_list_pos`
			LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
			WHERE `doc_list_pos`.`doc`='{$this->doc}' AND `doc_base`.`pos_type`='0'");
            while ($nxt = $res->fetch_row()) {
                $acp = getInCost($nxt[0], $doc_params['date']);
                if ($nxt[5] != $acp) {
                    $db->query("UPDATE `doc_base` SET `cost`='$acp', `cost_date`=NOW() WHERE `id`='$nxt[0]'");
                    doc_log("UPDATE", "cost:($nxt[4] => $acp)", 'pos', $nxt[0]);
                }
            }
        }
        $this->sentZEvent('apply');
    }

        function docCancel() {
		global $db;
		$rs = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`, `doc_sklady`.`dnc`
		FROM `doc_list`
		LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
		WHERE `doc_list`.`id`='{$this->doc}'");
		if(! $rs->num_rows)
			throw new Exception("Документ {$this->doc} не найден!");
		$nx = $rs->fetch_assoc();
		if (!$nx['ok'])
			throw new Exception("Документ ещё не проведён!");

		$db->update('doc_list', $this->doc, 'ok', 0 );

		$res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`pos_type`, `doc_base`.`vc`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$nx['sklad']}'
		WHERE `doc_list_pos`.`doc`='{$this->doc}'");
		while ($nxt = $res->fetch_row()) {
			if ($nxt[5] == 0) {
				if (!$nx['dnc']) {
					if ($nxt[1] > $nxt[2]) {
						$budet = $nxt[2] - $nxt[1];
						$badpos = $nxt[0];
						throw new Exception("Невозможно, т.к. будет недостаточно ($budet) товара '$nxt[3]:$nxt[4] - $nxt[6]($nxt[0])' на складе!");
					}
				}
				$db->query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`-'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='{$nx['sklad']}'");
				if (!$nx['dnc']) {
					$budet = getStoreCntOnDate($nxt[0], $nx['sklad']);
					if ($budet < 0) {
						$badpos = $nxt[0];
						throw new Exception("Невозможно, т.к. будет недостаточно ($budet) товара '$nxt[3]:$nxt[4] - $nxt[6]($nxt[0])' !");
					}
				}
			}
		}
		$this->sentZEvent('cancel');
	}

	// Формирование другого документа на основании текущего
	function morphTo($target_type) {
		global $tmpl, $db;
		if ($target_type == '') {
			$tmpl->ajax = 1;
			$tmpl->addContent("<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=2'\">Реализация</div>");
                        $tmpl->addContent("<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=5'\">Расходный банковский ордер</div>");
			$tmpl->addContent("<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=7'\">Расходный кассовый ордер</div>");
		}
		else if ($target_type == 2) {
			if (!isAccess('doc_realizaciya', 'create'))
				throw new AccessException();
			$db->startTransaction();
			$new_doc = new doc_Realizaciya();
			$dd = $new_doc->createFromP($this);
			$db->commit();
			header("Location: doc.php?mode=body&doc=$dd");
		}
                else if ($target_type == 5) {
			if (!isAccess('doc_rbank', 'create'))
				throw new AccessException();
			$this->recalcSum();
			$db->startTransaction();
			$new_doc = new doc_RBank();
			$doc_num = $new_doc->createFrom($this);
			// Вид расхода - закуп товара на продажу
			$new_doc->setDopData('rasxodi', 6);
			$db->commit();
			header('Location: doc.php?mode=body&doc='.$doc_num);
		}
		else if ($target_type == 7) {
			if (!isAccess('doc_rko', 'create'))
				throw new AccessException();
			$this->recalcSum();
			$db->startTransaction();
			$new_doc = new doc_Rko();
			$doc_num = $new_doc->createFrom($this);
			// Вид расхода - закуп товара на продажу
			$new_doc->setDopData('rasxodi', 6);
			$db->commit();
			header('Location: doc.php?mode=body&doc='.$doc_num);
		}
	}

/// Обычная накладная в PDF формате
/// @param to_str Вернуть строку, содержащую данные документа (в противном случае - отправить файлом)
	function printNaklPDF($to_str = false) {
		global $tmpl, $CONFIG, $db;

		if (!$to_str)
			$tmpl->ajax = 1;
		
		require('fpdf/fpdf_mc.php');
		$pdf = new PDF_MC_Table('P');
		$pdf->Open();
		$pdf->SetAutoPageBreak(0, 10);
		$pdf->AddFont('Arial', '', 'arial.php');
		$pdf->tMargin = 10;
		$pdf->AddPage();
		$pdf->SetFont('Arial', '', 10);
		$pdf->SetFillColor(255);

		$dt = date("d.m.Y", $this->doc_data['date']);

		$pdf->SetFont('', '', 16);
		if(!$this->dop_data['return']) {
                    $str = "Накладная N {$this->doc_data['altnum']}{$this->doc_data['subtype']} ({$this->doc}), от $dt";
                } else {
                    $str = "Возврат от покупателя N {$this->doc_data['altnum']}{$this->doc_data['subtype']} ({$this->doc}), от $dt";
                }
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0, 8, $str, 0, 1, 'C', 0);
		$pdf->SetFont('', '', 10);
		$str = "Поставщик: {$this->doc_data['agent_name']}";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0, 5, $str, 0, 1, 'L', 0);
		$str = "Покупатель: {$this->firm_vars['firm_name']}, тел: {$this->firm_vars['firm_telefon']}";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0, 5, $str, 0, 1, 'L', 0);
		$pdf->Ln();

		$pdf->SetLineWidth(0.5);
		$t_width = array(8);
		if ($CONFIG['poseditor']['vc']) {
			$t_width[] = 20;
			$t_width[] = 91;
		}
		else
			$t_width[] = 111;
		$t_width = array_merge($t_width, array(12, 15, 23, 23));

		$t_text = array('№');
		if ($CONFIG['poseditor']['vc']) {
			$t_text[] = 'Код';
			$t_text[] = 'Наименование';
		}
		else
			$t_text[] = 'Наименование';
		$t_text = array_merge($t_text, array('Место', 'Кол-во', 'Стоимость', 'Сумма'));

		foreach ($t_width as $id => $w) {
			$str = iconv('UTF-8', 'windows-1251', $t_text[$id]);
			$pdf->Cell($w, 6, $str, 1, 0, 'C', 0);
		}
		$pdf->Ln();
		$pdf->SetWidths($t_width);
		$pdf->SetHeight(3.8);

		$aligns = array('R');
		if ($CONFIG['poseditor']['vc']) {
			$aligns[] = 'L';
			$aligns[] = 'L';
		}
		else
			$aligns[] = 'L';
		$aligns = array_merge($aligns, array('C', 'R', 'R', 'R'));

		$pdf->SetAligns($aligns);
		$pdf->SetLineWidth(0.2);
		$pdf->SetFont('', '', 8);

		$res = $db->query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`mesto`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`id`, `doc_base`.`vc`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->doc_data['sklad']}'
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		$ii = 1;
		$sum = 0;
		while ($nxt = $res->fetch_row()) {
			$sm = $nxt[3] * $nxt[4];
			$cost = sprintf("%01.2f руб.", $nxt[4]);
			$cost2 = sprintf("%01.2f руб.", $sm);
			if (!@$CONFIG['doc']['no_print_vendor'] && $nxt[2])
				$nxt[1].=' / ' . $nxt[2];

			$row = array($ii);
			if ($CONFIG['poseditor']['vc']) {
				$row[] = $nxt[8];
				$row[] = "$nxt[0] $nxt[1]";
			}
			else
				$row[] = "$nxt[0] $nxt[1]";
			$row = array_merge($row, array($nxt[5], "$nxt[3] $nxt[6]", $cost, $cost2));

			$pdf->RowIconv($row);
			$ii++;
			$sum+=$sm;
		}
		$ii--;
		$cost = sprintf("%01.2f руб.", $sum);

		$pdf->Ln();

		$str = "Всего $ii наименований на сумму $cost";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0, 5, $str, 0, 1, 'L', 0);

		$str = "Товар получил, претензий к качеству товара и внешнему виду не имею.";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0, 5, $str, 0, 1, 'L', 0);
		$str = "Покупатель: ____________________________________";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0, 5, $str, 0, 1, 'L', 0);
		$str = "Поставщик:_____________________________________";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0, 5, $str, 0, 1, 'L', 0);

		if ($to_str)
			return $pdf->Output('blading.pdf', 'S');
		else
			$pdf->Output('blading.pdf', 'I');
	}

}
