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
namespace api; 

/// Обработчик API запросов к объектам *агент*. Проверяет необходимиые привилегии перед осуществлением действий.
class agent {
    
    protected function get($data) {
        if(!is_array($data) || !isset($data['id'])) {
            throw new \InvalidArgumentException('id агента не задан');
        }
        $agent_id = intval($data['id']);
        if(!$agent_id) {
            throw new \InvalidArgumentException('ID агента не задан');
        }        
        $agent = new \models\agent($agent_id);
        $agent_data = $agent->getData();
        $agent_data['group_id'] = $agent_data['group'];
        unset($agent_data['group']);
        if(!\acl::testAccess('directory.agent.global', \acl::VIEW)) {
            \acl::accessGuard('directory.agent.ingroup.'.$agent_data['group_id'], \acl::VIEW);
        }
        return ['id'=>$agent_id, 'data'=>$agent_data];
    }
    
    protected function create($data) {
        if(!is_array($data) || !isset($data['group_id'])) {
            throw new \InvalidArgumentException('нет входных данных или группа не задана');
        }
        if(!\acl::testAccess('directory.agent.global', \acl::CREATE)) {
            \acl::accessGuard('directory.agent.ingroup.'.$data['group_id'], \acl::CREATE);
        }
        $data['group'] = $data['group_id'];
        $agent = new \models\agent();
        $agent_id = $agent->create($data);
        return ['id'=>$agent_id, 'data'=>$agent->getData()];
    }

    public function dispatch($action, $data=null) {
        switch($action) {
            case 'get':
                return $this->get($data);
            case 'create':
                return $this->create($data);
            case 'listnames':
                \acl::accessGuard('directory.agent', \acl::VIEW);
                $ldo = new \Models\LDO\agentnames();
                return $ldo->getData();
            default:
                throw new \NotFoundException('Некорректное действие');
        }
    }
}