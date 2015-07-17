<?php
require_once("config_all.php");

$CONFIG['site']['skin']			= 'default';	// default по умолчанию
$CONFIG['site']['inner_skin']		= 'inner';	// по умолчанию = предыдущему

$CONFIG['site']['allow_phone_regist']	= false;	// Разрешить регистрацию по номеру мобильного телефона. Требуется настроенная отправка SMS
							// false по умолчанию
$CONFIG['site']['pass_type']		= 'CRYPT';	// Варианты: CRYPT (по умолчанию), MD5, SHA1. CRYPT обеспечивает самое надёжное хранение паролей

// Настройки прайса
$CONFIG['site']['price_col_cnt']	= 0;	// 2 по умолчанию
$CONFIG['site']['price_width_cost']	= 0;	// 12 по умлочанию
$CONFIG['site']['price_width_name']	= 0;	// 0 по умолчанию (автоматически)
$CONFIG['site']['price_width_vc']	= 0;	// Ширина колонки *код производителя* в PDF прайсе
$CONFIG['site']['price_text']		= array(
'Ваш адрес',
'Ваши телефоны',
'Ваши e-mail, jabber, ICQ',
'Ещё какая-то информация'
);
$CONFIG['site']['price_show_vc']	= 0;	// Отображать ли столбец с кодом производителя в прайсах

$CONFIG['site']['doc_header']		= '';	// Картинка - в шапке документов. {FN} будет заменён на номер фирмы
$CONFIG['site']['doc_shtamp']		= '';	// Картинка - штамп в документах. {FN} будет заменён на номер фирмы

$CONFIG['site']['vitrina_glstyle']	= '';	// Стиль списка групп на витрине
$CONFIG['site']['vitrina_plstyle']	= '';	// Стиль списка товаров на витрине
$CONFIG['site']['vitrina_limit']	= '';	// Количество товаров на страницу
$CONFIG['site']['vitrina_nds']		= 1;	// НДС при выписке счёта. 0 - выделять, 1 - включать
$CONFIG['site']['vitrina_pcnt']		= 1;	// Как отображать наличие товара. 0 - цифрой, 1 - звёздочками, 2 - много/мало
$CONFIG['site']['vitrina_pcnt_limit']	= array(1,10,100);	// Лимиты для значений мало/есть/много, либо звёздочек
$CONFIG['site']['vitrina_order']	= 'n';	// Сортировка по умолчанию для витрины. n - по имени, vc - по коду, c - по цене, s - по наличию
$CONFIG['site']['vitrina_show_vc']	= 0;	// Отображать ли столбец с кодом производителя в табицах витрины
$CONFIG['site']['vitrina_subtype']	= 'site';// Подтип заявок, создаваемых витриной. site по умолчанию.
$CONFIG['site']['vitrina_newtime']	= 180;	// Считать новинкой товар, созданный или впервые поступивший на склад не позже X суток назад
$CONFIG['site']['vitrina_sklad']	= 1;	// ID склада для количества на витрине. Если не задано - отображается суммарное количество по всем складам
$CONFIG['site']['vitrina_cntlock']	= 1;	// Ограничить оплату счетов при недостатке товара
$CONFIG['site']['vitrina_pricelock']	= 1;	// Ограничить оплату счетов при неактуальных ценах
$CONFIG['site']['recode_enable']	= false;// Разрешить "красивые" ссылки. Необходим mod_recode.
$CONFIG['site']['dowload_attach_speed'] = 16;	// Скорость скачивания вложений с сайта(кбайт/сек). Для снижения расхода памяти рекомендуются меньшие значения.
$CONFIG['site']['grey_price_days']	= 30;	// Срок (дней), по истечении которого, на витрине и в прайсах отображаются *серые цены*. 0 - не отображать.

					// Для работы этих опций нужен правильно настроенный SSL. Обе опции умолчанию - false
$CONFIG['site']['force_https']		= false;	// Принудительно использовать https при открытии любой страницы сайта. Желательно включить.
$CONFIG['site']['force_https_login']	= false;	// Принудительно использовать https при аутентификации, регистрации. Не влияет на шаблоны.
							// НАСТОЯТЕЛЬНО РЕКОМЕНДУЕТСЯ ВКЛЮЧИТЬ ПРИ НАЛИЧИИ ТЕХНИЧЕСКОЙ ВОЗМОЖНОСТИ

