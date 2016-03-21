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
$CONFIG['price']['notify_up']		= 30;	// Извещать, если цена какой-либо позиции увеличилась более, чем на X процентов
$CONFIG['price']['notify_down']		= 5;	// Извещать, если цена какой-либо позиции увеличилась более, чем на X процентов
$CONFIG['price']['numproc']		= 2;			// Количество параллельных процессов анализа. Ускоряет обработку на много(ядерных|процессорных) системах
$CONFIG['price']['mark_matched']	= false;		// Ставить отметку 'позиция обработана'
$CONFIG['price']['mark_doubles']	= false;		// Ставить отметку 'позиция обработана несколько раз'
								// Обработанная несколько раз позиция означает что есть некорректно составленные регулярные выражения
								// Но поиск таких позиций значительно снижает быстрдействие

// Архивация
$CONFIG['backup']['archiver']		= 'zip';		// Варианты: zip, 7z, tar, tbz, tgz
$CONFIG['backup']['archiv_dir']		= '/mnt/backup';
$CONFIG['backup']['ftp_host']		= '';
$CONFIG['backup']['ftp_login']		= '';
$CONFIG['backup']['ftp_pass']		= '';
$CONFIG['backup']['dirs']		= array('doc'=>'/home/ftp/');   // array( arch_name => info, ... ), где info - либо путь, 
                                                                        // либо array('path' => '', 'arch' => '', 'level' => '')
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

$CONFIG['auto']['user_del_days']	= 14;	// Периодически стирать неактивированных пользователей через X дней
$CONFIG['auto']['move_nr_to_end']	= true;	// Периодически перемещать непроведенные реализации на последний день
$CONFIG['auto']['move_no_to_end']	= false;// Периодически перемещать непроведенные заявки на последний день
$CONFIG['auto']['move_ntp_to_end']	= false;// Периодически перемещать непроведенные перемещения товаров на последний день
$CONFIG['auto']['doc_del_days']		= 2;	// Периодически стирать отмеченные на удаление документы через X дней
$CONFIG['auto']['resp_debt_notify']	= false;// Периодически информировать ответственных сотрудников о долгах их агентов
$CONFIG['auto']['update_currency']	= true;	// Периодически обновлять информацию о курсах валют
$CONFIG['auto']['agent_calc_avgsum']	= true;	// Периодически расчитывать информацию о обороте агентов за период
$CONFIG['auto']['agent_discount_notify']= true;	// Периодически уведомлять агентов об их накопительных скидках
$CONFIG['auto']['badpricenotify']       = true;	// Периодически уведомлять сотрудников о проблемах с ценами
$CONFIG['auto']['clear_image_cahe']	= 14;	// Периодически стирать изображения из кеша, через Х дней
$CONFIG['auto']['chpricenotify']	= false;// Периодически уведомлять обо всех изменениях цен
$CONFIG['auto']['red_event_doc_notify'] = true;

$CONFIG['badpricenotify']['threshold_low'] = 10;    // Порог оповещений о понижении цены, в %
$CONFIG['badpricenotify']['threshold_high'] = 20;   // Порог оповещений о повышении цены, в %

$CONFIG['chpricenotify']['notify_workers'] = false; // Информировать ли сотрудников обо всех изменениях цен
$CONFIG['chpricenotify']['notify_clients'] = false; // Информировать ли клиентов обо всех изменениях цен
$CONFIG['chpricenotify']['notify_address'] = '';    // Адрес или массив адресов для информирования обо всех изменениях цен
$CONFIG['red_event_doc_notify']['email'] = '';