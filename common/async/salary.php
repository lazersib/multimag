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

require_once($CONFIG['location'] . "/common/asyncworker.php");
require_once($CONFIG['site']['location'] . "/include/doc.core.php");
require_once($CONFIG['site']['location'] . "/include/doc.nulltype.php");

/// Ассинхронный обработчик. Расчёт вознаграждений.
class salary extends \AsyncWorker {
    protected $docs;
    protected $plus_docs;
    protected $last_liq_date = '';
    protected $last_liq_array = array();
    protected $users_fee = array();
    protected $param_pcs_id;        // Параметр - id свойства товара - сложность отгрузки и перемещения кладовщиком
    protected $param_pcs_in_id;      // Параметр - id свойства товара - сложность поступления кладовщиком
    protected $param_bigpack_id;    // Параметр - id свойства товара - количество товара в большой упаковке
    
    protected $conf_enable          = false;//< Разрешена ли работа модуля
    protected $conf_sk_re_pack_coeff= 0.5;  //< Коэффициент вознаграждения кладовщику реализации за упаковки
    protected $conf_sk_po_pack_coeff= 0.5;  //< Коэффициент вознаграждения кладовщику поступления за упаковки
    protected $conf_sk_pe_pack_coeff= 0.5;  //< Коэффициент вознаграждения кладовщику перемещения за упаковки
    protected $conf_sk_cnt_coeff    = 1;    //< Коэффициент вознаграждения кладовщику реализации за кол-во товара в накладной
    protected $conf_sk_place_coeff  = 2;    //< Коэффициент вознаграждения кладовщику реализации за кол-во различных мест в накладной
    protected $conf_sk_bigpack_coeff = 5;   //< Коэффициент усложнения сборки большой упаковки
    protected $conf_manager_id      = null; //< id пользователя-менеджера для начисления вознаграждения
    protected $conf_author_coeff    = 0.01; //< Коэффициент вознаграждения автору реализации
    protected $conf_resp_coeff      = 0.02; //< Коэффициент вознаграждения ответственному агента
    protected $conf_manager_coeff   = 0.005;//< Коэффициент вознаграждения менеджеру магазина
    protected $conf_use_liq         = false;//< Учитывать ли ликвидность при расчёте вознаграждения с товарной наценки
    protected $conf_liq_coeff       = 0.5;  //< Коэффициент влияния ликвидности на вознаграждение с товарной наценки
    protected $conf_work_pos_id     = 1;    //< id услуги "работа"
    protected $conf_debug           = true; //< Для отладки
    
    protected $users_salary_info = array();
    protected $pos_info = array();

    protected $pc = 0;
    
    protected $ppi = array(); // Pos prices info
    
