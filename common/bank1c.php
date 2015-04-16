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

/// Класс парсера и генератора банковских выписок в формате обмена 1С
class Bank1CExchange {

    var $rs = '';
    
    function __construct() {
    }
    
    /// Анализировать строку документа
    protected function parseDocumentLineold($name, $value, $params) {
        switch ($name) {
            case 'Номер':
                $params['docnum'] = $value;
                break;
            case 'ДатаПроведения':
                $params['date'] = $value;
                break;
            case 'БИК':
                $params['bik'] = $value;
                break;
            case 'Счет':
                $params['schet'] = $value;
                break;
            case 'КорреспондентБИК':
                $params['kbik'] = $value;
                break;
            case 'КорреспондентСчет':
                $params['kschet'] = $value;
                break;
            case 'ДебетСумма':
                $params['debet'] = $value;
                break;
            case 'КредитСумма':
                $params['kredit'] = $value;
                break;
            case 'НазначениеПлатежа':
                $params['desc'] = $value;
                break;
            case 'РасчСчет':
                $params['rs'] = $value;
                break;
        }
        return $params;
    }
    
    protected function parseDocumentLinev100($name, $value, $params) {
        return $this->parseDocumentLinev101($name, $value, $params);
    }
    
    protected function parseDocumentLinev101($name, $value, $params) {
        switch ($name) {
            // Шапка
            case 'Номер':
                $params['docnum'] = $value;
                break;
            case 'Дата':
                $params['date'] = $value;
                break;
            case 'ДатаПоступило':
                $params['p_date'] = $value;
                break;
            case 'ДатаСписано':
                $params['s_date'] = $value;
                break;
            case 'Сумма':
                $params['sum'] = $value;
                break;
            // Плательщик
            case 'ПлательщикСчет':
                $params['src']['rs'] = $value;
                break;
            case 'ПлательщикИНН':
                $params['src']['inn'] = $value;
                break;
            case 'ПлательщикКПП':
                $params['src']['kpp'] = $value;
                break;
            case 'Плательщик1':
                $params['src']['name'] = $value;
                break;
            case 'ПлательщикБанк1':
                $params['src']['bank_name'] = $value;
                break;
            case 'ПлательщикРасчСчет':
                $params['src']['krs'] = $value;
                break;
            case 'ПлательщикБИК':
                $params['src']['bik'] = $value;
                break;
            case 'ПлательщикКорсчет':
                $params['src']['ks'] = $value;
            // Получатель
            case 'ПолучательСчет':
                $params['dst']['rs'] = $value;
                break;
            case 'ПолучательИНН':
                $params['dst']['inn'] = $value;
                break;
            case 'ПолучательКПП':
                $params['dst']['kpp'] = $value;
                break;
            case 'Получатель1':
                $params['dst']['name'] = $value;
                break;
            case 'ПолучательБанк1':
                $params['dst']['bank_name'] = $value;
                break;
            case 'ПолучательРасчСчет':
                $params['dst']['rs'] = $value;
                break;
            case 'ПолучательБИК':
                $params['dst']['bik'] = $value;
                break;
            case 'ПолучательКорсчет':
                $params['dst']['ks'] = $value;
                break;
            // Хвост
            case 'ВидОплаты':
                $params['vid'] = $value;
                break;
            case 'НазначениеПлатежа':
                $params['desc'] = $value;
                break;
        }
        return $params;
    }
    
