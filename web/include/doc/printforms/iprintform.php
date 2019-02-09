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

namespace doc\printforms; 

/// Абстрактный класс печатной формы
abstract class iPrintForm {

    /**
     * @var \document|\doc_Nulltype Ссылка на документ с данными для печати
     */
    protected $doc;

    public $mime = "unknown/mime";
    
    public function __construct() {
    }

    /// Установить ссылку на распечатываемый документ
    public function setDocument($doc) {
        $this->doc = $doc;
    }

    /// Получить mime тип документа
    public function getMimeType() {
        return $this->mime;
    }

    /// Инициализация модуля вывода данных
    abstract public function initForm();
    
    /// Сформировать данные печатной формы
    abstract public function make();
    
    /// Возвращает имя документа
    abstract public function getName();

    /// Вывод данных
    /// @param $to_str Если истина - вернёт буфер с данными. Иначе - вывод в файл.
    abstract public function outData($to_str=false);
}