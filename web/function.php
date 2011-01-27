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


// УСТАРЕВШИЙ ФАЙЛ, ИСПОЛЬЗУЕТСЯ КОЕ-ГДЕ В НЕПЕРЕДЕЛАННЫХ МОДУЛЯХ. ПОСЛЕ ПЕРЕДЕЛКИ ЭТОТ ФАЙЛ НУЖНО УДАЛИТЬ.
require_once("include/config.php");

$GLOBALS['auth']=0;
$GLOBALS['id']=0;
$GLOBALS['a_news_add']=0;
$GLOBALS['a_reg']=0;
$GLOBALS['name']="Guest";
global $time_script_start;
$time_script_start = getmicrotime();

$ip=getenv("REMOTE_ADDR");
$GLOBALS['last_error']="All ok!";

$GLOBALS['a_base']=0;

$GLOBALS['m_left']=1;
$GLOBALS['m_right']=1;
$GLOBALS['m_top']=1;
$GLOBALS['v_top']=0;
$GLOBALS['m_bottom']=1;
$GLOBALS['m_refresh']=0;

session_start();


require_once("include/functions.inc.php");


if(!@mysql_connect($mysql_host,$mysql_login,$mysql_pass))
{
    msg("РќРµРІРѕР·РјРѕР¶РЅРѕ СЃРѕРµРґРёРЅРёС‚СЊСЃСЏ СЃ СЃРµСЂРІРµСЂРѕРј Р±Р°Р· РґР°РЅРЅС‹С…! РџРѕРїСЂРѕР±СѓР№С‚Рµ РїРѕР·РґРЅРµРµ! Р•СЃР»Рё РѕС€РёР±РєР° РїРѕРІС‚РѕСЂСЏРµС‚СЃСЏ - РѕР±СЂР°С‚РёС‚РµСЃСЊ Рє Р°РґРјРёРЅРёСЃС‚СЂР°С‚РѕСЂСѓ!","err");
    exit();
}
if(!@mysql_select_db($mysql_db))
{
    msg("РќРµРІРѕР·РјРѕР¶РЅРѕ Р°РєС‚РёРІРёР·РёСЂРѕРІР°С‚СЊ Р±Р°Р·Сѓ РґР°РЅРЅС‹С…! Р’РѕР·РјРѕР¶РЅРѕ, Р±Р°Р·Р° РґР°РЅРЅС‹С… РїРѕРІСЂРµР¶РґРµРЅР°. РћР±СЂР°С‚РёС‚РµСЃСЊ Рє Р°РґРјРёРЅРёСЃС‚СЂР°С‚РѕСЂСѓ!","err");
    exit();
}

mysql_query("SET CHARACTER SET UTF8");
mysql_query("SET character_set_results = UTF8");

function getmicrotime()
{
    list($usec, $sec) = explode(" ",microtime());
    return ((float)$usec + (float)$sec);
}

function rusdate($fstr,$rtime=-1)
{
   if($rtime==-1) $rtime=time();
   $dstr=date($fstr,$rtime);
   $dstr=eregi_replace("Monday","РџРѕРЅРµРґРµР»СЊРЅРёРє",$dstr);
   $dstr=eregi_replace("Tuesday","Р’С‚РѕСЂРЅРёРє",$dstr);
   $dstr=eregi_replace("Wednesday","РЎСЂРµРґР°",$dstr);
   $dstr=eregi_replace("Thursday","Р§РµС‚РІРµСЂРі",$dstr);
   $dstr=eregi_replace("Friday","РџСЏС‚РЅРёС†Р°",$dstr);
   $dstr=eregi_replace("Saturday","РЎСѓР±Р±РѕС‚Р°",$dstr);
   $dstr=eregi_replace("Sunday","Р’РѕСЃРєСЂРµСЃРµРЅСЊРµ",$dstr);
   $dstr=eregi_replace("January","СЏРЅРІР°СЂСЏ",$dstr);
   $dstr=eregi_replace("February","С„РµРІСЂР°Р»СЏ",$dstr);
   $dstr=eregi_replace("March","РјР°СЂС‚Р°",$dstr);
   $dstr=eregi_replace("April","Р°РїСЂРµР»СЏ",$dstr);
   $dstr=eregi_replace("May","РјР°СЏ",$dstr);
   $dstr=eregi_replace("June","РёСЋРЅСЏ",$dstr);
   $dstr=eregi_replace("July","РёСЋР»СЏ",$dstr);
   $dstr=eregi_replace("August","Р°РІРіСѓСЃС‚Р°",$dstr);
   $dstr=eregi_replace("September","СЃРµРЅС‚СЏР±СЂСЏ",$dstr);
   $dstr=eregi_replace("October","РѕРєС‚СЏР±СЂСЏ",$dstr);
   $dstr=eregi_replace("November","РЅРѕСЏР±СЂСЏ",$dstr);
   $dstr=eregi_replace("December","РґРµРєР°Р±СЂСЏ",$dstr);
   return $dstr;
}