    protected function parseDocumentLinev102($name, $value, $params) {
        switch ($name) {
            // Шапка
            case 'Номер':
                $params['docnum'] = $value;
                break;
            case 'Дата':
                $params['date'] = $value;
                break;
            case 'ДатаПоступило':
                $params['p_date'] = $value;
                break;
            case 'ДатаСписано':
                $params['s_date'] = $value;
                break;
            case 'Сумма':
                $params['sum'] = $value;
                break;
            // Плательщик
            case 'ПлательщикСчет':
                $params['src']['rs'] = $value;
                break;
            case 'ПлательщикИНН':
                $params['src']['inn'] = $value;
                break;
            case 'ПлательщикКПП':
                $params['src']['kpp'] = $value;
                break;
            case 'Плательщик':
                $params['src']['name'] = $value;
                break;
            case 'ПлательщикРасчСчет':
                $params['src']['krs'] = $value;
                break;
            case 'ПлательщикБИК':
                $params['src']['bik'] = $value;
                break;
            case 'ПлательщикБанк1':
                $params['src']['bank_name'] = $value;
                break;
            case 'ПлательщикКорсчет':
                $params['src']['ks'] = $value;
            // Получатель
            case 'ПолучательСчет':
                $params['dst']['rs'] = $value;
                break;
            case 'ПолучательИНН':
                $params['dst']['inn'] = $value;
                break;
            case 'ПолучательКПП':
                $params['dst']['kpp'] = $value;
                break;
            case 'Получатель':
                $params['dst']['name'] = $value;
                break;
            case 'ПолучательРасчСчет':
                $params['dst']['rs'] = $value;
                break;
            case 'ПолучательБИК':
                $params['dst']['bik'] = $value;
                break;
            case 'ПолучательБанк1':
                $params['dst']['bank_name'] = $value;
                break;
            case 'ПолучательКорсчет':
                $params['dst']['ks'] = $value;
                break;
            // Хвост
            case 'ВидОплаты':
                $params['vid'] = $value;
                break;
            case 'ВидПлатежа':
                $params['vid_p'] = $value;
                break;
            case 'НазначениеПлатежа':
                $params['desc'] = $value;
                break;
        }
        return $params;
    }
    
    /// @biref Парсер выписки
    /// Бросает исключение, если идентификатор не соответствует формату
    function Parse($raw_data) {
        $params = array();
        $parsed_data = array();
        $parsing = false;
        $first_line = 1;
        $version = 0;
        foreach ($raw_data as $line) {
            // Кодировку, установленную в файле не учитываем, т.к. параметр кодировки назван кириллицей. Получается, что для чтения кодировки нужно знать кодировку.
            $line = iconv('windows-1251', 'UTF-8', $line);
            $line = trim($line);
            if($first_line) {
                if($line != '1CClientBankExchange') {
                    throw new \Exception('Файл не является банковской выпиской в формате 1C!');
                }
                $first_line = 0;
            }
            else {
                $pl = explode("=", $line, 2);
                switch($pl[0]) {                    
                    case 'ВерсияФормата':
                        $version = trim($pl[1]);
                        break;
                    case 'СекцияРасчСчет':
                            $parsing = true;
                            $params = array();
                            $params['type'] = 'rs';
                        break;
                    case 'КонецРасчСчет':
                        if ($parsing) {
                            if(isset($parsed_data['rs'])) {
                                $this->rs = $parsed_data['rs'];
                            }
                            $parsing = false;
                        }
                        break;
                    case 'СекцияДокумент':
                        if ($pl[1] == "Платёжное поручение" || $pl[1] == "Платежное поручение" || $pl[1] == "Банковский ордер") {
                            $parsing = true;
                            $params = array();
                            $params['type'] = 'pp';
                        }    
                        break;
                    case 'КонецДокумента':
                        if ($parsing) {
                            $parsed_data[] = $params;
                            $parsing = false;
                        }
                        break;
                    default:
                        if($parsing) {
                            switch($version) {
                                case '1.00':
                                    $params = $this->parseDocumentLinev100($pl[0], $pl[1], $params);
                                    break;
                                case '1.01':
                                    $params = $this->parseDocumentLinev101($pl[0], $pl[1], $params);
                                    break;
                                case '1.02':
                                    $params = $this->parseDocumentLinev102($pl[0], $pl[1], $params);
                                    break;
                                default:
                                    throw new \Exception("неподдерживаемая версия формата: $version");
                            }
                            
                        }
                }
            }
        }
        return $parsed_data;
    }

}
