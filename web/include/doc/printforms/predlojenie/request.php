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
namespace doc\printforms\predlojenie; 

class request extends \doc\printforms\iPrintFormPdf {
 
    public function getName() {
        return "Заявка на поставку";
    }
       
    /// Сформировать данные печатной формы
    public function make() {
        global $db, $CONFIG;
        $doc_id = $this->doc->getId();
        $doc_data = $this->doc->getDocDataA();
        $firm_vars = $this->doc->getFirmVarsA();
        $nomenclature = $this->doc->getDocumentNomenclature();
        
        $this->pdf->AddPage('P');
        $this->addTechFooter();        
        $this->addHeadBanner($doc_data['firm_id']);
        
        $text = "Заявка поставщику №{$doc_data['altnum']}{$doc_data['subtype']} от ".date("d.m.Y", $doc_data['date']);
        $this->addHeader($text);
        $text = "Заказчик: {$firm_vars['firm_name']}";
        $this->addInfoLine($text);
        $text = "Поставщик: {$doc_data['agent_name']}";
        $this->addInfoLine($text);        
        $str = 'Просим рассмотреть возможность поставки следующей продукции:';
        $this->pdf->SetFont('', 'U', 14);
        $this->pdf->CellIconv(0, 5, $str, 0, 1, 'C', 0);
        $this->pdf->Ln(); 
        $th_widths = array(7, 125, 20, 20, 20);
        $th_texts = array('№', 'Наименование', 'Кол-во', 'Цена', 'Сумма');
        $tbody_aligns = array('R', 'L', 'R', 'R', 'R');
        $this->addTableHeader($th_widths, $th_texts, $tbody_aligns);

        $ii = 1;
        $sum = $cnt = 0;
        foreach($nomenclature as $line) {
            $row = array($ii, $line['name'], "{$line['cnt']} {$line['unit_name']}", sprintf("%0.2f р.", $line['price']), sprintf("%0.2f р.", $line['sum']));
            $this->pdf->RowIconv($row);
            $ii++;
            $sum += $line['sum'];
            $cnt += $line['cnt'];
        }

        $ii--;        

        $this->pdf->Ln(5);
        $text = "Всего $ii наименований общим количеством $cnt ед. на сумму ".sprintf("%0.2f р.", $sum);
        $this->addInfoLine($text);      

        if ($doc_data['comment']) {
            $this->pdf->Ln(5);
            $text = "Комментарий : " . $doc_data['comment'];
            $this->pdf->MultiCellIconv(0, 5, $text, 0, 'L', 0);
            $this->pdf->Ln(5);
        }
        $this->pdf->Ln();
        
        $res_autor = $db->query("SELECT `worker_real_name` FROM `users_worker_info` WHERE `user_id`='" . $doc_data['user'] . "'");
        if ($res_autor->num_rows) {
            $line = $res_autor->fetch_row();
            $autor_name = $line[0];
        } else {
            $autor_name = '                ';
        }
        $text = "Автор заявки: _________________________________________ ($autor_name)";
        $this->addSignLine($text);
    }    
}
