<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2014, BlackLight, TND Team, http://tndproject.org
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

if ($mode == '') {
	$tmpl->addContent("<h1>Статистика опросов</h1>");
	$res = $db->query("SELECT `id`, `text` FROM `questions` ORDER BY `id`");
	while ($nxt = $res->fetch_row()) {
		$tmpl->addContent("<h3>$nxt[0]. $nxt[1]</h3>");
		$arr = array();
		$max = 0;

		$r = $db->query("SELECT `id` FROM `question_answ` WHERE `q_id`='$nxt[0]' AND `answer` LIKE '%|0|%'");
		$cnt = $r->num_rows;
		if ($cnt) {
			$arr[0]['name'] = 'Затрудняюсь с ответом';
			$arr[0]['cnt'] = $cnt;
			$max+=$cnt;
		}

		$rs = $db->query("SELECT `question_vars`.`var_id`, `question_vars`.`text` FROM `question_vars` WHERE `q_id`='$nxt[0]'");
		while ($nx = $rs->fetch_row()) {
			$r = $db->query("SELECT `id` FROM `question_answ` WHERE `q_id`='$nxt[0]' AND `answer` LIKE '%|$nx[0]|%'");
			$cnt = $r->num_rows;
			$arr[$nx[0]]['name'] = $nx[1];
			$arr[$nx[0]]['cnt'] = $cnt;
			$max+=$cnt;
		}

		foreach ($arr as $line) {
			$pp = $line['cnt'] * 100 / $max;
			if ($pp) {
				$pp = sprintf("%0.2f", $pp);
				$tmpl->addContent("{$line['name']} - $pp%<br>");
			}
		}
		$tmpl->addContent("<br>Всего ответов: $max");
	}
}


$tmpl->Write();
?>