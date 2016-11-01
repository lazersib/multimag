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

namespace modules\service;

/// Статистика телефонных вызовов
class CDR extends \IModule {
    
    protected $queue_events = array(
        'QUEUESTART'=>'Старт очереди',
        'ENTERQUEUE'=>'Вход в очередь', 
        'CONNECT'=>'Соединён',
        'RINGNOANSWER'=>'Нет ответа',
        'COMPLETECALLER'=>'Завершён вызывающим',
        'COMPLETEAGENT'=>'Завершён агентом',
        'ABANDON'=>'Брошен',
        'TRANSFER'=>'Перевод',
        'CONFIGRELOAD'=>'Конфиг перезагружен'
    );


    public function __construct() {
        parent::__construct();
        $this->acl_object_name = 'service.cdr';
    }

    public function getName() {
        return 'Статистика телефонных вызовов';
    }
    
    public function getDescription() {
        return 'Модуль для просмотра статистики телефонной системы. Для работы модуля в конфигурационном файле'
        . ' необходимо указать параметры подключения к базе данных.';  
    }
    
    /// Нормализация номера телефона
    protected function getOptimizedPhone($phone) {
        global $CONFIG;
        $phone = preg_replace("/[^0-9+]/", "", $phone);
        if(strlen($phone)<3) {
            return false;
        }
        $phoneplus = $phone[0]=='+';
        $phone = preg_replace("/[^0-9]/", "", $phone);
        $len = strlen($phone);
        if($phone[0]==7 && $len==11) {
            return '+'.$phone;
        } elseif(!$phoneplus && $phone[0]==8 && $len==11) {
            return '+7'.substr($phone,1);
        } elseif(!$phoneplus && $phone[0]==9 && $len==10) {
            return '+7'.$phone; 
        } elseif(!$phoneplus && @$CONFIG['cdr']['local_length']==$len) {
            return @$CONFIG['cdr']['local_perfix'].$phone;
        } else {
            return $phone;
        }
    }
    
    protected function getCDR($db, $filter=null) {
        $data = array();
        $where_sql = ' WHERE 1 ';
        if(is_array($filter)) {
            foreach ($filter as $id => $value) {
                if(!$value) {
                    continue;
                }
                switch ($id) {
                    case 'date_from':
                        $where_sql .= " AND `calldate`>='".$db->real_escape_string($value)."'";
                        break;
                    case 'date_to':
                        $where_sql .= " AND `calldate`<='".$db->real_escape_string($value)."'";
                        break;
                    case 'src':
                        $where_sql .= " AND `src` LIKE '".$db->real_escape_string($value)."'";
                        break;
                    case 'dst':
                        $where_sql .= " AND `dst` LIKE '".$db->real_escape_string($value)."'";
                        break;
                    case 'disposition':
                        $where_sql .= " AND `disposition` = '".$db->real_escape_string($value)."'";
                        break;
                    case 'dcontext':
                        $where_sql .= " AND `dcontext` = '".$db->real_escape_string($value)."'";
                        break;
                    case 'accountcode':
                        $where_sql .= " AND `accountcode` = '".$db->real_escape_string($value)."'";
                        break;
                }
            }
        }
        $res = $db->query("SELECT SQL_CALC_FOUND_ROWS `asterisk_cdr`.`id`, `calldate`, `clid`, `src`, `dst`, `dcontext`, `lastapp`, `lastdata`, `accountcode`"
                . ", `duration`, `billsec`, `disposition`, `uniqueid`, `ex_queue`.`event` AS `q_event`, `conn_queue`.`agent` AS `q_agent`"
            . " FROM `asterisk_cdr`"
            . " LEFT JOIN `asterisk_queue_log` AS `ex_queue` ON `ex_queue`.`callid`=`asterisk_cdr`.`uniqueid`"
                . " AND (`ex_queue`.`event`='ABANDON' OR `ex_queue`.`event`='COMPLETECALLER' OR `ex_queue`.`event`='COMPLETEAGENT' OR `ex_queue`.`event`='TRANSFER')"
            . " LEFT JOIN `asterisk_queue_log` AS `conn_queue` ON `conn_queue`.`callid`=`asterisk_cdr`.`uniqueid`"
                . " AND (`conn_queue`.`event`='CONNECT')"
            . $where_sql
            . " ORDER BY `calldate` DESC"
            . " LIMIT 150000");
        while ($line = $res->fetch_assoc()) {
            $data[] = $line;
        }
        return $data;
    }
    
