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

/// Документ *пропуск*
class doc_PermitOut extends doc_Nulltype {
    public $cnt_fields;
    
    /// Конструктор
    /// @param $doc id документа
    function __construct($doc = 0) {
        global $CONFIG;
        parent::__construct($doc);
        $this->doc_type = 23;
        $this->typename = 'permitout';
        $this->viewname = 'Пропуск';
        $this->sklad_editor_enable = false;
        $this->header_fields = 'separator agent separator';
        if(isset($CONFIG['doc']['permitout_lines'])) {
            $this->cnt_fields = $CONFIG['doc']['permitout_lines'];
        } else {
            $this->cnt_fields = array('cnt_pack'=>'Количество упаковок');
        }
    }
    
    /// Инициализация дополнительных данных документа
    protected function initDefDopData() {
        global $CONFIG;
        $this->def_dop_data = array('transport_number' => '', 'load_permitter'=>'', 'exit_permitter'=>'', 'place'=>'');
        if(isset($CONFIG['doc']['permitout_lines'])) {
            foreach ($CONFIG['doc']['permitout_lines'] as $id=>$name) {
                $this->def_dop_data[$id] = 0;
            }
            //$this->def_dop_data = array_merge($this->def_dop_data, $CONFIG['doc']['permitout_lines']);
        } else {
            $this->def_dop_data = array_merge($this->def_dop_data, array('cnt_pack'=>0));
        }
    }

    /// Дополнительные поля заголовка документа
    function DopHead() {
        global $tmpl, $CONFIG, $db;
        if (!isset($this->dop_data['transport_number'])) {
            $this->dop_data['transport_number'] = '';
        }
        if (!isset($this->dop_data['load_permitter'])) {
            $this->dop_data['load_permitter'] = '';
        }
        if (!isset($this->dop_data['exit_permitter'])) {
            $this->dop_data['exit_permitter'] = '';
        }     
        $tmpl->addContent("Место хранения:<br>"
            . "<input type='text' name='place' style='width: 100%' value='{$this->dop_data['place']}'><br>"
            . "<hr>");
        $tmpl->addContent("Гос.номер транспортного средства<br>"
            . "<input type='text' name='transport_number' style='width: 100%' value='{$this->dop_data['transport_number']}'><br>"
            . "<hr>");
        
        foreach($this->cnt_fields as $id=>$name) {
            if (!isset($this->dop_data[$id])) {
                $this->dop_data[$id] = '';
            }
            $tmpl->addContent("$name<br>"
            . "<input type='text' name='$id' style='width: 100%' value='{$this->dop_data[$id]}'><br>");
        }
        $tmpl->addContent("<hr>");
        $workers = array();
        $res = $db->query("SELECT `user_id`, `worker_real_name` FROM `users_worker_info` WHERE `worker`='1' ORDER BY `worker_real_name`");
        while($nxt = $res->fetch_row()) {
            $workers[$nxt[0]] = $nxt[1];        }
        
        $tmpl->addContent("Погрузку разрешил:<br><select name='load_permitter'><option value='0'>--не выбран--</option>");       
        foreach($workers as $w_id=>$w_name) {
                $s=($this->dop_data['load_permitter']==$w_id)?' selected':'';
                $tmpl->addContent("<option value='$w_id'$s>".html_out($w_name)."</option>");
        }
        $tmpl->addContent("</select><br>");
        $tmpl->addContent("Выезд разрешил:<br><select name='exit_permitter'><option value='0'>--не выбран--</option>");
        foreach($workers as $w_id=>$w_name) {
                $s=($this->dop_data['exit_permitter']==$w_id)?' selected':'';
                $tmpl->addContent("<option value='$w_id'$s>".html_out($w_name)."</option>");
        }
        $tmpl->addContent("</select><br>");
    }

    function DopSave() {
        $new_data = array();
        foreach ($this->def_dop_data as $id=>$value) {
            $new_data[$id] = request($id);
        }
        foreach ($this->cnt_fields as $id=>$value) {
            $new_data[$id] = rcvint($id);
        }
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

     /// Провести документ
    function docApply($silent = 0) {
        global $db;
        if(!$this->isAltNumUnique() && !$silent) {
            throw new Exception("Номер документа не уникален!");
        }
        if($this->doc_data['p_doc']==0) {
            throw new \Exception('Пропуск не прикреплён к реализации!');
        }
        if (!$this->dop_data['transport_number']) {
            throw new \Exception('Гос.номер транспортного средства не указан');
        }
        if (!$this->dop_data['load_permitter']) {
            throw new \Exception('Поле *Погрузку разрешил* не заполнено');
        }
        if (!$this->dop_data['exit_permitter']) {
            throw new \Exception('Поле *Выезд разрешил* не заполнено');
        }
        
        $pdoc = \document::getInstanceFromDb($this->doc_data['p_doc']);
        if($pdoc->getTypeName()!='realizaciya' && $pdoc->getTypeName()!='realiz_bonus') {
            throw new \Exception('Пропуск прикреплён не к реализации!');
        }
        $pdop_data = $pdoc->getDopDataA();
        if(intval($pdop_data['mest'])<=0) {
            throw new Exception('Сумма мест в реализации не задана!');
        }
        
        $sum = 0;
        foreach($this->cnt_fields as $id=>$name) {
            if (!isset($this->dop_data[$id])) {
                $this->dop_data[$id] = 0;
            }
            $sum += intval($this->dop_data[$id]);
        }
        if($sum!=intval($pdop_data['mest'])) {
            throw new Exception('Сумма мест в пропуске не соответствует количеству мест реализации!');
        }
                
        if (!$silent) {
            $tim = time();
            $db->query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->id}'");
            $this->sentZEvent('apply');
        }
    }
}
