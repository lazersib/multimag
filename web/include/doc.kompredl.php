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


/// Документ *коммерческое предложение*
class doc_Kompredl extends doc_Nulltype {

    /// Конструктор
    /// @param $doc id документа
    function __construct($doc = 0) {
        parent::__construct($doc);
        $this->doc_type = 13;
        $this->typename = 'kompredl';
        $this->viewname = 'Коммерческое предложение';
        $this->sklad_editor_enable = true;
        $this->header_fields = 'bank sklad separator agent cena';
    }

    /// Установка значений по умолчанию для дополнительных параметров документа
    function initDefDopdata() {
        $this->def_dop_data = array('shapka' => '', 'cena' => 0);
    }

    /// Сформировать дополнительные заголовки документа
    function DopHead() {
        global $tmpl;
        $tmpl->addContent("Текст шапки:<br><textarea name='shapka'>{$this->dop_data['shapka']}</textarea><br>");
    }

    /// Сохранить дополнительные заголовки документа
    function DopSave() {
        $new_data = array(
            'shapka' => request('shapka')
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

    /// Отобразить дополнительные данные в теле документа
    function DopBody() {
        global $tmpl;
        if ($this->dop_data['shapka']) {
            $tmpl->addContent("<b>Текст шапки:</b> {$this->dop_data['shapka']}");
        } else {
            $tmpl->addContent("<br><b style='color: #f00'>ВНИМАНИЕ! Текст шапки не указан!</b><br>");
        }
        $tmpl->addContent("Срок поставки можно указать в комментариях наименования<br>");
    }

    /// Формирование другого документа на основе текущего
    /// @param $target_type Тип создаваемого документа
    function MorphTo($target_type) {
        global $tmpl, $db;
        if ($target_type == '') {
            $tmpl->ajax = 1;
            $tmpl->addContent("<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->id}&amp;tt=3'\">Заявка покупателя</div>");
        } else if ($target_type == 3) {
            $db->startTransaction();
            if (!isAccess('doc_zayavka', 'create')) {
                throw new AccessException();
            }
            $new_doc = new doc_Zayavka();
            $dd = $new_doc->createFromP($this);
            $new_doc->setDopData('cena', $this->dop_data['cena']);
            $db->commit();
            redirect("/doc.php?mode=body&doc=$dd");
        }
    }

}