function numprop($num)
{
    $s="";

    $num2=$num;
    settype($num,"integer");
    $num2-=$num;
    $num2*=100;
    if($num2) $s = sprintf("%22.0f РєРѕРїРµРµРє", $num2);

    $tt=0;  // СЂР°Р·СЂСЏРґ РІ С‚С‹СЃСЏС‡Рµ
    $ll=0;  // РЅРѕРјРµСЂ С‚С‹СЃСЏС‡Рё
    while($num>0)
    {
        $nn=$num%100;
        if(($tt!=0)||($nn>19))  $nn=$num%10;
        $num/=10;
        settype($num,"integer");
        if($tt==0)
        {
            if($ll==0)
            {
                $s1="СЂСѓР±Р»СЊ";
                $s2="СЂСѓР±Р»СЏ";
                $s3="СЂСѓР±Р»РµР№";
            }
            else if($ll==1)
            {
                $s1="С‚С‹СЃСЏС‡Р°";
                $s2="С‚С‹СЃСЏС‡Рё";
                $s3="С‚С‹СЃСЏС‡";
            }
            else if($ll==2)
            {
                $s1="РјРёР»Р»РёРѕРЅ";
                $s2="РјРёР»Р»РёРѕРЅР°";
                $s3="РјРёР»Р»РёРѕРЅРѕРІ";
            }
            else if($ll==3)
            {
                $s1="РјРёР»Р»РёР°СЂРґ";
                $s2="РјРёР»Р»РёР°СЂРґР°";
                $s3="РјРёР»Р»РёР°СЂРґРѕРІ";
            }
            else $s1=$s2=$s3="";

            if($nn==1)
            {
                if($ll==1) $s="РѕРґРЅР° $s1 ".$s;
                else $s="РѕРґРёРЅ $s1 ".$s;
            }
            else if($nn==2)
            {
                if($ll==1) $s="РґРІРµ $s2 ".$s;
                else $s="РґРІР° $s2 ".$s;
            }
            else if($nn==3) $s="С‚СЂРё $s2 ".$s;
            else if($nn==4) $s="С‡РµС‚С‹СЂРµ $s2 ".$s;
            else if($nn==5) $s="РїСЏС‚СЊ $s3 ".$s;
            else if($nn==6) $s="С€РµСЃС‚СЊ $s3 ".$s;
            else if($nn==7) $s="СЃРµРјСЊ $s3 ".$s;
            else if($nn==8) $s="РІРѕСЃРµРјСЊ $s3 ".$s;
            else if($nn==9) $s="РґРµРІСЏС‚СЊ $s3 ".$s;
            else if($nn==10)$s="РґРµСЃСЏС‚СЊ $s3 ".$s;
            else if($nn==11)$s="РѕРґРёРЅРЅР°РґС†Р°С‚СЊ $s3 ".$s;
            else if($nn==12)$s="РґРІРµРЅР°РґС†Р°С‚СЊ $s3 ".$s;
            else if($nn==13)$s="С‚СЂРёРЅР°РґС†Р°С‚СЊ $s3 ".$s;
            else if($nn==14)$s="С‡РµС‚С‹СЂРЅР°РґС†Р°С‚СЊ $s3 ".$s;
            else if($nn==15)$s="РїСЏС‚РЅР°РґС†Р°С‚СЊ $s3 ".$s;
            else if($nn==16)$s="С€РµСЃС‚РЅР°РґС†Р°С‚СЊ $s3 ".$s;
            else if($nn==17)$s="СЃРµРјРЅР°РґС†Р°С‚СЊ $s3 ".$s;
            else if($nn==18)$s="РІРѕСЃРµРјРЅР°РґС†Р°С‚СЊ $s3 ".$s;
            else if($nn==19)$s="РґРµРІСЏС‚РЅР°РґС†Р°С‚СЊ $s3 ".$s;
            //else $s="$nn(tt0) ".$s;
        }
        else if($tt==1)
        {

            if($nn==0) $s=$s;
            else if($nn==1) $s=$s;
            else if($nn==2) $s="РґРІР°РґС†Р°С‚СЊ ".$s;
            else if($nn==3) $s="С‚СЂРёРґС†Р°С‚СЊ ".$s;
            else if($nn==4) $s="СЃРѕСЂРѕРє  ".$s;
            else if($nn==5) $s="РїСЏС‚СЊРґРµСЃСЏС‚ ".$s;
            else if($nn==6) $s="С€РµСЃС‚СЊРґРµСЃСЏС‚ ".$s;
            else if($nn==7) $s="СЃРµРјСЊРґРµСЃСЏС‚ ".$s;
            else if($nn==8) $s="РІРѕСЃРµРјСЊРґРµСЃСЏС‚ ".$s;
            else if($nn==9) $s="РґРµРІСЏРЅРѕСЃС‚Рѕ ".$s;
            //else $s="$nn(tt1) ".$s;
        }
        else
        {
            if($nn==0) $s=$s;
            else if($nn==1) $s="СЃС‚Рѕ ".$s;
            else if($nn==2) $s="РґРІРµСЃС‚Рё ".$s;
            else if($nn==3) $s="С‚СЂРёСЃС‚Р° ".$s;
            else if($nn==4) $s="С‡РµС‚С‹СЂРµСЃС‚Р°  ".$s;
            else if($nn==5) $s="РїСЏС‚СЊСЃРѕС‚ ".$s;
            else if($nn==6) $s="С€РµСЃС‚СЊСЃРѕС‚ ".$s;
            else if($nn==7) $s="СЃРµРјСЊСЃРѕС‚ ".$s;
            else if($nn==8) $s="РІРѕСЃРµРјСЊСЃРѕС‚ ".$s;
            else if($nn==9) $s="РґРµРІСЏС‚СЊСЃРѕС‚ ".$s;
            //else $s="$nn(tte) ".$s;
        }

        $tt++;
        if($tt>2) { $tt=0; $ll++; }
    }


    return $s;
}

function msg($text="",$mode="",$head="")
{
    if($text=="") return;
    if($mode=="error") $mode="err";
    if(($mode!="ok")&&($mode!="err")) $mode="notify";
    if($head=="")
    {
        $msg="Р�РЅС„РѕСЂРјР°С†РёСЏ:";
        if($mode=="ok") $msg="РЎРґРµР»Р°РЅРѕ!";
        if($mode=="err") $msg="РћС€РёР±РєР°!";
    }
    else $msg=$head;

	if($mode=="ok") $msg="<img border=0 src='img/icon_suc.gif'>".$msg;
	else if($mode=="err") $msg="<img border=0 src='img/icon_err.gif'>".$msg;
	else $msg="<img border=0 src='img/icon_alert.gif'>".$msg;

    if(!$GLOBALS['v_top'])
    {
        $GLOBALS['m_right']=0;
        $GLOBALS['m_top']=0;
        $GLOBALS['m_left']=0;
        $GLOBALS['m_bottom']=0;
        top();
    }
    echo"<div class='$mode'><b>$msg</b><br>$text</div>";

}

function date_day($date)
{
   $ee=date("d M Y 00:00:00",$date);
   $tm=strtotime($ee);
   return $tm;
}

function logadd($str,$logname="main")
{
   $tim=time();
   $logname=date("Y.m.d",$tim);
   $tim=date("H:i:s",$tim);
   $fd=@fopen("/var/www/logs/$logname","a");

   @fwrite($fd,"$tim: $str\n");
   @fclose($fd);
}

// =================================== +++++++++++
function passcheck($user_name,$user_pass)
{
   $user_name=strtolower($user_name);
   $ip=getenv("REMOTE_ADDR");
   $tim=time();
   $ttm=$tim-60;

   $sql="SELECT * FROM user_badlist WHERE ip='$ip' AND date>$ttm";
   $res=mysql_query($sql);
   $row=mysql_num_rows($res);
   if($row>=5)
   {
      $sql="INSERT INTO `user_badlist` (`login`, `pass`, `date`, `ip`) VALUES ('$user_name', '$user_pass', '$tim', '$ip')";
      $res=mysql_query($sql);
      $GLOBALS['last_error']="РџСЂРµРІС‹С€РµРЅРѕ РєРѕР»РёС‡РµСЃС‚РІРѕ РїРѕРїС‹С‚РѕРє РІРІРѕРґР°!"; return -1;
   }
   $sql="SELECT id,name,pass,endlocktime,comment FROM users WHERE name='$user_name'";
   $res=mysql_query($sql);
   $row=mysql_numrows($res);
   if($row==0)
   {
      $sql="INSERT INTO `user_badlist` (`login`, `pass`, `date`, `ip`) VALUES ('$user_name', '$user_pass', '$tim', '$ip')";
      $res=mysql_query($sql);
      $GLOBALS['last_error']="РќРµРїСЂР°РІРёР»СЊРЅС‹Р№ Р»РѕРіРёРЅ РёР»Рё РїР°СЂРѕР»СЊ!";
      return 0;
   }
   $pass=mysql_result($res,0,2);
   $passmd=md5($user_pass);
   if($pass==$passmd)
   {
      $tt=@mysql_result($res,0,3);
      $ss=@mysql_result($res,0,4);
      if($tim>$tt) {$GLOBALS['last_error']="РџР°СЂРѕР»СЊ РїСЂРёРЅСЏС‚!";return 1;}
      else
      {
         $tt=date("d.M.Y H:i:s",$tt);
         $GLOBALS['last_error']="РџРѕР»СЊР·РѕРІР°С‚РµР»СЊ Р·Р°Р±Р»РѕРєРёСЂРѕРІР°РЅ РґРѕ $tt ($ss)!";
         return -2;
      }
   }

   $sql="INSERT INTO `user_badlist` (`login`, `pass`, `date`, `ip`) VALUES ('$user_name', '$user_pass', '$tim', '$ip')";
   $res=mysql_query($sql);
   $GLOBALS['last_error']="РќРµРїСЂР°РІРёР»СЊРЅС‹Р№ Р»РѕРіРёРЅ РёР»Рё РїР°СЂРѕР»СЊ!";
   return 0;
}

