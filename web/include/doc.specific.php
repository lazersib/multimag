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


/// Документ *спецификация*
class doc_Specific extends doc_Nulltype {

    function __construct($doc = 0) {
        parent::__construct($doc);
        $this->doc_type = 16;
        $this->typename = 'specific';
        $this->viewname = 'Спецификация';
        $this->sklad_editor_enable = true;
        $this->header_fields = 'bank cena separator agent';
        settype($this->id, 'int');
    }

    function initDefDopdata() {
        $this->def_dop_data = array('received' => 0, 'cena' => 1);
    }

    function DopHead() {
        global $tmpl;
        $checked = $this->dop_data['received'] ? 'checked' : '';
        $tmpl->addContent("<label><input type='checkbox' name='received' value='1' $checked>Документы подписаны и получены</label><br>");
    }

    function DopSave() {
        $new_data = array(
            'received' => rcvint('received')
        );
        $old_data = array_intersect_key($new_data, $this->dop_data);

        $log_data = '';
        if ($this->id)
            $log_data = getCompareStr($old_data, $new_data);
        $this->setDopDataA($new_data);
        if ($log_data)
            doc_log("UPDATE {$this->typename}", $log_data, 'doc', $this->id);
    }
    
    /**
     * Получить список документов, которые можно создать на основе этого
     * @return array Список документов
     */
    public function getMorphList() {
        $morphs = array(
            'zayavka' =>      ['name'=>'zayavka',     'document' => 'zayavka',    'viewname' => 'Заявка покупателя', ],
        );
        return $morphs;
    }
    
    /** Создать подчинённую заявку покупателя
     * 
     * @return \doc_Zayavka
     */
    protected function morphTo_zayavka() {
        $new_doc = new \doc_Zayavka();
        $dd = $new_doc->createFromP($this);
        $new_doc->setDopData('cena', $this->dop_data['cena']);
        return $new_doc;
    }
}
