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

require_once("../config_site.php");

$db = @ new mysqli($CONFIG['mysql']['host'], $CONFIG['mysql']['login'], $CONFIG['mysql']['pass'], $CONFIG['mysql']['db']);
if($db->connect_error)
{
	header("HTTP/1.0 503 Service temporary unavariable");
	exit();
}

if(!$db->set_charset("utf8"))
{
	header("HTTP/1.0 503 Service temporary unavariable");
	exit();
}

$cc=@$_GET['cc'];
$im=imagecreatefrompng("img/counterbg.png");
$bg_c = imagecolorallocate ($im, 150,255, 150);
$text_c = imagecolorallocate ($im, 0, 80, 0);
$tim=time();
$tt=$tim-60*60*24;
$res=$db->query("SELECT `id` FROM `counter` WHERE `date`>'$tt'");
$all=$res->num_rows;
$res=$db->query("SELECT `id` FROM `counter` WHERE `date`>'$tt' GROUP by `ip`");
$pip=$res->num_rows;
$tt=$tim-60*60*24*7;
$res=$db->query("SELECT `id` FROM `counter` WHERE `date`>'$tt' GROUP by `ip`");
$ww=$res->num_rows;
$res=$db->query("SELECT `id` FROM `counter` WHERE `date`>'$tt'");
$aw=$res->num_rows;


header("Content-type: image/png");
imagestring ($im,1,5,5,"Week: $aw/$ww", $text_c);
imagestring ($im,1,5,12,"Day:  $all/$pip", $text_c);
imagestring ($im,1,25,25,$CONFIG['site']['name'], $text_c);
imagepng($im);

?>