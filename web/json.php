<?php

//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2013, BlackLight, TND Team, http://tndproject.org
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

// Обработка ajax запросов от различных компонентов системы
// Можно формировать запрос одновременно к нескольким компонентам системы
// Запросы исполняются в порядке перечисления компонентов
// Обязательный параметр c - список компонетов, к которым формируется запрос, c разделителем "," (запятая)
// Опциональные параметры o.Z, где Z - имя компонента - имя поля, по которому должен быть отсортирован результат
// Опциональные параметры d.Z, где Z - имя компонента - если задан и не 0, сортировка будет от большего к меньшему
// Опциональные параметры f.Z, где Z - имя компонента - список полей, которые нужно получить в ответе, c разделителем "," (запятая)
// Опциональные параметры Z.X, где Z - имя компонента, передаются компонентам в виде X
// Стандартные значения X:
// X = id - получение данных об элементе с заданным id
// X = s - получить список элементов, у которых название, или другое строковое поле содержит значение s
// Ответ формируется в формате json.
// Ответ обязательно содержит поле result, которое может принимать значения ok или err.
// В случае, если result = err, добавляется обязательное поле error, содержащее текст ошибки
// Ответы от конкретного компонента содержатся в полях с именами, соответствующими именам компонентов в запросе

include_once("core.php");
$tmpl->ajax = 1;
ob_start();

/// Базовый класс для обработчиков ajax запросов
abstract class ajaxRequestBase {
	protected $filter = array(); //< Набор фильтров
	protected $fields = ''; //< Набор полей
	protected $order_field = false; //< Поле, по которому будет выполнена сортировка
	protected $order_reverse = false; //< Обратное направление сортировки (от большего к меньшему)
	
	/// Устанавливает фильтр param в значение value
	public function setFilter($filter, $value) {
		$this->filter[$filter] = $value;
	}
	
	/// @brief Задать фильтр
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
	
	/// @brief Получить json данные
	/// Если запрошено поле, которое нельзя вернуть по каким-либо причинам, метод выбрасывает исключение
	/// Допускается возврат незапрошенных полей
	/// Возвращает данные, отфильтрованные в соответствии с фильтрами 
	/// @param page Номер страницы данных
	/// @param lines Количество строк на странице
	abstract public function getJsonData($page = 0, $lines = 30);
};


?>
