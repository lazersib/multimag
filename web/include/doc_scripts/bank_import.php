<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2018, BlackLight, TND Team, http://tndproject.org
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

    protected $agent_rs;    //< Расчётные счета и связанные агенты
    protected $agent_inns;  //< ИНН и связанные агенты
    
    public function getName() {
        return "Импорт банковских документов";
    }
    
    /// Список счетов агентов
    protected function loadAgentsData() {
        global $db;
        $this->agent_rs = array();
        $this->agent_inns = array();
        /// Сначала из основного списка
        $res = $db->query("SELECT `id`, `inn`, `rs` FROM `doc_agent`");
        while($line = $res->fetch_assoc()) {
            if($line['rs']) {
                $this->agent_rs[$line['rs']] = $line['id'];
            }
            if($line['inn']) {
                $this->agent_inns[$line['inn']] = $line['id'];
            }
        }
        /// Тпеперь из реквизитов
        $res = $db->query("SELECT `agent_id`, `rs` FROM `agent_banks`");
        while($line = $res->fetch_assoc()) {
            if($line['rs']) {
                $this->agent_rs[$line['rs']] = $line['agent_id'];
            }
        }
    }
    
    /// Получить список наших банков
    protected function getMyBanksData() {
        global $db;
        $banks = [];
        $res = $db->query("SELECT `num` AS `bank_id`, `firm_id`, `rs` FROM `doc_kassa` WHERE `ids`='bank'");
        while($line = $res->fetch_assoc()) {
            if($line['rs']) {
                $banks[$line['rs']] = $line;
            }
        }
        return $banks;
    }
    
    // Добавление записи в банковские реквизиты агента
    protected function agent_create_br($agent_id, $agent_info) {
        global $db;
        $agent_bank_data = array(
            'agent_id'  => $agent_id,
            'rs'        => $agent_info['rs'],
            'ks'        => $agent_info['ks'],
            'bik'       => $agent_info['bik'], 
            'name'      => $agent_info['bank_name'],
        );
        return $db->insertA('agent_banks', $agent_bank_data);
    }

    /// Получить ID документа-основания для приходника
    protected function getBaseForIn($agent_id, $doc_sum) {
        global $db;
        $doc_id = null;
        if($agent_id!==null) {
            settype($agent_id, 'int');
        }
        $doc_sum = round($doc_sum, 2);
        if($agent_id>1) {   
            // Ищем реализацию
            $res = $db->query("SELECT `id`, `agent` FROM `doc_list` WHERE `type`=2 AND `sum`='$doc_sum' AND `agent`=$agent_id ORDER BY `id` DESC LIMIT 1");
            while($doc_info = $res->fetch_assoc()) {
                $doc_id = $doc_info['id'];
            }
            
            if(!$doc_id) {
                // Ищем заявку
                $res = $db->query("SELECT `id`, `agent` FROM `doc_list` WHERE `type`=3 AND `sum`='$doc_sum' AND `agent`=$agent_id ORDER BY `id` DESC LIMIT 1");
                while($doc_info = $res->fetch_assoc()) {
                    $doc_id = $doc_info['id'];
                }
            }
            if(!$doc_id) {
                // Ищем документ с подходящей суммой
                $res = $db->query("SELECT `id`, `agent` FROM `doc_list` WHERE (`type`=2 OR `type`=3) AND `agent`=$agent_id ORDER BY `id` DESC LIMIT 1");
                while($doc_info = $res->fetch_assoc()) {
                    $doc_id = $doc_info['id'];
                }
            } 
        } else {
            $res = $db->query("SELECT `id`, `agent` FROM `doc_list` WHERE (`type`=2 OR `type`=3) AND `sum`='$doc_sum' ORDER BY `id` DESC LIMIT 1");
            while($doc_info = $res->fetch_assoc()) {
                $doc_id = $doc_info['id'];
            }
        }
        return $doc_id;
    }
    
        /// Получить ID документа-основания для расходника
    protected function getBaseForOut($agent_id, $doc_sum) {
        global $db;
        $doc_id = null;
        if($agent_id!==null) {
            settype($agent_id, 'int');
        }
        $doc_sum = round($doc_sum, 2);
        if($agent_id>1) {   
            // Ищем поступление
            $res = $db->query("SELECT `id`, `agent` FROM `doc_list` WHERE `type`=1 AND `sum`='$doc_sum' AND `agent`=$agent_id ORDER BY `id` DESC LIMIT 1");
            while($doc_info = $res->fetch_assoc()) {
                $doc_id = $doc_info['id'];
            }            
            if(!$doc_id) {
                // Ищем товар в пути
                $res = $db->query("SELECT `id`, `agent` FROM `doc_list` WHERE `type`=12 AND `sum`='$doc_sum' AND `agent`=$agent_id ORDER BY `id` DESC LIMIT 1");
                while($doc_info = $res->fetch_assoc()) {
                    $doc_id = $doc_info['id'];
                }
            }
            if(!$doc_id) {
                // Ищем документ с подходящей суммой
                $res = $db->query("SELECT `id`, `agent` FROM `doc_list` WHERE (`type`=1 OR `type`=12) AND `agent`=$agent_id ORDER BY `id` DESC LIMIT 1");
                while($doc_info = $res->fetch_assoc()) {
                    $doc_id = $doc_info['id'];
                }
            } 
        } else {
            $res = $db->query("SELECT `id`, `agent` FROM `doc_list` WHERE (`type`=1 OR `type`=12) AND `sum`='$doc_sum' ORDER BY `id` DESC LIMIT 1");
            while($doc_info = $res->fetch_assoc()) {
                $doc_id = $doc_info['id'];
            }
        }
        return $doc_id;
    }
    
    protected function processData($data, $options) {
        global $db;
        $result = [];
        $banks = $this->getMyBanksData();
        $this->loadAgentsData();

        $db->startTransaction();
        foreach ($data as $import_doc) {
            if(isset($banks[$import_doc['src']['rs']])) { // Исходящий
                if(!$options['process_out']) {
                    continue;
                }
                $agent_info = $import_doc['dst'];
                $curr_rs = $import_doc['src']['rs'];
                $doc_type = 5;
                if(isset($import_doc['s_date']) && $import_doc['s_date']) {
                    list($d, $m, $y) = explode('.', $import_doc['s_date'], 3);
                } else {
                    list($d, $m, $y) = explode('.', $import_doc['date'], 3);
                }
            } elseif ($options['process_in']) {
                $agent_info = $import_doc['src'];
                $curr_rs = $import_doc['dst']['rs'];
                $doc_type = 4;
                if(isset($import_doc['p_date']) && $import_doc['p_date']) {
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
                throw new \Exception("Недопустимая дата в файле ($y-$m-$d), документ {$import_doc['docnum']}!");
            }
            $start_day_time = mktime(0, 0, 0, $m, $d, $y);
            $end_day_time = mktime(23, 59, 59, $m, $d, $y);
            $doc_time = mktime(12, 0, 0, $m, $d, $y);

            // Поиск агентов с таким расчётным счётом
            $agent_rs_sql = $db->real_escape_string($agent_info['rs']);
            $res = $db->query("SELECT `agent_id` FROM `agent_banks` WHERE `rs`='$agent_rs_sql'");
            $agents_line = '';
            while($line = $res->fetch_assoc()) {
                if($agents_line) {
                    $agents_line .=',';
                }
                $agents_line .= $line['agent_id'];
            }

            if($agents_line) {
                $agents_line = " OR `doc_list`.`agent` IN ($agents_line)";
            }

            $base_id = null;
            $bank_doc_num = null;
            $exist = false;
            
            // Поиск банковского документа в системе
            $agent_inn_sql = $db->real_escape_string($agent_info['inn']);
            $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`sum`, `doc_list`.`p_doc`
                    , `doc_list`.`agent`
                FROM `doc_list`
                INNER JOIN `doc_agent` ON `doc_list`.`agent`=`doc_agent`.`id`
                WHERE `doc_list`.`type`=$doc_type AND (`doc_agent`.`inn`='$agent_inn_sql' $agents_line)"
                . " AND `doc_list`.`altnum`='{$import_doc['docnum']}'"
                . " AND `doc_list`.`date`>=$start_day_time AND `doc_list`.`date`<=$end_day_time"); 
            while($doc_info = $res->fetch_assoc()) {
                $bank_doc_num = $doc_info['id'];
                $base_id = $doc_info['p_doc'];
                $exist = true;
            }            
            if(!$exist) {
                // Определяем id агента
                $agent_id = 1;
                if(isset($this->agent_rs[$agent_info['rs']])) {
                    $agent_id = $this->agent_rs[$agent_info['rs']];
                }
                if($agent_id==1) {
                    if(isset($this->agent_inns[$agent_info['inn']])) {
                        $agent_id = $this->agent_inns[$agent_info['inn']];
                    } 
                }
                
                // Определяем номер документа-основания
                if($doc_type == 4 && !$options['no_connect_in']) {
                    $base_id = $this->getBaseForIn($agent_id, $sum);
                }
                if($doc_type == 5 && !$options['no_connect_out']) {
                    $base_id = $this->getBaseForOut($agent_id, $sum);
                }

                // Устанавливаем банковские реквизиты агента
                if($agent_id>1) {  
                    $agent_ks_sql = $db->real_escape_string($agent_info['ks']);
                    $agent_bik_sql = $db->real_escape_string($agent_info['bik']);
                    $res = $db->query("SELECT * FROM `agent_banks` WHERE `agent_id`='$agent_id' AND `bik`='$agent_bik_sql'"
                        . " AND `ks`='$agent_ks_sql' AND `rs`='$agent_rs_sql'");
                    if($res->num_rows ==0 && !$options['no_create_br']) {
                        $agent_bank_id = $this->agent_create_br($agent_id, $agent_info);
                    }
                }

                if($agent_id == 1 && !$options['no_create_agents']) { //Автодобавление агента
                    $repl = array('ООО', 'ОАО', 'ЗАО', 'ПАО', 'ИП', '\'', '"');
                    $agent_ins_data = array(
                        'name'      => trim(str_replace($repl, '', $agent_info['name'])).' (auto)',
                        'fullname'  => trim($agent_info['name']),
                        'inn'       => $agent_info['inn'],
                        'kpp'       => $agent_info['kpp'],
                        'responsible' => $_SESSION['uid'],
                        'comment'   => 'Автоматически созданный агент'
                    );
                    $agent_id = $db->insertA('doc_agent', $agent_ins_data);
                    $agent_bank_id = $this->agent_create_br($agent_id, $agent_info);
                    $this->agent_rs[$agent_info['rs']] = $agent_id;
                    $this->agent_inns[$agent_info['inn']] = $agent_id;
                }

                if($agent_id == 1) { // Добавим название агента в комментарий
                    $import_doc['desc'] .= " - ".$agent_info['name'];
                }

                $doc_ins_data = array(
                    'type'      => $doc_type,
                    'date'      => $doc_time, // !!!!!!
                    'altnum'    => intval($import_doc['docnum']),
                    'subtype'   => $options['subtype'],
                    'sum'       => $sum,
                    'bank'      => $banks[$curr_rs]['bank_id'],
                    'agent'     => $agent_id,
                    'firm_id'   => $banks[$curr_rs]['firm_id'],
                    'comment'   => $import_doc['desc'],
                    'ok'        => $options['apply'] ? time() : 0,
                    'user'      => $_SESSION['uid'],
                    'p_doc'     => $base_id
                );
                $bank_doc_num = $db->insertA('doc_list', $doc_ins_data);
            }

            $result[] = [
                'id' => $bank_doc_num,
                'pay_type' => $doc_type,
                'pay_num' => $import_doc['docnum'],
                'pay_date' => date("Y-m-d", $start_day_time),
                'pay_sum' => $sum,
                'agent_rs' => $agent_info['rs'],
                'base_id' => $base_id,
                'exist' => $exist,
                'desc' => $import_doc['desc']                
            ];
        }
        $db->commit();
        return $result;
    }
    
    protected function getForm() {
        $max_fs = \webcore::getMaxUploadFileSize();
        $max_fs_size = \webcore::toStrDataSizeInaccurate($max_fs);
        return "<h1>" . $this->getname() . "</h1>
            <form action='' method='post' enctype='multipart/form-data'>
            <input type='hidden' name='mode' value='load'>
            <input type='hidden' name='param' value='i'>
            <input type='hidden' name='sn' value='bank_import'>

            Файл банковской выписки:<br>
            <small>В формате 1с v 1.01, $max_fs_size максимум.</small><br>
            <input type='hidden' name='MAX_FILE_SIZE' value='$max_fs'><input name='userfile' type='file'><br>
            <label><input type='checkbox' name='process_in' value='1' checked>Обработать приходы</label><br>
            <label><input type='checkbox' name='process_out' value='1'>Обработать расходы</label><br>
            <label><input type='checkbox' name='no_connect_in' value='1'>Не привязывать приходы</label><br>
            <label><input type='checkbox' name='no_connect_out' value='1'>Не привязывать расходы</label><br>
            <label><input type='checkbox' name='apply' value='1'>Провести документы</label><br>
            <label><input type='checkbox' name='no_create_agents' value='1'>Не создавать агентов (может привести к дублированию ордеров)</label><br>
            <label><input type='checkbox' name='no_create_br' value='1'>Не добавлять существующим агентам новые банковские реквизиты</label><br>            
            Подтип документов:<br>
            <input type='text' name='subtype' maxlength='5'><br>
            <button type='submit'>Выполнить</button>
            </form>
            <p>Проверка принадлежности агента происходит по расчётному счёту.</p>";
    }

    /// Исполнение сценария
    public function run($mode) {
        global $tmpl, $db;
        if ($mode == 'view') {
            $tmpl->addContent($this->getForm());
        } else if ($mode == 'load') {
            $options = [
                'process_in' => rcvint('process_in'),
                'process_out' => rcvint('process_out'),
                'no_connect_in' => rcvint('no_connect_in'),
                'no_connect_out' => rcvint('no_connect_out'),
                'subtype' => request('subtype'),
                'apply' => request('apply'),
                'no_create_agents' => request('no_create_agents'),
                'no_create_br' => request('no_create_br'),
            ];            
            
            $tmpl->addContent("<h1>" . $this->getname() . "</h1>");
            if ($_FILES['userfile']['size'] <= 0) {
                throw new \Exception("Забыли выбрать файл?");
            }
            $file = file($_FILES['userfile']['tmp_name']);
            $ex = new Bank1CExchange();
            $parsed_data = $ex->Parse($file);            
            $processed = $this->processData($parsed_data, $options);
            
            $table_header = ['ID', 'Тип', 'Номер П/П', 'Дата', 'Сумма', 'Счёт', 'Назначение', 'К документу', 'Есть'];
            $table_body = [];
            foreach($processed as $p) {
                $table_body[] = [
                    "<a href='/doc.php?mode=body&amp;doc={$p['id']}'>{$p['id']}</a>",
                    $p['pay_type']==4 ? 'Приход':'Расход',
                    html_out($p['pay_num']),
                    $p['pay_date'],
                    html_out($p['pay_sum']),
                    html_out($p['agent_rs']),
                    html_out($p['desc']),
                    $p['base_id'] ? "<a href='/doc.php?mode=body&amp;doc={$p['base_id']}'>{$p['base_id']}</a>":'',
                    $p['exist'] ? 'Да':'Нет',                
                ];
            }            
            $tmpl->addContent( \widgets::getTable($table_header, $table_body) );
        }
    }
}
