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


/// Удаление плохих email адресов из аккаунтов

$c=explode('/',__FILE__);$base_path='';
for($i=0;$i<(count($c)-2);$i++)	$base_path.=$c[$i].'/';
include_once("$base_path/config_cli.php");
require_once($CONFIG['cli']['location']."/core.cli.inc.php");
require_once($CONFIG['location']."/web/include/doc.core.php");


function process_email($email) {
    global $db;
    $s_email = $db->real_escape_string($email);
    $res = $db->query("SELECT `id`, `name` FROM `doc_agent` WHERE `email`='$s_email'");
    if($res->num_rows) {
        $data = $res->fetch_assoc();
        echo "$email: {$data['name']}\n";
        $db->query("UPDATE `doc_agent` SET `email`='' WHERE `id`='{$data['id']}'");
    }
    $res = $db->query("SELECT `doc_agent`.`id` AS `agent_id`, `doc_agent`.`name`, `agent_contacts`.`id`"
        . " FROM `agent_contacts`"
        . " LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`agent_contacts`.`agent_id`"
        . " WHERE `email`='$s_email'");
    if($res->num_rows) {
        $data = $res->fetch_assoc();
        echo "$email: {$data['name']}\n";
        $db->query("DELETE FROM `agent_contacts` WHERE `id`='{$data['id']}'");
    }

    $res = $db->query("SELECT `id`, `real_name`, `name` FROM `users` WHERE `reg_email`='$s_email'");
    if($res->num_rows) {
        $data = $res->fetch_assoc();
        echo "$email: {$data['name']} / {$data['real_name']}\n";
        $db->query("UPDATE `users` SET `reg_email`='', `reg_email_confirm`='' WHERE `id`='{$data['id']}'");
    }
}

$file = file("badmails.txt");
foreach($file as $fline) {
    $email = trim($fline);
    process_email($email);
}