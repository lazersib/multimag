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

echo'"ID","Store","Count","Price"',"\n";

$res = $db->query("SELECT `id` FROM `doc_sklady` ORDER BY `id`");
$stores = array();
while($line = $res->fetch_assoc()) {
    $stores[$line['id']] = $line['id'];
}

$res = $db->query("SELECT `id` FROM `doc_vars`");
$firms = array();
while($line = $res->fetch_assoc()) {
    $firms[$line['id']] = $line['id'];
}

$res = $db->query("SELECT `id`, `name` FROM `doc_base` ORDER BY `id`");
while($nxt = $res->fetch_assoc()) {
    $str = "{$nxt['id']},\"{$nxt['name']}\"";
    foreach ($stores as $store_id) {
        $cnt = getStoreCntOnDate($nxt['id'], $store_id, $unix_date);
        $price = getInCost($nxt['id'], $unix_date);
        if($cnt!=0) {
            echo "{$nxt['id']},$store_id,$cnt,$price\n";
        }
        
    }
}
