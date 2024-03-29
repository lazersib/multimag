<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2018, BlackLight, TND Team, http://tndproject.org
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

/// TODO: После рефакторинга, файл должен быть удалён. Код переностится, главным образом, в include/webcore.php

/// Автозагрузка классов для ядра
function core_autoload($class_name){
    global $CONFIG;
    $lower_class_name = strtolower($class_name);
    $lower_class_name = str_replace('\\', '/', $lower_class_name);
    if($CONFIG['site']['skin']) {
        $fname = $CONFIG['site']['location'].'/skins/'.$CONFIG['site']['skin'].'/include/'.$lower_class_name.'.php';
        if(is_readable($fname)) {
            include_once $fname;
            return;
        }
    }    
    $filename = dirname(__DIR__)
        .DIRECTORY_SEPARATOR
        .str_replace('\\', DIRECTORY_SEPARATOR, $class_name)
        .'.php';
    if(is_readable($filename)) {
        include_once $filename;
        return;
    }
}

spl_autoload_register('core_autoload');

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

/// @brief Обработчик неперехваченных исключений
///
/// Записывает событие в журнал ошибок и выдаёт сообщение. При правильной архитектуре программы никогда не должен быть вызван.
function exception_handler($exception)
{
	global $db;
	writeLogException($exception);
	header('HTTP/1.0 500 Internal error');
	header('Status: 500 Internal error');
	$s = html_out($exception->getMessage());
	$ff = html_out($_SERVER['REQUEST_URI']);
	echo"<!DOCTYPE html><html><meta charset=\"utf-8\"><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
            <head><title>Error 500: Необработанная внутренняя ошибка</title>
            <style type='text/css'>body{color: #000; background-color: #eee; text-align: center;}</style></head><body>
            <h1>Необработанная внутренняя ошибка</h1>".get_class($exception).": $s<br>Страница:$ff<br>Сообщение об ошибке передано администратору</body></html>";
        die();
}
set_exception_handler('exception_handler');

/// Подсветка подстроки в строке, используя span class=b_selection
/// @param str Исходная строка
/// @param substr Строка, которую необходимо подсветить
function SearchHilight($str, $substr) {
    if (!$substr)
        return $str;
    $tmp = explode($substr, ' ' . $str . ' ');
    $result = '';
    foreach ($tmp as $t) {
        if (!$result)
            $result = $t;
        else
            $result.="<span class='b_selection'>" . html_out($substr) . "</span>$t";
    }
    return $result;
}

/// Нормализация номера телефона
function normalizePhone($phone) {
    $phone = preg_replace("/[^0-9+]/", "", $phone);
    if(strlen($phone)<3) {
        return false;
    }
    $phoneplus = $phone[0]=='+';
    $phone = preg_replace("/[^0-9]/", "", $phone);
    if($phoneplus && $phone[0]==7 && strlen($phone)==11) {
        return '+'.$phone;
    } elseif(!$phoneplus && $phone[0]==8 && strlen($phone)==11) {
        return '+7'.substr($phone,1);
    } elseif(!$phoneplus && $phone[0]==9 && strlen($phone)==10) {
        return '+7'.$phone; 
    } else {
        return false;
    }
}

/// Отсылает заголовок перенаправления в броузер и завершает скрипт
function redirect($url) {
    if (headers_sent()) {
        return false;
    }

    //$url = HTTP::absoluteURI($url);
    header('Location: '. $url, true, 301);

    if ( isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] != 'HEAD') {
        echo '
<p>Redirecting to: <a href="'.str_replace('"', '%22', $url).'">'
             .htmlspecialchars($url).'</a>.</p>
<script type="text/javascript">
//<![CDATA[
if (location.replace == null) {
location.replace = location.assign;
}
location.replace("'.str_replace('"', '\\"', $url).'");
// ]]>
</script>';
    }
    exit;
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

/// Проверяет был ли получен запрос через ajax
/// @return boolean
function isAjaxRequest()
{
	return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
		&& !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
		&& strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/// Получает часть массива $_REQUEST, позволяет задать значение по умолчанию для отсутствующих элементов
/// @param $varname Массив значений ключей $_REQUEST
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
		redirect('/login.php');
		//$tmpl->msg("Для продолжения необходимо выполнить вход!","info","Требуется аутентификация");
		$tmpl->write();
		exit();
	}
	return 1;
}

