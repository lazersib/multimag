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
/// Отчёт по агентам ответственного сотрудника
class Report_Agent_resp extends BaseReport {

    /// Получить название отчёта
    function getName($short = 0) {
        if ($short) {
            return "По агентам ответственного сотрудника";
        } else {
            return "Отчёт по агентам ответственного сотрудника";
        }
    }

    /// Форма для формирования отчёта
    function Form() {
        global $tmpl, $db;
        $date_start = date("Y-01-01");
        $date_end = date("Y-m-d");
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
        <script src='/css/jquery/jquery.js' type='text/javascript'></script>
        <script src='/css/jquery/jquery.alerts.js' type='text/javascript'></script>
        <link href='/css/jquery/jquery.alerts.css' rel='stylesheet' type='text/css' media='screen'>
        <script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
        <form action='' method='post'>
        <input type='hidden' name='mode' value='agent_resp'>
        <input type='hidden' name='opt' value='make'>
        Начальная дата:<br>
        <input type='text' name='date_f' id='datepicker_f' value='$date_start'><br>
        Конечная дата:<br>
        <input type='text' name='date_t' id='datepicker_t' value='$date_end'><br>
        Сотрудник:<br>
        <select name='worker_id'>");
        $res = $db->query("SELECT `user_id` AS `id`, `worker_real_name` AS `name` FROM `users_worker_info` WHERE `worker`=1");
        while ($line = $res->fetch_assoc()) {
            $tmpl->addContent("<option value='{$line['id']}'>{$line['name']}</option>");
        }
        $tmpl->addContent("</select><br>
        Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
        <button type='submit'>Создать отчет</button></form>
        <script type=\"text/javascript\">
        initCalendar('datepicker_f',false);
        initCalendar('datepicker_t',false);
        </script>");
    }

    function Make($engine) {
        global $db;
        $date_f = rcvdate('date_f');
        $date_t = rcvdate('date_t');

        $udate_f = strtotime($date_f);
        $udate_t = strtotime($date_t . ' 23:59:59');

        $worker_id = rcvint('worker_id');
        $this->loadEngine($engine);

        $res = $db->query("SELECT `worker_real_name` FROM `users_worker_info` WHERE `user_id`=$worker_id");
        if (!$res->num_rows) {
            throw new Exception("Сотрудник не найден");
        }
        list($w_name) = $res->fetch_row();

        $this->header("Отчёт по агентам c $date_f по $date_t для ответственного $w_name (id:$worker_id)");
        $widths = array(8, 59, 11, 11, 11);
        $headers = array('id', 'Агент', 'По кассе', 'По банку', 'Всего');
        $this->tableBegin($widths);
        $this->tableHeader($headers);

        $res = $db->query("SELECT `id`, `name` FROM `doc_agent` WHERE `responsible`='$worker_id'");

        $k_sum = $b_sum = $all_sum = 0;
        while ($line = $res->fetch_assoc()) {
            $k_val = $b_val = 0;
            $k_res = $db->query("SELECT SUM(`sum`) FROM `doc_list` 
                WHERE `type`=6 AND `ok`>0 AND `mark_del`=0 AND `agent`={$line['id']} AND `date`>=$udate_f AND `date`<=$udate_t ");
            if ($k_res->num_rows) {
                list($k_val) = $k_res->fetch_row();
            }
            $b_res = $db->query("SELECT SUM(`sum`) FROM `doc_list` 
                WHERE `type`=4 AND `ok`>0 AND `mark_del`=0 AND `agent`={$line['id']} AND `date`>=$udate_f AND `date`<=$udate_t ");
            if ($b_res->num_rows) {
                list($b_val) = $b_res->fetch_row();
            }
            if (($k_val + $b_val) == 0) {
                continue;
            }

            $k_sum += $k_val;
            $b_sum += $b_val;

            $k_val_p = $b_val_p = '';
            if ($k_val) {
                $k_val_p = number_format($k_val, 2, '.', ' ') . ' р.';
            }
            if ($b_val) {
                $b_val_p = number_format($b_val, 2, '.', ' ') . ' р.';
            }
            $sum_p = number_format($k_val + $b_val, 2, '.', ' ') . ' р.';

            $this->tableRow(array($line['id'], $line['name'], $k_val_p, $b_val_p, $sum_p));
        }
        $this->tableAltStyle(true);
        $k_sum_p = $b_sum_p = '';
        if ($k_sum) {
            $k_sum_p = number_format($k_sum, 2, '.', ' ') . ' р.';
        }
        if ($b_sum) {
            $b_sum_p = number_format($b_sum, 2, '.', ' ') . ' р.';
        }
        $sum_p = number_format($k_sum + $b_sum, 2, '.', ' ') . ' р.';
        $this->tableSpannedRow(array(2, 1, 1, 1), array('Всего', $k_sum_p, $b_sum_p, $sum_p));
        $this->tableEnd();
        $this->output();
        exit(0);
    }

}
