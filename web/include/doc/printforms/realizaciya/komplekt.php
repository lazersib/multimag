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
namespace doc\printforms\realizaciya; 

class komplekt extends \doc\printforms\iPrintFormIDPdf {
    protected $form_basesum;
    
    public function getName() {
        return "Накладная на комплектацию (опт)";
    }
    
    protected function addPartnerInfoBlock() {
        $this->addOutPartnerInfoBlock();
    }

    /// Добавить блок с заголовком формы
    protected function addFormHeaderBlock() {
        $doc_id = $this->doc->getId();
        $doc_data = $this->doc->getDocDataA();
        $dop_data = $this->doc->getDopDataA();
        $text = "Накладная на комплектацию N  {$doc_data['altnum']}{$doc_data['subtype']} ($doc_id) от " . date("d.m.Y", $doc_data['date']);
        $this->addHeader($text);  
        $text = "К накладной N {$doc_data['altnum']}{$doc_data['subtype']} ({$doc_id})";
        $this->addInfoLine($text);           
    }
    
    /// Добавить блок с таблицей номенклатуры
    protected function addNomenclatureTableBlock() {
        global $CONFIG;
        $doc_data = $this->doc->getDocDataA();
        $dop_data = $this->doc->getDopDataA();

        $nomenclature = $this->doc->getDocumentNomenclature('bulkcnt,base_price,rto');
        $pc = \PriceCalc::getInstance();
        $pc->setAgentId($doc_data['agent']);
        if(isset($dop_data['ishop'])) {
            $pc->setFromSiteFlag($dop_data['ishop']);
        }
        
        $th_widths = array(6);
        $th_texts = array('№');
        $tbody_aligns = array('R');
        if ($CONFIG['poseditor']['vc']) {
            $th_widths[] = 10;
            $th_texts[] = 'Код';
            $tbody_aligns[] = 'R';
            $th_widths[] = 70;
        } else {
            $th_widths[] = 90;
        }
        $th_texts[] = 'Наименование';
        $tbody_aligns[] = 'L';
        $th_widths = array_merge($th_widths, array(10, 12, 12, 12, 12, 14, 12, 20));
        $th_texts = array_merge($th_texts, array('Ед.', 'Кол-во', 'Склад', 'В м.уп.', 'В б.уп.', 'Резерв', 'Масса', 'Место'));
        $tbody_aligns = array_merge($tbody_aligns, array('C', 'R', 'R', 'R', 'R', 'R', 'R', 'R'));
        $this->addTableHeader($th_widths, $th_texts, $tbody_aligns);        
        
        $this->form_linecount = 0;
        $this->form_sum = $this->form_summass = 0;
        $this->form_basesum = 0;
        foreach($nomenclature as $line) {
            $this->form_linecount++;
            $price = sprintf("%01.2f р.", $line['price']);
            $sum_line = sprintf("%01.2f р.", $line['sum']);
            $row = array($this->form_linecount);
            if (@$CONFIG['poseditor']['vc']) {
                $row[] = $line['vc'];
            }
            $row[] = $line['name'];
            $row = array_merge($row, 
                array(
                    $line['unit_name'],
                    $line['cnt'],
                    round($line['base_cnt']),  
                    $line['mult'],
                    $line['mult'],
                    $line['reserve'],
                    $line['mass'],
                    $line['place'],
                ) );
            if ($this->pdf->h <= ($this->pdf->GetY() + 18 )) {
                $this->pdf->AddPage();
                $this->addTechFooter();
            }
            $this->pdf->SetFont('', '', 8);
            $this->pdf->RowIconv($row);
            $this->form_sum += $line['sum'];
            $this->form_summass += $line['mass'] * $line['cnt'];
            $this->form_basesum += $pc->getPosDefaultPriceValue($line['pos_id'], $line)*$line['cnt'];
        }    
        $pc->setOrderSum($this->form_basesum);   
    }
}
