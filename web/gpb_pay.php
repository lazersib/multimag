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
		exit();
	}
	else
	{
		if(@$_SERVER['PHP_AUTH_USER']!=@$CONFIG['gpb']['callback_login'] || @$_SERVER['PHP_AUTH_PW']!=@$CONFIG['gpb']['callback_pass'] || !@$CONFIG['gpb']['callback_pass'] || !@$CONFIG['gpb']['callback_login'])
		{
			header('WWW-Authenticate: Basic realm="'.@$CONFIG['site']['name'].'"');
			header('HTTP/1.0 401 Unauthorized');
			echo 'Authentification error';
			exit();
		}
	}

	$merch_id=@$_REQUEST['merch_id'];
	$trx_id=@$_REQUEST['trx_id'];
	$merchant_trx=@$_REQUEST['merchant_trx'];
	settype($merchant_trx,'int');
	$result_code=@$_REQUEST['result_code'];
	settype($result_code,'int');
	$amount=@$_REQUEST['amount'];
	$account­_id=@$_REQUEST['account­_id'];
	$p_rrn=@$_REQUEST['p_rrn'];
	$order_id=@$_REQUEST['o_order_id'];
	settype($order_id,'int');
	$timestamp=@$_REQUEST['ts'];
	$signature=@$_REQUEST['signature'];
	$p_cardholder=@$_REQUEST['p_cardholder'];
	$p_maskedPan=@$_REQUEST['p_maskedPan'];
	$p_isFullyAuthenticated=@$_REQUEST['p_isFullyAuthenticated'];

	if(!$order_id)					throw new Exception("Не передан ID заказа");
	if($merch_id!=$CONFIG['gpb']['merch_id'])	throw new Exception("Неверный ID магазина!");
	if(!$trx_id)					throw new Exception("Не передан ID транзакции");
	if(!$timestamp)					throw new Exception("Не передан timestamp");

	$res=mysql_query("SELECT `doc_list`.`id`, `agent`, `sum`, `firm_id`, `contract`, `comment` FROM
	`doc_list`
	WHERE `doc_list`.`id`='$merchant_trx' AND `doc_list`.`type`='4'");
	if(mysql_errno())		throw new MysqlException("Невозможно получить данные документа");
	if(!mysql_num_rows($res))	throw new Exception("Банк-приход не найден");
	$b_info=mysql_fetch_assoc($res);

	$desc_add="\n---------------\nresult:$result_code\namount:$amount\naccount_id:$account­_id\np_rnn:$p_rrn\nts:$timestamp\nsignature:$signature\np_cardholder:$p_cardholder\np_maskedPan:$p_maskedPan\n3d_secure:$p_isFullyAuthenticated";
	$desc_sql=mysql_real_escape_string($b_info['comment'].$desc_add);
	mysql_query("UPDATE `doc_list` SET `comment`='$desc_sql' WHERE `id`='$merchant_trx'");
	if(mysql_errno())		throw new MysqlException("Невозможно обновить данные документа");
	if($amount!=round($b_info['sum']*100))	throw new Exception("Неверная сумма платежа ($amount != {$b_info['sum']})");

	if($result_code==1)
	{
		mysql_query("REPLACE INTO `doc_dopdata` (`doc`, `param`, `value`) VALUES ('$merchant_trx', 'cardpay', '1'), ('$merchant_trx', 'cardholder', '$p_cardholder'), ('$merchant_trx', 'masked_pan', '$p_maskedPan'),  ('$merchant_trx', 'p_rnn', '$p_rrn'),  ('$merchant_trx', 'trx_id', '$trx_id')");
		if(mysql_errno())		throw new MysqlException("Невозможно обновить данные документа");
		$doc=new doc_PBank($merchant_trx);
		$doc->DocApply();
	}
	else
	{
		mysql_query("UPDATE `doc_list` SET `mark_del`='".time()."' WHERE `id`='$merchant_trx'");
		if(mysql_errno())		throw new MysqlException("Невозможно обновить данные документа");

	}


	echo"<?xml version=\"1.0\" encoding=\"utf-8\"?><register-payment-response><result><code>1</code><desc>OK</desc></result></register-payment-response>";
}
catch(Exception $e)
{
	echo"<?xml version=\"1.0\" encoding=\"utf-8\"?><register-payment-response><result><code>2</code><desc>".$e->getMessage().", order: $order_id, bp: $merchant_trx</desc></result></register-payment-response>";
}


?>
