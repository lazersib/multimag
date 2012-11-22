<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2012, BlackLight, TND Team, http://tndproject.org
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

if(!function_exists('mysql_connect'))
{
	header("500 Internal Server Error");
	echo"<h1>500 Внутренняя ошибка сервера</h1>Расширение php-mysql не найдено. Обратитесь к администратору по адресу <a href='mailto:{$CONFIG['site']['admin_email']}'>{$CONFIG['site']['admin_email']}</a> c описанием проблемы.";
	exit();
}

if(!function_exists('mb_internal_encoding'))
{
	header("500 Internal Server Error");
	echo"<h1>500 Внутренняя ошибка сервера</h1>Расширение php-mbstring не найдено. Обратитесь к администратору по адресу <a href='mailto:{$CONFIG['site']['admin_email']}'>{$CONFIG['site']['admin_email']}</a> c описанием проблемы.";
	exit();
}

$time_start = microtime(true);
session_start();
mb_internal_encoding("UTF-8");

$c=explode('/',__FILE__);$base_path='';
for($i=0;$i<(count($c)-2);$i++)	$base_path.=$c[$i].'/';

if(! include_once("$base_path/config_site.php"))
{
	header("500 Internal Server Error");
	echo"<h1>500 Внутренняя ошибка сервера</h1>Конфигурационный файл не найден! Обратитесь к администратору по адресу <a href='mailto:{$CONFIG['site']['admin_email']}'>{$CONFIG['site']['admin_email']}</a> c описанием проблемы.";
	exit();
}

include_once($CONFIG['location']."/common/core.common.php");

if($CONFIG['site']['force_https'])
{
	header('Location: https://'.$_SERVER["HTTP_HOST"].$_SERVER['REQUEST_URI'], true, 301);
}

if(!isset($CONFIG['site']['display_name']))	$CONFIG['site']['display_name']=$CONFIG['site']['name'];

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

mysql_query("SET CHARACTER SET UTF8");
mysql_query("SET character_set_client = UTF8");
mysql_query("SET character_set_results = UTF8");
mysql_query("SET character_set_connection = UTF8");

header("X-Powered-By: MultiMag ".MULTIMAG_VERSION);

$ip=mysql_real_escape_string(getenv("REMOTE_ADDR"));
$ag=mysql_real_escape_string(getenv("HTTP_USER_AGENT"));
$rf=mysql_real_escape_string(urldecode(getenv("HTTP_REFERER")));
$qq=mysql_real_escape_string(urldecode($_SERVER['REQUEST_URI'].'?'.$_SERVER['QUERY_STRING']));
$ff=mysql_real_escape_string($_SERVER['SCRIPT_NAME']);
$tim=time();
$skidka="";
if(!isset($_REQUEST['ncnt'])) @mysql_query("INSERT INTO `counter` (`date`,`ip`,`agent`,`refer`,`query`,`file`) VALUES ('$tim','$ip','$ag','$rf','$qq','$ff')");

class ipv6
{
	function is_ipv6($ip = "")
	{
		if($ip=='')	return false;
		if (substr_count($ip,":") > 0 && substr_count($ip,".") == 0)
			return true;
		else	return false;
	}

	function is_ipv4($ip = "")
	{
		if($ip=='')	return false;
		return !ipv6::is_ipv6($ip);
	}

	function get_ip()
	{
		return  getenv ("REMOTE_ADDR");
	}

	function uncompress_ipv6($ip ="")
	{
		if($ip=='')	return false;
		if(strstr($ip,"::" ))
		{
			$e = explode(":", $ip);
			$s = 8-sizeof($e)+1;
			foreach($e as $key=>$val)
			{
				if ($val == "")
					for($i==0;$i<=$s;$i++)		$newip[] = 0;
				else	$newip[] = $val;
			}
			$ip = implode(":", $newip);
		}
		return $ip;
	}

