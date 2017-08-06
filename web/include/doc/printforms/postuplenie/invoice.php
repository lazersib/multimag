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
namespace doc\printforms\postuplenie; 

class invoice extends \doc\printforms\iPrintFormInvoicePdf {
 
    protected function addPartnerInfoBlock() {
        $this->addInPartnerInfoBlock();
    }

       
    /// Добавить блок с заголовком формы
    protected function addFormHeaderBlock() {
        $doc_id = $this->doc->getId();
        $doc_data = $this->doc->getDocDataA();
        $dop_data = $this->doc->getDopDataA();
        if (!$dop_data['return']) {
            $text = "Накладная N {$doc_data['altnum']}{$doc_data['subtype']} ($doc_id) от " . date("d.m.Y", $doc_data['date']);
        } else {
            $text = "Возврат от покупателя N {$doc_data['altnum']}{$doc_data['subtype']} ($doc_id) от " . date("d.m.Y", $doc_data['date']);
        }
        $this->addHeader($text);  
    }
    
    /// Добавить блок с подписями
    protected function addSignBlock() {        

        $text = "Покупатель: ____________________________________";
        $this->addSignLine($text);
        $text = "Поставщик:_____________________________________";
        $this->addSignLine($text); 
    }
  
}
