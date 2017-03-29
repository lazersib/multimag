<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2016, BlackLight, TND Team, http://tndproject.org
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

class nomTypesEditor extends \ListEditor {

    public function __construct($db_link) {
        parent::__construct($db_link);
        $this->print_name = 'Справочник типов номенклатуры';
        $this->table_name = 'doc_base_types';
    }

    /// Получить массив с именами колонок списка
    public function getColumnNames() {
        return array(
            'id' => 'id',
            'name' => 'Наименование',
            'account' => 'Номер бухгалтерского счёта',
            'service' => 'Является услугой (пока не используется)',
        );
    }
    
   
    public function getInputService($name, $value) {
        return $this->getCheckboxInput($name, 'Да', $value);
    }

    public function getFieldVisible($data) {
        return $data['service'] ? "<b style='color:#0c0'>Да</b>" : "<b style='color:#f00'>Нет</b>";
    }
    
    public function saveItem($id, $data) {
        if(!isset($data['service'])) {
            $data['service'] = 0;
        }
        return parent::saveItem($id, $data);        
    }
    
}
