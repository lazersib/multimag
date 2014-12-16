<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2014, BlackLight, TND Team, http://tndproject.org
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
            Подтип документов:<br>
            <input type='text' name='subtype' maxlength='5'><br>
            <button type='submit'>Выполнить</button>
            </form>"; //При выполнении сценария отсутствующие агенты будут добавлены автоамтически!<br>
    }
    
    function run($mode) {
        global $tmpl, $db;
        if ($mode == 'view') {
            $tmpl->addContent($this->getForm());
        } else if ($mode == 'load') {
            $process_in = rcvint('process_in');
            $process_out = rcvint('process_out');
            $subtype = request('subtype');
            
            $tmpl->addContent("<h1>" . $this->getname() . "</h1>");
            if ($_FILES['userfile']['size'] <= 0) {
                throw new Exception("Забыли выбрать файл?");
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
            
            // Список агентов
            $agents_rs = array();
            $res = $db->query("SELECT `id`, `rs` FROM `doc_agent`");
            while($line = $res->fetch_assoc()) {
                if($line['rs']) {
                    $agents_rs[$line['rs']] = $line['id'];
                }
            }
            
            $tmpl->addContent("<table width='100%' class='list'>
                <tr>
                <th>ID</th><th>Тип</th><th>Номер П/П</th><th>Дата</th><th>Сумма</th><th>Счёт</th><th>Назначение</th><th>Есть?</th>");
            $db->startTransaction();
            foreach ($parsed_data as $import_doc) {
                if(isset($banks[$import_doc['src']['rs']])) { // Исходящий
                    if(!$process_out) {
                        continue;
                    }
                    $agent_info = $import_doc['dst'];
                    $curr_rs = $import_doc['src']['rs'];
                    $doc_type = 5;
                } elseif ($process_in) {
                    $agent_info = $import_doc['src'];
                    $curr_rs = $import_doc['dst']['rs'];
                    $doc_type = 4;
                } else {
                    continue;
                }
                
                $import_doc['docnum'] = intval($import_doc['docnum']);
                $sum = sprintf("%0.2f", $import_doc['sum']);
                list($d, $m, $y) = explode('.', $import_doc['date'], 3);
                if(!checkdate($m, $d, $y)) {
                    throw new Exception("Недопустимая дата в файле ($y-$m-$d)!");
                }
                $start_day_time = mktime(0, 0, 0, $m, $d, $y);
                $end_day_time = mktime(23, 59, 59, $m, $d, $y);
                $doc_time = mktime(12, 0, 0, $m, $d, $y);
                
                // Поиск документа в системе
                $agent_rs_sql = $db->real_escape_string($agent_info['rs']);
                $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`sum`, `doc_list`.`agent`
                    FROM `doc_list`
                    INNER JOIN `doc_agent` ON `doc_list`.`agent`=`doc_agent`.`id`
                    WHERE `doc_list`.`type`=$doc_type AND `doc_agent`.`rs`='$agent_rs_sql' AND `doc_list`.`date`>=$start_day_time AND `doc_list`.`date`<=$end_day_time");
                $doc_nums = '';
                $exist = 0;
                while($doc_info = $res->fetch_assoc()) {
                    $doc_nums .= "<a href='/doc.php?mode=body&amp;doc={$doc_info['id']}'>{$doc_info['id']}</a> ";
                    $exist = 1;
                }
                
                if(!$doc_nums) {
                    // Определяем id агента
                    $agent_id = 1;
                    if(isset($agents_rs[$agent_info['rs']])) {
                        $agent_id = $agents_rs[$agent_info['rs']];
                    }
                    
                    // Определяем номер заявки покупателя
                    $order_id = 0;
                    
                    if($agent_id>1) {
                        $res = $db->query("SELECT `id`, `agent` FROM `doc_list` WHERE `type`='3' AND `sum`='$sum' AND `agent`=$agent_id ORDER BY `id` DESC LIMIT 1");
                        while($doc_info = $res->fetch_assoc()) {
                            $order_id = $doc_info['id'];
                        }
                    } else {
                        $res = $db->query("SELECT `id`, `agent` FROM `doc_list` WHERE `type`='3' AND `sum`='$sum' ORDER BY `id` DESC LIMIT 1");
                        while($doc_info = $res->fetch_assoc()) {
                            $order_id = $doc_info['id'];
                        }
                    }
                    
                    if($agent_id == 1) { //Автодобавление агента
                        $repl = array('ООО', 'ОАО', 'ЗАО', 'ИП', '\'', '"');
                        $agent_ins_data = array(
                            'name'      => trim(str_replace($repl, '', $agent_info['name'])),
                            'fullname'  => trim($agent_info['name']),
                            'inn'       => $agent_info['inn'],
                            'kpp'       => $agent_info['kpp'],
                            'rs'        => $agent_info['rs'],
                            'ks'        => $agent_info['ks'],
                            'bik'       => $agent_info['bik'],
                            'comment'   => 'Автоматически созданный агент'
                        );
                        $agent_id = $db->insertA('doc_agent', $agent_ins_data);
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
                        'ok'        => time()
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
                
                $tmpl->addContent("<tr><td>$doc_nums</td><td>$print_type</td><td>{$import_doc['docnum']}</td><td>".date("Y-m-d", $start_day_time)."</td>
                    <td>$sum</td><td>".html_out($agent_info['rs'])."</td><td>".html_out($import_doc['desc'])."</td><td>$exist</td></tr>");
            }
            $tmpl->addContent("</table>");
            $db->commit();
        }
    }

    function getName() {
        return "Импорт банковских документов";
    }

}
