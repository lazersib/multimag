<?php
include_once("core.php");
$order_id=$_SESSION['order_id'];
settype($order_id,'int');


try
{
	if(!$order_id)			throw new Exception("Неизвестный номер заказа. Возможно, сессия устарела.");
	$tmpl->SetContent("<h1>Оплата заказа</h1>");
	$tmpl->msg("Оплата заказа завершена успешно","ok");
	$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`ok`
	FROM `doc_list`
	WHERE `doc_list`.`p_doc`='$order_id' AND `doc_list`.`type`='4'");
	if(mysql_errno())		throw new MysqlException("Не удалось получить данные оплат");
	if(!mysql_num_rows($res))	throw new Exception("Обнаружена ошибка при выполнении платежа! Обратитесь к администратору магазина, сообщив номер заказа $order_id!");
	$order_info=mysql_fetch_assoc($res);
	if($order_info['ok'])		$tmpl->msg("Оплата выполнена. Заказ передан в обработку.","ok");
	else				$tmpl->AddContent("Информация о подтверждении оплаты пока не поступила. Подождите 1-2 минуты, и проверьте оплату. <a href='/gpb_pay_success.php'>Проверить оплату</a>");

}
catch(MysqlException $e)
{
	mysql_query("ROLLBACK");
	$tmpl->AddText("<br><br>");
	$tmpl->msg($e->getMessage(),"err");
}
catch(Exception $e)
{
	mysql_query("ROLLBACK");
	$tmpl->AddText("<br><br>");
	$tmpl->logger($e->getMessage());
}


$tmpl->write();
?>
