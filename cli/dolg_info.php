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

require_once($CONFIG['site']['location']."/core.php");
require_once($CONFIG['site']['location']."/include/doc.core.php");

$mail_text = array();
$sum_dolga = array();

$res = $db->query("SELECT `id`, `name`, `responsible` FROM `doc_agent` ORDER BY `name`");
while ($nxt = $res->fetch_row()) {
	$dolg = agentCalcDebt($nxt[0], 0);
	if ($dolg > 0) {
		$dolg = abs($dolg);
		$sum_dolga[$nxt[2]]+=$dolg;
		$dolg = sprintf("%0.2f", $dolg);
		$a_name = html_entity_decode($nxt[1], ENT_QUOTES, "UTF-8");
		$mail_text[$nxt[2]].="Агент $a_name (id:$nxt[0]) должен нам $dolg рублей\n";
	}
}

try {
	require_once($CONFIG['location'] . '/common/XMPPHP/XMPP.php');
	$xmppclient = new XMPPHP_XMPP($CONFIG['xmpp']['host'], $CONFIG['xmpp']['port'], $CONFIG['xmpp']['login'], $CONFIG['xmpp']['pass'], 'xmpphp', '');
	$xmpp_connected = 0;

	$res = $db->query("SELECT `users`.`id`, `users`.`name`, `users`.`reg_email`, `users`.`jid`, `users`.`reg_email_subscribe`, `users`.`reg_email_confirm`,
			`users`.`real_name`, `users_worker_info`.`worker_email`, `users_worker_info`.`worker_jid`, `users_worker_info`.`worker_real_name`
		FROM `users`
		LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`users_id`
		WHERE `users_worker_info`.`worker`>0");
	while ($nxt = $res->fetch_assoc()) {
		if ($mail_text[$nxt['id']]) {
			$dolg = sprintf("%0.2f", $sum_dolga[$nxt['id']]);
			$name = $nxt['worker_real_name'];
			if(!$name)	$name = $nxt['real_name'];
			if(!$name)	$name = $nxt['name'];
			$text = "Уважаемый(ая) $name!\nНекоторые из Ваших клиентов, для которых Вы являетесь ответственным сотрудником, имеют непогашенные долги перед нашей компанией на общую сумму {$dolg} рублей.\nНеобходимо в кратчайший срок решить данную проблему!\n\nВот список этих клиентов:\n" . $mail_text[$nxt['id']] . "\n\nПожалуйста, не откладывайте решение проблемы на длительный срок!";
			if($nxt['worker_email'])
				mailto($nxt['worker_email'], "Ваши долги", $text);
			else if($nxt['email'] && $nxt['reg_email_subscribe'] && $nxt['reg_email_confirm']=='1')
				mailto($nxt['email'], "Ваши долги", $text);
			if ($nxt['worker_jid'])
				$jid = $nxt['worker_jid'];
			else if($nxt['jid'])
				$jid = $nxt['jid'];
			if($jid) {
				if (!$xmpp_connected) {
					$xmppclient->connect();
					$xmppclient->processUntil('session_start');
					$xmppclient->presence();
					$xmpp_connected = 1;
				}
				$xmppclient->message($jid, $text);
				echo "\nСообщение было отправлено через XMPP!";
			}
			echo $text . "\n\n\n\n";
		}
	}
	$xmppclient->disconnect();
} catch (XMPPHP_Exception $e) {
	echo"\nНевозможно отправить сообщение XMPP";
} catch (Exception $e) {
	echo"Ошибка отправки почты!" . $e->getMessage();
}

?>