    protected function getCDRContextList() {
        global $db;
        $data = array();
        $res = $db->query("SELECT `dcontext`"
            . " FROM `asterisk_cdr`"
            . " GROUP BY `dcontext`"
            . " ORDER BY `dcontext` ASC"
            . " LIMIT 1500");
        while ($line = $res->fetch_assoc()) {
            $data[] = $line['dcontext'];
        }
        return $data;
    }
    
    protected function getDefinedContextList() {
        global $db;
        $data = array();
        $res = $db->query("SELECT * FROM `asterisk_context`");
        while ($line = $res->fetch_assoc()) {
            $data[$line['name']] = $line;
        }
        return $data;
    }
    
    protected function getCDRAccountsList() {
        global $db;
        $data = array();
        $res = $db->query("SELECT `accountcode`"
            . " FROM `asterisk_cdr`"
            . " GROUP BY `accountcode`"
            . " ORDER BY `accountcode` ASC"
            . " LIMIT 1500");
        while ($line = $res->fetch_assoc()) {
            $data[] = $line['accountcode'];
        }
        return $data;
    }
    
    protected function getQueue($filter=null, $page=1) {
        global $db;
        $data = array();
        $where_sql = ' WHERE 1 ';
        if(is_array($filter)) {
            foreach ($filter as $id => $value) {
                if(!$value) {
                    continue;
                }
                switch ($id) {
                    case 'date_from':
                        $where_sql .= " AND `time`>='".$db->real_escape_string($value)."'";
                        break;
                    case 'date_to':
                        $where_sql .= " AND `time`<='".$db->real_escape_string($value)."'";
                        break;
                    case 'callid':
                        $where_sql .= " AND `callid` = '".$db->real_escape_string($value)."'";
                        break;
                    case 'queuename':
                        $where_sql .= " AND `queuename` LIKE '".$db->real_escape_string($value)."'";
                        break;
                    case 'agent':
                        $where_sql .= " AND `agent` = '".$db->real_escape_string($value)."'";
                        break;
                    case 'event':
                        $where_sql .= " AND `event` = '".$db->real_escape_string($value)."'";
                        break;
                }
            }
        }
        $res = $db->query("SELECT SQL_CALC_FOUND_ROWS `id`, `time`, `callid`, `queuename`, `agent`, `event`, `data1`, `data2`, `data3`, `data4`, `data5`"
            . " FROM `asterisk_queue_log`"
            . $where_sql
            . " ORDER BY `time` ASC"
            . " LIMIT 150000");
        while ($line = $res->fetch_assoc()) {
            $data[] = $line;
        }
        return $data;
    }
    
    protected function getQueueEvents() {
        global $db;
        $data = array();
        $res = $db->query("SELECT `event`"
            . " FROM `asterisk_queue_log`"
            . " GROUP BY `event`"
            . " ORDER BY `event` ASC"
            . " LIMIT 150000");
        while ($line = $res->fetch_assoc()) {
            $data[] = $line['event'];
        }
        return $data;
    }
    
    protected function getQueueSummary($db, $filter=null) {
        
    }


    protected function getAgentPhonesInverse($db) {
        $data = array();
        $res = $db->query("SELECT `agent_id`, `value` FROM `agent_contacts` WHERE `type`='phone' AND `value`!=''");
        while($nxt = $res->fetch_row()) {
            $data[$nxt[1]] = $nxt[0];
        }
        return $data;
    }
    
    protected function getUserPhonesInverse($db) {
        $data = array();
        $res = $db->query("SELECT `id`, `reg_phone` FROM `users` WHERE `reg_phone`!=''");
        while($nxt = $res->fetch_row()) {
            $data[$nxt[1]] = $nxt[0];
        }
        return $data;
    }

    protected function getWorkerPhonesInverse($db) {
        $data = array();
        $res = $db->query("SELECT `user_id`, `worker_int_fix_phone` FROM `users_worker_info` WHERE `worker_int_fix_phone`!='' ORDER BY `worker`");
        while($nxt = $res->fetch_row()) {
            $data[$nxt[1]] = $nxt[0];
        }
        return $data;
    }
    
