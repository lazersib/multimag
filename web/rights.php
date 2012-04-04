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

$actions=array('read'=>'Чтение', 'write'=>'Запись', 'save'=>'Сохранение', 'view'=>'Просмотр', 'edit'=>'Изменение', 'apply'=>'Проведение', 'cancel'=>'Отмена', 'forcecancel'=>'П.отмена','today_cancel'=>'Отмена сев.', 'create'=>'Создание', 'delete'=>'Удаление', 'exec'=>'Выполнение');

if($mode=='upl')
{
	$s=@$_GET['s'];
	$s=mysql_real_escape_string($s);
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

if(!isAccess('sys_acl','edit'))	throw new AccessException("Недостаточно привилегий");

$tmpl->SetText("<h1 id='page-title'>Настройка привилегий</h1>");
$tmpl->SetTitle("Настройка привилегий");
if($mode=='')
{
	$tmpl->AddText("<h3>Группы пользователей</h3>
	<table class='list'><tr><th>N<th>Название<th>Описание");
	$res=mysql_query("SELECT `id`,`name`,`comment` FROM `users_grouplist`");
	while($nxt=mysql_fetch_row($res))
	{
		$tmpl->AddText("<tr><td>$nxt[0]<a href='?mode=gre&amp;g=$nxt[0]'><img src='/img/i_edit.png' alt='Изменить'></a> <td><a href='?mode=group_acl&amp;g=$nxt[0]'>$nxt[1]</a><td>$nxt[2]");
	}
	$tmpl->AddText("</table><a href='?mode=gre'>Новая группа</a>");
}
else if($mode=='group_acl')
{
	$g=rcv('g');
	$tmpl->AddText("<h2>Привилегии группы</h2>");
	$res=mysql_query("SELECT `id`, `object`, `desc`, `actions`
	FROM `users_objects` ORDER BY `object`, `actions`");
	if(mysql_errno())	throw new MysqlException("Не удалось получить список объектов");
	$tmpl->AddText("<form action='' method='post'>
	<input type='hidden' name='mode' value='group_acl_save'>
	<input type='hidden' name='g' value='$g'>
	<table width='100%' class='list'><tr><th>Объект");

	$actions_show=array();
	$object_actions=array();
	$objects=array();

	while($nxt=mysql_fetch_array($res))
	{
		$objects[$nxt['object']]=$nxt['desc'];
		if($nxt['actions']!='')
		{
			$act_line=explode(',',$nxt['actions']);
			foreach($act_line as $action)
			{
				$object_actions[$nxt['object']][$action]='';
				$actions_show[$action]='1';
			}
		}
	}

	$res=mysql_query("SELECT `gid`, `object`, `action` FROM `users_groups_acl`
	WHERE `gid`='$g'");
	if(mysql_errno())	throw new MysqlException("Не удалось получить ACL группы");
	while($nxt=mysql_fetch_row($res))
	{
		$object_actions[$nxt[1]][$nxt[2]]="style='border: #0f0 1px solid;' checked";
	}
	foreach($actions_show as $action => $v)
	{
		$tmpl->AddText("<th>{$actions[$action]}");
	}
	$colspan=count($actions_show)+1;
	foreach($objects as $obj_name => $obj_desc)
	{
		$tmpl->AddText("<tr>");
		if(array_key_exists($obj_name, $object_actions))
		{
			$tmpl->AddText("<td>$obj_desc");
			foreach($actions_show as $action => $v)
			{
				$tmpl->AddText("<td>");
				if(array_key_exists($action, $object_actions[$obj_name]))
				{
					$tmpl->AddText("<label><input type='checkbox' name='c_{$obj_name}_{$action}' value='1' {$object_actions[$obj_name][$action]}>Разрешить</label>");
				}
			}
		}
		else
		{
			$tmpl->AddText("<th colspan='$colspan'>$obj_desc");
		}
	}
	$tmpl->AddText("</table>
	<button type='submit'>Сохранить</button>
	</form>
	<h2>Пользователи в группе</h2>
	<form action='' method='post'>
	<input type='hidden' name='mode' value='us'>
	<input type='hidden' name='g' value='$g'>
	<input type='hidden' name='us_id' value='-1' id='sid' >
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
	");

	$res=mysql_query("SELECT `users_in_group`.`uid`, `users`.`name`
	FROM `users_in_group`
	LEFT JOIN `users` ON `users_in_group`.`uid`=`users`.`id`
	WHERE `users_in_group`.`gid`='$g'");
	while($nxt=mysql_fetch_row($res))
	{
		$tmpl->AddText("<a href='?mode=ud&amp;us_id=$nxt[0]&amp;g=$g'><img src='/img/i_del.png' alt='Удалить'></a> - $nxt[1]<br>");
	}

}
else if($mode=='group_acl_save')
{
	$g=rcv('g');
	$tmpl->AddText("<h2>Группа $g: сохранение привилегий группы</h2>");
	if(!isAccess('sys_acl','edit'))	throw new AccessException("Недостаточно привилегий");


	$res=mysql_query("SELECT `id`, `object`, `desc`, `actions` FROM `users_objects`
	ORDER BY `object`, `actions`");
	if(mysql_errno())	throw new MysqlException("Не удалось получить список объектов");

	$actions_show=array();
	$object_actions=array();
	$objects=array();

	while($nxt=mysql_fetch_array($res))
	{
		$objects[$nxt['object']]=$nxt['desc'];
		if($nxt['actions']!='')
		{
			$act_line=explode(',',$nxt['actions']);
			foreach($act_line as $action)
			{
				$object_actions[$nxt['object']][$action]='';
				$actions_show[$action]='1';
			}
		}
	}

	mysql_query("DELETE FROM `users_groups_acl` WHERE `gid`='$g'");
	foreach($objects as $obj_name => $obj_desc)
	{
		if(array_key_exists($obj_name, $object_actions))
		{
			foreach($actions_show as $action => $v)
			{
				$var=rcv("c_{$obj_name}_{$action}");
				if($var)
					mysql_query("INSERT INTO `users_groups_acl` (`gid`, `object`, `action`)
					VALUES ('$g', '$obj_name', '$action')");
			}
		}
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
	if($us_id<0) $tmpl->msg("Пользователь не выбран!","err");
	else
	{
		$res=mysql_query("INSERT INTO `users_in_group` ( `uid`, `gid`) VALUES ('$us_id', '$g')");
		if(mysql_errno())	throw new MysqlException("Пользователь не добавлен");
		$tmpl->msg("Пользователь добавлен","ok");
	}
}
else if($mode=='ud')
{
	$g=rcv('g');
	$us_id=rcv('us_id');
	if($us_id<0) $tmpl->msg("Пользователь не выбран!");
	else
	{
		$res=mysql_query("DELETE FROM `users_in_group` WHERE `uid`='$us_id' AND `gid`='$g'");
		if($res) $tmpl->msg("Пользователь удалён","ok");
		else $tmpl->msg("Пользователь НЕ удалён","err");
	}
}

$tmpl->write();

?>