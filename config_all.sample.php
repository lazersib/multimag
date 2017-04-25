<?php
// Системные настройки
$CONFIG['site']['admin_name']	= '';
$CONFIG['site']['admin_email']	= '';
$CONFIG['site']['doc_adm_email']= '';
$CONFIG['site']['doc_adm_jid']	= '';
$CONFIG['site']['name']		= 'example.com';
$CONFIG['site']['display_name']	= 'Интернет-магазин';
$CONFIG['site']['default_firm']	= 1;				// Организация по умолчанию для работы сайта
$CONFIG['site']['default_bank']	= 6;				// Банк по умолчанию для работы сайта. Если парамер не указан - будет выбран произвольный банк организации
$CONFIG['site']['default_kass']	= 1;				// Касса по умолчанию
$CONFIG['site']['default_agent']= 1;				// Агент по умолчанию
$CONFIG['site']['default_sklad']= 1;				// Склад по умолчанию
$CONFIG['location']		= '/usr/share/multimag';
$CONFIG['site']['location']	= $CONFIG['location'].'/web';
$CONFIG['cli']['location']	= $CONFIG['location'].'/cli';
$CONFIG['site']['user_pass_period'] = false;                    // Срок действия (суток) пароля обычного пользователя. Если false - не ограничен. false по умолчанию
$CONFIG['site']['worker_pass_period'] = 90;                     // Срок действия (суток) пароля сотрудника. Если false - не ограничен. 90 по умолчанию.
$CONFIG['site']['vitrina_pcnt']		= 1;	// Как отображать наличие товара. 0 - цифрой, 1 - звёздочками, 2 - много/мало
$CONFIG['site']['vitrina_pcnt_limit']	= array(1,10,100);	// Лимиты для значений мало/есть/много, либо звёздочек

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


//Проверка заданного типа в приходных/расходных кассовых ордерах
//и средств из/в банк при проведении документа.
$CONFIG['doc']['restrict_dc_nulltype'] = true;
// Настройки базы данных
$CONFIG['mysql']['host']	= 'localhost';
$CONFIG['mysql']['port']	= '';
$CONFIG['mysql']['db']		= 'dev_multimag';
$CONFIG['mysql']['login']	= '';
$CONFIG['mysql']['pass']	= '';

// Настройки  XMPP клиента. Используется для мгновенных уведомлений.
$CONFIG['xmpp']['host']		= '';	// Имя хоста XMPP сервера. Если не задан - все XMPP возможности будут отключены
$CONFIG['xmpp']['port']		= 5222;	// Порт XMPP сервера
$CONFIG['xmpp']['login']	= '';	// Логин на XMPP сервере
$CONFIG['xmpp']['pass']		= '';	// Пароль на XMPP сервере

// настройки отправки факсов
// $CONFIG['sendfax']['url']            = 'http://www.virtualofficetools.ru/API/fax.send.api.php';
// $CONFIG['sendfax']['username']	= '';	
// $CONFIG['sendfax']['password']	= '';
// $CONFIG['sendfax']['attempts']	= 5;	// кол-во попыток отправки
// $CONFIG['sendfax']['delay']	= 15;	// задержка между попытками, мин

// настройки отправки sms сообщений
// $CONFIG['sendsms']['service']	= 'infosmska';	// Службы отправки sms: infosmska, virtualofficetools
// $CONFIG['sendsms']['login']		= '';	// Логин службы отправки sms. Не обязятелен для некоторых служб
// $CONFIG['sendsms']['password']	= '';	// Пароль службы отправки sms. Не обязятелен для некоторых служб
// $CONFIG['sendsms']['callerid']	= 'SMS';	// Номер телефона или имя отправителя. Не более 11 символов. Поддерживается не всеми сервисами

// настройки отправки заявки на доработку сайта
$CONFIG['site']['trackticket_login']	= '';
$CONFIG['site']['trackticket_pass']	= '';

// Настройки для яндекс-маркет
$CONFIG['ymarket']['local_delivery_cost']	= 150;		// Цена доставки в пределах региона, указываемая в яндекс-маркете
$CONFIG['ymarket']['av_from_prices']		= false;	// Брать информацию о наличии из анализатора прайсов