    protected function getObjectLinkForPhone($phone) {
        $phone = $this->getOptimizedPhone($phone);
        if(!$phone) {
            return '';
        }
        if(isset($this->ap[$phone])) {
            $obj_id = $this->ap[$phone];
            if(isset($this->agents[$obj_id])) {
                $src_cell = $this->agents[$obj_id];
            } else {
                $src_cell = '?';
            }
            return "<a href='/docs.php?l=agent&mode=srv&opt=ep&pos=".$obj_id."'>".html_out($src_cell)."</a>";
        } elseif(isset($this->wp[$phone])) {
            $obj_id = $this->wp[$phone];
            if(isset($this->users[$obj_id])) {
                $src_cell = $this->users[$obj_id];
            } else {
                $src_cell = '?';
            }
            return "<a href='/adm.php?mode=users&amp;sect=view&amp;user_id=".$obj_id."'>".html_out($src_cell)."</a>";
        } elseif(isset($this->up[$phone])) {
            $obj_id = $this->up[$phone];
            if(isset($this->users[$obj_id])) {
                $src_cell = $this->users[$obj_id];
            } else {
                $src_cell = '?';
            }
            return "<a href='/adm.php?mode=users&amp;sect=view&amp;user_id=".$obj_id."'>".html_out($src_cell)."</a>";
        }
        return '';
    }

