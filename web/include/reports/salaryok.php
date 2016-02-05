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
            </fieldset>
            Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
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
        global $CONFIG, $db, $tmpl;
        $dt_f = strtotime(rcvdate('dt_f'));
        $dt_t = strtotime(rcvdate('dt_t') . " 23:59:59");
        
        $users_ldo = new \Models\LDO\workernames();
        $users = $users_ldo->getData();
        
        $salary = new \async\salary(0);
        $salary->loadPosTypes();    
        $tmpl->addBreadcrumb('Просмотр данных', '');        
        $tmpl->addContent("<table class='list' width='100%'>"
            . "<tr><th>id</th><th>Тип</th><th>Дата</th><th colspan='2'>Ответственный</th><th colspan='2'>Оператор</th><th colspan='2'>Менеджер</th><th colspan='2'>Кладовщик</th>"
            . "<th>Сумма</th></tr>");
        $sum = 0;
        $docs_res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`type`, `date`, `user`, `sum`, `p_doc`, `contract`"
                . ", `sklad` AS `store_id`, `doc_agent`.`responsible` AS `resp_id`, `doc_types`.`name` AS `type_name`, `doc_list`.`firm_id`"
            . " FROM `doc_list`"
            . " LEFT JOIN `doc_agent` ON `doc_agent`.`id` = `doc_list`.`agent`"
            . " LEFT JOIN `doc_types` ON `doc_types`.`id` = `doc_list`.`type`"
            . " WHERE `ok`>0 AND `mark_del`=0 AND `doc_list`.`type` IN (1,2,8,20) AND `date`>='$dt_f' AND `date`<'$dt_t'" 
            . " ORDER BY `date`");
        $count = 0;
        while ($doc_line = $docs_res->fetch_assoc()) {
            if(!\acl::testAccess([ 'firm.global', 'firm.'.$doc_line['firm_id']], \acl::VIEW)) {
                continue;
            }
            $doc_vars = array();
            $res = $db->query('SELECT `param`, `value` FROM `doc_dopdata` WHERE `doc`=' . $doc_line['id']);
            while ($line = $res->fetch_row()) {
                $doc_vars[$line[0]] = $line[1];
            }
            $doc_line['vars'] = $doc_vars;    
            if(!isset($doc_line['vars']['salary'])) {
                continue;
            }
            $info = json_decode($doc_line['vars']['salary'], true);
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
                . "<td>{$info['m_name']}</td><td>{$info['m_fee']}</td><td>{$info['sk_name']}</td><td>{$info['sk_fee']}</td><td>$sum_line</td></tr>");
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