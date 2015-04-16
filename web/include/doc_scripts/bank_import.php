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


include_once($CONFIG['location'] . "/common/bank1c.php");

/// Сценарий автоматизации:  Импорт банковской выписки
class ds_bank_import {

    function getForm() {
        return "<h1>" . $this->getname() . "</h1>
            <form action='' method='post' enctype='multipart/form-data'>
            <input type='hidden' name='mode' value='load'>
            <input type='hidden' name='param' value='i'>
            <input type='hidden' name='sn' value='bank_import'>

            Файл банковской выписки:<br>
            <small>В формате 1с v 1.01</small><br>
            <input type='hidden' name='MAX_FILE_SIZE' value='10000000'><input name='userfile' type='file'><br>
            <label><input type='checkbox' name='process_in' value='1' checked>Обработать приходы</label><br>
            <label><input type='checkbox' name='process_out' value='1'>Обработать расходы</label><br>
            <label><input type='checkbox' name='apply' value='1'>Провести документы</label><br>
            <label><input type='checkbox' name='no_create_agents' value='1'>Не создавать агентов (может привести к дублированию ордеров)</label><br>
            Подтип документов:<br>
            <input type='text' name='subtype' maxlength='5'><br>
            <button type='submit'>Выполнить</button>
            </form>
            <p>Проверка принадлежности агента происходит по расчётному счёту.</p>";
    }
    
    // Список сечетов агентов
    function loadAgentsRsList() {
        global $db;
        $agents_rs = array();
        $res = $db->query("SELECT `id`, `rs` FROM `doc_agent`");
        while($line = $res->fetch_assoc()) {
            if($line['rs']) {
                $agents_rs[$line['rs']] = $line['id'];
            }
        }
        return $agents_rs;
    }
    
