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

/**
@mainpage Cистема комплексного учёта торговли multimag. Документация разработчика.
<h2>Быстрый старт для разработчика multimag</h2>
<ul>
<li>BETemplate - шаблонизатор</li>
<li>doc.core.php содержит вспомогательные функции работы с документами</li>
<li>Vitrina формирует все страницы витрины и может быть перегружен шаблоном</li>
<li>doc_Nulltype является базовым классом для всех документов системы</li>
<li>BaseReport используется для генерации отчётов</li>
<li>От AsyncWorker наследуются обработчики, выполняющиеся независимо от веб сервера</li>
<li>От ListEditor наследуются редакторы простых справочников</li>
<li>PosEditor содержит методы для работы с редактором списка товаров</li>
<li>IModule - базовый класс для модулей. Ссылки на модули автоматически добавляются на нужные страницы. Это можно использовать для разработки плагинов.</li>
</ul>
Смотри <a href='annotated.html'>структуры данных</a> и <a href='hierarchy.html'>иерархию классов</a>, чтобы получить полное представление о классах системы
**/

/// Ядро веб-интерфейса мультимага
class webcore {
    
    /// Вычисляет максимально допустимый размер загружаемых файлов, в байтах
    public static function getMaxUploadFileSize() {
        $max_post = trim(ini_get('post_max_size'));
        $last = strtolower($max_post[strlen($max_post) - 1]);
        switch ($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $max_post *= 1024;
            case 'm':
                $max_post *= 1024;
            case 'k':
                $max_post *= 1024;
        }
        $max_fs = trim(ini_get('upload_max_filesize'));
        $last = strtolower($max_fs[strlen($max_fs) - 1]);
        switch ($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $max_fs *= 1024;
            case 'm':
                $max_fs *= 1024;
            case 'k':
                $max_fs *= 1024;
        }
        return min($max_fs, $max_post*0.95);
    }
    
    /** Преобразует целое число, являющееся объёмом данных к краткому неточному текстовому представлению с размерностью
     * 
     * @param type $size    Исходное значение
     * @return string       Краткое текстовое представление значения
     */
    public static function toStrDataSizeInaccurate($size) {
        if ($size > 1024 * 1024 * 1024) {
            $size = round($size / (1024 * 1024 * 1024), 2) . ' Гб';
        } else if ($size > 1024 * 1024) {
            $size = round($size / (1024 * 1024), 2) . ' Мб';
        } else if ($size > 1024) {
            $size = round($size / (1024), 2) . ' Кб';
        } else {
            $size.='байт';
        }
        return $size;
    }
    
    public static function concatLink($base_link, $params) {
        if(strpos($base_link, '?')!==false) {
            return $base_link.($params?('&amp;'.$params):'');
        }
        else {
            if($params) {
                return $base_link.'?'.$params;
            }
            else {
                return $base_link;
            }
        }
    }

}
