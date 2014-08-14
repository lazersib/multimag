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

define("MULTIMAG_REV", "692");
define("MULTIMAG_VERSION", "0.2.".MULTIMAG_REV);

/// Файл содержит код, используемый как web, так и cli скриптами

/// Автозагрузка общих классов для ядра и cli
function common_autoload($class_name){
	global $CONFIG;
	$class_name = strtolower($class_name);
	$class_name = str_replace('\\', '/', $class_name);
	@include_once $CONFIG['location']."/common/".$class_name.'.php';
}
spl_autoload_register('common_autoload');

/// Отправить сообщение по электронной почте
/// @param email Адрес получателя
/// @param subject Тема сообщения
/// @param msg Тело сообщения
/// @param from Адрес отправителя
function mailto($email, $subject, $msg, $from="") {
	global $CONFIG;
	require_once($CONFIG['location'].'/common/email_message.php');

	$email_message=new email_message_class();
	$email_message->default_charset="UTF-8";
	$email_message->SetEncodedEmailHeader("To", $email, $email);
	$email_message->SetEncodedHeader("Subject", $subject);
	if($from)	$email_message->SetEncodedEmailHeader("From", $from, $from);
	else		$email_message->SetEncodedEmailHeader("From", $CONFIG['site']['admin_email'], "Почтовый робот {$CONFIG['site']['display_name']}");
	$email_message->SetHeader("Sender",$CONFIG['site']['admin_email']);
	$email_message->SetHeader("X-Multimag-version", MULTIMAG_VERSION);
	$email_message->AddQuotedPrintableTextPart($msg);
	$error=$email_message->Send();

	if(strcmp($error,""))	throw new Exception($error);
	else			return 0;
}

/// возвращает строковое представление интервала
/// @param times - время в секундах
function sectostrinterval($times) {
	$ret=($times%60).' с.';
	$times=round($times/60);
	if(!$times)	return $ret;
	$ret=($times%60).' м. '.$ret;
	$times=round($times/60);
	if(!$times)	return $ret;
	$ret=$times.' ч. '.$ret;
	return $ret;
}

/// Получить unixtime начала указанных суток
/// @param date произвольное время в UNIXTIME формате
function date_day($date) {
   $ee=date("d M Y 00:00:00",$date);
   $tm=strtotime($ee);
   return $tm;
}

/// Расчёт долга агента. Положительное число обозначает долг агента, отрицательное - долг перед агентом.
/// @param agent_id	ID агента, для которого расчитывается баланс
/// @param no_cache	Не брать данные расчёта из кеша
/// @param firm_id	ID собственной фирмы, для которой будет расчитан баланс. Если 0 - расчёт ведётся для всех фирм.
/// @param local_db	Дескриптор соединения с базой данных. Если не задан - используется глобальная переменная.
function agentCalcDebt($agent_id, $no_cache=0, $firm_id=0, $local_db=0) {
	global $tmpl, $db, $doc_agent_dolg_cache_storage;
	//if(!$no_cache && isset($doc_agent_dolg_cache_storage[$agent_id]))	return $doc_agent_dolg_cache_storage[$agent_id];
	settype($agent_id,'int');
	settype($firm_id,'int');
	$dolg=0;
	$sql_add=$firm_id?"AND `firm_id`='$firm_id'":'';
	if($local_db)
		$res = $local_db->query("SELECT `type`, `sum` FROM `doc_list` WHERE `ok`>'0' AND `agent`='$agent_id' AND `mark_del`='0' $sql_add");
	else	$res = $db->query("SELECT `type`, `sum` FROM `doc_list` WHERE `ok`>'0' AND `agent`='$agent_id' AND `mark_del`='0' $sql_add");
	while($nxt=$res->fetch_row()) {
		switch($nxt[0])	{
			case 1: $dolg-=$nxt[1]; break;
			case 2: $dolg+=$nxt[1]; break;
			case 4: $dolg-=$nxt[1]; break;
			case 5: $dolg+=$nxt[1]; break;
			case 6: $dolg-=$nxt[1]; break;
			case 7: $dolg+=$nxt[1]; break;
			case 18: $dolg+=$nxt[1]; break;
		}
	}
	$res->free();
	$dolg = sprintf("%0.2f", $dolg);
	//$doc_agent_dolg_cache_storage[$agent_id]=$dolg;
	return $dolg;
}

