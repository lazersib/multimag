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

/// Документ *перемещение средств между кассами*
class doc_PerKas extends doc_Nulltype {

    function __construct($doc = 0) {
        parent::__construct($doc);
        $this->doc_type = 9;
        $this->typename = 'perkas';
        $this->viewname = 'Перемещение средств (касса)';
        $this->header_fields = 'sum separator kassa';
    }

    protected function initDefDopData() {
        $this->def_dop_data = array('v_kassu' => 0);
    }

    function dopHead() {
        global $tmpl, $db;
        $tmpl->addContent("В кассу:<br>
		<select name='v_kassu'>");
        $res = $db->query("SELECT `num`, `name`, `ballance` FROM `doc_kassa` WHERE `ids`='kassa' ORDER BY `name`");
        while ($nxt = $res->fetch_row()) {
            $bal_p = sprintf("%0.2f р.", $nxt[2]);
            if ($nxt[0] == $this->dop_data['v_kassu']) {
                $tmpl->addContent("<option value='$nxt[0]' selected>" . html_out("$nxt[1] ($bal_p)") . "</option>");
            } else {
                $tmpl->addContent("<option value='$nxt[0]'>" . html_out("$nxt[1] ($bal_p)") . "</option>");
            }
        }
        $tmpl->addContent("</select>");
    }

    function dopSave() {
        $new_data = array(
            'v_kassu' => rcvint('v_kassu')
        );
        $old_data = array_intersect_key($new_data, $this->dop_data);

        $log_data = '';
        if ($this->id) {
            $log_data = getCompareStr($old_data, $new_data);
        }
        $this->setDopDataA($new_data);
        if ($log_data) {
            doc_log("UPDATE {$this->typename}", $log_data, 'doc', $this->id);
        }
    }

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
        $dest_res = $db->query("SELECT `firm_id` FROM `doc_kassa` WHERE `ids`='kassa' AND `num`=".intval($this->dop_data['v_kassu']));
        $dest_till_info = $dest_res->fetch_assoc();

        // Запрет для другой фирмы
        if($doc_params['kassa_firm_id']!=null && $doc_params['kassa_firm_id']!=$doc_params['firm_id']) {
            throw new Exception("Исходная касса относится к другой организации!");
        }
        if ($dest_till_info['firm_id'] != null && $dest_till_info['firm_id'] != $doc_params['firm_id']) {
            throw new Exception("Касса назначения относится к другой организации!");
        }
        // Ограничение фирмы списком своих касс
        if($doc_params['firm_till_lock'] && ($doc_params['kassa_firm_id']!=$doc_params['firm_id'] || $dest_till_info['firm_id'] != $doc_params['firm_id']) ) {
            throw new Exception("Выбранная организация может работать только со своими кассами!");
        }
        
        $res = $db->query("SELECT `ballance` FROM `doc_kassa` WHERE `ids`='kassa' AND `num`='{$doc_params['kassa']}'");
        if (!$res->num_rows) {
            throw new Exception('Ошибка получения суммы кассы!');
        }
        $nxt = $res->fetch_row();
        if ($nxt[0] < $doc_params['sum']) {
            throw new Exception("Не хватает денег в кассе N{$doc_params['kassa']} ($nxt[0] < {$doc_params['sum']})!");
        }

        $res = $db->query("UPDATE `doc_kassa` SET `ballance`=`ballance`-'{$doc_params['sum']}' WHERE `ids`='kassa' AND `num`='{$doc_params['kassa']}'");
        if (!$db->affected_rows) {
            throw new Exception('Ошибка обновления кассы-источника!');
        }

        $res = $db->query("UPDATE `doc_kassa` SET `ballance`=`ballance`+'{$doc_params['sum']}' WHERE `ids`='kassa' AND `num`='{$this->dop_data['v_kassu']}'");
        if (!$db->affected_rows) {
            throw new Exception('Ошибка обновления кассы назначения!');
        }

        $budet = $this->checkKassMinus();
        if ($budet < 0) {
            throw new Exception("Невозможно, т.к. будет недостаточно ($budet) денег в кассе!");
        }

        if ($silent) {
            return;
        }

        $db->update('doc_list', $this->id, 'ok', time());
        $this->sentZEvent('apply');
    }

    function docCancel() {
        global $db;
        $data = $db->selectRow('doc_list', $this->id);
        if (!$data) {
            throw new Exception('Ошибка выборки данных документа!');
        }
        if (!$data['ok']) {
            throw new Exception('Документ не проведён!');
        }

        $res = $db->query("UPDATE `doc_kassa` SET `ballance`=`ballance`+'{$data['sum']}'	WHERE `ids`='kassa' AND `num`='{$data['kassa']}'");
        if (!$db->affected_rows) {
            throw new Exception('Ошибка обновления кассы-источника!');
        }

        $res = $db->query("UPDATE `doc_kassa` SET `ballance`=`ballance`-'{$data['sum']}'	WHERE `ids`='kassa' AND `num`='{$this->dop_data['v_kassu']}'");
        if (!$db->affected_rows) {
            throw new Exception('Ошибка обновления кассы назначения!');
        }

        $db->update('doc_list', $this->id, 'ok', 0);
        $this->sentZEvent('cancel');
    }

}
