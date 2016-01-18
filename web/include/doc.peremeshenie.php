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


/// Документ *перемещение товара*
class doc_Peremeshenie extends doc_Nulltype
{
	/// Конструктор
        /// @param $doc id документа
	function __construct($doc=0) {
		parent::__construct($doc);
		$this->doc_type				=8;
		$this->typename				='peremeshenie';
		$this->viewname			='Перемещение товара со склада на склад';
		$this->sklad_editor_enable		=true;
		$this->header_fields			='cena separator sklad';
	}
	
	function initDefDopdata() {
		$this->def_dop_data = array('kladovshik'=>0, 'na_sklad'=>0, 'mest'=>'', 'cena'=>0);
	}

	function dopHead() {
		global $tmpl, $db;
		$klad_id = $this->dop_data['kladovshik'];
		if(!$klad_id)	$klad_id=$this->firm_vars['firm_kladovshik_id'];
		$tmpl->addContent("На склад:<br>
		<select name='nasklad'>");
		$res = $db->query("SELECT `id`,`name` FROM `doc_sklady` ORDER BY `name`");
		while($nxt = $res->fetch_row())
		{
			if($nxt[0]==$this->dop_data['na_sklad'])
				$tmpl->addContent("<option value='$nxt[0]' selected>".html_out($nxt[1])."</option>");
			else
				$tmpl->addContent("<option value='$nxt[0]'>".html_out($nxt[1])."</option>");
		}
		$tmpl->addContent("</select><br>
		Кладовщик:<br><select name='kladovshik'>
		<option value='0'>--не выбран--</option>");
		$res = $db->query("SELECT `user_id`, `worker_real_name` FROM `users_worker_info` WHERE `worker`='1' ORDER BY `worker_real_name`");
		while($nxt = $res->fetch_row())
		{
			$s=($klad_id==$nxt[0])?'selected':'';
			$tmpl->addContent("<option value='$nxt[0]' $s>".html_out($nxt[1])."</option>");
		}
		$tmpl->addContent("</select><br>
		Количество мест:<br>
		<input type='text' name='mest' value='{$this->dop_data['mest']}'><br>");
	}

	function dopSave() {
		$new_data = array(
			'na_sklad' => rcvint('nasklad'),
			'mest' => rcvint('mest'),
			'kladovshik' => rcvint('kladovshik')
		);
		$old_data = array_intersect_key($new_data, $this->dop_data);
		
		$log_data='';
		if($this->id)
			$log_data = getCompareStr($old_data, $new_data);
		$this->setDopDataA($new_data);
		if($log_data)	doc_log("UPDATE {$this->typename}", $log_data, 'doc', $this->id);
	}

    function docApply($silent = 0) {
        global $CONFIG, $db;
        $tim = time();
        $dest_store_id = intval($this->dop_data['na_sklad']);
        if (!$dest_store_id) {
            throw new Exception("Не определён склад назначения!");
        }
        if ($this->doc_data['sklad'] == $dest_store_id) {
            throw new Exception("Исходный склад совпадает со складом назначения!");
        }

        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`,
                `doc_list`.`firm_id`, `doc_sklady`.`dnc`, `doc_sklady`.`firm_id` AS `store_firm_id`, `doc_vars`.`firm_store_lock`
            FROM `doc_list`
            INNER JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
            INNER JOIN `doc_vars` ON `doc_list`.`firm_id` = `doc_vars`.`id`
            WHERE `doc_list`.`id`='{$this->id}'");
        if (!$res->num_rows) {
            throw new Exception('Документ не найден!');
        }
        $doc_info = $res->fetch_assoc();
        
        $dest_store_info = $db->selectRow('doc_sklady', $dest_store_id);

        if ($doc_info['ok'] && (!$silent)) {
            throw new Exception('Документ уже был проведён!');
        }
        if (!$this->dop_data['mest'] && @$CONFIG['doc']['require_pack_count'] && !$silent) {
            throw new Exception("Количество мест не задано");
        }

        // Запрет на списание со склада другой фирмы
        if ($doc_info['store_firm_id'] != null && $doc_info['store_firm_id'] != $doc_info['firm_id']) {
            throw new Exception("Исходный склад принадлежит другой организации!");
        }
        if ($dest_store_info['firm_id'] != null && $dest_store_info['firm_id'] != $doc_info['firm_id']) {
            throw new Exception("Склад назначения принадлежит другой организации!");
        }
        // Ограничение фирмы списком своих складов
        if ($doc_info['firm_store_lock'] && ($doc_info['store_firm_id'] != $doc_info['firm_id'] || $dest_store_info['firm_id'] != $doc_info['firm_id']) ) {
            throw new Exception("Выбранная организация может работать только со своими складами!");
        }

        $res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt`, `doc_base`.`name`, `doc_base`.`proizv`, 
                `doc_base`.`pos_type`, `doc_base`.`vc`
            FROM `doc_list_pos`
            LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
            LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$doc_info['sklad']}'
            WHERE `doc_list_pos`.`doc`='{$this->id}'");
        $fail_text = '';
        while ($nxt = $res->fetch_row()) {
            if ($nxt[5] > 0) {
                throw new Exception("Перемещение услуги '$nxt[3]:$nxt[4]' недопустимо!");
            }
            if (!$doc_info['dnc'] && ($nxt[1] > $nxt[2])) {
                $pos_name = composePosNameStr($nxt[0], $nxt[6], $nxt[3], $nxt[4]);
                $fail_text .= " - Мало товара '$pos_name' -  есть:{$nxt[2]}, нужно:{$nxt[1]}. \n";
                continue;
            }
            $db->query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`-'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='{$doc_info['sklad']}'");
            $db->query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`+'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='$dest_store_id'");
            // Если это первое поступление
            if ($db->affected_rows == 0) {
                $db->query("INSERT INTO `doc_base_cnt` (`id`, `sklad`, `cnt`) VALUES ('$nxt[0]', '$dest_store_id', '$nxt[1]')");
            }

            if ((!$doc_info['dnc']) && (!$silent)) {
                $budet = getStoreCntOnDate($nxt[0], $doc_info['sklad']);
                if ($budet < 0) {
                    $pos_name = composePosNameStr($nxt[0], $nxt[6], $nxt[3], $nxt[4]);
                    $t = $budet + $nxt[1];
                    $fail_text .= " - Будет мало товара '$pos_name' - есть:$t, нужно:{$nxt[1]}. \n";
                    continue;
                }
            }
        }
        
        if($fail_text) {
            throw new Exception("Ошибка в номенклатуре: \n".$fail_text);
        }
        
        $res->free();
        if ($silent) {
            return;
        }
        $db->query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->id}'");
        $this->sentZEvent('apply');
    }

    function docCancel() {
		global $db;
		$nasklad = (int)$this->dop_data['na_sklad'];

		$res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`, `doc_sklady`.`dnc`
		FROM `doc_list`
		LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
		WHERE `doc_list`.`id`='{$this->id}'");
		if(!$res->num_rows)			throw new Exception('Документ не найден!');
		$nx = $res->fetch_assoc();
		if(!$nx['ok'])				throw new Exception('Документ не проведён!');
		$res = $db->query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='{$this->id}'");
		$res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt`, `doc_base`.`name`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$nx['sklad']}'
		WHERE `doc_list_pos`.`doc`='{$this->id}'");
		while($nxt = $res->fetch_row()) {
			$db->query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`-'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='$nasklad'");
			$db->query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`+'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='{$nx['sklad']}'");
			if(!$nx['dnc'])	{
				$budet=getStoreCntOnDate($nxt[0], $nx['sklad']);
				if($budet<0)			throw new Exception("Невозможно, т.к. будет недостаточно ($budet) товара '$nxt[3]' !");
			}
		}
	}

}
