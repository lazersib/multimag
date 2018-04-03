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

/// Класс для документов-расходников
class doc_debit extends \doc_Nulltype {

    /** Установить вид расхода для документа по кодовому имени вида расхода
     * 
     * @param string $codename Кодовое имя вида расхода
     */
    public function setDebitTypeFromCodename($codename) {
        global $db;
        $codename_sql = $db->real_escape_string($codename);
        $resource = $db->query("SELECT `id` FROM `doc_dtypes` WHERE `codename`='$codename_sql'");
        if($resource->num_rows) {
            $result = $resource->fetch_assoc();
            $this->setDopData('rasxodi', $result['id']);
        }
    }
}
