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
require_once($CONFIG['location']."/web/include/doc.core.php");

$unix_date = strtotime("2016-01-01 00:00:00");



$res = $db->query("SELECT `id` FROM `doc_vars`");
$firms = array();
while($line = $res->fetch_assoc()) {
    $firms[$line['id']] = $line['id'];
}
/*
$res = $db->query("SELECT `id`, `name` FROM `doc_base` ORDER BY `id`");
while($nxt = $res->fetch_assoc()) {
    $str = "{$nxt['id']},\"{$nxt['name']}\"";
    foreach ($stores as $store_id) {
        $cnt = getStoreCntOnDate($nxt['id'], $store_id, $unix_date);
        if($cnt!=0) {
            echo "{$nxt['id']},\"{$nxt['name']}\",$store_id,$cnt\n";
        }
        
    }
}
*/



function process_group($group) {
    global $db, $account, $firms, $unix_date, $fd;
    $g_res = $db->query("SELECT `id`, `pid` FROM `doc_agent_group` WHERE `pid`=$group");
    while($g_line = $g_res->fetch_assoc()) {
        if($group == 0) {
            if($g_line['id']==1) {
                $account = 62;
            } elseif ($g_line['id']==2) {
                $account = 60;
            } else {
                $account = 76;
            }                       
        }
        echo "Group:{$g_line['id']}\n";
        process_group($g_line['id']);
        $res = $db->query("SELECT `id`, `name`, `inn`, `kpp` FROM `doc_agent` WHERE `group`='{$g_line['id']}'");
        while($nxt = $res->fetch_assoc()) {
            foreach ($firms as $firm_id) {
                $balance = agentCalcDebt($nxt['id'], true, $firm_id, $db, $unix_date);
                if($balance!=0) {
                    $nxt['name'] = str_replace("\\", "", $nxt['name']);
                    $line = array( $nxt['id'], $firm_id, $account, $nxt['name'], $nxt['inn'], $nxt['kpp'], $balance );
                    fputcsv($fd, $line);
                }
            }
        }
    }
}

$account = 0;
$fd = fopen("agent_balances.csv","w");
fputs($fd, '"ID","Firm_id","Account","Name","Inn","Kpp","Balance"'."\n");

process_group(0);

fclose($fd);