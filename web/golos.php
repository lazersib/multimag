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

// УСТАРЕВШИЙ ФАЙЛЮ НЕОБХОДИМА ПЕРЕРАБОТКА.
include_once("function.php");
$ll=auth();
top();
zstart("Голосование");
$ip=getenv("REMOTE_ADDR");

$mode=html_encode(@$_GET['mode']);
$gv=html_encode(@$_GET['gv']);


$tim=time();
$res=mysql_query("SELECT * FROM golos WHERE ip='$ip' AND id='$mode'");
$ct=mysql_num_rows($res);
if($ct) echo"С этого IP адреса уже голосовали!<br><br>";
else if($gv=="") echo"Ты ничего не выбрал!<br><br>";
else
{
   mysql_query("INSERT INTO golos (`id`,`variant`,`ip`) VALUES ('$mode','$gv','$ip')");
   echo"Спасибо, твой голос учтён!<br>";
}

echo"<b>Результаты</b><br>
<table width=400 border=0>
";
$res=mysql_query("SELECT * FROM golos_vars WHERE id='$mode' ORDER BY subid");
while($nxt=mysql_fetch_row($res))
{
$res1=mysql_query("SELECT id,variant,sum(`ct`) FROM golos WHERE id='$mode' AND variant='$nxt[2]'  GROUP BY `variant`");
$nx=mysql_fetch_row($res1);
$nn=$nx[2];
if($nx=="") $nn=0;
$nn1=$nn*15;
echo"<tr><td width=200 align=right>$nn<img src='img/ztop.gif' width=$nn1 height=10></td><td>$nxt[1]</td></tr>";
}
echo"</table>";

zend();
bottom();
?>

