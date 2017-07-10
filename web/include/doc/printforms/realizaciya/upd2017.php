<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2016, BlackLight, TND Team, http://tndproject.org
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

class upd2017 extends \doc\printforms\iPrintFormPdf {
 
    public function getName() {
        return "Универсальный передаточный документ 2017";
    }
    
    protected function outHeaderLine($name, $value, $info) {
        $h = 3.5;
        $this->pdf->CellIconv(45, $h, $name, 0, 0, 'L');
        $this->pdf->CellIconv(195, $h, $value, "B", 0, 'L');
        $this->pdf->CellIconv(0, $h, $info, 0, 1, 'C');
        
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
        
        $this->pdf->SetLineWidth($this->line_bold_w);        
        $this->pdf->Line(40, 5, 40, 79);
        
        $this->pdf->SetY($y);
        $this->pdf->SetX($this->pdf->lMargin);
        $this->pdf->SetFont('', '', 10);
        $str = 'Универсальный передаточный документ';
        $this->pdf->MultiCellIconv(30, 4, $str, 0, 'L');
        $this->pdf->Ln(5);
        
        $this->pdf->SetFont('', '', 8);
        $str = 'Статус: ';
        $this->pdf->CellIconv(15, 4, $str, 0, 0, 'R');
        $this->pdf->CellIconv(8, 4, '1', 1, 0, 'C');
        $this->pdf->Ln(7);
        
        $this->pdf->SetFont('', '', 7);
        $str = '1 - счет-фактура и передаточный документ (акт)';
        $this->pdf->MultiCellIconv(30, 3, $str, 0, 'L');
        $this->pdf->Ln(2);
        $str = '2 - передаточный документ (акт)';
        $this->pdf->MultiCellIconv(30, 3, $str, 0, 'L');
        
        $old_l_margin = $this->pdf->lMargin;
        $this->pdf->lMargin = 42;
        $this->pdf->SetY($y);                
        $str = 'Приложение №1 к постановлению правительства РФ от 26 декабря 2011г N1137';
        $this->pdf->CellIconv(0, 4, $str, 0, 1, 'R');
        
        $this->pdf->SetY($y); 
        $this->pdf->SetFont('', '', 10);
        $str = "Счёт - фактура N {$doc_data['altnum']} от ". date("d.m.Y", $doc_data['date'])." (1)";
        $this->pdf->CellIconv(0, 4, $str, 0, 1, 'L');
        $str = "Исправление N ---- от --.--.---- (1a)";
        $this->pdf->CellIconv(0, 4, $str, 0, 1, 'L');
        $this->pdf->ln();
        
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
        $this->pdf->SetFont('', '', 7);
        $this->pdf->SetLineWidth($this->line_thin_w); 
        $this->outHeaderLine("Продавец:", $firm_vars['firm_name'], "(2)");
        $this->outHeaderLine("Адрес:", $firm_vars['firm_adres'], "(2а)");
        $this->outHeaderLine("ИНН / КПП продавца:", $firm_vars['firm_inn'], "(2б)");        
        $this->outHeaderLine("Грузоотправитель и его адрес:", $firm_vars['firm_gruzootpr'], "(3)");
        $this->outHeaderLine("Грузополучатель и его адрес:", $gruzop, "(4)");
        $this->outHeaderLine("К платёжно-расчётному документу", "№ $pp, от $ppdt", "(5)");
        $this->outHeaderLine("Покупатель:", $agent_info['fullname'], "(6)");
        $this->outHeaderLine("Адрес:", $agent_info['adres'], "(6а)");
        $this->outHeaderLine("ИНН / КПП покупателя:", $agent_info['inn'] . ' / ' . $agent_info['kpp'], "(6б)");
        $this->outHeaderLine("Валюта: наименование, код", "Российский рубль, 643", "(7)");      
        $this->outHeaderLine("Идентификатор государственного контракта, договора (соглашения)", "", "(8)");  
        $this->pdf->lMargin = $old_l_margin;
        $this->pdf->Ln();
        
        // Таблица номенклатуры - шапка        
        $y = $this->pdf->GetY();
        $t_all_offset = array();

        $this->pdf->SetLineWidth($this->line_normal_w); 
        $t_width = array(10, 20, 58, 22, 10, 15, 20, 10, 10, 16, 28, 26, 0);
        $t_ydelta = array(7, 7, 7, 0.2, 5, 5, 0.5, 6, 6, 7, 3, 0.2, 7);
        $t_text = array(
            'N п/п',
            'Код товара/ работ, услуг',
            'Наименование товара (описание выполненных работ, оказанных услуг), имущественного права',
            'Единица измерения',
            'Количество (объ ём)',
            'Цена (тариф) за единицу измерения',
            'Стоимость товаров (работ, услуг), имуществен- ных прав, всего без налога',
            'В том числе акциз',
            'Нало- говая ставка',
            'Сумма налога',
            'Стоимость товаров (работ, услуг), имущественных прав всего с учетом налога',
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
            $this->pdf->MultiCellIconv($w, 2.7, $t_text[$i], 0, 'C', 0);
            $offset+=$w;
        }

        $t2_width = array(7, 15, 7, 19);
        $t2_start = array(3, 3, 11, 11);
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

            if ($old_col == $t2_start[$i]) {
                $off2+=$t2_width[$i - 1];
            } else {
                $off2 = 0;
            }
            $old_col = $t2_start[$i];
            $t_all_offset[$offset + $off2] = $offset + $off2;
            $this->pdf->SetY($y);
            $this->pdf->SetX($offset + $off2 + $this->pdf->lMargin);
            $this->pdf->Cell($w2, 14, '', 1, 0, 'C', 0);

            $this->pdf->SetY($y + $t2_ydelta[$i]);
            $this->pdf->SetX($offset + $off2 + $this->pdf->lMargin);
            $this->pdf->MultiCellIconv($w2, 3, $t2_text[$i], 0, 'C', 0);
        }

