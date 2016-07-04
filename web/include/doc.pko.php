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
/// Документ *приходный кассовый ордер*
class doc_Pko extends paymentbasedoc {

    function __construct($doc = 0) {
        parent::__construct($doc);
        $this->doc_type = 6;
        $this->typename = 'pko';
        $this->viewname = 'Приходный кассовый ордер';
        $this->kassa_modify = 1;
        $this->header_fields = 'kassa sum separator agent';
    }

    function initDefDopdata() {
        global $db;
        $def_acc = $db->selectRowK('doc_accounts', 'usedby', 'kassa');
        $acc = '';
        if (is_array($def_acc)) {
            $acc = $def_acc['account'];
        }
        $this->def_dop_data = array('credit_type' => 0, 'account' => $acc);
    }

    function DopHead() {
        global $tmpl, $db;
        $tmpl->addContent("Вид дохода:<br><select name='credit_type'>");
        if (!$this->dop_data['credit_type']) {
            $tmpl->addContent("<option value='0' selected disabled>--не задан--</option>");
        }
        $res = $db->query("SELECT `id`, `account`, `name` FROM `doc_ctypes` WHERE `id`>'0'");
        while ($nxt = $res->fetch_assoc()) {
            if ($nxt['id'] == $this->dop_data['credit_type']) {
                $tmpl->addContent("<option value='{$nxt['id']}' selected>" . html_out($nxt['name']) . " (" . html_out($nxt['account']) . ")</option>");
            } else {
                $tmpl->addContent("<option value='{$nxt['id']}'>" . html_out($nxt['name']) . " (" . html_out($nxt['account']) . ")</option>");
            }
        }
        $tmpl->addContent("</select><br>");

        $tmpl->addContent("Номер бухгалтерского счёта:<br><input type='text' name='account' value='{$this->dop_data['account']}'><br>");
    }

    function DopSave() {
        $new_data = array(
            'credit_type' => rcvint('credit_type'),
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
        if (!$this->isAltNumUnique() && !$silent) {
            throw new Exception("Номер документа не уникален!");
        }
        if($this->doc_data['kassa']<=0) {
            throw new Exception("Касса не выбрана");
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
        if ($doc_params['sum']<=0) {
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

        $res = $db->query("UPDATE `doc_kassa` SET `ballance`=`ballance`+'{$doc_params['sum']}' WHERE `ids`='kassa' AND `num`='{$doc_params['kassa']}'");
        if (!$db->affected_rows) {
            throw new Exception('Ошибка обновления кассы!');
        }
        if ($silent) {
            return;
        }

        $db->update('doc_list', $this->id, 'ok', time());
        $this->sentZEvent('apply');
        $this->paymentNotify();
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

        $db->query("UPDATE `doc_kassa` SET `ballance`=`ballance`-'{$data['sum']}'	WHERE `ids`='kassa' AND `num`='{$data['kassa']}'");
        if (!$db->affected_rows) {
            throw new Exception('Ошибка обновления кассы!');
        }

        $db->update('doc_list', $this->id, 'ok', 0);
        $budet = $this->checkKassMinus();
        if ($budet < 0) {
            throw new Exception("Невозможно, т.к. будет недостаточно ($budet) денег в кассе!");
        }
        $this->sentZEvent('cancel');
    }

    protected function extendedAclCheck($acl, $today_acl, $action) {
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
        $this->extendedAclCheck(\acl::APPLY, \acl::TODAY_APPLY, 'проведения');
        return parent::extendedApplyAclCheck();
    }
    
    /// Выполнение дополнительных проверок доступа для отмены документа
    public function extendedCancelAclCheck() {
        $this->extendedAclCheck(\acl::CANCEL, \acl::TODAY_CANCEL, 'отмены проведения');
        return parent::extendedCancelAclCheck();
    }
}