$CONFIG['poseditor']['sn_enable']	= false;// Включить поддержку работы с серийными номерами
$CONFIG['poseditor']['sn_restrict']	= false;// Включить ограничения на выписку документов без серийных номеров
$CONFIG['poseditor']['vc']		= true;	// Показывать код производителя
$CONFIG['poseditor']['tdb']		= false;// Показывать размеры
$CONFIG['poseditor']['rto']		= true;	// Показывать резервы/транзиты/заявки
$CONFIG['poseditor']['show_reserve']	= false;// Показывать резервы в таблице документа
$CONFIG['poseditor']['show_packs']      = false;// Показывать размер упаковки
$CONFIG['poseditor']['show_bulkcnt']    = false;// Показывать кол-во оптом
$CONFIG['poseditor']['true_gtd']	= false;// Cхема учёта ГТД. false - ГТД берётся из доп. свойств наименования
                                                // 'easy' - ГТД берутся из поступлений. 
                                                // true - то же, что и 'easy', но при попытке получить кол-во большее, чтем поступило,
                                                //      сгенерируется исключение.

$CONFIG['doc']['invoice_header']	= '';	// Уведомление в шапке счёта (например, о смене реквизитов)
$CONFIG['doc']['require_pack_count']	= false;// Не проводить документы, если не указано количество мест
$CONFIG['doc']['require_storekeeper']	= false;// Не проводить документы, если не выбран кладовщик
$CONFIG['doc']['use_persist_altnum']	= true;	// Использовать непрерывную нумерацию документов
$CONFIG['doc']['sklad_default_order']	= 'vc';	// Вариант сортировки складских наименований по умолчанию: name,vc,basecost. name по умолчанию
$CONFIG['doc']['no_print_vendor']	= false;// Не печатать производителя в документах, прайсах, и пр.
$CONFIG['doc']['mincount_info']		= false;// Показывать информацию о выходе за пределы минимальных остатков
$CONFIG['doc']['update_in_cost']	= 0;	// Обновлять базовую цену при проведении поступления. ВНИМАНИЕ! ПОТЕНЦИАЛЬНО ОПАСНАЯ ФУНКЦИЯ!
						// 0 - не обновлять
						// 1 - обновлять по текущей цене поступления
						// 2 - обновлять по актуальной цене поступления
$CONFIG['doc']['notify_email']		= true;// Информировать покупателей о статусе заказа по email
$CONFIG['doc']['notify_sms']		= false;// Информировать покупателей о статусе заказа по sms. Требует настроек sms шлюза
$CONFIG['doc']['default_unit']		= 135;	// ID единицы измерения по умолчанию
$CONFIG['doc']['op_time']		= 365;	// Срок действительности документа *предложение поставщика*

//$CONFIG['doc']['pie']			= '';	// Текст отправляемой покупателю благодарности. Если текст задан, в заявке появится кнопка.

// $CONFIG['doc']['status_list']=array('err'=>'Ошибочный','inproc'=>'В процессе','ready'=>'Готов','ok'=>'Отгружен');	// Список статусов

// Настройки склада
$CONFIG['store']['leaf_only']	= true;	// Разрешать создавать наименования только в *листьях* складского дерева
$CONFIG['agents']['leaf_only']	= true;	// Разрешать создавать агентов только в *листьях* дерева групп агентов
//
// Пример обработки событий
// $CONFIG['zstatus']['doc:zayavka:apply']['testup_status']='inproc';
// $CONFIG['zstatus']['doc:zayavka:apply']['notify']='Ваш заказ {DOC} принят в обработку';
// 
// $CONFIG['zstatus']['doc:zayavka:print']['testup_status']='inproc';
// $CONFIG['zstatus']['doc:zayavka:print']['notify']='Ваш заказ {DOC} принят в обработку';
// $CONFIG['zstatus']['doc:zayavka:apply']['testup_status']='inproc';
// $CONFIG['zstatus']['doc:zayavka:apply']['notify']='Ваш заказ {DOC} принят в обработку';
// 
// $CONFIG['zstatus']['doc:realizaciya:cstatus:ok']['testup_status']='ready';
// $CONFIG['zstatus']['doc:realizaciya:cstatus:ok']['notify']='Ваш заказ N{DOC} на сумму {SUM} готов';
// 
// $CONFIG['zstatus']['doc:realizaciya:apply']['testup_status']='ok';
// $CONFIG['zstatus']['doc:realizaciya:apply']['notify']='Ваш заказ {DOC} отгружен';


