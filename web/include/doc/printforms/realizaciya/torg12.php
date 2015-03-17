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

namespace doc\printforms\realizaciya;

class torg12 extends \doc\printforms\iPrintForm {

    public function getName() {
        return "Товарная накладная ТОРГ-12";
    }

    protected function makeColRect($w_info, $start_y) {
        $this->pdf->SetLineWidth($this->line_bold_w);
        $this->pdf->Rect($w_info[2] + $this->pdf->lMargin, $start_y, $w_info[3] - $w_info[2], $this->pdf->GetY() - $start_y);
        $this->pdf->Rect($w_info[4] + $this->pdf->lMargin, $start_y, $w_info[12] - $w_info[4], $this->pdf->GetY() - $start_y);
        $this->pdf->Rect($w_info[13] + $this->pdf->lMargin, $start_y, $this->pdf->w - $this->pdf->rMargin - $this->pdf->lMargin - $w_info[13], $this->pdf->GetY() - $start_y);
        $this->pdf->SetLineWidth($this->line_normal_w);
    }
    
    /// Вывести суммарную информацию
    protected function makeSummary($text, $w_info, $line_height, $list_cnt, $list_sumbeznaloga, $list_sum, $list_sumnaloga, $list_summass) {
        $list_sumbeznaloga = sprintf("%01.2f", $list_sumbeznaloga);
        $list_sumnaloga = sprintf("%01.2f", $list_sumnaloga);
        $list_sum = sprintf("%01.2f", $list_sum);
        $list_summass = sprintf("%01.3f", $list_summass);

        $w = 0;
        for ($i = 0; $i < 7; $i++) {
            $w+=$w_info[$i];
        }
        $line_height = 3;
        $this->pdf->CellIconv($w, $line_height, $text, 0, 0, 'R', 1);
        $this->pdf->Cell($w_info[7], $line_height, '-', 1, 0, 'C', 1);
        $this->pdf->Cell($w_info[8], $line_height, $list_summass, 1, 0, 'R', 1);
        $this->pdf->Cell($w_info[9], $line_height, "$list_cnt / $list_summass", 1, 0, 'C', 1);

        $this->pdf->Cell($w_info[10], $line_height, '', 1, 0, 'C', 1);
        $this->pdf->Cell($w_info[11], $line_height, $list_sumbeznaloga, 1, 0, 'R', 1);
        $this->pdf->Cell($w_info[12], $line_height, "-", 1, 0, 'C', 1);
        $this->pdf->Cell($w_info[13], $line_height, $list_sumnaloga, 1, 0, 'R', 1);
        $this->pdf->Cell($w_info[14], $line_height, $list_sum, 1, 0, 'R', 1);
        $this->pdf->Ln();
    }

    // Вывод простого элемента блока подписей
    protected function makeSimpleItem($name, $value, $desc, $step, $microstep) {
        $this->pdf->SetFont('', '', 7);
        $this->pdf->CellIconv(50, $step, $name, 0, 0, 'L', 0);
        $this->pdf->CellIconv(0, $step, $value, 'B', 0, 'L', 0);
        $this->pdf->SetFont('', '', 5);
        $this->pdf->CellIconv(120, $microstep, $desc, 0, 1, 'C', 0);
        $this->pdf->SetFont('', '', 7);
    }
    
    // Вывод элемента *должность/подпись/фио*
    protected function makeDPFItem($name, $post, $fio, $step = 4, $microstep = 2) {
        $p1_w = array(30, 25, 2, 35, 2, 0);
        $this->pdf->SetFont('', '', 7);
        $this->pdf->CellIconv($p1_w[0], $step, $name, 0, 0, 'L', 0);
        $this->pdf->CellIconv($p1_w[1], $step, $post, 'B', 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[2], $step, '', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[3], $step, '', 'B', 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[4], $step, '', 0, 0, 'С', 0);
        $this->pdf->CellIconv($p1_w[5], $step, $fio, 'B', 1, 'C', 0);
        
        $this->pdf->SetFont('', '', 5);
        $this->pdf->CellIconv($p1_w[0], $microstep, '', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[1], $microstep, '(должность)', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[2], $microstep, '', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[3], $microstep, '(подпись)', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[4], $microstep, '',0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[5], $microstep, '(ф.и.о.)', 0, 1, 'C', 0);
        
    }
    
