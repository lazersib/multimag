<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2017, BlackLight, TND Team, http://tndproject.org
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
/// Отчёт по агентам
class Report_EditedPos extends BaseReport {

    /// Получить название отчёта
    function getName($short = 0) {
        if ($short) {
            return "По редактированию склада";
        } else {
            return "Отчёт по созданным и редактированным наименованиям на складе";
        }
    }

    /// Форма для формирования отчёта
    function Form() {
        global $tmpl, $db;
        $curdate = date("Y-m-d");
        $startdate = date("Y-m-01");
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
		<script src='/css/jquery/jquery.js' type='text/javascript'></script>
		<script src='/css/jquery/jquery.alerts.js' type='text/javascript'></script>
		<link href='/css/jquery/jquery.alerts.css' rel='stylesheet' type='text/css' media='screen'>
		<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='editedpos'>
		<input type='hidden' name='opt' value='make'>
		Начальная дата:<br>
		<input type='text' name='date_f' id='datepicker_f' value='$startdate'><br>
		Конечная дата:<br>
                <input type='text' name='date_t' id='datepicker_t' value='$curdate'><br>
                Сотрудник:<br>
                <select name='worker_id'><option value='null'>--не выбран--</option>");
                $res = $db->query("SELECT `user_id` AS `id`, `worker_real_name` AS `name` FROM `users_worker_info` WHERE `worker`=1");
                while ($line = $res->fetch_assoc()) {
                    $tmpl->addContent("<option value='{$line['id']}'>{$line['name']}</option>");
                }
                $tmpl->addContent("</select><br>
                <label><input type='checkbox' name='show_desc' value='1'>Показать описание действий</label><br>
                <label><input type='radio' name='order' value='pos_id' checked>Упорядочить по ID наименования</label><br>
                <label><input type='radio' name='order' value='date'>Упорядочить по дате</label><br>
  		Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
		<button type='submit'>Создать отчет</button></form>
		<script type=\"text/javascript\">
		initCalendar('datepicker_f',false);
		initCalendar('datepicker_t',false);
		</script>");
    }

    function Make($engine) {
        global $db;
        
        $show_desc = rcvint('show_desc');
        $worker_id = rcvint('worker_id');
        
        $dt_f = rcvdate('date_f');
        $dt_t = rcvdate('date_t');
        $daystart = strtotime("$dt_f 00:00:00");
        $dayend = strtotime("$dt_t 23:59:59");
        $print_df = date('Y-m-d', $daystart);
        $print_dt = date('Y-m-d', $dayend);
        
        $order = request('order');

        $this->loadEngine($engine);

        $this->header($this->getName() . " c $print_df по $print_dt");
        $widths = array(5, 10, 35, 15, 15, 12, 8);
        $headers = array('ID', 'Код', 'Наименование', 'Операция', 'Дата', 'Автор', 'N изм. '.$order);

        $this->tableBegin($widths);
        $this->tableHeader($headers);
        
        $sql_order = "`doc_log`.`object_id`";
        
        if($order=='date') {
            $sql_order = "`doc_log`.`time`";
        }

        $res = $db->query("SELECT `doc_log`.`id`, `doc_log`.`object_id`, `doc_log`.`motion`, `doc_log`.`time`, `doc_log`.`user`, `doc_log`.`desc`,
                `doc_base`.`name` AS `pos_name`, `doc_base`.`vc`, `doc_base`.`proizv` AS `vendor`, `users`.`name` AS `user_name`
            FROM `doc_log`
            LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_log`.`object_id`
            LEFT JOIN `users` ON `users`.`id`=`doc_log`.`user`
            WHERE `object`='pos'
            AND `time`>='$print_df' AND `time`<='$print_dt'
            ORDER BY $sql_order");
        $updated_pos = array();
        $created_pos = array();
        while ($line = $res->fetch_assoc()) {
            if($worker_id && $worker_id!=$line['user']) {
                continue;
            }
            $p_action = '';
            if(strpos($line['motion'], 'CREATE')!==false) {
                $created_pos[$line['object_id']] = 1;
                $p_action = 'Создание';
            }
            if(strpos($line['motion'], 'UPDATE')!==false) {
                $updated_pos[$line['object_id']] = 1;
                $p_action = 'Изменение';
            }
            if($p_action == '') {
                $p_action = $line['motion'];
            }
            $outline = array(
                $line['object_id'],
                $line['vc'],
                $line['pos_name'].' - '.$line['vendor'],
                $p_action,
                $line['time'],
                $line['user_name'],
                $line['id']
            );
            $this->tableRow($outline);
            if($show_desc) {
                $this->tableSpannedRow(array(2,5), array('Действия:', $line['desc']));
            }
        }
        $this->tableAltStyle(true);
        $this->tableSpannedRow(array(6,1), array('Всего событий:', $res->num_rows));
        $this->tableSpannedRow(array(6,1), array('Создано элементов:', count($created_pos)));
        $this->tableSpannedRow(array(6,1), array('Изменено элементов:', count($updated_pos)));
        
        $this->tableEnd();
        $this->output();
        exit(0);
    }
}
