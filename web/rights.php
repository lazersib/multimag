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

if($mode=='upl')
{
	$s=$_GET['s'];
	$s=mysql_escape_string($s);
	$res=mysql_query("SELECT `id`,`name`, `email` FROM `users` WHERE `name` LIKE '%$s%'");
	$i=0;
	$row=mysql_numrows($res);
	while($nxt=mysql_fetch_row($res))
	{
		$i=1;
		echo"$nxt[1]|$nxt[0]|$nxt[2]\n";
	}
	exit();
}

if(!isAccess('acl_control','edit'))	throw new AccessException("Недостаточно привилегий");

$tmpl->SetText("<h1 id='page-title'>Настройка привилегий</h1>");
$tmpl->SetTitle("Настройка привилегий");
if($mode=='')
{
	$tmpl->AddText("<h3>Группы пользователей</h3>
	<table><tr><th>N<th>Название<th>Описание");
	$res=mysql_query("SELECT `id`,`name`,`comment` FROM `users_grouplist`");
	while($nxt=mysql_fetch_row($res))
	{
		$tmpl->AddText("<tr><td>$nxt[0]<a href='?mode=gre&amp;g=$nxt[0]'><img src='/img/i_edit.png' alt='Изменить'></a> <td><a href='?mode=group&amp;g=$nxt[0]'>$nxt[1]</a><td>$nxt[2]");
	}
	$tmpl->AddText("</table><a href='?mode=gre'>Новая группа</a>");
}
else if($mode=='group')
{
	$g=rcv('g');
	$tmpl->AddText("<h2>Группа $g</h2> <h3>Привилегии группы</h3>");
	$actions=array('read'=>'Чтение', 'write'=>'Запись', 'save'=>'Сохранение', 'view'=>'Просмотр', 'edit'=>'Изменение', 'apply'=>'Проведение', 'cancel'=>'Отмена', 'create'=>'Создание', 'delete'=>'Удаление');
	
	$res=mysql_query("SELECT `id`, `object`, `desc`, `actions`
	FROM `users_objects`
	ORDER BY `object`, `actions`");
	if(mysql_errno())	throw new MysqlException("Не удалось получить ACL группы");
	$tmpl->AddText("<table><tr><th>ID<th>Объект<th>Действие");
	while($nxt=mysql_fetch_array($res))
	{
		if($nxt['actions']=='')	$tmpl->AddText("<tr><th colspan='3'>{$nxt['desc']}");
		else
		{
			$tmpl->AddText("<tr><td>{$nxt['id']}<td>{$nxt['desc']}<td>");
			$act_line=explode(',',$nxt['actions']);	
			foreach($act_line as $action)
			{
				if(!$actions[$action])	$actions[$action]=$action;
				$tmpl->AddText("<label><input type='checkbox' name='acl[{$nxt['object']}][$action]' value='1' >{$actions[$action]}</label><br>");
			}
		}
	}
	
	
	
	
	$tmpl->AddText("</table><a href='?mode=obje&amp;g=$g'>Добавить объект</a>");

	$tmpl->AddText("<h3>Пользователи в группе</h3><ul>");
	$res=mysql_query("SELECT `users_groups`.`id`, `users_groups`.`uid`, `users`.`name` FROM `users_groups`
	LEFT JOIN `users` ON `users`.`id` = `users_groups`.`uid`
	WHERE `gid`='$g'");
	while($nxt=mysql_fetch_row($res))
	{
		$tmpl->AddText("<li>$nxt[2] (uid:$nxt[1]) - <a href='?mode=ud&amp;g=$g&amp;us_id=$nxt[1]'><img src='/img/i_del.png'></a></li>");
	}
	$tmpl->AddText("</ul>
	<form action='' method=post>
	<h3>Добавить пользователя</h3>
	<input type='hidden' name='mode' value='us'>
	<input type='hidden' name='g' value='$g'>
	<input type='hidden' name='us_id' value='0' id='sid' >
	<script type='text/javascript' src='/css/jquery/jquery.js'></script>
	<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
	<input type='hidden' name='us_id' id='user_id' value='0'>
	<input type='text' id='user_nm'  style='width: 450px;' value=''><br>

	<script type=\"text/javascript\">
	$(document).ready(function(){
		$(\"#user_nm\").autocomplete(\"/rights.php\", {
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
			extraParams:{'mode':'upl'}
		});
	});
	
	function usliFormat (row, i, num) {
		var result = row[0] + \"<em class='qnt'>email: \" +
		row[2] + \"</em> \";
		return result;
	}
	
	
	function usselectItem(li) {
		if( li == null ) var sValue = \"Ничего не выбрано!\";
		if( !!li.extra ) var sValue = li.extra[0];
		else var sValue = li.selectValue;
		document.getElementById('user_id').value=sValue;
	}
	</script>
		
	<input type='submit' value='Записать'>
	</form>
	<br><br><br>
	");
}
else if($mode=='obje')
{
	$o=rcv('o');
	$g=rcv('g');
	$res=mysql_query("SELECT `id`, `object`, `a_read`, `a_write`, `a_edit`, `a_delete`
	FROM `users_grouprights` WHERE `object`='$o' AND `gid`='$g'");
	@$nxt=mysql_fetch_row($res);

	for($i=2;$i<6;$i++)	if($nxt[$i]) $nxt[$i]='checked';

	$tmpl->AddText("<h2>Объект $g</h2>
	<form action='' method='post'>
	<input type='hidden' name='mode' value='objs'>
	<input type='hidden' name='g' value='$g'>
	Имя объекта:<br>
	<input type='text' name='o' value='$nxt[1]'><br>
	Привилегии:<br>
	<label><input type='checkbox' name='a_read' value='1' $nxt[2]>Чтение</label><br>
	<label><input type='checkbox' name='a_write' value='1' $nxt[3]>Запись</label><br>
	<label><input type='checkbox' name='a_edit' value=1 $nxt[4]>Изменение</label><br>
	<label><input type='checkbox' name='a_delete' value=1 $nxt[5]>Удаление</label><br>
	<input type='submit' value='Записать'>
	</form>");
}
else if($mode=='objs')
{
	if(!isAccess('acl','read'))
	$o=rcv('o');
	$g=rcv('g');
	$a_read=rcv('a_read');
	$a_write=rcv('a_write');
	$a_edit=rcv('a_edit');
	$a_delete=rcv('a_delete');
	$res=mysql_query("SELECT `id`, `object`, `a_read`, `a_write`, `a_edit`, `a_delete`
	FROM `users_grouprights` WHERE `object`='$o' AND `gid`='$g'");
	if(mysql_num_rows($res))
	{
		$res=mysql_query("UPDATE `users_grouprights` SET `a_read`='$a_read', `a_write`='$a_write', `a_edit`='$a_edit', `a_delete`='$a_delete' WHERE `gid`='$g' AND `object`='$o'");
		if($res) $tmpl->msg("Привилегии обновлены","ok");
		else $tmpl->msg("Привилегии НЕ обновлены","err");
	}
	else
	{
		$res=mysql_query("INSERT INTO `users_grouprights`
		(`gid`, `object`, `a_read`, `a_write`, `a_edit`, `a_delete`) VALUES
		('$g', '$o', '$a_read', '$a_write', '$a_edit', '$a_delete' ) ");
		if($res) $tmpl->msg("Привилегии добавлены","ok");
		else $tmpl->msg("Привилегии НЕ добавлены","err");
	}
}
else if($mode=='gre')
{
	$g=rcv('g');
	$res=mysql_query("SELECT `id`, `name`, `comment` FROM `users_grouplist` WHERE `id`='$g'");
	$nxt=mysql_fetch_row($res);
	$tmpl->AddText("<h2>Редактирование группы</h2>
	<form action='' method=post>
	<input type=hidden name=mode value=grs>
	<input type=hidden name=g value='$nxt[0]'>
	Группа:<br>
	<input type=text name=gn value='$nxt[1]'><br>
	Комментарий:<br>
	<textarea name=comm class=e_msg rows='5' cols='30'>$nxt[2]</textarea><br>
	<input type=submit value='Записать'>
	</form>");
}
else if($mode=='grs')
{
	$g=rcv('g');
	$gn=rcv('gn');
	$comm=rcv('comm');
	$res=mysql_query("SELECT `id` FROM `users_grouplist` WHERE `id`='$g'");
	if((mysql_num_rows($res))&&($g))
	{
		$res=mysql_query("UPDATE `users_grouplist` SET `name`='$gn', `comment`='$comm' WHERE `id`='$g'");
		if($res) $tmpl->msg("Группа обновлена","ok");
		else $tmpl->msg("Группа НЕ обновлена","err");
	}
	else
	{
		$res=mysql_query("INSERT INTO `users_grouplist`
		( `name`, `comment`) VALUES ('$gn', '$comm')");
		if($res) $tmpl->msg("Группа добавлена","ok");
		else $tmpl->msg("Группа НЕ добавлена","err");
	}
}
else if($mode=='us')
{
	$g=rcv('g');
	$us_id=rcv('us_id');
	if($us_id==0) $tmpl->msg("Пользовательне выбран!");
	else
	{
	$res=mysql_query("INSERT INTO `users_groups`
	( `uid`, `gid`) VALUES ('$us_id', '$g')");
	if($res) $tmpl->msg("Пользователь добавлен","ok");
	else $tmpl->msg("Пользователь НЕ добавлен","err");
	}
}
else if($mode=='ud')
{
	$g=rcv('g');
	$us_id=rcv('us_id');
	if($us_id==0) $tmpl->msg("Пользователь не выбран!");
			else
	{
		$res=mysql_query("DELETE FROM `users_groups` WHERE `uid`='$us_id' AND `gid`='$g'");
		if($res) $tmpl->msg("Пользователь удалён","ok");
		else $tmpl->msg("Пользователь НЕ удалён","err");
	}
}

$tmpl->write();

?>