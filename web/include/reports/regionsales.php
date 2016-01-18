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
//
/// Отчёт по региональным продажам
class Report_regionsales extends BaseGSReport {

    var $group_info = array();
    
    /// Получить название отчёта
    function getName($short = 0) {
        if ($short) {
            return "По региональным продажам";
        } else {
            return "Отчёт по региональным продажам";
        }
    }
    
    protected function addPeriodWidget($name, $data) {
        $this->form_data .= "Дата от:<br>
        <input type='text' name='{$name}[from]' id='date' value='{$data['value']}'><br>                
        Дата до:<br>
        <input type='text' name='{$name}[to]' id='date' value='{$data['value2']}'><br>";
        $this->jsafter .= "initCalendar('{$name}[from]',false);initCalendar('{$name}[to]',false);\n";
    }
    
    protected function addSelectWidget($name, $data) {
        $this->form_data .= html_out($data['name']).":<br><select name='".html_out($name)."'>";
        if(isset($data['not_select'])) {
            if($data['not_select']) {
                 $this->form_data .= "<option value=''>--не выбрано--</option>";
            }
        }
        $s_data = array();
        if(isset($data['data_ldo'])) {
            $classname = '\\Models\\LDO\\'.$data['data_ldo'];
            $ldo = new $classname;
            $s_data = $ldo->getData();
        } elseif(isset($data['data'])) {
            $s_data = $data['data'];
        }
        foreach($s_data as $id=>$value) {
            $this->form_data .= "<option value=".html_out($id).">".html_out($value)."</option>";
        }
        $this->form_data .= "</select><br>";
    }
        
    protected function makeFormHTML($mode, $formstruct) {
        $this->form_data = $this->jsinclude = $this->jsafter = '';
        $this->form .= "<form action='' method='post'>
        <input type='hidden' name='mode' value='agent'>
        <input type='hidden' name='opt' value='make'>";
        foreach ($formstruct as $el_name => $el_info) {
            switch ($el_info['type']) {
                case 'period':
                    $this->addPeriodWidget($el_name, $el_info);
                    break;
                case 'select':
                    $this->addSelectWidget($el_name, $el_info);
                    break;
            }
        }
    }
    
    function getFormStruct() {
        return [
            'period' => [
                'type' => 'period',
                'name' => 'Дата',
                'value' => date("Y-m-01"),
                'value2' => date("Y-m-d"),
            ],
            'i[region]' => [
                'type' => 'select',
                'name' => 'Регион',
                'not_select' => true,
                'data_ldo' => 'regionnames',
            ],
            'i[responsible]' => [
                'type' => 'select',
                'name' => 'Ответственный',
                'not_select' => true,
                'data_ldo' => 'workernames',
            ],
            'agent' => [
                'type' => 'autocomplete',
                'name' => 'Агент',
                'not_select' => true,
                'data_ldo' => 'agentnames',
            ],
        ];        
    }

    /// Форма для формирования отчёта
    function Form() {
        global $tmpl;
        $struct = $this->getFormStruct();
        $this->form_data = $this->jsinclude = $this->jsafter = '';
        $this->addSelectWidget('i[region]', $struct['i[region]']);
        $this->addSelectWidget('i[responsible]', $struct['i[responsible]']);
        $startdate = date("Y-m-01");
        $curdate = date("Y-m-d");
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
            <script src='/css/jquery/jquery.js' type='text/javascript'></script>
            <script src='/css/jquery/jquery.alerts.js' type='text/javascript'></script>
            <link href='/css/jquery/jquery.alerts.css' rel='stylesheet' type='text/css' media='screen'>
            <script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
            <form action='' method='post'>
            <input type='hidden' name='mode' value='regionsales'>
            <input type='hidden' name='opt' value='make'>
            <fieldset><legend>Период:</legend>
            От: <input type='text' name='i[date][from]' id='date[from]' value='$startdate'>&nbsp;
            До: <input type='text' name='i[date][to]' id='date[to]' value='$curdate'>
            </fieldset>
            {$this->form_data}    
            Агент-партнёр:<br>
            <input type='hidden' name='i[agent]' id='agent_id' value=''>
            <input type='text' id='ag' name='agent_name' style='width: 400px;' value=''><br>
            <fieldset><legend>Товары</legend>");
            $this->GroupSelBlock();
            $tmpl->addContent("</fieldset>
            <fieldset><legend>Разделить по</legend>
            <label><input type='checkbox' name='i[div][region]' value=1>Регионам</label><br>
            <label><input type='checkbox' name='i[div][agent]' value=1>Агентам</label><br>
            <label><input type='checkbox' name='i[div][responsible]' value=1>Ответственным</label><br>
            </fieldset>
            <fieldset><legend>Детализация</legend>
            <label><input type='radio' name='i[detail]' value='none' checked>Не требуется</label><br>
            <label><input type='radio' name='i[detail]' value='goods'>Номенклатура</label><br>
            <label><input type='radio' name='i[detail]' value='groups'>Группы номенклатуры</label><br>
            <label><input type='radio' name='i[detail]' value='up_groups'>Группы верхнего уровня</label><br>
            </fieldset>
            <fieldset><legend>Разное</legend>
            <label><input type='checkbox' name='i[show_profit]' value='1'>Показать прибыль</label><br>
            </fieldset>
            
            <fieldset><legend>Формат</legend>
            <label><input type='radio' name='opt' value='pdf' checked>PDF</label>&nbsp;<label><input type='radio' name='opt' value='html'>HTML</label>
            </fieldset><br>
            <button type='submit'>Создать отчет</button></form>
            <script type='text/javascript'>
		
		$(document).ready(function(){
			$(\"#ag\").autocomplete(\"/docs.php\", {
			delay:300,
			minChars:1,
			matchSubset:1,
			autoFill:false,
			selectFirst:true,
			matchContains:1,
			cacheLength:10,
			maxItemsToShow:15, 	 
			formatItem:agliFormat,
			onItemSelect:agselectItem,
			extraParams:{'l':'agent','mode':'srv','opt':'ac'}
			});

		});
		function agliFormat (row, i, num) {
			var result = row[0] + \"<em class='qnt'>тел. \" +
			row[2] + \"</em> \";
			return result;
		}
		function agselectItem(li) {
			if( li == null ) var sValue = \"Ничего не выбрано!\";
			if( !!li.extra ) var sValue = li.extra[0];
			else var sValue = li.selectValue;
			document.getElementById('agent_id').value=sValue;
		}
		initCalendar('date[from]',false);initCalendar('date[to]',false);\n
		</script>");
    }
    
