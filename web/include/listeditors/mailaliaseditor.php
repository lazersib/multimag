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

class MailAliasEditor extends \ListEditor {

    public function __construct($db_link) {
        parent::__construct($db_link);
        $this->print_name = 'Почтовые алиасы';
        $this->table_name = 'virtual_aliases';
        $this->can_delete = true;
    }

    /// Получить массив с именами колонок списка
    public function getColumnNames() {
        return array(
            'id' => 'id',
            'domain_id' => 'Домен',
            'source' => 'Пользователь ящика',
            'destination' => 'Алиас'
        );
    }

    public function getInputDomain_id($name, $value) {
        $ret = "<select name='$name'>";
        $res = $this->db_link->query("SELECT `id`, `name` FROM `virtual_domains` ORDER BY `id`");
        while ($line = $res->fetch_assoc()) {
            $selected = $line['id'] == $value ? ' selected' : '';
            $ret.= "<option value='{$line['id']}'{$selected}>" . html_out($line['name']) . "</option>";
        }
        $ret.= "</select>";
        return $ret;
    }
    
    public function getInputSource($name, $value) {
        $ret = "<select name='$name'>";
        $res = $this->db_link->query("SELECT `id`, `user` FROM `virtual_users` ORDER BY `id`");
        while ($line = $res->fetch_assoc()) {
            $selected = $line['id'] == $value ? ' selected' : '';
            $ret.= "<option value='{$line['id']}'{$selected}>" . html_out($line['user']) . "</option>";
        }
        $ret.= "</select>";
        return $ret;
    }
}
