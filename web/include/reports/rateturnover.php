<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2017, BlackLight, TND Team, http://tndproject.org
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
/// Отчёт по оборачиваемости товаров
class Report_RateTurnover extends BaseGSReport {

    var $sklad = 0; // ID склада
    var $w_docs = 0; // Отображать документы
    var $div_dt = 0; // Разделить приходы и расходы


    function getName($short = 0) {
        if ($short) {
            return "По оборачиваемости товаров";
        } else {
            return "Отчёт по оборачиваемости товаров";
        }
    }

    function Form() {
        global $tmpl, $db;
        $year = date("Y");
        $d_f = date("Y-m-d", time() - 60 * 60 * 24 * 31);
        $ldo = new \Models\LDO\skladnames();
        $this->storenames = $ldo->getData();
        
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
            <script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
            <form action='' method='post'>
            <input type='hidden' name='mode' value='rateturnover'>
            <fieldset><legend>Год:</legend>
            <input type='text' name='year' value='$year'><br>
            </fieldset>
            <fieldset><legend>Склады</legend>");
        foreach($this->storenames as $store_id => $store_name) {
            $tmpl->addContent("<label><input type='checkbox' name='stores[]' value='$store_id' checked>".html_out($store_name)."</label><br>");
        }
        $tmpl->addContent("</fieldset>
            Не учитывать с ликвидностью менее:<br>
            <input type='text' name='min_liq'><br>
            Фильтр по производителю:<br>
            <input type='text' name='vendor'><br>
            Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
            <button type='submit'>Сформировать отчёт</button>
            </form>

            <script type=\"text/javascript\">
            function dtinit(){initCalendar('dt_f',false);initCalendar('dt_t',false);}

            addEventListener('load',dtinit,false);	
            </script>
            ");
    }
    
    function processPosId($pos_id) {
        global $db;
        settype($pos_id, 'int');
        $start = $end = $out = 0;
        $cnt = $doc = 0;
        $res = $db->query("SELECT `doc_list_pos`.`cnt`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`id`, `doc_list_pos`.`page`, `doc_list`.`date`
            FROM `doc_list_pos`
            LEFT JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
            WHERE  `doc_list`.`ok`>'0' AND `doc_list_pos`.`tovar`=$pos_id AND "
                . " (`doc_list`.`type`=1 OR `doc_list`.`type`=2 OR `doc_list`.`type`=8 OR `doc_list`.`type`=17 OR `doc_list`.`type`=20 OR `doc_list`.`type`=25) AND `doc_list`.`date`<=$this->dt_t
            ORDER BY `doc_list`.`date`");
        while ($nxt = $res->fetch_row()) {
            switch($nxt[1]) {
                case 1:
                    if (in_array($nxt[2], $this->stores) ) {
                        $cnt+=$nxt[0];
                        if($nxt[5]>=$this->dt_f) {
                            $out += $nxt[0];
                        }
                    }
                    break;
                case 2:
                case 20:
                    if (in_array($nxt[2], $this->stores) ) {
                        $cnt-=$nxt[0];
                    }
                    break;
               case 8:
                    if (in_array($nxt[2], $this->stores) ) {
                        $cnt-=$nxt[0];
                    } 
                    {
                        $r = $db->query("SELECT `value` FROM `doc_dopdata` WHERE `doc`=$nxt[3] AND `param`='na_sklad'");
                        if (!$r->num_rows) {
                            throw new \Exception("Cклад назначения в перемещении $nxt[3] не задан");
                        }
                        list($nasklad) = $r->fetch_row();
                        if (!$nasklad) {
                            throw new \Exception("Нулевой склад назначения в перемещении $nxt[3] при проверке на отрицательные остатки");
                        }
                        if (in_array($nasklad, $this->stores) ) {
                            $cnt+=$nxt[0];
                        }
                        $r->free();
                    }
                break;
                case 17:
                    if (in_array($nxt[2], $this->stores) ) {
                        if ($nxt[4] == 0) {
                            $cnt+=$nxt[0];
                        } else {
                            $cnt-=$nxt[0];
                        }
                    }
                    break;
                case 25:
                    if (in_array($nxt[2], $this->stores) ) {
                        $cnt+=$nxt[0];
                    }
                    break;
            }
            $cnt = round($cnt, 3);
            if($nxt[5]<$this->dt_f) {
                $start = $cnt;
            }
        }
        $res->free();
        return array('start' => $start, 'end' => $cnt, 'out' => $out);
    }
    
    function processGroup($group_id, $prefix = '') {
        global $db;
        $this_stat = array('id'=>$group_id, 'name'=>'', 'start'=>0, 'end'=>0, 'out'=>0, 'childs'=>[]);
        $res = $db->query("SELECT `id`, `name` FROM `doc_group` WHERE `pid`='$group_id'");
        $sql_add = ($this->min_liq>0) ? " AND `likvid`>='{$this->min_liq}' ":'';
        if($this->vendor) {
            $sql_add .= " AND `proizv`='".$db->real_escape_string($this->vendor)."'";
        }
        while($g_info = $res->fetch_assoc()) {
            $g_stat = $this->processGroup($g_info['id'], $prefix.'-');
            $g_stat['id'] = $g_info['id'];
            $g_stat['name'] = $g_info['name'];
            $p_res = $db->query("SELECT `id`, `likvid` FROM `doc_base` WHERE `group`='{$g_info['id']}' AND `pos_type`=0 ".$sql_add);
            while($p_info = $p_res->fetch_assoc()) {
                $price = getInCost($p_info['id']);
                $p_cnt = $this->processPosId($p_info['id']);
                $p_start = $p_cnt['start']*$price;
                $p_end = $p_cnt['end']*$price;
                $p_out = $p_cnt['out']*$price;
                $g_stat['start'] += $p_start;
                $g_stat['end'] += $p_end;
                $g_stat['out'] += $p_out;
                $this->sum_start += $p_start;
                $this->sum_end += $p_end;
                $this->sum_out += $p_out;
            }
            //$this->lineOut($g_info['id'], $prefix.$g_info['name'], $g_stat['start'], $g_stat['end'], $g_stat['out'], $g_stat['turnover_rate']); 
            $this_stat['start'] += $g_stat['start'];
            $this_stat['end'] += $g_stat['end'];
            $this_stat['out'] += $g_stat['out'];
            $this_stat['childs'][] = $g_stat;
        }
        $store_avg = $this_stat['start']/2 +$this_stat['end']/2;
        if($store_avg>0) {
            $this_stat['turnover_rate'] = $this_stat['out'] / $store_avg;
        }
        else {
            $this_stat['turnover_rate'] = 0;
        }
        
        return $this_stat;
    }
    
    function lineOut($id, $name, $start, $end, $out, $tr) {
        if($tr>0) {
            $tr_c = number_format(365/$tr, 2, '.',' ')." дней";
        }
        else {
            $tr_c = "";
        }
        $this->tableRow(
            array(
                $id,
                $name, 
                number_format($start, 0, '.',' ')." р.",
                number_format($end, 0, '.',' ')." р.",
                number_format($out, 0, '.',' ')." р.",
                number_format($tr, 2, '.',' '),
                $tr_c,
                )
            ); 
    }
    
    function renderStat($stat, $prefix = '') {
        if($stat['start'] == 0 && $stat['end'] == 0 && $stat['out'] == 0) {
            return;
        }
        if($stat['id']>0) {
            $this->lineOut($stat['id'], $prefix.$stat['name'], $stat['start'], $stat['end'], $stat['out'], $stat['turnover_rate']); 
        }
        foreach ($stat['childs'] as $child) {
            $this->renderStat($child, $prefix.'    ');
        }
    }

    function Make($engine) {
        $this->loadEngine($engine);

        $this->vendor = request('vendor');
        $this->year = rcvint("year");
        $this->dt_f = strtotime($this->year."-01-01");
        $this->dt_t = strtotime($this->year."-12-31 23:59:59");  
        $this->stores = request('stores', []);
        if(!is_array($this->stores)) {
            throw new \Exception("Ошибка списка складов!");
        }
        $this->min_liq = rcvrounded('min_liq');
        
        $header = $this->getName();
        if ($this->dt_f > 1) {
            $header .= ", с " . date('Y-m-d', $this->dt_f);
        }
        $header .= ", по " . date('Y-m-d', $this->dt_t);
        $this->header($header);

        
        $widths = array(5, 40, 10, 10, 10, 10, 15);
        $headers = array('ID', 'Наименование', 'Нач.', 'Конеч.', 'Ушло', 'Коэфф. об-сти', 'Об-сть');

        $this->col_cnt = count($widths);
        $this->tableBegin($widths);
        $this->tableHeader($headers);
        
        $this->sum_start = 0;
        $this->sum_end = 0;
        $this->sum_out = 0;
        
        $stat = $this->processGroup(0);
        $this->renderStat($stat);
        
        $this->tableAltStyle();
        $store_avg = $this->sum_start/2 + $this->sum_end/2;
        if($store_avg) {
            $turnover_rate = $this->sum_out / $store_avg;
        }
        else {
            $turnover_rate = 0;
        }
        $this->lineOut('', 'Итого:', $this->sum_start, $this->sum_end, $this->sum_out, $turnover_rate); 
        $this->tableAltStyle(false);
        /*

        if ($this->div_dt || !$this->w_docs) {
            $this->tableAltStyle();
            $end = $ss['start'] + $ss['prix'] - $ss['real'] - $ss['perem'] - $ss['sbor'] - $ss['korr'];
            if($this->w_docs) {
                $this->tableRow(array('', 'Итого:', '', $end, '', ''));                
            } 
            else {
                $this->tableRow(array('', '', 'Итого:', '', '', '', $ss['prix'], $ss['real'], $ss['perem'], $ss['sbor'], $ss['korr'], $end));
            }
            $this->tableAltStyle(false);
        }*/
        
        $this->tableEnd();
        $this->output();
        exit(0);
    }

}
