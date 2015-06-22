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


/// Отчёт по движению товара
class Report_Salary extends BaseGSReport { 
        function getName($short = 0) {
            if ($short) {
            return "По расчётным вознаграждениям";
        } else {
            return "Отчёт по расчётным вознаграждениям";
        }
    }

    function Form() {
        global $tmpl, $db;
        $d_t = date("Y-m-d");
        $d_f = date("Y-m-d", time() - 60 * 60 * 24 * 1);
        $tmpl->addBreadcrumb($this->getName(), ''); 
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
            <form action='' method='post'>
            <input type='hidden' name='mode' value='salary'>
            <input type='hidden' name='opt' value='get'>
            <fieldset><legend>Дата</legend>
            С:<input type=text id='dt_f' name='dt_f' value='$d_f'><br>
            По:<input type=text id='dt_t' name='dt_t' value='$d_t'>
            </fieldset>
            Сотрудник:<br>
            <select name='worker_id'>"
            . "<option value='0'>--не выбран--</option>");
            $res = $db->query("SELECT `user_id` AS `id`, `worker_real_name` AS `name` FROM `users_worker_info` WHERE `worker`=1");
            while($line = $res->fetch_assoc()) {
                $tmpl->addContent("<option value='{$line['id']}'>{$line['name']}</option>");
            }
            $tmpl->addContent("</select><br>
            </fieldset>
            <button type='submit'>Сформировать отчёт</button>
            </form>
            <script type=\"text/javascript\">
            function dtinit() {
                    initCalendar('dt_f',false);
                    initCalendar('dt_t',false);
            }
            addEventListener('load',dtinit,false);	
            </script>");
    }
    
    function make($engine) {
        if($engine=='get') {
            return $this->makeFull($engine);
        } else {
            return $this->makeDetail($engine);
        }
    }
    
