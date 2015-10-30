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


/// @brief Расчёт цен в программе
/// Учитывает скидки агента, пользователя, сумму заказа, и пр.
/// Замена функций getCurrentUserCost и getCostPos
/// Синглтон
class PriceCalc {

    protected static $_instance;    ///< Экземпляр для синглтона
    
    // устанавливаемые значения
    protected $from_site_flag = 0;  ///< флаг *заказ с сайта*
    protected $user_id = 0;         ///< id пользователя, для кторого расчитываем цены
    protected $agent_id = 0;        ///< id агента, для кторого расчитываем цены
    protected $order_sum = 0;       ///< сумма заказа, для которго расчитываем цены
    
    // вычисляемые значения
    protected $agent_avg_sum = false;   ///< Средняя сумма оборота агента
    protected $current_price_id = 0;    ///< id цены для текущих параметров заказа. При изменениии параметров - сбрасывается.
    protected $retail_price_id = 0;     ///< id розничной цены.
    protected $siteuser_price_id = 0;   ///< id цены для зарегистрированного пользователя
    protected $default_price_id = 0;    ///< id цены по умолчанию
    protected $agent_price_id = 0;      ///< id фиксированной цены агента
    protected $no_retail_prices = 0;    ///< флаг, запрещающий автоматическое использование розничных цен
    protected $no_bulk_prices = 0;      ///< флаг, запрещающий автоматическое использование разовых скидочных цен
    protected $bulk_prices;             ///< Список автоматических цен, включаемых по разным факторам
    protected $prices;                  ///< Все цены
    protected $pos_info_cache;          ///< Кеш информации о наименованиях
    protected $ppc;                     ///< Кеш цен наименований
    protected $gpi;                     ///< Кеш цен групп

    /// Конструктор копирования запрещён
    final private function __clone() {
        
    }

