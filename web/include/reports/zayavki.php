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
/// Отчёт по заявкам покупателей
class Report_Zayavki extends BaseGSReport {

    /// Получить имя отчёта
    public function getName($short = 0) {
        if ($short)
            return "По заявкам покупателей";
        else
            return "Отчёт по заявкам покупателей";
    }

    /// Отобразить форму
    protected function Form() {
        global $tmpl, $db;
        $d_t = date("Y-m-d");
        $d_f = date("Y-m-d", time() - 60 * 60 * 24 * 31);
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
            <script type=\"text/javascript\">
            function dtinit()
            {
                    initCalendar('dt_f',false)
                    initCalendar('dt_t',false)
            }
            addEventListener('load',dtinit,false)	
            </script>
            <form action='' method='post'>
            <input type='hidden' name='mode' value='zayavki'>
            <fieldset><legend>Дата</legend>
            С:<input type=text id='dt_f' name='dt_f' value='$d_f'><br>
            По:<input type=text id='dt_t' name='dt_t' value='$d_t'>
            </fieldset>
            Группа товаров:<br>");
        $this->GroupSelBlock();
        $tmpl->addContent("<br><label><input type='checkbox' name='ag' value='1'>Группировать по агентам</label><br>
            Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
            <button type='submit'>Сформировать отчёт</button>
            </form>");
    }

    /// Сформировать отчёт
    protected function Make($engine) {
        global $CONFIG, $db;
        $this->loadEngine($engine);
        $dt_f = strtotime(rcvdate('dt_f'));
        $dt_t = strtotime(rcvdate('dt_t')) + 60 * 60 * 24 - 1;
        $gs = rcvint('gs');
        $ag = rcvint('ag');
        $g = request('g', array());

        $print_df = date('Y-m-d', $dt_f);
        $print_dt = date('Y-m-d', $dt_t);
        $this->header("Отчёт по заявкам покупателей с $print_df по $print_dt");
        $headers = array('ID');
        $widths = array(5);

        $headers[] = 'Код';
        $widths[] = 10;

        switch (@$CONFIG['doc']['sklad_default_order']) {
            case 'vc': $order = '`doc_base`.`vc`';
                break;
            case 'cost': $order = '`doc_base`.`cost`';
                break;
            default: $order = '`doc_base`.`name`';
        }

        $headers = array_merge($headers, array('Наименование', 'Кол-во'));

        $widths[] = 75;
        $widths[] = 10;

        $this->tableBegin($widths);
        $this->tableHeader($headers);
        $cnt = 0;
        $col_cnt = count($headers);
        $sql_add = '';
        if ($gs) {
            if (is_array($g)) {
                $s = '';
                foreach ($g as $g_id) {
                    if ($s) {
                        $s .= ',';
                    }
                    $s.=intval($g_id);
                }
                $sql_add = " AND `doc_base`.`group` IN ($s)";
            }
        }

        $sql = "SELECT `doc_base`.`id`, `doc_base`.`vc`, CONCAT(`doc_group`.`printname`, ' ', `doc_base`.`name`) AS `name`,"
            . " `doc_list_pos`.`cnt`, `doc_dopdata`.`value` AS `status`, `doc_list`.`firm_id`"
            . " FROM `doc_list_pos`"
            . " INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`"
            . " INNER JOIN `doc_group` ON `doc_base`.`group`=`doc_group`.`id`"
            . " INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`"
            . " LEFT JOIN `doc_dopdata` ON `doc_list`.`id`=`doc_dopdata`.`doc` AND `doc_dopdata`.`param`='status'"
            . " WHERE `doc_list`.`date`>=$dt_f AND `doc_list`.`date`<=$dt_t AND `doc_list`.`type`=3 AND `doc_list`.`ok`>0 $sql_add";


        if (!$ag) {
            $res = $db->query($sql);
            $l_cnt = array();
            $info = array();
            while ($line = $res->fetch_assoc()) {
                if(!\acl::testAccess([ 'firm.global', 'firm.'.$line['firm_id']], \acl::VIEW)) {
                    continue;
                }
                if ($line['status'] == 'ok' || $line['status'] == 'err') {
                    continue;
                }
                $info[$line['id']] = $line;
                if (!isset($l_cnt[$line['id']])) {
                    $l_cnt[$line['id']] = $line['cnt'];
                } else {
                    $l_cnt[$line['id']] += $line['cnt'];
                }
            }
            foreach ($l_cnt AS $pos_id => $cnt) {
                $row = array($pos_id, $info[$pos_id]['vc'], $info[$pos_id]['name'], $cnt);
                $this->tableRow($row);
            }
        } else {
            $ares = $db->query("SELECT `id`, `name` FROM `doc_agent` ORDER BY `name`");
            while ($agent_info = $ares->fetch_assoc()) {
                $sql_this = $sql . " AND `doc_list`.`agent`={$agent_info['id']}";
                $res = $db->query($sql_this);
                if ($res->num_rows) {
                    $this->tableAltStyle();
                    $this->tableSpannedRow(array($col_cnt), array($agent_info['name']));
                    $this->tableAltStyle(false);
                    $l_cnt = array();
                    $info = array();
                    while ($line = $res->fetch_assoc()) {
                        if ($line['status'] == 'ok' || $line['status'] == 'err') {
                            continue;
                        }
                        if(!\acl::testAccess([ 'firm.global', 'firm.'.$line['firm_id']], \acl::VIEW)) {
                            continue;
                        }
                        $info[$line['id']] = $line;
                        if (!isset($l_cnt[$line['id']])) {
                            $l_cnt[$line['id']] = $line['cnt'];
                        } else {
                            $l_cnt[$line['id']] += $line['cnt'];
                        }
                    }
                    foreach ($l_cnt AS $pos_id => $cnt) {
                        $row = array($pos_id, $info[$pos_id]['vc'], $info[$pos_id]['name'], $cnt);
                        $this->tableRow($row);
                    }
                }
            }
        }


        $this->tableAltStyle();
        $this->tableEnd();
        $this->output();
        exit(0);
    }

}
