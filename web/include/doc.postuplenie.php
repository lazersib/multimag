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
/// Документ *Поступление*
class doc_Postuplenie extends doc_Nulltype {

    /// Конструктор
    function __construct($doc = 0) {
        parent::__construct($doc);
        $this->doc_type = 1;
        $this->typename = 'postuplenie';
        $this->viewname = 'Поступление товара на склад';
        $this->sklad_editor_enable = true;
        $this->header_fields = 'sklad cena separator agent';
    }

    function initDefDopdata() {
        $this->def_dop_data = array('kladovshik' => $this->firm_vars['firm_kladovshik_id'], 'input_doc' => '', 'input_date' => '', 'return' => 0, 'cena' => 1);
    }

    function dopHead() {
        global $tmpl, $db;
        $klad_id = $this->dop_data['kladovshik'];
        if (!$klad_id)
            $klad_id = $this->firm_vars['firm_kladovshik_id'];
        $tmpl->addContent("<hr>");
        $tmpl->addContent("Ном. вх. документа:<br><input type='text' name='input_doc' value='{$this->dop_data['input_doc']}'><br>");
        $tmpl->addContent("Дата. вх. документа:<br><input type='text' name='input_date' value='{$this->dop_data['input_date']}'><br>");
        $checked = $this->dop_data['return'] ? 'checked' : '';
        $tmpl->addContent("<label><input type='checkbox' name='return' value='1' $checked>Возвратный документ</label><hr>
		Кладовщик:<br><select name='kladovshik'>
		<option value='0'>--не выбран--</option>");
        $res = $db->query("SELECT `user_id`, `worker_real_name` FROM `users_worker_info` WHERE `worker`='1' ORDER BY `worker_real_name`");
        while ($nxt = $res->fetch_row()) {
            $s = ($klad_id == $nxt[0]) ? 'selected' : '';
            $tmpl->addContent("<option value='$nxt[0]' $s>" . html_out($nxt[1]) . "</option>");
        }
        $tmpl->addContent("</select><br>");
    }

    function dopSave() {
        $new_data = array(
            'input_doc' => request('input_doc'),
            'input_date' => rcvdate('input_date'),
            'return' => rcvint('return'),
            'kladovshik' => rcvint('kladovshik')
        );
        $old_data = array_intersect_key($new_data, $this->dop_data);

        $log_data = '';
        if ($this->id) {
            $log_data = getCompareStr($old_data, $new_data);
        }
        $this->setDopDataA($new_data);
        if ($log_data) {
            doc_log("UPDATE {$this->typename}", $log_data, 'doc', $this->id);
        }
    }

    /// Выполнение дополнительных проверок доступа для проведения документа
    public function extendedApplyAclCheck() {
        $acl_obj = ['store.global', 'store.'.$this->doc_data['sklad']];      
        if (!\acl::testAccess($acl_obj, \acl::APPLY)) {
           $d_start = date_day(time());
            $d_end = $d_start + 60 * 60 * 24 - 1;
            if (!\acl::testAccess($acl_obj, \acl::TODAY_APPLY)) {
                throw new \AccessException('Не достаточно привилегий для проведения документа с выбранным складом '.$this->doc_data['sklad']);
            } elseif ($this->doc_data['date'] < $d_start || $this->doc_data['date'] > $d_end) {
                throw new \AccessException('Не достаточно привилегий для проведения документа с выбранным складом '.$this->doc_data['sklad'].' произвольной датой');
            }
        }
        parent::extendedApplyAclCheck();
    }
    
    /// Выполнение дополнительных проверок доступа для отмены документа
    public function extendedCancelAclCheck() {
        $acl_obj = ['store.global', 'store.'.$this->doc_data['sklad']];      
        if (!\acl::testAccess($acl_obj, \acl::CANCEL)) {
           $d_start = date_day(time());
            $d_end = $d_start + 60 * 60 * 24 - 1;
            if (!\acl::testAccess($acl_obj, \acl::TODAY_CANCEL)) {
                throw new \AccessException('Не достаточно привилегий для отмены проведения документа с выбранным складом '.$this->doc_data['sklad']);
            } elseif ($this->doc_data['date'] < $d_start || $this->doc_data['date'] > $d_end) {
                throw new \AccessException('Не достаточно привилегий для отмены проведения документа с выбранным складом '.$this->doc_data['sklad'].' произвольной датой');
            }
        }
        parent::extendedCancelAclCheck();
    }
        
    public function docApply($silent = 0) {
        global $db;
        if(!$this->isAltNumUnique() && !$silent) {
            throw new Exception("Номер документа не уникален!");
        }
        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`, `doc_list`.`firm_id`,
                `doc_sklady`.`dnc`, `doc_sklady`.`firm_id` AS `store_firm_id`, `doc_vars`.`firm_store_lock`, `doc_list`.`p_doc`
            FROM `doc_list`
            INNER JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
            INNER JOIN `doc_vars` ON `doc_list`.`firm_id` = `doc_vars`.`id`
            WHERE `doc_list`.`id`='{$this->id}'");
        $doc_params = $res->fetch_assoc();
        $res->free();
        
        if (!$doc_params) {
            throw new \Exception('Документ ' . $this->id . ' не найден');
        }
        if ($doc_params['ok'] && (!$silent)) {
            throw new \Exception('Документ уже проведён!');
        }
        
        // Запрет на списание со склада другой фирмы
        if($doc_params['store_firm_id']!=null && $doc_params['store_firm_id']!=$doc_params['firm_id']) {
            throw new \Exception("Выбранный склад принадлежит другой организации!");
        }
        // Ограничение фирмы списком своих складов
        if($doc_params['firm_store_lock'] && $doc_params['store_firm_id']!=$doc_params['firm_id']) {
            throw new \Exception("Выбранная организация может работать только со своими складами!");
        }
        
        $res = $db->query("SELECT `doc_list_pos`.`tovar` AS `pos_id`, `doc_list_pos`.`cnt`, `doc_base`.`pos_type`, `doc_list_pos`.`id` AS `line_id`
                    , `doc_list_pos`.`cost` AS `line_price`, `doc_base`.`cost` AS `base_price`, `doc_base_cnt`.`mesto` AS `place`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
                LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$doc_params['sklad']}'
		WHERE `doc_list_pos`.`doc`='{$this->id}' AND `doc_base`.`pos_type`='0'");
        while ($line = $res->fetch_assoc()) {
            if(\cfg::get('doc', 'restrict_in_noplace') && !$silent) {
                if( ($line['place']=='' || $line['place']==='0') && $line['pos_type']==0 ) {
                    throw new \Exception("У товара ID:{$line['pos_id']} не задано место хранения. Проведение поступления без места хранения запрещено в настройках.");
                }
            }
            $db->query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`+'{$line['cnt']}' WHERE `id`='{$line['pos_id']}' AND `sklad`='{$doc_params['sklad']}'");
            // Если это первое поступление
            if ($db->affected_rows == 0) {
                $db->query("INSERT INTO `doc_base_cnt` (`id`, `sklad`, `cnt`) VALUES ('{$line['pos_id']}', '{$doc_params['sklad']}', '{$line['cnt']}')");
            }
            if(\cfg::get('poseditor', 'sn_restrict')) {
                $r = $db->query("SELECT COUNT(`doc_list_sn`.`id`) FROM `doc_list_sn` WHERE `prix_list_pos`='{$line['line_id']}'");
                $sn_data = $r->fetch_row();
                if ($sn_data[0] != $line['cnt']) {
                    throw new \Exception("Количество серийных номеров товара {$line['pos_id']} ({$line['cnt']})"
                        . " не соответствует количеству серийных номеров ($sn_data[0])");
                }
            }
            if(\cfg::get('doc', 'update_in_cost') == 1 && (!$silent)) {
                if ($line['line_price'] != $line['base_price']) {
                    $db->query("UPDATE `doc_base` SET `cost`='{$line['line_price']}', `cost_date`=NOW() WHERE `id`='{$line['pos_id']}'");
                    
                    $array = ['price'=>['old'=>$line['base_price'], 'new'=>$line['line_price']]];
                    doc_log("UPDATE", json_encode($array, JSON_UNESCAPED_UNICODE), 'pos', $line['pos_id']);
                }
            }
            
        }
        if ($silent) {
            return;
        }
        $db->update('doc_list', $this->id, 'ok', time());
        // Транзиты
        if($doc_params['p_doc']) {
            $res = $db->query("SELECT `id`, `ok` FROM `doc_list` WHERE `ok`>0 AND `type`=12 AND `id`={$doc_params['p_doc']}");
            if ($res->num_rows) {
                $res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`
                    FROM `doc_list_pos`
                    LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
                    WHERE `doc_list_pos`.`doc`='{$doc_params['p_doc']}'");
                $vals = '';
                while ($line = $res->fetch_row()) {
                    if ($vals) {
                        $vals .= ',';
                    }
                    $vals .= "('$line[0]', '$line[1]')";
                }
                if($vals) {
                    $db->query("INSERT INTO `doc_base_dop` (`id`, `transit`) VALUES $vals
                       ON DUPLICATE KEY UPDATE `transit`=`transit`-VALUES(`transit`)");
                } else {
                    throw new \Exception("Не удалось провести пустой документ!");
                }
            }
        }
        if(\cfg::get('doc', 'update_in_cost') == 2) {
            $res = $db->query("SELECT `doc_list_pos`.`tovar` AS `pos_id`, `doc_base`.`cost` AS `base_price`
                FROM `doc_list_pos`
                LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
                WHERE `doc_list_pos`.`doc`='{$this->id}' AND `doc_base`.`pos_type`='0'");
            while ($line = $res->fetch_assoc()) {
                $acp = getInCost($line['pos_id'], $doc_params['date']);
                if ($line['base_price'] != $acp) {
                    $db->query("UPDATE `doc_base` SET `cost`='$acp', `cost_date`=NOW() WHERE `id`='{$line['pos_id']}'");
                    $array = ['price'=>['old'=>$line['base_price'], 'new'=>$acp]];
                    doc_log("UPDATE", json_encode($array, JSON_UNESCAPED_UNICODE), 'pos', $line['pos_id']);
                }
            }
        }
        $this->sentZEvent('apply');
    }

    function docCancel() {
        global $db;
        $rs = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`, `doc_sklady`.`dnc`
		FROM `doc_list`
		LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
		WHERE `doc_list`.`id`='{$this->id}'");
        if (!$rs->num_rows)
            throw new Exception("Документ {$this->id} не найден!");
        $nx = $rs->fetch_assoc();
        if (!$nx['ok'])
            throw new Exception("Документ ещё не проведён!");

        $db->update('doc_list', $this->id, 'ok', 0);

        $res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`pos_type`, `doc_base`.`vc`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$nx['sklad']}'
		WHERE `doc_list_pos`.`doc`='{$this->id}'");
        while ($nxt = $res->fetch_row()) {
            if ($nxt[5] == 0) {
                if (!$nx['dnc']) {
                    if ($nxt[1] > $nxt[2]) {
                        $budet = $nxt[2] - $nxt[1];
                        $badpos = $nxt[0];
                        throw new Exception("Невозможно, т.к. будет недостаточно ($budet) товара '$nxt[3]:$nxt[4] - $nxt[6]($nxt[0])' на складе!");
                    }
                }
                $db->query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`-'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='{$nx['sklad']}'");
                if (!$nx['dnc']) {
                    $budet = getStoreCntOnDate($nxt[0], $nx['sklad']);
                    if ($budet < 0) {
                        $badpos = $nxt[0];
                        throw new Exception("Невозможно, т.к. будет недостаточно ($budet) товара '$nxt[3]:$nxt[4] - $nxt[6]($nxt[0])' !");
                    }
                }
            }
        }
        // Транзиты
        if($this->doc_data['p_doc']) {
            $res = $db->query("SELECT `id`, `ok` FROM `doc_list` WHERE `ok`>0 AND `type`=12 AND `id`={$this->doc_data['p_doc']}");
            if ($res->num_rows) {
                $res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`
                    FROM `doc_list_pos`
                    LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
                    WHERE `doc_list_pos`.`doc`='{$this->doc_data['p_doc']}'");
                $vals = '';
                while ($nxt = $res->fetch_row()) {
                    if ($vals) {
                        $vals .= ',';
                    }
                    $vals .= "('$nxt[0]', '$nxt[1]')";
                }
                if($vals) {
                    $db->query("INSERT INTO `doc_base_dop` (`id`, `transit`) VALUES $vals
                        ON DUPLICATE KEY UPDATE `transit`=`transit`+VALUES(`transit`)");
                }
            }
        }
        $this->sentZEvent('cancel');
    }

    // Формирование другого документа на основании текущего
    function morphTo($target_type) {
        global $tmpl, $db;
        if ($target_type == '') {
            $tmpl->ajax = 1;
            $tmpl->addContent("<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->id}&amp;tt=2'\">Реализация</div>");
            $tmpl->addContent("<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->id}&amp;tt=5'\">Расходный банковский ордер</div>");
            $tmpl->addContent("<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->id}&amp;tt=7'\">Расходный кассовый ордер</div>");
        } else if ($target_type == 2) {
            \acl::accessGuard('doc.realizaciya', \acl::CREATE);
            $db->startTransaction();
            $new_doc = new doc_Realizaciya();
            $dd = $new_doc->createFromP($this);
            $db->commit();
            header("Location: doc.php?mode=body&doc=$dd");
        }
        else {
            if ($target_type == 5) {
                \acl::accessGuard('doc.rbank', \acl::CREATE);
                $classNameNewDocument = 'doc_RBank';
            }
            else if ($target_type == 7) {
                \acl::accessGuard('doc.rko', \acl::CREATE);
                $classNameNewDocument = 'doc_Rko';
            }
            else{
                return;
            }
            $this->recalcSum();
            $db->startTransaction();
            $new_doc = new $classNameNewDocument();
            $doc_num = $new_doc->createFrom($this);
            $codeName =
                isset($this->dop_data['return']) && $this->dop_data['return']
                    ?'goods_return'
                    :'goods_buy';
            $resource = $db->query("SELECT `id` FROM `doc_dtypes` WHERE `codename`='$codeName'");
            if($resource->num_rows) {
                $result = $resource->fetch_assoc();
                $new_doc->setDopData('rasxodi', $result['id']);
            }
            $db->commit();
            header('Location: doc.php?mode=body&doc=' . $doc_num);
        }
    }

}