    // Вывод элемента *бухгалтер/подпись/фио*
    protected function makeBuxItem($fio, $step = 4, $microstep = 2) {
        $p1_w = array(55, 2, 35, 2, 0);
        $this->pdf->SetFont('', '', 7);
        $this->pdf->CellIconv($p1_w[0], $step, 'Главный (старший) бухгалтер', 0, 0, 'L', 0);
        $this->pdf->CellIconv($p1_w[1], $step, '', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[2], $step, '', 'B', 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[3], $step, '', 0, 0, 'С', 0);
        $this->pdf->CellIconv($p1_w[4], $step, $fio, 'B', 1, 'C', 0);
        
        $this->pdf->SetFont('', '', 5);
        $this->pdf->CellIconv($p1_w[0], $microstep, '', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[1], $microstep, '', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[2], $microstep, '(подпись)', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[3], $microstep, '',0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[4], $microstep, '(ф.и.о.)', 0, 1, 'C', 0);
    }
    
    // Вывод элемента *место для печати*
    protected function makeStampPlaceItem($step = 4) {
        $p1_w = array(30, 25, 2, 35, 2, 0);
        $this->pdf->SetFont('', '', 7);
        $this->pdf->CellIconv($p1_w[0], $step, 'М.П.', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[1], $step, '"_____"', 0, 0, 'R', 0);
        $this->pdf->CellIconv($p1_w[2], $step, '', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[3], $step, '', 'B', 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[4], $step, '', 0, 0, 'С', 0);
        $this->pdf->CellIconv($p1_w[5], $step, '20___г.', 0, 1, 'L', 0);
    }
    
