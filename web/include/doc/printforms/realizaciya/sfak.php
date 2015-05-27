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

class sfak extends \doc\printforms\iPrintFormPdf {
 
    public function getName() {
        return "Счёт-фактура";
    }
    
    protected function outHeaderLine($name, $value) {
        $h = 4.5;
        $this->pdf->MultiCellIconv(0, $h, $name.' '.$value, 0, 'L');
    }
    
    // Вывод элемента *должность/подпись/фио*
    protected function makeDPFItem($name, $num, $step = 4, $microstep = 2) {
        $p1_w = array(35, 2, 35, 2, 45, 0);
        $this->pdf->SetFont('', '', 7);
        $this->pdf->CellIconv(0, $step, $name, 0, 1, 'L', 0); 
        $this->pdf->CellIconv($p1_w[0], $step, '', 'B', 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[1], $step, '', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[2], $step, '', 'B', 0, 'R', 0);
        $this->pdf->CellIconv($p1_w[3], $step, '', 0, 0, 'L', 0);
        $this->pdf->CellIconv($p1_w[4], $step, '', 'B', 0, 'С', 0);
        $this->pdf->CellIconv($p1_w[5], $step, '['.$num.']', 0, 1, 'R', 0);
        
        $this->pdf->SetFont('', '', 5);
        $this->pdf->CellIconv($p1_w[0], $microstep, '(должность)', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[1], $microstep, '', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[2], $microstep, '(подпись)', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[3], $microstep, '',0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[4], $microstep, '(ф.и.о.)', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[5], $microstep, '', 0, 1, 'C', 0);
    }
    
    // Вывод простого элемента блока подписей
    protected function makeSimpleItem($name, $value, $num, $desc, $step, $microstep) {
        $this->pdf->SetFont('', '', 7);
        $this->pdf->CellIconv(0, $step, $name, 0, 1, 'L', 0);
        $this->pdf->CellIconv(120, $step, $value, 'B', 0, 'L', 0);
        $this->pdf->CellIconv(0, $step, '['.$num.']', 0, 1, 'R', 0);
        $this->pdf->SetFont('', '', 5);
        $this->pdf->CellIconv(120, $microstep, $desc, 0, 1, 'C', 0);
    }
    // Вывод простого элемента блока подписей *дата*
    protected function makeDateItem($name, $num, $step) {
        $this->pdf->SetFont('', '', 7);
        $this->pdf->CellIconv(60, $step, $name, 0, 0, 'L', 0);
        $this->pdf->CellIconv(60, $step, '"_____" _________________________ 20____г.', 0, 0, 'C', 0);
        $this->pdf->CellIconv(0, $step, '['.$num.']', 0, 1, 'R', 0);
    }