// Бонусная система
$CONFIG['bonus']['coeff']		= 0.01;		// Коэффициент бонусного вознаграждения

// Автоматический расчёт цен / система скидок
$CONFIG['pricecalc']['acc_type']	= 'prevquarter';	/* Тип периода расчёта оборота агента. X задаётся в acc_agent_time
						* 'days' - последние X дней
						* 'months' - последние X месяцев
						* 'years' - последние X лет
						* 'prevmonth' - предыдущий месяц 
						* 'prevquarter' - предыдущий квартал
						* 'prevhalfyear' - предыдущее полугодие
						* 'prevyear' - предыдущий год. По умолчанию.
						* '' - не расчитывать
						*/
//$CONFIG['pricecalc']['acc_time']	= 180;	// Длительность периода. См выше.
$CONFIG['pricecalc']['notify']		= true;	// Напоминать о периодических накопительных скидках незадолго до окончания периода фиксированной длительности

$CONFIG['site']['liquidity_interval']	= 180;	// Периодически расчитывать ликвидность за X дней
$CONFIG['site']['liquidity_per_group']	= false;// Считать ликвидность по группам

// Начисление вознаграждений сотрудникам
$CONFIG['salary']['enable'] = false;            //< Разрешена ли работа модуля
$CONFIG['salary']['sk_re_pack_coeff'] = 0.5;    //< Коэффициент вознаграждения кладовщику реализации за упаковки
$CONFIG['salary']['sk_po_pack_coeff'] = 0.5;    //< Коэффициент вознаграждения кладовщику поступления за упаковки
$CONFIG['salary']['sk_pe_pack_coeff'] = 0.5;    //< Коэффициент вознаграждения кладовщику перемещения за упаковки
$CONFIG['salary']['sk_cp_min_sum'] = 0;         //< Минимальная сумма для включения начислений за кол-во товара и мест в накладной кладовщику
$CONFIG['salary']['sk_cnt_coeff'] = 1;          //< Коэффициент вознаграждения кладовщику реализации за кол-во товара в накладной
$CONFIG['salary']['sk_place_coeff'] = 2;        //< Коэффициент вознаграждения кладовщику реализации за кол-во различных мест в накладной
$CONFIG['salary']['sk_bigpack_coeff'] = 5;      //< Коэффициент усложнения сборки большой упаковки
$CONFIG['salary']['manager_id'] = 1;            //< id пользователя-менеджера для начисления вознаграждения
$CONFIG['salary']['author_coeff'] = 0.01;       //< Коэффициент вознаграждения автору реализации
$CONFIG['salary']['resp_coeff'] = 0.02;         //< Коэффициент вознаграждения ответственному агента
$CONFIG['salary']['manager_coeff'] = 0.005;     //< Коэффициент вознаграждения менеджеру магазина
$CONFIG['salary']['use_liq'] = false;           //< Учитывать ли ликвидность при расчёте вознаграждения с товарной наценки
$CONFIG['salary']['liq_coeff'] = 0.5;           //< Коэффициент влияния ликвидности на вознаграждение с товарной наценки
$CONFIG['salary']['work_pos_id'] = 1;           //< id услуги "работа"
$CONFIG['salary']['stores_limit'] = '';         //< Ограничить расчёт указанными складами

// OAuth аутентификация
$CONFIG['oauth']['yandex']['id'] = '';
$CONFIG['oauth']['yandex']['secret'] = '';

$CONFIG['oauth']['google']['id'] = '';
$CONFIG['oauth']['google']['secret'] = '';

$CONFIG['oauth']['vk']['id'] = '';
$CONFIG['oauth']['vk']['secret'] = '';

$CONFIG['oauth']['mailru']['id'] = '';
$CONFIG['oauth']['mailru']['secret'] = '';

$CONFIG['oauth']['okru']['id'] = '';
$CONFIG['oauth']['okru']['secret'] = '';
$CONFIG['oauth']['okru']['public'] = '';
