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
namespace modules\site;

/// Модуль, реализующий страницу поиска
class search extends \IModule {

    var $search_str;    ///< Искомая строка
    var $nfr_flag = 0;  ///< Флаг неполных результатов поиска

    public function __construct() {
        parent::__construct();
        $this->link_prefix = '/search.php';
        $this->acl_object_name = 'generic.search';
    }

    // Получить название модуля
    /// @return Строка с именем
    public function getName() {
        return 'Поиск по сайту';
    }

    /// Получить описание модуля
    /// @return Строка с описанием
    public function getDescription() {
        return 'Поиск по сайту';
    }

    /// Запустить модуль на исполнение
    public function run() {
        global $tmpl;
        $tmpl->setTitle($this->getName());
        $this->ExecMode(request('mode'));
    }

    /// Задать строку поиска
    /// @param $search_str Искомая строка
    public function setSearchString($search_str) {
        parent::__construct();
        $this->search_str = $search_str;
    }
    
    /// Отобразить страницу поиска
    /// @param $mode вид страницы поиска
    public function ExecMode($mode = '') {
        global $tmpl, $CONFIG, $db;
        $tmpl->addBreadcrumb('Главная', '/');
        $tmpl->addBreadcrumb($this->getName(), $this->link_prefix);
        $tmpl->setContent("<h1>" . $this->getName() . "</h1>");
        $tmpl->setTitle($this->getName());
        if ($mode == '') {
            $this->tryGlobalSearch();
        } else if ($mode == 'goods') {
            $this->tryGoodsSearch();
        } else if ($mode == 'parametric') {
            $this->tryParametricSearch();
        } else {
            throw new \NotFoundException("Неверный $mode");
        }
    }
    
