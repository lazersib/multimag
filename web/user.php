<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2010, BlackLight, TND Team, http://tndproject.org
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
need_auth($tmpl);
$tmpl->SetTitle("Личный кабинет");
$tmpl->SetText("<h1 id='page-title'>Личный кабинет</h1>
<p class=text>На этой странице представлены дополнительные возможности, доступные только зарегистрированным пользователям. Разделы с пометкой 'В разработке' и 'Тестирование' размещены здесь только в целях тестирования и не являются полностью рабочими.</p>");

$tmpl->HideBlock('left');

if($mode=='')
{
	$tmpl->AddText("<ul>");

	//$tmpl->AddText("<li><a href='/user.php?mode=frequest' accesskey='w' style='color: #f00'>Сообщить об ошибке или заказать доработку программы</a></li>");

	if(isAccess('doc_list','view'))
		$tmpl->AddText("<li><a href='/docj.php' accesskey='l' title='Документы'>Журнал документов (L)</a></li>");

	if(isAccess('generic_articles','view'))
		$tmpl->AddText("<li><a href='/wiki.php' accesskey='w' title='Wiki-статьи'>Wiki-статьи (W)</a></li>");

	if(isAccess('generic_tickets','view'))
		$tmpl->AddText("<li><a href='/tickets.php' title='Задачи'>Планировщик задач</a></li>");

	if(isAccess('log_browser','view'))
		$tmpl->AddText("<li><a href='/statistics.php' title='Статистика по броузерам'>Статистика по броузерам</a></li>");

	if(isAccess('log_error','view'))
		$tmpl->AddText("<li><a href='?mode=elog' accesskey='e' title='Ошибки'>Журнал ошибок (E)</a></li>");

	if(isAccess('log_access','view'))
		$tmpl->AddText("<li><a href='?mode=clog'>Журнал посещений</a></li>");

	if(isAccess('sys_async_task','view'))
		$tmpl->AddText("<li><a href='?mode=async_task' title=''>Статус ассинхронных обработчиков</a></li>");

	if(isAccess('sys_ip-blacklist','view'))
		$tmpl->AddText("<li><a href='?mode=denyip'>Запрещенные IP адреса</a></li>");

	if(isAccess('sys_acl','view'))
		$tmpl->AddText("<li><a href='/rights.php'>Привилегии доступа</a></li>");

	if(isAccess('admin_comments','view'))
		$tmpl->AddText("<li><a href='/adm_comments.php'>Администрирование коментариев</a></li>");
	if(isAccess('admin_users','view'))
		$tmpl->AddText("<li><a href='/adm_users.php'>Администрирование пользователей (в разработке)</a></li>");

	$tmpl->AddText("<li><a href='/user.php?mode=user_data'>Личные данные</a></li>");
	$tmpl->AddText("<li><a href='/user.php?mode=doc_hist'>История документов</a></li>");

	$tmpl->AddText("</ul>");
}
else if($mode=='user_data')
{
	$opt=rcv('opt');
	$tmpl->SetText("<h1 id='page-title'>Личные данные</h1>");
	if($opt=='save')
	{
		$rname=rcv('rname');
		$tel=rcv('tel');
		$adres=rcv('adres');
		$subscribe=rcv('subscribe');
		mysql_query("UPDATE `users` SET `subscribe`='$subscribe', `rname`='$rname', `tel`='$tel', `adres`='$adres' WHERE `id`='$uid'");
		if(mysql_errno())	throw new MysqlException("Не удалось обновить основные данные пользователя!");
		$jid=rcv('jid');
		$icq=rcv('icq');
		$skype=rcv('skype');
		$mra=rcv('mra');
		$site_name=rcv('site_name');
		mysql_query("REPLACE INTO `users_data` (`uid`,`param`,`value`) VALUES
		( '$uid' ,'jid','$jid'),
		( '$uid' ,'icq','$icq'),
		( '$uid' ,'skype','$skype'),
		( '$uid' ,'mra','$mra'),
		( '$uid' ,'site_name','$site_name') ");
		if(mysql_errno())	throw new MysqlException("Не удалось обновить дополнительные данные пользователя!");
		$tmpl->msg("Данные обновлены!","ok");
	}


	$res=mysql_query("SELECT `name`, `email`, `date_reg`, `subscribe`, `rname`, `tel`, `adres` FROM `users` WHERE `id`='$uid'");
	if(mysql_errno())	throw new MysqlException("Не удалось получить основные данные пользователя!");
	$user_data=mysql_fetch_assoc($res);
	$user_dopdata=array('kont_lico'=>'','tel'=>'','dop_info'=>'');
	$res=mysql_query("SELECT `param`, `value` FROM `users_data` WHERE `uid`='$uid'");
	if(mysql_errno())	throw new MysqlException("Не удалось получить дополнительные данные пользователя!");
	while($line=mysql_fetch_row($res))	$user_dopdata[$line[0]]=$line[1];

	$subs_checked=$user_data['subscribe']?'checked':'';

	$tmpl->AddText("<form action='' method='post'>
	<input type='hidden' name='mode' value='user_data'>
	<input type='hidden' name='opt' value='save'>
	<table border='0' width='500' class='list'>
	<tr><th colspan='2'>Общие данные
	<tr><td>Логин:<td>{$user_data['name']}
	<tr><td>Дата регистрации:<td>{$user_data['date_reg']}
	<tr><td>E-mail:<td>{$user_data['email']}<br><label><input type='checkbox' name='subscribe' value='1' $subs_checked> Подписка</label>
	<tr><th colspan='2'>Данные физического лица
	<tr><td>Фамилия И.О.<td><input type='text' name='rname' value='{$user_data['rname']}'>
	<tr><td>Телефон<td><input type='text' name='tel' value='{$user_data['tel']}'>
	<tr><td>Адрес доставки<td><input type='text' name='adres' value='{$user_data['adres']}'>
	<tr><th colspan='2'>Дополнительные данные
	<tr><td>Jabber ID<td><input type='text' name='jid' value='{$user_dopdata['jid']}'>
	<tr><td>UIN ICQ<td><input type='text' name='icq' value='{$user_dopdata['icq']}'>
	<tr><td>Skype-login<td><input type='text' name='skype' value='{$user_dopdata['skype']}'>
	<tr><td>Mail-ru ID<td><input type='text' name='mra' value='{$user_dopdata['mra']}'>
	<tr><td>Сайт<td><input type='text' name='site_name' value='{$user_dopdata['site_name']}'>
	<tr><td><td><button type='submit'>Сохранить</button>
	</table></form>");
}
else if($mode=='doc_hist')
{
	$tmpl->SetText("<h1 id='page-title'>Выписанные документы</h1>
	<div class='content'>
	<table width='100%' class='list' cellspacing='0'>
	<tr class='title'><th>Номер<th>Дата<th>Документ<th>Подтверждён ?<th>Дата подтверждения<th>Сумма");
	$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_types`.`name`, `doc_list`.`ok`, `doc_list`.`sum`, `doc_list`.`type`
	FROM `doc_list`
	LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
	WHERE `doc_list`.`user`='{$_SESSION['uid']}'
	ORDER BY `date`");
	$i=0;


	while($nxt=mysql_fetch_array($res))
	{
		$date=date("Y-m-d H:i:s",$nxt['date']);
		$ok=$nxt['ok']?'Да':'Нет';
		$ok_date=$nxt['ok']?date("Y-m-d H:i:s",$nxt['ok']):'';
		$lnum=$nxt[0];
		if($nxt['type']==2 || $nxt['type']==3)	$lnum="<a href='/user.php?mode=doc_view&amp;doc=$nxt[0]'>$nxt[0]</a>";
		$tmpl->AddText("<tr class='lin$i'><td>$lnum<td>$date<td>$nxt[2]<td>$ok<td>$ok_date<td>$nxt[4]");
		$i=1-$i;
	}
	$tmpl->AddText("</table></div>");
}
else if($mode=='doc_view')
{
	try
	{
		include_once("include/doc.core.php");
		include_once("include/doc.nulltype.php");
		$doc=rcv('doc');
		if($doc)
		{
			$res=mysql_query("SELECT `id`, `type`, `user` FROM `doc_list` WHERE `id`='$doc'");
			if(mysql_errno())		throw new Exception("Документ не выбран");
			$doc_data=mysql_fetch_assoc($res);
			if(!$doc_data)				throw new Exception("Документ не найден!");
			if($doc_data['user']!=$uid)		throw new Exception("Документ не найден");

			$document=AutoDocumentType($doc_data['type'], $doc);
			if($doc_data['type']==3)		$document->PrintForm($doc, 'schet_pdf');
			else if($doc_data['type']==2)		$document->PrintForm($doc, 'sf_pdf');
			else					throw new Exception("Способ просмотра не задан!");
		}
		else 	throw new Exception("Документ не указан");
	}
	catch(Exception $e)
	{
		mysql_query("ROLLBACK");
		$tmpl->AddText("<br><br>");
		$tmpl->logger($e->getMessage());
	}
}

// else if($mode=='frequest')
// {
//         // create curl resource
//         $ch = curl_init();
//         // set url
//         curl_setopt($ch, CURLOPT_URL, "http://multimag.tndproject.org/login");
//         //return the transfer as a string
//         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//         curl_setopt($ch, CURLOPT_USERPWD, "");
//         curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
//         curl_setopt($ch, CURLOPT_HEADER, true);
//
//         // $output contains the output string
//         $data = curl_exec($ch);
//         $header=substr($data,0,curl_getinfo($ch,CURLINFO_HEADER_SIZE));
// 	//$body=substr($data,curl_getinfo($ch,CURLINFO_HEADER_SIZE));
// 	preg_match_all("/Set-Cookie: (.*?)=(.*?);/i",$header,$res);
// 	$cookie='';
// 	foreach ($res[1] as $key => $value)
// 		$cookie.= $value.'='.$res[2][$key].'; ';
//
//         curl_setopt($ch, CURLOPT_HEADER, false);
//         curl_setopt($ch, CURLOPT_COOKIE,$cookie);
//         curl_setopt($ch, CURLOPT_URL, "http://multimag.tndproject.org/newticket");
//         $output = curl_exec($ch);
//         // close curl resource to free up system resources
//         curl_close($ch);
//
//         $_SESSION['trac_cookie']=$cookie;
//
//         $doc = new DOMDocument();
// 	$doc->loadHTML($output);
// 	$doc->normalizeDocument ();
// 	$form=$doc->getElementById('propertyform');
// 	$elements=$form->getElementsByTagName("div");
// 	$token_elem=$elements->item(0)->getElementsByTagName('input')->item(0);
// 	$token=$token_elem->attributes->getNamedItem('value')->nodeValue;
//
// 	$type=$doc->getElementById('field-type');
//
// 	$tmpl->SetText("<h1>Оформление запроса на доработку программы</h1>
// 	Внимание! Данная страница в разработке. Вы можете воспользоваться старой версией, доступной по адресу: <a href='http://multimag.tndproject.org/newticket' >http://multimag.tndproject.org/newticket</a>
// 	<br><br>
// 	<p class='text'>
//
// 	Внимательно заполните все поля. Если иное не написано рядом с полем, все поля являются обязательными для заполнения. Особое внимание стоит уделить полю *краткое содержание*. <b>ВНИМАНИЕ! Для удобства отслеживания исполнения задач (вашего и разработчиков) каждая задача должна быть отдельной задачей. Несоблюдение этого условия может привести к тому, что некоторые задачи окажутся незамеченными</b>. Все глобальные задания можно и нужно отслеживать через систему-треккер.
// 	</p>
//
// 	<form action='/user.php' method='post'>
// 	<input type='hidden' name='token' value='$token'>
// 	<input type='hidden' name='mode' value='sendrequest'>
// 	<b>Краткое содержание</b>. Тема задачи. Максимально кратко (3-6 слов) и ёмко изложите суть поставленной задачи. Максимум 64 символа.<br>
// 	<i><u>Пример</u>: Реализовать печатную форму: Товарный чек</i><br>
// 	<input type='text' maxlength='64' name='summary' style='width:90%'><br>
// 	<b>Подробное описание</b>. Максимально подробно изложите суть задачи. Описание должно являться дополнением краткого содержания. Не допускается писать несколько задач.<br>
// 	<textarea name='description' rows='40' cols='6'></textarea><br>
// 	Тип задачи:<br>
// 	<select name='field_type'>
// 	<option>Дефект (Bug)</option><option selected='selected'>Улучшение</option><option>Задача</option><option>Предложение</option>
// 	</select><br>
// 	Приоритет:<br>
// 	<select name='field_priority'>
// 	<option>Критический</option><option>Важный</option><option selected='selected'>Обычный</option><option>Неважный</option><option>Несущественный</option>
// 	</select><br>
// 	Срочность выполнения:<br>
// 	<select name='field_milestone'>
// 	<option></option>
// 	<optgroup label='Open (by due date)'>
// 	<option selected='selected'>0.1</option>
// 	</optgroup><optgroup label='Open (no due date)'>
// 	<option>0.2</option><option>0.9</option><option>1.0</option>
// 	</optgroup>
// 	</select><br>
// 	Компонент:<br>
// 	<select id='field-component' name='field_component'>
// 	<option>CLI: Внешние обработчики</option><option>Wiki</option><option>Анализатор прайсов</option><option>Витрина и прайс-лист</option><option>Документы</option><option selected='selected'>Другое</option><option>Отчёты</option><option>Справочники</option><option>Ядро</option>
// 	</select><br>
//
// 	<button type='submit'>Сформировать задачу</button>
// 	</form>
// 	");
// }
else if($mode=="elog")
{
	if(!isAccess('log_error','view'))	throw new AccessException("Недостаточно привилегий");
	$p=rcv('p');
	settype($p,'int');
	if($p==0)	$p=1;
	$lines=100;
	$from=($p-1)*$lines;
	$tmpl->AddText("<h1>Журнал ошибок</h1>");
	$res=mysql_query("SELECT SQL_CALC_FOUND_ROWS `id`, `page`, `referer`, `msg`, `date`, `ip`, `agent`, `uid` FROM `errorlog` ORDER BY `date` DESC LIMIT $from, $lines");
	list($total) = mysql_fetch_row(mysql_query('SELECT FOUND_ROWS()'));
	$tmpl->AddText("<table width='100%' class='list'>
	<tr><th>ID<th>Page<th>Referer<th>Msg<th>Date<th>IP<th>Agent<th>UID");
	$i=0;
	while($nxt=mysql_fetch_row($res))
	{
		$i=1-$i;
		$tmpl->AddText("<tr class='lin$i'><td>$nxt[0]<td>$nxt[1]<td>$nxt[2]<td>$nxt[3]<td>$nxt[4]<td>$nxt[5]<td>$nxt[6]<td>$nxt[7]");
	}
	$tmpl->AddText("</table>");

	$pages_count = ceil($total/$lines);
	if ($pages_count > 1)
	{
		$tmpl->AddText("<p>Страницы: ");
		for ( $i = 1; $i <= $pages_count; ++$i )
		{
			if($i==$p)	$tmpl->AddText("<b>$i</b> ");
			else		$tmpl->AddText("<a href='?mode=elog&amp;p=$i'>$i</a> ");
		}
	$tmpl->AddText("</p>");
	}

}
else if($mode=="clog")
{
	$m=rcv('m');
	if(!isAccess('log_access','view'))	throw new AccessException("Недостаточно привилегий");

	$tmpl->AddText("<h1>Журнал посещений</h1>");
	if($m=="")
	{
		$g=" GROUP BY `ip`";
		$tmpl->AddText("<a href='?mode=clog&m=ng'>Без группировки</a><br><br>");
	}
	else	$g='';

	$res=mysql_query("SELECT * FROM `counter` $g ORDER BY `date` DESC");
	$tmpl->AddText("<table class='list'>
	<tr><th>IP<th>Страница<th>Ссылка (referer)<th>UserAgent<th>Дата");
	while($nxt=mysql_fetch_row($res))
	{
		$dt=date("Y-m-d H:i:s",$nxt[1]);
		$tmpl->AddText("<tr><td>$nxt[2]<td>$nxt[5]<br><small>$nxt[6]</small><td>$nxt[4]<td>$nxt[3]<td>$dt");
	}
	$tmpl->AddText("</table>");
}
else if($mode=='async_task')
{
	if(!isAccess('sys_async_task','view'))	throw new AccessException("Недостаточно привилегий");
	$tmpl->AddText("<h1>Статус ассинхронных обработчиков</h1>
	<table class='list'><tr><th>ID<th>Скрипт<th>Состояние");
	$res=mysql_query("SELECT `id`, `script`, `status` FROM `sys_cli_status` ORDER BY `script`");
	while($nxt=mysql_fetch_row($res))
	{
		$tmpl->AddText("<tr><td>$nxt[0]<td>$nxt[1]<td>$nxt[2]");
	}
	$tmpl->AddText("</table>");

}
else if($mode=="denyip")
{
	$tmpl->AddText("<h1>Заблокированные ресурсы</h1>
	<a href='?mode=iplog'>Часто посещаемые ресурсы</a>");
	if(isAccess('ip-blacklist','read'))
	{
		$tmpl->AddText("<table class='list'><tr><th>ID<th>IP<th>host<th>Действия");
		$res=mysql_query("SELECT * FROM `traffic_denyip`");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<tr><td>$nxt[0]<td>$nxt[1]<td>$nxt[2]<td><a href='?mode=denyipd&n=$nxt[0]'>Удалить</a>");
		}
		$tmpl->AddText("</table>
		<form method='post' action=''>
		<input type='hidden' name='mode' value='denyipa'>
		Добавить хост:<br>
		<input type='text' name='host'>
		<input type='submit' value='Добавить'>
		</form>");
	}
	else $tmpl->logger("У Вас недостаточно прав!");
}
else if($mode=='denyipa')
{
	if(isAccess('sys_ip-blacklist','create'))
	{
		$host=rcv('host');
		$ipl=gethostbynamel($host);
		foreach($ipl as $ip)
		{
			$tmpl->AddText("У хоста $host адрес $ip, ");
			$res=mysql_query("INSERT INTO `traffic_denyip` (`ip`,`host`) VALUES ('$ip','$host')");
			if(mysql_insert_id()) $tmpl->AddText("и он добавлен в список!<br>");
			else $tmpl->AddText("и такой адрес уже есть в списке!<br>");
		}
	}
	else $tmpl->logger("У Вас недостаточно прав!");
}
else if($mode=="denyipd")
{
	if(isAccess('sys_ip-blacklist','delete'))
	{
		$n=rcv('n');
		$res=mysql_query("DELETE FROM `traffic_denyip` WHERE `id`='$n'");
		$tmpl->msg("Готово!","ok");
	}
	else $tmpl->logger("У Вас недостаточно прав!");
}
else if($mode=='iplog')
{
	if(isAccess('sys_ip-log','view'))
	{
	$tmpl->AddText("<h1>25 часто используемых адресов</h1>");
	$res=mysql_query("SELECT `ip_daddr`, COUNT(`ip_daddr`) AS `cnt`, SUM(`ip_totlen`) AS `traf` FROM `ulog` GROUP BY `ip_daddr` ORDER BY `traf` DESC LIMIT 25");
	$tmpl->AddText("<table class='list'><tr><th>Адрес<th>Возможное имя сервера<th>Количество обращений<th>Трафик запросов<th>Заблокировать");
	while($nxt=mysql_fetch_row($res))
	{
		$ip=long2ip($nxt[0]);
		$addr=gethostbyaddr($ip);

		$tmpl->AddText("<tr><td>$ip<td><a href='http://$addr'>$addr</a><td>$nxt[1]<td>$nxt[2]<td><a href='?mode=denyipa&host=$addr'>хост</a>, <a href='?mode=denyipa&host=$ip'>IP</a>");
	}
	$tmpl->AddText("</table>");
	}
	else $tmpl->logger("У Вас недостаточно прав!");
}
else $tmpl->logger("Uncorrect mode!");



$tmpl->write();

?>