function login($user_name, $user_pass)
{
    $tim=time();
    $pch=passcheck($user_name, $user_pass);
    if($pch==1)
    {
       $tm=time();
       $sql="SELECT * FROM users WHERE name='$user_name'";
       $res=mysql_query($sql);
       $id=mysql_result($res,0,"id");
       $nm=mysql_result($res,0,"name");

//        $tm2=$tm-60*30;
//        $sql="DELETE FROM active_user WHERE date < $tm2";
//        $res=mysql_query($sql);
//
//        $sql="DELETE FROM active_user WHERE id='$id'";
//        $res=mysql_query($sql);
//        //$n_uid=rand(100000,999999).$tm.rand(100000,999999).rand(100000,999999).rand(100000,999999);
//        $n_uid = md5(uniqid(rand(),1));
//        $sql="INSERT INTO `active_user` (`id`, `uid`, `date`) VALUES ('$id', '$n_uid', '$tm')";
//        $res=mysql_query($sql);
//        if($res)
//        {
//           $cook="Set-Cookie: uid=$n_uid;";
//           header($cook);
//           return 1;
//        }

       $_SESSION['uid']=$id;
       $_SESSION['name']=$nm;
       $_SESSION['groups']=array("webadmin","staff","faculty","EApass");

    }
    $sql="INSERT INTO `user_badlist` (`login`,`pass`, `date`, `ip`) VALUES ('$user_name','$user_pass', '$tim', '$ip')";
    $res=mysql_query($sql);
    return $pch;
}

function logout()
{
   $uid=@$_COOKIE['uid'];
   $sql="DELETE FROM active_user WHERE uid='$uid'";
   $res=mysql_query($sql);
   $_SESSION['uid']=0;
}


function need_auth()
{
    if(!auth())
    {
        $GLOBALS['m_left']=1;
        $GLOBALS['m_right']=1;
        $GLOBALS['m_top']=1;
        $GLOBALS['m_bottom']=1;
        top("РќРµРѕР±С…РѕРґРёРјР° Р°РІС‚РѕСЂРёР·Р°С†РёСЏ!");
        msg("Р”Р»СЏ РїСЂРѕРґРѕР»Р¶РµРЅРёСЏ РІРІРµРґРёС‚Рµ СЃРІРѕР№ Р»РѕРіРёРЅ Рё РїР°СЂРѕР»СЊ РІ С„РѕСЂРјСѓ СЃРїСЂР°РІР°!","notify","РўСЂРµР±СѓРµС‚СЃСЏ Р°РІС‚РѕСЂРёР·Р°С†РёСЏ");
        bottom();
        exit();
    }
    return 1;
}


function auth()
{
   //$tm=time();
   //$tmm=$tm-(60*30);

   //$uid=@$_COOKIE['uid'];
   //$sql="SELECT * FROM active_user WHERE uid='$uid'";
   //$res=@mysql_query($sql);
   //$row=@mysql_numrows($res);
   //$dt=@mysql_result($res,0,"date");
   //$id=@mysql_result($res,0,"id");
   //$uid=@mysql_result($res,0,"uid");
   //if(!$row) return 0;

   //$sql="DELETE FROM active_user WHERE id='$id' OR `date` < '$tmm'";
   //$res=mysql_query($sql);

      //$sql="INSERT INTO `active_user` (`id`, `uid`, `date`) VALUES ('$id', '$uid', '$tm')";
   //$res=@mysql_query($sql);

	if($_SESSION['uid']==0) return 0;


   $GLOBALS['id']=$_SESSION['uid'];
   $GLOBALS['auth']=1;
   getattr();

   return 1;
}

function getattr($user_name="")
{
   $id=$GLOBALS['id'];
   if($user_name) $sql="SELECT * FROM users WHERE name='$user_name'";
   else   $sql="SELECT * FROM users WHERE id='$id'";
   $res=@mysql_query($sql);
   $fr=@mysql_fetch_row($res);

   $GLOBALS['id']=@$fr[0];
   $GLOBALS['a_manager']=@$fr[4];
   $GLOBALS['a_club_admin']=@$fr[5];
   $GLOBALS['a_superuser']=@$fr[6];
   $GLOBALS['a_lan_user']=@$fr[7];
   $GLOBALS['a_temp_user']=@$fr[8];

   $GLOBALS['active']=@$fr[13];

   $GLOBALS['u_skidka']=@$fr[10];
   $GLOBALS['u_ballance']=@$fr[9];
   $GLOBALS['u_mbcost']=@$fr[12];
   $GLOBALS['u_pft']=@$fr[15];
   $GLOBALS['u_comm']=@$fr[11];
   $GLOBALS['u_credit']=@$fr[22];
   $GLOBALS['name']=@$fr[1];
   $GLOBALS['lastdate']=@$fr[26];
   $GLOBALS['uin']=@$fr[25];
   $GLOBALS['tarif']=@$fr[24];
}

function card_validate($keycard,$pincard)
{
   $keycard=html_encode($keycard);
   $pincard=html_encode($pincard);
   $tm=time();

   //$GLOBALS['last_error']="РЎРёСЃС‚РµРјР° Р·Р°Р±Р»РѕРєРёСЂРѕРІР°РЅР°!";
   //return -5;

   $sql="SELECT * FROM `card_list` WHERE `key`='$keycard' AND `pin`='$pincard'";
   $res=mysql_query($sql);
   $row=mysql_num_rows($res);
   if($row)
   {
     $dt=mysql_result($res,0,"date_end");
     if(time()<=$dt)
     {
        $ia=mysql_result($res,0,"date_act");
        $tr=mysql_result($res,0,"date_trade");
        if($ia)
        {
           $GLOBALS['last_error']="РљР°СЂС‚Р° СѓР¶Рµ Р±С‹Р»Р° Р°РєС‚РёРІРёСЂРѕРІР°РЅР°!";
           return -2;    // Aktivirovana
        }
        //else if($tr==0)
        //{
        //    $sql="INSERT INTO `user_badlist` (`login`,`pin`, `date`, `ip`) VALUES ('###$user_name','$keycard:$pincard', '$tim', '$ip')";
        //    $res=mysql_query($sql);
        //    $GLOBALS['last_error']="РћС€РёР±РєР°! РћР±СЂР°С‚РёС‚РµСЃСЊ Рє РјРµРЅРµРґР¶РµСЂСѓ!!";
        //    return -10;    // Net pol'zovatelya
        //}
        else
        {
           $GLOBALS['last_error']="РљР°СЂС‚Р° РґРµР№СЃС‚РІРёС‚РµР»СЊРЅР°!";
           return mysql_result($res,0,"cost");            // OK
        }
     }
     else
     {
        $GLOBALS['last_error']="РљР°СЂС‚Р° РїСЂРѕСЃСЂРѕС‡РµРЅР°!";
        return -1;          // Prosrochena
     }
   }
   $GLOBALS['last_error']="РљР°СЂС‚Р° РЅРµ СЃСѓС‰РµСЃС‚РІСѓРµС‚!";
   return 0;                  // Ne sushestvuet
}

