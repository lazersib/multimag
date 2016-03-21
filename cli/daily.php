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

    if ($verbose) {
        echo "Очистка счётчика посещений...\n";
    }
    // Очистка счётчика посещений от старых данных
    $tt = time() - 60 * 60 * 24 * 10;
    $db->query("DELETE FROM `counter` WHERE `date` < '$tt'");

    // Очистка кеша изображений
    if ($CONFIG['auto']['clear_image_cahe'] > 0) {
        if ($verbose) {
            echo "Очистка кеша изображений...\n";
        }
        $action = new Actions\clearCache($CONFIG, $db);
        $action->run();
    }
    
    // Очистка от неподтверждённых пользователей
    if ($CONFIG['auto']['user_del_days'] > 0) {
        if ($verbose) {
            echo "Очистка от неподтверждённых пользователей...\n";
        }
        $action = new Actions\UserFree($CONFIG, $db);
        $action->run();
    }

    // Перемещение непроведённых реализаций на начало текущего дня
    if ($CONFIG['auto']['move_nr_to_end'] || $CONFIG['auto']['move_no_to_end'] || $CONFIG['auto']['move_ntp_to_end']) {
        if ($verbose) {
            echo "Перемещение непроведённых реализаций на начало текущего дня...\n";
        }
        $action = new Actions\DocMove($CONFIG, $db);
        $action->run();
    }

    // Загрузка курсов валют
    if ($CONFIG['auto']['update_currency']) {
        if ($verbose) {
            echo "Загрузка курсов валют...\n";
        }
        $action = new Actions\CurrencyUpdater($CONFIG, $db);
        $action->run();
    }

    // Расчет оборота агентов
    if ($CONFIG['auto']['agent_calc_avgsum']) {
        if ($verbose) {
            echo "Расчет оборота агентов...\n";
        }
        $action = new Actions\AgentCalcAvgsum($CONFIG, $db);
        $action->run();
    }

    // Информирование ответственных сотрудников о задолженностях его агентов при помощи email и jabber
    if ($CONFIG['auto']['resp_debt_notify']) {
        if ($verbose) {
            echo "Информирование ответственных сотрудников о задолженностях его агентов при помощи email и jabber...\n";
        }
        $action = new Actions\RespDebtNotify($CONFIG, $db);
        $action->run();
    }

    // Информирование агентов об их накопительных скидках при помощи email
    if ($CONFIG['auto']['agent_discount_notify']) {
        if ($verbose) {
            echo "Информирование агентов об их накопительных скидках при помощи email\n";
        }
        $action = new Actions\AgentDiscountNotify($CONFIG, $db);
        $action->run();
    }
    
    // Информирование о красных событиях в документах
    if ($CONFIG['auto']['red_event_doc_notify']) {
        if ($verbose) {
            echo "Информирование о красных событиях в документах\n";
        }
        $action = new actions\redEventDocNotify($CONFIG, $db);
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
