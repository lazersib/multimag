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

/// Документ *доверенность*
class doc_Doveren extends doc_Nulltype {

    /// Конструктор
    /// @param $doc id документа
    function __construct($doc = 0) {
        parent::__construct($doc);
        $this->doc_type = 10;
        $this->typename = 'doveren';
        $this->viewname = 'Доверенность';
        $this->sklad_editor_enable = true;
        $this->header_fields = 'separator agent cena';
    }

    /// Установка значений по умолчанию для дополнительных параметров документа
    protected function initDefDopdata() {
        $this->def_dop_data = array('ot' => '', 'cena' => 0, 'worker_id' => 0, 'end_date' => '');
    }

    /// Сформировать дополнительные заголовки документа
    function DopHead() {
        global $tmpl, $db;
        $tmpl->addContent("На получение от:<br>
		<input type='text' name='ot' value='{$this->dop_data['ot']}'><br>
                Сотрудник:<br><select name='worker_id'>
		<option value='0'>--не выбран--</option>");

        $res = $db->query("SELECT `user_id`, `worker_real_name` FROM `users_worker_info` WHERE `worker`='1' ORDER BY `worker_real_name`");
        while ($nxt = $res->fetch_row()) {
            $s = ($this->dop_data['worker_id'] == $nxt[0]) ? 'selected' : '';
            $tmpl->addContent("<option value='$nxt[0]' $s>" . html_out($nxt[1]) . "</option>");
        }
        $tmpl->addContent("</select><br> 
                Срок действия:<br>
                <input type='text' name='end_date' value='{$this->dop_data['end_date']}'><br>");
    }

    /// Сохранить дополнительные заголовки документа
    function DopSave() {
        $new_data = array(
            'ot' => request('ot'),
            'worker_id' => rcvint('worker_id'),
            'end_date' => rcvdate('end_date')
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

    /// Формирование другого документа на основе текущего
    /// @param $target_type Тип создаваемого документа
    function MorphTo($target_type) {
        global $tmpl;
        if ($target_type == '') {
            $tmpl->ajax = 1;
            $tmpl->addContent("<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->id}&amp;tt=1'\">
			<li><a href=''>Поступление товара</div>");
        } else if ($target_type == 1) {
            $this->recalcSum();
            \acl::accessGuard('doc.postuplenie', \acl::CREATE);  
            $new_doc = new doc_Postuplenie();
            $dd = $new_doc->createFrom($this);
            redirect("/doc.php?mode=body&doc=$dd");
        } else {
            throw new NotFoundException("Недоступно!");
        }
    }

}