    // Вывод элемента *пропись и цифра*
    protected function makePCItem($name, $value, $nvalue, $step = 4) {
        $p1_w = array(30, 64, 0);
        $this->pdf->SetFont('', '', 7);
        $this->pdf->CellIconv($p1_w[0], $step, $name, 0, 0, 'L', 0);
        $this->pdf->CellIconv($p1_w[1], $step, $value, 'B', 0, 'C', 0);
        $this->pdf->SetLineWidth($this->line_bold_w);
        $this->pdf->CellIconv($p1_w[2], $step, $nvalue, 1, 1, 'C', 0);
        $this->pdf->SetLineWidth($this->line_thin_w);
    }

    
    /// Сформировать данные печатной формы
    public function make() {
        global $db;
        $doc_data = $this->doc->getDocDataA();
        $dop_data = $this->doc->getDopDataA();
        $firm_vars = $this->doc->getFirmVarsA();
        $res = $db->query("SELECT `name`, `bik`, `rs`, `ks` FROM `doc_kassa` WHERE `ids`='bank' AND `num`='{$doc_data['bank']}'");
        $bank_info = $res->fetch_assoc();

        $this->pdf->AddPage('L');
        $y = $this->pdf->getY();
        $this->addInfoFooter();

        $this->pdf->setY($y);
        $this->pdf->SetFont('', '', 6);
        $str = 'Унифицированная форма ТОРГ-12 Утверждена постановлением госкомстата России от 25.12.98 № 132';
        $this->pdf->CellIconv(0, 4, $str, 0, 1, 'R');

        // Шапка с реквизитами
        $t2_y = $this->pdf->GetY();
        $this->pdf->SetFont('', '', 8);
        $str = $firm_vars['firm_gruzootpr'] . ", тел." . $firm_vars['firm_telefon'] . ", счёт " . $bank_info['rs'] . ", БИК " . $bank_info['bik'] . ", банк " . $bank_info['name'] . ", К/С {$bank_info['ks']}, адрес: {$firm_vars['firm_adres']}";
        $this->pdf->MultiCellIconv(230, 4, $str, 0, 'L');
        $y = $this->pdf->GetY();
        $this->pdf->Line(10, $this->pdf->GetY(), 230, $this->pdf->GetY());
        $this->pdf->SetFont('', '', 5);
        $str = "грузоотправитель, адрес, номер телефона, банковские реквизиты";
        $this->pdf->CellIconv(230, 2, $str, 0, 1, 'C');

        $this->pdf->SetFont('', '', 8);
        $this->pdf->Cell(0, 4, '', 0, 1, 'L');
        $this->pdf->Line(10, $this->pdf->GetY(), 230, $this->pdf->GetY());
        $this->pdf->SetFont('', '', 5);
        $str = "структурное подразделение";
        $this->pdf->CellIconv(220, 2, $str, 0, 1, 'C');

        $gruzop_info = $db->selectRow('doc_agent', $dop_data['gruzop']);
        $gruzop = '';
        if ($gruzop_info) {
            if ($gruzop_info['fullname']) {
                $gruzop.=$gruzop_info['fullname'];
            } else {
                $gruzop.=$gruzop_info['name'];
            }
            if ($gruzop_info['adres']) {
                $gruzop.=', адрес ' . $gruzop_info['adres'];
            }
            if ($gruzop_info['tel']) {
                $gruzop.=', тел. ' . $gruzop_info['tel'];
            }
            if ($gruzop_info['inn']) {
                $gruzop.=', ИНН ' . $gruzop_info['inn'];
            }
            if ($gruzop_info['kpp']) {
                $gruzop.=', КПП ' . $gruzop_info['kpp'];
            }
            if ($gruzop_info['okpo']) {
                $gruzop.=', ОКПО ' . $gruzop_info['okpo'];
            }
            if ($gruzop_info['okved']) {
                $gruzop.=', ОКВЭД ' . $gruzop_info['okved'];
            }
            if ($gruzop_info['rs']) {
                $gruzop.=', Р/С ' . $gruzop_info['rs'];
            }
            if ($gruzop_info['bank']) {
                $gruzop.=', в банке ' . $gruzop_info['bank'];
            }
            if ($gruzop_info['bik']) {
                $gruzop.=', БИК ' . $gruzop_info['bik'];
            }
            if ($gruzop_info['ks']) {
                $gruzop.=', К/С ' . $gruzop_info['ks'];
            }
        }

        $this->pdf->Ln(5);
        $this->pdf->SetFont('', '', 8);
        $str = "Грузополучатель";
        $this->pdf->CellIconv(30, 4, $str, 0, 0, 'L');
        $this->pdf->MultiCellIconv(190, 4, $gruzop, 0, 'L');
        $this->pdf->Line(40, $this->pdf->GetY(), 230, $this->pdf->GetY());

        $str = "Поставщик";
        $this->pdf->CellIconv(30, 4, $str, 0, 0, 'L');
        $str = "{$firm_vars['firm_name']}, {$firm_vars['firm_adres']}, ИНН/КПП {$firm_vars['firm_inn']}, К/С {$bank_info['ks']}, Р/С {$bank_info['rs']}, БИК {$bank_info['bik']}, в банке {$bank_info['name']}";
        $this->pdf->MultiCellIconv(190, 4, $str, 0, 'L');
        $this->pdf->Line(40, $this->pdf->GetY(), 230, $this->pdf->GetY());

        $platelshik_info = $db->selectRow('doc_agent', $dop_data['platelshik']);
        $platelshik = '';
        if ($platelshik_info) {
            if ($platelshik_info['fullname']) {
                $platelshik.=$platelshik_info['fullname'];
            } else {
                $platelshik.=$platelshik_info['name'];
            }
            if ($platelshik_info['adres']) {
                $platelshik.=', адрес ' . $platelshik_info['adres'];
            }
            if ($platelshik_info['tel']) {
                $platelshik.=', тел. ' . $platelshik_info['tel'];
            }
            if ($platelshik_info['inn']) {
                $platelshik.=', ИНН ' . $platelshik_info['inn'];
            }
            if ($platelshik_info['kpp']) {
                $platelshik.=', КПП ' . $platelshik_info['kpp'];
            }
            if ($platelshik_info['okpo']) {
                $platelshik.=', ОКПО ' . $platelshik_info['okpo'];
            }
            if ($platelshik_info['okved']) {
                $platelshik.=', ОКВЭД ' . $platelshik_info['okved'];
            }
            if ($platelshik_info['rs']) {
                $platelshik.=', Р/С ' . $platelshik_info['rs'];
            }
            if ($platelshik_info['bank']) {
                $platelshik.=', в банке ' . $platelshik_info['bank'];
            }
            if ($platelshik_info['bik']) {
                $platelshik.=', БИК ' . $platelshik_info['bik'];
            }
            if ($platelshik_info['ks']) {
                $platelshik.=', К/С ' . $platelshik_info['ks'];
            }
        }

        $str = "Плательщик";
        $this->pdf->CellIconv(30, 4, $str, 0, 0, 'L');
        $this->pdf->MultiCellIconv(190, 4, $platelshik, 0, 'L');
        $this->pdf->Line(40, $this->pdf->GetY(), 230, $this->pdf->GetY());

        $str_osn = "";
        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`date`
	FROM `doc_list`
	WHERE `doc_list`.`agent`='{$doc_data['agent']}' AND `doc_list`.`type`='14' AND `doc_list`.`ok`>'0'
	ORDER BY  `doc_list`.`date` DESC");
        if ($res->num_rows) {
            $nxt = $res->fetch_row();
            $str_osn.="Договор N$nxt[1] от " . date("d.m.Y", $nxt[2]) . ", ";
        }
        if ($doc_data['p_doc']) {
            $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`date`, `doc_list`.`p_doc`, `doc_list`.`type` FROM `doc_list`
		WHERE `id`={$doc_data['p_doc']}");
            if ($res->num_rows) {
                $nxt = $res->fetch_row();
                if ($nxt[4] == 1) {
                    $str_osn.="Счёт N$nxt[1] от " . date("d.m.Y", $nxt[2]) . ", ";
                } else if ($nxt[4] == 16) {
                    $str_osn.="Спецификация N$nxt[1] от " . date("d.m.Y", $nxt[2]) . ", ";
                }
                if ($nxt[3]) {
                    $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`date`, `doc_list`.`p_doc` FROM `doc_list`
				WHERE `id`={$nxt[3]} AND `doc_list`.`type`='16'");
                    if ($res->num_rows) {
                        $nxt = $res->fetch_row();
                        $str_osn.="Спецификация N$nxt[1] от " . date("d.m.Y", $nxt[2]) . ", ";
                    }
                }
            }
        }
        $str = "Основание";
        $this->pdf->CellIconv(30, 4, $str, 0, 0, 'L');
        $this->pdf->MultiCellIconv(190, 4, $str_osn, 0, 'L');
        $this->pdf->Line(40, $this->pdf->GetY(), 230, $this->pdf->GetY());
        $this->pdf->SetFont('', '', 5);
        $str = "договор, заказ-наряд";
        $this->pdf->CellIconv(220, 2, $str, 0, 1, 'C');

        // Правый столбик шапки
        $t3_y = $this->pdf->GetY();

        $set_x = 255;
        $width = 17;
        $this->pdf->SetFont('', '', 7);
        $this->pdf->SetY($t2_y);
        $set_x = $this->pdf->w - $this->pdf->rMargin - $width;

        $str = 'Коды';
        $this->pdf->SetX($set_x);
        $this->pdf->CellIconv($width, 4, $str, 1, 1, 'C');
        $set_x = $this->pdf->w - $this->pdf->rMargin - $width * 2;

        $tbt_y = $this->pdf->GetY();

        $lines = array('Форма по ОКУД', 'по ОКПО', 'Вид деятельности по ОКДП', 'по ОКПО', 'по ОКПО', 'по ОКПО');
        foreach ($lines as $str) {
            $this->pdf->SetX($set_x);
            $this->pdf->CellIconv($width, 4, $str, 0, 1, 'R');
        }
        $lines = array('Номер', 'Дата', 'Номер', 'Дата');
        foreach ($lines as $str) {
            $this->pdf->SetX($set_x);
            $this->pdf->CellIconv($width, 4, $str, 1, 1, 'R');
        }
        $str = 'Вид операции';
        $this->pdf->SetX($set_x);
        $this->pdf->CellIconv($width, 4, $str, 0, 1, 'R');

        $tbt_h = $this->pdf->GetY() - $tbt_y;
        $set_x = $this->pdf->w - $this->pdf->rMargin - $width;
        $this->pdf->SetY($tbt_y);
        $this->pdf->SetX($this->pdf->w - $this->pdf->rMargin - $width);
        $this->pdf->SetLineWidth($this->line_bold_w);
        $this->pdf->Cell($width, $tbt_h, '', 1, 1, 'R');
        $this->pdf->SetLineWidth($this->line_normal_w);

        $this->pdf->SetY($tbt_y);
        $lines = array('0330212', $firm_vars['firm_okpo'], '', $gruzop_info['okpo'], $firm_vars['firm_okpo'], $platelshik_info['okpo'], '', '', '', '');
        foreach ($lines as $str) {
            $this->pdf->SetX($set_x);
            $this->pdf->CellIconv($width, 4, $str, 1, 1, 'C');
        }

        // Название документа
        $this->pdf->SetY($tbt_y + 4 * 7 + 2);
        $this->pdf->SetX($this->pdf->w - $this->pdf->rMargin - $width * 3 - 3);
        $str = 'Транспортная накладная';
        $this->pdf->MultiCellIconv($width + 3, 6, $str, 0, 'R');

        $this->pdf->SetY($t3_y + 5);
        $this->pdf->SetX(40);
        $this->pdf->Cell(60, 4, '', 0, 0, 'R');
        $str = 'Номер документа';
        $this->pdf->CellIconv(25, 4, $str, 1, 0, 'C');
        $str = 'Дата составления';
        $this->pdf->CellIconv(25, 4, $str, 1, 1, 'C');
        $this->pdf->SetX(50);
        $this->pdf->SetLineWidth($this->line_bold_w);
        $this->pdf->SetFont('', '', 10);
        $str = 'ТОВАРНАЯ НАКЛАДНАЯ';
        $this->pdf->CellIconv(50, 4, $str, 0, 0, 'C');
        $this->pdf->SetFont('', '', 7);
        $this->pdf->Cell(25, 4, $doc_data['altnum'], 1, 0, 'C');
        $dt = date("d.m.Y", $doc_data['date']);
        $this->pdf->Cell(25, 4, $dt, 1, 1, 'C');
        $this->pdf->Ln(3);

        // Шапка таблицы
        $y = $this->pdf->GetY();
        $t_all_offset = array();
        $this->pdf->SetLineWidth($this->line_normal_w);
        $t_width = array(12, 85, 29, 14, 22, 14, 19, 16, 18, 29, 19);
        $t_ydelta = array(2, 1, 1, 3, 1, 5, 2, 5, 2, 1, 3);
        $t_text = array(
            'Номер по поряд- ку',
            'Товар',
            'Единица измерения',
            'Вид упаковки',
            'Количество',
            'Масса брутто',
            'Количе- ство (масса нетто)',
            'Цена, руб. коп.',
            'Сумма без учёта НДС, руб. коп',
            'НДС',
            'Сумма с учётом НДС, руб. коп.');

        foreach ($t_width as $w) {
            $this->pdf->Cell($w, 16, '', 1, 0, 'C', 0);
        }
        $this->pdf->Ln();
        $this->pdf->Ln(0.5);
        $this->pdf->SetFont('', '', 8);
        $offset = 0;
        foreach ($t_width as $i => $w) {
            $t_all_offset[$offset] = $offset;
            $this->pdf->SetY($y + $t_ydelta[$i] + 0.2);
            $this->pdf->SetX($offset + $this->pdf->lMargin);
            $this->pdf->MultiCellIconv($w, 3, $t_text[$i], 0, 'C', 0);
            $offset+=$w;
        }

        $t2_width = array(68, 17, 15, 14, 11, 11, 15, 14);
        $t2_start = array(1, 1, 2, 2, 4, 4, 9, 9);
        $t2_ydelta = array(4, 4, 2, 2, 1, 3, 3, 3);
        $t2_text = array(
            'наименование, характеристика, сорт, артикул товара',
            'код',
            'наимено- вание',
            'код по ОКЕИ',
            'в одном месте',
            'мест, штук',
            'ставка %',
            'сумма');
        $offset = 0;
        $c_id = 0;
        $old_col = 0;
        $y+=5;

        foreach ($t2_width as $i => $w2) {
            while ($c_id < $t2_start[$i]) {
                $offset+=$t_width[$c_id++];
            }

            if ($old_col == $t2_start[$i]) {
                $off2+=$t2_width[$i - 1];
            } else {
                $off2 = 0;
            }
            $old_col = $t2_start[$i];
            $t_all_offset[$offset + $off2] = $offset + $off2;
            $this->pdf->SetY($y);
            $this->pdf->SetX($offset + $off2 + $this->pdf->lMargin);
            $this->pdf->Cell($w2, 11, '', 1, 0, 'C', 0);

            $this->pdf->SetY($y + $t2_ydelta[$i]);
            $this->pdf->SetX($offset + $off2 + $this->pdf->lMargin);
            $this->pdf->MultiCellIconv($w2, 3, $t2_text[$i], 0, 'C', 0);
        }

        sort($t_all_offset, SORT_NUMERIC);
        $this->pdf->SetY($y + 11);
        $t_all_width = array();
        $old_offset = 0;
        foreach ($t_all_offset as $offset) {
            if ($offset == 0) {
                continue;
            }
            $t_all_width[] = $offset - $old_offset;
            $old_offset = $offset;
        }
        $t_all_width[] = 19;
        $i = 1;
        foreach ($t_all_width as $w) {
            $this->pdf->Cell($w, 4, $i, 1, 0, 'C', 0);
            $i++;
        }
        $this->pdf->Ln();
        

        // тело таблицы
        $y = $this->pdf->GetY();
        $nomenclature = $this->doc->getDocumentNomenclatureWVAT();

        $this->pdf->SetWidths($t_all_width);
        $font_sizes = array(0 => 7);
        $this->pdf->SetFSizes($font_sizes);
        $this->pdf->SetHeight(3.5);

        $aligns = array('R', 'L', 'L', 'C', 'C', 'C', 'C', 'C', 'R', 'R', 'R', 'R', 'R', 'R', 'R');
        $this->pdf->SetAligns($aligns);
        $this->pdf->SetFillColor(255, 255, 255);
        $i = 1;
        $summass = $sum = $sumnaloga = $sumbeznaloga = $cnt = 0;
        $list_summass = $list_sum = $list_sumnaloga = $list_sumbeznaloga = $list_cnt = 0;
        foreach ($nomenclature as $line) {
            $sumbeznaloga += $line['sum'];
            $list_sumbeznaloga += $line['sum'];
            $sum += $line['sum_all'];
            $list_sum += $line['sum_all'];
            $sumnaloga += $line['vat_s'];
            $list_sumnaloga += $line['vat_s'];
            $summass += $line['mass']*$line['cnt'];
            $list_summass += $line['mass']*$line['cnt'];
            $cnt += $line['cnt'];
            $list_cnt += $line['cnt'];

            $row = array(
                $i++,
                $line['name'],
                $line['code'],
                $line['unit_name'],
                $line['unit_code'],
                '-',
                '-',
                '-',
                sprintf("%01.3f", $line['mass']*$line['cnt']),
                $line['cnt'],
                sprintf("%01.2f", $line['price']),
                sprintf("%01.2f", $line['sum']),
                $line['vat_p'],
                sprintf("%01.2f", $line['vat_s']),
                sprintf("%01.2f", $line['sum_all']),
            );
            $this->pdf->RowIconv($row);

            if ($this->pdf->GetY() > 190) {
                $this->makeColRect($t_all_offset, $y);
                $this->makeSummary('Всего:', $t_all_width, 3.5, $list_cnt, $list_sumbeznaloga, $list_sum, $list_sumnaloga, $list_summass);
                $this->pdf->AddPage('L');
                $this->addInfoFooter();
                $y = $this->pdf->GetY();
                $list_summass = $list_sum = $list_sumnaloga = 0;
            }
        }
        
        $this->makeColRect($t_all_offset, $y);        
        $this->makeSummary('Всего:', $t_all_width, 3.5, $list_cnt, $list_sumbeznaloga, $list_sum, $list_sumnaloga, $list_summass);
        $this->makeSummary('Итого по накладной:', $t_all_width, 3.5, $cnt, $sumbeznaloga, $sum, $sumnaloga, $summass);

        // Контроль расстояния до конца листа
        $workspace_h = $this->pdf->h - $this->pdf->bMargin - $this->pdf->tMargin;
        if ($workspace_h <= $this->pdf->GetY() + 61) {
            $this->pdf->AddPage('L');
            $this->addInfoFooter();
        }
        $this->pdf->SetAutoPageBreak(0);
       
        // Подписи
        $step = 3.5;
        $microstep = 3;
        $this->pdf->SetLineWidth($this->line_thin_w);
        $lsy = $this->pdf->GetY();
        $old_r_margin = $this->pdf->rMargin;
        $this->pdf->rMargin = 160;
        
        $this->makeSimpleItem('Всего мест', '', '', $step, $microstep);
        $this->makeSimpleItem('Приложения (паспорта, сертификаты) на ', '', '', $step, $microstep);
        $str = "Всего отпущено " . num2str($cnt, 'sht', 0) . " наименований на сумму " . num2str($sum);
        $this->pdf->MultiCellIconv(0, $step, $str ,0,'L',0);
        
        $this->makeDPFItem('Отпуск разрешил', '', '', $step, $microstep);
        $this->makeBuxItem('', $step, $microstep);
        $this->makeDPFItem('Отпуск груза произвёл', '', '', $step, $microstep);
        $this->pdf->ln();
        $this->makeStampPlaceItem($step);
        
        $this->pdf->SetLineWidth($this->line_bold_w);
        $this->pdf->Line(140, $this->pdf->GetY() + 2, 140, $lsy);
        $this->pdf->SetLineWidth($this->line_thin_w);
        $this->pdf->rMargin = $old_r_margin;
        $this->pdf->SetY($lsy);
        $this->pdf->lMargin = 145;
        $this->pdf->ln();
        
        $this->makePCItem('Масса груза (нетто)', '', $summass, $step);        
        $this->makePCItem('Масса груза (брутто)', '', '', $step);
        
        if (isset($dop_data['dov_agent'])) {
            $dov_data = $db->selectRow('doc_agent_dov', $dop_data['dov_agent']);
            if ($dov_data) {
                $dov_agn = $dov_data['surname'] . ' ' . $dov_data['name'] . ' ' . $dov_data['name2'];
                $dov_agr = $dov_data['range'];
            } else {
                $dov_agn = $dov_agr = "";
            }
        }
        else {
            $dov_agn = $dov_agr = "";
        }
        $this->makeSimpleItem('По доверенности №', $dop_data['dov']." от ".$dop_data['dov_data'], 'кем, кому (организация, должность, фамилия и. о.)', $step, $microstep);
        $this->makeSimpleItem('Выданной', $dov_agr.' '.$dov_agn, 'кем, кому (организация, должность, фамилия и. о.)', $step, $microstep);
        $this->makeDPFItem('Груз принял', '', '', $step, $microstep);
        $this->makeDPFItem('Груз получил грузополучатель', '', '', $step, $microstep);
        $this->makeStampPlaceItem($step);
    }

}
