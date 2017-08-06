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

include_once("include/doc.s.nulltype.php");

class doc_s_Inform extends doc_s_Nulltype {

    function getReserveDocList($pos_id) {
        global $db;
        settype($pos_id, 'int');
        $orders = array();
        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`, `doc_list`.`agent` AS `agent_id`"
                . ", `doc_agent`.`name` AS `agent_name`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost` AS `price`"
            . " FROM `doc_list_pos`"
            . " INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`"
            . " LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`"
            . " WHERE `doc_list_pos`.`tovar`=$pos_id  AND `doc_list`.`type`=3 AND `doc_list`.`ok`>0");
        while($line = $res->fetch_assoc()) {
            $orders[$line['id']] = $line;
        }
        $res = $db->query("SELECT `doc_list`.`id`, `doc_list_pos`.`cnt`, `doc_list`.`p_doc`"
            . " FROM `doc_list_pos`"
            . " INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`"
            . " WHERE `doc_list_pos`.`tovar`=$pos_id  AND (`doc_list`.`type`=2 OR `doc_list`.`type`=20) AND `doc_list`.`ok`>0");
        while($line = $res->fetch_assoc()) {
            if(!$line['p_doc'] || !isset($orders[$line['p_doc']])) {
                continue;
            }
            $orders[$line['p_doc']]['cnt'] -= $line['cnt'];
            if($orders[$line['p_doc']]['cnt']<=0) {
                unset($orders[$line['p_doc']]);
            }
        }
        return $orders;
    }
    
    function getTransitDocList($pos_id) {
        global $db;
        settype($pos_id, 'int');
        $orders = array();
        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`, `doc_list`.`agent` AS `agent_id`"
                . ", `doc_agent`.`name` AS `agent_name`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost` AS `price`"
            . " FROM `doc_list_pos`"
            . " INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`"
            . " LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`"
            . " WHERE `doc_list_pos`.`tovar`=$pos_id  AND `doc_list`.`type`=12 AND `doc_list`.`ok`>0");
        while($line = $res->fetch_assoc()) {
            $orders[$line['id']] = $line;
        }
        $res = $db->query("SELECT `doc_list`.`id`, `doc_list_pos`.`cnt`, `doc_list`.`p_doc`"
            . " FROM `doc_list_pos`"
            . " INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`"
            . " WHERE `doc_list_pos`.`tovar`=$pos_id  AND `doc_list`.`type`= 1 AND `doc_list`.`ok`>0");
        while($line = $res->fetch_assoc()) {
            if(!$line['p_doc'] || !isset($orders[$line['p_doc']])) {
                continue;
            }
            $orders[$line['p_doc']]['cnt'] -= $line['cnt'];
            if($orders[$line['p_doc']]['cnt']<=0) {
                unset($orders[$line['p_doc']]);
            }
        }
        return $orders;
    }
    
    function getOfferDocList($pos_id) {
        global $db;
        settype($pos_id, 'int');
        $orders = array();
        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`, `doc_list`.`agent` AS `agent_id`"
                . ", `doc_agent`.`name` AS `agent_name`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost` AS `price`"
            . " FROM `doc_list_pos`"
            . " INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`"
            . " LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`"
            . " WHERE `doc_list_pos`.`tovar`=$pos_id  AND `doc_list`.`type`=11 AND `doc_list`.`ok`>0");
        while($line = $res->fetch_assoc()) {
            $orders[$line['id']] = $line;
        }
        return $orders;
    }
    
    
    function Service() {
        global $tmpl, $db;
        $opt = request('opt');
        $pos = rcvint('pos');
        $doc = rcvint('doc');
        $tmpl->ajax = 1;
        if($opt=='rezerv') {
            $orders = $this->getReserveDocList($pos);
            if(count($orders)>0) {
                $tmpl->addContent("<table class='list' width='100%'><tr><th>N</th><th>Дата</th><th>Агент</th><th>Кол-во</th><th>Цена</th></tr>");
                foreach($orders as $order) {
                    $date = date("Y-m-d", $order['date']);
                    $tmpl->addContent("<tr><td><a href='/doc.php?mode=body&amp;doc={$order['id']}'>{$order['altnum']}{$order['subtype']}</a></td>"
                        . "<td>$date</td><td>{$order['agent_name']}</td><td>{$order['cnt']}</td><td>{$order['price']}</td></tr>");
                }
                $tmpl->addContent("</table>");
            }
        }
        elseif($opt=='vputi') {
            $orders = $this->getTransitDocList($pos);
            if(count($orders)>0) {
                $tmpl->addContent("<table class='list' width='100%'><tr><th>N</th><th>Дата</th><th>Агент</th><th>Кол-во</th><th>Цена</th></tr>");
                foreach($orders as $order) {
                    $date = date("Y-m-d", $order['date']);
                    $tmpl->addContent("<tr><td><a href='/doc.php?mode=body&amp;doc={$order['id']}'>{$order['altnum']}{$order['subtype']}</a></td>"
                        . "<td>$date</td><td>{$order['agent_name']}</td><td>{$order['cnt']}</td><td>{$order['price']}</td></tr>");
                }
                $tmpl->addContent("</table>");
            }
        }
        elseif($opt=='p_zak') {
            $orders = $this->getOfferDocList($pos);
            if(count($orders)>0) {
                $tmpl->addContent("<table class='list' width='100%'><tr><th>N</th><th>Дата</th><th>Агент</th><th>Кол-во</th><th>Цена</th></tr>");
                foreach($orders as $order) {
                    $date = date("Y-m-d", $order['date']);
                    $tmpl->addContent("<tr><td><a href='/doc.php?mode=body&amp;doc={$order['id']}'>{$order['altnum']}{$order['subtype']}</a></td>"
                        . "<td>$date</td><td>{$order['agent_name']}</td><td>{$order['cnt']}</td><td>{$order['price']}</td></tr>");
                }
                $tmpl->addContent("</table>");
            }
        }
        else if ($opt == 'p_zak') {
            $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`, `doc_list_pos`.`cnt`,
                    `doc_list_pos`.`cost`, `doc_agent`.`name`
                FROM `doc_list_pos`
                INNER JOIN `doc_list` ON `doc_list`.`type`='11' AND `doc_list`.`ok`>'0' AND `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`id` NOT IN (SELECT DISTINCT `p_doc` FROM `doc_list` WHERE `ok` != '0' AND `type`='1' )
                LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
                WHERE `doc_list_pos`.`tovar`='$pos'");
            if ($res->num_rows) {
                $tmpl->addContent("<table class='list' width='100%'><tr><th>N</th><th>Дата</th><th>Агент</th><th>Кол-во</th><th>Цена</th></tr>");
                while ($nxt = $res->fetch_row()) {
                    $dt = date('d.m.Y', $nxt[3]);
                    $tmpl->addContent("<tr><td><a href='/doc.php?mode=body&amp;doc=$nxt[0]'>$nxt[1]$nxt[2]</a></td><td>$dt</td><td>"
                        . html_out($nxt[6]) . "</td><td>$nxt[4]</td><td>$nxt[5]</td></tr>");
                }
                $tmpl->addContent("</table>");
            } else
                $tmpl->msg("Не найдено!");
        }
        else if ($opt == 'ost') {
            $tmpl->ajax = 1;
            $pos = rcvint('pos');
            $res = $db->query("SELECT `doc_sklady`.`name`, `doc_base_cnt`.`cnt` 
                FROM `doc_base_cnt`
                INNER JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_base_cnt`.`sklad`
                WHERE `doc_base_cnt`.`id`='$pos' AND `doc_sklady`.`hidden`=0");
            $tmpl->addContent("<table width='100%' class='list'><tr><th>Склад<th>Кол-во</tr>");
            while ($nxt = $res->fetch_row())
                $tmpl->addContent('<tr><td>' . html_out($nxt[0]) . '</td><td>' . html_out($nxt[1]) . '</td></tr>');
            $tmpl->addContent("</table>");
        } else if ($opt == 'dolgi') {
            $agent = rcvint('agent');
            $res = $db->query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `id`");
            while ($nxt = $res->fetch_row()) {
                $dolg = agentCalcDebt($agent, 0, $nxt[0]);
                $tmpl->addContent("<div>Долг перед " . html_out($nxt[1]) . ": <b>$dolg</b> руб.</div>");
            }
        }
    }

}
