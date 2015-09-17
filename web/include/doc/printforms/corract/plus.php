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
namespace doc\printforms\corract; 

class plus extends \doc\printforms\iPrintFormInvoicePdf {
    
    public function getName() {
        return "Акт оприходования";
    }
    
    /// Добавить блок с информацией о поставщике и покупателе для расходной накладной
    protected function addPartnerInfoBlock() {
        $firm_vars = $this->doc->getFirmVarsA();
        $text = "Организация: {$firm_vars['firm_name']}, телефон: {$firm_vars['firm_telefon']}";
        $this->addInfoLine($text);        
        $this->pdf->Ln(3);
    }

    /// Добавить блок с заголовком формы
    protected function addFormHeaderBlock() {
        $doc_id = $this->doc->getId();
        $doc_data = $this->doc->getDocDataA();
        $dop_data = $this->doc->getDopDataA();

        $text = "Оприходование товаров N {$doc_data['altnum']}{$doc_data['subtype']} ($doc_id) от " . date("d.m.Y", $doc_data['date']);

        $this->addHeader($text);  
    }
    
    /// Добавить блок с таблицей номенклатуры
    protected function addNomenclatureTableBlock() {
        global $CONFIG;
        $nomenclature = $this->doc->getDocumentNomenclature();
        
        $th_widths = array(8, 20, 100, 23, 20, 20);
        $th_texts = array('№', 'Артикул', 'Наименование', 'Кол-во',  'Цена', 'Сумма');
        
        $this->addTableHeader($th_widths, $th_texts);    
        
        $th_widths = array(8, 20, 100, 15, 8, 20, 20);
        $tbody_aligns = array('R', 'L', 'L', 'R', 'L', 'R', 'R');
        $this->pdf->SetWidths($th_widths);
        $this->pdf->SetAligns($tbody_aligns);
        
        $this->form_linecount = 0;
        $this->form_sum = $this->form_summass = 0;
        foreach($nomenclature as $line) {
            if($line['cnt']<0) {
                continue;
            }
            $this->form_linecount++;
            $price = sprintf("%01.2f р.", $line['price']);
            $sum_line = sprintf("%01.2f р.", $line['sum']);
            $row = array($this->form_linecount);
            if (@$CONFIG['poseditor']['vc']) {
                $row[] = $line['vc'];
            }
            $row[] = $line['name'];
            $row = array_merge($row, array($line['cnt'], $line['unit_name'], $price, $sum_line));
            if ($this->pdf->h <= ($this->pdf->GetY() + 40 )) {
                $this->pdf->AddPage();
                $this->addTechFooter();
            }
            $this->pdf->RowIconv($row);
            $this->form_sum += $line['sum'];
            $this->form_summass += $line['mass'] * $line['cnt'];
        } 
    }
    
    // Вывод элемента *должность/подпись/фио*
    protected function makeDPFItem($name, $step = 5, $microstep = 3) {
        $p1_w = array(35, 45, 5, 55, 5, 45, 0);
        $this->pdf->SetFont('', '', 7);
        $this->pdf->CellIconv($p1_w[0], $step, $name, 0, 0, 'L', 0); 
        $this->pdf->CellIconv($p1_w[1], $step, '', 'B', 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[2], $step, '', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[3], $step, '', 'B', 0, 'R', 0);
        $this->pdf->CellIconv($p1_w[4], $step, '', 0, 0, 'L', 0);
        $this->pdf->CellIconv($p1_w[5], $step, '', 'B', 1, 'С', 0);
        
        $this->pdf->SetFont('', '', 5);
        $this->pdf->CellIconv($p1_w[0], $microstep, '', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[1], $microstep, '(должность)', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[2], $microstep, '', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[3], $microstep, '(подпись)', 0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[4], $microstep, '',0, 0, 'C', 0);
        $this->pdf->CellIconv($p1_w[5], $microstep, '(ф.и.о.)', 0, 1, 'C', 0);
    }
    
    /// Добавить блок с подписями
    protected function addSignBlock() { 
        $this->makeDPFItem('Председатель комиссии');
        $this->pdf->ln();
        $this->makeDPFItem('Члены комиссии');
        $this->makeDPFItem('');
        $this->makeDPFItem('');
        
        $text = "Лица, ответственные за сохранность товарно-материальных ценностей";
        $this->addInfoLine($text, 7);
        $this->makeDPFItem('');
        $this->makeDPFItem('');
        $this->makeDPFItem('');
    }
    
    /// Сформировать печатную форму
    public function make() {
        $this->pdf->AddPage();
        $this->addTechFooter();
        
        $this->addFormHeaderBlock();      
        $this->addPartnerInfoBlock(); 
        $this->addNomenclatureTableBlock();
        $this->pdf->Ln();
        
        $this->addSummaryBlock();
        $this->pdf->Ln();

        $this->addSignBlock();
        return;
    }
}
