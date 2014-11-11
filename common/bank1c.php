<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2014, BlackLight, TND Team, http://tndproject.org
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

    function __construct() {
    }
    
    /// Анализировать строку документа
    protected function parseDocumentLine($name, $value, $params) {
        switch ($name) {
            case 'Номер':
                $params['docnum'] = $value;
                break;
            case 'УникальныйНомерДокумента':
                $params['unique'] = $value;
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
                    throw new \Exception('Файл не является банковской выпиской в формате 1C! ');
                }
                $first_line = 0;
            }
            else {
                $pl = explode("=", $line, 2);
                switch($pl[0]) {
                    case 'СекцияДокумент':
                        if ($pl[1] == "Платёжное поручение") {
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
                            $params = $this->parseDocumentLine($pl[0], $pl[1], $params);
                        }
                }
            }
        }
        return $parsed_data;
    }

}
