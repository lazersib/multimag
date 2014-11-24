#!/usr/bin/php
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


$c=explode('/',__FILE__);$base_path='';
for($i=0;$i<(count($c)-2);$i++)	$base_path.=$c[$i].'/';
include_once("$base_path/config_cli.php");
require_once($CONFIG['cli']['location']."/core.cli.inc.php");

$sec = 0;

function transfer($firm_from_id, $firm_to_id, $store_id) {
    global $db, $sec;
    $sec++;
    $sum = 0;
    $unc_pos = array();
    $price_pos = array();
    echo "TRANSFER FROM $firm_from_id TO $firm_to_id:::::::::::::::::::::::::::::::::::::\n";
    $res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`name` FROM `doc_base` ORDER BY `id`");
    while($pos_info = $res->fetch_assoc()) {
        $min = 0;
        $cnt = 0;
        $price = 0;
        $date_info = '';
        $r = $db->query("SELECT `doc_list_pos`.`cnt`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list_pos`.`cost`, `doc_list`.`date` "
            . "FROM `doc_list` "
            . "INNER JOIN `doc_list_pos` ON `doc_list`.`id`=`doc_list_pos`.`doc` "
            . "WHERE `doc_list_pos`.`tovar`='{$pos_info['id']}' AND `doc_list`.`firm_id`=$firm_from_id AND `doc_list`.`sklad`=$store_id AND `doc_list`.`type`<=2 AND `doc_list`.`ok`>0 "
            . "ORDER BY `doc_list`.`date`");
        while($doc_line = $r->fetch_assoc()) {
            switch($doc_line['type']) {
                case 1:
                    $cnt += $doc_line['cnt'];
                    if($doc_line['cost']) {
                        $price = $doc_line['cost'];
                    }
                    break;
                case 2:
                    $cnt -= $doc_line['cnt'];
                    if($cnt<0) {
                        $min = min($min, $cnt);
                        $date_info .= date("Y-m-d ", $doc_line['date']);
                    }
                    break;
            }

        }
        if($min<0) {
            echo "id:{$pos_info['id']}, {$pos_info['name']}: $min, date: $date_info\n";
            $unc_pos[$pos_info['id']] = abs($min);
            $price_pos[$pos_info['id']] = $price;
            $sum += abs($min)*$price;
        }
    }

    if(count($unc_pos)) {
        $doc_data = array(
            'type'  => 1,
            'agent' => 12,
            'date'  => strtotime("2005-01-10 12:01:$sec"),
            'created'=> date("Y-m-d H:i:s"),
            'ok'    => time(),
            'sklad' => $store_id,
            'altnum'=> -1,
            'subtype'=>'bux',
            'firm_id'=>$firm_from_id,
            'sum'   => $sum
        );
        $doc_id = $db->insertA('doc_list', $doc_data);

        $pos_data = array(
            'doc'   => $doc_id,
            'tovar' => 0,
            'cnt'   => 1,
            'cost'  => 100
        );

        foreach($unc_pos as $pos_id => $cnt) {
            if(isset($price_pos[$pos_id])) {
                if($price_pos[$pos_id]>0) {
                    $pos_data['cost'] = $price_pos[$pos_id];
                }
            }
            $pos_data['tovar'] = $pos_id;
            $pos_data['cnt'] = $cnt;
            $db->insertA('doc_list_pos', $pos_data);
        }

        $doc_data['date'] = strtotime("2014-11-24 18:01:$sec");
        $doc_data['type'] = 2;
        $doc_data['firm_id'] = $firm_to_id;
        $doc_data['p_doc'] = $doc_id;

        $doc_id = $db->insertA('doc_list', $doc_data);
        $pos_data['doc'] = $doc_id;

        foreach($unc_pos as $pos_id => $cnt) {
            if(isset($price_pos[$pos_id])) {
                if($price_pos[$pos_id]>0) {
                    $pos_data['cost'] = $price_pos[$pos_id];
                }
            }
            $pos_data['tovar'] = $pos_id;
            $pos_data['cnt'] = $cnt;
            $db->insertA('doc_list_pos', $pos_data);
        }
    }
}

$db->startTransaction();
transfer(3, 1, 1);
transfer(1, 3, 1);

transfer(4, 3, 1);
transfer(3, 1, 1);
transfer(1, 4, 1);

transfer(4, 3, 1);
transfer(3, 1, 1);
transfer(1, 4, 1);


echo"test:\n";
sleep(1);
transfer(4, 3, 1);
transfer(1, 3, 1);
transfer(3, 4, 1);

$db->rollback();


//DELETE FROM `doc_list`
//WHERE `firm_id`=2 AND `sklad`=2


//UPDATE `doc_list` SET `firm_id`=1 WHERE `firm_id`=2
//UPDATE `doc_list` SET `sklad`=1 WHERE id=25644
//DELETE FROM `doc_list` WHERE id=116585