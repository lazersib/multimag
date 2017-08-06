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

$c = explode('/', __FILE__);
$base_path = '';
for ($i = 0; $i < (count($c) - 2); $i++) {
    $base_path .= $c[$i] . '/';
}
require_once("$base_path/config_cli.php");

require_once($CONFIG['cli']['location'] . "/core.cli.inc.php");
require_once($CONFIG['location'] . "/common/priceloader.xls.php");
require_once($CONFIG['location'] . "/common/priceloader.ods.php");

set_time_limit(60 * 120); // Выполнять не более 120 минут
$start_time = microtime(TRUE);

if(!\cfg::get('price', 'dir')) {
    exit(-1);
}

$mail_text = '';
$status_id = 0;

/// Фиктивный класс для анализатора прайсов. Надо переделать архитектуру так, чтобы он не требовался
class Foo {

    function addContent($t) {
        return;
    }

    function msg($t) {
        return;
    }

}

$tmpl = new Foo();

class priceAnalyser {
    
    /// Загрузить файлы прайсов в базу
    public function loadPricesToDB() {
        global $mail_text;
        $price_dir = \cfg::get('price', 'dir');
        if (!file_exists($price_dir)) {
            throw new \Exception("Каталог с прайсами ($price_dir) не существует");
        }
        if (!is_dir($price_dir)) {
            throw new \Exception("Каталог с прайсами ($price_dir) не является каталогом");
        }
        $dh = opendir($price_dir);
        if (!$dh) {
            throw new \Exception("Не удалось открыть каталог с прайсами ($price_dir)");
        }

        while (false !== ($filename = readdir($dh))) {
            $path_info = pathinfo($filename);
            $ext = isset($path_info['extension']) ? strtolower($path_info['extension']) : '';
            if ($ext == 'xls') {
                $loader = new XLSPriceLoader($price_dir . '/' . $filename);
            } else if ($ext == 'ods') {
                $loader = new ODSPriceLoader($price_dir . '/' . $filename);
            } else {
                continue;
            }
            $f = 0;
            $firm_array = $loader->detectSomeFirm();
            $loader->setInsertToDatabase();
            $msg = "File: $filename\n";
            foreach ($firm_array as $firm) {
                echo "{$msg}Firm_id: {$firm['firm_id']} ({$firm['firm_name']}), ";
                $loader->useFirmAndCurency($firm['firm_id'], $firm['curency_id']);
                $count = $loader->Run();
                echo "Parsed ($count items)!\n";
                $f = 1;
            }
            if ($f == 0) {
                $msg .= "соответствий не найдено. Прайс не обработан.";
                $mail_text .= "Анализ прайсов: $msg\n";
            } else {
                unlink($price_dir . '/' . $filename);
            }
        }
    }
    
