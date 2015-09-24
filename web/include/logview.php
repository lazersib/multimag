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

/// Класс просмотра журналов
class LogView {
    protected $object;
    protected $object_id;
    
    protected $a_colors = array();
    protected $agents;
    protected $users;
    protected $stores;
    protected $store_pos;
    
    public function setObject($object) {
        $this->object = $object;
    }
    
    public function setObjectId($object_id) {
        $this->object_id = intval($object_id);
    }
    
    protected function getAgentLink($agent_id) {
        settype($agent_id, 'int');
        if(!is_array($this->agents)) {
            $agent_ldo = new \Models\LDO\agentnames();
            $this->agents = $agent_ldo->getData();
        }
        if(isset($this->agents[$agent_id])) {
            $name = html_out($this->agents[$agent_id]);            
            return "<a href='/docs.php?l=agent&amp;mode=srv&amp;opt=ep&amp;pos={$agent_id}'>{$name}</a>";
        }
        else return $agent_id;        
    }
    
    protected function getUserLink($user_id) {
        settype($user_id, 'int');
        if(!is_array($this->users)) {
            $ldo = new \Models\LDO\usernames();
            $this->users = $ldo->getData();
        }
        if(isset($this->users[$user_id])) {
            $name = html_out($this->users[$user_id]);            
            return "<a href='/adm_users.php?mode=view&amp;id={$user_id}'>{$name}</a>";
        }
        else return $user_id;        
    }
    
    protected function getPosLink($pos_id) {
        settype($pos_id, 'int');
        if(!is_array($this->store_pos)) {
            $ldo = new \Models\LDO\posnames();
            $this->store_pos = $ldo->getData();
        }
        if(isset($this->store_pos[$pos_id])) {
            $name = html_out($this->store_pos[$pos_id]);            
            return "<a href='/docs.php?mode=srv&amp;opt=ep&amp;pos={$pos_id}'>{$name}</a>";
        }
        else return $pos_id;        
    }
    
    protected function getStoreLink($store_id) {
        settype($store_id, 'int');
        if(!is_array($this->stores)) {
            $ldo = new \Models\LDO\skladnames();
            $this->stores = $ldo->getData();
        }
        if(isset($this->stores[$store_id])) {
            $name = html_out($this->stores[$store_id]);            
            return "<a href='#$store_id'>{$name}</a>";
        }
        else return $store_id;        
    }
    
