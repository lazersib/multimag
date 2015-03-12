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

namespace Models;

/// Базовый класс для списков в данных, использующихся, в основном, для обработки ajax запросов 
abstract class ListDataObject {
	protected $options = ''; //< Набор опций
	protected $fields = ''; //< Набор полей
	protected $order_field = false; //< Поле, по которому будет выполнена сортировка
	protected $order_reverse = false; //< Обратное направление сортировки (от большего к меньшему)
	protected $page = 0; //< Страница ответа
	protected $limit = 1000; //< лимит на количество строк в ответе
	
	public $end = 0; //< признак последней страницы

	/// Устанавливает опции в значение value
	public function setOptions($value) {
		$this->options = $value;
	}
	
	/// @brief Задать список полей
	// Устанавливает список полей, которые нужно получить в ответе
	public function setFields($fields) {
		$this->fields = $fields;
	}
	
	/// @brief Задать поле сортировки
	public function setOrderField($field) {
		$this->order_field = $field;
	}
	
	/// @brief Задать направление сортировки
	public function setReverseOrderDirection($bool) {
		$this->order_reverse = $bool;
	}
	
	/// @brief Задать лимит выдачи
	public function setlimit($limit) {
		$this->limit = intval($limit);
	}
	
	/// @brief Задать страницу выдачи
	public function setPage($page) {
		$this->page = intval($page);
	}
	
	/// @brief Получить данные
	/// Если запрошено поле, которое нельзя вернуть по каким-либо причинам, метод выбрасывает исключение
	/// Допускается возврат незапрошенных полей
	/// Возвращает данные, отфильтрованные в соответствии с фильтрами 
	abstract public function getData();
}; 