	function compress_ipv6($ip ="")
	{
		if($ip=='')	return false;
		if(!strstr($ip,"::" ))
		{
			$e = explode(":", $ip);
			$zeros = array(0);
			$result = array_intersect ($e, $zeros );
			if (sizeof($result) >= 6)
			{
				if ($e[0]==0) $newip[] = "";
				foreach($e as $key=>$val)
					if ($val !=="0") $newip[] = $val;
				$ip = implode("::", $newip);
			}
		}
		return $ip;
	}
}

function get_max_upload_filesize()
{
    $max_post = trim(ini_get('post_max_size'));
    $last = strtolower($max_post[strlen($max_post)-1]);
    switch($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $max_post *= 1024;
        case 'm':
            $max_post *= 1024;
        case 'k':
            $max_post *= 1024;
    }

    $max_fs = trim(ini_get('upload_max_filesize'));
    $last = strtolower($max_fs[strlen($max_fs)-1]);
    switch($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $max_fs *= 1024;
        case 'm':
            $max_fs *= 1024;
        case 'k':
            $max_fs *= 1024;
    }


    return min($max_fs, $max_post);
}

/// Форматирование номера телефона, записанного в международном формате, в легкочитаемый вид
/// Номера, не начинающиеся с +, возвращаются без форматирования
function formatPhoneNumber($phone)
{
	if($phone[0]!='+')	return $phone;
	$divs=array('','','-','','','-','','','-','','-','','-','','-','','-','','-','','','','','','','','','','','','','','','','','','','','');
	$fphone='';
	$len=strlen($phone);
	for($i=0;$i<$len;$i++)
	{
		$fphone.=$divs[$i].$phone[$i];
	}
	return $fphone;
}

