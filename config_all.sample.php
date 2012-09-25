<?php
// Системные настройки
$CONFIG['site']['admin_name']	= '';
$CONFIG['site']['admin_email']	= '';
$CONFIG['site']['doc_adm_email']= '';
$CONFIG['site']['doc_adm_jid']	= '';
$CONFIG['site']['name']		= 'example.com';
$CONFIG['site']['display_name']	= 'Интернет-магазин';
$CONFIG['site']['default_firm']	= 1;				// Организация по умолчанию для работы сайта
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

require_once($CONFIG['location'].'/common/XMPPHP/XMPP.php');

$xmppclient = new XMPPHP_XMPP( $CONFIG['xmpp']['host'], $CONFIG['xmpp']['port'], $CONFIG['xmpp']['login'], $CONFIG['xmpp']['pass'], 'xmpphp', '', $printlog=false, $loglevel=XMPPHP_Log::LEVEL_INFO);

?>
