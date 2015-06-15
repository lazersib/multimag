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


class Report_Buy_book extends BaseReport {
    function getName($short = 0) {
        if ($short)
            return "Книга покупок";
        else
            return "Книга покупок";
    }

    function Form() {
        global $tmpl, $db;
        $date_st = date("Y-m-01");
        $date_end = date("Y-m-d");
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
            <form action='' method='get'>
            <input type='hidden' name='mode' value='buy_book'>
            <input type='hidden' name='opt' value='make'>
            Выберите фирму:<br>
            <select name='firm_id'>");
        $res = $db->query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `id`");
        while ($nxt = $res->fetch_row()) {
            $tmpl->addContent("<option value='$nxt[0]'>" . html_out($nxt[1]) . "</option>");
        }
        $tmpl->addContent("</select><br>
            <p class='datetime'>
            Дата от:<input type='text' id='id_pub_date_date' class='vDateField required' name='date_st' size='10' value='$date_st' maxlength='10' /><br>
            до:<input type='text' id='id_pub_date_date' class='vDateField required' name='date_end' size='10' value='$date_end' maxlength='10' />
            </p><button type='submit'>Создать отчет</button></form>");
    }
    
    function MakePDF() {
        global $tmpl, $db;
        $tmpl->ajax = 1;
        require('fpdf/fpdf_mc.php');
        $date_st = strtotime(rcvdate('date_st'));
        $date_end = strtotime(rcvdate('date_end')) + 60 * 60 * 24 - 1;
        $firm_id = rcvint('firm_id');
        if (!$date_end) {
            $date_end = time();
        }  
        $date_st_print = date("d.m.Y", $date_st);
        $date_end_print = date("d.m.Y", $date_end);    
        
        $res = $db->query("SELECT `id`, `firm_name`, `firm_inn`, `firm_director` FROM `doc_vars` WHERE `id`=$firm_id");
        if($res->num_rows) {
            $firm_info = $res->fetch_assoc();
        } else {
            throw new Exception("Организация не найдена");
        }

        $pdf = new PDF_MC_Table('L');
        $pdf->Open();
        $pdf->AddFont('Arial', '', 'arial.php');
        $pdf->SetMargins(6, 6);
        $pdf->SetAutoPageBreak(true, 6);
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetFillColor(255);
        $pdf->AddPage('L');       
        
        $pdf->SetFontSize(5);
        $text = "Приложение № 4 к постановлению Правительства Российской Федерации от 26.12.2011 № 1137";
        $pdf->CellIconv(0, 2, $text, 0, 1, 'R');
        $text = "(в ред. Постановления Правительства РФ от 30.07.2014 № 735)";
        $pdf->CellIconv(0, 2, $text, 0, 1, 'R');

        $pdf->SetFontSize(20);
        $pdf->CellIconv(0, 7, "Книга покупок", 0, 1, 'C');
        
        $pdf->SetFontSize(12);
        $text = "Покупатель: " . $firm_info['firm_name'];
        $pdf->CellIconv(0, 5, $text, 0, 1, 'L');
        $text = "Идентификационный номер и код причины постановки на учет налогоплательщика-покупателя: " . $firm_info['firm_inn'];
        $pdf->CellIconv(0, 5, $text, 0, 1, 'L');
        $text = "Покупка за период c $date_st_print по $date_end_print";
        $pdf->CellIconv(0, 5, $text, 0, 1, 'L');
        $pdf->ln(1);
        
        $pdf->SetFontSize(8);
        $w = 32;
        $th_widths = array(8, 10, 25, 15, 15, 15, 15, 15, 40, 30, 10, 9, 15, 15, 24, 24);
        $th_texts = array(
            '№ п/п', 
            'Код вида операции', 
            'Номер и дата счета- фактуры продавца', 
            'Номер и дата исправления счета-фактуры продавца', 
            'Номер и дата корректировочного счета-фактуры продавца', 
            'Номер и дата исправления корректировочного счета-фактуры продавца', 
            'Номер и дата документа, подтверждающего уплату налога',
            'Дата принятия на учет товаров (работ, услуг), имущественных прав',
            'Наименование продавца',
            'ИНН/КПП продавца',
            'Наи мено ва ние по сред ника',
            'ИНН / КПП по сред ника',
            'Номер таможен ной декла рации',
            'Наиме нова ние и код валю ты',
            'Стоимость покупок по  счету-фактуре, разница стоимости по корректировочному счету-фактуре (включая НДС) в валюте счета-фактуры',
            'Сумма НДС по счету-фактуре, разница суммы НДС по корректировочному счету-фактуре, принимаемая  к вычету, в рублях и копейках'
            );
        $head_aligns = array('C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C');
        $tbody_aligns = array('R', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'L', 'C', 'C', 'C', 'L', 'C', 'R', 'R');

        $pdf->SetWidths($th_widths);
        $pdf->SetHeight(4);
        $pdf->SetLineWidth(0.5);
        $pdf->SetAligns($head_aligns);
        $pdf->RowIconv($th_texts);

        $pdf->SetFontSize(6);
        $pdf->SetLineWidth(0.2);
        $pdf->SetAligns($tbody_aligns);

        $res = $db->query("SELECT `doc_list`.`id` AS `doc_id`, `doc_list`.`altnum`, `doc_list`.`date`, `doc_list`.`sum`"
            . " ,`doc_list`.`type`, `ret_t`.`value` AS `ret_flag`, `idn_t`.`value` AS `in_num`, `idd_t`.`value` AS `in_date`"
            . " ,`doc_agent`.`fullname` AS `agent_name`, `doc_agent`.`inn`, `doc_agent`.`kpp`"
            . " FROM `doc_list`"
            . " LEFT JOIN `doc_dopdata` AS `ret_t` ON `ret_t`.`doc`=`doc_list`.`id` AND `ret_t`.`param`='return'"
            . " LEFT JOIN `doc_dopdata` AS `idn_t` ON `idn_t`.`doc`=`doc_list`.`id` AND `idn_t`.`param`='input_doc'"
            . " LEFT JOIN `doc_dopdata` AS `idd_t` ON `idd_t`.`doc`=`doc_list`.`id` AND `idd_t`.`param`='input_date'"
            . " INNER JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`"
            . " WHERE `doc_list`.`date`>='$date_st' AND `doc_list`.`date`<='$date_end' AND `doc_list`.`type`=1 AND `doc_list`.`ok`>0"
            . " AND `doc_list`.`firm_id`=$firm_id");
        $c = 1;
        while($doc = $res->fetch_assoc()) {
            $code = '00';
            switch($doc['type']) {
                case 1:
                case 2:
                    if($doc['ret_flag']) {
                        $code = '03';
                    } else {
                        $code = '01';
                    }
                    break;
                case 20:
                    $code = '10';
                    break;
            }
            
            $row = array(
                $c,
                $code,
                'N' . $doc['in_num'].' от '. date('m.d.Y', strtotime($doc['in_date'])),
                '-',
                '-',
                '-',
                '-',
                date('m.d.Y', $doc['date']),
                $doc['agent_name'],
                $doc['inn'].' / '.$doc['kpp'],
                '-',
                '-',
                'НТД',
                'Рубль 643',
                sprintf("%0.2f", $doc['sum']),
                sprintf("%0.2f", $doc['sum'] / 1.18 * 18),
            );
            $pdf->RowIconv($row);
            $c++;
        }
        $pdf->ln();
        // Контроль расстояния до конца листа
        $workspace_h = $pdf->h - $pdf->bMargin - $pdf->tMargin;
        if ($workspace_h  <= $pdf->GetY() + 45) {
            $pdf->AddPage('L');
        }
        $pdf->SetAutoPageBreak(0);   
        
        $pdf->SetFontSize(12);
        $text = "Руководитель организации или иное уполномоченное лицо: ";
        $pdf->CellIconv(130, 5, $text, 0, 0, 'L');
        $pdf->CellIconv(40, 5, '', 'B', 0, 'L');
        $pdf->CellIconv(5, 5, '', 0, 0, 'L');
        $pdf->CellIconv(0, 5, '', 'B', 1, 'L');
        $pdf->SetFontSize(6);
        $pdf->CellIconv(130, 5, '', 0, 0, 'L');
        $pdf->CellIconv(40, 5, '(подпись)', 0, 0, 'C');
        $pdf->CellIconv(5, 5, '', 0, 0, 'L');
        $pdf->CellIconv(0, 5, '(ф.и.о.)', 0, 1, 'C');
        $pdf->SetFontSize(12);
        $text = "Индивидуальный предприниматель: ";
        $pdf->CellIconv(130, 5, $text, 0, 0, 'L');
        $pdf->CellIconv(40, 5, '', 'B', 0, 'L');
        $pdf->CellIconv(5, 5, '', 0, 0, 'L');
        $pdf->CellIconv(0, 5, '', 'B', 1, 'L');
        $pdf->SetFontSize(6);
        $pdf->CellIconv(130, 5, '', 0, 0, 'L');
        $pdf->CellIconv(40, 5, '(подпись)', 0, 0, 'C');
        $pdf->CellIconv(5, 5, '', 0, 0, 'L');
        $pdf->CellIconv(0, 5, '(ф.и.о.)', 0, 1, 'C');
        
        $pdf->SetFontSize(12);
        $text = "Реквизиты свидетельства о государственной";
        $pdf->CellIconv(0, 5, $text, 0, 1, 'L');
        $text = "регистрации индивидуального предпринимателя";
        $pdf->CellIconv(130, 5, $text, 0, 0, 'L');
        $pdf->CellIconv(0, 5, '', 'B', 1, 'L');
        
        $pdf->Output('buy_book.pdf', 'I');
        echo"1";
        exit(0);
    }

    function make($engine) {
        return $this->MakePDF();
    }

}
