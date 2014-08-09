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

class FirmListEditor extends \ListEditor {
	
	public function __construct() {
		$this->print_name = 'Справочник организаций';
		$this->table_name = 'doc_vars';
	}
	
	/// Получить массив с именами колонок списка
	public function getColumnNames() {
		return array(
		    'id'=>'id',
		    'firm_name'=>'Наименование',
		    'firm_inn' => 'ИНН',
		    'firm_adres' => 'Юридический адрес',
		    'firm_realadres' => 'Фактический адрес',
		    'firm_gruzootpr' => 'Данные грузоотправителя',
		    'firm_telefon' => 'Телефон',
		    'firm_okpo' => 'ОКПО',
		    'param_nds' => 'Ставка НДС',
		    
		    'firm_director'=>'ФИО директора',
		    'firm_manager' => 'ФИО менеджера',
		    'firm_buhgalter' => 'ФИО Бухгалтера',
		    
		    'firm_kladovshik' => 'ФИО Кладовщика',
		    'firm_kladovshik_id' => 'ID пользователя-кладовщика',
		    'firm_kladovshik_doljn' => 'Должность кладовщика'		    
		);
	}
		
	/// @brief Возвращает имя текущего элемента
	public function getItemName($item) {
		if(isset($item['firm_name']))
			return $item['firm_name'];
		else return '???';
	}
	
	public function getInputFirm_id($name, $value) {
		global $db;
		$res = $db->query("SELECT `id`, `name` FROM `firm_info` ORDER BY `id`");
		$ret = "<select name='$name'>";
		$ret .="<option value='0'>-- не задано --</option>";
		while($line = $res->fetch_assoc()) {
			$sel = $value==$line['id']?' selected':'';
			$ret .="<option value='{$line['id']}'{$sel}>{$line['id']}: ".html_out($line['name'])."</option>";
		}
		$ret .="</select>";
		return $ret;
	}	
};
