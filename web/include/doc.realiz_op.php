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

/// Документ *Оперативная реализация*
class doc_Realiz_op extends doc_Realizaciya {

    /// Конструктор
    /// @param $doc id документа
    function __construct($doc = 0) {
        parent::__construct($doc);
        $this->doc_type = 15;
        $this->typename = 'realiz_op';
        $this->viewname = 'Реализация товара (опер)';
    }

    /// Провести документ
    /// @param $silent Не менять отметку проведения
    function docApply($silent = 0) {
        global $db;
        if ($silent) {
            return;
        }
        if ($this->doc_data['ok']) {
            throw new \Exception('Документ уже проведён!');
        }
        $ok_time = time();
        $db->update('doc_list', $this->id, 'ok', $ok_time);
        $this->doc_data['ok'] = $ok_time;
        $this->sentZEvent('apply');
    }

    /// Отменить проведение документа
    function docCancel() {
        global $db;
        if (!$this->doc_data['ok']) {
            throw new \Exception('Документ не проведён!');
        }
        $db->update('doc_list', $this->id, 'ok', 0);
        $this->doc_data['ok'] = 0;
        $this->sentZEvent('cancel');
    }

}
