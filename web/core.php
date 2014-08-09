<?php
//	MultiMag v0.2 - Complex sales system
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

/**
@mainpage Cистема комплексного учёта торговли multimag. Документация разработчика.
<h2>Часто используемые классы</h2>
BETemplate в core.php содержит шаблонизатор страницы. \n
doc.core.php содержит основные функции работы с документами \n
Vitrina (vitrina.php) формирует все страницы витрины и может быть перегружен шаблоном \n
doc_Nulltype является базовым классом для всех документов системы \n
BaseReport используется для генерации отчётов \n
От AsyncWorker наследуются обработчики, выполняющиеся независимо от веб сервера \n
PosEditor содержит методы для работы с редактором списка товаров \n
Смотри <a href='annotated.html'>структуры данных</a> и <a href='hierarchy.html'>иерархию классов</a>, чтобы получить полное представление о классах системы
**/

/// Автозагрузка классов для ядра
function core_autoload($class_name){
	global $CONFIG;
	$class_name = strtolower($class_name);
	$class_name = str_replace('\\', '/', $class_name);
	include_once $CONFIG['site']['location']."/include/".$class_name.'.php';
}

spl_autoload_register('core_autoload');

/// Класс для работы с IP адресами IPv6
class ipv6
{
	/// Является ли строка IPv6 адресом ?
	function is_ipv6($ip = "")
	{
		if($ip=='')	return false;
		if (substr_count($ip,":") > 0 && substr_count($ip,".") == 0)
			return true;
		else	return false;
	}

	/// Является ли строка IPv4 адресом ?
	function is_ipv4($ip = "")
	{
		if($ip=='')	return false;
		return !ipv6::is_ipv6($ip);
	}

	/// Возвращает IP адрес клиента
	function get_ip()
	{
		return  getenv ("REMOTE_ADDR");
	}

	/// Преобразует заданный IPv6 адрес в полную форму
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

