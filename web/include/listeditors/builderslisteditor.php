<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2018, BlackLight, TND Team, http://tndproject.org
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

class BuildersListEditor extends \ListEditor {
	var $store_id = 0;	//< ID фильтрации склада сброщиков. Допустимо нулевое значение 
	
	public function __construct($db_link) {
		parent::__construct($db_link);
		$this->print_name = 'Справочник сборщиков';
		$this->table_name = 'factory_builders';
	}
	
	/// Получить массив с именами колонок списка
	public function getColumnNames() {
		return array(
		    'id'=>'id',
		    'name'=>'Имя',
		    'store_id'=>'Склад / цех',
		    'active' => 'Активен'
		);
	}
	
	/// Загрузить список всех элементов справочника
	public function loadList() {
		settype($this->store_id, 'int');
		$where = $this->store_id?" WHERE `store_id`={$this->store_id} ":'';
		$res = $this->db_link->query("SELECT `id`, `name`, `store_id`, `active` FROM `{$this->table_name}` $where ORDER BY `id`");
		$this->list = array();
		while ($line = $res->fetch_assoc()) {
			$this->list[$line['id']] = $line;
		}
	}
	
	public function getInputStore_id($name, $value) {
		settype($this->store_id, 'int');
		if (!$value && $this->store_id) {
			$value = $this->store_id;
		}
		$res = $this->db_link->query("SELECT `id`, `name` FROM `doc_sklady` ORDER BY `id`");
		$ret = "<select name='$name'>";
		$sel = $value==0?' selected':'';
		$ret .="<option disabled value='0'{$sel}>-- не задан --</option>";
		while($line = $res->fetch_assoc()) {
			$sel = $value==$line['id']?' selected':'';
			$ret .="<option value='{$line['id']}'{$sel}>{$line['id']}: ".html_out($line['name'])."</option>";
		}
		$ret .="</select>";
		return $ret;
	}	
	
	public function getInputActive($name, $value) {
		return $this->getCheckboxInput($name, 'Да', $value);
	}
}
