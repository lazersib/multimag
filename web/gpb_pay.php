<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2014, BlackLight, TND Team, http://tndproject.org
//
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU Affero General Public License as
//	published by the Free Software Foundation, either version 3 of the
//	License, or (at your option) any later version.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU Affero General Public License for more details.
//
//	You should have received a copy of the GNU Affero General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

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

	$merch_id		= request('merch_id');
	$trx_id			= request('trx_id');
	$merchant_trx		= rcvint('merchant_trx');
	$result_code		= rcvint('result_code');
	$amount			= request('amount');
	$account_id		= request('account­_id');
	$p_rrn			= request('p_rrn');
	$order_id		= rcvint('o_order_id');
	$timestamp		= request('ts');
	$signature		= request('signature');
	$p_cardholder		= request('p_cardholder');
	$p_maskedPan		= request('p_maskedPan');
	$p_isFullyAuthenticated	= request('p_isFullyAuthenticated');

	if(!$order_id)					throw new Exception("Не передан ID заказа");
	if($merch_id!=$CONFIG['gpb']['merch_id'])	throw new Exception("Неверный ID магазина!");
	if(!$trx_id)					throw new Exception("Не передан ID транзакции");
	if(!$timestamp)					throw new Exception("Не передан timestamp");

	$res=$db->query("SELECT `doc_list`.`id`, `agent`, `sum`, `firm_id`, `contract`, `comment` FROM `doc_list`
	WHERE `doc_list`.`id`='$merchant_trx' AND `doc_list`.`type`='4'");
	if(!$res->num_rows)	throw new Exception("Банк-приход не найден");
	$b_info=$res->fetch_assoc();

	$desc_add="\n---------------\nresult:$result_code\namount:$amount\naccount_id:$account_id\np_rnn:$p_rrn\nts:$timestamp\nsignature:$signature\np_cardholder:$p_cardholder\np_maskedPan:$p_maskedPan\n3d_secure:$p_isFullyAuthenticated";
	$desc_sql=$db->real_escape_string($b_info['comment'].$desc_add);
	$res=$db->query("UPDATE `doc_list` SET `comment`='$desc_sql' WHERE `id`='$merchant_trx'");

	if($result_code==1)
	{
		if($amount!=round($b_info['sum']*100))	throw new Exception("Неверная сумма платежа ($amount != {$b_info['sum']})");

		$p_cardholder_sql=$db->real_escape_string($p_cardholder);
		$p_maskedPan_sql=$db->real_escape_string($p_maskedPan);
		$p_rrn_sql=$db->real_escape_string($p_rrn);
		$trx_id_sql=$db->real_escape_string($trx_id);

		$res=$db->query("REPLACE INTO `doc_dopdata` (`doc`, `param`, `value`) VALUES ('$merchant_trx', 'cardpay', '1'), ('$merchant_trx', 'cardholder', '$p_cardholder_sql'), ('$merchant_trx', 'masked_pan', '$p_maskedPan_sql'),  ('$merchant_trx', 'p_rnn', '$p_rrn_sql'),  ('$merchant_trx', 'trx_id', '$trx_id_sql')");
		$doc=new doc_PBank($merchant_trx);
		$doc->DocApply();
	}
	else
	{
		$res=$db->query("UPDATE `doc_list` SET `mark_del`='".time()."' WHERE `id`='$merchant_trx'");
	}
	echo"<?xml version=\"1.0\" encoding=\"utf-8\"?><register-payment-response><result><code>1</code><desc>OK</desc></result></register-payment-response>";
}
catch(Exception $e)
{
	echo"<?xml version=\"1.0\" encoding=\"utf-8\"?><register-payment-response><result><code>2</code><desc>".$e->getMessage().", order: $order_id, bp: $merchant_trx</desc></result></register-payment-response>";
}


?>
