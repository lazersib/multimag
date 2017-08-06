<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2017, BlackLight, TND Team, http://tndproject.org
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


class Report_Cons extends BaseReport {
    function getName($short = 0) {
        if ($short) {
            return "Сводный";
        } else {
            return "Сводный отчет";
        }
    }

    function Form() {
        global $tmpl;
        $date_st = date("Y-m-01");
        $date_end = date("Y-m-d");
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
            <form action='' method='post'>
            <input type='hidden' name='mode' value='cons'>
            <input type='hidden' name='opt' value='make'>
            <fieldset><legend>Дата</legend>
            от:<input type='text' id='dt_f' name='date_st' size='10' value='$date_st' maxlength='10' /><br>
            до:<input type='text' id='dt_t' name='date_end' size='10' value='$date_end' maxlength='10' />
            </fieldset>
            <fieldset><legend>Организация</legend>");
            $firm_ldo = new \Models\LDO\firmnames();
            $firm_names = $firm_ldo->getData();
            foreach($firm_names as $firm_id => $firm_name) {
                if(\acl::testAccess([ 'firm.global', 'firm.'.$firm_id], \acl::VIEW)) {
                    $tmpl->addContent("<label><input type='checkbox' name='firms[]' value='$firm_id' checked>{$firm_id}: ".html_out($firm_name)."</label><br>");
                }
            }
        $tmpl->addContent("</fieldset>            
            <button type='submit'>Создать отчет</button></form>
            
            <script type=\"text/javascript\">
            function dtinit(){initCalendar('dt_f',false);initCalendar('dt_t',false);}
            addEventListener('load',dtinit,false);
            </script>");
    }

    function generateNomsData($date_start, $date_end, $firms) {
        global $db;
        if (!$date_end) {
            $date_end = time();
        }     
        $agents_info = array('in'=>array(), 'out'=>array());
        $doc_info = array();
        
        $res = $db->query("SELECT `doc_list`.`id`,`doc_list`.`type`,`doc_list`.`date`,`doc_list`.`sum`, `doc_list`.`subtype`, 
                `doc_list`.`firm_id`, `doc_list`.`agent`, `doc_dopdata`.`value` AS `return`
            FROM `doc_list`
            LEFT JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list`.`id` AND `doc_dopdata`.`param`='return'
            WHERE `doc_list`.`ok`!='0' AND `doc_list`.`date`>='$date_start' AND `doc_list`.`date`<='$date_end'");
        while($line = $res->fetch_assoc()) {
            if(!\acl::testAccess([ 'firm.global', 'firm.'.$line['firm_id']], \acl::VIEW)) {
                continue;
            }
            if(!in_array($line['firm_id'], $firms)) {
                continue;
            }
            $f = $line['firm_id'];
            $s = $line['subtype'];
            $a = $line['agent'];
            $dtype = $atype = false;
            if ($line['type'] == 1) {
                if ($line['return']) {
                    $dtype = 'in_ret';
                } else {
                    $dtype = 'in';
                    $atype = 'in';
                }                
            } elseif ($line['type'] == 2) {
                if ($line['return']) {
                    $dtype = 'out_ret';
                } else {
                    $dtype = 'out';
                    $atype = 'out';
                }                
            } elseif ($line['type'] == 20) {
                $dtype = 'out_bonus';  
                $atype = 'out';
            }

            if ($dtype) {
                if (!isset($doc_info[$f][$s])) {
                    $doc_info[$f][$s] = array('in' => 0, 'out' => 0, 'in_ret' => 0, 'out_ret' => 0, 'out_bonus' => 0); 
                }
                $doc_info[$f][$s][$dtype] += $line['sum'];
            }
            if($atype) {
                if (!isset($agents_info[$atype][$a])) {
                    $agents_info[$atype][$a] = $line['sum'];
                } else {
                    $agents_info[$atype][$a] += $line['sum'];
                }
            }
        }
        return array(
            'agents' => $agents_info,
            'docs' => $doc_info,
        );
    }
    
    function generateFinanceData($date_st, $date_end, $firms) {
        global $db;
        if (!$date_end) {
            $date_end = time();
        }
        
        $in_data = array(0 => array('cash' => 0, 'bank' => 0));
        $out_data = array(0 => array('cash' => 0, 'bank' => 0));
        $in_names = array(0 => '--не задан--');
        $out_names = array(0 => '--не задан--');
        $out_adm_flags = array(0 => 0);
        $out_r_flags = array(0 => 0);
        $summary = array('adm_out'=>0, 'store_out'=>0, 'podot'=>0);

        $res = $db->query("SELECT `id`, `name` FROM `doc_ctypes` ORDER BY `id`");
        while($line = $res->fetch_assoc()) {
            $in_names[$line['id']] = $line['name'];
            $in_data[$line['id']] = array('cash'=>0, 'bank'=>0);
        }
        
        $res = $db->query("SELECT `id`, `name`, `adm`, `r_flag` FROM `doc_dtypes` ORDER BY `id`");
        while($line = $res->fetch_assoc()) {
            $out_names[$line['id']] = $line['name'];
            $out_data[$line['id']] = array('cash'=>0, 'bank'=>0);
            $out_adm_flags[$line['id']] = $line['adm'];
            $out_r_flags[$line['id']] = $line['r_flag'];
        }

        // Обработка ПРОВЕДЁННЫХ документов за указанный период
        $doc_res = $db->query("SELECT `doc_list`.`id`,`doc_list`.`type`,`doc_list`.`date`,`doc_list`.`sum`, `doc_list`.`altnum`, 
                `d_table`.`value` AS `out_type`, `c_table`.`value` AS `in_type`, `doc_list`.`firm_id`
            FROM `doc_list`
            LEFT JOIN `doc_dopdata` AS `d_table` ON `d_table`.`doc`=`doc_list`.`id` AND `d_table`.`param`='rasxodi'
            LEFT JOIN `doc_dopdata` AS `c_table` ON `c_table`.`doc`=`doc_list`.`id` AND `c_table`.`param`='credit_type'
            WHERE `doc_list`.`ok`!='0' AND `doc_list`.`date`>='$date_st' AND `doc_list`.`date`<='$date_end'");
        while ($nxt = $doc_res->fetch_assoc()) {
            if(!\acl::testAccess([ 'firm.global', 'firm.'.$nxt['firm_id']], \acl::VIEW)) {
                continue;
            }
            if(!in_array($nxt['firm_id'], $firms)) {
                continue;
            }
            if(!$nxt['in_type']) {
                $nxt['in_type'] = 0;
            }
            if(!$nxt['out_type']) {
                $nxt['out_type'] = 0;
            }
            
            switch($nxt['type']) {
                case 4:     // Банковский приход
                    $in_data[$nxt['in_type']]['bank'] += $nxt['sum'];
                    break;
                case 5:     // Банковский расход
                    $out_data[$nxt['out_type']]['bank'] += $nxt['sum'];
                    if ($out_r_flags[$nxt['out_type']]) {
                        $summary['podot'] += $nxt['sum'];
                    }
                    if($out_adm_flags[$nxt['out_type']]) {
                        $summary['adm_out'] +=  $nxt['sum'];
                    } else {
                        $summary['store_out'] +=  $nxt['sum'];
                    }
                    break;
                case 6:
                    $in_data[$nxt['in_type']]['cash'] += $nxt['sum'];
                    break;
                case 7:     // Банковский расход
                    $out_data[$nxt['out_type']]['cash'] += $nxt['sum'];
                    if ($out_r_flags[$nxt['out_type']]) {
                        $summary['podot'] += $nxt['sum'];
                    }
                    if($out_adm_flags[$nxt['out_type']]) {
                        $summary['adm_out'] +=  $nxt['sum'];
                    } else {
                        $summary['store_out'] +=  $nxt['sum'];
                    }
                    break;
            }
        }

        return array(
            'in_names' => $in_names,
            'out_names' => $out_names,
            'in_data' => $in_data,
            'out_data' => $out_data,
            'summary' => $summary
        );
    }

    function addBlackHeadRow($pdf, $th_widths, $head_aligns, $th_texts, $tbody_aligns) {
        $pdf->SetFontSize(10);
        $pdf->SetWidths($th_widths);
        $pdf->SetHeight(5);
        $pdf->SetLineWidth(0.5);
        $pdf->SetAligns($head_aligns);
        $pdf->SetFillColor(0);
        $pdf->SetTextColor(255);
        $pdf->RowIconv($th_texts);
        $pdf->SetTextColor(0);
        $pdf->SetFillColor(255);
        $pdf->SetFontSize(8);
        $pdf->SetLineWidth(0.2);
        $pdf->SetAligns($tbody_aligns);
        $pdf->SetHeight(4);
    }
    
    function addWhiteHeadRow($pdf, $th_widths, $head_aligns, $th_texts, $tbody_aligns) {
        $pdf->SetFontSize(10);
        $pdf->SetWidths($th_widths);
        $pdf->SetHeight(5);
        $pdf->SetLineWidth(0.5);
        $pdf->SetAligns($head_aligns);
        $pdf->SetFillColor(255);
        $pdf->SetTextColor(0);
        $pdf->RowIconv($th_texts);
        $pdf->SetFontSize(8);
        $pdf->SetLineWidth(0.2);
        $pdf->SetAligns($tbody_aligns);
        $pdf->SetHeight(4);
    }
    
    function addGrayRow($pdf, $texts) {
        $pdf->SetFillColor(192);
        $pdf->SetDrawColor(192);
        $pdf->SetTextColor(0);
        $pdf->RowIconv($texts);
        $pdf->SetFillColor(255);
        $pdf->SetDrawColor(0);
    }
    
    function addGrayLine($pdf, $text) {
        $pdf->SetFillColor(192);
        $pdf->SetTextColor(0);
        $pdf->CellIconv(0, 5, $text, 1, 1, 'L', true);
        $pdf->SetFillColor(255);
    }
    
    function addBlackLine($pdf, $text) {
        $pdf->SetFillColor(0);
        $pdf->SetTextColor(255);
        $pdf->CellIconv(0, 5, $text, 1, 1, 'L', true);
        $pdf->SetFillColor(255);
        $pdf->SetTextColor(0);
    }
    
    function MakePDF() {
        global $tmpl, $db;
        $tmpl->ajax = 1;
        require('fpdf/fpdf_mc.php');
        $date_st = strtotime(rcvdate('date_st'));
        $date_end = strtotime(rcvdate('date_end')) + 60 * 60 * 24 - 1;
        $firms = request('firms', array());
        if(!is_array($firms)) {
            throw new \Exception('Параметр со списком фирм принят неверно');
        }
        if (!$date_end) {
            $date_end = time();
        }
        $firm_ldo = new \Models\LDO\firmnames();
        $firm_names = $firm_ldo->getData();
                
        $data = $this->generateNomsData($date_st, $date_end, $firms);

        $pdf = new PDF_MC_Table('P');
        $pdf->Open();
        $pdf->AddFont('Arial', '', 'arial.php');
        $pdf->SetMargins(6, 6);
        $pdf->SetAutoPageBreak(true, 6);
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetFillColor(255);
        $pdf->AddPage('P');

        $date_st_print = date("d.m.Y", $date_st);
        $date_end_print = date("d.m.Y", $date_end);

        $pdf->SetFontSize(20);
        $pdf->CellIconv(0, 7, $this->getName(), 0, 1, 'C');
        $text = "c $date_st_print по $date_end_print";
        $pdf->SetFontSize(14);
        $pdf->CellIconv(0, 7, $text, 0, 1, 'C');
        
        $firm_str = 'Организации: ';
        foreach ($firm_names as $firm_id => $firm_name) {
            if(in_array($firm_id, $firms)) {
                $firm_str .= $firm_name . ', ';
            }
        }
        $pdf->SetFontSize(10);
        $pdf->MultiCellIconv(0, 4, $firm_str, 0, 'L');
        
        $pdf->SetFontSize(12);
        $pdf->CellIconv(0, 7, 'Товарная сводка', 0, 1, 'L');

        $pdf->SetFontSize(10);
        $w = 32;
        $th_widths = array(8, 30, $w, $w, $w, $w, $w);
        $th_texts = array('№', 'Подтип', 'Поступления', 'Реализации', 'Бонусные реализации', 'Возвраты от покупателя', 'Возвраты поставщикам');
        $head_aligns = array('C', 'C', 'C', 'C', 'C', 'C', 'C');
        $tbody_aligns = array('R', 'L', 'R', 'R', 'R', 'R', 'R');

        $pdf->SetWidths($th_widths);
        $pdf->SetHeight(4);
        $pdf->SetLineWidth(0.5);
        $pdf->SetAligns($head_aligns);
        $pdf->RowIconv($th_texts);

        $pdf->SetFontSize(8);
        $pdf->SetLineWidth(0.2);
        $pdf->SetAligns($tbody_aligns);
        
        $c = 1;
        $all_sums = array('in' => 0, 'out' => 0, 'in_ret' => 0, 'out_ret' => 0, 'out_bonus' => 0);
        foreach ($data['docs'] as $firm_id => $ds_info) {
            $pdf->SetFillColor(0);
            $pdf->SetTextColor(255);
            $pdf->CellIconv(0, 5, $firm_names[$firm_id], 1, 1, 'L', true);
            $pdf->SetFillColor(255);
            $pdf->SetTextColor(0);
            $sums = array('in' => 0, 'out' => 0, 'in_ret' => 0, 'out_ret' => 0, 'out_bonus' => 0);
            foreach ($ds_info as $subtype => $docs_info) {
                foreach ($sums as $id => $val) {
                    $sums[$id] += $docs_info[$id];
                    $docs_info[$id] = number_format($docs_info[$id], 2, '.', ' ');
                }
                if ($subtype == '') {
                    $subtype = '--не задан--';
                }
                $row = array($c, $subtype, $docs_info['in'], $docs_info['out'], $docs_info['out_bonus'], $docs_info['in_ret'], $docs_info['out_ret']);
                $pdf->RowIconv($row);

                $c++;
            }
            $pdf->SetFillColor(192);
            $pdf->SetDrawColor(192);
            foreach ($sums as $id => $val) {
                $all_sums[$id] += $val;
                $sums[$id] = number_format($val, 2, '.', ' ');
            }
            $row = array('', 'Итого', $sums['in'], $sums['out'], $sums['out_bonus'], $sums['in_ret'], $sums['out_ret']);
            $pdf->RowIconv($row);
            $pdf->SetDrawColor(0);
        }
        foreach ($all_sums as $id => $val) {
            $all_sums[$id] = number_format($val, 2, '.', ' ');
        }
        $row = array('', 'Всего', $all_sums['in'], $all_sums['out'], $all_sums['out_bonus'], $all_sums['in_ret'], $all_sums['out_ret']);
        $pdf->SetFillColor(0);
        $pdf->SetTextColor(255);
        $pdf->RowIconv($row);
        $pdf->SetTextColor(0);
        $pdf->SetFillColor(255);
        $pdf->ln();

        $agent_limit = floor(($pdf->h-$pdf->GetY()-$pdf->bMargin-40)/8);
        // ====================================================================================
        $pdf->SetFontSize(12);
        $pdf->CellIconv(0, 7, $agent_limit.' крупнейших покупателей (в т.ч. бонусы)', 0, 1, 'L');

        $th_widths = array(20, 118, 30, 30);
        $th_texts = array('Место', 'Наименование', 'Сумма', '% от оборота');
        $head_aligns = array('C', 'C', 'C', 'C');
        $tbody_aligns = array('R', 'L', 'R', 'R');
        $this->addBlackHeadRow($pdf, $th_widths, $head_aligns, $th_texts, $tbody_aligns);

        $sum = 0;
        $agents = array();
        foreach ($data['agents']['out'] as $val) {
            $sum += $val;
        }
        asort($data['agents']['out'], SORT_NUMERIC);
        $agents = array_reverse($data['agents']['out'], true);
        $agents_ldo = new \Models\LDO\agentnames();
        $agent_names = $agents_ldo->getData();
        $c = 1;
        foreach ($agents as $id => $val) {
            $val_p = number_format($val, 2, '.', ' ');
            $row = array($c, $agent_names[$id], $val_p, sprintf("%0.2f", $val / $sum * 100));
            $pdf->RowIconv($row);
            $c++;
            if ($c > $agent_limit) {
                break;
            }
        }
        $pdf->ln();

        // ====================================================================================
        $pdf->SetFontSize(12);
        $pdf->CellIconv(0, 7, $agent_limit.' крупнейших поставщиков', 0, 1, 'L');

        $th_widths = array(20, 118, 30, 30);
        $th_texts = array('Место', 'Наименование', 'Сумма', '% от оборота');
        $head_aligns = array('C', 'C', 'C', 'C');
        $tbody_aligns = array('R', 'L', 'R', 'R');        
        $this->addBlackHeadRow($pdf, $th_widths, $head_aligns, $th_texts, $tbody_aligns);
        
        $sum = 0;
        $agents = array();
        foreach ($data['agents']['in'] as $val) {
            $sum += $val;
        }
        asort($data['agents']['in'], SORT_NUMERIC);
        $agents = array_reverse($data['agents']['in'], true);
        $agents_ldo = new \Models\LDO\agentnames();
        $agent_names = $agents_ldo->getData();
        $c = 1;
        foreach ($agents as $id => $val) {
            $val_p = number_format($val, 2, '.', ' ');
            $row = array($c, $agent_names[$id], $val_p, sprintf("%0.2f", $val / $sum * 100));
            $pdf->RowIconv($row);
            $c++;
            if ($c > $agent_limit) {
                break;
            }
        }
        // ====================================================================================
        $pdf->AddPage('P');
        $pdf->SetFontSize(12);
        $pdf->CellIconv(0, 7, 'Финансовая сводка', 0, 1, 'L');
        
        $fin_info = $this->generateFinanceData($date_st, $date_end, $firms);
        
        $th_widths = array(10, 93, 30, 35, 30);
        $th_texts = array('N', 'Вид', 'Нал. средства', 'Безнал. средства', 'Сумма');
        $head_aligns = array('C', 'C', 'C', 'C', 'C');
        $tbody_aligns = array('R', 'L', 'R', 'R', 'R');        
        $this->addWhiteHeadRow($pdf, $th_widths, $head_aligns, $th_texts, $tbody_aligns);
        
        $this->addBlackLine($pdf, 'Доходы');
        $bank_sum = $cash_sum = 0;
        foreach($fin_info['in_data'] as $id=>$line) {
            if($line['cash']==0 && $line['bank']==0) {
                continue;
            }
            $cash_p = number_format($line['cash'], 2, '.', ' ');
            $bank_p = number_format($line['bank'], 2, '.', ' ');
            $sum_p = number_format($line['cash']+$line['bank'], 2, '.', ' ');
            $row = array($id, $fin_info['in_names'][$id], $cash_p, $bank_p, $sum_p);
            $pdf->RowIconv($row);
            $bank_sum += $line['bank'];
            $cash_sum += $line['cash'];
        }
        $cash_p = number_format($cash_sum, 2, '.', ' ');
        $bank_p = number_format($bank_sum, 2, '.', ' ');
        $sum_p = number_format($cash_sum+$bank_sum, 2, '.', ' ');
        $row = array('', 'Всего:', $cash_p, $bank_p, $sum_p);
        $this->addGrayRow($pdf, $row);
        $in_all_sum = $cash_sum + $bank_sum;
        $this->addBlackLine($pdf, 'Расходы');
        $bank_sum = $cash_sum = 0;
        foreach($fin_info['out_data'] as $id=>$line) {
            if($line['cash']==0 && $line['bank']==0) {
                continue;
            }
            $cash_p = number_format($line['cash'], 2, '.', ' ');
            $bank_p = number_format($line['bank'], 2, '.', ' ');
            $sum_p = number_format($line['cash']+$line['bank'], 2, '.', ' ');
            $row = array($id, $fin_info['out_names'][$id], $cash_p, $bank_p, $sum_p);
            $pdf->RowIconv($row);
            $bank_sum += $line['bank'];
            $cash_sum += $line['cash'];
        }
        $cash_p = number_format($cash_sum, 2, '.', ' ');
        $bank_p = number_format($bank_sum, 2, '.', ' ');
        $sum_p = number_format($cash_sum+$bank_sum, 2, '.', ' ');
        $row = array('', 'Всего:', $cash_p, $bank_p, $sum_p);
        $this->addGrayRow($pdf, $row);
        
        $this->addBlackLine($pdf, 'Итоги');
        if ( ($bank_sum+$cash_sum) == 0) {
            $adm_proc_r_prn = "бесконечность";
        } else {
            $adm_proc_r_prn = number_format($fin_info['summary']['adm_out'] / ($bank_sum+$cash_sum) * 100, 2, '.', ' ');
        }
        if ( $in_all_sum == 0) {
            $adm_proc_prn = "бесконечность";
        } else {
            $adm_proc_prn = number_format($fin_info['summary']['adm_out'] / $in_all_sum * 100, 2, '.', ' ');
        }
        $sum_p = number_format($fin_info['summary']['adm_out'], 2, '.', ' ');
        $row = array('', 'Административные затраты:', '', '', $sum_p);
        $pdf->RowIconv($row);
        $row = array('', 'В процентах от доходов:', '', '', $adm_proc_prn);
        $pdf->RowIconv($row);
        $row = array('', 'В процентах от расходов:', '', '', $adm_proc_r_prn);
        $pdf->RowIconv($row);
        $sum_p = number_format($fin_info['summary']['store_out'], 2, '.', ' ');
        $row = array('', 'Затраты на товар:', '', '', $sum_p);
        $pdf->RowIconv($row);
        $sum_p = number_format($bank_sum + $cash_sum - $fin_info['summary']['podot'], 2, '.', ' ');
        $row = array('', 'Чистый расход (без подотчётных средств):', '', '', $sum_p);
        $pdf->RowIconv($row);
        
        $pdf->Output('cons_report.pdf', 'I');
        echo"1";
        exit(0);
    }

    function make($engine) {
        return $this->MakePDF();
    }

}
