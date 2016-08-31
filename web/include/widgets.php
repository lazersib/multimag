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


/// Класс со статическими функциями формирования виджетов
class widgets {
    
    /// Сформировать HTML код виджета *select* с экранированием данных
    static function getEscapedSelect($name, $values, $selected_id = null, $empty_item=false, $class_name='', $id_value='', $disabled=false) {
        $dis = $disabled?' disabled':'';
        $str = '<select name="'.html_out($name).'" class="'.$class_name.'" id="'.$id_value.$dis.'">';
        if($empty_item) {
            if($empty_item===true) {
                $str .= '<option value="null">--не выбрано--</option>';
            } else {
                $str .= '<option value="null">--'.html_out($empty_item).'--</option>';
            }
        }
        foreach($values as $id=>$value) {
            $s = ($selected_id == $id) ? 'selected' : '';
            $str .= '<option value="'.html_out($id).'" '.$s.'>' . html_out($value) . '</option>';
        }
        $str .= '</select>';
        return $str;
    }   
    
    /// Сформировать HTML код виджета *вкладки* с экранированием данных
    /// @param $list Массив со списком вкладок
    /// @param $opened Код открытой вкладки        
    /// @param $link_prefix Префикс ссылки вкладки
    /// @param $param_name Параметр ссылки выбора вкладки
    static function getEscapedTabsWidget($list, $opened, $link_prefix, $param_name) {
        $sel = array();
        $str = '<ul class="tabs">';
        foreach($list as $id=>$value) {
            $sel = $opened==$id ? ' class="selected"':'';
            $str .= "<li><a{$sel} href='{$link_prefix}&amp;{$param_name}={$id}'>".html_out($value['name'])."</a></li>";
        }
        $str .= '</ul>';
        return $str;
    }
    
    /// Сформировать HTML код виджета *таблица* без экранирования данных
    static function getTable($table_header, $table_body, $head_each_lines = 100, $table_class='list') {
        $str = '<table class="'.$table_class.'">';
        $line_cnt = 0;
        foreach($table_body as $line) {
            if( ($line_cnt % $head_each_lines) == 0) {
                $str .= "<tr>";
                foreach($table_header as $cell) {
                    $str .= "<th>$cell</th>";    
                }
                $str .= '</tr>';
            } 
            $str .= '<tr>';
            foreach($line as $cell) {
                $str .= "<td>$cell</td>";    
            }
            $str .= '</tr>';
            $line_cnt++;
        }        
        $str .= '</table>';
        return $str;
    }
        
}
