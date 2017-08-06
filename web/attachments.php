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

include_once("core.php");

function rewrite_input($att_id) {
	$arr = explode('/', $_SERVER['REQUEST_URI']);
	if (!is_array($arr))
		return $att_id;
	if (count($arr) < 4)
		return $att_id;
	return $arr[2];
}

$att_id = rewrite_input(request('att_id'));

settype($att_id, 'int');

$res = $db->query("SELECT `attachments`.`id`, `attachments`.`original_filename`
FROM `attachments`
WHERE `attachments`.`id`='$att_id'");

if ($res->num_rows < 1) {
	header('HTTP/1.0 404 Not Found');
	header('Status: 404 Not Found');
	$tmpl->msg("Файл не найден!", "err");
} else {
	$nxt = $res->fetch_row();
	if (!file_exists($CONFIG['site']['var_data_fs'] . '/attachments/' . $nxt[0])) {
		header('HTTP/1.0 404 Not Found');
		header('Status: 404 Not Found');
		$tmpl->msg("Файл не найден!", "err");
	} else {
		if ($CONFIG['site']['dowload_attach_speed'])
			$wait_length = 1000000 / ($CONFIG['site']['dowload_attach_speed'] / 2);
		else
			$wait_length = 150000;
		$filesize = filesize($CONFIG['site']['var_data_fs'] . '/attachments/' . $nxt[0]);
		header("Content-Type: application/octet-stream");
		header("Content-Disposition: attachment; filename=$nxt[1]");
		header("Content-Length: $filesize");
		$handle = fopen($CONFIG['site']['var_data_fs'] . '/attachments/' . $nxt[0], "rb");
		while (!feof($handle) && !connection_aborted()) {
			echo fread($handle, 2048);
			flush();
			usleep($wait_length);
		}
		fclose($handle);
		exit();
	}
}
?>
