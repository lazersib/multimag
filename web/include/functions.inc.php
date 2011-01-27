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


// Тоже устаревший файл
global $LAST_ERROR;

// ====================================== Генератор уникальных кодов =======================================
function keygen_unique($num=0, $minlen=5, $maxlen=12)
{
   if($minlen<1) $minlen=5;
   if($maxlen>10000) $maxlen=10000;
   if($maxlen<$minlen) $maxlen=$minlen;
   if(!$num)
   {
      $sstr="bcdfghjklmnprstvwxz";
      $gstr="aeiouy1234567890aeiouy";
      $rstr="aeiouy1234567890aeiouybcdfghjklmnprstvwxz";
      $sln=18; // +1
      $gln=21; // +1
      $rln=40; //+1
   }
   else
   {
      $sstr="135790";
      $gstr="24680";
      $rstr="1234567890";
      $sln=5; // +1
      $gln=4; // +1
      $rln=9; //+1
   }
   $r=rand(0,$rln);
   $s=$rstr[$r];
   $ln=rand($minlen,$maxlen);
   $sig=0;
   for($i=1;$i<$ln;$i++)
   {
      if(eregi($s[$i-1],$sstr))
      {
         $r=rand(0,$gln);
         $s.=$gstr[$r];
      }
      else
      {
         $r=rand(0,$sln);
         $s.=$sstr[$r];
      }
   }
   return $s;
}

// ======================================= Обработчики ввода переменных ====================================
function rcv($varname,$def="")
{
    $dt=htmlentities(@$_POST[$varname],ENT_QUOTES,"UTF-8");
    if($dt=="") $dt=htmlentities(@$_GET[$varname],ENT_QUOTES,"UTF-8");
    if($dt) return $dt;
    else return $def;
}

function unhtmlentities ($string)
{
	return html_entity_decode ($string,ENT_QUOTES,"UTF-8");
}

// function rcv($varname,$def="")
// {
//     $dt=html_encode(@$_POST[$varname]);
//     if($dt=="") $dt=html_encode(@$_GET[$varname]);
//     if($dt=="") return $def;
//     return $dt;
// }

// function rcv($varname)
// {
//     $dt=mysql_escape_string(@$_POST[$varname]);
//     if($dt=="") $dt=mysql_escape_string(@$_GET[$varname]);
//     return $dt;
// }

// Удаление лишних символов из строки для безопасной передачи в SQL запрос
function html_encode($sss)
{
   $sss=htmlspecialchars($sss);
   $sss=eregi_replace("'","\"",$sss);
   return $sss;
}

?>