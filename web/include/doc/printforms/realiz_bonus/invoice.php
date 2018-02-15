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
namespace doc\printforms\realiz_bonus; 

class invoice extends \doc\printforms\iPrintFormInvoicePdf {
    
    protected function addPartnerInfoBlock() {
        $this->addOutPartnerInfoBlock();
    }

    /// Добавить блок с заголовком формы
    protected function addFormHeaderBlock() {
        $doc_id = $this->doc->getId();
        $doc_data = $this->doc->getDocDataA();
        $text = "Бонусная накладная N {$doc_data['altnum']}{$doc_data['subtype']} ($doc_id) от " . date("d.m.Y", $doc_data['date']);
        $this->addHeader($text);  
    }
    
    /// Добавить блок с информацией о сумме документа
    /// @param $sum Сумма документа
    /// @param $cnt Количество наименований в документе
    protected function addSummaryBlock() {
        $doc_data = $this->doc->getDocDataA();
        $dop_data = $this->doc->getDopDataA();
        $sum_p = number_format($this->form_sum, 2, '.', ' ');
        $text = "Итого {$this->form_linecount} наименований на сумму $sum_p бонусных баллов.";
        if(isset($dop_data['mest'])) {
            if ($dop_data['mest']) {
                $text .= ", мест: " . $dop_data['mest'];
            }
        }
        $this->addInfoLine($text, 12); 
        $text = "Бонусный баланс: " . docCalcBonus($doc_data['agent']) . " бонусных баллов.";
        $this->addInfoLine($text);
    }
   
    /// Добавить блок с информацией об оплатах
    protected function addPaymentInfoBlock() {
    }

}
