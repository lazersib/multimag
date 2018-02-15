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
namespace doc\printforms\peremeshenie; 

class invoice extends \doc\printforms\iPrintFormPdf {
 
    public function getName() {
        return "Накладная перемещения";
    }
       
    /// Сформировать данные печатной формы
    public function make() {
        global $db, $CONFIG;
        $doc_id = $this->doc->getId();
        $doc_data = $this->doc->getDocDataA();
        $dop_data = $this->doc->getDopDataA();
        $firm_vars = $this->doc->getFirmVarsA();
        $nomenclature = $this->doc->getDocumentNomenclature('dest_place,comment');
        
        $sklad_info = $db->selectRowA('doc_sklady', $doc_data['sklad'], array('name'));
        if(! $sklad_info )	throw new Exception("Исходный склад не найден!");
        $from_sklad = $sklad_info['name'];
        $sklad_info = $db->selectRowA('doc_sklady', $dop_data['na_sklad'], array('name'));
        if(! $sklad_info )	throw new Exception("Склад назначения не найден!");
        $to_sklad = $sklad_info['name'];
        
        $this->pdf->AddPage('P');
        $this->addTechFooter();
        
        $dt = date("d.m.Y", $doc_data['date']);
        $text = "Накладная перемещения N {$doc_data['altnum']}{$doc_data['subtype']} ($doc_id) от $dt";
        $this->addHeader($text);
        $text = "Организация: {$firm_vars['firm_name']}, телефон: {$firm_vars['firm_telefon']}";
        $this->addInfoLine($text);
        $this->addInfoLine('Исходный склад: ' . $from_sklad);
        $this->addInfoLine('Склад назначения: ' . $to_sklad);
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
        $th_widths = array_merge($th_widths, array(22, 22, 16, 18));
        $th_texts = array_merge($th_texts, array('Место ист.', 'Место назн.', 'Кол-во', 'Об. масса'));
        $tbody_aligns = array_merge($tbody_aligns, array('C', 'C', 'R', 'R'));
        $this->addTableHeader($th_widths, $th_texts, $tbody_aligns);        
        
        $i = 0;
        $cnt = $summass = $sum_vat = 0;
        foreach($nomenclature as $line) {
            $i++;
            $row = array($i);
            $comm = array('');
            if (@$CONFIG['poseditor']['vc']) {
                $row[] = $line['vc'];
                $comm[] = '';
            }
            $row[] = $line['name'];
            $comm[] = $line['comment'];
            $row = array_merge($row, array($line['place'], $line['dest_place'], "{$line['cnt']} {$line['unit_name']}", $line['mass'] * $line['cnt']));
            if ($this->pdf->h <= ($this->pdf->GetY() + 40 )) {
                $this->pdf->AddPage();
                $this->addTechFooter();
            }
            $comm  = array_merge($comm, array('', '',  '', ''));
            $this->pdf->RowIconvCommented($row, $comm);
            $cnt += $line['cnt'];
            $summass += $line['mass'] * $line['cnt'];
        }        
        $this->pdf->Ln();
        $text = "Всего $i наименований количеством $cnt единиц и общей массой " . sprintf("%01.3f", $summass) . " кг.";
        $this->addInfoLine($text);
        $text = "Выдал кладовщик: ____________________________________";
        $this->addSignLine($text);
        $text = "Вид, количество и масса принятого товара совпадает с накладной. Внешние дефекты не обнаружены.";
        $this->addInfoLine($text);
        $text = "Принял кладовщик:_____________________________________";
        $this->addSignLine($text);
        return;
    }    
}