    protected function renderCDR() {
        global $tmpl, $db, $CONFIG;
        $filter = requestA(array('date_from', 'date_to', 'src', 'dst', 'dcontext', 'accountcode', 'direction', 'cgroup'));
        if(!$filter['date_from']) {
            $filter['date_from'] = date("Y-m-d");
        }
        $cdb = $this->getDefinedContextList();
        $contexts = $this->getCDRContextList();
        $context_options = "<option value=''>--не задан--</option>";
        foreach($contexts as $context) {
            $sel = $filter['dcontext']==$context?' selected':'';
            $context_options .= "<option value='$context'{$sel}>$context</option>";
        }
        
        $accounts = $this->getCDRAccountsList();
        $account_options = "<option value=''>--не задан--</option>";
        foreach($accounts as $account) {
            $sel = $filter['accountcode']==$account?' selected':'';
            $account_options .= "<option value='$account'{$sel}>$account</option>";
        }
        $dir = array(
            'in' => [
                'name' => 'Входящий',
                'sname' => 'Вход.',
                'color' => '080',
            ],
            'out' => [
                'name' => 'Исходящий',
                'sname' => 'Исх.',
                'color' => '008',
            ],
            'int' => [
                'name' => 'Внутренний',
                'sname' => 'Внут.',
                'color' => '888',
            ],
            'unk' => [
                'name' => 'Неизвестный',
                'sname' => 'Неизв.',
                'color' => 'F00',
            ],
        );
        $dir_options = "<option value=''>--не задан--</option>";
        foreach($dir as $id => $d) {
            $sel = $filter['direction']==$id?' selected':'';
            $dir_options .= "<option value='$id'{$sel}>{$d['name']}</option>";
        }
        $cgroups = array();
        foreach($cdb as $d) {
            $cgroups[$d['group_name']] = $d['group_name'];
        }
        
        $cgroups_options = "<option value=''>--не задан--</option>";
        foreach($cgroups as $d) {
            $sel = $filter['cgroup']==$d?' selected':'';
            $gn = html_out($d);
            $cgroups_options .= "<option value='$gn'{$sel}>$gn</option>";
        }
        
        
        $tmpl->addBreadcrumb('Детализация вызовов', '');
        $tmpl->addContent("<script type='text/javascript' src='/js/api.js'></script><script type='text/javascript' src='/css/doc_script.js'></script>"
            . "<form action='{$this->link_prefix}&amp;sect=cdr' method='post'>"
                . "<table>"
                . "<tr>"
                . "<th>Дата от:</th>"
                . "<th>Дата до:</th>"
                . "<th>Номер-источник</h>"
                . "<th>Номер-цель</th>"
                . "<th>Контекст</th>"
                . "<th>Аккаунт</th>"
                . "<th>Направление</th>"
                . "<th>Группа</th>"
                . "</tr>"
                . "<tr><td><input type='text' name='date_from' value='{$filter['date_from']}' id='date_from'></td>"
                . "<td><input type='text' name='date_to' value='{$filter['date_to']}' id='date_to'></td>"
                . "<td><input type='text' name='src' value='{$filter['src']}'></td>"
                . "<td><input type='text' name='dst' value='{$filter['dst']}'></td>"
                . "<td><select name='dcontext'>$context_options</select></td>"
                . "<td><select name='accountcode'>$account_options</select></td>"
                . "<td><select name='direction'>$dir_options</select></td>"
                . "<td><select name='cgroup'>$cgroups_options</select></td></tr>"
                . "</table>"
                . "<button type='submit'>Отфильтровать</button>"
                . "</form>"
                . "<script type=\"text/javascript\">"
                . "initCalendar('date_from',false);"
                . "initCalendar('date_to',false);"
                . "</script>");
        $tmpl->addContent("<table class='list' width='100%'>"
            . "<tr><th rowspan='2'>Дата</th><th rowspan='2'>Напр.</th><th rowspan='2'>Группа</th>"
            . "<th colspan='2'>Инициатор</th><th rowspan='2'>Аккаунт</th><th colspan='2'>Цель</th><th rowspan='2'>Контекст</th>"
            . "<th rowspan='2'>Длительность</th><th rowspan='2'>Статус</th><th rowspan='2'>Статус очереди</th><th rowspan='2'>Агент очереди</th><th rowspan='2'>Файл</th></tr>"
            . "<tr><th>Номер</th><th>Принадлежность</th><th>Номер</th><th>Принадлежность</th></tr>");
        $data = $this->getCDR($db, $filter);
        $this->ap = $this->getAgentPhonesInverse($db);
        $this->wp = $this->getWorkerPhonesInverse($db);
        $this->up = $this->getUserPhonesInverse($db);
        $agents_ldo = new \Models\LDO\agentnames();
        $this->agents = $agents_ldo->getData();
        $users_ldo = new \Models\LDO\usernames();
        $this->users = $users_ldo->getData();        
        
        $file_base_url = $this->link_prefix . "&amp;sect=audio&callid=";
        if(isset($CONFIG['service_cdr']['file_path'])) {
            $file_dir = $CONFIG['service_cdr']['file_path'];
        } else {
            $file_dir = '/var/spool/asterisk/monitor';
        }
        if(isset($CONFIG['service_cdr']['file_path'])) {
            $file_ext= $CONFIG['service_cdr']['file_ext'];
        } else {
            $file_ext = 'wav';
        }
        $count = $duration = 0;
        foreach ($data as $line_id=>$line) {
            $src_cell = $dst_cell = $queue_stat = '';
            $src_cell = $this->getObjectLinkForPhone($line['src']);
            $dst_cell = $this->getObjectLinkForPhone($line['dst']);
            if($src_cell=='') {
                $src_cell = "<a href='#' onclick='return createAgentFromPhoneMenu(event,\"{$line['src']}\");'><img src='/img/i_add.png' alt='Добавить'</a></a>";
            }
            if($dst_cell=='') {
                $dst_cell = "<a href='#' onclick='return createAgentFromPhoneMenu(event,\"{$line['dst']}\");'><img src='/img/i_add.png' alt='Добавить'</a></a>";
            }
            $direction = $cgroup = '-';
            if(isset($cdb[$line['dcontext']])) {
                $dc = $cdb[$line['dcontext']];                
            } else {
                $dc = ['name'=>'', 'direction'=>'unk', 'group_name'=>''];
            }
            if($filter['direction']) {
                if($filter['direction']!=$dc['direction']) {
                    continue;
                }
            }
            if($filter['cgroup']) {
                if($filter['cgroup']!=$dc['group_name']) {
                    continue;
                }
            }
            switch($dc['direction']) {
                case 'in':
                case 'out':
                case 'int':
                case 'unk':
                    $direction = "<span style='color:#".$dir[$dc['direction']]['color']."'>".$dir[$dc['direction']]['sname']."</span>";
                    break;
            }
            $cgroup = html_out($dc['group_name']);
            $duration +=intval($line['billsec']);
            $count++;
            $lenght = sectostrinterval(intval($line['billsec']));
            switch($line['q_event']) {
                case 'ABANDON':
                    $queue_stat = "<span style='color:#f80'>брошен</span>";
                    break;
                case 'COMPLETECALLER':
                    $queue_stat = "<span style='color:#080'>завершён&nbsp;вызывающим</span>";
                    break;
                case 'COMPLETEAGENT':
                    $queue_stat = "<span style='color:#f80'>завершён&nbsp;принимающим</span>";
                    break;
                default:
                    $queue_stat = $line['q_event'];
            }
            if($queue_stat) {
                $queue_stat = "<a href='" . $this->link_prefix . "&amp;sect=queue&callid=".html_out($line['uniqueid'])."'>{$queue_stat}</a>";
            }
            
            switch($line['disposition']) {
                case 'ANSWERED':
                    $disposition = "<span style='color:#0c0'>отвечен</span>";
                    break;
                case 'BUSY':
                    $disposition = "<span style='color:#00c'>занято</span>";
                    break;
                case 'NO ANSWER':
                    $disposition = "<span style='color:#f80'>не отвечен</span>";
                    break;
                case 'FAILED':
                    $disposition = "<span style='color:#f00; font-weight:bold'>СБОЙ</span>";
                    break;
                default:
                    $disposition = html_out($line['disposition']);
            }  
            $file = $file_dir . '/' . $line['uniqueid'] . '.' . $file_ext;
            
            if(file_exists($file)) {
                $file_cell = "<a onclick=\"playAudio('".$file_base_url.$line['uniqueid']."')\">слушать</a> - "
                    . "<a href='".$file_base_url.$line['uniqueid']."')\">загрузить</a>";
            } else {
                $file_cell = '';
            }
            $q_agent_cell = $line['q_agent'];
            if(strpos($line['q_agent'], 'SIP/')===0) {
                $num = substr($line['q_agent'], 4);
                $link = $this->getObjectLinkForPhone($num);
                if($link) {
                    $q_agent_cell .= ' / '.$link;
                }
            }
            if(strpos($line['q_agent'], 'Local/')===0) {
                $num = substr($line['q_agent'], 6);
                $link = $this->getObjectLinkForPhone($num);
                if($link) {
                    $q_agent_cell .= ' / '.$link;
                }
            }
            
            $tmpl->addContent("<tr>"               
                . "<td>".html_out($line['calldate'])."</td>"
                . "<td>".$direction."</td>"
                . "<td>".$cgroup."</td>"
                . "<td>".html_out($line['src'])."</td>"
                . "<td>".$src_cell."</td>"
                . "<td>".html_out($line['accountcode'])."</td>"
                . "<td>".html_out($line['dst'])."</td>"
                . "<td>".$dst_cell."</td>"                
                . "<td>".html_out($line['dcontext'])."</td>"
                . "<td style='text-align:right;'>".html_out($lenght)."</td>"
                . "<td>".$disposition."</td>"
                . "<td>".$queue_stat."</td>"
                . "<td>".$q_agent_cell."</td>"
                . "<td>".$file_cell."</td>"
                . "</tr>");
        }
        $lenght = sectostrinterval($duration);
        $tmpl->addContent("</table>"
            . "Итого: <b>$count</b> звонков общей продолжительностью <b>$lenght</b>"
            . "<script type='text/javascript' src='/js/audio.js'></script>"
            . "<div onclick='hideAudio();' id='audioBox'></div>"
            . ""
            . "");
    }
    
