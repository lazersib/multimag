<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2017, BlackLight, TND Team, http://tndproject.org
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

class RegionsEditor extends \ListEditor {

    public function __construct($db_link) {
        parent::__construct($db_link);
        $this->print_name = 'Справочник регионов доставки';
        $this->table_name = 'delivery_regions';
        $this->initTypesList();
    }

    /// Получить массив с именами колонок списка
    public function getColumnNames() {
        return array(
            'id' => 'id',
            'name' => 'Наименование',
            'delivery_type' => 'Способ доставки',
            'price' => 'Стоимость доставки',
            'description' => 'Описание',
        );
    }

    public function getInputDelivery_type($name, $value) {
        $ret = "<select name='$name'>";
        foreach ($this->types as $id => $item_name) {
            $selected = $id == $value ? ' selected' : '';
            $ret.= "<option value='{$id}'{$selected}>" . html_out($item_name) . "</option>";
        }
        $ret.= "</select>";
        return $ret;
    }

    public function getFieldDelivery_type($data) {
        if(isset($this->types[$data['delivery_type']])) {
            return html_out($this->types[$data['delivery_type']]);
        }
        return '';
    }

    protected function initTypesList() {
        if(isset($this->types)) {
            return;
        }
        $this->types = array();
        $res = $this->db_link->query("SELECT `id`, `name` FROM `delivery_types` ORDER BY `id`");
        while ($line = $res->fetch_assoc()) {
            $this->types[$line['id']] = $line['name'];
        }
    }
}
