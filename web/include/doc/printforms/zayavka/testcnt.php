<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2016, BlackLight, TND Team, http://tndproject.org
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

class testcnt extends \doc\printforms\iPrintFormPdf {
 
    public function getName() {
        return "Накладная на проверку наличия";
    }
       
    /// Сформировать данные печатной формы
    public function make() {
        global $db, $CONFIG;
        $doc_id = $this->doc->getId();
        $doc_data = $this->doc->getDocDataA();
        $firm_vars = $this->doc->getFirmVarsA();
        $nomenclature = $this->doc->getDocumentNomenclature('comment,rto');
        
        $this->pdf->AddPage('P');
        $this->addTechFooter();
        
        $text = "Накладная на проверку наличия N {$doc_data['altnum']}{$doc_data['subtype']} от ".date("d.m.Y", $doc_data['date']);
        $this->addHeader($text);
        $text = "К заявке N {$doc_data['altnum']}{$doc_data['subtype']} ({$doc_id})";
        $this->addInfoLine($text);
        $text = "Поставщик: {$firm_vars['firm_name']}";
        $this->addInfoLine($text);
        $text = "Покупатель: {$doc_data['agent_name']}";
        $this->addInfoLine($text);
        $this->pdf->Ln();        
        
        $th_widths = array(7);
        $th_texts = array('№');
        $tbody_aligns = array('R');
        if ($CONFIG['poseditor']['vc']) {
            $th_widths[] = 18;            
            $th_texts[] = 'Код';
            $tbody_aligns[] = 'R';
            $th_widths[] = 92;
        } else {
            $th_widths[] = 110;            
        }
        $th_texts[] = 'Наименование';
            $tbody_aligns[] = 'L';
        $th_widths = array_merge($th_widths, array(17, 15, 10, 15, 15));
        $th_texts = array_merge($th_texts, array('Кол-во', 'Остаток', 'Рез.', 'Факт', 'Место'));  
        $tbody_aligns = array_merge($tbody_aligns, array('R', 'R', 'R', 'R', 'R'));
        $this->addTableHeader($th_widths, $th_texts, $tbody_aligns);

        $i = 0;
        $ii = 1;
        $sum = $cnt = 0;
        foreach($nomenclature as $line) {
            $row = array($ii);
            $rowc = array('');
            if ($CONFIG['poseditor']['vc']) {
                $row[] = $line['vc'];
                $row[] = $line['name'];
                $rowc[] = '';
                $rowc[] = $line['comment'];
            } else {
                $row[] = $line['name'];
                $rowc[] = $line['comment'];
            }

            $row = array_merge($row, array("{$line['cnt']} {$line['unit_name']}", round($line['base_cnt'],2), $line['reserve'], '', $line['place']));
            $rowc = array_merge($rowc, array('', '', '', '', '', ''));
            $this->pdf->RowIconvCommented($row, $rowc);
            $i = 1 - $i;
            $ii++;
            $sum += $line['sum'];
            $cnt += $line['cnt'];
        }

        $ii--;
        $res_autor = $db->query("SELECT `worker_real_name` FROM `users_worker_info` WHERE `user_id`='" . $doc_data['user'] . "'");
        if ($res_autor->num_rows) {
            $line = $res_autor->fetch_row();
            $autor_name = $line[0];
        } else {
            $autor_name = '                ';
        }

        $this->pdf->Ln(5);
        $text = "Всего $ii наименований общим количеством $cnt ед.";
        $this->addInfoLine($text);

        if ($doc_data['comment']) {
            $this->pdf->Ln(5);
            $text = "Комментарий : " . $doc_data['comment'];
            $this->pdf->MultiCellIconv(0, 5, $text, 0, 'L', 0);
            $this->pdf->Ln(5);
        }
        $this->pdf->Ln();
        $text = "Автор заявки: _________________________________________ ($autor_name)";
        $this->addSignLine($text);
        $text = "Наличие подтвердил: ___________________________________ ";
        $this->addSignLine($text);
    }    
}