function card_activate($user_name,$keycard,$pincard,$pft=0)
{
   $user_name=html_encode($user_name);
   $keycard=html_encode($keycard);
   $pincard=html_encode($pincard);

   //$GLOBALS['last_error']="РЎРёСЃС‚РµРјР° Р·Р°Р±Р»РѕРєРёСЂРѕРІР°РЅР°!";
   //return -5;

   $ip=getenv("REMOTE_ADDR");
   $tim=time();
   $ttm=$tim-60;
   $sql="SELECT * FROM user_badlist WHERE ip='$ip' AND date>$ttm";
   $res=@mysql_query($sql);
   $row=@mysql_num_rows($res);
   if($row>=5)
   {
      $sql="INSERT INTO `user_badlist` (`login`,`pin`, `date`, `ip`) VALUES ('$user_name','$keycard:$pincard', '$tim', '$ip')";
      $res=mysql_query($sql);
      $GLOBALS['last_error']="РџСЂРµРІС‹С€РµРЅРѕ РєРѕР»РёС‡РµСЃС‚РІРѕ РїРѕРїС‹С‚РѕРє РІРІРѕРґР°!";
      return -100;
   }

   $cv=card_validate($keycard,$pincard);
   if($cv>0)
   {
       $sql="SELECT id, name, ballance, credit, a_temp_user FROM `users` WHERE `name` = '$user_name'";
       $res=mysql_query($sql);
       $row=@mysql_fetch_row($res);
       if($row)
       {

          if($pft)
          {
             $sql="UPDATE `users` SET `payfortime` = `payfortime` + '$cv' WHERE `name` = '$user_name'";
             $res=mysql_query($sql);
          }
          else
          {
             //if(($row[4])&&($cv<10))
             //{
             //   $GLOBALS['last_error']="РџСЂРёРіР»Р°СЃРёС‚РµР»СЊРЅС‹Рµ РєР°СЂС‚С‹ РЅРµР»СЊР·СЏ Р°РєС‚РёРІРёСЂРѕРІР°С‚СЊ РЅР° СЃС‡С‘С‚!!";
             //   return -12;
             //}
             //else
             {
                $cv1=$cv;
                $cv2=0;
                if($row[3]>0)
                {
                   if($row[3]>($cv/2))
                   {
                      $cv1=$cv2=$cv/2;
                   }
                   else
                   {
                      $cv2=$row[3];
                      $cv1=$cv-$cv2;
                   }
                }
                //$cv1=$cv-$row[3];
                //if($cv1<0) $cv1=0;
                //$cv2=$row[3]-$cv+$cv1;
                //$cv2=$row[3]-$cv2;


                $sql="UPDATE `users` SET `ballance` = `ballance` + '$cv1', `credit`=`credit`-'$cv2'
                    WHERE `name` = '$user_name'";
                $res=mysql_query($sql);
                if($cv2) $GLOBALS['last_error']="Р”РѕР±Р°РІР»РµРЅРѕ $cv1 РёР· $cv, $cv2 СЃРЅСЏС‚Рѕ Р·Р° РєСЂРµРґРёС‚!";
                else $GLOBALS['last_error']="РџРѕРїРѕР»РЅРµРЅРёРµ РїСЂРѕС€Р»Рѕ СѓСЃРїРµС€РЅРѕ!";
             }
          }
          $tm=time();
          $sql="UPDATE `card_list` SET `date_act` = '$tm',`user` = '$row[0]' WHERE `key` = '$keycard' AND `pin` = '$pincard'";
          $res=mysql_query($sql);
          return 1;
       }
       else
       {
          $GLOBALS['last_error']="РќРµС‚ С‚Р°РєРѕРіРѕ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ!";
          return -10;    // Net pol'zovatelya
       }
   }
   else
   {
      if($cv==0)
      {
         $sql="INSERT INTO `user_badlist` (`login`,`pin`, `date`, `ip`) VALUES ('$user_name','$keycard:$pincard', '$tim', '$ip')";
         $res=mysql_query($sql);
      }
      return $cv;
   }

}

//  ============================== +++++++Рљ+ +++++
function oldzstart($sss,$nnn="")
{
 echo"
 <TABLE cellSpacing=0 cellPadding=0 width='100%' border=0>
 <TR>
 <TD class=white width='100%' bgColor=#2d496e height=16 align=center>$sss</TD></TR>
 <TR><TD><table width='100%' border=0><tr><td valign=top>";
}

function oldzend()
{
  echo"
 <br></td></tr></table></TD>
 </TR></TABLE><BR>";
}

//  ============================== +++++++Рљ+ +++++
function zstart($text,$comment="")
{
 	echo"<h2 id='page-title'>$text</h2>
 	<div id='page-info'>$comment</div>
 	<!--PageText-->
	<div id='wikitext'>
	";
}

function zend()
{
  echo"
  <!--/PageText-->
  </div>
  ";
}

// ================================================== ++++++=Рљ+ ++=++++


function text_out($sss)
{
$search = array ("'<script[^>]*?>.*?</script>'si", // Р’С‹СЂРµР·Р°РµС‚СЃСЏ javascript
/* "'<[\/\!]*?[^<>]*?>'si",// Р’С‹СЂРµР·Р°СЋС‚СЃСЏ html-С‚СЌРіРё */
"'(:[)]+)|(:-[)]+)|:sm1:'",    // :))))
"'(:[(]+)|:sm2:'",
"'(;[)]+)|(;-[)]+)|:sm3:'",
"'(:[D]+)|:sm4:'",
"'(:-[D]+)|:sm5:'",
"'(:-[o|O]+)|:sm6:'",
"'(:-[{}]+)|:sm7:'",
"':sm8:'",
"'([\r\n])[\s]+'" // Р’С‹СЂРµР·Р°РµС‚СЃСЏ РїСѓСЃС‚РѕРµ РїСЂРѕСЃС‚СЂР°РЅСЃС‚РІРѕ
);

$replace = array ("",
// "",
"<img src='img/smiles/sm1.gif'>",
"<img src='img/smiles/sm2.gif'>",
"<img src='img/smiles/sm3.gif'>",
"<img src='img/smiles/sm4.gif'>",
"<img src='img/smiles/sm5.gif'>",
"<img src='img/smiles/sm6.gif'>",
"<img src='img/smiles/sm7.gif'>",
"<img src='img/smiles/sm8.gif'>",
 "<br>");

$sss = preg_replace ($search, $replace, $sss);

   $sss=eregi_replace("\n","<br>",$sss);
   return $sss;
}

