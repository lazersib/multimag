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


/// @brief Расчёт цен в программе
/// Учитывает скидки агента, пользователя, сумму заказа, и пр.
/// Замена функций getCurrentUserCost и getCostPos
/// Синглтон
class PriceCalc {
	protected static $_instance;	//< Экземпляр для синглтона
	
	// устанавливаемые значения
	protected $from_site_flag = 0;	//< id пользователя, для кторого расчитываем цены
	protected $agent_id = 0;	//< id агента, для кторого расчитываем цены
	protected $order_sum = 0;	//< сумма заказа, для которго расчитываем цены
	
	// вычисляемые значения
	protected $agent_avg_sum = false;	//< Средняя сумма оборота агента
	protected $current_price_id = 0;	//< id цены для текущих параметров заказа. При изменениии параметров - сбрасывается.
	protected $retail_price_id = 0;		//< id розничной цены.
	protected $siteuser_price_id = 0;	//< id цены для зарегистрированного пользователя
	protected $default_price_id = 0;	//< id цены по умолчанию
	protected $bulk_prices;			//< Список автоматических цен, включаемых по разным факторам
	protected $prices;			//< Все цены
	protected $pos_info_cache;		//< Кеш информации о наименованиях
	protected $ppc;				//< Кеш цен наименований

	final private function __clone(){}
	
