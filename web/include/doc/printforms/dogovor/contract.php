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
namespace doc\printforms\dogovor; 


class contract extends \doc\printforms\iPrintFormPdf {
 
    public function getName() {
        return "Договор";
    }

    /// Сформировать данные печатной формы
    public function make() {
        global $db;        
        require('fpdf/html2pdf.php');
        $doc_id = $this->doc->getId();
        $doc_data = $this->doc->getDocDataA();
        $dop_data = $this->doc->getDopDataA();
        $firm_vars = $this->doc->getFirmVarsA();
        
        $agent_info = $db->selectRow('doc_agent', $doc_data['agent']);
        $res = $db->query("SELECT `name`, `bik`, `rs`, `ks` FROM `doc_kassa` WHERE `ids`='bank' AND `num`='{$doc_data['bank']}'");
        $bank_info = $res->fetch_assoc();

        $wikiparser = new \WikiParser();

        $wikiparser->AddVariable('DOCNUM', $doc_data['altnum']);
        $wikiparser->AddVariable('DOCDATE', date("d.m.Y", $doc_data['date']));
        $wikiparser->AddVariable('AGENT', $agent_info['fullname']);
        $wikiparser->AddVariable('AGENTDOL', 'директора');
        $wikiparser->AddVariable('AGENTFIO', $agent_info['dir_fio_r']);
        $wikiparser->AddVariable('FIRMNAME', $firm_vars['firm_name']);
        $wikiparser->AddVariable('FIRMDIRECTOR', @$firm_vars['firm_director_r']);
        $wikiparser->AddVariable('ENDDATE', $dop_data['end_date']);

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

        $str = @"{$agent_info['fullname']}\nАдрес: {$agent_info['adres']}\nТелефон: {$agent_info['tel']}\nИНН:{$agent_info['inn']}, КПП:{$agent_info['kpp']}, ОКПО:{$agent_info['okpo']}, ОКВЭД:{$agent_info['okved']}\nР/С:{$agent_info['rs']} в банке {$agent_info['bank']}, БИК:{$agent_info['bik']}, К/С:{$agent_info['ks']}\n_______________________ / ______________________ /\n\n      М.П.";
        $str = iconv('UTF-8', 'windows-1251', $str);

        $y = $this->pdf->GetY();

        $this->pdf->MultiCell(85, 4, $str, 0, 'L', 0);
        $this->pdf->SetY($y);
        $this->pdf->SetX(100);

        $str = "{$firm_vars['firm_name']}\nАдрес: {$firm_vars['firm_adres']}\nИНН/КПП {$firm_vars['firm_inn']}\nР/С:{$bank_info['rs']} в банке {$bank_info['name']}, БИК:{$bank_info['bik']}, К/С:{$bank_info['ks']}\n_________________________ / {$firm_vars['firm_director']} /\n\n      М.П.";
        $str = iconv('UTF-8', 'windows-1251', $str);
        $this->pdf->MultiCell(0, 4, $str, 0, 'L', 0);
    }
}
