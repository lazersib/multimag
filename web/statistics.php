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
$tim = time();
$tt = $tim - 60 * 60 * 24 * 30;
$res = $db->query("SELECT `id`, `ip`, `agent`, `refer` FROM `counter` WHERE `date`>'$tt' GROUP by `ip`");
$max = 0;
$sum = 0;
$browsers = array();
$tmpl->setTitle("Статистика по броузерам");

$others = array();

while ($nxt = $res->fetch_row()) {
	if ( stripos($nxt[2], "Toata dragostea")!==false || stripos($nxt[2], "NetcraftSurveyAgent")!==false
		|| stripos($nxt[2], "Scanner")!=false || stripos($nxt[2], "SurveyBot")!=false || $nxt[2] == '')
		$browser = 'spam,viruses,scanners';
	else if (stripos($nxt[2], "Opera mobi")!==false)
		$browser = 'Opera mobile';
	else if (stripos($nxt[2], "Opera")!==false)
		$browser = 'Opera';
	else if (stripos($nxt[2], "firefox")!==false || stripos($nxt[2], "Iceweasel")!==false)
		$browser = 'Mozilla';
	else if (stripos($nxt[2], "wget")!==false)
		$browser = 'wget';
	else if (stripos($nxt[2], "Avant")!==false)
		$browser = 'avant';
	else if (stripos($nxt[2], "MSIE")!==false)
		$browser = 'Internet Explorer';
	else if (stripos($nxt[2], "Yandex")!==false)
		$browser = 'BOT: yandex';
	else if (stripos($nxt[2], "msnbot")!==false)
		$browser = 'BOT: msnbot';
	else if (stripos($nxt[2], "googlebot")!==false)
		$browser = 'BOT: googlebot';
	else if (stripos($nxt[2], "Yahoo")!==false)
		$browser = 'BOT: yahoo';
	else if (stripos($nxt[2], "Baiduspider")!==false)
		$browser = 'BOT: Baidu.jp';
	else if (stripos($nxt[2], "Bot")!==false || stripos("Spider", $nxt[2])!==false)
		$browser = 'BOT (spider): other';
	else if (stripos($nxt[2], "web.archive")!==false)
		$browser = 'Web Archive';
	else if (stripos($nxt[2], "Sosospider")!==false)
		$browser = 'BOT: Baidu.jp';
	else if (stripos($nxt[2], "Konqueror")!==false)
		$browser = 'konqueror';
	else if (stripos($nxt[2], "Chrome")!==false)
		$browser = 'Google Chrome';
	else if (stripos($nxt[2], "Mail.Ru")!==false)
		$browser = 'mail_ru browser';
	else if (stripos($nxt[2], "mozilla")!==false)
		$browser = 'Mozilla';
	else {
		$browser = 'z-other';
		@$others[$nxt[2]]++;
	}

	@$browsers[$browser]++;

	if ($max < $browsers[$browser])
		$max = $browsers[$browser];

	$sum++;
}



$coeff = 100 / $max;
$coeff_p = 100 / $sum;
ksort($browsers);
foreach ($browsers as $cur => $cnt) {
	$ln = $cnt * $coeff * 10;
	$pp = $coeff_p * $cnt * 100;
	settype($pp, "int");
	$pp/=100;
	settype($ln, "int");
	$color = rand(0, 9) . rand(0, 9) . rand(0, 9);
	$tmpl->addContent("$cur - $pp%
	<div style='width: $ln" . "px; height: 10px; background-color: #$color; color: #ccc'></div><br>");
}
$tmpl->addContent("<hr>");
foreach ($others as $cur => $cnt) {

	$tmpl->addContent("$cur - $cnt<br>");
}


$tmpl->write();
?>