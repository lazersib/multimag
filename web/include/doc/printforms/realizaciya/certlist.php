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

class certlist extends \doc\printforms\iPrintFormPdf {
 
    public function getName() {
        return "Реестр сертификатов";
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

    /// Получить список номенклатуры
    function getDocumentNomenclature($doc_id) {
        global $CONFIG, $db;
        $list = array();

        $cert_num_id = $cert_dates_id = $cert_creator_id = $exp_date_id = $t_store_id = 0;
        $res = $db->query("SELECT `id`, `codename` FROM  `doc_base_params`");
        while($line = $res->fetch_assoc()) {
            switch($line['param']) {
                case 'cert_num':
                    $cert_num_id = $line['id'];
                    break;
                case 'cert_expire':
                    $cert_dates_id = $line['id'];
                    break;
                case 'cert_publisher':
                    $cert_creator_id = $line['id'];
                    break;
                case 'expiration_period':
                    $exp_date_id = $line['id'];
                    break;
                case 'storage_temperature':
                    $t_store_id = $line['id'];
                    break;
            }
        }
        
        if(!$cert_num_id || !$cert_dates_id || !$cert_creator_id || !$exp_date_id || !$t_store_id) {
            throw new \Exception('Не найден один из необходимых параметров складской номенклатуры. Необходимые параметры: *cert_num - N сертификата*, *cert_expire - Срок действия*, *cert_publisher - Орган сертификации*. *expiration_period - срок годности*, *storage_temperature - Температура хранения*.');
        }
        
        $res = $db->query("SELECT `doc_list_pos`.`tovar` AS `pos_id`, `doc_group`.`printname` AS `group_printname`, `doc_base`.`name`, 
            `doc_base`.`proizv` AS `vendor`,  `doc_base`.`vc`, `certnum`.`value` AS `cert_num`, `certdate`.`value` AS `cert_dates`, 
            `certcreator`.`value` AS `cert_creator`, `exp`.`value` AS `exp_date`, `tst`.`value` AS `t_store`
        FROM `doc_list_pos`
        INNER JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
        LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
        LEFT JOIN `doc_base_values` AS `certnum` ON `certnum`.`id`=`doc_list_pos`.`tovar` AND `certnum`.`param_id`=$cert_num_id
        LEFT JOIN `doc_base_values` AS `certdate` ON `certdate`.`id`=`doc_list_pos`.`tovar` AND `certdate`.`param_id`=$cert_dates_id
        LEFT JOIN `doc_base_values` AS `certcreator` ON `certcreator`.`id`=`doc_list_pos`.`tovar` AND `certcreator`.`param_id`=$cert_creator_id
        LEFT JOIN `doc_base_values` AS `exp` ON `exp`.`id`=`doc_list_pos`.`tovar` AND `exp`.`param_id`=$exp_date_id 
        LEFT JOIN `doc_base_values` AS `tst` ON `tst`.`id`=`doc_list_pos`.`tovar` AND `tst`.`param_id`=$t_store_id
        WHERE `doc_list_pos`.`doc`='{$doc_id}'
        ORDER BY `doc_list_pos`.`id`");

        while ($line = $res->fetch_assoc()) {
            if($line['group_printname']) {
                $line['name'] = $line['group_printname'].' '.$line['name'];
            }
            $line['code'] = $line['pos_id'];
            if($line['vc']) {
                $line['code'] .= ' / '.$line['vc'];
            }
            $list[] = $line;
        }
        return $list;
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
        
        $this->pdf->SetFont('', '', 14);
        $str = 'Реестр сертификатов';
        $this->pdf->CellIconv(0, 5, $str, 0, 1, 'C');
        $str = "к накладной N {$doc_data['altnum']} от ". date("d.m.Y", $doc_data['date']);
        $this->pdf->CellIconv(0, 5, $str, 0, 1, 'C');
        $this->pdf->Ln(5);
        
        $this->pdf->SetFont('', '', 9);
        $str = $firm_vars['firm_name'].' представляет список сертификатов на реализуемую продукцию, оригиналы которых хранятся в офисе предприятия по адресу '.
            $firm_vars['firm_adres'];
        $this->pdf->MultiCellIconv(0, 4, $str, 0, 'L');
        $this->pdf->Ln(7);
        
        // Таблица номенклатуры - шапка        

        $this->pdf->SetLineWidth($this->line_bold_w);
        $t_width = array(10, 75, 40, 40, 40, 70);
        $t_text = array(
            'N п/п',
            'Наименование товара',
            'N сертификата',
            'Срок действия',
            'Орган сертификации',
            'Срок реализации, температура хранения');

        foreach ($t_width as $i => $w) {
            $this->pdf->CellIconv($w, 5, $t_text[$i], 1, 0, 'C');
        }
        $this->pdf->ln();
        // тело таблицы
        $this->pdf->SetFont('', '', 8);
        $this->pdf->SetLineWidth($this->line_normal_w); 
        $nomenclature = $this->getDocumentNomenclature($doc_id);
        
        $this->pdf->SetWidths($t_width);
        $font_sizes = array(0=>7);
        $this->pdf->SetFSizes($font_sizes);
        $this->pdf->SetHeight(3.5);

        $aligns = array('R', 'L', 'C', 'C', 'C', 'C', 'C');
        $this->pdf->SetAligns($aligns);
        $this->pdf->SetFillColor(255, 255, 255);
        $i = 1;
        $sumbeznaloga = $sumnaloga = $sum = 0;
        foreach ($nomenclature as $line ) {
            $row = array(
                $i++,
                $line['name'],
                $line['cert_num'],
                $line['cert_dates'],
                $line['cert_creator'],
                $line['exp_date'].', '.$line['t_store']);
            $this->pdf->RowIconv($row);
        }
        // Контроль расстояния до конца листа
        $workspace_h = $this->pdf->h - $this->pdf->bMargin - $this->pdf->tMargin;
        if ($workspace_h  <= $this->pdf->GetY() + 81) {
            $this->pdf->AddPage('L');
            $this->addTechFooter();
        }
        $this->pdf->SetAutoPageBreak(0);        
        $this->pdf->ln(3);
        
        $this->pdf->SetFont('', '', 9);
        $this->pdf->CellIconv(0, 4, "Выдал: ___________________________", 0, 1, 'L', 0);             
    }
    
    
}
