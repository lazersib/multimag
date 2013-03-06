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

require_once("core.php");

$ip=getenv("REMOTE_ADDR");

try
{
$tmpl->SetTitle("Опросы");
if($mode=='')
{
	$tmpl->AddText("<h1>Активные опросы</h1>");
	$res=mysql_query("SELECT `id`, `name`, `start_date`, `end_date` FROM `survey` WHERE `start_date`<=CURDATE() AND `end_date`>=CURDATE()");
	if(!$res)	throw new MysqlException("Не удалось выбрать активные опросы");
	if(mysql_num_rows($res))
	{
		$tmpl->AddText("<ul class='items'>");
		while($line=mysql_fetch_assoc($res))
		{
			$tmpl->AddText("<li><a href='?mode=get&amp;s={$line['id']}'>{$line['name']}</a><br><i>Действует c {$line['start_date']} по {$line['end_date']}</li>");
		}
		$tmpl->AddText("</ul>");
	}
	else $tmpl->AddText("отсутствуют");
}
else if($mode=='get')
{
	$survey_id = @$_REQUEST['s'];
	$question_num = @$_REQUEST['q'];
	settype($survey_id, 'int');
	settype($question_num, 'int');

	if(isset($_SESSION['uid']))	$uid=intval($_SESSION['uid']);
	else	$uid='NULL';


	$res=mysql_query("SELECT `survey`.`id`, `survey`.`name`, `survey`.`start_date`, `survey`.`end_date`, `survey`.`start_text`, `survey`.`end_text`, (SELECT COUNT(`survey_question`.`id`) FROM `survey_question` WHERE `survey_id`=`survey`.`id` ) AS `q_cnt` FROM `survey` WHERE `start_date`<=CURDATE() AND `end_date`>=CURDATE() AND `id`='$survey_id'");
	if(!$res)	throw new MysqlException("Не удалось выбрать опрос");
	if(!mysql_num_rows($res))	throw new NotFoundException("Опрос не существует, ещё не начался или уже завершен");
	$survey=mysql_fetch_assoc($res);
	$tmpl->AddText("<h1>{$survey['name']}</h1>");
	if($question_num<1)
	{
		if(!$survey['start_text'])	$survey['start_text']='Для начала опроса нажмите кнопку &quot;начать опрос&quot;';
		$tmpl->AddText("<form action='' method='post'>
		<input type='hidden' name='mode' value='get'>
		<input type='hidden' name='s' value='$survey_id'>
		<input type='hidden' name='q' value='1'>
		<p>{$survey['start_text']}</p>

		<button type='submit'>Начать опрос</button>");
	}
	else
	{
		if(isset($_REQUEST['vq']))
		{
			$vq=$_REQUEST['vq'];
			settype($vq,'int');
			$res=mysql_query("SELECT `id`, `survey_id`, `text`, `type` FROM `survey_question` WHERE `question_num`='$vq' AND `survey_id`='$survey_id'");
			if(!$res)	throw new MysqlException("Не удалось выбрать вопрос");
			if($question=mysql_fetch_assoc($res))
			{
				$answer_id='';
				$answer_int=-1;
				$answer_txt='';
				if(!$question['type'])	$answer_int=intval(@$_REQUEST['or']);
				else
				{
					if(isset($_REQUEST['oc']))
					if(is_array($_REQUEST['oc']))
					{
						foreach($_REQUEST['oc'] AS $val)
						{
							if($answer_txt)	$answer_txt.=',';
							$answer_txt.=$val;
						}
					}
				}
				$comment=rcv('comment');
				mysql_query("INSERT INTO `survey_answer` (`survey_id`, `question_num`, `answer_txt`, `answer_int`, `comment`, `uid`, `ip_address`)
				VALUES ($survey_id, $vq, '$answer_txt', '$answer_int', '$comment', $uid, '$ip')");
				if(mysql_errno())	throw new MysqlException("Не удалось сохранить ответ");
			}
			else		throw new Exception('Вопрос не найден');
		}

		$res=mysql_query("SELECT `id`, `survey_id`, `text`, `type` FROM `survey_question` WHERE `question_num`='$question_num' AND `survey_id`='$survey_id'");
		if(!$res)	throw new MysqlException("Не удалось выбрать вопрос");
		if($question=mysql_fetch_assoc($res))
		{
			$nq=$question_num+1;
			$tmpl->AddText("<div id='page-info'>Вопрос $question_num/{$survey['q_cnt']}</div>");
			$tmpl->AddText("<h4>{$question['text']}:</h4>
			<form action='' method='post'>
			<input type='hidden' name='mode' value='get'>
			<input type='hidden' name='vq' value='$question_num'>
			<input type='hidden' name='q' value='$nq'>");
			$res=mysql_query("SELECT `option_num`, `text` FROM `survey_quest_option` WHERE `question_id`='{$question['id']}'");
			if(!$res)	throw new MysqlException("Не удалось выбрать варианты ответов");

			while($nxt=mysql_fetch_row($res))
			{
				if(!$question['type'])	$tmpl->AddText("<label><input type='radio' name='or' value='$nxt[0]'>$nxt[1]</label><br>");
				else			$tmpl->AddText("<label><input type='checkbox' name='oc[]' value='$nxt[0]'>$nxt[1]</label><br>");
			}
			if(!$question['type'])	$tmpl->AddText("<label><input type='radio' name='or' value='0'>Затрудняюсь с ответом</label><br>");

			if($question['type'])	$tmpl->AddText("<br>Выберите не более трёх вариантов.<br><br>");
			else			$tmpl->AddText("<br>Выберите один наиболее подходящий вариант.<br><br>");

			$tmpl->AddText("Ваш коментарий:<br><input type='text' name='comment'><br>
			<button type='submit'>Далее &gt;&gt;</button>
			</form>");
		}
		else
		{
			mysql_query("INSERT INTO `survey_ok` (`survey_id`, `uid`, `ip`, `result`) VALUES ('$survey_id', $uid, '$ip', '1')");
			$tmpl->msg("Спасибо за участие в нашем опросе! Это поможет повысить удобство обслуживания наших клиентов.");
		}

	}

}

}
catch(MysqlException $e)
{
	header('HTTP/1.0 500 Internal error');
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

$tmpl->Write();
?>


