#!/usr/bin/php
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

// Ежедневный запуск в 0:01 
$c = explode('/', __FILE__);
$base_path = '';
for ($i = 0; $i < (count($c) - 2); $i++) {
    $base_path.=$c[$i] . '/';
}

include_once("$base_path/config_cli.php");
require_once($CONFIG['cli']['location'] . "/core.cli.inc.php");
//require_once($CONFIG['location']."/common/datecalcinterval.php");

// Очистка счётчика посещений от старых данных
$tt = time() - 60 * 60 * 24 * 10;
$db->query("DELETE FROM `counter` WHERE `date` < '$tt'");

run_periodically_actions(\action::DAILY);