<?php
// Системные настройки
$CONFIG['site']['admin_name']	= '';
$CONFIG['site']['admin_email']	= '';
$CONFIG['site']['doc_adm_email']= '';
$CONFIG['site']['doc_adm_jid']	= '';
$CONFIG['site']['name']		= 'multimag';
$CONFIG['site']['default_firm']	= 1;				// Организация по умолчанию для работы сайта
$CONFIG['site']['sn_enable']	= false;			// Включить поддержку работы с серийными номерами
$CONFIG['site']['sn_restrict']	= false;			// Включить ограничения на выписку документов без серийных номеров
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

// Настройки для яндекс-маркет
$CONFIG['ymarket']['local_delivery_cost']	= 150;

require_once($CONFIG['location']."/common/class.phpmailer.php"); 
require_once($CONFIG['location'].'/common/XMPPHP/XMPP.php');


$mail = new PHPMailer();  

$mail->Sender	= $CONFIG['site']['admin_email'];  
$mail->From	= $CONFIG['site']['admin_email'];  
$mail->FromName	= 'Site '.$CONFIG['site']['name'];  
$mail->Mailer	= "mail";  
$mail->CharSet	= "UTF-8";

$xmppclient = new XMPPHP_XMPP( $CONFIG['xmpp']['host'], $CONFIG['xmpp']['port'], $CONFIG['xmpp']['login'], $CONFIG['xmpp']['pass'], 'xmpphp', '', $printlog=false, $loglevel=XMPPHP_Log::LEVEL_INFO);

?>
