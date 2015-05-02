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
namespace doc\printforms\postuplenie; 

class invoice extends \doc\printforms\iPrintFormPdf {
 
    public function getName() {
        return "Накладная";
    }
       
    /// Сформировать данные печатной формы
    public function make() {
        global $db, $CONFIG;
        $doc_id = $this->doc->getId();
        $doc_data = $this->doc->getDocDataA();
        $dop_data = $this->doc->getDopDataA();
        $firm_vars = $this->doc->getFirmVarsA();
        $agent_data = $db->selectRow('doc_agent', $doc_data['agent']);
        $nomenclature = $this->doc->getDocumentNomenclature('vat');
        
        $this->pdf->AddPage('P');
        $this->addTechFooter();
        
        $dt = date("d.m.Y", $doc_data['date']);
        if(!$dop_data['return']) {
            $text = "Накладная N {$doc_data['altnum']}{$doc_data['subtype']} ($doc_id), от $dt";
        } else {
            $text = "Возврат от покупателя N {$doc_data['altnum']}{$doc_data['subtype']} ($doc_id), от $dt";
        }
        $this->addHeader($text);        
        $text = "Поставщик: {$agent_data['fullname']}, телефон: {$agent_data['tel']}";
        $this->addInfoLine($text);
        $text = "Покупатель: {$firm_vars['firm_name']}, телефон: {$firm_vars['firm_telefon']}";
        $this->addInfoLine($text);
        $this->pdf->Ln(3);

        $th_widths = array(8);
        $th_texts = array('№');
        $tbody_aligns = array('R');
        if ($CONFIG['poseditor']['vc']) {
            $th_widths[] = 20;
            $th_texts[] = 'Код';
            $tbody_aligns[] = 'R';
            $th_widths[] = 86;
        } else {
            $th_widths[] = 106;
        }
        $th_texts[] = 'Наименование';
        $tbody_aligns[] = 'L';
        $th_widths = array_merge($th_widths, array(20, 16, 20, 20));
        $th_texts = array_merge($th_texts, array('Место', 'Кол-во', 'Цена', 'Сумма'));
        $tbody_aligns = array_merge($tbody_aligns, array('C', 'R', 'R', 'R'));
        $this->addTableHeader($th_widths, $th_texts, $tbody_aligns);        
        
        $i = 0;
        $sum = $cnt = $summass = $sum_vat = 0;
        foreach($nomenclature as $line) {
            $i++;
            $price = sprintf("%01.2f р.", $line['price']);
            $sum_line = sprintf("%01.2f р.", $line['sum']);
            $row = array($i);
            if (@$CONFIG['poseditor']['vc']) {
                $row[] = $line['vc'];
            }
            $row[] = $line['name'];
            $row = array_merge($row, array($line['place'], "{$line['cnt']} {$line['unit_name']}", $price, $sum_line));
            if ($this->pdf->h <= ($this->pdf->GetY() + 40 )) {
                $this->pdf->AddPage();
                $this->addTechFooter();
            }
            $this->pdf->RowIconv($row);
            $sum += $line['sum'];
            $cnt += $line['cnt'];
            $summass += $line['mass'] * $line['cnt'];
            $sum_vat += $line['vat_s'];
        }        
        $sum_p = sprintf("%01.2f руб.", $sum);
        $this->pdf->Ln();

        $text = "Итого $i наименований на сумму $sum_p";
        $this->addInfoLine($text);
        $text = "Покупатель: ____________________________________";
        $this->addSignLine($text);
        $text = "Поставщик:_____________________________________";
        $this->addSignLine($text);

        return;
    }    
}
