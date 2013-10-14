<?php

//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2013, BlackLight, TND Team, http://tndproject.org
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

try
{

need_auth($tmpl);
$tmpl->setTitle("Администрирование пользователей");
if(!isAccess('admin_users','view'))	throw new AccessException("Недостаточно привилегий");

$mode=request('mode');

if($mode=='')
{
	$order='`users`.`id`';
	$res=$db->query("SELECT `users`.`id`, `users`.`name`, `users`.`reg_email`, `users`.`reg_email_confirm`, `users`.`reg_email_subscribe`, `users`.`reg_phone`, `users`.`reg_phone_confirm`, `users`.`reg_phone_subscribe`, `users`.`reg_date`, `users_worker_info`.`worker`,
	( SELECT `date` FROM `users_login_history` WHERE `user_id`=`users`.`id` ORDER BY `date` DESC LIMIT 1) AS `lastlogin_date`,
	( SELECT `user_id` FROM `users_openid` WHERE `user_id`=`users`.`id` LIMIT 1) AS `openid`
	FROM `users`
	LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`users`.`id`
	ORDER BY $order");
	$tmpl->addContent("<h1 id='page-title'>Список пользователей</h1>
	<table class='list' width='100%'>
	<tr><th rowspan='2'>ID</th>
	<th rowspan='2'>Имя</th>
	<th colspan='3'>email</th>
	<th colspan='3'>Телефон</th>
	<th rowspan='2'>OpenID</th>
	<th rowspan='2'>Последнее посещение</th>
	<th rowspan='2'>Сотрудник?</th>
	<th rowspan='2'>Дата регистрации</th>
	</tr>
	<tr>
	<th>адрес</th><th>С</th><th>S</th>
	<th>номер</th><th>С</th><th>S</th>
	</tr>
	");
	while($line=$res->fetch_assoc()) {
		$econfirm=$line['reg_email_confirm']=='1'?'Да':'Нет';
		$esubscribe=$line['reg_email_subscribe']?'Да':'Нет';

		$p_email=$line['reg_email']?"<a href='mailto:{$line['reg_email']}'>{$line['reg_email']}</a>":'';
		$pconfirm=$line['reg_phone_confirm']=='1'?'Да':'Нет';
		$psubscribe=$line['reg_phone_subscribe']?'Да':'Нет';

		$openid=$line['openid']?'Да':'Нет';

		$worker=$line['worker']?'Да':'Нет';

		@$tmpl->addContent("<tr><td><a href='?mode=view&amp;id={$line['id']}'>{$line['id']}</a></td><td>{$line['name']}</td>
		<td>$p_email</td><td>$econfirm</td><td>$esubscribe</td>
		<td>{$line['reg_phone']}</td><td>$pconfirm</td><td>$psubscribe</td>
		<td>$openid</td>
		<td>{$line['lastlogin_date']}</td><td>$worker</td><td>{$line['reg_date']}</td></tr>");
	}
	$tmpl->addContent("</table>");
}
else if($mode=='view')
{
	if(!isAccess('admin_users','view'))	throw new AccessException("Недостаточно привилегий");
	$id=rcvint('id');
	$res=$db->query("SELECT * FROM `users`
	LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`users`.`id`
	WHERE `id`='$id'");
	if(!$res->num_rows)		throw new Exception("Пользователь не найден!");
	$line=$res->fetch_assoc();

	$passch=$line['pass_change']?'Да':'Нет';
	$passexp=$line['pass_expired']?'Да':'Нет';
	switch($line['pass_type'])
	{
		case 'CRYPT':	$pass_hash='Сильная';	break;
		case 'SHA1':	$pass_hash='Средняя';	break;
		default: {
			if($line['pass']=='0')	$pass_hash='Пароль не задан';
			else			$pass_hash='Слабая';
		}
	}
	$bifact=$line['bifact_auth']?'Да':'Нет';
	$econfirm=$line['reg_email_confirm']=='1'?'Да':'Нет';
	$esubscribe=$line['reg_email_subscribe']?'Да':'Нет';
	$p_email=$line['reg_email']?"<a href='mailto:{$line['reg_email']}'>{$line['reg_email']}</a>":'';
	$pconfirm=$line['reg_phone_confirm']=='1'?'Да':'Нет';
	$psubscribe=$line['reg_phone_subscribe']?'Да':'Нет';
	$diasbled=$line['disabled']?('Да, '.$line['disabled_reason']):'Нет';



	$worker=$line['worker']?'Да':'Нет';

	$tmpl->addContent("<h1 id='page-title'>Данные пользователя</h1>
	<table class='list'>
	<tr><th colspan='2'>Основная информация</th></tr>
	<tr><td>ID</td><td>{$line['id']}</td></tr>
	<tr><td>Имя</td><td>".html_out($line['name'])."</td></tr>
	<tr><td>Дата регистрации</td><td>{$line['reg_date']}</td></tr>
	<tr><td>Заблокирован (забанен)</td><td>$diasbled</td></tr>
	<tr><td>Меняет пароль?</td><td>$passch</td></tr>
	<tr><td>Смна пароля при след. входе?</td><td>$passexp</td></tr>
	<tr><td>Дата смены пароля</td><td>{$line['pass_date_change']}</td></tr>
	<tr><td>Стойкость хэша пароля</td><td>$pass_hash</td></tr>
	<tr><td>Двухфакторная аутентификация</td><td>$bifact</td></tr>
	<tr><td>Регистрационный email</td><td><a href='mailto:{$line['reg_email']}'>{$line['reg_email']}</a></td></tr>
	<tr><td>emal подтверждён?</td><td>$econfirm</td></tr>
	<tr><td>email подписан?</td><td>$esubscribe</td></tr>
	<tr><td>Регистрационный телефон</td><td><a href='mailto:{$line['reg_phone']}'>{$line['reg_phone']}</a></td></tr>
	<tr><td>телефон подтверждён?</td><td>$pconfirm</td></tr>
	<tr><td>телефон подписан?</td><td>$psubscribe</td></tr>
	<tr><td>Jabber ID</td><td>{$line['jid']}</td></tr>
	<tr><td>Настоящее имя</td><td>".html_out($line['real_name'])."</td></tr>
	<tr><td>Адрес доставки заказов</td><td>".html_out($line['real_address'])."</td></tr>
	<tr><th colspan='2'>Связь с агентами</th></tr>");
	if(!$line['agent_id'])
	{
		$tmpl->addContent("<tr><td>Связь отсутствует</td><td><a href='/adm_users.php?mode=agent&amp;id=$id'>Установить</a></td></tr>");
	}
	else
	{
		$res=$db->query("SELECT `id`, `name`, `fullname`, `tel`, `fax_phone`, `sms_phone`, `adres`, `data_sverki` FROM `doc_agent` WHERE `id`='{$line['agent_id']}'");
		$adata=$res->fetch_assoc();
		$tmpl->addContent("
		<tr><td>ID агента</td><td><a href='/docs.php?l=agent&mode=srv&opt=ep&pos={$adata['id']}'>{$adata['id']}</a> - <a href='/adm_users.php?mode=agent&amp;id=$id'>Убрать связь</a></td></tr>
		<tr><td>Краткое название</td><td>".html_out($adata['name'])."</td></tr>
		<tr><td>Полное название</td><td>".html_out($adata['fullname'])."</td></tr>
		<tr><td>Телефон</td><td>".html_out($adata['tel'])."</td></tr>
		<tr><td>Факс</td><td>".html_out($adata['fax_phone'])."</td></tr>
		<tr><td>Телефон для SMS</td><td>".html_out($adata['sms_phone'])."</td></tr>
		<tr><td>Адрес</td><td>".html_out($adata['adres'])."}</td></tr>
		<tr><td>Дата сверки</td><td>".html_out($adata['data_sverki'])."</td></tr>
		");
	}
	$tmpl->addContent("
	<tr><th colspan='2'>Карточка сотрудника (<a href='/adm_users.php?mode=we&amp;id=$id'>править</a>)</th></tr>
	<tr><td>Является сотрудником</td><td>$worker</td></tr>");
	if($line['worker'])
		$tmpl->addContent("<tr><td>Рабочий email</td><td><a href='mailto:{$line['worker_email']}'>{$line['worker_email']}</a></td></tr>
		<tr><td>Рабочий телефон</td><td>".html_out($line['worker_phone'])."</td></tr>
		<tr><td>Рабочий Jabber</td><td>".html_out($line['worker_jid'])."</td></tr>
		<tr><td>Рабочее имя</td><td>".html_out($line['worker_real_name'])."</td></tr>
		<tr><td>Рабочий адрес</td><td>".html_out($line['worker_real_address'])."</td></tr>");

	$tmpl->addContent("<tr><th colspan='2'>Дополнительная информация</th></tr>");
	$res=$db->query("SELECT `param`, `value` FROM `users_data` WHERE `uid`='$id'");
	while($line=$res->fetch_row())
	{
		$tmpl->addContent("<tr><td>$line[0]</td><td>".html_out($line[1])."</td></tr>");
	}
	$tmpl->addContent("<tr><th colspan='2'><a href='/adm_users.php?mode=view_login_history&amp;id=$id'>История входов</a></th></tr>");
	$tmpl->addContent("</table>");
}
else if($mode=='view_login_history')
{
	if(!isAccess('admin_users','view'))	throw new AccessException("Недостаточно привилегий");
	$id=rcvint('id');
	$tmpl->addContent("<h1 id='page-title'>Данные пользователя</h1>
	<table class='list'>
	<tr><th colspan='2'><a href='/adm_users.php?mode=view&amp;id=$id'>Основная информация</a></th></tr>
	<tr><th colspan='2'>История входов</th></tr>");
	$res=$db->query("SELECT `date`, CONCAT(`ip`,' - ',`method`) FROM `users_login_history` WHERE `user_id`='$id' ORDER BY `date` DESC");
	while($line=$res->fetch_row())
	{
		$tmpl->addContent("<tr><td>$line[0]</td><td>$line[1]</td></tr>");
	}

	$tmpl->addContent("</table>");
}
else if($mode=='agent')
{
	if(!isAccess('admin_users','edit'))	throw new AccessException("Недостаточно привилегий");
	$id=rcvint('id');
	if(isset($_REQUEST['opt']))
	{
		if($_REQUEST['agent_nm'])
		{
			$agent_id=$_REQUEST['agent_id'];
			settype($agent_id,'int');
		}
		else	$agent_id='NULL';
		$res=$db->query("UPDATE `users` SET `agent_id`=$agent_id WHERE `id`='$id'");
		$tmpl->msg("Привязка выполнена!",'ok');
	}
	$res=$db->query("SELECT `users`.`agent_id`, `doc_agent`.`name` FROM `users`
	LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`users`.`agent_id`
	WHERE `users`.`id`='$id'");
	if(!$res->num_rows)		throw new Exception("Пользователь не найден!");
	$line = $res->fetch_assoc();
	$tmpl->addContent("<h1 id='page-title'>Привязка пользователя к агенту</h1>
	<div id='page-info'><a href='/adm_users.php?mode=view&amp;id=$id'>Назад</a></div>
	<form action='' method='post'>
	<input type='hidden' name='id' value='$id'>
	<input type='hidden' name='mode' value='agent'>
	<input type='hidden' name='opt' value='save'>
	Краткое название прикрепляемого агента:<br>
	<script type='text/javascript' src='/css/jquery/jquery.js'></script>
	<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
	<input type='hidden' name='agent_id' id='agent_id' value='{$line['agent_id']}'>
	<input type='text' id='agent_nm' name='agent_nm'  style='width: 450px;' value='".html_out($line['name'])."'><br>

	<script type=\"text/javascript\">
	$(document).ready(function(){
		$(\"#agent_nm\").autocomplete(\"/docs.php\", {
			delay:300,
			minChars:1,
			matchSubset:1,
			autoFill:false,
			selectFirst:true,
			matchContains:1,
			cacheLength:10,
			maxItemsToShow:15,
			formatItem:usliFormat,
			onItemSelect:usselectItem,
			extraParams:{'l':'agent','mode':'srv','opt':'ac'}
		});
	});

	function usliFormat (row, i, num) {
		var result = row[0] + \"<em class='qnt'>id: \" +
		row[1] + \"</em> \";
		return result;
	}
	function usselectItem(li) {
		if( li == null ) var sValue = \"Ничего не выбрано!\";
		if( !!li.extra ) var sValue = li.extra[0];
		else var sValue = li.selectValue;
		document.getElementById('agent_id').value=sValue;
	}
	</script>
	<input type='submit' value='Записать'>
	</form>");
}


}
catch(Exception $e)
{
	$db->rollback();
	$tmpl->addContent("<br><br>");
	$tmpl->logger($e->getMessage());
}

$tmpl->write();

?>