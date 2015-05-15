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
namespace doc\printforms; 

abstract class iPrintFormIDPdf extends \doc\printforms\iPrintFormInvoicePdf {
    protected $form_basesum;
    
    /// Добавить блок с таблицей номенклатуры
    protected function addNomenclatureTableBlock() {
        global $CONFIG;
        $doc_data = $this->doc->getDocDataA();
        $dop_data = $this->doc->getDopDataA();

        $nomenclature = $this->doc->getDocumentNomenclature('base_price,bulkcnt');
        $pc = \PriceCalc::getInstance();
        $pc->setAgentId($doc_data['agent']);
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
            $row = array_merge($row, array($line['place'], "{$line['cnt']} {$line['unit_name']}", $price, $sum_line));
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
    
    /// Добавить блок с информацией с текущими и возможными скидками
    protected function addDiscountInfoBlock() {
        $dop_data = $this->doc->getDopDataA();
        $pc = \PriceCalc::getInstance();
        if ($this->form_sum < $this->form_basesum) {
            if ($dop_data['cena']) {
                $text = '';
            } else {
                $text = 'Ваша цена: ' . $pc->getCurrentPriceName() . '. ';
            }
            $sk_p = number_format($this->form_basesum - $this->form_sum, 2, '.', ' ');
            $text .= "Размер скидки: $sk_p руб.";
            $this->addInfoLine($text);
        }

        $next_price_info = $pc->getNextPriceInfo();
        if ($next_price_info) {
            if ($next_price_info['incsum'] < ($this->form_basesum / 5)) { // Если надо докупить на сумму менее 20%
                $next_sum_p = number_format($next_price_info['incsum'], 2, '.', ' ');
                $text = "При увеличении суммы покупки на $next_sum_p руб., вы можете получить цену \"{$next_price_info['name']}\"!";
                $this->addInfoLine($text);
            }
        }

        $next_periodic_price_info = $pc->getNextPeriodicPriceInfo();
        if ($next_periodic_price_info) {
            if ($next_periodic_price_info['incsum'] < ($this->form_basesum / 5)) { // Если надо докупить на сумму менее 20%
                $next_sum_p = number_format($next_periodic_price_info['incsum'], 2, '.', ' ');
                $text = "При осуществлении дополнительных оплат за {$next_periodic_price_info['period']} на $next_sum_p руб.,"
                    . " вы получите цену \"{$next_periodic_price_info['name']}\"!";
                $this->addInfoLine($text);
            }
        }
    }
    
    /// Сформировать данные печатной формы
    public function make() {
        $this->pdf->AddPage('P');
        $this->addTechFooter();
        
        $this->addFormHeaderBlock();      
        $this->addPartnerInfoBlock(); 
        $this->addNomenclatureTableBlock();
        $this->pdf->Ln();
        
        $this->addSummaryBlock();
        $this->addPaymentInfoBlock();
        $this->addDiscountInfoBlock();
        $this->pdf->Ln();

        $this->addSignBlock();
        return;
    }    
}
