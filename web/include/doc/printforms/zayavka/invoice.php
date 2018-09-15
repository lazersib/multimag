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
namespace doc\printforms\zayavka; 

class invoice extends \doc\printforms\iPrintFormPdf {
 
    public function getName() {
        return "Счёт";
    }
       
    /// Сформировать данные печатной формы
    public function make() {
        global $db, $CONFIG;
        $doc_data = $this->doc->getDocDataA();
        $firm_vars = $this->doc->getFirmVarsA();
        $agent = new \models\agent($doc_data['agent']);
        $nomenclature = $this->doc->getDocumentNomenclature('vat');
        $res = $db->query("SELECT `name`, `bik`, `rs`, `ks` FROM `doc_kassa` WHERE `ids`='bank' AND `num`='{$doc_data['bank']}'");
        $bank_data = $res->fetch_assoc();
        
        $this->addPage();       
        $this->addHeadBanner($doc_data['firm_id']);
        $this->addSiteBanner();
        
        $this->pdf->SetFont('', '', 10);
        $text = "Внимание!"
            . " Оплата данного счёта означает согласие с условиями поставки товара."
            . " Уведомление об оплате обязательно, иначе не гарантируется наличие товара на складе."
            . " Товар отпускается по факту прихода денег на р/с поставщика, самовывозом, при наличии доверенности и паспорта.";
        $this->pdf->MultiCellIconv(0, 5, $text, 1, 'C', 0);        
        
        $this->pdf->y++;
        $this->pdf->SetFont('', 'U', 10);
        $text = 'Счёт действителен в течение трёх банковских дней!';
        $this->pdf->CellIconv(0, 5, $text, 0, 1, 'C', 0);
        $this->pdf->ln(2);
        
        $this->pdf->SetFont('', '', 11);
        $text = 'Образец заполнения платёжного поручения:';
        $this->pdf->CellIconv(0, 5, $text, 0, 1, 'C', 0);

        $old_x = $this->pdf->GetX();
        $old_y = $this->pdf->GetY();
        $old_margin = $this->pdf->lMargin;
        $table_c = 110;
        $table_c2 = 15;

        $this->pdf->SetFont('', '', 9);
        $this->pdf->MultiCellIconv($table_c, 5, $bank_data['name'], 0, 1, 'L', 0);
        $this->pdf->SetX($old_x);
        $this->pdf->SetY($old_y);        
        $this->pdf->CellIconv($table_c, 10, '' , 1, 1, 'L', 0);
        $text = 'ИНН ' . $firm_vars['firm_inn'] . ' КПП';
        $this->pdf->CellIconv($table_c, 5, $text, 1, 1, 'L', 0);

        $tx = $this->pdf->GetX();
        $ty = $this->pdf->GetY();
        $this->pdf->CellIconv($table_c, 10, '', 1, 1, 'L', 0);
        $this->pdf->lMargin = $old_x + 1;
        $this->pdf->SetX($tx + 1);
        $this->pdf->SetY($ty + 1);
        $this->pdf->SetFont('', '', 9);
        $text = 'Получатель: ' . $firm_vars['firm_name'];
        $this->pdf->MultiCellIconv($table_c, 3, $text, 0, 1, 'L', 0);

        $this->pdf->SetFont('', '', 12);
        $this->pdf->lMargin = $old_x + $table_c;
        $this->pdf->SetY($old_y);
        $text = 'БИК';
        $this->pdf->CellIconv($table_c2, 5, $text, 1, 1, 'L', 0);
        $text = 'корр/с';
        $this->pdf->CellIconv($table_c2, 10, $text, 1, 1, 'L', 0);
        $text = 'р/с N';
        $this->pdf->CellIconv($table_c2, 10, $text, 1, 1, 'L', 0);

        $this->pdf->lMargin = $old_x + $table_c + $table_c2;
        $this->pdf->SetY($old_y);
        $this->pdf->Cell(0, 5, $bank_data['bik'], 1, 1, 'L', 0);
        $this->pdf->Cell(0, 5, $bank_data['ks'], 1, 1, 'L', 0);
        $this->pdf->Cell(0, 15, $bank_data['rs'], 1, 1, 'L', 0);
        $this->pdf->lMargin = $old_margin;
        $this->pdf->SetY($old_y + 30);

        if( @$CONFIG['doc']['invoice_header'] ) {
            $this->pdf->ln(2);
            $this->pdf->SetFont('', '', 14);
            $this->pdf->MultiCellIconv(0, 6, $CONFIG['doc']['invoice_header'], 1, 'C', 0);
            $this->pdf->ln(2);
        }
        
        $text = 'Счёт № '.$doc_data['altnum'].' от '.date("d.m.Y", $doc_data['date']);
        $this->addHeader($text);
        if ($doc_data['contract']) {
            $contract = new \doc_Dogovor($doc_data['contract']);
            $contract_data = $contract->getDocDataA();
            $text = 'К договору № ' . $contract_data['altnum'] . ' от ' . date("d.m.Y", $contract_data['date']);
            $this->addInfoLine($text);
        }
        $text = "Поставщик: {$firm_vars['firm_name']}, {$firm_vars['firm_adres']}, тел: {$firm_vars['firm_telefon']}";
        $this->addInfoLine($text);
        $text = "Покупатель: {$agent->fullname}, адрес: {$agent->adres}, телефон: ".$agent->getPhone();
        $this->addInfoLine($text);
        $this->pdf->Ln(3);
        
        if ($doc_data['comment']) {
            $this->pdf->SetFont('', '', 11);
            $text = str_replace("<br>", ", ", $doc_data['comment']);
            $this->pdf->MultiCellIconv(0, 5, $text, 0, 1, 'L');
            $this->pdf->Ln(3);
        }

        $th_widths = array(8);
        $th_texts = array('№');
        $tbody_aligns = array('R');
        if ($CONFIG['poseditor']['vc']) {
            $th_widths[] = 20;
            $th_texts[] = 'Код';
            $tbody_aligns[] = 'R';
            $th_widths[] = 106;
        } else {
            $th_widths[] = 126;
        }
        $th_texts[] = 'Наименование';
        $tbody_aligns[] = 'L';
        $th_widths = array_merge($th_widths, array(16, 20, 20));
        $th_texts = array_merge($th_texts, array('Кол-во', 'Цена', 'Сумма'));
        $tbody_aligns = array_merge($tbody_aligns, array('R', 'R', 'R'));
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
            $row = array_merge($row, array("{$line['cnt']} {$line['unit_name']}", $price, $sum_line));
            $this->controlPageBreak(30);
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

        $this->controlPageBreak(50);
        $this->addSignAndStampImage($doc_data['firm_id']);

        $this->pdf->SetFont('', '', 10);
        $text = "Масса товара: $summass кг.";
        $this->pdf->CellIconv(0, 6, $text, 0, 0, 'L', 0);

        $vat_p = sprintf("%01.2f", $sum_vat);
        $this->pdf->SetFont('', '', 12);
        $text = "Итого: $i наименований на сумму $sumcost руб.";
        $this->pdf->CellIconv(0, 7, $text, 0, 1, 'R', 0);
        $text = "В том числе НДС: $vat_p руб.";
        $this->pdf->CellIconv(0, 5, $text, 0, 1, 'R', 0);

        $this->addWorkerInfo($doc_data);

        return;
    }    
}
