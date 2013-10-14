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
// Опциональные параметры Z[], где Z - имя компонента, передаются компонентам как option
// Опциональный параметр p - номер страницы запроса
// Опциональный параметр l - лимит на количество строк в ответе
// Стандартные значения X:
// X = id - получение данных об элементе с заданным id
// X = s - получить список элементов, у которых название, или другое строковое поле содержит значение s
// Ответ формируется в формате json.

// Ответ должен содержать поле result, которое может принимать значения ok или err.
// В случае, если result = err, должно добавляться обязательное поле error, содержащее текст ошибки
// Ответы от конкретного компонента должны содержатся в полях с именами, соответствующими именам компонентов в запросе

include_once("core.php");

/// Базовый класс для обработчиков ajax запросов
abstract class ajaxRequest {
	protected $options = ''; //< Набор опций
	protected $fields = ''; //< Набор полей
	protected $order_field = false; //< Поле, по которому будет выполнена сортировка
	protected $order_reverse = false; //< Обратное направление сортировки (от большего к меньшему)
	protected $limit = 30; //< лимит на количество строк в ответе

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
	
	/// @brief Получить json данные
	/// Если запрошено поле, которое нельзя вернуть по каким-либо причинам, метод выбрасывает исключение
	/// Допускается возврат незапрошенных полей
	/// Возвращает данные, отфильтрованные в соответствии с фильтрами 
	/// @param page Номер страницы данных
	abstract public function getJsonData($page = 0);
};

/// Обработчик ajax запросов журнала документов
class ajaxRequest_DocJournal extends ajaxRequest {
	public function getJsonData($page = 0) {
		
	}
};

function ajax_autoload($class_name){
	global $CONFIG;
	$class_name = strtolower($class_name);
	@include_once $CONFIG['site']['location']."/include/ajaxrequest/".$class_name.'.php';
}

try {
	$tmpl->ajax = 1;
	need_auth();
	ob_start();
	spl_autoload_register('ajax_autoload');
	$c = request('c');
	if(!$c)	throw new Exception ('Список компонентов не задан');
	$components = explode(',', $c);
	foreach($components as $component) {
		$class_name = 'ajaxRequest_'.$component;
		$request = new $class_name;
		$o = request('o.'.$component);
		if($o)	$request->setOrderField($o);
		$d = request('d.'.$component);
		if($d)	$request->setReverseOrderDirection($true);
		$f = request('f.'.$component);
		if($f)	$request->setFields($f);
		$z = request($component);
		if($z)	$request->setOptions($z);
		if(isset($_REQUEST['l']))
			$request->setLimit(request('l'));
		
		$p = rcvint('p');
		$data = $request->getJsonData($p);
		echo $data;
	}
}
catch(AccessException $e) {
	ob_end_clean();
	$result = array('result'=>'err', 'error'=>'Нет доступа: '.$e->getMessage());
	echo json_encode($result, JSON_UNESCAPED_UNICODE);
}
catch(mysqli_sql_exception $e) {
	$tmpl->logger($e->getMessage(), 1);
	ob_end_clean();
	$result = array('result'=>'err', 'error'=>'Ошибка в базе данных: '.$e->getMessage());
	echo json_encode($result, JSON_UNESCAPED_UNICODE);
}
catch(Exception $e) {
	ob_end_clean();
	$result = array('result'=>'err', 'error'=>$e->getMessage());
	echo json_encode($result, JSON_UNESCAPED_UNICODE);
}




?>