/// @brief Класс расширяет функциональность mysqli
/// Т.к. используется почти везде, нет смысла выносить в отдельный файл
class MysqiExtended extends mysqli {
	
	/// Начать транзакцию
	function startTransaction(){
		return $this->query("START TRANSACTION");
	}
	
	/// Получить все значения строки из таблицы по ключу в виде массива
	/// @param table	Имя таблицы
	/// @param key_value	Значение ключа, по которому производится выборка. Будет приведено к целому типу.
	/// @return 		В случае успеха возвращает ассоциативный массив с данными. В случае sql ошибки вернёт false. В случае, если искомой строки нет в таблице, вернет 0
	function selectRow($table, $key_value) {
		settype($key_value,'int');
		$res=$this->query('SELECT * FROM `'.$table.'` WHERE `id`='.$key_value);
		if(!$res)		return false;
		if(!$res->num_rows)	return 0;
		return	$res->fetch_assoc();
	}
	
	/// Получить заданные значения строки из таблицы по ключу в виде массива
	/// @param table	Имя таблицы
	/// @param key_value	Значение ключа, по которому производится выборка. Будет приведено к целому типу.
	/// @param array	Массив со значениями, содержащими имена полей
	/// @return 		В случае успеха возвращает ассоциативный массив с данными. В случае, если искомой строки нет в таблице, вернет массив со значениями, равными ''
	function selectRowA($table, $key_value, $array) {
		settype($key_value,'int');
		$q=$f='';
		foreach($array as $value) {
			if($f)	$q.=',`'.$value.'`';
			else {	$q='`'.$value.'`'; $f=1;}
		}
		$res = $this->query('SELECT '.$q.' FROM `'.$table.'` WHERE `id`='.$key_value);
		if(!$res->num_rows){
			$info = array();
			foreach ($array as $value)
				$info[$value] = '';
			return $info;
		}
		return	$res->fetch_assoc();
	}
	
		/// Получить заданные значения строки из таблицы по ключу в виде массива
	/// @param table	Имя таблицы
	/// @param key_value	Значение ключа, по которому производится выборка. Будет приведено к целому типу.
	/// @param array	Массив с ключами, содержащими имена полей
	/// @return 		В случае успеха возвращает ассоциативный массив с данными. В случае, если искомой строки нет в таблице, вернет исходный массив
	function selectRowAi($table, $key_value, $array) {
		settype($key_value,'int');
		$q=$f='';
		foreach($array as $key => $value) {
			if($f)	$q.=',`'.$key.'`';
			else {	$q='`'.$key.'`'; $f=1;}
		}
		$res=$this->query('SELECT '.$q.' FROM `'.$table.'` WHERE `id`='.$key_value);
		if(!$res->num_rows)	return $array;
		return	$res->fetch_assoc();
	}
	
	/// Получить значения столбца из таблицы структуры ключ/param/value по ключу в виде массива
	/// @param table	Имя таблицы
	/// @param key_value	Значение ключа, по которому производится выборка. Будет приведено к целому типу.
	/// @param array	Массив со значениями, содержащими имена полей
	/// @return 		В случае успеха возвращает ассоциативный массив с данными. В случае sql ошибки вернёт false. В случае, если искомого значения нет в таблице, вернет пустую строку для такого значения
	function selectFieldKA($table, $key_name, $key_value, $array) {
		settype($key_value,'int');
		$a=array_fill_keys($array, '');
		$res=$this->query('SELECT `param`, `value` FROM `'.$table.'` WHERE `'.$key_name.'`='.$key_value);
		if(!$res)	return false;
		while($line=$res->fetch_row())
		{
			if(array_key_exists($line[0], $a))
				$a[$line[0]]=$line[1];
		}
		return $a;
	}
	
