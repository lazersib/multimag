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
namespace async;

/// Проверка поступления оплаты за документы и простановка соответствующих отметок
class paycheck extends \AsyncWorker {

    public function getDescription() {
        return "Проверка поступления оплаты за документы и простановка соответствующих отметок";
    }

    public function run() {
        global $db;
        $db->startTransaction();        
        $res = $db->query("SELECT `id` FROM `doc_agent` ORDER BY `id`");
        $i = 0;        
        while($line = $res->fetch_assoc()) {
            $this->setStatus($i/$res->num_rows*100, 'Agent:'.$line['id']);
            $this->calcAgent($line['id']);
            $i++;
        }
        $db->commit();
    }

    protected function loadDocsForAgent($agent_id) {
        global $db;
        $this->docs = array();
        // Грузим
        $docs_res = $db->query("SELECT `id`, `type`, `date`, `user`, `sum`, `p_doc`, `contract`, `sklad` AS `store_id`, `doc_dopdata`.`value` AS `return`"
            . " FROM `doc_list`"
            . " LEFT JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list`.`id` AND `doc_dopdata`.`param`='return'"
            . " WHERE `ok`>0 AND `mark_del`=0 AND `type` IN (1, 2, 4, 5, 6, 7, 18) AND `agent`=$agent_id" 
            . " ORDER BY `date`");
        while ($doc_line = $docs_res->fetch_assoc()) {
            if($doc_line['return']) {
                continue;
            }
            $doc_vars = array();
            $res = $db->query('SELECT `param`, `value` FROM `doc_dopdata` WHERE `doc`=' . $doc_line['id']);
            while ($line = $res->fetch_row()) {
                $doc_vars[$line[0]] = $line[1];
            }
            $doc_line['vars'] = $doc_vars;
            $doc_line['childs'] = array();
            $doc_line['fullpayed'] = 0;
            $this->docs[$doc_line['id']] = $doc_line;
        }
        // Заполняем id потомков
        foreach ($this->docs as $id => $val) {
            if ($val['p_doc'] > 0 && isset($this->docs[$val['p_doc']])) {
                $this->docs[$val['p_doc']]['childs'][] = $id;
            }
        }
    }
    
    // @return Остаточная сумма от запрошенной к вычету
    protected function decDocSum($doc_id, $sum) {
        if(isset($this->plus_docs[$doc_id])) {
            if ($this->docs[$doc_id]['sum'] <= $sum) {
                $sum = round($sum-$this->docs[$doc_id]['sum'],2);
                $this->docs[$doc_id]['sum'] = 0;
                unset($this->plus_docs[$doc_id]);
            } else {
                $this->docs[$doc_id]['sum'] = round($this->docs[$doc_id]['sum']-$sum, 2);
                $sum = 0;
            }
        }
        return $sum;
    }
    
    protected function calcAgent($agent_id) {
        global $db;
        $this->plus_docs = array();
        $minus_docs = array();
        $this->loadDocsForAgent($agent_id);
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
                if($cur_sum<=0) {
                    break;
                }
            }
            $this->docs[$id]['paytest_sum'] = $cur_sum;
            if($cur_sum == 0) { // Оплачен полностью
                $this->docs[$id]['fullpay'] = true;
            }
        }
        // Оплата по списку
        foreach ($minus_docs as $id) {
            $cur_sum = $this->docs[$id]['paytest_sum'];           
            while($cur_sum>0 && count($this->plus_docs)>0) {
                reset($this->plus_docs);
                list(,$c_id) = each($this->plus_docs);
                $cur_sum = $this->decDocSum($c_id, $cur_sum);
            }            
            $payed = $cur_sum==0?1:0;
            $paysum = $this->docs[$id]['sum'] - $cur_sum;
            $db->query("REPLACE `doc_dopdata` (`doc`, `param`, `value`) VALUES ($id, 'payed', $payed), ($id, 'paysum', $paysum)");
        }        
    }
    
}
