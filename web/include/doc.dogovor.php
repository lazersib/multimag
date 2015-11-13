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

/// Документ *договор*
class doc_Dogovor extends doc_Nulltype {

    // Создание нового документа или редактирование заголовка старого
    function __construct($doc = 0) {
        global $CONFIG;
        parent::__construct($doc);
        $this->doc_type = 14;
        $this->typename = 'dogovor';
        $this->viewname = 'Договор';
        $this->sklad_editor_enable = false;
        $this->header_fields = 'bank separator agent cena';
        settype($this->id, 'int');
        if (!$doc) {
            $this->doc_data['comment'] = @$CONFIG['doc']['contract_template'];
        }
    }

    function initDefDopdata() {
        $this->def_dop_data = array('name' => '', 'end_date' => '', 'debt_control' => 0, 'debt_size' => 0, 'limit' => 0, 'received' => 0, 'cena' => 0, 'deferment'=>0);
    }

    function DopHead() {
        global $tmpl;
        if ($this->id) {
            $end_date = @$this->dop_data['end_date'];
        } else {
            $end_date = date("Y-12-31");
        }
        $name = $this->dop_data['name'];
        $dchecked = $this->dop_data['debt_control'] ? 'checked' : '';
        $debt_size = $this->dop_data['debt_size'];
        $limit = $this->dop_data['limit'];
        $deferment = $this->dop_data['deferment'];
        $checked = $this->dop_data['received'] ? 'checked' : '';
        $tmpl->addContent("
            Отображаемое наименование:<br>
            <input type='text' name='name' value='$name'><br>
            Дата истечения:<br>
            <input type='text' name='end_date' value='$end_date'><br>
            <label><input type='checkbox' name='debt_control' value='1' $dchecked>Контроль задолженности</label><br>
            <input type='text' name='debt_size' value='$debt_size'><br>
            Максимальная отсрочка платежа, дней:<br>
            <input type='text' name='deferment' value='$deferment'><br>
            Лимит оборотов по договору:<br>
            <input type='text' name='limit' value='$limit'><br>
            <label><input type='checkbox' name='received' value='1' $checked>Документы подписаны и получены</label><br>");
    }

    function DopSave() {
        $new_data = array(
            'received' => request('received'),
            'end_date' => rcvdate('end_date'),
            'debt_control' => rcvint('debt_control') ? '1' : '0',
            'debt_size' => rcvint('debt_size'),
            'name' => request('name'),
            'limit' => rcvint('limit'),
            'deferment' => rcvint('deferment'),
            'received' => rcvint('received') ? '1' : '0'
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

    function DopBody() {
        global $tmpl, $db;
        if ($this->dop_data['received'])
            $tmpl->addContent("<br><b>Документы подписаны и получены</b><br>");
        if ($this->doc_data['comment']) {
            $agent = new \models\agent($this->doc_data['agent']);
            $res = $db->query("SELECT `name`, `bik`, `rs`, `ks` FROM `doc_kassa` WHERE `ids`='bank' AND `num`='{$this->doc_data['bank']}'");
            $bank_info = $res->fetch_assoc();

            $wikiparser = new WikiParser();

            $wikiparser->AddVariable('DOCNUM', $this->doc_data['altnum']);
            $wikiparser->AddVariable('DOCDATE', date("d.m.Y", $this->doc_data['date']));
            $wikiparser->AddVariable('AGENT', $agent->fullname);
            $wikiparser->AddVariable('AGENTDOL', 'директора');
            $wikiparser->AddVariable('AGENTFIO', $agent->dir_fio_r);
            $wikiparser->AddVariable('FIRMNAME', $this->firm_vars['firm_name']);
            $wikiparser->AddVariable('FIRMDIRECTOR', $this->firm_vars['firm_director_r']);
            $wikiparser->AddVariable('ENDDATE', @$this->dop_data['end_date']);
            $text = $wikiparser->parse($this->doc_data['comment'], ENT_QUOTES, "UTF-8");
            $tmpl->addContent("<b>Текст договора (форматирование может отличаться от форматирования при печати):</b> <p>$text</p>");
            $this->doc_data['comment'] = '';
        } else {
            $tmpl->addContent("<br><b style='color: #f00'>ВНИМАНИЕ! Текст договора не указан!</b><br>");
        }
    }

    /// Формирование другого документа на основании текущего
    function MorphTo($target_type) {
        global $tmpl;
        $tmpl->ajax = 1;
        if ($target_type == '') {
            $tmpl->ajax = 1;
            $tmpl->addContent("<div onclick=\"window.location='?mode=morphto&amp;doc={$this->id}&amp;tt=16'\">Спецификация</div>");
        } else if ($target_type == 16) {
            \acl::accessGuard('doc.specific', \acl::CREATE);            
            $new_doc = new doc_Specific();
            $dd = $new_doc->createFrom($this);
            $this->sentZEvent('morph_specific');
            header("Location: doc.php?mode=body&doc=$dd");
        }
    }

}
