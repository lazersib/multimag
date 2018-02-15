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
/// Отчёт *Проверка товаров*
class Report_goods_approve extends BaseGSReport {

    var $d_sum = 0;
    var $c_sum = 0;

    /// Получить имя отчёта
    public function getName($short = 0) {
        if ($short) {
            return "Проверка карточек товаров";
        } else {
            return "Отчёт по проверенным карточкам товаров";
        }
    }

    /// Запустить отчёт
    public function Run($opt) {
        if ($opt == '') {
            $this->Form();
        } else {
            $this->Make();
        }
    }

    /// Отобразить форму
    protected function Form() {
        global $tmpl, $db;
        $dt_f = date("Y-m-01");
        $dt_t = date("Y-m-d");
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
            <script src='/js/calendar.js'></script>
            <form action='' method='post'>
            <input type='hidden' name='mode' value='goods_approve'>
            <input type='hidden' name='opt' value='make'>
            <fieldset><legend>Показывать</legend>
            <label><input type='radio' name='show_mode' value='na' checked>Не проверенные никем</label><br>
            <label><input type='radio' name='show_mode' value='na_me'>Не проверенные мной</label><br>
            <label><input type='radio' name='show_mode' value='a'>Проверенные кем угодно</label><br>
            <label><input type='radio' name='show_mode' value='a_me'>Проверенные мной</label><br>
            </fieldset>
            <br>Группа товаров:<br>");
            $this->GroupSelBlock();
            $tmpl->addContent("<br>
            <button type='submit'>Создать отчет</button></form>");
    }

    /// Сформировать отчёт
    protected function Make() {
        global $tmpl;
        $gs = rcvint('gs');
        $g = request('g');
        $show_mode = request('show_mode');
        $this->count = 0;
        $tmpl->addContent("<h1 id='page-title'>".$this->getName()."</h1>
		<table class='list' width='100%'>
		<tr><th>ID</th><th>Наименование</th><th>Пользователь</th><th>Дата</th></tr>
		");
        $u_ldo = new \Models\LDO\usernames();
        $vars = array(
            'show_mode' => $show_mode,
            'group_filter' => $gs,
            'group_list' => $g,
            'users' => $u_ldo->getData(),
        );
        $this->processGroup(0, $vars);  
        $tmpl->addContent("</table>");
        $tmpl->addContent("<br>Всего: {$this->count} элементов</b><br>");
    }
    
    protected function processGroup($group_id, $vars) {
        global $db, $tmpl;
        settype($group_id,'int');
        
        $this->processGroupItems($group_id, $vars);
        
        /// и подгруппы
        $res_group = $db->query("SELECT `id`, `name` FROM `doc_group` WHERE `pid`=$group_id ORDER BY `id`");
        while ($group_line = $res_group->fetch_assoc()) {
            if ($vars['group_filter'] && is_array($vars['group_list'])) {
                if (!in_array($group_line['id'], $vars['group_list'])) {
                    continue;
                }
            }
            $tmpl->addContent("<tr><th colspan='10'>".html_out($group_line['name'])."</th></tr>");
            $this->processGroup($group_line['id'], $vars);            
        }
        
    }
    
    
    protected function processGroupItems($group_id, $vars) {
        global $db, $tmpl;
        $res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`cost` AS `base_price`, `doc_base`.`mass`,
                `doc_base`.`vc`, `doc_base`.`group`, `doc_base`.`bulkcnt`, `doc_base`.`proizv` AS `vendor`
            FROM `doc_base`
            WHERE `doc_base`.`group`='$group_id'
            ORDER BY 'name'");
        while ($pos_info = $res->fetch_assoc()) {
            $user_id = $time = '';
            $show_flag = false;
            if($vars['show_mode']=='na' || $vars['show_mode']=='na_me') {
                $show_flag = true;
            }
            $lr = $db->query("SELECT `user` AS `user_id`, `time`, `motion` FROM `doc_log` "
                . " WHERE `object`='pos' AND `object_id`={$pos_info['id']} "
                . " ORDER BY `id` DESC");
            while($l_line = $lr->fetch_assoc()) {
                if($l_line['motion']!='APPROVE') {
                    break;
                }
                switch($vars['show_mode']) {
                    case 'na_me': {
                        if($GLOBALS['uid']==$l_line['user_id']) {
                            $show_flag = false;
                        }
                        } break;
                    case 'na':
                        $show_flag = false;
                        break;
                    case 'a_me': {
                        if($_SESSION['uid']==$l_line['user_id']) {
                            $show_flag = true;
                        }
                        } break;
                    case 'a':
                        $show_flag = true;
                        break;
                }
                if(!$user_id) {
                    $user_id = $l_line['user_id'];
                    $time = $l_line['time'];
                }
            }
            if($show_flag) {
                $user_name = html_out($vars['users'][$user_id]);
                $tmpl->addContent("<tr><td><a href='/docs.php?l=sklad&mode=srv&opt=ep&pos={$pos_info['id']}&param=v'>{$pos_info['id']}</td><td>".html_out($pos_info['name'])."</td><td>$user_name ($user_id)</td><td>$time</td></tr>");
                $this->count++;
            }
        }
    }
}