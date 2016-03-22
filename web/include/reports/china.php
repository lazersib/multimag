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

/// Китайский отчёт
class Report_China extends BaseGSReport {

    var $sklad = 0; // ID склада
    var $w_docs = 0; // Отображать документы
    var $div_dt = 0; // Разделить приходы и расходы

    function getName($short = 0) {
        if ($short) {
            return "Китайский";
        } else {
            return "Китайский отчёт";
        }
    }

    function Form() {
        global $tmpl, $db;
        $d_t = date("Y-m-d");
        $d_f = date("Y-m-d", time() - 60 * 60 * 24 * 31);
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
            <script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
            <form action='' method='post'>
            <input type='hidden' name='mode' value='china'>
            <fieldset><legend>Дата</legend>
            С:<input type=text id='dt_f' name='dt_f' value='$d_f'><br>
            По:<input type=text id='dt_t' name='dt_t' value='$d_t'>
            </fieldset>
            <fieldset><legend>Склад:</legend>
            <select name='sklad'>");
        $res = $db->query("SELECT `id`, `name` FROM `doc_sklady`");
        while ($nxt = $res->fetch_row()) {
            $tmpl->addContent("<option value='$nxt[0]'>" . html_out($nxt[1]) . "</option>");
        }
        $tmpl->addContent("</select></fieldset><br>
            <fieldset><legend>Ограничения по величине продаж</legend>
            не менее:<br>
            <input type='text' name='pc_min' value='0'><br>
            не более:<br>
            <input type='text' name='pc_max' value='100'><br>
            </fieldset>
            <fieldset><legend>Сортировать по:</legend>
            <select name='order'>
            <option value='vc_up'>Коду товара: по возрастанию</option>
            <option value='vc_down'>Коду товара: по убыванию</option>
            <option value='s_up'>По величине продаж: по возрастанию</option>
            <option value='s_down'>По величине продаж: по убыванию</option>
            <option value='pc_up'>По кодовому названию поставщика: по возрастанию</option>
            <option value='pc_down'>По кодовому названию поставщика: по убыванию</option>
            </select></fieldset>");
        $this->GroupSelBlock();
        $tmpl->addContent("            
            <div id='pos_sel' style='display: none;'>
            <input type='hidden' name='pos_id' id='pos_id' value=''>
            <input type='text' id='posit' style='width: 400px;' value=''>
            </div>
            <div id='agent_sel' style='display: none;'>
            <input type='hidden' name='agent' id='agent_id' value=''>
            <input type='text' id='ag' name='agent_name' style='width: 400px;' value=''><br>
            </div>

            
            Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
            <button type='submit'>Сформировать отчёт</button>
            </form>

            <script type=\"text/javascript\">
            function dtinit(){initCalendar('dt_f',false);initCalendar('dt_t',false);}

            addEventListener('load',dtinit,false);	            

            </script>
            ");
    }

    function getPosCounters($pos_info) {        
        global $db;
        $dt_f = $this->dt_f;
        $dt_t = $this->dt_t;
        $ret_data = array(
            'last_in_cnt' => 0,
            'out_cnt' => 0,
            'out_sum' => 0,
            'outmove_cnt' => 0,
        );
        $ret_data['start_cnt'] = getStoreCntOnDate($pos_info['id'], $this->sklad, $dt_f, 1);
        $ret_data['end_cnt'] = getStoreCntOnDate($pos_info['id'], $this->sklad, $dt_t, 1);
        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list_pos`.`cnt`, `doc_list`.`date`
            FROM `doc_list_pos`
            INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
            WHERE `doc_list_pos`.`tovar`='{$pos_info['id']}' AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<'$dt_t' AND (
            (`doc_list`.`type`='1' AND `doc_list`.`sklad`='{$this->sklad}') OR
            (`doc_list`.`type`='17' AND `doc_list`.`sklad`='{$this->sklad}' AND `doc_list_pos`.`page`='0') ) AND `doc_list`.`ok`>0
            ORDER BY `doc_list`.`date` DESC LIMIT 1");
        while ($line = $res->fetch_assoc()) {
            $ret_data['last_in_cnt'] = $line['cnt'];
            $ret_data['last_in_date'] = date("Y-m-d", $line['date']);
        }

        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`
            FROM `doc_list_pos`
            INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
            WHERE `doc_list_pos`.`tovar`='{$pos_info['id']}' AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<'$dt_t' AND `doc_list`.`sklad`='{$this->sklad}' AND (`doc_list`.`type`='2' OR `doc_list`.`type`='20') AND `doc_list`.`ok`>0
            ORDER BY `doc_list`.`date`");
        while ($line = $res->fetch_assoc()) {
            $ret_data['out_cnt'] += $line['cnt'];
            $ret_data['out_sum'] += $line['cnt']*$line['cost'];
        }
        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list_pos`.`cnt`
            FROM `doc_list_pos`
            INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
            WHERE `doc_list_pos`.`tovar`='{$pos_info['id']}' AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<'$dt_t' AND `doc_list`.`sklad`='{$this->sklad}' AND `doc_list`.`type`='8' AND `doc_list`.`ok`>0
            ORDER BY `doc_list`.`date`");
        while ($line = $res->fetch_assoc()) {
            $ret_data['outmove_cnt'] += $line['cnt'];
        }
                
        return $ret_data;
    }
    
    private function processGroup($group_id) {
        global $db;
        settype($group_id, 'int');
        $pc_min = rcvint('pc_min');
        $pc_max = rcvint('pc_max');
        $db->query("TRUNCATE TABLE `temp_table`");
        $sql_fields = "`doc_base`.`id`, `doc_base`.`vc`, CONCAT(`doc_base`.`name`, ' - ', `doc_base`.`proizv`) AS `name`"
                . ", `doc_base`.`cost` AS `base_price`, `vals`.`value` AS `provider_code`, `cnvals`.`value` AS `provider_codename`";
        $sql_joins = " LEFT JOIN `doc_base_values` AS `vals` ON `vals`.`id`=`doc_base`.`id` AND `vals`.`param_id`='{$this->pcode_id}'"
                    . " LEFT JOIN `doc_base_values` AS `cnvals` ON `cnvals`.`id`=`doc_base`.`id` AND `cnvals`.`param_id`='{$this->pcodename_id}'";        
        $sql_header = "SELECT $sql_fields FROM `doc_base` $sql_joins";
        $res = $db->query( $sql_header
            . " WHERE `doc_base`.`group`='{$group_id}'");
        $max = 0;
        while ($pos_info = $res->fetch_assoc()) {
            $ret = $this->getPosCounters($pos_info);
            $ret['id'] = $pos_info['id'];
            $ret['vc'] = $pos_info['vc'];
            $ret['name'] = $pos_info['name'];
            $ret['provider_code'] = $pos_info['provider_code'];
            $ret['provider_codename'] = $pos_info['provider_codename'];
            $db->insertA('temp_table', $ret);
            if($ret['out_sum'] > $max) {
                $max = $ret['out_sum'];
            }
        }
        $res = $db->query("SELECT * FROM `temp_table`"
            . " WHERE `last_in_cnt`>0 OR `out_cnt`>0 OR `outmove_cnt` >0 OR `start_cnt`>0 OR `end_cnt`>0"
            . " ORDER BY {$this->sql_order}");
        while($pos_info = $res->fetch_assoc()) {
            if($max) {
                $pp = sprintf("%0.2f", ($pos_info['out_sum'])*100/$max);
            }
            else {
                $pp = '0.00';
            }
            if($pp<$pc_min || $pp>$pc_max) {
                continue;
            }
            $this->tableRow(
                array(
                    $pos_info['id'], $pos_info['vc'], $pos_info['name'], $pos_info['provider_codename'], $pos_info['provider_code'],
                    $pos_info['last_in_cnt'], $pos_info['last_in_date'], $pos_info['start_cnt'], $pos_info['out_cnt'], $pos_info['outmove_cnt'],
                    $pos_info['end_cnt'], $pos_info['out_sum'], $pp)
                );
            
        }
    }
    
    function walkGroup($pgroup_id=0) {
        global $db;
        settype($pgroup_id, 'int');
        $gs = rcvint('gs');
        $g = request('g', array());
        $res_group = $db->query("SELECT `id`, `name` FROM `doc_group` WHERE `pid`=$pgroup_id ORDER BY `id`");
        while ($group_line = $res_group->fetch_assoc()) {  
            if($gs) {
                if(!in_array($group_line['id'], $g)) {
                    continue;
                }
            }
            $this->tableAltStyle();
            $this->tableSpannedRow(array($this->col_cnt), array($group_line['id'] . '. ' . $group_line['name']));
            $this->tableAltStyle(false);
            $this->processGroup($group_line['id']);
            $this->walkGroup($group_line['id']);
        }
    }

    function Make($engine) {
        global $CONFIG, $db;
        $this->loadEngine($engine);

        $this->dt_f = strtotime(rcvdate('dt_f'));
        $this->dt_t = strtotime(rcvdate('dt_t') . " 23:59:59");
        $this->sklad = rcvint('sklad');
        $order = request('order');

        if (!$this->sklad) {
            $this->sklad = 1;
        }

        $res = $db->query("SELECT `id`, `name` FROM `doc_sklady` WHERE `id`='{$this->sklad}'");
        if (!$res->num_rows) {
            throw new Exception("Склад не найден");
        }
        list($sklad_id, $sklad_name) = $res->fetch_row();

        $header = $this->getName();
        if ($this->dt_f > 1) {
            $header .= ", с " . date('Y-m-d', $this->dt_f);
        }
        $header .= ", по " . date('Y-m-d', $this->dt_t) . ", склад: $sklad_name($sklad_id)";
        
        $widths = array(4, 5, 20, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7);
        $headers = array('ID', 'Код', 'Наименование', 'Поставщик', 'Кит. код', 'Посл.приход', 'Дата посл.прихода', 'Нач.кол-во', 'Реализ.', 'Перем.', 'Остаток', 'Сумма продаж', '% прибыли');
        $this->header($header);

        $this->col_cnt = count($widths);
        $this->tableBegin($widths);
        $this->tableHeader($headers);
        
        /// TODO
        switch ($order) {
            case 'vc_up': $this->sql_order = '`vc` ASC';
                break;
            case 'vc_down': $this->sql_order = '`vc` DESC';
                break;
            case 'pc_up': $this->sql_order = '`provider_codename` ASC';
                break;
            case 'pc_down': $this->sql_order = '`provider_codename` DESC';
                break;
            case 's_up': $this->sql_order = '`out_sum` ASC';
                break;
            case 's_down': $this->sql_order = '`out_sum` DESC';
                break;
            default: $this->sql_order = '`name`';
        }
        
        $this->pcode_id = 0;
        $res = $db->query("SELECT `doc_base_params`.`id` FROM `doc_base_params` WHERE `doc_base_params`.`codename`='provider_code'");
        if($res->num_rows) {
            list($this->pcode_id) = $res->fetch_row();
        }
        $this->pcodename_id = 0;
        $res = $db->query("SELECT `doc_base_params`.`id` FROM `doc_base_params` WHERE `doc_base_params`.`codename`='provider_codename'");
        if($res->num_rows) {
            list($this->pcodename_id) = $res->fetch_row();
        }
        
        $db->query("CREATE TEMPORARY TABLE `temp_table` (
            `id` int(11) NOT NULL,
            `vc` varchar(16) NOT NULL,
            `name` varchar(128) NOT NULL,
            `provider_code` varchar(128) NOT NULL,
            `provider_codename` varchar(128) NOT NULL,
            `last_in_cnt` int(11) NOT NULL,
            `last_in_date` date NOT NULL,
            `out_cnt` int(11) NOT NULL,
            `outmove_cnt` int(11) NOT NULL,
            `start_cnt` int(11) NOT NULL,
            `end_cnt` int(11) NOT NULL,
            `out_sum` decimal(10,2) NOT NULL
          ) ENGINE=MEMORY DEFAULT CHARSET=utf8");
        
        $this->walkGroup();
        
        $this->tableEnd();
        $this->output();
        exit(0);
    }

}
