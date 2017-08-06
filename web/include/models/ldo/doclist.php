<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2017, BlackLight, TND Team, http://tndproject.org
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

namespace Models\LDO;

/// Класс списка документов
/// Выдача содержит лишь данные документов, без связанных справочников
class doclist extends \Models\ListDataObject {

    protected $limit = 1000; //< лимит на количество строк в ответе

    /// @brief Получить строку фильтров
    /// @return Возвращает WHERE часть SQL запроса к таблице журнала документов

    protected function getFilter() {
        global $db;
        $filter = '';
        if (is_array($this->options)) {
            foreach ($this->options as $key => $value) {
                switch ($key) {
                    case 'df': // Date from

                        $filter.=' AND `doc_list`.`date`>=' . strtotime($value);
                        break;
                    case 'dt': // Date to
                        $filter.=' AND `doc_list`.`date`<=' . (strtotime($value) + 60 * 60 * 24 - 1);
                        break;
                    case 'an': // Alternative number
                        $filter.=' AND `doc_list`.`altnum`=' . $db->real_escape_string($value);
                        break;
                    case 'st': // Subtype
                        $filter.=' AND `doc_list`.`subtype`=\'' . $db->real_escape_string($value) . '\'';
                        break;
                    case 'fi': // Firm id
                        $filter.=' AND `doc_list`.`firm_id`=' . intval($value);
                        break;
                    case 'sk': // Store
                        $filter.=' AND (`doc_list`.`sklad`=' . intval($value) . ' OR `na_sklad_t`.`value`=' . intval($value) . ')';
                        break;
                    case 'bk': { // bank/kassa
                            if ($value[0] == 'b')
                                $filter.=' AND `doc_list`.`bank`=' . intval(substr($value, 1));
                            else if ($value[0] == 'k')
                                $filter.=' AND (`doc_list`.`kassa`=' . intval(substr($value, 1)) . ' OR `v_kassu_t`.`value`=' . intval(substr($value, 1)) . ')';
                        }break;
                    case 'ag': // Agent
                        $filter.=' AND `doc_list`.`agent`=' . intval($value);
                        break;
                    case 'au': // Author
                        $filter.=' AND `doc_list`.`user`=' . intval($value);
                        break;
                    case 'ok': // Ok status
                        if ($value == '+')
                            $filter.=' AND `doc_list`.`ok`!=0';
                        else if ($value == '-')
                            $filter.=' AND `doc_list`.`ok`=0';
                        break;
                    case 'dct': {
                            if (!is_array($value))
                                continue;
                            $s = '';
                            foreach ($value as $d_id => $d_show) {
                                if ($d_show)
                                    $s.=' OR `doc_list`.`type` = ' . intval($d_id);
                            }
                            if ($s)
                                $filter.=' AND (0 ' . $s . ')';
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
        if (is_array($this->options)) {
            foreach ($this->options as $key => $value) {
                switch ($key) {
                    case 'pos': // Store pos
                        $joins.='INNER JOIN `doc_list_pos` ON `doc_list_pos`.`tovar`=' . intval($value) . ' AND `doc_list`.`id`=`doc_list_pos`.`doc`';
                        break;
                }
            }
        }
        return $joins;
    }

    /// @brief Получить строку дополнительных полей
    /// @return Возвращает строку дополнительных полей
    protected function getAdds() {
        global $db;
        $add = '';
        if (is_array($this->options)) {
            foreach ($this->options as $key => $value) {
                switch ($key) {
                    case 'pos': // Store pos
                        $add.=', `doc_list_pos`.`cnt` AS `pos_cnt`, `doc_list_pos`.`cost` AS `pos_cost`, `doc_list_pos`.`page` AS `pos_page`';
                        break;
                }
            }
        }
        return $add;
    }

    /// @brief Получить сумму оплаты реализации
    /// Поведение для других документов не определено
    /// @param doc_id	ID документа
    /// @param p_doc_id	ID родительского документа
    /// @return		сумма оплаты
    protected function getPaySum($doc_id, $p_doc_id) {
        global $db;
        settype($p_doc_id, 'int');
        $add = '';
        if ($p_doc_id)
            $add = " OR (`p_doc`='$p_doc_id' AND (`type`='4' OR `type`='6'))";
        $res = $db->query("SELECT SUM(`sum`)
			FROM `doc_list`
			WHERE ((`p_doc`='$doc_id' AND (`type`='4' OR `type`='6')) $add) AND `ok`>0 AND `p_doc`!='0' GROUP BY `p_doc`");
        if ($r = $res->fetch_row())
            return round($r[0], 2);
        else
            return 0;
    }

    /// @brief Получить состояние отгрузки заявки
    /// Поведение для других документов не определено
    /// @param doc_id	ID документа
    /// @return		n - не отгружено, p - частичная отгрузка,  a - полная отгрузка
    protected function getOutStatus($doc_id) {
        //return '';
        global $db;
        $res = $db->query("SELECT `doc_list_pos`.`doc` AS `doc_id`, `doc_list_pos`.`tovar` AS `pos_id`, `doc_list_pos`.`cnt`,
			( SELECT SUM(`doc_list_pos`.`cnt`) FROM `doc_list_pos`
			INNER JOIN `doc_list` ON `doc_list_pos`.`doc`=`doc_list`.`id`
			WHERE `doc_list_pos`.`tovar`=`pos_id` AND `doc_list`.`p_doc`=`doc_id` AND `doc_list`.`type`='2'	AND `doc_list`.`ok`>'0'
			) AS `r_cnt`
		FROM `doc_list_pos`
		WHERE `doc_list_pos`.`doc`='$doc_id'");
        $f = 0;
        $n = 0;
        while ($nx = $res->fetch_assoc()) {
            if ($nx['r_cnt'] == 0) {
                $n = 1;
                continue;
            }
            $f = 1;
            if ($nx['cnt'] > $nx['r_cnt']) {
                $f = 2;
                break;
            }
        }
        switch ($f) {
            case 1: if ($n)
                    $r = 'p';
                else
                    $r = 'n';
                break;
            case 2: $r = 'p';
                break;
            default:$r = 'n';
        }
        $res->free();
        return $r;
    }

    /// @brief Получить данные списка документов
    public function getData() {
        global $db;
        $start = intval($this->page) * $this->limit;
        $sql_filter = $this->getFilter();
        $sql_join = $this->getJoins();
        $sql_add = $this->getAdds();
        $doc_types = \document::getListTypes();
        
        $sql = "SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`agent` AS `agent_id`, `doc_list`.`contract` AS `contract_id`, `doc_list`.`ok`,
                `doc_list`.`date`, `doc_list`.`kassa` AS `kassa_id`, `doc_list`.`bank` AS `bank_id`, `doc_list`.`sklad` AS `sklad_id`,
                `doc_list`.`user` AS `author_id`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`sum`, `doc_list`.`mark_del`, `doc_list`.`firm_id`,
                `doc_list`.`err_flag`, `doc_list`.`p_doc`, `na_sklad_t`.`value` AS `nasklad_id`, `v_kassu_t`.`value` AS `vkassu_id` $sql_add
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
            if(!\acl::testAccess([ 'firm.global', 'firm.'.$line['firm_id'] ], \acl::VIEW) || !\acl::testAccess('doc.'.@$doc_types[$line['type']], \acl::VIEW)) {
                continue;
            }

            $line['date'] = date("Y-m-d", $line['date']) . '&nbsp' . date("H:i:s", $line['date']);
            if ($line['nasklad_id'] == 'null') {
                unset($line['nasklad_id']);
            }
            if ($line['vkassu_id'] == 'null') {
                unset($line['vkassu_id']);
            }
            //$result .= json_encode($line, JSON_UNESCAPED_UNICODE);

            switch ($line['type']) {
                case 2: // Проплаты
                    $line['pay_sum'] = $this->getPaySum($line['id'], $line['p_doc']);
                    break;
                case 3: // Отгрузки
                    $line['out_status'] = $this->getOutStatus($line['id']);
                    break;
            }


            $result[] = $line;
        }
        if ($res->num_rows < $this->limit) {
            $this->end = 1;
        }
        return $result;
    }

}