function exception_handler($exception)
{
	$ip=getenv("REMOTE_ADDR");
	$ag=getenv("HTTP_USER_AGENT");
	$rf=urldecode(getenv("HTTP_REFERER"));
	$ff=$_SERVER['REQUEST_URI'];
	$uid=$_SESSION['uid'];
	$s=mysql_real_escape_string($exception->getMessage());
	$ag=mysql_real_escape_string($ag);
	$rf=mysql_real_escape_string($rf);
	$ff=mysql_real_escape_string($ff);
	mysql_query("INSERT INTO `errorlog` (`page`,`referer`,`msg`,`date`,`ip`,`agent`, `uid`) VALUES
	('$ff','$rf','$s',NOW(),'$ip','$ag', '$uid')");
	header('HTTP/1.0 404 Internal error');
	header('Status: 404 Internal error');
	echo"<!DOCTYPE html><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"><title>Error 500: Необработанная внутренняя ошибка</title>
	<style type='text/css'>body{color: #000; background-color: #eee; text-align: center;}</style></head><body>
	<h1>Необработанная внутренняя ошибка</h1>".get_class($exception).": $s<br>Страница:$ff<br>Сообщение об ошибке передано администратору</body></html>";
}
set_exception_handler('exception_handler');

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
/// Переделать: cохранить безопасность записи данных в базу, но убрать преобразование в html-символы
function rcv($varname,$def="")
{
	$dt=htmlentities(@$_POST[$varname],ENT_QUOTES,"UTF-8");
	if($dt=="") $dt=htmlentities(@$_GET[$varname],ENT_QUOTES,"UTF-8");
	if($dt) return $dt;
	else return $def;
}

/// Безопасное получение целого значения
function rcvint($varname,$def=0)
{
	if(isset($_REQUEST[$varname]))	return intval($_REQUEST[$varname]);
	return $def;
}

/// Получение строкового значения для записи в базу
function rcvstrsql($varname,$def='')
{
	if(isset($_REQUEST[$varname]))	return mysql_real_escape_string($_REQUEST[$varname]);
	return $def;
}


function unhtmlentities ($string)
{
	return html_entity_decode ($string,ENT_QUOTES,"UTF-8");
}

function getpost($varname,$def="")
{
	if(isset($_POST[$varname]))	return $_POST[$varname];
	if(isset($_GET[$varname]))	return $_GET[$varname];
	return $def;
}

/// INSERT данных из массива c экранированием
function mysql_escaped_insert($table,$array_data)
{
	$keys='';
	$values='';
	foreach($array_data as $id => $data)
	{
		if($keys)
		{
			$keys.=', ';
			$values.=', ';
		}
		$keys.='`'.mysql_real_escape_string($id).'`';
		if(is_int($data))	$values.=$data;
		else			$values.='\''.mysql_real_escape_string($data).'\'';
	}
	mysql_query("INSERT INTO `$table` ($keys) VALUES ($values)");
}

// =================================== Аутентификация и контроль привилегий ============================================
/// Требование аутентификации
function need_auth()
{
	global $tmpl;
	if(!auth())
	{
		$SESSION['last_page']=$ff.$qq;
		$_SESSION['cook_test']='data';
		header('Location: /login.php');
		$tmpl->msg("Для продолжения необходимо выполнить вход!","info","Требуется аутентификация");
		$tmpl->write();
		exit();
	}
	return 1;
}

// Проверка аутентификации
function auth()
{
	return (@$_SESSION['uid']==0)?0:1;
}

// Получить привилегии (read, write, edit, delete) доступа к указанному объекту.
// Не используется для остальных
// Не рекомендуется к использованию с версии 0.0.1r221
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

// Есть ли право доступа к указанному объекту для указанной операции
function isAccess($object, $action,$no_redirect=false)
{
	$uid=@$_SESSION['uid'];
	if($uid==1)	return true;
	$res=mysql_query("(
	SELECT `users_acl`.`id` FROM `users_acl` WHERE `uid`='$uid' AND `object`='$object' AND `action`='$action'
	) UNION (
	SELECT `users_groups_acl`.`id` FROM `users_groups_acl`
	INNER JOIN `users_in_group` ON `users_in_group`.`gid`=`users_groups_acl`.`gid`
	WHERE `uid`='$uid' AND `object`='$object' AND `action`='$action')
	UNION (
	SELECT `users_groups_acl`.`id` FROM `users_groups_acl`
	INNER JOIN `users_in_group` ON `users_in_group`.`gid`=`users_groups_acl`.`gid`
	WHERE `uid`='0' AND `object`='$object' AND `action`='$action')
	UNION(
	SELECT `users_acl`.`id` FROM `users_acl` WHERE `uid`='0' AND `object`='$object' AND `action`='$action')");
	if(mysql_errno())	throw new MysqlException("Выборка привилегий не удалась");
	$access=(mysql_num_rows($res)>0)?true:false;
	if((!$uid) && (!$access) && (!$no_redirect))	need_auth();
	return $access;
}

// Транслитерация
function translitIt($str)
{
    $tr = array(
        "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G",
        "Д"=>"D","Е"=>"E","Ж"=>"J","З"=>"Z","И"=>"I",
        "Й"=>"Y","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N",
        "О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T",
        "У"=>"U","Ф"=>"F","Х"=>"H","Ц"=>"TS","Ч"=>"CH",
        "Ш"=>"SH","Щ"=>"SCH","Ъ"=>"","Ы"=>"YI","Ь"=>"",
        "Э"=>"E","Ю"=>"YU","Я"=>"YA","а"=>"a","б"=>"b",
        "в"=>"v","г"=>"g","д"=>"d","е"=>"e","ж"=>"j",
        "з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
        "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
        "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
        "ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"y",
        "ы"=>"yi","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya"
    );
    return strtr($str,$tr);
}



// ==================================== Рассылка ===================================================
function SendSubscribe($tema,$msg)
{
	global $CONFIG;
	$res=mysql_query("SELECT `firm_name` FROM `doc_vars` WHERE `id`='{$CONFIG['site']['default_firm']}'");
	if(mysql_errno())	throw new MysqlException("Ошибка получения наименования организации");
	$firm_name=mysql_result($res,0,0);
	$res=mysql_query("(SELECT `name`, `reg_email`, `real_name` FROM `users` WHERE `reg_email_subscribe`='1' AND `reg_email_confirm`='1')
	UNION
	(SELECT `name`, `email`, `fullname` AS `rname` FROM `doc_agent` WHERE `no_mail`='0' AND `email`!='')
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

Вы получили это письмо потому что подписаны на рассылку сайта {$CONFIG['site']['display_name']} ( http://{$CONFIG['site']['name']} ), либо являетесь клиентом $firm_name.
Отказаться от рассылки можно, перейдя по ссылке http://{$CONFIG['site']['name']}/login.php?mode=unsubscribe&email=$nxt[1]
";
		mail($nxt[1],$tema." - {$CONFIG['site']['name']}", $txt ,"Content-type: text/plain; charset=UTF-8\nFrom: {$CONFIG['site']['display_name']} <{$CONFIG['site']['admin_email']}>");
	}
}

function sendAdmMessage($text,$subject='')
{
	global $CONFIG;
	if($subject=='')	$subject="Admin mail from {$CONFIG['site']}";

	if($CONFIG['site']['doc_adm_email'])
		mailto($CONFIG['site']['doc_adm_email'],$subject ,$text, $from);

	if($CONFIG['site']['doc_adm_jid'] && $CONFIG['xmpp']['host'])
	{
		try
		{
			require_once($CONFIG['location'].'/common/XMPPHP/XMPP.php');

			$xmppclient = new XMPPHP_XMPP( $CONFIG['xmpp']['host'], $CONFIG['xmpp']['port'], $CONFIG['xmpp']['login'], $CONFIG['xmpp']['pass'], 'xmpphp', '', $printlog=false, $loglevel=XMPPHP_Log::LEVEL_INFO);
			$xmppclient->connect();
			$xmppclient->processUntil('session_start');
			$xmppclient->presence();
			$xmppclient->message($CONFIG['site']['doc_adm_jid'], $text);
			$xmppclient->disconnect();
		}
		catch(XMPPHP_Exception $e)
		{
			$tmpl->logger("Невозможно отправить сообщение по XMPP!","err");
		}
	}
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

// ====================================== Шаблон страницы ===============================================
class BETemplate
{
	var $tpl;			///< Шаблон
	var $ajax=0;			///< Флаг ajax выдачи
	var $tplname;			///< Наименование загруженного шаблона
	var $page_blocks=array();	///< Новые блоки шаблонизатора. Ассоциативный массив. Замена устаревшего $page
	var $hide_blocks=array();	///< Скрытые блоки. Блоки, отображать которые не нужно

	function BETemplate()
	{
		global $CONFIG;
		if($CONFIG['site']['skin'])	$this->LoadTemplate($CONFIG['site']['skin']);
		else				$this->LoadTemplate('default');
	}
	/// Загрузка шаблона по его имени
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

	/// Установить флаг скрытия заданной части страницы
	function HideBlock($block)
	{
		$this->hide_blocks[$block]=true;
	}

	/// Снять флаг скрытия заданной части страницы
	function ShowBlock($block)
	{
		unset($this->hide_blocks[$block]);
	}

	/// Задать HTML содержимое шапки страницы
	function SetTop($s)
	{
		@$this->page_blocks['top']=$s;
	}

	/// Добавить HTML содержимое в конец шапки страницы
	function AddTop($s)
	{
		@$this->page_blocks['top'].=$s;
	}

	/// Задать HTML содержимое правой колонки страницы
	function SetRight($s)
	{
		@$this->page_blocks['right']=$s;
	}

	/// Вставить HTML содержимое в начало правой колонки страницы
	function InsRight($s)
	{
		@$this->page_blocks['right']=$s.$this->page_blocks['right'];
	}

	/// Добавить HTML содержимое в конец правой колонки страницы
	function AddRight($s)
	{
		@$this->page_blocks['right'].=$s;
	}

	/// Вставить HTML содержимое в начало левой колонки страницы
	function AddLeft($s)
	{
		@$this->page_blocks['left'].=$s;
	}

	/// Задать HTML содержимое левой колонки страницы
	function SetLeft($s)
	{
		@$this->page_blocks['left']=$s;
	}

	/// Задать текст заголовка (обычно тэг title) страницы
	function SetTitle($s)
	{
		@$this->page_blocks['title']=$s;
	}

	/// Задать содержимое мета-тэга keywords
	function SetMetaKeywords($s)
	{
		@$this->page_blocks['meta_keywords']=$s;
	}

	/// Задать содержимое мета-тэга description
	function SetMetaDescription($s)
	{
		@$this->page_blocks['meta_description']=$s;
	}

	/// Добавить HTML содержимое к основному блоку страницы (content)
	/// Не рекомендуется к использованию. Вместо этого используйте SetContent
	/// @sa SetContent
	function SetText($s)
	{
		@$this->page_blocks['content']=$s;
	}

	/// Задать HTML содержимое основного блока страницы (content)
	/// Не рекомендуется к использованию. Вместо этого используйте AddContent
	/// @sa AddContent
	function AddText($s)
	{
		@$this->page_blocks['content'].=$s;
	}

	/// Задать HTML содержимое основного блока страницы (content)
	function SetContent($s)
	{
		@$this->page_blocks['content']=$s;
	}

	/// Добавить HTML содержимое к основному блоку страницы (content)
	function AddContent($s)
	{
		@$this->page_blocks['content'].=$s;
	}

	/// Добавить содержимое к таблице стилей страницы (тэг style)
	function AddStyle($s)
	{
		@$this->page_blocks['stylesheet'].=$s;
	}

	/// Задать содержимое к пользовательского блока страницы
	/// @param block_name Имя блока. Не должно совпадать с именами стандартных блоков.
	/// @param data HTML данные блока
	function SetCustomBlockData($block_name, $data)
	{
		@$this->page_blocks[$block_name]=$data;
	}
	/// Добавить содержимое к пользовательскому блоку страницы
	/// @param block_name Имя блока. Не должно совпадать с именами стандартных блоков.
	/// @param data HTML данные блока
	function AddCustomBlockData($block_name, $data)
	{
		@$this->page_blocks[$block_name].=$data;
	}

	/// Добавить блок (div) с информацией к основному блоку страницы (content)
	/// @param text Текст сообщения
	/// @param mode Вид сообщения: ok - сообщение об успехе, err - сообщение об ошибке, info - информационное сообщение
	/// @param head Заголовок сообшения
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

		@$this->page_blocks['content'].="<div class='$mode'><b>$msg</b><br>$text</div>";
	}

	/// Сформировать HTML и отправить его, в соответствии с загруженным шаблоном и установленным содержимым блоков
	function write()
	{
		global $time_start;
		if(stripos(getenv("HTTP_USER_AGENT"), "MSIE" )!==FALSE )
		{
			$this->page_blocks['notsupportbrowser']="<div style='background: #ffb; border: 1px #fff outset; padding: 3px; padding-right: 15px; text-align: right; font-size: 14px;'><img src='/img/win/important.png' alt='info' style='float: left'>
			Вероятно, Вы используете неподдерживаемую версию броузера.<br><b>Для правильной работы сайта, скачайте и установите последнюю версию <a href='http://mozilla.com'>Mozilla</a>, <a href='http://www.opera.com/download/'>Opera</a> или <a href='http://www.google.com/intl/ru/chrome/browser/'>Chrome</a></b><div style='clear: both'></div></div>";
		}
		$time = microtime(true) - $time_start;
		$this->page_blocks['gentime']=round($time,4);

		@include_once("skins/".$this->tplname."/style.php");
		if($this->ajax)		echo $this->page_blocks['content'];
		else
		{
			@include_once("skins/".$this->tplname."/style.php");
			if(function_exists('skin_prepare'))
			{
				$res=skin_prepare();
			}

			if(function_exists('skin_render'))
			{
				$res=skin_render($this->page_blocks,$this->tpl);
			}
			else
			{
				$signatures=array();
				foreach($this->page_blocks as $key => $value)
				{
					$signatures[]="<!--site-$key-->";
				}
				$res=str_replace($signatures,$this->page_blocks,$this->tpl);
			}
			echo"$res";
		}
		$time = microtime(true) - $time_start;
		if($time>=3)
			$this->logger("Exec time: $time",1);
	}

	/// Записать сообщение об ошибке в журнал и опционально вывести на страницу
	/// @param s Основной текст сообщения
	/// @param silent Если TRUE, то сообщение не выводится на страницу. FALSE по умолчанию.
	/// @param hidden_data Скрытый текст сообщения об ошибке. Заносится в журнал, на страницу не выводится.
	function logger($s, $silent=0, $hidden_data='')
	{
		$ip=getenv("REMOTE_ADDR");
		$ag=getenv("HTTP_USER_AGENT");
		$rf=getenv("HTTP_REFERER");
		$ff=$_SERVER['REQUEST_URI'];
		$uid=@$_SESSION['uid'];
		$s=mysql_real_escape_string($s);
		$hidden_data=mysql_real_escape_string($hidden_data);
		$ag=mysql_real_escape_string($ag);
		$rf=mysql_real_escape_string($rf);
		$ff=mysql_real_escape_string($ff);
		mysql_query("INSERT INTO `errorlog` (`page`,`referer`,`msg`,`date`,`ip`,`agent`, `uid`) VALUES
		('$ff','$rf','$s $hidden_data',NOW(),'$ip','$ag', '$uid')");

		if(!$silent)
		$this->msg("$s<br>Страница:$ff<br>Сообщение об ошибке передано администратору","err","Внутренняя ошибка!");
	}
};


class AccessException extends Exception
{
	function __construct($text='')
	{
		parent::__construct($text);
	}
};

class MysqlException extends Exception
{
	var $sql_error;
	var $sql_errno;
	function __construct($text)
	{
		$this->sql_error=mysql_error();
		$this->sql_errno=mysql_errno();
		switch($this->sql_errno)
		{
			case 1062:	$text.=" {$this->sql_errno}:Дублирование - такая запись уже существует в базе данных. Исправьте данные, и попробуйте снова.";	break;
			case 1452:	$text.=" {$this->sql_errno}:Нарушение связи - введённые данные недопустимы, либо предпринята попытка удаления объекта, от которого зависят другие объекты. Проверьте правильность заполнения полей.";	break;
		}
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
		$uid=@$_SESSION['uid'];
		$s=mysql_real_escape_string($this->message);
		$hidden_data=mysql_real_escape_string($this->sql_errno).": ".mysql_real_escape_string($this->sql_error);
		$ag=mysql_real_escape_string($ag);
		$rf=mysql_real_escape_string($rf);
		$qq=mysql_real_escape_string($qq);
		$ff=mysql_real_escape_string($ff);
		@mysql_query("INSERT INTO `errorlog` (`page`,`referer`,`msg`,`date`,`ip`,`agent`, `uid`) VALUES
		('$ff $qq','$rf','$s $hidden_data',NOW(),'$ip','$ag', '$uid')");
	}
};

global $tmpl;
global $uid;
global $mode;
$tmpl=new BETemplate;

/// Глобальная переменная должна быть заменена в местах использования на $_REQUEST['mode']
if(isset($_REQUEST['mode']))	$mode=$_REQUEST['mode'];
else				$mode='';

/// Нужно вычистить глобальную переменную UID везде
if(isset($_SESSION['uid']))	$uid=$_SESSION['uid'];
if($uid=='') $uid=0;

/// Должно быть убрано, должно подключаться и создаваться по необходимости
require_once("include/imgresizer.php");
require_once("include/wikiparser.php");

$wikiparser=new WikiParser();
$wikiparser->reference_wiki	= "/wiki/";
$wikiparser->reference_site	= @($_SERVER['HTTPS']?'https':'http')."://{$_SERVER['HTTP_HOST']}/";
$wikiparser->image_uri		= "/share/var/wikiphoto/";
$wikiparser->ignore_images	= false;

?>