    /// Получить начало строки sql запроса списка товаров
    public function getPosListSqlStr() {
        return "SELECT SQL_CALC_FOUND_ROWS `doc_base`.`id`, `doc_group`.`printname` AS `group_printname`, `doc_base`.`name`, `doc_base`.`proizv` AS `vendor`, 
                `doc_base`.`cost` AS `price`, `doc_base`.`cost_date`, `doc_base`.`mass`, `doc_base_dop`.`transit`, `doc_base`.`analog_group`, `doc_base`.`vc`,
                `doc_base_dop`.`type`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, 
                (SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `cnt`
            FROM `doc_base`
            LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
            LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`";
    }

    /// Поиск товаров
    /// @param $search_str  Подстрока поиска
    /// @param $cnt_limit   Лимит на количество строк в поисковом блоке
    /// @param $sql_add     Дополнительные условия к sql запросу
    function searchGoods($search_str, $cnt_limit = 8, $sql_add = '') {
        global $CONFIG, $db;        
        $s_sql = $db->real_escape_string($search_str);
        $html_s = html_out($search_str);
        settype($cnt_limit, 'int');
        
        $this->nfr_flag = false;
        $tbody = '';
        $found_ids = '0';
        $sql = $this->getPosListSqlStr();
        
        $sqla = $sql . " WHERE (`doc_base`.`name` = '$s_sql' OR `doc_base`.`vc` = '$s_sql') $sql_add
            AND `doc_base`.`hidden`='0' AND `doc_group`.`hidelevel`='0'
            ORDER BY `doc_base`.`name`
            LIMIT $cnt_limit";
        $res = $db->query($sqla);
        if ($res->num_rows) {
            $rows_res = $db->query("SELECT FOUND_ROWS()");
            list($found_cnt) = $rows_res->fetch_row();
            if ($res->num_rows < $found_cnt) {
                $found_info = "показано {$res->num_rows} из $found_cnt найденных";
                $this->nfr_flag = true;
            } else {
                $found_info = "{$res->num_rows} совпадений найдено";
            }
            $tbody .= "<tr><th colspan='20' align='center'>Поиск совпадений с $html_s - $found_info</th></tr>";
            $groups_analog_list = '';
            while ($line = $res->fetch_assoc()) {
                $tbody .= $this->drawTableLine($line, $search_str);
                $found_ids.=',' . $line['id'];
                if ($line['analog_group']) {
                    if ($groups_analog_list) {
                        $groups_analog_list.=',';
                    }
                    $groups_analog_list.="'" . $db->real_escape_string($line['analog_group']) . "'";
                }
            }
            if ($groups_analog_list) {
                $sqla = $sql . "WHERE `doc_base`.`id` NOT IN ($found_ids) AND `doc_base`.`analog_group` IN ($groups_analog_list) $sql_add"
                    . " AND `doc_base`.`name` != '$s_sql' AND `doc_base`.`vc` = !'$s_sql'"
                    . " AND `doc_base`.`hidden`='0' AND `doc_group`.`hidelevel`='0'"
                    . " ORDER BY `doc_base`.`name`"
                    . " LIMIT $cnt_limit";
                $res = $db->query($sqla);
                if ($res->num_rows) {
                    $rows_res = $db->query("SELECT FOUND_ROWS()");
                    list($found_cnt) = $rows_res->fetch_row();
                    if ($res->num_rows < $found_cnt) {
                        $found_info = "показано {$res->num_rows} из $found_cnt найденных";
                        $this->nfr_flag = true;
                    } else {
                        $found_info = "{$res->num_rows} совпадений найдено";
                    }
                    $tbody .= "<tr><th colspan='8' align='center'>Поиск аналогов $html_s - $found_info</th></tr>";
                    $groups_analog_list = '';
                    while ($line = $res->fetch_assoc()) {
                        $tbody .= $this->drawTableLine($line, $search_str);
                        $found_ids.=',' . $line['id'];
                    }
                }
            }
            $sf = 1;
        }

        $sqla = $sql . "WHERE (`doc_base`.`name` LIKE '$s_sql%' OR `doc_base`.`vc` LIKE '$s_sql%') $sql_add AND `doc_base`.`id` NOT IN ($found_ids)"
            . " AND `doc_base`.`hidden`='0' AND `doc_group`.`hidelevel`='0'"
            . " ORDER BY `doc_base`.`name`"
            . " LIMIT $cnt_limit";
        $res = $db->query($sqla);
        if ($res->num_rows) {
            $rows_res = $db->query("SELECT FOUND_ROWS()");
            list($found_cnt) = $rows_res->fetch_row();
            if ($res->num_rows < $found_cnt) {
                $found_info = "показано {$res->num_rows} из $found_cnt найденных";
                $this->nfr_flag = true;
            } else {
                $found_info = "{$res->num_rows} совпадений найдено";
            }
            $tbody .= "<tr><th colspan='8' align='center'>Поиск по названию, начинающемуся на $html_s - $found_info</th></tr>";
            $groups_analog_list = '';
            while ($line = $res->fetch_assoc()) {
                $tbody .= $this->drawTableLine($line, $search_str);
                $found_ids.=',' . $line['id'];
                if ($line['analog_group']) {
                    if ($groups_analog_list) {
                        $groups_analog_list.=',';
                    }
                    $groups_analog_list.="'" . $db->real_escape_string($line['analog_group']) . "'";
                }
            }
            if ($groups_analog_list) {
                $sqla = $sql . "WHERE `doc_base`.`id` NOT IN ($found_ids) AND `doc_base`.`analog_group` IN ($groups_analog_list) $sql_add"
                    . " AND `doc_base`.`name` NOT LIKE '$s_sql%' AND `doc_base`.`vc` NOT LIKE '$s_sql%'"
                    . " AND `doc_base`.`hidden`='0' AND `doc_group`.`hidelevel`='0'"
                    . " ORDER BY `doc_base`.`name`"
                    . " LIMIT $cnt_limit";
                $res = $db->query($sqla);
                if ($res->num_rows) {
                    $rows_res = $db->query("SELECT FOUND_ROWS()");
                    list($found_cnt) = $rows_res->fetch_row();
                    if ($res->num_rows < $found_cnt) {
                        $found_info = "показано {$res->num_rows} из $found_cnt найденных";
                        $this->nfr_flag = true;
                    } else {
                        $found_info = "{$res->num_rows} совпадений найдено";
                    }
                    $tbody .= "<tr><th colspan='8' align='center'>И их аналоги - $found_info</th></tr>";
                    $groups_analog_list = '';
                    while ($line = $res->fetch_assoc()) {
                        $tbody .= $this->drawTableLine($line, $search_str);
                        $found_ids.=',' . $line['id'];
                    }
                }
            }
            $sf = 1;
        }

        $sqla = $sql . "WHERE (`doc_base`.`name` LIKE '%$s_sql%' OR `doc_base`.`vc` LIKE '%$s_sql%') $sql_add"
            . " AND `doc_base`.`hidden`='0' AND `doc_group`.`hidelevel`='0'"
            . " AND `doc_base`.`name` NOT LIKE '$s_sql%' AND `doc_base`.`vc` NOT LIKE '$s_sql%'"
            . " AND `doc_base`.`id` NOT IN ($found_ids) "
            . " ORDER BY `doc_base`.`name`"
            . " LIMIT $cnt_limit";
        $res = $db->query($sqla);
        if ($cnt = $res->num_rows) {
            $rows_res = $db->query("SELECT FOUND_ROWS()");
            list($found_cnt) = $rows_res->fetch_row();
            if ($res->num_rows < $found_cnt) {
                $found_info = "показано {$res->num_rows} из $found_cnt найденных";
                $this->nfr_flag = true;
            } else {
                $found_info = "{$res->num_rows} совпадений найдено";
            }
            $tbody .= "<tr><th colspan='8' align='center'>Наименования, содержащие $html_s - $found_info</th></tr>";
            $groups_analog_list = '';
            while ($line = $res->fetch_assoc()) {
                $tbody .= $this->drawTableLine($line, $search_str);
                $found_ids.=',' . $line['id'];
            }
        }

        if ($tbody) {
            $tbody = "<table width='100%' cellspacing='0' border='0' class='list'>"
                . "<tr><th>Наименование</th><th>Наличие</th><th>Цена, руб</th><th>d, мм</th><th>D, мм</th><th>B, мм</th><th>m, кг</th><th>&nbsp;</th></tr>"
                . $tbody
                . "</table>";
            if (@$CONFIG['site']['grey_price_days']) {
                $tbody.="<span style='color:#888'>Серая цена</span> требует уточнения<br>";
            }
        }
        return $tbody;
    }
    
    /// Параметрический поиск товаров
    /// @param $params Поисковые параметры
    function searchGoodsParametric($params) {
        global $CONFIG, $db;        
        $cnt_limit = 100;
        //settype($cnt_limit, 'int');
        
        $this->nfr_flag = false;
        $tbody = '';
        $found_ids = '0';
        $add_where_sql = '';
        $search_str = '';
        
        foreach($params as $name => $value) {
            if(strlen($value)==0) {
                continue;
            }
            $sql_val = $db->real_escape_string($value);
            switch($name) {
                case 'name':
                    $search_str = $value;
                    break;
                case 'vendor':
                    $add_where_sql .= " AND `doc_base`.`proizv` LIKE '%$sql_val%'";
                    break;
                case 'type':
                    $add_where_sql .= " AND `doc_base`.`type` = '$sql_val'";
                    break;
                case 'd_int_min':
                    $add_where_sql .= " AND `doc_base_dop`.`d_int` >= '$sql_val'";
                    break;
                case 'd_int_max':
                    $add_where_sql .= " AND `doc_base_dop`.`d_int` <= '$sql_val'";
                    break;
                case 'd_ext_min':
                    $add_where_sql .= " AND `doc_base_dop`.`d_ext` >= '$sql_val'";
                    break;
                case 'd_ext_max':
                    $add_where_sql .= " AND `doc_base_dop`.`d_ext` <= '$sql_val'";
                    break;
                case 'size_min':
                    $add_where_sql .= " AND `doc_base_dop`.`size` >= '$sql_val'";
                    break;
                case 'size_max':
                    $add_where_sql .= " AND `doc_base_dop`.`size` <= '$sql_val'";
                    break;
                case 'mass_min':
                    $add_where_sql .= " AND `doc_base`.`mass` >= '$sql_val'";
                    break;
                case 'mass_max':
                    $add_where_sql .= " AND `doc_base`.`mass` <= '$sql_val'";
                    break;
                case 'price_min':
                    $add_where_sql .= " AND `doc_base`.`cost` >= '$sql_val'";
                    break;
                case 'price_max':
                    $add_where_sql .= " AND `doc_base`.`cost` <= '$sql_val'";
                    break;
            }
        }
        
        $html_s = html_out($search_str);
        $s_sql = $db->real_escape_string($search_str);
        $sql = $this->getPosListSqlStr();
        
        if($search_str) {
            $tbody = $this->searchGoods($search_str, 1000, $add_where_sql);
        } else {
            $sqla = $sql . "WHERE 1 ".$add_where_sql
                . " AND `doc_base`.`hidden`='0' AND `doc_group`.`hidelevel`='0'"
                . " ORDER BY `doc_base`.`name`"
                . " LIMIT $cnt_limit";
            $res = $db->query($sqla);
            if ($cnt = $res->num_rows) {
                $rows_res = $db->query("SELECT FOUND_ROWS()");
                list($found_cnt) = $rows_res->fetch_row();
                if ($res->num_rows < $found_cnt) {
                    $found_info = "показано {$res->num_rows} из $found_cnt найденных";
                    $this->nfr_flag = true;
                } else {
                    $found_info = "{$res->num_rows} совпадений найдено";
                }
                $tbody .= "<tr><th colspan='8' align='center'>Параметрический поиск - $found_info</th></tr>";
                $groups_analog_list = '';
                while ($line = $res->fetch_assoc()) {
                    $tbody .= $this->drawTableLine($line, $search_str);
                    $found_ids.=',' . $line['id'];
                }
            }
            if ($tbody) {
                $tbody = "<table width='100%' cellspacing='0' border='0' class='list'>"
                    . "<tr><th>Наименование</th><th>Наличие</th><th>Цена, руб</th><th>d, мм</th><th>D, мм</th><th>B, мм</th><th>m, кг</th><th>&nbsp;</th></tr>"
                    . $tbody
                    . "</table>";
                if (@$CONFIG['site']['grey_price_days']) {
                    $tbody.="<span style='color:#888'>Серая цена</span> требует уточнения<br>";
                }
            }
        }
        


        
        return $tbody;
    }
    
    /// Сформировать строку таблицы найденных товаров
    function drawTableLine($line, $s) {
        global $CONFIG;
        if (@$CONFIG['site']['grey_price_days']) {
            $cce_time = $CONFIG['site']['grey_price_days'] * 60 * 60 * 24;
        }
        $basket_img = "/skins/" . $CONFIG['site']['skin'] . "/basket16.png";
        $pc = \PriceCalc::getInstance();
        $pref = \pref::getInstance();
        $pc->setFirmId($pref->getSitePref('default_firm_id'));
        $ret = '';

        if ($CONFIG['site']['recode_enable']) {
            $link = "/vitrina/ip/{$line['id']}.html";
        } else {
            $link = "/vitrina.php?mode=product&amp;p={$line['id']}";
        }

        $cost = $pc->getPosDefaultPriceValue($line['id']);
        if ($cost <= 0) {
            $cost = 'уточняйте';
        }
        $nal = $this->GetCountInfo($line['cnt'], $line['transit']);

        $cce = '';
        if (@$CONFIG['site']['grey_price_days']) {
            if (strtotime($line['cost_date']) < $cce_time) {
                $cce = ' style=\'color:#888\'';
            }
        }
        $name = $line['name'];
        if ($line['vc']) {
            $name .= ' - ' . $line['vc'];
        }
        if ($line['group_printname']) {
            $name = $line['group_printname'] . ' ' . $name;
        }
        if ($line['vendor']) {
            $name .= ' / ' . $line['vendor'];
        }
        $name = SearchHilight(html_out($name), $s);
        $ret .= "<tr><td><a href='$link'>$name</a></td><td>$nal</td><td $cce>$cost</td><td>{$line['d_int']}</td><td>{$line['d_ext']}</td><td>{$line['size']}</td>"
            . "<td>{$line['mass']}</td><td><a href='/vitrina.php?mode=korz_add&amp;p={$line['id']}&amp;cnt=1'"
            . " onclick=\"ShowPopupWin('/vitrina.php?mode=korz_add&amp;p={$line['id']}&amp;cnt=1','popwin'); return false;\""
            . " rel='nofollow'><img src='$basket_img' alt='В корзину!'></a></td></tr>";
        return $ret;
    }

    /// Поиск по статьям
    /// @param $s Подстрока поиска
    function searchArticles($s) {
        global $db;
        $ret = '';
        $wikiparser = new \WikiParser();
        $s_sql = $db->real_escape_string($s);
        $res = $db->query("SELECT `name`, `text` FROM `articles` WHERE `text` LIKE '%$s_sql%' OR `name` LIKE '%$s_sql%'");
        while ($nxt = $res->fetch_row()) {
            $text = $wikiparser->parse($nxt[1]);
            $head = $wikiparser->title;
            if ($head == '') {
                $head = $nxt[0];
            }
            $text = strip_tags($text);
            $size = 130;
            $text = ". $text .";
            $pos = mb_stripos($text, $s);
            if ($pos === FALSE) {
                $pos = 0;
            }
            $start = $pos - $size;
            if ($start < 0) {
                $start = 0;
            }
            $width = $size * 2;
            $str = mb_substr($text, $start, $width);
            $str_array = mb_split(' ', $str);
            $c = '';
            $str = '... ';
            foreach ($str_array as $id => $elem) {
                if ($id == 0) {
                    continue;
                }
                $str.=$c . ' ';
                $c = $elem;
            }
            $str.=" ...";
            $str = mb_eregi_replace($s, "<b>$s</b>", $str);
            $ret.="<li><a href='/articles/$nxt[0].html'>" . html_out($head) . "</a><br>$str</li>";
        }
        if($ret) {
            $ret = "<ol class='items'>$ret</ol>";
        }
        return $ret;
    }

    /// Формирование html кода формы глобального поиска
    function getGlobalSearchForm() {
        $ret = "<div class='searchblock'><form action='{$this->link_prefix}' method='get'>
            <input type='search' name='s' placeholder='Искать..' value='" . html_out($this->search_str) . "' class='sp' require> 
            <input type='submit' value='Найти'><br>
            <a href='{$this->link_prefix}?mode=parametric&amp;param[name]=" . html_out($this->search_str) . "'>Параметрический поиск</a>
            </form>
            </div>";
        return $ret;
    }
    
    /// Формирование html кода формы поиска по товарам
    function getGoodsSearchForm() {
        $ret = "<div class='searchblock'><form action='{$this->link_prefix}' method='get'>
            <input type='hidden' name='mode' value='goods'>
            <input type='search' name='s' placeholder='Искать..' value='" . html_out($this->search_str) . "' class='sp' require> 
            <input type='submit' value='Найти'><br>
            <a href='{$this->link_prefix}?mode=parametric&amp;param[name]=" . html_out($this->search_str) . "'>Параметрический поиск</a>
            </form>
            </div>";
        return $ret;
    }
    
    /// Формирование html кода текстового поля ввода формы параметрического поиска
    /// @param $name    Имя поля ввода
    /// @param $fields  Данные с значениями полей формы
    function getField($name, $fields) {
        return "<input type='text' name='param[$name]' value='{$fields[$name]}'>";
    }
        
    /// Формирование html кода формы параметрического поиска
    /// @param $form_data  Данные с значениями полей формы
    function getParametricSearchForm($form_data = []) {
        $fields = array(
            'name' => '',
            'vendor' => '',
            'type' => '',
            'd_int_min' => '',
            'd_int_max' => '',
            'd_ext_min' => '',
            'd_ext_max' => '',            
            'size_min' => '',
            'size_max' => '',
            'mass_min' => '',
            'mass_max' => '',
            'price_min' => '',
            'price_max' => '',
        );
        foreach ($fields as $id => &$field) {
            if(isset($form_data[$id])) {
                $field = html_out($form_data[$id]);
            }
        }
        $ret = "<div class='param_search_block'><form action='{$this->link_prefix}' method='get'>
        <input type='hidden' name='mode' value='parametric'>
        <input type='hidden' name='opt' value='go'>
        <table width='100%' class='list'>
        <tr><th colspan='2'>Наименование</th><th colspan='2'>Производитель</th><th>Тип</th></tr>
        <tr>
            <td colspan='2'>".$this->getField('name', $fields)."</td>
            <td colspan='2'>".$this->getField('vendor', $fields)."</td>
            <td>".$this->getField('type', $fields)."</td>
        </tr>
        <tr><th>Внутренний диаметр</th><th>Внешний диаметр</th><th>Высота</th><th>Масса</th><th>Цена</th></tr>
        <tr>
            <td>От:&nbsp;".$this->getField('d_int_min', $fields)."</td>
            <td>От:&nbsp;".$this->getField('d_ext_min', $fields)."</td>
            <td>От:&nbsp;".$this->getField('size_min', $fields)."</td>
            <td>От:&nbsp;".$this->getField('mass_min', $fields)."</td>
            <td>От:&nbsp;".$this->getField('price_min', $fields)."</td>
        </tr>
        <tr>
            <td>До:&nbsp;".$this->getField('d_int_max', $fields)."</td>
            <td>До:&nbsp;".$this->getField('d_ext_max', $fields)."</td>
            <td>До:&nbsp;".$this->getField('size_max', $fields)."</td>
            <td>До:&nbsp;".$this->getField('mass_max', $fields)."</td>
            <td>До:&nbsp;".$this->getField('price_max', $fields)."</td>
        </tr>
        <tr>
            <td colspan='5' class='sbutline' align='center'><button type='submit'>Найти</button>
        </table>
        </form>
        </div>";
        return $ret;
    }

    /// Выполнить поиск по сайту
    function tryGlobalSearch() {
        global $tmpl;
        
        if (!$this->search_str) {
            $tmpl->setTitle($this->getName());
            $tmpl->addBreadcrumb($this->getName(), '');
            $tmpl->setContent("<h1>".$this->getName()."</h1>");
            $tmpl->addContent($this->getGlobalSearchForm()); 
        } else {
            $tmpl->setTitle("Ищем: ".html_out($this->search_str));
            $tmpl->addBreadcrumb("Ищем: ".html_out($this->search_str), '');
            $tmpl->setContent("<h1>Ищем: ".html_out($this->search_str)."</h1>");
            $tmpl->addContent($this->getGlobalSearchForm());
            $tmpl->addContent("<h2>Поиск в товарах</h2>");
            $str = $this->searchGoods($this->search_str);
            if ($str) {
                $tmpl->addContent($str . "<p><a href='{$this->link_prefix}?mode=goods&amp;s=" . html_out($this->search_str) 
                    . "'>Ещё товары по запросу *" . html_out($this->search_str) . "* &gt;&gt;&gt;</a></p>");
            } else {
                $tmpl->addContent("Не дал результатов");
            }

            $str = $this->searchArticles($this->search_str);
            $tmpl->addContent("<h2>Поиск в статьях</h2>");
            if ($str) {
                $tmpl->addContent($str);
            } else {
                $tmpl->addContent("Не дал результатов");
            }
        }        
    }
    
    /// Выполнить поиск по товарам
    function tryGoodsSearch() {
        global $tmpl;
        
        if (!$this->search_str) {
            $tmpl->setTitle($this->getName());
            $tmpl->addBreadcrumb($this->getName(), '');
            $tmpl->setContent("<h1>".$this->getName()."</h1>");
            $tmpl->addContent($this->getGoodsSearchForm()); 
        } else {
            $tmpl->setTitle("Ищем: ".html_out($this->search_str));
            $tmpl->addBreadcrumb("Ищем: ".html_out($this->search_str), '');
            $tmpl->setContent("<h1>Ищем: ".html_out($this->search_str)."</h1>");
            $tmpl->addContent($this->getGoodsSearchForm());
            $str = $this->searchGoods($this->search_str, 1000);
            if ($str) {
                $tmpl->addContent($str);
                if($this->nfr_flag) {
                    $tmpl->addContent("<p><a href='{$this->link_prefix}?mode=goods&amp;s=" . html_out($this->search_str) 
                        . "'>Ещё товары по запросу *" . html_out($this->search_str) . "* &gt;&gt;&gt;</a></p>");
                }
            } else {
                $tmpl->addContent("Не дал результатов");
            }
        }        
    }
    
    /// Выполнить поиск по товарам
    function tryParametricSearch() {
        global $tmpl;
        $opt = request('opt');
        $params = @$_REQUEST['param'];
        $name = "Параметрический поиск";
        if (!$opt && !$params) {            
            $tmpl->setTitle($name);
            $tmpl->addBreadcrumb($name, '');
            $tmpl->setContent("<h1>$name</h1>");
            $tmpl->addContent($this->getParametricSearchForm()); 
        } else {
            $tmpl->setTitle("$name: ".html_out($this->search_str));
            $tmpl->addBreadcrumb("$name: ".html_out($this->search_str), '');
            $tmpl->setContent("<h1>$name: ".html_out($this->search_str)."</h1>");
            $tmpl->addContent($this->getParametricSearchForm($params));
            $str = $this->searchGoodsParametric($params);
            if ($str) {
                $tmpl->addContent($str);
            } else {
                $tmpl->addContent("Не дал результатов");
            }
        }        
    }

    /// Получить отображаемую информацию о количестве товара
    /// @param $count Количество товара в наличиии
    /// @param $transit Количество товара в пути
    /// @return Строка с информацией о наличии
    protected function GetCountInfo($count, $transit) {
        global $CONFIG;
        if (!isset($CONFIG['site']['vitrina_pcnt_limit'])) {
            $CONFIG['site']['vitrina_pcnt_limit'] = [1, 10, 100];
        }
        if ($CONFIG['site']['vitrina_pcnt'] == 1) {
            if ($count <= 0) {
                if ($transit) {
                    return 'в пути';
                } else {
                    return 'уточняйте';
                }
            }
            else if ($count <= $CONFIG['site']['vitrina_pcnt_limit'][0]) {
                return '*';
            } else if ($count <= $CONFIG['site']['vitrina_pcnt_limit'][1]) {
                return '**';
            } else if ($count <= $CONFIG['site']['vitrina_pcnt_limit'][2]) {
                return '***';
            } else {
                return '****';
            }
        }
        else if ($CONFIG['site']['vitrina_pcnt'] == 2) {
            if ($count <= 0) {
                if ($transit) {
                    return 'в пути';
                } else {
                    return 'уточняйте';
                }
            } else if ($count <= $CONFIG['site']['vitrina_pcnt_limit'][0]) {
                return 'мало';
            } else if ($count <= $CONFIG['site']['vitrina_pcnt_limit'][1]) {
                return 'есть';
            } else if ($count <= $CONFIG['site']['vitrina_pcnt_limit'][2]) {
                return 'много';
            } else {
                return 'оч.много';
            }
        } else {
            return round($count) . ($transit ? ('/' . $transit) : '');
        }
    }

}
