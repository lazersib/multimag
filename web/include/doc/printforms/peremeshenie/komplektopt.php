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
namespace doc\printforms\peremeshenie; 

class komplektopt extends \doc\printforms\realizaciya\komplektopt {
    
    /// Добавить блок с заголовком формы
    protected function addFormHeaderBlock() {
        $doc_id = $this->doc->getId();
        $doc_data = $this->doc->getDocDataA();
        $text = "Накладная на комплектацию N  {$doc_data['altnum']}{$doc_data['subtype']} ($doc_id) от " . date("d.m.Y", $doc_data['date']);
        $this->addHeader($text);  
        $text = "К накладной перемещения N {$doc_data['altnum']}{$doc_data['subtype']} ({$doc_id})";
        $this->addInfoLine($text);           
    }
    
    protected function addPartnerInfoBlock() {
        global $db, $CONFIG;
        $doc_id = $this->doc->getId();
        $doc_data = $this->doc->getDocDataA();
        $dop_data = $this->doc->getDopDataA();
        $firm_vars = $this->doc->getFirmVarsA();
        
        $sklad_info = $db->selectRowA('doc_sklady', $doc_data['sklad'], array('name'));
        if (!$sklad_info) {
            throw new Exception("Исходный склад не найден!");
        }
        $from_sklad = $sklad_info['name'];
        $sklad_info = $db->selectRowA('doc_sklady', $dop_data['na_sklad'], array('name'));
        if (!$sklad_info) {
            throw new Exception("Склад назначения не найден!");
        }
        $to_sklad = $sklad_info['name'];

        $text = "Организация: {$firm_vars['firm_name']}, телефон: {$firm_vars['firm_telefon']}";
        $this->addInfoLine($text);
        $this->addInfoLine('Исходный склад: ' . $from_sklad);
        $this->addInfoLine('Склад назначения: ' . $to_sklad);
    }
}
