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
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
            <script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
            <form action='' method='post'>
            <input type='hidden' name='mode' value='rateturnover'>
            <fieldset><legend>Год:</legend>
            <input type='text' name='year' value='$year'><br>
            </fieldset>

            Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
            <button type='submit'>Сформировать отчёт</button>
            </form>

            <script type=\"text/javascript\">
            function dtinit(){initCalendar('dt_f',false);initCalendar('dt_t',false);}

            addEventListener('load',dtinit,false);	
            </script>
            ");
    }

    function dividedOutPos($pos_info, $dt_f, $dt_t, $subtype) {
        global $db;
        $start_cnt = getStoreCntOnDate($pos_info['id'], $this->sklad, $dt_f, 1);
        $s_where = '';
        $prix_cnt = $prix_sum = $r_cnt = $r_sum = 0;
        if ($subtype) {
            $s_where = " AND `doc_list`.`subtype` = '" . $db->real_escape_string($subtype) . "'";
        }
        if ($this->w_docs) {
            $this->tableSpannedRow(array($this->col_cnt), array("{$pos_info['vc']} {$pos_info['name']} ({$pos_info['id']})"));
            $this->tableRow(array('', 'На начало периода', '', $start_cnt, '', ''));
            $this->tableAltStyle();
            $this->tableSpannedRow(array($this->col_cnt), array('Приходы'));
            $this->tableAltStyle(false);
        }
        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`agent`, `doc_agent`.`name` AS `agent_name`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `ns`.`value` AS `na_sklad`, `doc_sklady`.`name` AS `sklad_name`, `doc_types`.`name` AS `doc_name`, `doc_list`.`date`, CONCAT(`doc_list`.`altnum`, `doc_list`.`subtype`) AS `snum`
            FROM `doc_list_pos`
            INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
            INNER JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
            LEFT JOIN `doc_dopdata` AS `ns` ON `ns`.`doc`=`doc_list_pos`.`doc` AND `ns`.`param`='na_sklad'
            LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
            LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
            WHERE `doc_list_pos`.`tovar`='{$pos_info['id']}' AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<'$dt_t' AND (
                (`doc_list`.`type`='1' AND `doc_list`.`sklad`='{$this->sklad}')
                OR (`doc_list`.`type`='8' AND `ns`.`value`='{$this->sklad}')
                OR (`doc_list`.`type`='17' AND `doc_list`.`sklad`='{$this->sklad}' AND `doc_list_pos`.`page`='0')
                OR (`doc_list`.`type`='25' AND `doc_list`.`sklad`='{$this->sklad}' AND `doc_list_pos`.`cnt`>'0')
            ) 
            AND `doc_list`.`ok`>0
            $s_where
            ORDER BY `doc_list`.`date`");
        
        while ($nxt = $res->fetch_assoc()) {
            $from = 'Сборка';
            if ($nxt['type'] == 1) {
                $from = $nxt['agent_name'];
            } else if ($nxt['type'] == 8) {
                $from = $nxt['sklad_name'];
            }
            $date = date("Y-m-d H:i:s", $nxt['date']);
            $sumline = $nxt['cnt'] * $nxt['cost'];
            if ($this->w_docs) {
                $this->tableRow(array($date, "{$nxt['doc_name']} {$nxt['snum']} ({$nxt['id']})", $from, $nxt['cnt'], $nxt['cost'], $sumline));
            }
            $prix_cnt+=$nxt['cnt'];
            $prix_sum+=$sumline;
        }
        if ($this->w_docs) {
            $this->tableRow(array('', 'Всего приход:', '', $prix_cnt, '', $prix_sum));
        }       
                
        if ($this->w_docs) {
            $this->tableAltStyle();
            $this->tableSpannedRow(array($this->col_cnt), array('Реализации'));
            $this->tableAltStyle(false);
        }
        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`agent`, `doc_agent`.`name` AS `agent_name`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `ns`.`value` AS `na_sklad`, `doc_sklady`.`name` AS `sklad_name`, `doc_types`.`name` AS `doc_name`, `doc_list`.`date`, CONCAT(`doc_list`.`altnum`, `doc_list`.`subtype`) AS `snum`
            FROM `doc_list_pos`
            INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
            INNER JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
            LEFT JOIN `doc_dopdata` AS `ns` ON `ns`.`doc`=`doc_list_pos`.`doc` AND `ns`.`param`='na_sklad'
            LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
            LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`ns`.`value`
            WHERE `doc_list_pos`.`tovar`='{$pos_info['id']}' AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<'$dt_t' AND `doc_list`.`sklad`='{$this->sklad}' AND
            (`doc_list`.`type`='2' OR `doc_list`.`type`='20') AND `doc_list`.`ok`>0
            $s_where
            ORDER BY `doc_list`.`date`");
        $realiz_cnt = $sum = 0;
        while ($nxt = $res->fetch_assoc()) {
            if ($this->w_docs) {
                $from = '';
                if ($nxt['type'] == 2) {
                    $from = $nxt['agent_name'];
                } else if ($nxt['type'] == 8) {
                    $from = $nxt['sklad_name'];
                }
                $date = date("Y-m-d H:i:s", $nxt['date']);
                $sumline = $nxt['cnt'] * $nxt['cost'];

                $this->tableRow(array($date, "{$nxt['doc_name']} {$nxt['snum']} ({$nxt['id']})", $from, $nxt['cnt'], $nxt['cost'], $sumline));
                $sum+=$sumline;
            }
            $realiz_cnt+=$nxt['cnt'];
        }
        if ($this->w_docs) {
            $this->tableRow(array('', 'По реализациям:', '', $realiz_cnt, '', $sum));
        }
        $r_cnt+=$realiz_cnt;
        $r_sum+=$sum;
        
        if ($this->w_docs) {
            $this->tableAltStyle();
            $this->tableSpannedRow(array($this->col_cnt), array('Перемещения'));
            $this->tableAltStyle(false);
        }
        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`agent`, `doc_agent`.`name` AS `agent_name`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `ns`.`value` AS `na_sklad`, `doc_sklady`.`name` AS `sklad_name`, `doc_types`.`name` AS `doc_name`, `doc_list`.`date`, CONCAT(`doc_list`.`altnum`, `doc_list`.`subtype`) AS `snum`
            FROM `doc_list_pos`
            INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
            INNER JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
            LEFT JOIN `doc_dopdata` AS `ns` ON `ns`.`doc`=`doc_list_pos`.`doc` AND `ns`.`param`='na_sklad'
            LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
            LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`ns`.`value`
            WHERE `doc_list_pos`.`tovar`='{$pos_info['id']}' AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<'$dt_t' AND `doc_list`.`sklad`='{$this->sklad}' AND `doc_list`.`type`='8' AND `doc_list`.`ok`>0
                    $s_where
                    ORDER BY `doc_list`.`date`");
        $perem_cnt = $sum = 0;
        while ($nxt = $res->fetch_assoc()) {
            if ($this->w_docs) {
                $from = 'Сборка';
                if ($nxt['type'] == 2) {
                    $from = $nxt['agent_name'];
                } else if ($nxt['type'] == 8) {
                    $from = $nxt['sklad_name'];
                }
                $date = date("Y-m-d H:i:s", $nxt['date']);
                $sumline = $nxt['cnt'] * $nxt['cost'];
                $this->tableRow(array($date, "{$nxt['doc_name']} {$nxt['snum']} ({$nxt['id']})", $from, $nxt['cnt'], $nxt['cost'], $sumline));
                $sum+=$sumline;
            }
            $perem_cnt+=$nxt['cnt'];
        }
        if ($this->w_docs) {
            $this->tableRow(array('', 'По перемещениям:', '', $perem_cnt, '', $sum));
        }
        $r_cnt+=$perem_cnt;
        $r_sum+=$sum;
        
        if ($this->w_docs) {
            $this->tableAltStyle();
            $this->tableSpannedRow(array($this->col_cnt), array('Корректировки'));
            $this->tableAltStyle(false);
        }
        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`type`, ABS(`doc_list_pos`.`cnt`) AS `cnt`, `doc_list_pos`.`cost`, `ns`.`value` AS `na_sklad`, `doc_sklady`.`name` AS `sklad_name`, `doc_types`.`name` AS `doc_name`, `doc_list`.`date`, CONCAT(`doc_list`.`altnum`, `doc_list`.`subtype`) AS `snum`
            FROM `doc_list_pos`
            INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
            INNER JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
            LEFT JOIN `doc_dopdata` AS `ns` ON `ns`.`doc`=`doc_list_pos`.`doc` AND `ns`.`param`='na_sklad'
            LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
            LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`ns`.`value`
            WHERE `doc_list_pos`.`tovar`='{$pos_info['id']}' AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<'$dt_t' "
                . " AND `doc_list`.`sklad`='{$this->sklad}' AND `doc_list`.`type`='25' AND `doc_list_pos`.`cnt`<'0' AND `doc_list`.`ok`>0
            $s_where
            ORDER BY `doc_list`.`date`");
        $corr_cnt = $sum = 0;
        while ($nxt = $res->fetch_assoc()) {
            if ($this->w_docs) {
                $from = '';
                if ($nxt['type'] == 2) {
                    $from = $nxt['agent_name'];
                } else if ($nxt['type'] == 8) {
                    $from = $nxt['sklad_name'];
                }
                $date = date("Y-m-d H:i:s", $nxt['date']);
                $sumline = $nxt['cnt'] * $nxt['cost'];

                $this->tableRow(array($date, "{$nxt['doc_name']} {$nxt['snum']} ({$nxt['id']})", $from, $nxt['cnt'], $nxt['cost'], $sumline));
                $sum+=$sumline;
            }
            $corr_cnt+=$nxt['cnt'];
        }
        if ($this->w_docs) {
            $this->tableRow(array('', 'По корректировкам:', '', $corr_cnt, '', $sum));
        }
        $r_cnt+=$corr_cnt;
        $r_sum+=$sum;
        
        if ($this->w_docs) {
            $this->tableAltStyle();
            $this->tableSpannedRow(array($this->col_cnt), array('Сборки'));
            $this->tableAltStyle(false);
        }
        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`agent`, `doc_agent`.`name` AS `agent_name`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `ns`.`value` AS `na_sklad`, `doc_sklady`.`name` AS `sklad_name`, `doc_types`.`name` AS `doc_name`, `doc_list`.`date`, CONCAT(`doc_list`.`altnum`, `doc_list`.`subtype`) AS `snum`
            FROM `doc_list_pos`
            INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
            INNER JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
            LEFT JOIN `doc_dopdata` AS `ns` ON `ns`.`doc`=`doc_list_pos`.`doc` AND `ns`.`param`='na_sklad'
            LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
            LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`ns`.`value`
            WHERE `doc_list_pos`.`tovar`='{$pos_info['id']}' AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<'$dt_t' AND `doc_list`.`sklad`='{$this->sklad}' AND (`doc_list`.`type`='17' AND `doc_list_pos`.`page`!='0') AND `doc_list`.`ok`>0
                        $s_where
                    ORDER BY `doc_list`.`date`");
        $sbor_cnt = $sum = 0;
        while ($nxt = $res->fetch_assoc()) {
            if ($this->w_docs) {
                $from = 'Сборка';
                if ($nxt['type'] == 2) {
                    $from = $nxt['agent_name'];
                } else if ($nxt['type'] == 8) {
                    $from = $nxt['sklad_name'];
                }
                $date = date("Y-m-d H:i:s", $nxt['date']);
                $sumline = $nxt['cnt'] * $nxt['cost'];
                $this->tableRow(array($date, "{$nxt['doc_name']} {$nxt['snum']} ({$nxt['id']})", $from, $nxt['cnt'], $nxt['cost'], $sumline));
                $sum+=$sumline;
            }
            $sbor_cnt+=$nxt['cnt'];
        }
        $r_cnt+=$sbor_cnt;
        if ($this->w_docs) {
            $this->tableAltStyle();
            $this->tableSpannedRow(array($this->col_cnt), array(''));
            $this->tableAltStyle(false);
            $this->tableRow(array('', 'По сборкам:', '', $sbor_cnt, '', $sum));

            $r_sum+=$sum;
            $this->tableRow(array('', 'Всего расход:', '', $r_cnt, '', $r_sum));
            $end_cnt = $start_cnt + $prix_cnt - $r_cnt;
            $this->tableRow(array('', 'На конец периода:', '', $end_cnt, '', ''));
        } else {
            $end_cnt = $start_cnt + $prix_cnt - $r_cnt;
            if ($prix_cnt || $realiz_cnt || $perem_cnt || $sbor_cnt || $corr_cnt) {
                $this->tableRow(
                    array(
                        $pos_info['id'], $pos_info['vc'], $pos_info['name'], $this->getLastBuyDate($pos_info['id']),
                        $pos_info['base_price'], $start_cnt, $prix_cnt, $realiz_cnt, $perem_cnt, $sbor_cnt, $corr_cnt, $end_cnt)
                    );
            }
        }
        return array(
            'start' => $start_cnt,
            'prix' => $prix_cnt,
            'real' => $realiz_cnt,
            'perem' => $perem_cnt,
            'sbor' => $sbor_cnt,
            'korr' => $corr_cnt);
    }

    function serialOutPos($pos_info, $dt_f, $dt_t, $subtype) {
        global $tmpl, $db;
        $cur_cnt = getStoreCntOnDate($pos_info['id'], $this->sklad, $dt_f, 1);
        $s_where = '';
        if ($subtype) {
            $s_where = " AND `doc_list`.`subtype` = '" . $db->real_escape_string($subtype) . "'";
        }
        if ($this->w_docs) {
            $this->tableAltStyle();
            $this->tableSpannedRow(array($this->col_cnt), array("{$pos_info['vc']} {$pos_info['name']} ({$pos_info['id']})"));
            $this->tableAltStyle(false);
            $this->tableSpannedRow(array($this->col_cnt - 1, 1), array('На начало периода:', $cur_cnt));
        }
        
        $res = $db->query("SELECT `doc_list`.`id` AS `doc_id`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list_pos`.`page`,
                `doc_agent`.`name` AS `agent_name`, `doc_list_pos`.`cnt`, `ds`.`name` AS `sklad_name`, `nsn`.`name` AS `nasklad_name`,
                `doc_types`.`name` AS `doc_name`, `doc_list`.`date`, CONCAT(`doc_list`.`altnum`, `doc_list`.`subtype`) AS `snum`
            FROM `doc_list_pos`
            INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
            INNER JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
            LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
            LEFT JOIN `doc_dopdata` AS `ns` ON `ns`.`doc`=`doc_list_pos`.`doc` AND `ns`.`param`='na_sklad'
            LEFT JOIN `doc_sklady` AS `ds` ON `ds`.`id`=`doc_list`.`sklad`
            LEFT JOIN `doc_sklady` AS `nsn` ON `nsn`.`id`=`ns`.`value`
            WHERE `doc_list_pos`.`tovar`='{$pos_info['id']}' AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<'$dt_t' AND (
                (`doc_list`.`type`='1' AND `doc_list`.`sklad`='{$this->sklad}') OR
                ((`doc_list`.`type`='2' OR `doc_list`.`type`='20' OR `doc_list`.`type`='25') AND `doc_list`.`sklad`='{$this->sklad}') OR
                (`doc_list`.`type`='8' AND (`doc_list`.`sklad`='{$this->sklad}' OR `ns`.`value`='{$this->sklad}')) OR
                (`doc_list`.`type`='17' AND `doc_list`.`sklad`='{$this->sklad}') ) 
                AND `doc_list`.`ok`>0
                $s_where
            ORDER BY `doc_list`.`date`");
        $sp = $sr = 0;
        while ($nxt = $res->fetch_assoc()) {
            $p = $r = '';
            $link = '';
            switch ($nxt['type']) {
                case 1: $p = $nxt['cnt'];
                    $link = 'От ' . $nxt['agent_name'];
                    break;
                case 2:
                case 20:$r = $nxt['cnt'];
                    $link = 'Для ' . $nxt['agent_name'];
                    break;
                case 8: {
                        if ($nxt['sklad'] == $this->sklad) {
                            $r = $nxt['cnt'];
                            $link = 'На ' . $nxt['nasklad_name'];
                        } else {
                            $p = $nxt['cnt'];
                            $link = 'С ' . $nxt['sklad_name'];
                        }
                    }break;
                case 17: {
                        if ($nxt['page'] == 0)
                            $p = $nxt['cnt'];
                        else
                            $r = $nxt['cnt'];
                        $link = $nxt['agent_name'];
                    }
                    break;
                case 25:
                    if($nxt['cnt']>0) {
                        $p = $nxt['cnt'];
                    }
                    else {
                        $r = abs($nxt['cnt']);
                    }
                    break;
                default:$p = $r = 'fff-' . $nxt['type'];
            }
            $cur_cnt += $p - $r;
            $cur_cnt = round($cur_cnt, 5);
            $date = date("Y-m-d H:i:s", $nxt['date']);
            $this->tableRow(array($date, "{$nxt['doc_name']} {$nxt['snum']} / {$nxt['doc_id']}", $link, $p, $r, $cur_cnt));
            $sp+=$p;
            $sr+=$r;
        }
        $this->tableSpannedRow(array($this->col_cnt - 3, 1, 1, 1), array('На конец периода:', $sp, $sr, $cur_cnt));
    }

    function outPos($pos_info, $dt_f, $dt_t, $subtype) {
        if ($this->div_dt || !$this->w_docs) {
            return $this->dividedOutPos($pos_info, $dt_f, $dt_t, $subtype);
        } else {
            return $this->serialOutPos($pos_info, $dt_f, $dt_t, $subtype);
        }
    }
    
    function getLastBuyDate($pos_id) {
        global $db;
        $res = $db->query("SELECT `doc_list`.`id` AS `doc_id`, `doc_list`.`date`
            FROM `doc_list_pos`
            INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
            WHERE `doc_list_pos`.`tovar`='$pos_id' AND `doc_list`.`type`='1'
            ORDER BY `doc_list`.`date` DESC"
            . " LIMIT 1");
        if($res->num_rows) {
            $info = $res->fetch_assoc();
            return date('Y-m-d', $info['date']);
        }
        return '';
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
                    $cnt+=$nxt[0];
                    if($nxt[5]>=$this->dt_f) {
                        $out += $nxt[0];
                    }
                    break;
                case 2:
                case 20:
                    $cnt-=$nxt[0];
                    break;
                case 17:
                    if ($nxt[4] == 0) {
                        $cnt+=$nxt[0];
                    } else {
                        $cnt-=$nxt[0];
                    }
                    break;
                case 25:
                    $cnt+=$nxt[0];
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
        while($g_info = $res->fetch_assoc()) {
            $g_stat = $this->processGroup($g_info['id'], $prefix.'-');
            $g_stat['id'] = $g_info['id'];
            $g_stat['name'] = $g_info['name'];
            $p_res = $db->query("SELECT `id` FROM `doc_base` WHERE `group`='{$g_info['id']}' AND `pos_type`=0");
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
        global $db;
        $this->loadEngine($engine);

        $this->year = rcvint("year");
        $this->dt_f = strtotime($this->year."-01-01");
        $this->dt_t = strtotime($this->year."-12-31 23:59:59");
        
        $ldo = new \Models\LDO\skladnames();
        $this->storenames = $ldo->getData();

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
