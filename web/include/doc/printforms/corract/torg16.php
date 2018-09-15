<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2018, BlackLight, TND Team, http://tndproject.org
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

namespace doc\printforms\corract;

class torg16 extends \doc\printforms\iPrintFormPdf {

    public function getName() {
        return "Акт списания";
    }

    protected function makeColRect($w_info, $start_y) {
        $this->pdf->SetLineWidth($this->line_bold_w);
        $this->pdf->Rect($w_info[2] + $this->pdf->lMargin, $start_y, $w_info[3] - $w_info[2], $this->pdf->GetY() - $start_y);
        //$this->pdf->Rect($w_info[4] + $this->pdf->lMargin, $start_y, $w_info[12] - $w_info[4], $this->pdf->GetY() - $start_y);
        //$this->pdf->Rect($w_info[13] + $this->pdf->lMargin, $start_y, $this->pdf->w - $this->pdf->rMargin - $this->pdf->lMargin - $w_info[13], $this->pdf->GetY() - $start_y);
        $this->pdf->SetLineWidth($this->line_normal_w);
    }
    
    /// Вывести суммарную информацию
    protected function makeSummary($text, $w_info, $line_height, $list_sum) {
        $list_sum = sprintf("%01.2f", $list_sum);

        $w = 0;
        for ($i = 0; $i <9; $i++) {
            $w+=$w_info[$i];
        }
        $line_height = 3;
        $this->pdf->CellIconv($w, $line_height, $text, 0, 0, 'R', 1);
        $this->pdf->Cell($w_info[9], $line_height, $list_sum, 1, 0, 'R', 1);
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
        $p1_w = array(50, 45, 2, 45, 2, 45);
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

        $this->addPage('L');
        $y = $this->pdf->getY();        

        $this->pdf->setY($y);
        $this->pdf->SetFont('', '', 6);
        $str = 'Унифицированная форма ТОРГ-16 Утверждена постановлением госкомстата России от 25.12.98 № 132';
        $this->pdf->CellIconv(0, 4, $str, 0, 1, 'R');

        // Шапка с реквизитами
        $t2_y = $this->pdf->GetY();
        $this->pdf->SetFont('', '', 8);
        $str = $firm_vars['firm_name'] . ", тел." . $firm_vars['firm_telefon'] . ", ИНН " . $firm_vars['firm_inn'] . ", счёт " . $bank_info['rs'] . ", БИК " . $bank_info['bik'] . ", банк " . $bank_info['name'] . ", К/С {$bank_info['ks']}, адрес: {$firm_vars['firm_adres']}";
        $this->pdf->MultiCellIconv(230, 4, $str, 0, 'L');
        $y = $this->pdf->GetY();
        $this->pdf->Line(10, $this->pdf->GetY(), 230, $this->pdf->GetY());
        $this->pdf->SetFont('', '', 5);
        $str = "организация";
        $this->pdf->CellIconv(230, 2, $str, 0, 1, 'C');

        $this->pdf->SetFont('', '', 8);
        $this->pdf->Cell(0, 4, '', 0, 1, 'L');
        $this->pdf->Line(10, $this->pdf->GetY(), 230, $this->pdf->GetY());
        $this->pdf->SetFont('', '', 5);
        $str = "структурное подразделение";
        $this->pdf->CellIconv(220, 2, $str, 0, 1, 'C');
        $this->pdf->ln(8);
        $str = "Основание";
        $this->pdf->CellIconv(30, 4, $str, 0, 0, 'L');
        $this->pdf->MultiCellIconv(190, 4, 'Приказ', 0, 'L');
        $this->pdf->Line(40, $this->pdf->GetY(), 230, $this->pdf->GetY());
        $this->pdf->SetFont('', '', 5);
        $str = "ненужное зачеркнуть";
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

        $lines = array('Форма по ОКУД', 'по ОКПО', '', 'Вид деятельности по ОКДП');
        foreach ($lines as $str) {
            $this->pdf->SetX($set_x);
            $this->pdf->CellIconv($width, 4, $str, 0, 1, 'R');
        }
        $lines = array('Номер', 'Дата');
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
        $lines = array('0330216', $firm_vars['firm_okpo'], '98707889', '', '', '', '');
        foreach ($lines as $str) {
            $this->pdf->SetX($set_x);
            $this->pdf->CellIconv($width, 4, $str, 1, 1, 'C');
        }

        // Название документа

        $this->pdf->SetY($t3_y + 5);
        $this->pdf->SetX(40);
        $this->pdf->Cell(60, 4, '', 0, 0, 'R');
        $str = 'Номер документа';
        $this->pdf->CellIconv(25, 4, $str, 1, 0, 'C');
        $str = 'Дата составления';
        $this->pdf->CellIconv(25, 4, $str, 1, 1, 'C');
        $this->pdf->SetX(40);
        $this->pdf->SetLineWidth($this->line_bold_w);
        $this->pdf->SetFont('', '', 10);
        $str = 'АКТ О СПИСАНИИ ТОВАРОВ';
        $this->pdf->CellIconv(60, 4, $str, 0, 0, 'C');
        $this->pdf->SetFont('', '', 7);
        $this->pdf->Cell(25, 4, $doc_data['altnum'], 1, 0, 'C');
        $dt = date("d.m.Y", $doc_data['date']);
        $this->pdf->Cell(25, 4, $dt, 1, 1, 'C');
        $this->pdf->Ln(3);
        
        // Таблица 1
        $y = $this->pdf->GetY();
        $t_all_offset = array();
        $this->pdf->SetLineWidth($this->line_normal_w);
        $t_width = array(60, 97, 120);
        $t_text = array(
            'Дата',
            'Товарная накладная',
            'Признаки понижения качества (причины списания)',
            );

        foreach ($t_width as $w) {
            $this->pdf->Cell($w, 10, '', 1, 0, 'C', 0);
        }
        $this->pdf->Ln();
        $this->pdf->Ln(0.5);
        $this->pdf->SetFont('', '', 8);
        $offset = 0;
        foreach ($t_width as $i => $w) {
            $t_all_offset[$offset] = $offset;
            $this->pdf->SetY($y + 1.2);
            $this->pdf->SetX($offset + $this->pdf->lMargin);
            $this->pdf->MultiCellIconv($w, 3, $t_text[$i], 0, 'C', 0);
            $offset+=$w;
        }
        $t2_width = array(30, 30, 50, 47, 100, 20);
        $t2_start = array(0, 0, 1, 1, 2, 2);
        $t2_text = array(
            'поступления товара',
            'списания товара',
            'номер',
            'дата',
            'наименование',
            'код',
            );
        $offset = 0;
        $c_id = 0;
        $old_col = 0;
        $y+=5;

        foreach ($t2_width as $i => $w2) {
            while ($c_id < $t2_start[$i]) {
                $offset+=$t_width[$c_id++];
            }

            if ($old_col == $t2_start[$i] && $i>0) {
                $off2+=$t2_width[$i - 1];
            } else {
                $off2 = 0;
            }
            $old_col = $t2_start[$i];
            $t_all_offset[$offset + $off2] = $offset + $off2;
            $this->pdf->SetY($y);
            $this->pdf->SetX($offset + $off2 + $this->pdf->lMargin);
            $this->pdf->Cell($w2, 5, '', 1, 0, 'C', 0);

            $this->pdf->SetY($y+1);
            $this->pdf->SetX($offset + $off2 + $this->pdf->lMargin);
            $this->pdf->MultiCellIconv($w2, 3, $t2_text[$i], 0, 'C', 0);
        }
        sort($t_all_offset, SORT_NUMERIC);
        $this->pdf->SetY($y + 5);
        $t_all_width = array();
        $old_offset = 0;
        foreach ($t_all_offset as $offset) {
            if ($offset == 0) {
                continue;
            }
            $t_all_width[] = $offset - $old_offset;
            $old_offset = $offset;
        }
        $t_all_width[] = 20;
        $i = 1;
        foreach ($t_all_width as $w) {
            $this->pdf->Cell($w, 4, $i, 1, 0, 'C', 0);
            $i++;
        }
        $this->pdf->Ln();
        
        $this->pdf->SetWidths($t_all_width);
        $font_sizes = array(0 => 7);
        $this->pdf->SetFSizes($font_sizes);
        $this->pdf->SetHeight(3.5);

        $aligns = array('C', 'C', 'C', 'C', 'C', 'C');
        $this->pdf->SetAligns($aligns);
        $this->pdf->SetFillColor(255, 255, 255);
        
        $nomenclature = $this->doc->getDocumentNomenclature();
        foreach ($nomenclature as $line) {
            if($line['cnt']>0) {
                continue;
            }
            $row = array(
                '',
                $dt,
                '',
                '',
                '',
                '',
            );
            $this->pdf->RowIconv($row);
            $this->controlPageBreak(30, 'L');
        }
        $this->pdf->ln(5);
        
        
        
        

        // Шапка таблицы
        $y = $this->pdf->GetY();
        $t_all_offset = array();
        $this->pdf->SetLineWidth($this->line_normal_w);
        $t_width = array(120, 36, 15, 26, 20, 24, 36);
        $t_ydelta = array(1, 1, 5, 1, 4, 4, 5);
        $t_text = array(
            'Товар',
            'Единица измерения',
            'Количество',
            'Масса',
            'Цена, руб. коп.',
            'Стоимость, руб. коп',
            'Примечание'
        );

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

        $t2_width = array(10, 90, 20, 18, 18, 15, 11);
        $t2_start = array(0, 0, 0, 1, 1, 3, 3);
        $t2_ydelta = array(4, 4, 4, 3, 3, 1, 4);
        $t2_text = array(
            'N п/п',
            'наименование',
            'артикул',
            'наимено- вание',
            'код по ОКЕИ',
            'одного места (штуки)',
            'нетто',
            );
        $offset = 0;
        $c_id = 0;
        $old_col = 0;
        $y+=5;

        foreach ($t2_width as $i => $w2) {
            while ($c_id < $t2_start[$i]) {
                $offset+=$t_width[$c_id++];
            }

            if ($old_col == $t2_start[$i] && $i>0) {
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
        $t_all_width[] = 36;
        $i = 1;
        foreach ($t_all_width as $w) {
            $this->pdf->Cell($w, 4, $i, 1, 0, 'C', 0);
            $i++;
        }
        $this->pdf->Ln();
        

        // тело таблицы
        $y = $this->pdf->GetY();
        $nomenclature = $this->doc->getDocumentNomenclature('comment');

        $this->pdf->SetWidths($t_all_width);
        $font_sizes = array(0 => 7);
        $this->pdf->SetFSizes($font_sizes);
        $this->pdf->SetHeight(3.5);

        $aligns = array('R', 'L', 'L', 'C', 'C', 'C', 'C', 'C', 'R', 'R', 'R', 'R', 'R', 'R', 'R');
        $this->pdf->SetAligns($aligns);
        $this->pdf->SetFillColor(255, 255, 255);
        $i = 1;
        $sum = 0;
        $list_summass = $list_sum = $list_sumnaloga = $list_sumbeznaloga = $list_cnt = 0;
        foreach ($nomenclature as $line) {
            if($line['cnt']>0) {
                continue;
            }
            $line['cnt'] = abs($line['cnt']);
            $line['sum'] = abs($line['sum']);
            $line['mass'] = abs($line['mass']);

            $row = array(
                $i++,
                $line['name'],
                $line['code'],
                $line['unit_name'],
                $line['unit_code'],
                $line['cnt'],
                sprintf("%01.3f", $line['mass']),
                sprintf("%01.3f", $line['mass']*$line['cnt']),                
                sprintf("%01.2f", $line['price']),
                sprintf("%01.2f", $line['sum']),
                $line['comment']
            );
            $this->pdf->RowIconv($row);

            if ($this->pdf->GetY() > 190) {
                $this->makeColRect($t_all_offset, $y);
                $this->addPage('L');                
                $y = $this->pdf->GetY();
                $list_summass = $list_sum = $list_sumnaloga = 0;
            }
            $sum += $line['sum'];
        }
        $this->makeColRect($t_all_offset, $y);        
        $this->makeSummary('Итого:', $t_all_width, 3.5, $sum);

        $this->controlPageBreak(61);
        $this->pdf->SetAutoPageBreak(0);
       
        // Подписи
        $step = 3.5;
        $microstep = 3;
        $this->pdf->SetLineWidth($this->line_thin_w);
        
        $sum_p = num2str($sum);
        $this->makeSimpleItem('Сумма списания', $sum_p, '', $step, $microstep);
        $this->pdf->ln();
        $str = 'Все члены комиссии предупреждены об ответственности за подписание акта, содержащие сведения, не соответствующие действительности.';
        $this->pdf->MultiCellIconv(0, $step, $str ,0,'L',0);
        $this->pdf->ln();
        $this->makeDPFItem('Председатель комиссии: ', '', '', $step, $microstep);
        $this->makeDPFItem('Члены комиссии: ', '', '', $step, $microstep);
        $this->makeDPFItem('', '', '', $step, $microstep);
        $this->makeDPFItem('', '', '', $step, $microstep);
        $this->makeDPFItem('Материально ответственное лицо', '', '', $step, $microstep);
        
        $str = 'Решение руководиетля';
        $this->pdf->MultiCellIconv(0, $step, $str ,0,'L',0);
        $this->makeSimpleItem('Стоимость списанного товара отнести на счет', '' , '', $step, $microstep);

    }

}
