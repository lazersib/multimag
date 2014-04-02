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
$CONFIG['price']['dir']			= '/home/ftp/price';	// Путь к каталогу с прайс-листами в odf формате. Обрабатанные прайс-листы будут удалены!
$CONFIG['price']['numproc']		= 2;			// Количество параллельных процессов анализа. Ускоряет обработку на много(ядерных|процессорных) системах
$CONFIG['price']['mark_matched']	= false;		// Ставить отметку 'позиция обработана'
$CONFIG['price']['mark_doubles']	= false;		// Ставить отметку 'позиция обработана несколько раз'
								// Обработанная несколько раз позиция означает что есть некорректно составленные регулярные выражения
								// Но поиск таких позиций значительно снижает быстрдействие

// Архивация
$CONFIG['backup']['archiver']		= 'zip';		// Варианты: zip, 7z
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
								// Если параметры заданы, то при недоступности указанного узла через основной интерфейс
								// маршрут по умолчанию будет переназначен на резервный. Если указано, что резервный канал - PPP -
								// он будет предварительно активирован
$CONFIG['route']['backup_ext_ip']	= '';			// IP внешнего резервного интерфейса (пустая строка - отключение функции)
$CONFIG['route']['backup_ext_iface']	= 'ppp0';		// Имя внешнего резервного интерфейса
$CONFIG['route']['backup_pppoe_name']	= 'dsl-provider';	// Имя pppoe соединения резервного канала
$CONFIG['route']['test_host']		= 'ya.ru';		// Хост для тестирования доступности основного канала при помощи ICMP запросов
$CONFIG['route']['lan_range']		= '192.168.1.0/24';	// Внутренняя сеть (адрес, маска)
$CONFIG['route']['ulog']['enable']	= false;		// Включить журналирование трафика
$CONFIG['route']['ulog']['ports']	= '80,8080,3189';	// Порты, которые необходимо журналировать
$CONFIG['route']['iplimit']['enable']	= true;			// Включить ограничение доступа к вебу (80 порт) по IP в рабочее время
$CONFIG['route']['iplimit']['hstart']	= 10;			// С (часов)
$CONFIG['route']['iplimit']['hend']	= 18;			// По (часов)
$CONFIG['route']['iplimit']['toport']	= 8123;			// Перенаправлять запросы на указанный порт (0-просто блокировать)
$CONFIG['route']['transparent_proxy']	= false;
$CONFIG['route']['allow_ext_tcp_ports']	= array(22,80,443);	// Внешние порты TCP, по которым разрешены подключения
$CONFIG['route']['allow_ext_udp_ports']	= array(53);		// Внешние порты UDP, по которым разрешены подключения
$CONFIG['route']['dnat_tcp']		= array(3389=>'192.168.1.2:3389');		// Проброс TCP портов через NAT
$CONFIG['route']['dnat_udp']		= array();		// Проброс UDP портов через NAT

// Снятие ответственного у агентов
// 0 - не информировать
$CONFIG['resp_clear']['info_time']	= 90;	// Информировать ответственного о продажах, отстутствующих Х дней
$CONFIG['resp_clear']['clear_time']	= 120;	// Убирать отвтетственного, если нет продаж X дней
$CONFIG['resp_clear']['info_mail']	= '';	// Адрес для отсылки отчётов и предупреждений

$CONFIG['auto']['user_del_days']	= 14;	// Стирать неактивированных пользователей через X дней
$CONFIG['auto']['move_nr_to_end']	= true;	// Перемещать непроведенные реализации на последний день
$CONFIG['auto']['move_no_to_end']	= false;// Перемещать непроведенные заявки на последний день
$CONFIG['auto']['doc_del_days']		= 2;	// Стирать отмеченные на удаление документы через X дней
$CONFIG['auto']['liquidity_interval']	= 180;	// Расчитывать ликвидность за X дней

?>