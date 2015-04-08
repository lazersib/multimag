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

/// Ассинхронный обработчик. Расчёт вознаграждений.
class salary extends \AsyncWorker {
    var $docs;
    var $plus_docs;
    protected $last_liq_date = '';
    protected $last_liq_array = array();
    protected $users_fee = array();
    protected $param_pcs_id;        // Параметр - id свойства товара - сложность сборки кладовщиком
    
    protected $conf_enable          = false;//< Разрешена ли работа модуля
    protected $conf_sk_pack_coeff   = 0.5;  //< Коэффициент вознаграждения кладовщику реализации за упаковки
    protected $conf_sk_cnt_coeff    = 1;    //< Коэффициент вознаграждения кладовщику реализации за кол-во товара в накладной
    protected $conf_sk_place_coeff  = 2;    //< Коэффициент вознаграждения кладовщику реализации за кол-во различных мест в накладной
    protected $conf_manager_id      = null; //< id пользователя-менеджера для начисления вознаграждения
    protected $conf_author_coeff    = 0.01; //< Коэффициент вознаграждения автору реализации
    protected $conf_resp_coeff      = 0.02; //< Коэффициент вознаграждения ответственному агента
    protected $conf_manager_coeff   = 0.005;//< Коэффициент вознаграждения менеджеру магазина
    protected $conf_use_liq         = 1;    //< Учитывать ли ликвидность при расчёте вознаграждения с товарной наценки
    protected $conf_liq_coeff       = 0.5;  //< Коэффициент влияния ликвидности на вознаграждение с товарной наценки
    protected $conf_work_pos_id     = 1;    //< id услуги "работа"
    
    protected $users_salary_info = array();


    protected $pc = 0;
    
    protected $ppi = array(); // Pos prices info
    
    public function __construct($task_id) {
        global $CONFIG;
        parent::__construct($task_id);
        if (isset($CONFIG['salary']['enable'])) {
            $this->conf_enable = $CONFIG['salary']['enable'];
        }
        if (isset($CONFIG['salary']['sk_pack_coeff'])) {
            $this->conf_sk_pack_coeff = $CONFIG['salary']['sk_pack_coeff'];
        }
        if (isset($CONFIG['salary']['sk_cnt_coeff'])) {
            $this->conf_sk_cnt_coeff = $CONFIG['salary']['sk_cnt_coeff'];
        }
        if (isset($CONFIG['salary']['sk_place_coeff'])) {
            $this->conf_sk_place_coeff = $CONFIG['salary']['sk_place_coeff'];
        }
        if (isset($CONFIG['salary']['manager_id'])) {
            $this->conf_manager_id = $CONFIG['salary']['manager_id'];
        }
        if (isset($CONFIG['salary']['author_coeff'])) {
            $this->conf_author_coeff = $CONFIG['salary']['author_coeff'];
        }
        if (isset($CONFIG['salary']['resp_coeff'])) {
            $this->conf_resp_coeff = $CONFIG['salary']['resp_coeff'];
        }
        if (isset($CONFIG['salary']['manager_coeff'])) {
            $this->conf_manager_coeff = $CONFIG['salary']['manager_coeff'];
        }
        if (isset($CONFIG['salary']['use_liq'])) {
            $this->conf_use_liq = $CONFIG['salary']['use_liq'];
        }
        if (isset($CONFIG['salary']['liq_coeff'])) {
            $this->conf_liq_coeff = $CONFIG['salary']['liq_coeff'];
        }
        if (isset($CONFIG['salary']['work_pos_id'])) {
            $this->conf_work_pos_id = $CONFIG['salary']['work_pos_id'];
        }
    }
        
    function getDescription() {
        return "Расчёт вознаграждений";
    }