    /// Сформировать данные печатной формы
    public function make() {
        global $db;
        $doc_id = $this->doc->getId();
        $doc_data = $this->doc->getDocDataA();
        $dop_data = $this->doc->getDopDataA();
        $firm_vars = $this->doc->getFirmVarsA();
        
        $this->pdf->AddPage('L');
        $y = $this->pdf->getY();
        $this->addTechFooter();
        
        $this->pdf->Setx(150);
        $this->pdf->SetFont('Arial', '', 7);
        $str = 'Приложение №1 к постановлению правительства РФ от 26 декабря 2011г N1137';
        $str = iconv('UTF-8', 'windows-1251', $str);
        $this->pdf->MultiCell(0, 4, $str, 0, 'R');
        $this->pdf->Ln();
        
        // заголовок
        $this->pdf->SetFont('', '', 16);
        $step = 4;
        $str = iconv('UTF-8', 'windows-1251', "Счёт - фактура N {$doc_data['altnum']} от ". date("d.m.Y", $doc_data['date']));
        $this->pdf->Cell(0, 6, $str, 0, 1, 'L');
        $str = iconv('UTF-8', 'windows-1251', "Исправление N ---- от --.--.----");
        $this->pdf->Cell(0, 6, $str, 0, 1, 'L');
        $this->pdf->Ln(5);
                
        // Загрузка данных шапки
        $gruzop_info = $db->selectRow('doc_agent', $dop_data['gruzop']);
        $gruzop = '';
        if ($gruzop_info) {
            if ($gruzop_info['fullname']) {
                $gruzop .= $gruzop_info['fullname'];
            }
            if ($gruzop_info['adres']) {
                $gruzop.=', адрес ' . $gruzop_info['adres'];
            }
        }
        $agent_info = $db->selectRow('doc_agent', $doc_data['agent']);
        if (!$agent_info) {
            throw new \Exception('Агент не найден');
        }
        
        if ($doc_data['p_doc']) {
            $rs = $db->query("SELECT `id`, `altnum`, `date` FROM `doc_list` WHERE
		(`p_doc`='$doc_id' AND (`type`='4' OR `type`='6') AND `date`<='{$doc_data['date']}' ) OR
		(`p_doc`='{$doc_data['p_doc']}' AND (`type`='4' OR `type`='6') AND `date`<='{$doc_data['date']}')
		AND `ok`>'0' AND `p_doc`!='0' GROUP BY `p_doc`");
            if ($rs->num_rows) {
                $line = $rs->fetch_row();
                $pp = $line[1];
                $ppdt = date("d.m.Y", $line[2]);
                if (!$pp) {
                    $pp = $line[0];
                }
            }
            else {
                $pp = $ppdt = "           ";
            }
        } else {
            $pp = $ppdt = "           ";
        }
        // Шапка
        $this->pdf->SetFont('', '', 10);
        $this->pdf->SetLineWidth($this->line_thin_w); 
        $this->outHeaderLine("Продавец:", $firm_vars['firm_name']);
        $this->outHeaderLine("Адрес:", $firm_vars['firm_adres']);
        $this->outHeaderLine("ИНН / КПП продавца:", $firm_vars['firm_inn']);        
        $this->outHeaderLine("Грузоотправитель и его адрес:", $firm_vars['firm_gruzootpr']);
        $this->outHeaderLine("Грузополучатель и его адрес:", $gruzop, "(4)");
        $this->outHeaderLine("К платёжно-расчётному документу", "№ $pp, от $ppdt");
        $this->outHeaderLine("Покупатель:", $agent_info['fullname']);
        $this->outHeaderLine("Адрес:", $agent_info['adres']);
        $this->outHeaderLine("ИНН / КПП покупателя:", $agent_info['inn'] . ' / ' . $agent_info['kpp']);
        $this->outHeaderLine("Валюта: наименование, код", "Российский рубль, 643");        
        $this->pdf->Ln();
        
        // Таблица номенклатуры - шапка        
        // ====== Основная таблица =============
        $y = $this->pdf->GetY();

        $t_all_offset = array();

        $this->pdf->SetLineWidth($this->line_normal_w);
        $t_width = array(88, 22, 10, 15, 20, 10, 10, 16, 28, 26, 0);
        $t_ydelta = array(7, 0, 5, 5, 0, 6, 6, 7, 3, 0, 7);
        $t_text = array(
            'Наименование товара (описание выполненных работ, оказанных услуг, имущественного права)',
            'Единица измерения',
            'Количество (объ ём)',
            'Цена (тариф) за единицу измерения',
            'Стоимость товаров (работ, услуг), имуществен- ных прав, всего без налога',
            'В том числе акциз',
            'Нало- говая ставка',
            'Сумма налога',
            'Стоимость товаров (работ, услуг, имущественных прав), всего с учетом налога',
            'Страна происхождения',
            'Номер таможенной декларации');

        foreach ($t_width as $w) {
            $this->pdf->Cell($w, 20, '', 1, 0, 'C', 0);
        }
        $this->pdf->Ln();
        $this->pdf->Ln(0.5);
        $this->pdf->SetFont('', '', 7);
        $offset = 0;
        foreach ($t_width as $i => $w) {
            $t_all_offset[$offset] = $offset;
            $this->pdf->SetY($y + $t_ydelta[$i] + 0.2);
            $this->pdf->SetX($offset + $this->pdf->lMargin);
            $str = iconv('UTF-8', 'windows-1251', $t_text[$i]);
            $this->pdf->MultiCell($w, 2.7, $str, 0, 'C', 0);
            $offset+=$w;
        }

        $t2_width = array(7, 15, 7, 19);
        $t2_start = array(1, 1, 9, 9);
        $t2_ydelta = array(2, 1, 2, 3);
        $t2_text = array(
            "к\nо\nд",
            'условное обозначение (наци ональное)',
            "к\nо\nд",
            'краткое наименование');
        $offset = 0;
        $c_id = 0;
        $old_col = 0;
        $y+=6;

        foreach ($t2_width as $i => $w2) {
            while ($c_id < $t2_start[$i]) {
                $offset+=$t_width[$c_id++];
            }

            if ($old_col == $t2_start[$i])
                $off2+=$t2_width[$i - 1];
            else
                $off2 = 0;
            $old_col = $t2_start[$i];
            $t_all_offset[$offset + $off2] = $offset + $off2;
            $this->pdf->SetY($y);
            $this->pdf->SetX($offset + $off2 + $this->pdf->lMargin);
            $this->pdf->Cell($w2, 14, '', 1, 0, 'C', 0);

            $this->pdf->SetY($y + $t2_ydelta[$i]);
            $this->pdf->SetX($offset + $off2 + $this->pdf->lMargin);
            $str = iconv('UTF-8', 'windows-1251', $t2_text[$i]);
            $this->pdf->MultiCell($w2, 3, $str, 0, 'C', 0);
        }

        $t3_text = array(1, 2, '2a', 3, 4, 5, 6, 7, 8, 9, 10, '10a', 11);
        $this->pdf->SetLineWidth(0.2);
        sort($t_all_offset, SORT_NUMERIC);
        $this->pdf->SetY($y + 14);
        $t_all_width = array();
        $old_offset = 0;
        foreach ($t_all_offset as $offset) {
            if ($offset == 0)
                continue;
            $t_all_width[] = $offset - $old_offset;
            $old_offset = $offset;
        }
        $t_all_width[] = 32;
        $i = 1;
        foreach ($t_all_width as $w) {
            $this->pdf->Cell($w, 4, $t3_text[$i - 1], 1, 0, 'C', 0);
            $i++;
        }
        
        // тело таблицы
        $nomenclature = $this->doc->getDocumentNomenclatureWVATandNums();
        
        $this->pdf->SetWidths($t_all_width);
        $font_sizes = array(0=>7);
        $this->pdf->SetFSizes($font_sizes);
        $this->pdf->SetHeight(3.5);

        $aligns = array('L', 'C', 'L', 'R', 'R', 'R', 'C', 'R', 'R', 'R', 'R', 'L', 'R');
        $this->pdf->SetAligns($aligns);
        $this->pdf->SetY($y + 18);
        $this->pdf->SetFillColor(255, 255, 255);
        $i = 1;
        $sumbeznaloga = $sumnaloga = $sum = 0;
        foreach ($nomenclature as $line ) {
            $sumbeznaloga += $line['sum_wo_vat'];
            $sum += $line['sum'];
            $sumnaloga += $line['vat_s'];
            if($line['vat_p']>0) {
                $p_vat_p = $line['vat_p'].'%';
                $vat_s_p = sprintf("%01.2f", $line['vat_s']);
            }   else {
                $p_vat_p = $vat_s_p = 'без налога';
            }
            $row = array(
                $line['name'],
                $line['unit_code'],
                $line['unit_name'],
                $line['cnt'],
                sprintf("%01.2f", $line['price']),
                sprintf("%01.2f", $line['sum_wo_vat']),
                $line['excise'],
                $p_vat_p,
                $vat_s_p,
                sprintf("%01.2f", $line['sum']),
                $line['country_code'],
                $line['country_name'],
                $line['ncd']);

            $this->pdf->RowIconv($row);
        }
        
        // Контроль расстояния до конца листа
        $workspace_h = $this->pdf->h - $this->pdf->bMargin - $this->pdf->tMargin;
        if ($workspace_h  <= $this->pdf->GetY() + 65) {
            $this->pdf->AddPage('L');
            $this->addTechFooter();
        }
        $this->pdf->SetAutoPageBreak(0);        

        // Итоги
        $sum = sprintf("%01.2f", $sum);
        if($sumnaloga>0) {
            $sumnaloga = sprintf("%01.2f", $sumnaloga);
        }   else {
            $sumnaloga = '--';
        }
        $sumbeznaloga = sprintf("%01.2f", $sumbeznaloga);
        $step = 5.5;
        $this->pdf->SetFont('', '', 9);
        $this->pdf->SetLineWidth(0.3);
        $str = iconv('UTF-8', 'windows-1251', "Всего к оплате:");
        $this->pdf->Cell($t_all_width[0] + $t_all_width[1] + $t_all_width[2] + $t_all_width[3] + $t_all_width[4], $step, $str, 1, 0, 'L', 0);
        $this->pdf->Cell($t_all_width[5], $step, $sumbeznaloga, 1, 0, 'R', 0);
        $this->pdf->Cell($t_all_width[6] + $t_all_width[7], $step, 'X', 1, 0, 'C', 0);
        $this->pdf->CellIconv($t_all_width[8], $step, $sumnaloga, 1, 0, 'R', 0);
        $this->pdf->Cell($t_all_width[9], $step, $sum, 1, 0, 'R', 0);

        $this->pdf->Ln(10);

        $this->pdf->SetFont('', '', 10);
        $str = iconv('UTF-8', 'windows-1251', "Руководитель организации:");
        $this->pdf->Cell(50, $step, $str, 0, 0, 'L', 0);
        $str = '_____________________';
        $this->pdf->Cell(50, $step, $str, 0, 0, 'L', 0);
        $str = iconv('UTF-8', 'windows-1251', "/" . $firm_vars['firm_director'] . "/");
        $this->pdf->Cell(40, $step, $str, 0, 0, 'L', 0);

        $str = iconv('UTF-8', 'windows-1251', "Главный бухгалтер:");
        $this->pdf->Cell(40, $step, $str, 0, 0, 'R', 0);
        $str = '_____________________';
        $this->pdf->Cell(50, $step, $str, 0, 0, 'L', 0);
        $str = iconv('UTF-8', 'windows-1251', "/" . $firm_vars['firm_buhgalter'] . "/");
        $this->pdf->Cell(0, $step, $str, 0, 0, 'L', 0);
        $this->pdf->Ln(4);
        $this->pdf->SetFont('', '', 7);
        $str = iconv('UTF-8', 'windows-1251', "или иное уполномоченное лицо");
        $this->pdf->Cell(140, 3, $str, 0, 0, 'L', 0);
        $str = iconv('UTF-8', 'windows-1251', "или иное уполномоченное лицо");
        $this->pdf->Cell(50, 3, $str, 0, 0, 'L', 0);
        $this->pdf->Ln(8);

        $this->pdf->SetFont('', '', 10);
        $str = iconv('UTF-8', 'windows-1251', "Индивидуальный предприниматель:______________________ / ____________________________/");
        $this->pdf->Cell(160, $step, $str, 0, 0, 'L', 0);
        $this->pdf->Cell(0, $step, '____________________________________', 0, 1, 'R', 0);

        $this->pdf->SetFont('', '', 7);
        $this->pdf->Cell(160, $step, '', 0, 0, 'L', 0);
        $str = iconv('UTF-8', 'windows-1251', "реквизиты свидетельства о государственной регистрации ИП");
        $this->pdf->Cell(0, 3, $str, 0, 0, 'R', 0);


        $this->pdf->Ln(10);
        $this->pdf->SetFont('', '', 7);
        $str = iconv('UTF-8', 'windows-1251', "ПРИМЕЧАНИЕ. Первый экземпляр (оригинал) - покупателю, второй экземпляр (копия) - продавцу");
        $this->pdf->Cell(0, $step, $str, 0, 0, 'R', 0);

        $this->pdf->Ln();
    }
    
    
}