/// Проверка аутентификации
function auth() {
	return (@$_SESSION['uid']==0)?0:1;
}

// Проверка, не принадлежит ли текущая сессия другому пользователю
function testForeignSession() {
    global $db, $tmpl;
    if(auth()) {
        $res = $db->query("SELECT `last_session_id` FROM `users` WHERE `id`=".intval($_SESSION['uid']));
        if($res->num_rows) {
            list($stored_session_id) = $res->fetch_row();
            if($stored_session_id != session_id()) {
                $_SESSION['another_device'] = 1;
                $_SESSION['uid'] = 0;
                need_auth();
            }
        }
    }
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

/// Загрузка шаблона с заданным названием
function SafeLoadTemplate($template)
{
	global $tmpl, $CONFIG;
	if($template)	$tmpl->loadTemplate($template);
}

/// Получить данные профиля пользователя по uid
function getUserProfile($uid) {
    global $db;
    settype($uid, 'int');
    $user_profile = array();
    $user_profile['main'] = array();
    $user_profile['dop'] = array();

    $res = $db->query("SELECT * FROM `users` WHERE `id`='$uid'");
    if (!$res->num_rows) { // Если не найден
        return $user_profile;
    } 
    $user_profile['main'] = $res->fetch_assoc();
    unset($user_profile['main']['pass']); // В целях безопасности
    unset($user_profile['main']['pass_change']);
    $res = $db->query("SELECT `param`,`value` FROM `users_data` WHERE `uid`='$uid'");
    while ($nn = $res->fetch_row()) {
        $user_profile['dop'][$nn[0]] = $nn[1];
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
        
        /// Добавить виджет *вкладки*
        /// @param $list Массив со списком вкладок
        /// @param $opened Код открытой вкладки        
        /// @param $link_prefix Префикс ссылки вкладки
        /// @param $param_name Параметр ссылки выбора вкладки
        function addTabsWidget($list, $opened, $link_prefix, $param_name) {
            $str = \widgets::getEscapedTabsWidget($list, $opened, $link_prefix, $param_name);
            $this->addContent($str);
        }
        
        function addTableWidget($table_header, $table_body, $head_each_lines = 100) {
            $str = \widgets::getTable($table_header, $table_body, $head_each_lines);
            $this->addContent($str);
        }

	/// Добавить блок (div) с информацией к основному блоку страницы (content)
	/// @param $text Текст сообщения
	/// @param $mode Вид сообщения: ok - сообщение об успехе, err - сообщение об ошибке, info - информационное сообщение
	/// @param $head Заголовок сообшения
	function msg($text = "", $mode = "", $head = "") {
            if ($text == "") {
                return;
            }
            switch($mode) {
                case 'err':
                case 'ok':
                    break;
                case 'error':
                    $mode = 'err';
                    break;
                case 'info':
                    $mode = 'notify';
                    break;
                default:
                    $mode = "notify";
            }
            if ($head == "") {
                switch($mode) {
                    case 'err':
                        $head = "Ошибка";
                        break;
                    case 'ok':
                        $head = "Сделано";
                        break;
                    default:
                        $head = "Информация";
                }
            }
            @$this->page_blocks['content'].="<div class='$mode'><b>$head</b><br>$text</div>";
	}
        
        /// Вывод сообщения об ошибке
        /// @param $text Текст сообщения
	/// @param $head Заголовок сообшения
        function errorMessage($text, $head = "") {            
            $this->msg($text, 'err', $head);
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
			Вероятно, Вы используете неподдерживаемую версию броузера.<br>
                        <b>Для правильной работы сайта, скачайте и установите последнюю версию <a href='http://mozilla.com'>Mozilla</a>, <a href='http://www.opera.com/download/'>Opera</a> или <a href='http://www.google.com/intl/ru/chrome/browser/'>Chrome</a></b><div style='clear: both'></div></div>";
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
                /// TODO: Сделать что-нибудь с этой записью
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
		$db->query("INSERT INTO `errorlog` (`page`,`referer`,`msg`,`date`,`ip`,`useragent`, `uid`) VALUES
		('$ff','$rf','$s_sql',NOW(),'$ip','$ag', '$uid')");

		if (!$silent) {
			$s = html_out($s);
			$ff = html_out($_SERVER['REQUEST_URI']);
			$this->msg("$s<br>Страница:$ff<br>Сообщение об ошибке передано администратору", "err", "Внутренняя ошибка!");
		}
		return $db->insert_id;
	}

}

/// Класс-исключение используется для информирования о отсутствии привилегий на доступ к запрошенной функции
class LoginException extends Exception {

    function __construct($text = '', $code = 0, $previous = NULL) {
        header('HTTP/1.0 403 Forbidden');
        parent::__construct($text, $code, $previous);
    }
}

/// Класс-исключение используется для информирования о отсутствии привилегий на доступ к запрошенной функции
class AccessException extends Exception {

    function __construct($text = '', $code = 0, $previous = NULL) {
        header('HTTP/1.0 403 Forbidden');
        parent::__construct($text, $code, $previous);
    }
}

/// Класс-исключение используется для информирования о отсутствии запрашиваемого объекта. Устанавливает заголовок 404 Not found
class NotFoundException extends Exception {

    function __construct($text = '', $code = 404, $previous = NULL) {
        header('HTTP/1.0 404 Not found');
        parent::__construct($text, $code, $previous);
    }

}

/// Базовый класс для создания автожурналируемых исключений
class AutoLoggedException extends Exception {
    
    function __construct($text='', $code=0, $previous=NULL) {
        parent::__construct($text, $code, $previous);
        writeLogException($this);
    }
}


/// Записывает исключение в журнал ошибок
/// @param $e   Объект класса Exception, или унаследованного класса
function writeLogException($e) {
    global $db;
    if($db) {
        if (isset($_SESSION['uid'])) {
            $uid = intval($_SESSION['uid']);
        } else {
            $uid = null;
        }

        $data = array();
        $data['page'] = urldecode($_SERVER['REQUEST_URI']);
        $data['referer'] = urldecode(getenv("HTTP_REFERER"));
        $data['class'] = get_class($e);
        $data['code'] = $e->getCode();
        $data['msg'] = $e->getMessage();
        $data['file'] = $e->getFile();
        $data['line'] = $e->getLine();
        $data['trace'] = $e->getTraceAsString();
        $data['ip'] = getenv("REMOTE_ADDR");
        $data['useragent'] = getenv("HTTP_USER_AGENT");
        $data['date'] = date('Y-m-d H:i:s');
        $data['uid'] = $uid;
        return $db->insertA('errorlog', $data);
    }
}

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
        header("Retry-After: 3000");
	die("<h1>500 Внутренняя ошибка сервера</h1>Расширение php-mysqli не найдено. Программа установлена некорректно. Обратитесь к администратору c описанием проблемы.");
}

if(!function_exists('mb_internal_encoding'))
{
	header("HTTP/1.0 500 Internal Server Error");
        header("Retry-After: 3000");
        header("Retry-After: 3000");
	die("<h1>500 Внутренняя ошибка сервера</h1>Расширение php-mbstring не найдено. Программа установлена некорректно. Обратитесь к администратору c описанием проблемы.");
}

$time_start = microtime(true);


mb_internal_encoding("UTF-8");

$base_path = dirname(dirname(__FILE__));
if(! include_once("$base_path/config_site.php")) {
	header("HTTP/1.0 500 Internal Server Error");
        header("Retry-After: 3000");
	die("<h1>500 Внутренняя ошибка сервера</h1>Конфигурационный файл не найден! Программа установлена некорректно. Обратитесь к администратору c описанием проблемы.");
}

if (@$CONFIG['site']['maintain_ip']) {
    if($CONFIG['site']['maintain_ip']!=getenv('REMOTE_ADDR')) {
	header("HTTP/1.0 503 Service temporary unavariable");
        header("Retry-After: 300");
        die("<!DOCTYPE html>
<html>
<head>
<meta charset=\"utf-8\">
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
<title>Error 500: Необработанная внутренняя ошибка</title>
<style type='text/css'>body{color: #000; background-color: #eee; text-align: center;}</style></head><body>
<h1>503 Service temporary unavariable</h1>Сайт отключне на техобслуживание. Повторите попытку через несколько минут!<br>
The site in maintenance mode. Please try again in a few minutes!</body></html>"
	);
    }
}

if(isset($CONFIG['site']['session_cookie_domain'])) {
    if($CONFIG['site']['session_cookie_domain']) {
        session_set_cookie_params(0, '/' , $CONFIG['site']['session_cookie_domain']);
    }
}

session_start();
require_once($CONFIG['location']."/common/core.common.php");

if ($CONFIG['site']['force_https'] && !isset($_SERVER['HTTPS'])) {
    header('Location: https://' . $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI'], true, 301);
}

cfg::requiredFilled('site', 'admin_name');
cfg::requiredFilled('site', 'admin_email');
cfg::requiredFilled('site', 'doc_adm_email');
cfg::requiredFilled('site', 'doc_adm_jid');
cfg::requiredFilled('site', 'name');

$db = @ new MysqiExtended($CONFIG['mysql']['host'], $CONFIG['mysql']['login'], $CONFIG['mysql']['pass'], $CONFIG['mysql']['db']);

if($db->connect_error) {
    header("HTTP/1.0 503 Service temporary unavariable");
    header("Retry-After: 3000");
    die("<!DOCTYPE html>
<html>
<head>
<meta charset=\"utf-8\">
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
<title>Error 500: Необработанная внутренняя ошибка</title>
<style type='text/css'>body{color: #000; background-color: #eee; text-align: center;}</style></head><body>
<h1>503 Сервис временно недоступен!</h1>Не удалось соединиться с сервером баз данных. Возможно он перегружен, и слишком медленно отвечает на запросы, либо выключен. Попробуйте подключиться через 5 минут. Если проблема сохранится - пожалуйста, напишите письмо <a href='mailto:{$CONFIG['site']['admin_email']}'>{$CONFIG['site']['admin_email']}</a> c описанием проблемы: ErrorCode: {$db->connect_errno} ({$db->connect_error})</body></html>"
	);
}

// Включаем автоматическую генерацию исключений для mysql
mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);

if(!$db->set_charset("utf8"))
{
	header("HTTP/1.0 503 Service temporary unavariable");
        header("Retry-After: 3000");
	die("<!DOCTYPE html>
<html>
<head>
<meta charset=\"utf-8\">
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
<title>Error 500: Необработанная внутренняя ошибка</title>
<style type='text/css'>body{color: #000; background-color: #eee; text-align: center;}</style></head><body>
<h1>503 Сервис временно недоступен!</h1>Невозможно задать кодировку соединения с базой данных: " . $db->error . "</body></html>"
	);
}


header("X-Powered-By: MultiMag ".MULTIMAG_VERSION);
// не получилось из-за невалидного json в редакторе наименований документа. Нужен рефакторинг.
//header("Content-Security-Policy: default-src 'self' 'unsafe-inline' *.".$_SERVER["HTTP_HOST"]); 
// HSTS Mode
if ((\cfg::get('site', 'force_https') || \cfg::get('site', 'force_https_login')) && isset($_SERVER['HTTPS'])) {
	header("Strict-Transport-Security: max-age=31536000");
}

// Счётчик-логгер посещений
if(!isset($_REQUEST['ncnt']) && !isset($not_use_counter)) {
    $ip = $db->real_escape_string(getenv("REMOTE_ADDR"));
    $ag = $db->real_escape_string(getenv("HTTP_USER_AGENT"));
    $rf = $db->real_escape_string(urldecode(getenv("HTTP_REFERER")));
    $qq = $db->real_escape_string(urldecode($_SERVER['REQUEST_URI'] . '?' . $_SERVER['QUERY_STRING']));
    $ff = $db->real_escape_string($_SERVER['SCRIPT_NAME']);
    $uid = $db->real_escape_string(@$_SESSION['uid']);
    $tim = time();
    $db->query("INSERT INTO `counter` (`date`,`ip`,`agent`,`refer`,`query`,`file`,`user_id`) VALUES ('$tim','$ip','$ag','$rf','$qq','$ff','$uid')");
}

$tmpl = new BETemplate;
testForeignSession();