function russtrtotime($tm)
{
      if($tm!="")
      {
         $tma=@split(" ",$tm);
         $tmd=@split("\.",@$tma[0]);
         $tmt=@split(":",@$tma[1]);
         $tm=@mktime(@$tmt[0],@$tmt[1],@$tmt[2],@$tmd[1],@$tmd[0],@$tmd[2]);
         return $tm;
      }
      else return 0;
}


function counter()
{
   $query="SELECT counter FROM variables";
   $res=mysql_query($query);
   $c=mysql_result($res,0,"counter");
   $c++;
   $query="UPDATE variables SET counter='$c'";
   $res=mysql_query($query);
   echo "<img src='inc/counter_img.php?cc=$c' border=0>";
}

function oldmmenu()
{
echo"<A class=topmenu title='РџРµСЂРµС…РѕРґ РЅР° РіР»Р°РІРЅСѓСЋ СЃС‚СЂР°РЅРёС†Сѓ СЃР°Р№С‚Р°' href='index.php'>РќР° РіР»Р°РІРЅСѓСЋ</A> |
    <A class=topmenu title='РџРѕРёСЃРє РїРѕРґС€РёРїРЅРёРєР° РїРѕ РїР°СЂР°РјРµС‚СЂР°Рј' href='search.php'>РџРѕРёСЃРє</A> |
    <A class=topmenu title='Р¤РѕСЂРјРёСЂРѕРІР°РЅРёРµ РїСЂР°Р№СЃ - Р»РёСЃС‚Р°' href='price.php'>РџСЂР°Р№СЃ - Р»РёСЃС‚</A> |
    <A class=topmenu title='РћР±СЃСѓР¶РґРµРЅРёРµ СЂР°Р·Р»РёС‡РЅС‹С… РІРѕРїСЂРѕСЃРѕРІ' href='forum.php'>Р¤РѕСЂСѓРј</A>
";
     $nm=@$GLOBALS['a_club_admin'];
      if($nm)
      {
         echo" | <a class=topmenu href='stat.php'>РљС‚Рѕ СЃРєРѕР»СЊРєРѕ РёРіСЂР°Р»</a>";
         echo" | <a class=topmenu href='club_service.php'>РђРґРјРёРЅРєР°</a>";
      }



      if(@$GLOBALS['a_manager'])
      echo" | <a class=topmenu href='doc_journal.php'>Р”РѕРєСѓРјРµРЅС‚С‹</a> |
      <a class=topmenu href='card_make.php'>РЎРґРµР»Р°С‚СЊ РєР°СЂС‚С‹</a>";

      if(@$GLOBALS['a_superuser']==1)
      {
         echo" | <a class=topmenu href='club_su_service.php'>РњР°СЃС‚РµСЂ-Р°РґРјРёРЅРёСЃС‚СЂС‚СЂРѕРІР°РЅРёРµ</a>";
      }
}

function mmenu()
{
	echo"
	<ul>
	<li class='home noborder accesskey ak_home'><a class='urllink' href='/index.php'>Р”РѕРјРѕР№</a></li>
	<li class='search accesskey ak_search'><a class='urllink' href='/search.php'>РџРѕРёСЃРє</a></li>
	<li class='search accesskey ak_search'><a class='urllink' href='/vitrina.php'>Р’РёС‚СЂРёРЅР°</a></li>
	<li class='search accesskey ak_search'><a class='urllink' href='/price.php'>РџСЂР°Р№СЃ</a></li>
	<li class='search accesskey ak_search'><a class='urllink' href='/forum/'>Р¤РѕСЂСѓРј</a></li>
	";
	if(@$_SESSION['korz_cnt'])
	{
		echo"<li><a class='urllink' href='doc_magaz.php?mode=korz'>Р’Р°С€Р° РєРѕСЂР·РёРЅР°</a></li>";
	}
	if(@$_SESSION['uid']>0)
	{
		$res=mysql_query("SELECT `id` FROM `priv_message` WHERE `userid`='".@$_SESSION['uid']."'");
		$cnt=mysql_num_rows($res);
		echo"
		<li class='login accesskey ak_login'><a class='urllink' href='login.php'>Р’С‹Р№С‚Рё</a></li>
		<li class='search accesskey ak_search'><a class='urllink' href='priv_msg.php'>РЎРѕРѕР±С‰РµРЅРёСЏ ($cnt)</a></li>";
		if(@$GLOBALS['a_manager'])
		echo"
		<li class='search accesskey ak_search'><a class='urllink' href='card_make.php'>РЎРґРµР»Р°С‚СЊ РєР°СЂС‚С‹</a></li>
		";

		if(@$GLOBALS['a_superuser'])
		{
			echo"<li class='search accesskey ak_search'><a class='urllink' href='club_su_service.php'>РђРґРјРёРЅРєР°</a></li>";
		}
		echo"<li><a href='user.php' accesskey='s' title='Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅС‹Рµ РІРѕР·РјРѕР¶РЅРѕСЃС‚Рё'>Р’РѕР·РјРѕР¶РЅРѕСЃС‚Рё</a></li>";
	}
	else echo"<li class='login accesskey ak_login'><a class='urllink' href='login.php'>Р’РѕР№С‚Рё</a></li>";
	echo"</ul>";

}