        $t3_text = array('А', 'Б', 1, 2, '2a', 3, 4, 5, 6, 7, 8, 9, 10, '10a', 11);
        $this->pdf->SetLineWidth($this->line_normal_w);
        sort($t_all_offset, SORT_NUMERIC);
        $this->pdf->SetY($y + 14);
        $t_all_width = array();
        $old_offset = 0;
        foreach ($t_all_offset as $offset) {
            if ($offset == 0) {
                continue;
            }
            $t_all_width[] = $offset - $old_offset;
            $old_offset = $offset;
        }
        $t_all_width[] = 32;
        $i = 1;
        foreach ($t_all_width as $w) {
            $this->pdf->CellIconv($w, 4, $t3_text[$i - 1], 1, 0, 'C', 0);
            $i++;
        }
        
        // тело таблицы
        $nomenclature = $this->doc->getDocumentNomenclatureWVATandNums();
        
        $this->pdf->SetWidths($t_all_width);
        $font_sizes = array(0=>7);
        $this->pdf->SetFSizes($font_sizes);
        $this->pdf->SetHeight(3.5);

        $aligns = array('R', 'C', 'L', 'C', 'L', 'R', 'R', 'R', 'C', 'C', 'R', 'R', 'R', 'L', 'R');
        $this->pdf->SetAligns($aligns);
        $this->pdf->SetY($y + 18);
        $this->pdf->SetFillColor(255, 255, 255);
        $i = 1;
        $sumbeznaloga = $sumnaloga = $sum = $summass = 0;
        foreach ($nomenclature as $line ) {
            $sumbeznaloga += $line['sum_wo_vat'];
            $sum += $line['sum'];
            $sumnaloga += $line['vat_s'];
            $summass += $line['mass']*$line['cnt'];
            if($line['vat_p']>0) {
                $p_vat_p = $line['vat_p'].'%';
                $vat_s_p = sprintf("%01.2f", $line['vat_s']);
            }   else {
                $p_vat_p = $vat_s_p = 'без налога';
            }
            $row = array(
                $i++,
                $line['code'],
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
                $line['gtd']);
            $lsy = $this->pdf->GetY();
            $this->pdf->RowIconv($row);
            $this->pdf->SetLineWidth($this->line_bold_w);
            if($this->pdf->GetY()<$lsy) {
                $lsy = $this->pdf->tMargin;
            }
            $this->pdf->Line(40, $this->pdf->GetY() , 40, $lsy);
            $this->pdf->SetLineWidth($this->line_normal_w);
        }
        // Контроль расстояния до конца листа
        $workspace_h = $this->pdf->h - $this->pdf->bMargin - $this->pdf->tMargin;
        if ($workspace_h  <= $this->pdf->GetY() + 81) {
            $this->pdf->AddPage('L');
            $this->addTechFooter();
        }
        $this->pdf->SetAutoPageBreak(0);        

        // Итоги
        $sum = sprintf("%01.2f", $sum);
        if($sumnaloga>0) {
            $sumnaloga = sprintf("%01.2f", $sumnaloga);
        }   else {
            $sumnaloga = 'без налога';
        }
        $sumbeznaloga = sprintf("%01.2f", $sumbeznaloga);
        $step = 4;
        $lsy = $this->pdf->GetY();
        $this->pdf->SetFont('', '', 8);
        $this->pdf->Cell($t_all_width[0], $step, '', 1, 0, 'R', 0);
        $this->pdf->Cell($t_all_width[1], $step, '', 1, 0, 'R', 0);
        $str = iconv('UTF-8', 'windows-1251', "Всего к оплате:");
        $allpay_w = 0;
        
