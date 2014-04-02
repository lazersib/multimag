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

// Настройки базы данных
$CONFIG['mysql']['host']	= 'localhost';
$CONFIG['mysql']['port']	= '';
$CONFIG['mysql']['db']		= 'dev_multimag';
$CONFIG['mysql']['login']	= '';
$CONFIG['mysql']['pass']	= '';

// Настройки  XMPP клиента
$CONFIG['xmpp']['host']		= '';
$CONFIG['xmpp']['port']		= 5222;
$CONFIG['xmpp']['login']	= '';
$CONFIG['xmpp']['pass']		= '';

// настройки отправки факсов
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

?>