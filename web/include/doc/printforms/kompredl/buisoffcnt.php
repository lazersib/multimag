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
namespace doc\printforms\kompredl; 

class buisoffcnt extends buisoff {
    var $form_sum = 0;
    var $form_summass = 0;
    public function getName() {
        return "Предложение с количеством";
    }
       
    /// Добавить блок с информацией о сумме документа
    protected function addSummaryBlock() {
        $dop_data = $this->doc->getDopDataA();
        $sum_p = number_format($this->form_sum, 2, '.', ' ');
        $mass_p = number_format($this->form_summass, 3, '.', ' ');
        $text = "Итого {$this->form_linecount} наименований массой $mass_p кг. на сумму $sum_p руб.";
        if(isset($dop_data['mest'])) {
            if ($dop_data['mest']) {
                $text .= ", мест: " . $dop_data['mest'];
            }
        }
        $this->addInfoLine($text, 12); 
    }
    
    /// Сформировать данные печатной формы
    public function make() {
        global $db;
        $doc_id = $this->doc->getId();
        $doc_data = $this->doc->getDocDataA();
        $dop_data = $this->doc->getDopDataA();
        $firm_vars = $this->doc->getFirmVarsA();
        $nomenclature = $this->doc->getDocumentNomenclature('comment');
        
        $this->pdf->AddPage('P');
        $this->addTechFooter();        
        $this->addHeadBanner($doc_data['firm_id']);
        
        $text = "Коммерческое предложение №{$doc_data['altnum']}{$doc_data['subtype']} от " . date("d.m.Y", $doc_data['date']);
        $this->addHeader($text);
        $text = 'Поставщик: ' . $firm_vars['firm_name'] . ', тел. ' . $firm_vars['firm_telefon'];
        $this->addInfoLine($text);
        $this->pdf->Ln(4);

        if($dop_data['shapka']) {
            $this->addMiniHeader($dop_data['shapka']);
        }

        $th_widths = array(7, 80, 15, 15, 35, 20, 20);
        $th_texts = array('№', 'Наименование', 'Масса', 'Кол-во', 'Срок поставки', 'Цена', 'Сумма');
        $tbody_aligns = array('R', 'L', 'R', 'R', 'L', 'R', 'R');
        $this->addTableHeader($th_widths, $th_texts, $tbody_aligns);

        $ii = 0;
        $cnt = $this->form_sum = 0;
        foreach($nomenclature as $line) {
            $cnt += $line['cnt'];
            $this->form_sum += $line['sum'];
            $this->form_summass += $line['mass'] * $line['cnt'];
            $ii++;
            $row = array(
                $ii, 
                $line['name'], 
                $line['mass'].' кг.', 
                $line['cnt'] .' '.$line['unit_name'], 
                $line['comment'], 
                sprintf("%0.2f р.", $line['price']),
                sprintf("%0.2f р.", $line['sum'])
            );
            $this->pdf->RowIconv($row);
                        
        }
        $this->form_linecount = $ii;
        if ($this->pdf->h <= ($this->pdf->GetY() + 40)) {
            $this->pdf->AddPage();
        }
        $this->addSummaryBlock();
        $this->addMiniHeader("Цены указаны с учётом НДС, за 1 ед. товара");
        $this->pdf->ln(6);
        
        if ($doc_data['comment']) {
            $this->pdf->SetFont('', '', 10);
            $this->pdf->Ln(5);
            $this->pdf->MultiCellIconv(0, 5, $doc_data['comment'], 0, 'L', 0);
            $this->pdf->Ln(5);
        }
        $this->addSiteBanner();
        $this->addWorkerInfo($doc_data);
    }    
}
