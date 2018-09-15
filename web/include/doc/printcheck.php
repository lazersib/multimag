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

/**
 * Трейт *печать кассового чека*
 * @package doc
 */
trait PrintCheck {

    /**
     * @var \CRI\Atol\Atol Объект ККМ
     */
    private $crId;

    /**
     * Получить объект взаимодействия с ККМ по id
     * @param $cr_id int ID регистратора
     * @throws \NotFoundException
     */
    private function useCashRegister($cr_id) {
        global $db;
        $kkm_line = $db->selectRow('cash_register', $cr_id);
        if(!$kkm_line) {
            throw new \NotFoundException("ID кассового аппарата $cr_id не найден в базе данных");
        }
        $this->crId = new \CRI\Atol\Atol();
        $this->crId->connect($kkm_line['connect_line']);
        $this->crId->setPassword($kkm_line['password']);
        $this->crId->setSection($kkm_line['section']);
    }

    /**
     * Проверить, и при необходимости, установить нужный режим ККМ
     * @throws \Exception
     */
    private function touchRegMode() {
        global $db;
        $stateCode = $this->crId->requestGetStateCode();
        if($stateCode['state']>0) {
            $this->crId->requestExitFromMode();
            $stateCode = $this->crId->requestGetStateCode();
            if($stateCode['state']>0) {
                $this->crId->requestExitFromMode();
                $stateCode = $this->crId->requestGetStateCode();
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
        if($stateCode['state']==0) {
            $this->crId->requestEnterToMode(1, $ui['cr_password']);
        }
        else {
            throw new \Exception("Режим: {$stateCode['state']} - в нём печать не возможна, и сменить не получается!");
        }
        $state = $this->crId->requestGetState();
        if($state['flags']['session']==false) {
            throw new \Exception("Смена не открыта, печать чека не возможна!");
        }
    }

    /**
     * Напечатать чек
     * @param $cr_id
     * @throws \CRI\Atol\AtolException
     * @throws \CRI\Atol\AtolHLError
     * @throws \CRI\Atol\AtolHLException
     * @throws \NotFoundException
     * @throws \Exception
     */
    protected function printCheck($cr_id) {
        if($this->doc_data['p_doc']==0) {
            throw new \Exception("Невозможна печать чека для документа, не являющегося потомком накладной");
        }
        $doc = \document::getInstanceFromDb($this->doc_data['p_doc']);
        if($doc->typename == 'realizaciya') {
            if($this->typename == 'pko' || $this->typename == 'payinfo') {
                $ret = $doc->getDopData('return');
                $check_type = $ret ? \CRI\Atol\atol::CT_OUT_RETURN : \CRI\Atol\atol::CT_IN;
                $pay_type = $this->typename == 'payinfo' ? 2 : 1;
                $pay_sum = $ret ? 0:1;
            }
            else {
                throw new \Exception("Невозможна печать чека - данный документ не допустим для расхода товара");
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
                throw new \Exception("Невозможна печать чека - данный документ не допустим для прихода товара");
            }
        }
        else {
            throw new \Exception("Печать чека возможна лишь на основании поступления или реализации");
        }
        if(!$doc->getDocData('ok')) {
            throw new \Exception("Накладная-основание не проведена");
        }
        if($this->getDocData('sum')!=$doc->getDocData('sum')) {
            throw new \Exception("Частичные поступления и возвраты не реализованы");
        }        

        try {
            $this->useCashRegister($cr_id);
            $this->touchRegMode();
            $this->crId->requestOpenCheck($check_type);
            $nom = $doc->getDocumentNomenclature('base_price,bulkcnt');
            $sum = 0;
            foreach ($nom as $line) {
                $tax = $section = 0;
                $this->crId->requestRegisterNomenclature($line['name'], $line['price'], $line['cnt']);
                //$cr->cmdRegisterNomenclature($line['name'], $line['price'], $line['cnt']);
                $sum += $line['price'] * $line['cnt'];
            }
            $sum *= $pay_sum;
            $this->crId->requestCloseCheck($pay_type, $sum);
            /// TODO: услуги, коды товаров
        }
        catch(\CRI\Atol\AtolHLError $e) {
            try {
                $this->crId->abortBuffer();
                $this->crId->requestBreakCheck();
            } catch (\Exception $ex) {}
            throw $e;
        }
        catch(\CRI\Atol\AtolException $e) {
            try {
                $this->crId->abortBuffer();
                $this->crId->requestBreakCheck();
            } catch (\Exception $ex) {}   
            throw $e;
        }
    }
    
}
