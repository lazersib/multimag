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
//

/// Документ *перемещение средств между кассами*
class doc_PerKas extends doc_Nulltype {

    function __construct($doc = 0) {
        parent::__construct($doc);
        $this->doc_type = 9;
        $this->doc_name = 'perkas';
        $this->doc_viewname = 'Перемещение средств (касса)';
        $this->header_fields = 'sum separator kassa';
    }

    function initDefDopdata() {
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
        if ($this->doc) {
            $log_data = getCompareStr($old_data, $new_data);
        }
        $this->setDopDataA($new_data);
        if ($log_data) {
            doc_log("UPDATE {$this->doc_name}", $log_data, 'doc', $this->doc);
        }
    }

    function docApply($silent = 0) {
        global $db;
        $data = $db->selectRow('doc_list', $this->doc);
        if (!$data) {
            throw new Exception('Ошибка выборки данных документа при проведении!');
        }
        if ($data['ok'] && (!$silent)) {
            throw new Exception('Документ уже проведён!');
        }

        $res = $db->query("SELECT `ballance` FROM `doc_kassa` WHERE `ids`='kassa' AND `num`='{$data['kassa']}'");
        if (!$res->num_rows) {
            throw new Exception('Ошибка получения суммы кассы!');
        }
        $nxt = $res->fetch_row();
        if ($nxt[0] < $data['sum']) {
            throw new Exception("Не хватает денег в кассе N{$data['kassa']} ($nxt[0] < {$data['sum']})!");
        }

        $res = $db->query("UPDATE `doc_kassa` SET `ballance`=`ballance`-'{$data['sum']}' WHERE `ids`='kassa' AND `num`='{$data['kassa']}'");
        if (!$db->affected_rows) {
            throw new Exception('Ошибка обновления кассы-источника!');
        }

        $res = $db->query("UPDATE `doc_kassa` SET `ballance`=`ballance`+'{$data['sum']}' WHERE `ids`='kassa' AND `num`='{$this->dop_data['v_kassu']}'");
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

        $db->update('doc_list', $this->doc, 'ok', time());
        $this->sentZEvent('apply');
    }

    function docCancel() {
        global $db;
        $data = $db->selectRow('doc_list', $this->doc);
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

        $db->update('doc_list', $this->doc, 'ok', 0);
        $this->sentZEvent('cancel');
    }

}
