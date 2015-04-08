#!/usr/bin/php
<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2015, BlackLight, TND Team, http://tndproject.org
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

$c = explode('/', __FILE__);
$base_path = '';
for ($i = 0; $i < (count($c) - 2); $i++)
	$base_path.=$c[$i] . '/';
require_once("$base_path/config_cli.php");
require_once($CONFIG['cli']['location'] . "/core.cli.inc.php");

try {
    $res = $db->query("SELECT `id`, `task` FROM `async_workers_tasks` WHERE `needrun`=1 LIMIT 1");
    while ($ainfo = $res->fetch_assoc()) {
        $db->query("UPDATE `async_workers_tasks` SET `needrun`=0, `textstatus`='Запускается' WHERE `id`='{$ainfo['id']}'");
        require_once($CONFIG['location'] . "/common/async/" . strtolower($ainfo['task']) . ".php");
        $classname = '\\async\\'.$ainfo['task'];
        $worker = new $classname($ainfo['id']);
        $worker->run();
        $worker->end();
    }
} catch (Exception $e) {
    if ($worker) {
        try {
            $worker->finalize();
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            $db->query("UPDATE `async_workers_tasks` SET `needrun`=0, `textstatus`='" . $e->getMessage() . "' WHERE `id`='{$ainfo['id']}'");
        }
    }
    echo $e->getMessage();
    $db->query("UPDATE `async_workers_tasks` SET `needrun`=0, `textstatus`='" . $db->real_escape_string($e->getMessage()) . "' WHERE `id`='{$ainfo['id']}'");
}