    public function __construct($task_id) {
        global $CONFIG;
        parent::__construct($task_id);
        if (isset($CONFIG['salary']['enable'])) {
            $this->conf_enable = $CONFIG['salary']['enable'];
        }
        if (isset($CONFIG['salary']['sk_re_pack_coeff'])) {
            $this->conf_sk_re_pack_coeff = $CONFIG['salary']['sk_re_pack_coeff'];
        }
        if (isset($CONFIG['salary']['sk_po_pack_coeff'])) {
            $this->conf_sk_po_pack_coeff = $CONFIG['salary']['sk_po_pack_coeff'];
        }
        if (isset($CONFIG['salary']['sk_pe_pack_coeff'])) {
            $this->conf_sk_pe_pack_coeff = $CONFIG['salary']['sk_pe_pack_coeff'];
        }
        if (isset($CONFIG['salary']['sk_cnt_coeff'])) {
            $this->conf_sk_cnt_coeff = $CONFIG['salary']['sk_cnt_coeff'];
        }
        if (isset($CONFIG['salary']['sk_place_coeff'])) {
            $this->conf_sk_place_coeff = $CONFIG['salary']['sk_place_coeff'];
        }
        if (isset($CONFIG['salary']['sk_bigpack_coeff'])) {
            $this->conf_sk_bigpack_coeff = $CONFIG['salary']['sk_bigpack_coeff'];
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
        if(!$this->conf_enable) {
            return;
        }
        //$db->query("FLUSH TABLE CACHE");
        $this->loadPosData();        
        // Расчёт
        $tmp = microtime(true);
        $db->startTransaction();
        $this->loadPosTypes();
        $this->responsibles=array();
        $res = $db->query("SELECT `id`, `name`, `responsible` FROM `doc_agent` ORDER BY `id`");
        while($line = $res->fetch_assoc()) {
            $this->responsibles[$line['id']] = $line['responsible'];            
        }
        $this->calc();
        echo " Done\n";
        $this->payFee();
        $db->commit();
        echo "Commit!";
    }
    
    // Получить необходимые данные о номенклатуре
    function loadPosData() {
        global $db;
        // ID параметра сложности отгрузки и перемещения
        $res = $db->query("SELECT `id` FROM `doc_base_params` WHERE `codename`='pack_complexity_sk'");
        if (!$res->num_rows) {
            $db->query("INSERT INTO `doc_base_params` (`name`, `codename`, `type`, `hidden`)"
                . " VALUES ('Cложность комплектации кладовщиком', 'pack_complexity_sk', 'float', 1)");
            throw new \Exception("Параметр начисления зарплаты не был найден. Параметр создан. Перед начислением заработной платы необходимо заполнить свойства номенклатуры.");
        }
        list($this->param_pcs_id) = $res->fetch_row();
        // ID параметра сложности поступления
        $res = $db->query("SELECT `id` FROM `doc_base_params` WHERE `codename`='pack_complexity_sk_in'");
        if (!$res->num_rows) {
            $db->query("INSERT INTO `doc_base_params` (`name`, `codename`, `type`, `hidden`)"
                . " VALUES ('Cложность поступления кладовщиком', 'pack_complexity_sk_in', 'float', 1)");
            throw new \Exception("Параметр начисления зарплаты не был найден. Параметр создан. Перед начислением заработной платы необходимо заполнить свойства номенклатуры.");
        }
        list($this->param_pcs_in_id) = $res->fetch_row();
        // ID параметра большой упаковки
        $res = $db->query("SELECT `id` FROM `doc_base_params` WHERE `codename`='bigpack_cnt'");
        if (!$res->num_rows) {
            $db->query("INSERT INTO `doc_base_params` (`name`, `codename`, `type`, `hidden`)"
                . " VALUES ('Кол-во в большой упаковке', 'bigpack_cnt', 'int', 0)");
            throw new \Exception("Параметр *bigpack_cnt - кол-во в большой упаковке*. Параметр создан. Перед начислением заработной платы необходимо заполнить свойства номенклатуры.");
        }
        list($this->param_bigpack_id) = $res->fetch_row();
        // Загружаем данные номенклатуры
        $res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`mult`, `doc_base`.`name`, `doc_base`.`vc`, `doc_base`.`proizv` AS `vendor`"
                . ", `pcs_t`.`value` AS `pcs`, `pcs_tin`.`value` AS `pcs_in`, `bp_t`.`value` AS `bigpack_cnt`"
            . " FROM `doc_base`"
            . " LEFT JOIN `doc_base_values` AS `pcs_t` ON `pcs_t`.`id`=`doc_base`.`id` AND `pcs_t`.`param_id`='{$this->param_pcs_id}'"
            . " LEFT JOIN `doc_base_values` AS `pcs_tin` ON `pcs_tin`.`id`=`doc_base`.`id` AND `pcs_tin`.`param_id`='{$this->param_pcs_in_id}'"
            . " LEFT JOIN `doc_base_values` AS `bp_t` ON `bp_t`.`id`=`doc_base`.`id` AND `bp_t`.`param_id`='{$this->param_bigpack_id}'"
            . " ORDER BY `doc_base`.`id`");
        while($line = $res->fetch_assoc()) {
            if ($line['mult']==0) {
                $line['mult'] = 1;
            }
            if ($line['pcs']===null) {
                $line['pcs'] = 1;
            }
            if ($line['pcs_in']===null) {
                $line['pcs_in'] = 1;
            }
            $this->pos_info[$line['id']] = $line;
        }
    }
        
    function loadDocs() {
        global $db;
        $this->docs = array();
        $rdate = strtotime("2016-12-01");
        // Грузим
        $docs_res = $db->query("SELECT `id`, `type`, `date`, `user`, `sum`, `p_doc`, `contract`, `sklad` AS `store_id`, `agent` AS `agent_id`"
            . ", `doc_dopdata`.`value` AS `return`"
            . " FROM `doc_list`"
            . " LEFT JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list`.`id` AND `doc_dopdata`.`param`='return'"
            . " WHERE `ok`>0 AND `mark_del`=0 AND `type` IN (1, 2, 8, 20) AND `date`<'$rdate'" 
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
            $this->docs[$doc_line['id']] = $doc_line;
        }
    }
    
