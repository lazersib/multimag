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

/// @brief Справочник товаров и услуг.
///
/// Позволяет отображать список товаров, редактировать товары и их доп.свойства индивидуально или набором, управлять товарными группами, осуществлять поиск
class doc_s_Sklad {
	
	function __construct()	{
		$this->pos_vars = array('group', 'name', 'desc', 'proizv', 'cost', 'likvid', 'pos_type', 'hidden', 'unit', 'vc', 'stock', 'warranty',
		    'warranty_type', 'no_export_yml', 'country', 'title_tag', 'meta_keywords', 'meta_description', 'cost_date', 'mult', 'bulkcnt',
		    'analog_group', 'mass', 'nds');
		$this->dop_vars = array('type', 'analog', 'd_int', 'd_ext', 'size', 'ntd');
		$this->group_vars = array('name' , 'desc' , 'pid' , 'hidelevel' , 'printname', 'no_export_yml', 'title_tag', 'meta_keywords', 'meta_description');
	}
	
	/// Отобразить справочник
	function View()	{
		global $tmpl, $CONFIG, $db;
		doc_menu();
		if(!isAccess('list_sklad','view'))	throw new AccessException();
		$tmpl->setTitle("Редактор склада");
		if(rcvint('sklad')) $_SESSION['sklad_num'] = rcvint('sklad');
		if(!isset($_SESSION['sklad_num'])) $_SESSION['sklad_num'] = 1;
		$sklad = $_SESSION['sklad_num'];
		
		if(isset($_REQUEST['store_only'])) $_SESSION['sklad_store_only'] = rcvint('store_only');
		if(!isset($_SESSION['sklad_store_only'])) $_SESSION['sklad_store_only'] = false;
		$store_only = $_SESSION['sklad_store_only'];

		if(rcvint('cost')) $_SESSION['sklad_cost'] = rcvint('cost');
		if(!isset($_SESSION['sklad_cost'])) {
			if(@$CONFIG['store']['default_cost']>0)	$_SESSION['sklad_cost'] = $CONFIG['store']['default_cost'];
			else $_SESSION['sklad_cost'] = -1;
		}
		$cost = $_SESSION['sklad_cost'];
		
		$statistic_res = $db->query("SELECT COUNT(`doc_base`.`id`), SUM(`doc_base_cnt`.`cnt`), SUM(`doc_base_cnt`.`cnt`*`doc_base`.`mass`) AS `mass`
				FROM `doc_base`
				LEFT JOIN `doc_base_dop` ON `doc_base`.`id`=`doc_base_dop`.`id`
				LEFT JOIN `doc_base_cnt` ON `doc_base`.`id`=`doc_base_cnt`.`id` AND `doc_base_cnt`.`sklad`=$sklad");
		if($statistic_res->num_rows){
			list($_pos_cnt, $_item_cnt, $_all_mass) = $statistic_res->fetch_row();
                        if($_pos_cnt>20000000) {
                            $pos_cnt = number_format($_pos_cnt/1000000, 1, '.', ' ').' млн.';
                        } elseif($_pos_cnt>2000) {
                            $pos_cnt = number_format($_pos_cnt/1000, 1, '.', ' ').' тыс.';
                        }
			else $pos_cnt = number_format($_pos_cnt, 0, '.', ' ');
                        
                        if($_item_cnt>20000000) {
                            $item_cnt = number_format($_item_cnt/1000000, 1, '.', ' ').' млн.';
                        } elseif($_item_cnt>2000) {
                            $item_cnt = number_format($_item_cnt/1000, 1, '.', ' ').' тыс.';
                        }
			else $item_cnt = number_format($_item_cnt, 2, '.', ' ');
                        
                        if($_all_mass>20000000) {
                            $all_mass = number_format($_all_mass/1000000, 1, '.', ' ').' тыс.тонн';
                        } elseif($_all_mass>2000) {
                            $all_mass = number_format($_all_mass/1000, 1, '.', ' ').' тонн';
                        }
			else $all_mass = number_format($_all_mass, 2, '.', ' ').' кг.';
		}
		else	$pos_cnt=$item_cnt=$all_mass=0;
		
		$tmpl->addContent("
		<script type='text/javascript'>
		function SelAll(_this)
		{
			var flag=_this.checked
			var node=document.getElementById('sklad')
			var elems = node.getElementsByClassName('pos_ch')

			var l = elems.length;
			for(var i=0; i<l; i++)
			{
				elems[i].checked=flag;
			}
		}
		</script>
		<table width='100%'><tr><td width='170'><h1>Склад</h1></td>
		<td align='center'>На складе <b>$pos_cnt</b> наименований в количестве <b>$item_cnt</b> единиц, массой <b>$all_mass</b></td>
		<td align='right'>
		<form action='' method='post'>
		<input type='hidden' name='l' value='sklad'>
		<select name='cost'>
		<option value='-1'>-- не выбрано --</option>");
		$c_res = $db->query("SELECT `id`, `name` FROM `doc_cost` ORDER BY `name`");
		while($nxt = $c_res->fetch_row()) {
			$s = ($cost == $nxt[0])?' selected':'';
			$tmpl->addContent("<option value='$nxt[0]' $s>".html_out($nxt[1])."</option>");
		}
		$c_res->free();
		$tmpl->addContent("</select>
		<select name='sklad'>");
		$s_res = $db->query("SELECT `id`, `name` FROM `doc_sklady` ORDER BY `name`");
		while($nxt = $s_res->fetch_row()) {
			$s = ($sklad == $nxt[0])?' selected':'';
			$tmpl->addContent("<option value='$nxt[0]'$s>".html_out($nxt[1])."</option>");
		}
		$s_res->free();
		$tmpl->addContent("</select>");
		$so_selected = $store_only?' selected':'';
		$nso_selected = $store_only?'':' selected';
		$tmpl->addContent("<select name='store_only'><option value='0'{$nso_selected}>Все</option><option value='1'{$so_selected}>Только в наличии</option></label>");
		$tmpl->addContent("<input type='submit' value='Выбрать'>
		</form></table>
		<table width='100%'><tr><td id='groups' width='200' valign='top' class='lin0'>");
		$this->draw_groups(0);
		$tmpl->addContent("<td id='sklad' valign='top'  class='lin1'>");
		$this->ViewSklad();
		$tmpl->addContent("</table>");
	}

	/// Служебные функции справочника
	function Service() {
		global $tmpl, $CONFIG, $db;
		$opt = request("opt");
		$g = rcvint('g');
		if($opt == 'pl') {
			$s = request('s');
			$tmpl->ajax = 1;
			if($s)	$this->ViewSkladS($s);
			else	$this->ViewSklad($g);
		}
		else if($opt == 'ep')
			$this->Edit();
		else if($opt == 'acost') {
			$pos = rcvint('pos');
			$tmpl->ajax = 1;
			$tmpl->addContent( getInCost($pos) );
		}
		else if ($opt == 'menu') {
			$tmpl->ajax = 1;
			$pos = rcvint('pos');
			$dend = date("Y-m-d");
			$tmpl->addContent("
			<div onclick=\"ShowPopupWin('/docs.php?l=pran&mode=srv&opt=ceni&pos=$pos'); return false;\" >Где и по чём</div>
			<div onclick=\"window.open('/docj_new.php?pos_id=$pos&date_to=$dend')\">Товар в журнале</div>
			<div onclick=\"window.open('/doc_reports.php?mode=sales&amp;w_docs=1&amp;sel_type=pos&amp;opt=pdf&amp;sklad={$_SESSION['sklad_num']}&amp;dt_t=$dend&amp;pos_id=$pos')\">Отчёт по движению</div>
			<div onclick=\"window.open('/docs.php?mode=srv&amp;opt=ep&amp;pos=$pos')\">Редактирование позиции</div>");
		}
		else if ($opt == 'ac') {
			$q = request('q');
			$q_sql = $db->real_escape_string($q);
			$tmpl->ajax = 1;
			$res = $db->query("SELECT `id`, `name`, `proizv`, `vc` FROM `doc_base` WHERE LOWER(`name`) LIKE LOWER('%$q_sql%') OR LOWER(`vc`) LIKE LOWER('%$q_sql%') ORDER BY `name`");
			while ($nxt = $res->fetch_row()) {
				if (@$CONFIG['poseditor']['vc'])
					$nxt[1].='(' . $nxt[3] . ')';
				$tmpl->addContent( html_out("$nxt[1]|$nxt[0]|$nxt[2]|$nxt[3]")."\n");
			}
		}
		else if ($opt == 'acj') {
			try {
				$s = request('s');
				$s_sql = $db->real_escape_string($s);
				$tmpl->ajax = 1;
				$res = $db->query("SELECT `id`, `name`, `proizv`, `vc` FROM `doc_base` WHERE LOWER(`name`) LIKE LOWER('%$s_sql%') OR LOWER(`vc`) LIKE LOWER('%$s_sql%') ORDER BY `name`");
				$str = '';
				while ($nxt = $res->fetch_row()) {
					if (@$CONFIG['poseditor']['vc'])
						$nxt[1].='(' . $nxt[3] . ')';
					if ($str)	$str.=",\n";
					$str.="{id:'$nxt[0]',name:'$nxt[1]',vendor:'$nxt[2]',vc:'$nxt[3]'}";
				}
				$tmpl->setContent("{response: 'data', content: [$str] }");
			}
			catch (Exception $e) {
				$tmpl->setContent("{response: 'err', message: 'Внутренняя ошибка'}");
			}
		}
		else if ($opt == 'acv') {
			$q = request('q');
			$q_sql = $db->real_escape_string($q);
			$tmpl->ajax = 1;
			$res = $db->query("SELECT `id`, `name`, `proizv`, `vc` FROM `doc_base` WHERE LOWER(`vc`) LIKE LOWER('%$q_sql%') ORDER BY `vc`");
			while ($nxt = $res->fetch_row())
				$tmpl->addContent("$nxt[3]|$nxt[0]|$nxt[2]|$nxt[1]\n");
		}
		else if ($opt == 'acp') {
			$q = request('q');
			$q_sql = $db->real_escape_string($q);
			$tmpl->ajax = 1;
			$res = $db->query("SELECT `id`, `proizv` FROM `doc_base` WHERE LOWER(`proizv`) LIKE LOWER('%$q_sql%') GROUP BY `proizv` ORDER BY `proizv`");
			while ($nxt = $res->fetch_row())
				$tmpl->addContent("$nxt[1]|$nxt[0]\n");
		} else if ($opt == 'go') {
			$to_group = rcvint('to_group');
			doc_menu();
			$up_data = array();
			switch (request('sale_flag')) {
				case 'set': $up_data[] = "`stock`='1'";
					break;
				case 'unset': $up_data[] = "`stock`='0'";
					break;
			}
			switch (request('hidden_flag')) {
				case 'set': $up_data[] = "`hidden`='1'";
					break;
				case 'unset': $up_data[] = "`hidden`='0'";
					break;
			}
			switch (request('yml_flag')) {
				case 'set': $up_data[] = "`no_export_yml`='1'";
					break;
				case 'unset': $up_data[] = "`no_export_yml`='0'";
					break;
			}
			if ($to_group > 0)
				$up_data[] = "`group`='$to_group'";
			$up_query = '';
			foreach ($up_data as $line) {
				if ($up_query)
					$up_query.=", ";
				$up_query.=$line;
			}
			$pos = request('pos');
			if ($up_query) {
				if (is_array($pos)) {
					$c = 0;
					$a = 0;
					foreach ($pos as $id => $value) {
						settype($id, 'int');
						$db->query("UPDATE `doc_base` SET $up_query WHERE `id`='$id'");
						$c++;
						$a += $db->affected_rows;
					}
					$tmpl->msg("Успешно обновлено $a строк. " . ($c - $a) . " из $c выбранных строк остались неизменёнными.", "ok");
				}
				else	$tmpl->msg("Не выбраны позиции для обновления!", 'err');
			}
			else	$tmpl->msg("Не выбрано действие!", 'err');
		}
		else	$tmpl->msg("Неверная опция - ".html_out($opt));
	}

	/// Отобразить форму редактирования
	function Edit() {
		global $tmpl, $CONFIG, $db;
		doc_menu();
		$pos = rcvint('pos');
		$param = request('param');
		$group = rcvint('g');
		if (!isAccess('list_sklad', 'view'))	throw new AccessException();
		if (($pos == 0) && ($param != 'g'))	$param = '';
		$tmpl->setTitle("Правка складского наименования");
		if ($pos != 0)		$this->PosMenu($pos, $param);

		if ($param == '') {
			$pres = $db->query("SELECT `doc_base`.`group`, `doc_base`.`name`, `doc_base`.`desc`, `doc_base`.`proizv`, `doc_base`.`cost`, `doc_base`.`likvid`, `doc_img`.`id` AS `img_id`, `doc_img`.`type` AS `img_type`, `doc_base`.`pos_type`, `doc_base`.`hidden`, `doc_base`.`unit`, `doc_base`.`vc`, `doc_base`.`stock`, `doc_base`.`warranty`, `doc_base`.`warranty_type`, `doc_base`.`no_export_yml`, `doc_base`.`country`, `doc_base`.`title_tag`, `doc_base`.`meta_keywords`, `doc_base`.`meta_description`, `doc_base`.`cost_date`, `doc_base`.`mult`, `doc_base`.`bulkcnt`, `doc_base`.`analog_group`, `doc_base`.`mass`, `doc_base`.`nds`
			FROM `doc_base`
			LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id` AND `doc_base_img`.`default`='1'
			LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
			WHERE `doc_base`.`id`='$pos'");
			
			if($pres->num_rows) {
				$pos_info = $pres->fetch_assoc();
				if ($pos_info['pos_type'])
					$n = "<input type='hidden' name='pd[type]' value='1'>Услуга";
				else
					$n = "<input type='hidden' name='pd[type]' value='0'>Товар";
			}
			else {
				$pos_info = array();
				foreach ($this->pos_vars as $value)
					$pos_info[$value] = '';				
				$tmpl->addContent("<h3>Новая запись</h3>");
				$n = "<label><input type='radio' name='pd[type]' value='0' checked>Товар</label><br>
				<label><input type='radio' name='pd[type]' value='1'>Услуга</label>";
			}

			$cc = '';
                        
                        if($pos_info['nds']===null) {
                            $pos_info['nds'] = '';
                        }
	
			if (@$pos_info['img_id']) {
				$miniimg = new ImageProductor($pos_info['img_id'], 'p', $pos_info['img_type']);
				$miniimg->SetY(320);
				$miniimg->SetX(240);
				$cc = "<td rowspan='18' style='width: 250px;'><img src='" . $miniimg->GetURI() . "' alt='".html_out($pos_info['name'])."'></td>";
			}

			$i = '';
			$act_cost = sprintf('%0.2f', getInCost($pos));
			if ($pos != 0)
				$selected = $pos_info['group'];
			else
				$selected = $group;
			$hid_check = $pos_info['hidden'] ? 'checked' : '';
			$yml_check = $pos_info['no_export_yml'] ? 'checked' : '';
			$stock_check = $pos_info['stock'] ? 'checked' : '';
			$wt0_check = (!$pos_info['warranty_type']) ? 'checked' : '';
			$wt1_check = ($pos_info['warranty_type']) ? 'checked' : '';
			
			if(!isset($CONFIG['store']['pos_leaf_only']))
				$CONFIG['store']['pos_leaf_only'] = false;

			$tmpl->addContent("<form action='' method='post'>
			<input type='hidden' name='mode' value='esave'>
			<input type='hidden' name='l' value='sklad'>
			<input type='hidden' name='pos' value='$pos'>
			<input type='hidden' name='pd[id]' value='$pos'>
			<table cellpadding='0' width='100%' class='list'>
        		<tr><td align='right' width='20%'>$n</td>
        		<td colspan='3'><input type='text' name='pd[name]' value='".html_out($pos_info['name'])."' style='width: 95%'>$cc
        		<tr><td align='right'>Группа</td>
				<td>" . selectGroupPos('pd[group]', $selected, false, '', '', @$CONFIG['store']['leaf_only']) . "</td>
				<td align='right'>Имя группы аналогов:<br><small>Аналогами будут товары<br>с совпадающим значением поля</small></td>
				<td><input type='text' name='pd[analog_group]' value='".html_out($pos_info['analog_group'])."'></td>
				</tr>
			<tr><td align='right'>Страна происхождения<br><small>Для счёта-фактуры</small></td><td><select name='pd[country]'>");
			$tmpl->addContent("<option value='0'>--не выбрана--</option>");
			$res = $db->query("SELECT `id`, `name` FROM `class_country` ORDER BY `name`");
			while ($nx = $res->fetch_row()) {
				$selected = ($group == $nx[0]) || ($nx[0] == $pos_info['country']) ? 'selected' : '';
				$tmpl->addContent("<option value='$nx[0]' $selected>".html_out($nx[1])."</option>");
			}
			$tmpl->addContent("</select></td>
				<td align='right'>Масса, кг:<br><small>Используется в ТОРГ-12</small></td>
				<td><input type='text' name='pd[mass]' value='".html_out($pos_info['mass'])."'></td>
			</tr>
        		<tr><td align='right'>Изготовитель</td>
			<td><input type='text' name='pd[proizv]' value='".html_out($pos_info['proizv'])."' id='proizv_nm' style='width: 95%'><br>
			<div id='proizv_p' class='dd'></div></td>
			<td align='right'>Код изготовителя</td><td><input type='text' name='pd[vc]' value='".html_out($pos_info['vc'])."'></td></tr>
			<tr><td align='right'>Единица измерения</td><td><select name='pd[unit]'>");

			$res2 = $db->query("SELECT `id`, `name` FROM `class_unit_group` ORDER BY `id`");
			while ($nx2 = $res2->fetch_row()) {
                            $tmpl->addContent("<option disabled style='color:#fff; background-color:#000'>".html_out($nx2[1])."</option>\n");
                            $res = $db->query("SELECT `id`, `name`, `rus_name1` FROM `class_unit` WHERE `class_unit_group_id`='$nx2[0]'");
                            while ($nx = $res->fetch_row()) {
                                $i = "";
                                if ($pos != 0) {
                                        if ($nx[0] == $pos_info['unit'])
                                                $i = " selected";
                                }
                                else if ($nx[0] == @$CONFIG['doc']['default_unit'])
                                        $i = " selected";
                                $tmpl->addContent("<option value='$nx[0]' $i>".html_out("$nx[1] ($nx[2])")."</option>");
                            }
			}
			$tmpl->addContent("</select></td>
				<td align='right'>Количество оптом:</td>
				<td><input type='text' name='pd[bulkcnt]' value='".html_out($pos_info['bulkcnt'])."'></td>
			</tr>
			<tr><td align='right'>Базовая цена</td>
				<td><input type='text' name='pd[cost]' value='{$pos_info['cost']}'> с {$pos_info['cost_date']} </td>
				<td align='right'>Кратность:</td>
				<td><input type='text' name='pd[mult]' value='".html_out($pos_info['mult'])."'></td>	
			</tr>
                        <tr><td align='right'>Ставка НДС</td>
				<td><input type='text' name='pd[nds]' value='{$pos_info['nds']}'></td>
				<td align='right' colspan=2></td>
			</tr>
			<tr><td align='right'>Ликвидность:</td>
				<td><b>{$pos_info['likvid']}%
					<small>=Сумма(Кол-во заявок + Кол-во реализаций) / МаксСумма(Кол-во заявок + Кол-во реализаций)</small></b></td>
				<td align='right'>Актуальная цена поступления:</td><td><b>$act_cost</b></td>
			</tr>
			<tr><td align='right'>Гарантийный срок:</td><td><input type='text' name='pd[warranty]' value='{$pos_info['warranty']}'> мес.</td>
			<td align='right'>Гарантия:</td><td><label><input type='radio' name='pd[warranty_type]' value='0' $wt0_check>От продавца</label> <label><input type='radio' name='pd[warranty_type]' value='1' $wt1_check>От производителя</label></td></tr>
			<tr><td align='right'>Видимость:</td><td><label><input type='checkbox' name='pd[hidden]' value='1' $hid_check>Не отображать на витрине</label></td><td><label><input type='checkbox' name='pd[no_export_yml]' value='1' $yml_check>Не экспортировать в YML</label>
			<td><label><input type='checkbox' name='pd[stock]' value='1' $stock_check>Поместить в спецпредложения</label></td></tr>

			<tr><td align='right'>Описание</td><td colspan='3'><textarea name='pd[desc]'>".html_out($pos_info['desc'])."</textarea></td></tr>
			<tr><td align='right'>Тэг title карточки товара на витрине</td>
			<td colspan='3'><input type='text' name='pd[title_tag]' value='".html_out($pos_info['title_tag'])."' style='width: 95%' maxlength='128'></td></tr>
			<tr><td align='right'>Мета-тэг keywords карточки товара на витрине</td>
			<td colspan='3'><input type='text' name='pd[meta_keywords]' value='".html_out($pos_info['meta_keywords'])."' style='width: 95%' maxlength='128'></td></tr>
			<tr><td align='right'>Мета-тэг description карточки товара на витрине</td>
			<td colspan='3'><input type='text' name='pd[meta_description]' value='".html_out($pos_info['meta_description'])."' style='width: 95%' maxlength='256'></td></tr>
			");
			if ($pos != 0)
				$tmpl->addContent("<tr><td align='right'>Режим записи:</td><td colspan='3'>
				<label><input type='radio' name='sr' value='0' checked>Сохранить</label>
				<label><input type='radio' name='sr' value='1'>Добавить</label></td></tr>");
			$tmpl->addContent("<tr><td></td><td  colspan='3'><input type='submit' value='Сохранить'></td></tr>
			<script type='text/javascript' src='/css/jquery/jquery.js'></script>
			<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>

			<script type=\"text/javascript\">
			$(document).ready(function(){
				$(\"#proizv_nm\").autocomplete(\"/docs.php\", {
					delay:300,
					minChars:1,
					matchSubset:1,
					autoFill:false,
					selectFirst:true,
					matchContains:1,
					cacheLength:20,
					maxItemsToShow:15,
					extraParams:{'l':'sklad','mode':'srv','opt':'acp'}
				});
			});
			</script>
			</table></form>");
		}
		// Дополнительные свойства
		else if ($param == 'd') {
                    $this->dopDataEditForm($pos);
		}
		// Складские свойства
		else if ($param == 's') {
			$res = $db->query("SELECT `doc_sklady`.`name`, `doc_base_cnt`.`cnt`, `doc_base_cnt`.`mincnt`,  `doc_base_cnt`.`mesto`, `doc_base_cnt`.`sklad`
			FROM `doc_base_cnt`
			LEFT JOIN `doc_sklady` ON `doc_sklady`.`id` = `doc_base_cnt`.`sklad`
			WHERE `doc_base_cnt`.`id`='$pos'");
			$tmpl->addContent("
			<form action='' method='post'>
			<input type='hidden' name='mode' value='esave'>
			<input type='hidden' name='l' value='sklad'>
			<input type='hidden' name='pos' value='$pos'>
			<input type='hidden' name='param' value='s'>
			<table cellpadding='0' width='50%' class='list'>
			<tr><th>Склад</th><th>Кол-во</th><th>Минимум</th><th>Место</th></tr>");
			while ($nxt = $res->fetch_row()) {
				$tmpl->addContent("<tr>
				<td><a href='?mode=ske&amp;sklad=$nxt[4]'>".html_out($nxt[0])."</a></td>
				<td>$nxt[1]</td>
				<td><input type='text' name='min$nxt[4]' value='$nxt[2]'></td>
				<td><input type='text' name='mesto$nxt[4]' value='".html_out($nxt[3])."'></td></tr>");
			}
			$tmpl->addContent("</table>
			<button type='submit'>Сохранить</button>
			</form>");
		}
		// Изображения
		else if ($param == 'i') {
			$max_fs = get_max_upload_filesize();
			$max_fs_size = $max_fs;
			if ($max_fs_size > 1024 * 1024)
				$max_fs_size = ($max_fs_size / (1024 * 1024)) . ' Мб';
			else if ($max_fs_size > 1024)
				$max_fs_size = ($max_fs_size / (1024)) . ' Кб';
			else
				$max_fs_size.='байт';
			$res = $db->query("SELECT `doc_base_img`.`img_id`, `doc_img`.`type`
			FROM `doc_base_img`
			LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
			WHERE `doc_base_img`.`pos_id`='$pos'");
			$checked = ($res->num_rows == 0) ? 'checked' : '';
			$tmpl->addContent("
			<table>
			<tr><th width='50%'>Изображения</th><th width='50%'>Прикреплённые файлы</th></tr>
			<tr><td valign='top'>
			<form action='' method='post' enctype='multipart/form-data'>
			<input type='hidden' name='mode' value='esave'>
			<input type='hidden' name='l' value='sklad'>
			<input type='hidden' name='pos' value='$pos'>
			<input type='hidden' name='param' value='i'>
			<table class='list' width='100%'>
			<tr><th width='10%'>По умолч.</th><th>Файл</th><th>Имя изображения</th></tr>
			<tr><td><input type='radio' name='def_img' value='1' $checked></td>
			<td><input type='hidden' name='MAX_FILE_SIZE' value='$max_fs'><input name='userfile1' type='file'></td>
			<td><input type='text' name='photoname_1' value=''></td>
			</tr>
			<tr><td><input type='radio' name='def_img' value='2'></td>
			<td><input type='hidden' name='MAX_FILE_SIZE' value='$max_fs'><input name='userfile2' type='file'></td>
			<td><input type='text' name='photoname_2' value=''></td>
			</tr>
			<tr><td><input type='radio' name='def_img' value='3'></td>
			<td><input type='hidden' name='MAX_FILE_SIZE' value='$max_fs'><input name='userfile3' type='file'></td>
			<td><input type='text' name='photoname_3' value=''></td>
			</tr>
			<tr><td><input type='radio' name='def_img' value='4'></td>
			<td><input type='hidden' name='MAX_FILE_SIZE' value='$max_fs'><input name='userfile4' type='file'></td>
			<td><input type='text' name='photoname_4' value=''></td>
			</tr>
			<tr><td><input type='radio' name='def_img' value='5'></td>
			<td><input type='hidden' name='MAX_FILE_SIZE' value='$max_fs'><input name='userfile5' type='file'></td>
			<td><input type='text' name='photoname_5' value=''></td>
			</tr>
			<tr><td colspan='3' align='center'>
			<button type='submit'>Сохранить</button>
			</table>
			<b>Форматы</b>: Не более $max_fs_size суммарно, разрешение от 150*150 до 10000*10000, форматы JPG, PNG, допустим, но не рекомендуется GIF<br>
			<b>Примечание</b>: Если написать имя картинки, которая уже есть в базе, то она и будет установлена вне зависимости от того, передан файл или нет.
			</form><h2>Ассоциированные с товаром картинки</h2>");
			while ($nxt = $res->fetch_row()) {
				$miniimg = new ImageProductor($nxt[0], 'p', $nxt[1]);
				$miniimg->SetX(175);
				$img = "<img src='" . $miniimg->GetURI() . "' width='175'>";

				$tmpl->addContent("$img<br>
				<a href='?mode=esave&amp;l=sklad&amp;param=i_d&amp;pos=$pos&amp;img=$nxt[0]'>Убрать ассоциацию</a><br><br>");
			}
			$tmpl->addContent("</td><td valign='top'>
			<form action='' method='post' enctype='multipart/form-data'>
			<input type='hidden' name='mode' value='esave'>
			<input type='hidden' name='l' value='sklad'>
			<input type='hidden' name='pos' value='$pos'>
			<input type='hidden' name='param' value='i_a'>
			<table cellpadding='0' class='list'>
			<tr><td>Прикрепляемый файл:
			<td><input type='hidden' name='MAX_FILE_SIZE' value='$max_fs'><input name='userfile' type='file'><br><small>Не более $max_fs_size</small>
			<tr><td>Описание файла (до 128 символов):
			<td><input type='text' name='comment' value='Инструкция для $pos'><br>
			<small>Если написать описание файла, которое уже есть в базе, то соответствующий файл и будет установлен, вне зависимости от того, передан он или нет.</small>
			<tr><td colspan='2' align='center'>
			<input type='submit' value='Сохранить'>
			</table>
			<table class='list' width='100%'>
			<tr><th colspan='4'>Прикреплённые файлы</th></tr>");
			$res = $db->query("SELECT `doc_base_attachments`.`attachment_id`, `attachments`.`original_filename`, `attachments`.`comment`
			FROM `doc_base_attachments`
			LEFT JOIN `attachments` ON `attachments`.`id`=`doc_base_attachments`.`attachment_id`
			WHERE `doc_base_attachments`.`pos_id`='$pos'");
			while ($nxt = $res->fetch_row()) {
				if ($CONFIG['site']['recode_enable'])
					$link = "/attachments/{$nxt[0]}/$nxt[1]";
				else	$link = "/attachments.php?att_id={$nxt[0]}";
				$tmpl->addContent("<tr><td>$nxt[0]</td><td><a href='$link'>".html_out($nxt[1])."</a></td></td><td>".html_out($nxt[2])."</td><td><a href='?mode=esave&amp;l=sklad&amp;param=i_ad&amp;pos=$pos&amp;att=$nxt[0]' title='Убрать ассоциацию'><img src='/img/i_del.png' alt='Убрать ассоциацию'></a></td></tr>");
			}
			$tmpl->addContent("</table></td></tr></table>");
		}
		// Цены
		else if ($param == 'c') {
			$cres = $db->query("SELECT `cost` AS `base_price`, `group`, `bulkcnt` FROM `doc_base` WHERE `doc_base`.`id`='$pos'");
			if(!$cres->num_rows)	throw new Exception("Позиция не найдена");
			$pos_info = $cres->fetch_assoc();
			
			//list($base_cost) = $cres->fetch_row();

			$cost_types = array('pp' => 'Процент', 'abs' => 'Абсолютная наценка', 'fix' => 'Фиксированная цена');
			$direct = array((-1) => 'Вниз', 0 => 'K ближайшему', 1 => 'Вверх');
			$res = $db->query("SELECT `doc_cost`.`id`, `doc_base_cost`.`id`, `doc_cost`.`name`, `doc_cost`.`type`, `doc_cost`.`value`, `doc_base_cost`.`type`, `doc_base_cost`.`value`, `doc_base_cost`.`accuracy`, `doc_base_cost`.`direction`, `doc_cost`.`accuracy`, `doc_cost`.`direction`
			FROM `doc_cost`
			LEFT JOIN `doc_base_cost` ON `doc_cost`.`id`=`doc_base_cost`.`cost_id` AND `doc_base_cost`.`pos_id`='$pos'");
			$tmpl->addContent("
			<form action='docs.php' method='post'>
			<input type='hidden' name='mode' value='esave'>
			<input type='hidden' name='l' value='sklad'>
			<input type='hidden' name='pos' value='$pos'>
			<input type='hidden' name='param' value='c'>
			<table cellpadding='0' width='50%' class='list'>
			<tr><th>Цена</th><th>Тип</th><th>Значение</th><th>Точность</th><th>Округление</th><th>Результат</th></tr>
			<tr><td><b>Базовая</b><td>Базовая цена<td>{$pos_info['base_price']} руб.<td>-<td>-<td>{$pos_info['base_price']} руб.");
			$pc = PriceCalc::getInstance();
			while ($cn = $res->fetch_row()) {
				$sig = ($cn[4] > 0) ? '+' : '';
				if ($cn[3] == 'pp')
					$def_val = "({$sig}$cn[4] %)";
				else if ($cn[3] == 'abs')
					$def_val = "({$sig}$cn[4] руб.)";
				else if ($cn[3] == 'fix')
					$def_val = "(= $cn[4] руб.)";
				else
					$def_val = "({$sig}$cn[4] XX)";

				$checked = $cn[1] ? 'checked' : '';
				if (!$cn[1]) {
					$cn[5] = $cn[3];
					$cn[6] = $cn[4];
					$cn[7] = $cn[9];
					$cn[8] = $cn[10];
				}

				$tmpl->addContent("<tr><td><label><input type='checkbox' name='ch$cn[0]' value='1' $checked>".html_out($cn[2])." $def_val</label>
				<td><select name='cost_type$cn[0]'>");
				foreach ($cost_types as $id => $type) {
					$sel = ($id == $cn[5]) ? ' selected' : '';
					$tmpl->addContent("<option value='$id'$sel>$type</option>");
				}

				$tmpl->addContent("</select>
				<td><input type='text' name='val$cn[0]' value='$cn[6]'>
				<td><select name='accur$cn[0]'>");
				for ($i = -3; $i < 3; $i++) {
					$a = sprintf("%0.2f", pow(10, $i * (-1)));
					$sel = $cn[7] == $i ? 'selected' : '';
					$tmpl->addContent("<option value='$i' $sel>$a</option>");
				}
				$tmpl->addContent("</select>
				<td><select name='direct$cn[0]'>");
				for ($i = (-1); $i < 2; $i++) {
					$sel = $cn[8] == $i ? 'selected' : '';
					$tmpl->addContent("<option value='$i' $sel>{$direct[$i]}</option>");
				}
				$result = $pc->getPosSelectedPriceValue($pos, $cn[0], $pos_info);
				$tmpl->addContent("</select><td>$result руб.");
			}
			$tmpl->addContent("</table>
			<button>Сохранить цены</button></form>");
		}
		// Комплектующие
		else if ($param == 'k') {
			$peopt = request('peopt');
			require_once("include/doc.sklad.kompl.php");
			$poseditor = new KomplPosList($pos);
			$poseditor->SetEditable(1);
			if ($peopt == '') {
				$res = $db->query("SELECT `doc_base_values`.`value` FROM `doc_base_params`
				LEFT JOIN `doc_base_values` ON `doc_base_values`.`param_id`=`doc_base_params`.`id` AND `doc_base_values`.`id`='$pos'
				 WHERE `doc_base_params`.`codename`='ZP'");
				if($res->num_rows)
					list($zp) = $res->fetch_row();
				else	$zp='';
				$tmpl->addContent($poseditor->Show('', $zp));
			} else {
				$tmpl->ajax = 1;
				if ($peopt == 'jget') {
					$str = $poseditor->GetAllContent();
					$tmpl->setContent($str);
				}
				// Получение данных наименования
				else if ($peopt == 'jgpi') {
					$pos = rcvint('pos');
					$tmpl->setContent($poseditor->GetPosInfo($pos));
				}
				// Json вариант добавления позиции
				else if ($peopt == 'jadd') {
					if (!isAccess('list_sklad', 'edit'))
						throw new AccessException();
					$pe_pos = rcvint('pe_pos');
					$tmpl->setContent($poseditor->AddPos($pe_pos));
				}
				// Json вариант удаления строки
				else if ($peopt == 'jdel') {
					if (!isAccess('list_sklad', 'edit'))
						throw new AccessException();
					$line_id = rcvint('line_id');
					$tmpl->setContent($poseditor->Removeline($line_id));
				}
				// Json вариант обновления
				else if ($peopt == 'jup') {
					if (!isAccess('list_sklad', 'edit'))
						throw new AccessException();
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
					$str = "{ response: 'sklad_list', content: [" . $poseditor->SearchSkladList($s) . "] }";
					$tmpl->setContent($str);
				}
				// Получение списка групп
				else if($peopt=='jgetgroups')
				{
					$doc_content = $poseditor->getGroupList();
					$tmpl->setContent($doc_content);
				}
				else throw new NotFoundException();
			}
		}
		// Связанные товары
		else if ($param == 'l') {
			$peopt = request('peopt');
			require_once("include/doc.sklad.link.php");
			$poseditor = new LinkPosList($pos);
			$poseditor->SetEditable(1);
			if ($peopt == '') {
				$tmpl->addContent($poseditor->Show());
			} else {
				$tmpl->ajax = 1;
				if ($peopt == 'jget') {
					$str = $poseditor->GetAllContent();
					$tmpl->setContent($str);
				}
				// Получение данных наименования
				else if ($peopt == 'jgpi') {
					$pos = rcvint('pos');
					$tmpl->setContent($poseditor->GetPosInfo($pos));
				}
				// Json вариант добавления позиции
				else if ($peopt == 'jadd') {
					if (!isAccess('list_sklad', 'edit'))
						throw new AccessException();
					$pe_pos = rcvint('pe_pos');
					$tmpl->setContent($poseditor->AddPos($pe_pos));
				}
				// Json вариант удаления строки
				else if ($peopt == 'jdel') {
					if (!isAccess('list_sklad', 'edit'))
						throw new AccessException();
					$line_id = rcvint('line_id');
					$tmpl->setContent($poseditor->Removeline($line_id));
				}
				// Json вариант обновления
				else if ($peopt == 'jup') {
					if (!isAccess('list_sklad', 'edit'))
						throw new AccessException();
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
					$str = "{ response: 'sklad_list', content: [" . $poseditor->SearchSkladList($s) . "] }";
					$tmpl->setContent($str);
				}
				// Получение списка групп
				else if($peopt=='jgetgroups')
				{
					$doc_content = $poseditor->getGroupList();
					$tmpl->setContent($doc_content);
				}
				else throw new NotFoundException();
			}
		}
		// Аналоги
		else if ($param == 'n') {
                    $pos_info = $db->selectRow('doc_base', $pos);

                    $analog_group = $pos_info['analog_group'];
                    $tmpl->addContent("<form action='' method='post'>
                    <input type='hidden' name='mode' value='esave'>
                    <input type='hidden' name='l' value='sklad'>
                    <input type='hidden' name='pos' value='$pos'>
                    <input type='hidden' name='param' value='n'>
                    Имя группы аналогов:<br>
                    <input type='text' name='analog_group' value='$analog_group'>
                    <button type='submit'>Записать</button>
                    </form>
                    <h3>Аналоги в группе</h3>
                    <table class='list'>
                    <tr><th>id</th><th>Код</th><th>Название</th><th>Производитель</th><th>Цена</th><th>Остаток</th>");
                    if(@$CONFIG['poseditor']['rto']) { 
                        $tmpl->addContent("<th>Резерв</th><th>Под заказ</th><th>В пути</th>");
                    }
                    $tmpl->addContent("</tr>");
                    
                    $base_link = '/docs.php?mode=srv';
                    $analog_group_sql = $db->real_escape_string($analog_group);
                    $res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base`.`proizv` AS `vendor`, `cost` AS `price`, (
                            SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id`
                        ) AS `cnt`,
                        `doc_base_dop`.`reserve`, `doc_base_dop`.`transit`, `doc_base_dop`.`offer`
                        FROM `doc_base`
                        LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
                        WHERE `analog_group`='$analog_group_sql' AND `analog_group`!=''");
                    while($line = $res->fetch_assoc()) {
                        $link = $base_link.'&amp;pos='.$line['id'];
                        $rto = '';
                        if (@$CONFIG['poseditor']['rto']) {
                            $clink = $link.'&amp;l=inf';
                            if ($line['reserve']) {
                                $rto .= "<td align='right'><a onclick=\"ShowPopupWin('{$clink}&amp;opt=rezerv'); return false;\" href='#'>{$line['reserve']}</a></td>";
                            } else {
                                $rto .= "<td></td>";
                            }
                            if ($line['offer']) {
                                $rto .= "<td align='right'><a onclick=\"ShowPopupWin('{$clink}&amp;opt=p_zak'); return false;\" href='#'>{$line['offer']}</a></td>";
                            } else {
                                $rto .= "<td></td>";
                            }
                            if ($line['transit']) {
                                $rto .= "<td align='right'><a onclick=\"ShowPopupWin('{$clink}&amp;opt=vputi'); return false;\" href='#'>{$line['transit']}</a></td>";
                            } else {
                                $rto .= "<td></td>";
                            }
                        }
                        if ($line['cnt'] != 0) {
                            $line['cnt'] = "<a href='#' onclick=\"ShowPopupWin('$link&amp;opt=ost'); return false;\" title='Отобразить все остатки'>{$line['cnt']}</a>";
                        } else {
                            $line['cnt'] = '';
                        }
                        $tmpl->addContent("<tr>
                            <td><a href='{$link}&amp;opt=ep'>{$line['id']}</a></td>
                            <td>{$line['vc']}</td><td>{$line['name']}</td><td>{$line['vendor']}</td> 
                            <td align='right'>{$line['price']}</td><td align='right'>{$line['cnt']}</td>$rto
                            </tr>");
                    }

                    $tmpl->addContent("</table>");
		}
		// История изменений
		else if ($param == 'h') {
			$res = $db->query("SELECT `doc_log`.`motion`, `doc_log`.`desc`, `doc_log`.`time`, `users`.`name`, `doc_log`.`ip`
			FROM `doc_log`
			LEFT JOIN `users` ON `users`.`id`=`doc_log`.`user`
			WHERE `doc_log`.`object`='pos' AND `doc_log`.`object_id`='$pos'
				ORDER BY `doc_log`.`time` DESC");
			$tmpl->addContent("<h1>История наименования $pos</h1>
			<table width='100%' class='list'>
			<tr><th>Выполненное действие</th><th>Описание действия</th><th>Дата</th><th>Пользователь</th><th>IP</th></tr>");
			while ($nxt = $res->fetch_row())
				$tmpl->addContent('<tr><td>'.html_out($nxt[0]).'</td><td>'.html_out($nxt[1]).'</td><td>'.html_out($nxt[2]).'</td><td>'.html_out($nxt[3]).'</td><td>'.html_out($nxt[4]).'</td></tr>');
			$tmpl->addContent("</table>");
		}
		// Правка описания группы
		else if ($param == 'g') {
                        $max_fs = get_max_upload_filesize();
			$max_fs_size = $max_fs;
			if ($max_fs_size > 1024 * 1024)
				$max_fs_size = ($max_fs_size / (1024 * 1024)) . ' Мб';
			else if ($max_fs_size > 1024)
				$max_fs_size = ($max_fs_size / (1024)) . ' Кб';
			else
				$max_fs_size.='байт';
			$res = $db->query("SELECT * FROM `doc_group` WHERE `id`='$group'");
			if($res->num_rows)	$group_info = $res->fetch_assoc();
			else {
				$group_info = array();
				foreach ($this->group_vars as $value)
					$group_info[$value] = '';
			}
			$tmpl->addContent("<h1>Описание группы</h1>
			<script type=\"text/javascript\">
			function rmLine(t)
			{
				var line=t.parentNode.parentNode
				line.parentNode.removeChild(line)
			}

			function addLine()
			{
				var fgtab=document.getElementById('fg_table').tBodies[0]
				var sel=document.getElementById('fg_select')
				var newrow=fgtab.insertRow(fgtab.rows.length)
				var lineid=sel.value
				var checked=(document.getElementById('fg_check').checked)?'checked':''

				var ctext = sel.selectedIndex !== -1 ? sel.options[sel.selectedIndex].text : ''

				newrow.innerHTML=\"<td><input type='hidden' name='fn[\"+lineid+\"]' value='1'>\"+
				\"<input type='checkbox' name='fc[\"+lineid+\"]' value='1' \"+checked+\"></td><td>\"+ctext+\"</td><td>\"+
				\"<img src='/img/i_del.png' alt='' onclick='return rmLine(this)'></td>\"
			}

			</script>
			<form action='docs.php' method='post' enctype='multipart/form-data'>
			<input type='hidden' name='mode' value='esave'>
			<input type='hidden' name='l' value='sklad'>
			<input type='hidden' name='g' value='$group'>
			<input type='hidden' name='param' value='g'>
			<table cellpadding='0' width='50%' class='list'>
			<tr><td>Наименование группы $group:</td>
			<td><input type='text' name='name' value='".html_out($group_info['name'])."'></td></tr>
			<tr><td>Находится в группе:</td>
			<td>" . selectGroupPos('pid', $group_info['pid'], true));

			if (file_exists("{$CONFIG['site']['var_data_fs']}/category/$group.jpg"))
				$img = "<br><img src='{$CONFIG['site']['var_data_web']}/category/$group.jpg'><br><a href='/docs.php?l=sklad&amp;mode=esave&amp;g=$group&amp;param=gid'>Удалить изображение</a>";
			else
				$img = '';

			$hid_check = $group_info['hidelevel'] ? 'checked' : '';
			$yml_check = $group_info['no_export_yml'] ? 'checked' : '';

			$tmpl->addContent("</td></tr>
			<tr><td>Скрытие:</td>
			<td><label><input type='checkbox' name='hid' value='3' $hid_check>Не отображать на витрине и в прайсах</label><br>
			<label><input type='checkbox' name='no_export_yml' value='3' $yml_check>Не экспортировать в YML</label></td></tr>
			<tr><td>Печатное название:</td>
			<td><input type='text' name='pname' value='".html_out($group_info['printname'])."'></td></tr>
			<tr><td>Тэг title группы на витрине:</td>
			<td><input type='text' name='title_tag' value='".html_out($group_info['title_tag'])."' maxlength='128'></td></tr>
			<tr><td>Мета-тэг keywords группы на витрине:</td>
			<td><input type='text' name='meta_keywords' value='".html_out($group_info['meta_keywords'])."' maxlength='128'></td></tr>
			<tr><td>Мета-тэг description группы на витрине:</td>
			<td><input type='text' name='meta_description' value='".html_out($group_info['meta_description'])."' maxlength='256'></td></tr>

			<tr><td>Изображение (jpg, до $max_fs_size, от 100*100):</td>
			<td><input type='hidden' name='MAX_FILE_SIZE' value='$max_fs'><input name='userfile' type='file'>$img</td></tr>
			<tr><td>Описание:</td>
			<td><textarea name='desc'>".html_out($group_info['desc'])."</textarea></td></tr>
			<tr><td>Статические дополнительные свойства товаров группы<br><br>
			Добавить из набора:<select name='collection'>
			<option value='0'>--не выбран--</option>");
			$rgroups = $db->query("SELECT `id`, `name` FROM `doc_base_pcollections_list` ORDER BY `name`");
			while ($col = $res->fetch_row()) {
				$tmpl->addContent("<option value='$col[0]'>".html_out($col[1])."</option>");
			}
			$tmpl->addContent("</select></td>
			<td>
			<table width='100%' id='fg_table' class='list'>
			<thead>
			<tr><th><img src='/img/i_filter.png' alt='Отображать в фильтрах'></th><th>Название параметра</th><th>&nbsp;</th></tr>
			</thead>
			<tfoot>
			<tr><td><input type='checkbox' id='fg_check'><td>
			<select name='pp' id='fg_select'>
			<option value='0' selected>--не выбрано--</option>");
			$res_group = $db->query("SELECT `id`, `name` FROM `doc_base_gparams` ORDER BY `name`");
			while ($groupp = $res_group->fetch_row()) {
				$tmpl->addContent("<option value='-1' disabled>".html_out($groupp[1])."</option>");
				$res = $db->query("SELECT `id`, `name` FROM `doc_base_params` WHERE `group_id`='$groupp[0]' ORDER BY `name`");
				while ($param = $res->fetch_row()) {
					$tmpl->addContent("<option value='$param[0]'>- ".html_out($param[1])."</option>");
				}
			}
			$tmpl->addContent("</select>

			</td><td><img src='/img/i_add.png' alt='' onclick='return addLine()'></td></tr>
			</td></tr></tfoot>
			<tbody>");

			$r = $db->query("SELECT `doc_base_params`.`id`, `doc_base_params`.`name`, `doc_group_params`.`show_in_filter` FROM `doc_base_params`
			LEFT JOIN `doc_group_params` ON `doc_group_params`.`param_id`=`doc_base_params`.`id`
			WHERE  `doc_group_params`.`group_id`='$group'
			ORDER BY `doc_base_params`.`id`");
			while ($p = $r->fetch_row()) {
				$checked = $p[2] ? 'checked' : '';
				$tmpl->addContent("<tr><td><input type='hidden' name='fn[$p[0]]' value='1'>
				<input type='checkbox' name='fc[$p[0]]' value='1' $checked></td><td>".html_out($p[1])."</td>
				<td><img src='/img/i_del.png' alt='' onclick='return rmLine(this)'></td></tr>");
			}

			$tmpl->addContent("</tbody></table>
			<tr class='lin1'><td colspan='2' align='center'>
			<button type='submit'>Сохранить</button>
			</table></form>");

			if ($group) {
				$cost_types = array('pp' => 'Процент', 'abs' => 'Абсолютная наценка', 'fix' => 'Фиксированная цена');
				$direct = array((-1) => 'Вниз', 0 => 'K ближайшему', 1 => 'Вверх');
				$res = $db->query("SELECT `doc_cost`.`id`, `doc_group_cost`.`id`, `doc_cost`.`name`, `doc_cost`.`type`, `doc_cost`.`value`, `doc_group_cost`.`type`, `doc_group_cost`.`value`, `doc_group_cost`.`accuracy`, `doc_group_cost`.`direction`, `doc_cost`.`accuracy`, `doc_cost`.`direction`
				FROM `doc_cost`
				LEFT JOIN `doc_group_cost` ON `doc_cost`.`id`=`doc_group_cost`.`cost_id` AND `doc_group_cost`.`group_id`='$group'");
				$tmpl->addContent("<h1>Задание цен</h1>
				<form action='docs.php' method='post'>
				<input type='hidden' name='mode' value='esave'>
				<input type='hidden' name='l' value='sklad'>
				<input type='hidden' name='g' value='$group'>
				<input type='hidden' name='param' value='gc'>
				<table cellpadding='0' width='50%' class='list'>
				<tr><th>Цена</th><th>Тип</th><th>Значение</th><th>Точность</th><th>Округление</th></tr>");
				while ($cn = $res->fetch_row()) {
					$sig = ($cn[4] > 0) ? '+' : '';
					if ($cn[3] == 'pp')
						$def_val = "({$sig}$cn[4] %)";
					else if ($cn[3] == 'abs')
						$def_val = "({$sig}$cn[4] руб.)";
					else if ($cn[3] == 'fix')
						$def_val = "(= $cn[4] руб.)";
					else
						$def_val = "({$sig}$cn[4] XX)";

					$checked = $cn[1] ? 'checked' : '';

					$tmpl->addContent("<tr><td><label><input type='checkbox' name='ch$cn[0]' value='1' $checked>".html_out($cn[2])." $def_val</label></td>
					<td><select name='cost_type$cn[0]'>");
					foreach ($cost_types as $id => $type) {
						$sel = ($id == $cn[5]) ? ' selected' : '';
						$tmpl->addContent("<option value='$id'$sel>$type</option>");
					}
					if (!$cn[1]) {
						$cn[5] = $cn[3];
						$cn[6] = $cn[4];
						$cn[7] = $cn[9];
						$cn[8] = $cn[10];
					}
					$tmpl->addContent("</select></td>
					<td><input type='text' name='val$cn[0]' value='$cn[6]'></td>
					<td><select name='accur$cn[0]'>");
					for ($i = -3; $i < 3; $i++) {
						$a = sprintf("%0.2f", pow(10, $i * (-1)));
						$sel = $cn[7] == $i ? 'selected' : '';
						$tmpl->addContent("<option value='$i' $sel>$a</option>");
					}
					$tmpl->addContent("</select>
					<td><select name='direct$cn[0]'>");
					for ($i = (-1); $i < 2; $i++) {
						$sel = $cn[8] == $i ? 'selected' : '';
						$tmpl->addContent("<option value='$i' $sel>{$direct[$i]}</option>");
					}
					$tmpl->addContent("</select>");
				}
				$tmpl->addContent("</table>
				<button>Сохранить цены</button></form>");
			}
		}
		// Импорт из яндекс маркета
		else if ($param == 'y') {
			if (!request('a')) {
				$res = $db->query("SELECT `doc_base_params`.`id`, `doc_base_values`.`value` FROM `doc_base_params`
				LEFT JOIN `doc_base_values` ON `doc_base_values`.`param_id`=`doc_base_params`.`id` AND `doc_base_values`.`id`='$pos'
				WHERE `doc_base_params`.`codename`='ym_url'");
				
				$tmpl->addContent("
				<form method='post' action='/docs.php'>
				<input type='hidden' name='l' value='sklad'>
				<input type='hidden' name='mode' value='srv'>
				<input type='hidden' name='opt' value='ep'>
				<input type='hidden' name='param' value='y'>
				<input type='hidden' name='pos' value='$pos'>
				<input type='hidden' name='a' value='parse'>
				Введите url нужного предложения Яндекс-маркета:<br>
				<input type='text' name='url' value=''>
				<button>Получить данные</button>
				</form>");
				if ($res->num_rows) {
					$nxt = $res->fetch_row();
					$tmpl->addContent("<a href='http://market.yandex.ru/model-spec.xml?modelid=".html_out($nxt[1])."'>Посмотреть на яндекс-маркете</a>");
				}
			}
			else {
				$url = request('url');
                                $keywords = null;   // Чтобы подсветка не ругалась
				preg_match("/[?]*modelid=([\d]{1,9})[?]*+/", $url, $keywords);
				$ym_id = $keywords[1];
				settype($ym_id, 'int');
				$url = "http://market.yandex.ru/model-spec.xml?modelid=" . $ym_id;
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_FAILONERROR, 1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_TIMEOUT, 4);
				$result = curl_exec($ch);
				if (curl_errno($ch))
					throw new Exception(curl_error($ch));
				curl_close($ch);

				$dom = new domDocument();
				$dom->loadHTML($result);
				$dom->preserveWhiteSpace = false;

				$f = 0;
				$tables = $dom->getElementsByTagName('table');
				foreach ($tables as $table) {
					if ($table->getAttribute('class') == 'b-properties') {
						$f = 1;
						break;
					}
				}

				function getSelectParams($id, $name) {
                                    global $db;
                                    $ret = "<select name='sel[$id]'><option value='-1' selected>--не выбрано--</option>";
                                    $selected = $db->real_escape_string($name);
                                    $res = $db->query("SELECT CONCAT(`doc_base_gparams`.`name`,' - ',`doc_base_params`.`name`), `doc_base_params`.`ym_assign`
                                    FROM `doc_base_params`
                                    INNER JOIN `doc_base_gparams` ON `doc_base_gparams`.`id`=`doc_base_params`.`group_id`
                                    WHERE `doc_base_params`.`ym_assign`='$selected'");
                                    if ($res->num_rows) {
                                        $nxt = $res->fetch_row();
                                        return $nxt[0];
                                    }
                                    $res_group = $db->query("SELECT `id`, `name` FROM `doc_base_gparams` ORDER BY `name`");
                                    while ($group = $res_group->fetch_row()) {
                                        $ret.="<option value='-1' disabled>".html_out($group[1])."</option>";
                                        $res = $db->query("SELECT `id`, `param`, `ym_assign`"
                                            . " FROM `doc_base_params`"
                                            . " WHERE `group_id`='$group[0]'"
                                            . " ORDER BY `name`");
                                        while ($param = $res->fetch_row()) {
                                            $warn = $param[2] ? '(!)' : '';
                                            $ret.="<option value='$param[0]'>- ".html_out($param[1])." $warn</option>";
                                        }
                                    }
                                    $ret.="</select>";
                                    return $ret;
				}

				if ($f) {
					$values = array();

					mb_internal_encoding('UTF-8');
					$rows = $table->getElementsByTagName('tr');
					$prefix = $param = '';
					foreach ($rows as $row) {
						for ($item = $row->firstChild; $item != null; $item = $item->nextSibling) {
							$class = $item->getAttribute('class');
							if (strpos($class, 'b-properties__title') !== false)
								$prefix = $item->nodeValue;
							else if (strpos($class, 'b-properties__label') !== false)
								$param = $item->nodeValue;
							else if (strpos($class, 'b-properties__value') !== false)
								$values["$prefix:$param"] = $item->nodeValue;
						}
					}
					$tmpl->addContent("
					<form method='post' action='/docs.php'>
					<input type='hidden' name='l' value='sklad'>
					<input type='hidden' name='mode' value='esave'>
					<input type='hidden' name='param' value='y'>
					<input type='hidden' name='pos' value='$pos'>
					<input type='hidden' name='ym_id' value='$ym_id'>
					<table class='list'>
					<tr><th>Параметр Яндекс Маркета</th><th>Ассоциированный параметр</th><th>Значение из Яндекс Маркета</th></tr>");
					$i = 0;
					foreach ($values as $param => $value) {
						$tmpl->addContent("<tr><td><input type='checkbox' name='ch[$i]' value='".html_out($param)."' checked>".html_out($param)."</td><td>" . getSelectParams($i, $param) . "</td><td><input type='text' name='val[$i]' value='".html_out($value)."' style='width: 400px'></td></tr>");
						$i++;
					}
					$tmpl->addContent("</table>
					<label><input type='checkbox' name='id_save' value='1' checked>Сохранить новый ID</label><br>
					<label><input type='checkbox' name='auto' value='1' checked>Автоматически ассоциировать одноимённые параметры</label><br>
					<label><input type='checkbox' name='create' value='1'>Создать и ассоциировать отсутствующие (не рекомендуется, т.к. это может нарушить авторские права)</label><br>
					<label><input type='checkbox' name='to_collection' value='1'>Добавить создаваемые параметры в категорию</label><br>
					<select name='collection'>
					<option value='0'>--не выбран--</option>");
					$rgroups = $db->query("SELECT `id`, `name` FROM `doc_base_pcollections_list` ORDER BY `name`");
					while ($col = $rgroups->fetch_row()) {
						$tmpl->addContent("<option value='$col[0]'>".html_out($col[1])."</option>");
					}
					$tmpl->addContent("</select>
					<button>Записать</button>
					</form>");
				}
			}
		}
		else	$tmpl->msg("Неизвестная закладка");
	}

    /// Запись формы карточки товара
    protected function saveProduct($pos_id) {
        global $db, $CONFIG, $tmpl;
        $pd = request('pd');
        $sr = request('sr');

        if (($pos_id) && (!$sr)) {
            if (!isAccess('list_sklad', 'edit')) {
                throw new AccessException();
            }
            $sql_add = $log_add = '';
            $old_data = $db->selectRowA('doc_base', $pos_id, $this->pos_vars);

            if (@$CONFIG['store']['leaf_only']) {
                $new_group = intval($pd['group']);
                $res = $db->query("SELECT `id` FROM `doc_group` WHERE `pid`=$new_group");
                if ($res->num_rows) {
                    throw new Exception("Запись наименования возможна только в конечную группу!");
                }
            }

            foreach ($old_data as $id => $value) {
                if ($id == 'id' || $id == 'likvid' || $id == 'cost_date') {
                    continue;
                }
                if (!isset($pd[$id])) {
                    $pd[$id] = 0;
                }
                if ($pd[$id] != $value) {
                    if ($id == 'country') {
                        if (!$pd[$id] && !$value) {
                            continue;
                        }
                        $new_val = intval($pd[$id]);
                        if (!$new_val)
                            $new_val = 'NULL';
                    }
                    else if ($id == 'cost') {
                        $cost = sprintf("%0.2f", $pd[$id]);
                        $new_val = "'$cost', `cost_date`=NOW()";
                    } else if ($id == 'nds') {
                        if ($pd[$id] === '') {
                            $new_val = 'NULL';
                        } else {
                            $new_val = intval($pd[$id]);
                        }
                    } else {
                        $new_val = "'" . $db->real_escape_string($pd[$id]) . "'";
                    }

                    $log_add.=", $id:($value => {$pd[$id]})";
                    $sql_add.=", `$id`=$new_val";
                }
            }
            if(@$CONFIG['store']['require_mass']) {
                if($pd['mass']==0 && $pd['type']==0) {
                    throw new \Exception('Обязательное поле *масса* не заполено');
                }
            }
            if ($sql_add) {
                $db->query("UPDATE `doc_base` SET `id`=`id` $sql_add WHERE `id`='$pos_id'");
                $tmpl->msg("Данные обновлены!");
                doc_log("UPDATE", "$log_add", 'pos', $pos_id);
            } else {
                $tmpl->msg("Ничего не было изменено", 'info');
            }
        }
        else {
            if (!isAccess('list_sklad', 'create')) {
                throw new AccessException();
            }

            $log = '';
            $data = array();
            foreach ($this->pos_vars as $field) {
                if ($field == 'nds') {
                    if ($pd[$field] === '') {
                        $data[$field] = 'NULL';
                    } else {
                        $data[$field] = intval($pd[$field]);
                    }
                    $log.="$field:" . $data[$field] . ", ";
                } elseif ($field != 'cost_date' && isset($pd[$field])) {
                    $data[$field] = $pd[$field];
                    $log.="$field:" . $pd[$field] . ", ";
                }
            }
            $data['cost_date'] = date("Y-m-d H:i:s");
            if(@$CONFIG['store']['require_mass']) {
                if($data['mass']==0 && $data['type']==0) {
                    throw new \Exception('Обязательное поле *масса* не заполено');
                }
            }
            $opos = $pos_id;

            $db->startTransaction();
            $pos_id = $db->insertA('doc_base', $data);

            if ($opos) {
                $res = $db->query("SELECT `type`, `d_int`, `d_ext`, `size` FROM `doc_base_dop` WHERE `id`='$opos'");
                $nxt = $res->fetch_assoc();
                if ($nxt) {
                    $db->query("REPLACE `doc_base_dop` (`id`, `type`, `d_int`, `d_ext`, `size`)
                        VALUES ('$pos_id', '{$nxt['type']}', '{$nxt['d_int']}', '{$nxt['d_ext']}', '{$nxt['size']}')");
                }
            }
            doc_log("CREATE", $log, 'pos', $pos_id);

            $res = $db->query("SELECT `id` FROM `doc_sklady`");
            while ($nxt = $res->fetch_row()) {
                $db->query("INSERT INTO `doc_base_cnt` (`id`, `sklad`, `cnt`) VALUES ('$pos_id', '$nxt[0]', '0')");
            }

            $this->PosMenu($pos_id, '');
            $tmpl->msg("Добавлена новая позиция!<br><a href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;pos=$pos_id'>Перейти</a>");
            $db->commit();
        }
    }

    /// Сохранить данные после редактирования
	function ESave() {
		global $tmpl, $CONFIG, $db;
		doc_menu();
		$pos = rcvint('pos');
		$param = request('param');
		$group = rcvint('g');
		$tmpl->setTitle("Правка складского наименования");
		if ($pos != 0) {
                    $this->PosMenu($pos, $param);
                }
                
		if ($param == '') {
		    $this->saveProduct($pos);
		}
		else if ($param == 'd') {
			$analog = request('analog');
			$d_int = request('d_int', 0);
			$d_ext = request('d_ext', 0);
			$size = request('size', 0);
			$ntd = request('ntd');
			if (!isAccess('list_sklad', 'edit'))	throw new AccessException();

			if (isset($_REQUEST['type']))		$type = $_REQUEST['type'];
			else					$type = 'null';
			if ($type !== 'null')			settype($type, 'int');

			$old_data = $db->selectRowA('doc_base_dop', $pos, $this->dop_vars);
			$log_add = '';
			if ($old_data['analog'] != $analog)
				$log_add.=", analog:({$old_data['analog']} => $analog)";
			if ($old_data['type'] != $type && ($old_data['type'] != '' || $type != 'null'))
				$log_add.=", type:({$old_data['type']} => $type)";
			if ($old_data['d_int'] != $d_int)
				$log_add.=", d_int:({$old_data['d_int']} => $d_int)";
			if ($old_data['d_ext'] != $d_ext)
				$log_add.=", d_ext:({$old_data['d_ext']} => $d_ext)";
			if ($old_data['size'] != $size)
				$log_add.=", size:({$old_data['size']} => $size)";
			if ($old_data['ntd'] != $ntd)
				$log_add.=", ntd:({$old_data['ntd']} => $ntd)";

			if ($type !== 'null')
				$type = "'$type'";
			
			$analog_sql = $db->real_escape_string($analog);
			$d_int_sql = $db->real_escape_string($d_int);
			$d_ext_sql = $db->real_escape_string($d_ext);
			$size_sql = $db->real_escape_string($size);
			$ntd_sql = $db->real_escape_string($ntd);
			
			$db->query("REPLACE `doc_base_dop` (`id`, `analog`, `type`, `d_int`, `d_ext`, `size`, `ntd`)
                            VALUES ('$pos', '$analog_sql', $type, '$d_int_sql', '$d_ext_sql', '$size_sql', '$ntd_sql')");

			$res = $db->query("SELECT `param_id`, `value` FROM `doc_base_values` WHERE `id`='$pos'");
			$dp = array();
			while ($nxt = $res->fetch_row())
				$dp[$nxt[0]] = $nxt[1];
			$par = request('par');
			if (is_array($par)) {
                            foreach ($par as $key => $value) {
                                $key_sql = $db->real_escape_string($key);
                                if ($value !== '') {
                                    $value_sql = $db->real_escape_string($value);
                                    if (@$dp[$key] != $value) {
                                        $log_add.=@", $key:({$old_data[$key]} => $value)";
                                    }
                                    $db->query("REPLACE `doc_base_values` (`id`, `param_id`, `value`) VALUES ('$pos', '$key_sql', '$value_sql')");
                                }
                                else {
                                    $db->query("DELETE FROM `doc_base_values` WHERE `id`='$pos' AND `param_id`='$key_sql'");
                                }
                            }
			}

			$par_add = request('par_add');
			$value_add = request('value_add');
			if ($par_add && $value_add) {
                            $par_sql = $db->real_escape_string($par_add);
                            $value_add = $db->real_escape_string($value_sql);
                            $db->query("REPLACE `doc_base_values` (`id`, `param_id`, `value`) VALUES ('$pos', '$par_add', '$value_sql')");
                            if ($dp[$key] != $value) {
                                $log_add.=", $par_add:$value_add";
                            }
			}
			if ($log_add) {
                            doc_log("UPDATE", "$log_add", 'pos', $pos);
                        }
			$tmpl->msg("Данные сохранены!");
                        
                        $this->dopDataEditForm($pos);
		}
		else if ($param == 's') {
			if (!isAccess('list_sklad', 'edit'))	throw new AccessException();
			$res = $db->query("SELECT `doc_sklady`.`name`, `doc_base_cnt`.`cnt`, `doc_base_cnt`.`mincnt`,  `doc_base_cnt`.`mesto`, `doc_base_cnt`.`sklad`
			FROM `doc_base_cnt`
			LEFT JOIN `doc_sklady` ON `doc_sklady`.`id` = `doc_base_cnt`.`sklad`
			WHERE `doc_base_cnt`.`id`='$pos'");
			$log_add = '';
			while ($nxt = $res->fetch_row()) {
				$mincnt = rcvint("min$nxt[4]");
				$mesto = request("mesto$nxt[4]");
				if ($nxt[2] != $mincnt)
					$log_add.=", mincnt:({$nxt[2]} => $mincnt)";
				if ($nxt[3] != $mesto)
					$log_add.=", mesto:({$nxt[3]} => $mesto)";
				if ($nxt[2] != $mincnt || $nxt[3] != $mesto) {
					$mesto_sql = $db->real_escape_string($mesto);
					$db->query("UPDATE `doc_base_cnt` SET `mincnt`='$mincnt', `mesto`='$mesto_sql' WHERE `id`='$pos' AND `sklad`='$nxt[4]'");
				}
			}
			if ($log_add)	doc_log("UPDATE", "$log_add", 'pos', $pos);
		}
		else if ($param == 'n') {
			if (!isAccess('list_sklad', 'edit'))
				throw new AccessException();
			$analog_group = request('analog_group');
			$old_data = $db->selectRow('doc_base', $pos);
			if($analog_group!=$old_data['analog_group']) {
				$analog_group_sql = $db->real_escape_string($analog_group);
				$db->query("UPDATE `doc_base` SET `analog_group`='$analog_group_sql' WHERE `id`='$pos'");
				doc_log("UPDATE", "analog_group: {$old_data['analog_group']}=>$analog_group", 'pos', $pos);
				$tmpl->msg("Данные сохранены", 'ok');
			}
			else	$tmpl->msg("Ничего не было изменено", 'info');
				
		}
		else if ($param == 'i') {
			$id = 0;
			$max_img_size = get_max_upload_filesize();
			$min_pix = 15;
			$max_pix = 20000;
			global $CONFIG;
			$def_img = rcvint('def_img');
			if (!isAccess('list_sklad', 'edit'))	throw new AccessException();

			for ($img_num = 1; $img_num <= 5; $img_num++) {
				$set_def = 0;
				if ($def_img == $img_num)	$set_def = 1;
				$nm = request('photoname_' . $img_num);
				if(!$nm)	continue;
				$nm_sql = $db->real_escape_string($nm);
				$res = $db->query("SELECT `id` FROM `doc_img` WHERE `name`='$nm_sql'");
				if ($res->num_rows) {
					list($img_id) = $res->fetch_row();
					$tmpl->msg("Эта картинка найдена, N $img_id", "info");
				}
				else {
					if ($_FILES['userfile' . $img_num]['size'] <= 0)
						throw new Exception("Файл не получен. Возможно он не был выбран, либо его размер превышает максимально допустимый сервером.");
					if ($_FILES['userfile' . $img_num]['size'] > $max_img_size)
						throw new Exception("Слишком большой файл! Допустимо не более $max_img_size байт!");
					$iminfo = getimagesize($_FILES['userfile' . $img_num]['tmp_name']);
					switch ($iminfo[2]) {
						case IMAGETYPE_JPEG: $imtype = 'jpg';
							break;
						case IMAGETYPE_PNG: $imtype = 'png';
							break;
						case IMAGETYPE_GIF: $imtype = 'gif';
							break;
						default: $imtype = '';
					}
					if (!$imtype)
						throw new Exception("Файл - не картинка, или неверный формат файла. Рекомендуется PNG и JPG, допустим но не рекомендуется GIF.");
					if (($iminfo[0] < $min_pix) || ($iminfo[1] < $min_pix))
						throw new Exception("Слишком мелкая картинка! Минимальный размер - $min_pix пикселей!");
					if (($iminfo[0] > $max_pix) || ($iminfo[1] > $max_pix))
						throw new Exception("Слишком большая картинка! Максимальный размер - $max_pix пикселей!");
					$db->startTransaction();
					$db->query("INSERT INTO `doc_img` (`name`, `type`) VALUES ('$nm_sql', '$imtype')");
					$img_id = $db->insert_id;
					if (!$img_id)	throw new Exeption("Ошибка присваивания изображению номера");
					
					if (!move_uploaded_file($_FILES['userfile' . $img_num]['tmp_name'], $CONFIG['site']['var_data_fs'] . '/pos/' . $img_id . '.' . $imtype))
						throw new Exception("Файл не загружен, $img_id.$imtype", "err");

					$db->commit();
					$tmpl->msg("Файл загружен, $img_id.$imtype", "info");
				}
				if ($img_id) {
					if ($set_def)
						$db->query("UPDATE `doc_base_img` SET `default`='0' WHERE `pos_id`='$pos'");
					$db->query("INSERT INTO `doc_base_img` (`pos_id`, `img_id`, `default`) VALUES ('$pos', '$img_id', '$set_def')");
					doc_log("UPDATE", "Add image (id:$img_id)", 'pos', $pos);
				}
			}
		}
		else if ($param == 'i_a') {
			$attachment_id = 0;
			$comment = request('comment');
			$comm_sql = $db->real_escape_string($comment);
			if (!isAccess('list_sklad', 'edit'))	throw new AccessException();
			$db->startTransaction();
			$res = $db->query("SELECT `id` FROM `attachments` WHERE `comment`='$comm_sql'");
			if ($res->num_rows) {
				list($attachment_id) = $db->fetch_row();
				$tmpl->msg("Этот файл найден, N $attachment_id", "info");
			} else {
				if ($_FILES['userfile']['size'] <= 0)
					throw new Exception("Файл не получен. Возможно он не был выбран, либо его размер превышает максимально допустимый сервером");

				$filename = $_FILES['userfile']['name'];
				$filename = str_replace("#", "Hash.", $filename);
				$filename = str_replace("$", "Dollar", $filename);
				$filename = str_replace("%", "Percent", $filename);
				$filename = str_replace("^", "", $filename);
				$filename = str_replace("&", "and", $filename);
				$filename = str_replace("*", "", $filename);
				$filename = str_replace("?", "", $filename);
				$filename = str_replace("'", "", $filename);
				$filename = str_replace("\"", "", $filename);
				$filename = str_replace("\\", "", $filename);
				$filename = str_replace("/", "", $filename);
				$filename = str_replace(" ", "_", $filename);
				$filename_sql = $db->real_escape_string($filename);
				$db->query("INSERT INTO `attachments` (`original_filename`, `comment`)	VALUES ('$filename_sql', '$comm_sql')");
				$attachment_id = $db->insert_id;
				if (!$attachment_id)	throw new Exception("Не удалось получить ID строки");
				if (!file_exists($CONFIG['site']['var_data_fs'] . '/attachments/')) {
					if (!mkdir($CONFIG['site']['var_data_fs'] . '/attachments/', 0755, true))
						throw new Exception("Не удалось создать директорию для прикреплённых файлов. Вероятно, права доступа установлены неверно.");
				}
				else if (!is_dir($CONFIG['site']['var_data_fs'] . '/attachments/'))
					throw new Exception("Вместо директории для прикреплённых файлов обнаружен файл. Обратитесь к администратору.");

				if (!move_uploaded_file($_FILES['userfile']['tmp_name'], $CONFIG['site']['var_data_fs'] . '/attachments/' . $attachment_id))
					throw new Exception("Не удалось сохранить файл");
				$tmpl->msg("Файл загружен, ID:$attachment_id", "info");
			}
			if ($attachment_id)
				$db->query("INSERT INTO `doc_base_attachments` (`pos_id`, `attachment_id`) VALUES ('$pos', '$attachment_id')");

			$db->commit();
			doc_log("UPDATE", "Add attachment (id:$attachment_id, $filename, $comment)", 'pos', $pos);
		}
		else if ($param == 'i_d') {
			if (!isAccess('list_sklad', 'edit'))		throw new AccessException();
			$img = rcvint('img');
			$db->query("DELETE FROM `doc_base_img` WHERE `pos_id`='$pos' AND `img_id`='$img'");
			doc_log("UPDATE", "delete image (id:$img)", 'pos', $pos);
			$tmpl->msg("Ассоциация с изображением удалена! Для продолжения работы воспользуйтесь меню!", "ok");
		}
		else if ($param == 'i_ad') {
			if (!isAccess('list_sklad', 'edit'))	throw new AccessException();
			$att = rcvint('att');
			$db->query("DELETE FROM `doc_base_attachments` WHERE `pos_id`='$pos' AND `attachment_id`='$att'");
			doc_log("UPDATE", "delete attachment (id:$att)", 'pos', $pos);
			$tmpl->msg("Ассоциация с присоединённым файлом удалена! Для продолжения работы воспользуйтесь меню!", "ok");
		}
		else if ($param == 'c') {
			if (!isAccess('list_sklad', 'edit'))	throw new AccessException();
			$db->startTransaction();
			$res = $db->query("SELECT `doc_cost`.`id`, `doc_base_cost`.`id`, `doc_base_cost`.`type`, `doc_base_cost`.`value`, `doc_base_cost`.`accuracy`, `doc_base_cost`.`direction`
			FROM `doc_cost`
			LEFT JOIN `doc_base_cost` ON `doc_cost`.`id`=`doc_base_cost`.`cost_id` AND `doc_base_cost`.`pos_id`='$pos'");
			$log = '';
			while ($nxt = $res->fetch_row()) {

				$ch = request('ch' . $nxt[0]);
				$cost_type = request('cost_type' . $nxt[0]);
				$val = rcvrounded('val' . $nxt[0], 2);
				$accur = rcvint('accur' . $nxt[0]);
				$direct = rcvint('direct' . $nxt[0]);

				$cost_type_sql = $db->real_escape_string($cost_type);
				
				if ($nxt[1] && (!$ch)) {
					$db->query("DELETE FROM `doc_base_cost` WHERE `id`='$nxt[1]'");
					$log.="DELETE cost ID:$nxt[0] - type:$nxt[6], value:$nxt[7]; ";
				}
				else if ($nxt[1] && $ch) {
					$update = $changes = '';
					if ($nxt[2] != $cost_type) {
						$update.=", `type`='$cost_type_sql'";
						$changes.="type:{$nxt[2]}=>{$cost_type}, ";
					}
					if ($nxt[3] != $val) {
						$update.=", `value`='$val'";
						$changes.="value:{$nxt[3]}=>{$val}, ";
					}
					if ($nxt[4] != $accur) {
						$update.=", `accuracy`='$accur'";
						$changes.="accuracy:{$nxt[4]}=>{$accur}, ";
					}
					if ($nxt[5] != $direct) {
						$update.=", `direction`='$direct'";
						$changes.="direction:{$nxt[5]}=>{$direct}, ";
					}
					if ($update) {
						$db->query("UPDATE `doc_base_cost` SET `id`=`id` $update WHERE `id`='$nxt[1]'");
						$log.="UPDATE cost ID:$nxt[0] - $changes ";
					}
				}
				else if ($ch) {
					$db->query("INSERT INTO `doc_base_cost` (`cost_id`, `pos_id`, `type`, `value`, `accuracy`, `direction`)
					VALUES ('$nxt[0]', '$pos', '$cost_type_sql', '$val', '$accur', '$direct')");
					$log.="INSERT cost ID:$nxt[0] - type:$cost_type, value:$val, accuracy:$accur, direction:$direct;";
				}
			}
			$tmpl->msg("Изменения сохранены!", "ok");
			if ($log)
				doc_log('UPDATE pos-ceni', $log, 'pos', $pos);
			$db->commit();
		}
		else if ($param == 'k') {
                    if (!isAccess('list_sklad', 'edit')) {
                        throw new AccessException();
                    }
                    $zp = request('zp');
                    $res = $db->query("SELECT `doc_base_params`.`id`, `doc_base_values`.`value` FROM `doc_base_params`
                    LEFT JOIN `doc_base_values` ON `doc_base_values`.`param_id`=`doc_base_params`.`id` AND `doc_base_values`.`id`='$pos'
                    WHERE `doc_base_params`.`codename`='ZP'");
                    if (!$res->num_rows) {
                        $db->query("INSERT INTO `doc_base_params` (`name`, `codename`, `type`, `hidden`)"
                           . " VALUES ('Зп сборщика', 'ZP', 'double', 1)");
                        $nxt = array(0 => $db->insert_id, 1 => 0);
                    } else {
                        $nxt = $res->fetch_row();
                    }
                    if ($zp != $nxt[1]) {
                        $zp_sql = $db->real_escape_string($zp);
                        $db->query("REPLACE `doc_base_values` (`id`, `param_id`, `value`) VALUES ('$pos', '$nxt[0]', '$zp_sql')");
                        doc_log("UPDATE pos", "ZP: ($nxt[1] => $zp)", 'pos', $pos);
                        $tmpl->msg("Данные обновлены!", "ok");
                    }
                    else	$tmpl->msg("Ничего не изменилось!");
		}
		else if ($param == 'g') {
			if (!isAccess('list_sklad', 'edit'))	throw new AccessException();
			$max_size = get_max_upload_filesize();
			$name = request('name');
			$desc = request('desc');
			$pid = rcvint('pid');
			$hid = rcvint('hid');
			$pname = request('pname');
			$title_tag = request('title_tag');
			$meta_keywords = request('meta_keywords');
			$meta_description = request('meta_description');
			$collection = rcvint('collection');
			$no_export_yml = rcvint('no_export_yml');
			
			$name_sql = $db->real_escape_string($name);
			$desc_sql = $db->real_escape_string($desc);
			$pname_sql = $db->real_escape_string($pname);
			$title_tag_sql = $db->real_escape_string($title_tag);
			$meta_keywords_sql = $db->real_escape_string($meta_keywords);
			$meta_description_sql = $db->real_escape_string($meta_description);
			
			if ($group) {
				if($pid==$group)
					throw new Exception("Нельзя добавить группу саму в себя!");
				$res = $db->query("UPDATE `doc_group` SET `name`='$name_sql', `desc`='$desc_sql', `pid`='$pid', `hidelevel`='$hid',
					`printname`='$pname_sql', `no_export_yml`='$no_export_yml', `title_tag`='$title_tag_sql',
					`meta_keywords`='$meta_keywords_sql', `meta_description`='$meta_description_sql' WHERE `id` = '$group'");
			} else {
				$res = $db->query("INSERT INTO `doc_group` (`name`, `desc`, `pid`, `hidelevel`, `printname`, `no_export_yml`, `title_tag`,
						`meta_keywords`, `meta_description`)
					VALUES ('$name_sql', '$desc_sql', '$pid', '$hid', '$pname_sql', '$no_export_yml', '$title_tag_sql', '$meta_keywords_sql',
						'$meta_description_sql' )");
			}
			
			$db->query("DELETE FROM `doc_group_params` WHERE `group_id`='$group'");
			$fn = request('fn');
			if (is_array($fn)) {
				foreach ($fn as $id => $val) {
					settype($id, 'int');
					$show = (@$_POST['fc'][$id]) ? '1' : '0';
					$db->query("INSERT INTO `doc_group_params` (`group_id`, `param_id`, `show_in_filter`) VALUES ('$group', '$id', '$show')");
				}
			}
			if ($collection) {
				$rparams = $db->query("SELECT `doc_base_pcollections_set`.`param_id`
					FROM `doc_base_pcollections_set`
					WHERE `collection_id`='$collection'");
				while ($param = $rparams->fetch_row())
					$db->query("INSERT INTO `doc_group_params` (`group_id`, `param_id`, `show_in_filter`) VALUES ('$group', '$param[0]', '0')");
			}

			if ($_FILES['userfile']['size'] > 0) {
				$iminfo = getimagesize($_FILES['userfile']['tmp_name']);
				switch ($iminfo[2]) {
					case IMAGETYPE_JPEG: $imtype = 'jpg';
						break;
					default: $imtype = '';
				}
				if (!$imtype)
					throw new Exception("Неверный формат файла! Допустимы только изображения в формате jpeg.");
				else if (($iminfo[0] < 100) || ($iminfo[1] < 100))
					throw new Exception("Слишком мелкая картинка! Минимальный размер - 100*100 пикселей!");
				if (!move_uploaded_file($_FILES['userfile']['tmp_name'], "{$CONFIG['site']['var_data_fs']}/category/$group.jpg"))
					throw new Exception("Не удалось записать изображение. Проверьте права доступа к директории {$CONFIG['site']['var_data_fs']}/category/");
			}
			$tmpl->msg("Сохранено!");
		}
		else if ($param == 'gid') {
			if (!isAccess('list_sklad', 'edit'))	throw new AccessException();
			if (!file_exists("{$CONFIG['site']['var_data_fs']}/category/$group.jpg"))
				throw new Exception("Изображение не найдено");
			if (!unlink("{$CONFIG['site']['var_data_fs']}/category/$group.jpg"))
				throw new Exception("Не удалось удалить изображение! Проверьте права доступа!");
			$tmpl->msg("Изображение удалено!", "ok");
		}
		else if ($param == 'gc') {
			if (!isAccess('list_sklad', 'edit'))	throw new AccessException();
			$db->startTransaction();
			$res = $db->query("SELECT `doc_cost`.`id`, `doc_group_cost`.`id`, `doc_group_cost`.`type`, `doc_group_cost`.`value`, `doc_group_cost`.`accuracy`, `doc_group_cost`.`direction`
			FROM `doc_cost`
			LEFT JOIN `doc_group_cost` ON `doc_cost`.`id`=`doc_group_cost`.`cost_id` AND `doc_group_cost`.`group_id`='$group'");
			$log = '';
			
			while ($nxt = $res->fetch_row()) {
				$ch = rcvint('ch' . $nxt[0]);
				$cost_type = request('cost_type' . $nxt[0], 2);
				$val = rcvrounded('val' . $nxt[0], 2);
				$accur = rcvint('accur' . $nxt[0]);
				$direct = rcvint('direct' . $nxt[0]);
				
				$cost_type_sql = $db->real_escape_string($cost_type);
				
				if ($nxt[1] && (!$ch)) {
					$db->delete('doc_group_cost', $nxt[1]);
					$log.="DELETE cost ID:$nxt[0] - type:$nxt[6], value:$nxt[7]; ";
				}
				else if ($nxt[1] && $ch) {
					$update = $changes = '';
					if ($nxt[2] != $cost_type) {
						$update = ", `type`='$cost_type_sql'";
						$changes = "type:{$nxt[2]}=>{$cost_type}, ";
					}
					if ($nxt[3] != $val) {
						$update = ", `value`='$val'";
						$changes = "value:{$nxt[3]}=>{$val}, ";
					}
					if ($nxt[4] != $accur) {
						$update = ", `accuracy`='$accur'";
						$changes = "accuracy:{$nxt[4]}=>{$accur}, ";
					}
					if ($nxt[5] != $direct) {
						$update = ", `direction`='$direct'";
						$changes = "direction:{$nxt[5]}=>{$direct}, ";
					}
					if ($update) {
						$db->query("UPDATE `doc_group_cost` SET `id`=`id` $update WHERE `id`='$nxt[1]'");
						$log.="UPDATE cost ID:$nxt[0] - $changes ";
					}
				}
				else if ($ch) {
					$db->query("INSERT INTO `doc_group_cost` (`cost_id`, `group_id`, `type`, `value`, `accuracy`, `direction`)
					VALUES ('$nxt[0]', '$group', '$cost_type_sql', '$val', '$accur', '$direct')");
					$log.="INSERT cost ID:$nxt[0] - type:$cost_type, value:$val, accuracy:$accur, direction:$direct; ";
				}
			}
			$tmpl->msg("Изменения сохранены!", "ok");
			if ($log)
				doc_log('UPDATE group-ceni', $log, 'group', $group);
			$db->commit();
		}
		else if ($param == 'y') {
                    $url = request('url');
                    $checkboxes = request('ch');
                    $ym_id = rcvint('ym_id');
                    $id_save = request('id_save');
                    $auto = request('auto');
                    $create = request('create');
                    $collection = rcvint('collection');
                    $to_collection = request('to_collection');

                    if ($id_save) {
                        $res = $db->query("SELECT `doc_base_params`.`id`, `doc_base_values`.`value` FROM `doc_base_params`
                        LEFT JOIN `doc_base_values` ON `doc_base_values`.`param_id`=`doc_base_params`.`id` AND `doc_base_values`.`id`='$pos'
                        WHERE `doc_base_params`.`param`='ym_url'");
                        if (!$res->num_rows) {
                                $db->query("INSERT INTO `doc_base_params` (`name`, `codename`, `type`, `hidden`)"
                                    . " VALUES ('URL Я.Маркет', 'ym_url', 'double', 1)");
                                $nxt = array(0 => $db->insert_id, 1 => 0);
                        }
                        else
                                $nxt = $res->fetch_row();
                        if ($ym_id != $nxt[1]) {
                                $db->query("REPLACE `doc_base_values` (`id`, `param_id`, `value`) VALUES ('$pos', '$nxt[0]', '$ym_id')");
                                doc_log("UPDATE pos", "ym_url: ($nxt[1] => $ym_id)", 'pos', $pos);
                        }
                    }
                    if (!is_array($checkboxes))	throw new Exception('Не передан набор данных');
                    $log_add = '';
                    foreach ($checkboxes as $id => $param) {
                        $param_sql = $db->real_escape_string($param);
                        $res = $db->query("SELECT `doc_base_params`.`id` FROM `doc_base_params` WHERE `doc_base_params`.`ym_assign`='$param_sql'");
                        if ($res->num_rows)	list($int_param) = $res->fetch_row();
                        else {
                            $int_param = $_POST['sel'][$id];
                            settype($int_param, 'int');
                            if ($int_param < 1 && $auto) {
                                $res = $db->query("SELECT `doc_base_params`.`id`, CONCAT(`doc_base_gparams`.`name`,':',`doc_base_params`.`name`) AS `pname`
                                FROM `doc_base_params`
                                INNER JOIN `doc_base_gparams` ON `doc_base_gparams`.`id`=`doc_base_params`.`group_id`
                                WHERE CONCAT(`doc_base_gparams`.`name`,':',`doc_base_params`.`name`)='$param_sql'");
                                if ($res->num_rows)	list($int_param) = $res->fetch_row();
                            }
                            if ($int_param < 1 && $create) {
                                list($gname, $pname) = mb_split(":", $param, 2);
                                $gname_sql = $db->real_escape_string($gname);
                                $pname_sql = $db->real_escape_string($pname);
                                $gres = $db->query("SELECT `id`, `name` FROM `doc_base_gparams` WHERE `name` = '$gname_sql'");
                                if ($gres->num_rows)
                                    list($g_id) = $gres->fetch_row();
                                else {
                                    $db->query("INSERT INTO `doc_base_gparams` (`name`) VALUES ('$gname_sql')");
                                    $g_id = $db->insert_id;
                                }
                                $res = $db->query("SELECT `id`, `param` FROM `doc_base_params` WHERE `group_id`='$g_id' AND `name`='$pname_sql'");
                                if (!$res->num_rows) {
                                    $db->query("INSERT INTO `doc_base_params` (`name`, `type`, `group_id`, `ym_assign`) VALUES ('$pname_sql', 'text', '$g_id', '$param_sql')");
                                    $int_param = $db->insert_id;
                                    $db->query("INSERT INTO `doc_base_pcollections_set` (`collection_id`, `param_id`) VALUES ('$collection', '$int_param')");
                                }
                            }
                            if ($int_param < 1)	continue;
                            $db->query("UPDATE `doc_base_params` SET `ym_assign`='$param_sql' WHERE `id`='$int_param'");
                        }
                        if ($int_param < 1)		continue;
                        $val = $db->real_escape_string($_POST['val'][$id]);
                        $db->query("REPLACE `doc_base_values` (`id`, `param_id`, `value`) VALUES ('$pos', '$int_param', '$val')");
                        $log_add.=", $int_param:(=> $val)";
                    }
                    $tmpl->msg("Данные сохранены!", "ok");
                    if ($log_add)	doc_log("UPDATE", "$log_add", 'pos', $pos);
                }
		else	$tmpl->msg("Неизвестная закладка");
	}

	/// Формирует html код заданного уровня иерархии групп
	/// @param select ID группы, выбранной пользователем (текущая группа)
	/// @param level ID группы верхнего уровня (родительская)
	/// @return HTML код для данной родительской группы
	function draw_level($select, $level) {
		global $db;
		$ret = '';
		$res = $db->query("SELECT `id`, `name`, `desc` FROM `doc_group` WHERE `pid`='$level' ORDER BY `id`");
		$i = 0;
		$r = '';
		if ($level == 0)	$r = 'IsRoot';
		while ($nxt = $res->fetch_row()) {
			if ($nxt[0] == 0)
				continue;
			$item = "<a href='#' onclick=\"EditThis('/docs.php?mode=srv&amp;opt=pl&amp;g=$nxt[0]','sklad'); return false;\" >".html_out($nxt[1])."</a>";
			if ($i >= ($res->num_rows - 1))
				$r.=" IsLast";
			$tmp = $this->draw_level($select, $nxt[0]); // рекурсия
			if ($tmp)	$ret.="<li class='Node ExpandClosed $r'>
				<div class='Expand'></div>
				<div class='Content'>$item
				</div><ul class='Container'>" . $tmp . '</ul></li>';
			else	$ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div></li>";
			$i++;
		}
		return $ret;
	}

	/// Отображает древо групп
	/// @param select ID группы, выбранной пользователем (текущая группа)
	function draw_groups($select) {
		global $tmpl;
		$tmpl->addContent("
		<input type='text' id='sklsearch' placeholder='Глобальный фильтр...' onkeydown=\"DelayedSave('/docs.php?mode=srv&amp;opt=pl','sklad', 'sklsearch'); return true;\" >
		<div onclick='tree_toggle(arguments[0])'>
		<div><a href='' onclick=\"EditThis('/docs.php?mode=srv&amp;opt=pl&amp;g=0','sklad'); return false;\" >Группы</a>  (<a href='/docs.php?l=sklad&mode=edit&param=g&g=0'><img src='/img/i_add.png' alt=''></a>)</div>
		<ul class='Container'>".$this->draw_level($select,0)."</ul></div>");

	}

	/// Отображает список товаров группы, разбитый на страницы
	/// @param group ID группы, товары из которой нужно показать
	/// @param s не используется
	/// TODO: убрать обращения к параметру s и сам параметр
	function ViewSklad($group=0,$s=''){
		global $tmpl, $CONFIG, $db;
		$sklad = $_SESSION['sklad_num'];

		$go = request('go');
		$lim = 200;
		$vc_add = '';
		$fields_sql = $join_sql = '';
		if ($group && !$go) {
			$desc_data = $db->selectRow('doc_group', $group);
			if($desc_data['desc']) $tmpl->addContent('<p>'.html_out($desc_data['desc']).'</p>');
			
			$tmpl->addContent("
			<a href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;pos=0&amp;g=$group'><img src='/img/i_add.png' alt=''> Добавить</a> |
			<a href='/docs.php?l=sklad&amp;mode=edit&amp;param=g&amp;g=$group'><img src='/img/i_edit.png' alt=''> Правка группы</a> |
			<a href='/docs.php?l=sklad&amp;mode=search'><img src='/img/i_find.png' alt=''> Расширенный поиск</a> |
			<a href='#' onclick=\"EditThis('/docs.php?mode=srv&amp;opt=pl&amp;g=$group&amp;go=1','sklad'); return false;\" ><img src='/img/i_reload.png' alt=''> Групповые операции</a><br>");
			
		}
		else if ($go) {
			$tmpl->addContent("<form action='' method='post'>
			<input type='hidden' name='l' value='sklad'>
			<input type='hidden' name='mode' value='srv'>
			<input type='hidden' name='opt' value='go'>

			<div class='sklad-go'>
			<table width='100%'>
			<tr><td width='25%'><fieldset><legend>Поместить в спецпредложения</legend>
			<label><input type='radio' name='sale_flag' value='' checked>Не менять</label><br>
			<label><input type='radio' name='sale_flag' value='set'>Установить</label><br>
			<label><input type='radio' name='sale_flag' value='unset'>Снять</label>
			</fieldset></td>
			<td width='25%'><fieldset><legend>Не отображать на витрине</legend>
			<label><input type='radio' name='hidden_flag' value='' checked>Не менять</label><br>
			<label><input type='radio' name='hidden_flag' value='set'>Установить</label><br>
			<label><input type='radio' name='hidden_flag' value='unset'>Снять</label>
			</fieldset></td>
			<td width='25%'><fieldset><legend>Не экспортировать в YML</legend>
			<label><input type='radio' name='yml_flag' value='' checked>Не менять</label><br>
			<label><input type='radio' name='yml_flag' value='set'>Установить</label><br>
			<label><input type='radio' name='yml_flag' value='unset'>Снять</label>
			</fieldset></td>
			<td width='25%'><fieldset><legend>Переместить в группу</legend>
			" . selectGroupPos('to_group', 0, false, '', '', @$CONFIG['store']['leaf_only']) . "
			</fieldset></td>
			</table>
			<br><button type='submit'>Выполнить</button>
			</div>");
			$lim = 5000000;
			$vc_add.="<input type='checkbox' id='selall' onclick='return SelAll(this);'>";
		}

		switch (@$CONFIG['doc']['sklad_default_order']) {
			case 'vc': $order = '`doc_base`.`vc`';
				break;
			case 'cost': $order = '`doc_base`.`cost`';
				break;
			default: $order = '`doc_base`.`name`';
		}
                
                if(isset($CONFIG['store']['add_columns'])) {
                    $opts = array();
                    $e_options = explode(',', $CONFIG['store']['add_columns']);
                    foreach($e_options as $opt) {
                        $opts[$opt] = 1;
                    }
                    if(isset($opts['bigpack'])) {
                        $res = $db->query("SELECT `id` FROM `doc_base_params` WHERE `codename`='bigpack_cnt'");
                        if (!$res->num_rows) {
                            $db->query("INSERT INTO `doc_base_params` (`name`, `codename`, `type`, `hidden`)"
                                . " VALUES ('Кол-во в большой упаковке', 'bigpack_cnt', 'int', 0)");
                            throw new \Exception("Параметр *bigpack_cnt - кол-во в большой упаковке* не найден. Параметр создан.");
                        }
                        list($p_bp_id) = $res->fetch_row();
                        $fields_sql .= ", `bp_t`.`value` AS `bigpack_cnt`";
                        $join_sql .= " LEFT JOIN `doc_base_values` AS `bp_t` ON `bp_t`.`id`=`doc_base`.`id` AND `bp_t`.`param_id`='$p_bp_id'";
                    }
                    if(isset($opts['bulkcnt'])) {
                        $fields_sql .= ", `doc_base`.`bulkcnt`";
                    }
                    if(isset($opts['mult'])) {
                        $fields_sql .= ", `doc_base`.`mult`";
                    }
                }

		$sql = "SELECT `doc_base`.`id`,`doc_base`.`group`,`doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`likvid`, `doc_base`.`vc`,
                        `doc_base`.`cost` AS `base_price`, `doc_base`.`bulkcnt`, `doc_base`.`cost_date`, `doc_base`.`mass`, `doc_base`.`hidden`, 
                        `doc_base`.`no_export_yml`, `doc_base`.`stock`,
                    `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`,                   
                        `doc_base_dop`.`reserve`, `doc_base_dop`.`transit`, `doc_base_dop`.`offer`,
                    `doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt`,
                    (SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `allcnt` $fields_sql
		FROM `doc_base`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$sklad'
                $join_sql
		WHERE `doc_base`.`group`='$group' ";
		if($_SESSION['sklad_store_only'])
			$sql .= " AND `doc_base_cnt`.`cnt`>0 ";
		$sql.=" ORDER BY $order";

		$page = rcvint('p');
		$res = $db->query($sql);
		$row = $res->num_rows;
		$pagebar = '';
		if ($row > $lim) {
			$dop = "g=$group";
			if ($page < 1)
				$page = 1;
			if ($page > 1) {
				$i = $page - 1;
				$pagebar.="<a href='' onclick=\"EditThis('/docs.php?l=sklad&amp;mode=srv&amp;opt=pl&amp;$dop&amp;p=$i','sklad'); return false;\">&lt;&lt;</a> ";
			}
			else
				$pagebar.='<span>&lt;&lt;</span>';
			$cp = $row / $lim;
			for ($i = 1; $i < ($cp + 1); $i++) {
				if ($i == $page)
					$pagebar.=" <b>$i</b> ";
				else
					$pagebar.="<a href='' onclick=\"EditThis('/docs.php?l=sklad&amp;mode=srv&amp;opt=pl&amp;$dop&amp;p=$i','sklad'); return false;\">$i</a> ";
			}
			if ($page < $cp) {
				$i = $page + 1;
				$pagebar.="<a href='' onclick=\"EditThis('/docs.php?l=sklad&amp;mode=srv&amp;opt=pl&amp;$dop&amp;p=$i','sklad'); return false;\">&gt;&gt;</a> ";
			}
			else
				$pagebar.='<span>&gt;&gt;</span>';
			$sl = ($page - 1) * $lim;
			$pagebar.='<br>';
			$res->data_seek($sl);
		}
		else	$sl = 0;

		if ($row) {
                    $tmpl->addContent("$pagebar<table width='100%' cellspacing='1' cellpadding='2' class='list'>
                        <tr><th>№</th>");
                    if ($CONFIG['poseditor']['vc']) {
                        $tmpl->addContent('<th>Код</th>');
                    }
                    $tmpl->addContent("<th>Наименование</th><th>Производитель</th><th>Цена, р.</th><th>Ликв.</th><th>АЦП, р.</th>");
                    if($_SESSION['sklad_cost'] > 0) {
                        $tmpl->addContent('<th>Выб. цена</th>');
                    }
                    if($CONFIG['poseditor']['tdb']) {
                        $tmpl->addContent('<th>Тип<th>d</th><th>D</th><th>B</th>');
                    }
                    $tmpl->addContent('<th>Масса</th>');
                    if(isset($CONFIG['store']['add_columns'])) {
                        $e_options = explode(',', $CONFIG['store']['add_columns']);
                        foreach($e_options as $opt) {
                            switch ($opt) {
                                case 'mult':
                                    $tmpl->addContent('<th>В упаковке</th>');
                                    break;
                                case 'bigpack':
                                    $tmpl->addContent('<th>В б.уп.</th>');
                                    break;
                                case 'bulkcnt':
                                    $tmpl->addContent('<th>Опт от</th>');
                                    break;
                            }
                        }
                    }
                    
                    if($CONFIG['poseditor']['rto']) {
                        $tmpl->addContent("<th><img src='/img/i_lock.png' alt='В резерве'></th><th><img src='/img/i_alert.png' alt='Под заказ'></th><th><img src='/img/i_truck.png' alt='В пути'></th>");
                    }
                    $tmpl->addContent("<th>Склад</th><th>Всего</th><th>Место</th></tr>");
                    $tmpl->addContent("<tr class='lin0'><th colspan='20' align='center'>В группе $row наименований, показаны " . ( ($sl + $lim) < $row ? $lim : ($row - $sl) ) . ", начиная с $sl");
                    $i = 0;
                    $this->DrawSkladTable($res, $s, $lim, $e_options);
                    $tmpl->addContent("</table>$pagebar");
                    if ($go) {
                            $tmpl->addContent("<b>Легенда:</b> Заполненность дополнительных свойств наименования: <b><span style='color: #f00;'>&lt;40%</span>, <span style='color: #f80;'>&lt;60%</span>, <span style='color: #00C;'>&lt;90%</span>, <span style='color: #0C0;'>&gt;90%</span>,</b>");
                    }
		}
		else if($group)
			$tmpl->msg("В выбранной группе товаров не найдено!");
		else	$tmpl->msg("Выберите нужную группу в левом меню");
		if($go) $tmpl->addContent("</form>");
		else {
			$tmpl->addContent("<a href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;pos=0&amp;g=$group'><img src='/img/i_add.png' alt=''> Добавить</a>");
			if ($group)
				$tmpl->addContent(" | <a href='/docs.php?l=sklad&amp;mode=edit&amp;param=g&amp;g=$group'><img src='/img/i_edit.png' alt=''> Правка группы</a>");
			$tmpl->addContent(" | <a href='/docs.php?l=sklad&amp;mode=search'><img src='/img/i_find.png' alt=''> Расширенный поиск</a>");
		}
	}

    /// Отображает результаты поиска товаров по наименованию
    /// Отображает список товаров группы, разбитый на страницы
    /// @param $s подстрока поиска
    function ViewSkladS($s) {
        global $tmpl, $CONFIG, $db;
        $sf = 0;
        $html_s = html_out($s);
        $found_ids = '0';   // Для NOT IN
        $sklad = $_SESSION['sklad_num']; /// TODO: убрать отсюда в конструктор или ещё куда-нибудь
        $tmpl->addContent("<b>Показаны наименования изо всех групп!</b><br>");
        $tmpl->addContent("<table width='100%' cellspacing='1' cellpadding='2' class='list'>
            <tr><th>№</th>");
        if ($CONFIG['poseditor']['vc']) {
            $tmpl->addContent('<th>Код</th>');
        }
        $tmpl->addContent("<th>Наименование</th><th>Производитель</th><th>Цена, р.</th><th>Ликв.</th><th>АЦП, р.</th>");
        if($_SESSION['sklad_cost'] > 0) {
            $tmpl->addContent('<th>Выб. цена</th>');
        }
        if($CONFIG['poseditor']['tdb']) {
            $tmpl->addContent('<th>Тип<th>d</th><th>D</th><th>B</th>');
        }
        $tmpl->addContent('<th>Масса</th>');
        if(isset($CONFIG['store']['add_columns'])) {
            $e_options = explode(',', $CONFIG['store']['add_columns']);
            foreach($e_options as $opt) {
                switch ($opt) {
                    case 'mult':
                        $tmpl->addContent('<th>В упаковке</th>');
                        break;
                    case 'bigpack':
                        $tmpl->addContent('<th>В б.уп.</th>');
                        break;
                    case 'bulkcnt':
                        $tmpl->addContent('<th>Опт от</th>');
                        break;
                }
            }
        }

        if($CONFIG['poseditor']['rto']) {
            $tmpl->addContent("<th><img src='/img/i_lock.png' alt='В резерве'></th><th><img src='/img/i_alert.png' alt='Под заказ'></th><th><img src='/img/i_truck.png' alt='В пути'></th>");
        }
        $tmpl->addContent("<th>Склад</th><th>Всего</th><th>Место</th></tr>");

        switch (@$CONFIG['doc']['sklad_default_order']) {
            case 'vc': $order = '`doc_base`.`vc`';
                break;
            case 'cost': $order = '`doc_base`.`cost`';
                break;
            default: $order = '`doc_base`.`name`';
        }
        $fields_sql = $join_sql = '';
        if(isset($CONFIG['store']['add_columns'])) {
            $opts = array();
            $e_options = explode(',', $CONFIG['store']['add_columns']);
            foreach($e_options as $opt) {
                $opts[$opt] = 1;
            }
            if(isset($opts['bigpack'])) {
                $res = $db->query("SELECT `id` FROM `doc_base_params` WHERE `codename`='bigpack_cnt'");
                if (!$res->num_rows) {
                    $db->query("INSERT INTO `doc_base_params` (`name`, `codename`, `type`, `hidden`)"
                        . " VALUES ('Кол-во в большой упаковке', 'bigpack_cnt', 'int', 0)");
                    throw new \Exception("Параметр *bigpack_cnt - кол-во в большой упаковке* не найден. Параметр создан.");
                }
                list($p_bp_id) = $res->fetch_row();
                $fields_sql .= ", `bp_t`.`value` AS `bigpack_cnt`";
                $join_sql .= " LEFT JOIN `doc_base_values` AS `bp_t` ON `bp_t`.`id`=`doc_base`.`id` AND `bp_t`.`param_id`='$p_bp_id'";
            }
            if(isset($opts['bulkcnt'])) {
                $fields_sql .= ", `doc_base`.`bulkcnt`";
            }
            if(isset($opts['mult'])) {
                $fields_sql .= ", `doc_base`.`mult`";
            }
        }

        $s_sql = $db->real_escape_string($s);
        $limit = 100;
        $sql = "SELECT SQL_CALC_FOUND_ROWS `doc_base`.`id`, `doc_base`.`group`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`likvid`,
                    `doc_base`.`cost` AS `base_price`, `doc_base`.`bulkcnt`, `doc_base`.`analog_group`, `doc_base`.`mass`, `doc_base`.`hidden`, 
                    `doc_base`.`vc`, `doc_base`.`no_export_yml`, `doc_base`.`stock`, `doc_base`.`cost_date`,
                `doc_base_dop`.`type`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`,
                    `doc_base_dop`.`reserve`, `doc_base_dop`.`transit`, `doc_base_dop`.`offer`,                    
                `doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt`,
                (SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `allcnt` $fields_sql
            FROM `doc_base`
            LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
            LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$sklad' " . $join_sql;
        $where_add = '';
        if ($_SESSION['sklad_store_only']) {
            $where_add .= " AND `doc_base_cnt`.`cnt`>0 ";
        }

        $sqla = $sql . "WHERE (`doc_base`.`name` = '$s_sql' OR `doc_base`.`vc` = '$s_sql') $where_add  ORDER BY $order";
        $ores = $db->query($sqla);
        if ($ores->num_rows) {
            $tmpl->addContent("<tr><th colspan='20' align='center'>Поиск совпадений с $html_s - {$ores->num_rows} строк найдено</th></tr>");
            $groups_analog_list = '';
            while($line = $ores->fetch_assoc()) {
                $tmpl->addContent( $this->drawTableLine($line, $s, $e_options) );
                $found_ids.=','.$line['id'];
                if($line['analog_group']) {
                    if($groups_analog_list) {
                        $groups_analog_list.=',';
                    }
                    $groups_analog_list.="'".$db->real_escape_string($line['analog_group'])."'";
                }
            }
            if($groups_analog_list) {
                $sqla = $sql . "WHERE `doc_base`.`id` NOT IN ($found_ids) AND `doc_base`.`analog_group` IN ($groups_analog_list) $where_add ORDER BY $order";
                $res = $db->query($sqla);
                if ($res->num_rows) {
                    $tmpl->addContent("<tr><th colspan='20' align='center'>Поиск аналогов $html_s - {$res->num_rows} строк найдено</th></tr>");
                    $groups_analog_list = '';
                    while($line = $res->fetch_assoc()) {
                        $tmpl->addContent( $this->drawTableLine($line, $s, $e_options) );
                        $found_ids.=','.$line['id'];
                    }
                }
            }
            $sf = 1;
        }
        
        $sqla = $sql . "WHERE (`doc_base`.`name` LIKE '$s_sql%' OR `doc_base`.`vc` LIKE '$s_sql%') AND `doc_base`.`id` NOT IN ($found_ids) $where_add"
            . "ORDER BY $order LIMIT $limit";
        $res = $db->query($sqla);
        if ($cnt = $res->num_rows) {
            $rows_res = $db->query("SELECT FOUND_ROWS()");
            list($found_cnt) = $rows_res->fetch_row();
            $tmpl->addContent("<tr><th colspan='20' align='center'>Поиск по названию, начинающемуся на $html_s - показано $cnt из $found_cnt</th></tr>");
            $groups_analog_list = '';
            while($line = $res->fetch_assoc()) {
                $tmpl->addContent( $this->drawTableLine($line, $s, $e_options) );
                $found_ids.=','.$line['id'];
                if($line['analog_group']) {
                    if($groups_analog_list) {
                        $groups_analog_list.=',';
                    }
                    $groups_analog_list.="'".$db->real_escape_string($line['analog_group'])."'";
                }
            }
            if($groups_analog_list) {
                $sqla = $sql . "WHERE `doc_base`.`id` NOT IN ($found_ids) AND `doc_base`.`analog_group` IN ($groups_analog_list) $where_add"
                        . "ORDER BY $order LIMIT $limit";
                $res = $db->query($sqla);

                if ($cnt = $res->num_rows) {
                    $rows_res = $db->query("SELECT FOUND_ROWS()");
                    list($found_cnt) = $rows_res->fetch_row();
                    $tmpl->addContent("<tr><th colspan='20' align='center'>Поиск аналогов для предыдущего блока - показано $cnt из $found_cnt</th></tr>");

                    $groups_analog_list = '';
                    while($line = $res->fetch_assoc()) {
                        $tmpl->addContent( $this->drawTableLine($line, $s, $e_options) );
                        $found_ids.=','.$line['id'];
                    }
                }
            }
            $sf = 1;
        }
        
        $sqla = $sql . "WHERE (`doc_base`.`name` LIKE '%$s_sql%' OR `doc_base`.`vc` LIKE '%$s_sql%') AND `doc_base`.`id` NOT IN ($found_ids) $where_add"
            . "ORDER BY $order LIMIT 50";
        $res = $db->query($sqla);
        if ($cnt = $res->num_rows) {
            $rows_res = $db->query("SELECT FOUND_ROWS()");
            list($found_cnt) = $rows_res->fetch_row();
            $tmpl->addContent("<tr><th colspan='20' align='center'>Поиск по вхождению $html_s - показано $cnt из $found_cnt</th></tr>");
            while($line = $res->fetch_assoc()) {
                $tmpl->addContent( $this->drawTableLine($line, $s, $e_options) );
            }
        }

        if ($sf == 0)
            $tmpl->msg("По данным критериям товаров не найдено!");
    }

    /// Поиск товаров по параметрам
	function Search() {
		global $tmpl, $CONFIG, $db;
		$opt = request("opt");
		$name = request('name');
		$analog = request('analog');
		$desc = request('desc');
		$proizv = request('proizv');
		$mesto = request('mesto');
		$di_min = rcvrounded('di_min', 3);
		$di_max = rcvrounded('di_max', 3);
		$de_min = rcvrounded('de_min', 3);
		$de_max = rcvrounded('de_max', 3);
		$size_min = rcvrounded('size_min', 3);
		$size_max = rcvrounded('size_max', 3);
		$m_min = rcvrounded('m_min', 3);
		$m_max = rcvrounded('m_max', 3);
		$cost_min = rcvrounded('cost_min', 2);
		$cost_max = rcvrounded('cost_max', 2);
		$li_min = rcvrounded('li_min', 3);
		$li_max = rcvrounded('li_max', 3);
		$type = request('type');

		if ($opt == '' || $opt == 's') {
			doc_menu();
			$analog_checked = $analog ? 'checked' : '';
			$desc_checked = $desc ? 'checked' : '';
			$tmpl->addContent("<h1>Расширенный поиск</h1>
			<form action='docs.php' method='post'>
			<input type='hidden' name='mode' value='search'>
			<input type='hidden' name='opt' value='s'>
			<table width='100%'>
			<tr><th>Наименование</th><th>Ликвидность</th><th>Производитель</th><th>Тип</th><th>Место на складе</th></tr>
			<tr>
			<td><input type='text' name='name' value='".html_out($name)."'><br><label><input type='checkbox' name='analog' value='1' $analog_checked>Или аналог</label> <label><input type='checkbox' name='desc' value='1' $desc_checked>Или описание</label>
			<td>От: <input type='text' name='li_min' value='$li_min'><br>до: <input type='text' name='li_max' value='$li_max'>
			<td><input type='text' id='proizv' name='proizv' value='".html_out($proizv)."' onkeydown=\"return AutoFill('/docs.php?mode=search&amp;opt=pop_proizv','proizv','proizv_p')\"><br>
			<div id='proizv_p' class='dd'></div>
			<td><select name='type' id='pos_type'>");
			$res = $db->query("SELECT `id`, `name` FROM `doc_base_dop_type` ORDER BY `id`");
			$tmpl->addContent("<option value='null'>--не выбрано--</option>");
			while ($nx = $res->fetch_row()) {
				$ii = ($nx[0] === $type)?' selected':'';
				$tmpl->addContent("<option value='$nx[0]'$ii>".html_out($nx[0].' - '.$nx[1])."</option>");
			}

			$tmpl->addContent("</select>
			<td><input type='text' name='mesto' value='".html_out($mesto)."'>
			<tr><th>Внутренний диаметр</th><th>Внешний диаметр</th><th>Высота</th><th>Масса</th><th>Цена</th></tr>
			<tr>
			<td>От: <input type='text' name='di_min' value='$di_min'><br>до: <input type='text' name='di_max' value='$di_max'>
			<td>От: <input type='text' name='de_min' value='$de_min'><br>до: <input type='text' name='de_max' value='$de_max'>
			<td>От: <input type='text' name='size_min' value='$size_min'><br>до: <input type='text' name='size_max' value='$size_max'>
			<td>От: <input type='text' name='m_min' value='$m_min'><br>до: <input type='text' name='m_max' value='$m_max'>
			<td>От: <input type='text' name='cost_min' value='$cost_min'><br>до: <input type='text' name='cost_max' value='$cost_max'>
			</tr>
			<tr><td colspan='5' align='center'><input type='submit' value='Найти'></td></tr></table>
			</form>");
		}
		if ($opt == 'pop_proizv') {
			$tmpl->ajax = 1;
			$s = request('s');
			$s_sql = $db->real_escape_string($s);
			$res = $db->query("SELECT `proizv` FROM `doc_base` WHERE LOWER(`proizv`) LIKE LOWER('%$s_sql%') GROUP BY `proizv`  ORDER BY `proizv`LIMIT 20");
			$row = $res->num_rows;
			$tmpl->setContent("<div class='pointer' onclick=\"return AutoFillClick('proizv','','proizv_p');\">-- Убрать --</div>");
			while ($nxt = $res->fetch_row()) {
				$i = 1;
				$tmpl->addContent("<div class='pointer' onclick=\"return AutoFillClick('proizv','".html_out($nxt[0])."','proizv_p');\">".html_out($nxt[0])."</div>");
			}
			if (!$i)$tmpl->addContent("<b>Искомая комбинация не найдена!");
		}
		else if ($opt == 's') {
			$tmpl->addContent("<h1>Результаты</h1>");
			$sklad = $_SESSION['sklad_num'];
			settype($sklad, 'int');
			$sql = "SELECT `doc_base`.`id`, `doc_base`.`group`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`likvid`,
				`doc_base`.`cost` AS `base_price`, `doc_base`.`bulkcnt`,
				`doc_base`.`cost_date`,	`doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`,
				`doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base`.`mass`, `doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt`,
				(SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `allcnt`,
				`doc_base`.`vc`, `doc_base`.`hidden`, `doc_base`.`no_export_yml`, `doc_base`.`stock`
				FROM `doc_base`
				LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$sklad'
				LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
				WHERE 1 ";

			switch (@$CONFIG['doc']['sklad_default_order']) {
				case 'vc': $order = '`doc_base`.`vc`';
					break;
				case 'cost': $order = '`doc_base`.`cost`';
					break;
				default: $order = '`doc_base`.`name`';
			}

			if ($name) {
				if (!$analog && !$desc)
					$sql.="AND `doc_base`.`name` LIKE '%$name%'";
				else {
					$s = "`doc_base`.`name` LIKE '%$name%'";
					if ($analog)
						$s.=" OR `doc_base_dop`.`analog` LIKE '%$name%'";
					if ($desc)
						$s.=" OR `doc_base`.`desc` LIKE '%$name%'";
					$sql.="AND ( $s )";
				}
			}
			if ($proizv)	$sql.="AND `doc_base`.`proizv` LIKE '%".$db->real_escape_string($proizv)."%'";
			if ($mesto)	$sql.="AND `doc_base_cnt`.`mesto` LIKE '".$db->real_escape_string($mesto)."'";
			if ($di_min)	$sql.="AND `doc_base_dop`.`d_int` >= '$di_min'";
			if ($di_max)	$sql.="AND `doc_base_dop`.`d_int` <= '$di_max'";
			if ($li_min)	$sql.="AND `doc_base`.`likvid` >= '$li_min'";
			if ($li_max)	$sql.="AND `doc_base`.`likvid` <= '$li_max'";
			if ($de_min)	$sql.="AND `doc_base_dop`.`d_ext` >= '$de_min'";
			if ($de_max)	$sql.="AND `doc_base_dop`.`d_ext` <= '$de_max'";
			if ($size_min)	$sql.="AND `doc_base_dop`.`size` >= '$size_min'";
			if ($size_max)	$sql.="AND `doc_base_dop`.`size` <= '$size_max'";
			if ($m_min)	$sql.="AND `doc_base`.`mass` >= '$m_min'";
			if ($m_max)	$sql.="AND `doc_base`.`mass` <= '$m_max'";
			if ($cost_min)	$sql.="AND `doc_base`.`cost` >= '$cost_min'";
			if ($cost_max)	$sql.="AND `doc_base`.`cost` <= '$cost_max'";
			if ($type != 'null')	$sql.="AND `doc_base_dop`.`type` = '$type'";

			$sql.="ORDER BY $order";

			$cheader_add = ($_SESSION['sklad_cost'] > 0) ? '<th>Выб. цена' : '';
			$tmpl->addContent("<table width='100%' cellspacing='1' cellpadding='2' class='list'>
			<tr><th>№</th>");
			
			if(@$CONFIG['poseditor']['vc'])
				$tmpl->addContent("<th>Код</th>");
			$tmpl->addContent("<th>Наименование</th><th>Производитель</th><th>Цена, р.</th><th>Ликв.</th><th>АЦП, р.</th>$cheader_add<th>Аналог</th>");
			
			if (@$CONFIG['poseditor']['tdb'] == 1)
				$tmpl->addContent("<th>Тип</th><th>d</th><th>D</th><th>B</th>");
			$tmpl->addContent("<th>Масса</th>");
			if(@$CONFIG['poseditor']['rto'])
				$tmpl->addContent("<th><img src='/img/i_lock.png' alt='В резерве'></th><th><img src='/img/i_alert.png' alt='Под заказ'></th><th><img src='/img/i_truck.png' alt='В пути'></th>");
			$tmpl->addContent("<th>Склад</th><th>Всего</th><th>Место</th></tr>");

			$res = $db->query($sql);
			if ($cnt = $res->num_rows) {
				$tmpl->addContent("<tr><th colspan='16' align='center'>Параметрический поиск, найдено $cnt");
				$this->DrawSkladTable($res, $name);
				$sf = 1;
			}
			$tmpl->addContent("</table>");
		}
	}

        function makeContextMenuLink($pos_id, $opt, $value, $title = 'Отобразить документы') {
            return "<a onclick=\"ShowPopupWin('/docs.php?l=inf&mode=srv&pos=$pos_id&opt=$opt'); return false;\""
                . " title='$title' href='/docs.php?l=inf&mode=srv&pos=$pos_id&opt=$opt'>$value</a>";
        }
        
    function drawTableLine($line, $s='', $opts = array()) {
        global $CONFIG;
        $go = request('go');
        if (@$CONFIG['poseditor']['rto']) {
            $reserve = $line['reserve'] ? $this->makeContextMenuLink($line['id'], 'rezerv', $line['reserve']) : '';
            $pod_zakaz = $line['offer'] ? $this->makeContextMenuLink($line['id'], 'p_zak', $line['offer']) : '';
            $v_puti = $line['transit'] ? $this->makeContextMenuLink($line['id'], 'vputi', $line['transit']) : '';
            $rto_add = "<td>$reserve</td><td>$pod_zakaz</td><td>$v_puti</td>";
        } else {
            $rto_add = '';
        }
        $line['allcnt'] = $line['allcnt']!=0 ? $this->makeContextMenuLink($line['id'], 'ost', $line['allcnt'], 'Отобразить все остатки') : '';
        if ($line['cnt']==0) {
            $line['cnt'] = '';
        }
        if(!$line['mass']) {
            $line['mass'] = '';
        }

        $dcc = strtotime($line['cost_date']);
        $price_class = "";
        if ($dcc > (time() - 60 * 60 * 24 * 30 * 3)) {
            $price_class = " class=f_green";
        } else if ($dcc > (time() - 60 * 60 * 24 * 30 * 6)) {
            $price_class = " class=f_purple";
        } else if ($dcc > (time() - 60 * 60 * 24 * 30 * 9)) {
            $price_class = " class=f_brown";
        } else if ($dcc > (time() - 60 * 60 * 24 * 30 * 12)) {
            $price_class = " class=f_more";
        }

        $info = '';
        if ($line['hidden']) {
            $info.='H';
        }
        if ($line['no_export_yml']) {
            $info.='Y';
        }
        if ($line['stock']) {
            $info.='S';
        }
        if ($info) {
            $info = "&nbsp;<span style='color: #f00; font-weight: bold'>$info</span>";
        }

        $name = SearchHilight(html_out($line['name']), $s);

        $price_p = sprintf("%0.2f", $line['base_price']);
        $in_price = sprintf("%0.2f", getInCost($line['id']));

        $vc_add = @$CONFIG['poseditor']['vc'] ? "<td align='left'>".SearchHilight(html_out($line['vc']), $s)."</td>" : '';

        if (@$CONFIG['poseditor']['tdb']) {
            $tdb_add = "<td>{$line['type']}</td><td>{$line['d_int']}</td><td>{$line['d_ext']}</td><td>{$line['size']}</td>";
        } else {
            $tdb_add = '';
        }

        $cb = $go ? "<input type='checkbox' name='pos[{$line['id']}]' class='pos_ch' value='1'>" : '';
        if ($_SESSION['sklad_cost'] > 0) {
            $pc = PriceCalc::getInstance();
            $cadd = '<td><b>' . $pc->getPosSelectedPriceValue($line['id'], $_SESSION['sklad_cost'], $line) . '</b></td>';
        } else {
            $cadd = '';
        }
        $opts_add = '';
        foreach($opts as $opt) {
            switch ($opt) {
                case 'mult':
                case 'bulkcnt':
                    $opts_add .= '<td align="right">'.$line[$opt].'</td>';
                    break;
                case 'bigpack':
                    $opts_add .= '<td align="right">'.$line['bigpack_cnt'].'</td>';
                    break;

            }
        }

        return "<tr class='pointer' oncontextmenu=\"ShowPosContextMenu(event, {$line['id']}, ''); return false;\"  align='right'><td>{$cb}"
            . "<a href='/docs.php?mode=srv&amp;opt=ep&amp;pos={$line['id']}'>{$line['id']}</a>"
            . "<a href='#' onclick=\"ShowPosContextMenu(event, {$line['id']}, ''); return false;\" title='Меню'>"
            . "<img src='img/i_menu.png' alt='Меню' border='0'></a></td>"
            . "$vc_add<td align='left'>$name $info</td><td align='left'>{$line['proizv']}</td><td{$price_class}>$price_p</td><td>{$line['likvid']}</td>"
            . "<td>$in_price</td>{$cadd}$tdb_add<td>{$line['mass']}</td>{$opts_add}{$rto_add}<td>{$line['cnt']}</td><td>{$line['allcnt']}</td><td>{$line['mesto']}</td></tr>";
    }
        
    /// Отображает таблицу товаров
    /// @param res Результат выполнения sql запроса к таблице товаров
    /// @param s Подстрока поиска
    /// @param lim Максимальное количество строк
    function DrawSkladTable($res, $s = '', $lim = 1000, $opts = array()) {
        global $tmpl;
        $i = 0;
        while ($nxt = $res->fetch_assoc()) {
            $tmpl->addContent($this->drawTableLine($nxt, $s, $opts));
            $i++;
            if ($i > $lim) {
                break;
            }
        }
    }

    /// Вывод списка комплектующих товара
	/// @param pos ID запрашиваемого товара
	function ViewKomplList($pos) {
		global $tmpl, $db;
		$tmpl->addContent("<table width='100%' class='list'>
		<tr><th>N</th><th>ID</th><th>Наименование</th><th>Цена (базовая)</th><th>Кол-во</th><th>Стоимость</th></tr>");
		$res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`cost`, `doc_base_kompl`.`cnt`  FROM `doc_base_kompl`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_base_kompl`.`kompl_id`
		WHERE `doc_base_kompl`.`pos_id`='$pos'");
		$i = $sum_p = 0;
		while ($nxt = $res->fetch_row()) {
			$i++;
			$sum_p+=$sum = $nxt[2] * $nxt[3];
			$tmpl->addContent("<tr><td>$i</td><td>$nxt[0]</td><td>".html_out($nxt[1])."</td><td>$nxt[2]</td><td>$nxt[3]</td><td>$sum</td></tr>");
		}
		$tmpl->addContent("</table><p align='right' id='sum'>Итого для сборки позиции используется $i позиций на сумму $sum_p руб.</p>");
	}

	/// Отображает вкладки редактора товара
	/// @param pos ID запрашиваемого товара
	/// @param param Код открытой вкладки
	/// @param pos_name Наименование запрашиваемого товара
	function PosMenu($pos, $param) {
		global $tmpl, $CONFIG, $db;
		settype($pos, 'int');
		$sel = array('v' => '', 'd' => '', 'a' => '', 's' => '', 'i' => '', 'c' => '', 'k' => '', 'l' => '', 'n'=>'', 'h' => '', 'y' => '');
		if ($param == '')
			$param = 'v';
		$sel[$param] = "class='selected'";

		if ($CONFIG['poseditor']['vc'])
			$res = $db->query("SELECT CONCAT(`doc_base`.`vc`, ' - ', `doc_base`.`name`) FROM `doc_base` WHERE `doc_base`.`id`='$pos'");
		else
			$res = $db->query("SELECT `doc_base`.`name` FROM `doc_base` WHERE `doc_base`.`id`='$pos'");
		$pos_info = $res->fetch_row();
		if ($pos_info) {
			$tmpl->setTitle("Редактируем ".html_out($pos_info[0]));
			$tmpl->addContent("<h1>Редактируем ".html_out($pos_info[0])."</h1>");
		}

		$tmpl->addContent("
		<ul class='tabs'>
		<li><a {$sel['v']} href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;pos=$pos'>Основные</a></li>
		<li><a {$sel['d']} href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=d&amp;pos=$pos'>Дополнительные</a></li>
		<li><a {$sel['s']} href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=s&amp;pos=$pos'>Склады</a></li>
		<li><a {$sel['i']} href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=i&amp;pos=$pos'>Картинки и файлы</a></li>
		<li><a {$sel['c']} href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=c&amp;pos=$pos'>Цены</a></li>		
		<li><a {$sel['l']} href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=l&amp;pos=$pos'>Связи</a></li>
		<li><a {$sel['n']} href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=n&amp;pos=$pos'>Аналоги</a></li>
		<li><a {$sel['k']} href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=k&amp;pos=$pos'>Комплектующие</a></li>
		<li><a {$sel['a']} href='/docs.php?l=pran&amp;mode=srv&amp;opt=ep&amp;param=a&amp;pos=$pos'>Анализатор</a></li>
		<li><a {$sel['h']} href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=h&amp;pos=$pos'>История</a></li>
		<li><a {$sel['y']} href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=y&amp;pos=$pos'>Импорт Я.Маркет</a></li>
		</ul>");
	}
        
    protected function dopDataEditForm($pos_id) {
        global $db, $tmpl;
        $pres = $db->query("SELECT `doc_base_dop`.`type`, `doc_base_dop`.`analog`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, 
            `doc_base_dop`.`size`, `doc_base_dop`.`ntd`, `doc_base`.`group` AS `group_id`
        FROM `doc_base`
        LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`='$pos_id'
        WHERE `doc_base`.`id`='$pos_id'");

        if($pres->num_rows) {
                $pos_info = $pres->fetch_assoc();
        }
        else {
            $pos_info = array();
            foreach ($this->dop_vars as $value) {
                $pos_info[$value] = '';
            }
        }
        
        // option для select типа
        $type_opt = "<option value='null'>--не задан--</option>";
        $res = $db->query("SELECT `id`, `name` FROM `doc_base_dop_type` ORDER BY `id`");
        while ($nx = $res->fetch_row()) {
            $ii = "";
            if ($nx[0] === $pos_info['type']) {
                $ii = " selected";
            }
            $type_opt .= "<option value='$nx[0]' $ii>" . html_out("$nx[0] - $nx[1]") . "</option>";
        }
        
        // Динамические свойства - записанные
        $dyn_table = '';
        $dpv_res = $db->query("SELECT `doc_base_params`.`id`, `doc_base_params`.`name`, `doc_base_values`.`value`
                , `class_unit`.`rus_name1` AS `unit_name`
            FROM `doc_base_values`
            LEFT JOIN `doc_base_params` ON `doc_base_params`.`id`=`doc_base_values`.`param_id`
            LEFT JOIN `class_unit` ON `doc_base_params`.`unit_id`=`class_unit`.`id`
            WHERE `doc_base_values`.`id`='$pos_id' AND `doc_base_params`.`hidden`=0 "
            . " AND ( `doc_base_params`.`group_id`=0 OR `doc_base_params`.`group_id` IS NULL)");
        while ($nx = $dpv_res->fetch_assoc()) {
            $dyn_table .= "<tr><td align='right'>".html_out($nx['name']).", ".html_out($nx['unit_name'])."</td>"
                . "<td><input type='text' name='par[{$nx['id']}]' value='".html_out($nx['value'])."'></td></tr>";
        }
        $g_res = $db->query("SELECT * FROM `doc_base_gparams` ORDER BY `name`");
        while ($g_info = $g_res->fetch_assoc()) {
            $add_table = '';
            $dpv_res = $db->query("SELECT `doc_base_params`.`id`, `doc_base_params`.`name`, `doc_base_values`.`value`
                , `class_unit`.`rus_name1` AS `unit_name`
                FROM `doc_base_values`
                LEFT JOIN `doc_base_params` ON `doc_base_params`.`id`=`doc_base_values`.`param_id`
                LEFT JOIN `class_unit` ON `doc_base_params`.`unit_id`=`class_unit`.`id`
                WHERE `doc_base_values`.`id`='$pos_id' AND `doc_base_params`.`hidden`=0 AND `doc_base_params`.`group_id`='{$g_info['id']}'");
            while ($nx = $dpv_res->fetch_assoc()) {
                $add_table .= "<tr><td align='right'>".html_out($nx['name']).", ".html_out($nx['unit_name'])."</td>"
                . "<td><input type='text' name='par[{$nx['id']}]' value='".html_out($nx['value'])."'></td></tr>";
            }
            if($add_table) {
                $dyn_table .= "<tr><th colspan='2'>".html_out($g_info['name'])."</th></tr>".$add_table; 
            }
        }
        $dyn_table .= "<tr><td colspan='2'</td></tr>";
        // Динамические свойства - от групп
        $gdp_res = $db->query("SELECT `doc_base_params`.`id`, `doc_base_params`.`name`, `class_unit`.`rus_name1` AS `unit_name`
            FROM `doc_base_params`
            LEFT JOIN `doc_group_params` ON `doc_group_params`.`param_id`=`doc_base_params`.`id`
            LEFT JOIN `class_unit` ON `doc_base_params`.`unit_id`=`class_unit`.`id`
            WHERE `doc_group_params`.`group_id`='{$pos_info['group_id']}' AND `doc_base_params`.`hidden`='0' 
                AND `doc_base_params`.`id` NOT IN ( SELECT `doc_base_values`.`param_id` FROM `doc_base_values` WHERE `doc_base_values`.`id`='$pos_id' )
            ORDER BY `doc_base_params`.`id`");
        while ($nx = $gdp_res->fetch_assoc()) {
            $dyn_table .= "<tr><td align='right'>".html_out($nx['name']).", ".html_out($nx['unit_name'])."</td>"
                . "<td><input type='text' name='par[{$nx['id']}]' value=''></td></tr>";
        }
        
        // добавление динамических свойств
        $dyn_foot = "<tr><td align='right'><select name='pp' id='fg_select'>";  
        $r = $db->query("SELECT `doc_base_params`.`id`,  `doc_base_params`.`name`,  `doc_base_params`.`codename`,  `doc_base_params`.`type`
                , `class_unit`.`rus_name1` AS `unit_name`
            FROM `doc_base_params` 
            LEFT JOIN `class_unit` ON `doc_base_params`.`unit_id`=`class_unit`.`id`
            WHERE `group_id` IS NULL ORDER BY `name`");
        while ($p = $r->fetch_assoc()) {
            $dyn_foot .= "<option value='{$p['id']}'>".html_out($p['name']).", ".html_out($p['unit_name'])." :{$p['type']}</option>";
        }
        $g_res = $db->query("SELECT * FROM `doc_base_gparams` ORDER BY `name`");
        while ($g_info = $g_res->fetch_assoc()) {
            $dyn_foot .= "<option style='color:#fff; background-color:#000' disabled>".html_out($g_info['name'])."</option>";
            $r = $db->query("SELECT `doc_base_params`.`id`,  `doc_base_params`.`name`,  `doc_base_params`.`codename`,  `doc_base_params`.`type`
                , `class_unit`.`rus_name1` AS `unit_name`
                FROM `doc_base_params` 
                LEFT JOIN `class_unit` ON `doc_base_params`.`unit_id`=`class_unit`.`id`
                WHERE `group_id`='{$g_info['id']}' ORDER BY `name`");
            while ($p = $r->fetch_assoc()) {
                $dyn_foot .= "<option value='{$p['id']}'>".html_out($p['name']).", ".html_out($p['unit_name'])." :{$p['type']}</option>";
            }
        }
        $dyn_foot .= "</select></td><td><input type='text' id='value_add'>&nbsp;<img src='/img/i_add.png' alt='' onclick='return addLine()'></td></tr></td></tr>";
        
        // Служебные (системные) свойства
        $srv_table = '';
        $dpv_res = $db->query("SELECT `doc_base_params`.`id`, `doc_base_params`.`name`, `doc_base_params`.`codename`, `doc_base_values`.`value`
                , `class_unit`.`rus_name1` AS `unit_name`
            FROM `doc_base_params`
            LEFT JOIN `class_unit` ON `doc_base_params`.`unit_id`=`class_unit`.`id`
            LEFT JOIN `doc_base_values` ON `doc_base_params`.`id`=`doc_base_values`.`param_id` AND `doc_base_values`.`id`='$pos_id'
            WHERE `doc_base_params`.`hidden`!=0");
        while ($nx = $dpv_res->fetch_assoc()) {
            $name = html_out($nx['name']).', '.html_out($nx['unit_name']).'<br><small>'.html_out($nx['codename']).'</small>';
            $srv_table .= "<tr><td align='right'>$name</td><td><input type='text' name='par[{$nx['id']}]' value='".html_out($nx['value'])."'></td></tr>";
        }
        
        $tmpl->addContent("
        <script type=\"text/javascript\">
        function rmLine(t) {
            var line=t.parentNode.parentNode;
            line.parentNode.removeChild(line);
        }
        function addLine() {
            var fgtab=document.getElementById('fg_table').tBodies[0];
            var sel=document.getElementById('fg_select');
            var newrow=fgtab.insertRow(fgtab.rows.length);
            var lineid=sel.value;
            var ctext = sel.selectedIndex !== -1 ? sel.options[sel.selectedIndex].text : '';
            var text=document.getElementById('value_add').value;
            newrow.innerHTML=\"<td align='right'>\"+ctext+\"</td><td><input type='text' name='par[\"+lineid+\"]' value='\"+text+\"'></td>\";
        }
        </script>");
        
        $tmpl->addContent("
        <form action='' method='post'>
        <input type='hidden' name='mode' value='esave'>
        <input type='hidden' name='l' value='sklad'>
        <input type='hidden' name='pos' value='$pos_id'>
        <input type='hidden' name='param' value='d'>
        <table width='100%'>
        <tr>
        <td valign='top' width='33%'>
        <table class='list' width='100%'>
        <tr><td align='right'>Тип</td><td><select name='type' id='pos_type' >$type_opt</select></td></tr>
        <tr><td align='right'>Внутренний диаметр, мм (d)</td><td><input type='text' name='d_int' value='{$pos_info['d_int']}' id='pos_d_int'></td></tr>
        <tr><td align='right'>Внешний диаметр, мм (D)</td><td><input type='text' name='d_ext' value='{$pos_info['d_ext']}' id='pos_d_ext'></td></tr>
        <tr><td align='right'>Высота, мм (B)</td><td><input type='text' name='size' value='{$pos_info['size']}' id='pos_size'></td></tr>
        <tr><td align='right'>Номер таможенной декларации</td><td><input type='text' name='ntd' value='{$pos_info['ntd']}'></td></tr>
        </table>
        </td>
        <td valign='top' width='33%'><table class='list' width='100%' id='fg_table'><tbody><tfoot>$dyn_foot</tfoot>$dyn_table</tbody></table></td>
        <td valign='top' width='33%'><table class='list' width='100%'>$srv_table</table></td>
        </table>
        <table width='100%'>
        <tr><td align='center'><input type='submit' value='Сохранить'>
        </table></form>");
    }
}

