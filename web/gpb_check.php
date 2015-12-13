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

include_once("core.php");
include_once("include/doc.core.php");

header("Content-type: application/xml");

try {
    $pref = \pref::getInstance();
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="' . $pref->site_name . '"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Authentification cancel by user';
        exit();
    } else {
        if (@$_SERVER['PHP_AUTH_USER'] != @$CONFIG['gpb']['callback_login'] || @$_SERVER['PHP_AUTH_PW'] != @$CONFIG['gpb']['callback_pass'] || !@$CONFIG['gpb']['callback_pass'] || !@$CONFIG['gpb']['callback_login']) {
            header('WWW-Authenticate: Basic realm="' . $pref->site_name . '"');
            header('HTTP/1.0 401 Unauthorized');
            echo 'Authentification error';
            exit();
        }
    }


    $order_id = rcvint('o_order_id');
    $merch_id = request('merch_id');
    $trx_id = request('trx_id');
    $timestamp = request('ts');
    if (!$order_id) {
        throw new Exception("Не передан ID заказа");
    }
    if ($merch_id != $CONFIG['gpb']['merch_id']) {
        throw new Exception("Неверный ID магазина!");
    }
    if (!$trx_id) {
        throw new Exception("Не передан ID транзакции");
    }
    if (!$timestamp) {
        throw new Exception("Не передан timestamp");
    }
    if (!$CONFIG['gpb']['bank_id']) {
        throw new Exception("Не настроен id банка");
    }

    $res = $db->query("SELECT `doc_list`.`id`, `agent`, `sum`, `firm_id`, `contract` FROM `doc_list`
	WHERE `doc_list`.`id`='$order_id' AND `doc_list`.`type`='3'");
    if (!$res->num_rows) {
        throw new Exception("Заказ не найден!");
    }
    $order_info = $res->fetch_assoc();

    ///TODO: Надо проверить, была ли оплата
    /// так же надо проверить статус - возможна ли оплата
    $text = "order_id: $order_id\nmerch_id:$merch_id\ntrx_id:$trx_id\nts:$timestamp";
    $text_sql = $db->real_escape_string($text);

    // Создаём документ банк-приход:
    $res = $db->query("INSERT INTO `doc_list` (`type`, `agent`, `contract`, `p_doc`, `date`, `bank`, `sum`, `firm_id`, `comment` )
	VALUES ('24', '{$order_info['agent']}', '{$order_info['contract']}', '{$order_info['id']}', '" . time() . "', '{$CONFIG['gpb']['bank_id']}', '{$order_info['sum']}', '{$order_info['firm_id']}', '$text_sql')");
    $doc_bank_id = $db->insert_id;

    $sum = round($order_info['sum'] * 100);
    echo"<payment-avail-response><result><code>1</code><desc>OK, $text</desc></result>"
            . "<merchant-trx>$doc_bank_id</merchant-trx><purchase><shortDesc>Pay for order {$order_info['id']}</shortDesc>"
            . "<longDesc>{$pref->site_display_name} ({$pref->site_name}). Оплата за заказ {$order_info['id']}</longDesc>"
            . "<account-amount><id>{$CONFIG['gpb']['accounts_id']}</id><amount>$sum</amount><currency>643</currency><exponent>2</exponent></account-amount></purchase>"
        . "</payment-avail-response>";
} catch (Exception $e) {
    echo"<?xml version=\"1.0\" encoding=\"utf-8\"?><payment-avail-response><result><code>2</code><desc>" . html_out($e->getMessage()) . "</desc></result></payment-avail-response>";
}
