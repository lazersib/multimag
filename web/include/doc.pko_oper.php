<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2015, BlackLight, TND Team, http://tndproject.org
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
/// Документ *приходный кассовый ордер*
class doc_pko_oper extends doc_Pko {

    function __construct($doc = 0) {
        parent::__construct($doc);
        $this->doc_type = 22;
        $this->typename = 'pko_oper';
        $this->viewname = 'Приходный кассовый ордер (оперативный)';
        $this->ksaas_modify = 0;
    }

    // Провести
    function docApply($silent = 0) {
        global $db;
        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`kassa`, `doc_list`.`ok`, `doc_list`.`firm_id`, `doc_list`.`sum`,
                `doc_kassa`.`firm_id` AS `kassa_firm_id`, `doc_vars`.`firm_till_lock`
            FROM `doc_list`
            INNER JOIN `doc_kassa` ON `doc_kassa`.`num`=`doc_list`.`kassa` AND `ids`='kassa'
            INNER JOIN `doc_vars` ON `doc_list`.`firm_id` = `doc_vars`.`id`
            WHERE `doc_list`.`id`='{$this->id}'");
        $doc_params = $res->fetch_assoc();
        $res->free();

        if (!$doc_params) {
            throw new Exception('Документ ' . $this->id . ' не найден');
        }

        if ($doc_params['ok'] && (!$silent)) {
            throw new Exception('Документ уже проведён!');
        }

        // Запрет для другой фирмы
        if ($doc_params['kassa_firm_id'] != null && $doc_params['kassa_firm_id'] != $doc_params['firm_id']) {
            throw new Exception("Выбранная касса относится другой организации!");
        }
        // Ограничение фирмы списком своих банков
        if ($doc_params['firm_till_lock'] && $doc_params['kassa_firm_id'] != $doc_params['firm_id']) {
            throw new Exception("Выбранная организация может работать только со своими кассами!");
        }

        $db->update('doc_list', $this->id, 'ok', time());
        $this->sentZEvent('apply');
    }

    // Отменить проведение
    function DocCancel() {
        global $db;
        $data = $db->selectRow('doc_list', $this->id);
        if (!$data) {
            throw new Exception('Ошибка выборки данных документа!');
        }
        if (!$data['ok']) {
            throw new Exception('Документ не проведён!');
        }

        $db->update('doc_list', $this->id, 'ok', 0);
        $budet = $this->checkKassMinus();
        if ($budet < 0) {
            throw new Exception("Невозможно, т.к. будет недостаточно ($budet) денег в кассе!");
        }
        $this->sentZEvent('cancel');
    }

}
