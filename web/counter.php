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
$not_use_counter = 1;
require_once("core.php");

$cname="{$CONFIG['site']['var_data_fs']}/cache/counter.png";
$icname="{$CONFIG['site']['var_data_web']}/cache/counter.png";
if(file_exists($cname)) {
    $mtime = filemtime($cname);
    $expiredate = time() - 60*5;
    if($mtime > $expiredate) {
        header("Location: $icname");
        exit();
    }
}


$im = imagecreatefrompng("img/counterbg.png");
$bg_c = imagecolorallocate($im, 150, 255, 150);
$text_c = imagecolorallocate($im, 0, 80, 0);
$tim = time();
$tt = $tim - 60 * 60 * 24;
$res = $db->query("SELECT `id` FROM `counter` WHERE `date`>'$tt'");
$all = $res->num_rows;
$res = $db->query("SELECT `id` FROM `counter` WHERE `date`>'$tt' GROUP by `ip`");
$pip = $res->num_rows;
$tt = $tim - 60 * 60 * 24 * 7;
$res = $db->query("SELECT `id` FROM `counter` WHERE `date`>'$tt' GROUP by `ip`");
$ww = $res->num_rows;
$res = $db->query("SELECT `id` FROM `counter` WHERE `date`>'$tt'");
$aw = $res->num_rows;
$pref = \pref::getInstance();

header("Content-type: image/png");
imagestring($im, 1, 5, 5, "Week: $aw/$ww", $text_c);
imagestring($im, 1, 5, 12, "Day:  $all/$pip", $text_c);
imagestring($im, 1, 5, 25, $pref->site_name, $text_c);
imagepng($im);
imagepng($im, $cname, 9);
