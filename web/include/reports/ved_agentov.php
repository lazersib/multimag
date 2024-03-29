<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2018, BlackLight, TND Team, http://tndproject.org
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
/// Отчёт *Ведомость по агентам*
class Report_Ved_Agentov {

    var $d_sum = 0;
    var $c_sum = 0;

    /// Получить имя отчёта
    public function getName($short = 0) {
        if ($short) {
            return "Ведомость по агентам";
        } else {
            return "Ведомость по агентам";
        }
    }

    /// Запустить отчёт
    public function Run($opt) {
        if ($opt == '') {
            $this->Form();
        } else {
            $this->Make();
        }
    }

    /// Отобразить форму
    protected function Form() {
        global $tmpl, $db;
        $dt_f = date("Y-m-01");
        $dt_t = date("Y-m-d");
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
            <script src='/js/calendar.js'></script>
            <form action='' method='post'>
            <input type='hidden' name='mode' value='ved_agentov'>
            <input type='hidden' name='opt' value='make'>
            Дата c:<input type='text' name='dt_f' id='dt_f' value='$dt_f'>
            по:<input type='text' name='dt_t' id='dt_t' value='$dt_t'>
            </p>
            Организация:<br><select name='firm_id'>"
        . "<option value='0'>--любая--</option>");
        $rs = $db->query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `firm_name`");
        while ($nx = $rs->fetch_row()) {
            if(\acl::testAccess([ 'firm.global', 'firm.'.$nx[0]], \acl::VIEW)) {
                $tmpl->addContent("<option value='$nx[0]'>" . html_out($nx[1]) . "</option>");
            }
        }

