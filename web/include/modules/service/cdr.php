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

namespace modules\service;

/// Настройка почтовых ящиков и алиасов
class CDR extends \IModule {
    
    public function __construct() {
        parent::__construct();
        $this->acl_object_name = 'service_cdr';
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
        } elseif(!$phoneplus && $CONFIG['cdr']['local_length']==$len) {
            return $CONFIG['cdr']['local_perfix'].$phone;
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
                }
            }
        }
        $res = $db->query("SELECT SQL_CALC_FOUND_ROWS `asterisk_cdr`.`id`, `calldate`, `clid`, `src`, `dst`, `dcontext`, `lastapp`, `lastdata`"
                . ", `duration`, `billsec`, `disposition`, `uniqueid`, `ex_queue`.`event` AS `q_event`"
            . " FROM `asterisk_cdr`"
            . " LEFT JOIN `asterisk_queue_log` AS `ex_queue` ON `ex_queue`.`callid`=`asterisk_cdr`.`uniqueid`"
            . " AND (`ex_queue`.`event`='ABANDON' OR `ex_queue`.`event`='COMPLETECALLER' OR `ex_queue`.`event`='COMPLETEAGENT')"
            . $where_sql
            . " ORDER BY `calldate` DESC"
            . " LIMIT 500");
        while ($line = $res->fetch_assoc()) {
            $data[] = $line;
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
        $res = $db->query("SELECT `user_id`, `worker_phone` FROM `users_worker_info` WHERE `worker_phone`!='' ORDER BY `worker`");
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
            return "<a href='/adm_users.php?mode=view&id=".$obj_id."'>".html_out($src_cell)."</a>";
        } elseif(isset($this->up[$phone])) {
            $obj_id = $this->up[$phone];
            if(isset($this->users[$obj_id])) {
                $src_cell = $this->users[$obj_id];
            } else {
                $src_cell = '?';
            }
            return "<a href='/adm_users.php?mode=view&id=".$obj_id."'>".html_out($src_cell)."</a>";
        }
        return '';
    }

    protected function renderCDR() {
        global $tmpl, $db, $CONFIG;
        $filter = requestA(array('date_from', 'date_to', 'src', 'dst', 'context'));
                
        $tmpl->addBreadcrumb('Детализация вызовов', '');
        $tmpl->addContent("<form action='{$this->link_prefix}&amp;sect=cdr' method='post'>"
                . "<table>"
                . "<tr><td>Дата от:</td><td><input type='text' name='date_from' value='{$filter['date_from']}'></td>"
                . "<td>Дата до:</td><td><input type='text' name='date_to' value='{$filter['date_to']}'></td>"
                . "<td>Номер-источник</td><td><input type='text' name='src' value='{$filter['src']}'></td>"
                . "<td>Номер-цель</td><td><input type='text' name='dst' value='{$filter['dst']}'></td></tr>"
                . "</table>"
                . "<button type='submit'>Отфильтровать</button>"
                . "</form>");
        $tmpl->addContent("<table class='list' width='100%'>"
            . "<tr><th rowspan='2'>Дата</th><th colspan='2'>От</th><th colspan='2'>На</th><th rowspan='2'>Контекст</th>"
            . "<th rowspan='2'>Длительность</th><th rowspan='2'>Статус</th><th rowspan='2'>Статус очереди</th><th rowspan='2'>ID</th><th rowspan='2'>Файл</th></tr>"
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
                
        foreach ($data as $line_id=>$line) {
            $src_cell = $dst_cell = $queue_stat = '';
            $src_cell = $this->getObjectLinkForPhone($line['src']);
            $dst_cell = $this->getObjectLinkForPhone($line['dst']);
            
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
            
            $tmpl->addContent("<tr>"               
                . "<td>".html_out($line['calldate'])."</td>"
                . "<td>".html_out($line['src'])."</td>"
                . "<td>".$src_cell."</td>"
                . "<td>".html_out($line['dst'])."</td>"
                . "<td>".$dst_cell."</td>"
                . "<td>".html_out($line['dcontext'])."</td>"
                . "<td style='text-align:right;'>".html_out($lenght)."</td>"
                . "<td>".$disposition."</td>"
                . "<td>".$queue_stat."</td>"
                . "<td>".html_out($line['uniqueid'])."</td>"
                . "<td>".$file_cell."</td>"
                . "</tr>");
        }
        
        $tmpl->addContent("</table>"
            . "<script type='text/javascript' src='/js/audio.js'></script>"
            . "<div onclick='hideAudio();' id='audioBox'></div>"
            . ""
            . "");
    }
    
    protected function renderSummary() {
        global $tmpl, $db;
        $tmpl->addBreadcrumb('Карта почтовых алиасов', '');
        $tmpl->addContent("<table class='list'>"
            . "<tr><th>Алиас</th><th>Ящик</th>");
        $map = $this->calcAMap($db);
        foreach ($map as $email=>$users) {
            $span = count($users);
            $tmpl->addContent("<tr><td rowspan='$span'>".html_out($email)."</td>");
            $fl = 1;
            foreach($users as $user) {
                if(!$fl) {
                    $tmpl->addContent("<tr>");
                    $fl = 0;
                }
                $tmpl->addContent("<td>".html_out($user)."</td></tr>");
            }
        }
        
        $tmpl->addContent("</table>");
    }
    
    public function run() {
        global $CONFIG, $tmpl, $db;
        /* if (!isset($CONFIG['service_cdr'])) {
            throw new \Exception("Модуль не настроен!");
        }
        if (!is_array($CONFIG['service_cdr'])) {
            throw new \Exception("Неверные настройки модуля!");
        }
        $conf = $CONFIG['service_cdr'];
        $db = new \MysqiExtended($conf['db_host'], $conf['db_login'], $conf['db_pass'], $conf['db_name']);
        if ($db->connect_error) {
            throw new Exception("Не удалось соединиться с базой данных телефонной статистики");
        }
         * 
         */
        $tmpl->addBreadcrumb($this->getName(), $this->link_prefix);
        $sect = request('sect');
        switch ($sect) {
            case '':
                $tmpl->addBreadcrumb($this->getName(), '');
                $tmpl->addContent("<p>".$this->getDescription()."</p>"
                    . "<ul>"
                    . "<li><a href='" . $this->link_prefix . "&amp;sect=queue_summary'>Сводка очередей вызовов</li>"
                    . "<li><a href='" . $this->link_prefix . "&amp;sect=cdr'>Записи детализации вызовов</li>"
                    . "</ul>");
                break;
            case 'queue_summary':
                $this->renderSummary();
                break;
            case 'cdr':
                $this->renderCDR();
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
