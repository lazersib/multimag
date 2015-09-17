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

/// Документ *Акт корректировки*
class doc_CorrAct extends doc_Nulltype {
    var $status_list;

    function __construct($doc = 0) {
        parent::__construct($doc);
        $this->doc_type = 25;
        $this->allow_neg_cnt = true;
        $this->typename = 'corract';
        $this->viewname = 'Акт корректировки';
        $this->sklad_editor_enable = true;
        $this->header_fields = 'sklad cena';
    }
    
    function initDefDopdata() {
        $this->def_dop_data = array('cena' => 0);
    }

    /// Провести документ
    function docApply($silent = 0) {
        global $db;
        if(!$this->isAltNumUnique() && !$silent) {
            throw new Exception("Номер документа не уникален!");
        }
        $tim = time();
        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`, `doc_list`.`firm_id`,
                `doc_sklady`.`dnc`, `doc_sklady`.`firm_id` AS `store_firm_id`, `doc_vars`.`firm_store_lock`, `doc_list`.`p_doc`
            FROM `doc_list`
            INNER JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
            INNER JOIN `doc_vars` ON `doc_list`.`firm_id` = `doc_vars`.`id`
            WHERE `doc_list`.`id`='{$this->id}'");
        $doc_params = $res->fetch_assoc();
        $res->free();
        if ($doc_params['ok'] && (!$silent)) {
            throw new Exception('Документ уже был проведён!');
        }
        // Запрет на списание со склада другой фирмы
        if($doc_params['store_firm_id']!=null && $doc_params['store_firm_id']!=$doc_params['firm_id']) {
            throw new Exception("Выбранный склад принадлежит другой организации!");
        }
        // Ограничение фирмы списком своих складов
        if($doc_params['firm_store_lock'] && $doc_params['store_firm_id']!=$doc_params['firm_id']) {
            throw new Exception("Выбранная организация может списывать только со своих складов!!");
        }
        
        if (!$silent) {
            $db->query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->id}'");
        }

        $res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt`, `doc_base`.`name`, `doc_base`.`proizv`,
                `doc_base`.`pos_type`, `doc_list_pos`.`id`, `doc_base`.`vc`, `doc_list_pos`.`cost`
            FROM `doc_list_pos`
            LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
            LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$doc_params['sklad']}'
        WHERE `doc_list_pos`.`doc`='{$this->id}' AND `doc_base`.`pos_type`='0'");
        $fail_text = '';
        while ($nxt = $res->fetch_row()) {
            if($nxt[1]==0) {
                $pos_name = composePosNameStr($nxt[0], $nxt[7], $nxt[3], $nxt[4]);
                $fail_text .= " - Нулевое количество '$pos_name'\n";
                continue;
            } 
            
            if (!$doc_params['dnc']) {
                if ( ($nxt[2]+$nxt[1])<0 ) {
                    $pos_name = composePosNameStr($nxt[0], $nxt[7], $nxt[3], $nxt[4]);
                    $fail_text .= " - Мало товара '$pos_name' -  есть:{$nxt[2]}, нужно:{$nxt[1]} на складе {$doc_params['sklad']}. \n";
                    continue;
                }
            }
               

            $db->query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`+'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='{$doc_params['sklad']}'");
            // Если это первое изменение
            if ($db->affected_rows == 0) {
                $db->query("INSERT INTO `doc_base_cnt` (`id`, `sklad`, `cnt`) VALUES ('$nxt[0]', '{$doc_params['sklad']}', '$nxt[1]')");
            }
            if (!$doc_params['dnc'] && (!$silent)) {
                $budet = getStoreCntOnDate($nxt[0], $doc_params['sklad'], $doc_params['date']);
                if ($budet < 0) {
                    $pos_name = composePosNameStr($nxt[0], $nxt[7], $nxt[3], $nxt[4]);
                    $t = $budet + $nxt[1];
                    $fail_text .= " - Будет мало товара '$pos_name' - есть:$t, нужно:{$nxt[1]}. \n";
                    continue;
                }
            }
        }        
        if($fail_text) {
            throw new Exception("Ошибка в номенклатуре: \n".$fail_text);
        }        
        if ($silent) {
            return;
        }
        $this->fixPrice();
        
        $this->sentZEvent('apply');
    }

    function docCancel() {
        global $db;

        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->id}'");
        if (!$res->num_rows) {
            throw new Exception('Документ не найден!');
        }
        $nx = $res->fetch_row();
        if (!$nx[4]) {
            throw new Exception('Документ НЕ проведён!');
        }

        $res = $db->query("SELECT `id` FROM `doc_list` WHERE `p_doc`='{$this->id}' AND `ok`>'0'");
        if ($res->num_rows) {
            throw new Exception('Нельзя отменять документ с проведёнными подчинёнными документами.');
        }

        $db->query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='{$this->id}'");
        $res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base`.`pos_type` FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`	WHERE `doc_list_pos`.`doc`='{$this->id}' AND `doc_base`.`pos_type`='0'");

        while ($nxt = $res->fetch_row()) {
            $db->query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`-'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='$nx[3]'");
        }

        $this->sentZEvent('cancel');
    }
}