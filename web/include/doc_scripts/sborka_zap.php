<?php

//	MultiMag v0.1 - Complex sales system
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

include_once('include/doc.zapposeditor.php');

/// Сценарий автоматизации: сборка с перемещением и начислением заработной платы
class ds_sborka_zap {

	function Run($mode) {
		global $tmpl, $uid, $db;
		$tmpl->hideBlock('left');
		if ($mode == 'view') {
			$tmpl->addContent("<h1>" . $this->getname() . "</h1>
			<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
			<form action='' method='post' enctype='multipart/form-data'>
			<input type='hidden' name='mode' value='create'>
			<input type='hidden' name='param' value='i'>
			<input type='hidden' name='sn' value='sborka_zap'>
			Склад:<br><select name='sklad'>");
			$sres = $db->query("SELECT `id`,`name` FROM `doc_sklady` ORDER BY `id`");
			while ($nxt = $sres->fetch_row())
				$tmpl->addContent("<option value='$nxt[0]'>".html_out($nxt[1])."</option>");
			$tmpl->addContent("</select><br>Организация:<br><select name='firm'>");
			$rs = $db->query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `firm_name`");
			while ($nx = $rs->fetch_row())
				$tmpl->addContent("<option value='$nx[0]'>".html_out($nx[1])."</option>");
			$tmpl->addContent("</select><br>
			Агент:<br>
			<input type='hidden' name='agent' id='agent_id' value=''>
			<input type='text' id='agent_nm'  style='width: 450px;' value=''><br>
			Услуга начисления зарплаты:<br>
			<input type='hidden' name='tov_id' id='tov_id' value=''>
			<input type='text' id='tov'  style='width: 400px;' value=''><br>
			Переместить готовый товар на склад:<br>
			<select name='nasklad'>
			<option value='0' selected>--не требуется--</option>");
			$res = $db->query("SELECT `id`,`name` FROM `doc_sklady` ORDER BY `id`");
			while ($nxt = $res->fetch_row())
				$tmpl->addContent("<option value='$nxt[0]'>".html_out($nxt[1])."</option>");
			$tmpl->addContent("</select><br>
			<label><input type='checkbox' name='not_a_p' value='1'>Не проводить перемещение</label><br>

			<script type=\"text/javascript\">
			$(document).ready(function(){
				$(\"#agent_nm\").autocomplete(\"/docs.php\", {
					delay:300,
					minChars:1,
					matchSubset:1,
					autoFill:false,
					selectFirst:true,
					matchContains:1,
					cacheLength:10,
					maxItemsToShow:15,
					formatItem:agliFormat,
					onItemSelect:agselectItem,
					extraParams:{'l':'agent','mode':'srv','opt':'ac'}
				});
				$(\"#tov\").autocomplete(\"/docs.php\", {
				delay:300,
				minChars:1,
				matchSubset:1,
				autoFill:false,
				selectFirst:true,
				matchContains:1,
				cacheLength:10,
				maxItemsToShow:15,
				formatItem:tovliFormat,
				onItemSelect:tovselectItem,
				extraParams:{'l':'sklad','mode':'srv','opt':'ac'}
				});
			});

			function agliFormat (row, i, num) {
				var result = row[0] + \"<em class='qnt'>тел. \" +
				row[2] + \"</em> \";
				return result;
			}


			function agselectItem(li) {
				if( li == null ) var sValue = \"Ничего не выбрано!\";
				if( !!li.extra ) var sValue = li.extra[0];
				else var sValue = li.selectValue;
				document.getElementById('agent_id').value=sValue;
			}

			function tovliFormat (row, i, num) {
				var result = row[0] + \"<em class='qnt'>\" +
				row[2] + \"</em> \";
				return result;
			}

			function tovselectItem(li) {
				if( li == null ) var sValue = \"Ничего не выбрано!\";
				if( !!li.extra ) var sValue = li.extra[0];
				else var sValue = li.selectValue;
				document.getElementById('tov_id').value=sValue;

			}

			</script>


			<button type='submit'>Выполнить</button>
			</form>
			");
		} else if ($mode == 'create') {
			$tmpl->addContent("<h1>" . $this->getname() . "</h1>");
			$agent = rcvint('agent');
			$sklad = rcvint('sklad');
			$nasklad = rcvint('nasklad');
			$firm = rcvint('firm');
			$tov_id = rcvint('tov_id');
			$not_a_p = rcvint('not_a_p');
			$tim = time();
			$res = $db->query("INSERT INTO `doc_list` (`date`, `firm_id`, `type`, `user`, `altnum`, `subtype`, `sklad`, `agent`)
				VALUES	('$tim', '$firm', '17', '$uid', '0', 'auto', '$sklad', '$agent')");
			$doc = $db->insert_id;
			$db->query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)
				VALUES ('$doc','cena','1'), ('$doc','script_mark','ds_sborka_zap'), ('$doc','nasklad','$nasklad'), ('$doc','tov_id','$tov_id'),
				('$doc','not_a_p','$not_a_p')");
			header("Location: /doc_sc.php?mode=edit&sn=sborka_zap&doc=$doc&tov_id=$tov_id&agent=$agent&sklad=$sklad&firm=$firm&nasklad=$nasklad&not_a_p=$not_a_p");
		}
		else if ($mode == 'reopen') {
			$tmpl->addContent("<h1>" . $this->getname() . "</h1>");
			$doc = rcvint('doc');
			$dres = $db->query("SELECT `firm_id`, `sklad`, `agent`, `ok` FROM `doc_list` WHERE `id`='$doc'");
			if (!$nxt = $dres->fetch_assoc())	throw new Exception("Документ не найден");
			if ($nxt['ok'])				throw new Exception("Операция не допускается для проведённого документа");
			$agent = $nxt['agent'];
			$sklad = $nxt['sklad'];
			$firm = $nxt['firm'];
			$res = $db->query("SELECT `doc`,`param`,`value` FROM `doc_dopdata` WHERE `doc`='$doc'");
			$no_mark = true;
			while ($nxt = $res->fetch_row()) {
				if ($nxt[1] == 'script_mark' && $nxt[2] == 'ds_sborka_zap')
					$no_mark = false;
				else if ($nxt[1] == 'nasklad')
					$nasklad = $nxt[2];
				else if ($nxt[1] == 'tov_id')
					$tov_id = $nxt[2];
			}
			if ($no_mark)	throw new Exception("Этот документ создан вручную, а не через сценарий. Недостаточно информации для редактирования документа через сценарий.");

			$db->query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)	VALUES ('$doc','cena','1')");
			header("Location: /doc_sc.php?mode=edit&amp;sn=sborka_zap&amp;doc=$doc&amp;tov_id=$tov_id&amp;agent=$agent&amp;sklad=$sklad&amp;firm=$firm&amp;nasklad=$nasklad");
		}
		else if ($mode == 'edit') {
			$tov_id = rcvint('tov_id');
			$doc = rcvint('doc');
			$agent = rcvint('agent');
			$sklad = rcvint('sklad');
			$firm = rcvint('firm');
			$nasklad = rcvint('nasklad');
			$not_a_p = rcvint('not_a_p');
			$this->ReCalcPosCost($doc, $tov_id);
			$zp = $this->CalcZP($doc);
			$tmpl->addContent("<h1>" . $this->getname() . "</h1>
		Необходимо выбрать товары, которые будут скомплектованы. Устанавливать цену не требуется - при проведении документа она будет выставлена автоматически исходя из стоимости затраченных ресурсов. Для того, чтобы узнать цены - обновите страницу. После выполнения сценария выбранные товары будут оприходованы на склад, а соответствующее им количество ресурсов, использованных для сборки, будет списано. Попытка провести через этот сценарий товары, не содержащие ресурсов, вызовет ошибку. Если это указано в свойствах товара, от агента-сборщика будет оприходована выбранная услуга для последующей выдачи заработной платы (на данный момент в размере $zp руб.).<br>
		<a href='/doc_sc.php?mode=exec&amp;sn=sborka_zap&amp;doc=$doc&amp;tov_id=$tov_id&amp;agent=$agent&amp;sklad=$sklad&amp;firm=$firm&amp;nasklad=$nasklad&amp;not_a_p=$not_a_p'>Выполнить необходимые действия</a>
		<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>");

			$document = new doc_Sborka($doc);
			$poseditor = new SZapPosEditor($document);
			$dpd = $document->getDopDataA();
			$poseditor->cost_id = $dpd['cena'];
			$dd = $document->getDocDataA();
			$poseditor->SetEditable($dd['ok'] ? 0 : 1);
			$poseditor->sklad_id = $dd['sklad'];
			$tmpl->addContent($poseditor->Show());
		} else if ($mode == 'exec') {
			$doc = rcvint('doc');
			$tov_id = rcvint('tov_id');
			$agent = rcvint('agent');
			$sklad = rcvint('sklad');
			$firm = rcvint('firm');
			$nasklad = rcvint('nasklad');
			$not_a_p = rcvint('not_a_p');
			$db->startTransaction();
			$this->ReCalcPosCost($doc, $tov_id);
			$document = AutoDocument($doc);
			$document->DocApply();
			$zp = $this->CalcZP($doc);
			$tim = time();
			// Проверка, создано ли уже поступление зарплаты
			$res = $db->query("SELECT `id` FROM `doc_list` WHERE `type`='1' AND `p_doc`='$doc'");
			if ($res->num_rows) {
				list($post_doc) = $res->fetch_row();
				$db->query("UPDATE `doc_list_pos` SET `cost`='$zp' WHERE `doc`='$post_doc'");
			}
			else {
				$altnum = GetNextAltNum(1, 'auto', 0, 0, 1);
				$db->query("INSERT INTO `doc_list` (`date`, `firm_id`, `type`, `user`, `altnum`, `subtype`, `sklad`, `agent`, `p_doc`, `sum`)
			VALUES	('$tim', '$firm', '1', '$uid', '$altnum', 'auto', '$sklad', '$agent', '$doc', '$zp')");
				$post_doc = $db->insert_id;
				$db->query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `cost`) VALUES ('$post_doc', '$tov_id', '1', '$zp')");
				$document2 = AutoDocument($post_doc);
				$document2->DocApply();
			}

			$db->query("UPDATE `doc_list` SET `sum`='$zp' WHERE `id`='$post_doc'");

			// Проверка, создано ли уже перемещение
			$res = $db->query("SELECT `id` FROM `doc_list` WHERE `type`='8' AND `p_doc`='$doc'");
			if ($res->num_rows) {
				list($perem_doc_num) = $res->fetch_row();
				$r = $db->query("SELECT `value` FROM `doc_dopdata` WHERE `doc`='$perem_doc_num' AND `param`='na_sklad'");
				list($nasklad) = $r->fetch_row();
				$perem_doc = new doc_Peremeshenie($perem_doc_num);
			} else if (($sklad != $nasklad) && $nasklad) {
				$perem_doc = new doc_Peremeshenie();
				$perem_doc->createFrom($document);
				$perem_doc->setDopData('na_sklad', $nasklad);
				$perem_doc->setDopData('mest', 1);
			}

			if (($sklad != $nasklad) && $nasklad) {
				$docnum = $perem_doc->getDocNum();
				$res = $db->query("SELECT `tovar`, `cnt`, `cost` FROM `doc_list_pos` WHERE `doc`='$doc' AND `page`='0'");
				while ($nxt = $res->fetch_row()) {
					$db->query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `cost`, `page`)
					VALUES ('$docnum', '$nxt[0]', '$nxt[1]', '$nxt[2]', '0')");
				}
				
				if (!$not_a_p)	$perem_doc->DocApply();
			}
			$db->commit();
			$tmpl->ajax = 0;
			$tmpl->msg("Все операции выполнены успешно. Размер зарплаты: $zp");
		}
		else if ($mode == 'srv') {
			$opt = request('opt');
			$doc = rcvint('doc');
			$document = new doc_Sborka($doc);
			$poseditor = new SZapPosEditor($document);
			$dd = $document->getDopDataA();
			$poseditor->cost_id = $dd['cena'];
			$dd = $document->getDocDataA();
			$poseditor->sklad_id = $dd['sklad'];
			$tmpl->ajax = 1;
			$tmpl->setContent('');

			// Json-вариант списка товаров
			if ($opt == 'jget') {
				$doc_sum = $document->recalcSum();
				$str = "{ response: '2', content: [" . $poseditor->GetAllContent() . "], sum: '$doc_sum' }";
				$tmpl->addContent($str);
			}
			// Получение данных наименования
			else if ($opt == 'jgpi') {
				$pos = rcvint('pos');
				$tmpl->addContent($poseditor->GetPosInfo($pos));
			}
			// Json вариант добавления позиции
			else if ($opt == 'jadd') {
				if (!isAccess('doc_sborka', 'edit'))	throw new AccessException("Недостаточно привилегий");
				$pos = rcvint('pos');
				$tmpl->setContent($poseditor->AddPos($pos));
			}
			// Json вариант удаления строки
			else if ($opt == 'jdel') {
				if (!isAccess('doc_sborka', 'edit'))	throw new AccessException("Недостаточно привилегий");
				$line_id = rcvint('line_id');
				$tmpl->setContent($poseditor->Removeline($line_id));
			}
			// Json вариант обновления
			else if ($opt == 'jup') {
				if (!isAccess('doc_sborka', 'edit'))	throw new AccessException("Недостаточно привилегий");
				$line_id = rcvint('line_id');
				$value = request('value');
				$type = request('type');
				$tmpl->setContent($poseditor->UpdateLine($line_id, $type, $value));
			}
			// Получение номенклатуры выбранной группы
			else if ($opt == 'jsklad') {
				$group_id = rcvint('group_id');
				$str = "{ response: 'sklad_list', group: '$group_id',  content: [" . $poseditor->GetSkladList($group_id) . "] }";
				$tmpl->setContent($str);
			}
			// Поиск по подстроке по складу
			else if ($opt == 'jsklads') {
				$s = request('s');
				$str = "{ response: 'sklad_list', content: [" . $poseditor->SearchSkladList($s) . "] }";
				$tmpl->setContent($str);
			} else if ($opt == 'jsn') {
				$action = request('a');
				$line_id = rcvint('line');
				$data = request('data');
				$tmpl->setContent($poseditor->SerialNum($action, $line_id, $data));
			}
		}
	}

