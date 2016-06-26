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

class FirmListEditor extends \ListEditor {
    
    protected $types = array(''=>'--???--', 'ip'=>'ИП', 'ooo'=>'ООО', 'pao'=>'ПАО', 'fl'=>'Физ.лицо','alt'=>'Другое');

    public function __construct($db_link) {
        parent::__construct($db_link);
        $this->print_name = 'Справочник организаций';
        $this->table_name = 'doc_vars';
    }

    /// Получить массив с именами колонок списка
    public function getColumnNames() {
        return array(
            'id' => 'id',
            'firm_type' => 'Вид',
            'firm_name' => 'Наименование',
            'firm_inn' => 'ИНН',
            'firm_regnum' => 'Регистрационный номер',
            'firm_regdate' => 'Дата регистрации',
            'firm_adres' => 'Юридический адрес',
            'firm_realadres' => 'Фактический адрес',
            'firm_gruzootpr' => 'Данные грузоотправителя',
            'firm_telefon' => 'Телефон',
            'firm_okpo' => 'ОКПО',
            'param_nds' => 'Ставка НДС',
            'pricecoeff' => 'Ценовой коэффициент',
            'no_retailprices' => 'Не использовать розничные цены',
            'firm_leader_post' => 'Должность руководителя',
            'firm_leader_post_r' => 'Должность руководителя в родительном падеже',
            'firm_director' => 'ФИО руководителя',
            'firm_director_r' => 'ФИО руководителя в родительном падеже',
            'firm_leader_reason' => 'Основание деятельности руководителя',
            'firm_leader_reason_r' => 'Основание деятельности руководителя в родительном падеже',
            'firm_manager' => 'ФИО менеджера',
            'firm_buhgalter' => 'ФИО Бухгалтера',
            'firm_kladovshik' => 'ФИО Кладовщика',
            'firm_kladovshik_id' => 'ID пользователя-кладовщика',
            'firm_kladovshik_doljn' => 'Должность кладовщика',
            'firm_store_lock' => 'Ограничить своими складами',
            'firm_bank_lock' => 'Ограничить своими банками',
            'firm_till_lock' => 'Ограничить своими кассами'
        );
    }

    /// @brief Возвращает имя текущего элемента
    public function getItemName($item) {
        if (isset($item['firm_name'])) {
            return $item['firm_name'];
        } else {
            return '???';
        }
    }
    
    protected function getFieldFirm_type($data) {
        if(isset($this->types[$data['firm_type']])) {
            return html_out($this->types[$data['firm_type']]);
        }
        return '';
    }


    protected function getInputFirm_Type($name, $value) {
        $ret = "<select name='$name'>";
        foreach($this->types as $id => $item_name) {
            $selected = $id == $value ? ' selected' : '';
            $ret.= "<option value='{$id}'{$selected}>" . html_out($item_name) . "</option>";
        }
        $ret.= "</select>";
        return $ret;
    }

    public function getInputNo_retailprices($name, $value) {
        return $this->getCheckboxInput($name, 'Да', $value);
    }
    
    public function getFieldNo_retailprices($data) {
        return $data['no_retailprices'] ? "<b style='color:#F00'>Да</b>" : "<b style='color:#0C0'>Нет</b>";
    }
    
    public function getInputFirm_store_lock($name, $value) {
        return $this->getCheckboxInput($name, 'Да', $value);
    }

    public function getFieldFirm_store_lock($data) {
        return $data['firm_store_lock'] ? "<b style='color:#0c0'>Да</b>" : "<b style='color:#f00'>Нет</b>";
    }
    
    public function getInputFirm_bank_lock($name, $value) {
        return $this->getCheckboxInput($name, 'Да', $value);
    }

    public function getFieldFirm_bank_lock($data) {
        return $data['firm_bank_lock'] ? "<b style='color:#0c0'>Да</b>" : "<b style='color:#f00'>Нет</b>";
    }
    
    public function getInputFirm_till_lock($name, $value) {
        return $this->getCheckboxInput($name, 'Да', $value);
    }

    public function getFieldFirm_till_lock($data) {
        return $data['firm_till_lock'] ? "<b style='color:#0c0'>Да</b>" : "<b style='color:#f00'>Нет</b>";
    }
    
    /// Записать в базу строку справочника
    public function saveItem($id, $data) {
        if(!isset($data['firm_store_lock'])) {
            $data['firm_store_lock'] = 0;
        }    
        if(!isset($data['firm_bank_lock'])) {
            $data['firm_bank_lock'] = 0;
        }
        if(!isset($data['firm_till_lock'])) {
            $data['firm_till_lock'] = 0;
        }
        return parent::saveItem($id, $data);
    }
}