    function run() {
        global $db;
        //$db->query("FLUSH TABLE CACHE");
        // Получить ID для сложности
        $res = $db->query("SELECT `id` FROM `doc_base_params` WHERE `param`='pack_complexity_sk'");
        if (!$res->num_rows) {
            $db->query("INSERT INTO `doc_base_params` (`param`, `type`, `pgroup_id`, `system`) VALUES ('pack_complexity_sk', 'float', NULL, 1)");
            throw new Exception("Параметр начисления зарплаты не был найден. Параметр создан. Перед начислением заработной платы необходимо заполнить свойства номенклатуры.");
        }
        list($this->param_pcs_id) = $res->fetch_row();
        // Расчёт
        $tmp = microtime(true);
        $db->startTransaction();
        $this->loadPosTypes();
        $res = $db->query("SELECT `id`, `name`, `responsible` FROM `doc_agent` ORDER BY `id`");
        while($line = $res->fetch_assoc()) {
            echo "Agent ".$line['id']."\n";
            $this->calcAgent($line['id'], $line['responsible']);
            echo " Done\n";
        }
        $this->payFee();
        $db->commit();
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
    
    function loadDocsForAgent($agent_id) {
        global $db;
        $this->docs = array();
        //$rdate = strtotime("2015-02-20");
        // Грузим
        $docs_res = $db->query("SELECT `id`, `type`, `date`, `user`, `sum`, `p_doc`, `contract`, `sklad` AS `store_id`"
            . " FROM `doc_list`"
            . " WHERE `ok`>0 AND `mark_del`=0 AND `type` IN (1, 2, 4, 5, 6, 7, 14, 18) AND `agent`=$agent_id" // AND `date`<'$rdate'" 
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
    }
    
    function calcAgent($agent_id, $responsible_id) {
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
                if( ! @$doc['vars']['salary']) {
                    $salary = $this->calcFee($doc, $responsible_id);
                    $this->incFee('operator', $salary['o_uid'], $salary['o_fee'], $doc['id']);
                    $this->incFee('resp', $salary['r_uid'], $salary['r_fee'], $doc['id']);
                    $this->incFee('manager', $salary['m_uid'], $salary['m_fee'], $doc['id']);
                    $this->incFee('sk', $salary['sk_uid'], $salary['sk_fee'], $doc['id']);
                    $ser_salary_sql = json_encode($salary, JSON_UNESCAPED_UNICODE);
                    $db->insertA('doc_dopdata', array('doc'=>$doc['id'], 'param'=>'salary', 'value'=>$ser_salary_sql));
                }
                //echo "$id (".round($cnt/count($minus_docs), 2).")\n";
                $cnt++;
            }
        }
        
    }
    