	function ReCalcPosCost($doc, $tov_id) {
		global $db;
		$db->query("DELETE FROM `doc_list_pos`	WHERE `doc`='$doc' AND `page`!='0'");
		$res = $db->query("SELECT `id`, `tovar`, `cnt` FROM `doc_list_pos`
		WHERE `doc`='$doc' AND `page`='0'");
		while ($nxt = $res->fetch_row()) {
			$cost = 0;
			$rs = $db->query("SELECT `doc_base_kompl`.`kompl_id`, `doc_base_kompl`.`cnt`, `doc_base`.`cost` FROM `doc_base_kompl`
			LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_base_kompl`.`kompl_id`
			WHERE `doc_base_kompl`.`pos_id`='$nxt[1]'");
			if ($rs->num_rows == 0)		throw new Exception("У товара $nxt[1] не заданы комплектующие");
			while ($nx = $rs->fetch_row()) {
				$acp = getInCost($nx[0], 0, true);
				if ($acp > 0)	$cost+=$nx[1] * $acp;
				else		$cost+=$nx[1] * $nx[2];
				$cntc = $nxt[2] * $nx[1];
				if ($acp > 0)	
					$db->query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `cost`, `page`)
						VALUES ('$doc', '$nx[0]', '$cntc', '$acp', '$nxt[1]')");
				else	$db->query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `cost`, `page`)
						VALUES ('$doc', '$nx[0]', '$cntc', '$nx[2]', '$nxt[1]')");
			}

			// Расчитываем зарплату
			$r = $db->query("SELECT `doc_base_params`.`id`, `doc_base_values`.`value` FROM `doc_base_params`
		LEFT JOIN `doc_base_values` ON `doc_base_values`.`param_id`=`doc_base_params`.`id` AND `doc_base_values`.`id`='$nxt[1]'
		WHERE `doc_base_params`.`param`='ZP'");
			if ($r->num_rows) {
				list($a, $zp) = $r->fetch_row();
				$db->query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `cost`, `page`)
					VALUES ('$doc', '$tov_id', '$nxt[2]', '$zp', '$nxt[1]')");
				$cost+=$zp;
			}
			else	$zp = 0;
			$db->query("UPDATE `doc_list_pos` SET `cost`='$cost' WHERE `id`='$nxt[0]'");
		}
		DocSumUpdate($doc);
	}

	function CalcZP($doc) {
		global $db;
		$zp = 0;
		$res = $db->query("SELECT `id`, `tovar`, `cnt` FROM `doc_list_pos`
			WHERE `doc`='$doc' AND `page`='0'");
		while ($nxt = $res->fetch_row()) {
			$rs = $db->query("SELECT `doc_base_values`.`value` FROM `doc_base_params`
			LEFT JOIN `doc_base_values` ON `doc_base_values`.`param_id`=`doc_base_params`.`id` AND `doc_base_values`.`id`='$nxt[1]'
			WHERE `doc_base_params`.`param`='ZP'");
			if (! $rs->num_rows)	continue;
			$n = $rs->fetch_row();
			$zp+=$nxt[2] * $n[0];
		}
		return $zp;
	}

	function getName() {
		return "Сборка с выдачей заработной платы";
	}
}
?>