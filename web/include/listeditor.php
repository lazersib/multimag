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

/// Базовый класс для редактора справочников
abstract class ListEditor {
	var $list = array();		//< Массив объектов справочника
	var $link_prefix;		//< Префикс для ссылок
	var $opt_var_name = 'opt';	//< Имя переменной HTTP(S) запросов для опций справочника
	var $param_var_name = 'le';	//< Имя переменной HTTP(S) запросов для данных элемента справочника
	var $line_var_name = '';	//< Имя переменной HTTP(S) запросов для id строки справочника
	var $print_name = 'Абстрактный справочник'; //< Отображаемое имя справочника
	var $table_name = '';		//< Имя таблицы справочника в БД
	var $db_link = null;		//< Ссылка на объект соединения с базой данных
	var $acl_object_name = '';	//< Имя объекта контроля привилегий
        var $add_headers = null;        // Дополнительные поля в заголовке таблицы
        protected $can_delete = false;  //< Допустимо ли удаление строк справочника
	
	public function __construct($db_link) {
		$this->db_link = $db_link;
	}
	
	/// Получить массив с именами колонок списка
	abstract public function getColumnNames();	
	
	/// Загрузить список всех элементов справочника
	public function loadList() {
		$col_names = $this->getColumnNames();
		$sql_names = '';
		foreach($col_names as $name=>$value) {
			if ($sql_names) {
				$sql_names .= ', ';
			}
			$sql_names .= "`$name`";
		}
		$res = $this->db_link->query("SELECT $sql_names FROM {$this->table_name} ORDER BY `id`");
		$this->list = array();
		while ($line = $res->fetch_assoc()) {
			$this->list[$line['id']] = $line;
		}
	}
	/// Получить данные элемента справочника
	public function getItem($id) {
		return $this->db_link->selectRow($this->table_name, $id);
	}

    /// Записать в базу строку справочника
    public function saveItem($id, $data) {
        $write_data = array();
        $col_names = $this->getColumnNames();
        foreach ($col_names as $col_id => $col_value) {
            if ($col_id == 'id') {
                continue;
            }
            if (isset($data[$col_id])) {
                if($data[$col_id]==='null') {
                    $write_data[$col_id] = 'NULL';
                }
                else {
                    $write_data[$col_id] = $data[$col_id];
                }
            } else {
                $write_data[$col_id] = 'NULL';
            }
        }
        if ($id) {
            if (!isAccess($this->acl_object_name, 'edit')) {
                throw new \AccessException();
            }
            $this->db_link->updateA($this->table_name, $id, $write_data);
        } else {
            if (!isAccess($this->acl_object_name, 'create')) {
                throw new \AccessException();
            }
            $id = $this->db_link->insertA($this->table_name, $write_data);
        }
        return $id;
    }

    /// Удалить запись
    public function removeItem($id) {
        if (!$this->can_delete) {
            throw new Exception("Удаление строк данного справочника недопустимо!");
        }
        if (!isAccess($this->acl_object_name, 'delete')) {
            throw new AccessException();
        }
        return $this->db_link->delete($this->table_name, $id);
    }

    /// @broef Получить HTML код таблицы с элементами справочника
    /// Вызывает (если определено) 'getField'.ucfirst($cn) для каждой ячейки таблицы
    public function getListItems() {
        if (!isAccess($this->acl_object_name, 'view')) {
            throw new AccessException($this->acl_object_name);
        }
        $ret = "<table class='list'><tr>";
        $col_names = $this->getColumnNames();
        foreach ($col_names as $id => $name) {
            $ret .= "<th>$name</th>";
        }
        if(is_array($this->add_headers)) {
            foreach ($this->add_headers as $id => $name) {
                $ret .= "<th>$name</th>";
            }
        }
        if ($this->can_delete) {
            $ret.="<th>&nbsp;</th>";
        }
        $ret .= "</tr>";
        $this->loadList();
        foreach ($this->list as $id => $line) {
            $ret.= "<tr><td><a href='{$this->link_prefix}&amp;{$this->opt_var_name}=e&amp;{$this->line_var_name}=$id'>$id</a></td>";
            foreach ($line as $cn => $cv) {
                if ($cn == 'id') {
                    continue;
                }

                $method = 'getField' . ucfirst($cn);
                if (method_exists($this, $method)) {
                    $ret .= "<td>" . $this->$method($line) . "</td>";
                } else {
                    $ret .= "<td>" . html_out($cv) . "</td>";
                }
            }
            if(is_array($this->add_headers)) {
                foreach ($this->add_headers as $cn => $cv) {
                    $method = 'getField' . ucfirst($cn);
                    if (method_exists($this, $method)) {
                        $ret .= "<td>" . $this->$method($line) . "</td>";
                    } else {
                        $ret .= "<td>&nbsp;</td>";
                    }
                }
            }
            if ($this->can_delete) {
                $ret.="<td><a href='{$this->link_prefix}&amp;{$this->opt_var_name}=d&amp;{$this->line_var_name}=$id'>"
                    . "<img src='/img/i_del.png' alt='del'></a></td>";
            }
            $ret .= "</tr>";
        }
        $ret .= "</table>";
        $ret .= "<span>&nbsp;&nbsp;&nbsp;<a href='{$this->link_prefix}&amp;{$this->opt_var_name}=n'><img src='/img/i_add.png' src='new'>&nbsp;&nbspНовая запись</a></span>";
        return $ret;
    }