    public function calcFee($doc, $responsible_id) {
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
        $b_sum = $additional_sum;
        while ($nxt_tov = $res_tov->fetch_assoc()) {
            // Продавцам и пр
            $tmp = microtime(true);
            //$incost = $this->getInPrice($nxt_tov['tovar'], $doc['date']);
            $incost = getInCost($nxt_tov['tovar'], $doc['date']);
            $this->pc += microtime(true)-$tmp;
            if ($this->conf_use_liq && isset($a_likv[$nxt_tov['tovar']])) {
                $additional_sum += ($nxt_tov['cost'] - $incost) * $nxt_tov['cnt'] * (1 - $a_likv[$nxt_tov['tovar']] * $this->conf_liq_coeff / 100 );
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
        $sk_fee = $sk_pos_fee * $this->conf_sk_pack_coeff + count($a_places) * $this->conf_sk_place_coeff + $pos_cnt * $this->conf_sk_cnt_coeff;
        // Запись начисления
        $salary = array(
            'o_uid' => $doc['user'],
            'o_fee' => 0,
            'r_uid' => $responsible_id,
            'r_fee' => 0,
            'm_uid' => $this->conf_manager_id,
            'm_fee' => 0,
            'sk_uid'=> null,
            'sk_fee'=> 0
        );
        
        if($doc['user']) {
            $salary['o_fee'] = round($additional_sum * $this->conf_author_coeff, 2);
        }
        if($responsible_id) {
            $salary['r_fee'] = round($additional_sum * $this->conf_resp_coeff, 2);
        }
        if($this->conf_manager_id) {
            $salary['m_fee'] = round($additional_sum * $this->conf_manager_coeff, 2);
        }
        if( isset( $doc['vars']['kladovshik'] ) ) {            
            $salary['sk_uid'] = intval($doc['vars']['kladovshik']);
            $salary['sk_fee'] = round($sk_fee, 2);
        }
        return $salary;
    }
        
    function getInPrice($pos_id, $limit_date) {
        if($this->ppi[$pos_id]['type']) {
            return 0;
        }
        if( !isset($this->ppi[$pos_id]['docs']) ) {
            $this->loadPosDocs($pos_id);
        }
        if($this->ppi[$pos_id]['date']>$limit_date) {
            reset($this->ppi[$pos_id]['docs']);
            $this->ppi[$pos_id]['price'] = 0;
            $this->ppi[$pos_id]['cnt'] = 0;
            $this->ppi[$pos_id]['date'] = 0;
        }
        $this->ppi[$pos_id]['date']=$limit_date;
        do {
            $line = current($this->ppi[$pos_id]['docs']);
            if($line['date']>$limit_date) {
                break;
            }
            if( $line['doc_type']==2 || ( $line['doc_type']==17 && $line['page']!=0 )  ) {
                $line['cnt'] *= -1;                
            }
            if( ( ( $this->ppi[$pos_id]['cnt'] + $line['cnt'] ) != 0 ) &&
                $line['cnt'] !=0 && $line['return_flag'] != 1 ) {
                if($this->ppi[$pos_id]['cnt']>0) {
                    $this->ppi[$pos_id]['price'] = ( ($this->ppi[$pos_id]['price']*$this->ppi[$pos_id]['cnt']) + ($line['price']*$line['cnt']) ) / 
                        ($this->ppi[$pos_id]['cnt'] + $line['cnt']);
                } else {
                    $this->ppi[$pos_id]['price'] = $line['price'];
                }
            }
            $this->ppi[$pos_id]['cnt'] += $line['cnt'];
        } while( next($this->ppi[$pos_id]['docs']) !== FALSE);
        return round($this->ppi[$pos_id]['price'], 2);
    }


    public function loadPosTypes() {
        global $db;
        $res = $db->query("SELECT `id`, `pos_type` FROM `doc_base`");
        while($nxt = $res->fetch_row()) {
            $this->ppi[$nxt[0]]['type'] = $nxt[1];
            $this->ppi[$nxt[0]]['price'] = 0;
            $this->ppi[$nxt[0]]['cnt'] = 0;
            $this->ppi[$nxt[0]]['date'] = 0;
        }
    }
    
    protected function loadPosDocs($pos_id) {
        global $db;
        $docs = array();
        $res = $db->query("SELECT `doc_list`.`type` AS `doc_type`, `doc_list`.`date`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost` AS `price`, `doc_list_pos`.`page`,
                `doc_dopdata`.`value` AS `return_flag`
            FROM `doc_list_pos`
            INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND (`doc_list`.`type`<='2' OR `doc_list`.`type`='17')
            LEFT JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list_pos`.`doc` AND `doc_dopdata`.`param`='return'
            WHERE `doc_list_pos`.`tovar`='$pos_id' AND `doc_list`.`ok`>'0' ORDER BY `doc_list`.`date`");
        while ($line = $res->fetch_assoc()) {
            $docs[] = $line;
        }
        $res->free();
        $this->ppi[$pos_id]['docs'] = $docs;
    }

    /// Увеличить счётчик оплаты для заданного сотрудника в заданной роли
    /// @param $role    Роль сотрудника
    /// @param $uid     id сотрудника
    /// @param $value   Значение, на которое нужно увеличить счётчик
    public function incFee($role, $uid, $value, $doc_id) {
        settype($uid, 'int');
        if($uid == 0 || $value==0) {
            return;
        }        
        if(!isset($this->users_fee[$uid])) {
            $this->users_fee[$uid] = array('operator'=>0, 'resp'=>0, 'manager'=>0, 'sk'=>0, 'docs'=>array());
        }
        if(!isset($this->users_fee[$uid][$role])) {
            $this->users_fee[$uid][$role] = round($value, 2);
        } else {
            $this->users_fee[$uid][$role] += round($value, 2);
        }
        $this->users_fee[$uid]['docs'][$doc_id] = 1;        
    }
    
    public function getUsersFee() {
        return $this->users_fee;
    }

    protected function payFee() {
        global $db, $CONFIG;
        $mail_text = '';
        foreach($this->users_fee as $uid=>$line) {
            $comment = "Вознаграждение для $uid:\n";
            $sum = 0;
            if($line['operator']>0) {
                $sum += $line['operator'];
                $comment .=" - как оператору: {$line['operator']}\n";
            }
            if($line['resp']>0) {
                $sum += $line['resp'];
                $comment .=" - как ответственному: {$line['resp']}\n";
            }
            if($line['manager']>0) {
                $sum += $line['manager'];
                $comment .=" - как менеджеру: {$line['manager']}\n";
            }
            if($line['sk']>0) {
                $sum += $line['sk'];
                $comment .=" - как кладовщику: {$line['sk']}\n";
            }
            $comment .="Документы:\n";
            foreach($line['docs'] as $doc_id=>$tmp) {
                $comment .= $doc_id.', ';
            }
            $user_info = $db->selectRow("users", $uid);
            if(!$user_info) {
                $comment .= "\nПользователь не найден!\n";
            } elseif(!$user_info['agent_id']) {
                $comment .= "\nПользователь не привязан к агенту!\n";
            } else {
                $tm = time();
                $comment_sql = $db->real_escape_string($comment);
                $res = $db->query("INSERT INTO doc_list (`type`,`agent`,`date`,`sklad`,`user`,`nds`,`altnum`,`subtype`,`comment`,`firm_id`, `sum`,`ok`)
		VALUES ('1','{$user_info['agent_id']}','$tm','1','0','1','0','auto','$comment_sql','{$CONFIG['site']['default_firm']}','$sum','$tm')");
		$doc = $db->insert_id;
                $db->insertA('doc_list_pos', array('doc'=>$doc, 'tovar'=>$this->conf_work_pos_id, 'cnt'=>1, 'cost'=>$sum));
                $comment .= "\nНачислено по документу $doc\n";
            }
            echo $comment."\n\n";
            $mail_text .= $comment;
        }
        mailto($CONFIG['site']['doc_adm_email'], "Salary script", $mail_text);
    }

    // Расчёт ликвидности на текущую дату с кешированием
    protected function getLiquidityOnDate($date) {
        $sdate = date("Ymd", $date);
        if ($this->conf_use_liq && $sdate != $this->last_liq_date) {
            $this->last_liq_array = getLiquidityOnDate($date - 1);
            $this->last_liq_date = $sdate;
        }
        return $this->last_liq_array;
    }

    function finalize() {
        
    }

}
