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
namespace sync;

/// Класс формирования XML файла для обмена с 1с
class Xml1cDataExport extends \sync\DataExport {
    public $dom;    //< Объект DOMDocument
 
    /// Преобразовывает ассоциативный многоуровневый массив $data в DOMElement
    /// @param $group_node_name Имя создвавемого элемента списка
    /// @param $item_node_name Имя объектов-элементов
    /// @param $data ассоциативный многоуровневый массив с данными
    /// @return DOMElement c данными из $data
    public function convertToXmlElement($group_node_name, $item_node_name, $data) {
        $res_node = $this->dom->createElement($group_node_name);
        foreach($data as $id=>$line) {
            $item_node = $this->dom->createElement($item_node_name);
            $item_node->setAttribute('id', $id);
            foreach($line AS $name=>$value) {
                if($name==='id' || $value===null || $value==='' || $value==='0000-00-00') {
                    continue;
                }
                if(is_array($value)) {
                    $param_node = $this->convertToXmlElement($name, substr($name, 0, -1), $value);
                } else {
                    $param_node = $this->dom->createElement($name, $value);
                }
                $item_node->appendChild($param_node);
            }
            $res_node->appendChild($item_node);
        }
        return $res_node;
    }
}