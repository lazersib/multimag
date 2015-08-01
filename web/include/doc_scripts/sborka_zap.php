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

include_once('include/doc.zapposeditor.php');

/// Сценарий автоматизации: сборка с перемещением и начислением заработной платы
class ds_sborka_zap {
    
    protected function processForm() {
        global $tmpl, $db;
        $tmpl->addContent("<h1>" . $this->getname() . "</h1>
            <script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
            <form action='' method='post' enctype='multipart/form-data'>
            <input type='hidden' name='mode' value='create'>
            <input type='hidden' name='param' value='i'>
            <input type='hidden' name='sn' value='sborka_zap'>
            Склад:<br><select name='store_id'>");
        $sres = $db->query("SELECT `id`,`name` FROM `doc_sklady` ORDER BY `id`");
        while ($nxt = $sres->fetch_row()) {
            $tmpl->addContent("<option value='$nxt[0]'>" . html_out($nxt[1]) . "</option>");
        }
        $tmpl->addContent("</select><br>Организация:<br><select name='firm_id'>");
        $rs = $db->query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `firm_name`");
        while ($nx = $rs->fetch_row()) {
            $tmpl->addContent("<option value='$nx[0]'>" . html_out($nx[1]) . "</option>");
        }
        $tmpl->addContent("</select><br>
            Агент:<br>
            <input type='hidden' name='agent_id' id='agent_id' value=''>
            <input type='text' id='agent_nm'  style='width: 450px;' value=''><br>
            Услуга начисления зарплаты:<br>
            <select name='service_id'>");
            $res=$db->query("SELECT `id`,`name` FROM `doc_base` WHERE `pos_type`=1 ORDER BY `name`");
            while($nxt=$res->fetch_row()) {
                    $tmpl->addContent("<option value='$nxt[0]'>".html_out($nxt[1])."</option>");
            }
            $tmpl->addContent("</select>
            Переместить готовый товар на склад:<br>
            <select name='to_store_id'>
            <option value='0' selected>--не требуется--</option>");
        $res = $db->query("SELECT `id`,`name` FROM `doc_sklady` ORDER BY `id`");
        while ($nxt = $res->fetch_row()) {
            $tmpl->addContent("<option value='$nxt[0]'>" . html_out($nxt[1]) . "</option>");
        }
        $tmpl->addContent("</select><br>
            Кладовщик, принимающий готовый товар на складе:<br><select name='storekeeper_id'>");
        $res = $db->query("SELECT `user_id`, `worker_real_name` FROM `users_worker_info` WHERE `worker`='1' ORDER BY `worker_real_name`");
        while ($nxt = $res->fetch_row()) {
            $tmpl->addContent("<option value='$nxt[0]'>" . html_out($nxt[1]) . "</option>");
        }
        $tmpl->addContent("</select><br>
            <label><input type='checkbox' name='not_a_p' value='1'>Не проводить перемещение</label><br>

            <script type=\"text/javascript\">
            $(document).ready(function(){
                $(\"#agent_nm\").autocomplete(\"/docs.php\", {delay:300,minChars:1,matchSubset:1,autoFill:false,selectFirst:true,matchContains:1,cacheLength:10,
                    maxItemsToShow:15,formatItem:agliFormat,onItemSelect:agselectItem,extraParams:{'l':'agent','mode':'srv','opt':'ac'}});
                $(\"#tov\").autocomplete(\"/docs.php\", {delay:300,minChars:1,matchSubset:1,autoFill:false,selectFirst:true,matchContains:1,cacheLength:10,
                    maxItemsToShow:15,formatItem:tovliFormat,onItemSelect:tovselectItem,extraParams:{'l':'sklad','mode':'srv','opt':'ac'}});
            });
            function agliFormat (row, i, num) { var result = row[0] + \"<em class='qnt'>тел. \" + row[2] + \"</em> \";return result;}
            function agselectItem(li) {if( li == null ) var sValue = \"Ничего не выбрано!\";if( !!li.extra ) var sValue = li.extra[0];else var sValue = li.selectValue;document.getElementById('agent_id').value=sValue;}
            function tovliFormat (row, i, num) {var result = row[0] + \"<em class='qnt'>\" + row[2] + \"</em> \";return result;}
            function tovselectItem(li) {if( li == null ) var sValue = \"Ничего не выбрано!\";if( !!li.extra ) var sValue = li.extra[0];else var sValue = li.selectValue;document.getElementById('tov_id').value=sValue;}
            </script>
            <button type='submit'>Выполнить</button>
            </form>
            ");
    }
    
    protected function processCreate() {
        $agent_id = rcvint('agent_id');
        $store_id = rcvint('store_id');
        $to_store_id = rcvint('to_store_id');
        $firm_id = rcvint('firm_id');
        $service_id = rcvint('service_id');
        $not_a_p = rcvint('not_a_p');
        $storekeeper_id = rcvint('storekeeper_id');

        $doc_data = array(
            'firm_id' => $firm_id,
            'subtype' => '',
            'sklad' => $store_id,
            'agent' => $agent_id
        );
        $dop_data = array(
            'cena' => 1,
            'script' => 'sborka_zap',
            'nasklad' => $to_store_id,
            'service_id' => $service_id,
            'not_a_p' => $not_a_p,
            'storekeeper_id' => $storekeeper_id,
        );        
        $doc_obj = new doc_Sborka();
        $doc_id = $doc_obj->create($doc_data);
        $doc_obj->setDopDataA($dop_data); 
        redirect("/doc_sc.php?mode=edit&sn=sborka_zap&doc=$doc_id");        
    }
    
    protected function processReopen() {
        $doc_id = rcvint('doc');
        $doc_obj = \document::getInstanceFromDb($doc_id);
        $doc_data = $doc_obj->getDocDataA();
        $dop_data = $doc_obj->getDopDataA();
        if ($doc_data['ok']) {
            throw new Exception("Операция не допускается для проведённого документа");
        }
        if (!isset($dop_data['script'])) {
            throw new \Exception("Этот документ создан вручную, а не через сценарий. Недостаточно информации для редактирования документа через сценарий.");
        } elseif($dop_data['script'] !=  'sborka_zap') {
             throw new \Exception("Этот документ создан через другой сценарий. Редактирование через этот сценарий невозможно.");
        }
        $doc_obj->setDopData('cena', 1);
        redirect("/doc_sc.php?mode=edit&sn=sborka_zap&doc=$doc_id");
    }
    
    protected function processEdit() {
        global $tmpl;        
        $doc_id = rcvint('doc');
        $doc_obj = \document::getInstanceFromDb($doc_id);
        $doc_data = $doc_obj->getDocDataA();
        $dop_data = $doc_obj->getDopDataA();        
        
        $zp = $this->CalcZP($doc_id);
        $tmpl->addContent("<h1>" . $this->getname() . "</h1>
Необходимо выбрать товары, которые будут скомплектованы. Устанавливать цену не требуется - при проведении документа она будет выставлена автоматически исходя из стоимости затраченных ресурсов. Для того, чтобы узнать цены - обновите страницу. После выполнения сценария выбранные товары будут оприходованы на склад, а соответствующее им количество ресурсов, использованных для сборки, будет списано. Попытка провести через этот сценарий товары, не содержащие ресурсов, вызовет ошибку. Если это указано в свойствах товара, от агента-сборщика будет оприходована выбранная услуга для последующей выдачи заработной платы (на данный момент в размере $zp руб.).<br>
<a href='/doc_sc.php?mode=exec&amp;sn=sborka_zap&amp;doc=$doc_id'>Выполнить необходимые действия</a>
<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>");

        $this->ReCalcPosPrices($doc_obj);
        $poseditor = new SZapPosEditor($doc_obj);        
        $poseditor->cost_id = $dop_data['cena'];
        $poseditor->SetEditable($doc_data['ok'] ? 0 : 1);
        $poseditor->sklad_id = $doc_data['sklad'];
        $poseditor->updateDocSum();
        $tmpl->addContent($poseditor->Show());
    }
    
    protected function processExec() {
        global $db, $tmpl;
        $doc_id = rcvint('doc');

        $db->startTransaction();
        $document = \document::getInstanceFromDb($doc_id);
        $this->ReCalcPosPrices($document);
        $poseditor = new SZapPosEditor($document);
        $poseditor->updateDocSum();
        $document->DocApply();
        $zp = $this->CalcZP($doc_id);
        $store_id = $document->getDocData('sklad');
        $to_store_id = $document->getDopData('nasklad');
        $storekeeper_id = $document->getDopData('storekeeper_id');
        $not_a_p = $document->getDopData('not_a_p');
        $service_id = $document->getDopData('service_id');
        if(!$store_id) {
            throw new Exception('Склад сборки не задан');
        }
        if(!$service_id) {
            throw new Exception('Услуга работы не задана');
        }
        if(!$not_a_p && !$to_store_id) {
            throw new Exception('Склад назначения не задан');
        }
        
        
        // Проверка, создано ли уже поступление зарплаты
        $res = $db->query("SELECT `id` FROM `doc_list` WHERE `type`='1' AND `p_doc`='$doc_id'");
        if ($res->num_rows) {
            list($post_doc) = $res->fetch_row();
            $db->query("UPDATE `doc_list_pos` SET `cost`='$zp' WHERE `doc`='$post_doc'");
            $db->query("UPDATE `doc_list` SET `sum`='$zp' WHERE `id`='$post_doc'");
        } else {    
            $p_obj = new doc_Postuplenie();
            $p_doc_id = $p_obj->createFrom($document);   
            $db->query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `cost`) VALUES ('$p_doc_id', '$service_id', '1', '$zp')");
            $p_obj->setDocData('sum', $zp);
            $p_obj->DocApply();
        }

        // Проверка, создано ли уже перемещение
        $res = $db->query("SELECT `id` FROM `doc_list` WHERE `type`='8' AND `p_doc`='$doc_id'");
        if ($res->num_rows) {
            list($perem_doc_num) = $res->fetch_row();
            $r = $db->query("SELECT `value` FROM `doc_dopdata` WHERE `doc`='$perem_doc_num' AND `param`='na_sklad'");
            list($to_store_id) = $r->fetch_row();
            $perem_doc = new doc_Peremeshenie($perem_doc_num);
        } else if (($store_id != $to_store_id) && $to_store_id) {
            $perem_doc = new doc_Peremeshenie();
            $perem_doc->createFrom($document);
            $perem_doc->setDopData('kladovshik', $storekeeper_id);
            $perem_doc->setDopData('na_sklad', $to_store_id);
            $perem_doc->setDopData('mest', 1);
        }

        if (($store_id != $to_store_id) && $to_store_id) {
            $docnum = $perem_doc->getId();
            $res = $db->query("SELECT `tovar`, `cnt`, `cost` FROM `doc_list_pos` WHERE `doc`='$doc_id' AND `page`='0'");
            while ($nxt = $res->fetch_row()) {
                $db->query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `cost`, `page`)
                        VALUES ('$docnum', '$nxt[0]', '$nxt[1]', '$nxt[2]', '0')");
            }

            if (!$not_a_p) {
                $perem_doc->DocApply();
            }
        }
        $db->commit();
        $tmpl->ajax = 0;
        $tmpl->msg("Все операции выполнены успешно. Размер зарплаты: $zp");
    }
    
    protected function processSrv() {
        global $tmpl;
        $peopt = request('peopt');
        $doc = rcvint('doc');
        $document = new doc_Sborka($doc);
        $poseditor = new SZapPosEditor($document);
        $dd = $document->getDopDataA();
        $poseditor->cost_id = $dd['cena'];
        $dd = $document->getDocDataA();
        $poseditor->sklad_id = $dd['sklad'];
        $tmpl->ajax = 1;
        $tmpl->setContent('');

        // Json-вариант списка товаров
        if ($peopt == 'jget') {
            $doc_sum = $document->recalcSum();
            $str = "{ response: 'loadlist', content: [" . $poseditor->GetAllContent() . "], sum: '$doc_sum' }";
            $tmpl->addContent($str);
        }
        // Получение данных наименования
        else if ($peopt == 'jgpi') {
            $pos = rcvint('pos');
            $tmpl->addContent($poseditor->GetPosInfo($pos));
        } else if ($peopt == 'jgetgroups') {
            $doc_content = $poseditor->getGroupList();
            $tmpl->addContent($doc_content);
        }
        // Json вариант добавления позиции
        else if ($peopt == 'jadd') {
            if (!isAccess('doc_sborka', 'edit'))
                throw new AccessException("Недостаточно привилегий");
            $pe_pos = rcvint('pe_pos');
            $tmpl->setContent($poseditor->AddPos($pe_pos));
        }
        // Json вариант удаления строки
        else if ($peopt == 'jdel') {
            if (!isAccess('doc_sborka', 'edit')) {
                throw new AccessException("Недостаточно привилегий");
            }
            $line_id = rcvint('line_id');
            $tmpl->setContent($poseditor->Removeline($line_id));
        }
        // Json вариант обновления
        else if ($peopt == 'jup') {
            if (!isAccess('doc_sborka', 'edit')) {
                throw new AccessException("Недостаточно привилегий");
            }
            $line_id = rcvint('line_id');
            $value = request('value');
            $type = request('type');
            $tmpl->setContent($poseditor->UpdateLine($line_id, $type, $value));
        }
        // Получение номенклатуры выбранной группы
        else if ($peopt == 'jsklad') {
            $group_id = rcvint('group_id');
            $str = "{ response: 'sklad_list', group: '$group_id',  content: [" . $poseditor->GetSkladList($group_id) . "] }";
            $tmpl->setContent($str);
        }
        // Поиск по подстроке по складу
        else if ($peopt == 'jsklads') {
            $s = request('s');
            $str = "{ response: 'sklad_list', content: " . $poseditor->SearchSkladList($s) . " }";
            $tmpl->setContent($str);
        } else {
            throw new NotFoundException();
        }
    }
        
    function Run($mode) {
        global $tmpl;
        $tmpl->hideBlock('left');
        switch ($mode) {
            case 'view':
                $this->processForm();
                break;
            case 'create':
                $this->processCreate();
                break;
            case 'reopen':
                $this->processReopen();
                break;
            case 'edit':
                $this->processEdit();
                break;
            case 'exec':
                $this->processExec();
                break;
            case 'srv':
                $this->processSrv();
                break;
            default :
                throw new \NotFoundException();
        }
    }

    function ReCalcPosPrices($doc_obj) {
        global $db;
        $doc_id = $doc_obj->getId();
        $service_id = $doc_obj->getDopData('service_id');
        $db->query("DELETE FROM `doc_list_pos`	WHERE `doc`='$doc_id' AND `page`!='0'");
        $res = $db->query("SELECT `id`, `tovar`, `cnt` FROM `doc_list_pos`
            WHERE `doc`='$doc_id' AND `page`='0'");
        while ($nxt = $res->fetch_row()) {
            $cost = 0;
            $rs = $db->query("SELECT `doc_base_kompl`.`kompl_id`, `doc_base_kompl`.`cnt`, `doc_base`.`cost` FROM `doc_base_kompl`
                LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_base_kompl`.`kompl_id`
                WHERE `doc_base_kompl`.`pos_id`='$nxt[1]'");
            if ($rs->num_rows == 0) {
                throw new \Exception("У товара $nxt[1] не заданы комплектующие");
            }
            while ($nx = $rs->fetch_row()) {
                $acp = getInCost($nx[0], 0, true);
                if ($acp > 0) {
                    $cost+=$nx[1] * $acp;
                } else {
                    $cost+=$nx[1] * $nx[2];
                }
                $cntc = $nxt[2] * $nx[1];
                if ($acp > 0) {
                    $db->query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `cost`, `page`)
                        VALUES ('$doc_id', '$nx[0]', '$cntc', '$acp', '$nxt[1]')");
                } else {
                    $db->query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `cost`, `page`)
                        VALUES ('$doc_id', '$nx[0]', '$cntc', '$nx[2]', '$nxt[1]')");
                }
            }

            // Расчитываем зарплату
            $r = $db->query("SELECT `doc_base_params`.`id`, `doc_base_values`.`value` FROM `doc_base_params`
		LEFT JOIN `doc_base_values` ON `doc_base_values`.`param_id`=`doc_base_params`.`id` AND `doc_base_values`.`id`='$nxt[1]'
		WHERE `doc_base_params`.`codename`='ZP'");
            if ($r->num_rows) {
                list($a, $zp) = $r->fetch_row();
                $db->query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `cost`, `page`)
                    VALUES ('$doc_id', '$service_id', '$nxt[2]', '$zp', '$nxt[1]')");
                $cost+=$zp;
            } else {
                $zp = 0;
            }
            $db->query("UPDATE `doc_list_pos` SET `cost`='$cost' WHERE `id`='$nxt[0]'");
        }
    }

    function CalcZP($doc) {
        global $db;
        $zp = 0;
        $res = $db->query("SELECT `id`, `tovar`, `cnt` FROM `doc_list_pos` WHERE `doc`='$doc' AND `page`='0'");
        while ($nxt = $res->fetch_row()) {
            $rs = $db->query("SELECT `doc_base_values`.`value` FROM `doc_base_params`
                LEFT JOIN `doc_base_values` ON `doc_base_values`.`param_id`=`doc_base_params`.`id` AND `doc_base_values`.`id`='$nxt[1]'
                WHERE `doc_base_params`.`codename`='ZP'");
            if (!$rs->num_rows) {
                continue;
            }
            $n = $rs->fetch_row();
            $zp+=$nxt[2] * $n[0];
        }
        return $zp;
    }

    function getName() {
        return "Сборка с выдачей заработной платы";
    }

}
