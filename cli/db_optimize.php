#!/usr/bin/php
<?php
//	MultiMag v0.1 - Complex sales system
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
require_once("$base_path/config_cli.php");

require_once($CONFIG['cli']['location']."/core.cli.inc.php");
require_once($CONFIG['location']."/common/async/dbcheck.php");
try {
	$worker = new DbCheckWorker(0);
	$worker->run();
} catch (Exception $e) {
	if ($worker) {
		try {
			$worker->finalize();
		} catch (Exception $e) {
			echo $e->getMessage() . "\n";
		}
	}

	echo $e->getMessage() . "\n";
	try {
		$mail_text = "При автоматической проверке базы данных сайта найдены следующие проблемы:\n****\n\n" . $e->getMessage() . "\n\n****\nНеобходимо исправить найденные ошибки!";

		mailto($CONFIG['site']['doc_adm_email'], "DB check report", $mail_text);
		echo "Почта отправлена!";
		$db->query("UPDATE `variables` SET `corrupted`='1'");
	} catch (Exception $e) {
		echo"Ошибка отправки почты!" . $e->getMessage();
	}
}

?>