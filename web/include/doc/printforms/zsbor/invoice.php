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
namespace doc\printforms\zsbor; 

class invoice extends \doc\printforms\iPrintFormInvoicePdf {
    
    public function getName() {
        return "Заявка на производство";
    }
    
    protected function addPartnerInfoBlock() {
        $firm_vars = $this->doc->getFirmVarsA();
        $text = "Поставщик: {$firm_vars['firm_name']}, телефон: {$firm_vars['firm_telefon']}";
        $this->addInfoLine($text);                
        $this->pdf->Ln(3);
    }

    /// Добавить блок с заголовком формы
    protected function addFormHeaderBlock() {
        $doc_id = $this->doc->getId();
        $doc_data = $this->doc->getDocDataA();
        $dop_data = $this->doc->getDopDataA();
        $text = "Заявка на производство N {$doc_data['altnum']}{$doc_data['subtype']} ($doc_id) от " . date("d.m.Y", $doc_data['date']);
        $this->addHeader($text);  
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
        return;
    }
}