    function makeDetail($engine) {
        global $tmpl, $db;
        $doc = rcvint('doc');
        $users_ldo = new \Models\LDO\workernames();
        $users = $users_ldo->getData();
        
        $salary = new \async\salary(0);
        $salary->loadPosTypes(); 
        $salary->loadPosData();
        $tmpl->addBreadcrumb('Просмотр данных', '');
        
        
        $docs_res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`type`, `date`, `user`, `sum`, `p_doc`, `contract`, `sklad` AS `store_id`"
            . " , `doc_agent`.`responsible` AS `resp_id`, `doc_types`.`name` AS `type_name`, `doc_dopdata`.`value` AS `return`"
            . " FROM `doc_list`"
            . " LEFT JOIN `doc_agent` ON `doc_agent`.`id` = `doc_list`.`agent`"
            . " LEFT JOIN `doc_types` ON `doc_types`.`id` = `doc_list`.`type`"
            . " LEFT JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list`.`id` AND `doc_dopdata`.`param`='return'"
            . " WHERE `doc_list`.`id`=$doc");
        if($doc_line = $docs_res->fetch_assoc()) {
            $doc_vars = array();
            $o_name = $o_fee = $r_name = $r_fee = $m_name = $m_fee = $sk_name = $sk_fee = '';
            $sum_line = 0;
            $res = $db->query('SELECT `param`, `value` FROM `doc_dopdata` WHERE `doc`=' . $doc_line['id']);
            while ($line = $res->fetch_row()) {
                $doc_vars[$line[0]] = $line[1];
            }
            $doc_line['vars'] = $doc_vars;            
            $info = $salary->calcFee($doc_line, $doc_line['resp_id'], 1);
            $tmpl->addContent("<h1>Расчёты по {$doc_line['type_name']} - $doc</h1>");
            $tmpl->addContent("<table class='list' width='100%'>"
            . "<tr><th>id</th><th>Код</th><th>Наименование</th><th>Пр-ль</th><th>Кол-во</th><th>В м.уп.</th><th>В б.уп.</th><th>Коэфф.сл.сб.</th><th>Сумма сб.</th>");
            if($doc_line['type']==2) {
                $tmpl->addContent("<th>П.цена</th><th>Вх.цена</th><th>Ликв.</th><th>Расч.ст.</th>");
            }
            $tmpl->addContent("</tr>");
            $k_sum = $p_sum = 0;
            foreach($info['detail'] as $pos_line) {
                if($pos_line['mult']==0) {
                    $pos_line['mult'] = 1;
                }
                if($pos_line['pcs']===null) {
                    $pos_line['pcs'] = 1;
                }
                $ssb = number_format($pos_line['sk_fee'], 2, '.', ' ');
                $k_sum += $pos_line['sk_fee'];                
                
                $tmpl->addContent("<tr><td>{$pos_line['id']}</td><td>{$pos_line['vc']}</td><td>{$pos_line['name']}</td><td>{$pos_line['vendor']}</td>"
                . "<td align='right'>{$pos_line['cnt']}</td><td align='right'>{$pos_line['mult']}</td><td align='right'>{$pos_line['bigpack_cnt']}</td><td align='right'>{$pos_line['pcs']}</td>"
                . "<td align='right'>$ssb</td>");
                if($doc_line['type']==2) {
                    $p_sum += $pos_line['p_sum'];
                    $pos_line['price'] = number_format($pos_line['price'], 2, '.', ' ');
                    $pos_line['in_price'] = number_format($pos_line['in_price'], 2, '.', ' ');
                    $pos_line['pos_liq'] = number_format($pos_line['pos_liq'], 2, '.', ' ');
                    $pos_line['p_sum'] = number_format($pos_line['p_sum'], 2, '.', ' ');
                    $tmpl->addContent("<td align='right'>{$pos_line['price']}</td><td align='right'>{$pos_line['in_price']}</td>"
                    . "<td align='right'>{$pos_line['pos_liq']}</td><td align='right'>{$pos_line['p_sum']}</td>");
                }
                $tmpl->addContent("</tr>");
            }
            $k_sum = number_format($k_sum, 2, '.', ' ');
            $tmpl->addContent("<tr><td>&nbsp;</td><td colspan='3'>Итого:</td><td>&nbsp;</td><td></td><td></td>"
            . "<td align='right'>$k_sum</td>");
            if($doc_line['type']==2) {
                $p_sum = number_format($p_sum, 2, '.', ' ');
                $tmpl->addContent("<td></td><td></td><td></td><td align='right'>$p_sum</td>");
            }
            $tmpl->addContent("</tr>");
            $tmpl->addContent("</table>");
            
            $tmpl->addContent("<ul>");
            if($doc_line['type']==2) {
                $r_sum = number_format($info['r_sum'], 2, '.', ' ');
                $tmpl->addContent("<li>Расчётная стоимость для отдела продаж: $r_sum</li>"
                    . "<li>Коэффициент ликвидности: <b>{$info['liq_coeff']}</b></li>");
            }
            if( isset($info['o_uid']) ) {
                $o_name = html_out(isset($users[$info['o_uid']]) ? html_out($users[$info['o_uid']]) : ('??? - '.$info['o_uid'])); 
                $o_fee = number_format($info['o_fee'], 2, '.', ' ');
                $tmpl->addContent("<li>Оператор: $o_name</li>"
                    . "<li>Коэффициент: <b>{$info['o_coeff']}</b></li>"
                    . "<li>Вознаграждение: <b>$o_fee</b></li>");
            }
            
            if( isset($info['r_uid']) ) {
                $r_name = html_out(isset($users[$info['r_uid']]) ? html_out($users[$info['r_uid']]) : ('??? - '.$info['r_uid']));
                $r_fee = number_format($info['r_fee'], 2, '.', ' ');
                $tmpl->addContent("<li>Ответственный: $r_name</li>"
                    . "<li>Коэффициент: <b>{$info['r_coeff']}</b></li>"
                    . "<li>Вознаграждение: <b>$r_fee</b></li>");
            }
            
            if( isset($info['m_uid']) ) {
                $m_name = html_out(isset($users[$info['m_uid']]) ? html_out($users[$info['m_uid']]) : ('??? - '.$info['m_uid']));
                $m_fee = number_format($info['m_fee'], 2, '.', ' ');
                $tmpl->addContent("<li>Менеджер: $m_name</li>"
                    . "<li>Коэффициент: <b>{$info['m_coeff']}</b></li>"
                    . "<li>Вознаграждение: <b>$m_fee</b></li>");
            }
            
            if( isset($info['sk_uid']) ) {
                $sk_name = html_out(isset($users[$info['sk_uid']]) ? html_out($users[$info['sk_uid']]) : ('??? - '.$info['sk_uid']));
                $sk_fee = number_format($info['sk_fee'], 2, '.', ' ');
                $tmpl->addContent("<li>Кладовщик: $sk_name</li>"
                    . "<li>Коэффициент за упаковки: <b>{$info['sk_coeff']}</b></li>"
                    . "<li>Вознаграждение за упаковки: <b>{$info['sk_packfee']}</b></li>"
                    . "<li>Коэффициент за места: <b>{$info['sk_pl_coeff']}</b></li>"
                    . "<li>Вознаграждение за места: <b>{$info['sk_places']}</b></li>"
                    . "<li>Коэффициент за количество: <b>{$info['sk_cnt_coeff']}</b></li>"
                    . "<li>Вознаграждение за количество: <b>{$info['sk_pcnt']}</b></li>"
                    . "<li>Вознаграждение клвдовщика: <b>$sk_fee</b></li>");
            }
            $tmpl->addContent("</ul>");   
        }
    }

    function makeFull($engine) {
        global $CONFIG, $db, $tmpl;
        $dt_f = strtotime(rcvdate('dt_f'));
        $dt_t = strtotime(rcvdate('dt_t') . " 23:59:59");
        $worker_id = rcvint('worker_id');
        
        $users_ldo = new \Models\LDO\workernames();
        $users = $users_ldo->getData();
        
        $salary = new \async\salary(0);
        $salary->loadPosTypes(); 
        $salary->loadPosData();
        $tmpl->addBreadcrumb('Просмотр данных', '');        
        $tmpl->addContent("<table class='list' width='100%'>"
            . "<tr><th>id</th><th>Тип</th><th>Дата</th><th colspan='2'>Ответственный</th><th colspan='2'>Оператор</th><th colspan='2'>Менеджер</th><th colspan='2'>Кладовщик</th>"
            . "<th>Сумма</th></tr>");
        $sum = 0;
        $docs_res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`type`, `date`, `user`, `sum`, `p_doc`, `contract`, `sklad` AS `store_id`"
            . " , `doc_agent`.`responsible` AS `resp_id`, `doc_types`.`name` AS `type_name`, `doc_dopdata`.`value` AS `return`"
            . " FROM `doc_list`"
            . " LEFT JOIN `doc_agent` ON `doc_agent`.`id` = `doc_list`.`agent`"
            . " LEFT JOIN `doc_types` ON `doc_types`.`id` = `doc_list`.`type`"
            . " LEFT JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list`.`id` AND `doc_dopdata`.`param`='return'"
            . " WHERE `ok`>0 AND `mark_del`=0 AND `doc_list`.`type` IN (1,2,8) AND `date`>='$dt_f' AND `date`<'$dt_t'" 
            . " ORDER BY `date`");
        while ($doc_line = $docs_res->fetch_assoc()) {
            if($doc_line['return']) {
                continue;
            }
            $w_cont = 0;
            if($worker_id) {
                $w_cont = 1;
            }
            $doc_vars = array();
            $o_name = $o_fee = $r_name = $r_fee = $m_name = $m_fee = $sk_name = $sk_fee = '';
            $sum_line = 0;
            $res = $db->query('SELECT `param`, `value` FROM `doc_dopdata` WHERE `doc`=' . $doc_line['id']);
            while ($line = $res->fetch_row()) {
                $doc_vars[$line[0]] = $line[1];
            }
            $doc_line['vars'] = $doc_vars;            
            $info = $salary->calcFee($doc_line, $doc_line['resp_id']);
            
            if( isset($info['o_uid']) ) {
                $salary->incFee('operator', $info['o_uid'], $info['o_fee'], $doc_line['id']);
                $o_name = html_out(isset($users[$info['o_uid']]) ? html_out($users[$info['o_uid']]) : ('??? - '.$info['o_uid'])); 
                $sum_line += $info['o_fee'];
                $o_fee = number_format($info['o_fee'], 2, '.', ' ');
                if($worker_id==$info['o_uid']) {
                    $w_cont = 0;
                }
            }
            
            if( isset($info['r_uid']) ) {
                $salary->incFee('resp', $info['r_uid'], $info['r_fee'], $doc_line['id']);
                $r_name = html_out(isset($users[$info['r_uid']]) ? html_out($users[$info['r_uid']]) : ('??? - '.$info['r_uid']));
                $sum_line += $info['r_fee'];
                $r_fee = number_format($info['r_fee'], 2, '.', ' ');
                if($worker_id==$info['r_uid']) {
                    $w_cont = 0;
                }
            }
            
            if( isset($info['m_uid']) ) {
                $salary->incFee('manager', $info['m_uid'], $info['m_fee'], $doc_line['id']);
                $m_name = html_out(isset($users[$info['m_uid']]) ? html_out($users[$info['m_uid']]) : ('??? - '.$info['m_uid']));
                $sum_line += $info['m_fee'];                
                $m_fee = number_format($info['m_fee'], 2, '.', ' ');
                if($worker_id==$info['m_uid']) {
                    $w_cont = 0;
                }

            }
            
            if( isset($info['sk_uid']) ) {
                $salary->incFee('sk', $info['sk_uid'], $info['sk_fee'], $doc_line['id']);
                $sk_name = html_out(isset($users[$info['sk_uid']]) ? html_out($users[$info['sk_uid']]) : ('??? - '.$info['sk_uid']));
                $sum_line += $info['sk_fee'];
                $sk_fee = number_format($info['sk_fee'], 2, '.', ' ');
                if($worker_id==$info['sk_uid']) {
                    $w_cont = 0;
                }
            }
            $sum += $sum_line;
            $p_date = date("Y-m-d", $doc_line['date']); 
            if($sum_line) {
                $sum_line = number_format($sum_line, 2, '.', ' ');
            }   else {
                $sum_line = '';
            }
            if($w_cont) {
                continue;
            }
            $tmpl->addContent("<tr><td><a href='/doc_reports.php?mode=salary&amp;opt=doc&amp;doc={$doc_line['id']}'>{$doc_line['id']}</a></td>"
                . "<td>{$doc_line['type_name']}</td><td>$p_date</td>"
                . "<td>$r_name</td><td align='right'>$r_fee</td><td>$o_name</td><td align='right'>$o_fee</td>"
                . "<td>$m_name</td><td align='right'>$m_fee</td><td>$sk_name</td><td align='right'>$sk_fee</td><td align='right'>$sum_line</td></tr>");
        }
        $sum = number_format($sum, 2, '.', ' ');
        $tmpl->addContent("<tr><td colspan=11>Итого:</td><td align='right'>$sum</td></tr>");
        $tmpl->addContent("</table>");
        $tmpl->addContent("<table class='list'><tr><th colspan=2>По пользователям</th></tr>");
        $users_fee = $salary->getUsersFee();
        foreach($users_fee as $uid=>$info) {
            $fee = $info['operator'] + $info['resp'] + $info['manager'] + $info['sk'];
            $fee = number_format($fee, 2, '.', ' ');
            $r_name = html_out(isset($users[$uid]) ? $users[$uid] : ('??? - '.$uid));
            $tmpl->addContent("<tr><td>$r_name</td><td align='right'>$fee</td></tr>");
        }
        $tmpl->addContent("</table>");
    }

}