	/// Преобразует заданный IPv6 адрес в краткую форму
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

/// Вычисляет максимально допустимый размер вложений в байтах
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

/// @brief Форматирование номера телефона, записанного в международном формате, в легкочитаемый вид.
///
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

/// Округление в нужную сторону
/// @param number Исходное число
/// @param precision Точность округления
/// @param direction Направление округления
function roundDirect($number, $precision = 0, $direction = 0)
{
	if ($direction==0 )	return round($number, $precision);
	else
	{
		$factor = pow(10, -1 * $precision);
		return ($direction<0)
			? floor($number / $factor) * $factor
			: ceil($number / $factor) * $factor;
	}
}

/// @brief Обработчик неперехваченных исключений
///
/// Записывает событие в журнал ошибок и выдаёт сообщение. При правильной архитектуре программы никогда не должен быть вызван.
function exception_handler($exception)
{
	global $db;
	if($db)
	{
		$uid=@$_SESSION['uid'];
		settype($uid,"int");
		$ip=$db->real_escape_string(getenv("REMOTE_ADDR"));
		$s=$db->real_escape_string($exception->getMessage());
		$ag=$db->real_escape_string(getenv("HTTP_USER_AGENT"));
		$rf=$db->real_escape_string(urldecode(getenv("HTTP_REFERER")));
		$ff=$db->real_escape_string($_SERVER['REQUEST_URI']);
		$db->query("INSERT INTO `errorlog` (`page`,`referer`,`msg`,`date`,`ip`,`agent`, `uid`) VALUES
		('$ff','$rf','$s',NOW(),'$ip','$ag', '$uid')");
	}
	header('HTTP/1.0 500 Internal error');
	header('Status: 500 Internal error');
	$s=html_out($exception->getMessage());
	$ff=html_out($_SERVER['REQUEST_URI']);
	echo"<!DOCTYPE html><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"><title>Error 500: Необработанная внутренняя ошибка</title>
	<style type='text/css'>body{color: #000; background-color: #eee; text-align: center;}</style></head><body>
	<h1>Необработанная внутренняя ошибка</h1>".get_class($exception).": $s<br>Страница:$ff<br>Сообщение об ошибке передано администратору</body></html>";
}
set_exception_handler('exception_handler');

/// Подсветка подстроки в строке, используя span class=b_selection
/// @param str Исходная строка
/// @param substr Строка, которую необходимо подсветить
function SearchHilight($str,$substr) {
	if(!$substr) return $str;
	$tmp = explode($substr,' '.$str.' ');
	$result = '';
	foreach($tmp as $t) {
		if(!$result) $result = $t;
		else $result.="<span class='b_selection'>$substr</span>$t";
	}
	return $result;
}

/// @brief Генератор псевдоуникального кода.
///
/// Используется для генерации легкозапоминаемых паролей.
/// @param $num Если true - использовать только цифры.
/// @param $minlen Минимальная длина кода.
/// @param $maxlen Максимальная длина кода.
function keygen_unique($num=0, $minlen=5, $maxlen=12) {
	if($minlen<1) $minlen = 5;
	if($maxlen>10000) $maxlen = 10000;
	if($maxlen<$minlen) $maxlen = $minlen;
	if(!$num) {
		$sstr = "bcdfghjklmnprstvwxz";
		$gstr = "aeiouy1234567890aeiouy";
		$rstr = "aeiouy1234567890aeiouybcdfghjklmnprstvwxz";
		$sln = strlen($sstr)-1;
		$gln = strlen($gstr)-1;
		$rln = strlen($rstr)-1;
	}
	else {
		$sstr="135790";
		$gstr="24680";
		$rstr="1234567890";
		$sln = strlen($sstr)-1;
		$gln = strlen($gstr)-1;
		$rln = strlen($rstr)-1;
	}
	$r = rand(0,$rln);
	$s = $rstr[$r];
	$ln = rand($minlen,$maxlen);
	for($i=1;$i<$ln;$i++) {
		$a = strpos($sstr, $s[$i-1]);
		if($a!==false) {
			$r=rand(0,$gln);
			$s.=$gstr[$r];
		}
		else {
			$r=rand(0,$sln);
			$s.=$sstr[$r];
		}
	}
	return $s;
}

// ======================================= Обработчики ввода переменных ====================================

/// Обёртка над $_REQUEST, позволяющая задать значение по умолчанию
/// @param $varname Имя элемента $_REQUEST
/// @param $dev Возвращаемое значение, если искомый элемент отсутствует
function request($varname,$def='')
{
	if(isset($_REQUEST[$varname]))	return $_REQUEST[$varname];
	return $def;
}

/// Получает часть массива $_REQUEST, позволяет задать значение по умолчанию для отсутствующих элементов
/// @param $varname Массив значений ключенй $_REQUEST
/// @param $dev Возвращаемое значение, если искомый элемент отсутствует
function requestA($var_array, $def='')
{
	$a=array_fill_keys($var_array, $def);
	$v=array_intersect_key($_REQUEST, $a);
	$r=array_merge($a,$v);
	return $r;
}

/// Безопасное получение целого значения
function rcvint($varname, $def=0)
{
	if(isset($_REQUEST[$varname]))	return intval($_REQUEST[$varname]);
	return $def;
}

/// Безопасное получение числа заданной точности
function rcvrounded($varname, $round=3, $def=0)
{
	if(isset($_REQUEST[$varname]))	return round($_REQUEST[$varname],$round);
	return $def;
}

/// Безопасное получение строки с датой
function rcvdate($varname, $def='1970-01-01')
{
	if(isset($_REQUEST[$varname]))	return date("Y-m-d", strtotime($_REQUEST[$varname]));
	return $def;
}

/// Безопасное получение строки с временем
function rcvtime($varname, $def='1970-01-01')
{
	if(isset($_REQUEST[$varname]))	return date("H:i:s", strtotime($_REQUEST[$varname]));
	return $def;
}

/// Безопасное получение строки с датой и временем
function rcvdatetime($varname, $def='1970-01-01')
{
	if(isset($_REQUEST[$varname]))	return date("Y-m-d H:i:s", strtotime($_REQUEST[$varname]));
	return $def;
}

/// Преобразование HTML сущностей в их ASCII представление. Обёртка над html_entity_decode.
function html_in($data)
{
	return html_entity_decode($data, ENT_QUOTES, "UTF-8");
}

/// Обёртка над htmlentities
function html_out($data)
{
	return htmlentities($data, ENT_QUOTES, "UTF-8");
}

// =================================== Аутентификация и контроль привилегий ============================================
/// @brief Требование аутентификации.
///
/// Выполняет редирект на страницу аутентификации, если аутентификация не пройдена.
function need_auth()
{
	global $tmpl;
	if(!auth())
	{
		$_SESSION['last_page']=$_SERVER['REQUEST_URI'];
		$_SESSION['cook_test']='data';
		header('Location: /login.php');
		$tmpl->msg("Для продолжения необходимо выполнить вход!","info","Требуется аутентификация");
		$tmpl->write();
		exit();
	}
	return 1;
}

/// Проверка аутентификации
function auth() {
	return (@$_SESSION['uid']==0)?0:1;
}

/// Есть ли привилегия доступа к указанному объекту для указанной операции
/// @param $object Имя объекта, для которого нужно проверить привилегии
/// @param $action Имя действия, для осуществления которого нужно проверить привилегии
/// @param $no_redirect Если false - то в случае отсутствия привилегий, и если не пройдена аутентификация, выполняет редирект на страницу аутентификации
function isAccess($object, $action,$no_redirect=false)
{
	global $db;
	$uid=@$_SESSION['uid'];
	if($uid==1)	return true;
	$res=$db->query("(
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
	$access=($res->num_rows>0)?true:false;
	if((!$uid) && (!$access) && (!$no_redirect))	need_auth();
	return $access;
}

/// Транслитерация строки
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
    
    foreach($tr as $s=>$r)
	$str=mb_ereg_replace ($s, $r, $str);	
    return $str;
}



// ==================================== Рассылка ===================================================

/// @brief Выполнение рассылки сообщения на электронную почту по базе агентов и зарегистрированных пользователей.
///
/// В текст рассылки автоматически добавляется информация о том, как отказаться от рассылки
/// @param $title Заголовок сообщения
/// @param $subject Тема email сообщения
/// @param $msg Тело сообщения
/// @param $list_id ID рассылки
function SendSubscribe($title, $subject, $msg, $list_id='') {
	global $CONFIG, $db;
	if(!$list_id)
		$list_id = md5($subject.$msg.microtime()).'.'.date("dmY").'.'.$CONFIG['site']['name'];
	require_once($CONFIG['location'].'/common/email_message.php');
	$res = $db->query("SELECT `firm_name` FROM `doc_vars` WHERE `id`='{$CONFIG['site']['default_firm']}'");
	list($firm_name) = $res->fetch_row();
	$res = $db->query("(SELECT `name`, `reg_email` AS `email`, `real_name` FROM `users` WHERE `reg_email_subscribe`='1' AND `reg_email_confirm`='1' AND `reg_email`!='')
	UNION
	(SELECT `name`, `email`, `fullname` AS `real_name` FROM `doc_agent` WHERE `no_mail`='0' AND `email`!='')
	");
	while($nxt = $res->fetch_assoc()) {
		if($nxt['real_name'])	$nxt['name']="{$nxt['real_name']} ({$nxt['name']})";
        	$txt="
Здравствуйте, {$nxt['name']}!

$title
------------------------------------------

$msg

------------------------------------------

Вы получили это письмо потому что подписаны на рассылку сайта {$CONFIG['site']['display_name']} ( http://{$CONFIG['site']['name']}?from=email ), либо являетесь клиентом $firm_name.
Отказаться от рассылки можно, перейдя по ссылке http://{$CONFIG['site']['name']}/login.php?mode=unsubscribe&email={$nxt['email']}&from=email
";
		$email_message = new email_message_class();
		$email_message->default_charset = "UTF-8";
		$email_message->SetEncodedEmailHeader("To", $nxt['email'], $nxt['email']);
		$email_message->SetEncodedHeader("Subject", $subject." - {$CONFIG['site']['name']}");
		$email_message->SetEncodedEmailHeader("From", $CONFIG['site']['admin_email'], $CONFIG['site']['display_name']);
		$email_message->SetHeader("Sender", $CONFIG['site']['admin_email']);
		$email_message->SetHeader("List-id", '<'.$list_id.'>');
		$email_message->SetHeader("List-Unsubscribe",
			"http://{$CONFIG['site']['name']}/login.php?mode=unsubscribe&email={$nxt['email']}&from=list_unsubscribe");
		$email_message->SetHeader("X-Multimag-version", MULTIMAG_VERSION);
		
		$email_message->AddQuotedPrintableTextPart($txt);
		$error = $email_message->Send();

		if(strcmp($error,""))	throw new Exception($error);
	}
}

/// Отправляет оповещение администратору сайта по всем доступным каналам связи
/// @param $text Тело сообщения
/// @param $subject Тема сообщения
function sendAdmMessage($text,$subject='') {
	global $CONFIG;
	if($subject=='')	$subject="Admin mail from {$CONFIG['site']}";

	if($CONFIG['site']['doc_adm_email'])
		mailto($CONFIG['site']['doc_adm_email'],$subject ,$text);

	if($CONFIG['site']['doc_adm_jid'] && $CONFIG['xmpp']['host'])
	{
		try
		{
			require_once($CONFIG['location'].'/common/XMPPHP/XMPP.php');
			$xmppclient = new XMPPHP_XMPP( $CONFIG['xmpp']['host'], $CONFIG['xmpp']['port'], $CONFIG['xmpp']['login'], $CONFIG['xmpp']['pass'], 'xmpphp', '');
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

/// Загрузка шаблона с заданным названием
function SafeLoadTemplate($template)
{
	global $tmpl, $CONFIG;
	if($template)	$tmpl->loadTemplate($template);
}

/// Получить данные профиля пользователя по uid
function getUserProfile($uid)
{
	global $db;
	settype($uid,'int');
	$user_profile=array();
	$user_profile['main']=array();
	$user_profile['dop']=array();

	$res=$db->query("SELECT * FROM `users` WHERE `id`='$uid'");
	if(!$res->num_rows)	return $user_profile;	// Если не найден
	$user_profile['main']	= $res->fetch_assoc();
	unset($user_profile['main']['pass']);	// В целях безопасности
	unset($user_profile['main']['pass_change']);
	$res=$db->query("SELECT `param`,`value` FROM `users_data` WHERE `uid`='$uid'");
	while($nn=$res->fetch_row())
	{
		$user_profile['dop'][$nn[0]]=$nn[1];
	}
	return $user_profile;
}

/// Класс шаблонизатора вывода страницы. Содержит методы, отвечающие за загрузку темы оформления, заполнения страницы содержимым и отправки в броузер
class BETemplate {

	var $tpl;			///< Шаблон
	var $ajax = 0;			///< Флаг ajax выдачи
	var $tplname;			///< Наименование загруженного шаблона
	var $page_blocks = array();	///< Новые блоки шаблонизатора. Ассоциативный массив. Замена устаревшего $page
	var $hide_blocks = array();	///< Скрытые блоки. Блоки, отображать которые не нужно
	var $breadcrumbs = array();	///< "Хлебные крошки" - массив в формате текст->ссылка

	function __construct() {
		global $CONFIG;
		if ($CONFIG['site']['skin'])
			$this->loadTemplate($CONFIG['site']['skin']);
		else
			$this->loadTemplate('default');
	}

	/// Загрузка шаблона по его имени
	function loadTemplate($s) {
		$this->tplname = $s;
		$fd = @file('skins/' . $s . '/style.tpl');
		if ($fd) {
			$this->tpl = "";
			foreach ($fd as $item)
				$this->tpl.=$item;
		}
	}

	/// Установить флаг скрытия заданной части страницы
	/// @param $block Имя блока страницы
	function hideBlock($block) {
		$this->hide_blocks[$block] = true;
	}

	/// Снять флаг скрытия заданной части страницы
	/// @param $block Имя блока страницы
	function showBlock($block) {
		unset($this->hide_blocks[$block]);
	}

	/// Задать HTML содержимое шапки страницы
	function setTop($s) {
		@$this->page_blocks['top'] = $s;
	}

	/// Добавить HTML содержимое в конец шапки страницы
	function addTop($s) {
		@$this->page_blocks['top'].=$s;
	}

	/// Задать HTML содержимое правой колонки страницы
	function setRight($s) {
		@$this->page_blocks['right'] = $s;
	}

	/// Вставить HTML содержимое в начало правой колонки страницы
	function insRight($s) {
		@$this->page_blocks['right'] = $s . $this->page_blocks['right'];
	}

	/// Добавить HTML содержимое в конец правой колонки страницы
	function addRight($s) {
		@$this->page_blocks['right'].=$s;
	}

	/// Вставить HTML содержимое в начало левой колонки страницы
	function addLeft($s) {
		@$this->page_blocks['left'].=$s;
	}

	/// Задать HTML содержимое левой колонки страницы
	function setLeft($s) {
		@$this->page_blocks['left'] = $s;
	}

	/// Задать текст заголовка (обычно тэг title) страницы
	function setTitle($s) {
		@$this->page_blocks['title'] = $s;
	}

	/// Задать содержимое мета-тэга keywords
	function setMetaKeywords($s) {
		@$this->page_blocks['meta_keywords'] = $s;
	}

	/// Задать содержимое мета-тэга description
	function setMetaDescription($s) {
		@$this->page_blocks['meta_description'] = $s;
	}

	/// Задать HTML содержимое основного блока страницы (content)
	function setContent($s) {
		@$this->page_blocks['content'] = $s;
	}

	/// Добавить HTML содержимое к основному блоку страницы (content)
	function addContent($s) {
		@$this->page_blocks['content'].=$s;
	}

	/// Добавить содержимое к таблице стилей страницы (тэг style)
	function addStyle($s) {
		@$this->page_blocks['stylesheet'].=$s;
	}

	/// Задать содержимое к пользовательского блока страницы
	/// @param $block_name Имя блока. Не должно совпадать с именами стандартных блоков.
	/// @param $data HTML данные блока
	function setCustomBlockData($block_name, $data) {
		@$this->page_blocks[$block_name] = $data;
	}

	/// Добавить содержимое к пользовательскому блоку страницы
	/// @param $block_name Имя блока. Не должно совпадать с именами стандартных блоков.
	/// @param $data HTML данные блока
	function addCustomBlockData($block_name, $data) {
		@$this->page_blocks[$block_name].=$data;
	}

	/// Добавить блок (div) с информацией к основному блоку страницы (content)
	/// @param $text Текст сообщения
	/// @param $mode Вид сообщения: ok - сообщение об успехе, err - сообщение об ошибке, info - информационное сообщение
	/// @param $head Заголовок сообшения
	function msg($text = "", $mode = "", $head = "") {
		if ($text == "")
			return;
		if ($mode == "error")
			$mode = "err";
		if ($mode == 'info')
			$mode = 'notify';
		if (($mode != "ok") && ($mode != "err"))
			$mode = "notify";
		if ($head == "") {
			$msg = "Информация:";
			if ($mode == "ok")
				$msg = "Сделано!";
			if ($mode == "err")
				$msg = "Ошибка!";
		}
		else
			$msg = $head;

		@$this->page_blocks['content'].="<div class='$mode'><b>$msg</b><br>$text</div>";
	}

	/// Установить "хлебные крошки"
	function setBrearcrumbs($data) {
		if(is_array($data))
			$this->breadcrumbs = $data;
	}
	
	/// Добавить "хлебные крошки"
	function addBreadcrumb($name, $link) {
		$this->breadcrumbs[$name] = array('name'=>$name, 'link'=>$link);
	}
	

	/// Сформировать HTML и отправить его, в соответствии с загруженным шаблоном и установленным содержимым блоков
	function write() {
		global $time_start;
		if (stripos(getenv("HTTP_USER_AGENT"), "MSIE") !== FALSE) {
			$this->page_blocks['notsupportbrowser'] = "<div style='background: #ffb; border: 1px #fff outset; padding: 3px; padding-right: 15px; text-align: right; font-size: 14px;'><img src='/img/win/important.png' alt='info' style='float: left'>
			Вероятно, Вы используете неподдерживаемую версию броузера.<br><b>Для правильной работы сайта, скачайте и установите последнюю версию <a href='http://mozilla.com'>Mozilla</a>, <a href='http://www.opera.com/download/'>Opera</a> или <a href='http://www.google.com/intl/ru/chrome/browser/'>Chrome</a></b><div style='clear: both'></div></div>";
		}
		$time = microtime(true) - $time_start;
		$this->page_blocks['gentime'] = round($time, 4);

		@include_once("skins/" . $this->tplname . "/style.php");
		if ($this->ajax)
			echo @$this->page_blocks['content'];
		else {
			@include_once("skins/" . $this->tplname . "/style.php");
			$this->page_blocks['breadcrumbs'] = '';
			if(count($this->breadcrumbs)) {
				$this->page_blocks['breadcrumbs'] .= "<div id='breadcrumbs'>";
				foreach($this->breadcrumbs as $item) {
					if($item['link'])
						$this->page_blocks['breadcrumbs'] .= "<a href='{$item['link']}'>".html_out($item['name'])."</a> ";
					else	$this->page_blocks['breadcrumbs'] .= html_out($item['name']);
				}
				$this->page_blocks['breadcrumbs'] .= "</div>";
			}
			if (function_exists('skin_prepare')) {
				$res = skin_prepare();
			}

			if (function_exists('skin_render')) {
				$res = skin_render($this->page_blocks, $this->tpl);
			} else {
				$signatures = array();
				foreach ($this->page_blocks as $key => $value) {
					$signatures[] = "<!--site-$key-->";
				}
				$res = str_replace($signatures, $this->page_blocks, $this->tpl);
			}
			echo"$res";
		}
		$time = microtime(true) - $time_start;
		if ($time >= 3)
			$this->logger("Exec time: $time", 1); /// Записывам ошибку, если скрипт долго работает
	}

	/// Записать сообщение об ошибке в журнал и опционально вывести на страницу
	/// @param $s Основной текст сообщения
	/// @param $silent Если TRUE, то сообщение не выводится на страницу. FALSE по умолчанию.
	/// @param $hidden_data Скрытый текст сообщения об ошибке. Заносится в журнал, на страницу не выводится.
	/// TODO: нужен класс регистрации ошибок, с уровнями ошибок, возможностью записи в файл, отправки на email, jabber, sms и пр.
	function logger($s, $silent = 0, $hidden_data = '') {
		global $db;
		if (isset($_SESSION['uid']))
			$uid = $_SESSION['uid'];
		else
			$uid = 0;
		settype($uid, "int");
		$ip = $db->real_escape_string(getenv("REMOTE_ADDR"));
		$s_sql = $db->real_escape_string($s);
		$ag = $db->real_escape_string(getenv("HTTP_USER_AGENT"));
		$rf = $db->real_escape_string(urldecode(getenv("HTTP_REFERER")));
		$ff = $db->real_escape_string($_SERVER['REQUEST_URI']);
		$db->query("INSERT INTO `errorlog` (`page`,`referer`,`msg`,`date`,`ip`,`agent`, `uid`) VALUES
		('$ff','$rf','$s_sql',NOW(),'$ip','$ag', '$uid')");

		if (!$silent) {
			$s = html_out($s);
			$ff = html_out($_SERVER['REQUEST_URI']);
			$this->msg("$s<br>Страница:$ff<br>Сообщение об ошибке передано администратору", "err", "Внутренняя ошибка!");
		}
		return $db->insert_id;
	}

}

;

/// Класс-исключение используется для информирования о отсутствии привилегий на доступ к запрошенной функции
class AccessException extends Exception
{
	function __construct($text='')
	{
		header('HTTP/1.0 403 Forbidden');
		parent::__construct("Нет доступа: ".$text);
	}
};

/// Класс-исключение используется для информирования о отсутствии запрашиваемого объекта. Устанавливает заголовок 404 Not found
class NotFoundException extends Exception
{
	function __construct($text='')
	{
		header('HTTP/1.0 404 Not found');
		parent::__construct($text);
	}
};

/// Класс-исключение для информирования об ошибке при выполнении myqsl запроса. Устарело, к удалению.
class MysqlException extends Exception
{
	var $sql_error;
	var $sql_errno;
	var $db;
	function __construct($text,$_db=0)
	{
		global $db;
		if(!$_db)	$_db=$db;
		$this->db=$_db;
		$this->sql_error=$this->db->error;
		$this->sql_errno=$this->db->errno;
		switch($this->sql_errno)
		{
			case 1062:	$text.=" {$this->sql_errno}:Дублирование - такая запись уже существует в базе данных. Исправьте данные, и попробуйте снова.";	break;
			case 1452:	$text.=" {$this->sql_errno}:Нарушение связи - введённые данные недопустимы, либо предпринята попытка удаления объекта, от которого зависят другие объекты. Проверьте правильность заполнения полей.";	break;
		}
		parent::__construct($text);
		$this->WriteLog();
	}

	/// Записывает событие в журнал ошибок
	/// TODO: нужен класс регистрации ошибок, с уровнями ошибок, возможностью записи в файл, отправки на email, jabber, sms и пр.
	function WriteLog()
	{
	        global $db;
	        $uid=@$_SESSION['uid'];
		settype($uid,"int");
		$ip=$db->real_escape_string(getenv("REMOTE_ADDR"));
		$s=$db->real_escape_string(get_class($this).': '.$this->message .' '. $this->sql_errno . ': ' . $this->sql_error);
		$ag=$db->real_escape_string(getenv("HTTP_USER_AGENT"));
		$rf=$db->real_escape_string(urldecode(getenv("HTTP_REFERER")));
		$ff=$db->real_escape_string($_SERVER['REQUEST_URI']);
		$db->query("INSERT INTO `errorlog` (`page`,`referer`,`msg`,`date`,`ip`,`agent`, `uid`) VALUES
		('$ff','$rf','$s',NOW(),'$ip','$ag', '$uid')");
	}
};


/// Базовый класс для создания автожурналируемых исключений
class AutoLoggedException extends Exception
{
	function __construct($text='')
	{
		parent::__construct($text);
		$this->WriteLog();
	}

	/// Записывает событие в журнал ошибок
	/// TODO: нужен класс регистрации ошибок, с уровнями ошибок, возможностью записи в файл, отправки на email, jabber, sms и пр.
	protected function WriteLog()
	{
	        global $db;
	        $uid=$_SESSION['uid'];
		settype($uid,"int");
		$ip=$db->real_escape_string(getenv("REMOTE_ADDR"));
		$s=$db->real_escape_string (get_class($this).': '.$this->message);
		$ag=$db->real_escape_string(getenv("HTTP_USER_AGENT"));
		$rf=$db->real_escape_string(urldecode(getenv("HTTP_REFERER")));
		$ff=$db->real_escape_string($_SERVER['REQUEST_URI']);
		$db->query("INSERT INTO `errorlog` (`page`,`referer`,`msg`,`date`,`ip`,`agent`, `uid`) VALUES
		('$ff','$rf','$s',NOW(),'$ip','$ag', '$uid')");
	}
};

//class DB extends MysqiExtended {
//	protected static $_instance; 
//	private function __construct(){}
//	private function __clone() {}
//	private function __wakeup() {}
//	
//	public static function getInstance(){
//		if (self::$_instance === null) {
//		self::$_instance = new self;   
//		}
//	return self::$_instance;
//	}
//};


if(!function_exists('mysqli_query'))
{
	header("HTTP/1.0 500 Internal Server Error");
	die("<h1>500 Внутренняя ошибка сервера</h1>Расширение php-mysqli не найдено. Программа установлена некорректно. Обратитесь к администратору c описанием проблемы.");
}

if(!function_exists('mb_internal_encoding'))
{
	header("HTTP/1.0 500 Internal Server Error");
	die("<h1>500 Внутренняя ошибка сервера</h1>Расширение php-mbstring не найдено. Программа установлена некорректно. Обратитесь к администратору c описанием проблемы.");
}

$time_start = microtime(true);
if(!function_exists('mb_internal_encoding'))
{
	header("HTTP/1.0 500 Internal Server Error");
	die("<h1>500 Внутренняя ошибка сервера</h1>Расширение mbstring не установлено! Программа установлена некорректно. Обратитесь к администратору c описанием проблемы.");
}

session_start();
mb_internal_encoding("UTF-8");

$base_path = dirname(dirname(__FILE__));
if(! include_once("$base_path/config_site.php"))
{
	header("HTTP/1.0 500 Internal Server Error");
	die("<h1>500 Внутренняя ошибка сервера</h1>Конфигурационный файл не найден! Программа установлена некорректно. Обратитесь к администратору c описанием проблемы.");
}

include_once($CONFIG['location']."/common/core.common.php");

if($CONFIG['site']['force_https'])
	header('Location: https://'.$_SERVER["HTTP_HOST"].$_SERVER['REQUEST_URI'], true, 301);

if(!isset($CONFIG['site']['display_name']))	$CONFIG['site']['display_name']=$CONFIG['site']['name'];



$db = @ new MysqiExtended($CONFIG['mysql']['host'], $CONFIG['mysql']['login'], $CONFIG['mysql']['pass'], $CONFIG['mysql']['db']);



if($db->connect_error)
{
	header("HTTP/1.0 503 Service temporary unavariable");
	die("<h1>503 Сервис временно недоступен!</h1>Не удалось соединиться с сервером баз данных. Возможно он перегружен, и слишком медленно отвечает на запросы, либо выключен. Попробуйте подключиться через 5 минут. Если проблема сохранится - пожалуйста, напишите письмо <a href='mailto:{$CONFIG['site']['admin_email']}'>{$CONFIG['site']['admin_email']}</a> c описанием проблемы: ErrorCode: {$db->connect_errno} ({$db->connect_error})");
}

// Включаем автоматическую генерацию исключений для mysql
mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);

if(!$db->set_charset("utf8"))
{
    header("HTTP/1.0 503 Service temporary unavariable");
    die("<h1>503 Сервис временно недоступен!</h1>Невозможно задать кодировку соединения с базой данных: ".$db->error);
}


// m_ysql_query("SET CHARACTER SET UTF8");
// m_ysql_query("SET character_set_client = UTF8");
// m_ysql_query("SET character_set_results = UTF8");
// m_ysql_query("SET character_set_connection = UTF8");

header("X-Powered-By: MultiMag ".MULTIMAG_VERSION);

/// TODO: Убрать обращения этих переменных из других файлов, и сделать их локальными
$tim=time();
$ip=$db->real_escape_string(getenv("REMOTE_ADDR"));
$ag=$db->real_escape_string(getenv("HTTP_USER_AGENT"));
$rf=$db->real_escape_string(urldecode(getenv("HTTP_REFERER")));
$qq=$db->real_escape_string(urldecode($_SERVER['REQUEST_URI'].'?'.$_SERVER['QUERY_STRING']));
$ff=$db->real_escape_string($_SERVER['SCRIPT_NAME']);

if(!isset($_REQUEST['ncnt']))
{
	$db->query("INSERT INTO `counter` (`date`,`ip`,`agent`,`refer`,`query`,`file`) VALUES ('$tim','$ip','$ag','$rf','$qq','$ff')");
}

/// TODO: Пересмотреть принцип работы со скидками
$skidka="";

$tmpl=new BETemplate;

/// Глобальная переменная должна быть заменена в местах использования на $_REQUEST['mode']
if(isset($_REQUEST['mode']))	$mode=$_REQUEST['mode'];
else				$mode='';

/// Нужно вычистить глобальную переменную UID везде
if(isset($_SESSION['uid']))	$uid=$_SESSION['uid'];
else				$uid=0;

/// Должно быть убрано, должно подключаться и создаваться по необходимости
require_once("include/imgresizer.php");
require_once("include/wikiparser.php");

$wikiparser=new WikiParser();
$wikiparser->reference_wiki	= "/wiki/";
$wikiparser->reference_site	= @($_SERVER['HTTPS']?'https':'http')."://{$_SERVER['HTTP_HOST']}/";
$wikiparser->image_uri		= "/share/var/wikiphoto/";
$wikiparser->ignore_images	= false;

$dop_status=array('new'=>'Новый', 'err'=>'Ошибочный', 'inproc'=>'В процессе', 'ready'=>'Готов', 'ok'=>'Отгружен');
if(is_array(@$CONFIG['doc']['status_list']))	$CONFIG['doc']['status_list']=array_merge($dop_status, $CONFIG['doc']['status_list']);
else						$CONFIG['doc']['status_list']=$dop_status;
?>
