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

// Ежедневный запуск в 0:01 
$c=explode('/',__FILE__);$base_path='';
for($i=0;$i<(count($c)-2);$i++)	$base_path.=$c[$i].'/';
include_once("$base_path/config_cli.php");
require_once($CONFIG['cli']['location']."/core.cli.inc.php");

try {

	// Очистка от неподтверждённых пользователей
	if ($CONFIG['auto']['user_del_days'] > 0) {
		$tim = time();
		$dtim = time() - 60 * 60 * 24 * $CONFIG['auto']['user_del_days'];
		$dtim = date('Y-m-d H:i:s', $dtim);
		$res = $db->query("SELECT `id` FROM `users`
			LEFT JOIN `users_openid` ON `users_openid`.`user_id`=`users`.`id`
			WHERE `users_openid`.`user_id` IS NULL AND `users`.`reg_date`<'$dtim' AND `users`.`reg_email_confirm`!='1' AND `reg_phone_confirm`!='1'");
		while ($nxt = $res->fetch_row())
			$db->query("DELETE FROM `users` WHERE `id`='$nxt[0]'");
	}

// Перемещение непроведённых реализаций на начало текущего дня
	if ($CONFIG['auto']['move_nr_to_end'] == true) {
		$end_day = strtotime(date("Y-m-d 00:00:01"));
		$db->query("UPDATE `doc_list` SET `date`='$end_day' WHERE `type`='2' AND `ok`='0'");
	}

// Перемещение непроведённых заявок на начало текущего дня
	if ($CONFIG['auto']['move_no_to_end'] == true) {
		$end_day = strtotime(date("Y-m-d 00:00:02"));
		$db->query("UPDATE `doc_list` SET `date`='$end_day' WHERE `type`='3' AND `ok`='0'");
	}

// Очистка счётчика посещений от старых данных
	$tt = time() - 60 * 60 * 24 * 10;
	$db->query("DELETE FROM `counter` WHERE `date` < '$tt'");

// Загрузка курсов валют
	$data = file_get_contents("http://www.cbr.ru/scripts/XML_daily.asp");
	$doc = new DOMDocument('1.0');
	$doc->loadXML($data);
	$doc->normalizeDocument();
	$valutes = $doc->getElementsByTagName('Valute');
	foreach ($valutes as $valute) {
		$name = $value = 0;
		foreach ($valute->childNodes as $val) {
			switch ($val->nodeName) {
				case 'CharCode':
					$name = $val->nodeValue;
					break;
				case 'Value':
					$value = $val->nodeValue;
					break;
			}
		}
		$value = round(str_replace(',', '.', $value), 4);
		$db->query("UPDATE `currency` SET `coeff`='$value' WHERE `name`='$name'");
	}
// Расчет оборота агентов
	if($CONFIG['auto']['acc_agent_time']) {
		$acc = array();
		$time_start = time() - $CONFIG['auto']['acc_agent_time']*60*60*24;
		$res = $db->query("SELECT `agent`, `sum` FROM `doc_list` WHERE `date`>='$time_start' AND (`type`='1' OR `type`='4' OR `type`='6') AND `ok`>0
			AND `agent`>0 AND `sum`>0");
		while($line = $res->fetch_assoc()) {
			if(isset($acc[$line['agent']]))
				$acc[$line['agent']] += $line['sum'];
			else $acc[$line['agent']] = $line['sum'];
		}
		foreach($acc as $agent => $sum) {
			$db->update('doc_agent', $agent, 'avg_sum', $sum);
		}
	}
} catch (Exception $e) {
	mailto($CONFIG['site']['doc_adm_email'], "Error in daily.php", $e->getMessage());
	echo $e->getMessage() . "\n";
}


?>
