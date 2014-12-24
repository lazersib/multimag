<?php
//	MultiMag v0.2 - Complex sales system
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
namespace doc\printforms\realizaciya; 

class upd extends \doc\printforms\iPrintForm {
 
    public function getName() {
        return "Универсальный передаточный документ";
    }
    
    protected function outHeaderLine($name, $value, $info) {
        $h = 3.5;
        $this->pdf->CellIconv(45, $h, $name, 0, 0, 'L');
        $this->pdf->CellIconv(195, $h, $value, "B", 0, 'L');
        $this->pdf->CellIconv(0, $h, $info, 0, 1, 'C');
        
    }


    /// Сформировать данные печатной формы
    public function make() {
        global $db;
        $doc_id = $this->doc->getDocNum();
        $doc_data = $this->doc->getDocDataA();
        $dop_data = $this->doc->getDopDataA();
        $firm_vars = $this->doc->getFirmVarsA();
        
        $this->pdf->SetAutoPageBreak(1, 5);
        $this->pdf->AddPage('L');
        $x = $this->pdf->getX();
        $y = $this->pdf->getY();
        $this->pdf->SetX(200);
        $this->pdf->SetY(200);
        $this->pdf->SetFont('Arial', '', 2);
        $str = 'Подготовлено в multimag v:'.MULTIMAG_VERSION;
        $this->pdf->CellIconv(0, 4, $str, 0, 0, 'R');
        
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
        $str = "Счёт - фактура N {$doc_data['altnum']}, от ". date("d.m.Y", $doc_data['date'])." (1)";
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
            throw new Exception('Агент не найден');
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
        $this->pdf->lMargin = $old_l_margin;
        $this->pdf->Ln();
        
        // Таблица номенклатуры
        
        $y = $this->pdf->GetY();
        $t_all_offset = array();

        $this->pdf->SetLineWidth($this->line_normal_w); 
        $t_width = array(10, 20, 58, 22, 10, 15, 20, 10, 10, 16, 28, 26, 0);
        $t_ydelta = array(7, 7, 7, 0, 5, 5, 0, 6, 6, 7, 3, 0, 7);
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
            $str = iconv('UTF-8', 'windows-1251', $t_text[$i]);
            $this->pdf->MultiCell($w, 2.7, $str, 0, 'C', 0);
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
                $t_a[$offset] = $offset;
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

        $t3_text = array('А', 'Б', 1, 2, '2a', 3, 4, 5, 6, 7, 8, 9, 10, '10a', 11);
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
        foreach ($t_all_width as $id => $w) {
            $this->pdf->CellIconv($w, 3.5, $t3_text[$i - 1], 1, 0, 'C', 0);
            $i++;
        }
    }
    
}
