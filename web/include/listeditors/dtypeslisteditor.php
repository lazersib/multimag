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

class DTypesListEditor extends \ListEditor {

    public function __construct($db_link) {
        parent::__construct($db_link);
        $this->print_name = 'Справочник видов расходов';
        $this->table_name = 'doc_dtypes';
    }

    /// Получить массив с именами колонок списка
    public function getColumnNames() {
        return array(
            'id' => 'id',
            'account' => 'Счет',
            'name' => 'Наименование',
            'codename' => 'Кодовое обозначение',
            'adm' => 'Административный',
            'r_flag' => 'Под отчёт',
        );
    }

    public function getInputAdm($name, $value) {
        return $this->getCheckboxInput($name, 'Да', $value);
    }

    public function getInputR_flag($name, $value) {
        return $this->getCheckboxInput($name, 'Да', $value);
    }
    
    /// Записать в базу строку справочника
    public function saveItem($id, $data) {
        if($data['codename']=='') {
            $data['codename'] = null;
        }
        if($data['adm']==null) {
            $data['adm'] = 0;
        }
        if($data['r_flag']==null) {
            $data['r_flag'] = 0;
        }
        return parent::saveItem($id, $data);
    }

}
