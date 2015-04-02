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
namespace async;

require_once($CONFIG['location'] . "/common/asyncworker.php");
require_once($CONFIG['site']['location'] . "/include/doc.core.php");
require_once($CONFIG['site']['location'] . "/include/doc.nulltype.php");

/// Ассинхронный обработчик. Расчёт заработной платы.
class salary extends \AsyncWorker {
    var $docs;
    var $plus_docs;
    protected $last_liq_date = '';
    protected $last_liq_array = array();
    protected $users_fee = array();
    protected $param_pcs_id;        // Параметр - id свойства товара - сложность сборки кладовщиком
    
    protected $conf_sk_pack_coeff;
    protected $conf_sk_cnt_coeff;
    protected $conf_sk_place_coeff;
    protected $conf_manager_id;
    protected $conf_author_coeff;
    protected $conf_resp_coeff;
    protected $conf_manager_coeff;
    protected $conf_use_likv;
    
    protected $pc = 0;
    
    function getDescription() {
        return "Расчёт заработной платы";
    }

    function run() {
        global $db;
        $db->query("FLUSH TABLE CACHE");
        // Получить ID для сложности
        $res = $db->query("SELECT `id` FROM `doc_base_params` WHERE `param`='pack_complexity_sk'");
        if (!$res->num_rows) {
            $db->query("INSERT INTO `doc_base_params` (`param`, `type`, `pgroup_id`, `system`) VALUES ('pack_complexity_sk', 'float', NULL, 1)");
            throw new Exception("Параметр начисления зарплаты не был найден. Параметр создан. Перед начислением заработной платы необходимо заполнить свойства номенклатуры.");
        }
        list($this->param_pcs_id) = $res->fetch_row();
        // Расчёт
        $db->startTransaction();
        $res = $db->query("SELECT `id`, `name`, `responsible` FROM `doc_agent` ORDER BY `id`");
        while($line = $res->fetch_assoc()) {
            echo "Agent ".$line['id'];
            $this->calcAgent($line['id'], $line['responsible']);
            echo " Done/n";
        }
    }
    
    // @return Остаточная сумма от запрошенной к вычету
    function decDocSum($doc_id, $sum) {
        if(isset($this->plus_docs[$doc_id])) {
            if ($this->docs[$doc_id]['sum'] <= $sum) {
                $sum -= $this->docs[$doc_id]['sum'];
                $this->docs[$doc_id]['sum'] = 0;
                unset($this->plus_docs[$doc_id]);
            } else {
                $this->docs[$doc_id]['sum'] -= $sum;
                $sum = 0;
            }
        }
        return $sum;
    }
    
