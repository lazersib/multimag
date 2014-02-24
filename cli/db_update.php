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

/// Наложение патчей на активную базу данных при обновлении, либо вручную
$c=explode('/',__FILE__);$base_path='';
for($i=0;$i<(count($c)-2);$i++)	$base_path.=$c[$i].'/';
include_once("$base_path/config_cli.php");
require_once($CONFIG['cli']['location']."/core.cli.inc.php");

unset($CONFIG['backup']['dirs']);
include_once($CONFIG['cli']['location']."/backup.php");

function applyPatch($patch) {
	global $db;
	$file = file_get_contents($patch);
	if (!$file)
		throw new Exception("Не удаётся открыть файл патча!");
	$queries = explode(";", $file);
	$db->query("START TRANSACTION");
	foreach ($queries as $query) {
		if (strlen(trim($query)) == 0)
			continue;
		$db->query($query);
	}
	$db->query("COMMIT");
}

try {
	$patches = scandir($CONFIG['location'] . "/db_patches/");
	if (!is_array($patches))
		throw new Exception("Не удалось получить список файлов патчей!");
	for ($i = 0; $i < 1000; $i++) {
		$res = $db->query("SELECT `version` FROM `db_version`");
		if($res->num_rows)
			list($db_version) = $res->fetch_row();
		else	$db_version = 0;
		if ($db_version != MULTIMAG_REV) {
			foreach ($patches as $patch) {
				if (strpos($patch, '~') !== false)
					continue;
				if (strpos($patch, $db_version) === 0) {
					echo "Накладываем патч $patch\n";
					applyPatch($CONFIG['location'] . "/db_patches/$patch");
					break;
				}
			}
		}
		else	break;
		
	}
} catch (Exception $e) {
	echo "\n\n==============================================\nОШИБКА ОБНОВЛЕНИЯ БАЗЫ: " . $e->getMessage() . "\n==============================================\n\n";
}

?>
