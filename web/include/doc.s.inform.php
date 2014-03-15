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

include_once("include/doc.s.nulltype.php");

class doc_s_Inform extends doc_s_Nulltype
{
	function Service() {
		global $tmpl, $db;
		$opt = request('opt');
		$pos = rcvint('pos');
		$doc = rcvint('doc');
		$tmpl->ajax = 1;
		if($opt == 'p_zak'){
			$res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_agent`.`name`
			FROM `doc_list_pos`
			INNER JOIN `doc_list` ON `doc_list`.`type`='11' AND `doc_list`.`ok`>'0' AND `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`id` NOT IN (SELECT DISTINCT `p_doc` FROM `doc_list` WHERE `ok` != '0' AND `type`='1' )
			LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
			WHERE `doc_list_pos`.`tovar`='$pos'");
			if($res->num_rows) {
				$tmpl->addContent("<table class='list' width='100%'><tr><th>N</th><th>Дата</th><th>Агент</th><th>Кол-во</th><th>Цена</th></tr>");
				while($nxt = $res->fetch_row()) {
					$dt = date('d.m.Y', $nxt[3]);
					$tmpl->addContent("<tr><td><a href='/doc.php?mode=body&amp;doc=$nxt[0]'>$nxt[1]$nxt[2]</a></td><td>$dt</td><td>"
						.html_out($nxt[6])."</td><td>$nxt[4]</td><td>$nxt[5]</td></tr>");
				}
				$tmpl->addContent("</table>");
			}
			else $tmpl->msg("Не найдено!");
		}
		else if($opt == 'vputi') {
			$res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_agent`.`name`, `doc_dopdata`.`value`
			FROM `doc_list_pos`
			INNER JOIN `doc_list` ON `doc_list`.`type`='12' AND `doc_list`.`ok`>'0' AND `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`id` NOT IN (SELECT DISTINCT `p_doc` FROM `doc_list` WHERE `ok` != '0' AND `type`='1' )
			LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
			LEFT JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list`.`id` AND `doc_dopdata`.`param`='dataprib'
			WHERE `doc_list_pos`.`tovar`='$pos'");
			if($res->num_rows) {
				$tmpl->addContent("<table width='290' class='list'>
				<tr><th>N</th><th>Дата док-та</th><th>Агент</th><th>Кол-во</th><th>Цена</th><th>Дата приб.</th></tr>");
				while($nxt = $res->fetch_row()) {
					$dt = date('d.m.Y', $nxt[3]);
					$tmpl->addContent("<tr><td><a href='/doc.php?mode=body&amp;doc=$nxt[0]'>$nxt[1]$nxt[2]</a></td><td>$dt</td><td>"
						.html_out($nxt[6])."</td><td>$nxt[4]</td><td>$nxt[5]</td><td>".html_out($nxt[7])."</td></tr>");
				}
				$tmpl->addContent("</table>");
			}
			else $tmpl->msg("Не найдено!");
		}
		else if($opt == 'rezerv') {
			$res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_agent`.`name`
			FROM `doc_list_pos`
			INNER JOIN `doc_list` ON `doc_list`.`type`='3' AND `doc_list`.`ok`>'0' AND `doc_list`.`id`!='$doc'
			AND `doc_list`.`id`=`doc_list_pos`.`doc` 
			AND `doc_list`.`id` NOT IN (SELECT DISTINCT `p_doc` FROM `doc_list` 
			INNER JOIN `doc_list_pos` ON `doc_list`.`id`=`doc_list_pos`.`doc`
			WHERE `ok` != '0' AND `type`='2' AND `doc_list_pos`.`tovar`='$pos' )
			LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
			WHERE `doc_list_pos`.`tovar`='$pos'");
			if($res->num_rows) {
				$tmpl->addContent("<table width='100%' class='list'>
				<tr><th>N</th><th>Дата</th><th>Агент</th><th>Кол-во</th><th>Цена</th></tr>");
				while($nxt = $res->fetch_row()) {
					$dt = date('d.m.Y', $nxt[3]);
					$tmpl->addContent("<tr><td><a href='/doc.php?mode=body&amp;doc=$nxt[0]'>$nxt[1]$nxt[2]</a></td><td>$dt</td><td>"
						.html_out($nxt[6])."</td><td>$nxt[4]</td><td>$nxt[5]</td></tr>");
				}
				$tmpl->addContent("</table>");
			}
			else $tmpl->msg("Не найдено!");
		}
		else if($opt=='dolgi') {
			$agent = rcvint('agent');
			$res = $db->query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `id`");
			while($nxt = $res->fetch_row())	{
				$dolg = agentCalcDebt($agent, 0, $nxt[0]);
				$tmpl->addContent("<div>Долг перед ".html_out($nxt[1]).": <b>$dolg</b> руб.</div>");
			}
		}
	}
};

?>