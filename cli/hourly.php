#!/usr/bin/php
<?php
//      MultiMag v0.2 - Complex sales system
//
//      Copyright (C) 2005-2015, BlackLight, TND Team, http://tndproject.org
//
//      This program is free software: you can redistribute it and/or modify
//      it under the terms of the GNU Affero General Public License as
//      published by the Free Software Foundation, either version 3 of the
//      License, or (at your option) any later version.
//
//      This program is distributed in the hope that it will be useful,
//      but WITHOUT ANY WARRANTY; without even the implied warranty of
//      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//      GNU Affero General Public License for more details.
//
//      You should have received a copy of the GNU Affero General Public License
//      along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

// Ежечасный запуск в XX:58 
$c = explode('/', __FILE__);
$base_path = '';
for ($i = 0; $i < (count($c) - 2); $i++) {
    $base_path.=$c[$i] . '/';
}

include_once("$base_path/config_cli.php");
require_once($CONFIG['cli']['location'] . "/core.cli.inc.php");
//require_once($CONFIG['location']."/common/datecalcinterval.php");

$verbose = 0;

try {
    if ($_SERVER['argc'] > 1) {
        for ($i = 0; $i < $_SERVER['argc']; $i++) {
            switch ($_SERVER['argv'][$i]) {
                case '-v':
                case '--verbose':
                    $verbose = 1;
                    break;
            }
        }
    }
   
    // Информирование о резком изменении цен
    if ($CONFIG['auto']['badpricenotify']) {
        if ($verbose) {
            echo "Информирование о резком изменении цен\n";
        }
        $action = new \actions\BadPriceNotify($CONFIG, $db);
        $action->run();       
    }
    
    // Информирование об изменении цен
    if ($CONFIG['auto']['chpricenotify']) {
        if ($verbose) {
            echo "Информирование о изменении цен\n";
        }
        $action = new \actions\chPriceNotify($CONFIG, $db);
        $action->run();       
    }
    
} catch (XMPPHP_Exception $e) {
    if ($CONFIG['site']['admin_email']) {
        mailto($CONFIG['site']['admin_email'], "XMPP exception in daily.php", $e->getMessage());
    }
    echo "XMPP exception: " . $e->getMessage() . "\n";
} catch (mysqli_sql_exception $e) {
    if ($CONFIG['site']['admin_email']) {
        mailto($CONFIG['site']['admin_email'], "Mysql exception in daily.php", $e->getMessage());
    }
    echo "Mysql exception: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    if ($CONFIG['site']['admin_email']) {
        mailto($CONFIG['site']['admin_email'], "General exception in daily.php", $e->getMessage());
    }
    echo "General exception: " . $e->getMessage() . "\n";
}
