<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2010, BlackLight, TND Team, http://tndproject.org
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

if(!@mysql_connect($CONFIG['mysql']['host'],$CONFIG['mysql']['login'],$CONFIG['mysql']['pass']))
{
	//echo"<h1>503 Сервис временно недоступен!</h1>Не удалось соединиться с сервером баз данных. Возможно он перегружен, и слишком медленно отвечает на запросы, либо выключен. Попробуйте подключиться через 5 минут. Если проблема сохранится - пожалуйста, напишите письмо по адресу <a href='mailto:{$CONFIG['site']['admin_email']}'>{$CONFIG['site']['admin_email']}</a> c описанием проблемы.";
	exit();
}
if(!@mysql_select_db($CONFIG['mysql']['db']))
{
    //echo"Невозможно активизировать базу данных! Возможно, база данных повреждена. Попробуйте подключиться через 5 минут. Если проблема сохранится - пожалуйста, напишите письмо по адресу <a href='mailto:{$CONFIG['site']['admin_email']}'>{$CONFIG['site']['admin_email']}</a> c описанием проблемы.";
    exit();
}

mysql_query("SET CHARACTER SET UTF8");
mysql_query("SET character_set_results = UTF8");

$ip=getenv("REMOTE_ADDR");
$ag=getenv("HTTP_USER_AGENT");
$rf=getenv("HTTP_REFERER");
$qq=$_SERVER['QUERY_STRING'];
$ff=$_SERVER['PHP_SELF'];
$tim=time();
$skidka="";

$cc=@$_GET['cc'];
$im=imagecreatefrompng("img/counterbg.png");
$bg_c = imagecolorallocate ($im, 150,255, 150);
$text_c = imagecolorallocate ($im, 0, 80, 0);
$tim=time();
$tt=$tim-60*60*24;
$res=mysql_query("SELECT `id` FROM `counter` WHERE `date`>'$tt'");
echo mysql_error();
$all=mysql_num_rows($res);
$res=mysql_query("SELECT `id` FROM `counter` WHERE `date`>'$tt' GROUP by `ip`");
$pip=mysql_num_rows($res);
$tt=$tim-60*60*24*7;
$res=mysql_query("SELECT `id` FROM `counter` WHERE `date`>'$tt' GROUP by `ip`");
$ww=mysql_num_rows($res);
$res=mysql_query("SELECT `id` FROM `counter` WHERE `date`>'$tt'");
$aw=mysql_num_rows($res);


header("Content-type: image/png");
imagestring ($im,1,5,5,"Week: $aw/$ww", $text_c);
imagestring ($im,1,5,12,"Day:  $all/$pip", $text_c);
imagestring ($im,1,25,25,$CONFIG['site']['name'], $text_c);
imagepng($im);

?>