<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2018, BlackLight, TND Team, http://tndproject.org
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

/// Периодическая рассылка прайс-листов
abstract class pricesendaction extends \Action {

    /// Конструктор
    public function __construct($config, $db) {
        parent::__construct($config, $db);
    }

    /// Запустить задачу
    public function run() {
        global $db;
        switch($this->interval) {
            case self::DAILY:
                $period = 'daily';
                break;
            case self::WEEKLY:
                $period = 'weekly';
                break;
            case self::MONTHLY:
                $period = 'monthly';
                break;
            default:
                throw new Exception('Недопустимый интервал запуска задачи!');
        }
        $res = $db->query("SELECT `id`, `name`, `period`, `format`, `use_zip`, `price_id`, `options`, `lettertext`"
            . " FROM `prices_delivery` WHERE `period`='$period'");
        while($line=$res->fetch_assoc()) {
            $line['options'] = json_decode($line['options'], true);
            $line['subscribers'] = array();
            $cr = $db->query("SELECT `agent_contacts`.`id`, `agents`.`name`, `agent_contacts`.`value` AS `email`"
                    . ", `agent_contacts`.`person_name`, `agent_contacts`.`person_post`"
                . " FROM `prices_delivery_contact`"
                . " INNER JOIN `agent_contacts` ON `agent_contacts`.`id`=`prices_delivery_contact`.`agent_contacts_id` AND `agent_contacts`.`type`='email'"
                . " INNER JOIN `doc_agent` AS `agents` ON `agents`.`id`=`agent_contacts`.`agent_id`"
                . " WHERE `prices_delivery_contact`.`prices_delivery_id`='{$line['id']}' AND `agent_contacts`.`no_ads`='0'");
            while($l = $cr->fetch_assoc()) {
                if(!$l['email']) {
                    continue;
                }
                $line['subscribers'][] = $l;                
            }
            if(count($line['subscribers'])==0) {
                continue;
            }
            $psender = new \priceSender();
            $psender->setFormat($line['format']);
            $psender->setZip($line['use_zip']);
            $psender->setPriceId($line['price_id']);
            $psender->setText($line['lettertext']);
            $psender->setOptions($line['options']);
            $psender->setContactList($line['subscribers']);
            $psender->run();
        }
        
        
    }
    
}
