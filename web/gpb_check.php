<?php
include_once("core.php");
include_once("include/doc.core.php");

header("Content-type: application/xml");

try
{
	if(!isset($_SERVER['PHP_AUTH_USER']))
	{
	header('WWW-Authenticate: Basic realm="'.@$CONFIG['site']['name'].'"');
	header('HTTP/1.0 401 Unauthorized');
	echo 'Authentification cancel by user';
	$xmppclient->connect();
		$xmppclient->processUntil('session_start');
		$xmppclient->presence();
		$xmppclient->message('lazersib@jabber.ru', 'au cancel');
		$xmppclient->disconnect();
	exit();
	}
	else
	{
	if(@$_SERVER['PHP_AUTH_USER']!=@$CONFIG['gpb']['callback_login'] || @$_SERVER['PHP_AUTH_PW']!=@$CONFIG['gpb']['callback_pass'] || !@$CONFIG['gpb']['callback_pass'] || !@$CONFIG['gpb']['callback_login'])
	{
		header('WWW-Authenticate: Basic realm="'.@$CONFIG['site']['name'].'"');
		header('HTTP/1.0 401 Unauthorized');
		echo 'Authentification error';
		$xmppclient->processUntil('session_start');
		$xmppclient->presence();
		$xmppclient->message('lazersib@jabber.ru', "user: {$_SERVER['PHP_AUTH_USER']}!={$CONFIG['gpb']['callback_login']}\npass: {$_SERVER['PHP_AUTH_PW']}!={$CONFIG['gpb']['callback_pass']}" );
		$xmppclient->disconnect();
		exit();
	}
	}


	$order_id=@$_REQUEST['o_order_id'];
	settype($order_id,'int');
	$merch_id=@$_REQUEST['merch_id'];
	$trx_id=@$_REQUEST['trx_id'];
	$timestamp=@$_REQUEST['ts'];
	if(!$order_id)					throw new Exception("Не передан ID заказа");
	if($merch_id!=$CONFIG['gpb']['merch_id'])	throw new Exception("Неверный ID магазина!");
	if(!$trx_id)					throw new Exception("Не передан ID транзакции");
	if(!$timestamp)					throw new Exception("Не передан timestamp");
	if(!$CONFIG['gpb']['bank_id'])			throw new Exception("Не настроен id банка");

	$res=mysql_query("SELECT `doc_list`.`id`, `agent`, `sum`, `firm_id`, `contract` FROM
	`doc_list`
	WHERE `doc_list`.`id`='$order_id' AND `doc_list`.`type`='3'");
	if(mysql_errno())		throw new MysqlException("Невозможно получить данные документа");
	if(!mysql_num_rows($res))	throw new Exception("Заказ не найден!");
	$order_info=mysql_fetch_assoc($res);


	// Надо проверить, была ли оплата
	// так же надо проверить статус - возможна ли оплата
	$text="order_id: $order_id\nmerch_id:$merch_id\ntrx_id:$trx_id\nts:$timestamp";
	$text_sql=mysql_real_escape_string($text);
	// Создаём документ банк-приход:
	mysql_query("INSERT INTO `doc_list` (`type`, `agent`, `contract`, `p_doc`, `date`, `bank`, `sum`, `firm_id`, `comment` )
	VALUES ('4', '{$order_info['agent']}', '{$order_info['contract']}', '{$order_info['id']}', '".time()."', '{$CONFIG['gpb']['bank_id']}', '{$order_info['sum']}', '{$order_info['firm_id']}', '$text_sql')");
	if(mysql_errno())		throw new MysqlException("Не удалось создать документ банка".mysql_error());
	$doc_bank_id=mysql_insert_id();

	$sum=round($order_info['sum']*100);
	echo"<payment-avail-response><result><code>1</code><desc>OK, $text</desc></result><merchant-trx>$doc_bank_id</merchant-trx><purchase><shortDesc>Pay for order {$order_info['id']}</shortDesc><longDesc>{$CONFIG['site']['display_name']} ({$CONFIG['site']['name']}). Оплата за заказ {$order_info['id']}</longDesc><account-amount><id>{$CONFIG['gpb']['accounts_id']}</id><amount>$sum</amount><currency>643</currency><exponent>2</exponent></account-amount></purchase></payment-avail-response>";
}
catch(Exception $e)
{
	echo"<?xml version=\"1.0\" encoding=\"utf-8\"?><payment-avail-response><result><code>2</code><desc>".$e->getMessage()."</desc></result></payment-avail-response>";
}


?>
