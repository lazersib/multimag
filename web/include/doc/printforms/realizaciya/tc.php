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
namespace doc\printforms\realizaciya; 

class tc extends \doc\printforms\iPrintFormIDPdf {
    protected $show_agent = 1;  ///< Выводить ли информацию о агенте-покупателе
    protected $show_disc = 1;   ///< Выводить ли информацию о скидках
    protected $show_kkt = 1;    ///< Выводить ли информацию о работе без использования ККТ
    
    /// Возвращает имя документа
    public function getName() {
        return "Товарный чек";
    }
    
    /// Выводить ли информацию о скидках
    public function showDiscount($flag) {
        $this->show_disc = $flag;
    }
    
    /// Выводить ли информацию о агенте-покупателе
    public function showAgent($flag) {
        $this->show_agent = $flag;
    }
    
    /// Выводить ли информацию о работе без использования ККТ
    public function showKKT($flag) {
        $this->show_kkt = $flag;
    }
    
    /// Добавить блок о продавце и покупателе
    protected function addPartnerInfoBlock() {
        $doc_data = $this->doc->getDocDataA();
        $firm_vars = $this->doc->getFirmVarsA();
        $agent = new \models\agent($doc_data['agent']);
        $text = "Поставщик: {$firm_vars['firm_name']}, ИНН: {$firm_vars['firm_inn']}, адрес: {$firm_vars['firm_adres']}, телефон: {$firm_vars['firm_telefon']}";
        if($firm_vars['firm_regnum']) {
            if($firm_vars['firm_type']=='ip' && $firm_vars['firm_regdate']) {
                $text.=", свидетельство о постановке на учет N {$firm_vars['firm_regnum']} от {$firm_vars['firm_regdate']}";
            } elseif($firm_vars['firm_type']=='ooo') {
                $text.=", ЕГРЮЛ {$firm_vars['firm_regnum']}";
            }
        }
        $this->addInfoLine($text);  
        $this->pdf->Ln(1);
        if($this->show_agent) {
            $text = "Покупатель: {$agent->fullname}, телефон: ".$agent->getPhone();
            $this->addInfoLine($text);
        }
        $this->pdf->Ln(3);
    }

    /// Добавить блок с заголовком формы
    protected function addFormHeaderBlock() {
        $doc_data = $this->doc->getDocDataA();
        $text = "Товарный чек N {$doc_data['altnum']}{$doc_data['subtype']} от " . date("d.m.Y", $doc_data['date']);
        $this->addHeader($text);  
    }
    
    /// Добавить блок с информацией с текущими и возможными скидками
    protected function addDiscountInfoBlock() {
        if($this->show_disc) {
            parent::addDiscountInfoBlock();
        }
        if($this->show_kkt) {
            $text = "Работа осуществляется без применения контрольно-кассовой техники в соответствии с ФЗ 162 от 07.07.2009.";
            $this->addInfoLine($text, 12); 
        }
    }    
}
