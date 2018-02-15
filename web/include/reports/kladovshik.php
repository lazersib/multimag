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


/// Отчёт по кладовщикам в реализациях
class Report_Kladovshik extends BaseReport {

    function getName($short = 0) {
        if ($short) {
            return "По кладовщикам в реализациях";
        } else {
            return "Отчёт по кладовщикам в реализациях";
        }
    }

    function Form() {
        global $tmpl, $db;
        $d_f = date("Y-m-d", time() - 60 * 60 * 24 * 31);
        $d_t = date("Y-m-d");
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
            <script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
            <form action='' method='post'>
            <input type='hidden' name='mode' value='kladovshik'>
            <fieldset><legend>Дата</legend>
            От: <input type=text id='dt_f' name='dt_f' value='$d_f'><br>
            До: <input type=text id='dt_t' name='dt_t' value='$d_t'>
            </fieldset>
            <br>
            Кладовщик:<br><select name='kladovshik'><option value='0' selected>--не выбран--</option>");
        $res = $db->query("SELECT `user_id`, `worker_real_name` FROM `users_worker_info` WHERE `worker`='1' ORDER BY `worker_real_name`");
        while ($nxt = $res->fetch_row()) {
            $tmpl->addContent("<option value='$nxt[0]'>" . html_out($nxt[1]) . "</option>");
        }
        $tmpl->addContent("</select><br><br>
            Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
            <button type='submit'>Сформировать отчёт</button>
            </form>

            <script type=\"text/javascript\">
            function dtinit() {
                initCalendar('dt_apply',false)
                initCalendar('dt_update',false)
            }

            addEventListener('load',dtinit,false)
            </script>
            ");
    }

    function Make($engine) {
        global $db;
        $this->loadEngine($engine);

        $dt_f = strtotime(rcvdate('dt_f'));
        $dt_t = strtotime(rcvdate('dt_t') . " 23:59:59");
        $kladovshik = rcvint('kladovshik');

        $print_f = date('Y-m-d', $dt_f);
        $print_t = date('Y-m-d', $dt_t);

        $this->header($this->getName() . ", с $print_f по $print_t");

        $widths = array(5, 20, 15, 15, 15, 30);
        $headers = array('ID', 'док', 'Дата', 'Сумма', 'Автор', 'Кладовщик');

        $this->col_cnt = count($widths);
        $this->tableBegin($widths);
        $this->tableHeader($headers);

        $sql_add = '';
        if ($kladovshik) {
            $sql_add = " AND `doc_dopdata`.`value`='$kladovshik' ";
        }

        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`sum`, `autor`.`name` AS `autor_name`, `klad`.`name` AS `sk_name`, 
            `doc_types`.`name` AS `doc_name`, `doc_dopdata`.`value`, `doc_list`.`firm_id`
            FROM `doc_list`
            LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
            LEFT JOIN `users` AS `autor` ON `autor`.`id`=`doc_list`.`user`
            LEFT JOIN `doc_dopdata` ON `doc_list`.`id`=`doc_dopdata`.`doc` AND `doc_dopdata`.`param`='kladovshik'
            LEFT JOIN `users` AS `klad` ON `klad`.`id`=`doc_dopdata`.`value`
            WHERE `doc_list`.`ok`>'0' AND (`doc_list`.`type`='1' OR `doc_list`.`type`='2' OR `doc_list`.`type`='8') AND `doc_list`.`date`>='$dt_f'
                    AND `doc_list`.`date`<='$dt_t' $sql_add
            ORDER BY `doc_list`.`date`");
        $count = $sum = 0;
        while ($line = $res->fetch_assoc()) {
            if(!\acl::testAccess([ 'firm.global', 'firm.'.$line['firm_id']], \acl::VIEW)) {
                continue;
            }
            $this->tableRow(array($line['id'], $line['doc_name'], date('Y-m-d H:i:s', $line['date']), $line['sum'], $line['autor_name'], $line['sk_name']));
            $count++;
            $sum+=$line['sum'];
        }
        $this->tableAltStyle(true);
        $sum = sprintf("%0.2f руб.", $sum);
        $this->tableSpannedRow(array(2, 1, 1, 2), array("Итого:", "$count документов", $sum, ''));
        $this->tableEnd();
        $this->output();
        exit(0);
    }

}
