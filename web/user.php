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
$tmpl->SetTitle("Дополнительные возможности");
$tmpl->AddText("<h1>Дополнительные возможности</h1>$dsa
<p class=text>На этой странице представлены дополнительные возможности, доступные только зарегистрированным пользователям. Разделы с пометкой 'В разработке' и 'Тестирование' размещены здесь только в целях тестирования и не являются полностью рабочими.");

$tmpl->HideBlock('left');

if($mode=='')
{
	$tmpl->AddText("<ul>");

	//$tmpl->AddText("<li><a href='/user.php?mode=frequest' accesskey='w' style='color: #f00'>Сообщить об ошибке или заказать доработку программы</a></li>");
	

	$rights=getright('doc_list',$uid);
	if($rights['read'])
	{
		$tmpl->AddText("<li><a href='docj.php' accesskey='l' title='Документы'>Журнал документов (L)</a></li>");
	}
	
	$rights=getright('statistic',$uid);
	if($rights['read'])
	{
		$tmpl->AddText("<li><a href='statistics.php' title='Статистика по броузерам'>Статистика по броузерам</a></li>");
	}
	
	$tmpl->AddText("<li><a href='wiki.php' accesskey='w' title='Wiki-статьи'>Wiki-статьи (W)</a></li>");
	
	$rights=getright('cli',$uid);
	if($rights['read'])
	{
		$tmpl->AddText("<li><a href='?mode=cli_status' title=''>Статус внешних обработчиков</a></li>");
	}
	$rights=getright('tickets',$uid);
	if($rights['read'])
	{
		$tmpl->AddText("<li><a href='/tickets.php' title='Задачи'>Планировщик задач</a></li>");
	}


	$rights=getright('errorlog',$uid);
	if($rights['read'])
	{
		$tmpl->AddText("<li><a href='?mode=elog' accesskey='e' title='Ошибки'>Журнал ошибок (E)</a></li>");
	}

	$rights=getright('counterlog',$uid);
	if($rights['read'])
	{
		$tmpl->AddText("<li><a href='?mode=clog'>Журнал посещений</a></li>");
	}

	$rights=getright('deny_ip',$uid);
	if($rights['read'])
	{
		$tmpl->AddText("<li><a href='?mode=denyip'>Запрещенные сайты</a></li>");
	}
	$rights=getright('rights',$uid);
	if($rights['write'])
	{
		$tmpl->AddText("<li><a href='rights.php'>Привилегии доступа</a></li>");
	}
	$tmpl->AddText("</ul>");
}
else if($mode=='frequest')
{
        // create curl resource 
        $ch = curl_init();
        // set url 
        curl_setopt($ch, CURLOPT_URL, "http://multimag.tndproject.org/login");
        //return the transfer as a string 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERPWD, "");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_HEADER, true);
        
        // $output contains the output string 
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
        
        $doc = new DOMDocument();
	$doc->loadHTML($output);
	$doc->normalizeDocument ();
	$form=$doc->getElementById('propertyform');
	$elements=$form->getElementsByTagName("div");
	$token_elem=$elements->item(0)->getElementsByTagName('input')->item(0);
	$token=$token_elem->attributes->getNamedItem('value')->nodeValue;
	
	$type=$doc->getElementById('field-type');

	$tmpl->SetText("<h1>Оформление запроса на доработку программы</h1>
	Внимание! Данная страница в разработке. Вы можете воспользоваться старой версией, доступной по адресу: <a href='http://multimag.tndproject.org/newticket' >http://multimag.tndproject.org/newticket</a>
	<br><br>
	<p class='text'>
	
	Внимательно заполните все поля. Если иное не написано рядом с полем, все поля являются обязательными для заполнения. Особое внимание стоит уделить полю *краткое содержание*. <b>ВНИМАНИЕ! Для удобства отслеживания исполнения задач (вашего и разработчиков) каждая задача должна быть отдельной задачей. Несоблюдение этого условия может привести к тому, что некоторые задачи окажутся незамеченными</b>. Все глобальные задания можно и нужно отслеживать через систему-треккер.
	</p>
	
	<form action='/user.php' method='post'>
	<input type='hidden' name='token' value='$token'>
	<input type='hidden' name='mode' value='sendrequest'>
	<b>Краткое содержание</b>. Тема задачи. Максимально кратко (3-6 слов) и ёмко изложите суть поставленной задачи. Максимум 64 символа.<br>
	<i><u>Пример</u>: Реализовать печатную форму: Товарный чек</i><br>
	<input type='text' maxlength='64' name='summary' style='width:90%'><br>
	<b>Подробное описание</b>. Максимально подробно изложите суть задачи. Описание должно являться дополнением краткого содержания. Не допускается писать несколько задач.<br>
	<textarea name='description' rows='40' cols='6'></textarea><br>
	Тип задачи:<br>
	<select name='field_type'>
	<option>Дефект (Bug)</option><option selected='selected'>Улучшение</option><option>Задача</option><option>Предложение</option>
	</select><br>
	Приоритет:<br>
	<select name='field_priority'>
	<option>Критический</option><option>Важный</option><option selected='selected'>Обычный</option><option>Неважный</option><option>Несущественный</option>
	</select><br>
	Срочность выполнения:<br>
	<select name='field_milestone'>
	<option></option>
	<optgroup label='Open (by due date)'>
	<option selected='selected'>0.1</option>
	</optgroup><optgroup label='Open (no due date)'>
	<option>0.2</option><option>0.9</option><option>1.0</option>
	</optgroup>
	</select><br>
	Компонент:<br>
	<select id='field-component' name='field_component'>
	<option>CLI: Внешние обработчики</option><option>Wiki</option><option>Анализатор прайсов</option><option>Витрина и прайс-лист</option><option>Документы</option><option selected='selected'>Другое</option><option>Отчёты</option><option>Справочники</option><option>Ядро</option>
	</select><br>
	
	<button type='submit'>Сформировать задачу</button>
	</form>
	");




}
else if($mode=="elog")
{
	$rights=getright('errorlog',$uid);
	if($rights['read'])
	{
		$tmpl->AddText("<h1>Журнал ошибок</h1>");
		$res=mysql_query("SELECT `id`, `page`, `referer`, `msg`, `date`, `ip`, `agent`, `uid` FROM `errorlog` ORDER BY `date` DESC");
		$tmpl->AddText("<table width=100%>
		<tr><th>ID<th>Page<th>Referer<th>Msg<th>Date<th>IP<th>Agent<th>UID");
		$i=0;
		while($nxt=mysql_fetch_row($res))
		{
			$i=1-$i;
			$tmpl->AddText("<tr class='lin$i'><td>$nxt[0]<td>$nxt[1]<td>$nxt[2]<td>$nxt[3]<td>$nxt[4]<td>$nxt[5]<td>$nxt[6]<td>$nxt[7]");
		}
		$tmpl->AddText("</table>");
	}
	else $tmpl->logger("У Вас недостаточно прав!");
}
else if($mode=="clog")
{
	$m=rcv('m');
	$rights=getright('counterlog',$uid);
	if($rights['read'])
	{
		$tmpl->AddText("<h1>Журнал посещений</h1>");
		if($m=="")
		{
		$g=" GROUP BY `ip`";
		$tmpl->AddText("<a href='?mode=clog&m=ng'>Без группировки</a><br><br>");
		}
	
		$res=mysql_query("SELECT * FROM `counter` $g ORDER BY `date` DESC");
		$tmpl->AddText("<table width=100%>
		<tr><th>IP<th>Страница<th>Ссылка<th>UserAgent<th>Дата");
			while($nxt=mysql_fetch_row($res))
			{
			$dt=date("Y.M.D H:i:s",$nxt[1]);
				$tmpl->AddText("<tr><td>$nxt[2]<td>$nxt[5]<td>$nxt[4]<td>$nxt[3]<td>$dt");
			}
			$tmpl->AddText("</table>");
	}
	else $tmpl->logger("У Вас недостаточно прав!");
}
else if($mode=='cli_status')
{
	$tmpl->AddText("<h1>Статус внешних обработчиков</h1>
	<table><tr><th>ID<th>Скрипт<th>Состояние");
	$res=mysql_query("SELECT `id`, `script`, `status` FROM `sys_cli_status` ORDER BY `script`");
	while($nxt=mysql_fetch_row($res))
	{
		$tmpl->AddText("<tr><td>$nxt[0]<td>$nxt[1]<td>$nxt[2]");
	}
	$tmpl->AddText("</table>");

}
else if($mode=="oldhid")
{
	$tim=time()-60*60*24;
	$rights=getright('base_items',$uid);
	if($rights['edit'])
	{
		$res=mysql_query("UPDATE `base_items` SET `exist`='1' WHERE `date_update`<'$tim' AND `exist`>'1'");
		if($res) $tmpl->msg("Сделано!");
		else $tmpl->msg("Ошибка!","err");
	}
	else $tmpl->logger("У Вас недостаточно прав!");
}
else if($mode=="nexhid")
{
	$tim=time()-60*60*24;
	$rights=getright('base_items',$uid);
	if($rights['edit'])
	{
		$res=mysql_query("UPDATE `base_items` SET `exist`='0' WHERE `date_update`<'$tim' AND `exist`='1'");
		if($res) $tmpl->msg("Сделано!");
		else $tmpl->msg("Ошибка!","err");
	}
	else $tmpl->logger("У Вас недостаточно прав!");
}

else if($mode=="denyip")
{
	$tmpl->AddText("<h1>Заблокированные ресурсы</h1>
	<a href='?mode=iplog'>Часто посещаемые ресурсы</a>");
	$rights=getright('deny_ip',$uid);
	if($rights['read'])
	{
		$tmpl->AddText("<table border=1><tr><th>ID<th>IP<th>host<th>Действия");
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
	$rights=getright('deny_ip',$uid);
	if($rights['edit'])
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
	$rights=getright('deny_ip',$uid);
	if($rights['delete'])
	{
		$n=rcv('n');
		$res=mysql_query("DELETE FROM `traffic_denyip` WHERE `id`='$n'");
		$tmpl->msg("Готово!","ok");
	}
	else $tmpl->logger("У Вас недостаточно прав!");
}
else if($mode=='iplog')
{
	$rights=getright('deny_ip',$uid);
	if($rights['read'])
	{
	$tmpl->AddText("<h1>25 часто используемых адресов</h1>");
	$res=mysql_query("SELECT `ip_daddr`, COUNT(`ip_daddr`) AS `cnt`, SUM(`ip_totlen`) AS `traf` FROM `ulog` GROUP BY `ip_daddr` ORDER BY `traf` DESC LIMIT 25");
	$tmpl->AddText("<table><tr><th>Адрес<th>Возможное имя сервера<th>Количество обращений<th>Трафик запросов<th>Заблокировать");
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