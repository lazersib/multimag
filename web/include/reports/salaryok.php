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


/// Отчёт по начисленным вознаграждениям
class Report_SalaryOk extends BaseGSReport { 
        function getName($short = 0) {
            if ($short) {
            return "По начисленным вознаграждениям";
        } else {
            return "Отчёт по начисленным вознаграждениям";
        }
    }

    function Form() {
        global $tmpl, $db;
        $d_t = date("Y-m-d");
        $d_f = date("Y-m-d", time() - 60 * 60 * 24 * 31);
        $tmpl->addBreadcrumb($this->getName(), ''); 
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
            <form action='' method='post'>
            <input type='hidden' name='mode' value='salaryok'>
            <fieldset><legend>Дата</legend>
            С:<input type=text id='dt_f' name='dt_f' value='$d_f'><br>
            По:<input type=text id='dt_t' name='dt_t' value='$d_t'>
            </fieldset>
            Использовать дату:<br>
            <select name='datetype'>
                <optgroup>БЫСТРО</optgroup>
                <option value='doc'>Документа</option>
                <optgroup>МЕДЛЕННО</optgroup>
                <option value='pay_orm'>Начисления вознаграждения операторам/ответственным/менеджерам</option>
                <option value='pay_sk'>Начисления вознаграждения кладовщикам</option>
                <option value='pay_ormsk'>Начисления вознаграждения операторам/ответственным/менеджерам ИЛИ кладовщикам</option>
                </select><br>
            <label><input type='checkbox' name='clearoor' value='1'>Не отображать начисления вне периода</label><br>
            <input type='hidden' name='opt' value='html'>
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

    function Make($engine) {
        global $db, $tmpl;
        $dt_f = strtotime(rcvdate('dt_f'));
        $dt_t = strtotime(rcvdate('dt_t') . " 23:59:59");
        $datetype = request('datetype');
        $clearoor = rcvint('clearoor');
        
        $users_ldo = new \Models\LDO\workernames();
        $users = $users_ldo->getData();
        
        $salary = new \async\salary(0);
        $tmpl->addBreadcrumb('Просмотр данных', '');        
        $tmpl->addContent("<table class='list' width='100%'>"
            . "<tr><th>id</th><th>Тип</th><th>Дата док-та</th>"
                . "<th colspan='2'>Ответственный</th><th colspan='2'>Оператор</th><th colspan='2'>Менеджер</th><th>Дата нач.</th>"
                . "<th colspan='2'>Кладовщик</th><th>Дата нач.</th><th>Сумма</th></tr>");
        $sum = 0;
        $filter = '';
        if($datetype=='doc') {
            $filter = " AND `date`>='$dt_f' AND `date`<'$dt_t'";
        }
        $docs_res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`type`, `date`, `user`, `sum`, `p_doc`, `contract`"
                . ", `sklad` AS `store_id`, `doc_agent`.`responsible` AS `resp_id`, `doc_types`.`name` AS `type_name`, `doc_list`.`firm_id`"
            . " FROM `doc_list`"
            . " LEFT JOIN `doc_agent` ON `doc_agent`.`id` = `doc_list`.`agent`"
            . " LEFT JOIN `doc_types` ON `doc_types`.`id` = `doc_list`.`type`"
            . " WHERE `ok`>0 AND `mark_del`=0 AND `doc_list`.`type` IN (1,2,8,20)" . $filter
            . " ORDER BY `date`");
        $count = 0;
        while ($doc_line = $docs_res->fetch_assoc()) {
            if(!\acl::testAccess([ 'firm.global', 'firm.'.$doc_line['firm_id']], \acl::VIEW)) {
                continue;
            }
            $doc_line['vars'] = array();
            $res = $db->query('SELECT `param`, `value` FROM `doc_dopdata` WHERE `doc`=' . $doc_line['id']);
            while ($line = $res->fetch_row()) {
                $doc_line['vars'][$line[0]] = $line[1];
            }
            $doc_line['textvars'] = array();
            $res = $db->query('SELECT `param`, `value` FROM `doc_textdata` WHERE `doc_id`=' . $doc_line['id']);
            while ($line = $res->fetch_row()) {
                $doc_line['textvars'][$line[0]] = $line[1];
            }
  
            if(!isset($doc_line['textvars']['salary'])) {
                continue;
            }
            $info = json_decode($doc_line['textvars']['salary'], true);            
            
            $orm_date_ok = $sk_date_ok = false;
            $orm_date = $sk_date = '';
            if(isset($info['orm_date_pay']) ) {
                $orm_time = strtotime($info['orm_date_pay']);
                $orm_date = date("Y-m-d", $orm_time);
                if($orm_time>=$dt_f && $orm_time<=$dt_t) {
                    $orm_date_ok = true;
                }
            } 
            if(isset($info['sk_date_pay']) ) {
                $sk_time = strtotime($info['sk_date_pay']);
                $sk_date = date("Y-m-d", $sk_time);
                if($sk_time>=$dt_f && $sk_time<=$dt_t) {
                    $sk_date_ok = true;
                }
            }
            
            if($datetype=='pay_orm' && !$orm_date_ok) {
                continue;
            }
            if($datetype=='pay_sk' && !$sk_date_ok) {
                continue;              
            }
            if($datetype=='pay_ormsk' && !$orm_date_ok && !$sk_date_ok) {                
                continue;                
            }
            
            if($clearoor) {
                if(!$orm_date_ok) {
                    unset($info['o_uid']);
                    unset($info['r_uid']);
                    unset($info['m_uid']);
                    $info['o_fee'] = 0;
                    $info['r_fee'] = 0;
                    $info['m_fee'] = 0;
                }
                if(!$sk_date_ok) {
                    unset($info['sk_uid']);
                    $info['sk_fee'] = 0;
                }
            }
            
            if(isset($info['o_uid'])) {
                $salary->incFee('operator', $info['o_uid'], $info['o_fee'], $doc_line['id']);
                $info['o_name'] = html_out(isset($users[$info['o_uid']]) ? $users[$info['o_uid']] : ('??? - '.$info['o_uid']));  
            } else {
                $info['o_name'] = $info['o_fee'] = '';                
            }
            
            if(isset($info['r_uid'])) {
                $salary->incFee('resp', $info['r_uid'], $info['r_fee'], $doc_line['id']);                
                $info['r_name'] = html_out(isset($users[$info['r_uid']]) ? $users[$info['r_uid']] : ('??? - '.$info['r_uid']));
            } else {
                $info['r_name'] = $info['r_fee'] = '';                
            }
            if(isset($info['m_uid'])) {
                $salary->incFee('manager', $info['m_uid'], $info['m_fee'], $doc_line['id']);
                $info['m_name'] = html_out(isset($users[$info['m_uid']]) ? $users[$info['m_uid']] : ('??? - '.$info['m_uid']));
            } else {
                $info['m_name'] = $info['m_fee'] = '';                
            }
            if(isset($info['sk_uid'])) {
                $salary->incFee('sk', $info['sk_uid'], $info['sk_fee'], $doc_line['id']);
                $info['sk_name'] = html_out(isset($users[$info['sk_uid']]) ? $users[$info['sk_uid']] : ('??? - '.$info['sk_uid']));
            } else {
                $info['sk_name'] = $info['sk_fee'] = '';                
            }
            
            $sum_line = $info['r_fee'] + $info['o_fee'] + $info['m_fee'] + $info['sk_fee'];            
            $sum += $sum_line;
            $p_date = date("Y-m-d", $doc_line['date']);
            $count++;
            $tmpl->addContent("<tr><td><a href='/doc.php?mode=body&doc={$doc_line['id']}'>{$doc_line['id']}</a></td>"
                . "<td>{$doc_line['type_name']}</td><td>$p_date</td>"
                . "<td>{$info['r_name']}</td><td>{$info['r_fee']}</td><td>{$info['o_name']}</td><td>{$info['o_fee']}</td>"
                . "<td>{$info['m_name']}</td><td>{$info['m_fee']}</td><td>$orm_date</td><td>{$info['sk_name']}</td><td>{$info['sk_fee']}</td><td>$sk_date</td><td>$sum_line</td></tr>");
        }
        $tmpl->addContent("<tr><td>Итого:</td><td>$count штук</td><td colspan=9></td><td>$sum</td></tr>");
        $tmpl->addContent("</table>");
        $tmpl->addContent("<table class='list'><tr><th colspan=2>По пользователям</th></tr>");
        $users_fee = $salary->getUsersFee();
        foreach($users_fee as $uid=>$info) {
            $fee = $info['operator'] + $info['resp'] + $info['manager'] + $info['sk'];
            $fee = sprintf('%0.2f', $fee);
            $r_name = html_out(isset($users[$uid]) ? $users[$uid] : ('??? - '.$uid));
            $tmpl->addContent("<tr><td>$r_name</td><td align='right'>$fee</td></tr>");
        }
        $tmpl->addContent("</table>");
    }

}