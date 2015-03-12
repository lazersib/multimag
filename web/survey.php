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

$ip = getenv("REMOTE_ADDR");
$colors = array('888', 'C40', '0C0', '00C', 'C90', 'C04', '80C', '08C', 'CF0');

try {
	$tmpl->SetTitle("Опросы");
        $mode = request('mode');
	if ($mode == '') {
		$tmpl->addContent("<h1>Активные опросы</h1>");
		$res = $db->query("SELECT `id`, `name`, `start_date`, `end_date` FROM `survey` WHERE `start_date`<=CURDATE() AND `end_date`>=CURDATE()");
		if ($res->num_rows) {
			$tmpl->addContent("<ul class='items'>");
			while ($line = $res->fetch_assoc()) {
				$tmpl->addContent("<li><a href='?mode=get&amp;s={$line['id']}'>{$line['name']}</a> (<a href='?mode=view&amp;s={$line['id']}'>Результаты</a>)<br><i>Действует c {$line['start_date']} по {$line['end_date']}</li>");
			}
			$tmpl->addContent("</ul>");
		}
		else	$tmpl->addContent("отсутствуют");
	}
	else if ($mode == 'get') {
		$survey_id = @$_REQUEST['s'];
		$question_num = @$_REQUEST['q'];
		settype($survey_id, 'int');
		settype($question_num, 'int');

		if (isset($_SESSION['uid']))
			$uid = intval($_SESSION['uid']);
		else	$uid = 'NULL';

		if (@$_SESSION['uid']) {
			$uid = intval($_SESSION['uid']);
			$where = "`uid`='$uid'";
		} else {
			$ip = $db->real_escape_string(getenv("REMOTE_ADDR"));
			$where = "`ip`='$ip'";
		}
		$res = $db->query("SELECT `id` FROM `survey_ok` WHERE $where");
		if ($res->num_rows)	$tmpl->msg("Вы уже участвовали в опросе. Повторное участие не тербуется.");
		else {
			$res = $db->query("SELECT `survey`.`id`, `survey`.`name`, `survey`.`start_date`, `survey`.`end_date`, `survey`.`start_text`, `survey`.`end_text`, (SELECT COUNT(`survey_question`.`id`) FROM `survey_question` WHERE `survey_id`=`survey`.`id` ) AS `q_cnt` FROM `survey` WHERE `start_date`<=CURDATE() AND `end_date`>=CURDATE() AND `id`='$survey_id'");
			if (!$res->num_rows)	throw new NotFoundException("Опрос не существует, ещё не начался или уже завершен");
			$survey = $res->fetch_assoc();
			$tmpl->addContent("<h1>{$survey['name']}</h1>");
			if ($question_num < 1) {
				if (!$survey['start_text'])
					$survey['start_text'] = 'Для начала опроса нажмите кнопку &quot;начать опрос&quot;';
				$tmpl->addContent("<form action='' method='post'>
			<input type='hidden' name='mode' value='get'>
			<input type='hidden' name='s' value='$survey_id'>
			<input type='hidden' name='q' value='1'>
			<p>{$survey['start_text']}</p>

			<button type='submit'>Начать опрос</button>");
			}
			else {
				if (isset($_REQUEST['vq'])) {
					$vq = $_REQUEST['vq'];
					settype($vq, 'int');
					$res = $db->query("SELECT `id`, `survey_id`, `text`, `type` FROM `survey_question` WHERE `question_num`='$vq' AND `survey_id`='$survey_id'");
					if ($question = $res->fetch_assoc()) {
						$answer_id = '';
						$answer_int = -1;
						$answer_txt = '';
						if (!$question['type'])
							$answer_int = intval(@$_REQUEST['or']);
						else {
							if (isset($_REQUEST['oc']))
								if (is_array($_REQUEST['oc'])) {
									foreach ($_REQUEST['oc'] AS $val) {
										if ($answer_txt)
											$answer_txt.=',';
										$answer_txt.=$val;
									}
								}
						}
						$comment = request('comment');
						$comment_sql = $db->real_escape_string($comment);
						$db->query("INSERT INTO `survey_answer` (`survey_id`, `question_num`, `answer_txt`, `answer_int`, `comment`, `uid`, `ip_address`)
						VALUES ($survey_id, $vq, '$answer_txt', '$answer_int', '$comment_sql', $uid, '$ip')");
					}
					else	throw new Exception('Вопрос не найден');
				}

				$res = $db->query("SELECT `id`, `survey_id`, `text`, `type` FROM `survey_question` WHERE `question_num`='$question_num' AND `survey_id`='$survey_id'");
				if ($question = $res->fetch_assoc()) {
					$nq = $question_num + 1;
					$tmpl->addContent("<div id='page-info'>Вопрос $question_num/{$survey['q_cnt']}</div>");
					$tmpl->addContent("<h4>{$question['text']}:</h4>
				<form action='' method='post'>
				<input type='hidden' name='mode' value='get'>
				<input type='hidden' name='vq' value='$question_num'>
				<input type='hidden' name='q' value='$nq'>");
					$res = $db->query("SELECT `option_num`, `text` FROM `survey_quest_option` WHERE `question_id`='{$question['id']}'");
					while ($nxt = $res->fetch_row()) {
						if (!$question['type'])		$tmpl->addContent("<label><input type='radio' name='or' value='$nxt[0]'>$nxt[1]</label><br>");
						else	$tmpl->addContent("<label><input type='checkbox' name='oc[]' value='$nxt[0]'>$nxt[1]</label><br>");
					}
					if (!$question['type'])	$tmpl->addContent("<label><input type='radio' name='or' value='0'>Затрудняюсь с ответом</label><br>");

					if ($question['type'])	$tmpl->addContent("<br>Выберите не более трёх вариантов.<br><br>");
					else			$tmpl->addContent("<br>Выберите один наиболее подходящий вариант.<br><br>");

					$tmpl->addContent("Ваш коментарий:<br><input type='text' name='comment'><br>
					<button type='submit'>Далее &gt;&gt;</button></form>");
				}
				else {
					$db->query("INSERT INTO `survey_ok` (`survey_id`, `uid`, `ip`, `result`) VALUES ('$survey_id', $uid, '$ip', '1')");
					if (!$survey['end_text'])
						$survey['end_text'] = 'Спасибо за участие в нашем опросе! Это поможет повысить удобство обслуживания наших клиентов.';
					$tmpl->msg($survey['end_text'] . "<br><a href='?mode=view&amp;s=$survey_id'>Смотреть результаты</a>", "ok");
				}
			}
		}
	}
	else if ($mode == 'view') {
		$survey_id = @$_REQUEST['s'];
		settype($survey_id, 'int');

		$res = $db->query("SELECT `survey`.`id`, `survey`.`name`, `survey`.`start_date`, `survey`.`end_date`, `survey`.`start_text`, `survey`.`end_text`, (SELECT COUNT(`survey_question`.`id`) FROM `survey_question` WHERE `survey_id`=`survey`.`id` ) AS `q_cnt` FROM `survey` WHERE `start_date`<=CURDATE() AND `end_date`>=CURDATE() AND `id`='$survey_id'");
		if (!$res->num_rows)	throw new NotFoundException("Опрос не существует, ещё не начался или уже завершен");
		$survey = $res->fetch_assoc();
		$tmpl->addContent("<h1>{$survey['name']} - результаты</h1>");
		$res = $db->query("SELECT `id`, `survey_id`, `question_num`, `text`, `type` FROM `survey_question` WHERE `survey_id`='$survey_id'");
		while ($question = $res->fetch_assoc()) {
			$ares = $db->query("SELECT `survey_answer`.`answer_int`, COUNT(`survey_answer`.`answer_int`), `survey_quest_option`.`text`
		FROM `survey_answer`
		LEFT JOIN `survey_quest_option` ON `survey_quest_option`.`option_num`=`survey_answer`.`answer_int` AND `survey_quest_option`.`question_id`='{$question['id']}' AND `survey_quest_option`.`survey_id`='$survey_id'
		WHERE `survey_answer`.`survey_id`='$survey_id' AND `survey_answer`.`question_num`='{$question['question_num']}' GROUP BY `survey_answer`.`answer_int`");
			$answs = array();
			$answs_t = array();
			$sum = 0;
			while ($answer = $ares->fetch_row()) {
				$answs[$answer[0]] = $answer[1];
				$answs_t[$answer[0]] = $answer[2];
				$sum+=$answer[1];
			}
			$answs_t[0] = "Затрудняюсь ответить";
			$tmpl->addContent("<tr><td>$answer[2]</td><td>$answer[1]</td></tr>");
			$tmpl->addContent("<h3>{$question['text']}</h3><table class='list' width='50%'>");
			foreach ($answs AS $id => $value) {
				$pp = intval($value / $sum * 100);
				$tmpl->addContent("<tr style='height: 35px'><td>{$answs_t[$id]}<br><div style='border: 1px solid #000000;background:#{$colors[$id]};margin:0;height: 5px;width:$pp%;'></td><td>$pp %</td></tr>");
			}
			$tmpl->addContent("</table>");
		}
	}
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

$tmpl->Write();
