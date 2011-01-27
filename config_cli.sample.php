<?php
$c=explode('/',__FILE__);$base_path='';
for($i=0;$i<(count($c)-1);$i++)	$base_path.=$c[$i].'/';
include_once("$base_path/config_all.php");

// Обмен с клиент-банком NVTB
$CONFIG['bank']['mountpoint']		= '';
$CONFIG['bank']['remotepoint']		= '//192.168.1.11/bank';
$CONFIG['bank']['login']		= '';
$CONFIG['bank']['pass']			= '';

// Анализатор прайсов
$CONFIG['price']['dir']			= '/home/ftp/price';

// Архивация
$CONFIG['backup']['archiv_dir']		= '/mnt/backup';
$CONFIG['backup']['ftp_host']		= '';
$CONFIG['backup']['ftp_login']		= '';
$CONFIG['backup']['ftp_pass']		= '';
$CONFIG['backup']['dirs']		= array('doc'=>'/home/ftp/'); // array( arch_name => path, ... );
$CONFIG['backup']['mysql']		= true;
$CONFIG['backup']['ziplevel']		= 1;	// 0 - 9
$CONFIG['backup']['min_free_space']	= 2000; // Megabytes

// Маршрутизация, NAT, файрфол
$CONFIG['route']['ext_ip']		= '';			// IP внешнего интерфейса (пустая строка - отключение функции)
$CONFIG['route']['ext_iface']		= 'eth0';		// Имя внешнего интерфейса
$CONFIG['route']['lan_range']		= '192.168.1.0/24';	// Внутренняя сеть (адрес, маска)
$CONFIG['route']['ulog']['enable']	= false;		// Включить журналирование трафика
$CONFIG['route']['ulog']['ports']	= '80,8080,3189';	// Порты, которые необходимо журналировать
$CONFIG['route']['iplimit']['enable']	= true;			// Включить ограничение доступа к вебу (80 порт) по IP в рабочее время
$CONFIG['route']['iplimit']['hstart']	= 10;			// С (часов)
$CONFIG['route']['iplimit']['hend']	= 18;			// По (часов)
$CONFIG['route']['iplimit']['toport']	= 8123;			// Перенаправлять запросы на указанный порт (0-просто блокировать)

// Снятие ответственного у агентов
// 0 - не информировать
$CONFIG['resp_clear']['info_time']	= 90;	// Информировать ответственного о продажах, отстутствующих Х дней
$CONFIG['resp_clear']['clear_time']	= 120;	// Убирать отвтетственного, если нет продаж X дней
$CONFIG['resp_clear']['info_mail']	= '';	// Адрес для отсылки отчётов и предупреждений

$CONFIG['auto']['user_del_days']	= 14;	// Стирать неактивных пользователей через X дней
$CONFIG['auto']['move_nr_to_end']	= true;	// Перемещать непроведенные реализации на конец дня

// ======================== НИЖЕ НЕ ИСПРАВЛЯТЬ =============================
// ======================== DO NOT EDIT AFTER THIS =========================
if(!@mysql_connect($CONFIG['mysql']['host'],$CONFIG['mysql']['login'],$CONFIG['mysql']['pass']))
{
	echo"Нет связи с сервером баз данных!";
	exit();
}
if(!@mysql_select_db($CONFIG['mysql']['db']))
{
	echo"Невозможно активизировать базу данных! Возможно, база данных повреждена или занята!";
	exit();
}

mysql_query("SET CHARACTER SET UTF8");
mysql_query("SET character_set_client = UTF8");
mysql_query("SET character_set_results = UTF8");
mysql_query("SET character_set_connection = UTF8");
?>
