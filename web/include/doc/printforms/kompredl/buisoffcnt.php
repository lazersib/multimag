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
namespace doc\printforms\kompredl; 

class buisoffcnt extends buisoff {
 
    public function getName() {
        return "Предложение с количеством";
    }
       
    /// Сформировать данные печатной формы
    public function make() {
        global $db;
        $doc_id = $this->doc->getId();
        $doc_data = $this->doc->getDocDataA();
        $dop_data = $this->doc->getDopDataA();
        $firm_vars = $this->doc->getFirmVarsA();
        $nomenclature = $this->doc->getDocumentNomenclature('comment');
        $res = $db->query("SELECT `name`, `bik`, `rs`, `ks` FROM `doc_kassa` WHERE `ids`='bank' AND `num`='{$doc_data['bank']}'");
        $bank_info = $res->fetch_assoc();
        
        $this->pdf->AddPage('P');
        $this->addTechFooter();        
        $this->addHeadBanner($doc_data['firm_id']);
        $this->addbankInfo($firm_vars, $bank_info);
        
        $text = "Коммерческое предложение №{$doc_data['altnum']}{$doc_data['subtype']} от " . date("d.m.Y", $doc_data['date']);
        $this->addHeader($text);
        $text = 'Поставщик: ' . $firm_vars['firm_name'] . ', тел. ' . $firm_vars['firm_telefon'];
        $this->addInfoLine($text);
        $this->pdf->Ln(4);

        if($dop_data['shapka']) {
            $this->addMiniHeader($dop_data['shapka']);
        }

        $th_widths = array(7, 100, 15, 15, 35, 20);
        $th_texts = array('№', 'Наименование', 'Масса', 'Кол-во', 'Срок поставки', 'Цена');
        $tbody_aligns = array('R', 'L', 'R', 'R', 'L', 'R');
        $this->addTableHeader($th_widths, $th_texts, $tbody_aligns);

        $ii = 1;
        $cnt = 0;
        foreach($nomenclature as $line) {
            $row = array($ii, $line['name'], $line['mass'].' кг.', $line['cnt'] .' '.$line['unit_name'], $line['comment'], sprintf("%0.2f р.", $line['price']));
            $this->pdf->RowIconv($row);
            $ii++;
            $cnt += $line['cnt'];
        }

        if ($this->pdf->h <= ($this->pdf->GetY() + 40)) {
            $this->pdf->AddPage();
        }

        $this->addMiniHeader("Цены указаны с учётом НДС, за 1 ед. товара");
        $this->pdf->ln(6);
        
        if ($doc_data['comment']) {
            $this->pdf->SetFont('', '', 10);
            $this->pdf->Ln(5);
            $this->pdf->MultiCellIconv(0, 5, $doc_data['comment'], 0, 'L', 0);
            $this->pdf->Ln(5);
        }
        $this->addSiteBanner();
        $this->addWorkerInfo($doc_data);
    }    
}
