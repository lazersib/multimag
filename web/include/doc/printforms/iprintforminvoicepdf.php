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

abstract class iPrintFormInvoicePdf extends \doc\printforms\iPrintFormPdf {
    protected $form_linecount;
    protected $form_sum;    
    protected $form_summass;
    
    /// Добавить блок с информацией о партнёрах сделки
    abstract protected function addPartnerInfoBlock();
    
    public function getName() {
        return "Накладная";
    }
    
    /// Добавить блок с заголовком формы
    protected function addFormHeaderBlock() {
        $doc_id = $this->doc->getId();
        $doc_data = $this->doc->getDocDataA();
        $text = "Накладная N {$doc_data['altnum']}{$doc_data['subtype']} ($doc_id) от " . date("d.m.Y", $doc_data['date']);
        $this->addHeader($text);  
    }
    
    /// Добавить блок с информацией о поставщике и покупателе для приходной накладной
    protected function addInPartnerInfoBlock() {
        global $db;
        $doc_data = $this->doc->getDocDataA();
        $firm_vars = $this->doc->getFirmVarsA();
        $agent_data = $db->selectRow('doc_agent', $doc_data['agent']);
        $text = "Поставщик: {$agent_data['fullname']}, телефон: {$agent_data['tel']}";
        $this->addInfoLine($text);
        $text = "Покупатель: {$firm_vars['firm_name']}, телефон: {$firm_vars['firm_telefon']}";
        $this->addInfoLine($text);
        $this->pdf->Ln(3);
    }
    
    /// Добавить блок с информацией о поставщике и покупателе для расходной накладной
    protected function addOutPartnerInfoBlock() {
        global $db;
        $doc_data = $this->doc->getDocDataA();
        $firm_vars = $this->doc->getFirmVarsA();
        $agent_data = $db->selectRow('doc_agent', $doc_data['agent']);
        $text = "Поставщик: {$firm_vars['firm_name']}, телефон: {$firm_vars['firm_telefon']}";
        $this->addInfoLine($text);        
        $text = "Покупатель: {$agent_data['fullname']}, телефон: {$agent_data['tel']}";
        $this->addInfoLine($text);
        $this->pdf->Ln(3);
    }
    
    /// Добавить блок с таблицей номенклатуры
    protected function addNomenclatureTableBlock() {
        global $CONFIG;
        $nomenclature = $this->doc->getDocumentNomenclature();
        
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
            if ($this->pdf->h <= ($this->pdf->GetY() + 40 )) {
                $this->pdf->AddPage();
                $this->addTechFooter();
            }
            $this->pdf->RowIconv($row);
            $this->form_sum += $line['sum'];
            $this->form_summass += $line['mass'] * $line['cnt'];
        }    
    }
    
    /// Добавить блок с информацией о сумме документа
    /// @param $sum Сумма документа
    /// @param $cnt Количество наименований в документе
    protected function addSummaryBlock() {
        $dop_data = $this->doc->getDopDataA();
        $sum_p = number_format($this->form_sum, 2, '.', ' ');
        $text = "Итого {$this->form_linecount} наименований на сумму $sum_p руб.";
        if(isset($dop_data['mest'])) {
            if ($dop_data['mest']) {
                $text .= ", мест: " . $dop_data['mest'];
            }
        }
        $this->addInfoLine($text, 12); 
    }
   
    /// Добавить блок с информацией об оплатах
    protected function addPaymentInfoBlock() {
        global $db;
        $doc_id = $this->doc->getId();
        $rs = $db->query("SELECT SUM(`sum`) FROM `doc_list` 
            WHERE (`p_doc`='{$doc_id}' AND (`type`='4' OR `type`='6')) AND `ok`>0 AND `p_doc`!='0' GROUP BY `p_doc`");
        if ($rs->num_rows) {
            $prop_data = $rs->fetch_row();
            $pay_p = number_format($prop_data[0], 2, '.', ' ');
            $text = "Оплачено: $pay_p руб.";
            $this->addInfoLine($text);
        }
    }   
       
    /// Добавить блок с подписями
    protected function addSignBlock() {        
        $text = "Товар получил, претензий к качеству товара и внешнему виду не имею.";
        $this->addInfoLine($text);
        $text = "Покупатель: ____________________________________";
        $this->addSignLine($text);
        $text = "Поставщик:_____________________________________";
        $this->addSignLine($text); 
    }
 
    /// Сформировать печатную форму
    public function make() {
        $this->pdf->AddPage('P');
        $this->addTechFooter();
        
        $this->addFormHeaderBlock();      
        $this->addPartnerInfoBlock(); 
        $this->addNomenclatureTableBlock();
        $this->pdf->Ln();
        
        $this->addSummaryBlock();
        $this->addPaymentInfoBlock();
        $this->pdf->Ln();

        $this->addSignBlock();
        return;
    }
}