	/// Вставить строку в заданную таблицу
	/// @param table	Имя таблицы
	/// @param array	Ассоциативный массив вставляемых данных
	/// @return id вставленной строки или false в случае ошибки
	function insertA($table, $array) {
		$cols=$values='';
		$f=0;
		foreach($array as $key=>$value){
			if($value!=='NULL')
				$value = '\''.$this->real_escape_string($value).'\'';
			if(!$f){
				$cols = '`'.$key.'`';
				$values = $value;
				$f=1;
			}
			else {
				$cols .= ', `'.$key.'`';
				$values .= ', '.$value;
			}
		}
		if(!$this->query("INSERT INTO `$table` ($cols) VALUES ($values)"))
			return false;
		return $this->insert_id;
	}


	/// Обновить данные в заданной таблице
	/// @param table	Имя таблицы
	/// @param key_value	Значение ключа, по которому будет произведено обновление. Будет приведено к целому типу.
	/// @param field	Название поля таблицы
	/// @param value	Новое значение поля таблицы. Автоматически экранируется.
	/// @return Возвращаемое значение аналогично mysqli::query
	function update($table, $key_value, $field, $value){
		settype($key_value,'int');
		if($value!=='NULL')
			$value = '\''.$this->real_escape_string($value).'\'';
		return $this->query("UPDATE `$table` SET `$field`=$value WHERE `id`=$key_value");
	}
	
	/// Обновить данные в заданной таблице данными из массива по ключу с именем id
	/// @param table	Имя таблицы
	/// @param key_value	Значение ключа, по которому будет произведено обновление. Будет приведено к целому типу.
	/// @param array	Ассоциативный массив ключ/значение для обновления. Значения автоматически экранируется.
	/// @return 		Возвращаемое значение аналогично mysql::query
	function updateA($table, $key_value, $array){
		settype($key_value,'int');
		$q=$this->updatePrepare($array);
		return $this->query("UPDATE `$table` SET $q WHERE `id`=$key_value");
	}

	/// Обновить данные в заданной таблице данными из массива по ключу с заданным именем
	/// @param table	Имя таблицы
	/// @param key_name	Имя ключа таблицы
	/// @param key_value	Значение ключа, по которому будет произведено обновление. Будет приведено к целому типу.
	/// @param array	Ассоциативный массив ключ/значение для обновления. Значения автоматически экранируется.
	/// @return Возвращаемое значение аналогично mysqli::query
	function updateKA($table, $key_name, $key_value, $array) {
		settype($key_value,'int');
		$q=$this->updatePrepare($array);
		return $this->query("UPDATE `$table` SET $q WHERE `id`=$key_value");
	}
	
	/// Заменить данные в заданной таблице данными из массива по ключу с заданным именем
	/// @param table	Имя таблицы
	/// @param key_name	Имя ключа таблицы
	/// @param key_value	Значение ключа, по которому будет произведено обновление. Будет приведено к целому типу.
	/// @param array	Ассоциативный массив ключ/значение для обновления. Значения автоматически экранируется.
	/// @return Возвращаемое значение аналогично mysqli::query
	function replaceKA($table, $key_name, $key_value, $array) {
		settype($key_value,'int');
		$q=$f='';
		foreach($array as $key => $value) {
			if($value!=='NULL')
				$value = '\''.$this->real_escape_string($value).'\'';
			if($f)	$q.=',(\''.$key_value.'\',\''.$key.'\','.$value.')';
			else {	$q='(\''.$key_value.'\',\''.$key.'\','.$value.')'; $f=1;}
		}
		return $this->query('REPLACE `'.$table.'` (`'.$key_name.'`, `param`, `value`) VALUES '.$q);
	}
	
	/// Удалить из заданной тоаблицы строку с указанным id
	/// @param key_value	Значение ключа, по которому будет произведено обновление. Будет приведено к целому типу.
	public function delete($table, $key_value) {
		settype($key_value,'int');
		return $this->query('DELETE FROM `'.$table.'` WHERE `id`='.$key_value);
	}
	
	/// Подготавливает данные для update запросов
	/// @param array Ассоциативный массив ключ/значение для обновления. Значения автоматически экранируется.
	private function updatePrepare($array) {
		$q=$f='';
		foreach($array as $key => $value) {
			if($value!=='NULL')
				$value = '\''.$this->real_escape_string($value).'\'';
			if($f)	$q.=',`'.$key.'`='.$value;
			else {	$q='`'.$key.'`='.$value; $f=1;}
		}
		return $q;
	}
};


?>