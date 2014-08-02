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

/// Документ *сборка изделия*
class doc_Sborka extends doc_Nulltype {

	function __construct($doc=0){
		parent::__construct($doc);
		$this->doc_type				= 17;
		$this->doc_name				= 'sborka';
		$this->doc_viewname			= 'Сборка изделия';
		$this->sklad_editor_enable		= true;
		$this->header_fields			= 'agent cena sklad';
		settype($this->doc,'int');
		$this->dop_menu_buttons			= "<a href='/doc_sc.php?mode=reopen&sn=sborka_zap&amp;doc=$doc&amp;' title='Передать в сценарий'><img src='img/i_launch.png' alt='users'></a>";
	}
	
	function initDefDopdata() {
		$this->def_dop_data = array('sklad'=>0, 'cena'=>1);
	}
	
	public function DocApply($silent = 0) {
		global $db;

		$pres = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`, `doc_sklady`.`dnc`
		FROM `doc_list`
		LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
		WHERE `doc_list`.`id`='{$this->doc}'");
		$doc_info = $pres->fetch_assoc();
		if (!$doc_info)
			throw new Exception("Документ {$this->doc} не найден!");
		if ($doc_info['ok'] && (!$silent))
			throw new Exception('Документ уже был проведён!');
		$pres->free();
		
		$res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt` AS `sklad_cnt`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`pos_type`, `doc_list_pos`.`id`, `doc_list_pos`.`page`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$doc_info['sklad']}'
		WHERE `doc_list_pos`.`doc`='{$this->doc}' AND `doc_base`.`pos_type`='0'");
		while ($doc_line = $res->fetch_array()) {
			$sign = $doc_line['page'] ? '-' : '+';
			if ($doc_line['page']) {
				if (!$doc_info['dnc'])
					if ($doc_line[1] > $doc_line[2])
						throw new Exception("Недостаточно ($doc_line[1]) товара '$doc_line[3]:$doc_line[4]($doc_line[0])': на складе только $doc_line[2] шт!");
				if (!$doc_info['dnc'] && (!$silent)) {
					$budet = getStoreCntOnDate($doc_line[0], $doc_info['sklad']);
					if ($budet < 0)
						throw new Exception("Невозможно ($silent), т.к. будет недостаточно ($budet) товара '$doc_line[3]:$doc_line[4]($doc_line[0])'!");
				}
			}

			$db->query("UPDATE `doc_base_cnt` SET `cnt`=`cnt` $sign '{$doc_line['cnt']}' WHERE `id`='{$doc_line['tovar']}' AND `sklad`='{$doc_info['sklad']}'");
			// Если это первое поступление
			if ($db->affected_rows == 0)
				$db->query("INSERT INTO `doc_base_cnt` (`id`, `sklad`, `cnt`) VALUES ('$doc_line[0]', '$doc_info[3]', '{$doc_line['cnt']}')");
		}
		if ($silent)	return;
		$db->update('doc_list', $this->doc, 'ok', time() );
		$this->sentZEvent('apply');
	}

	function DocCancel() {
		global $db;
		$pres = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`, `doc_sklady`.`dnc`
		FROM `doc_list`
		LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
		WHERE `doc_list`.`id`='{$this->doc}'");
		$nx = $pres->fetch_row();
		if (!$nx)	throw new Exception("Документ {$this->doc} не найден!");
		if (!$nx[4])	throw new Exception("Документ ещё не проведён!");

		$db->update('doc_list', $this->doc, 'ok', 0 );

		$res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`pos_type`, `doc_list_pos`.`page`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$nx[3]'
		WHERE `doc_list_pos`.`doc`='{$this->doc}'");
		while ($nxt = $res->fetch_row()) {
			if ($nxt[5] == 0) {
				$sign = $nxt[6] ? '+' : '-';
				$db->query("UPDATE `doc_base_cnt` SET `cnt`=`cnt` $sign '$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='$nx[3]'");
			}
		}
	}

	function Service() {
		global $tmpl, $db;
		$tmpl->ajax = 1;
		$opt = request('opt');
		$pos = request('pos');
		include_once('include/doc.zapposeditor.php');
		$poseditor = new SZapPosEditor($this);
		$poseditor->cost_id = $this->dop_data['cena'];
		$poseditor->sklad_id = $this->doc_data['sklad'];

		if (isAccess('doc_' . $this->doc_name, 'view')) {

			// Json-вариант списка товаров
			if ($opt == 'jget') {
				$doc_sum = $this->recalcSum();
				$str = "{ response: 'loadlist', content: [" . $poseditor->GetAllContent() . "], sum: '$doc_sum' }";
				$tmpl->addContent($str);
			}
			// Получение данных наименования
			else if ($opt == 'jgpi') {
				$pos = rcvint('pos');
				$tmpl->addContent($poseditor->GetPosInfo($pos));
			}
			// Json вариант добавления позиции
			else if ($opt == 'jadd') {
				if (!isAccess('doc_sborka', 'edit'))
					throw new AccessException("Недостаточно привилегий");
				$pos = rcvint('pos');
				$tmpl->setContent($poseditor->AddPos($pos));
			}
			// Json вариант удаления строки
			else if ($opt == 'jdel') {
				if (!isAccess('doc_sborka', 'edit'))
					throw new AccessException("Недостаточно привилегий");
				$line_id = rcvint('line_id');
				$tmpl->setContent($poseditor->Removeline($line_id));
			}
			// Json вариант обновления
			else if ($opt == 'jup') {
				if (!isAccess('doc_sborka', 'edit'))
					throw new AccessException("Недостаточно привилегий");
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
				$line_id = request('line');
				$data = request('data');
				$tmpl->setContent($poseditor->SerialNum($action, $line_id, $data));
			}
			else if($opt=='jdeldoc')
			{
				try
				{
					if(! isAccess('doc_'.$this->doc_name,'delete') )	throw new AccessException("Недостаточно привилегий");
					$tim=time();

					$res = $db->query("SELECT `id` FROM `doc_list` WHERE `p_doc`='{$this->doc}' AND `mark_del`='0'");
					if($res->num_rows)
						throw new Exception("Есть подчинённые не удалённые документы. Удаление невозможно.");
					$db->update('doc_list', $this->doc, 'mark_del', $tim);
					doc_log("MARKDELETE",  '', "doc", $this->doc);
					$this->doc_data['mark_del']=$tim;
					$json=' { "response": "1", "message": "Пометка на удаление установлена!", "buttons": "'.$this->getApplyButtons().'", "statusblock": "Документ помечен на удаление" }';
					$tmpl->setContent($json);
						
				}
				catch(Exception $e)
				{
					$tmpl->setContent("{response: 0, message: '".$e->getMessage()."'}");
				}
			}
			// Снять пометку на удаление
			else if($opt=='jundeldoc')
			{
				try
				{
					if(! isAccess('doc_'.$this->doc_name,'delete') )	throw new AccessException("Недостаточно привилегий");	
					$db->update('doc_list', $this->doc, 'mark_del', 0);
					doc_log("UNDELETE", '', "doc", $this->doc);
					$json=' { "response": "1", "message": "Пометка на удаление снята!", "buttons": "'.$this->getApplyButtons().'", "statusblock": "Документ не будет удалён" }';
					$tmpl->setContent($json);
				}
				catch(Exception $e)
				{
					$tmpl->setContent("{response: 0, message: '".$e->getMessage()."'}");
				}
			}
			else throw new NotFoundException();
		}
	}
};

?>