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

$firm_res = $db->query("SELECT `id`, `firm_name` AS `name` FROM `doc_vars`");
while($firm_info = $firm_res->fetch_assoc()) {
    echo "firm: {$firm_info['name']}\n";
    $unc_pos = array();
    $res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`name` FROM `doc_base` ORDER BY `id`");
    while($pos_info = $res->fetch_assoc()) {
        $min = 0;
        $cnt = 0;
        $r = $db->query("SELECT `doc_list_pos`.`cnt`, `doc_list`.`type`, `doc_list`.`sklad` "
            . "FROM `doc_list` "
            . "INNER JOIN `doc_list_pos` ON `doc_list`.`id`=`doc_list_pos`.`doc` "
            . "WHERE `doc_list_pos`.`tovar`='{$pos_info['id']}' AND `doc_list`.`firm_id`={$firm_info['id']} "
            . "ORDER BY `doc_list`.`date`");
        while($doc_line = $r->fetch_assoc()) {
            switch($doc_line['type']) {
                case 1:
                    $cnt += $doc_line['cnt'];
                    break;
                case 2:
                    $cnt -= $doc_line['cnt'];
                    break;
            }
            $min = min($min, $cnt);
        }
        if($min<0) {
            echo "id:{$pos_info['id']}, {$pos_info['name']}: $min\n";
            $unc_pos[$pos_info['id']] = abs($min);
        }
    }
    
    if(count($unc_pos)) {
        $doc_data = array(
            'type'  => 1,
            'agent' => 1,
            'date'  => strtotime("2005-01-10 12:01:02"),
            'created'=> date("Y-m-d H:i:s"),
            'ok'    => time(),
            'sklad' => 1            
        );
    }
}