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

include_once("core.php");
$tim=time();
$tt=$tim-60*60*24*30;
$res=mysql_query("SELECT `id`, `ip`, `agent`, `refer` FROM `counter` WHERE `date`>'$tt' GROUP by `ip`");
$max=0;
$sum=0;
$browsers=array();

$others=array();

while($nxt=mysql_fetch_row($res))
{
	if(eregi("Toata dragostea",$nxt[2])|| eregi("NetcraftSurveyAgent",$nxt[2]) || eregi("Scanner",$nxt[2]) || eregi("SurveyBot",$nxt[2]) || $nxt[2]=='')
		$browser='spam,viruses,scanners';
	else if(eregi("Opera mobi",$nxt[2]))
		$browser='Opera mobile';
	else if(eregi("Opera",$nxt[2]))
		$browser='Opera';
	else if(eregi("firefox",$nxt[2]))
		$browser='Mozilla: firefox';
	else if(eregi("Iceweasel",$nxt[2]))
		$browser='Mozilla: Iceweasel';
	else if(eregi("wget",$nxt[2]))
		$browser='wget';
	else if(eregi("Avant",$nxt[2]))
		$browser='avant';
	else if(eregi("HTC",$nxt[2]))
		$browser='HTC Mobile';
	else if(eregi("MSIE",$nxt[2]))
		$browser='Internet Explorer';
	else if(eregi("Yandex",$nxt[2]))
		$browser='BOT: yandex';
	else if(eregi("msnbot",$nxt[2]))
		$browser='BOT: msnbot';
	else if(eregi("googlebot",$nxt[2]))
		$browser='BOT: googlebot';
	else if(eregi("Yahoo",$nxt[2]))
		$browser='BOT: yahoo';
	else if(eregi("Baiduspider",$nxt[2]))
		$browser='BOT: Baidu.jp';
	else if(eregi("Bot",$nxt[2])||eregi("Spider",$nxt[2]))
		$browser='BOT (spider): other';
	else if(eregi("web.archive",$nxt[2]))
		$browser='Web Archive';
	else if(eregi("Sosospider",$nxt[2]))
		$browser='BOT: Baidu.jp';
	else if(eregi("Konqueror",$nxt[2]))
		$browser='konqueror';
	else if(eregi("Chrome",$nxt[2]))
		$browser='Google Chrome';

	else if(eregi("Mail.Ru",$nxt[2]))
		$browser='mail_ru_agent';
	else if(eregi("mozilla",$nxt[2]))
		$browser='Mozilla';

	else
	{
		$browser='z-other';
		$others[$nxt[2]]++;
	}
	
	$browsers[$browser]++;
	
	if($max<$browsers[$browser]) $max=$browsers[$browser];
				
	$sum++;
}



$coeff=100/$max;
$coeff_p=100/$sum;
ksort($browsers);
foreach($browsers as $cur=> $cnt)
{
	$ln=$cnt*$coeff*10;
	$pp=$coeff_p*$cnt*100;
	settype($pp,"int");
	$pp/=100;
	settype($ln,"int");
	$color=rand(0,9).rand(0,9).rand(0,9);
	$tmpl->AddText("$cur - $pp%
	<div style='width: $ln"."px; height: 10px; background-color: #$color; color: #ccc'></div><br>");
}
$tmpl->AddText("<hr>");
foreach($others as $cur=> $cnt)
{

	$tmpl->AddText("$cur - $cnt<br>");
}


$tmpl->write();

?>