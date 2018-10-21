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
namespace doc\printforms\specific; 

class invoice_for_contract extends \doc\printforms\iPrintFormPdf {
 
    public function getName() {
        return "Спецификация к договору";
    }
       
    /// Сформировать данные печатной формы
    public function make() {
        global $db;
        $doc_data = $this->doc->getDocDataA();
        $dop_data = $this->doc->getDopDataA();
        $firm_vars = $this->doc->getFirmVarsA();
        $agent = new \models\agent($doc_data['agent']);
        $nomenclature = $this->doc->getDocumentNomenclature('vat');
        
        $this->addPage();   
        
        $contract_id = null;
        if($doc_data['contract']) {
            $contract_id = $doc_data['contract'];
        }
        else {
            $res = $db->query("SELECT `id` FROM `doc_list` WHERE `id`='{$doc_data['p_doc']}' AND `type`='14'");
            if($res->num_rows) {
                list($contract_id) = $res->fetch_row();
            }    
            else {
                throw new \Exception("Не задан договор");
            }
        }                
        
        $contract = new \doc_Dogovor($contract_id);
        $contract_data = $contract->getDocDataA();
        $c_dopdata = $contract->getDopDataA();
        $text = "Приложеие №1\nк " . $c_dopdata['name'] . " №" . $contract_data['altnum'] . "\nот " . date("d.m.Y", $contract_data['date'])." г.";
        $this->addRightPreHeader($text);        
        
        $dt = date("d.m.Y", $doc_data['date']);
        $str = 'Спецификация № ' . $doc_data['altnum'];
        $this->addHeader($str);
        $str = ' от ' . $dt;
        $this->addMiniHeader($str);   
        $this->pdf->Ln(3); 
        
        $text = "{$firm_vars['firm_name']}, именуемое  в  дальнейшем «Поставщик», в лице директора {$firm_vars['firm_director_r']},"
        . " действующего на основании {$firm_vars['firm_leader_reason_r']}, с одной стороны,"
        . " и {$agent->fullname}, именуемое в дальнейшем «Покупатель» в лице {$agent->leader_post_r} {$agent->leader_name_r},"
        . " действующего на основании {$agent->leader_reason_r}, с другой стороны, вместе именуемые Стороны,"
        . " согласовали настоящую Спецификацию о поставке:";
        $this->addInfoLine($text);
        $this->pdf->Ln(5);      
        
        $th_widths = [7, 110, 12, 12, 24, 22];
        $th_texts = ['№', 'Наименование', 'Ед.изм.', 'Кол-во', 'Цена без НДС', 'Сумма с НДС'];
        $tbody_aligns = ['R', 'L', 'C', 'R', 'R', 'R'];
        $this->addTableHeader($th_widths, $th_texts, $tbody_aligns, 9);        
        
        $i = 0;
        $sum = $cnt = $summass = $sum_vat = 0;
        foreach($nomenclature as $line) {
            $i++;
            $price_wo_vat = sprintf("%01.2f р.", $line['price_wo_vat']);
            $price = sprintf("%01.2f р.", $line['price']);
            $sum_line = sprintf("%01.2f р.", $line['sum']);
            $row = [$i, $line['name'], $line['unit_name'], $line['cnt'], $price_wo_vat, $sum_line];
            $this->controlPageBreak(30);
            $this->pdf->SetFont('', '', 8);
            $this->pdf->RowIconv($row);
            $sum += $line['sum'];
            $cnt += $line['cnt'];
            $summass += $line['mass'] * $line['cnt'];
            $sum_vat += $line['vat_s'];
        }
        $sumcost = sprintf("%01.2f р.", $sum);
        $sumvat = sprintf("%01.2f р.", $sum_vat);
        
        $right_col_w = $th_widths[count($th_widths)-1];
        $left_col_w = array_sum($th_widths) - $right_col_w;
        $this->pdf->SetWidths([$left_col_w, $right_col_w]);
        $this->pdf->SetAligns(['R', 'R']);
        $this->pdf->RowIconv(['Итого:', $sumcost]);
        $this->pdf->RowIconv(['В том числе НДС:', $sumvat]);
        $this->addInfoLine("Общая стоимость по Спецификации №{$doc_data['altnum']} составляет: $sum рублей, с учетом НДС", 11);
        $this->controlPageBreak(50);
              
        if ($doc_data['comment']) {
            $text = str_replace("<br>", ", ", $doc_data['comment']);
            $this->addInfoLine($text, 11);
            $this->pdf->Ln(3);
        }
        $this->pdf->Ln(5);
        
        $this->pdf->SetWidths([60, 127]);
        $this->pdf->SetAligns(['L', 'L']);
        $this->pdf->SetFont('', '', 8);
        
        $this->pdf->RowIconv(['Срок поставки товара', @$dop_data['delivery_time'].' денй']);
        $this->pdf->RowIconv(['Грузоотправитель', "{$firm_vars['firm_name']}\nАдрес грузоотправителя: {$firm_vars['firm_adres']}" ]);
        $this->pdf->RowIconv(['Грузополучатель', "{$agent->fullname}\nАдрес получателя: {$agent->adres}" ]);
        $this->pdf->RowIconv(['Доставка товара осуществляется', "{$firm_vars['firm_name']}" ]);
        $this->pdf->RowIconv(['Стоимость транспортных расходов и порядок оплаты', "Поставщик несет все расходы по транспортировке Товара до пункта назначения. Покупатель самостоятельно несет расходы по разгрузке Товара с прибывшего транспортного средства." ]);
        $this->pdf->RowIconv(['Гарантийный срок на товар', @$dop_data['warranty_time']." дней с момента подписания сторонами Акта приема-передачи товара" ]);
        
        $this->pdf->Ln(4);
        
        $this->pdf->SetWidths([$left_col_w, $right_col_w]);
        $this->pdf->SetAligns(['R', 'R']);
        
        
        $this->pdf->SetFont('', '', 16);
        $str = "Поставщик";        
        $this->pdf->CellIconv(95, 6, $str, 0, 0, 'L', 0);
        $str = "Покупатель";
        $this->pdf->CellIconv(0, 6, $str, 0, 0, 'L', 0);

        $this->pdf->Ln(5);
        $this->pdf->SetFont('', '', 10);
        $y = $this->pdf->GetY();
        
        $this->pdf->MultiCellIconv(90, 5, $firm_vars['firm_name'], 0, 'L', 0);        
        
        $this->pdf->Ln(15);
        $this->pdf->CellIconv(40, 6, $firm_vars['firm_leader_post'], 'B', 0, 'L', 0);
        $this->pdf->CellIconv(45, 6, $firm_vars['firm_director'], 'B', 0, 'R', 0);
        
        $this->pdf->SetY($y);
        $this->pdf->SetX(105);
        
        $this->pdf->MultiCellIconv(90, 5, $agent->fullname, 0, 'L', 0); 
        $this->pdf->Ln(15);
        $this->pdf->SetX(105);
        $this->pdf->CellIconv(40, 6, $agent->leader_post, 'B', 0, 'L', 0);
        $this->pdf->CellIconv(50, 6, $agent->leader_name, 'B', 0, 'R', 0);
        
        $delta = $this->pdf->h - ($this->pdf->GetY() + 55);
        if ($delta > 17) {
            $delta = 17;
        }

        if (\cfg::get('site', 'doc_leader_shtamp')) {
            $shtamp_img = str_replace('{FN}', $doc_data['firm_id'], \cfg::get('site', 'doc_leader_shtamp'));
            $this->pdf->Image($shtamp_img, 4, $this->pdf->GetY() + $delta, 120);
        }
        return;
    }    
}