    public function getData($input_data) {
        global $db;
        $agents_data = array();
        $date_from = strtotime($input_data['date']['from']);
        $date_to = strtotime($input_data['date']['to']." 23:59:59");
        $sql_fields = "`doc_list`.`id`, `doc_list`.`type`, `doc_list`.`agent` AS `agent_id`, `doc_agent`.`region`, `doc_agent`.`responsible`, `doc_dopdata`.`value` AS `return_flag`";
        $sql_where = "`doc_list`.`date`>='$date_from' AND `doc_list`.`date`<='$date_to' AND `doc_list`.`type`>=1 AND `doc_list`.`type`<=3";
        $sql_join = " LEFT JOIN `doc_agent` ON `doc_list`.`agent` = `doc_agent`.`id`"
                . " LEFT JOIN `doc_dopdata` ON `doc_list`.`id`=`doc_dopdata`.`doc` AND `doc_dopdata`.`param`='return' ";
        if($input_data['agent']) {
            $sql_where .= " AND `doc_list`.`agent`=".intval($input_data['agent']);
        }
        if($input_data['responsible']) {
            $sql_where .= " AND `doc_agent`.`responsible`=".intval($input_data['responsible']);
        }
        if($input_data['region']) {
            $sql_where .= " AND `doc_agent`.`region`=".intval($input_data['region']);
        }
        $res = $db->query("SELECT $sql_fields FROM `doc_list` "
            . $sql_join
            . " WHERE "
            . $sql_where);
        while($doc_info = $res->fetch_assoc()) {
            $return_flag = $sale_flag = $order_flag = 0;
            if($doc_info['type']==1 && $doc_info['return_flag']) {
                $return_flag = 1;
            } elseif($doc_info['type']==2 && $doc_info['return_flag']==0) {
                $sale_flag = 1;
            } elseif($doc_info['type']==3) {
                $order_flag = 1;
            }
            if(!isset($agents_data[$doc_info['agent_id']])) {
                $agents_data[$doc_info['agent_id']] = array();
            }
            $cur_poslist = &$agents_data[$doc_info['agent_id']];
            $pos_res = $db->query("SELECT `doc_list_pos`.`tovar` AS `pos_id`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost` AS `price`, `doc_base`.`mass`, `doc_base`.`group` AS `group_id`"
                    . " FROM `doc_list_pos`"
                    . " INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`"
                    . " WHERE `doc_list_pos`.`doc`='{$doc_info['id']}'");
            while ($pos_info = $pos_res->fetch_assoc()) {
                if(!isset($cur_poslist[$pos_info['pos_id']])) {
                    $cur_poslist[$pos_info['pos_id']] = 
                            array(
                                'sale_cnt' => $pos_info['cnt']*$sale_flag,
                                'ret_cnt' => $pos_info['cnt']*$return_flag,
                                'order_cnt' => $pos_info['cnt']*$order_flag,
                                'mass' => $pos_info['mass'],
                                'profit' => 0
                            );
                    if($input_data['show_profit']) {
                        if($sale_flag) {
                            $cur_poslist[$pos_info['pos_id']]['profit'] = ($pos_info['price']-getInCost($pos_info['pos_id'], $doc_info['date']))*$pos_info['cnt'];
                        } elseif($return_flag) {
                            $cur_poslist[$pos_info['pos_id']]['profit'] = ($pos_info['price']-getInCost($pos_info['pos_id'], $doc_info['date']))*$pos_info['cnt']*(-1);
                        }
                    }
                } else {
                    $cur_poslist[$pos_info['pos_id']]['sale_cnt'] += $pos_info['cnt']*$sale_flag;
                    $cur_poslist[$pos_info['pos_id']]['ret_cnt'] += $pos_info['cnt']*$return_flag;
                    $cur_poslist[$pos_info['pos_id']]['order_cnt'] += $pos_info['cnt']*$order_flag;
                    if($input_data['show_profit']) {
                        if($sale_flag) {
                            $cur_poslist[$pos_info['pos_id']]['profit'] += ($pos_info['price']-getInCost($pos_info['pos_id'], $doc_info['date']))*$pos_info['cnt'];
                        } elseif($return_flag) {
                            $cur_poslist[$pos_info['pos_id']]['profit'] += ($pos_info['price']-getInCost($pos_info['pos_id'], $doc_info['date']))*$pos_info['cnt']*(-1);
                        }
                    }
                }
            }
        }
        $out_data = array();
        if($input_data['div']['region']) {
            return $this->regionGroupData($input_data, $agents_data);
        } elseif($input_data['div']['responsible']) {
            return $this->responsibleGroupData($input_data, $agents_data);
        } elseif($input_data['div']['agent']) {
            return $this->agentGroupData($input_data, $agents_data);
        } else {
            return $this->allGroupData($input_data, $agents_data);
        }
        var_dump($agents_data);
        exit();
    }

