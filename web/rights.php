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

require_once("core.php");

$actions=array('read'=>'Чтение', 'write'=>'Запись', 'save'=>'Сохранение', 'view'=>'Просмотр', 'edit'=>'Изменение', 'apply'=>'Проведение', 'today_apply'=>'Проведение сев.', 'cancel'=>'Отмена', 'forcecancel'=>'П.отмена','today_cancel'=>'Отмена сев.', 'create'=>'Создание', 'delete'=>'Удаление', 'exec'=>'Выполнение', 'redirect'=>'Перенаправление', 'printna'=>'Печать непроведённого');

try {

    if (!isAccess('sys_acl', 'view')) {
        throw new AccessException("Недостаточно привилегий");
    }

    $tmpl->setContent("<h1>Настройка привилегий</h1>");
    $tmpl->setTitle("Настройка привилегий");
    $mode = request('mode');
    if ($mode == '') {
        $tmpl->addContent("<h3>Группы пользователей</h3><table class='list'><tr><th>N</th><th>Название</th><th>Описание</th></tr>");
	$res=$db->query("SELECT `id`,`name`,`comment` FROM `users_grouplist`");
	while($nxt=$res->fetch_row())
	{
		$tmpl->addContent("<tr><td>$nxt[0]<a href='?mode=gre&amp;g=$nxt[0]'><img src='/img/i_edit.png' alt='Изменить'></a></td><td><a href='?mode=group_acl&amp;g=$nxt[0]'>$nxt[1]</a></td><td>$nxt[2]</td></tr>");
	}
	$tmpl->addContent("</table><a href='?mode=gre'>Новая группа</a>");
}
else if($mode=='group_acl')
{
	$g=rcvint('g');
	$tmpl->addContent("<h2>Привилегии группы</h2>");
	$res=$db->query("SELECT `id`, `object`, `desc`, `actions`
	FROM `users_objects` ORDER BY `object`, `actions`");
	$tmpl->addContent("<form action='' method='post'>
	<input type='hidden' name='mode' value='group_acl_save'>
	<input type='hidden' name='g' value='$g'>
	<table width='100%' class='list'><tr><th>Объект");

	$actions_show=array();
	$object_actions=array();
	$objects=array();

	while($nxt=$res->fetch_array())
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

	$res=$db->query("SELECT `gid`, `object`, `action` FROM `users_groups_acl`
	WHERE `gid`='$g'");
	while($nxt=$res->fetch_row())
	{
		$object_actions[$nxt[1]][$nxt[2]]="style='border: #0f0 1px solid;' checked";
	}
	foreach($actions_show as $action => $v)
	{
		$tmpl->addContent("<th>{$actions[$action]}");
	}
	$colspan=count($actions_show)+1;
	foreach($objects as $obj_name => $obj_desc)
	{
		$tmpl->addContent("<tr>");
		if(array_key_exists($obj_name, $object_actions))
		{
			$tmpl->addContent("<td>$obj_desc");
			foreach($actions_show as $action => $v)
			{
				$tmpl->addContent("<td>");
				if(array_key_exists($action, $object_actions[$obj_name]))
				{
					$tmpl->addContent("<label><input type='checkbox' name='c_{$obj_name}_{$action}' value='1' {$object_actions[$obj_name][$action]}>"
                                        . $action."</label>");
				}
			}
		}
		else	$tmpl->AddContent("<th colspan='$colspan'>$obj_desc");
	}
	$tmpl->addContent("</table>
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

	$res=$db->query("SELECT `users_in_group`.`uid`, `users`.`name`
	FROM `users_in_group`
	LEFT JOIN `users` ON `users_in_group`.`uid`=`users`.`id`
	WHERE `users_in_group`.`gid`='$g'");
	while($nxt=$res->fetch_row())
	{
		$tmpl->addContent("<a href='?mode=ud&amp;us_id=$nxt[0]&amp;g=$g'><img src='/img/i_del.png' alt='Удалить'></a> - $nxt[1]<br>");
	}

}
else if($mode=='group_acl_save')
{
	$g=rcvint('g');
	$tmpl->AddContent("<h2>Группа $g: сохранение привилегий группы</h2>");
	if(!isAccess('sys_acl','edit'))	throw new AccessException();

	$res=$db->query("SELECT `id`, `object`, `desc`, `actions` FROM `users_objects`
	ORDER BY `object`, `actions`");

	$actions_show=array();
	$object_actions=array();
	$objects=array();

	while($nxt=$res->fetch_array())
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

	$res=$db->query("DELETE FROM `users_groups_acl` WHERE `gid`='$g'");
	foreach($objects as $obj_name => $obj_desc)
	{
		if(array_key_exists($obj_name, $object_actions))
		{
			foreach($actions_show as $action => $v)
			{
				if( request("c_{$obj_name}_{$action}") )
				{
					$res=$db->query("INSERT INTO `users_groups_acl` (`gid`, `object`, `action`) VALUES ('$g', '$obj_name', '$action')");
				}
			}
		}
	}
}
else if($mode=='gre')
{
	$g=rcvint('g');
	$res=$db->query("SELECT `id`, `name`, `comment` FROM `users_grouplist` WHERE `id`='$g'");
	$nxt = $res->fetch_row();
	$tmpl->addContent("<h2>Редактирование группы</h2>
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
	if(!isAccess('sys_acl','edit'))	throw new AccessException();
	$g	= rcvint('g');
	$gn	= request('gn');
	$comm	= request('comm');
	$res = $db->query("SELECT `id` FROM `users_grouplist` WHERE `id`='$g'");
	if(($res->num_rows)&&($g))
	{
		$res=$db->query("UPDATE `users_grouplist` SET `name`='$gn', `comment`='$comm' WHERE `id`='$g'");
		$tmpl->msg("Группа обновлена","ok");
	}
	else
	{
		$res=$db->query("INSERT INTO `users_grouplist`	( `name`, `comment`) VALUES ('$gn', '$comm')");
		$tmpl->msg("Группа добавлена","ok");
	}
}
else if($mode=='us')
{
	if(!isAccess('sys_acl','edit'))	throw new AccessException();
	$g	= rcvint('g');
	$us_id	= rcvint('us_id');
	if($us_id<0) $tmpl->msg("Пользователь не выбран!","err");
	else
	{
		$res=$db->query("INSERT INTO `users_in_group` ( `uid`, `gid`) VALUES ('$us_id', '$g')");
		$tmpl->msg("Пользователь добавлен","ok");
	}
}
else if($mode=='ud')
{
	if(!isAccess('sys_acl','delete'))	throw new AccessException();
	$g	= rcvint('g');
	$us_id	= rcvint('us_id');
	if($us_id<0) $tmpl->msg("Пользователь не выбран!");
	else
	{
		$res=$db->query("DELETE FROM `users_in_group` WHERE `uid`='$us_id' AND `gid`='$g'");
		$tmpl->msg("Пользователь удалён","ok");
	}
}
else if($mode=='upl')
{
	$s=request('s');
	$s=$db->real_escape_string($s);
	$res=$db->query("SELECT `id`,`name`, `reg_email` FROM `users` WHERE `name` LIKE '%$s%'");
	while($nxt=$res->fetch_row())
	{
		echo"$nxt[1]|$nxt[0]|$nxt[2]\n";
	}
	exit();
}
else throw new NotFoundException();

}
catch(mysqli_sql_exception $e) {
    $tmpl->ajax=0;
    $id = writeLogException($e);
    $tmpl->errorMessage("Порядковый номер ошибки: $id<br>Сообщение передано администратору", "Ошибка в базе данных");
}
catch(Exception $e)
{
    writeLogException($e);
    $tmpl->errorMessage($e->getMessage());
}


$tmpl->write();

?>