        for($c = 2; $c<7; $allpay_w += $t_all_width[$c++]) {}
        $this->pdf->CellIconv($allpay_w, $step, "Всего к оплате:", 1, 0, 'L', 0);
        $this->pdf->Cell($t_all_width[7], $step, $sumbeznaloga, 1, 0, 'R', 0);
        $this->pdf->Cell($t_all_width[8] + $t_all_width[8], $step, 'X', 1, 0, 'C', 0);
        $this->pdf->CellIconv($t_all_width[10], $step, $sumnaloga, 1, 0, 'R', 0);
        $this->pdf->Cell($t_all_width[11], $step, $sum, 1, 0, 'R', 0);
        $this->pdf->Cell($t_all_width[12], $step, '', 1, 0, 'R', 0);
        $this->pdf->Cell($t_all_width[13], $step, '', 1, 0, 'R', 0);
        $this->pdf->Cell($t_all_width[14], $step, '', 1, 0, 'R', 0);
        $this->pdf->ln();
        
        // Подписи
        $this->pdf->SetFont('', '', 7);
        $step = 3;
        $microstep = 2.5;
        $y = $this->pdf->GetY();
        $this->pdf->Ln(2);
        $this->pdf->AliasNbPages();
        $this->pdf->MultiCellIconv($t_all_width[0] + $t_all_width[1], 5, "Документ составлен на {nb} листах", 0, 'L');
        
        $p1_w = array(45, 35, 2, 40, 45, 35, 2, 40);
        
        $this->pdf->SetLineWidth($this->line_thin_w);
        $this->pdf->lMargin = 42;
        $this->pdf->SetY($y+2);
        $this->pdf->SetX($this->pdf->lMargin);
        $this->pdf->CellIconv($p1_w[0] + $p1_w[1] + $p1_w[2] + $p1_w[3], $step, 'Руководитель организации', 0, 0, 'L', 0);
        $this->pdf->CellIconv(0, $step, 'Главный бухгалтер', 0, 1, 'L', 0);
        $this->pdf->CellIconv($p1_w[0], $step, 'или иное уполномоченное лицо', 0, 0, 'L', 0);
        $this->pdf->CellIconv($p1_w[1], $step, '', 'B', 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[2], $step, '', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[3], $step, $firm_vars['firm_director'], 'B', 0, 'R', 0);
        $this->pdf->CellIconv($p1_w[4], $step, 'или иное уполномоченное лицо', 0, 0, 'L', 0);
        $this->pdf->CellIconv($p1_w[5], $step, '', 'B', 0, 'С', 0);
        $this->pdf->CellIconv($p1_w[6], $step, '', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[7], $step, $firm_vars['firm_buhgalter'], 'B', 1, 'R', 0);
        
        $this->pdf->SetFont('', '', 5);
        $this->pdf->CellIconv($p1_w[0], $microstep, '', 0, 0, 'L', 0);
        $this->pdf->CellIconv($p1_w[1], $microstep, '(подпись)', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[2], $microstep, '', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[3], $microstep, '(ф.и.о.)',0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[4], $microstep, '', 0, 0, 'L', 0);
        $this->pdf->CellIconv($p1_w[5], $microstep, '(подпись)', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[6], $microstep, '', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[7], $microstep, '(ф.и.о.)', 0, 1, 'C', 0);
        $this->pdf->Ln(2);
        
        $this->pdf->SetFont('', '', 7);
        $this->pdf->CellIconv($p1_w[0], $step, 'Индивидуальный предприниматель', 0, 0, 'L', 0);
        $this->pdf->CellIconv($p1_w[1], $step, '', 'B', 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[2], $step, '', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[3], $step, $firm_vars['firm_director'], 'B', 0, 'R', 0);
        $this->pdf->CellIconv($p1_w[4] - 30, $step, '', 0, 0, 'L', 0);
        $this->pdf->CellIconv(30 + $p1_w[5] + $p1_w[6] + $p1_w[7], $step, '', 'B', 1, 'С', 0);
        
        $this->pdf->SetFont('', '', 5);
        $this->pdf->CellIconv($p1_w[0], $microstep, '', 0, 0, 'L', 0);
        $this->pdf->CellIconv($p1_w[1], $microstep, '(подпись)', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[2], $microstep, '', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[3], $microstep, '(ф.и.о.)',0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[4] - 30, $microstep, '', 0, 0, 'С', 0);
        $this->pdf->CellIconv(20 + $p1_w[5] + $p1_w[6] + $p1_w[7],
                $microstep, '(реквизиты свидетельства о государственной регистрации индивидуального предпринимателя)', 0, 1, 'C', 0);       
        
