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

define("MULTIMAG_VERSION", "0.0.1r206");

if(!function_exists('mysql_connect'))
{
	header("500 Internal Server Error");
	echo"<h1>500 Внутренняя ошибка сервера</h1>Расширение php-mysql не найдено. Обратитесь к администратору по адресу <a href='mailto:{$CONFIG['site']['admin_email']}'>{$CONFIG['site']['admin_email']}</a> c описанием проблемы.";
	exit();
}

session_start();

$c=explode('/',__FILE__);$base_path='';
for($i=0;$i<(count($c)-2);$i++)	$base_path.=$c[$i].'/';

if(! @ include_once("$base_path/config_site.php"))
{
	header("500 Internal Server Error");
	echo"<h1>500 Внутренняя ошибка сервера</h1>Конфигурационный файл не найден! Обратитесь к администратору по адресу <a href='mailto:{$CONFIG['site']['admin_email']}'>{$CONFIG['site']['admin_email']}</a> c описанием проблемы.";
	exit();
}

if(!@mysql_connect($CONFIG['mysql']['host'],$CONFIG['mysql']['login'],$CONFIG['mysql']['pass']))
{
	header("503 Service temporary unavariable");
	echo"<h1>503 Сервис временно недоступен!</h1>Не удалось соединиться с сервером баз данных. Возможно он перегружен, и слишком медленно отвечает на запросы, либо выключен. Попробуйте подключиться через 5 минут. Если проблема сохранится - пожалуйста, напишите письмо по адресу <a href='mailto:{$CONFIG['site']['admin_email']}'>{$CONFIG['site']['admin_email']}</a> c описанием проблемы.";
	exit();
}
if(!@mysql_select_db($CONFIG['mysql']['db']))
{
    echo"Невозможно активизировать базу данных! Возможно, база данных повреждена. Попробуйте подключиться через 5 минут. Если проблема сохранится - пожалуйста, напишите письмо по адресу <a href='mailto:{$CONFIG['site']['admin_email']}'>{$CONFIG['site']['admin_email']}</a> c описанием проблемы.";
    exit();
}

$time_start = microtime(true);

mysql_query("SET CHARACTER SET UTF8");
mysql_query("SET character_set_client = UTF8");
mysql_query("SET character_set_results = UTF8");
mysql_query("SET character_set_connection = UTF8");

$ip=getenv("REMOTE_ADDR");
$ag=getenv("HTTP_USER_AGENT");
$rf=getenv("HTTP_REFERER");
$qq=$_SERVER['QUERY_STRING'];
$ff=$_SERVER['REQUEST_URI'];
$tim=time();
$skidka="";
$ncnt=rcv('ncnt');
if(!$ncnt) @mysql_query("INSERT INTO `counter` (`date`,`ip`,`agent`,`refer`,`query`,`file`) VALUES ('$tim','$ip','$ag','$rf','$qq','$ff')");

