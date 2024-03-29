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

class discount extends \doc\printforms\iPrintFormIDPdf {

    /// Возвращает имя документа
    public function getName() {
        return "Детализация скидок";
    }
    
    /// Добавить блок с заголовком формы
    protected function addFormHeaderBlock() {
        $doc_id = $this->doc->getId();
        $doc_data = $this->doc->getDocDataA();
        $text = "Детализация скидок к накладной N {$doc_data['altnum']}{$doc_data['subtype']} ($doc_id) от " . date("d.m.Y", $doc_data['date']);
        $this->addHeader($text);  
    }
    
    protected function addPartnerInfoBlock() {
        $this->addOutPartnerInfoBlock();
    }
    
    /// Добавить блок с таблицей номенклатуры
    protected function addNomenclatureTableBlock() {
        global $CONFIG;
        $doc_data = $this->doc->getDocDataA();
        $dop_data = $this->doc->getDopDataA();

        $nomenclature = $this->doc->getDocumentNomenclature('base_price,bulkcnt');
        $pc = \PriceCalc::getInstance();
        $pc->setFirmId($doc_data['firm_id']);
        $pc->setAgentId($doc_data['agent']);
        $pc->setUserId($doc_data['user']);
        if(isset($dop_data['ishop'])) {
            $pc->setFromSiteFlag($dop_data['ishop']);
        }
        
        $th_widths = array(8);
        $th_texts = array('№');
        $tbody_aligns = array('R');
        if ($CONFIG['poseditor']['vc']) {
            $th_widths[] = 20;
            $th_texts[] = 'Код';
            $tbody_aligns[] = 'R';
            $th_widths[] = 76;
        } else {
            $th_widths[] = 96;
        }
        $th_texts[] = 'Наименование';
        $tbody_aligns[] = 'L';
        $th_widths = array_merge($th_widths, array(16,20,20,18,15,26,23,26));
        $th_texts = array_merge($th_texts, array('Кол-во', 'Цена б/ск.', 'Цена со ск.', 'Ск. р.', 'Ск., %', 'Сумма б/ск.', 'Сумма ск.', 'Сумма со ск.'));
        $tbody_aligns = array_merge($tbody_aligns, array('R','R','R','R','R','R','R','R'));
        $this->addTableHeader($th_widths, $th_texts, $tbody_aligns);        
        
        $this->form_linecount = 0;
        $this->form_sum = $this->form_summass = 0;
        $this->form_basesum = 0;
        foreach ($nomenclature as $line) {
            $this->form_linecount++;
            $row = array($this->form_linecount);
            if ($CONFIG['poseditor']['vc']) {
                $row[] = $line['vc'];
            }
            $row[] = $line['name'];

            $def_price = $pc->getPosDefaultPriceValue($line['pos_id']);
            $skid = round($def_price - $line['price'], 2);
            $skid_p = round($skid / $def_price * 100, 2);
            $sum_line = $line['cnt'] * $line['price'];
            $def_sum_line = $line['cnt'] * $def_price;
            $skid_sum_line = $line['cnt'] * $skid;

            $def_price_s = sprintf("%01.2f руб.", $def_price);
            $price_s = sprintf("%01.2f руб.", $line['price']);
            $skid_s = sprintf("%01.2f руб.", $skid);
            $skid_p_s = sprintf("%01.2f %%", $skid_p);
            $sum_line_s = sprintf("%01.2f руб.", $sum_line);
            $def_sum_line_s = sprintf("%01.2f руб.", $def_sum_line);
            $skid_sum_line_s = sprintf("%01.2f руб.", $skid_sum_line);

            $row = array_merge($row, array($line['cnt'] . ' ' . $line['unit_name'], $def_price_s, $price_s, $skid_s, $skid_p_s, $def_sum_line_s,
                $skid_sum_line_s, $sum_line_s));
            $this->controlPageBreak();
            $this->pdf->SetFont('', '', 8);
            $this->pdf->RowIconv($row);
            $this->form_sum += $line['sum'];
            $this->form_summass += $line['mass'] * $line['cnt'];
            $this->form_basesum += $pc->getPosDefaultPriceValue($line['pos_id'], $line)*$line['cnt'];
        }    
        $pc->setOrderSum($this->form_basesum);   
    }
    
    /// Сформировать данные печатной формы
    public function make() {
        $this->addPage('L');
        
        $this->addFormHeaderBlock();      
        $this->addPartnerInfoBlock(); 
        $this->addNomenclatureTableBlock();
        $this->pdf->Ln();
        
        $this->addSummaryBlock();
        $this->addPaymentInfoBlock();
        $this->addDiscountInfoBlock();

        return;
    }
}