    protected function renderQueue() {
        global $tmpl, $db, $CONFIG;
        $filter = requestA(array('date_from', 'date_to', 'callid', 'queuename', 'agent', 'event'));
        if(!$filter['date_from']) {
            $filter['date_from'] = date("Y-m-d");
        }
        $event_options = "<option value=''>--не задано--</option>";
        $events = $this->getQueueEvents();
        foreach($events as $event) {
            $sel = $filter['event']==$event?' selected':'';
            if(isset($this->queue_events[$event])) {
                $event_options .= "<option value='$event'{$sel}>{$this->queue_events[$event]}</option>";
            } else {
                $event_options .= "<option value='$event'{$sel}>$event</option>";
            }
        }
        
        $tmpl->addBreadcrumb('Журнал очереди приёма вызовов', '');
        $tmpl->addContent("<form action='{$this->link_prefix}&amp;sect=queue' method='post'>"
                . "<table>"
                . "<tr><td>Дата от:</td><td><input type='text' name='date_from' id='date_from' value='{$filter['date_from']}'></td>"
                . "<td>Дата до:</td><td><input type='text' name='date_to' id='date_to' value='{$filter['date_to']}'></td>"
                . "<td>ID вызова</td><td><input type='text' name='callid' value='{$filter['callid']}'></td>"
                . "<td>Имя очереди</td><td><input type='text' name='queuename' value='{$filter['queuename']}'></td>"
                . "<td>Событие</td><td><select name='event'>$event_options</select></td></tr>"
                . "</table>"
                . "<button type='submit'>Отфильтровать</button>"
                . "<script type=\"text/javascript\">"
                . "initCalendar('date_from',false);"
                . "initCalendar('date_to',false);"
                . "</script>"
                . "</form>");
        $tmpl->addContent("<table class='list' width='100%'>"
            . "<tr><th>Дата</th><th>Очередь</th><th>Событие</th><th>Агент</th><th>1</th><th>2</th><th>3</th><th>4</th><th>5</th><th>ID</th></tr>");
        $data = $this->getQueue($filter);
        $this->ap = $this->getAgentPhonesInverse($db);
        $this->wp = $this->getWorkerPhonesInverse($db);
        $this->up = $this->getUserPhonesInverse($db);
        $agents_ldo = new \Models\LDO\agentnames();
        $this->agents = $agents_ldo->getData();
        $users_ldo = new \Models\LDO\usernames();
        $this->users = $users_ldo->getData();
        
        foreach ($data as $line_id=>$line) {
            $agent_cell = html_out($line['agent']);
            
            if(strpos($line['agent'], 'SIP/')===0) {
                $num = substr($line['agent'], 4);
                $link = $this->getObjectLinkForPhone($num);
                if($link) {
                    $agent_cell .= ' / '.$link;
                }
            }
                
            if(isset($this->queue_events[$line['event']])) {
                $event = $this->queue_events[$line['event']];
            } else {
                $event = $line['event'];
            }
            switch($line['event']) {
                case 'ABANDON':
                    $line['data1'] = "Конечная позиция: {$line['data1']}";
                    $line['data2'] = "Стартовая позиция: {$line['data2']}";
                    $line['data3'] = "Ждал: ".sectostrinterval(intval($line['data3']));
                    break;
                case 'COMPLETEAGENT':
                case 'COMPLETECALLER':
                    $line['data1'] = "Ждал: ".sectostrinterval(intval($line['data1']));
                    $line['data2'] = "Длительность звонка: ".sectostrinterval(intval($line['data2']));
                    $line['data3'] = "Стартовая позиция: {$line['data3']}";
                    break;
                case 'CONNECT':
                    $line['data1'] = "Ждал: ".sectostrinterval(intval($line['data1']));
                    $line['data2'] = "Канал: {$line['data2']}";
                    $line['data3'] = "Звонил: ".sectostrinterval(intval($line['data3']));
                    break;
                case 'ENTERQUEUE':
                    $line['data1'] = "URL: {$line['data1']}";
                    $link = $this->getObjectLinkForPhone($line['data2']);
                    if($link) {
                        $line['data2'] .= ' / '.$link;
                    }
                    $line['data2'] = "Номер: ".$line['data2'];
                    break;
                case 'RINGNOANSWER':
                    if($line['data1']>100) {
                        $line['data1'] = "Звонил: ".sectostrinterval(intval($line['data1']/1000)).' '.($line['data1']%1000).' мс.';
                    }
                    break;
                case 'TRANSFER':
                    $line['data1'] = "Цель: {$line['data1']}";
                    $line['data2'] = "Контекст: {$line['data2']}";
                    $line['data3'] = "Ждал: ".sectostrinterval(intval($line['data3']));
                    $line['data4'] = "Звонил: ".sectostrinterval(intval($line['data4']));
                    $line['data5'] = "Стартовая позиция: {$line['data5']}";
                    break;
            }
            
            $tmpl->addContent("<tr>"               
                . "<td>".html_out($line['time'])."</td>"
                . "<td>".html_out($line['queuename'])."</td>"
                . "<td>".html_out($event)."</td>"
                . "<td>".$agent_cell."</td>"                
                . "<td>".html_out($line['data1'])."</td>"
                . "<td>".$line['data2']."</td>"
                . "<td>".html_out($line['data3'])."</td>"
                . "<td>".html_out($line['data4'])."</td>"
                . "<td>".html_out($line['data5'])."</td>"
                . "<td>".html_out($line['callid'])."</td>"
                . "</tr>");           
        }
        
        $tmpl->addContent("</table>");
    }
    
