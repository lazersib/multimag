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
/// Отчёт по нарушению лимитов минимальных остатков
class Report_MinCnt extends BaseGSReport {

    function getName($short = 0) {
        if ($short) {
            return "По минимальным остаткам";
        } else {
            return "Отчёт по нарушению лимитов минимальных остатков";
        }
    }

    function Form() {
        global $tmpl, $db;
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
            <script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
            <form action='' method='post'>
            <input type='hidden' name='mode' value='mincnt'>
            Склад:<br>
            <select name='sklad'>
            <option value='0'>--не задан--</option>");
        $res = $db->query("SELECT `id`, `name` FROM `doc_sklady` ORDER BY id");
        while ($nxt = $res->fetch_row()) {
            $tmpl->addContent("<option value='$nxt[0]'>" . html_out($nxt[1]) . "</option>");
        }
        $tmpl->addContent("</select><br>
            Формат:<br><select name='opt'><option>pdf</option><option>html</option></select><br>
            <button type='submit'>Сформировать отчёт</button>
            </form>");
    }

    function Make($engine) {
        global $CONFIG, $db;
        $this->loadEngine($engine);
        $sklad = rcvint('sklad');

        $header = $this->getName();
        if ($sklad) {
            $header.=" на складе N$sklad";
        }
        $this->header($header);

        $widths = array(5, 8, 51, 8, 8, 9, 11);
        $headers = array('ID', 'Код', 'Наименование', 'Остаток', 'В пути', 'Минимум', 'Не хватает');

        $this->col_cnt = count($widths);
        $this->tableBegin($widths);
        $this->tableHeader($headers);

        switch (@$CONFIG['doc']['sklad_default_order']) {
            case 'vc': $order = '`doc_base`.`vc`';
                break;
            default: $order = '`doc_base`.`name`';
        }

        if ($sklad) {
            $res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`vc`, CONCAT(`doc_base`.`name`, ' - ', `doc_base`.`proizv`) AS `name`,
                    `doc_base_cnt`.`cnt`, `doc_base`.`transit_cnt`, `doc_base_cnt`.`mincnt`
                FROM `doc_base_cnt`
                LEFT JOIN `doc_base` ON `doc_base_cnt`.`id`=`doc_base`.`id`
                WHERE `doc_base_cnt`.`sklad`='$sklad' AND `doc_base_cnt`.`cnt`<`doc_base_cnt`.`mincnt`
                ORDER BY $order DESC");
        } else {
            $res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`vc`, CONCAT(`doc_base`.`name`, ' - ', `doc_base`.`proizv`) AS `name`,
                    `doc_base_cnt`.`cnt`, `doc_base`.`transit_cnt`, `doc_base_cnt`.`mincnt`
                FROM `doc_base_cnt`
                LEFT JOIN `doc_base` ON `doc_base_cnt`.`id`=`doc_base`.`id`
                WHERE `doc_base_cnt`.`cnt`<`doc_base_cnt`.`mincnt`
                ORDER BY $order DESC");
        }
        while ($nxt = $res->fetch_row()) {
            $nxt[6] = $nxt[5] - $nxt[3];
            $this->tableRow($nxt);
        }
        $this->tableEnd();
        $this->output();
        exit(0);
    }

}