        $tmpl->addContent("</select><br>
		<fieldset><legend>Отчёт по</legend>
		<select name='sel_type' id='sel_type'>
		<option value='all'>Всем агентам</option>
		<option value='group'>Выбранной группе</option>
		<option value='pos'>Выбранному агенту</option>
		</select>
		<div id='sb' style='display: none;'>
		" . selectAgentGroup('ag_sel_group', 0, 0) . "
		</div>
		<div id='ag_sel' style='display: none;'>
		<input type='hidden' name='agent' id='agent_id' value=''>
		<input type='text' id='agent_nm'  style='width: 450px;' value=''><br>
		</div>
		</fieldset>
		<fieldset><legend>Показать задолженность</legend>
		<label><input type='radio' name='debt' value='0' checked>Любую</label><br>
		<label><input type='radio' name='debt' value='1'>Только агента</label><br>
		<label><input type='radio' name='debt' value='-1'>Только нашу</label>
		</fieldset>
		<fieldset><legend>Детализация</legend>
		<label><input type='checkbox' name='detail_ag' value='1' checked>Агенты</label><br>
		<label><input type='checkbox' name='detail_doc' value='1' checked>Документы движения</label>
		</fieldset>

		<button type='submit'>Создать отчет</button></form>
		<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<script type='text/javascript'>

		function forminit()
		{
			initCalendar('dt_f',false);
			initCalendar('dt_t',false);
			$(\"#agent_nm\").autocomplete(\"/docs.php\", {
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
		}

		function selectChange(event)
		{
			if(this.value=='group')
				document.getElementById('sb').style.display='block';
			else	document.getElementById('sb').style.display='none';
			if(this.value=='pos')
				document.getElementById('ag_sel').style.display='block';
			else	document.getElementById('ag_sel').style.display='none';
		}


		addEventListener('load',forminit,false)
		document.getElementById('sel_type').addEventListener('change',selectChange,false)


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
		</script>");
    }

    /// Сформировать отчёт
    protected function Make() {
        global $tmpl, $db;
        $this->dt_f = strtotime(@$_REQUEST['dt_f']);
        $this->dt_t = strtotime(@$_REQUEST['dt_t'] . " 23:59:59");
        $this->sel_type = @$_REQUEST['sel_type'];
        $agent = intval(@$_REQUEST['agent']);
        $ag_sel_group = intval(@$_REQUEST['ag_sel_group']);
        $this->debt = intval(@$_REQUEST['debt']);
        $detail_ag = intval(@$_REQUEST['detail_ag']);
        $this->detail_doc = intval(@$_REQUEST['detail_doc']);
        $firm_id = rcvint('firm_id');
        $tmpl->addContent("<h1 id='page-title'>Ведомость по агентам</h1>
		<table class='list' width='100%'>
		<tr><th rowspan='2'>Дата</th><th rowspan='2'>Документ</th><th rowspan='2'>Начальный долг</th><th rowspan='2'>Увеличение долга</th><th rowspan='2'>Уменьшение долга</th><th colspan='2'>На конец периода</th></tr>
		<tr><th>Наш долг</th><th>Долг клиента</th></tr>");

        $aw = '';
        if ($firm_id) {
            $aw = " AND `doc_list`.`firm_id`=$firm_id";
        }
        $this->d_sum = $this->c_sum = 0;
        if ($this->sel_type == 'pos') {
            $res = $db->query("SELECT `id`, `name` FROM `doc_agent` WHERE `id`='$agent' ORDER BY `name`");
            while ($line = $res->fetch_assoc()) {
                $tmpl->addContent($this->makeBlock($line['name'], "AND `doc_list`.`agent`='{$line['id']}' $aw"));
            }
        } else if ($this->sel_type == 'group') {
            if ($detail_ag) {
                $res = $db->query("SELECT `id`, `name` FROM `doc_agent` WHERE `group`='$ag_sel_group' ORDER BY `name`");
                while ($line = $res->fetch_assoc()) {
                    $tmpl->addContent($this->makeBlock($line['name'], "AND `doc_list`.`agent`='{$line['id']}' $aw"));
                }
            } else {
                $res = $db->query("SELECT `id` FROM `doc_agent` WHERE `group`='$ag_sel_group' ORDER BY `name`");
                $where = '';
                while ($line = $res->fetch_assoc()) {
                    if ($where)
                        $where.=' OR ';
                    $where.="`doc_list`.`agent`='{$line['id']}'";
                }
                $tmpl->addContent($this->makeBlock('Выбранная группа:', "AND ($where) $aw"));
            }
        }
        else {
            if ($detail_ag) {
                $res = $db->query("SELECT `id`, `name` FROM `doc_agent` ORDER BY `name`");
                while ($line = $res->fetch_assoc()) {
                    $tmpl->addContent($this->makeBlock($line['name'], "AND `doc_list`.`agent`='{$line['id']}' $aw"));
                }
            } else
                $tmpl->addContent($this->makeBlock('Итог:'));
        }
        $d_sum_p = number_format($this->d_sum, 2, '.', '&nbsp;');
        $c_sum_p = number_format($this->c_sum, 2, '.', '&nbsp;');
        $tmpl->addContent("<tr style='background-color: #efc; font-weight: bold;'>"
            . "<td>&nbsp;</td><td>Всего</td><td>&nbsp;</td><td align='right'>$d_sum_p</td><td align='right'>$c_sum_p</td><td>&nbsp;</td><td>&nbsp;</td></tr>");
        $tmpl->addContent("</table>");
    }

    /// Сформировать блок с данными
    protected function makeBlock($head, $agent_where = '') {
        global $db;
        $res = $db->query("SELECT `doc_list`.`id` AS `doc_id`, `doc_list`.`altnum` ,`doc_list`.`subtype`, `doc_list`.`type` AS `doc_type`, `doc_list`.`sum` AS `doc_sum`, `doc_list`.`date`, `doc_types`.`name` AS `doc_typename`, `doc_agent`.`name` AS `agent_name`, `doc_list`.`firm_id`
		FROM `doc_list`
		LEFT JOIN `doc_types` ON `doc_list`.`type`=`doc_types`.`id`
		LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
		WHERE `ok`>'0' AND `mark_del`='0' AND `date`<='{$this->dt_t}' $agent_where
		ORDER BY `doc_list`.`date`");
        $dolg = $start_dolg = $df = 0;
        $table_data = $start_dolg_p = '';
        while ($line = $res->fetch_assoc()) {
            if(!\acl::testAccess([ 'firm.global', 'firm.'.$line['firm_id']], \acl::VIEW)) {
                continue;
            }
            $d_inc = $d_dec = $info = '';
            $cont = 0;
            if (!$df && $line['date'] > $this->dt_f) {
                $df = 1;
                if ($start_dolg = $dolg)
                    $start_dolg_p = sprintf("%0.2f", $dolg);
            }

            switch ($line['doc_type']) {
                case 1: $dolg-=$line['doc_sum'];
                    $d_dec = $line['doc_sum'];
                    break;
                case 2: $dolg+=$line['doc_sum'];
                    $d_inc = $line['doc_sum'];
                    break;
                case 4: $dolg-=$line['doc_sum'];
                    $d_dec = $line['doc_sum'];
                    break;
                case 5: $dolg+=$line['doc_sum'];
                    $d_inc = $line['doc_sum'];
                    break;
                case 6: $dolg-=$line['doc_sum'];
                    $d_dec = $line['doc_sum'];
                    break;
                case 7: $dolg+=$line['doc_sum'];
                    $d_inc = $line['doc_sum'];
                    break;
                case 18: $dolg+=$line['doc_sum'];
                    $d_inc = $line['doc_sum'];
                    break;
                default: $cont = 1;
            }
            if ($cont)
                continue;
            $this->d_sum += $d_inc;
            $this->c_sum += $d_dec;

            $dolg_p = number_format(abs($dolg), 2, '.', '&nbsp;');
            $date_p = date("Y-m-d", $line['date']);
            $end_nd = $end_cd = '';

            if ($dolg > 0)
                $end_cd = $dolg_p;
            else if ($dolg < 0)
                $end_nd = $dolg_p;
            if ($this->sel_type != 'pos' && $agent_where == '')
                $info = ' (' . html_out($line['agent_name']) . ')';
            if ($d_inc) {
                $d_inc = number_format($d_inc, 2, '.', '&nbsp;');
            }
            if ($d_dec) {
                $d_dec = number_format($d_dec, 2, '.', '&nbsp;');
            }
            if ($df && $this->detail_doc)
                $table_data.="<tr><td>$date_p</td><td><a href='/doc.php?mode=body&amp;doc={$line['doc_id']}'>{$line['doc_typename']} N{$line['altnum']}{$line['subtype']} / {$line['doc_id']}</a>{$info}</td><td></td>
				<td align='right'>$d_inc</td>
				<td align='right'>$d_dec</td>
				<td align='right'>$end_nd</td>
				<td align='right'>$end_cd</td></tr>";
        }

        $end_cd = $end_nd = $end_cnd = $end_ccd = '';
        if ($dolg > 0)
            $end_cd = $dolg_p;
        else if ($dolg < 0)
            $end_nd = $dolg_p;
        $cdolg = $dolg - $start_dolg;
        if ($cdolg < 0)
            $end_ccd = number_format(abs($cdolg), 2, '.', '&nbsp;');
        else if ($cdolg > 0)
            $end_cnd = number_format(abs($cdolg), 2, '.', '&nbsp;');
        if ($start_dolg_p) {
            $start_dolg_p = number_format($start_dolg_p, 2, '.', '&nbsp;');
        }
        $ret = '';
        if ($this->debt == 0 || ($this->debt * $dolg > 0)) {
            $ret.="<tr style='background-color: #fec; font-weight: bold;'><td colspan='2'>$head</td><td align='right'>$start_dolg_p</td><td align='right'>$end_cnd</td><td align='right'>$end_ccd</td><td align='right' style='color: #f00;'>$end_nd</td><td align='right'>$end_cd</td></tr>";
            if ($this->detail_doc)
                $ret.=$table_data;
        }
        return $ret;
    }

}
