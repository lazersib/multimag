#!/usr/bin/php
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


$c=explode('/',__FILE__);
$base_path='';
for($i=0;$i<(count($c)-2);$i++)	$base_path.=$c[$i].'/';

require_once("$base_path/config_cli.php");
require_once("$base_path/web/include/doc.core.php");
require_once($CONFIG['cli']['location']."/core.cli.inc.php");

$sec = 0;

function getMinCntPos($pos_id, $store_id)
{
    global $db;
    settype($pos_id, 'int');
    settype($store_id, 'int');
    $cnt = $mincnt = 0;
    $res = $db->query("SELECT `doc_list_pos`.`cnt`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`id`, `doc_list_pos`.`page` FROM `doc_list_pos`
	INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
	WHERE  `doc_list`.`ok`>'0' AND `doc_list_pos`.`tovar`=$pos_id AND 
            (`doc_list`.`type`=1 OR `doc_list`.`type`=2 OR `doc_list`.`type`=8 OR `doc_list`.`type`=17) 
        ORDER BY `doc_list`.`date`");
    while ($nxt = $res->fetch_row()) {
        if ($nxt[1] == 1) {
            if ($nxt[2] == $store_id)
                $cnt+=$nxt[0];
        }
        else if ($nxt[1] == 2 || $nxt[1] == 20) {
            if ($nxt[2] == $store_id)
                $cnt-=$nxt[0];
        }
        else if ($nxt[1] == 8) {
            if ($nxt[2] == $store_id)
                $cnt-=$nxt[0];
            else {
                $r = $db->query("SELECT `value` FROM `doc_dopdata` WHERE `doc`=$nxt[3] AND `param`='na_sklad'");
                if (!$r->num_rows)
                    throw new Exception("Cклад назначения в перемещении $nxt[3] не задан");
                list($nasklad) = $r->fetch_row();
                if (!$nasklad)
                    throw new Exception("Нулевой склад назначения в перемещении $nxt[3] при проверке на отрицательные остатки");
                if ($nasklad == $store_id)
                    $cnt+=$nxt[0];
                $r->free();
            }
        }
        else if ($nxt[1] == 17) {
            if ($nxt[2] == $store_id) {
                if ($nxt[4] == 0)
                    $cnt+=$nxt[0];
                else
                    $cnt-=$nxt[0];
            }
        }
        $cnt = round($cnt, 3);
        if ($cnt < $mincnt) {
            $mincnt = $cnt;
        }
    }
    $res->free();
    return array($cnt, $mincnt);
}

function testMinusStore($store_id, $firm_id) {
    global $db;
    $pos_list = array();
    $pos_plus_list = array();
    $res = $db->query("SELECT `id`, CONCAT(`vc`, ' ', `name`) AS `name`, `cost`
        FROM `doc_base`
        WHERE `pos_type`=0
        ORDER BY `id`");
    $sum = $plus_sum = 0;
    while($line = $res->fetch_assoc()) {
        list($cnt, $mincnt) = getMinCntPos($line['id'], $store_id);
        $mincnt = ceil(abs($mincnt));
        if($mincnt>0) {
            $pos_list[$line['id']] = array('tovar'=>$line['id'], 'cnt'=>$mincnt, 'cost'=>$line['cost']);
            $sum += $mincnt*$line['cost'];
            echo "{$line['name']} - $mincnt\n";
        }
        if($cnt<0) {
            $pos_plus_list[$line['id']] = array('tovar'=>$line['id'], 'cnt'=>ceil(abs($cnt)), 'cost'=>$line['cost']);
            $plus_sum += $mincnt*$line['cost'];
        }
    }
    
    if(count($pos_plus_list)) {
        fixStartCnt($store_id, $firm_id, $pos_plus_list, $plus_sum);
    }
    
    if(count($pos_list)) {
        fixStore($store_id, $firm_id, $pos_list, $sum);
    }
    if(count($pos_plus_list) || count($pos_list)) {
        return 1;
    }
}

function fixStartCnt($store_id, $firm_id, $pos_list, $sum) {
    global $db, $sec;
    
    $doc_data = array(
        'type'  => 1,
        'agent' => 1,
        'date'  => strtotime("2005-01-10 12:01:00"),
        'created'=> date("Y-m-d H:i:s"),
        'ok'    => time(),
        'sklad' => $store_id,
        'altnum'=> $store_id,
        'subtype'=>'sfix',
        'firm_id'=>$firm_id,
        'sum'   => $sum
    );
    $doc_id = $db->insertA('doc_list', $doc_data);

    foreach($pos_list as $pos_info) {
        $pos_info['doc'] = $doc_id;
        $db->insertA('doc_list_pos', $pos_info);
    }
}

function fixStore($store_id, $firm_id, $pos_list, $sum) {
    global $db, $sec;
    
    $doc_data = array(
        'type'  => 1,
        'agent' => 1,
        'date'  => strtotime("2005-01-10 12:00:00"),
        'created'=> date("Y-m-d H:i:s"),
        'ok'    => time(),
        'sklad' => $store_id,
        'altnum'=> $store_id,
        'subtype'=>'fix',
        'firm_id'=>$firm_id,
        'sum'   => $sum
    );
    $doc_id = $db->insertA('doc_list', $doc_data);

    foreach($pos_list as $pos_info) {
        $pos_info['doc'] = $doc_id;
        $db->insertA('doc_list_pos', $pos_info);
    }

    $doc_data['date'] = time();
    $doc_data['type'] = 2;
    $doc_data['p_doc'] = $doc_id;

    $doc_id = $db->insertA('doc_list', $doc_data);
    $pos_data['doc'] = $doc_id;

    foreach($pos_list as $pos_info) {
        $pos_info['doc'] = $doc_id;
        $db->insertA('doc_list_pos', $pos_info);
    }
}

$db->startTransaction();
$res = $db->query("SELECT `id`, `name` FROM `doc_sklady` ORDER BY `id`");
while($line = $res->fetch_assoc()) {
    echo "FIX {$line['name']}:\n";
    if(testMinusStore($line['id'], 1)) {
        echo "AGAIN:\n";
        testMinusStore($line['id'], 1);
    }
}
//$db->rollback();
$db->commit();


//DELETE FROM `doc_list`
//WHERE `firm_id`=2 AND `sklad`=2


//UPDATE `doc_list` SET `firm_id`=1 WHERE `firm_id`=2
//UPDATE `doc_list` SET `sklad`=1 WHERE id=25644
//DELETE FROM `doc_list` WHERE id=116585
