<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2017, BlackLight, TND Team, http://tndproject.org
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

class buisoffdesc extends buisoff {
 
    public function getName() {
        return "Предложение с описанием";
    }
       
    /// Сформировать данные печатной формы
    public function make() {
        $doc_data = $this->doc->getDocDataA();
        $firm_vars = $this->doc->getFirmVarsA();
        $text_header = $this->doc->getTextData('text_header');
        $nomenclature = $this->doc->getDocumentNomenclature('base_desc');
        
        $this->pdf->AddPage('P');
        $this->addTechFooter();        
        $this->addHeadBanner($doc_data['firm_id']);
        
        $text = "Коммерческое предложение №{$doc_data['altnum']}{$doc_data['subtype']} от " . date("d.m.Y", $doc_data['date']);
        $this->addHeader($text);
        $text = 'Поставщик: ' . $firm_vars['firm_name'] . ', тел. ' . $firm_vars['firm_telefon'];
        $this->addInfoLine($text);
        $this->pdf->Ln(4);

        if($text_header) {
            $this->addMiniHeader($text_header);
        }

        $th_widths = array(7, 85, 80, 20);
        $th_texts = array('№', 'Наименование', 'Описание', 'Цена');
        $tbody_aligns = array('R', 'L', 'L', 'R');
        $this->addTableHeader($th_widths, $th_texts, $tbody_aligns);

        $ii = 1;
        $cnt = 0;
        foreach($nomenclature as $line) {
            $row = array($ii, $line['name'], $line['base_desc'], sprintf("%0.2f р.", $line['price']));
            $this->pdf->RowIconv($row);
            $ii++;
            $cnt += $line['cnt'];
        }

        if ($this->pdf->h <= ($this->pdf->GetY() + 40)) {
            $this->pdf->AddPage();
        }

        if($firm_vars['param_nds']) {
            $this->addMiniHeader("Цены указаны с учётом НДС, за 1 ед. товара");
            $this->pdf->ln(6);
        }  
        
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
