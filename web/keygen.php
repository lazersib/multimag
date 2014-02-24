<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2014, BlackLight, TND Team, http://tndproject.org
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

   $sstr="bcdfghjklmnprstvwxz";
   $gstr="aeiouy1234567890aeiouy";
   $rstr="aeiouy1234567890aeiouybcdfghjklmnprstvwxz";
   $r=rand(0,40);
   $s=$rstr[$r];
   $ln=rand(7,12);
   $sig=0;
   for($i=1;$i<$ln;$i++)
   {
      if(eregi($s[$i-1],$sstr))
      {
         $r=rand(0,21);
         $s.=$gstr[$r];
      }
      else
      {
         $r=rand(0,18);
         $s.=$sstr[$r];
      }

   }
   header("Content-type: image/png");
   $im=imagecreate(200,50);
   $bg_c = imagecolorallocate ($im, 255,255, 255);
   $text_c = imagecolorallocate ($im, 0, 0, 80);

   for($i=0;$i<500;$i++)
   {
   	imagesetpixel($im, rand(0,200), rand(0,50), $text_c);
   }

   imagestring ($im,5,10,5,$s, $text_c);
   imagestring ($im,1,10,40,"Pass generator v.1.0", $text_c);
   imagepng($im);
?>
