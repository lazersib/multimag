<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2016, BlackLight, TND Team, http://tndproject.org
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
// Опциональные параметры o/Z, где Z - имя компонента - имя поля, по которому должен быть отсортирован результат
// Опциональные параметры d/Z, где Z - имя компонента - если задан и не 0, сортировка будет от большего к меньшему
// Опциональные параметры f/Z, где Z - имя компонента - список полей, которые нужно получить в ответе, c разделителем "," (запятая)
// Опциональные параметры p/Z, где Z - имя компонента - номер страницы запроса
// Опциональные параметры l/Z, где Z - имя компонента - лимит на количество строк в ответе
// Опциональные параметры Z[], где Z - имя компонента, передаются компонентам как option

// Стандартные значения X:
// X = id - получение данных об элементе с заданным id
// X = s - получить список элементов, у которых название, или другое строковое поле содержит значение s
// Ответ формируется в формате json.

// Ответ должен содержать поле result, которое может принимать значения ok или err.
// В случае, если result = err, должно добавляться обязательное поле error, содержащее текст ошибки
// Ответы от конкретного компонента должны содержатся в полях с именами, соответствующими именам компонентов в запросе

include_once("core.php");

try {
	$tmpl->ajax = 1;
	need_auth();
	\acl::accessGuard('service.doclist', \acl::VIEW);
	ob_start();
	$starttime = microtime(true);
	$c = request('c');
	if(!$c)	throw new Exception ('Список компонентов не задан');
	$components = explode(',', $c);
	
	$result = "{\"result\":\"ok\"";
	
	foreach($components as $component) {
		$class_name = '\\Models\LDO\\'.$component;
		$request = new $class_name;
		$o = request('o/'.$component);
		if($o)	$request->setOrderField($o);
		$d = request('d/'.$component);
		if($d)	$request->setReverseOrderDirection($true);
		$f = request('f/'.$component);
		if($f)	$request->setFields($f);
		$p = request('p/'.$component);
		if($p)	$request->setPage($p);
		$l = request('l/'.$component);
		if($l)	$request->setLimit($f);
		
		$z = request($component);
		if($z)	$request->setOptions($z);
		
		$data = json_encode($request->getData(), JSON_UNESCAPED_UNICODE);
		$result.=",\"$component\":$data";
		if($request->end)
			$result.=",\"{$component}_end\":\"1\"";
	}
	$exec_time = round(microtime(true) - $starttime, 3);
	$result .= ",\"exec_time\":\"$exec_time\",\"user_id\":\"{$_SESSION['uid']}\"}";
	echo $result;
}
catch(AccessException $e) {
	ob_end_clean();
	$result = array('result'=>'err', 'error'=>'Нет доступа: '.$e->getMessage());
	echo json_encode($result, JSON_UNESCAPED_UNICODE);
}
catch(mysqli_sql_exception $e) {
    writeLogException($e);
    ob_end_clean();
    $result = array('result'=>'err', 'error'=>'Ошибка в базе данных: '.$e->getMessage());
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
}
catch(Exception $e) {
    ob_end_clean();
    writeLogException($e);
    $result = array('result'=>'err', 'error'=>$e->getMessage());
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
}

