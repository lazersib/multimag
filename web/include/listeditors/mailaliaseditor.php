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

class MailAliasEditor extends \ListEditor {

    public function __construct($db_link) {
        parent::__construct($db_link);
        $this->print_name = 'Почтовые алиасы';
        $this->table_name = 'virtual_aliases';
        $this->can_delete = true;
        $this->initDomainList();
        $this->initUserList();
    }

    /// Получить массив с именами колонок списка
    public function getColumnNames() {
        return array(
            'id' => 'id',
            'alias_prefix' => 'Префикс алиаса',
            'domain_id' => 'Домен',
            'user_id' => 'Пользователь'
        );
    }

    protected function getInputDomain_id($name, $value) {
        $ret = "<select name='$name'>";
        foreach($this->domain_list as $id => $domain_name) {
            $selected = $id == $value ? ' selected' : '';
            $ret.= "<option value='{$id}'{$selected}>" . html_out($domain_name) . "</option>";
        }
        $ret.= "</select>";
        return $ret;
    }
    
    protected function getInputUser_id($name, $value) {
        $ret = "<select name='$name'>";
        foreach($this->user_list as $id => $user_name) {
            $selected = $id == $value ? ' selected' : '';
            $ret.= "<option value='{$id}'{$selected}>" . html_out($user_name) . "</option>";
        }
        $ret.= "</select>";
        return $ret;
    }
    
    protected function getFieldDomain_id($data) {
        return html_out($this->domain_list[$data['domain_id']]);
    }
    
    protected function getFieldUser_id($data) {
        return html_out($this->user_list[$data['user_id']]);
    }
    
    protected function initDomainList() {
        if(isset($this->domain_list)) {
            return;
        }
        $this->domain_list = array();
        $res = $this->db_link->query("SELECT `id`, `name` FROM `virtual_domains` ORDER BY `id`");
        while ($line = $res->fetch_assoc()) {
            $this->domain_list[$line['id']] = $line['name'];
        }
    }
    
    protected function initUserList() {
        if(isset($this->user_list)) {
            return;
        }
        $this->user_list = array();
        $res = $this->db_link->query("SELECT `id`, `user` FROM `view_users_auth` ORDER BY `id`");
        while ($line = $res->fetch_assoc()) {
            $this->user_list[$line['id']] = $line['user'];
        }
    }
}
