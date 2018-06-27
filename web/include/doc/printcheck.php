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

namespace doc;

/// Трейт *печать кассового чека*
trait PrintCheck {
    
    protected function getCashRegister($cr_id) {
        global $db;
        $res = $db->query("SELECT `name`, `connect_line`, `password`, `section` FROM `cash_register` WHERE `id`='$cr_id'");
        $kkm_line = $res->fetch_assoc();
        if(!$kkm_line) {
            throw new \NotFoundException("ID кассового аппарата $cr_id не найден в базе данных");
        }
        $cr = new \CRI\Atol\Atol();
        $cr->connect($kkm_line['connect_line']); 
        $cr->setPassword($kkm_line['password']);
        $cr->setSection($kkm_line['section']);
        return $cr;
    }
    
    protected function touchRegMode($cr) {
        global $db;
        $statecode = $cr->requestGetStateCode();
        if($statecode['state']>0) {
            $cr->requestExitFromMode();
            $statecode = $cr->requestGetStateCode();
            if($statecode['state']>0) {
                $cr->requestExitFromMode();
                $statecode = $cr->requestGetStateCode();
            }
        }    
        $res = $db->query("SELECT `cr_password` FROM `users_worker_info` WHERE `user_id`='{$_SESSION['uid']}'");
        $ui = $res->fetch_assoc();
        if($ui==null) {
            throw new \Exception("Вы - не сотрудник, и не можете распечатать чек!");
        }
        if($ui['cr_password']==0) {
            throw new \Exception("Ваш пароль кассира не задан, и вы не можете распечатать чек!");
        }
        if($statecode['state']==0) {
            $cr->requestEnterToMode(1, $ui['cr_password']);
        }
        else {
            throw new \Exception("Режим: {$statecode['state']} - в нём печать не возможна, и сменить не получается!");
        }
        $state = $cr->requestGetState();
        if($state['flags']['session']==false) {
            throw new \Exception("Смена не открыта, печать чека не возможна!");
        }
    }
    
    // Напечатать чек
    protected function printCheck($cr_id) {
        if($this->doc_data['p_doc']==0) {
            throw new Exception("Невозможна печать чека для документа, не являющегося потомком накладной");
        }
        $doc = \document::getInstanceFromDb($this->doc_data['p_doc']);
        $check_type = 0;
        $pay_type = 0;
        $pay_sum = 1;
        if($doc->typename == 'realizaciya') {
            if($this->typename == 'pko' || $this->typename == 'payinfo') {
                $ret = $doc->getDopData('return');
                $check_type = $ret ? \CRI\Atol\atol::CT_OUT_RETURN : \CRI\Atol\atol::CT_IN;
                $pay_type = $this->typename == 'payinfo' ? 2 : 1;
                $pay_sum = $ret ? 0:1;
            }
            else {
                throw new Exception("Невозможна печать чека - данный документ не допустим для расхода товара");
            }
        }
        else if($doc->typename == 'postuplenie') {
            if($this->typename == 'rko' || $this->typename == 'payinfo') {
                $ret = $doc->getDopData('return');
                $check_type = $ret ? \CRI\Atol\atol::CT_IN_RETURN : \CRI\Atol\atol::CT_OUT;
                $pay_type = $this->typename == 'payinfo' ? 2 : 1;
                $pay_sum = $ret ? 0:1;
            }
            else {
                throw new Exception("Невозможна печать чека - данный документ не допустим для прихода товара");
            }
        }
        else {
            throw new Exception("Печать чека возможна лишь на основании поступления или реализации");
        }
        $cr = $this->getCashRegister($cr_id);
        try {
            $nom = $doc->getDocumentNomenclature('base_price,bulkcnt');  
            $this->touchRegMode($cr);
            $cr->requestOpenCheck($check_type);
            $sum = 0;
            foreach ($nom as $line) {
                $tax = $section = 0;
                $cr->requestRegisterNomenclature($line['name'], $line['price'], $line['cnt']);
                //$cr->cmdRegisterNomenclature($line['name'], $line['price'], $line['cnt']);
                $sum += $line['price'] * $line['cnt'];
            }
            $sum *= $pay_sum;
            $cr->requestCloseCheck($pay_type, $sum);
            /// TODO: услуги, коды товаров
        }
        catch(\CRI\Atol\AtolHLError $e) {
            try {
                $cr->abortBuffer();
                $cr->requestBreakCheck();                
            } catch (\Exception $ex) {}
            throw $e;
        }
        catch(\CRI\Atol\AtolException $e) {
            try {
                $cr->abortBuffer();
                $cr->requestBreakCheck();
            } catch (\Exception $ex) {}   
            throw $e;
        }
    }
    
}
