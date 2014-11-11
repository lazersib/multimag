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
            <input type='hidden' name='MAX_FILE_SIZE' value='10000000'><input name='userfile' type='file'>
            <button type='submit'>Выполнить</button>
            </form>";
    }
    
    function Run($mode) {
        global $tmpl, $db;
        if ($mode == 'view') {
            $tmpl->addContent($this->getForm());
        } else if ($mode == 'load') {
            $tmpl->addContent("<h1>" . $this->getname() . "</h1>");
            if ($_FILES['userfile']['size'] <= 0) {
                throw new Exception("Забыли выбрать файл?");
            }
            $file = file($_FILES['userfile']['tmp_name']);
            $ex = new Bank1CExchange();
            $parsed_data = $ex->Parse($file);
            
            // Список наших фирм и счетов
            $banks = array();
            $res = $db->query("SELECT `num` AS `bank_id`, `firm_id`, `rs` FROM `doc_kassa` WHERE `ids`='bank'");
            while($line = $res->fetch_assoc()) {
                if($line['rs']) {
                    $banks[$line['rs']] = $line;
                }
            }
            
            $tmpl->addContent("<table width='100%' class='list'>
                <tr>
                <th>ID</th><th>Номер П/П</th><th>Дата</th><th>Сумма</th><th>Счёт</th><th>Назначение</th><th>Есть?</th>");

            foreach ($parsed_data as $v_line) {
                if($v_line['kredit']==0) { // Это не входящий документ
                    continue;
                }
                
                $uni = intval($v_line['unique']);
                $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`sum`, `doc_dopdata`.`value`
		FROM `doc_list` 
		LEFT JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list`.`id` AND `doc_dopdata`.`param`='unique'
		WHERE `doc_dopdata`.`value`=$uni AND `doc_list`.`type`='4'");
                $doc_nums = '';
                while($doc_info = $res->fetch_assoc()) {
                    $doc_nums .= $doc_info['id'] .' ';
                }
                
                if(!$doc_nums) {
                    // Определяем агента
                    $agent_id = 1;
                    $rs_sql = $db->real_escape_string($v_line['kschet']);
                    $res = $db->query("SELECT `id` FROM `doc_agent` WHERE `rs` = '$rs_sql'");
                    while($ag_info = $res->fetch_assoc()) {
                        $agent_id = $ag_info['id'];
                    }
                    
                    // Определяем номер счёта
                    $order_id = 0;
                    $sum = $db->real_escape_string($v_line['kredit']);
                    if($agent_id>1) {
                        $res = $db->query("SELECT `id`, `agent` FROM `doc_list` WHERE `type`='3' AND `sum`='$sum' AND `agent`=$agent_id ORDER BY `id` DESC LIMIT 1");
                        while($doc_info = $res->fetch_assoc()) {
                            $order_id = $doc_info['id'];
                        }
                    } else {
                        $res = $db->query("SELECT `id`, `agent` FROM `doc_list` WHERE `type`='3' AND `sum`='$sum' ORDER BY `id` DESC LIMIT 1");
                        while($doc_info = $res->fetch_assoc()) {
                            $order_id = $doc_info['id'];
                            $agent_id = $doc_info['agent'];
                        }
                    }
                    $desc = $db->real_escape_string($v_line['desc']);
                    $docnum = $db->real_escape_string($v_line['docnum']);
                    $time = time();
                    $bank_id = $banks[$v_line['schet']]['bank_id'];
                    $firm_id = $banks[$v_line['schet']]['firm_id'];
                    $db->query("INSERT INTO `doc_list` ( `type`, `agent`, `comment`, `date`, `altnum`, `subtype`, `sum`, `p_doc`, `sklad`, `bank`, `firm_id`)
                            VALUES ('4', '$agent_id', '$desc', '$time', '$docnum', 'auto', '$sum', '$order_id' , 0, '$bank_id', '$firm_id')");
                    $new_id = $db->insert_id;
                    echo "insert_id: $new_id\n";
                    $db->query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`) VALUES ('$new_id', 'unique', '$uni')");
                    $doc_nums = $new_id;
                }
                
                $tmpl->addContent("<tr><td>{$v_line['unique']}</td><td>{$v_line['docnum']}</td><td>{$v_line['date']}</td>
                    <td>{$v_line['kredit']}</td><td>{$v_line['kschet']}</td>"
                    . "<td>".html_out($v_line['desc'])."</td><td>$doc_nums</td></tr>");

                $tmpl->AddContent("</tr>");
            }
            $tmpl->addContent("</table>");
        }
    }

    function getName() {
        return "Импорт банковских документов";
    }

}
