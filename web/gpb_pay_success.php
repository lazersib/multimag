<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2018, BlackLight, TND Team, http://tndproject.org
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
$order_id=$_SESSION['order_id'];
settype($order_id,'int');

try
{
	if(!$order_id)			throw new Exception("Неизвестный номер заказа. Возможно, сессия устарела.");
	$tmpl->setContent("<h1>Оплата заказа</h1>");
	$tmpl->msg("Оплата заказа завершена успешно","ok");
	$res=$db->query("SELECT `doc_list`.`id`, `doc_list`.`ok` FROM `doc_list` WHERE `doc_list`.`p_doc`='$order_id' AND `doc_list`.`type`='4'");
	if(!$res->num_rows)	throw new Exception("Обнаружена ошибка при выполнении платежа! Обратитесь к администратору магазина, сообщив номер заказа $order_id!");
	$order_info = $res->fetch_assoc();
	if($order_info['ok'])		$tmpl->msg("Оплата выполнена. Заказ передан в обработку.","ok");
	else				$tmpl->addContent("Информация о подтверждении оплаты пока не поступила. Подождите 1-2 минуты, и проверьте оплату. <a href='/gpb_pay_success.php'>Проверить оплату</a>");

}
catch(mysqli_sql_exception $e) {
	$db->rollback();
	$tmpl->ajax=0;
	$id = writeLogException($e);
	$tmpl->msg("Порядковый номер ошибки: $id<br>Сообщение об ошибке занесено в журнал", 'err', "Ошибка в базе данных");
}
catch(Exception $e)
{
    $tmpl->addContent("<br><br>");
    writeLogException($e);
    $tmpl->errorMessage($e->getMessage());
}


$tmpl->write();
?>
