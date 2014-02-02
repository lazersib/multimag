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
	protected $limit = 500; //< лимит на количество строк в ответе

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

/// Обработчик ajax запросов списка документов
/// Выдача содержит лишь данные документов, без связанных справочников
class ajaxRequest_DocList extends ajaxRequest {
	
	/// @brief Получить строку фильтров
	/// @return Возвращает WHERE часть SQL запроса к таблице журнала документов
	protected function getFilter() {
		global $db;
		$filter = '';
		if(is_array($this->options)) {
			foreach ($this->options as $key=>$value) {
				switch($key) {
					case 'df':	// Date from
						$filter.=' AND `doc_list`.`date`>='.strtotime($value);
						break;
					case 'dt':	// Date to
						$filter.=' AND `doc_list`.`date`<='.strtotime($value);
						break;
					case 'an':	// Alternative number
						$filter.=' AND `doc_list`.`altnum`='.$db->real_escape_string($value);
						break;
					case 'st':	// Subtype
						$filter.=' AND `doc_list`.`subtype`=\''.$db->real_escape_string($value).'\'';
						break;
					case 'fi':	// Firm id
						$filter.=' AND `doc_list`.`firm_id`='.intval($value);
						break;
					case 'sk':	// Store
						$filter.=' AND `doc_list`.`sklad`='.intval($value);
						break;
					case 'bk':{	// bank/kassa
						if($value[0]=='b')
							$filter.=' AND `doc_list`.`bank`='.intval(substr($value,1));
						else if($value[0]=='k')
							$filter.=' AND `doc_list`.`kassa`='.intval(substr($value,1));
						}break;
					case 'ag':	// Agent
						$filter.=' AND `doc_list`.`agent`='.intval($value);
						break;
					case 'au':	// Author
						$filter.=' AND `doc_list`.`user`='.intval($value);
						break;
					case 'dct':{	if(!is_array($value))	continue;
							$s = '';
							foreach($value as $d_id => $d_show) {
								if($d_show)	$s.=' OR `doc_list`.`type` = '.intval($d_id);
							}
							if($s)	$filter.=' AND (0 '.$s.')';
							
						}break;
				}		
			}
		}
		return $filter;
	}
	
	/// @brief Получить строку дополнительных таблиц
	/// @return Возвращает JOIN часть SQL запроса к таблице журнала документов
	protected function getJoins() {
		global $db;
		$joins = '';
		if(is_array($this->options)) {
			foreach ($this->options as $key=>$value) {
				switch($key) {
					case 'pos':	// Store pos
						$joins.='INNER JOIN `doc_list_pos` ON `doc_list_pos`.`tovar`='.intval($value).' AND `doc_list`.`id`=`doc_list_pos`.`doc`';
						break;
				}		
			}
		}
		return $joins;
	}


	/// @brief Получить json данные списка документов
	public function getJsonData($page = 0) {
		global $db;
		$start = intval($page) * $this->limit + 1;		
		$sql_filter = $this->getFilter();
		$sql_join = $this->getJoins();
		
		$sql = "SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`agent` AS `agent_id`, `doc_list`.`contract` AS `contract_id`, `doc_list`.`ok`,
			`doc_list`.`date`, `doc_list`.`kassa` AS `kassa_id`, `doc_list`.`bank` AS `bank_id`, `doc_list`.`sklad` AS `sklad_id`,
			`doc_list`.`user` AS `author_id`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`sum`, `doc_list`.`mark_del`, `doc_list`.`firm_id`,
			`doc_list`.`err_flag`, `doc_list`.`p_doc`, `na_sklad_t`.`value` AS `nasklad_id`, `v_kassu_t`.`value` AS `vkassu_id`
		FROM `doc_list`
		LEFT JOIN `doc_dopdata` AS `na_sklad_t` ON `na_sklad_t`.`doc`=`doc_list`.`id` AND `na_sklad_t`.`param`='na_sklad'
		LEFT JOIN `doc_dopdata` AS `v_kassu_t` ON `v_kassu_t`.`doc`=`doc_list`.`id` AND `v_kassu_t`.`param`='v_kassu'
		$sql_join
		WHERE 1 $sql_filter
		ORDER by `doc_list`.`date` DESC
		LIMIT $start,{$this->limit}";
		
		$result = array();
		$res = $db->query($sql);
		while ($line = $res->fetch_assoc()) {
			//if ($result)	$result.=",";
			//else		$result = '[';
			$line['date'] = date("Y-m-d H:i:s", $line['date']);
			if($line['nasklad_id']=='null')	unset($line['nasklad_id']);
			if($line['vkassu_id']=='null')	unset($line['vkassu_id']);
			//$result .= json_encode($line, JSON_UNESCAPED_UNICODE);
			$result[] = $line;
		}
		//$result .= ']';
		$result = json_encode($result, JSON_UNESCAPED_UNICODE);
		return $result;
	}
};

/// Обработчик ajax запросов списка типов документов
class ajaxRequest_doctypes extends ajaxRequest {
	
	/// @brief Получить json данные
	public function getJsonData($page = 0) {
		global $db;
		$sql = "SELECT `id`, `name` FROM `doc_types` ORDER by `id` ASC";
		$result = '';
		$a = array();
		$res = $db->query($sql);
		while ($line = $res->fetch_assoc()) {
			//if ($result)	$result.=",";
			//$result .= json_encode($line, JSON_UNESCAPED_UNICODE);
			$a[$line['id']] = $line['name'];
		}
		return json_encode($a, JSON_UNESCAPED_UNICODE);;
	}
}

