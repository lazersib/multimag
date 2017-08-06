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
//

/// Документ *сборка изделия*
class doc_Sborka extends doc_Nulltype {

    public function __construct($doc=0){
        parent::__construct($doc);
        $this->doc_type			= 17;
        $this->typename			= 'sborka';
        $this->viewname		= 'Сборка изделия';
        $this->sklad_editor_enable	= true;
        $this->header_fields		= 'agent cena sklad';
        settype($this->id,'int');
    }
    
    /// Получить строку с HTML кодом дополнительных кнопок документа
    protected function getAdditionalButtonsHTML() {
        if(isset($this->dop_data['script'])) {
            if(!$this->dop_data['script']) {
                return '';
            }
            $sn = html_out($this->dop_data['script']);
            return "<a href='/doc_sc.php?mode=reopen&amp;sn=$sn&amp;doc={$this->id}' title='Передать в сценарий'><img src='img/i_launch.png' alt='users'></a>";
        }
        return '';
    }
	
    public function initDefDopdata() {
        $this->def_dop_data = array('sklad'=>0, 'cena'=>1);
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
    
    public function DocApply($silent = 0) {
        global $db;

        $pres = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`, `doc_sklady`.`dnc`
            FROM `doc_list`
            LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
            WHERE `doc_list`.`id`='{$this->id}'");
        $doc_info = $pres->fetch_assoc();
        if (!$doc_info) {
            throw new Exception("Документ {$this->id} не найден!");
        }
        if ($doc_info['ok'] && (!$silent)) {
            throw new Exception('Документ уже был проведён!');
        }
        $pres->free();
        // Списание. Сделано отдельно для списания одновременно количества со всех страниц с правильным учётом остатков на складе
        $res = $db->query("SELECT `doc_list_pos`.`tovar`, SUM(`doc_list_pos`.`cnt`) AS `cnt`, `doc_base_cnt`.`cnt` AS `sklad_cnt`, `doc_base`.`name`, `doc_base`.`proizv`,
                `doc_base`.`pos_type`, `doc_list_pos`.`id`, `doc_list_pos`.`page`, `doc_base`.`vc`
            FROM `doc_list_pos`
            INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
            LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$doc_info['sklad']}'
            WHERE `doc_list_pos`.`doc`='{$this->id}' AND `doc_base`.`pos_type`='0' AND `doc_list_pos`.`page`>0
            GROUP BY `doc_list_pos`.`tovar`");
        $fail_text = '';
        while ($line = $res->fetch_assoc()) {
            $sign = '-';
            $db->query("INSERT INTO `doc_base_cnt` (`id`, `sklad`, `cnt`) VALUES ('{$line['tovar']}', '{$doc_info['sklad']}', '{$line['cnt']}')"
                    . " ON DUPLICATE KEY UPDATE `cnt`=`cnt` $sign '{$line['cnt']}'");
            if (!$doc_info['dnc']) {
                if ($line['cnt'] > $line['sklad_cnt']) {
                    $pos_name = composePosNameStr($line['tovar'], $line['vc'], $line['name'], $line['proizv']);
                    $fail_text .= " - Мало товара '$pos_name' -  есть:{$line['sklad_cnt']}, нужно:{$line['cnt']}. \n";
                    continue;
                }
                if (!$silent) {
                    $ret = getStoreCntOnDate($line['tovar'], $doc_info['sklad'], $doc_info['date'], false, true);
                    if ($ret['cnt'] < 0) {
                        $pos_name = composePosNameStr($line['tovar'], $line['vc'], $line['name'], $line['proizv']);
                        $fail_text .= " - Будет ({$ret['cnt']}) мало товара '$pos_name', документ {$ret['doc']} \n";
                        continue;
                    }
                }
            }
        }
        if($fail_text) {
            throw new \Exception("Ошибка в номенклатуре: \n".$fail_text);
        }
        // Оприходование
        $res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt` AS `sklad_cnt`, `doc_base`.`name`, `doc_base`.`proizv`,
                `doc_base`.`pos_type`, `doc_list_pos`.`id`, `doc_list_pos`.`page`, `doc_base`.`vc`
            FROM `doc_list_pos`
            INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
            LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$doc_info['sklad']}'
            WHERE `doc_list_pos`.`doc`='{$this->id}' AND `doc_base`.`pos_type`='0' AND `doc_list_pos`.`page`=0");
        $fail_text = '';
        while ($line = $res->fetch_assoc()) {
            $sign = '+';
            $db->query("INSERT INTO `doc_base_cnt` (`id`, `sklad`, `cnt`) VALUES ('{$line['tovar']}', '{$doc_info['sklad']}', '{$line['cnt']}')"
                . " ON DUPLICATE KEY UPDATE `cnt`=`cnt` $sign '{$line['cnt']}'");
        }
        if($fail_text) {
            throw new \Exception("Ошибка в номенклатуре: \n".$fail_text);
        }        
        parent::docApply($silent);
    }