// ============================================= ++++ + =++ ++++=++++
function oldtop($sss="")
{
    $sss.=$_SESSION['uid'];

    $ii=$GLOBALS['m_refresh'];
    $GLOBALS['v_top']=1;
    $ll=auth();
    @header("Pragma: no-cache");
    $rr="";
    if($ii) $rr="<META HTTP-EQUIV=\"Refresh\" CONTENT=\"$ii\">";
    if($sss=="") echo"<head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=windows-1251\" />$rr<title>nps.org - РџРѕРґС€РёРїРЅРёРєРё РІ РЅРѕРІРѕСЃРёР±РёСЂСЃРєРµ</title></head>";
    else  echo"<head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=windows-1251\" />$rr<title>nsk-ps.info - $sss</title></head>";


    echo"<link rel='stylesheet' type='text/css' href='css/main.css'>
    <link rel='stylesheet' type='text/css' href='css/base 0000.css'>
    <script type='text/javascript' src='css/00000000.js'></script>
    <script type='text/javascript' src='css/core0000.js'></script>
    <script type='text/javascript' src='css/calendar.js'></script>
    <script type='text/javascript' src='css/DateTime.js'></script>
    <script src='css/function.js' type='text/javascript'></script>
    <body>";

    if($GLOBALS['m_top'])
    {
        echo"<table width=100%>
        <tr><td><IMG src='img/logo.png' border=0>
        <td width=20%>";



if(!($GLOBALS['auth']==1))
   {
     if((@$_GET['mode']!="logout")&&(@$_GET['mode']!="read_logout"))
     {

     echo"
     <form method=post action='login.php'>
     <table class=tbody width=100%>
     <tr><td align=right>Р�РјСЏ:<td><input type=text name=login class=text><td rowspan=2><input type=submit value=Р’С…РѕРґ!>
     <tr><td align=right>РџР°СЂРѕР»СЊ:<td><input type=password name=pass class=text>
     </td></tr>
     </table>
     <br>
     <input type=hidden name=mode value=login>
     </form>
     ";
     }
     else if(@$_GET['mode']=="logout")
     {
        msg("Р’С‹ СѓСЃРїРµС€РЅРѕ РІС‹С€Р»Рё!");
     }
     else msg("Р’С‹ СѓСЃРїРµС€РЅРѕ РІС‹С€Р»Рё, РІСЃРµ СЃРѕРѕР±С‰РµРЅРёСЏ РїРѕРјРµС‡РµРЅС‹ РєР°Рє РїСЂРѕС‡РёС‚Р°РЅРЅС‹Рµ!");

   }
   else
   {
      $nm=@$GLOBALS['name'];
      $ball=@$GLOBALS['u_ballance']*100;
      $lastdate=@$GLOBALS['lastdate'];
      settype($ball,"integer");
      $ball/=100;
      $tt=date("Y-m-d H:i:s",time());
      echo"Р�РјСЏ: <b>$nm</b>: <a href='login.php?mode=logout'>Р’С‹С…РѕРґ</a>, <a href='login.php?mode=read_logout'>c РїРѕРјРµС‚РєРѕР№</a><br>";
      echo"<a href='acc_oper.php'>РЈРїСЂР°РІР»РµРЅРёРµ Р°РєРєР°СѓРЅС‚РѕРј ($ball СЂСѓР±)</a><br>";


      $us_id=$GLOBALS['id'];
      $sql="SELECT id FROM priv_message WHERE `userid` = $us_id AND date>'$lastdate'";
      $res=@mysql_query($sql);
      $row=@mysql_numrows($res);
      if($row) echo"<img alt='Р•СЃС‚СЊ РЅРѕРІС‹Рµ СЃРѕРѕР±С‰РµРЅРёСЏ' src='img/msg.jpg'>";
      echo"<a href='priv_msg.php'>Р›РёС‡РЅС‹Рµ СЃРѕРѕР±С‰РµРЅРёСЏ";
      if(!$row) $row="РЅРµС‚";
      echo" ($row РЅРѕРІС‹С…)</a><br>";

      $res=@mysql_query("SELECT id FROM forum_themes WHERE last_date>'$lastdate'");
      $themes=@mysql_numrows($res);
      $res=@mysql_query("SELECT id FROM forum_messages WHERE date>'$lastdate'");
      $mess=@mysql_numrows($res);
      if($themes||$mess) echo"<img alt='Р•СЃС‚СЊ РЅРѕРІС‹Рµ С‚РµРјС‹' src='img/msg.jpg'><a href='forum.php?mode=new'>
      Р¤РѕСЂСѓРј: РЅРѕРІС‹С… СЃРѕРѕР±С‰РµРЅРёР№:$mess, С‚РµРј:$themes</a><br>";




   }

        echo"</td></tr></table>";
        mmenu();
        echo"<TABLE cellSpacing=0 cellPadding=0 width='100%' border=0>
  <TR>
    <TD width='100%' bgColor=#2d496e><IMG height=1
      src='img/pixel.gif' width=1 border=0></TD></TR>
  <TR>
    <TD width='100%' bgColor=#2d496e><IMG height=1
      src='img/pixel.gif' width=1 border=0></TD></TR>
  <TR>
    <TD width='100%'><IMG height=2 src='img/pixel.gif'
      width=1 border=0></TD>
  <TR>
    <TD width='100%' bgColor=#2d496e><IMG height=1

      src='img/pixel.gif' width=1
border=0></TD></TR></TBODY></TABLE>
    ";
}

echo"<TABLE cellSpacing=0 cellPadding=0 width='100%' border=0><TR>";

   if($GLOBALS['m_left'])
   {
      if(@$_GET['bg'])
         echo"<TD vAlign=top width=165>";
      else
         echo"<TD vAlign=top width=165 bgColor=#f7f7f7>";
      zstart("РЎС‡С‘С‚С‡РёРєРё",150);
      echo"<center>";
      counter();
      zend();



      echo"<BR><IMG height=1 src='img/pixel.gif' width=165 border=0></TD>
      <TD bgColor=#2d496e width=1><IMG height=1 src='img/pixel.gif'
      width=1 border=0></TD>";
   }
   echo"<td valign=top>";
}


