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


$c=explode('/',__FILE__);$base_path='';
for($i=0;$i<(count($c)-2);$i++)	$base_path.=$c[$i].'/';
include_once("$base_path/config_cli.php");
require_once($CONFIG['cli']['location']."/core.cli.inc.php");

$tim=time();
$i_time=time()-60*60*24*$CONFIG['resp_clear']['info_time'];
$c_time=time()-60*60*24*$CONFIG['resp_clear']['clear_time'];

$info_mail='';

if ($CONFIG['resp_clear']['info_time']) {
	$res = $db->query("SELECT `doc_agent`.`id`, `doc_agent`.`name`, `doc_agent`.`responsible`, `users`.`name`, `users`.`reg_email` FROM `doc_agent`
	LEFT JOIN `users` ON `users`.`id`=`doc_agent`.`responsible`
	WHERE `doc_agent`.`id` NOT IN (SELECT `agent` FROM `doc_list` WHERE `date`>='$i_time' ) AND `doc_agent`.`responsible`>'0'");
	$resp_info = array();
	$resp_mail = array();

	if ($res->num_rows > 0)
		$info_mail.="По следующим агентам, ассоциированным с ответственными, не было движения более {$CONFIG['resp_clear']['info_time']} дней:\n";

	while ($nxt = $res->fetch_row()) {
		$info_mail.='id:' . str_pad($nxt[0], 6, ' ', STR_PAD_LEFT) . ' - ' . $nxt[1] . " (ответственный - $nxt[3] (id:$nxt[2])\n";
		if (!isset($resp_info[$nxt[2]]))
			$resp_info[$nxt[2]] = '';
		$resp_info[$nxt[2]].='id:' . str_pad($nxt[0], 6, ' ', STR_PAD_LEFT) . ' - ' . $nxt[1] . "\n";
		$resp_mail[$nxt[2]] = $nxt[4];
	}

	foreach ($resp_info as $id => $resp) {
		$mail_text = "По следующим агентам, для которых Вы назначены ответственным менеджером, , не было движения более {$CONFIG['resp_clear']['info_time']} дней:\n\n" . $resp . "\nЕсли Вы не примите меры, то через некоторое время Вы перестанете быть ответственным менеджером этого агента!\n\nВы получили это письмо, так как являетесь ответственным менеджером.\nЭто письмо сгенерированно автоматически системой оповещения сайта {$CONFIG['site']['name']}.\nОтвечать на него не нужно.";

		try {
			mailto($resp_mail[$id], $CONFIG['site']['name'] . " - Информация для ответственного сотрудника", $mail_text);
			echo "Почта отправлена!";
		} catch (Exception $e) {
			echo"Ошибка отправки почты!" . $e->getMessage();
		}
	}
}

try {
	if ($CONFIG['resp_clear']['clear_time']) {
		$res = $db->query("SELECT `doc_agent`.`id`, `doc_agent`.`name`, `doc_agent`.`responsible`, `users`.`name`, `users`.`reg_email` FROM `doc_agent`
			LEFT JOIN `users` ON `users`.`id`=`doc_agent`.`responsible`
			WHERE `doc_agent`.`id` NOT IN (SELECT `agent` FROM `doc_list` WHERE `date`>='$c_time' ) AND `doc_agent`.`responsible`>'0'");
		if ($res->num_rows > 0)
			$info_mail.="\n\nУ следующих агентов были сняты ассоциации с ответственным, т.к. не было движения более {$CONFIG['resp_clear']['clear_time']} дней:\n";
		while ($nxt = $res->fetch_row()) {
			$info_mail.='id:' . str_pad($nxt[0], 6, ' ', STR_PAD_LEFT) . ' - ' . $nxt[1] . " (был ответственный - $nxt[3] (id:$nxt[2])\n";
			$db->query("UPDATE `doc_agent` SET `responsible`='0' WHERE `id`='$nxt[0]'");
		}
	}
} catch (Exception $e) {
	$info_mail.=$e->getMessage();
	echo $e->getMessage();
}

if ($info_mail) {
	$mail_text = $info_mail . "\n\nВы получили это письмо, так как ваш адрес указан в настройках сайта.\nЭто письмо сгенерированно автоматически системой оповещения сайта {$CONFIG['site']['name']}.\nОтвечать на него не нужно.";

	try {
		mailto($CONFIG['resp_clear']['info_mail'], $CONFIG['site']['name'] . " - Информация о неактивных клиентах", $mail_text);
		echo "Почта отправлена!";
	} catch (Exception $e) {
		echo"Ошибка отправки почты!" . $e->getMessage();
	}
}
?>