        $this->pdf->Ln(1);
        $this->pdf->SetLineWidth($this->line_bold_w);
        $this->pdf->Line(40, $this->pdf->GetY() , 40, $lsy);
        $this->pdf->Line(40, $this->pdf->GetY() , $this->pdf->w - $this->pdf->rMargin , $this->pdf->GetY());
        $this->pdf->SetLineWidth($this->line_thin_w);
        
        $reason_info = '';
        if(isset($dop_data['dov_agent']))	{
		$dov_data = $db->selectRow('doc_agent_dov', $dop_data['dov_agent']);
		if($dov_data) {
                    $reason_info = "Доверенность №{$dop_data['dov']} от {$dop_data['dov_data']}, ";
                    $reason_info .= "выданной {$dov_data['range']} {$dov_data['surname']} {$dov_data['name']} {$dov_data['name2']}";
		}
	}
        $this->pdf->lMargin = $old_l_margin;
        $this->pdf->Ln(2);
        $this->pdf->SetFont('', '', 7);
        $this->pdf->CellIconv(70, $step, 'Основание передачи (сдачи) / получения (приёмки)', 0, 0, 'L', 0);
        $this->pdf->CellIconv(200, $step, $reason_info, 'B', 0, 'C', 0);
        $this->pdf->CellIconv(0, $step, '[8]', 0, 1, 'R', 0);
        $this->pdf->SetFont('', '', 5);
        $this->pdf->CellIconv(80, $microstep, '', 0, 0, 'L', 0);
        $this->pdf->CellIconv(190, $microstep, '(договор; доверенность и др.)', 0, 0, 'C', 0);
        $this->pdf->CellIconv(0, $microstep, '', 0, 1, 'R', 0);
        
        $this->pdf->Ln(1);
        $this->pdf->SetFont('', '', 7);
        $this->pdf->CellIconv(50, $step, 'Данные о транспортировке и грузе', 0, 0, 'L', 0);
        $this->pdf->CellIconv(220, $step, 'Масса: '.sprintf("%0.3f", $summass).' кг.', 'B', 0, 'L', 0);
        $this->pdf->CellIconv(0, $step, '[9]', 0, 1, 'R', 0);
        $this->pdf->SetFont('', '', 5);
        $this->pdf->CellIconv(80, $microstep, '', 0, 0, 'L', 0);
        $this->pdf->CellIconv(190, $microstep, '(транспортная накладная, поручение экспедитору, экспедиторская / складская расписка и др, / масса нетто/брутто груза, если не приведены ссылки на документы, содержащие эти сведения)', 0, 0, 'C', 0);
        $this->pdf->CellIconv(0, $microstep, '', 0, 1, 'R', 0);
        
        $lsy = $this->pdf->GetY();
        $old_r_margin = $this->pdf->rMargin;
        $this->pdf->rMargin = 160;
        
        $step = 4;
        $this->pdf->Ln(2);
        $this->makeDPFItem('Товар (груз) передал / услуги, результаты работ, права сдал', 10, $step, $microstep);
        $this->makeDateItem('Дата отгрузки, передачи (сдачи)', 11, $step);
        $this->makeSimpleItem('Иные сведения об отгрузке, передаче', '', 12,
                '(ссылки на неотъемлемые приложения, сопутствующие документы, иные документы и т.п.)', $step, $microstep);
        $this->makeDPFItem('Ответственный за правильность оформления факта хозяйственной жизни', 13, $step, $microstep);
        $this->makeSimpleItem('Наименование экономического субъекта - составителя документа (в т.ч. комиссионера / агента)',
                $firm_vars['firm_name'].', ИНН/КПП:'.$firm_vars['firm_inn'], 14,
                '(может не заполняться при проставлении печати в М.П., может быть указан ИНН / КПП)', $step, $microstep);
        $this->pdf->SetLineWidth($this->line_bold_w);
        $this->pdf->Line(140, $this->pdf->GetY()+2, 140, $lsy);
        $this->pdf->SetLineWidth($this->line_thin_w);
        
        $this->pdf->rMargin = $old_r_margin;
        $this->pdf->SetY($lsy);
        $this->pdf->lMargin = 145;
        
        $this->pdf->Ln(2);
        $this->makeDPFItem('Товар (груз) получил / услуги, результаты работ, права принял', 15, $step, $microstep);
        $this->makeDateItem('Дата получения (приёмки)', 16, $step);
        $this->makeSimpleItem('Иные сведения о получении, приёмке', '', 17,
                '(информация о наличии/отсутствии претензии; ссылки на неотъемлемые приложения, и другие документы и т.п.)', $step, $microstep);
        $this->makeDPFItem('Ответственный за правильность оформления факта хозяйственной жизни', 18, $step, $microstep);
        $this->makeSimpleItem('Наименование экономического субъекта - составителя документа', '', 19,
                '(может не заполняться при проставлении печати в М.П., может быть указан ИНН / КПП)', $step, $microstep);      
    }
    
    
}
