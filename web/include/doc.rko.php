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
/// Документ *Расходный кассовый ордер*
class doc_Rko extends doc_Nulltype {

    function __construct($doc = 0) {
        parent::__construct($doc);
        $this->doc_type = 7;
        $this->typename = 'rko';
        $this->viewname = 'Расходный кассовый ордер';
        $this->ksaas_modify = -1;
        $this->header_fields = 'kassa sum separator agent';
    }

    function initDefDopdata() {
        global $db;
        $def_acc = $db->selectRowK('doc_accounts', 'usedby', 'kassa');
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
        if (!$this->dop_data['rasxodi'])
            $tmpl->addContent("<option value='0' selected disabled>--не задан--</option>");
        while ($nxt = $res->fetch_assoc())
            if ($nxt['id'] == $this->dop_data['rasxodi'])
                $tmpl->addContent("<option value='{$nxt['id']}' selected>" . html_out($nxt['name']) . " (" . html_out($nxt['account']) . ")</option>");
            else
                $tmpl->addContent("<option value='{$nxt['id']}'>" . html_out($nxt['name']) . " (" . html_out($nxt['account']) . ")</option>");

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
        if ($this->id)
            $log_data = getCompareStr($old_data, $new_data);
        $this->setDopDataA($new_data);
        if ($log_data)
            doc_log("UPDATE {$this->typename}", $log_data, 'doc', $this->id);
    }

    // Провести
    function docApply($silent = 0) {
        global $db;
        if (!$this->isAltNumUnique() && !$silent) {
            throw new Exception("Номер документа не уникален!");
        }
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
        if ($doc_params['sum'] <= 0) {
            throw new Exception('Нельзя провести документ с нулевой или отрицательной суммой!');
        }
        $this->checkIfTypeForDocumentExists();
        
        // Запрет для другой фирмы
        if ($doc_params['kassa_firm_id'] != null && $doc_params['kassa_firm_id'] != $doc_params['firm_id']) {
            throw new Exception("Выбранная касса относится другой организации!");
        }
        // Ограничение фирмы списком своих банков
        if ($doc_params['firm_till_lock'] && $doc_params['kassa_firm_id'] != $doc_params['firm_id']) {
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
            throw new Exception('Ошибка обновления кассы!');
        }
        if (!$silent) {
            $budet = $this->checkKassMinus();
            if ($budet < 0) {
                throw new Exception("Невозможно, т.к. будет недостаточно ($budet) денег в кассе!");
            }
        }
        parent::docApply($silent);
    }

    // Отменить проведение
    function docCancel() {
        global $db;
        if (!$this->doc_data['ok']) {
            throw new Exception('Документ не проведён!');
        }
        $db->query("UPDATE `doc_kassa` SET `ballance`=`ballance`+'{$this->doc_data['sum']}'"
            . " WHERE `ids`='kassa' AND `num`='{$this->doc_data['kassa']}'");
        if (!$db->affected_rows) {
            throw new Exception('Ошибка обновления кассы!');
        }
        parent::docCancel();
    }
    
    protected function extendedAcl2Check($acl, $today_acl, $action) {
        $acl_obj = ['cash.global', 'cash.'.$this->doc_data['kassa']];      
        if (!\acl::testAccess($acl_obj, $acl)) {
            $d_start = date_day(time());
            $d_end = $d_start + 60 * 60 * 24 - 1;
            if (!\acl::testAccess($acl_obj, $today_acl)) {
                throw new \AccessException('Не достаточно привилегий для '.$action.' документа с выбранной кассой '.$this->doc_data['kassa']);
            } elseif ($this->doc_data['date'] < $d_start || $this->doc_data['date'] > $d_end) {
                throw new \AccessException('Не достаточно привилегий для '.$action.' документа с выбранной кассой '.$this->doc_data['kassa'].' произвольной датой');
            }
        }
    }
    
    public function extendedViewAclCheck() {
        $acl_obj = ['cash.global', 'cash.'.$this->doc_data['kassa']];      
        if (!\acl::testAccess($acl_obj, \acl::VIEW)) {
            throw new \AccessException('Не достаточно привилегий для просмотра документа с выбранной кассой '.$this->doc_data['kassa']);
        }
        return parent::extendedViewAclCheck();
    }
    
    /// Выполнение дополнительных проверок доступа для проведения документа
    public function extendedApplyAclCheck() {
        $this->extendedAcl2Check(\acl::APPLY, \acl::TODAY_APPLY, 'проведения');
        return parent::extendedApplyAclCheck();
    }
    
    /// Выполнение дополнительных проверок доступа для отмены документа
    public function extendedCancelAclCheck() {
        $this->extendedAcl2Check(\acl::CANCEL, \acl::TODAY_CANCEL, 'отмены проведения');
        return parent::extendedCancelAclCheck();
    }
}