    /// @brief Возвращает имя текущего элемента
	/// Нужно переопределить, если колонка с именем - не name
	public function getItemName($item) {
		if (isset($item['name'])) {
			return $item['name'];
		} else if (isset($item['id'])) {
			return $item['id'];
		} else {
			return '???';
		}
	}
	
	/// Возвращает HTML код checkbox элемента формы
	public function getCheckboxInput($name, $label, $value) {
		$checked = $value?' checked':'';
		return "<label><input type='checkbox' name='$name' value='1'{$checked}>".html_out($label)."</label>";
	}
	
	/// Возвращает HTML код формы редактирования элемента
	public function getEditForm($id) {
		global $tmpl;
		$ret = "<form action='{$this->link_prefix}' method='post'>";
		$ret .= "<input type='hidden' name='{$this->opt_var_name}' value='s'>";
		
		$item = $this->getItem($id);
		if($item){
			$ret .= "<input type='hidden' name='{$this->line_var_name}' value='$id'>";
			$tmpl->addBreadcrumb('Правка элемента "'.$this->getItemName($item).'"', '');
		}
		else {
			$item = $this->getColumnNames();
			foreach ($item as $_id=>$val) {
				$item[$_id] = '';
			}
			$ret .= "<input type='hidden' name='{$this->line_var_name}' value='0'>";
			$tmpl->addBreadcrumb('Новый элемент', '');
		}
		$ret .= "<table class='list' width='600px'><tr>";
		$col_names = $this->getColumnNames();
		foreach($col_names as $_id=>$cname) {
			if ($_id == 'id') {
				continue;
			}
			$method = 'getInput'.ucfirst($_id);
			$ret .= "<tr><td align='right'>".html_out($cname)."</td><td>";
			$input_name = $this->param_var_name."[$_id]";
			if (method_exists($this, $method)) {
				$ret .= $this->$method($input_name, $item[$_id]);
			} else {
				$ret .= "<input type='text' name='$input_name' value='" . html_out($item[$_id]) . "' style='width:95%;'>";
			}
			$ret .= "</td></tr>";
		}
		$ret .= "<tr><td>&nbsp;</td><td><button type='submit'>Записать</button></td></tr>";
		$ret .= "</table></form>";
		return $ret;
	}

	/// Добавить в шаблон HTML код виджета справочника
	public function run() {
		global $tmpl;
		$opt = request($this->opt_var_name);
		if ($opt != '') {
			$tmpl->addBreadcrumb($this->print_name, $this->link_prefix);
		} else {
			$tmpl->addBreadcrumb($this->print_name, '');
		}
		$tmpl->setTitle($this->print_name);
		switch($opt) {
			case '':
				$tmpl->addContent($this->getListItems());
				break;
			case 'e':
				$id = rcvint($this->line_var_name);
				$tmpl->addContent($this->getEditForm($id));
				break;
			case 'n':
				$tmpl->addContent($this->getEditForm(0));
				break;
			case 's':
				$id = rcvint($this->line_var_name);
				$id = $this->saveItem($id, request($this->param_var_name));
				$tmpl->msg("Данные сохранены", "ok");
				$tmpl->addContent($this->getEditForm($id));
				break;
                        case 'd':
                                $id = rcvint($this->line_var_name);
                                $this->removeItem($id);
                                $tmpl->msg("Строка удалена", "ok");
                                $tmpl->addContent($this->getListItems());
                                break;
			default:
				throw new NotFoundException('Неверная опция '.$opt);
		}
	}
}
