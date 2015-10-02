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
/// Документ *товар в пути*
class doc_v_puti extends doc_Nulltype {

    function __construct($doc = 0) {
        parent::__construct($doc);
        $this->doc_type = 12;
        $this->typename = 'v_puti';
        $this->viewname = 'Товары в пути';
        $this->sklad_editor_enable = true;
        $this->header_fields = 'sklad cena separator agent';
        settype($this->id, 'int');
    }

    function initDefDopdata() {
        $this->def_dop_data = array('dataprib' => '', 'transkom' => 0, 'input_doc' => '', 'cena' => 0);
    }

    function DopHead() {
        global $tmpl, $db;
        if (!$this->id) {
            $this->dop_data['dataprib'] = date("Y-m-d");
        }
        $tmpl->addContent("Ориентировочная дата прибытия:<br><input type='text' name='dataprib'  class='vDateField' value='{$this->dop_data['dataprib']}'>");

        $cur_agent = $this->doc_data['agent'];
        if (!$cur_agent) {
            $cur_agent = 1;
        }

        if (!$this->dop_data['transkom']) {
            $this->dop_data['transkom'] = $cur_agent;
        }

        $res = $db->query("SELECT `name` FROM `doc_agent` WHERE `id`='{$this->dop_data['transkom']}'");
        if ($res->num_rows) {
            list($transkom_name) = $res->fetch_row();
        } else {
            $transkom = '';
        }

        $tmpl->addContent("<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<br>Транспортная компания:<br>
		<input type='hidden' name='transkom' id='transkom_id' value='{$this->dop_data['transkom']}'>
		<input type='text' id='transkom'  style='width: 100%;' value='$transkom_name'><br>
		<script type=\"text/javascript\">
		$(document).ready(function(){
			$(\"#transkom\").autocomplete(\"/docs.php\", {
			delay:300,
			minChars:1,
			matchSubset:1,
			autoFill:false,
			selectFirst:true,
			matchContains:1,
			cacheLength:10,
			maxItemsToShow:15,
			formatItem:agliFormat,
			onItemSelect:transkomselectItem,
			extraParams:{'l':'agent','mode':'srv','opt':'ac'}
			});
		});

		function transkomselectItem(li) {
		if( li == null ) var sValue = \"Ничего не выбрано!\";
		if( !!li.extra ) var sValue = li.extra[0];
		else var sValue = li.selectValue;
		document.getElementById('transkom_id').value=sValue;
		}
		</script>");

        $tmpl->addContent("Ном. вх. документа:<br><input type='text' name='input_doc' value='{$this->dop_data['input_doc']}'><br>");
    }

    function DopSave() {
        $new_data = array(
            'dataprib' => rcvdate('dataprib'),
            'transkom' => request('transkom'),
            'input_doc' => request('input_doc')
        );
        $old_data = array_intersect_key($new_data, $this->dop_data);

        $log_data = '';
        if ($this->id) {
            $log_data = getCompareStr($old_data, $new_data);
        }
        $this->setDopDataA($new_data);
        if ($log_data) {
            doc_log("UPDATE {$this->typename}", $log_data, 'doc', $this->id);
        }
    }

    /// Провести документ
    /// @param silent Не менять отметку проведения
    function docApply($silent = 0) {
        global $db;
        // Транзиты
        $res = $db->query("SELECT `id`, `ok` FROM `doc_list` WHERE `ok`>0 AND `type`=1 AND `p_doc`={$this->id}");
        if (!$res->num_rows) {
            $res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`
                FROM `doc_list_pos`
                LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
                WHERE `doc_list_pos`.`doc`='{$this->id}'");
            $vals = '';
            while ($nxt = $res->fetch_row()) {
                if ($vals) {
                    $vals .= ',';
                }
                $vals .= "('$nxt[0]', '$nxt[1]')";
            }
            if ($vals) {
                $db->query("INSERT INTO `doc_base_dop` (`id`, `transit`) VALUES $vals
                    ON DUPLICATE KEY UPDATE `transit`=`transit`+VALUES(`transit`)");
            } else {
                throw new Exception("Не удалось провести пустой документ!");
            }
        }
        if ($silent) {
            return;
        }
        if (!$this->isAltNumUnique()) {
            throw new Exception("Номер документа не уникален!");
        }
        $data = $db->selectRow('doc_list', $this->id);
        if (!$data) {
            throw new Exception('Ошибка выборки данных документа при проведении!');
        }
        if ($data['ok']) {
            throw new Exception('Документ уже проведён!');
        }
        $db->update('doc_list', $this->id, 'ok', time());
    }

    /// Отменить проведение документа
    function docCancel() {
        global $db;
        $data = $db->selectRow('doc_list', $this->id);
        if (!$data) {
            throw new Exception('Ошибка выборки данных документа!');
        }
        if (!$data['ok']) {
            throw new Exception('Документ не проведён!');
        }
        $db->update('doc_list', $this->id, 'ok', 0);
        // Транзиты
        $res = $db->query("SELECT `id`, `ok` FROM `doc_list` WHERE `ok`>0 AND `type`=1 AND `p_doc`={$this->id}");
        if (!$res->num_rows) {
            $res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`
            FROM `doc_list_pos`
            LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
            WHERE `doc_list_pos`.`doc`='{$this->id}'");
            $vals = '';
            while ($nxt = $res->fetch_row()) {
                if ($vals) {
                    $vals .= ',';
                }
                $vals .= "('$nxt[0]', '$nxt[1]')";
            }
            if ($vals) {
                $db->query("INSERT INTO `doc_base_dop` (`id`, `transit`) VALUES $vals
                   ON DUPLICATE KEY UPDATE `transit`=`transit`-VALUES(`transit`)");
            }
        }
    }

    // Формирование другого документа на основании текущего
    function MorphTo($target_type) {
        global $tmpl, $db;
        if ($target_type == '') {
            $tmpl->ajax = 1;
            $tmpl->addContent("<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->id}&amp;tt=1'\">Поступление</div>");
        } else if ($target_type == 1) {
            if (!isAccess('doc_postuplenie', 'create')) {
                throw new AccessException("");
            }
            $db->startTransaction();
            $new_doc = new doc_Postuplenie();
            $dd = $new_doc->createFromP($this);
            $new_doc->setDopData('cena', $this->dop_data['cena']);
            $db->commit();
            $ref = "Location: doc.php?mode=body&doc=$dd";
            header($ref);
        } else {
            $tmpl->msg("В разработке", "info");
        }
    }

}
