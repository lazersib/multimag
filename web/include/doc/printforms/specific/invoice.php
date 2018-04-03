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

class invoice extends \doc\printforms\iPrintFormPdf {
 
    public function getName() {
        return "Спецификация";
    }
       
    /// Сформировать данные печатной формы
    public function make() {
        global $db, $CONFIG;
        $doc_data = $this->doc->getDocDataA();
        $firm_vars = $this->doc->getFirmVarsA();
        $agent = new \models\agent($doc_data['agent']);
        $nomenclature = $this->doc->getDocumentNomenclature('vat');
        
        $this->pdf->AddPage('P');
        $this->addTechFooter();        
        $this->addHeadBanner($doc_data['firm_id']);
        
        $dres = $db->query("SELECT `altnum`, `date` FROM `doc_list` WHERE `id`='{$doc_data['p_doc']}'");
        $dog = $dres->fetch_assoc();
        if ($dog) {
            $dog['date'] = date("Y-m-d", $dog['date']);
            $text = "К договору N{$dog['altnum']} от {$dog['date']}";
            $this->addInfoLine($text);
        }
        elseif ($doc_data['contract']) {
            $contract = new \doc_Dogovor($doc_data['contract']);
            $contract_data = $contract->getDocDataA();
            $text = 'К договору № ' . $contract_data['altnum'] . ' от ' . date("d.m.Y", $contract_data['date']);
            $this->addInfoLine($text);
        }
        
        $dt = date("d.m.Y", $doc_data['date']);
        $str = 'Спецификация № ' . $doc_data['altnum'] . ' от ' . $dt;
        $this->addHeader($str);
        $str = "на поставку продукции";
        $this->addHeader($str);        
        
        $text = "Поставщик: {$firm_vars['firm_name']}, {$firm_vars['firm_adres']}, тел: {$firm_vars['firm_telefon']}";
        $this->addInfoLine($text);
        $this->pdf->Ln(3);      
        
        $th_widths = array(7);
        $th_texts = array('№');
        $tbody_aligns = array('R');
        if(\cfg::get('poseditor', 'vc')) {
            $th_widths[] = 15;
            $th_texts[] = 'Код';
            $tbody_aligns[] = 'R';
            $th_widths[] = 76;
        } else {
            $th_widths[] = 96;
        }
        $th_texts[] = 'Наименование';
        $tbody_aligns[] = 'L';
        $th_widths = array_merge($th_widths, array(12, 12, 24, 21, 22));
        $th_texts = array_merge($th_texts, array('Ед.изм.', 'Кол-во', 'Цена без НДС', 'Цена с НДС', 'Сумма с НДС'));
        $tbody_aligns = array_merge($tbody_aligns, array('C', 'R', 'R', 'R', 'R'));
        $this->addTableHeader($th_widths, $th_texts, $tbody_aligns, 9);        
        
        $i = 0;
        $sum = $cnt = $summass = $sum_vat = 0;
        foreach($nomenclature as $line) {
            $i++;
            $price_wo_vat = sprintf("%01.2f р.", $line['price_wo_vat']);
            $price = sprintf("%01.2f р.", $line['price']);
            $sum_line = sprintf("%01.2f р.", $line['sum']);
            $row = array($i);
            if (@$CONFIG['poseditor']['vc']) {
                $row[] = $line['vc'];
            }
            $row[] = $line['name'];
            $row = array_merge($row, array($line['unit_name'], $line['cnt'], $price_wo_vat, $price, $sum_line));
            if ($this->pdf->h <= ($this->pdf->GetY() + 40 )) {
                $this->pdf->AddPage();
                $this->addTechFooter();
            }
            $this->pdf->SetFont('', '', 8);
            $this->pdf->RowIconv($row);
            $sum += $line['sum'];
            $cnt += $line['cnt'];
            $summass += $line['mass'] * $line['cnt'];
            $sum_vat += $line['vat_s'];
        }
        
        $cost = num2str($sum);
        $sumcost = sprintf("%01.2f", $sum);
        $summass = sprintf("%01.3f", $summass);

        if ($this->pdf->h <= ($this->pdf->GetY() + 60)) {
            $this->pdf->AddPage();
            $this->addTechFooter();
        }
        
        $delta = $this->pdf->h - ($this->pdf->GetY() + 55);
        if ($delta > 17) {
            $delta = 17;
        }
        
        if ($doc_data['comment']) {
            $text = str_replace("<br>", ", ", $doc_data['comment']);
            $this->addInfoLine($text, 11);
            $this->pdf->Ln(3);
        }
        
        $this->pdf->Ln(2);
        $allsum_p = sprintf("%01.2f", $sumcost);
        $str = "Общая сумма спецификации N {$this->doc_data['altnum']} с учетом НДС составляет $allsum_p рублей.";
        $this->addInfoLine($str, 11);
        $nds_sum_p = sprintf("%01.2f", $sum_vat);
        $str = "Сумма НДС составляет $nds_sum_p рублей.";
        $this->addInfoLine($str, 11);
        $this->pdf->Ln(7);
        
        $this->pdf->SetFont('', '', 16);
        $str = "Покупатель";
        $str = iconv('UTF-8', 'windows-1251', $str);
        $this->pdf->Cell(90, 6, $str, 0, 0, 'L', 0);
        $str = "Поставщик";
        $str = iconv('UTF-8', 'windows-1251', $str);
        $this->pdf->Cell(0, 6, $str, 0, 0, 'L', 0);

        $this->pdf->Ln(5);
        $this->pdf->SetFont('', '', 10);

        $phone = $agent->getPhone();
        $str = "{$agent->fullname}\n{$agent->adres}, тел. {$phone}\nИНН {$agent->inn}, КПП {$agent->kpp} ОКПО {$agent->okpo}, ОКВЭД {$agent->okved}\n".
                "Р/С {$agent->rs}, в банке {$agent->bank}\nК/С {$agent->ks}, БИК {$agent->bik}\n__________________ / _________________ /\n\n      М.П.";
        $str = iconv('UTF-8', 'windows-1251', $str);

        $y = $this->pdf->GetY();

        $this->pdf->MultiCell(90, 5, $str, 0, 'L', 0);
        $this->pdf->SetY($y);
        $this->pdf->SetX(100);

        $res = $db->query("SELECT `name`, `bik`, `rs`, `ks` FROM `doc_kassa` WHERE `ids`='bank' AND `num`='{$doc_data['bank']}'");
        $bank_info = $res->fetch_assoc();

        $str = "{$firm_vars['firm_name']}\n{$firm_vars['firm_adres']}\nИНН/КПП {$firm_vars['firm_inn']}\nР/С {$bank_info['rs']}, в банке {$bank_info['name']}\nК/С {$bank_info['ks']}, БИК {$bank_info['bik']}\n__________________ / {$firm_vars['firm_director']} /\n\n      М.П.";
        $str = iconv('UTF-8', 'windows-1251', $str);
        $this->pdf->MultiCell(0, 5, $str, 0, 'L', 0);

        if (\cfg::get('site', 'doc_shtamp')) {
            $shtamp_img = str_replace('{FN}', $doc_data['firm_id'], \cfg::get('site', 'doc_shtamp'));
            $this->pdf->Image($shtamp_img, 4, $this->pdf->GetY() + $delta, 120);
        }

        $this->addWorkerInfo($doc_data);

        return;
    }    
}
