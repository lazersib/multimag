<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2012, BlackLight, TND Team, http://tndproject.org
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

// Работа с товарами

class doc_s_Sklad
{
	function View()
	{
		global $tmpl,$CONFIG;
		doc_menu(0,0);
		if(!isAccess('list_sklad','view'))	throw new AccessException("");
		$sklad=round(@$_REQUEST['sklad']);
		if($sklad) $_SESSION['sklad_num']=$sklad;
		if(!isset($_SESSION['sklad_num'])) $_SESSION['sklad_num']=1;
		$sklad=$_SESSION['sklad_num'];

		$cost=round(@$_REQUEST['cost']);
		if($cost) $_SESSION['sklad_cost']=$cost;
		if(!isset($_SESSION['sklad_cost']))
		{
			if(@$CONFIG['stock']['default_cost']>0)	$_SESSION['sklad_cost']=$CONFIG['stock']['default_cost'];
			else $_SESSION['sklad_cost']=-1;
		}
		$cost=$_SESSION['sklad_cost'];

		$tmpl->AddText("
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
		<table width='100%'><tr><td width='300'><h1>Склад</h1>
		<td align='right'>
		<form action='' method='post'>
		<input type='hidden' name='l' value='sklad'>
		<select name='cost'>");
		$res=mysql_query("SELECT `id`, `name` FROM `doc_cost` ORDER BY `name`");
		$tmpl->AddText("<option value='-1'>-- не выбрано --</option>");
		while($nxt=mysql_fetch_row($res))
		{
			if($cost==$nxt[0]) $s=' selected'; else $s='';
			$tmpl->AddText("<option value='$nxt[0]' $s>$nxt[1]</option>");
		}
		$tmpl->AddText("</select>

		<select name='sklad'>");
		$res=mysql_query("SELECT `id`, `name` FROM `doc_sklady` ORDER BY `name`");

		while($nxt=mysql_fetch_row($res))
		{
			if($sklad==$nxt[0]) $s=' selected'; else $s='';
			$tmpl->AddText("<option value='$nxt[0]' $s>$nxt[1]</option>");
		}
		$tmpl->AddText("</select>
		<input type='submit' value='Выбрать'>
		</form></table>
		<table width='100%'><tr><td id='groups' width='200' valign='top' class='lin0'>");
		$this->draw_groups(0);
		$tmpl->AddText("<td id='sklad' valign='top'  class='lin1'>");
		$this->ViewSklad();
		$tmpl->AddText("</table>");
	}

	function Service()
	{
		global $tmpl, $CONFIG;
		$opt=rcv("opt");
		$g=rcv('g');
		if($opt=='pl')
		{
			$s=rcv('s');
			$tmpl->ajax=1;
			if($s)	$this->ViewSkladS($g,$s);
			else	$this->ViewSklad($g);
		}
		else if($opt=='ep')
		{
			$this->Edit();
		}
		else if($opt=='acost')
		{
			$pos=rcv('pos');
			$tmpl->ajax=1;
			$tmpl->AddText( GetInCost($pos) );
		}
		else if($opt=='ost')
		{
			$tmpl->ajax=1;
			$pos=rcv('pos');
			$res=mysql_query("SELECT `doc_sklady`.`name`, `doc_base_cnt`.`cnt` FROM `doc_base_cnt`
			LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_base_cnt`.`sklad`
			WHERE `doc_base_cnt`.`id`='$pos'");
			$tmpl->AddText("<table width='100%'><tr><th>Склад<th>Кол-во</tr>");
			while($nxt=mysql_fetch_row($res))
				$tmpl->AddText("<tr><td>$nxt[0]<td>$nxt[1]");
			$tmpl->AddText("</table>");
		}
		else if($opt=='menu')
		{
			$tmpl->ajax=1;
			$pos=rcv('pos');
			$dend=date("Y-m-d");
			$tmpl->AddText("
			<div onclick=\"ShowPopupWin('/docs.php?l=pran&mode=srv&opt=ceni&pos=$pos'); return false;\" >Где и по чём</div>
			<div onclick=\"window.open('/docj.php?mode=filter&opt=fsn&tov_id=$pos&tov_name=$pos&date_to=$dend')\">Товар в журнале</div>
			<div onclick=\"window.open('/docs.php?mode=srv&amp;opt=ep&amp;pos=$pos')\">Редактирование позиции</div>");
		}
		else if($opt=='ac')
		{
			$q=rcv('q');
			$i=0;
			$tmpl->ajax=1;
			$res=mysql_query("SELECT `id`, `name`, `proizv`, `vc` FROM `doc_base` WHERE LOWER(`name`) LIKE LOWER('%$q%') OR LOWER(`vc`) LIKE LOWER('%$q%') ORDER BY `name`");
			$row=mysql_numrows($res);
			while($nxt=mysql_fetch_row($res))
			{
				$i=1;
				if($CONFIG['poseditor']['vc'])	$nxt[1].='('.$nxt[3].')';
				$nxt[1]=unhtmlentities($nxt[1]);
				$tmpl->AddText("$nxt[1]|$nxt[0]|$nxt[2]|$nxt[3]\n");
			}
		}
		else if($opt=='acj')
		{
			try
			{
				$s=rcv('s');
				$i=0;
				$tmpl->ajax=1;
				$res=mysql_query("SELECT `id`, `name`, `proizv`, `vc` FROM `doc_base` WHERE LOWER(`name`) LIKE LOWER('%$s%') OR LOWER(`vc`) LIKE LOWER('%$s%') ORDER BY `name`");
				$row=mysql_numrows($res);
				$str='';
				while($nxt=mysql_fetch_row($res))
				{
					$i=1;
					if($CONFIG['poseditor']['vc'])	$nxt[1].='('.$nxt[3].')';
					$nxt[1]=unhtmlentities($nxt[1]);
					$tmpl->AddText("$nxt[1]|$nxt[0]|$nxt[2]|$nxt[3]\n");
					if($str)	$str.=",\n";
					$str.="{id:'$nxt[0]',name:'$nxt[1]',vendor:'$nxt[2]',vc:'$nxt[3]'}";
				}
				$tmpl->SetText("{response: 'data', content: [$str] }");
			}
			catch(Exception $e)
			{
				$tmpl->SetText("{response: 'err', message: 'Внутренняя ошибка'}");
			}
		}
		else if($opt=='acv')
		{
			$q=rcv('q');
			$i=0;
			$tmpl->ajax=1;
			$res=mysql_query("SELECT `id`, `name`, `proizv`, `vc` FROM `doc_base` WHERE LOWER(`vc`) LIKE LOWER('%$q%') ORDER BY `vc`");
			$row=mysql_numrows($res);
			while($nxt=mysql_fetch_row($res))
			{
				$i=1;
				$nxt[1]=unhtmlentities($nxt[1]);
				$tmpl->AddText("$nxt[3]|$nxt[0]|$nxt[2]|$nxt[1]\n");
			}
		}
		else if($opt=='acp')
		{
			$q=rcv('q');
			$i=0;
			$tmpl->ajax=1;
			$res=mysql_query("SELECT `id`, `proizv` FROM `doc_base` WHERE LOWER(`proizv`) LIKE LOWER('%$q%') GROUP BY `proizv` ORDER BY `proizv`");
			$row=mysql_numrows($res);
			while($nxt=mysql_fetch_row($res))
			{
				$i=1;
				$nxt[1]=unhtmlentities($nxt[1]);
				$tmpl->AddText("$nxt[1]|$nxt[0]\n");
			}
		}
		else if($opt=='go')
		{
			$to_group=rcv('to_group');
			doc_menu(0,0);
			$up_data=array();
			switch(@$_POST['sale_flag'])
			{
				case 'set':	$up_data[]="`stock`='1'";	break;
				case 'unset':	$up_data[]="`stock`='0'";	break;
			}
			switch(@$_POST['hidden_flag'])
			{
				case 'set':	$up_data[]="`hidden`='1'";	break;
				case 'unset':	$up_data[]="`hidden`='0'";	break;
			}
			switch(@$_POST['yml_flag'])
			{
				case 'set':	$up_data[]="`no_export_yml`='1'";	break;
				case 'unset':	$up_data[]="`no_export_yml`='0'";	break;
			}
			if($to_group>0)		$up_data[]="`group`='".((int)$to_group)."'";
			$up_query='';
			foreach($up_data as $line)
			{
				if($up_query)	$up_query.=", ";
				$up_query.=$line;
			}
			$pos=@$_POST['pos'];
			if($up_query)
			{
				if(is_array($pos))
				{
					$c=0;
					$a=0;
					foreach($pos as $id=>$value)
					{
						settype($id,'int');
						mysql_query("UPDATE `doc_base` SET $up_query WHERE `id`='$id'");
						if(mysql_errno())	throw new MysqlException("Не удалось обновить строку!");
						$c++;
						$a+=mysql_affected_rows();
					}
					$tmpl->msg("Успешно обновлено $a строк. ".($c-$a)." из $c выбранных строк остались неизменёнными.","ok");
				}
				else $tmpl->msg("Не выбраны позиции для обновления!",'err');
			}
			else $tmpl->msg("Не выбрано действие!",'err');
		}
		else $tmpl->msg("Неверный режим!");
	}

// Служебные функции класса
	function Edit()
	{
		global $tmpl, $CONFIG, $uid;
		doc_menu();
		$pos=rcv('pos');
		$param=rcv('param');
		$group=rcv('g');
		if(!isAccess('list_sklad','view'))	throw new AccessException("");
		if(($pos==0)&&($param!='g')) $param='';

		if($pos!=0)
		{
			$this->PosMenu($pos, $param);
		}

		if($param=='')
		{
			$res=mysql_query("SELECT `doc_base`.`group`, `doc_base`.`name`, `doc_base`.`desc`, `doc_base`.`proizv`, `doc_base`.`cost`, `doc_base`.`likvid`, `doc_img`.`id` AS `img_id`, `doc_img`.`type` AS `img_type`, `doc_base`.`pos_type`, `doc_base`.`hidden`, `doc_base`.`unit`, `doc_base`.`vc`, `doc_base`.`stock`, `doc_base`.`warranty`, `doc_base`.`warranty_type`, `doc_base`.`no_export_yml`, `doc_base`.`country`, `doc_base`.`title_tag`, `doc_base`.`meta_keywords`, `doc_base`.`meta_description`, `doc_base`.`cost_date`
			FROM `doc_base`
			LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id` AND `doc_base_img`.`default`='1'
			LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
			WHERE `doc_base`.`id`='$pos'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить информацию о наименовании");
			$nxt=@mysql_fetch_array($res);
			if(is_array($nxt))
			foreach($nxt as $id => $value)
			{
				$nxt[$id]=htmlentities($value,ENT_QUOTES,'UTF-8');
			}
			$cc='';
			if(!$nxt)
			{
				$tmpl->AddText("<h3>Новая позиция</h3>");
				$n="<label><input type='radio' name='pd[type]' value='0' checked>Товар</label><br>
				<label><input type='radio' name='pd[type]' value='1'>Услуга</label>";
			}
			else
			{
				if($nxt[8]) $n="<input type='hidden' name='pd[type]' value='1'>Услуга";
				else $n="<input type='hidden' name='pd[type]' value='0'>Товар";
			}
			if($nxt['img_id'])
			{
				include_once("include/imgresizer.php");
				$miniimg=new ImageProductor($nxt['img_id'],'p', $nxt['img_type']);
				$miniimg->SetY(320);
				$miniimg->SetX(240);
				$cc="<td rowspan='18' style='width: 250px;'><a href='{$CONFIG['site']['var_data_web']}/pos/$nxt[6].$nxt[7]'><img src='".$miniimg->GetURI()."' alt='{$nxt['name']}'></a></td>";
			}

			$i='';
			$act_cost=sprintf('%0.2f',GetInCost($pos));
			if($pos!=0)	$selected=$nxt['group'];
			else		$selected=$group;
			$hid_check=$nxt[9]?'checked':'';
			$yml_check=$nxt['no_export_yml']?'checked':'';
			$stock_check=$nxt[12]?'checked':'';
			$wt0_check=(!$nxt['warranty_type'])?'checked':'';
			$wt1_check=($nxt['warranty_type'])?'checked':'';

			$tmpl->AddText("<form action='' method='post'>
			<input type='hidden' name='mode' value='esave'>
			<input type='hidden' name='l' value='sklad'>
			<input type='hidden' name='pos' value='$pos'>
			<input type='hidden' name='pd[id]' value='$pos'>
			<table cellpadding='0' width='100%'>
        		<tr class='lin0'><td align='right' width='20%'>$n</td>
        		<td colspan='3'><input type='text' name='pd[name]' value='{$nxt['name']}' style='width: 95%'>$cc
        		<tr class='lin1'><td align='right'>Группа</td>
        		<td colspan='3'>".selectGroupPos('pd[group]',$selected,1)."</td></tr>
        		<tr class='lin0'><td align='right'>Изготовитель</td>
			<td><input type='text' name='pd[proizv]' value='{$nxt['proizv']}' id='proizv_nm' style='width: 95%'><br>
			<div id='proizv_p' class='dd'></div></td>
			<td align='right'>Код изготовителя</td><td><input type='text' name='pd[vc]' value='{$nxt['vc']}'></td></tr>
			<tr class='lin1'><td align='right'>Единица измерения</td><td colspan='3'><select name='pd[unit]'>");

			$res2=mysql_query("SELECT `id`, `name` FROM `class_unit_group` ORDER BY `id`");
			while($nx2=mysql_fetch_row($res2))
			{
				$tmpl->AddText("<option disabled style='color:#fff; background-color:#000'>$nx2[1]</option>\n");
				$res=mysql_query("SELECT `id`, `name`, `rus_name1` FROM `class_unit` WHERE `class_unit_group_id`='$nx2[0]'");
				while($nx=mysql_fetch_row($res))
				{
					$i="";
					if((($pos!=0)&&($nx[0]==$nxt['unit']))||($group==$nx[0])) $i=" selected";
					$tmpl->AddText("<option value='$nx[0]' $i>$nx[1] ($nx[2])</option>");
				}
			}
			$tmpl->AddText("</select></td></tr>
			<tr class='lin0'><td align='right'>Страна происхождения<br><small>Для счёта-фактуры</small></td><td colspan='3'><select name='pd[country]'>");
			$tmpl->AddText("<option value='0'>--не выбрана--</option>");
			$res=mysql_query("SELECT `id`, `name` FROM `class_country` ORDER BY `name`");
			while($nx=mysql_fetch_row($res))
			{
				$selected=($group==$nx[0])||($nx[0]==$nxt['country'])?'selected':'';
				$tmpl->AddText("<option value='$nx[0]' $selected>$nx[1]</option>");
			}
			$tmpl->AddText("</select></td></tr>
			<tr class='lin1'><td align='right'>Ликвидность:</td><td colspan='3'><b>{$nxt['likvid']}% <small>=Сумма(Кол-во заявок + Кол-во реализаций) / МаксСумма(Кол-во заявок + Кол-во реализаций)</small></b></td></tr>
			<tr class='lin0'><td align='right'>Базовая цена</td><td><input type='text' name='pd[cost]' value='{$nxt['cost']}'> с {$nxt['cost_date']} </td>
			<td align='right'>Актуальная цена поступления:</td><td><b>$act_cost</b></td></tr>
			<tr class='lin1'><td align='right'>Гарантийный срок:</td><td><input type='text' name='pd[warranty]' value='{$nxt['warranty']}'> мес.</td>
			<td align='right'>Гарантия:</td><td><label><input type='radio' name='pd[warranty_type]' value='0' $wt0_check>От продавца</label> <label><input type='radio' name='pd[warranty_type]' value='1' $wt1_check>От производителя</label></td></tr>
			<tr class='lin1'><td align='right'>Видимость:</td><td><label><input type='checkbox' name='pd[hidden]' value='1' $hid_check>Не отображать на витрине</label></td><td><label><input type='checkbox' name='pd[no_export_yml]' value='1' $yml_check>Не экспортировать в YML</label>
			<td><label><input type='checkbox' name='pd[stock]' value='1' $stock_check>Поместить в спецпредложения</label></td></tr>

			<tr class='lin0'><td align='right'>Описание</td><td colspan='3'><textarea name='pd[desc]'>{$nxt['desc']}</textarea></td></tr>
			<tr class='lin0'><td align='right'>Тэг title карточки товара на витрине</td>
			<td colspan='3'><input type='text' name='pd[title_tag]' value='{$nxt['title_tag']}' style='width: 95%' maxlength='128'></td></tr>
			<tr class='lin1'><td align='right'>Мета-тэг keywords карточки товара на витрине</td>
			<td colspan='3'><input type='text' name='pd[meta_keywords]' value='{$nxt['meta_keywords']}' style='width: 95%' maxlength='128'></td></tr>
			<tr class='lin0'><td align='right'>Мета-тэг description карточки товара на витрине</td>
			<td colspan='3'><input type='text' name='pd[meta_description]' value='{$nxt['meta_description']}' style='width: 95%' maxlength='256'></td></tr>
			");
			if($pos!=0)
				$tmpl->AddText("<tr class='lin1'><td align='right'>Режим записи:</td><td colspan='3'>
				<label><input type='radio' name='sr' value='0' checked>Сохранить</label>
				<label><input type='radio' name='sr' value='1'>Добавить</label></td></tr>");
			$tmpl->AddText("<tr class='lin1'><td></td><td  colspan='3'><input type='submit' value='Сохранить'></td></tr>
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
		else if($param=='d')
		{
			$res=mysql_query("SELECT `doc_base_dop`.`type`, `doc_base_dop`.`analog`, `doc_base_dop`.`koncost`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`, `doc_base_dop`.`ntd`, `doc_base`.`group`
			FROM `doc_base`
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`='$pos'
			WHERE `doc_base`.`id`='$pos'");
			$nxt=mysql_fetch_row($res);
			$tmpl->AddText("
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
				var ctext = sel.selectedIndex !== -1 ? sel.options[sel.selectedIndex].text : ''
				var text=document.getElementById('value_add').value
				newrow.innerHTML=\"<td align='right'>\"+ctext+\"</td><td><input type='text' name='par[\"+lineid+\"]' value='\"+text+\"'></td>\"
			}

			</script>
			<form action='' method='post'>
			<input type='hidden' name='mode' value='esave'>
			<input type='hidden' name='l' value='sklad'>
			<input type='hidden' name='pos' value='$pos'>
			<input type='hidden' name='param' value='d'>
			<table cellpadding='0' width='100%' id='fg_table'>
			<tfoot>
			<tr><td align='right'><select name='pp' id='fg_select'>");
			$r=mysql_query("SELECT `id`, `param`, `type` FROM `doc_base_params` WHERE `system`='0' ORDER BY `param`");
			if(mysql_errno())	throw new MysqlException("Не удалось получить информацию о дополнительных свойствах");
			while($p=mysql_fetch_row($r))
			{
				$tmpl->AddText("<option value='$p[0]'>$p[1]</option>");
			}
			$tmpl->AddText("</select></td><td><input type='text' id='value_add'><img src='/img/i_add.png' alt='' onclick='return addLine()'></td></tr>
			</td></tr>
			<tr class='lin1'><td><td><input type='submit' value='Сохранить'>
			</tfoot>
			<tbody>
			<tr class='lin0'><td align='right'>Аналог<td><input type='text' name='analog' value='$nxt[1]' id='pos_analog'>
			<tr class='lin1'><td align='right'>Рыночная цена<td><input type='text' name='koncost' value='$nxt[2]' id='pos_koncost'>
			<tr class='lin0'><td align='right'>Тип<td><select name='type' id='pos_type' >
			<option value='null'>--не задан--</option>");

			$res=mysql_query("SELECT `id`, `name` FROM `doc_base_dop_type` ORDER BY `id`");
			while($nx=mysql_fetch_row($res))
			{
				$ii="";
				if($nx[0]===$nxt[0]) $ii=" selected";
				$tmpl->AddText("<option value='$nx[0]' $ii>$nx[0] - $nx[1]</option>");
			}

			$tmpl->AddText("</select>
			<tr class='lin1'><td align='right'>Внутренний размер (d)<td><input type='text' name='d_int' value='$nxt[3]' id='pos_d_int'></td></tr>
			<tr class='lin0'><td align='right'>Внешний размер (D)<td><input type='text' name='d_ext' value='$nxt[4]' id='pos_d_ext'></td></tr>
			<tr class='lin1'><td align='right'>Высота (B)<td><input type='text' name='size' value='$nxt[5]' id='pos_size'></td></tr>
			<tr class='lin0'><td align='right'>Масса<td><input type='text' name='mass' value='$nxt[6]' id='pos_mass'></td></tr>
			<tr class='lin1'><td align='right'>Номер таможенной декларации<td><input type='text' name='ntd' value='$nxt[7]'></td></tr>");
			$res=mysql_query("SELECT `doc_base_values`.`param_id`, `doc_base_params`.`param`, `doc_base_values`.`value` FROM `doc_base_values`
			LEFT JOIN `doc_base_params` ON `doc_base_params`.`id`=`doc_base_values`.`param_id`
			WHERE `doc_base_values`.`id`='$pos'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить дополнительные свойства!");
			$i=0;
			while($nx=mysql_fetch_row($res))
			{
				$tmpl->AddText("<tr class='lin$i'><td align='right'>$nx[1]<td><input type='text' name='par[$nx[0]]' value='$nx[2]'>");
				$i=1-$i;
			}
			$res=mysql_query("SELECT `doc_base_params`.`id`, `doc_base_params`.`param`, `doc_group_params`.`show_in_filter` FROM `doc_base_params`
			LEFT JOIN `doc_group_params` ON `doc_group_params`.`param_id`=`doc_base_params`.`id`
			WHERE  `doc_group_params`.`group_id`='$nxt[8]' AND `doc_base_params`.`system`='0' AND `doc_base_params`.`id` NOT IN ( SELECT `doc_base_values`.`param_id` FROM `doc_base_values` WHERE `doc_base_values`.`id`='$pos' )
			ORDER BY `doc_base_params`.`id`");
			if(mysql_errno())	throw new MysqlException("Не удалось получить дополнительные свойства группы!");
			while($nx=mysql_fetch_row($res))
			{
				$tmpl->AddText("<tr class='lin$i'><td align='right'>$nx[1]<td><input type='text' name='par[$nx[0]]' value=''>");
				$i=1-$i;
			}

			$tmpl->AddText("</tbody></table></form>");
		}
		// Складские свойства
		else if($param=='s')
		{
			$res=mysql_query("SELECT `doc_sklady`.`name`, `doc_base_cnt`.`cnt`, `doc_base_cnt`.`mincnt`,  `doc_base_cnt`.`mesto`, `doc_base_cnt`.`sklad`
			FROM `doc_base_cnt`
			LEFT JOIN `doc_sklady` ON `doc_sklady`.`id` = `doc_base_cnt`.`sklad`
			WHERE `doc_base_cnt`.`id`='$pos'");
			$tmpl->AddText("
			<form action='' method='post'>
			<input type='hidden' name='mode' value='esave'>
			<input type='hidden' name='l' value='sklad'>
			<input type='hidden' name='pos' value='$pos'>
			<input type='hidden' name='param' value='s'>
			<table cellpadding='0' width='50%'>
			<tr><th>Склад<th>Кол-во<th>Минимум<th>Место");
			$i=0;
			while($nxt=@mysql_fetch_row($res))
			{
				$tmpl->AddText("<tr class='lin$i'>
				<td><a href='?mode=ske&amp;sklad=$nxt[4]'>$nxt[0]</a>
				<td>$nxt[1]
				<td><input type='text' name='min$nxt[4]' value='$nxt[2]'>
				<td><input type='text' name='mesto$nxt[4]' value='$nxt[3]'>");
				$i=1-$i;
			}
			$tmpl->AddText("</table>
			<input type='submit' value='Сохранить'>
			</form>");

		}
		// Изображения
		else if($param=='i')
		{
			$max_fs=get_max_upload_filesize();
			$max_fs_size=$max_fs;
			if($max_fs_size>1024*1024)	$max_fs_size=($max_fs_size/(1024*1024)).' Мб';
			else if($max_fs_size>1024)	$max_fs_size=($max_fs_size/(1024)).' Кб';
			else				$max_fs_size.='байт';
			$res=mysql_query("SELECT `doc_base_img`.`img_id`, `doc_img`.`type`
			FROM `doc_base_img`
			LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
			WHERE `doc_base_img`.`pos_id`='$pos'");
			$checked=(mysql_num_rows($res)==0)?'checked':'';
			$tmpl->AddText("
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
			<td><input type='text' name='photoname_1' value='photo_$pos'></td>
			</tr>
			<tr><td><input type='radio' name='def_img' value='2'></td>
			<td><input type='hidden' name='MAX_FILE_SIZE' value='$max_fs'><input name='userfile2' type='file'></td>
			<td><input type='text' name='photoname_2' value='photo_$pos'></td>
			</tr>
			<tr><td><input type='radio' name='def_img' value='3'></td>
			<td><input type='hidden' name='MAX_FILE_SIZE' value='$max_fs'><input name='userfile3' type='file'></td>
			<td><input type='text' name='photoname_3' value='photo_$pos'></td>
			</tr>
			<tr><td><input type='radio' name='def_img' value='4'></td>
			<td><input type='hidden' name='MAX_FILE_SIZE' value='$max_fs'><input name='userfile4' type='file'></td>
			<td><input type='text' name='photoname_4' value='photo_$pos'></td>
			</tr>
			<tr><td><input type='radio' name='def_img' value='5'></td>
			<td><input type='hidden' name='MAX_FILE_SIZE' value='$max_fs'><input name='userfile5' type='file'></td>
			<td><input type='text' name='photoname_5' value='photo_$pos'></td>
			</tr>
			<tr class='lin0'><td colspan='3' align='center'>
			<input type='submit' value='Сохранить'>
			</table>
			<b>Форматы</b>: Не более $max_fs_size суммарно, разрешение от 150*150 до 10000*10000, форматы JPG, PNG, допустим, но не рекомендуется GIF<br>
			<b>Примечание</b>: Если написать имя картинки, которая уже есть в базе, то она и будет установлена вне зависимости от того, передан файл или нет.

			</form><h2>Ассоциированные с товаром картинки</h2>");
			while($nxt=@mysql_fetch_row($res))
			{
				$miniimg=new ImageProductor($nxt[0],'p', $nxt[1]);
				$miniimg->SetX(175);
				$img="<img src='".$miniimg->GetURI()."' width='175'>";

				$tmpl->AddText("$img<br>
				<a href='?mode=esave&amp;l=sklad&amp;param=i_d&amp;pos=$pos&amp;img=$nxt[0]'>Убрать ассоциацию</a><br><br>");
			}
			$tmpl->AddText("</td><td valign='top'>
			<form action='' method='post' enctype='multipart/form-data'>
			<input type='hidden' name='mode' value='esave'>
			<input type='hidden' name='l' value='sklad'>
			<input type='hidden' name='pos' value='$pos'>
			<input type='hidden' name='param' value='i_a'>
			<table cellpadding='0'>
			<tr class='lin1'><td>Прикрепляемый файл:
			<td><input type='hidden' name='MAX_FILE_SIZE' value='$max_fs'><input name='userfile' type='file'><br><small>Не более $max_fs_size</small>
			<tr class='lin0'><td>Описание файла (до 128 символов):
			<td><input type='text' name='comment' value='Инструкция для $pos'><br>
			<small>Если написать описание файла, которое уже есть в базе, то соответствующий файл и будет установлен, вне зависимости от того, передан он или нет.</small>
			<tr class='lin1'><td colspan='2' align='center'>
			<input type='submit' value='Сохранить'>
			</table>
			<table class='list' width='100%'>
			<tr><th colspan='4'>Прикреплённые файлы</th></tr>
			");
			$res=mysql_query("SELECT `doc_base_attachments`.`attachment_id`, `attachments`.`original_filename`, `attachments`.`comment`
			FROM `doc_base_attachments`
			LEFT JOIN `attachments` ON `attachments`.`id`=`doc_base_attachments`.`attachment_id`
			WHERE `doc_base_attachments`.`pos_id`='$pos'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список прикреплённых файлов");
			while($nxt=@mysql_fetch_row($res))
			{
				if($CONFIG['site']['recode_enable'])	$link="/attachments/{$nxt[0]}/$nxt[1]";
				else					$link="/attachments.php?att_id={$nxt[0]}";
				$tmpl->AddText("<tr><td>$nxt[0]</td><td><a href='$link'>$nxt[1]</td></td><td>$nxt[2]</td><td><a href='?mode=esave&amp;l=sklad&amp;param=i_ad&amp;pos=$pos&amp;att=$nxt[0]' title='Убрать ассоциацию'><img src='/img/i_del.png' alt='Убрать ассоциацию'></a></td></tr>");
			}
			$tmpl->AddText("
			</table>
			</td></tr></table>");
		}
		// Цены
		else if($param=='c')
		{
			$res=mysql_query("SELECT `doc_base`.`cost` FROM `doc_base` WHERE `doc_base`.`id`='$pos'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить базовую цену товара");
			$base_cost=mysql_result($res,0,0);

			$cost_types=array('pp' => 'Процент', 'abs' => 'Абсолютная наценка', 'fix' => 'Фиксированная цена');
			$direct=array((-1)=>'Вниз', 0=>'K ближайшему', 1=>'Вверх');
			$res=mysql_query("SELECT `doc_cost`.`id`, `doc_base_cost`.`id`, `doc_cost`.`name`, `doc_cost`.`type`, `doc_cost`.`value`, `doc_base_cost`.`type`, `doc_base_cost`.`value`, `doc_base_cost`.`accuracy`, `doc_base_cost`.`direction`, `doc_cost`.`accuracy`, `doc_cost`.`direction`
			FROM `doc_cost`
			LEFT JOIN `doc_base_cost` ON `doc_cost`.`id`=`doc_base_cost`.`cost_id` AND `doc_base_cost`.`pos_id`='$pos'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список цен");
			$tmpl->AddText("
			<form action='docs.php' method='post'>
			<input type='hidden' name='mode' value='esave'>
			<input type='hidden' name='l' value='sklad'>
			<input type='hidden' name='pos' value='$pos'>
			<input type='hidden' name='param' value='c'>
			<table cellpadding='0' width='50%'>
			<tr><th>Цена<th>Тип<th>Значение<th>Точность<th>Округление<th>Результат
			<tr><td><b>Базовая</b><td>Базовая цена<td>$base_cost руб.<td>-<td>-<td>$base_cost руб.");
			while($cn=mysql_fetch_row($res))
			{
				$sig=($cn[4]>0)?'+':'';
				if($cn[3]=='pp')	$def_val="({$sig}$cn[4] %)";
				else if($cn[3]=='abs')	$def_val="({$sig}$cn[4] руб.)";
				else if($cn[3]=='fix')	$def_val="(= $cn[4] руб.)";
				else			$def_val="({$sig}$cn[4] XX)";

				$checked=$cn[1]?'checked':'';
				if(!$cn[1])
				{
					$cn[5]=$cn[3];
					$cn[6]=$cn[4];
					$cn[7]=$cn[9];
					$cn[8]=$cn[10];
				}

				$tmpl->AddText("<tr><td><label><input type='checkbox' name='ch$cn[0]' value='1' $checked>$cn[2] $def_val</label>
				<td><select name='cost_type$cn[0]'>");
				foreach($cost_types as $id => $type)
				{
					$sel=($id==$cn[5])?' selected':'';
					$tmpl->AddText("<option value='$id'$sel>$type</option>");
				}

				$tmpl->AddText("</select>
				<td><input type='text' name='val$cn[0]' value='$cn[6]'>
				<td><select name='accur$cn[0]'>");
				for($i=-3;$i<3;$i++)
				{
					$a=sprintf("%0.2f",pow(10,$i*(-1)));
					$sel=$cn[7]==$i?'selected':'';
					$tmpl->AddText("<option value='$i' $sel>$a</option>");
				}
				$tmpl->AddText("</select>
				<td><select name='direct$cn[0]'>");
				for($i=(-1);$i<2;$i++)
				{
					$sel=$cn[8]==$i?'selected':'';
					$tmpl->AddText("<option value='$i' $sel>{$direct[$i]}</option>");
				}
				$result=GetCostPos($pos, $cn[0]);
				$tmpl->AddText("</select><td>$result руб.");
			}
			$tmpl->AddText("</table>
			<button>Сохранить цены</button></form>");
		}
		// Комплектующие
		else if($param=='k')
		{
			$plm=rcv('plm');
			include_once("include/doc.sklad.kompl.php");
			if($plm=='')
			{
				$res=mysql_query("SELECT `doc_base_params`.`id`, `doc_base_values`.`value` FROM `doc_base_params`
				LEFT JOIN `doc_base_values` ON `doc_base_values`.`param_id`=`doc_base_params`.`id` AND `doc_base_values`.`id`='$pos'
				 WHERE `doc_base_params`.`param`='ZP'");
				if(mysql_errno())	throw new MysqlException("Не удалось выбрать доп.свойство товара");
				$nxt=@mysql_fetch_row($res);

				kompl_poslist($pos);
				$tmpl->AddText("
				<script type=\"text/javascript\">
				window.document.onkeydown = OnEnterBlur;
				</script>
				<form action='docs.php' method='post'>
				<input type='hidden' name='mode' value='esave'>
				<input type='hidden' name='l' value='sklad'>
				<input type='hidden' name='pos' value='$pos'>
				<input type='hidden' name='param' value='k'>
				Зарплата за сборку: <input type='text' name='zp' value='$nxt[1]'> руб. <button>Сохранить</button>
				</form>
				<table width=100% id='sklad_editor'>
				<tr><td id='groups' width=200 valign='top' class='lin0>'");
				kompl_groups($pos);
				$tmpl->AddText("<td id='sklad' valign='top' class='lin1'>");
				kompl_sklad($pos,0);
				$tmpl->AddText("</table>");
			}
			else if($plm=='sg')
			{
				$tmpl->ajax=1;
				$tmpl->SetText('');
				$group=rcv('group');
				kompl_sklad($pos, $group);
			}
			else if($plm=='pos')
			{
				$tmpl->ajax=1;
				$tmpl->SetText('');
				$vpos=rcv('vpos');
				if(!isAccess('list_sklad','edit'))	throw new AccessException("");
				$res=mysql_query("SELECT `id`, `kompl_id`, `cnt` FROM `doc_base_kompl` WHERE `pos_id`='$pos' AND `kompl_id`='$vpos'");
				if(mysql_errno())	throw new MysqlException("Не удалось выбрать строку документа!");
				if(mysql_num_rows($res)==0)
				{
					mysql_query("INSERT INTO `doc_base_kompl` (`pos_id`,`kompl_id`,`cnt`) VALUES ('$pos','$vpos','1')");
					if(mysql_errno())	throw new MysqlException("Не удалось вставить строку!");
					doc_log("UPDATE komplekt","add kompl: pos_id:$vpos",'pos',$pos);
				}
				else
				{
					$nxt=mysql_fetch_row($res);
					mysql_query("UPDATE `doc_base_kompl` SET `cnt`=`cnt`+'1' WHERE `pos_id`='$pos' AND `kompl_id`='$vpos'");
					if(mysql_errno())	throw new MysqlException("Не удалось вставить строку!");
					doc_log("UPDATE komplekt","change cnt: kompl_id:$nxt[1], cnt:$nxt[2]+1",'pos',$nxt[1]);
				}

				kompl_poslist($pos);
			}
			else if($plm=='cc')
			{
				$tmpl->ajax=1;
				$tmpl->SetText('');
				$s=rcv('s');
				$vpos=rcv('vpos');
				if($s<=0) $s=1;
				$res=mysql_query("SELECT `kompl_id`, `cnt` FROM `doc_base_kompl` WHERE `id`='$vpos'");
				if(mysql_errno())	throw MysqlException("Не удалось выбрать строку!");
				$nxt=mysql_fetch_row($res);
				if(!$nxt)		throw new Exception("Строка $vpos не найдена. Вероятно, она была удалена другим пользователем или Вами в другом окне.");
				if($s!=$nxt[1])
				{
					$res=mysql_query("UPDATE `doc_base_kompl` SET `cnt`='$s' WHERE `pos_id`='$pos' AND `id`='$vpos'");
					if(mysql_errno())	throw MysqlException("Не удалось обновить количество в строке");
					kompl_poslist($pos);
					doc_log("UPDATE komplekt","change cnt: kompl_id:$nxt[1], cnt:$nxt[1] => $s",'pos',$nxt[1]);
				}
				else kompl_poslist($pos);
			}
			else if($plm='d')
			{
				$tmpl->ajax=1;
				$tmpl->SetText('');
				$vpos=rcv('vpos');
				$res=mysql_query("SELECT `kompl_id`, `cnt` FROM `doc_base_kompl` WHERE `id`='$vpos'");
				if(mysql_errno())	throw new MysqlException("Не удалось выбрать строку документа!");
				$nxt=mysql_fetch_row($res);
				if(!$nxt)		throw new Exception("Строка не найдена. Вероятно, она была удалена другим пользователем или Вами в другом окне.");

				$res=mysql_query("DELETE FROM `doc_base_kompl` WHERE `id`='$vpos'");
				doc_log("UPDATE komplekt","del kompl: kompl_id:$nxt[0], doc_list_pos:$pos, cnt:$nxt[1], cost:$nxt[2]",'pos',$pos);

				kompl_poslist($pos);
			}
		}
		// Связанные товары
		else if($param=='l')
		{
			$jparam=rcv('jparam');
			require_once("include/doc.sklad.link.php");
			$poseditor=new LinkPosList($pos);
			$poseditor->SetEditable(1);
			if($jparam=='')
			{
				$tmpl->AddText($poseditor->Show());
			}
			else
			{
				$tmpl->ajax=1;
				if($jparam=='jget')
				{
					$str="{ response: '2', content: [".$poseditor->GetAllContent()."]}";
					$tmpl->SetText($str);
				}
				// Получение данных наименования
				else if($jparam=='jgpi')
				{
					$pos=rcv('pos');
					$tmpl->AddText($poseditor->GetPosInfo($pos));
				}
				// Json вариант добавления позиции
				else if($jparam=='jadd')
				{
					$pos=rcv('pos');
					$tmpl->SetText($poseditor->AddPos($pos));
				}
				// Json вариант удаления строки
				else if($jparam=='jdel')
				{
					$line_id=rcv('line_id');
					$tmpl->SetText($poseditor->Removeline($line_id));
				}
				// Json вариант обновления
				else if($jparam=='jup')
				{
					$line_id=rcv('line_id');
					$value=rcv('value');
					$type=rcv('type');
					$tmpl->SetText($poseditor->UpdateLine($line_id, $type, $value));
				}
				// Получение номенклатуры выбранной группы
				else if($jparam=='jsklad')
				{
					$group_id=rcv('group_id');
					$str="{ response: 'sklad_list', group: '$group_id',  content: [".$poseditor->GetSkladList($group_id)."] }";
					$tmpl->SetText($str);
				}
				// Поиск по подстроке по складу
				else if($jparam=='jsklads')
				{
					$s=rcv('s');
					$str="{ response: 'sklad_list', content: [".$poseditor->SearchSkladList($s)."] }";
					$tmpl->SetText($str);
				}

			}
		}
		// История изменений
		else if($param=='h')
		{
			$res=mysql_query("SELECT `doc_log`.`motion`, `doc_log`.`desc`, `doc_log`.`time`, `users`.`name`, `doc_log`.`ip`
			FROM `doc_log`
			LEFT JOIN `users` ON `users`.`id`=`doc_log`.`user`
			WHERE `doc_log`.`object`='pos' AND `doc_log`.`object_id`='$pos'");
			$tmpl->AddText("<h1>История наименования $pos</h1>
			<table width=100% class='colorized'>
			<tr><th>Выполненное действие<th>Описание действия<th>Дата<th>Пользователь<th>IP");
			$i=0;
			while($nxt=mysql_fetch_row($res))
			{
				$i=1-$i;
				$tmpl->AddText("<tr class='lin$i'><td>$nxt[0]<td>$nxt[1]<td>$nxt[2]<td>$nxt[3]<td>$nxt[4]");
			}
			$tmpl->AddText("</table>");
		}
		// Правка описания группы
		else if($param=='g')
		{
			$res=mysql_query("SELECT `id`, `name` , `desc` , `pid` , `hidelevel` , `printname`, `no_export_yml`, `title_tag`, `meta_keywords`, `meta_description`
			FROM `doc_group`
			WHERE `id`='$group'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить данные группы");
			@$nxt=mysql_fetch_row($res);
			$tmpl->AddText("<h1>Описание группы</h1>
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
			<input type='hidden' name='g' value='$nxt[0]'>
			<input type='hidden' name='param' value='g'>
			<table cellpadding='0' width='50%'>
			<tr class='lin1'>
			<td>Наименование группы $nxt[0]:
			<td><input type='text' name='name' value='$nxt[1]'>
			<tr class='lin0'>
			<td>Находится в группе:
			<td><select name='pid'>");

			$i='';
			if(@$nxt[3]==0) $i=" selected";
			$tmpl->AddText("<option value='0' $i style='color: #fff; background-color: #000'>--</option>");

			$res=mysql_query("SELECT * FROM `doc_group`");
			while($nx=mysql_fetch_row($res))
			{
				$i="";

				if($nx[0]==$nxt[3]) $i=" selected";
				$tmpl->AddText("<option value='$nx[0]' $i>$nx[1] ($nx[0])</option>");
			}

			if(file_exists("{$CONFIG['site']['var_data_fs']}/category/$group.jpg"))
				$img="<br><img src='{$CONFIG['site']['var_data_web']}/category/$group.jpg'><br><a href='/docs.php?l=sklad&amp;mode=esave&amp;g=$nxt[0]&amp;param=gid'>Удалить изображение</a>";
			else $img='';

			$hid_check=$nxt[4]?'checked':'';
			$yml_check=$nxt[6]?'checked':'';

			$tmpl->AddText("</select>
			<tr class='lin1'>
			<td>Скрытие:
			<td><label><input type='checkbox' name='hid' value='3' $hid_check>Не отображать на витрине и в прайсах</label><br>
			<label><input type='checkbox' name='no_export_yml' value='3' $yml_check>Не экспортировать в YML</label><br>
			<tr class='lin0'>
			<td>Печатное название:
			<td><input type='text' name='pname' value='$nxt[5]'>
			<tr class='lin1'><td>Тэг title группы на витрине:<td><input type='text' name='title_tag' value='$nxt[7]' maxlength='128'>
			<tr class='lin0'><td>Мета-тэг keywords группы на витрине:<td><input type='text' name='meta_keywords' value='$nxt[8]' maxlength='128'>
			<tr class='lin1'><td>Мета-тэг description группы на витрине:<td><input type='text' name='meta_description' value='$nxt[9]' maxlength='256'>

			<tr class='lin0'><td>Изображение (jpg, до 100 кб, 50*50 - 200*200):
			<td><input type='hidden' name='MAX_FILE_SIZE' value='1000000'><input name='userfile' type='file'>$img
			<tr class='lin1'>
			<td>Описание:
			<td><textarea name='desc'>$nxt[2]</textarea>
			<tr class='lin0'><td>Статические дополнительные свойства товаров группы<br><br>
			Добавить из набора:<select name='collection'>
			<option value='0'>--не выбран--</option>");
			$rgroups=mysql_query("SELECT `id`, `name` FROM `doc_base_pcollections_list` ORDER BY `name`");
			if(mysql_errno())	throw new MysqlException("Не удалось получить наборы свойств складской номенклатуры");
			while($col=mysql_fetch_row($rgroups))
			{
				$tmpl->AddText("<option value='$col[0]'>$col[1]</option>");
			}
			$tmpl->AddText("</select>
			<td>
			<table width='100%' id='fg_table' class='list'>
			<thead>
			<tr><th><img src='/img/i_filter.png' alt='Отображать в фильтрах'></th><th>Название параметра</th><th>&nbsp;</th></tr>
			</thead>
			<tfoot>
			<tr><td><input type='checkbox' id='fg_check'><td>
			<select name='pp' id='fg_select'>
			<option value='0' selected>--не выбрано--</option>");
			$res_group=mysql_query("SELECT `id`, `name` FROM `doc_base_gparams` ORDER BY `name`");
			while($groupp=mysql_fetch_row($res_group))
			{
				$tmpl->AddText("<option value='-1' disabled>$groupp[1]</option>");
				$res=mysql_query("SELECT `id`, `param` FROM `doc_base_params` WHERE `pgroup_id`='$groupp[0]' ORDER BY `param`");
				while($param=mysql_fetch_row($res))
				{
					$tmpl->AddText("<option value='$param[0]'>- $param[1]</option>");
				}
			}
			$tmpl->AddText("</select>

			</td><td><img src='/img/i_add.png' alt='' onclick='return addLine()'></td></tr>
			</td></tr></tfoot>
			<tbody>");

			$r=mysql_query("SELECT `doc_base_params`.`id`, `doc_base_params`.`param`, `doc_group_params`.`show_in_filter` FROM `doc_base_params`
			LEFT JOIN `doc_group_params` ON `doc_group_params`.`param_id`=`doc_base_params`.`id`
			WHERE  `doc_group_params`.`group_id`='$group'
			ORDER BY `doc_base_params`.`id`");
			if(mysql_errno())	throw new MysqlException("Не удалось получить информацию о дополнительных свойствах");
			while($p=mysql_fetch_row($r))
			{
				$checked=$p[2]?'checked':'';
				$tmpl->AddText("<tr><td><input type='hidden' name='fn[$p[0]]' value='1'>
				<input type='checkbox' name='fc[$p[0]]' value='1' $checked></td><td>$p[1]</td>
				<td><img src='/img/i_del.png' alt='' onclick='return rmLine(this)'></td></tr>");
			}

			$tmpl->AddText("</tbody></table>
			<tr class='lin1'><td colspan='2' align='center'>
			<button type='submit'>Сохранить</button>
			</table></form>");

			if($nxt[0])
			{
				$cost_types=array('pp' => 'Процент', 'abs' => 'Абсолютная наценка', 'fix' => 'Фиксированная цена');
				$direct=array((-1)=>'Вниз', 0=>'K ближайшему', 1=>'Вверх');
				$res=mysql_query("SELECT `doc_cost`.`id`, `doc_group_cost`.`id`, `doc_cost`.`name`, `doc_cost`.`type`, `doc_cost`.`value`, `doc_group_cost`.`type`, `doc_group_cost`.`value`, `doc_group_cost`.`accuracy`, `doc_group_cost`.`direction`, `doc_cost`.`accuracy`, `doc_cost`.`direction`
				FROM `doc_cost`
				LEFT JOIN `doc_group_cost` ON `doc_cost`.`id`=`doc_group_cost`.`cost_id` AND `doc_group_cost`.`group_id`='$nxt[0]'");
				if(mysql_errno())	throw new MysqlException("Не удалось получить список цен");
				$tmpl->AddText("<h1>Задание цен</h1>
				<form action='docs.php' method='post'>
				<input type='hidden' name='mode' value='esave'>
				<input type='hidden' name='l' value='sklad'>
				<input type='hidden' name='g' value='$nxt[0]'>
				<input type='hidden' name='param' value='gc'>
				<table cellpadding='0' width='50%'>
				<tr><th>Цена<th>Тип<th>Значение<th>Точность<th>Округление");
				while($cn=mysql_fetch_row($res))
				{
					$sig=($cn[4]>0)?'+':'';
					if($cn[3]=='pp')	$def_val="({$sig}$cn[4] %)";
					else if($cn[3]=='abs')	$def_val="({$sig}$cn[4] руб.)";
					else if($cn[3]=='fix')	$def_val="(= $cn[4] руб.)";
					else			$def_val="({$sig}$cn[4] XX)";

					$checked=$cn[1]?'checked':'';

					$tmpl->AddText("<tr><td><label><input type='checkbox' name='ch$cn[0]' value='1' $checked>$cn[2] $def_val</label>
					<td><select name='cost_type$cn[0]'>");
					foreach($cost_types as $id => $type)
					{
						$sel=($id==$cn[5])?' selected':'';
						$tmpl->AddText("<option value='$id'$sel>$type</option>");
					}
					if(!$cn[1])
					{
						$cn[5]=$cn[3];
						$cn[6]=$cn[4];
						$cn[7]=$cn[9];
						$cn[8]=$cn[10];
					}
					$tmpl->AddText("</select>
					<td><input type='text' name='val$cn[0]' value='$cn[6]'>
					<td><select name='accur$cn[0]'>");
					for($i=-3;$i<3;$i++)
					{
						$a=sprintf("%0.2f",pow(10,$i*(-1)));
						$sel=$cn[7]==$i?'selected':'';
						$tmpl->AddText("<option value='$i' $sel>$a</option>");
					}
					$tmpl->AddText("</select>
					<td><select name='direct$cn[0]'>");
					for($i=(-1);$i<2;$i++)
					{
						$sel=$cn[8]==$i?'selected':'';
						$tmpl->AddText("<option value='$i' $sel>{$direct[$i]}</option>");
					}
					$tmpl->AddText("</select>");
				}
				$tmpl->AddText("</table>
				<button>Сохранить цены</button></form>");
			}
		}
		// Импорт из яндекс маркета
		else if($param=='y')
		{
			$a=rcv('a');
			if($a=='')
			{
				$res=mysql_query("SELECT `doc_base_params`.`id`, `doc_base_values`.`value` FROM `doc_base_params`
				LEFT JOIN `doc_base_values` ON `doc_base_values`.`param_id`=`doc_base_params`.`id` AND `doc_base_values`.`id`='$pos'
				 WHERE `doc_base_params`.`param`='ym_id'");
				if(mysql_errno())	throw new MysqlException("Не удалось выбрать доп.свойство товара");
				$nxt=@mysql_fetch_row($res);
				
				$tmpl->AddText("
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
				if($nxt[1])	$tmpl->AddText("<a href='http://market.yandex.ru/model-spec.xml?modelid=".@$nxt[1]."'>Посмотреть на яндекс-маркете</a>");
			}
			else
			{
				$url=@$_POST['url'];
				preg_match("/[?]*modelid=([\d]{1,9})[?]*+/", $url,$keywords);
				$ym_id = $keywords[1];
				settype($ym_id,'int');
				$url="http://market.yandex.ru/model-spec.xml?modelid=".$ym_id;
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_FAILONERROR, 1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
				curl_setopt($ch, CURLOPT_TIMEOUT, 4);
				$result = curl_exec($ch);
				curl_close($ch);

				$dom = new domDocument();
				$dom->loadHTML($result);
				$dom->preserveWhiteSpace = false;

				$f=0;
				$tables = $dom->getElementsByTagName('table');
				foreach($tables as $table)
				{
					if($table->getAttribute('class')=='b-properties')
					{
						$f=1;
						break;
					}
				}

				function getSelectParams($id,$name)
				{
					$ret="<select name='sel[$id]'><option value='-1' selected>--не выбрано--</option>";
					$selected=mysql_real_escape_string($name);
					$res=mysql_query("SELECT CONCAT(`doc_base_gparams`.`name`,' - ',`doc_base_params`.`param`), `doc_base_params`.`ym_assign`
					FROM `doc_base_params`
					INNER JOIN `doc_base_gparams` ON `doc_base_gparams`.`id`=`doc_base_params`.`pgroup_id`
					WHERE `doc_base_params`.`ym_assign`='$selected'");
					if(mysql_num_rows($res))
					{
						return mysql_result($res,0,0);
					}
					$res_group=mysql_query("SELECT `id`, `name` FROM `doc_base_gparams` ORDER BY `name`");
					while($group=mysql_fetch_row($res_group))
					{
						$ret.="<option value='-1' disabled>$group[1]</option>";
						$res=mysql_query("SELECT `id`, `param`, `ym_assign` FROM `doc_base_params` WHERE `pgroup_id`='$group[0]' ORDER BY `param`");
						while($param=mysql_fetch_row($res))
						{
							$warn=$param[2]?'(!)':'';
							$ret.="<option value='$param[0]'>- $param[1] $warn</option>";
						}
					}
					$ret.="</select>";
					return $ret;
				}

				if($f)
				{
					$values=array();

					mb_internal_encoding('UTF-8');
					$rows = $table->getElementsByTagName('tr');
					$prefix=$param='';
					foreach ($rows as $row)
					{
						for($item=$row->firstChild;$item!=null;$item=$item->nextSibling)
						{
							$class=$item->getAttribute('class');
							if(strpos($class,'b-properties__title')!==false)
								$prefix=$item->nodeValue;
							else if(strpos($class,'b-properties__label')!==false)
								$param=$item->nodeValue;
							else if(strpos($class,'b-properties__value')!==false)
								$values["$prefix:$param"]=$item->nodeValue;
						}

					}
					$tmpl->AddText("
					<form method='post' action='/docs.php'>
					<input type='hidden' name='l' value='sklad'>
					<input type='hidden' name='mode' value='esave'>
					<input type='hidden' name='param' value='y'>
					<input type='hidden' name='pos' value='$pos'>
					<input type='hidden' name='ym_id' value='$ym_id'>
					<table class='list'>
					<tr><th>Параметр Яндекс Маркета</th><th>Ассоциированный параметр</th><th>Значение из Яндекс Маркета</th></tr>");
					$i=0;
					foreach($values as $param => $value)
					{
						$tmpl->AddText("<tr><td><input type='checkbox' name='ch[$i]' value='$param' checked>$param</td><td>".getSelectParams($i,$param)."</td><td><input type='text' name='val[$i]' value='$value' style='width: 400px'></td></tr>");
						$i++;
					}
					$tmpl->AddText("</table>
					<label><input type='checkbox' name='id_save' value='1' checked>Сохранить новый ID</label><br>
					<label><input type='checkbox' name='auto' value='1' checked>Автоматически ассоциировать одноимённые параметры</label><br>
					<label><input type='checkbox' name='create' value='1'>Создать и ассоциировать отсутствующие (не рекомендуется, т.к. это может нарушить авторские права)</label><br>
					<label><input type='checkbox' name='to_collection' value='1'>Добавить создаваемые параметры в категорию</label><br>
					<select name='collection'>
					<option value='0'>--не выбран--</option>");
					$rgroups=mysql_query("SELECT `id`, `name` FROM `doc_base_pcollections_list` ORDER BY `name`");
					if(mysql_errno())	throw new MysqlException("Не удалось получить наборы свойств складской номенклатуры");
					while($col=mysql_fetch_row($rgroups))
					{
						$tmpl->AddText("<option value='$col[0]'>$col[1]</option>");
					}
					$tmpl->AddText("</select>
					<button>Записать</button>
					</form>");
				}

			}
		}
		else $tmpl->msg("Неизвестная закладка");
	}
	function ESave()
	{
		global $tmpl, $CONFIG, $uid;
		doc_menu();
		$pos=rcv('pos');
		$param=rcv('param');
		$group=rcv('g');

		if($pos!=0)
		{
			$this->PosMenu($pos, $param);
		}

		if($param=='')
		{
			$pd=@$_REQUEST['pd'];
			$sr=@$_REQUEST['sr'];

			if( ($pos)&&(!$sr) )
			{
				if(!isAccess('list_sklad','edit'))	throw new AccessException("");
				$sql_add=$log_add='';
				$res=mysql_query("SELECT `id`,`group`, `name`, `desc`, `proizv`, `cost`, `likvid`, `hidden`, `unit`, `vc`, `stock`, `warranty`, `warranty_type`, `no_export_yml`, `country`, `title_tag`, `meta_keywords`, `meta_description` FROM `doc_base` WHERE `id`='$pos'");
				if(mysql_errno())	throw new MysqlException("Не удалось получить старые свойства позиции!");
				$old_data=mysql_fetch_assoc($res);

				foreach($old_data as $id=>$value)
				{
					if($id=='id' || $id=='likvid')	continue;
					if(!isset($pd[$id]))	$pd[$id]=0;
					if($pd[$id]!=$value)
					{
						if($id=='country')
						{
							if(!$pd[$id] && !$value)	continue;
							$new_val=intval($pd[$id]);
							if(!$new_val)	$new_val='NULL';
						}
						else if($id=='cost')
						{
							$cost=sprintf("%0.2f",$pd[$id]);
							$new_val="'$cost', `cost_date`=NOW()";
						}
						else	$new_val="'".mysql_real_escape_string($pd[$id])."'";

						$log_add.=", $id:($value => {$pd[$id]})";
						$sql_add.=", `$id`=$new_val";
					}
				}

				if($sql_add)
				{
					$res=mysql_query("UPDATE `doc_base` SET `id`=`id` $sql_add WHERE `id`='$pos'");
					if(mysql_errno())	throw new MysqlException("Не удалось обновить свойства позиции!");
					$tmpl->msg("Данные обновлены!");
					doc_log("UPDATE","$log_add", 'pos', $pos);
				}
				else $tmpl->msg("Ничего не было изменено",'info');
			}
			else
			{
				if(!isAccess('list_sklad','create'))	throw new AccessException("");
				$fields=array('name', 'vc', 'group', 'proizv', 'desc', 'cost', 'stock', 'pos_type', 'hidden', 'unit', 'warranty', 'warranty_type', 'no_export_yml', 'country', 'title_tag', 'meta_keywords', 'meta_description');
				$cols=$values=$log='';
				foreach($fields as $field)
				{
					$cols.="`$field`,";
					$values.="'".mysql_real_escape_string(@$pd[$field])."',";
					$log.="$field:".@$pd[$field].", ";
				}
				$cols.="`cost_date`";
				$values.="NOW()";
				$res=mysql_query("INSERT INTO `doc_base` ($cols) VALUES	($values)");
				if(mysql_errno())	throw new MysqlException("Ошибка сохранения основной информации.");
				$opos=$pos;
				$pos=mysql_insert_id();
				if($opos)
				{
					$res=mysql_query("SELECT `doc_base_dop`.`type`, `doc_base_dop`.`analog`, `doc_base_dop`.`koncost`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`
					FROM `doc_base_dop`
					WHERE `doc_base_dop`.`id`='$opos'");
					if(mysql_errno())	throw new MysqlException("Ошибка выборки дополнительной информации.");
					$nxt=@mysql_fetch_row($res);
					$res=mysql_query("REPLACE `doc_base_dop` (`id`, `analog`, `koncost`, `type`, `d_int`, `d_ext`, `size`, `mass`)
					VALUES ('$pos', '$nxt[1]', '0', '$nxt[0]', '$nxt[3]', '$nxt[4]', '$nxt[5]', '$nxt[6]')");
					if(mysql_errno())	throw new MysqlException("Ошибка сохранения дополнительной информации.");

				}
				doc_log("INSERT pos",$log,'pos',$pos);
				$this->PosMenu($pos, '');

				$res=mysql_query("SELECT `id` FROM `doc_sklady`");
				if(mysql_errno())	throw new MysqlException("Ошибка выборки складов.");
				while($nxt=mysql_fetch_row($res))
					mysql_query("INSERT INTO `doc_base_cnt` (`id`, `sklad`, `cnt`) VALUES ('$pos', '$nxt[0]', '0')");

				$tmpl->msg("Добавлена новая позиция!<br><a href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;pos=$pos'>Перейти</a>");
			}
		}
		else if($param=='d')
		{
			$analog=rcv('analog');
			$koncost=rcv('koncost',0);
			$type=rcv('type','null');
			$d_int=rcv('d_int',0);
			$d_ext=rcv('d_ext',0);
			$size=rcv('size',0);
			$mass=rcv('mass',0);
			$ntd=rcv('ntd');
			if(!isAccess('list_sklad','edit'))	throw new AccessException("");


			$res=mysql_query("SELECT `analog`, `koncost`, `type`, `d_int`, `d_ext`, `size`, `mass`, `ntd` FROM `doc_base_dop` WHERE `id`='$pos'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить дополнительные параметры!");
			$old_data=mysql_fetch_assoc($res);
			$log_add='';
			if($old_data['analog']!=$analog)
				$log_add.=", analog:({$old_data['analog']} => $analog)";
			if($old_data['koncost']!=$koncost)
				$log_add.=", koncost:({$old_data['koncost']} => $koncost)";
			if($old_data['type']!=$type && ($old_data['type']!='' || $type!='null'))
				$log_add.=", type:({$old_data['type']} => $type)";
			if($old_data['d_int']!=$d_int)
				$log_add.=", d_int:({$old_data['d_int']} => $d_int)";
			if($old_data['d_ext']!=$d_ext)
				$log_add.=", d_ext:({$old_data['d_ext']} => $d_ext)";
			if($old_data['size']!=$size)
				$log_add.=", size:({$old_data['size']} => $size)";
			if($old_data['mass']!=$mass)
				$log_add.=", mass:({$old_data['mass']} => $mass)";
			if($old_data['ntd']!=$ntd)
				$log_add.=", ntd:({$old_data['ntd']} => $ntd)";

			if($type!=='null')	$type="'$type'";
			$res=mysql_query("REPLACE `doc_base_dop` (`id`, `analog`, `koncost`, `type`, `d_int`, `d_ext`, `size`, `mass`, `ntd`) VALUES ('$pos', '$analog', '$koncost', $type, '$d_int', '$d_ext', '$size', '$mass', '$ntd')");
			if(mysql_errno())	throw new MysqlException("Не удалось установить дополнительные параметры!");

			$res=mysql_query("SELECT `param_id`, `value` FROM `doc_base_values` WHERE `id`='$pos'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить дополнительные параметры!");
			$dp=array();
			while($nxt=mysql_fetch_row($res))	$dp[$nxt[0]]=$nxt[1];
			$par=@$_POST['par'];
			if(is_array($par))
			{
				foreach($par as $key => $value)
				{
					$key=mysql_real_escape_string($key);
					$value=mysql_real_escape_string($value);
					if($dp[$key]!=$value)
						$log_add.=", $key:({$old_data[$key]} => $value)";
					mysql_query("REPLACE `doc_base_values` (`id`, `param_id`, `value`) VALUES ('$pos', '$key', '$value')");
					if(mysql_errno())	throw new MysqlException("Не удалось обновить дополнительные параметры!");
				}
			}

			$par_add=rcv('par_add');
			$value_add=rcv('value_add');
			if($par_add && $value_add)
			{
				mysql_query("REPLACE `doc_base_values` (`id`, `param_id`, `value`) VALUES ('$pos', '$par_add', '$value_add')");
				if(mysql_errno())	throw new MysqlException("Не удалось добавить дополнительные параметры!");
				if($dp[$key]!=$value)
					$log_add.=", $par_add:$value_add";
			}
			if($log_add)	doc_log("UPDATE","$log_add",'pos',$pos);
			$tmpl->msg("Данные сохранены!");
		}
		else if($param=='s')
		{
			if(!isAccess('list_sklad','edit'))	throw new AccessException("");
			$res=mysql_query("SELECT `doc_sklady`.`name`, `doc_base_cnt`.`cnt`, `doc_base_cnt`.`mincnt`,  `doc_base_cnt`.`mesto`, `doc_base_cnt`.`sklad`
			FROM `doc_base_cnt`
			LEFT JOIN `doc_sklady` ON `doc_sklady`.`id` = `doc_base_cnt`.`sklad`
			WHERE `doc_base_cnt`.`id`='$pos'");
			$log_add='';
			while($nxt=@mysql_fetch_row($res))
			{
				$mincnt=round(rcv("min$nxt[4]"));
				$mesto=rcv("mesto$nxt[4]");
				if($nxt[2]!=$mincnt)
					$log_add.=", mincnt:({$nxt[2]} => $mincnt)";
				if($nxt[3]!=$mesto)
					$log_add.=", mesto:({$nxt[3]} => $mesto)";
				if($nxt[2]!=$mincnt || $nxt[3]!=$mesto)
				{
					mysql_query("UPDATE `doc_base_cnt` SET `mincnt`='$mincnt', `mesto`='$mesto' WHERE `id`='$pos' AND `sklad`='$nxt[4]'");
					if(mysql_errno())	throw new MysqlException("Не удалось обновить места и значения минимального количества!");
				}

			}
			if($log_add)	doc_log("UPDATE","$log_add",'pos',$pos);
		}
		else if($param=='i')
		{
			$id=0;
			$max_fs=get_max_upload_filesize();
			$max_img_size=min(8*1024*1204,$max_fs);
			$min_pix=15;
			$max_pix=20000;
			global $CONFIG;

			$def_img=round(rcv('def_img'));
			if(!isAccess('list_sklad','edit'))	throw new AccessException("");

			for($img_num=1;$img_num<=5;$img_num++)
			{
				$set_def=0;
				if($def_img==$img_num)	$set_def=1;
				$nm=rcv('photoname_'.$img_num);

				$res=mysql_query("SELECT `id` FROM `doc_img` WHERE `name`='$nm'");
				if(mysql_num_rows($res))
				{
					$img_id=mysql_result($res,0,0);
					$tmpl->msg("Эта картинка найдена, N $img_id","info");
				}
				else
				{
					if($_FILES['userfile'.$img_num]['size']<=0)
						$tmpl->msg("Файл не получен. Возможно он не был выбран, либо его размер превышает максимально допустимый сервером.",'err');
					else
					{
						if($_FILES['userfile'.$img_num]['size']>$max_img_size)
							$tmpl->msg("Слишком большой файл! Допустимо не более $max_img_size байт!",'err');
						else
						{
							$iminfo=getimagesize($_FILES['userfile'.$img_num]['tmp_name']);
							switch ($iminfo[2])
							{
								case IMAGETYPE_JPEG: $imtype='jpg'; break;
								case IMAGETYPE_PNG: $imtype='png'; break;
								case IMAGETYPE_GIF: $imtype='gif'; break;
								default: $imtype='';
							}
							if(!$imtype) $tmpl->msg("Файл - не картинка, или неверный формат файла. Рекомендуется PNG и JPG, допустим но не рекомендуется GIF.",'err');
							else if(($iminfo[0]<$min_pix)||($iminfo[1]<$min_pix))
							$tmpl->msg("Слишком мелкая картинка! Минимальный размер - $min_pix пикселей!",'err');
							else if(($iminfo[0]>$max_pix)||($iminfo[1]>$max_pix))
							$tmpl->msg("Слишком большая картинка! Максимальный размер - $max_pix пикселей!",'err');
							else
							{
								mysql_query("START TRANSACTION");
								mysql_query("INSERT INTO `doc_img` (`name`, `type`)	VALUES ('$nm', '$imtype')");
								$img_id=mysql_insert_id();
								if($img_id)
								if(move_uploaded_file($_FILES['userfile'.$img_num]['tmp_name'],$CONFIG['site']['var_data_fs'].'/pos/'.$img_id.'.'.$imtype))
								{
									mysql_query("COMMIT");
									$tmpl->msg("Файл загружен, $img_id.$imtype","info");
								}
								else
								{
									$tmpl->msg("Файл не загружен, $img_id.$imtype","err");
									$img_id=false;
								}
							}
						}
					}
				}
				if($img_id)
				{
					if($set_def)	mysql_query("UPDATE `doc_base_img` SET `default`='0' WHERE `pos_id`='$pos'");
					mysql_query("INSERT INTO `doc_base_img` (`pos_id`, `img_id`, `default`) VALUES ('$pos', '$img_id', '$set_def')");
					doc_log("UPDATE","Add image (id:$img_id)", 'pos', $pos);
				}
			}

		}
		else if($param=='i_a')
		{
			$attachment_id=0;
			global $CONFIG;
			$comment=rcv('comment');
			if(!isAccess('list_sklad','edit'))	throw new AccessException("");
			mysql_query("START TRANSACTION");
			$res=mysql_query("SELECT `id` FROM `attachments` WHERE `comment`='$comment'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить коментарии");
			if(mysql_num_rows($res))
			{
				$attachment_id=mysql_result($res,0,0);
				$tmpl->msg("Этот файл найден, N $attachment_id","info");
			}
			else
			{
				if($_FILES['userfile']['size']<=0)	throw new Exception("Файл не получен. Возможно он не был выбран, либо его размер превышает максимально допустимый сервером");

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
				$filename = str_replace(" ", "_", $filename);
				$filename = mysql_real_escape_string($filename);
				$comment = mysql_real_escape_string($comment);
				mysql_query("INSERT INTO `attachments` (`original_filename`, `comment`)	VALUES ('$filename', '$comment')");
				if(mysql_errno())	throw new MysqlException("Не удалось сохранить информацию о файле в базу данных");
				$attachment_id=mysql_insert_id();
				if(!$attachment_id)	throw new MysqlException("Не удалось получить ID строки");
				if(!file_exists($CONFIG['site']['var_data_fs'].'/attachments/'))
				{
					if(!mkdir($CONFIG['site']['var_data_fs'].'/attachments/', 0777, true))	throw new Exception("Не удалось создать директорию для прикреплённых файлов. Вероятно, права доступа установлены неверно.");
				}
				else if(!is_dir($CONFIG['site']['var_data_fs'].'/attachments/'))	throw new Exception("Вместо директории для прикреплённых файлов обнаружен файл. Обратитесь к администратору.");

				if(!move_uploaded_file($_FILES['userfile']['tmp_name'], $CONFIG['site']['var_data_fs'].'/attachments/'.$attachment_id))
					throw new Exception("Не удалось сохранить файл");
				$tmpl->msg("Файл загружен, ID:$attachment_id","info");
			}
			if($attachment_id)
			{
				mysql_query("INSERT INTO `doc_base_attachments` (`pos_id`, `attachment_id`) VALUES ('$pos', '$attachment_id')");
				if(mysql_errno())	throw new MysqlException("Не удалось внести запись о прикреплении файла");
			}

			mysql_query("COMMIT");
			doc_log("UPDATE","Add attachment (id:$attachment_id, $filename, $comment)", 'pos', $pos);

		}
		else if($param=='i_d')
		{
			if(!isAccess('list_sklad','edit'))	throw new AccessException("");
			$img=rcv('img');
			mysql_query("DELETE FROM `doc_base_img` WHERE `pos_id`='$pos' AND `img_id`='$img'");
			if(mysql_errno())	throw new MysqlException("Не удалось удалить ассоциацию");
			doc_log("UPDATE","delete image (id:$img)", 'pos', $pos);
			$tmpl->msg("Ассоциация с изображением удалена! Для продолжения работы воспользуйтесь меню!","ok");


		}
		else if($param=='i_ad')
		{
			if(!isAccess('list_sklad','edit'))	throw new AccessException("");
			$att=rcv('att');
			mysql_query("DELETE FROM `doc_base_attachments` WHERE `pos_id`='$pos' AND `attachment_id`='$att'");
			if(mysql_errno())	throw new MysqlException("Не удалось удалить ассоциацию");
			doc_log("UPDATE","delete attachment (id:$att)", 'pos', $pos);
			$tmpl->msg("Ассоциация с присоединённым файлом удалена! Для продолжения работы воспользуйтесь меню!","ok");
		}
		else if($param=='c')
		{
			if(!isAccess('list_sklad','edit'))	throw new AccessException("");
			$res=mysql_query("SELECT `doc_cost`.`id`, `doc_base_cost`.`id`, `doc_base_cost`.`type`, `doc_base_cost`.`value`, `doc_base_cost`.`accuracy`, `doc_base_cost`.`direction`
			FROM `doc_cost`
			LEFT JOIN `doc_base_cost` ON `doc_cost`.`id`=`doc_base_cost`.`cost_id` AND `doc_base_cost`.`pos_id`='$pos'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список цен");
			$log='';
			mysql_query("START TRANSACTION");
			while($nxt=mysql_fetch_row($res))
			{

				$ch=rcv('ch'.$nxt[0]);
				$cost_type=rcv('cost_type'.$nxt[0]);
				$val=rcv('val'.$nxt[0]);
				$accur=round(rcv('accur'.$nxt[0]));
				$direct=round(rcv('direct'.$nxt[0]));

				if($nxt[1] && (!$ch))
				{
					mysql_query("DELETE FROM `doc_base_cost` WHERE `id`='$nxt[1]'");
					if(mysql_errno())	throw new MysqlException("Не удалось удалить заданную цену");
					$log.="DELETE cost ID:$nxt[0] - type:$nxt[6], value:$nxt[7]; ";
				}
				else if($nxt[1] && $ch)
				{

					$update=$changes='';
					if($nxt[2]!=$cost_type)
					{
						$update.=", `type`='$cost_type'";
						$changes.="type:{$nxt[2]}=>{$cost_type}, ";
					}
					if($nxt[3]!=$val)
					{
						$update.=", `value`='$val'";
						$changes.="value:{$nxt[3]}=>{$val}, ";
					}
					if($nxt[4]!=$accur)
					{
						$update.=", `accuracy`='$accur'";
						$changes.="accuracy:{$nxt[4]}=>{$accur}, ";
					}
					if($nxt[5]!=$direct)
					{
						$update.=", `direction`='$direct'";
						$changes.="direction:{$nxt[5]}=>{$direct}, ";
					}
					if($update)
					{
						mysql_query("UPDATE `doc_base_cost` SET `id`=`id` $update WHERE `id`='$nxt[1]'");
						if(mysql_errno())	throw new MysqlException("Не удалось изменить заданную цену");
						$log.="UPDATE cost ID:$nxt[0] - $changes ";
					}
				}
				else if($ch)
				{
					mysql_query("INSERT INTO `doc_base_cost` (`cost_id`, `pos_id`, `type`, `value`, `accuracy`, `direction`)
					VALUES ('$nxt[0]', '$pos', '$cost_type', '$val', '$accur', '$direct')");
					if(mysql_errno())	throw new MysqlException("Не удалось записать заданную цену");
					$log.="INSERT cost ID:$nxt[0] - type:$cost_type, value:$val, accuracy:$accur, direction:$direct;";
				}


			}
			$tmpl->msg("Изменения сохранены!","ok");
			if($log)	doc_log('UPDATE pos-ceni', $log, 'pos', $pos);
			mysql_query("COMMIT");
		}
		else if($param=='k')
		{
			if(!isAccess('list_sklad','edit'))	throw new AccessException("");
			$zp=rcv('zp');
			$res=mysql_query("SELECT `doc_base_params`.`id`, `doc_base_values`.`value` FROM `doc_base_params`
			LEFT JOIN `doc_base_values` ON `doc_base_values`.`param_id`=`doc_base_params`.`id` AND `doc_base_values`.`id`='$pos'
			WHERE `doc_base_params`.`param`='ZP'");
			if(mysql_errno())	throw new MysqlException("Не удалось выбрать доп.свойство товара");
			if(!mysql_num_rows($res))
			{
				mysql_query("INSERT INTO `doc_base_params` (`param`, `type`, `system`, `pgroup_id`) VALUES ('ZP', 'double', '1','1')");
				if(mysql_errno())	throw new MysqlException("Не удалось добавить доп.свойство товара");
				$nxt=array(0 => mysql_insert_id(), 1 => 0);
			}
			else $nxt=mysql_fetch_row($res);
			if($zp!=$nxt[1])
			{
				mysql_query("REPLACE `doc_base_values` (`id`, `param_id`, `value`) VALUES ('$pos', '$nxt[0]', '$zp')");
				if(mysql_errno())	throw new MysqlException("Не удалось обновить дополнительные параметры!");
				doc_log("UPDATE pos","ZP: ($nxt[1] => $zp)", 'pos', $pos);
				$tmpl->msg("Данные обновлены!","ok");
			}
			else	$tmpl->msg("Ничего не изменилось!");
		}
		else if($param=='g')
		{
			if(!isAccess('list_sklad','edit'))	throw new AccessException("");
			$max_size=100;
			$name=rcv('name');
			$desc=rcv('desc');
			$pid=rcv('pid');
			$hid=rcv('hid');
			$pname=rcv('pname');
			$title_tag=rcv('title_tag');
			$meta_keywords=rcv('meta_keywords');
			$meta_description=rcv('meta_description');
			$collection=rcv('collection');
			settype($collection,'int');
			$no_export_yml=rcv('no_export_yml');
			if($group)
			{
				$res=mysql_query("UPDATE `doc_group` SET `name`='$name', `desc`='$desc', `pid`='$pid', `hidelevel`='$hid', `printname`='$pname', `no_export_yml`='$no_export_yml', `title_tag`='$title_tag', `meta_keywords`='$meta_keywords', `meta_description`='$meta_description' WHERE `id` = '$group'");
			}
			else
			{
				$res=mysql_query("INSERT INTO `doc_group` (`name`, `desc`, `pid`, `hidelevel`, `printname`, `no_export_yml`, `title_tag`, `meta_keywords`, `meta_description`)
				VALUES ('$name', '$desc', '$pid', '$hid', '$pname', '$no_export_yml', '$title_tag', '$meta_keywords', '$meta_description' )");
			}
			if(mysql_errno())	throw new MysqlException("Не удалось сохранить информацию группы");

			mysql_query("DELETE FROM `doc_group_params` WHERE `group_id`='$group'");
			$fn=@$_POST['fn'];
			if(is_array($fn))
			{
				foreach($fn as $id => $val)
				{
					settype($id,'int');
					$show=(@$_POST['fc'][$id])?'1':'0';
					mysql_query("INSERT INTO `doc_group_params` (`group_id`, `param_id`, `show_in_filter`) VALUES ('$group', '$id', '$show')");
				}
			}
			if($collection)
			{
				$rparams=mysql_query("SELECT `doc_base_pcollections_set`.`param_id`
				FROM `doc_base_pcollections_set`
				WHERE `collection_id`='$collection'");
				if(mysql_errno())	throw new MysqlException("Не удалось получить параметры из набора");
				while($param=mysql_fetch_row($rparams))
				{
					mysql_query("INSERT INTO `doc_group_params` (`group_id`, `param_id`, `show_in_filter`) VALUES ('$group', '$param[0]', '0')");
				}
			}

			if($_FILES['userfile']['size']>0)
			{
				if($_FILES['userfile']['size']>$max_size*1024)
					throw new Exception("Слишком большой файл! Допустимо не более $max_size кб!");
				else
				{
					$iminfo=getimagesize($_FILES['userfile']['tmp_name']);
					switch ($iminfo[2])
					{
						case IMAGETYPE_JPEG: $imtype='jpg'; break;
						default: $imtype='';
					}
					if(!$imtype) throw new Exception("Неверный формат файла! Допустимы только изображения в формате jpeg.");
					else if(($iminfo[0]<50)||($iminfo[1]<50))
					throw new Exception("Слишком мелкая картинка! Минимальный размер - 50*50 пикселей!");
					else if(($iminfo[0]>200)||($iminfo[1]>200))
					throw new Exception("Слишком большая картинка! Максимальный размер - 200*200 пикселей!");
					if(!move_uploaded_file($_FILES['userfile']['tmp_name'], "{$CONFIG['site']['var_data_fs']}/category/$group.jpg"))
							throw new Exception("Не удалось записать изображение. Проверьте права доступа к директории {$CONFIG['site']['var_data_fs']}/category/");
				}
			}
			$tmpl->msg("Сохранено!");
		}
		else if($param=='gid')
		{
			if(!isAccess('list_sklad','edit'))	throw new AccessException("");
			if(!file_exists("{$CONFIG['site']['var_data_fs']}/category/$group.jpg"))
				throw new Exception("Изображение не найдено");
			if(!unlink("{$CONFIG['site']['var_data_fs']}/category/$group.jpg"))
				throw new Exception("Не удалось удалить изображение! Проверьте права доступа!");
			$tmpl->msg("Изображение удалено!","ok");
		}
		else if($param=='gc')
		{
			if(!isAccess('list_sklad','edit'))	throw new AccessException("");
			$res=mysql_query("SELECT `doc_cost`.`id`, `doc_group_cost`.`id`, `doc_group_cost`.`type`, `doc_group_cost`.`value`, `doc_group_cost`.`accuracy`, `doc_group_cost`.`direction`
			FROM `doc_cost`
			LEFT JOIN `doc_group_cost` ON `doc_cost`.`id`=`doc_group_cost`.`cost_id` AND `doc_group_cost`.`group_id`='$group'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список цен");
			$log='';
			mysql_query("START TRANSACTION");
			while($nxt=mysql_fetch_row($res))
			{

				$ch=rcv('ch'.$nxt[0]);
				$cost_type=rcv('cost_type'.$nxt[0]);
				$val=rcv('val'.$nxt[0]);
				$accur=rcv('accur'.$nxt[0]);
				$direct=rcv('direct'.$nxt[0]);
				if($nxt[1] && (!$ch))
				{
					mysql_query("DELETE FROM `doc_group_cost` WHERE `id`='$nxt[1]'");
					if(mysql_errno())	throw new MysqlException("Не удалось удалить заданную цену");
					$log.="DELETE cost ID:$nxt[0] - type:$nxt[6], value:$nxt[7]; ";
				}
				else if($nxt[1] && $ch)
				{
					$update=$changes='';
					if($nxt[2]!=$cost_type)
					{
						$update=", `type`='$cost_type'";
						$changes="type:{$nxt[2]}=>{$cost_type}, ";
					}
					if($nxt[3]!=$val)
					{
						$update=", `value`='$val'";
						$changes="value:{$nxt[3]}=>{$val}, ";
					}
					if($nxt[4]!=$accur)
					{
						$update=", `accuracy`='$accur'";
						$changes="accuracy:{$nxt[4]}=>{$accur}, ";
					}
					if($nxt[5]!=$direct)
					{
						$update=", `direction`='$direct'";
						$changes="direction:{$nxt[5]}=>{$direct}, ";
					}
					if($update)
					{
						mysql_query("UPDATE `doc_group_cost` SET `id`=`id` $update WHERE `id`='$nxt[1]'");
						if(mysql_errno())	throw new MysqlException("Не удалось изменить заданную цену");
						$log.="UPDATE cost ID:$nxt[0] - $changes ";
					}
				}
				else if($ch)
				{
					mysql_query("INSERT INTO `doc_group_cost` (`cost_id`, `group_id`, `type`, `value`, `accuracy`, `direction`)
					VALUES ('$nxt[0]', '$group', '$cost_type', '$val', '$accur', '$direct')");
					if(mysql_errno())	throw new MysqlException("Не удалось записать заданную цену");
					$log.="INSERT cost ID:$nxt[0] - type:$cost_type, value:$val, accuracy:$accur, direction:$direct; ";
				}


			}
			$tmpl->msg("Изменения сохранены!","ok");
			if($log)	doc_log('UPDATE group-ceni', $log, 'group', $group);
			mysql_query("COMMIT");
		}
		else if($param=='y')
		{
			$url=$_POST['url'];
			$checkboxes=$_POST['ch'];
			$ym_id=$_POST['ym_id'];
			$id_save=@$_POST['id_save'];
			$auto=@$_POST['auto'];
			$create=@$_POST['create'];
			$collection=@$_POST['collection'];
			$to_collection=@$_POST['to_collection'];
			settype($ym_id,'int');
			settype($collection,'int');
			
			if($id_save)
			{
				$res=mysql_query("SELECT `doc_base_params`.`id`, `doc_base_values`.`value` FROM `doc_base_params`
				LEFT JOIN `doc_base_values` ON `doc_base_values`.`param_id`=`doc_base_params`.`id` AND `doc_base_values`.`id`='$pos'
				WHERE `doc_base_params`.`param`='ym_id'");
				if(mysql_errno())	throw new MysqlException("Не удалось выбрать доп.свойство товара");
				if(!mysql_num_rows($res))
				{
					mysql_query("INSERT INTO `doc_base_params` (`param`, `type`, `system`, `pgroup_id`) VALUES ('ym_id', 'double', '1','1')");
					if(mysql_errno())	throw new MysqlException("Не удалось добавить доп.свойство товара");
					$nxt=array(0 => mysql_insert_id(), 1 => 0);
				}
				else $nxt=mysql_fetch_row($res);
				if($ym_id!=$nxt[1])
				{
					mysql_query("REPLACE `doc_base_values` (`id`, `param_id`, `value`) VALUES ('$pos', '$nxt[0]', '$ym_id')");
					if(mysql_errno())	throw new MysqlException("Не удалось обновить дополнительные параметры!");
					doc_log("UPDATE pos","ZP: ($nxt[1] => $ym_id)", 'pos', $pos);
				}
			}
			if(!is_array($checkboxes))	throw new Exception('Не передан набор данных');
			$log_add='';
			foreach($checkboxes as $id => $param)
			{
				$param_e=mysql_real_escape_string($param);
				$res=mysql_query("SELECT `doc_base_params`.`id` FROM `doc_base_params` WHERE `doc_base_params`.`ym_assign`='$param_e'");
				if(mysql_errno())	throw new MysqlException('Не удалось получить ID свойства');
				if(mysql_num_rows($res))
				{
					$int_param=mysql_result($res,0,0);
				}
				else
				{
					$int_param=$_POST['sel'][$id];
					settype($int_param,'int');
					if($int_param<1 && $auto)	
					{
						$res=mysql_query("SELECT `doc_base_params`.`id`, CONCAT(`doc_base_gparams`.`name`,':',`doc_base_params`.`param`) AS `pname`
						FROM `doc_base_params`
						INNER JOIN `doc_base_gparams` ON `doc_base_gparams`.`id`=`doc_base_params`.`pgroup_id`
						WHERE CONCAT(`doc_base_gparams`.`name`,':',`doc_base_params`.`param`)='$param_e'");
						if(mysql_errno())	throw new MysqlException('Не удалось получить одноимённые свойства');
						if($line=@mysql_fetch_row($res))
							$int_param=$line[0];
					}
					if($int_param<1 && $create)
					{
						list($gname,$pname)=mb_split(":",$param,2);
						$gname_sql=mysql_real_escape_string($gname);
						$pname_sql=mysql_real_escape_string($pname);
						$res=mysql_query("SELECT `id`, `name` FROM `doc_base_gparams` WHERE `name` = '$gname_sql'");
						if(mysql_errno())	throw new MysqlException('Не удалось получить данные групп');
						if(mysql_num_rows($res)>0)
						{
							$g_id=mysql_result($res,0,0);
						}
						else
						{	
							$res=mysql_query("INSERT INTO `doc_base_gparams` (`name`) VALUES ('$gname_sql')");
							if(mysql_errno())	throw new MysqlException('Не удалось добавить группу');
							$g_id=mysql_insert_id();
						}
						$res=mysql_query("SELECT `id`, `param` FROM `doc_base_params` WHERE `pgroup_id`='$g_id' AND `param`='$pname_sql'");
						if(mysql_errno())	throw new MysqlException('Не удалось получить данные наименований');
						if(mysql_num_rows($res)>0)
						{
							$p_id=mysql_result($res,0,0);
						}
						else
						{	
							mysql_query("INSERT INTO `doc_base_params` (`param`, `type`, `pgroup_id`, `ym_assign`) VALUES ('$pname_sql', 'text', '$g_id', '$param_e')");
							if(mysql_errno())	throw new MysqlException('Не удалось добавить параметр');
							$int_param=mysql_insert_id();
							mysql_query("INSERT INTO `doc_base_pcollections_set` (`collection_id`, `param_id`) VALUES ('$collection', '$int_param')");
							if(mysql_errno())	throw new MysqlException('Не удалось расширить набор');
						}	
					}
					if($int_param<1) continue;
					mysql_query("UPDATE `doc_base_params` SET `ym_assign`='$param_e' WHERE `id`='$int_param'");
					if(mysql_errno())	throw new MysqlException('Не удалось обновит привязку');
				}
				if($int_param<1)	continue;
				$val=mysql_real_escape_string($_POST['val'][$id]);
				mysql_query("REPLACE `doc_base_values` (`id`, `param_id`, `value`) VALUES ('$pos', '$int_param', '$val')");
				if(mysql_errno())	throw new MysqlException("Не удалось добавить дополнительные параметры!");
				$log_add.=", $int_param:(=> $val)";

			}
			$tmpl->msg("Данные сохранены!","ok");
			if($log_add)	doc_log("UPDATE","$log_add",'pos',$pos);
		}
		else $tmpl->msg("Неизвестная закладка");
	}

	function draw_level($select, $level)
	{
		$ret='';
		$res=mysql_query("SELECT `id`, `name`, `desc` FROM `doc_group` WHERE `pid`='$level' ORDER BY `id`");
		$i=0;
		$r='';
		if($level==0) $r='IsRoot';
		$cnt=mysql_num_rows($res);
		while($nxt=mysql_fetch_row($res))
		{
			if($nxt[0]==0) continue;
			$item="<a href='' title='$nxt[2]' onclick=\"EditThis('/docs.php?mode=srv&amp;opt=pl&amp;g=$nxt[0]','sklad'); return false;\" >$nxt[1]</a>";

			if($i>=($cnt-1)) $r.=" IsLast";

			$tmp=$this->draw_level($select, $nxt[0]); // рекурсия
			if($tmp)
				$ret.="
				<li class='Node ExpandClosed $r'>
			<div class='Expand'></div>
			<div class='Content'>$item
			</div><ul class='Container'>".$tmp.'</ul></li>';
		else
			$ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div></li>";
			$i++;
		}
		return $ret;
	}


	function draw_groups($select)
	{
		global $tmpl;
		$tmpl->AddText("
		Отбор:<input type='text' id='sklsearch' onkeydown=\"DelayedSave('/docs.php?mode=srv&amp;opt=pl','sklad', 'sklsearch'); return true;\" >
		<div onclick='tree_toggle(arguments[0])'>
		<div><a href='' onclick=\"EditThis('/docs.php?mode=srv&amp;opt=pl&amp;g=0','sklad'); return false;\" >Группы</a>  (<a href='/docs.php?l=sklad&mode=edit&param=g&g=0'><img src='/img/i_add.png' alt=''></a>)</div>
		<ul class='Container'>".$this->draw_level($select,0)."</ul></div>

		");

	}

	function ViewSklad($group=0,$s='')
	{
		global $tmpl, $CONFIG;

		$sklad=$_SESSION['sklad_num'];
		$go=rcv('go');
		$lim=200;
		$vc_add='';
		$sql_add='';
		if($group && !$go)
		{
			$tmpl->AddText("
			<a href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;pos=0&amp;g=$group'><img src='/img/i_add.png' alt=''> Добавить</a> |
			<a href='/docs.php?l=sklad&amp;mode=edit&amp;param=g&amp;g=$group'><img src='/img/i_edit.png' alt=''> Правка группы</a> |
			<a href='/docs.php?l=sklad&amp;mode=search'><img src='/img/i_find.png' alt=''> Расширенный поиск</a> |
			<a href='#' onclick=\"EditThis('/docs.php?mode=srv&amp;opt=pl&amp;g=$group&amp;go=1','sklad'); return false;\" ><img src='/img/i_reload.png' alt=''> Групповые операции</a><br>");
			$res=mysql_query("SELECT `desc` FROM `doc_group` WHERE `id`='$group'");
			$g_desc=mysql_result($res,0,0);
			if($g_desc) $tmpl->AddText("<h4>$g_desc</h4>");
		}
		else if($go)
		{
			$tmpl->AddText("<form action='' method='post'>
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
			".selectGroupPos('to_group',0,1)."
			</fieldset></td>
			</table>
			<br><button type='submit'>Выполнить</button>
			</div>");
			$lim=5000000;
			$vc_add.="<input type='checkbox' id='selall' onclick='return SelAll(this);'>";
			$sql_add=",
			((SELECT COUNT(`doc_base_params`.`id`) FROM `doc_base_params`
			LEFT JOIN `doc_group_params` ON `doc_group_params`.`param_id`=`doc_base_params`.`id`
			WHERE  `doc_group_params`.`group_id`='$group' AND `doc_base_params`.`system`='0' AND `doc_base_params`.`id` IN ( SELECT `doc_base_values`.`param_id` FROM `doc_base_values` WHERE `doc_base_values`.`id`=`doc_base`.`id` AND `doc_base_values`.`value`!=''))/
			(SELECT COUNT(`doc_base_params`.`id`) FROM `doc_base_params`
			LEFT JOIN `doc_group_params` ON `doc_group_params`.`param_id`=`doc_base_params`.`id`
			WHERE  `doc_group_params`.`group_id`='$group' AND `doc_base_params`.`system`='0')*100) AS `ppz`
			";
		}

		switch(@$CONFIG['doc']['sklad_default_order'])
		{
			case 'vc':	$order='`doc_base`.`vc`';	break;
			case 'cost':	$order='`doc_base`.`cost`';	break;
			default:	$order='`doc_base`.`name`';
		}

		$sql="SELECT `doc_base`.`id`,`doc_base`.`group`,`doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`likvid`, `doc_base`.`cost`, `doc_base`.`cost_date`,
		`doc_base_dop`.`koncost`,  `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`,
		`doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt`,
		(SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`), `doc_base`.`vc`, `doc_base`.`hidden`, `doc_base`.`no_export_yml`, `doc_base`.`stock` $sql_add
		FROM `doc_base`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$sklad'
		WHERE `doc_base`.`group`='$group'
		ORDER BY $order";


		$page=rcv('p');
		$res=mysql_query($sql);
		$row=mysql_num_rows($res);
		echo mysql_error();
		$pagebar='';
		if($row>$lim)
		{
			$dop="g=$group";
			if($page<1) $page=1;
			if($page>1)
			{
				$i=$page-1;
				$pagebar.="<a href='' onclick=\"EditThis('/docs.php?l=sklad&amp;mode=srv&amp;opt=pl&amp;$dop&amp;p=$i','sklad'); return false;\">&lt;&lt;</a> ";
			}
			else $pagebar.='<span>&lt;&lt;</span>';
			$cp=$row/$lim;
			for($i=1;$i<($cp+1);$i++)
			{
				if($i==$page) $pagebar.=" <b>$i</b> ";
				else $pagebar.="<a href='' onclick=\"EditThis('/docs.php?l=sklad&amp;mode=srv&amp;opt=pl&amp;$dop&amp;p=$i','sklad'); return false;\">$i</a> ";
			}
			if($page<$cp)
			{
				$i=$page+1;
				$pagebar.="<a href='' onclick=\"EditThis('/docs.php?l=sklad&amp;mode=srv&amp;opt=pl&amp;$dop&amp;p=$i','sklad'); return false;\">&gt;&gt;</a> ";
			}
			else $pagebar.='<span>&gt;&gt;</span>';
			$sl=($page-1)*$lim;
			$pagebar.='<br>';
			$res=mysql_query("$sql LIMIT $sl,$lim");
		}
		else $sl=0;

		if(mysql_num_rows($res))
		{
			if($CONFIG['poseditor']['vc'])		$vc_add.='<th>Код</th>';

			$tdb_add=$CONFIG['poseditor']['tdb']?'<th>Тип<th>d<th>D<th>B':'';
			$rto_add=$CONFIG['poseditor']['rto']?"<th><img src='/img/i_lock.png' alt='В резерве'><th><img src='/img/i_alert.png' alt='Под заказ'><th><img src='/img/i_truck.png' alt='В пути'>":'';

			$cheader_add=($_SESSION['sklad_cost']>0)?'<th>Выб. цена':'';
			$tmpl->AddText("$pagebar<table width='100%' cellspacing='1' cellpadding='2'><tr>
			<th>№ $vc_add<th>Наименование<th>Производитель<th>Цена, р.<th>Ликв.<th>Рыноч.цена, р. $cheader_add<th>Аналог{$tdb_add}<th>Масса{$rto_add}<th>Склад<th>Всего<th>Место");
			$tmpl->AddText("<tr class='lin0'><th colspan='18' align='center'>В группе $row наименований, показаны ".( ($sl+$lim)<$row?$lim:($row-$sl) ).", начиная с $sl" );
			$i=0;
			$this->DrawSkladTable($res,$s);
			$tmpl->AddText("</table>$pagebar");
			if($go)
			{
				$tmpl->AddText("<b>Легенда:</b> Заполненность дополнительных свойств наименования: <b><span style='color: #f00;'>&lt;40%</span>, <span style='color: #f80;'>&lt;60%</span>, <span style='color: #00C;'>&lt;90%</span>, <span style='color: #0C0;'>&gt;90%</span>,</b>");
			}

		}
		else if($group)	$tmpl->msg("В выбранной группе товаров не найдено!");
		else $tmpl->msg("Выберите нужную группу в левом меню");
		if($go)	$tmpl->AddText("</form>");
		else
		{	$tmpl->AddText("<a href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;pos=0&amp;g=$group'><img src='/img/i_add.png' alt=''> Добавить</a>");
			if($group)	$tmpl->AddText(" | <a href='/docs.php?l=sklad&amp;mode=edit&amp;param=g&amp;g=$group'><img src='/img/i_edit.png' alt=''> Правка группы</a>");
			$tmpl->AddText(" | <a href='/docs.php?l=sklad&amp;mode=search'><img src='/img/i_find.png' alt=''> Расширенный поиск</a>");
		}
	}

	function ViewSkladS($group=0,$s)
	{
		global $tmpl, $CONFIG;
		$sf=0;
		$sklad=$_SESSION['sklad_num'];
		$tmpl->AddText("<b>Показаны наименования изо всех групп!</b><br>");
		$vc_add=$CONFIG['poseditor']['vc']?'<th>Код</th>':'';
		$tdb_add=$CONFIG['poseditor']['tdb']?'<th>Тип<th>d<th>D<th>B':'';
		$rto_add=$CONFIG['poseditor']['rto']?"<th><img src='/img/i_lock.png' alt='В резерве'><th><img src='/img/i_alert.png' alt='Под заказ'><th><img src='/img/i_truck.png' alt='В пути'>":'';
		$cheader_add=($_SESSION['sklad_cost']>0)?'<th>Выб. цена':'';
		$tmpl->AddText("<table width='100%' cellspacing='1' cellpadding='2'><tr>
		<th>№{$vc_add}<th>Наименование<th>Производитель<th>Цена, р.<th>Ликв.<th>Рыноч.цена, р. $cheader_add<th>Аналог{$tdb_add}<th>Масса{$rto_add}<th>Склад<th>Всего<th>Место");

		switch($CONFIG['doc']['sklad_default_order'])
		{
			case 'vc':	$order='`doc_base`.`vc`';	break;
			case 'cost':	$order='`doc_base`.`cost`';	break;
			default:	$order='`doc_base`.`name`';
		}

		$sql="SELECT `doc_base`.`id`,`doc_base`.`group`,`doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`likvid`, `doc_base`.`cost`, `doc_base`.`cost_date`,
		`doc_base_dop`.`koncost`,  `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`,
		`doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt`, (SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`), `doc_base`.`vc`, `doc_base`.`hidden`, `doc_base`.`no_export_yml`, `doc_base`.`stock`";

		$sqla=$sql."FROM `doc_base`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$sklad'
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
		WHERE `doc_base`.`name` LIKE '$s%' OR `doc_base`.`vc` LIKE '$s%' ORDER BY $order LIMIT 100";
		$res=mysql_query($sqla);
		if($cnt=mysql_num_rows($res))
		{
			$tmpl->AddText("<tr><th colspan='18' align='center'>Поиск по названию, начинающемуся на $s: найдено $cnt");
			if($cnt>=100)	$tmpl->AddText("<tr style='color: #f00'><td colspan='18' align='center'>Вероятно, показаны не все наименования. Уточните запрос.");
			$this->DrawSkladTable($res,$s);
			$sf=1;
		}

		$sqla=$sql."FROM `doc_base`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$sklad'
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
		WHERE `doc_base_dop`.`analog` LIKE '%$s%' AND `doc_base`.`name` NOT LIKE '%$s%' ORDER BY $order LIMIT 30";
		$res=mysql_query($sqla);
		if($cnt=mysql_num_rows($res))
		{
			$tmpl->AddText("<tr class='lin0'><th colspan='18' align='center'>Поиск аналога, для $s: найдено $cnt");
			if($cnt>=100)	$tmpl->AddText("<tr style='color: #f00'><td colspan='18' align='center'>Вероятно, показаны не все наименования. Уточните запрос.");
			$this->DrawSkladTable($res,$s);
			$sf=1;
		}

		$sqla=$sql."FROM `doc_base`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$sklad'
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
		WHERE (`doc_base`.`name` LIKE '%$s%'  OR `doc_base`.`vc` LIKE '%$s%') AND `doc_base`.`vc` NOT LIKE '$s%' AND `doc_base`.`name` NOT LIKE '$s%' ORDER BY $order LIMIT 100";
		$res=mysql_query($sqla);
		if($cnt=mysql_num_rows($res))
		{
			$tmpl->AddText("<tr class='lin0'><th colspan='18' align='center'>Поиск по названию, содержащему $s: найдено $cnt");
			if($cnt>=100)	$tmpl->AddText("<tr style='color: #f00'><td colspan='18' align='center'>Вероятно, показаны не все наименования. Уточните запрос.");
			$this->DrawSkladTable($res,$s);
			$sf=1;
		}

		$tmpl->AddText("</table><a href='/docs.php?mode=srv&amp;opt=ep&amp;pos=0&amp;g=$group'><img src='/img/i_add.png' alt=''> Добавить</a>");

		if($sf==0)
			$tmpl->msg("По данным критериям товаров не найдено!");
	}

	function Search()
	{
		global $tmpl, $CONFIG;
		$opt=rcv("opt");
		$name=rcv('name');
		$analog=rcv('analog');
		$desc=rcv('desc');
		$proizv=rcv('proizv');
		$mesto=rcv('mesto');
		$di_min=rcv('di_min');
		$di_max=rcv('di_max');
		$de_min=rcv('de_min');
		$de_max=rcv('de_max');
		$size_min=rcv('size_min');
		$size_max=rcv('size_max');
		$m_min=rcv('m_min');
		$m_max=rcv('m_max');
		$cost_min=rcv('cost_min');
		$cost_max=rcv('cost_max');
		$li_min=rcv('li_min');
		$li_max=rcv('li_max');
		$type=getpost('type');

		if($opt=='' || $opt=='s')
		{
			doc_menu();
			$analog_checked=$analog?'checked':'';
			$desc_checked=$desc?'checked':'';
			$tmpl->AddText("<h1>Расширенный поиск</h1>
			<form action='docs.php' method='post'>
			<input type='hidden' name='mode' value='search'>
			<input type='hidden' name='opt' value='s'>
			<table width='100%'>
			<tr><th>Наименование
			<th>Ликвидность
			<th>Производитель
			<th>Тип
			<th>Место на складе
			<tr class='lin1'>
			<td><input type='text' name='name' value='$name'><br><label><input type='checkbox' name='analog' value='1' $analog_checked>Или аналог</label> <label><input type='checkbox' name='desc' value='1' $desc_checked>Или описание</label>
			<td>От: <input type='text' name='li_min' value='$li_min'><br>до: <input type='text' name='li_max' value='$li_max'>
			<td><input type='text' id='proizv' name='proizv' value='$proizv' onkeydown=\"return AutoFill('/docs.php?mode=search&amp;opt=pop_proizv','proizv','proizv_p')\"><br>
			<div id='proizv_p' class='dd'></div>
			<td><select name='type' id='pos_type'>");
			$res=mysql_query("SELECT `id`, `name` FROM `doc_base_dop_type` ORDER BY `id`");
			$tmpl->AddText("<option value='null'>--не выбрано--</option>");
			while($nx=mysql_fetch_row($res))
			{
				$ii="";
				if($nx[0]===$type) $ii=" selected";
				$tmpl->AddText("<option value='$nx[0]' $ii>$nx[0] - $nx[1]</option>");
			}

			$tmpl->AddText("</select>
			<td><input type='text' name='mesto' value='$mesto'>

			<tr>
			<th>Внутренний диаметр
			<th>Внешний диаметр
			<th>Высота
			<th>Масса
			<th>Цена
			<tr class='lin1'>
			<td>От: <input type='text' name='di_min' value='$di_min'><br>до: <input type='text' name='di_max' value='$di_max'>
			<td>От: <input type='text' name='de_min' value='$de_min'><br>до: <input type='text' name='de_max' value='$de_max'>
			<td>От: <input type='text' name='size_min' value='$size_min'><br>до: <input type='text' name='size_max' value='$size_max'>
			<td>От: <input type='text' name='m_min' value='$m_min'><br>до: <input type='text' name='m_max' value='$m_max'>
			<td>От: <input type='text' name='cost_min' value='$cost_min'><br>до: <input type='text' name='cost_max' value='$cost_max'>

			<tr>
			<td colspan='5' align='center'><input type='submit' value='Найти'>
			</table>
			</form>");
		}
		if($opt=='pop_proizv')
		{
			$tmpl->ajax=1;
			$s=rcv('s');
			$res=mysql_query("SELECT `proizv` FROM `doc_base` WHERE LOWER(`proizv`) LIKE LOWER('%$s%') GROUP BY `proizv`  ORDER BY `proizv`LIMIT 20");
			$row=mysql_numrows($res);
			$tmpl->SetText("<div class='pointer' onclick=\"return AutoFillClick('proizv','','proizv_p');\">-- Убрать --</div>");
			while($nxt=mysql_fetch_row($res))
			{
				$i=1;
				$tmpl->AddText("<div class='pointer' onclick=\"return AutoFillClick('proizv','$nxt[0]','proizv_p');\">$nxt[0]</div>");
			}
			if(!$i) $tmpl->AddText("<b>Искомая комбинация не найдена!");
		}
		else if($opt=='s')
		{
			$tmpl->AddText("<h1>Результаты</h1>");
			$sklad=$_SESSION['sklad_num'];

			$sql="SELECT `doc_base`.`id`,`doc_base`.`group`,`doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`likvid`, `doc_base`.`cost`, `doc_base`.`cost_date`,
			`doc_base_dop`.`koncost`,  `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`,
			`doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt`, (SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`), `doc_base`.`vc`, `doc_base`.`hidden`, `doc_base`.`no_export_yml`, `doc_base`.`stock`
			FROM `doc_base`
			LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$sklad'
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
			WHERE 1 ";

			switch($CONFIG['doc']['sklad_default_order'])
			{
				case 'vc':	$order='`doc_base`.`vc`';	break;
				case 'cost':	$order='`doc_base`.`cost`';	break;
				default:	$order='`doc_base`.`name`';
			}

			if($name)
			{
				if(!$analog && !$desc) 	$sql.="AND `doc_base`.`name` LIKE '%$name%'";
				else
				{
					$s="`doc_base`.`name` LIKE '%$name%'";
					if($analog)	$s.=" OR `doc_base_dop`.`analog` LIKE '%$name%'";
					if($desc)	$s.=" OR `doc_base`.`desc` LIKE '%$name%'";
					$sql.="AND ( $s )";
				}

			}
			if($proizv)		$sql.="AND `doc_base`.`proizv` LIKE '%$proizv%'";
			if($mesto)		$sql.="AND `doc_base_cnt`.`mesto` LIKE '$mesto'";
			if($di_min)		$sql.="AND `doc_base_dop`.`d_int` >= '$di_min'";
			if($di_max)		$sql.="AND `doc_base_dop`.`d_int` <= '$di_max'";
			if($li_min)		$sql.="AND `doc_base`.`likvid` >= '$li_min'";
			if($li_max)		$sql.="AND `doc_base`.`likvid` <= '$li_max'";
			if($de_min)		$sql.="AND `doc_base_dop`.`d_ext` >= '$de_min'";
			if($de_max)		$sql.="AND `doc_base_dop`.`d_ext` <= '$de_max'";
			if($size_min)		$sql.="AND `doc_base_dop`.`size` >= '$size_min'";
			if($size_max)		$sql.="AND `doc_base_dop`.`size` <= '$size_max'";
			if($m_min)		$sql.="AND `doc_base_dop`.`mass` >= '$m_min'";
			if($m_max)		$sql.="AND `doc_base_dop`.`mass` <= '$m_max'";
			if($cost_min)		$sql.="AND `doc_base`.`cost` >= '$cost_min'";
			if($cost_max)		$sql.="AND `doc_base`.`cost` <= '$cost_max'";
			if($type!='null')	$sql.="AND `doc_base_dop`.`type` = '$type'";

			$sql.="ORDER BY $order";

			$cheader_add=($_SESSION['sklad_cost']>0)?'<th>Выб. цена':'';
			//$nheader_add=$_SESSION['sklad_cost']?'<th>Выб. цена':'';
			$tmpl->AddText("<table width='100%' cellspacing='1' cellpadding='2'><tr>
			<th>№<th>Наименование<th>Производитель<th>Цена, р.<th>Ликв.<th>Рыноч.цена, р. $cheader_add<th>Аналог<th>Тип<th>d<th>D<th>B
			<th>Масса<th><img src='/img/i_lock.png' alt='В резерве'><th><img src='/img/i_alert.png' alt='Под заказ'><th><img src='/img/i_truck.png' alt='В пути'><th>Склад<th>Всего<th>Место");

			$res=mysql_query($sql);
			if(mysql_errno())	throw new MysqlException("Не удалось получить информацию!");
			if($cnt=mysql_num_rows($res))
			{
				$tmpl->AddText("<tr class='lin0'><th colspan='16' align='center'>Параметрический поиск, найдено $cnt");
				$this->DrawSkladTable($res,$name);
				$sf=1;
			}
			$tmpl->AddText("</table>");


		}
	}


function DrawSkladTable($res,$s)
{
	global $tmpl, $CONFIG;
	$i=0;
	$go=rcv('go');
	while($nxt=mysql_fetch_array($res))
	{
		$rezerv=$CONFIG['poseditor']['rto']?DocRezerv($nxt[0],0):'';
		$pod_zakaz=$CONFIG['poseditor']['rto']?DocPodZakaz($nxt[0],0):'';
		$v_puti=$CONFIG['poseditor']['rto']?DocVPuti($nxt[0],0):'';

		if($rezerv)	$rezerv="<a onclick=\"OpenW('/docs.php?l=inf&mode=srv&opt=rezerv&pos=$nxt[0]'); return false;\"  title='Отобразить документы' href='/docs.php?l=inf&mode=srv&opt=p_zak&pos=$nxt[0]'>$rezerv</a>";

		if($pod_zakaz)	$pod_zakaz="<a onclick=\"ShowPopupWin('/docs.php?l=inf&mode=srv&opt=p_zak&pos=$nxt[0]'); return false;\"  title='Отобразить документы' href='/docs.php?l=inf&mode=srv&opt=p_zak&pos=$nxt[0]'>$pod_zakaz</a>";

		if($v_puti)	$v_puti="<a onclick=\"ShowPopupWin('/docs.php?l=inf&mode=srv&opt=vputi&pos=$nxt[0]'); return false;\"  title='Отобразить документы' href='/docs.php?l=inf&mode=srv&opt=vputi&pos=$nxt[0]'>$v_puti</a>";

		if($nxt[16]!=0)
		{
			$nxt[16]="<a onclick=\"ShowPopupWin('/docs.php?mode=srv&opt=ost&pos=$nxt[0]'); return false;\" title='Отобразить все остатки'>$nxt[16]</a>";
		}

		{
			// Дата цены $nxt[5]
			$dcc=strtotime($nxt[6]);
			$cc="";
			if($dcc>(time()-60*60*24*30*3)) $cc="class=f_green";
			else if($dcc>(time()-60*60*24*30*6)) $cc="class=f_purple";
			else if($dcc>(time()-60*60*24*30*9)) $cc="class=f_brown";
			else if($dcc>(time()-60*60*24*30*12)) $cc="class=f_more";
		}
		$end=date("Y-m-d");

		$info='';
		if($nxt['hidden'])		$info.='H';
		if($nxt['no_export_yml'])	$info.='Y';
		if($nxt['stock'])		$info.='S';
		if($info)			$info="<span style='color: #f00; font-weight: bold'>$info</span>";
		if($go)
		{
			$pz=sprintf("%0.1f",$nxt[21]);
			if($nxt[21]<40)		$color='f00';
			else if($nxt[21]<60)	$color='f80';
			else if($nxt[21]<90)	$color='00C';
			else			$color='0C0';
			$info=" <span style='color: #{$color}; font-weight: bold'>$pz%</span>";
		}
		$nxt[2]=SearchHilight($nxt[2],$s);
		$nxt[8]=SearchHilight($nxt[8],$s);
		$i=1-$i;
		$cost_p=sprintf("%0.2f",$nxt[5]);
		$cost_r=sprintf("%0.2f",$nxt[7]);
		$vc_add=$CONFIG['poseditor']['vc']?"<td>{$nxt['vc']}</th>":'';

		if ($CONFIG['poseditor']['tdb'] == 1) $tdb_add = "<td>$nxt[9]<td>$nxt[10]<td>$nxt[11]<td>$nxt[12]"; else $tdb_add = '';
		if ($CONFIG['poseditor']['rto'] == 1) $rto_add = "<td>$rezerv<td>$pod_zakaz<td>$v_puti"; else $rto_add = '';

		$cb=$go?"<input type='checkbox' name='pos[$nxt[0]]' class='pos_ch' value='1'>":'';
		$cadd=($_SESSION['sklad_cost']>0)?('<td>'.GetCostPos($nxt[0],$_SESSION['sklad_cost'])):'';

		$tmpl->AddText("<tr class='lin$i pointer' oncontextmenu=\"return ShowContextMenu(event, '/docs.php?mode=srv&opt=menu&doc=0&pos=$nxt[0]'); return false;\">
		<td>$cb
		<a href='/docs.php?mode=srv&amp;opt=ep&amp;pos=$nxt[0]'>$nxt[0]</a>
		<a href='' onclick=\"return ShowContextMenu(event, '/docs.php?mode=srv&amp;opt=menu&amp;doc=0&amp;pos=$nxt[0]')\" title='Меню' accesskey=\"S\"><img src='img/i_menu.png' alt='Меню' border='0'></a> $vc_add
		<td align=left>$nxt[2] $info<td>$nxt[3]<td $cc>$cost_p<td>$nxt[4]%<td>$cost_r{$cadd}<td>$nxt[8]{$tdb_add}<td>$nxt[13]{$rto_add}<td>$nxt[15]<td>$nxt[16]<td>$nxt[14]");
	}
}

	// Вывод списка комплектующих позиции
	function ViewKomplList($pos)
	{
		global $tmpl;
		$tmpl->AddText("<table width='100%'>
		<tr><th>N<th>ID<th>Наименование<th>Цена (базовая)<th>Кол-во<th>Стоимость");
		$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`cost`, `doc_base_kompl`.`cnt`  FROM `doc_base_kompl`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_base_kompl`.`kompl_id`
		WHERE `doc_base_kompl`.`pos_id`='$pos'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список комплектующих");
		$i=$sum_p=0;
		while($nxt=mysql_fetch_row($res))
		{
			$i++;
			$sum_p+=$sum=$nxt[2]*$nxt[3];
			$tmpl->AddText("<tr><td>$i<td>$nxt[0]<td>$nxt[1]<td>$nxt[2]<td>$nxt[3]<td>$sum");
		}
		$tmpl->AddText("</table><p align=right id=sum>Итого для сборки позиции используется $i позиций на сумму $sum_p руб.</p>");
	}

	function PosMenu($pos, $param, $pos_name='')
	{
		global $tmpl, $CONFIG;
		$sel=array('v'=>'','d'=>'','a'=>'','s'=>'','i'=>'','c'=>'','k'=>'','l'=>'','h'=>'','y'=>'','t'=>'');
		if($param=='')	$param='v';
		$sel[$param]="class='selected'";

		if($CONFIG['poseditor']['vc'])
			$res=mysql_query("SELECT CONCAT(`doc_base`.`vc`, ' - ', `doc_base`.`name`) FROM `doc_base` WHERE `doc_base`.`id`='$pos'");
		else	$res=mysql_query("SELECT `doc_base`.`name` FROM `doc_base` WHERE `doc_base`.`id`='$pos'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить наименование позиции!");
		$pos_info=mysql_fetch_row($res);
		if($pos_info)
		{
			$tmpl->SetTitle("Редактируем $pos_info[0]");
			$tmpl->AddText("<h1>Редактируем  $pos_info[0]</h1>");
		}

		$tmpl->AddText("
		<ul class='tabs'>
		<li><a {$sel['v']} href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;pos=$pos'>Основные</a></li>
		<li><a {$sel['d']} href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=d&amp;pos=$pos'>Дополнительные</a></li>
		<li><a {$sel['a']} href='/docs.php?l=pran&amp;mode=srv&amp;opt=ep&amp;pos=$pos'>Анализатор</a></li>
		<li><a {$sel['s']} href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=s&amp;pos=$pos'>Состояние складов</a></li>
		<li><a {$sel['i']} href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=i&amp;pos=$pos'>Картинки и файлы</a></li>
		<li><a {$sel['c']} href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=c&amp;pos=$pos'>Цены</a></li>
		<li><a {$sel['k']} href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=k&amp;pos=$pos'>Комплектующие</a></li>
		<li><a {$sel['l']} href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=l&amp;pos=$pos'>Связи</a></li>
		<li><a {$sel['h']} href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=h&amp;pos=$pos'>История</a></li>
		<li><a {$sel['y']} href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=y&amp;pos=$pos'>Импорт Я.Маркет</a></li>
		<li><a {$sel['t']} href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=t&amp;pos=$pos'>Test</a></li>
		</ul>");
	}

};


?>