    protected function renderSummary() {
        global $db, $tmpl;
        $filter = requestA(array('date_from', 'date_to', 'callid', 'queuename', 'agent', 'event'));
        if(!$filter['date_from']) {
            $filter['date_from'] = date("Y-m-01");
        }
        $data = $this->getQueue($filter);
        $this->ap = $this->getAgentPhonesInverse($db);
        $this->wp = $this->getWorkerPhonesInverse($db);
        $this->up = $this->getUserPhonesInverse($db);
        $agents_ldo = new \Models\LDO\agentnames();
        $this->agents = $agents_ldo->getData();
        $users_ldo = new \Models\LDO\usernames();
        $this->users = $users_ldo->getData();
        $events = $this->getQueueEvents();
        $queues = array();
        $abandoned = array();
        $noanswer = array();
        $numbers = array();
        foreach ($data as $event) {
            if(!isset($queues[$event['queuename']])) {
                $queues[$event['queuename']] = array('events'=>0);
                foreach($events as $e) {
                    $queues[$event['queuename']][$e] = 0;
                }
            }
            
            $queues[$event['queuename']]['events']++;
            switch($event['event']) {
                case 'ENTERQUEUE':
                    $numbers[$event['callid']] = $event['data2'];
                    $queues[$event['queuename']][$event['event']]++;
                    break;
                case 'ABANDON':
                    $queues[$event['queuename']][$event['event']]++;
                    $abandoned[$event['callid']] = array (
                        'callid' => $event['callid'],
                        'end' => $event['data1'],
                        'start' => $event['data2'],
                        'wait' => $event['data3'],
                        'time' => $event['time'],
                    );                    
                    break;
                
                case 'RINGNOANSWER':
                    if($event['data1']>100) {
                        $queues[$event['queuename']][$event['event']]++;
                        if(isset($noanswer[$event['agent']])) {
                            $noanswer[$event['agent']]++;
                        } else {
                            $noanswer[$event['agent']] = 1;
                        }
                    }
                    break;
                default:
                    $queues[$event['queuename']][$event['event']]++;                    
            }
        }
        $tmpl->addBreadcrumb('Сводка очередей вызовов', '');
        $tmpl->addContent("<form action='{$this->link_prefix}' method='post'>"
                . "<input type='hidden' name='sect' value='queue_summary'>"
                . "<table>"
                . "<tr><td>Дата от:</td><td><input type='text' name='date_from' id='date_from' value='{$filter['date_from']}'></td>"
                . "<td>Дата до:</td><td><input type='text' name='date_to' value='{$filter['date_to']}'></td>"
                . "<td>Имя очереди</td><td><input type='text' name='queuename' value='{$filter['queuename']}'></td>"
                . "</table>"
                . "<button type='submit'>Отфильтровать</button>"
                . "<script type=\"text/javascript\">"
                . "initCalendar('date_from',false);"
                . "initCalendar('date_to',false);"
                . "</script>"
                . "</form>");
        $tmpl->addContent("<table class='list' width='100%'>"
            . "<tr><th>Очередь</th><th>Входов</th><th>Брошенных</th><th>Соединений</th><th>Пропущено</th><th>Заверш.агентом</th>"
                . "<th>Заверш.вызывающим</th><th>Переводов</th><th>Событий</th></tr>");
        foreach($queues as $qname => $qdata) {
            $tmpl->addContent("<tr>"               
                . "<td>".html_out($qname)."</td>"
                . "<td>".html_out($qdata['ENTERQUEUE'])."</td>"
                . "<td>".html_out($qdata['ABANDON'])."</td>"
                . "<td>".html_out($qdata['CONNECT'])."</td>"
                . "<td>".html_out($qdata['RINGNOANSWER'])."</td>"
                . "<td>".html_out($qdata['COMPLETEAGENT'])."</td>"
                . "<td>".html_out($qdata['COMPLETECALLER'])."</td>"
                . "<td>".html_out($qdata['TRANSFER'])."</td>"
                . "<td>".html_out($qdata['events'])."</td>"
                . "</tr>");   
        }
        $tmpl->addContent("</table>");
        
        $tmpl->addContent("<h2>Брошенные звонки</h2><table class='list' width='100%'>"
            . "<tr><th>Id</th><th>Номер</th><th>Принадлежность</th><th>Ждал</th><th>Начал с</th><th>Бросил на</th><th>Время</th></tr>");
        foreach($abandoned as $data) {
            $object = $number = '';
            if(isset($numbers[$data['callid']])) {
                $number = $numbers[$data['callid']];
                $object = $this->getObjectLinkForPhone($number);
            }
            
            $tmpl->addContent("<tr>"               
                . "<td><a href='{$this->link_prefix}&amp;sect=queue&amp;callid=".html_out($data['callid'])."&amp;date_from=1970-01-01>".html_out($data['callid'])."</a></td>"
                . "<td>".html_out($number)."</td>"
                . "<td>".$object."</td>"
                . "<td>".html_out($data['wait'])."</td>"
                . "<td>".html_out($data['start'])."</td>"
                . "<td>".html_out($data['end'])."</td>"
                . "<td>".html_out($data['time'])."</td>"
                . "</tr>");   
        }
        $tmpl->addContent("</table>");
        
        $tmpl->addContent("<h2>Пропущенные звонки</h2><table class='list' width='100%'>"
            . "<tr><th>Агент</th><th>Принадлежность</th><th>Количество</th></tr>");
        foreach($noanswer as $agent=> $count) {
            $object = '';
            if(isset($numbers[$data['callid']])) {
                $object = $this->getObjectLinkForPhone($agent);
            }    
            $link = "{$this->link_prefix}&amp;sect=queue&amp;event=RINGNOANSWER&amp;agent=".html_out($agent).
                "&amp;date_from=".html_out($filter['date_from']).
                "&amp;date_to=".html_out($filter['date_to']);
            $tmpl->addContent("<tr>"               
                . "<td><a href='$link'>".html_out($agent)."</a></td>"
                . "<td>".$object."</td>"
                . "<td>".$count."</td>"
                . "</tr>");   
        }
        $tmpl->addContent("</table>");
    }
    