function exception_handler($exception)
{ 
	$ip=getenv("REMOTE_ADDR");
	$ag=getenv("HTTP_USER_AGENT");
	$rf=getenv("HTTP_REFERER");
	$ff=$_SERVER['REQUEST_URI'];
	$uid=$_SESSION['uid'];
	$s=mysql_real_escape_string($exception->getMessage());
	$ag=mysql_real_escape_string($ag);
	$rf=mysql_real_escape_string($rf);
	$ff=mysql_real_escape_string($ff);
	mysql_query("INSERT INTO `errorlog` (`page`,`referer`,`msg`,`date`,`ip`,`agent`, `uid`) VALUES
	('$ff','$rf','$s',NOW(),'$ip','$ag', '$uid')");
	header("500 Internal error");
	echo"<!DOCTYPE html><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"><title>Error 500: Необработанная внутренняя ошибка</title>
	<style type='text/css'>body{color: #0f0; background-color: #000; text-align: center;}</style></head><body>
	<h1>Необработанная внутренняя ошибка</h1>".get_class($exception).": $s<br>Страница:$ff<br>Сообщение об ошибке передано администратору</body></html>";
  
} 
set_exception_handler('exception_handler');


// ==== Это убрать отсюда =========================================
function ParseCost($cost)
{
    global $skidka;
    if($skidka=="")
    {
        if($_SESSION['uid']) $skidka=7.5;
    }
    return sprintf("%01.2f руб.",($cost*(100/(100+$skidka))));

}

// =================================== Подсветка найденного текста ====================================
function SearchHilight($str,$substr)
{
	if(!$substr) return $str;
	$tmp=split($substr,' '.$str.' ');
	$result='';
	foreach($tmp as $t)
	{
		if(!$result) $result=$t;
		else $result.='<span class=b_selection>'.$substr.'</span>'.$t;
	}
	return $result;
}

// ====================================== Генератор кодов ==================================================
// ==== Это тоже убрать отсюда =========================================
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

// ======================================== Авторизация =====================================================
function need_auth()
{
    global $tmpl;
    if(!auth())
    {
        $SESSION['last_page']=$ff.$qq;
        $_SESSION['cook_test']='data';
        header('Location: login.php');
        $tmpl->msg("Для продолжения необходимо авторизоваться!","notify","Требуется авторизация");
        $tmpl->write();
        exit();
    }
    return 1;
}


function auth()
{
   if($_SESSION['uid']==0) return 0;

   return 1;
}

function getright($object,$uid)
{
	if($uid==1)
	{
		$nxt['read']=1;
		$nxt['write']=1;
		$nxt['edit']=1;
		$nxt['delete']=1;
		return $nxt;
	}
	$res=mysql_query("
	SELECT MAX(`users_grouprights`.`a_read`) AS `read`, MAX(`users_grouprights`.`a_write`) AS `write`, MAX(`users_grouprights`.`a_edit`) AS `edit`, MAX(`users_grouprights`.`a_delete`) AS `delete`
	FROM `users_grouprights`
	INNER JOIN `users_groups` ON `users_groups`.`gid`=`users_grouprights`.`gid` AND ( `users_groups`.`uid`='$uid'
	OR `users_groups`.`uid`='0')
	WHERE `users_grouprights`.`object`='$object'
	GROUP BY `users_grouprights`.`object`");
	$nxt=mysql_fetch_assoc($res);
	return $nxt;
}

// ==================================== Рассылка ===================================================
function SendSubscribe($tema,$msg)
{
	global $CONFIG;
	$res=mysql_query("SELECT `firm_name` FROM `doc_vars` WHERE `id`='{$CONFIG['site']['default_firm']}'");
	if(mysql_errno())	throw new MysqlException("Ошибка получения наименования организации");
	$firm_name=mysql_result($res,0,0);
	$res=mysql_query("(SELECT `name`, `email`, `rname` FROM `users` WHERE `subscribe`='1' AND `confirm`='0')
	UNION
	(SELECT `name`, `email`, `fullname` AS `rname` FROM `doc_agent` WHERE `no_mail`!='0' AND `email`!='')
	");
	if(mysql_errno())	throw new MysqlException("Ошибка получения списка подписчиков");
	while($nxt=mysql_fetch_row($res))
	{
		if($nxt[2])	$nxt[0]="$nxt[2] ($nxt[0])";
        	$txt="
Здравствуйте, $nxt[0]!

$tema
------------------------------------------

$msg

------------------------------------------

Вы получили это письмо потому что подписаны на рассылку сайта http://{$CONFIG['site']['name']}, либо являетесь клиентом $firm_name.
Отказаться от рассылки можно, перейдя по ссылке http://{$CONFIG['site']['name']}/login.php?mode=unsubscribe&email=$nxt[1]
";
		mail($nxt[1],$tema." - {$CONFIG['site']['name']}", $txt ,"Content-type: text/plain; charset=UTF-8\nFrom: {$CONFIG['site']['name']} <{$CONFIG['site']['admin_email']}>");
	}
}

function mailto($email,$tema,$msg,$from="")
{
	global $mail;
	$mail->Body = $msg;  
	$mail->AddAddress($email, $email );  
	$mail->Subject=$tema;
	if($from) $mail->From = $from;  
	return $mail->Send();
}

function date_day($date)
{
   $ee=date("d M Y 00:00:00",$date);
   $tm=strtotime($ee);
   return $tm;
}

function SafeLoadTemplate($template)
{
	global $tmpl, $CONFIG;
	if($template)	$tmpl->LoadTemplate($template);	
}

// Удалить!
function MysqlAssert($str='')
{
	if(mysql_errno())	throw new MysqlException($str);
}


class MysqlException extends Exception
{
	var $sql_error;
	function __construct($text)
	{
		$this->sql_error=mysql_error();
		parent::__construct($text);
		$this->WriteLog();
	}
	
	function WriteLog()
	{
	        $ip=getenv("REMOTE_ADDR");
		$ag=getenv("HTTP_USER_AGENT");
		$rf=getenv("HTTP_REFERER");
		$qq=$_SERVER['QUERY_STRING'];
		$ff=$_SERVER['PHP_SELF'];
		$uid=$_SESSION['uid'];
		$s=mysql_real_escape_string($this->message);
		$hidden_data=mysql_real_escape_string($this->sql_error);
		$ag=mysql_real_escape_string($ag);
		$rf=mysql_real_escape_string($rf);
		$qq=mysql_real_escape_string($qq);
		$ff=mysql_real_escape_string($ff);
		@mysql_query("INSERT INTO `errorlog` (`page`,`referer`,`msg`,`date`,`ip`,`agent`, `uid`) VALUES
		('$ff $qq','$rf','$s $hidden_data',NOW(),'$ip','$ag', '$uid')");	
	}
};

class AccessException extends Exception
{
	function __construct($text='')
	{
		parent::__construct($text);	
	}
};

// ====================================== Шаблон страницы ===============================================
class BETemplate
{
	var $tpl;			// Шаблон
	var $page;		    // Данные страницы
	var $ajax;
	var $tplname;
	var $hide_blocks;		// Скрытые блоки. Блоки, отображать которые не нужно

	function BETemplate()
	{
		global $CONFIG;
		$this->page[0]=$this->page[1]=$this->page[2]=$this->page[3]=$this->page[4]=$this->page[5]=$this->page[6]="";
		if($CONFIG['site']['skin'])	$this->LoadTemplate($CONFIG['site']['skin']);
		else				$this->LoadTemplate('default');
		$this->ajax=0;
		$this->hide_blocks=array();
	}

	function LoadTemplate($s)
	{
		$this->tplname=$s;
		$fd=@file('skins/'.$s.'/style.tpl');
		if($fd)
		{
			$this->tpl="";
			foreach($fd as $item)
				$this->tpl.=$item;
		}
	}
		
	function HideBlock($block)
	{
		$this->hide_blocks[$block]=true;
	}
	
	function ShowBlock($block)
	{
		unset($this->hide_blocks[$block]);
	}
	
// TOP
	function SetTMenu($s)
	{
		$this->page[1]=$s;
	}
	function AddTMenu($s)
	{
		$this->page[1].=$s;
	}
// RIGHT
	function SetRMenu($s)
	{
		$this->page[2]=$s;
	}
	function InsRMenu($s)
	{
		$this->page[2]=$s.$this->page[2];
	}
	function AddRMenu($s)
	{
		$this->page[2].=$s;
	}
// LEFT
	function AddLMenu($s)
	{
		$this->page[5].=$s;
	}
	function SetLMenu($s)
	{
		$this->page[5]=$s;
	}
	function SetTitle($s)
	{
		$this->page[3]=$s;
	}
// TEXT
	function SetText($s)
	{
		$this->page[0]=$s;
	}
	function AddText($s)
	{
		$this->page[0].=$s;
	}
// STYLE
	function AddStyle($s)
	{
		$this->page[4].=$s;
	}

	function AddNote($head,$text,$id)
	{
		$this->page[6].="<div class=note><div id=hd>(<a href='notes.php?mode=wait&amp;n=$id'>x</a>) $head</div><a href='notes.php?n=$id'><div id=txt>$text</div></a></div>";
	}

	function ClearNote()
	{
	   $this->page[6]="";
	}

	// ====================================== Сообщение ======================================================
	function msg($text="",$mode="",$head="")
	{
		if($text=="") return;
		if($mode=="error") $mode="err";
		if($mode=='info') $mode='notify';
		if(($mode!="ok")&&($mode!="err")) $mode="notify";
		if($head=="")
		{
			$msg="Информация:";
			if($mode=="ok") $msg="Сделано!";
			if($mode=="err") $msg="Ошибка!";
		}
		else $msg=$head;

		$this->page[0].="<div class='$mode'><b>$msg</b><br>$text</div>";
	}


	function write()
	{
		@include_once("skins/".$this->tplname."/style.php");
		if($this->ajax)
			echo $this->page[0];
		else
		{
			@include_once("skins/".$this->tplname."/style.php");
			if(function_exists('skin_render'))
			{
				$res=skin_render($this->page,$this->tpl);
			}
			else
			{
				$res=$this->tpl;
				ksort($this->page);
				$sign=array("<!--site-text-->","<!--site-tmenu-->","<!--site-rmenu-->","<!--site-title-->","<!--site-style-->",
				"<!--site-lmenu-->","<!--site-notes-->");
	
				if(!isset($this->hide_blocks['left']))
					$this->page[5]="<td class=lmenu>".$this->page[5]."<td class=fvbl>";
				if(!isset($this->hide_blocks['right']))
					$this->AddStyle(".rmenu { display: table-cell; }");
				else
					$this->AddStyle(".rmenu { display: none; }");
	
				$res=str_replace($sign,$this->page,$res);
			}
			echo"$res";
		}
		global $time_start;
		$time = microtime(true) - $time_start;
		if($time>=3)
			$this->logger("Exec time: $time",1);
	}

    function logger($s, $silent=0, $hidden_data='')
    {
    	
        $ip=getenv("REMOTE_ADDR");
        $ag=getenv("HTTP_USER_AGENT");
        $rf=getenv("HTTP_REFERER");
        $ff=$_SERVER['REQUEST_URI'];
        $uid=$_SESSION['uid'];
        $s=mysql_real_escape_string($s);
        $hidden_data=mysql_real_escape_string($hidden_data);
        $ag=mysql_real_escape_string($ag);
        $rf=mysql_real_escape_string($rf);
        $qq=mysql_real_escape_string($qq);
        $ff=mysql_real_escape_string($ff);
        mysql_query("INSERT INTO `errorlog` (`page`,`referer`,`msg`,`date`,`ip`,`agent`, `uid`) VALUES
        ('$ff','$rf','$s $hidden_data',NOW(),'$ip','$ag', '$uid')");

        if(!$silent)
        $this->msg("$s<br>Страница:$ff<br>Сообщение об ошибке передано администратору","err","Внутренняя ошибка!");
    }
};



global $tmpl;
global $uid;
global $mode;
$tmpl=new BETemplate;
$mode=rcv('mode');
if(isset($_SESSION['uid']))	$uid=$_SESSION['uid'];
if($uid=='') $uid=0;

require_once("include/wikiparser.php");

$wikiparser=new WikiParser();

$wikiparser->reference_wiki	= "http://{$CONFIG['site']['name']}/wiki/";
$wikiparser->reference_site	= "http://{$CONFIG['site']['name']}/";
$wikiparser->image_uri		= "/share/var/wikiphoto/";
$wikiparser->ignore_images	= false;

?>
