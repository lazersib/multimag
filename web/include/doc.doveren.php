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
        $this->setDopDataA($new_data);
    }
    
    /**
     * Получить список документов, которые можно создать на основе этого
     * @return array Список документов
     */
    public function getMorphList() {
        $morphs = array(
            'postuplenie' =>   ['name'=>'postuplenie', 'document' => 'postuplenie',    'viewname' => 'Поступление ТМЦ', ],
        );
        return $morphs;
    }
    
    protected function morphTo_postuplenie() {
        $new_doc = new doc_Postuplenie();
        $new_doc->createFromP($this);
        $new_doc->setDopData('cena', $this->dop_data['cena']);
        return $new_doc;
    }
}