    /// Подготовить таблицы для анализа
    public function prepareTables() {
        global $db;
        echo "Начинаем анализ...\n";
        $db->query("UPDATE `price` SET `seeked`='0'");
        $db->query("CREATE TABLE IF NOT EXISTS `parsed_price_tmp` (
            `id` int(11) NOT NULL auto_increment,
            `firm` int(11) NOT NULL,
            `pos` int(11) NOT NULL,
            `cost` decimal(10,2) NOT NULL,
            `nal` varchar(10) NOT NULL,
            `from` int(11) NOT NULL,
            `selected` TINYINT(4) NOT NULL ,
            UNIQUE KEY `id` (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");

        if (\cfg::get('price', 'mark_matched')) {
            $db->query("DROP TABLE IF EXISTS `price_seeked`");
            $db->query("CREATE TABLE IF NOT EXISTS `price_seeked` (
                    `id` int(11) NOT NULL,
                    `seeked` int(11) NOT NULL,
                    UNIQUE KEY `id` (`id`)
                    ) ENGINE=Memory");
        }
    }
    
    // Скорректировать таблицы для обновления цен
    public function fixTables() {
        global $db;
        if (\cfg::get('price', 'mark_matched')) {
            $db->query("UPDATE `price`,`price_seeked` SET `price`.`seeked`=`price_seeked`.`seeked`  WHERE `price`.`id`=`price_seeked`.`id`");
        }

        $db->query("ALTER TABLE `parsed_price_tmp`
            ADD INDEX ( `firm` ),
            ADD INDEX ( `pos` ),
            ADD INDEX ( `cost` ),
            ADD INDEX ( `nal` ),
            ADD INDEX ( `from` )");

        $db->query("DROP TABLE `parsed_price`");
        $db->query("RENAME TABLE `parsed_price_tmp` TO `parsed_price`");

        echo "Анализ прайсов завершен успешно!\n";
    }
    
    /// Обновление цен по прайсам
    public function updatePrices() {
        global $db;
        $res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`cost` AS `price`, `doc_base`.`name`
                , (SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `allcnt`
                , `doc_base`.`group` AS `group_id`
            FROM `doc_base`");
        $row = $res->num_rows;
        $old_p = $i = 0;
        while ($nxt = $res->fetch_assoc()) {
            $i++;
            $p = floor($i / $row * 100);
            if ($old_p != $p) {
                $old_p = $p;
            }
            $nxt['allcnt'] = round($nxt['allcnt'], 5);

            $mincost = 99999999;
            $ok_line = 0;
            $rrp = 0;
            $rs = $db->query("SELECT `parsed_price`.`cost`,`firm_info`.`type`, `firm_info_group`.`id`, `parsed_price`.`id`, `firm_info`.`rrp`, `firm_info`.`id`
                    FROM  `parsed_price`
                    LEFT JOIN `firm_info` ON `firm_info`.`id`=`parsed_price`.`firm`
                    LEFT JOIN `firm_info_group` ON `firm_info_group`.`firm_id`=`parsed_price`.`firm` AND `firm_info_group`.`group_id`='{$nxt['group_id']}'
                    WHERE `parsed_price`.`pos`='{$nxt['id']}' AND `parsed_price`.`cost`>'0' AND `parsed_price`.`nal`!='' AND `parsed_price`.`nal`!='-' AND `parsed_price`.`nal`!='call' AND `parsed_price`.`nal`!='0'");
            while ($nx = $rs->fetch_row()) {
                if ($nx[4]) {
                    $rrp = $nx[0];
                    $ok_line = $nx[3];
                    break;
                }
                if (($nx[1] == 1 || ($nx[1] == 2 && $nx[2] != '')) && $mincost > $nx[0]) {
                    $mincost = $nx[0];
                    $ok_line = $nx[3];
                }
            }

            if ($ok_line == 0) {
                $mincost = 0;
            }

            if ($rrp) {
                $pc = PriceCalc::getInstance();
                $cost_id = $pc->getDefaultPriceId();
                $cres = $db->query("SELECT `value` FROM `doc_base_cost` WHERE `pos_id`='{$nxt['id']}' AND `cost_id`='$cost_id'");
                if ($cres->num_rows) {
                    list($s_cost) = $cres->fetch_row();
                }

                if ($s_cost != $rrp) {
                    if ($s_cost) {
                        $db->query("UPDATE `doc_base_cost` SET `value`='$rrp' AND `rrp_firm_id`='$nx[5]' WHERE `pos_id`='{$nxt['id']}'
                                                    AND `cost_id`='$cost_id'");
                    } else {
                        $db->query("INSERT INTO `doc_base_cost` (`cost_id`, `pos_id`, `type`, `value`, `accuracy`, `direction`, `rrp_firm_id`)
                                            VALUES ('$cost_id', '{$nxt['id']}', 'fix', '$rrp', '2', '0', '$nx[5]')");
                    }
                    echo "У наименования ID:{$nxt['id']} изменена РОЗНИЧНАЯ цена с $s_cost на $rrp. Наименование: {$nxt['name']}\n";
                }
                continue;
            }

            if ($nxt['allcnt'] == 0) {
                $db->query("UPDATE `parsed_price` SET `selected`='1' WHERE `id`='$ok_line'");
                if ($nxt['price'] != $mincost && $mincost > 0) {
                    $txt = "У наименования ID:{$nxt['id']} изменена цена с {$nxt['price']} на $mincost. Наименование: {$nxt['name']}, в наличиии: {$nxt['allcnt']}\n";
                    $db->query("UPDATE `doc_base` SET `cost`='$mincost', `cost_date`=NOW() WHERE `id`='{$nxt['id']}'");
                    echo $txt;
                    if ($nxt['price']) {
                        $pp = ($nxt['price'] - $mincost) * 100 / $nxt['price'];
                    } else {
                        $pp = -1000;
                    }
                    if ($pp > \cfg::get('price', 'notify_down') && \cfg::get('price', 'notify_down')) {
                        $mail_text .= $txt;
                    }
                    if (($pp * (-1)) > \cfg::get('price', 'notify_up') && \cfg::get('price', 'notify_up')) {
                        $mail_text .= $txt;
                    }
                }
            }
        }
        $db->query("ALTER TABLE `parsed_price` ADD INDEX ( `selected` )");
    }
}

function log_write($dir, $msg) {
    $f_log = fopen($dir . '/load.log', 'a');
    fprintf($f_log, date("Y.m.d H:i:s ") . $msg . "\n");
    fclose($f_log);
    echo $msg . "\n";
}

function forked_match_process($nproc, $limit, $res, $db) {
    global $mail_text;
    $i = 0;
    while ($nxt = $res->fetch_row()) {
        $i++;
        $res1 = $db->query("SELECT `id`, `search_str`, `replace_str` FROM `prices_replaces`");
        while ($nxt1 = $res1->fetch_row()) {
            $nxt[2] = str_replace("{{{$nxt1[1]}}}", $nxt1[2], $nxt[2]);
            $nxt[5] = str_replace("{{{$nxt1[1]}}}", $nxt1[2], $nxt[5]);
        }

        $a = preg_match("/$nxt[2]/", ' ');
        $b = preg_match("/$nxt[5]/", ' ');
        if ($a === FALSE || $b === FALSE) {
            $mail_text .= "Анализ прайсов: регулярное выражение позиции id:$nxt[3] (для $nxt[0]) составлено с ошибкой! Это значительно снижает быстродействие, и может вызвать сбой!\n";
            continue;
        }

        $str_array = preg_split("/( OR | AND )/", $nxt[1], -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $sql_add = '';
        $conn = '';
        $c = 1;
        foreach ($str_array as $str_l) {
            if ($c) {
                $str_l_sql = $db->real_escape_string($str_l);
                $sql_add .= " $conn (`price`.`name` LIKE '%$str_l_sql%' OR `price`.`art` LIKE '%$str_l_sql%')";
            } else {
                $conn = $str_l;
            }
            $c = 1 - $c;
        }
        $rs = $db->query("SELECT `price`.`id`, `price`.`name`, `price`.`cost`, `price`.`firm`, `price`.`nal`, `firm_info`.`coeff`, `currency`.`coeff`, `price`.`art`, `price`.`currency`
		FROM `price`
		INNER JOIN `firm_info` ON `firm_info`.`id`=`price`.`firm`
		LEFT JOIN `currency` ON `currency`.`id`=`price`.`currency`
		WHERE $sql_add");
        while ($nx = $rs->fetch_row()) {
            $a = preg_match("/$nxt[2]/", $nx[1]);
            $b = preg_match("/$nxt[2]/", $nx[7]);

            if ($a || $b) {
                if ($nxt[5]) {
                    $a = preg_match("/$nxt[5]/", $nx[1]);
                    $b = preg_match("/$nxt[5]/", $nx[7]);
                    if ($a || $b) {
                        continue;
                    }
                }

                if ($nx[5] == 0) {
                    $nx[5] = 1;
                }
                if ($nx[6] == 0) {
                    $nx[6] = 1;
                }
                $cost = $nx[2] * $nx[5] * $nx[6];
                $db->query("INSERT INTO `parsed_price_tmp` (`firm`, `pos`, `cost`, `nal`, `from`)
				VALUES ('$nx[3]', '$nxt[3]', '$cost', '$nx[4]', '$nx[0]' )");

                if (\cfg::get('price', 'mark_matched')) {
                    if (\cfg::get('price', 'mark_doubles')) {
                        $db->query("INSERT INTO `price_seeked` VALUES ($nx[0], 1) ON DUPLICATE KEY UPDATE `seeked`=`seeked`+1");
                    } else {
                        $db->query("INSERT IGNORE INTO `price_seeked` VALUES ($nx[0], 1)");
                    }
                }
            }
        }
        if ($i > $limit) {
            break;
        }
    }
}

function mysql_reconnect() {
    $db = new MysqiExtended(\cfg::get('mysql', 'host'), \cfg::get('mysql', 'login'), \cfg::get('mysql', 'pass'), \cfg::get('mysql', 'db'));

    if ($db->connect_error) {
        die("Ошибка соединения с базой данных");
    }

    // Включаем автоматическую генерацию исключений для mysql
    mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);

    if (!$db->set_charset("utf8")) {
        die("Невозможно задать кодировку соединения с базой данных: " . $db->error);
    }
    return $db;
}

function parallel_match() {
    global $a_start_time, $db;
    $res = $db->query("SELECT `doc_base`.`name`, `seekdata`.`sql`, `seekdata`.`regex`, `seekdata`.`id`, `doc_group`.`name`, `seekdata`.`regex_neg`
	FROM `seekdata`
	LEFT JOIN `doc_group` ON `doc_group`.`id`=`seekdata`.`group`
	LEFT JOIN `doc_base` ON `doc_base`.`id`=`seekdata`.`id`");
    $row = $res->num_rows;
    $a_start_time = microtime(TRUE);
    $db->close();

    // Подготовка к распараллеливанию
    $numproc = \cfg::get('price', 'numproc');  // Включая родительский
    if ($numproc < 1) {
        $numproc = 1;
    }
    if ($numproc > 128) {
        $numproc = 128;
    }
    $pids_array = array();
    $limit_per_child = floor($row / $numproc);

    for ($i = 0; $i < ($numproc - 1); $i++) {
        $pid = pcntl_fork();
        if ($pid == -1) {
            throw new \Exception("Параллельная обработка невозможна");
        }
        $pids_array[] = $pid;
        if (!$pid) {
            $res->data_seek($limit_per_child * $i);
            $db = mysql_reconnect();
            forked_match_process($i + 1, $limit_per_child, $res, $db);
            echo"Proc N$i end...\n";
            exit(0);
        }
    }
    $res->data_seek($limit_per_child * $i);
    $db = mysql_reconnect();
    forked_match_process(0, $row - $limit_per_child * $i, $res, $db);
    $status = 0;
    foreach ($pids_array as $pid) {
        pcntl_waitpid($pid, $status);
    }

    echo"Параллельная обработка завершена!\n";
}

try {
    
    $res = $db->query("SELECT `recalc_active` FROM `variables`");
    if ($res->num_rows) {
        list($lock) = $res->fetch_row();
    } else {
        $lock = 0;
    }
    if ($lock) {
        exit(-2);
    }
    
    $pran = new \priceAnalyser();    
    $pran->loadPricesToDB();
    $pran->prepareTables();
    
    parallel_match();

    $pran->fixTables();
    $pran->updatePrices();
    
} catch (Exception $e) {
    $txt = "Ошибка: " . $e->getMessage() . "\n";
    echo $txt;
    $mail_text .= $txt;
}

$work_time = microtime(TRUE) - $start_time;

$h = $m = 0;
$s = round($work_time * 100) / 100;
if ($s > 60) {
    $m = floor($s / 60);
    $s -= $m * 60;
}

if ($m > 60) {
    $h = floor($m / 60);
    $m -= $h * 60;
}

$text_time = 'Скрипт выполнен за ';
if ($h) {
    $text_time .= "$h часов ";
}
if ($m) {
    $text_time .= "$m минут ";
}
if ($s) {
    $text_time .= "$s секунд ";
}
$text_time .= " (всего $work_time секунд)\n";

echo $text_time;

// ===================== ОТПРАВКА ПОЧТЫ ===============================================================
if ($mail_text) {
    try {
        $mail_text = "При анализе прайс-листов произошло следующее:\n****\n\n" . $mail_text . "\n\n****\nНайденные ошибки желательно исправить в кратчайший срок!!\n\n$text_time";
        mailto(\cfg::get('site', 'admin_email'), "Price analyzer info", $mail_text);
        if (\cfg::get('site', 'admin_email') != \cfg::get('site', 'doc_adm_email')) {
            mailto(\cfg::get('site', 'doc_adm_email'), "Price analyzer info", $mail_text);
        }
        echo "Почта отправлена!";
    } catch (Exception $e) {
        echo"Ошибка отправки почты!" . $e->getMessage();
    }
} else {
    echo"Ошибок не найдено, не о чем оповещать!\n";
}