    function calc() {
        global $db;
        $this->loadDocs();       
        // Начисление зарплаты
        $cnt = 0;       
        foreach ($this->docs as $id => $doc) {
            $need_calc = false;
            if(isset($doc['vars']['salary'])) {
                $old_salary = json_decode($doc['vars']['salary'], true);
                if(!$old_salary) {
                    $old_salary = array();
                }
            }
            else {
                $old_salary = array();
            }
            $payed = false;
            if(isset($doc['vars']['payed'])) {
                if($doc['vars']['payed']) {
                    $payed = true;
                }
            }
            $responsible_id = 0;
            if(isset($this->responsibles[$doc['agent_id']])) {
                $responsible_id = $this->responsibles[$doc['agent_id']];
            }
            if($doc['type'] == 1 || $doc['type'] == 8 || $doc['type'] == 2 || $doc['type'] == 20) {
                if (!isset($old_salary['sk_uid']) && isset($doc['vars']['kladovshik'])) {
                    $need_calc = true;
                }
            }
            if($doc['type'] == 2 || $doc['type'] == 20) { // Обычные и бонусные реализации
                if (!isset($old_salary['r_uid'])) {
                    $need_calc = true;
                }
                elseif (!isset($old_salary['r_uid']) && $responsible_id>0) {
                    $need_calc = true;
                }
                elseif (!isset($old_salary['m_uid']) && $this->conf_manager_id>0) {
                    $need_calc = true;
                }                
            }
            
            if ($need_calc) {
                $new_salary = $this->calcFee($doc, $responsible_id, false, $old_salary);
                if(count($new_salary)>0) {
                    if($payed || $doc['type'] != 2) {
                        if (isset($new_salary['o_uid'])) {
                            $this->incFee('operator', $new_salary['o_uid'], $new_salary['o_fee'], $doc['id']);
                        }
                        if (isset($new_salary['r_uid'])) {
                            $this->incFee('resp', $new_salary['r_uid'], $new_salary['r_fee'], $doc['id']);
                        }
                        if (isset($new_salary['m_uid'])) {
                            $this->incFee('manager', $new_salary['m_uid'], $new_salary['m_fee'], $doc['id']);
                        }
                    } else {
                        unset($new_salary['o_uid']);
                        unset($new_salary['o_fee']);
                        unset($new_salary['r_uid']);
                        unset($new_salary['r_fee']);
                        unset($new_salary['m_uid']);
                        unset($new_salary['m_fee']);                        
                    }
                    if (isset($new_salary['sk_uid'])) {
                        $this->incFee('sk', $new_salary['sk_uid'], $new_salary['sk_fee'], $doc['id']);
                    }
                    if(count($new_salary)>0) {
                        $salary = array_merge($old_salary, $new_salary);
                        $ser_salary_sql = $db->real_escape_string(json_encode($salary, JSON_UNESCAPED_UNICODE));
                        $db->query("REPLACE `doc_dopdata` (`doc`, `param`, `value`) VALUES ($id, 'salary', '$ser_salary_sql')");
                    }
                }
            }
            echo "$id (".round($cnt/count($this->docs), 2).")\n";
            $cnt++;
            
        }
    }
    
