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

/// Остатки товара на складе на выбранную дату
class Report_OstatkiNaDatu extends BaseGSReport {
    
    protected $group_filter;
    protected $col_cfg;
    protected $orders = [
            'name' => 'По наименованию',
            'code' => 'По коду производителя',
            'base_price' => 'По базовой цене',
        ];
    
    function getName($short = 0) {
        if ($short) {
            return "Остатки на выбранную дату";
        } else {
            return "Остатки товара на складе на выбранную дату";
        }
    }

    function Form() {
        global $tmpl, $db;
        $curdate = date("Y-m-d");
        $def_order = \cfg::get('doc', 'sklad_default_order');
        if($def_order == 'vc') {
            $def_order = 'code';
        }
        else if($def_order == 'cost') {
            $def_order = 'base_price';
        }
        
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>");
        $tmpl->addContent("
            <script type=\"text/javascript\">
            addEventListener('load', function() {initCalendar('dt',false);},false);
            </script>
            <form action='' method='post'>
            <input type='hidden' name='mode' value='ostatkinadatu'>
            Дата:<br>
            <input type='text' name='date' id='dt' value='$curdate'><br>
            ");
        $ldo = new \Models\LDO\skladnames();
        $store_names = $ldo->getData();        
        $tmpl->addContent("Склад:<br>". \widgets::getEscapedSelect('store', $store_names) ."<br>" );
        $tmpl->addContent("Сортировка:<br>". \widgets::getEscapedSelect('order', $this->orders, $def_order) ."<br>" );
        $tmpl->addContent("Группа товаров:<br>");
        $this->GroupSelBlock();
        $tmpl->addContent("<label><input type='checkbox' name='group_vendor' value='1'>Группировать по производителю</labe><br>
            Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>            
            <button type='submit'>Создать отчет</button></form>");
    }
    
    protected function setColumn($id, $name, $width, $flexible = false) {
        $this->col_cfg[$id] = [
            'name' => $name,
            'width' => $width,
            'flex' => $flexible,
            'hidden' => false,
        ];
    }
    
    protected function fixColumnWidth($all_width = 100) {
        $sum_width_flex = $sum_width_noflex = 0;
        $flex = [];
        foreach($this->col_cfg as $id => $col) {
            if($col['hidden']) {
                continue;
            }
            if($col['flex']) {
                $sum_width_flex += $col['width'];
                $flex[] = $id;
            }
            else {
                $sum_width_noflex += $col['width'];
            }
        }
        $all_sum = $sum_width_noflex + $sum_width_flex;
        if($all_width == $all_sum) {
            return;
        }
        else {
            $flex_mod = ($all_width - $sum_width_noflex) / $sum_width_flex;
            foreach($this->col_cfg as $id => $col) {
                if($col['flex']) {
                    $this->col_cfg[$id]['width'] *= $flex_mod;
                }
            }
        }   
        
    }
    
    protected function groupsMakeTable($options) {
        global $db;
        $c_count = 0;
        foreach($this->col_cfg as $col) {
            if(!$col['hidden']) {
                $c_count++;
            }
        } 
        switch ($options['order']) {
            case 'code': 
                $order = '`doc_base`.`vc`';
                break;
            case 'base_price': 
                $order = '`doc_base`.`cost`';
                break;
            default: 
                $order = '`doc_base`.`name`';
        }
        
        $res_group = $db->query("SELECT `id`, `name` FROM `doc_group` ORDER BY `vieworder`,`name`");
        while ($group_line = $res_group->fetch_assoc()) {
            if ($this->group_filter && !in_array($group_line['id'], $this->group_filter)) {
                continue;
            }

            $this->tableAltStyle();
            $this->tableSpannedRow(array($c_count), array(html_out("{$group_line['id']}. {$group_line['name']}")));
            $this->tableAltStyle(false);

            $res = $db->query("SELECT `doc_base`.`id`, CONCAT(`doc_base`.`name`, ' - ', `doc_base`.`proizv`) AS `name` , `doc_base`.`cost`,
                    `doc_base`.`mass`, `doc_base`.`vc`
                FROM `doc_base`
                WHERE `doc_base`.`group`='{$group_line['id']}'
                ORDER BY $order");
            while ($nxt = $res->fetch_row()) {
                $count = getStoreCntOnDate($nxt[0], $options['store_id'], $this->time, 1);
                if ($count == 0) {
                    continue;
                }
                if ($count < 0) {
                    $this->zeroflag = 1;
                }
                $cost_p = sprintf("%0.2f", $nxt[2]);
                $bsum_p = sprintf("%0.2f", $nxt[2] * $count);
                $count_p = round($count, 3);
                $this->bsum+=$nxt[2] * $count;
                $this->summass+=$count * $nxt[3];

                if (!$this->col_cfg['code']['hidden']) {
                    $a = array($nxt[0], $nxt[4], $nxt[1], $count_p, $cost_p, $count * $nxt[3], $bsum_p);
                } else {
                    $a = array($nxt[0], $nxt[1], $count_p, $cost_p, $count * $nxt[3], $bsum_p);
                }
                $this->tableRow($a);
            }
        }
    }
    
    protected function tableSort($a, $b) {
        return strcmp($a['name'], $b['name']);
    }

    protected function vendorMakeTable($options) {
        global $db;
        $c_count = 0;
        foreach($this->col_cfg as $col) {
            if(!$col['hidden']) {
                $c_count++;
            }
        } 
        $res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`proizv` AS `name` , `doc_base`.`cost` AS `base_price`,
                `doc_base`.`mass`
            FROM `doc_base`");
        $data = [];
        while ($line = $res->fetch_assoc()) {
            $id = $line['name'];
            /*if($id === null) {
                continue;
            }*/
            $count = getStoreCntOnDate($line['id'], $options['store_id'], $this->time, 1);
            if ($count == 0) {
                continue;
            }
            if ($count < 0) {
                $this->zeroflag = 1;
            }
            
            if(!isset($data[$id])) {
                $data[$id] = [
                    'name' => $id,
                    'mass' => 0,
                    'count' => 0,
                    'sum' => 0,
                ];
            }
            $data[$id]['count'] += $count;
            $data[$id]['mass'] += $count * $line['mass'];
            $data[$id]['sum'] += $count * $line['base_price'];
        }
        
        uasort($data, [$this, 'tableSort']);
        
        foreach($data as $line) {            
            $sum_p = sprintf("%0.2f", $line['sum']);
            $count_p = round($line['count'], 3);
            $this->bsum += $line['sum'];
            $this->summass += $line['mass'];
            $a = array($line['name'], $count_p, $line['mass'], $sum_p);
            $this->tableRow($a);
        }
        
    }
            
    function Make($engine) {
        global $db, $CONFIG;
        $store = rcvint('store');
        $date = rcvdate('date');        
        $gs = request('gs');
        $g = request('g');
        $group_vendor = rcvint('group_vendor');
        $order = request('order');
        
        // Prepare options
        $this->time = strtotime($date . " 23:59:59");        
        if(!in_array($order, $this->orders)) {
            $order = 'name';
        }
        $this->group_filter = false;
        if($g && is_array($gs)) {
            $this->group_filter = $gs;
        }        
        
        $res = $db->query("SELECT `name` FROM `doc_sklady` WHERE `id`='$store'");
        if (!$res->num_rows) {
            throw new \Exception("Склад не найден!");
        }
        list($sklad_name) = $res->fetch_row();

        $this->loadEngine($engine);
        
        $this->setColumn('id', 'N', 7);
        $this->setColumn('code', 'Код', 7);
        $this->setColumn('name', 'Наименование', 60, true);
        $this->setColumn('count', 'Кол-во', 9);
        $this->setColumn('base_price', 'Б. цена', 9);
        $this->setColumn('mass', 'Масса', 9);
        $this->setColumn('sum', 'Сумма', 9);       
        
        $this->col_cfg['code']['hidden'] = !\cfg::get('poseditor', 'vc');
        
        $h_text = "Остатки товара на складе N$store ($sklad_name) на дату ".date("Y-m-d", $this->time);
        if($group_vendor) {
            $h_text .= ", сгруппированный по производителю";
            $this->col_cfg['id']['hidden'] = true;
            $this->col_cfg['code']['hidden'] = true;
            $this->col_cfg['base_price']['hidden'] = true;
            $this->col_cfg['name']['name'] = 'Производитель';
        }
        $this->header($h_text);
        
        $this->fixColumnWidth();
        
        $c_widths = [];
        $c_captions = [];
        $c_count = 0;
        foreach($this->col_cfg as $col) {
            if(!$col['hidden']) {
                $c_widths[] = $col['width'];
                $c_captions[] = $col['name'];
                $c_count++;
            }
        }  
        $this->tableBegin($c_widths);
        $this->tableHeader($c_captions);

        $this->zeroflag = $this->bsum = $this->summass = 0;
        if($group_vendor) {
            $this->vendorMakeTable( [
                'order' => $order,
                'store_id' => $store,
            ]);
        }
        else {
            $this->groupsMakeTable( [
                'order' => $order,
                'store_id' => $store,
            ]);
        }
        
        $this->bsum = sprintf("%0.2f", $this->bsum);
        $this->tableAltStyle();
        $this->tableSpannedRow(array($c_count - 1, 1), array('Итого:', $this->bsum));
        if (!$this->zeroflag) {
            $this->tableSpannedRow(array($c_count), array("Общая масса склада: $this->summass кг."));
        } else {
            $this->tableSpannedRow(array($c_count), array("Общая масса склада: невозможно определить из-за отрицательных остатков"));
        }
        $this->tableAltStyle(false);
        $this->tableEnd();
        $this->output();
        exit(0);
    }

}
