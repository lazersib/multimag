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

/// Отчёт по остаткам комплектующих
class Report_Pos_Komplekt extends BaseGSReport {

    function getName($short = 0) {
        if ($short) {
            return "По остаткам комплектующих";
        } else {
            return "Отчёт по остаткам комплектующих";
        }
    }

    function Form() {
        global $tmpl, $db;
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
            <form action='' method='post'>
            <input type='hidden' name='mode' value='pos_komplekt'>
            Склад:<br>
            <select name='sklad'>");
        $res = $db->query("SELECT `id`, `name` FROM `doc_sklady`");
        while ($nxt = $res->fetch_row()) {
            $tmpl->addContent("<option value='$nxt[0]'>" . html_out($nxt[1]) . "</option>");
        }
        $tmpl->addContent("</select><br>
            <label><input type='checkbox' name='show_all' value='1'>Отобразить всю номенклатуру</label><br>
            <label><input type='checkbox' name='show_conn' value='1'>Отобразить коды связанных товаров</label><br>
            Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
            <button type='submit'>Сформировать отчёт</button>
            </form>");
    }

    function Make($engine) {
        global $CONFIG, $db;
        $this->loadEngine($engine);
        $sklad = rcvint('sklad');
        $show_all = rcvint('show_all');
        $show_conn = rcvint('show_conn');

        $this->header($this->getName());
        $headers = array('ID', 'Код', 'Наименование', 'Остаток');
        $widths = array(5, 10, 75, 10);

        switch (@$CONFIG['doc']['sklad_default_order']) {
            case 'vc': $order = '`doc_base`.`vc`';
                break;
            case 'cost': $order = '`doc_base`.`cost`';
                break;
            default: $order = '`doc_base`.`name`';
        }

        $this->tableBegin($widths);
        $this->tableHeader($headers);
        $cnt = 0;
        $col_cnt = count($headers);
        $res_group = $db->query("SELECT `id`, `name` FROM `doc_group` ORDER BY `id`");
        while ($group_line = $res_group->fetch_assoc()) {
            $this->tableAltStyle();
            $this->tableSpannedRow(array($col_cnt), array($group_line['id'] . ': ' . $group_line['name']));
            $this->tableAltStyle(false);
            if ($show_all) {
                $res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base_cnt`.`cnt`, `doc_base_kompl`.`kompl_id`
                    FROM `doc_base`
                    LEFT JOIN `doc_base_kompl` ON `doc_base`.`id`=`doc_base_kompl`.`kompl_id`
                    INNER JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$sklad'
                    WHERE `doc_base`.`group`='{$group_line['id']}'
                    GROUP BY `doc_base`.`id`
                    ORDER BY $order");
            } else
                $res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base_cnt`.`cnt`
                    FROM `doc_base_kompl`
                    INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_base_kompl`.`kompl_id`
                    INNER JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$sklad'
                    WHERE `doc_base`.`group`='{$group_line['id']}'
                    GROUP BY `doc_base_kompl`.`kompl_id`
                    ORDER BY $order");
            while ($nxt = $res->fetch_assoc()) {
                $nxt['cnt'] = round($nxt['cnt'], 3);
                if (!$nxt['cnt']) {
                    continue;
                }
                if ($show_all) {
                    if ($nxt['kompl_id']) {
                        $nxt['name'] = '(+) ' . $nxt['name'];
                    }
                    unset($nxt['kompl_id']);
                }
                $nxt['cnt'] = round($nxt['cnt'], 3);
                $this->tableRow(array($nxt['id'], $nxt['vc'], $nxt['name'], $nxt['cnt']));
                if ($show_conn) {
                    $r = $db->query("SELECT `doc_base_kompl`.`pos_id`, `doc_base`.`vc`
                        FROM `doc_base_kompl`
                        LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_base_kompl`.`pos_id`
                        WHERE `doc_base_kompl`.`kompl_id`='{$nxt['id']}'");
                    if ($r->num_rows) {
                        $list = '';
                        while ($l = $r->fetch_row()) {
                            if ($list) {
                                $list.=", ";
                            }
                            $list.="$l[0] ($l[1])";
                        }
                        $this->tableSpannedRow(array(2, $col_cnt - 2), array('', $list));
                    }
                }
                $cnt++;
            }
        }
        $this->tableAltStyle();
        $this->tableSpannedRow(array(1, $col_cnt - 1), array('Итого:', $cnt . ' товаров'));
        $this->tableEnd();
        $this->output();
        exit(0);
    }

}