    public function run() {
        global $CONFIG, $tmpl, $db;
        $tmpl->addBreadcrumb($this->getName(), $this->link_prefix);
        $sect = request('sect');
        switch ($sect) {
            case '':
                $tmpl->addBreadcrumb($this->getName(), '');
                $tmpl->addContent("<p>".$this->getDescription()."</p>"
                    . "<ul>"
                    . "<li><a href='" . $this->link_prefix . "&amp;sect=cdr'>Записи детализации вызовов</li>"
                    . "<li><a href='" . $this->link_prefix . "&amp;sect=queue'>Журнал очередей вызовов</li>"
                    . "<li><a href='" . $this->link_prefix . "&amp;sect=queue_summary'>Сводка очередей вызовов</li>"
                    . "<li><a href='" . $this->link_prefix . "&amp;sect=context'>Редактор контекстов</li>"
                    . "</ul>");
                break;
            case 'queue':
                $this->renderQueue();
                break;
            case 'queue_summary':
                $this->renderSummary();
                break;
            case 'cdr':
                $this->renderCDR();
                break;
            case 'context':
                $editor = new \ListEditors\CdrContextEditor($db);
                $editor->line_var_name = 'id';
                $editor->link_prefix = $this->link_prefix.'&sect=context';
                $editor->acl_object_name = $this->acl_object_name;
                $editor->run();
                break;
            case 'audio':
                $callid = request('callid');
                if(!$callid) {
                    throw new \NotFoundException("Данные не найдены");
                }
                if(isset($CONFIG['service_cdr']['file_path'])) {
                    $file_dir = $CONFIG['service_cdr']['file_path'];
                } else {
                    $file_dir = '/var/spool/asterisk/monitor';
                }
                if(isset($CONFIG['service_cdr']['file_path'])) {
                    $file_ext= $CONFIG['service_cdr']['file_ext'];
                } else {
                    $file_ext = 'wav';
                }
                $file = $file_dir . '/' . $callid . '.' . $file_ext;
                $send = new \sendFile;
                $send->Path = $file;                
                $send->send();
                exit;
                break;
            default:
                throw new \NotFoundException("Секция не найдена");
        }
    }

}
