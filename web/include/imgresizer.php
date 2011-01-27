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

function img_resize($n, $imgdir, $ext='jpg')
{
	global $CONFIG;
	settype($n,"integer");
	// Качество
	$q=$_GET['q'];
	settype($q,"integer");
	// Размер X
	$x=$_GET['x'];
	settype($n,"integer");
	// Размер Y
	$y=$_GET['y'];
	settype($y,"integer");
	// ??
	$nrs=$_GET['nrs'];
	settype($nrs,"integer");
	$u=$_GET['u'];
	settype($u,"integer");
	if(!$q) $q=75;
	
	if( (!$x) && (!$y) )	$x=300;
	
	if(!$n) @header("Pragma: no-cache");
	//header("Content-type: image/jpg");
	
	$cc[0]=$CONFIG['site']['name'];
	$cc[1]="";
	$cc[2]="";
	
	
	$rs=0;
	
	$cname="{$CONFIG['site']['var_data_fs']}/cache/$imgdir/$n-$x-$y-$q.jpg";
	$icname="{$CONFIG['site']['var_data_web']}/cache/$imgdir/$n-$x-$y-$q.jpg";
	if(!file_exists($cname))
	{
		$imagefile="{$CONFIG['site']['var_data_fs']}/$imgdir/$n.$ext";
		
		@mkdir("{$CONFIG['site']['var_data_fs']}/cache/$imgdir/",0755);
		$dx=$dy=0;
		if($x||$y)
		{
			$sz=getimagesize($imagefile);
			$sx=$sz[0];
			$sy=$sz[1];
			$stype=$sz[2];
			{
				
				
				// Жёстко заданные размеры
				$aspect=$sx/$sy;
				if($y&&(!$x))
				{
					if($y>$sy)	$y=$sy;
					$x=round($aspect*$y);
				}
				else if($x&&(!$y))
				{
					if($x>$sx)	$x=$sx;
					$y=round($x/$aspect);
				}
				$naspect=$x/$y;
				$nx=$x;
				$ny=$y;
				if($aspect<$naspect)	$nx=round($aspect*$y);
				else			$ny=round($x/$aspect);
				$lx=($x-$nx)/2;
				$ly=($y-$ny)/2;
			
			}
			$rs=1;
		}
		if($ext=='jpg')		$im=imagecreatefromjpeg($imagefile);
		else if($ext=='png')	$im=imagecreatefrompng($imagefile);
		else if($ext=='gif')	$im=imagecreatefromgif($imagefile);
		else if($ext=='xpm')	$im=imagecreatefromxpm($imagefile);
		else die("invalid extension!"); 
 		if($rs)
		{
			$im2=imagecreatetruecolor($x,$y);
			imagefill($im2, 0, 0, imagecolorallocate($im2, 255, 255, 255));
			imagecopyresampled($im2, $im, $lx, $ly, 0, 0, $nx, $ny, $sx, $sy);
			imagedestroy($im);
			$im=$im2;
		}
		if($x<200) $ss=1; else if($x<1000) $ss=2; else $ss=4;
	
		$bg_c = imagecolorallocate ($im, 128,128, 128);
		$text_c = imagecolorallocate ($im, 255, 255, 255);
		for($t=0;$t<=2;$t++)
		{
			for($i=(-1);$i<=1;$i++)
			for($j=(-1);$j<=1;$j++)
				imagestring ($im,$ss,5+$i+$lx,5+$j+$t*(5+$ss*3),$cc[$t], $bg_c);
			imagestring ($im,$ss,5+$lx,5+$t*(5+$ss*3),$cc[$t], $text_c);
		}
		imagejpeg($im,"$cname",$q);
		//imagejpeg($im,"",$q);
	}
	header("Location: $icname");
	exit();
}


?>