    public function showLog() {
        global $db, $tmpl;
        $sql_obj = $db->real_escape_string($this->object);
        $res = $db->query("SELECT `doc_log`.`motion`, `doc_log`.`desc`, `doc_log`.`time`, `users`.`name`, `doc_log`.`ip`, `doc_log`.`user`
            FROM `doc_log`
            LEFT JOIN `users` ON `users`.`id`=`doc_log`.`user`
            WHERE `doc_log`.`object`='$sql_obj' AND `doc_log`.`object_id`='{$this->object_id}'
            ORDER BY `doc_log`.`time` DESC");
        $tmpl->addContent("<table width=100% class='list'>
            <tr><th>Действие<th>Описание действия<th>Дата<th>Пользователь<th>IP");
        $users_ib = array();

        while ($nxt = $res->fetch_row()) {
            if (!isset($users_ib[$nxt[5]])) {
                $users_ib[$nxt[5]] = substr(md5($nxt[3]), 0, 3);
            }
            
            list($action, $desc) = $this->parseAction($nxt[0], $nxt[1]);
            $nxt[2] = str_replace(' ', '&nbsp;', $nxt[2]);
            $tmpl->addContent("<tr><td>$action<td>$desc<td>$nxt[2]<td><div class='iblock' style='background-color: #{$users_ib[$nxt[5]]}'>&nbsp;</div> $nxt[3]<td>$nxt[4]");
        }
        $tmpl->addContent("</table>");
    }
    
    protected function parseAction($action, $desc) {
        if (!isset($this->a_colors[$action])) {
            $this->a_colors[$action] = substr(md5($action), 0, 3);
        }
        $col = $this->a_colors[$action];
        
        $ret = array('desc'=>$desc);
        if(stripos($action, 'create')!==false) {
            $action = 'Создание';
            $desc = $this->parseDescDocCreate($desc);
        } elseif(stripos($action, 'print')!==false) {
            $action = 'Печать';
        } elseif(stripos($action, 'update')!==false) {
            $action = 'Изменение';
            $desc = $this->parseDescDocUpdate($desc);
        } elseif(stripos($action, 'apply')!==false) {
            $action = 'Проведение';
        } elseif(stripos($action, 'Send email')!==false) {
            $action = 'Отправка email';
        }
        
        return array("<div class='iblock' style='background-color: #{$col}'>&nbsp;</div>".$action, $desc);
    }
    
    protected function parseDescDocCreate($desc) {
        if(stripos($desc, 'from')===0) {
            $doc = intval(substr($desc, 5));
            if($doc) {
                $desc = "На основании <a href='/doc.php?mode=body&amp;doc=$doc'>$doc</a>";
            }
        }
        return $desc;
    }
    
    protected function parseDescDocUpdate($desc) {
        if(stripos($desc, ', ')!==false) {
            $items = explode(', ', $desc);
            $desc = '';
            foreach($items as $item) {
                if($item) {
                    $desc .= $this->parseDescDocUpdateItem($item).', ';
                }
            }
        }
        else {
            $desc = $this->parseDescDocUpdateItem($desc);
        }
        return $desc;
    }
    
    protected function parseDescDocUpdateItem($desc) {
        $matches = null;
        if(stripos($desc, 'comment: ')===0) {
            $desc = 'Комментарий: '.substr($desc, 9);
        } elseif(stripos($desc, 'add pos: ')===0) {
            $desc = substr($desc, 9);
            $i = explode(':', $desc);
            $desc = 'Добавлено наименование: '.$this->getPosLink($i[1]);
        } elseif(preg_match('/date:[ (]*(\d*)=>(\d*)/i' , $desc, $matches)) {
            if (date('Ymd', $matches[1]) == date('Ymd', $matches[2])) {
                $desc = 'Время: '.date("H:i:s", $matches[1]).' => '.date('H:i:s', $matches[2]);
            } else {
                $desc = 'Дата: '.date("Y-m-d H:i:s", $matches[1]).' => '.date('Y-m-d H:i:s', $matches[2]);
            }
        } elseif(preg_match('/agent:[ (]*(\d*)=>(\d*)/i' , $desc, $matches)) {
            $desc = 'Агент: '.$this->getAgentLink($matches[1]).' => '.$this->getAgentLink($matches[2]);
        } elseif(preg_match('/sklad:[ (]*(\d*)=>(\d*)/i' , $desc, $matches)) {
            $desc = 'Склад: '.$this->getStoreLink($matches[1]).' => '.$this->getStoreLink($matches[2]);
        } elseif(preg_match('/platelshik:[ (]*(\d*)[ ]*=>[ ]*(\d*)/i' , $desc, $matches)) {
            $desc = 'Плательщик: '.$this->getAgentLink($matches[1]).' => '.$this->getAgentLink($matches[2]);
        } elseif(preg_match('/gruzop:[ \(]*(\d*)[ ]*=>[ ]*(\d*)/i' , $desc, $matches)) {
            $desc = 'Грузополучатель: '.$this->getAgentLink($matches[1]).' => '.$this->getAgentLink($matches[2]);
        } elseif(preg_match('/kladovshik:[ \(]*(\d*)[ ]*=>[ ]*(\d*)/i' , $desc, $matches)) {
            $desc = 'Кладовщик: '.$this->getUserLink($matches[1]).' => '.$this->getUserLink($matches[2]);
        }
        
        return $desc;
    }
    
}