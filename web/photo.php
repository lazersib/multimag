<?php

//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2013, BlackLight, TND Team, http://tndproject.org
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


$n=$_GET['n'];
settype($n,"integer");
$q=$_GET['q'];
settype($q,"integer");
$x=$_GET['x'];
settype($n,"integer");
$y=$_GET['y'];
settype($y,"integer");
$nrs=$_GET['nrs'];
settype($nrs,"integer");
$u=$_GET['u'];
settype($u,"integer");
if(!$q) $q=75;

if(!$n) @header("Pragma: no-cache");
//header("Content-type: image/jpg");

$cc[0]=$CONFIG['site']['name'];
$cc[1]="";
$cc[2]="";

require_once("core.php");

if(!$n)
{
	$res=$db->query("SELECT `id` FROM `photogalery`");
	if(!$res)	throw new MysqlException("Не удалось получить список изображений");
	$rnd=rand(0,$res->num_rows-1);
	$res->field_seek($rnd);
	list($n)=$res->fetch_row();
}

$res=$db->query("SELECT `photogalery`.`id`, `photogalery`.`uid`, `photogalery`.`comment`, `users`.`name`
FROM `photogalery`
LEFT JOIN `users` ON `users`.`id`=`photogalery`.`uid`
WHERE `photogalery`.`id`='$n' ");
if(!$res)	throw new MysqlException("Не удалось получить данные изображения");
if($nxt=$res->fetch_row())
{

	$imagefile="{$CONFIG['site']['var_data_fs']}/galery/$n.jpg";
	settype($cc,"array");
	$cc[0]=$CONFIG['site']['name'];
	$cc[1]=$nxt[2];
	$cc[2]=translit($nxt[2]);
}

$rs=0;
$cname="{$CONFIG['site']['var_data_fs']}/galery/cache/$n-$x-$y-$q.jpg";
$icname="{$CONFIG['site']['var_data_web']}/galery/cache/$n-$x-$y-$q.jpg";
if(!file_exists($cname))
{
	$dx=$dy=0;
	if($x||$y)
	{
		$sz=getimagesize($imagefile);
		{
			if($x>$sz[0]) $x=$sz[0];
			if($y>$sz[0]) $y=$sz[0];
			if($y&&(!$x))
			{
			if($y<10) $y=10;
			if(!$nrs)
				$x=($sz[0]/$sz[1])*$y;
			else
				$dx=((($sz[0]/$sz[1])*$y)-$x)/2;
			}
			else
			{
				if($x<10) $x=10;
				if(!$nrs)
					$y=($sz[1]/$sz[0])*$x;
				else
					$dy=((($sz[1]/$sz[0])*$x)-$y)/2/$x*$sz[1];
				if($dy<0)
				{
					$dx=((($sz[0]/$sz[1])*$y)-$x)/2/$y*$sz[0];
					//$dx=((($sz[0]/$sz[1])*$y)-$x)/2;
					$dy=0;
				}
			}
		}
		$rs=1;
	}

	$im=imagecreatefromjpeg($imagefile);
	if($rs)
	{
		$im2=imagecreatetruecolor($x,$y);
		imagecopyresampled($im2, $im,0,0,0+($dx/2),0+($dy/2),$x,$y,$sz[0]-$dx,$sz[1]-$dy);
		imagedestroy($im);
		$im=$im2;
	}
	if($x<200) $ss=1; else if($x<1000) $ss=2; else $ss=4;

	$bg_c = imagecolorallocate ($im, 0,0, 0);
	$text_c = imagecolorallocate ($im, 255, 255, 255);
	for($t=0;$t<=2;$t++)
	{
		for($i=(-1);$i<=1;$i++)
		for($j=(-1);$j<=1;$j++)
			imagestring ($im,$ss,5+$i,5+$j+$t*(5+$ss*3),$cc[$t], $bg_c);
		imagestring ($im,$ss,5,5+$t*(5+$ss*3),$cc[$t], $text_c);
	}
	imagejpeg($im,"$cname",$q);
	//imagejpeg($im,"",$q);
}
header("Location: $icname");


?>