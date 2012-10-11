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
	$res=mysql_query("SELECT `users_worker_info`.`worker` FROM `users`
	LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`users`.`id`
	WHERE `users`.`id`='{$_SESSION['uid']}'");
	if(@mysql_result($res,0,0))
		$tmpl->AddText("<li><a href='/user.php?mode=frequest' accesskey='w' style='color: #f00'>Сообщить об ошибке или заказать доработку программы</a> - ФУНКЦИЯ РАБОТАЕТ !</li>");

	if(isAccess('doc_list','view'))
		$tmpl->AddText("<li><a href='/docj.php' accesskey='l' title='Документы'>Журнал документов (L)</a></li>");

	if(isAccess('doc_fabric','view'))
		$tmpl->AddText("<li><a href='/fabric.php'>Учёт производства (экспериментально)</a></li>");

	if(isAccess('generic_articles','view'))
		$tmpl->AddText("<li><a href='/articles.php' accesskey='w' title='Cтатьи'>Cтатьи (W)</a></li>");

	if(isAccess('generic_tickets','view'))
		$tmpl->AddText("<li><a href='/tickets.php' title='Задачи'>Планировщик задач</a></li>");

	if(isAccess('log_browser','view'))
		$tmpl->AddText("<li><a href='/statistics.php' title='Статистика по броузерам'>Статистика по броузерам</a></li>");

	if(isAccess('log_error','view'))
		$tmpl->AddText("<li><a href='?mode=elog' accesskey='e' title='Ошибки'>Журнал ошибок (E)</a></li>");

	if(isAccess('log_access','view'))
		$tmpl->AddText("<li><a href='?mode=clog'>Журнал посещений</a></li>");

	if(isAccess('sys_async_task','view'))
		$tmpl->AddText("<li><a href='?mode=async_task' title=''>Ассинхронные задачи</a></li>");

	if(isAccess('sys_ps-stat','view'))
		$tmpl->AddText("<li><a href='?mode=psstat' title=''>NEW Статистика переходов с поисковиков (экспериментально)</a></li>");

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
		$jid=rcv('jid');
		$icq=rcv('icq');
		$skype=rcv('skype');
		$mra=rcv('mra');
		$site_name=rcv('site_name');

		mysql_query("UPDATE `users` SET `reg_email_subscribe`='$subscribe', `reg_phone`='$tel', `real_name`='$rname', `real_address`='$adres', `jid`='$jid' WHERE `id`='$uid'");
		if(mysql_errno())	throw new MysqlException("Не удалось обновить основные данные пользователя!");


		mysql_query("REPLACE INTO `users_data` (`uid`,`param`,`value`) VALUES
		( '$uid' ,'jid','$jid'),
		( '$uid' ,'icq','$icq'),
		( '$uid' ,'skype','$skype'),
		( '$uid' ,'mra','$mra'),
		( '$uid' ,'site_name','$site_name') ");
		if(mysql_errno())	throw new MysqlException("Не удалось обновить дополнительные данные пользователя!");
		$tmpl->msg("Данные обновлены!","ok");
	}


	$res=mysql_query("SELECT `name`, `reg_email`, `reg_date`, `reg_email_subscribe`, `real_name`, `reg_phone`, `real_address`, `jid` FROM `users` WHERE `id`='$uid'");
	if(mysql_errno())	throw new MysqlException("Не удалось получить основные данные пользователя!");
	$user_data=mysql_fetch_assoc($res);
	$user_dopdata=array('kont_lico'=>'','tel'=>'','dop_info'=>'');
	$res=mysql_query("SELECT `param`, `value` FROM `users_data` WHERE `uid`='$uid'");
	if(mysql_errno())	throw new MysqlException("Не удалось получить дополнительные данные пользователя!");
	while($line=mysql_fetch_row($res))	$user_dopdata[$line[0]]=$line[1];

	$subs_checked=$user_data['reg_email_subscribe']?'checked':'';
	if(!$user_data['jid'])	$user_data['jid']=@$user_dopdata['jid'];

	@$tmpl->AddText("<form action='' method='post'>
	<input type='hidden' name='mode' value='user_data'>
	<input type='hidden' name='opt' value='save'>
	<table border='0' width='500' class='list'>
	<tr><th colspan='2'>Общие данные
	<tr><td>Логин:<td>{$user_data['name']}
	<tr><td>Дата регистрации:<td>{$user_data['reg_date']}
	<tr><td>E-mail:<td>{$user_data['reg_email']}<br><label><input type='checkbox' name='subscribe' value='1' $subs_checked> Подписка</label>
	<tr><td>Jabber ID<td><input type='text' name='jid' value='{$user_data['jid']}'>
	<tr><th colspan='2'>Данные физического лица
	<tr><td>Фамилия И.О.<td><input type='text' name='rname' value='{$user_data['real_name']}'>
	<tr><td>Телефон<td><input type='text' name='tel' value='{$user_data['reg_phone']}'>
	<tr><td>Адрес доставки<td><input type='text' name='adres' value='{$user_data['real_address']}'>
	<tr><th colspan='2'>Дополнительные данные
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

else if($mode=='frequest')
{
       if(!$CONFIG['site']['trackticket_login'])	throw new Exception("Конфигурация модуля не заполнена!");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://multimag.tndproject.org/login");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERPWD, $CONFIG['site']['trackticket_login'].':'.$CONFIG['site']['trackticket_pass']);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_HEADER, true);

        $data = curl_exec($ch);
        $header=substr($data,0,curl_getinfo($ch,CURLINFO_HEADER_SIZE));
	//$body=substr($data,curl_getinfo($ch,CURLINFO_HEADER_SIZE));
	preg_match_all("/Set-Cookie: (.*?)=(.*?);/i",$header,$res);
	$cookie='';
	foreach ($res[1] as $key => $value)
		$cookie.= $value.'='.$res[2][$key].'; ';

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_COOKIE,$cookie);
        curl_setopt($ch, CURLOPT_URL, "http://multimag.tndproject.org/newticket");
        $output = curl_exec($ch);
        // close curl resource to free up system resources
        curl_close($ch);

        $_SESSION['trac_cookie']=$cookie;

        $doc = new DOMDocument('1.0','UTF8');

	@$doc->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">'.$output);
	$doc->normalizeDocument ();

	$form=$doc->getElementById('propertyform');
	if(!$form)	throw new Exception("Не удалость получить форму треккера!");

	$form_inputs=$form->getElementsByTagName('input');
	$token='';
	foreach($form_inputs as $input)
	{
		$input_name=$input->attributes->getNamedItem('name');
		$input_name=$input_name?$input_name->nodeValue:'';
		if($input_name=='__FORM_TOKEN')
		{
			$input_value=$input->attributes->getNamedItem('value');
			$input_value=$input_value?$input_value->nodeValue:'';
			$token=$input_value;
			break;
		}
	}
	$form_selects=$form->getElementsByTagName('select');
	$selects=array();
	$selects_html=array();
	foreach($form_selects as $select)
	{
		$select_name=$select->attributes->getNamedItem('name');
		$select_name=$select_name?$select_name->nodeValue:'';
		$selects[$select_name]=array();
		$select_options=$select->getElementsByTagName('option');
		$selects_html[$select_name]='';

		foreach($select_options as $option)
		{
			if($option->nodeValue=='Ядро')	continue;
			$selected=$option->attributes->getNamedItem('selected');
			$selected=$selected?' selected':'';
			$selects[$select_name][]=$option->nodeValue;
			$selects_html[$select_name].='<option'.$selected.'>'.$option->nodeValue.'</option>';
		}
	}

	$tmpl->SetTitle("Запрос на доработку программы");
	$tmpl->SetText("<h1 id='page-title'>Оформление запроса на доработку программы</h1>
	<div id='page-info'>Внимание! Страница является упрощённым интерфейсом к <a href='http://multimag.tndproject.org/newticket' >http://multimag.tndproject.org/newticket</a></div>
	<p class='text'>Заполняя эту форму, вы формируете заказ на доработку сайта от имени Вашей организации в общедоступный реестр заказов, расположенный по адресу <a href='http://multimag.tndproject.org/report/3'>http://multimag.tndproject.org/report/3</a>.
	<br>
	Внимательно заполните все поля. Если иное не написано рядом с полем, все поля являются обязательными для заполнения. Особое внимание стоит уделить полю *краткое содержание*.
	<br>
	<b>Для удобства отслеживания исполнения задач (вашего и разработчиков) каждая задача должна быть добавлена отдельно. Нарушение этого условия скорее всего приведёт к тому, что некоторые задачи окажутся незамеченными.</b>
	<br>
	Все задания можно и нужно отслеживать через систему-треккер.
	</p>
	</p>

	<form action='/user.php' method='post'>
	<input type='hidden' name='token' value='$token'>
	<input type='hidden' name='mode' value='sendrequest'>

	<b>Тип задачи</b> определяет суть задачи и очерёдность её исполнения.
	<ul>
	<li>Тип <u>Дефект</u> используется для информирования разработчиков о неверной работе существующих частей сайта. Такие задачи исполняются в первую очередь.</li>
	<li>Тип <u>Улучшение</u> используйте для задач по доработке существующего функционала сайта</li>
	<li>Тип <u>Задача</u> используется для задач, описывающих новый функционал. Это тип по умолчанию.</li>
	<li>Тип <u>Предложение</u> используете в том случае, если Вам бы хотелось видеть какой-либо функционал на сайте, но Вы не планируете заказывать его разработку в ближайшее время. Используется для отправки идей по доработке разработчикам и другим пользователям программы.</li>
	</ul>
	<i><u>Пример</u>: Задача</i><br>
	<select name='field_type'>{$selects_html['field_type']}</select><br><br>

	<b>Краткое содержание</b>. Тема задачи. Максимально кратко (3-8 слов) и ёмко изложите суть поставленной задачи. Максимум 64 символа.<br>
	<i><u>Пример</u>: Реализовать печатную форму: Приходный кассовый ордер</i><br>
	<input type='text' maxlength='64' name='field_summary' style='width:90%'><br><br>

	<b>Подробное описание</b>. Максимально подробно изложите суть задачи. Описание должно являться дополнением краткого содержания. Не допускается писать несколько задач. Можно использовать wiki разметку для форматирвания.<br>
	<i><u>Пример</u>: Форма должна быть доступна в документе *приходный кассовый ордер*, должна быть в PDF формате, и соответствовать общепринятой форме КО-1</i><br>
	<textarea name='field_description' rows='7' cols='80'></textarea><br><br>

	<b>Компонент приложения</b>. Выбирается исходя из того, к какой части сайта относится ваша задача. Если задача относится к вашим индивидуальным модификациям - выбирайте *пользовательский дизайн*<br>
	<i><u>Пример</u>: Документы</i><br>
	<select name='field_component'>{$selects_html['field_component']}</select><br><br>

	<b>Приоритет</b> определяет то, насколько срочно требуется выполнить поставленную задачу. Критический приоритет допустимо указывать только для задач с типом *дефект*<br>
	<i><u>Пример</u>: Обычный</i><br>
	<select name='field_priority'>{$selects_html['field_priority']}</select><br><br>

	<b>Целевая версия</b> нужна, чтобы указать, в какой версии программы вы хотели бы видеть реализацию этой задачи. Вы можете отложить реализацию, указав более позднюю версию. Нет смысла выбирать более раннюю версию, т.к. приём задач в неё закрыт. В случае, если задача не соответствует целям версии, разработчики могут изменить этот параметр.<br>
	<i><u>Пример</u>: 0.9</i><br>
	<select name='field_milestone'>{$selects_html['field_milestone']}</select><br><br>

	<button type='submit'>Опубликовать задачу</button>
	</form>");
}
else if($mode=='sendrequest')
{
	$fields=array(
	'__FORM_TOKEN'		=> $_POST['token'],
	'field_type' 		=> $_POST['field_type'],
	'field_summary' 	=> $_POST['field_summary'],
	'field_description'	=> $_POST['field_description']."\nUser: {$_SESSION['name']} at {$_SERVER['HTTP_HOST']}",
	'field_component'	=> $_POST['field_component'],
	'field_priority'	=> $_POST['field_priority'],
	'field_milestone'	=> $_POST['field_milestone'],
	'field_reporter'	=> $CONFIG['site']['trackticket_login'],
	'submit'		=> 'submit'
	);

	var_dump($fields);
	var_dump($_SESSION['trac_cookie']);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://multimag.tndproject.org/newticket");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_COOKIE,$_SESSION['trac_cookie'].' trac_form_token='.$_POST['token']);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);


        $data = curl_exec($ch);
        $header=substr($data,0,curl_getinfo($ch,CURLINFO_HEADER_SIZE));
	$body=substr($data,curl_getinfo($ch,CURLINFO_HEADER_SIZE));
        curl_close($ch);

	$ticket=0;
	$ticket_url='';
	$hlines=explode("\n",$header);
	foreach($hlines as $line)
	{
		$line=trim($line);
		if(strpos($line,'Location')===0)
		{
			$chunks=explode(": ",$line);
			$ticket_url=trim($chunks[1]);
			$chunks=explode("/",$ticket_url);
			$ticket=$chunks[count($chunks)-1];
			settype($ticket,'int');
			break;
		}
	}

	$tmpl->SetText("<h1 id='page-title'>Оформление запроса на доработку программы</h1>
	<div id='page-info'>Внимание! Страница является упрощённым интерфейсом к <a href='http://multimag.tndproject.org/newticket' >http://multimag.tndproject.org/newticket</a></div>");
	if($ticket)
	{
		$tmpl->msg("Номер задачи: <b>$ticket</b>.<br>Посмотресть созданную задачу, а так же следить за ходом её выполнения, можно по ссылке: <a href='$ticket_url'>$ticket_url</a>","ok","Задача успешно внесена в реестр!");
		$tmpl->AddText("<iframe width='100%' height='70%' src='$ticket_url'></iframe>");
	}
	else	$tmpl->msg("Не удалось создать задачу! Сообщите о проблеме своему системному администратору!","err");
}
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
	$task=rcv('task');
	if($task)
	{
		if(!isAccess('sys_async_task','exec'))	throw new AccessException("Недостаточно привилегий");
		mysql_query("INSERT INTO `async_workers_tasks` (`task`, `needrun`, `textstatus`) VALUES ('$task', 1, 'Запланировано')");
		if(mysql_errno())	throw new MysqlException("Не удалось запланировать задачу");
	}

	$tmpl->AddText("<h1>Ассинхронные задачи</h1>");
	$dir=$CONFIG['location'].'/common/async/';
	if (is_dir($dir))
	{
		if ($dh = opendir($dir))
		{
			$tmpl->AddText("<ul>");
			while (($file = readdir($dh)) !== false)
			{
				if( preg_match('/.php$/',$file) )
				{
					$cn=explode('.',$file);
					include_once("$dir/$file");
					$class_name=$cn[0]."Worker";;
					$class=new $class_name(0);
					$nm=$class->getDescription();
					$tmpl->AddText("<li><a href='/user.php?mode=async_task&amp;task=$cn[0]'>Запланировать $cn[0] ($nm)</a></li>");

				}
			}
			closedir($dh);
			$tmpl->AddText("</ul>");
		}
	}

	$tmpl->AddText("
	<table class='list'><tr><th>ID<th>Задача<th>Ож.запуска<th>Состояние");
	$res=mysql_query("SELECT `id`, `task`, `needrun`, `textstatus` FROM `async_workers_tasks` ORDER BY `id` DESC");
	while($nxt=mysql_fetch_row($res))
	{
		$tmpl->AddText("<tr><td>$nxt[0]<td>$nxt[1]<td>$nxt[2]<td>$nxt[3]");
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
	if(!isAccess('sys_ip-log','view'))	throw new AccessException();

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
else if($mode=='psstat')
{
	if(!isAccess('sys_ps-stat','view'))	throw new AccessException();
	$tmpl->SetTitle("Статистика переходов по поисковым запросам");
	$tmpl->SetText("<h1 id='page-title'>Статистика переходов по поисковым запросам</h1>");
	if(isset($_POST['date']))
	{
		if(preg_match('/^(([0-9]{4})-([0-9]{2})-([0-9]{2}))$/',$_POST['date'],$data_post))
		{
			$data_post = mktime(0, 0, 0, $data_post[3], $data_post[4], $data_post[2]);
			if ($data_post>time()) $data_post = time();
		}
		else $data_post = time();
	}
	else $data_post = time();

	$tmpl->AddText("<form action='' method='post'>Статистика за 7 дней, по дату <input name='date' type='text' value='".date('Y-m-d', $data_post)."' maxlength='10'> (YYYY-MM-DD) <button type='submit'>Получить данные</button></form>");

	if(isset($_POST['date']))
	{
		$data_post_1 = $data_post - (24*60*60);
		$data_post_2 = $data_post_1 - (24*60*60);
		$data_post_3 = $data_post_2 - (24*60*60);
		$data_post_4 = $data_post_3 - (24*60*60);
		$data_post_5 = $data_post_4 - (24*60*60);
		$data_post_6 = $data_post_5 - (24*60*60);

		$tmpl->AddText("<table class='list' width='100%'>
		<tr>
		<th scope='col'>Поисковый запрос</th>
		<th scope='col'>Всего:</th>
		<th scope='col'>".date("Y-m-d", $data_post_6)."</th>
		<th scope='col'>".date("Y-m-d", $data_post_5)."</th>
		<th scope='col'>".date("Y-m-d", $data_post_4)."</th>
		<th scope='col'>".date("Y-m-d", $data_post_3)."</th>
		<th scope='col'>".date("Y-m-d", $data_post_2)."</th>
		<th scope='col'>".date("Y-m-d", $data_post_1)."</th>
		<th scope='col'>".date("Y-m-d", $data_post)."</th>
		</tr>");

		$counter_data = "SELECT `ps_query`.`query`,
		sum(`main`.`counter`) as `counter`,
		`".date("Y-m-d", $data_post_6)."`.`counter` as `".date("Y-m-d", $data_post_6)."`,
		`".date("Y-m-d", $data_post_5)."`.`counter` as `".date("Y-m-d", $data_post_5)."`,
		`".date("Y-m-d", $data_post_4)."`.`counter` as `".date("Y-m-d", $data_post_4)."`,
		`".date("Y-m-d", $data_post_3)."`.`counter` as `".date("Y-m-d", $data_post_3)."`,
		`".date("Y-m-d", $data_post_2)."`.`counter` as `".date("Y-m-d", $data_post_2)."`,
		`".date("Y-m-d", $data_post_1)."`.`counter` as `".date("Y-m-d", $data_post_1)."`,
		`".date("Y-m-d", $data_post)."`.`counter` as `".date("Y-m-d", $data_post)."`
		from `ps_counter` as `main`
		left join `ps_query` on `ps_query`.`id` = `main`.`query`
		left join (
		SELECT `query`, sum(`counter`) as `counter` from `ps_counter` where `date` = '".date("Y-m-d", $data_post_6)."' group by `query`
		) as `".date("Y-m-d", $data_post_6)."` on `".date("Y-m-d", $data_post_6)."`.`query` = `main`.`query`
		left join (
		SELECT `query`, sum(`counter`) as `counter` from `ps_counter` where `date` = '".date("Y-m-d", $data_post_5)."' group by `query`
		) as `".date("Y-m-d", $data_post_5)."` on `".date("Y-m-d", $data_post_5)."`.`query` = `main`.`query`
		left join (
		SELECT `query`, sum(`counter`) as `counter` from `ps_counter` where `date` = '".date("Y-m-d", $data_post_4)."' group by `query`
		) as `".date("Y-m-d", $data_post_4)."` on `".date("Y-m-d", $data_post_4)."`.`query` = `main`.`query`
		left join (
		SELECT `query`, sum(`counter`) as `counter` from `ps_counter` where `date` = '".date("Y-m-d", $data_post_3)."' group by `query`
		) as `".date("Y-m-d", $data_post_3)."` on `".date("Y-m-d", $data_post_3)."`.`query` = `main`.`query`
		left join (
		SELECT `query`, sum(`counter`) as `counter` from `ps_counter` where `date` = '".date("Y-m-d", $data_post_2)."' group by `query`
		) as `".date("Y-m-d", $data_post_2)."` on `".date("Y-m-d", $data_post_2)."`.`query` = `main`.`query`
		left join (
		SELECT `query`, sum(`counter`) as `counter` from `ps_counter` where `date` = '".date("Y-m-d", $data_post_1)."' group by `query`
		) as `".date("Y-m-d", $data_post_1)."` on `".date("Y-m-d", $data_post_1)."`.`query` = `main`.`query`
		left join (
		SELECT `query`, sum(`counter`) as `counter` from `ps_counter` where `date` = '".date("Y-m-d", $data_post)."' group by `query`
		) as `".date("Y-m-d", $data_post)."` on `".date("Y-m-d", $data_post)."`.`query` = `main`.`query`
		where `main`.`date` >= '".date("Y-m-d", $data_post_6)."' and `main`.`date` <= '".date("Y-m-d", $data_post)."'
		group by `main`.`query`
		order by `counter` DESC";

		if($counter_data = mysql_query($counter_data))
		{
			while ($counter_data_row = mysql_fetch_row($counter_data))
			{
				$tmpl->AddText("<tr>
				<td>". $counter_data_row[0] ."</td>
				<td>". $counter_data_row[1] ."</td>
				<td>". $counter_data_row[2] ."</td>
				<td>". $counter_data_row[3] ."</td>
				<td>". $counter_data_row[4] ."</td>
				<td>". $counter_data_row[5] ."</td>
				<td>". $counter_data_row[6] ."</td>
				<td>". $counter_data_row[7] ."</td>
				<td>". $counter_data_row[8] ."</td>
				</tr>");
			}
		}
		$tmpl->AddText("</table>");
	}
}
else $tmpl->logger("Uncorrect mode!");



$tmpl->write();

?>