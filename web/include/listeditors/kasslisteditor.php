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
namespace ListEditors;

class KassListEditor extends \ListEditor {
	
	public function __construct($db_link) {
		parent::__construct($db_link);
		$this->print_name = 'Справочник касс';
	}
	
	/// Получить массив с именами колонок списка
	public function getColumnNames() {
		return array(
		    'id'=>'id',
		    'name'=>'Наименование',
		    'firm_id'=>'Организация'
		);
	}
	
	/// Загрузить список всех элементов справочника
	public function loadList() {
		$res = $this->db_link->query("SELECT `num` AS `id`, `name`, `firm_id`
			FROM `doc_kassa`
			WHERE `ids`='kassa'
			ORDER BY `num`");
		$this->list = array();
		while ($line = $res->fetch_assoc()) {
			$this->list[$line['id']] = $line;
		}
	}

	public function getItem($id) {
		settype($id, 'int');
		$res = $this->db_link->query("SELECT `num` AS `id`, `name`, `bik`, `rs`, `ks`, `firm_id`
			FROM `doc_kassa`
			WHERE `ids`='kassa' AND `num`=$id");
		if ($res->num_rows) {
			return $res->fetch_assoc();
		} else {
			return false;
		}
	}
	
	public function getInputFirm_id($name, $value) {
		$res = $this->db_link->query("SELECT `id`, `name` FROM `firm_info` ORDER BY `id`");
		$ret = "<select name='$name'>";
		$ret .="<option value='0'>-- не задано --</option>";
		while($line = $res->fetch_assoc()) {
			$sel = $value==$line['id']?' selected':'';
			$ret .="<option value='{$line['id']}'{$sel}>{$line['id']}: ".html_out($line['name'])."</option>";
		}
		$ret .="</select>";
		return $ret;
	}

	public function saveItem($id, $data) {
		settype($id, 'int');
		$name_sql	= $this->db_link->real_escape_string($data['name']);
		$firm_id	= intval($data['firm_id']);
		if($id) {
			$this->db_link->query("UPDATE `doc_kassa` SET `name`='$name_sql', `firm_id`='$firm_id'
				WHERE `ids`='kassa' AND `num`=$id");
			return $id;
		}
		$res = $this->db_link->query("SELECT `num` FROM `doc_kassa` WHERE `ids`='kassa' ORDER BY `num` DESC LIMIT 1");
		if ($res->num_rows) {
			$line = $res->fetch_row();
			$id = $line[0] + 1;
		} else {
			$id = 1;
		}
		$this->db_link->query("INSERT INTO `doc_kassa` (`ids`, `num`, `name`, `firm_id`) ".
			"VALUES ('kassa', $id, '$name_sql', '$firm_id')");
		return $id;
	}	
}