    protected function allGroupData($input_data, $agents_data) {
        
    }
    
    
            
    function Make($engine) {
        global $db;
        $input_data = $_REQUEST['i'];
        if(!isset($input_data['show_profit'])) {
            $input_data['show_profit'] = false;
        }
        $data = $this->getData($input_data);
        
        $agent = rcvint('agent');
        $this->loadEngine($engine);

        $res = $db->query("SELECT `name` FROM `doc_agent` WHERE `id`='$agent'");
        if (!$res->num_rows)
            throw new Exception("Агент не найден");
        list($ag_name) = $res->fetch_row();

        $this->header($this->getName() . " $ag_name");
        $widths = array(55, 15, 15, 15);
        $headers = array('Документ', 'Приход', 'Расход', 'Остаток');
        $this->tableBegin($widths);
        $this->tableHeader($headers);

        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`sum`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`,
            `doc_types`.`name` AS `doc_type`
            FROM `doc_list`
            LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
            WHERE `doc_list`.`ok`>'0' AND `doc_list`.`mark_del`='0' AND `doc_list`.`agent`='$agent'
            ORDER BY `doc_list`.`date`");
        $sum = 0;
        while ($nxt = $res->fetch_row()) {
            $prix = $rasx = $tovar = 0;
            switch ($nxt[1]) {
                case 1: $prix = $nxt[2];
                    $tovar = 1;
                    break;
                case 2: $rasx = $nxt[2];
                    $tovar = 1;
                    break;
                case 4: $prix = $nxt[2];
                    break;
                case 5: $rasx = $nxt[2];
                    break;
                case 6: $prix = $nxt[2];
                    break;
                case 7: $rasx = $nxt[2];
                    break;
                case 18: {
                        if ($nxt[2] > 0)
                            $rasx = $nxt[2];
                        else
                            $prix = abs($nxt[2]);
                    }
                    break;
            }
            $sum = round($sum + $prix - $rasx);
            $sum_p = $prix_p = $rasx_p = '';
            if ($sum)
                $sum_p = sprintf("%0.2f", $sum);
            if ($prix)
                $prix_p = sprintf("%0.2f", $prix);
            if ($rasx)
                $rasx_p = sprintf("%0.2f", $rasx);
            $dt = date("d.m.Y H:i:s", $nxt[5]);

            $tovar_str = '';
            if ($tovar) {

                $rs = $db->query("SELECT `doc_base`.`name`, `doc_base`.`proizv`,  `doc_list_pos`.`cnt`
				FROM `doc_list_pos`
				LEFT JOIN `doc_base` ON `doc_base`.`id`= `doc_list_pos`.`tovar`
				WHERE `doc_list_pos`.`doc`='$nxt[0]'");
                while ($nx = $rs->fetch_row()) {
                    if (!$tovar_str)
                        $tovar_str = "$nx[0]/$nx[1]:$nx[2]";
                    else
                        $tovar_str.=", $nx[0]/$nx[1]:$nx[2]";
                }
                $tovar_str = "\n Товары: $tovar_str";
            }

            $this->tableRow(array("$nxt[6] N{$nxt[3]}{$nxt[4]} ($nxt[0])\n от $dt $tovar_str", $prix_p, $rasx_p, $sum_p));
        }
        $this->tableEnd();
        $this->output();
        exit(0);
    }

}
