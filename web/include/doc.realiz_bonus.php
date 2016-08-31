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
//


/// Документ *Реализация за бонусы*
class doc_Realiz_bonus extends doc_Realizaciya {

    /// Конструктор
    /// @param $doc id документа
    function __construct($doc = 0) {
        parent::__construct($doc);
        $this->doc_type = 20;
        $this->typename = 'realiz_bonus';
        $this->viewname = 'Реализация товара за бонусы';
        $this->sklad_editor_enable = true;
        $this->header_fields = 'sklad cena separator agent';
        settype($this->id, 'int');
    }

    function DocApply($silent = 0) {
        global $db;
        if (!$silent) {
            $res = $db->query("SELECT `no_bonuses` FROM `doc_agent` WHERE `id`=" . intval($this->doc_data['agent']));
            if (!$res->num_rows) {
                throw new Exception("Агент не найден");
            }
            $agent_info = $res->fetch_row();
            if ($agent_info[0]) {
                throw new Exception("Агент не участвует в бонусной программе");
            }
            $bonus = docCalcBonus($this->doc_data['agent']);
            if ($this->doc_data['sum'] > $bonus) {
                throw new Exception("У агента недостаточно бонусов");
            }
            $this->fixPrice();
        }
        parent::DocApply($silent);
    }

}
