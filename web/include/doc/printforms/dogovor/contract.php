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
namespace doc\printforms\dogovor; 


class contract extends \doc\printforms\iPrintFormPdf {
 
    public function getName() {
        return "Договор";
    }

    /// Сформировать данные печатной формы
    public function make() {
        global $db;        
        require('fpdf/html2pdf.php');
        $doc_data = $this->doc->getDocDataA();
        $firm_vars = $this->doc->getFirmVarsA();
        
        $agent = new \models\agent($doc_data['agent']);
        $res = $db->query("SELECT `name`, `bik`, `rs`, `ks` FROM `doc_kassa` WHERE `ids`='bank' AND `num`='{$doc_data['bank']}'");
        $bank_info = $res->fetch_assoc();

        $wikiparser = new \WikiParser();
        $vars = $this->doc->getVariables();
        foreach($vars as $var => $obj) {
            $wikiparser->AddVariable($var, $obj['value']);
        }

        $text = $wikiparser->parse($doc_data['comment']);

        $this->pdf = new \createPDF($text, '', '', '', '');
        $this->pdf->run();

        $this->pdf = $this->pdf->pdf;

        $this->pdf->SetFont('', '', 14);
        $str = "Покупатель";
        $str = iconv('UTF-8', 'windows-1251', $str);
        $this->pdf->Cell(90, 6, $str, 0, 0, 'L', 0);
        $str = "Поставщик";
        $str = iconv('UTF-8', 'windows-1251', $str);
        $this->pdf->Cell(0, 6, $str, 0, 0, 'L', 0);

        $this->pdf->Ln(7);
        $this->pdf->SetFont('', '', 8);

        // Реквизиты поккупателя
        $str = $agent->fullname;
        if($agent->adres) {
            $str .= "\nАдрес: {$agent->adres}";
        }
        if($agent->getPhone()) {
            $str .= "\nТелефон: ".$agent->getPhone();
        }
        if($agent->inn || $agent->kpp) {
            $str .= "\n";
            if($agent->inn) {
                $str .= "ИНН: ".$agent->inn;
            }
            if($agent->kpp) {
                $str .= "КПП: ".$agent->kpp;
            }
        }
        if($agent->okpo) {
            $str .= "\nОКПО: ".$agent->okpo;
        }
        if($agent->okved) {
            $str .= "\nОКВЭД: ".$agent->okved;
        }
        if($agent->rs ||$agent->bank ||$agent->bik ||$agent->ks) {
            $str .= "\n";
            if($agent->rs) {
                $str .="Р/С:{$agent->rs}";
            }
            if($agent->bank) {
                $str .=" в банке {$agent->bank}";
            }
            if($agent->bik) {
                $str .=", БИК:{$agent->bik}";
            }
            if($agent->ks) {
                $str .=", К/С:{$agent->ks}";
            }
        }        
        $str .= "\n_______________________ / ______________________ /\n\n      М.П.";
        
        $y = $this->pdf->GetY();

        $this->pdf->MultiCellIconv(85, 4, $str, 0, 'L', 0);
        $this->pdf->SetY($y);
        $this->pdf->SetX(100);

        $str = "{$firm_vars['firm_name']}\nАдрес: {$firm_vars['firm_adres']}\nИНН/КПП {$firm_vars['firm_inn']}\nР/С:{$bank_info['rs']} в банке {$bank_info['name']}, БИК:{$bank_info['bik']}, К/С:{$bank_info['ks']}\n_________________________ / {$firm_vars['firm_director']} /\n\n      М.П.";
        $this->pdf->MultiCellIconv(0, 4, $str, 0, 'L', 0);
    }
}
