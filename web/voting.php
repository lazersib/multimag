<?php
//	MultiMag v0.2 - Complex sales system
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

require_once("core.php");

$colors=array('000', 'C40', '0C0', '00C', 'C90', 'C04', '80C', '08C', 'CF0');

try
{

$tmpl->SetTitle("Голосования");
if(@$_REQUEST['mode']=='')
{
	$tmpl->AddText("<h1>Голосования</h1>");
	$res=mysql_query("SELECT `id`, `name`, `end_date` FROM `votings`
	WHERE `start_date`<=NOW() AND `end_date`>=NOW()");
	if(!$res)	throw new MysqlException("Не удалось получить список активных голосований");
	if(mysql_num_rows($res))
	{
		$tmpl->AddText("<h2>Активные голосования</h2><ul class='items'>");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<li><a href='/voting.php?mode=vv&amp;vote_id=$nxt[0]'>$nxt[1]</a> - <i><b>Закончится:</b> $nxt[2]</i></li>");
		}
		$tmpl->AddText("</ul>");
	}
	else $tmpl->AddText("<h2>Активные голосования на данный момент отсутствуют!</h2>");

	$res=mysql_query("SELECT `id`, `name`, `end_date` FROM `votings`
	WHERE `end_date`<=NOW()");
	if(!$res)	throw new MysqlException("Не удалось получить список активных голосований");
	if(mysql_num_rows($res))
	{
		$tmpl->AddText("<h2>Прошедшие голосования</h2><ul class='items'>");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<li><a href='/voting.php?mode=vv&amp;vote_id=$nxt[0]'>$nxt[1]</a> - <i><b>Закончилось:</b> $nxt[2]</i></li>");
		}
		$tmpl->AddText("</ul>");
	}
}
else if(@$_REQUEST['mode']=='vv')
{
	$vote_id=@$_REQUEST['vote_id'];
	settype($vote_id,'int');
	$res=mysql_query("SELECT `id`, `name`, `start_date`, `end_date` FROM `votings`
	WHERE `id`='$vote_id'");
	if(!$res)	throw new MysqlException("Не удалось получить данные голосований");
	if(!mysql_num_rows($res))	throw new Exception("Голосование не найдено");
	$vote_data=mysql_fetch_assoc($res);
	$tmpl->SetTitle("{$vote_data['name']} - голосование");
	$tmpl->AddText("<h1>Голосование: {$vote_data['name']}</h1><div id='page-info'>Проходит с {$vote_data['start_date']} по {$vote_data['end_date']}</div>");

	if(isset($_REQUEST['opt']))
	{
		/// TODO: проверка диапазона дат!
		$variant=@$_REQUEST['variant'];
		settype($variant,'int');
		if(time() < strtotime($vote_data['start_date']))		$tmpl->msg("Голосование ещё не началось",'err');
		else if(time() > strtotime($vote_data['end_date'].' 23:59:59'))	$tmpl->msg("Голосование уже закончилось",'err');
		else if($variant<1)	$tmpl->msg("Вы не выбрани вариант ответа",'err');
		else
		{
			$res=mysql_query("SELECT `variant_id`, `text` FROM `votings_vars` WHERE `voting_id`='$vote_id' AND `variant_id`='$variant'");
			if(!$res)	throw new MysqlException("Не удалось получить варианты голосования");
			if(!mysql_num_rows($res))	throw new Exception("Вариант не найден!");
			$ip=mysql_real_escape_string(getenv("REMOTE_ADDR"));
			if(@$_SESSION['uid'])	$uid=intval($_SESSION['uid']);
			else			$uid='NULL';
			mysql_query("INSERT INTO `votings_results` (`voting_id`, `variant_id`, `user_id`, `ip_addr`) VALUES ($vote_id, $variant, $uid, '$ip')");
			if(mysql_errno())	$tmpl->msg("Не удалось записать ваш голос. Возможно, вы уже голосовали.",'err');
		}
	}

	if(@$_SESSION['uid'])
	{
		$uid=intval($_SESSION['uid']);
		$where="`votings_results`.`user_id`='$uid'";
	}
	else
	{
		$ip=mysql_real_escape_string(getenv("REMOTE_ADDR"));
		$where="`votings_results`.`ip_addr`='$ip'";
	}
	$res=mysql_query("SELECT `id` FROM `votings_results` WHERE $where");
	if(!$res)	throw new MysqlException("Не удалось получить результаты голосований");
	if(!mysql_num_rows($res) && (time() < strtotime($vote_data['end_date'].' 23:59:59')) && (time() > strtotime($vote_data['start_date'])) )	//Выводим форму голосования
	{
		$tmpl->AddText("<form action='/voting.php' method='post'>
		<input type='hidden' name='mode' value='vv'>
		<input type='hidden' name='opt' value='ok'>
		<input type='hidden' name='vote_id' value='$vote_id'>");
		$res=mysql_query("SELECT `variant_id`, `text` FROM `votings_vars` WHERE `voting_id`='$vote_id'");
		if(!$res)	throw new MysqlException("Не удалось получить варианты голосования");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<label><input type='radio' name='variant' value='$nxt[0]'>$nxt[1]</label><br>");
		}
		$tmpl->AddText("<button type='submit'>Голосовать</button></form>");
	}
	else //Выводим результаты
	{
		$tmpl->AddText("<table class='list'>");
		$res=mysql_query("SELECT `votings_vars`.`variant_id`, `votings_vars`.`text`,
		( SELECT COUNT(`votings_results`.`id`) FROM `votings_results` WHERE `votings_results`.`voting_id`='$vote_id' AND `votings_results`.`variant_id`=`votings_vars`.`variant_id`) AS `rate`,
		( SELECT COUNT(`votings_results`.`id`) FROM `votings_results` WHERE `votings_results`.`voting_id`='$vote_id') AS `all`
		FROM `votings_vars` WHERE `voting_id`='$vote_id'");
		if(!$res)	throw new MysqlException("Не удалось получить варианты голосования");
		while($nxt=mysql_fetch_row($res))
		{
			$pp=intval($nxt[2]/$nxt[3]*100);
			$tmpl->AddText("<tr style='height: 35px'><td>$nxt[1]<br><div style='border: 1px solid #000000;background:#{$colors[$nxt[0]]};margin:0;height: 5px;width:$pp%;'></td><td>&nbsp;</td><td>$pp %</td></tr>");
		}
		$tmpl->AddText("</table>");
	}
}

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