    function run($mode) {
        global $tmpl, $db;
        if ($mode == 'view') {
            $tmpl->addContent($this->getForm());
        } else if ($mode == 'load') {
            $process_in = rcvint('process_in');
            $process_out = rcvint('process_out');
            $subtype = request('subtype');
            $apply = request('apply');
            $no_create_agents = request('no_create_agents');
            
            $tmpl->addContent("<h1>" . $this->getname() . "</h1>");
            if ($_FILES['userfile']['size'] <= 0) {
                throw new \Exception("Забыли выбрать файл?");
            }
            $file = file($_FILES['userfile']['tmp_name']);
            $ex = new Bank1CExchange();
            $parsed_data = $ex->Parse($file);
            //var_dump($parsed_data);
            
            // Список наших фирм и счетов
            $banks = array();
            $res = $db->query("SELECT `num` AS `bank_id`, `firm_id`, `rs` FROM `doc_kassa` WHERE `ids`='bank'");
            while($line = $res->fetch_assoc()) {
                if($line['rs']) {
                    $banks[$line['rs']] = $line;
                }
            }
                        
            $agents_rs = $this->loadAgentsRsList();
            
            $tmpl->addContent("<table width='100%' class='list'>
                <tr>
                <th>ID</th><th>Тип</th><th>Номер П/П</th><th>Дата</th><th>Сумма</th><th>Счёт</th><th>Назначение</th><th>К заявке</th><th>Есть?</th>");
            $db->startTransaction();
            foreach ($parsed_data as $import_doc) {
                if(isset($banks[$import_doc['src']['rs']])) { // Исходящий
                    if(!$process_out) {
                        continue;
                    }
                    $agent_info = $import_doc['dst'];
                    $curr_rs = $import_doc['src']['rs'];
                    $doc_type = 5;
                    if(isset($import_doc['s_date'])) {
                        list($d, $m, $y) = explode('.', $import_doc['s_date'], 3);
                    } else {
                        list($d, $m, $y) = explode('.', $import_doc['date'], 3);
                    }
                } elseif ($process_in) {
                    $agent_info = $import_doc['src'];
                    $curr_rs = $import_doc['dst']['rs'];
                    $doc_type = 4;
                    if(isset($import_doc['p_date'])) {
                        list($d, $m, $y) = explode('.', $import_doc['p_date'], 3);
                    } else {
                        list($d, $m, $y) = explode('.', $import_doc['date'], 3);
                    }
                } else {
                    continue;
                }
                if(!isset($banks[$curr_rs])) {
                    throw new \Exception('В банковской выписке найден счёт собственной организации, которого нет в справочнике. Загрузка невозможна.');
                }
                
                $import_doc['docnum'] = intval($import_doc['docnum']);
                $sum = sprintf("%0.2f", $import_doc['sum']);                
                if(!checkdate($m, $d, $y)) {
                    throw new \Exception("Недопустимая дата в файле ($y-$m-$d)!");
                }
                $start_day_time = mktime(0, 0, 0, $m, $d, $y);
                $end_day_time = mktime(23, 59, 59, $m, $d, $y);
                $doc_time = mktime(12, 0, 0, $m, $d, $y);
                
                // Поиск документа в системе
                $agent_rs_sql = $db->real_escape_string($agent_info['rs']);
                $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`sum`, `doc_list`.`agent`
                    FROM `doc_list`
                    INNER JOIN `doc_agent` ON `doc_list`.`agent`=`doc_agent`.`id`
                    WHERE `doc_list`.`type`=$doc_type AND `doc_agent`.`rs`='$agent_rs_sql' AND `doc_list`.`altnum`='{$import_doc['docnum']}'"
                    . " AND `doc_list`.`date`>=$start_day_time AND `doc_list`.`date`<=$end_day_time");
                $doc_nums = '';
                $exist = 0;
                while($doc_info = $res->fetch_assoc()) {
                    $doc_nums .= "<a href='/doc.php?mode=body&amp;doc={$doc_info['id']}'>{$doc_info['id']}</a> ";
                    $exist = 1;
                }
                $order_id = '';
                if(!$doc_nums) {
                    // Определяем id агента
                    $agent_id = 1;
                    if(isset($agents_rs[$agent_info['rs']])) {
                        $agent_id = $agents_rs[$agent_info['rs']];
                    }
                    
                    // Определяем номер заявки покупателя    
                    if($agent_id>1) {
                        $res = $db->query("SELECT `id`, `agent` FROM `doc_list` WHERE `type`='3' AND `sum`='$sum' AND `agent`=$agent_id ORDER BY `id` DESC LIMIT 1");
                        while($doc_info = $res->fetch_assoc()) {
                            $order_id = $doc_info['id'];
                        }
                        if(!$order_id) {
                            $res = $db->query("SELECT `id`, `agent` FROM `doc_list` WHERE `type`='3' AND `agent`=$agent_id ORDER BY `id` DESC LIMIT 1");
                            while($doc_info = $res->fetch_assoc()) {
                                $order_id = $doc_info['id'];
                            }
                        } 
                    } else {
                        $res = $db->query("SELECT `id`, `agent` FROM `doc_list` WHERE `type`='3' AND `sum`='$sum' ORDER BY `id` DESC LIMIT 1");
                        while($doc_info = $res->fetch_assoc()) {
                            $order_id = $doc_info['id'];
                        }
                    }
                    
                    if($agent_id == 1 && !$no_create_agents) { //Автодобавление агента
                        $repl = array('ООО', 'ОАО', 'ЗАО', 'ИП', '\'', '"');
                        $agent_ins_data = array(
                            'name'      => trim(str_replace($repl, '', $agent_info['name'])).' (auto)',
                            'fullname'  => trim($agent_info['name']),
                            'inn'       => $agent_info['inn'],
                            'kpp'       => $agent_info['kpp'],
                            'rs'        => $agent_info['rs'],
                            'ks'        => $agent_info['ks'],
                            'bik'       => $agent_info['bik'],
                            'bank'       => $agent_info['bank_name'],
                            'responsible' => $_SESSION['uid'],
                            'comment'   => 'Автоматически созданный агент'
                        );
                        $agent_id = $db->insertA('doc_agent', $agent_ins_data);
                        $agents_rs[$agent_info['rs']] = $agent_id;
                    }
                    
                    if($agent_id == 1) { // Добавим название агента в комментарий
                        $import_doc['desc'] .= " - ".$agent_info['name'];
                    }

                    if(!$order_id) {
                        $order_id = 'null';
                    }
                    $doc_ins_data = array(
                        'type'      => $doc_type,
                        'date'      => $doc_time, // !!!!!!
                        'altnum'    => intval($import_doc['docnum']),
                        'subtype'   => $subtype,
                        'sum'       => $sum,
                        'bank'      => $banks[$curr_rs]['bank_id'],
                        'agent'     => $agent_id,
                        'firm_id'   => $banks[$curr_rs]['firm_id'],
                        'comment'   => $import_doc['desc'],
                        'ok'        => $apply ? time() : 0,
                        'user'      => $_SESSION['uid'],
                        'p_doc'     => $order_id
                    );
                    $doc_id = $db->insertA('doc_list', $doc_ins_data);
                    $doc_nums = $doc_nums .= "<a href='/doc.php?mode=body&amp;doc=$doc_id'>$doc_id</a>";
                }
                
                if($exist) {
                    $exist = 'Да';
                } else {
                    $exist = 'Нет';
                }
                
                if($doc_type==4) {
                    $print_type = 'Приход';
                } else {
                    $print_type = 'Расход';
                }
                if($order_id) {
                    $order_id = "<a href='/doc.php?mode=body&amp;doc=$order_id'>$order_id</a>";
                }
                $tmpl->addContent("<tr><td>$doc_nums</td><td>$print_type</td><td>{$import_doc['docnum']}</td><td>".date("Y-m-d", $start_day_time)."</td>
                    <td>$sum</td><td>".html_out($agent_info['rs'])."</td><td>".html_out($import_doc['desc'])."</td><td>$exist</td><td>$exist</td></tr>");
            }
            $tmpl->addContent("</table>");
            $db->commit();
        }
    }

    function getName() {
        return "Импорт банковских документов";
    }

}