    /// Расчитать сумму вознаграждения для заданного документа
    public function calcFee($doc, $responsible_id, $detail = false, $old_salary=array()) {
        global $db;
        $salary = array();
        $additional_sum = 0;    // Расчётная добавленная стоимость. В зависимости о настроек, может уменьшаться линейно от ликвидности
        if($doc['type']==2 || $doc['type']==20) {
            $a_likv = $this->getLiquidityOnMonth($doc['date']);
        }
        $pos_cnt = $sk_pos_fee = 0;
        $a_places = array();
            
        $res_tov = $db->query("SELECT `doc_list_pos`.`id`, `doc_list_pos`.`tovar` AS `pos_id`, `doc_list_pos`.`cost`, `doc_list_pos`.`cnt`,
                 `doc_base_cnt`.`mesto`
             FROM `doc_list_pos`
             INNER JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$doc['store_id']}'
             WHERE `doc_list_pos`.`doc`='{$doc['id']}'");
        while ($nxt_tov = $res_tov->fetch_assoc()) {
            $pos_extinfo = $this->pos_info[$nxt_tov['pos_id']];
            if($detail) {
                $det_line = array();
                $det_line['id'] = $nxt_tov['pos_id'];
                $det_line['name'] = $pos_extinfo['name'];
                $det_line['vendor'] = $pos_extinfo['vendor'];
                $det_line['vc'] = $pos_extinfo['vc'];
                $det_line['cnt'] = $nxt_tov['cnt'];
                $det_line['pcs'] = $pos_extinfo['pcs'];
                $det_line['mult'] = $pos_extinfo['mult'];
                $det_line['bigpack_cnt'] = $pos_extinfo['bigpack_cnt'];
            }
            // Продавцам и пр
            if($doc['type']==2 || $doc['type']==20) {
                if($detail) {
                    $det_line['in_price'] = 0;
                    $det_line['price'] = $nxt_tov['cost'];                    
                    $det_line['pos_liq'] = 0;
                }
                if ($this->conf_use_liq && isset($a_likv[$nxt_tov['pos_id']])) {
                    $p_sum = ($nxt_tov['cost']) * $nxt_tov['cnt'] * (1 - $a_likv[$nxt_tov['pos_id']] * $this->conf_liq_coeff / 100 );
                    if($detail) {
                        $det_line['pos_liq'] = $a_likv[$nxt_tov['pos_id']];
                    }
                } else {
                    $p_sum = ($nxt_tov['cost']) * $nxt_tov['cnt'];
                }
                $p_sum = round($p_sum, 2);
                if($detail) {
                    $det_line['p_sum'] = $p_sum;
                }
                $additional_sum += $p_sum;
            }
            // Кладовщикам            
            $a_places[intval($nxt_tov['mesto'])] = 1;
            if($pos_extinfo['bigpack_cnt']>0) {
                $bigpacks = floor($nxt_tov['cnt'] / $pos_extinfo['bigpack_cnt']);
                $normpacks = ($nxt_tov['cnt'] - $bigpacks * $pos_extinfo['bigpack_cnt']) / $pos_extinfo['mult'];
                if ($doc['type'] == 1) {
                    $sk_cur_fee = $bigpacks * $pos_extinfo['pcs_in'] * $this->conf_sk_bigpack_coeff;   // Big
                    $sk_cur_fee += $normpacks * $pos_extinfo['pcs_in'];                                // Normal
                } else {
                    $sk_cur_fee = $bigpacks * $pos_extinfo['pcs'] * $this->conf_sk_bigpack_coeff;   // Big
                    $sk_cur_fee += $normpacks * $pos_extinfo['pcs'];                                // Normal
                }
            } else {
                if ($doc['type'] == 1) {
                    $sk_cur_fee = $nxt_tov['cnt'] / $pos_extinfo['mult'] * $pos_extinfo['pcs_in'];         // Normal only
                } else {
                    $sk_cur_fee = $nxt_tov['cnt'] / $pos_extinfo['mult'] * $pos_extinfo['pcs'];         // Normal only
                }
            }
            $sk_cur_fee = round($sk_cur_fee, 2);
            if($detail) {
                $det_line['sk_fee'] = $sk_cur_fee;
            }
            $sk_pos_fee += $sk_cur_fee;
            $pos_cnt++;
            if($detail) {
                $salary['detail'][] = $det_line;
            } 
        }
        $res_tov->free();
        // Подготовка результата
        
        // Для реализации
        if ( $doc['type'] == 2 || $doc['type']==20) {
            if ($doc['user'] && !isset($old_salary['o_uid'])) {
                $salary['o_fee'] = round($additional_sum * $this->conf_author_coeff, 2);
                $salary['o_uid'] = $doc['user'];
            }
            if ($responsible_id && !isset($old_salary['r_uid'])) {
                $salary['r_fee'] = round($additional_sum * $this->conf_resp_coeff, 2);
                $salary['r_uid'] = $responsible_id;
            }
            if ($this->conf_manager_id && !isset($old_salary['m_uid'])) {
                $salary['m_fee'] = round($additional_sum * $this->conf_manager_coeff, 2);
                $salary['m_uid'] = $this->conf_manager_id;
            }
            if($detail) {
                $salary['o_coeff'] = $this->conf_author_coeff;
                $salary['r_coeff'] = $this->conf_resp_coeff;
                $salary['m_coeff'] = $this->conf_manager_coeff;
                $salary['r_sum'] = $additional_sum;
                if ($this->conf_use_liq) {
                    $salary['liq_coeff'] = $this->conf_liq_coeff;
                } else {
                    $salary['liq_coeff'] = 0;
                }
            }
        }
        
        if( isset( $doc['vars']['kladovshik'] ) ) {  
            if( $doc['vars']['kladovshik']  && !isset($old_salary['sk_uid'])) {
                switch($doc['type']) {
                    case 1:
                        $sk_coeff = $this->conf_sk_po_pack_coeff;                
                        break;
                    case 2:
                    case 20:
                        $sk_coeff = $this->conf_sk_re_pack_coeff;                    
                        break;
                    case 8:
                        $sk_coeff = $this->conf_sk_pe_pack_coeff;
                        break;
                    default:
                        $sk_coeff = 0;
                }
                $sk_fee = $sk_pos_fee * $sk_coeff + count($a_places) * $this->conf_sk_place_coeff + $pos_cnt * $this->conf_sk_cnt_coeff;
                $salary['sk_uid'] = intval($doc['vars']['kladovshik']);
                $salary['sk_fee'] = round($sk_fee, 2);
                if($detail) {
                    $salary['sk_coeff'] = $sk_coeff;
                    $salary['sk_pl_coeff'] = $this->conf_sk_place_coeff;
                    $salary['sk_cnt_coeff'] = $this->conf_sk_cnt_coeff;
                    $salary['sk_packfee'] = round($sk_pos_fee, 2);
                    $salary['sk_places'] = count($a_places);
                    $salary['sk_pcnt'] = $pos_cnt;                
                }
            }
        }
        return $salary;
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
            $this->users_fee[$uid] = array('operator'=>0, 'resp'=>0, 'manager'=>0, 
                'sk'=>0, 'sk_pos'=>0, 'sk_cnt'=>0, 'sk_pls'=>0, 'sk_in'=>0, 'sk_out'=>0, 'sk_move'=>0, 'dcnt'=>0,
                'docs'=>array());
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
                $comment .= "\nПользователь не найден! Вознаграждение не начислено.\n";
            } elseif(!$user_info['agent_id']) {
                $comment .= "\nПользователь не привязан к агенту! Вознаграждение не начислено.\n";
            } else {
                $tm = time();
                $doc_type = 1;
                $subtype = 'fee';
                $i_doc = \document::getInstanceFromType($doc_type);
                $altnum = $i_doc->getNextAltNum($doc_type, $subtype, date('Y-m-d', $tm), $CONFIG['site']['default_firm']);
                $comment_sql = $db->real_escape_string($comment);
                $res = $db->query("INSERT INTO doc_list (`type`,`agent`,`date`,`sklad`,`user`,`nds`,`altnum`,`subtype`,`comment`,`firm_id`, `sum`,`ok`)
		VALUES ('$doc_type','{$user_info['agent_id']}','$tm','1','0','1','$altnum','$subtype','$comment_sql','{$CONFIG['site']['default_firm']}','$sum','$tm')");
		$doc = $db->insert_id;
                $db->insertA('doc_list_pos', array('doc'=>$doc, 'tovar'=>$this->conf_work_pos_id, 'cnt'=>1, 'cost'=>$sum));
                $comment .= "\nНачислено по документу $doc\n";
            }
            echo $comment."\n\n";
            $mail_text .= $comment;
        }
        mailto($CONFIG['site']['doc_adm_email'], "Salary script", $mail_text);
    }

    // Расчёт ликвидности на начало текущего месяца с кешированием
    protected function getLiquidityOnMonth($date) {
        $sdate = date("Ym", $date);
        if ($this->conf_use_liq && $sdate != $this->last_liq_date) {
            $time = strtotime(date("Y-m-01 00:00:00", $date)) - 1;
            $this->last_liq_array = getLiquidityOnDate($time);
            $this->last_liq_date = $sdate;
        }
        return $this->last_liq_array;
    }

    function finalize() {
        
    }

}
