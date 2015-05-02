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
namespace doc\printforms\doveren; 

class dov extends \doc\printforms\iPrintFormPdf {
 
    public function getName() {
        return "Доверенность";
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
        
        $res = $db->query("SELECT `user_id`, `worker_real_name`, `worker_post_name`, `pasp_num`, `pasp_date`, `pasp_kem`
            FROM `users_worker_info` 
            INNER JOIN `users` ON `users`.`id`=`users_worker_info`.`user_id`
            INNER JOIN `doc_agent` ON `doc_agent`.`id`=`users`.`agent_id`
            WHERE `user_id`=".intval($dop_data['worker_id'])."
            ORDER BY `worker_real_name`");
        if(!$res->num_rows) {
            throw new \Exception("Сотрудник не выбран или не связан с агентом");
        }
        $worker_info = $res->fetch_assoc();
        
        $this->pdf->AddPage('P');
        $this->addTechFooter();
        
        $this->pdf->SetLineWidth($this->line_normal_w);        
        $this->pdf->SetFillColor(255, 255, 255);
        
        $widths = array(30, 25, 25, 65, 45);
        $aligns = array('C', 'C', 'C', 'C', 'C');
        $font_sizes = array(0=>7);
        
        $this->pdf->SetWidths($widths);        
        $this->pdf->SetFSizes($font_sizes);
        $this->pdf->SetAligns($aligns);
        $this->pdf->SetHeight(3);  
        $this->pdf->SetFont('', '', 8);
        
        $row = array(
            'Номер доверенности',
            'Дата выдачи',
            'Срок действия',
            'Должность и фамилия лица, которому выдана доверенность',
            'Расписка в получении доверенности'
        );
        $this->pdf->RowIconv($row);
        $this->pdf->SetHeight(3.5);
                
        $row = array(1, 2, 3, 4, 5);
        $this->pdf->Row($row);
        
        $row = array(
            $doc_data['altnum'],
            date("d.m.Y", $doc_data['date']),
            '',
            $doc_data['agent_fullname'],
            ''
        );
        $this->pdf->RowIconv($row);
        
        $widths = array(67, 45, 78);
        $this->pdf->SetWidths($widths);
        
        $row = array(
            'Поставщик',
            'Номер и дата наряда (замещающего наряд документа) или извещения',
            'Номер и дата документа, подтверждающего выполнение поручения'
        );
        $this->pdf->RowIconv($row);
        $row = array(1, 2, 3);
        $this->pdf->Row($row);
        $row = array(
            $dop_data['ot'],
            '',
            ''
        );
        $this->pdf->RowIconv($row);
        $this->pdf->CellIconv(0, 4, 'Линия отреза', 'B', 1, 'C');
        $this->pdf->ln(6);
        
        $this->pdf->SetFont('', '', 5);
        $this->pdf->CellIconv(0, 2, 'Типовая межотраслевая форма N М-2', 0, 1, 'R');
        $this->pdf->CellIconv(0, 2, 'Утверждена постановлением Госкомстата России от 30.10.97 N 71a', 0, 1, 'R');
        $this->pdf->ln(1);
        
        $this->pdf->SetFont('', '', 6);
        $this->pdf->CellIconv(170, 3, '', 0, 0, 'R');
        $y = $this->pdf->GetY();
        $this->pdf->CellIconv(0, 3, 'Коды', 1, 1, 'C');
        $this->pdf->SetFont('', '', 8);
        $this->pdf->SetLineWidth($this->line_bold_w);  
        $this->pdf->CellIconv(170, 4, 'Форма по ОКУД', 0, 0, 'R');
        $this->pdf->CellIconv(0, 4, '0315001', 1, 1, 'C');
        $this->pdf->CellIconv(170, 4, 'по ОКПО', 0, 0, 'R');
        $this->pdf->CellIconv(0, 4, '34506136', 1, 1, 'C');
        
        $this->pdf->SetY($y+5);
        $this->pdf->SetFont('', '', 10);
        $this->pdf->MultiCellIconv(140, 3.5, 'Организация: '.$firm_vars['firm_name'], 0, 'L');
        $this->pdf->ln(7);
        
        $this->pdf->SetFont('', '', 12);
        $this->pdf->SetLineWidth($this->line_normal_w); 
        $this->pdf->CellIconv(90, 4, 'Доверенность N ', 0, 0, 'R');
        $this->pdf->CellIconv(20, 4, $doc_data['altnum'], 'B', 1, 'C');
        
        $this->pdf->SetFont('', '', 8);
        $this->pdf->CellIconv(40, 4, 'Дата выдачи:', 0, 0, 'R');
        $this->pdf->CellIconv(20, 4, date("d.m.Y", $doc_data['date']), 'B', 1, 'L');
        
        $this->pdf->CellIconv(40, 4, 'Действительна до:', 0, 0, 'R');
        $this->pdf->CellIconv(20, 4, date("Y-m-d", $doc_data['date']), 'B', 1, 'L'); /// TODO: ТУТ ПОЛЕ НАДО
        $this->pdf->ln();
        
        $str = $firm_vars['firm_name'];
        if($firm_vars['firm_inn']) {
            $str .= ', ИНН/КПП: '.$firm_vars['firm_inn'];
        }
        if($firm_vars['firm_adres']) {
            $str .= ', адрес: '.$firm_vars['firm_adres'];
        }
        if($firm_vars['firm_telefon']) {
            $str .= ', телефон: '.$firm_vars['firm_telefon'];
        }
        
        $this->pdf->MultiCellIconv(0, 3.5, $str, 'B', 'L');
        $this->pdf->SetFont('', '', 5);
        $this->pdf->CellIconv(0, 2, 'наименование потребителя и его адрес', 0, 1, 'C');
        $this->pdf->SetFont('', '', 8);
        
        $this->pdf->MultiCellIconv(0, 3.5, $str, 'B', 'L');
        $this->pdf->SetFont('', '', 5);
        $this->pdf->CellIconv(0, 2, 'наименование плательщика и его адрес', 0, 1, 'C');
        $this->pdf->SetFont('', '', 8);
        
        $this->pdf->CellIconv(40, 4, 'Счёт N', 0, 0, 'R');
        $this->pdf->MultiCellIconv(0, 4, $str, 'B', 'L');
        $this->pdf->SetFont('', '', 5);
        $this->pdf->CellIconv(0, 2, 'наименование банка', 0, 1, 'C');
        $this->pdf->SetFont('', '', 8);
        
        $this->pdf->CellIconv(40, 4, 'Доверенность выдана', 0, 0, 'R');
        $this->pdf->CellIconv(60, 4, $worker_info['worker_post_name'], 'B', 0, 'L');
        $this->pdf->CellIconv(1, 4, '', 0, 0, 'L');
        $this->pdf->CellIconv(0, 4, $worker_info['worker_real_name'], 'B', 1, 'L');
        $this->pdf->SetFont('', '', 5);
        $this->pdf->CellIconv(40, 2, '', 0, 0, 'C');
        $this->pdf->CellIconv(60, 2, 'должность', 0, 0, 'C');
        $this->pdf->CellIconv(0, 2, 'фамилия, имя, отчество', 0, 1, 'C');
        $this->pdf->SetFont('', '', 8);
        
        $this->pdf->CellIconv(40, 4, 'Паспорт:', 0, 0, 'R');
        $this->pdf->CellIconv(0, 4, $worker_info['pasp_num'], 'B', 1, 'L');
        $this->pdf->CellIconv(40, 4, 'Кем выдан:', 0, 0, 'R');
        $this->pdf->CellIconv(0, 4, $worker_info['pasp_kem'], 'B', 1, 'L');
        $this->pdf->CellIconv(40, 4, 'Дата выдачи:', 0, 0, 'R');
        $this->pdf->CellIconv(0, 4, $worker_info['pasp_date'], 'B', 1, 'L');
        
        $this->pdf->CellIconv(40, 4, 'на получение от', 0, 0, 'R');
        $this->pdf->MultiCellIconv(0, 4, $doc_data['agent_fullname'], 'B', 'L');
        $this->pdf->SetFont('', '', 5);
        $this->pdf->CellIconv(0, 2, 'наименование поставщика', 0, 1, 'C');
        $this->pdf->SetFont('', '', 8);
        $this->pdf->CellIconv(40, 4, 'Материальных ценностей по', 0, 0, 'R');
        $this->pdf->CellIconv(0, 4, '', 'B', 1, 'L');
        $this->pdf->SetFont('', '', 5);
        $this->pdf->CellIconv(0, 2, 'наименование, номер и дата документа', 0, 1, 'C');
        $this->pdf->SetFont('', '', 8);
        $this->pdf->CellIconv(0, 4, '', 'B', 1, 'L');
        $this->pdf->ln();
        
        $this->pdf->SetFont('', '', 10);
        $this->pdf->CellIconv(0, 4, 'Перечень товарно-материальных ценностей, подлежащих получению', 0, 1, 'C');
        
        $widths = array(15, 72, 15, 88);
        $this->pdf->SetWidths($widths);
        
        $row = array(
            'Номер по порядку',
            'Материальные ценности',
            'Единица измерения',
            'Количество (прописью)'
        );
        $this->pdf->RowIconv($row);
        $row = array(1, 2, 3, 4);
        $this->pdf->RowIconv($row);
        $aligns = array('R', 'L', 'C', 'L');
        $this->pdf->SetAligns($aligns);
        
        $nomenclature = $this->doc->getDocumentNomenclature();
        $sum = 0;
        $i = 1;
        foreach ($nomenclature as $line ) {
            $sum += $line['sum'];
            
            $row = array(
                $i++,
                $line['name'],
                $line['unit_name'],
                num2str($line['cnt'], 'nul', 0)
            );
            $this->pdf->RowIconv($row);
        }
        $this->pdf->ln();
        $this->pdf->CellIconv(0, 8, 'Подпись лица, получившего доверенность ______________________ удостоверяем.', 0, 1, 'L');
        
        $this->pdf->CellIconv(30, 4, 'Руководитель', 0, 0, 'L');
        $this->pdf->CellIconv(30, 4, '', 'B', 0, 'L');
        $this->pdf->CellIconv(3, 4, '', 0, 0, 'L');
        $this->pdf->CellIconv(30, 4, $firm_vars['firm_director'], 'B', 1, 'L');
        
        $this->pdf->CellIconv(30, 4, '', 0, 0, 'L');
        $this->pdf->CellIconv(30, 4, 'Подпись', 0, 0, 'C');
        $this->pdf->CellIconv(3, 4, '', 0, 0, 'L');
        $this->pdf->CellIconv(30, 4, 'Расшифровка подписи', 0, 1, 'C');
        
        $this->pdf->CellIconv(30, 6, 'М.П.', 0, 1, 'C');
        
        $this->pdf->CellIconv(30, 4, 'Главный бухгалтер', 0, 0, 'L');
        $this->pdf->CellIconv(30, 4, '', 'B', 0, 'L');
        $this->pdf->CellIconv(3, 4, '', 0, 0, 'L');
        $this->pdf->CellIconv(30, 4, $firm_vars['firm_buhgalter'], 'B', 1, 'L');
        
        $this->pdf->CellIconv(30, 4, '', 0, 0, 'L');
        $this->pdf->CellIconv(30, 4, 'Подпись', 0, 0, 'C');
        $this->pdf->CellIconv(3, 4, '', 0, 0, 'L');
        $this->pdf->CellIconv(30, 4, 'Расшифровка подписи', 0, 1, 'C');
    }
}
