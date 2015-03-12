<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2015, BlackLight, TND Team, http://tndproject.org
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

try {
    $tmpl->setTitle("Личный кабинет");
    $tmpl->setContent("<h1>Личный кабинет</h1>");

    $tmpl->hideBlock('left');
    $mode = request('mode');
    
    if ($mode == '') {
        $tmpl->addBreadcrumb('Главная', '/');
        $tmpl->addBreadcrumb('Личный кабинет', '');
        $auth = new \authenticator();
        $auth->loadDataForID($_SESSION['uid']);
        if( $auth->isNeedConfirmEmail() || $auth->isNeedConfirmPhone() ) {
            $login_page = new \Modules\Site\login();
            $tmpl->addContent( $login_page->getConfirmForm($_SESSION['name']) ."<br><br>" );
        }
        
        $block = '';
        if (isAccess('doc_list', 'view')) {
            $block .= "<li><a href='/docj_new.php' accesskey='l' title='Документы'>Журнал документов (L)</a></li>";
        }
        if (isAccess('doc_factory', 'view')) {
            $block .= "<li><a href='/factory.php'>Учёт производства (экспериментально)</a></li>";
        }
        if (isAccess('log_call_request', 'view')) {
            $block .= "<li><a href='?mode=log_call_request' accesskey='c'>Журнал запрошенных звонков (C)</a></li>";
        }
        if (isAccess('sys_async_task', 'view')) {
            $block .= "<li><a href='?mode=async_task' title=''>Ассинхронные задачи</a></li>";
        }
        if (isAccess('sys_ip-blacklist', 'view')) {
            $block .= "<li><a href='?mode=denyip'>Запрещенные IP адреса</a></li>";
	}
        if (isAccess('generic_tickets', 'view')) {
            $block .= "<li><a href='/tickets.php' accesskey='t' title='Задания'>Планировщик заданий (T)</a></li>";
        }
        if( $block ) {
            $tmpl->addContent("<h2>Сотруднику</h2>"
                . "<ul class='items'>"
                . "<li><a href='/user.php?mode=feedback' style='color: #f00' accesskey='r'>Сообщить об ошибке или заказать доработку программы (R)</a></li>"
                . $block
                . "</ul>");
	}
	$block = '';
	// Администрирование
        if (isAccess('admin_comments', 'view')) {
            $block .= "<li><a href='/adm_comments.php'>Администрирование коментариев</a></li>";
        }
        if (isAccess('admin_users', 'view')) {
            $block .= "<li><a href='/adm_users.php'>Администрирование пользователей</a></li>";
        }
        if (isAccess('admin_mailconfig', 'view')) {
            $block .= "<li><a href='/adm.php?mode=mailconfig'>Настройка почтовых ящиков и алиасов</a></li>";
        }	
  	if (isAccess('sys_ps-stat', 'view')) {
            $block .= "<li><a href='?mode=psstat' title=''>Статистика переходов с поисковиков</a></li>";
	}	
	if (isAccess('sys_acl', 'view')) {
            $block .= "<li><a href='/rights.php'>Привилегии доступа</a></li>";
	}
        if (isAccess('log_browser', 'view')) {
            $block .= "<li><a href='/statistics.php' title='Статистика по броузерам'>Статистика по броузерам</a></li>";
        }
        if (isAccess('log_error', 'view')) {
            $block .= "<li><a href='?mode=elog' accesskey='e' title='Ошибки'>Журнал ошибок (E)</a></li>";
        }
        if (isAccess('log_access', 'view')) {
            $block .= "<li><a href='?mode=clog'>Журнал посещений</a></li>";
	}
        if( $block ) {
            $tmpl->addContent("<h2>Администратору</h2>"
                . "<ul class='items'>$block</ul>");
	}

	$block = '';
        if (isAccess('generic_articles', 'view')) {
                $block .= "<li><a href='/articles.php' accesskey='w' title='Cтатьи'>Cтатьи (W)</a></li>";
        }
	$block .= "<li><a href='/user.php?mode=profile' accesskey='p'>Мой профиль (P)</a></li>";
	$block .= "<li><a href='/user.php?mode=my_docs' accesskey='d'>Мои документы (D)</a></li>";
	$block .= "<li><a href='/voting.php'>Голосования</a></li>";
        
        if( $block ) {
            $tmpl->addContent("<h2>Клиенту</h2>"
                . "<ul class='items'>$block</ul>");
	}
}
else if($mode=='profile' || $mode=='chpwd' || $mode=='cemail' || $mode=='cphone' || $mode=='my_docs' || $mode=='get_doc' || $mode=='elog' 
    || $mode=='log_call_request' || $mode=='feedback'  || $mode=='feedback_send' ){
    $cab = new \Modules\Site\cabinet();
    $cab->ExecMode($mode);
}
else if ($mode == "clog") {
	if (!isAccess('log_access', 'view'))	throw new AccessException();

	$tmpl->addContent("<h1>Журнал посещений</h1>");
	if (request('m')) {
		$g = " GROUP BY `ip`";
		$tmpl->addContent("<a href='?mode=clog&m=ng'>Без группировки</a><br><br>");
	}
	else	$g = '';
	$p = 
	$res = $db->query("SELECT * FROM `counter` $g ORDER BY `date` DESC");
	$tmpl->addContent("<table class='list'><tr><th>IP</th><th>Страница</th><th>Ссылка (referer)</th><th>UserAgent</th><th>Дата</th></tr>");
	while ($nxt = $res->fetch_row()) {
		$dt = date("Y-m-d H:i:s", $nxt[1]);
		$tmpl->addContent("<tr><td>$nxt[2]</td><td>" . html_out($nxt[5]) . "<br><small>" . html_out($nxt[6]) . "</small></td><td>" . html_out($nxt[4]) . "</td><td>" . html_out($nxt[3]) . "</td><td>$dt</td></tr>");
	}
	$tmpl->addContent("</table>");
}
else if($mode=='async_task')
{
	if(!isAccess('sys_async_task','view'))	throw new AccessException();
	$task=request('task');
	if($task)
	{
		if(!isAccess('sys_async_task','exec'))	throw new AccessException();
		$sql_task=$db->real_escape_string($task);
		$res=$db->query("INSERT INTO `async_workers_tasks` (`task`, `needrun`, `textstatus`) VALUES ('$sql_task', 1, 'Запланировано')");
	}

	$tmpl->addContent("<h1>Ассинхронные задачи</h1>");
	$dir=$CONFIG['location'].'/common/async/';
	if (is_dir($dir))
	{
		if ($dh = opendir($dir))
		{
			$tmpl->addContent("<ul>");
			while (($file = readdir($dh)) !== false)
			{
				if( preg_match('/.php$/',$file) )
				{
					$cn=explode('.',$file);
					include_once("$dir/$file");
					$class_name=$cn[0]."Worker";
					$class=new $class_name(0);
					$nm=$class->getDescription();
					$tmpl->addContent("<li><a href='/user.php?mode=async_task&amp;task=$cn[0]'>Запланировать $cn[0] ($nm)</a></li>");

				}
			}
			closedir($dh);
			$tmpl->addContent("</ul>");
		}
	}

	$tmpl->addContent("<table class='list'><tr><th>ID</th><th>Задача</th><th>Ож.запуска</th><th>Состояние</th></tr>");
	$res=$db->query("SELECT `id`, `task`, `needrun`, `textstatus` FROM `async_workers_tasks` ORDER BY `id` DESC");
	while($nxt=$res->fetch_row())
	{
		$tmpl->addContent("<tr><td>$nxt[0]</td><td>$nxt[1]</td><td>$nxt[2]</td><td>".html_out($nxt[3])."</td></tr>");
	}
	$tmpl->addContent("</table>");
}
else if($mode=="denyip")
{
	if(!isAccess('sys_ip-blacklist','view'))	throw new AccessException();
	$tmpl->setContent("<h1>Заблокированные ресурсы</h1>
	<a href='?mode=iplog'>Часто посещаемые ресурсы</a>");

	$tmpl->addContent("<table class='list'><tr><th>ID</th><th>IP</th><th>host</th><th>Действия</th></tr>");
	$res=$db->query("SELECT * FROM `traffic_denyip`");
	while($nxt=$res->fetch_row())
	{
		$tmpl->addContent("<tr><td>$nxt[0]</td><td>$nxt[1]</td><td>$nxt[2]</td><td><a href='?mode=denyipd&n=$nxt[0]'>Удалить</a></td></tr>");
	}
	$tmpl->addContent("</table>
	<form method='post' action=''>
	<input type='hidden' name='mode' value='denyipa'>
	Добавить хост:<br>
	<input type='text' name='host'>
	<input type='submit' value='Добавить'>
	</form>");

}
else if($mode=='denyipa')
{
	if(!isAccess('sys_ip-blacklist','create'))	throw new AccessException();
	$host=request('host');
	$ipl=gethostbynamel($host);
	foreach($ipl as $ip)
	{
		$tmpl->addContent("У хоста $host адрес $ip, ");
		$sql_ip=$db->real_escape_string($ip);
		$sql_host=$db->real_escape_string($host);
		$res=$db->query("INSERT INTO `traffic_denyip` (`ip`,`host`) VALUES ('$sql_ip','$sql_host')");
		if($db->insert_id) $tmpl->addContent("и он добавлен в список!<br>");
		else $tmpl->addContent("и такой адрес уже есть в списке!<br>");
	}
}
else if($mode=="denyipd")
{
	if(!isAccess('sys_ip-blacklist','delete'))	throw new AccessException();
	$n=rcvint('n');
	$res=$db->query("DELETE FROM `traffic_denyip` WHERE `id`='$n'");
	$tmpl->msg("Готово!","ok");
}
else if($mode=='iplog')
{
	if(!isAccess('sys_ip-log','view'))	throw new AccessException();
	$tmpl->addContent("<h1>25 часто используемых адресов</h1>");
	$res=$db->query("SELECT `ip_daddr`, COUNT(`ip_daddr`) AS `cnt`, SUM(`ip_totlen`) AS `traf` FROM `ulog` GROUP BY `ip_daddr` ORDER BY `traf` DESC LIMIT 25");
	$tmpl->addContent("<table class='list'><tr><th>Адрес</th><th>Возможное имя сервера</th><th>Количество обращений</th><th>Трафик запросов</th><th>Заблокировать</th></tr>");
	while($nxt=$res->fetch_row())
	{
		$ip=long2ip($nxt[0]);
		$addr=gethostbyaddr($ip);

		$tmpl->addContent("<tr><td>$ip<td><a href='http://$addr'>$addr</a><td>$nxt[1]<td>$nxt[2]<td><a href='?mode=denyipa&host=$addr'>хост</a>, <a href='?mode=denyipa&host=$ip'>IP</a>");
	}
	$tmpl->addContent("</table>");
}
else if($mode=='psstat')
{
	if(!isAccess('sys_ps-stat','view'))	throw new AccessException();
	$tmpl->setTitle("Статистика переходов по поисковым запросам");
	$tmpl->setContent("<h1 id='page-title'>Статистика переходов по поисковым запросам</h1>");
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

	$tmpl->addContent("<form action='' method='post'>Статистика за 7 дней, по дату <input name='date' type='text' value='".date('Y-m-d', $data_post)."' maxlength='10'> (YYYY-MM-DD) <button type='submit'>Получить данные</button></form>");

	if(isset($_POST['date']))
	{
		$data_post_1 = $data_post - (24*60*60);
		$data_post_2 = $data_post_1 - (24*60*60);
		$data_post_3 = $data_post_2 - (24*60*60);
		$data_post_4 = $data_post_3 - (24*60*60);
		$data_post_5 = $data_post_4 - (24*60*60);
		$data_post_6 = $data_post_5 - (24*60*60);

		$tmpl->addContent("<table class='list' width='100%'>
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

		$counter_res = $db->query($counter_data);
		
		while ($counter_data_row = $counter_res->fetch_row())
		{
			$tmpl->addContent("<tr>
			<td>". html_out($counter_data_row[0]) ."</td>
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
		
		$tmpl->addContent("</table>");
	}
}
else throw new NotFoundException("Неверный запрос");

}
catch(mysqli_sql_exception $e) {
    $tmpl->ajax=0;
    $id = writeLogException($e);
    $tmpl->errorMessage("Порядковый номер ошибки: $id<br>Сообщение передано администратору", "Ошибка в базе данных");
}
catch(Exception $e) {
    writeLogException($e);
    $tmpl->errorMessage($e->getMessage());
}
$tmpl->write();