    function calcAgent($agent_id, $responsible_id) {
        global $db;
        $this->docs = array();       
        $this->plus_docs = array();
        $minus_docs = array();

        // Грузим
        $docs_res = $db->query("SELECT `id`, `type`, `date`, `user`, `sum`, `p_doc`, `contract`, `sklad` AS `store_id`"
            . " FROM `doc_list`"
            . " WHERE `ok`>0 AND `mark_del`=0 AND `type` IN (1, 2, 4, 5, 6, 7, 14, 18) AND `agent`=$agent_id"
            . " ORDER BY `date`");
        while ($doc_line = $docs_res->fetch_assoc()) {
            $doc_vars = array();
            $res = $db->query('SELECT `param`, `value` FROM `doc_dopdata` WHERE `doc`=' . $doc_line['id']);
            while ($line = $res->fetch_row()) {
                $doc_vars[$line[0]] = $line[1];
            }
            $doc_line['vars'] = $doc_vars;
            $doc_line['childs'] = array();
            $doc_line['fullpay'] = 0;
            $this->docs[$doc_line['id']] = $doc_line;
        }

        // Заполняем id потомков
        foreach ($this->docs as $id => $val) {
            if ($val['p_doc'] > 0 && isset($this->docs[$val['p_doc']])) {
                $this->docs[$val['p_doc']]['childs'][] = $id;
            }
        }
        
        // Делим по признаку приход/расход
        foreach ($this->docs as $id => $val) {
            switch ($val['type']) {
                case 1: // Поступление
                case 4: // Банк-приход
                case 6: // Касса-приход
                    $this->plus_docs[$id] = $id;
                    break;
                case 2:
                case 5:
                case 7:
                    $minus_docs[$id] = $id;
                    break;
                case 18:
                    if($val['sum']>0) {
                        $minus_docs[$id] = $id;
                    } else {
                        $this->plus_docs[$id] = $id;
                    }
                    $this->docs[$id]['sum'] = abs($this->docs[$id]['sum']);
                    break;
            }
        }
        // Учёт агентских вознаграждений
        foreach ($minus_docs as $id) {
            foreach($this->docs[$id]['childs'] as $c_id) {
                if($this->docs[$c_id]['type']!=7) {
                    continue;
                }
                if(isset($this->docs[$id]['dec_sum'])) {
                    $this->docs[$id]['dec_sum'] += $this->docs[$c_id]['sum'];
                } else {
                    $this->docs[$id]['dec_sum'] = $this->docs[$c_id]['sum'];
                }
            }
        }
        
        // Обрабатываем расходы
        // Проверка оплаты потомками
        foreach ($minus_docs as $id) {
            $cur_sum = $this->docs[$id]['sum'];
            foreach($this->docs[$id]['childs'] as $c_id) {
                if($this->docs[$c_id]['sum']==0) {
                    continue;
                }
                switch ($this->docs[$c_id]['type']) {
                    case 1: // Поступление
                    case 4: // Банк-приход
                    case 6: // Касса-приход
                    case 18:
                        $cur_sum = $this->decDocSum($c_id, $cur_sum);
                        break;
                }
            }
            if($cur_sum == 0) { // Оплачен полностью
                $this->docs[$id]['fullpay'] = true;
            }
        }
        // Оплата по списку
        foreach ($minus_docs as $id) {
            $cur_sum = $this->docs[$id]['sum'];            
            while($cur_sum>0 && count($this->plus_docs)>0) {
                reset($this->plus_docs);
                list(,$c_id) = each($this->plus_docs);
                $cur_sum = $this->decDocSum($c_id, $cur_sum);
            }            
            if($cur_sum == 0) { // Оплачен полностью
                $this->docs[$id]['fullpay'] = true;
            }
        }
        // Начисление зарплаты
        $cnt = 0;
        foreach ($minus_docs as $id) {
            $doc = $this->docs[$id];
            if($doc['type']=='2' && $doc['fullpay']) {
                if( !isset($doc['vars']['salary']) ) {
                    $this->calcFee($doc, $responsible_id);
                } elseif(!$doc['vars']['salary']) {
                    $this->calcFee($doc, $responsible_id);
                }
                echo "$id (".round($cnt/count($minus_docs), 2).")\n";
                $cnt++;
            }
        }
    }
    
    protected function calcFee($doc, $responsible_id) {
        global $db;
        $a_likv = $this->getLiquidityOnDate($doc['date']);
        // Расчёт  стоимости
        
        $res_tov = $db->query("SELECT `doc_list_pos`.`id`, `doc_list_pos`.`tovar`, `doc_list_pos`.`cost`, `doc_list_pos`.`cnt`,
                 `doc_base_values`.`value` AS `pcs`, `doc_base`.`mult`,  `doc_base_cnt`.`mesto`
             FROM `doc_list_pos`
             INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
             INNER JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$doc['store_id']}'
             LEFT JOIN `doc_base_values` ON `doc_base_values`.`id`=`doc_list_pos`.`tovar` AND `doc_base_values`.`param_id`='{$this->param_pcs_id}'
             WHERE `doc_list_pos`.`doc`='{$doc['id']}'");
        
        $additional_sum = 0;    // Расчётная добавленная стоимость. В зависимости о настроек, может уменьшаться линейно от ликвидности
        if (isset($this->docs[$doc['id']]['dec_sum'])) {
            $additional_sum -= $this->docs[$doc['id']]['dec_sum'];
        }
        $pos_cnt = $sk_pos_fee = 0;
        $a_places = array();
        while ($nxt_tov = $res_tov->fetch_assoc()) {
            // Продавцам и пр
            
            $incost = $this->getInCost($nxt_tov['tovar'], $doc['date']);
            
            if ($this->conf_use_likv && isset($a_likv[$nxt_tov['tovar']])) {
                $additional_sum += ($nxt_tov['cost'] - $incost) * $nxt_tov['cnt'] * (1 - $a_likv[$nxt_tov['tovar']] * $this->l_coeff / 100 );
            } else {
                $additional_sum += ($nxt_tov['cost'] - $incost) * $nxt_tov['cnt'];
            }
            // Кладовщикам
            if (!$nxt_tov['mult']) {
                $nxt_tov['mult'] = 1;
            }
            $a_places[intval($nxt_tov['mesto'])] = 1;
            $sk_pos_fee += $nxt_tov['pcs'] * $nxt_tov['cnt'] / $nxt_tov['mult'];
            $pos_cnt++;
        }
        $author_fee = $additional_sum * $this->conf_author_coeff;
        $resp_fee = $additional_sum * $this->conf_resp_coeff;
        $manager_fee = $additional_sum * $this->conf_manager_coeff;
        $sk_fee = $sk_pos_fee * $this->conf_sk_pack_coeff + count($a_places) * $this->conf_sk_place_coeff + $pos_cnt * $this->conf_sk_cnt_coeff;
        // Запись начисления
        $salary = array(
            'a_uid' => $doc['user'],
            'a_fee' => $author_fee,
            'r_uid' => $responsible_id,
            'r_fee' => $resp_fee,
            'm_uid' => $this->conf_manager_id,
            'm_fee' => $manager_fee,
        );
        // Запоминаем начисления
        $this->incFee('author', $doc['user'], $author_fee);
        $this->incFee('resp', $responsible_id, $resp_fee);
        $this->incFee('manager', $this->conf_manager_id, $manager_fee);
        if( isset( $doc['vars']['kladovshik'] ) ) {
            $this->incFee('sk', $doc['vars']['kladovshik'], $sk_fee);
            $salary['sk_uid'] = intval($doc['vars']['kladovshik']);
            $salary['sk_fee'] = $sk_fee;
        }
        $ser_salary_sql = $db->real_escape_string( json_encode($salary, JSON_UNESCAPED_UNICODE) );
        $db->insertA('doc_dopdata', array('doc'=>$doc['id'], 'param'=>'salary', 'value'=>$ser_salary_sql));
    }
    
