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

class AtolHLError extends \Exception {
    protected $extcode;

    public function __construct(int $code = 0, int $extcode = 0, \Throwable $previous = null) {
        $message = self::getNameForCode($code, $extcode);
        $this->extcode = $extcode;
        parent::__construct($message, $code, $previous);
    }
    
    public function getExtCode() {
        return $this->extcode;
    }
        
    static function getNameForCode(int $code, int $extcode = 0) {
        switch ($code) {
            case 0:
                return 'Ошибок нет';
            case 8:
                return 'Неверная цена (сумма)';
            case 10:
                return 'Неверное количество';
            case 11:
                return 'Переполнение счетчика наличности';
            case 12:
                return 'Невозможно сторно последней операции';
            case 13:
                return 'Сторно по коду невозможно (в чеке зарегистрировано меньшее количество товаров с указанным кодом)';
            case 14:
                return 'Невозможен повтор последней операции';
            case 15:
                return 'Повторная скидка на операцию невозможна';
            case 16:
                return 'Скидка/надбавка на предыдущую операцию невозможна';
            case 17:
                return 'Неверный код товара';
            case 18:
                return 'Неверный штрихкод товара';
            case 19:
                return 'Неверный формат';
            case 20:
                return 'Неверная длина';                
            case 21:
                return 'ККТ заблокирована в режиме ввода даты';
            case 22:
                return 'Требуется подтверждение ввода даты';
            case 24:
                return 'Нет больше данных для передачи ПО ККТ';
            case 25:
                return 'Нет подтверждения или отмены регистрации прихода';
            case 26:
                return 'Отчет с гашением прерван. Вход в режим невозможен.';
            case 27:
                return 'Отключение контроля наличности невозможно (не настроены необходимые типы оплаты).';
            case 30:
                return 'Вход в режим заблокирован';
            // ~~~~~~~~~~~~~~~~~~
            case 102:
                return 'Команда не реализуется в данном режиме ККТ';
            // ~~~~~~~~~~~~~~~~~~
            case 113:
                return 'Сумма не наличных платежей превышает сумму чека';
            case 114:
                return 'Сумма платежей меньше суммы чека';
            case 115:
                return 'Накопление меньше суммы возврата или аннулирования';               
            // ~~~~~~~~~~~~~~~~~~
            case 123:
                return 'Неверная величина скидки / надбавки';               
            // ~~~~~~~~~~~~~~~~~~
            case 130:
                return 'Открыт чек аннулирования – операция невозможна';
            case 132:
                return 'Переполнение буфера контрольной ленты';
            case 134:
                return 'Вносимая клиентом сумма меньше суммы чека';
            case 135:
                return 'Открыт чек возврата – операция невозможна';
            case 136:
                return 'Смена превысила 24 часа';
            case 137:
                return 'Открыт чек прихода – операция невозможна';
            case 138:
                return 'Переполнение ФП';
            case 140:
                return 'Неверный пароль';
            case 141:
                return 'Буфер контрольной ленты не переполнен';
            case 142:
                return 'Идет обработка контрольной ленты';
            case 143:
                return 'Обнуленная касса (повторное гашение невозможно)';
                // ~~~~~~~~~~~~~~~~~~
            case 151:
                return 'Подсчет суммы сдачи невозможен';
            case 152:
                return 'В ККТ нет денег для выплаты';
            case 154:
                return 'Чек закрыт – операция невозможна';
            case 155:
                return 'Чек открыт – операция невозможна';
            case 156:
                return 'Смена открыта, операция невозможна';
                // ~~~~~~~~~~~~~~~~~~
            case 246:  
                return self::getnameForExtCode($extcode);
            default:
                return 'Неизвестный код ошибки: '.$code;
        }
    }
    
    static function getnameForExtCode(int $extcode) {
        switch($extcode) {
            case 0:
                return 'Превышение максимального размера чека';
            case 1:
                return 'Некорректная версия ФФД';
            case 2:
                return 'Внутренняя ошибка ККТ';
            case 3:
                return 'Параметр доступен только для чтения';
            default:
                return 'Неизвестный расширенный код ошибки: '.$extcode;
        }
    }
}