    /// Конструктор. Загружает и сортирует список цен из базы данных.
    final private function __construct() {
        global $db;
        $this->pos_info_cache = array();
        $this->ppc = array();
        $this->bulk_prices = array();
        $this->gpi = array();

        $res = $db->query("SELECT `id`, `name`, `type`, `value`, `context`, `priority`, `accuracy`, `direction`, `bulk_threshold`, `acc_threshold`
			FROM `doc_cost` ORDER BY `priority`");
        while ($line = $res->fetch_assoc()) {
            $contexts = str_split($line['context']);
            foreach ($contexts as $context) {
                switch ($context) {
                    case 'r': // retail
                        $this->retail_price_id = intval($line['id']);
                        break;
                    case 's': // site user
                        $this->siteuser_price_id = intval($line['id']);
                        break;
                    case 'd': // default
                        $this->default_price_id = intval($line['id']);
                        break;
                    case 'b': // bulk
                        $this->bulk_prices[] = array(
                            'id' => intval($line['id']),
                            'bulk_threshold' => intval($line['bulk_threshold']),
                            'acc_threshold' => intval($line['acc_threshold'])
                        );
                        break;
                }
            }
            $this->prices[$line['id']] = $line;
        }
    }

    /// Получить экземпляр класса
    /// @return PriceCalc
    public static function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /// Установить флаг *заказ с сайта*
    /// @param $flag Флаг
    public function setFromSiteFlag($flag) {
        $this->from_site_flag = $flag;
        $this->current_price_id = 0;
    }

    /// Установить ID пользователя для расчёта цен
    /// @param $user_id id пользователя.
    public function setUserId($user_id) {
        $this->user_id = $user_id;
        $this->current_price_id = 0;
    }

    /// Установить ID агента для расчёта цен
    /// @param $agent_id id агента. Должен существовать.
    public function setAgentId($agent_id) {
        $this->agent_id = $agent_id;
        $this->agent_avg_sum = false;
        $this->agent_price_id = 0;
        $this->no_retail_prices = 0;
        $this->no_bulk_prices = 0;
        $this->current_price_id = 0;
    }

    /// Получить флаг no_bulk_prices
    public function getNBPFlag() {
        return $this->no_bulk_prices;
    }

    /// Получить флаг no_retail_prices
    public function getNRPFlag() {
        return $this->no_retail_prices;
    }

    /// Получить id фиксированной цены агента
    public function getAgentPriceId() {
        return $this->agent_price_id;
    }

    /// Установить сумму заказа
    /// @param $order_sum сумма заказа
    public function setOrderSum($order_sum) {
        $this->order_sum = $order_sum;
        $this->current_price_id = 0;
    }

    /// Получить id цены по-умолчанию
    public function getDefaultPriceId() {
        return $this->default_price_id;
    }

    /// Получить id розничной цены
    public function getRetailPriceId() {
        return $this->retail_price_id;
    }

    /// Получить наименование текущей цены
    public function getCurrentPriceName() {
        $price_id = $this->getCurrentPriceID();
        return $this->prices[$price_id]['name'];
    }

    /// Получить ID текущей цены. Учитываются разные критерии.
    /// @return id текущей цены
    public function getCurrentPriceID() {
        global $db;
        if ($this->current_price_id) {
            return $this->current_price_id;
        }
        $find_id = 0;

        if ($this->agent_id > 1 && $this->agent_avg_sum === false) {
            $agent_info = $db->selectRow('doc_agent', $this->agent_id);
            $this->agent_avg_sum = $agent_info['avg_sum'];
            $this->agent_price_id = $agent_info['price_id'];
            $this->no_retail_prices = $agent_info['no_retail_prices'];
            $this->no_bulk_prices = $agent_info['no_bulk_prices'];
        }

        if ($this->agent_price_id && $this->no_bulk_prices) {
            $find_id = $this->agent_price_id;
        } else {
            foreach ($this->bulk_prices as $price) {
                if ($this->agent_price_id && $this->agent_price_id == $price['id']) {
                    $find_id = $price['id'];
                    break;
                }
                if ($this->order_sum >= $price['bulk_threshold']) {
                    $find_id = $price['id'];
                    break;
                }
                if ($this->agent_avg_sum && $this->agent_avg_sum >= $price['acc_threshold'] && !$this->agent_price_id) {
                    $find_id = $price['id'];
                    break;
                }
                if ($price['id'] == $this->siteuser_price_id && $this->siteuser_price_id && $this->from_site_flag && $this->user_id) {
                    $find_id = $price['id'];
                    break;
                }
            }
        }
        if ((!$find_id) && $this->agent_price_id) {
            $find_id = $this->agent_price_id;
        }

        if (!$find_id) {
            $find_id = $this->default_price_id;
        }

        $this->current_price_id = $find_id;
        return $find_id;
    }

    /// Получить ID следующей цены для текущего заказа
    public function getNextPriceInfo() {
        $next_price_id = 0;
        if ($this->no_bulk_prices) {
            return false;
        }
        foreach ($this->bulk_prices as $price) {
            if ($this->agent_price_id) {
                if ($this->agent_price_id == $price['id']) {
                    break;
                }
            }
            else if ($this->agent_avg_sum && $this->agent_avg_sum >= $price['acc_threshold']) {
                break;
            }
            if ($this->from_site_flag && $this->siteuser_price_id && $price['id'] == $this->siteuser_price_id) {
                break;
            }
            if ($this->order_sum >= $price['bulk_threshold']) {
                break;
            }
            $next_price_id = $price['id'];
        }
        if (!$next_price_id) {
            return false;
        }
        return array('id' => $next_price_id,
            'name' => $this->prices[$next_price_id]['name'],
            'incsum' => $this->prices[$next_price_id]['bulk_threshold'] - $this->order_sum);
    }

    /// Получить ID следующей цены для накопительной скидки
    public function getNextPeriodicPriceInfo() {
        global $CONFIG, $db;
        $next_price_id = 0;
        if ($this->agent_price_id || !$this->agent_id) {
            return false;
        }

        if (isset($CONFIG['pricecalc']['acc_time'])) {
            $cnt = intval($CONFIG['pricecalc']['acc_time']);
        } else {
            $cnt = 0;
        }
        $print_period = '';

        // Получение дат для выборки
        $di = new \DateCalcInterval();
        switch ($CONFIG['pricecalc']['acc_type']) {
            case 'months':
                $di->calcXMonthsBack($cnt);
                $print_period = "последние $cnt месяц(ев)";
                $start_period = $di->start;
                break;
            case 'years':
                $di->calcXYearsBack($cnt);
                $print_period = "последние $cnt лет";
                $start_period = $di->start;
                break;
            case 'prevmonth':
                $di->calcPrevMonth();
                $print_period = "текущий месяц";
                $start_period = $di->end;
                break;
            case 'prevquarter':
                $di->calcPrevQuarter();
                $print_period = "текущий квартал";
                $start_period = $di->end;
                break;
            case 'prevhalfyear':
                $di->calcPrevHalfyear();
                $print_period = "текущее полугодие";
                $start_period = $di->end;
                break;
            case '':break;
            case 'prevyear':
            default:
                $di->calcPrevYear();
                $print_period = "текущий год";
                $start_period = $di->end;
        }

        // Получение суммы расчётов за текущий период
        $acc_sum = 0;
        $res = $db->query("SELECT SUM(`sum`) FROM `doc_list` WHERE `date`>='$start_period'
            AND (`type`='1' OR `type`='4' OR `type`='6') AND `ok`>0 AND `agent`>0 AND `sum`>0 AND `agent`=" . intval($this->agent_id));
        $line = $res->fetch_row();
        if ($line) {
            $acc_sum = $line[0];
        }

        foreach ($this->bulk_prices as $price) {
            if ($acc_sum >= $price['acc_threshold']) {
                break;
            }
            $next_price_id = $price['id'];
        }
        if (!$next_price_id) {
            return false;
        }
        return array('id' => $next_price_id,
            'name' => $this->prices[$next_price_id]['name'],
            'incsum' => $this->prices[$next_price_id]['acc_threshold'] - $acc_sum,
            'period' => $print_period);
    }

    /// Получить значение цены по умолчанию для товарного наименования
    /// @param $pos_id id товарного наименования
    /// @param $pos_info Массив с данными товарного наименования: base_price, group, bulkcnt. Если параметр не задан - данные будут взяты из базы
    /// @return Значение цены
    public function getPosDefaultPriceValue($pos_id, $pos_info = false) {
        $pos_info = $this->fixPosInfo($pos_id, $pos_info);
        return $this->getPosSelectedPriceValue($pos_id, $this->default_price_id, $pos_info);
    }

    /// Получить значение розничной цены для товарного наименования
    /// @param $pos_id id товарного наименования
    /// @param $pos_info Массив с данными товарного наименования: base_price, group, bulkcnt. Если параметр не задан - данные будут взяты из базы
    /// @return Значение цены
    public function getPosRetailPriceValue($pos_id, $pos_info = false) {
        $pos_info = $this->fixPosInfo($pos_id, $pos_info);
        return $this->getPosSelectedPriceValue($pos_id, $this->retail_price_id, $pos_info);
    }

    /// @brief Получить значение цены пользователя для товарного наименования
    /// Функция не учитывает заказываемое количество товара!
    /// @param $pos_id id товарного наименования
    /// @param $pos_info Массив с данными товарного наименования: base_price, group, bulkcnt. Если параметр не задан - данные будут взяты из базы
    /// @return Значение цены
    public function getPosUserPriceValue($pos_id, $pos_info = false) {
        $pos_info = $this->fixPosInfo($pos_id, $pos_info);
        $price_id = $this->getCurrentPriceID();
        return $this->getPosSelectedPriceValue($pos_id, $price_id, $pos_info);
    }

    /// Получить ID цены для товарного наименования при заданном приобретаемом количестве
    /// @param $pos_id id товарного наименования
    /// @param $count количество наименования в заказе
    /// @param $pos_info Массив с данными товарного наименования: base_price, group, bulkcnt. Если параметр не задан - данные будут взяты из базы
    /// @return ID цены
    public function getPosAutoPriceID($pos_id, $count = 0, $pos_info = false) {
        $pos_info = $this->fixPosInfo($pos_id, $pos_info);

        settype($pos_info['bulkcnt'], 'int');
        settype($count, 'int');

        if ($pos_info['bulkcnt'] > 1 && $pos_info['bulkcnt'] > $count && $this->retail_price_id != 0 && !$this->no_retail_prices) {
            $price_id = $this->retail_price_id;
        } else {
            $price_id = $this->getCurrentPriceID();
        }

        return $price_id;
    }

    /// Получить значение цены для товарного наименования при заданном приобретаемом количестве
    /// @param pos_id id товарного наименования
    /// @param count количество наименования в заказе
    /// @return Значение цены
    public function getPosAutoPriceValue($pos_id, $count = 0, $pos_info = false) {
        $pos_info = $this->fixPosInfo($pos_id, $pos_info);
        $price_id = $this->getPosAutoPriceID($pos_id, $count, $pos_info);
        return $this->getPosSelectedPriceValue($pos_id, $price_id, $pos_info);
    }

    /// @brief Тестирует информацию о наименовании на наличие необходимых данных. Если данные не переданы - запрашивает из базы. Если данных не достаточно - выбрасывает исключение.
    /// Функция не оценивает корректность этих данных, только наличие необходимых ключей в массиве. Результат кешируется.
    /// @param $pos_id id товарного наименования
    /// @param $pos_info Массив с данными товарного наименования
    /// @param $pos_info Массив с данными товарного наименования: base_price, group, bulkcnt.
    protected function fixPosInfo($pos_id, $pos_info = false) {
        global $db;
        settype($pos_id, 'int');
        if (isset($this->pos_info_cache[$pos_id])) {
            return $this->pos_info_cache[$pos_id];
        }

        if (is_array($pos_info)) {
            if (isset($pos_info['group_id']) && !isset($pos_info['group'])) {
                $pos_info['group'] = $pos_info['group_id'];
            }
            if (isset($pos_info['base_price']) && isset($pos_info['group']) && isset($pos_info['bulkcnt'])) {
                $this->pos_info_cache[$pos_id] = $pos_info;
                return $pos_info;
            } else {
                throw new Exception('Не переданы необходимые данные для расчёта цены.');
            }
        }
        $res = $db->query("SELECT `cost` AS `base_price`, `group`, `bulkcnt` FROM `doc_base` WHERE `doc_base`.`id`=$pos_id");
        if ($res->num_rows == 0) {
            throw new Exception("Товар ID:$pos_id не найден!");
        }
        $this->pos_info_cache[$pos_id] = $res->fetch_assoc();

        return $this->pos_info_cache[$pos_id];
    }

    /// Получить значение выбранной цены для товарного наименования. Данные кешируются.
    /// @param $pos_id id товарного наименования
    /// @param $price_id id цены
    /// @param $pos_info Массив с данными товарного наименования: base_price, group, bulkcnt. Если параметр не задан - данные будут взяты из базы
    /// @return Значение цены
    public function getPosSelectedPriceValue($pos_id, $price_id, $pos_info = false) {
        global $db;
        settype($pos_id, 'int');
        settype($price_id, 'int');
        $pos_info = $this->fixPosInfo($pos_id, $pos_info);

        if (!isset($this->ppc[$pos_id])) {
            $this->ppc[$pos_id] = array();
        }
        if (isset($this->ppc[$pos_id][$price_id])) {
            return $this->ppc[$pos_id][$price_id];
        }

        // Проверяем переопределение в наименовании
        $res = $db->query("SELECT `doc_base_cost`.`id`, `doc_base_cost`.`type`, `doc_base_cost`.`value`, `doc_base_cost`.`accuracy`,
			`doc_base_cost`.`direction`
		FROM `doc_base_cost`
		WHERE `doc_base_cost`.`cost_id`=$price_id AND `doc_base_cost`.`pos_id`=$pos_id");

        if ($res->num_rows != 0) {
            $line = $res->fetch_assoc();
            switch ($line['type']) {
                case 'pp': $price = $pos_info['base_price'] * $line['value'] / 100 + $pos_info['base_price'];
                    break;
                case 'abs': $price = $pos_info['base_price'] + $line['value'];
                    break;
                case 'fix': $price = $line['value'];
                    break;
                default: $price = 0;
            }
            if ($price > 0) {
                return $this->ppc[$pos_id][$price_id] = sprintf("%0.2f", roundDirect($price, $line['accuracy'], $line['direction']));
            } else {
                return $this->ppc[$pos_id][$price_id] = 0;
            }
        }
        $res->free();

        // Ищем переопределение в группах
        $base_group = $pos_info['group'];
        while ($base_group) {
            $gdata = $this->getGroupPriceinfo($base_group, $price_id);
            if ($gdata['gc_id']) {
                switch ($gdata['type']) {
                    case 'pp': $price = $pos_info['base_price'] * $gdata['value'] / 100 + $pos_info['base_price'];
                        break;
                    case 'abs': $price = $pos_info['base_price'] + $gdata['value'];
                        break;
                    case 'fix': $price = $gdata['value'];
                        break;
                    default: $price = 0;
                }

                if ($price > 0) {
                    return $this->ppc[$pos_id][$price_id] = sprintf("%0.2f", roundDirect($price, $gdata['accuracy'], $gdata['direction']));
                } else {
                    return $this->ppc[$pos_id][$price_id] = 0;
                }
            }
            $base_group = $gdata['pid'];
        }

        // Если не переопределена нигде - получаем из глобальных данных
        $cur_price_info = $this->prices[$price_id];
        switch ($cur_price_info['type']) {
            case 'pp': $price = $pos_info['base_price'] * $cur_price_info['value'] / 100 + $pos_info['base_price'];
                break;
            case 'abs': $price = $pos_info['base_price'] + $cur_price_info['value'];
                break;
            case 'fix': $price = $cur_price_info['value'];
                break;
            default: $price = 0;
        }

        if ($price > 0) {
            return $this->ppc[$pos_id][$price_id] = sprintf("%0.2f", roundDirect($price, $cur_price_info['accuracy'], $cur_price_info['direction']));
        } else {
            return $this->ppc[$pos_id][$price_id] = 0;
        }
    }

    /// Получить информацию о переопределении цены в группе
    /// @param $group_id id группы товаров
    /// @param $price_id id цены
    /// @return Ассоциативный массив с ключами pid, gc_id, type, value, accuracy, direction
    protected function getGroupPriceinfo($group_id, $price_id) {
        global $db;
        if (!isset($this->gpi[$group_id])) {
            $this->gpi[$group_id] = array();
        }
        if (isset($this->gpi[$group_id][$price_id])) {
            return $this->gpi[$group_id][$price_id];
        }
        $res = $db->query("SELECT `doc_group`.`pid`, 
                `doc_group_cost`.`id` AS `gc_id`, `doc_group_cost`.`type`, `doc_group_cost`.`value`, `doc_group_cost`.`accuracy`, `doc_group_cost`.`direction`
            FROM `doc_group`
            LEFT JOIN `doc_group_cost` ON `doc_group`.`id`=`doc_group_cost`.`group_id`  AND `doc_group_cost`.`cost_id`=$price_id
            WHERE `doc_group`.`id`=$group_id");
        if ($res->num_rows == 0) {
            throw new \AutoLoggedException("Группа ID:$group_id не найдена");
        }
        $this->gpi[$group_id][$price_id] = $res->fetch_assoc();
        $res->free();
        return $this->gpi[$group_id][$price_id];
    }
    
    /// Получить информацию о цене по её ID
    /// @param $price_id id цены
    /// @return Ассоциативный массив с ключами id, name, type, value, context, priority, accuracy, direction, bulk_threshold, acc_threshold
    protected function getPriceInfo($price_id) {
        if(isset($this->prices[$price_id])) {
            return $this->prices[$price_id];
        }
        throw new \Exception('Запрошенная цена не найдена');
    }

}