    function getInCost($pos_id, $limit_date = 0, $type = 0) {
        global $db;
        settype($pos_id, 'int');
        settype($limit_date, 'int');
        $cnt = $cost = 0;
        $sql_add = '';
        $res = $db->query("SELECT `pos_type`, `cost` FROM `doc_base` WHERE `id`=$pos_id");
        list($type, $cost) = $res->fetch_row();
        if ($type == 1) {
            return 0;
        }
        if ($limit_date)
            $sql_add = "AND `doc_list`.`date`<='$limit_date'";
        $res = $db->query("SELECT `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_list`.`type`, `doc_list_pos`.`page`, `doc_dopdata`.`value`
            FROM `doc_list_pos`
            INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND (`doc_list`.`type`<='2' OR `doc_list`.`type`='17')
            LEFT JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list_pos`.`doc` AND `doc_dopdata`.`param`='return'
            WHERE `doc_list_pos`.`tovar`='$pos_id' AND `doc_list`.`ok`>'0' $sql_add ORDER BY `doc_list`.`date`");
        while ($nxt = $res->fetch_row()) {
            if (($nxt[2] == 2) || ($nxt[2] == 17) && ($nxt[3] != '0'))
                $nxt[0] = $nxt[0] * (-1);
            if (($cnt + $nxt[0]) == 0) {
                
            } else if ($nxt[0] > 0 && $nxt[4] != 1) {
                if ($cnt > 0)
                    $cost = ( ($cnt * $cost) + ($nxt[0] * $nxt[1])) / ($cnt + $nxt[0]);
                else
                    $cost = $nxt[1];
            }
            $cnt+=$nxt[0];
        }
        $res->free();
        return round($cost, 2);
    }

    /// Увеличить счётчик оплаты для заданного сотрудника в заданной роли
    /// @param $role    Роль сотрудника
    /// @param $uid     id сотрудника
    /// @param $value   Значение, на которое нужно увеличить счётчик
    protected function incFee($role, $uid, $value) {
        settype($uid, 'int');
        if($uid == 0) {
            return;
        }
        if(!isset($this->users_fee[$role])) {
            $this->users_fee[$role] = array();
        }
        if(!isset($this->users_fee[$role][$uid])) {
            $this->users_fee[$role][$uid] = $value;
        } else {
            $this->users_fee[$role][$uid] += $value;
        }
    }


    // Расчёт ликвидности на текущую дату с кешированием
    protected function getLiquidityOnDate($date) {
        $sdate = date("Ymd", $date);
        if ($this->conf_use_likv && $sdate != $this->last_liq_date) {
            $this->last_liq_array = getLiquidityOnDate($date - 1);
            $this->last_liq_date = $sdate;
        }
        return $this->last_liq_array;
    }

    function finalize() {
        
    }

}
