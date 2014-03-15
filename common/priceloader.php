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

/// Абстрактный класс для создания загрузчиков прайсов
/// Позволяет загрузить данные в базу, либо сформировать HTML - таблицу с данными из файла
abstract class PriceLoader
{
	// Входные данные
	protected $firm_id=0;
	
	// Настройки
	protected $silent=0;		// Не выводиьть сообщений
	protected $build_html_data=0;	// Сформировать HTML таблицу. Значение - кол-во колонок таблицы
	protected $insert_to_database=0;	// Сохранить результат в базу данных

	// Общие рабочие переменные
	protected $table_parsing=0;	// Флаг процесса обработки таблицы
	protected $line_cnt=0;		// Счётчик обработанных строк прайса
	protected $line;			// Массив ячеек текущей строки
	protected $firm_cols=array();	// Номера требуемых колонок прайса
	protected $def_currency;		// Валюта по умолчанию
	protected $currencies=array();	// Массив валют
	
	// Выходные данные
	protected $html='';		// HTML - представление таблиц (опция build_html_data)
	
	// Абстрактный конструктор запрещает создание экземпляра класса
	abstract function __construct($filename);
	
	/// Включить/выключить создание HTML таблицы
	public function setBuildHTMLData($lines=20)	{$this->build_html_data=$lines;}
	
	/// Включить/выключить сохранение данных в базу. Требует определения соответствия прайса организации
	public function setInsertToDatabase($flag=1)	{$this->insert_to_database=$flag;}
	
	/// Получить HTML-представление
	public function getHTML()			{return $this->html;}
	
	/// Проверить, существует ли указанная строка-сигнатура в загруженных данных
	abstract public function findSignature($signature);
	
	/// Начать разбор загруженных данных. Только для внутреннего использования
	abstract protected function parse();
	
	/// Определение принадлежности прайс-листа по сигнатуре
	public function detectFirm() {
		global $db;
		$res = $db->query("SELECT `id`, `name`, `signature`, `currency` FROM `firm_info`");
		while ($nxt = $res->fetch_row()) {
			if ($this->findSignature($nxt[2])) {
				$this->def_currency = $nxt[3];
				return $this->firm_id = $nxt[0];
			}
		}
		return false;
	}
	
	/// Определение совпадений фирмы с несколькими сигнатурами. Для выполнения анализа для нужной фирмы использовать useFirmAndCurency(firm_id, currency_id)
	public function detectSomeFirm() {
		global $db;
		$firm_list = array();
		$res = $db->query("SELECT `id`, `name`, `signature`, `currency` FROM `firm_info`");
		while ($nxt = $res->fetch_row()) {
			if ($this->findSignature($nxt[2]))
				$firm_list[] = array('firm_id' => $nxt[0], 'firm_name' => $nxt[1], 'curency_id' => $nxt[3]);
		}
		return $firm_list;
	}
	
	/// Выбрать фирму и валюту для последующей загрузки прайса в базу
	public function useFirmAndCurency($firm_id, $currency_id) {
		$this->firm_id = $firm_id;
		$this->def_currency = $currency_id;
	}

	/// Запуск анализа
	public function Run() {
		global $db;
		$this->line_cnt = 0;
		if (($this->firm_id == 0) && $this->insert_to_database)
			throw new Exception("Принадлежность прайс-листа к фирме не задана");
		$this->table_parsing = 0;
		$this->html = '';

		if ($this->insert_to_database) {
			$db->query("DELETE FROM `price` WHERE `firm` = '{$this->firm_id}'");
			$db->query("UPDATE `firm_info` SET `last_update`=NOW() WHERE `id`='{$this->firm_id}'");
			$res = $db->query("SELECT `id`, `name` FROM `currency`");
			$this->currencies = array();
			while ($nxt = $res->fetch_row())
				$this->currencies[$nxt[1]] = strtoupper($nxt[0]);
		}
		$this->parse();
		return $this->line_cnt;
	}
	
// ===================== Функции, вызываемые парсером в процессе работы =============================================
	
	protected function tableBegin($table_name) {
		global $db;
		if ($this->insert_to_database) {
			$sql_table_name = $db->real_escape_string($table_name);
			$res = $db->query("SELECT `art`, `name`, `cost`, `nal`, `currency`, `info` FROM `firm_info_struct` WHERE `firm_id`='{$this->firm_id}' AND `table_name` LIKE '$sql_table_name'");
			if (!$res->num_rows) {
				$res = $db->query("SELECT `art`, `name`, `cost`, `nal`, `currency`, `info` FROM `firm_info_struct` WHERE `firm_id`='{$this->firm_id}' AND `table_name` = ''");
			}
			if (!$res->num_rows)
			//настройки для листа не найдены
				$this->table_parsing = 0;
			else {
				$this->firm_cols = $res->fetch_assoc();
				$this->table_parsing = 1;
			}
		}
		if ($this->build_html_data) {
			$this->html.="<table class='list'><caption>$table_name</caption><thead><tr>";
			for ($i = 1; $i <= $this->build_html_data; $i++) {
				$this->html.="<th>$i</th>";
			}
			$this->html.="</tr></thead><tbody>";
		}
	}

	protected function tableEnd() {
		if ($this->build_html_data)
			$this->html.="</tbody></table><br>";
	}

	protected function rowBegin() {
		// Пока пусто
	}

	protected function rowEnd() {
		global $db;
		if ($this->insert_to_database && $this->table_parsing && isset($this->line[$this->firm_cols['cost']])) {
			$cost = $this->line[$this->firm_cols['cost']];
			$cost = preg_replace("/[^,.\d]+/", "", $cost);
			$cost = str_replace(",", ".", $cost);
			settype($cost, "double");

			if (@$this->line[$this->firm_cols['name']] && (@$this->line[$this->firm_cols['nal']] || @$this->line[$this->firm_cols['cost']])) {
				$this->line_cnt++;
				$name = $db->real_escape_string(@$this->line[$this->firm_cols['name']]);
				$art = $db->real_escape_string(@$this->line[$this->firm_cols['art']]);
				$nal = $db->real_escape_string(@$this->line[$this->firm_cols['nal']]);
				$curr = strtoupper(trim(@$this->line[$this->firm_cols['currency']]));
				$info = $db->real_escape_string(@$this->line[$this->firm_cols['info']]);
				if (strpos($curr, '$') !== false)
					$curr = 'USD';
				else if (stripos($curr, 'р') !== false)
					$curr = 'RUR';
				if (isset($this->currencies[$curr]))
					$curr = $this->currencies[$curr];
				else
					$curr = $this->def_currency;
				$db->query("INSERT INTO `price`	(`name`,`cost`,`firm`,`art`,`date`, `nal`, `currency`, `info`) VALUES 
				('$name', '$cost', '{$this->firm_id}', '$art', NOW(), '$nal', '$curr', '$info')");
			}
		}
		if ($this->build_html_data) {
			$this->html.="<tr>";
			for ($i = 1; $i <= $this->build_html_data; $i++) {
				$val = @htmlentities($this->line[$i], ENT_COMPAT, 'UTF-8');
				$this->html.="<td>$val</td>";
			}
			$this->html.="</tr>";
		}
	}

}

?>