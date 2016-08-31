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

// header("Pragma: no-cache");
// header("Content-type: image/png");
session_start();
$str=$_SESSION['c_str'];
/*
$x=150; $y=30;
$im=imagecreatetruecolor($x,$y);
// Случайный фон
$inc_color=0;
for($i=0;$i<$x;$i++)
{
    $inc_color+=rand(1000,1000000);
    
    for($j=0;$j<$y;$j++)
    {
        $col=imagecolorallocate ($im, rand(0,150), rand(0,150), rand(0,150));
        imagesetpixel ($im, $i, $j, $col);
    
    }
}

$sc=rand(1,40);
for($i=0;$i<strlen($str);$i++)
{
    $text_color = imagecolorallocate ($im, rand(100,255), rand(100,255), rand(100,255));
    imagestring ($im,6,$sc+($i*10)+rand(0,3),rand(0,12),$str[$i], $text_color);
}

for($i=0;$i<250;$i++)
{
        $col=imagecolorallocate ($im, rand(150,255), rand(150,2550), rand(150,255));
        imagesetpixel ($im, rand(0,$x),rand(0,$y), $col);
}

imagepng($im);*/


global $CONFIG;
$CONFIG['captcha_gd_foreground_noise']=0;
$CONFIG['captcha_gd_y_grid']=20;
$CONFIG['captcha_gd_x_grid']=20;
include('include/captcha_gd.php');
$captcha = new captcha();
$captcha->execute($str, rand());
exit;

?>