    public function DocCancel() {
        global $db;
        $pres = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`, `doc_sklady`.`dnc`
            FROM `doc_list`
            LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
            WHERE `doc_list`.`id`='{$this->id}'");
        $nx = $pres->fetch_row();
        if (!$nx) {
            throw new Exception("Документ {$this->id} не найден!");
        }
        if (!$nx[4]) {
            throw new Exception("Документ ещё не проведён!");
        }

        $db->update('doc_list', $this->id, 'ok', 0);
        $this->doc_data['ok'] = 0;

        $res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt`, `doc_base`.`name`, `doc_base`.`proizv`, 
                `doc_base`.`pos_type`, `doc_list_pos`.`page`
            FROM `doc_list_pos`
            LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
            LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$nx[3]'
            WHERE `doc_list_pos`.`doc`='{$this->id}'");
        while ($nxt = $res->fetch_row()) {
            if ($nxt[5] == 0) {
                $sign = $nxt[6] ? '+' : '-';
                $db->query("UPDATE `doc_base_cnt` SET `cnt`=`cnt` $sign '$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='$nx[3]'");
            }
        }
        $this->sentZEvent('cancel');
    }

    public function Service() {
        global $tmpl;
        $tmpl->ajax = 1;
        $opt = request('opt');
        $peopt = request('peopt');
        $pe_pos = request('pe_pos');
        include_once('include/doc.zapposeditor.php');
        $poseditor = new SZapPosEditor($this);
        $poseditor->cost_id = $this->dop_data['cena'];
        $poseditor->sklad_id = $this->doc_data['sklad'];

        if (\acl::testAccess('doc.' . $this->typename, \acl::VIEW)) {

            // Json-вариант списка товаров
            if ($peopt == 'jget') {
                $doc_sum = $this->recalcSum();
                $str = "{ response: 'loadlist', content: [" . $poseditor->GetAllContent() . "], sum: '$doc_sum' }";
                $tmpl->addContent($str);
            } else if ($peopt == 'jgetgroups') {
                $doc_content = $poseditor->getGroupList();
                $tmpl->addContent($doc_content);
            }
            // Получение данных наименования
            else if ($peopt == 'jgpi') {
                $pe_pos = rcvint('pos');
                $tmpl->addContent($poseditor->GetPosInfo($pe_pos));
            }
            // Json вариант добавления позиции
            else if ($peopt == 'jadd') {
                \acl::accessGuard('doc.' . $this->typename, \acl::UPDATE);
                $pe_pos = rcvint('pos');
                $tmpl->setContent($poseditor->AddPos($pe_pos));
            }
            // Json вариант удаления строки
            else if ($peopt == 'jdel') {
                \acl::accessGuard('doc.' . $this->typename, \acl::UPDATE);
                $line_id = rcvint('line_id');
                $tmpl->setContent($poseditor->Removeline($line_id));
            }
            // Json вариант обновления
            else if ($peopt == 'jup') {
                \acl::accessGuard('doc.' . $this->typename, \acl::UPDATE);
                $line_id = rcvint('line_id');
                $value = request('value');
                $type = request('type');
                $tmpl->setContent($poseditor->UpdateLine($line_id, $type, $value));
            }
            // Получение номенклатуры выбранной группы
            else if ($peopt == 'jsklad') {
                $group_id = rcvint('group_id');
                $str = "{ response: 'sklad_list', group: '$group_id',  content: [" . $poseditor->GetSkladList($group_id) . "] }";
                $tmpl->setContent($str);
            }
            // Поиск по подстроке по складу
            else if ($peopt == 'jsklads') {
                $s = request('s');
                $str = "{ response: 'sklad_list', content: " . $poseditor->SearchSkladList($s) . " }";
                $tmpl->setContent($str);
            } else if ($peopt == 'jsn') {
                $action = request('a');
                $line_id = request('line');
                $data = request('data');
                $tmpl->setContent($poseditor->SerialNum($action, $line_id, $data));
            } else if ($opt == 'jdeldoc') {
                $tmpl->setContent( $this->serviceDelDoc() );
            }
            else if ($opt == 'jundeldoc') {
                $tmpl->setContent($this->serviceUnDelDoc());
            } else {
                throw new NotFoundException('Параметр не найден!');
            }
        }
    }
}