function top($sss="")
{
	$ii=$GLOBALS['m_refresh'];
    $GLOBALS['v_top']=1;
    $ll=auth();
    @header("Pragma: no-cache");

	echo"
	<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"
 \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />

<head>
<title>NSK-PS.info : $sss</title>

<style rel='stylesheet' type='text/css' media='screen, projection'/>
	@import url(http://nsk-ps.info/pub/skins/sinorca/basic.css);
	@import url(http://nsk-ps.info/pub/skins/sinorca/layout.css);
	@import url(http://nsk-ps.info/pub/skins/sinorca/sinorca.css);
</style>
<script type='text/javascript' src='css/comm.js'></script>
<link rel='stylesheet' type='text/css' href='css/calendar.css'>
    <link rel='stylesheet' type='text/css' href='css/base 0000.css'>
    <script type='text/javascript' src='css/00000000.js'></script>
    <script type='text/javascript' src='css/core0000.js'></script>
    <script type='text/javascript' src='css/calendar.js'></script>
    <script type='text/javascript' src='css/DateTime.js'></script>

    <link rel='stylesheet' type='text/css' href='pub/skins/sinorca/basic.css'>
    <link rel='stylesheet' type='text/css' href='pub/skins/sinorca/layout.css'>
    <link rel='stylesheet' type='text/css' href='pub/skins/sinorca/sinorca.css'>
	<link rel='stylesheet' type='text/css' href='pub/skins/sinorca/fixed.css'>

	<link rel='stylesheet' type='text/css' href='css/global.css'>

<!--[if IE 5.5]>
<link rel='stylesheet' type='text/css' media='screen, projection' href='http://nsk-ps.info/pub/skins/sinorca/sinorca-ie-5.5.css' />
<![endif]-->

<!--[if IE 6]>
<link rel='stylesheet' type='text/css' media='screen, projection' href='http://nsk-ps.info/pub/skins/sinorca/sinorca-ie-6.0.css' />
<![endif]-->

<!--HTMLHeader-->
<style type='text/css'>
<!--
  ul, ol, pre, dl, p { margin-top:0px; margin-bottom:0px; }
  code.escaped { white-space: nowrap; }
  .vspace { margin-top:1.33em; }
  .indent { margin-left:40px; }
  .outdent { margin-left:40px; text-indent:-40px; }
  a.createlinktext { text-decoration:none; border-bottom:1px dotted gray; }
  a.createlink { text-decoration:none; position:relative; top:-0.5em;
    font-weight:bold; font-size:smaller; border-bottom:none; }
  img { border:0px; }
  .apprlink { font-size:smaller; }
  .Pm { color:purple; font-style:italic; }
  .note { color:green; font-style:italic; }

  dl.dlcol dt { float:left; padding-right:0.5em; }
  dl.dlcol dd { margin-left:13em; }
.editconflict { color:green;
  font-style:italic; margin-top:1.33em; margin-bottom:1.33em; }

  table.markup { border:2px dotted #ccf; width:90%; }
  td.markup1, td.markup2 { padding-left:10px; padding-right:10px; }
  table.vert td.markup1 { border-bottom:1px solid #ccf; }
  table.horiz td.markup1 { width:23em; border-right:1px solid #ccf; }
  table.markup caption { text-align:left; }
  div.faq p, div.faq pre { margin-left:2em; }
  div.faq p.question { margin:1em 0 0.75em 0; font-weight:bold; }
  div.faqtoc div.faq * { display:none; }
  div.faqtoc div.faq p.question
    { display:block; font-weight:normal; margin:0.5em 0 0.5em 20px; line-height:normal; }
  div.faqtoc div.faq p.question * { display:inline; }

    .frame
      { border:1px solid #cccccc; padding:4px; background-color:#f9f9f9; }
    .lfloat { float:left; margin-right:0.5em; }
    .rfloat { float:right; margin-left:0.5em; }
a.varlink { text-decoration:none; }

-->
</style>
<meta name='robots' content='index,follow' />


<link rel='stylesheet' type='text/css'  media='screen' href='http://nsk-ps.info/pub/skins/sinorca/fixed.css' />
<link rel='icon' type='image/png' href='http://nsk-ps.info/favicon.ico' />
<link rel='shortcut icon' type='image/png' href='http://nsk-ps.info/favicon.ico' />

</head>

<body>

<div id='wiki-wrap' class='wiki-wrap'>";

    if($GLOBALS['m_top'])
    {
echo"<!--PageTopFmt-->
<div id='wiki-top' class='wiki-top'>
	<div  id='top-left' >
<p>&nbsp;<strong>РЎРµР№С‡Р°СЃ : </strong>".rusdate ("l, d.m.Y H:i")."
</p></div>
<div  id='top-right' >
<ul><li class='rss noborder'><a rel='nofollow'  class='wikilink' href='http://nsk-ps.info/pmwiki.php?n=Site.AllRecentChanges?action=rss'>RSS</a>
</li><li class='atom'><a rel='nofollow'  class='wikilink' href='http://nsk-ps.info/pmwiki.php?n=Site.AllRecentChanges?action=atom'>ATOM</a>
</li><li class='allchanges accesskey ak_allchanges'>(<a rel='nofollow'  class='wikilink' href='http://nsk-ps.info/pmwiki.php?n=Site.AllRecentChanges'>Р’СЃРµ</a>) <span class='changes accesskey ak_changes'><a rel='nofollow'  class='wikilink' href='http://nsk-ps.info/pmwiki.php?n=Site.RecentChanges'>РёР·РјРµРЅРµРЅРёСЏ</a></span>
</li></ul></div>
<p><br clear='all' />
</p>

</div>
<!--/PageTopFmt-->

<!--PageHeadFmt-->
<div id='wiki-head' class='wiki-head'>
	<h1>NSK-PS.info</h1>
	<form  action='http://nsk-ps.info/pmwiki.php?n=Main.HomePage' method='get'>
		<input type='text' name='q' value='РџРѕРёСЃРє' size='17' />
		<input type='submit' value='>>' size='7'/>
		<input type='hidden' name='action' value='search' />
	</form>
</div>
<!--/PageHeadFmt-->

<!--PageInfoFmt-->
<div id='wiki-info' class='wiki-info'>

<div  id='info-left' >
";
mmenu();

echo"
</div>

<div  id='info-right' >
</div>
<p><br clear='all' />
</p>

</div>
";
}

    if($GLOBALS['m_left'])
    {
echo"<!--PageMenuFmt-->
<div id='wiki-menu' class='wiki-menu'>
	<ul><li><a class='selflink' href='/acc_oper.php'>РЈРїСЂР°РІР»РµРЅРёРµ Р°РєРєР°СѓРЅС‚РѕРј</a>
	<ul><li><a class='selflink' href='http://nsk-ps.info/pmwiki.php?n=Main.HomePage'>Р”РѕРјР°С€РЅСЏСЏ СЃС‚СЂР°РЅРёС†Р°</a>
</li><li><a class='wikilink' href='http://nsk-ps.info/pmwiki.php?n=Main.WikiSandbox'>РџРµСЃРѕС‡РЅРёС†Р°</a></li>
</ul>
<p class='vspace sidehead'><a class='wikilink'>Р Р°Р·РґРµР»С‹</a><ul>
	<li><a class='wikilink' href='/index.php'>Р”РѕРјРѕР№</a></li>
	<li><a class='wikilink' href='/search.php'>РџРѕРёСЃРє</a></li>
	<li><a class='wikilink' href='/vitrina.php'>Р’РёС‚СЂРёРЅР°</a></li>
	<li><a class='wikilink' href='/price.php'>РџСЂР°Р№СЃ</a></li>
	<li><a class='wikilink' href='/forum/'>Р¤РѕСЂСѓРј</a></li>
</ul>
<p class='vspace sidehead'><a class='wikilink'>РќР°С€Рё РґСЂСѓР·СЊСЏ</a><ul>
	<li><a class='wikilink' href='http://tndproject.org'>tndproject.org</a></li>
	<li><a class='wikilink' href='http://salaris.ru'>salaris.ru</a></li>

</li></ul><p class='vspace sidehead'> <a class='wikilink' href='http://nsk-ps.info/pmwiki.php?n=PmWiki.PmWiki'>PmWiki</a>
</p><ul><li><a class='wikilink' href='http://nsk-ps.info/pmwiki.php?n=PmWiki.InitialSetupTasks'>Initial Setup Tasks</a>
</li><li><a class='wikilink' href='http://nsk-ps.info/pmwiki.php?n=PmWiki.BasicEditing'>Basic Editing</a>
</li><li><a class='wikilink' href='http://nsk-ps.info/pmwiki.php?n=PmWiki.DocumentationIndex'>Documentation Index</a>
</li><li><a class='wikilink' href='http://nsk-ps.info/pmwiki.php?n=PmWiki.FAQ'>PmWiki FAQ</a>
</li><li><a class='wikilink' href='http://nsk-ps.info/pmwiki.php?n=PmWiki.PmWikiPhilosophy'>PmWikiPhilosophy</a>
</li><li><a class='wikilink' href='http://nsk-ps.info/pmwiki.php?n=PmWiki.ReleaseNotes'>Release Notes</a>
</li><li><a class='wikilink' href='http://nsk-ps.info/pmwiki.php?n=PmWiki.ChangeLog'>ChangeLog</a>

</li></ul><p class='vspace sidehead'> <a class='urllink' href='http://www.pmwiki.org' rel='nofollow'>pmwiki.org</a>
</p><ul><li><a class='urllink' href='http://www.pmwiki.org/wiki/Cookbook/CookbookBasics' rel='nofollow'>Cookbook (addons)</a>
</li><li><a class='urllink' href='http://www.pmwiki.org/wiki/Cookbook/Skins' rel='nofollow'>Skins (themes)</a>
</li><li><a class='urllink' href='http://www.pmwiki.org/PITS/PITS' rel='nofollow'>PITS (issue tracking)</a>
</li><li><a class='wikilink' href='http://nsk-ps.info/pmwiki.php?n=PmWiki.MailingLists'>Mailing Lists</a>
</li></ul><p class='vspace'  style='text-align: right;'> <span style='font-size:83%'><a class='wikilink' href='http://nsk-ps.info/pmwiki.php?n=Site.SideBar?action=edit'>edit SideBar</a></span>
</p>

</div>
<!--/PageMenuFmt-->

<div id='wiki-page' class='wiki-page'>
";
}
else echo"<div>";


}


function bottom()
{
echo"<hr id='page-end'/></div>";

    if($GLOBALS['m_bottom'])
    {
    global $time_script_start;
    $time_end = getmicrotime();
    $time = $time_end - $time_script_start;
    $time=sprintf("%01.5f",$time);
    echo"
<!--PageFootFmt-->
<div id='wiki-foot' class='wiki-foot'>
	<div  id='foot-left' >
<p>Powered by PmWiki, СЃРіРµРЅРµСЂРёСЂРѕРІР°РЅРѕ Р·Р° $time СЃРµРє.<br clear='all' />
Skin by CarlosAB, optimized by BlackLight
</p></div>
<div  id='foot-right' >
<p>looks borrowed from <a class='urllink' href='http://haran.freeshell.org/oswd/sinorca' rel='nofollow'>http://haran.freeshell.org/oswd/sinorca</a> <br clear='all' />
More skins <span  style='color: white;'><a style='color: white' class='urllink' href='http://www.pmwiki.org/wiki/Cookbook/Skin' rel='nofollow'>here</a></span>

</p></div>
<p><br clear='all' />
</p>

</div>
<!--/PageFootFmt-->
<a target=\"_top\"
href=\"http://top.mail.ru/jump?from=1428464\"><img
src=\"http://db.cc.b5.a1.top.list.ru/counter?id=1428464;t=131\"
border=\"0\" height=\"40\" width=\"88\"
alt=\"Р РµР№С‚РёРЅРі@Mail.ru\"/></a>
<img src='counter.php'>
";
}
echo"
</div>

<!--HTMLFooter-->





</body>
</html>



";


}

function oldbottom()
{
   auth();
   echo"
   <!-- MAIN_END -->
   </td>
   ";
   if($GLOBALS['m_right'])
   {
   echo"
   <TD bgColor=#2d496e width=1><IMG height=1 src='img/pixel.gif'
      width=1 border=0></TD>
      ";

   if(@$_GET['bg'])
      echo"<td width=200 valign=top>";
   else
      echo"<td width=200 valign=top bgColor=#f7f7f7>";




   zend();

//    zstart("Р“РѕР»РѕСЃРѕРІР°РЅРёРµ");
//    $tim=time();
//    $res=mysql_query("SELECT * FROM golos_names WHERE enddate>'$tim' ORDER BY id DESC");
//    $ng=mysql_fetch_row($res);
//    echo"<b>$ng[1]</b><br><br>";
//    $res=mysql_query("SELECT * FROM golos_vars WHERE id='$ng[0]' ORDER BY subid");
//    $i=0;
//    echo"<form action='golos.php' method=get><input type=hidden name=mode value='$ng[0]'>";
//    while($nxt=mysql_fetch_row($res))
//    {
//       $i++;
//       echo"<input type=radio name=gv value=$i>$nxt[1]<br>";
//    }
//    echo"<input type=submit value='Р“РѕР»РѕСЃРѕРІР°С‚СЊ!'></form>";
//    zend();


   echo"</td>";
   }
   echo"
   </tr></table>";
   if($GLOBALS['m_bottom'])
   {
   echo"
   <TABLE cellSpacing=0 cellPadding=0 width='100%' border=0>
  <TBODY>
  <TR>
    <TD width='100%' bgColor=#2d496e><IMG height=1
      src='img/pixel.gif' width=1 border=0></TD></TR>
  <TR>";
  mmenu();
  echo"</TD></TR>
  <TR>
    <TD bgColor=#2d496e><IMG height=1 src='img/pixel.gif'
      width=1 border=0></TD></TR>
  <TR>
    <TD height=60>
      <TABLE width='100%'>
        <TBODY>
        <TR>
          <TD align=middle width='100%'>
          <FONT class=mini>Copyright В© 2003 - 2005 Blacklight<br>
          РЎР°Р№С‚ РѕСЃРЅРѕРІР°РЅ РЅР° РґРІРёР¶РєРµ BlackEngine<br>
          Р�СЃРїРѕР»СЊР·РѕРІР°РЅРёРµ СЃРєСЂРёРїС‚РѕРІ Р±РµР· СЂР°Р·СЂРµС€РµРЅРёСЏ РїСЂР°РІРѕРѕР±Р»Р°РґР°С‚РµР»СЏ РЅРµ РґРѕРїСѓСЃРєР°РµС‚СЃСЏ!
          <BR></FONT></TD></TR></TBODY></TABLE></TD></TR>
  <TR>
    <TD bgColor=#2d496e><IMG height=1 src='inc/pixel.gif'
      width=1 border=0></TD></TR></TBODY></TABLE><BR>
<CENTER>
<TABLE cellSpacing=0 cellPadding=0 width='100%' border=0>
  <TBODY>
  <TR>
    <TD width='100%'>
      <P align=center></P></TD></TR></TBODY></TABLE><BR></CENTER></BODY></HTML>


   ";
   }
}

function errormode()
{
	$rf=getenv("HTTP_REFERER");
	$q=$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING'];
	$us=$_SESSION['name'];
	logadd("ERROR: uncorrect mode! User:$us, referer:$rf query:$q");
	msg("!Р•СЃР»Рё Р’С‹ РІРёРґРёС‚Рµ СЌС‚Рѕ РѕРєРѕС€РєРѕ, Р»РёР±Рѕ РЅР° СЃР°Р№С‚Рµ РїРѕСЏРІРёР»Р°СЃСЊ РЅРµРІРµСЂРЅР°СЏ СЃСЃС‹Р»РєР°, Р»РёР±Рѕ РІС‹ РҐРђРљР•Р ! Р•СЃР»Рё РІС‹ РЅРµ С…Р°РєРµСЂ, СЃРѕРѕР±С‰РёС‚Рµ Р°РґРјРёРЅРёСЃС‚СЂР°С‚РѕСЂСѓ, С‡С‚Рѕ РЅР° СЃС‚Р°СЂРЅРёС†Рµ<br>$rf<br>РµС€С‘ РѕСЃС‚Р°Р»Р°СЃСЊ СЃСЃС‹Р»РєР° РЅР° СЃС‚СЂР°РЅРёС†Сѓ<br>$q<br>Рё РѕРЅ РїРѕСЃС‚Р°СЂР°РµС‚СЃСЏ РёСЃРїСЂР°РІРёС‚СЊ СЌС‚Сѓ РѕС€РёР±РєСѓ! Р�РЅС„РѕСЂРјР°С†РёСЏ РѕР± РѕС€РёР±РєРµ Р·Р°РЅРµСЃРµРЅР° РІ Р»РѕРі!","err","РќРµРґРѕРїСѓСЃС‚РёРјС‹Р№ СЂРµР¶РёРј!");

}


?>
