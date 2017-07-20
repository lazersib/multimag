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
/// Отчёт менеджера
class Report_Manager extends BaseReport {

    /// Получить название отчёта
    function getName($short = 0) {
        if ($short) {
            return "Менеджера";
        } else {
            return "Отчёт менеджера";
        }
    }

    /// Форма для формирования отчёта
    function Form() {
                global $tmpl, $db;
        $curdate = date("Y-m-d");
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>");
        $ldo = new \Models\LDO\workernames();
        $ret = \widgets::getEscapedSelect('worker_id', $ldo->getData(), @$_SESSION['uid'], 'не назначен');       
        $tmpl->addContent("
            <script type=\"text/javascript\">
            function dtinit() {
                    initCalendar('dt',false)
            }
            addEventListener('load',dtinit,false)	
            </script>
            <form action='' method='post'>
            <input type='hidden' name='mode' value='manager'>
            Дата:<br>
            <input type='text' name='date' id='dt' value='$curdate'><br>
            Сотрудник:<br>$ret<br>
            Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
            <button type='submit'>Создать отчет</button></form>");
    }

    function Make($engine) {
        global $db;
        $worker_id = rcvint('worker_id');
        $date = rcvdate('date');
        $this->loadEngine($engine);

        $res = $db->query("SELECT `users`.`name`, `users_worker_info`.`worker_real_name` FROM `users`"
            . "LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`users`.`id` WHERE `id`='$worker_id'");
        if (!$res->num_rows) {
            throw new Exception("Пользователь не найден");
        }
        $worker_info = $res->fetch_assoc();
        
        $date = getdate(strtotime($date));
        $days = cal_days_in_month(CAL_GREGORIAN, $date['mon'], $date['year']);
        $d_start = mktime(0, 0, 0, $date['mon'], 1, $date['year']);
        $d_end = mktime(23, 59, 59, $date['mon'], $days, $date['year']);
        $d_start_p = date("Y-m-d H:i:s", $d_start);
        $d_end_p = date("Y-m-d H:i:s", $d_end);
        $wd_count = 0;
        for($i=0;$i<$days;$i++) {
            $w = (int)date("w", $d_start+$i*60*60*24);
            if($w>0 && $w<6) {
                $wd_count++;
            }
        }
        
        
        $this->header("Отчёт по работе с клиентами");
        $this->header("Сотрудник: {$worker_info['worker_real_name']} ({$worker_info['name']})", 3);
        $this->header("Диапазон: c $d_start_p по $d_end_p", 3);
        
        $this->header("1. Целью данного отчета является сбор и анализ данных для оптимизации работы компании предотвращении и/или устранение проблемных ситуаций, а также оценка работы сотрудника.
2. Сроки сдачи данного Отчета Директору — 5 числа следующего месяца.
3. Информация, содержащаяся в данном Отчете подлежит выборочному контролю. Автором отчёта считается сотрудник, сформировавший отчёт. Автор данного Отчета несет административную ответственность за достоверность его содержания.", 5);
        
        $widths = array(30, 30, 40);
        $headers = array('Критерий', 'Данные', 'Примечание');
        $this->tableBegin($widths);
        $this->tableHeader($headers);
        
        $this->tableRow(array("Количество рабочих дней:", $wd_count, ""));
        $this->tableRow(array("Количество отработанных дней:", "", ""));
        
        $res = $db->query("SELECT `doc_list`.`id`, `wid`.`value` AS `worker_id` FROM `doc_list`"
            . " LEFT JOIN `doc_dopdata` AS `wid` ON `wid`.`doc`=`doc_list`.`id` AND `wid`.`param`='worker_id'"
            . " WHERE `doc_list`.`type`='3' AND `doc_list`.`date`>='$d_start' AND `doc_list`.`date`<='$d_end'"            
            . "     AND (`doc_list`.`user`='$worker_id' OR `wid`.`value`='$worker_id')");
        $z_receive = 0;
        while($line = $res->fetch_assoc()) {
            $z_receive++;
        }
        
        $res = $db->query("SELECT `doc_list`.`id`  FROM `doc_list`"
            . " LEFT JOIN `doc_dopdata` AS `wid` ON `wid`.`doc`=`doc_list`.`id` AND `wid`.`param`='worker_id'"
            . " LEFT JOIN `doc_dopdata` AS `st` ON `st`.`doc`=`doc_list`.`id` AND `st`.`param`='status'"
            . " WHERE `doc_list`.`type`='3' AND `doc_list`.`date`>='$d_start' AND `doc_list`.`date`<='$d_end'"            
            . "     AND (`st`.`value`='err' OR `st`.`value`='ready' OR `st`.`value`='ok')"
            . "     AND (`doc_list`.`user`='$worker_id' OR `wid`.`value`='$worker_id')");
        $z_ok = 0;
        while($line = $res->fetch_assoc()) {
            $z_ok++;
        }
        
        $res = $db->query("SELECT `doc_list`.`id`  FROM `doc_list`"
            . " LEFT JOIN `doc_dopdata` AS `wid` ON `wid`.`doc`=`doc_list`.`id` AND `wid`.`param`='worker_id'"
            . " LEFT JOIN `doc_dopdata` AS `st` ON `st`.`doc`=`doc_list`.`id` AND `st`.`param`='status'"
            . " WHERE `doc_list`.`type`='3' AND `doc_list`.`date`>='$d_start' AND `doc_list`.`date`<='$d_end'"            
            . "     AND (`st`.`value`='inproc')"
            . "     AND (`doc_list`.`user`='$worker_id' OR `wid`.`value`='$worker_id')");
        $z_inproc = 0;
        while($line = $res->fetch_assoc()) {
            $z_inproc++;
        }
        
        $res = $db->query("SELECT `doc_list`.`id`  FROM `doc_list`"
            . " LEFT JOIN `doc_dopdata` AS `wid` ON `wid`.`doc`=`doc_list`.`id` AND `wid`.`param`='worker_id'"
            . " LEFT JOIN `doc_dopdata` AS `st` ON `st`.`doc`=`doc_list`.`id` AND `st`.`param`='status'"
            . " WHERE `doc_list`.`type`='3' AND `doc_list`.`date`>='$d_start' AND `doc_list`.`date`<='$d_end'"            
            . "     AND (`st`.`value`='err')"
            . "     AND (`doc_list`.`user`='$worker_id' OR `wid`.`value`='$worker_id')");
        $z_err = 0;
        while($line = $res->fetch_assoc()) {
            $z_err++;
        }
        
        $res = $db->query("SELECT `doc_list`.`id`  FROM `doc_list`"
            . " LEFT JOIN `doc_dopdata` AS `wid` ON `wid`.`doc`=`doc_list`.`id` AND `wid`.`param`='worker_id'"
            . " INNER JOIN `doc_list` AS `d_r` ON `d_r`.`p_doc`=`doc_list`.`id` AND `d_r`.`type`='2' AND `d_r`.`ok`>'0'"
            . " WHERE `doc_list`.`type`='3' AND `doc_list`.`date`>='$d_start' AND `doc_list`.`date`<='$d_end'"
            . "     AND (`doc_list`.`user`='$worker_id' OR `wid`.`value`='$worker_id')");
        $z_real = 0;
        while($line = $res->fetch_assoc()) {
            $z_real++;
        }
        $this->tableAltStyle(1);
        $this->tableSpannedRow(array(3), array("Заявки покупателей:"));
        $this->tableAltStyle(0);
        $this->tableRow(array("Принято:", $z_receive, ""));
        $this->tableRow(array("Обработано:", $z_ok, ""));
        $this->tableRow(array("В работе:", $z_inproc, ""));
        $this->tableRow(array("Реализовано:", $z_real, ""));
        $this->tableRow(array("Отказов:", $z_err, ""));
        
        $ldo = new \Models\LDO\skladnames();
        $storenames = $ldo->getData();
        
        // импорт/россияб физ/юр
        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`sum`, `doc_list`.`agent`, `doc_agent`.`type` AS `agent_type`, `doc_list`.`sklad` AS `store`"
            . " FROM `doc_list`"
            . " LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`"
            . " WHERE `doc_list`.`type`='2' AND `doc_list`.`date`>='$d_start' AND `doc_list`.`date`<='$d_end'"            
            . "     AND `doc_list`.`user`='$worker_id'");
        $r_ok = $r_sum = 0;
        $import_sum = $import_mass = 0;
        $rus_sum = $rus_mass = 0;
        $ul_sum = $fl_sum = 0;
        $ul_mass = $fl_mass = 0;
        $s_sum = array();
        $s_mass = array();
        foreach($storenames as $s_id => $s_name) {
            $s_sum[$s_id] = 0;
            $s_mass[$s_id] = 0;
        }
        while($doc_info = $res->fetch_assoc()) {
            $r_ok++;
            $r_sum+=$doc_info['sum'];
            $l_res = $db->query("SELECT `doc_list_pos`.`cnt`, `doc_list_pos`.`cost` AS `price`, `doc_base`.`mass`, `class_country`.`alfa2` AS `cc`"
                . " FROM `doc_list_pos`"
                . " INNER JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`"
                . " LEFT JOIN `class_country` ON `class_country`.`id`=`doc_base`.`country`"
                . " WHERE `doc_list_pos`.`doc`='{$doc_info['id']}'");
            while($line = $l_res->fetch_assoc()) {
                if($line['cc']=='RU') {
                    $rus_sum+=$line['cnt']*$line['price'];
                    $rus_mass+=$line['cnt']*$line['mass'];
                }
                else {
                    $import_sum+=$line['cnt']*$line['price'];
                    $import_mass+=$line['cnt']*$line['mass'];
                }
                if($doc_info['agent_type']==1) {
                    $ul_sum+=$line['cnt']*$line['price'];
                    $ul_mass+=$line['cnt']*$line['mass'];
                }
                else {
                    $fl_sum+=$line['cnt']*$line['price'];
                    $fl_mass+=$line['cnt']*$line['mass'];
                }
                $s_sum[$doc_info['store']] += $line['cnt']*$line['price'];
                $s_mass[$doc_info['store']] += $line['cnt']*$line['mass'];
            }
        }
               
        // возвраты
        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`sum` FROM `doc_list`"
            . " INNER JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list`.`id` AND `doc_dopdata`.`param`='return' AND `doc_dopdata`.`value`!=''"
            . "      AND `doc_dopdata`.`value`!='0'"
            . " WHERE `doc_list`.`type`='1' AND `doc_list`.`date`>='$d_start' AND `doc_list`.`date`<='$d_end'"            
            . "     AND `doc_list`.`user`='$worker_id'");
        $ret_sum = $ret_cnt = $ret_mass = 0;
        while($doc_info = $res->fetch_assoc()) {
            $ret_sum+=$doc_info['sum'];
            $l_res = $db->query("SELECT `doc_list_pos`.`cnt`, `doc_list_pos`.`cost` AS `price`, `doc_base`.`mass`, `class_country`.`alfa2` AS `cc`"
                . " FROM `doc_list_pos`"
                . " INNER JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`"
                . " WHERE `doc_list_pos`.`doc`='{$doc_info['id']}'");
            while($line = $l_res->fetch_assoc()) {
                $ret_sum+=$line['cnt']*$line['price'];
                $ret_mass+=$line['cnt']*$line['mass'];
                $ret_cnt+=$line['cnt'];
            }
        }
        
        $this->tableAltStyle(1);
        $this->tableSpannedRow(array(3), array("Реализации:"));
        $this->tableAltStyle(0);
        $this->tableRow(array("Количество:", $r_ok, ""));
        $this->tableRow(array("На сумму:", number_format($r_sum, 2, '.', ' '), ""));
        $this->tableRow(array("В т.ч импорта (сумма/масса):", number_format($import_sum, 2, '.', ' ')." / $import_mass", ""));
        $this->tableRow(array("В т.ч российских (сумма/масса):", number_format($rus_sum, 2, '.', ' ')." / $rus_mass", ""));
        $this->tableRow(array("В т.ч физ.лиц (сумма/масса):", number_format($fl_sum, 2, '.', ' ')." / $fl_mass", ""));
        $this->tableRow(array("В т.ч юр.лиц (сумма/масса):", number_format($ul_sum, 2, '.', ' ')." / $ul_mass", ""));
        foreach($storenames as $s_id => $s_name) {
            if($s_sum[$s_id]>0 || $s_mass[$s_id]>0) {
                $this->tableRow(array("В т.ч со склада $s_name (сумма/масса):", number_format($s_sum[$s_id], 2, '.', ' ')." / $s_mass[$s_id]", ""));
            }
        }
        $this->tableRow(array("Откатов (количество/сумма):", "", ""));
        $this->tableRow(array("Возвратов (количество/сумма/масса):", "$ret_cnt / ".number_format($ret_sum, 2, '.', ' ')." / $ret_mass", ""));
        
        $rev_act_cnt = 0;
        $res = $db->query("SELECT `doc_agent`.`id`"
            . " FROM `doc_agent`"
            . " WHERE `data_sverki`>='$d_start_p' AND `data_sverki`<='$d_end_p' AND `responsible`='$worker_id'");
        $rev_act_cnt = $res->num_rows;
        
        $debt = 0;
        $agent_cnt = 0;
        $res = $db->query("SELECT `doc_agent`.`id`"
            . " FROM `doc_agent`"
            . " WHERE `responsible`='$worker_id'");
        while($line=$res->fetch_assoc()) {
            $d = agentCalcDebt($line['id']);
            if($d>0) {
                $debt += $d;
            }
            $agent_cnt++;
        }
        
        $this->tableAltStyle(1);
        $this->tableSpannedRow(array(3), array("Акты сверок:"));
        $this->tableAltStyle(0);
        $this->tableRow(array("Сделано актов сверок:", $rev_act_cnt, ""));
        $this->tableRow(array("Подписано односторонне:", "", ""));
        $this->tableRow(array("Подписано двусторонне:", "", ""));
        $this->tableRow(array("Наименование организации (планируемые сроки):", "", ""));
        $this->tableRow(array("Общая задолженность, руб:", number_format($debt, 2, '.', ' '), ""));
        $this->tableRow(array("В т.ч. просроченная, руб:", "", "возможен автоматический расчёт на основании данных договоров"));
        $this->tableRow(array("Наименование организаций, наличие письма, обратная связь", "", ""));
        
        $res = $db->query("SELECT `doc_list`.`id`, `rc`.`value` AS `received` FROM `doc_list`"
            . " LEFT JOIN `doc_dopdata` AS `rc` ON `rc`.`doc`=`doc_list`.`id` AND `rc`.`param`='received'"
            . " WHERE `doc_list`.`type`='14' AND `doc_list`.`date`>='$d_start' AND `doc_list`.`date`<='$d_end'"            
            . "     AND `doc_list`.`user`='$worker_id'");
        $contract_cnt = $contract_signed = 0;
        while($doc_info = $res->fetch_assoc()) {
            $contract_cnt++;
            if($doc_info['received']) {
                $contract_signed++;
            }
        }
        
        $new_agents = array();
        $res = $db->query("SELECT `doc_log`.`object_id` AS `id`, `doc_agent`.`fullname`"
            . " FROM `doc_log`"
            . " INNER JOIN `doc_agent` ON `doc_agent`.`id`=`doc_log`.`object_id`"
            . "WHERE `time`>='$d_start_p' AND `time`<='$d_end_p' AND `object`='agent' AND `motion`='CREATE' AND `user`='$worker_id'");
        $new_agent_cnt = $res->num_rows;
        while($a_line = $res->fetch_assoc()) {
            $contacts = array();
            $c_res = $db->query("SELECT * FROM `agent_contacts` WHERE `agent_id`='{$a_line['id']}'");
            while($c_line = $c_res->fetch_assoc()) {
                $contacts[] = $c_line;
            }
            $new_agents[] = array(
                'id' => $a_line['id'],
                'fullname' => $a_line['fullname'],
                'contacts' => $contacts,
            );            
        }
        
        $res = $db->query("SELECT `doc_list`.`agent` FROM `doc_list`"
            . " WHERE `doc_list`.`date`>='$d_start' AND `doc_list`.`date`<='$d_end'"            
            . "     AND `doc_list`.`user`='$worker_id'"
            . " GROUP BY `doc_list`.`agent`");
        $agent_m_cnt = 0;
        if($res->num_rows>0) {
            $agent_m_cnt = $res->num_rows;
        }
        
        $this->tableAltStyle(1);
        $this->tableSpannedRow(array(3), array("Прочее:"));
        $this->tableAltStyle(0);
        $this->tableRow(array("Клиентов всего:", $agent_cnt, ""));
        $this->tableRow(array("Клиентов за месяц:", $agent_m_cnt, ""));
        $this->tableRow(array("Создано договоров:", $contract_cnt, ""));
        $this->tableRow(array("Подписано договоров:", $contract_signed, ""));
        $this->tableRow(array("Новых клиентов (список с контактами в приложении):", $new_agent_cnt, ""));
        $this->tableRow(array("Посещено организаций:", "", ""));
        $this->tableRow(array("Холодных звонков (список в приложении):", "", ""));
        $this->tableRow(array("Поднято клиентов (список в приложении):", "", ""));
               
        $this->tableEnd();
        
        $this->header("Приложение: новые клиенты", 3);
        $widths = array(30, 30, 20, 20);
        $headers = array('Клиент', 'Контактное лицо', 'Должность', 'Телефон');
        $this->tableBegin($widths);
        $this->tableHeader($headers);
        foreach($new_agents as $agent) {
            $no_contacts = 1;
            foreach ($agent['contacts'] as $contact) {
                if($contact['type']!='phone') {
                    continue;
                }
                $this->tableRow(array($agent['fullname'], $contact['person_name'], $contact['person_post'], $contact['value']));
                $no_contacts = 0;
            }
            if($no_contacts) {
                $this->tableRow(array($agent['fullname'], "--не заполнено--", "--не заполнено--", "--не заполнено--"));
            }
        }
        
        $this->output();
        exit(0);
    }

}


