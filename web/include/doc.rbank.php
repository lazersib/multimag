<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2018, BlackLight, TND Team, http://tndproject.org
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

/// Документ *Расход средств из банка*
class doc_RBank extends doc_Nulltype {

    function __construct($doc = 0) {
        parent::__construct($doc);
        $this->doc_type = 5;
        $this->typename = 'rbank';
        $this->viewname = 'Расход средств из банка';
        $this->bank_modify = -1;
        $this->header_fields = 'bank sum separator agent';
    }

    function initDefDopdata() {
        global $db;
        $def_acc = $db->selectRowK('doc_accounts', 'usedby', 'bank');
        $acc = '';
        if (is_array($def_acc)) {
            $acc = $def_acc['account'];
        }
        $this->def_dop_data = array('rasxodi' => 0, 'account' => $acc);
    }

    function dopHead() {
        global $tmpl, $db;
        $tmpl->addContent("Вид расхода:<br><select name='rasxodi'>");
        $res = $db->query("SELECT `id`, `account`, `name` FROM `doc_dtypes` WHERE `id`>'0'");
        if (!$this->dop_data['rasxodi']) {
            $tmpl->addContent("<option value='0' selected disabled>--не задан--</option>");
        }
        while ($nxt = $res->fetch_assoc()) {
            if ($nxt['id'] == $this->dop_data['rasxodi']) {
                $tmpl->addContent("<option value='{$nxt['id']}' selected>" . html_out($nxt['name']) . " (" . html_out($nxt['account']) . ")</option>");
            } else {
                $tmpl->addContent("<option value='{$nxt['id']}'>" . html_out($nxt['name']) . " (" . html_out($nxt['account']) . ")</option>");
            }
        }

        $tmpl->addContent("</select><br>");
        $tmpl->addContent("Номер бухгалтерского счёта:<br><input type='text' name='account' value='{$this->dop_data['account']}'><br>");
    }

    function dopSave() {
        $new_data = array(
            'rasxodi' => request('rasxodi'),
            'account' => request('account')
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

    // Провести
    function docApply($silent = 0) {
        global $db;

        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`bank`, `doc_list`.`ok`, `doc_list`.`firm_id`, `doc_list`.`sum`,
                `doc_kassa`.`firm_id` AS `bank_firm_id`, `doc_vars`.`firm_bank_lock`
            FROM `doc_list`
            INNER JOIN `doc_kassa` ON `doc_kassa`.`num`=`doc_list`.`bank` AND `ids`='bank'
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
        if ($doc_params['sum']<=0) {
            throw new Exception('Нельзя провести документ с нулевой или отрицательной суммой!');
        }
        $this->checkIfTypeForDocumentExists();
        
        // Запрет для другой фирмы
        // Проверка временно отключена
        //if($doc_params['bank_firm_id']!=null && $doc_params['bank_firm_id']!=$doc_params['firm_id']) {
        //    throw new Exception("Выбранный банк относится другой организации!");
        //}
        // Ограничение фирмы списком своих банков
        if($doc_params['firm_bank_lock'] && $doc_params['bank_firm_id']!=$doc_params['firm_id']) {
            throw new Exception("Выбранная организация может работать только со своими банками!");
        }

        $db->query("UPDATE `doc_kassa` SET `ballance`=`ballance`-'{$doc_params['sum']}' WHERE `ids`='bank' AND `num`='{$doc_params['bank']}'");
        if (!$db->affected_rows) {
            throw new Exception("Cумма в банке {$doc_params['bank']} не изменилась!");
        }

        parent::docApply($silent);
    }

    // Отменить проведение
    function docCancel() {
        global $db;
        $db->query("UPDATE `doc_kassa` SET `ballance`=`ballance`+'{$this->doc_data['sum']}' WHERE `ids`='bank' AND `num`='{$this->doc_data['bank']}'");
        if (!$db->affected_rows) {
            throw new Exception("Cумма в банке {$this->doc_data['bank']} не изменилась!");
        }
        parent::docCancel();
    }
}
