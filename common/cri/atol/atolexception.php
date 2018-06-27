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

namespace CRI\Atol;

class AtolException extends \Exception {
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

class KassException extends \Exception {

    function __construct($code) {
        $this->code = $code;
        switch ($code) {
            case 30: $this->message = "$code:ФР: Область перерегистраций ФП переполнена";
                break;
            case 30: $this->message = "$code:ФР: Нет данных!";
                break;
            case 51: $this->message = "$code:ФР: Некорректные параметры в комманде";
                break;
            case 55: $this->message = "$code:ФР: Команда не поддерживается в данной реализации";
                break;
            case 60: $this->message = "$code:ФР: Смена открыта, операция невозможна, либо неверный регистрационный номер ЭКЛЗ!";
                break;
            case 64: $this->message = "$code:ФР: Переполнение диапазона скидок!";
                break;
            case 69: $this->message = "$code:ФР: Cумма всех типов оплаты меньше итога чека!";
                break;
            case 78: $this->message = "$code:ФР: Смена превысила 24 часа!";
                break;
            case 80: $this->message = "$code:ФР: Идёт печать предыдущей команды!";
                break;
            case 88: $this->message = "$code:ФР: Ожидание комманды продолжения печати!";
                break;
            case 93: $this->message = "$code:ФР: Таблица не определена!";
                break;
            case 94: $this->message = "$code:ФР: Некорректная операция!";
                break;
            case 103: $this->message = "$code:ФР: Ошибка связи с ФП!";
                break;
            case 107: $this->message = "$code:ФР: Нет чековой ленты!";
                break;
            case 114: $this->message = "$code:ФР: Команда не поддерживается в данном подрежиме!";
                break;
            case 115: $this->message = "$code:ФР: Команда не поддерживается в данном режиме!";
                break;
            case 126: $this->message = "$code:ФР: Неверное значение в поле длины!";
                break;
            case 144: $this->message = "$code:ФР: Поле превышает размер, установленный в настройках!";
                break;
            case 149: $this->message = "$code:ФР: ЭТУ ОШИБКУ (149) МЫ НЕ НАШЛИ В ПРОТОКОЛЕ!";
                break;
            case 163: $this->message = "$code:ЭКЛЗ: Некорректное состояние ЭКЛЗ!";
                break;
            case 163: $this->message = "$code:ЭКЛЗ: Некорректное состояние ЭКЛЗ!";
                break;
            default: $this->message = "CODE$code, описание ошибки отстутствует! Читай инструкцию от кассы!";
        }
    }

}