/// Обработчик ajax запросов списка наименований агентов
class ajaxRequest_agentnames extends ajaxRequest {
	
	/// @brief Получить json данные
	public function getJsonData($page = 0) {
		global $db;
		$sql = "SELECT `id`, `name` FROM `doc_agent`";
		$result = '';
		$a = array();
		$res = $db->query($sql);
		while ($line = $res->fetch_assoc()) {
			$a[$line['id']] = $line['name'];
		}
		return json_encode($a, JSON_UNESCAPED_UNICODE);
	}
}

/// Обработчик ajax запросов списка наименований пользователей
class ajaxRequest_usernames extends ajaxRequest {
	
	/// @brief Получить json данные
	public function getJsonData($page = 0) {
		global $db;
		$sql = "SELECT `id`, `name` FROM `users` ORDER by `id` ASC";
		$result = '';
		$a = array();
		$res = $db->query($sql);
		while ($line = $res->fetch_assoc()) {
			$a[$line['id']] = $line['name'];
		}
		return json_encode($a, JSON_UNESCAPED_UNICODE);;
	}
}

/// Обработчик ajax запросов списка наименований складов
class ajaxRequest_skladnames extends ajaxRequest {
	
	/// @brief Получить json данные
	public function getJsonData($page = 0) {
		global $db;
		$sql = "SELECT `id`, `name` FROM `doc_sklady` ORDER by `id` ASC";
		$result = '';
		$a = array();
		$res = $db->query($sql);
		while ($line = $res->fetch_assoc()) {
			$a[$line['id']] = $line['name'];
		}
		return json_encode($a, JSON_UNESCAPED_UNICODE);;
	}
}

/// Обработчик ajax запросов списка наименований касс
class ajaxRequest_kassnames extends ajaxRequest {
	
	/// @brief Получить json данные
	public function getJsonData($page = 0) {
		global $db;
		$sql = "SELECT `num`, `name` FROM `doc_kassa` WHERE `ids`='kassa' ORDER by `num` ASC";
		$result = '';
		$a = array();
		$res = $db->query($sql);
		while ($line = $res->fetch_assoc()) {
			$a[$line['num']] = $line['name'];
		}
		return json_encode($a, JSON_UNESCAPED_UNICODE);;
	}
}

/// Обработчик ajax запросов списка наименований банков
class ajaxRequest_banknames extends ajaxRequest {
	
	/// @brief Получить json данные
	public function getJsonData($page = 0) {
		global $db;
		$sql = "SELECT `num`, `name` FROM `doc_kassa` WHERE `ids`='bank' ORDER by `num` ASC";
		$result = '';
		$a = array();
		$res = $db->query($sql);
		while ($line = $res->fetch_assoc()) {
			$a[$line['num']] = $line['name'];
		}
		return json_encode($a, JSON_UNESCAPED_UNICODE);;
	}
}

/// Обработчик ajax запросов списка наименований фирм
class ajaxRequest_firmnames extends ajaxRequest {
	
	/// @brief Получить json данные
	public function getJsonData($page = 0) {
		global $db;
		$sql = "SELECT `id`, `firm_name` FROM `doc_vars` ORDER by `id` ASC";
		$result = '';
		$a = array();
		$res = $db->query($sql);
		while ($line = $res->fetch_assoc()) {
			$a[$line['id']] = $line['firm_name'];
		}
		return json_encode($a, JSON_UNESCAPED_UNICODE);;
	}
}

/// Обработчик ajax запросов списка складских наименований
class ajaxRequest_posnames extends ajaxRequest {
	
	/// @brief Получить json данные
	public function getJsonData($page = 0) {
		global $db, $CONFIG;
		$sql = "SELECT `id`, `name`, `proizv` AS `vendor`, `vc` FROM `doc_base` ORDER BY `name`";
		$result = '';
		$a = array();
		$res = $db->query($sql);
		while ($line = $res->fetch_assoc()) {
			$str = '';
			if (@$CONFIG['poseditor']['vc'] && $line['vc'])
				$str = $line['vc'].' ';
			$str .= $line['name'];
			if($line['vendor'])
				$str .= ' '.$line['vendor'];
			$a[$line['id']] = $str;
		}
		return json_encode($a, JSON_UNESCAPED_UNICODE);;
	}
}


function ajax_autoload($class_name){
	global $CONFIG;
	$class_name = strtolower($class_name);
	@include_once $CONFIG['site']['location']."/include/ajaxrequest/".$class_name.'.php';
}

try {
	$tmpl->ajax = 1;
	need_auth();
	ob_start();
	$starttime = microtime(true);
	spl_autoload_register('ajax_autoload');
	$c = request('c');
	if(!$c)	throw new Exception ('Список компонентов не задан');
	$components = explode(',', $c);
	
	$result = "{\"result\":\"ok\"";
	
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
		$result.=",\"$component\":$data";
	}
	$exec_time = round(microtime(true) - $starttime, 3);
	$result .= ",\"exec_time\":\"$exec_time\"}";
	echo $result;
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