$CONFIG['store']['default_cost']	= -1;	// Цена по умолчанию для доп.столбца на складе. По умолчанию  -1 - не задано
$CONFIG['store']['add_columns']         = '';   // Дополнительные колонки складского блока

$CONFIG['images']['watermark']		= 1;	// Показывать ли название сайта поверх изображений. Ещё варианты:
						// $CONFIG['images']['watermark']=array('w'=>0,'p'=>'1','g'=>'1');
$CONFIG['images']['font_watermark']	= '';	// Шрифт текста, накладываемого на изображение
$CONFIG['images']['quality']		= 70;	// Качество (по уровню сжатия) изображений

$CONFIG['notify']['comments']		= true;	// Оповещать о коментариях

/// Не включайте не настроенные способы оплаты !
$CONFIG['payments']['types']['cash']		= true;	// Разрешить оплату наличными
$CONFIG['payments']['types']['bank']		= true;	// Разрешить выписку счёта для оплаты безналичным банковским переводом
$CONFIG['payments']['types']['card_o']		= false;// Разрешить оплату по карте на сайте
$CONFIG['payments']['types']['card_t']		= false;// Разрешить оплату по карте при получении товара
$CONFIG['payments']['types']['wmr']		= false;// Разрешить оплату по webmoney wmr на сайте
$CONFIG['payments']['types']['credit_brs']	= false;// Разрешить способ оплаты *кредит через банк русский стандарт*
$CONFIG['payments']['default']		= 'cash'; // Способ оплаты по умолчанию

// Параметры приёма платежей через газпромбанк
$CONFIG['gpb']['initial_url']	= '';
$CONFIG['gpb']['merch_id']	= '';
$CONFIG['gpb']['accounts_id']	= '';
$CONFIG['gpb']['terminal_id']	= '';
$CONFIG['gpb']['bank_id']	= 1;
$CONFIG['gpb']['callback_login']= '';
$CONFIG['gpb']['callback_pass']	= '';

// настройки модуля 1с синхронизации
$CONFIG['1csync']['login']      = '';
$CONFIG['1csync']['pass']       = '';

$CONFIG['doc_scripts']['zp_s_prodaj.coeff']     = 0.05;	// Коэффициент начислений для зарплаты с продаж
$CONFIG['doc_scripts']['zp_s_prodaj.l_coeff']   = 0.5;  // Коэффициент понижения зарплаты от ликвидности. Диапазон от -1 до 1
$CONFIG['doc_scripts']['zp_s_prodaj_conn.new_coeff']=0.02;
$CONFIG['doc_scripts']['zp_s_prodaj_conn.new_days']=90;
$CONFIG['doc_scripts']['zp_s_prodaj_conn.old_coeff']=0.01;

// Параметры для кредита *русский стандарт*
$CONFIG['credit_brs']['address']= 'https://anketa.bank.rs.ru/minipotreb.php';
$CONFIG['credit_brs']['id_tpl']	= 0;

// Уведомления запроса звонка
$CONFIG['call_request']['captcha']	= true;	// Использовать ли captcha во избежание заспамливания и перерасхода средств с sms счёта
$CONFIG['call_request']['email']	= '';	// Адрес email уведомления
$CONFIG['call_request']['xmpp']		= '';	// Адрес jabber уведомления
$CONFIG['call_request']['sms']		= '';	// Адрес sms уведомления

// Модуль управления настройками почтовыми аккаунтами
$CONFIG['admin_mailconfig']['db_host']	= 'localhost';
$CONFIG['admin_mailconfig']['db_port']	= '';
$CONFIG['admin_mailconfig']['db_name']	= 'mail';
$CONFIG['admin_mailconfig']['db_login']= '';
$CONFIG['admin_mailconfig']['db_pass']	= '';

// Расположение изменяемых доступных данных - изображения, итп
$CONFIG['site']['var_data_web']		= '/share/var';		// по отношению к корню сайта
$CONFIG['site']['var_data_fs']		= $CONFIG['site']['location'].$CONFIG['site']['var_data_web'];	// по отношению к корню файловой системы