	/// Конструктор. Загружает и сортирует список цен из базы данных.
	final private function __construct() {
		global $db;
		$this->pos_info_cache = array();
		$this->ppc = array();
		$this->bulk_prices = array();
		
		$res = $db->query("SELECT `id`, `name`, `type`, `value`, `context`, `priority`, `accuracy`, `direction`, `bulk_threshold`, `acc_threshold`
			FROM `doc_cost` ORDER BY `priority`");
		while($line = $res->fetch_assoc()) {
			$contexts = str_split($line['context']);
			foreach($contexts as $context) {
				switch($context) {
					case 'r':	// retail
						$this->retail_price_id = $line['id'];
						break;
					case 's':	// site user
						$this->siteuser_price_id = $line['id'];
						break;
					case 'd':	// default
						$this->default_price_id = $line['id'];
						break;
					case 'b':	// bulk
						$this->bulk_prices[] = array(
						    'id' => $line['id'],
						    'bulk_threshold' => $line['bulk_threshold'],
						    'acc_threshold' => $line['acc_threshold']
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
	    if (self::$_instance === null)
		    self::$_instance = new self();
	    return self::$_instance;
	}
	
	/// Установить флаг *заказ с сайта*
	/// @param flag Флаг
	public function setFromSiteFlag($flag) {
		$this->from_site_flag = $flag;
		$this->current_price_id = 0;
	}

	/// Установить ID агента для расчёта цен
	/// @param agent_id id агента. Должен существовать.
	public function setAgentId($agent_id) {
		$this->agent_id = $agent_id;
		$this->agent_avg_sum = false;
		$this->current_price_id = 0;		
	}

	/// Установить сумму заказа
	/// @param order_sum сумма заказа
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
	
	/// Получить ID текущей цены. Учитываются разные критерии.
	/// @return id текущей цены
	public function getCurrentPriceID() {
		global $db;
		if($this->current_price_id)
			return $this->current_price_id;
		$find_id = 0;
		
		if($this->agent_id>1 && $this->agent_avg_sum===false) {
			$agent_info = $db->selectRow('doc_agent', $this->agent_id);
			$this->agent_avg_sum = $agent_info['avg_sum'];
		}
		
		foreach($this->bulk_prices as $price) {
			if($this->order_sum>=$price['bulk_threshold']) {
				$find_id = $price['id'];
				break;
			}
			if($this->agent_avg_sum && $this->agent_avg_sum>=$price['acc_threshold']) {
				$find_id = $price['id'];
				break;
			}
		}
		if( (!$find_id) && $this->from_site_flag )
			$find_id = $this->siteuser_price_id;
		if( !$find_id )
			$find_id = $this->default_price_id;
		return $find_id;
	}


	
	/// Получить значение цены по умолчанию для товарного наименования
	/// @param pos_id id товарного наименования
	/// @return Значение цены
	public function getPosDefaultPriceValue($pos_id) {
		$pos_info = $this->getPosInfo($pos_id);
		return $this->getPosSelectedPriceValue($pos_id, $this->default_price_id, $pos_info);
	}
	
	/// Получить значение розничной цены для товарного наименования
	/// @param pos_id id товарного наименования
	/// @return Значение цены
	public function getPosRetailPriceValue($pos_id) {
		$pos_info = $this->getPosInfo($pos_id);
		return $this->getPosSelectedPriceValue($pos_id, $this->retail_price_id, $pos_info);
	}
	
	/// @brief Получить значение цены прользователя для товарного наименования
	/// Функция не учитывает заказываемое количество товара!
	/// @param pos_id id товарного наименования
	/// @return Значение цены
	public function getPosUserPriceValue($pos_id) {
		$pos_info = $this->getPosInfo($pos_id);
		$price_id = $this->getCurrentPriceID();
		return $this->getPosSelectedPriceValue($pos_id, $price_id, $pos_info);
	}
	
	/// Получить значение цены для товарного наименования при заданном приобретаемом количестве
	/// @param pos_id id товарного наименования
	/// @param count количество наименования в заказе
	/// @return Значение цены
	public function getPosAutoPriceValue($pos_id, $count=0) {
		$pos_info = $this->getPosInfo($pos_id);
		
		if($pos_info['bulkcnt']>1 && $pos_info['bulkcnt']>$count && $this->retail_price_id!=0)
			$price_id = $this->retail_price_id;
		else $price_id = $this->getCurrentPriceID();
		
		return $this->getPosSelectedPriceValue($pos_id, $price_id, $pos_info);
	}
	
	/// Получить нужную для расчета цены информацию о наименовании. Данные кешируются.
	/// @param pos_id id товарного наименования
	/// @return массив с базовой ценой, id группы и оптовым количеством товарного наименования
	public function getPosInfo($pos_id) {
		global $db;
		settype($pos_id,'int');
		if(isset($this->pos_info_cache[$pos_id]))
			return $this->pos_info_cache[$pos_id];
		// TODO: оптимизировать, с учетом того, что эти данные могут быть в месте обращения к классу. А могут и не быть.
		$res = $db->query("SELECT `cost` AS `base_price`, `group`, `bulkcnt` FROM `doc_base` WHERE `doc_base`.`id`=$pos_id");
		if($res->num_rows == 0)		throw new Exception("Товар ID:$pos_id не найден!");
		$this->pos_info_cache[$pos_id] = $res->fetch_assoc();
		return $this->pos_info_cache[$pos_id];
	}
	
	/// Получить значение выбранной цены для товарного наименования. Данные кешируются.
	/// @param pos_id id товарного наименования
	/// @return Значение цены
	public function getPosSelectedPriceValue($pos_id, $price_id, $pos_info) {
		global $db;
		settype($pos_id,'int');
		settype($price_id,'int');
		
		if(!isset($this->ppc[$pos_id]))
			$this->ppc[$pos_id] = array();
		if(isset($this->ppc[$pos_id][$price_id]))
			return $this->ppc[$pos_id][$price_id];
		
		// Проверяем переопределение в наименовании
		$res = $db->query("SELECT `doc_base_cost`.`id`, `doc_base_cost`.`type`, `doc_base_cost`.`value`, `doc_base_cost`.`accuracy`,
			`doc_base_cost`.`direction`
		FROM `doc_base_cost`
		WHERE `doc_base_cost`.`cost_id`=$price_id AND `doc_base_cost`.`pos_id`=$pos_id");
		
		if($res->num_rows!=0) {
			$line = $res->fetch_assoc();			
			switch($line['type']) {
				case 'pp':	$price = $pos_info['base_price'] * $line['value'] / 100 + $pos_info['base_price'];
						break;
				case 'abs':	$price = $pos_info['base_price'] + $line['value'];
						break;
				case 'fix':	$price = $line['value'];
						break;
				default:	$price = 0;
			}
			if($price > 0)	return $this->ppc[$pos_id][$price_id] = sprintf("%0.2f", roundDirect($price, $line['accuracy'], $line['direction']));
			else 	return $this->ppc[$pos_id][$price_id] = 0;
		}
		$res->free();
		
		// Ищем переопределение в группах
		$base_group = $pos_info['group'];
		while($base_group) {
			$res = $db->query("SELECT `doc_group_cost`.`id` AS `gc_id`, `doc_group_cost`.`type`, `doc_group_cost`.`value`, `doc_group`.`pid`, `doc_group_cost`.`accuracy`, `doc_group_cost`.`direction`
			FROM `doc_group`
			LEFT JOIN `doc_group_cost` ON `doc_group`.`id`=`doc_group_cost`.`group_id`  AND `doc_group_cost`.`cost_id`=$price_id
			WHERE `doc_group`.`id`=$base_group");
			if($res->num_rows == 0)		throw new AutoLoggedException("Группа ID:$base_group не найдена");
			$gdata = $res->fetch_assoc();
			$res->free();
			if($gdata['gc_id']) {
				switch($gdata['type']) {
					case 'pp':	$price = $pos_info['base_price'] * $gdata['value'] / 100 + $pos_info['base_price'];
							break;
					case 'abs':	$price = $pos_info['base_price'] + $gdata['value'];
							break;
					case 'fix':	$price = $gdata['value'];
							break;
					default:	$price = 0;
				}

				if($price > 0)	return $this->ppc[$pos_id][$price_id] = sprintf("%0.2f", roundDirect($price, $gdata['accuracy'], $gdata['direction']));
				else 		return $this->ppc[$pos_id][$price_id] = 0;
			}
			$base_group = $gdata['pid'];
		}
		
		// Если не переопределена нигде - получаем из глобальных данных
		$cur_price_info = $this->prices[$price_id];
		switch($cur_price_info['type'])
		{
			case 'pp':	$price = $pos_info['base_price'] * $cur_price_info['value'] / 100 + $pos_info['base_price'];
					break;
			case 'abs':	$price = $pos_info['base_price'] + $cur_price_info['value'];
					break;
			case 'fix':	$price = $cur_price_info['value'];
					break;
			default:	$price = 0;
		}

		if($price > 0)	return $this->ppc[$pos_id][$price_id] = sprintf("%0.2f", roundDirect($price, $cur_price_info['accuracy'], $cur_price_info['direction']));
		else 		return $this->ppc[$pos_id][$price_id] = 0;
	}
}


?>
