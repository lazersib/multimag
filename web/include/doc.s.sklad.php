<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2010, BlackLight, TND Team, http://tndproject.org
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
$doc_types[1]="Склад";
// [ \s][^$][\d\w]{0,10}=[^'\\]

class doc_s_Sklad
{
	function View()
	{
		global $tmpl;
		doc_menu(0,0);
		
		$sklad=rcv('sklad');
		settype($sklad,'int');
		if($sklad) $_SESSION['sklad_num']=$sklad;
		if(!$_SESSION['sklad_num']) $_SESSION['sklad_num']=1;
		$sklad=$_SESSION['sklad_num'];
		
		$tmpl->AddText("<table width='100%'><tr><td width='300'><h1>Склад</h1>
		<td align='right'>
		<form action='' method='post'>
		<input type='hidden' name='l' value='sklad'>
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
		global $tmpl;
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
// 			$res=mysql_query("SELECT `name`,`proizv` FROM `doc_base` WHERE `id`='$pos'");
// 			$tov=mysql_result($res,0,0).":".mysql_result($res,0,1);
			$dend=date("Y-m-d");
			$tmpl->AddText("
			<a href='' onclick=\"ShowPopupWin('/docs.php?l=pran&mode=srv&opt=ceni&pos=$pos'); return false;\" ><div>Где и по чём</a></div>
			<a href='/docj.php?mode=filter&opt=fsn&tov_id=$pos&tov_name=$pos&date_to=$dend'><div>Товар в журнале</div></a>
			<a href='/docs.php?mode=srv&amp;opt=ep&amp;pos=$pos'><div>Редактирование позиции</div></a>");
		}
		else if($opt=='ac')
		{
			$q=rcv('q');
			$i=0;
			$tmpl->ajax=1;
			$res=mysql_query("SELECT `id`, `name`, `proizv` FROM `doc_base` WHERE LOWER(`name`) LIKE LOWER('%$q%') ORDER BY `name`");
			$row=mysql_numrows($res);
			while($nxt=mysql_fetch_row($res))
			{
				$i=1;
				$nxt[1]=unhtmlentities($nxt[1]);
				$tmpl->AddText("$nxt[1]|$nxt[0]|$nxt[2]\n");
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
		else $tmpl->msg("Неверный режим!");
	}
		
// Служебные функции класса
	function Edit()
	{
		global $tmpl, $CONFIG;
		doc_menu();
		$pos=rcv('pos');
		$param=rcv('param');
		$group=rcv('g');

		if(($pos==0)&&($param!='g')) $param='';

		if($pos!=0)
		{
			$this->PosMenu($pos, $param);
		}
		
		if($param=='')
		{
			$res=mysql_query("SELECT `doc_base`.`group`, `doc_base`.`name`, `doc_base`.`desc`, `doc_base`.`proizv`, `doc_base`.`cost`, `doc_base`.`likvid`, `doc_img`.`id`, `doc_img`.`type`, `doc_base`.`pos_type`, `doc_base`.`hidden`, `doc_base`.`unit`, `doc_base`.`vc`
			FROM `doc_base`
			LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id` AND `doc_base_img`.`default`='1'
			LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
			WHERE `doc_base`.`id`='$pos'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить информацию о товаре");
			$nxt=@mysql_fetch_row($res);
			$cc='';
			if($nxt[6]) $cc="<td rowspan='8'><img src='{$CONFIG['site']['var_data_web']}/pos/$nxt[6].$nxt[7]' alt='$nxt[1]'>";
			if(!$nxt) 
			{
				$tmpl->AddText("<h3>Новая позиция</h3>");
				$cc.="<tr class='lin1'><td align='right'>Вид:<td>
				<label><input type='radio' name='pos_type' value='0' checked>Товар</label> 
				<label><input type='radio' name='pos_type' value='1'>Услуга</label>";
			}
			else
			{
				$cc.="<tr class='lin1'><td align='right'>Вид:<td>";
				if($nxt[8]) $cc.="<input type='hidden' name='pos_type' value='1'>Услуга";
				else $cc.="<input type='hidden' name='pos_type' value='0'>Товар";
			}
			$tmpl->AddText("<form action='' method='post'>
			<table cellpadding='0' width='100%'>
			<input type='hidden' name='mode' value='esave'>
			<input type='hidden' name='l' value='sklad'>
			<input type='hidden' name='pos' value='$pos'>
        		<tr class='lin0'><td align='right' width='20%'>Наименование
        		<td><input type='text' name='pos_name' value='$nxt[1]' style='width: 95%'>$cc
        		<tr class='lin0'><td align='right'>Производитель
			<td><input type='text' name='proizv' value='$nxt[3]' id='proizv_nm' style='width: 95%'><br>
			<div id='proizv_p' class='dd'></div>
				
        		<tr class='lin1'><td align='right'>Группа
        		<td><select name='g'>");

			if((($pos!=0)&&($nxt[0]==0))||($group==0)) $i=" selected";
			$tmpl->AddText("<option value='0' $i>--</option>");
			
			$res=mysql_query("SELECT * FROM `doc_group`");
			while($nx=mysql_fetch_row($res))
			{
				$i="";
				
				if((($pos!=0)&&($nx[0]==$nxt[0]))||($group==$nx[0])) $i=" selected";
				$tmpl->AddText("<option value='$nx[0]' $i>$nx[1]</option>");
			}
			$i='';
			$act_cost=sprintf('%0.2f',GetInCost($pos));
			
			if($nxt[9])	$hid_check='checked';
			
			$tmpl->AddText("</select>
			<tr class='lin0'><td align='right'>Код изготовителя<td><input type='text' name='vc' value='$nxt[11]'>
			<tr class='lin1'><td align='right'>Базовая цена<td><input type='text' name='cost' value='$nxt[4]'>
			<tr class='lin0'><td align='right'>Единица измерения<td><select name='unit'>");
			$res=mysql_query("SELECT `id`, `name`, `printname` FROM `doc_units`");
			while($nx=mysql_fetch_row($res))
			{
				$i="";
				if((($pos!=0)&&($nx[0]==$nxt[10]))||($group==$nx[0])) $i=" selected";
				$tmpl->AddText("<option value='$nx[0]' $i>$nx[1] ($nx[2])</option>");
			}
			$tmpl->AddText("</select>
			<tr class='lin1'><td align='right'>Актуальная цена поступления:<td><b>$act_cost</b>
			<tr class='lin0'><td align='right'>Ликвидность:<td><b>$nxt[5]%</b>
			<tr class='lin1'><td align='right'>Скрытность:<td><label><input type='checkbox' name='hid' value='1' $hid_check>Не отображать на витрине</a>
			<tr class='lin0'><td align='right'>Описание<td colspan='2'><textarea name='desc'>$nxt[2]</textarea>");
			if($pos!=0)
				$tmpl->AddText("<tr class='lin1'><td align='right'>Режим записи:<td>
				<label><input type='radio' name='sr' value='0' checked>Сохранить</label><br>
				<label><input type='radio' name='sr' value='1'>Добавить</label><br>");
			$tmpl->AddText("<tr class='lin0'><td><td><input type='submit' value='Сохранить'>
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
			$res=mysql_query("SELECT `doc_base_dop`.`type`, `doc_base_dop`.`analog`, `doc_base_dop`.`koncost`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`, `doc_base_dop`.`strana`, `doc_base_dop`.`ntd`
			FROM `doc_base_dop`
			WHERE `doc_base_dop`.`id`='$pos'");
			$nxt=@mysql_fetch_row($res);

			$tmpl->AddText("<form action='' method='post'>
			<table cellpadding='0' width='100%'>	
			<input type='hidden' name='mode' value='esave'>
			<input type='hidden' name='l' value='sklad'>
			<input type='hidden' name='pos' value='$pos'>
			<input type='hidden' name='param' value='d'>
			<tr class='lin0'><td align='right'>Аналог<td><input type='text' name='analog' value='$nxt[1]' id='pos_analog'>
			<tr class='lin1'><td align='right'>Рыночная цена<td><input type='text' name='koncost' value='$nxt[2]' id='pos_koncost'>
			<tr class='lin0'><td align='right'>Тип<td><select name='type' id='pos_type' >");
		
			$res=mysql_query("SELECT `id`, `name` FROM `doc_base_dop_type` ORDER BY `id`");
			while($nx=mysql_fetch_row($res))
			{
				$ii="";
				if($nx[0]==$nxt[0]) $ii=" selected";
				$tmpl->AddText("<option value='$nx[0]' $ii>$nx[0] - $nx[1]</option>");
			}

			$tmpl->AddText("</select>
			<tr class='lin1'><td align='right'>Внутренний размер (d)<td><input type='text' name='d_int' value='$nxt[3]' id='pos_d_int'>
			<tr class='lin0'><td align='right'>Внешний размер (D)<td><input type='text' name='d_ext' value='$nxt[4]' id='pos_d_ext'>
			<tr class='lin1'><td align='right'>Высота (B)<td><input type='text' name='size' value='$nxt[5]' id='pos_size'></td></tr>
			<tr class='lin0'><td align='right'>Масса<td><input type='text' name='mass' value='$nxt[6]' id='pos_mass'>
			<tr class='lin1'><td align='right'>Страна происхождения<td><input type='text' name='strana' value='$nxt[7]'>
			<tr class='lin1'><td align='right'>Номер таможенной декларации<td><input type='text' name='ntd' value='$nxt[8]'>");
			$res=mysql_query("SELECT `doc_base_values`.`param_id`, `doc_base_params`.`param`, `doc_base_values`.`value` FROM `doc_base_values`
			LEFT JOIN `doc_base_params` ON `doc_base_params`.`id`=`doc_base_values`.`param_id`
			WHERE `doc_base_values`.`id`='$pos'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить дополнительные свойства!");
			while($nx=mysql_fetch_row($res))
			{
				$tmpl->AddText("<tr><td align='right'>$nx[1]<td><input type='text' name='par[$nx[0]]' value='$nx[2]'>");	
			}
			$tmpl->AddText("<tr class='lin0'><td align='right'><select name='par_add'><option value='0' selected>-- выберите --</option>");
			$res=mysql_query("SELECT `id`, `param` FROM `doc_base_params` ORDER BY `param`");
			while($nx=mysql_fetch_row($res))
			{
				$tmpl->AddText("<option value='$nx[0]'>$nx[1]</option>");
			}
			$tmpl->AddText("</select><td><input type='text' name='value_add'>");
			$tmpl->AddText("<tr class='lin0'><td><td><input type='submit' value='Сохранить'>
			</table></form>");
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
			$tmpl->AddText("<form action='' method='post' enctype='multipart/form-data'>
			<input type='hidden' name='mode' value='esave'>
			<input type='hidden' name='l' value='sklad'>
			<input type='hidden' name='pos' value='$pos'>
			<input type='hidden' name='param' value='i'>
			<table cellpadding='0' width='50%'>
			<tr class='lin1'><td>Файл картнки:
			<td><input type='hidden' name='MAX_FILE_SIZE' value='1000000'><input name='userfile' type='file'>
			<tr class='lin0'><td>Название картинки:
			<td><input type='text' name='nm' value='photo_$pos'><br>
			Если написать имя картинки, которая уже есть в базе, то она и будет установлена вне зависимости от того, передан файл или нет.
			<tr class='lin1'><td>Дополнительно:
				<td><label><input type='checkbox' name='set_def' value='1' checked>Установить по умолчанию</label>
			<tr class='lin0'><td colspan='2' align='center'>        	
			<input type='submit' value='Сохранить'>
			</table>
			
			</form><h2>Ассоциированные с товаром картинки</h2>");
			$res=mysql_query("SELECT `doc_base_img`.`img_id`, `doc_img`.`type`
			FROM `doc_base_img`
			LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
			WHERE `doc_base_img`.`pos_id`='$pos'");
			while($nxt=@mysql_fetch_row($res))
			{
				$tmpl->AddText("<img src='{$CONFIG['site']['var_data_web']}/pos/$nxt[0].$nxt[1]'><br>
				<a href='?mode=esave&amp;l=sklad&amp;param=i_d&amp;pos=$pos&amp;img=$nxt[0]'>Убрать ассоциацию</a><br><br>");
			}			
		}
		// Цены
		else if($param=='c')
		{
			$res=mysql_query("SELECT `doc_base`.`cost` FROM `doc_base` WHERE `doc_base`.`id`='$pos'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить базовую цену товара");
			$base_cost=mysql_result($res,0,0);
			
			
			$cost_types=array('pp' => 'Процент', 'abs' => 'Абсолютная наценка', 'fix' => 'Фиксированная цена');
			$direct=array(0=>'Вниз', 1=>'K ближайшему', 2=>'Вверх');
			$res=mysql_query("SELECT `doc_cost`.`id`, `doc_base_cost`.`id`, `doc_cost`.`name`, `doc_cost`.`type`, `doc_cost`.`value`, `doc_base_cost`.`type`, `doc_base_cost`.`value`, `doc_base_cost`.`accuracy`, `doc_base_cost`.`direction`
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
				
				$tmpl->AddText("<tr><td><label><input type='checkbox' name='ch$cn[0]' value='1' $checked>$cn[2] $def_val</label>
				<td><select name='cost_type$cn[0]'>");
				foreach($cost_types as $id => $type)
				{
					$sel=($id==$cn[5])?' selected':'';
					$tmpl->AddText("<option value='$id'$sel>$type</option>");
				}
				if(!$cn[1])	$cn[6]=$cn[4];
				
				
				$result=GetCostPos($pos, $cn[0]);
				
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
				for($i=0;$i<3;$i++)
				{
					$sel=$cn[8]==$i?'selected':'';
					$tmpl->AddText("<option value='$i' $sel>{$direct[$i]}</option>");
				}
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
			$tmpl->msg("Здесь будет возможность задать связи данного наименования с другими. Это будет отражено на витрине, как связанные товары. Так-же можно использовать для заданя аналогов.");
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
			$res=mysql_query("SELECT `id`, `name` , `desc` , `pid` , `hidelevel` , `printname` 
			FROM `doc_group`
			WHERE `id`='$group'");
			@$nxt=mysql_fetch_row($res);
			$tmpl->AddText("<h1>Описание группы</h1>
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
			if($nxt[3]==0) $i=" selected";
			$tmpl->AddText("<option value='0' $i>--</option>");
			
			$res=mysql_query("SELECT * FROM `doc_group`");
			while($nx=mysql_fetch_row($res))
			{
				$i="";
				
				if($nx[0]==$nxt[3]) $i=" selected";
				$tmpl->AddText("<option value='$nx[0]' $i>$nx[1] ($nx[0])</option>");
			}
			
			if(file_exists("{$CONFIG['site']['var_data_fs']}/category/$group.jpg"))
				$img="<br><img src='{$CONFIG['site']['var_data_web']}/category/$group.jpg'><br><a href='/docs.php?l=sklad&amp;mode=esave&amp;g=$nxt[0]&amp;param=gid'>Удалить изображение</a>";
			
			$tmpl->AddText("</select>
			<tr class='lin1'>
			<td>Индекс сокрытия:
			<td><input type='text' name='hid' value='$nxt[4]'>
			<tr class='lin0'>
			<td>Печатное название:
			<td><input type='text' name='pname' value='$nxt[5]'>
			<tr class='lin1'><td>Изображение (jpg, до 100 кб, 50*50 - 150*150):
			<td><input type='hidden' name='MAX_FILE_SIZE' value='1000000'><input name='userfile' type='file'>$img
			<tr class='lin0'>
			<td>Описание:
			<td><textarea name='desc'>$nxt[2]</textarea>
			<tr class='lin1'><td colspan='2' align='center'>        	
			<input type='submit' value='Сохранить'>
			</table>
			</form>");
			if($nxt[0])
			{
				$cost_types=array('pp' => 'Процент', 'abs' => 'Абсолютная наценка', 'fix' => 'Фиксированная цена');
				$direct=array(0=>'Вниз', 1=>'K ближайшему', 2=>'Вверх');
				$res=mysql_query("SELECT `doc_cost`.`id`, `doc_group_cost`.`id`, `doc_cost`.`name`, `doc_cost`.`type`, `doc_cost`.`value`, `doc_group_cost`.`type`, `doc_group_cost`.`value`, `doc_group_cost`.`accuracy`, `doc_group_cost`.`direction`
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
					if(!$cn[1])	$cn[6]=$cn[4];
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
					for($i=0;$i<3;$i++)
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
		else $tmpl->msg("Неизвестная закладка");
	}
	function ESave()
	{
		global $tmpl, $CONFIG;		
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
			$pos_name=rcv('pos_name');
			$proizv=rcv('proizv');
			$g=rcv('g');
			$desc=rcv('desc');
			$cost=rcv('cost');
			$sr=rcv('sr');
			$pos_type=rcv('pos_type');
			$hid=rcv('hid');
			$unit=rcv('unit');
			$vc=rcv('vc');
			if(!$hid)	$hid=0;
			$cc='Цена осталась прежняя!';
			if( ($pos)&&(!$sr) )
			{
				$sql_add=$log_add='';
				$res=mysql_query("SELECT `group`, `name`, `desc`, `proizv`, `cost`, `likvid`, `hidden`, `unit`, `vc` FROM `doc_base` WHERE `id`='$pos'");
				if(mysql_errno())	throw new MysqlException("Не удалось получить старые свойства позиции!");
				$old_data=mysql_fetch_assoc($res);
				if($old_data['name']!=$pos_name)
				{
					$sql_add.=", `name`='$pos_name'";
					$log_add.=", name:({$old_data['name']} => $pos_name)";
				}
				if($old_data['cost']!=$cost)
				{
					$sql_add.=", `cost`='$cost', `cost_date`=NOW()";
					$cc='Установлена новая цена!';
					$log_add.=", cost:({$old_data['cost']} => $cost)";
				}
				if($old_data['group']!=$g)
				{
					$sql_add.=", `group`='$g'";
					$log_add.=", group:({$old_data['group']} => $g)";
				}
				if($old_data['proizv']!=$proizv)
				{
					$sql_add.=", `proizv`='$proizv'";
					$log_add.=", proizv:({$old_data['proizv']} => $proizv)";
				}
				if($old_data['proizv']!=$proizv)
				{
					$sql_add.=", `proizv`='$proizv'";
					$log_add.=", proizv:({$old_data['proizv']} => $proizv)";
				}
				if($old_data['desc']!=$desc)
				{
					$sql_add.=", `desc`='$desc'";
					$log_add.=", desc:({$old_data['desc']} => $desc)";
				}
				if($old_data['desc']!=$desc)
				{
					$sql_add.=", `desc`='$desc'";
					$log_add.=", desc:({$old_data['desc']} => $desc)";
				}
				if($old_data['hidden']!=$hid)
				{
					$sql_add.=", `hidden`='$hid'";
					$log_add.=", hidden:({$old_data['hidden']} => $hid)";
				}
				if($old_data['unit']!=$unit)
				{
					$sql_add.=", `unit`='$unit'";
					$log_add.=", unit:({$old_data['unit']} => $unit)";
				}
				if($old_data['vc']!=$vc)
				{
					$sql_add.=", `vc`='$vc'";
					$log_add.=", vc:({$old_data['vc']} => $vc)";
				}
				
				$res=mysql_query("UPDATE `doc_base` SET `id`=`id` $sql_add WHERE `id`='$pos'");
				if(mysql_errno())	throw new MysqlException("Не удалось обновить свойства позиции!");
				$tmpl->msg("Данные обновлены! $cc");
				doc_log("UPDATE pos","1 $log_add", 'pos', $pos);
			}
			else
			{	
				$res=mysql_query("INSERT INTO `doc_base` (`name`, `group`, `proizv`, `desc`, `cost`, `cost_date`, `pos_type`, `hidden`, `unit`)
				VALUES	('$pos_name', '$g', '$proizv', '$desc', '$cost', NOW() , '$pos_type', '$hid', '$unit')");
				$opos=$pos;
				$pos=mysql_insert_id();
				if($opos)
				{				
					$res=mysql_query("SELECT `doc_base_dop`.`type`, `doc_base_dop`.`analog`, `doc_base_dop`.`koncost`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`
					FROM `doc_base_dop`
					WHERE `doc_base_dop`.`id`='$opos'");
					$nxt=@mysql_fetch_row($res);
					$res=mysql_query("REPLACE `doc_base_dop` (`id`, `analog`, `koncost`, `type`, `d_int`, `d_ext`, `size`, `mass`) 
					VALUES ('$pos', '$nxt[1]', '0', '$nxt[0]', '$nxt[3]', '$nxt[4]', '$nxt[5]', '$nxt[6]')");
					doc_log("INSERT pos","name:$pos_name, proizv:$proizv, group:$group, desc: $desc, hidden:$hid, cost:$cost",'pos',$pos);
				}
				$this->PosMenu($pos, '');
				if($res)
				{
					$tmpl->msg("Добавлена новая позиция!<br><a href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;pos=$pos'>Перейти</a>");
					$res=mysql_query("SELECT `id` FROM `doc_sklady`");
					while($nxt=mysql_fetch_row($res))
						mysql_query("INSERT INTO `doc_base_cnt` (`id`, `sklad`, `cnt`) VALUES ('$pos', '$nxt[0]', '0')");
				}
				else $tmpl->msg("Ошибка сохранения!".mysql_error(),"err");
				
			}
		}
		else if($param=='d')
		{
			$analog=rcv('analog');
			$koncost=rcv('koncost');
			$type=rcv('type');
			$d_int=rcv('d_int');
			$d_ext=rcv('d_ext');
			$size=rcv('size');
			$mass=rcv('mass');
			$strana=rcv('strana');
			$ntd=rcv('ntd');
			
			// ЭТОГ ЧТО ТАКОЕ?
			//$res=mysql_query("UPDATE `doc_base` SET `analog`='$analog', `koncost`='$koncost', `type`='$type', `d_int`='$d_int', `d_ext`='$d_ext', `size`='$size', `mass`='$mass', `strana`='$strana' $i WHERE `id`='$pos'");
			
			$res=mysql_query("REPLACE `doc_base_dop` (`id`, `analog`, `koncost`, `type`, `d_int`, `d_ext`, `size`, `mass`, `strana`, `ntd`) VALUES ('$pos', '$analog', '$koncost', '$type', '$d_int', '$d_ext', '$size', '$mass', '$strana', '$ntd')");
			
			$par=@$_POST['par'];
			if(is_array($par))
			{
				foreach($par as $key => $value)
				{
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
			}
			if($res) $tmpl->msg("Данные сохранены!".mysql_affected_rows());
			else $tmpl->msg("Ошибка сохранения!".mysql_error(),"err");
		}
		else if($param=='s')
		{
			$res=mysql_query("SELECT `doc_sklady`.`name`, `doc_base_cnt`.`cnt`, `doc_base_cnt`.`mincnt`,  `doc_base_cnt`.`mesto`, `doc_base_cnt`.`sklad`
			FROM `doc_base_cnt`
			LEFT JOIN `doc_sklady` ON `doc_sklady`.`id` = `doc_base_cnt`.`sklad`
			WHERE `doc_base_cnt`.`id`='$pos'");
			while($nxt=@mysql_fetch_row($res))
			{
				$mincnt=rcv("min$nxt[4]");
				$mesto=rcv("mesto$nxt[4]");
				$r=mysql_query("UPDATE `doc_base_cnt` SET `mincnt`='$mincnt', `mesto`='$mesto' WHERE `id`='$pos' AND `sklad`='$nxt[4]'");
				if($r) $tmpl->msg("$nxt[0] - Сохранено","ok");
				else $tmpl->msg("$nxt[0] - ошибка".mysql_error(),"err");
			}
			
		
		}
		else if($param=='i')
		{
			$id=0;
			$max_size=500;
			$min_pix=50;
			$max_pix=800;
			global $CONFIG;
			$nm=rcv('nm');
			$set_def=rcv('set_def');
			$res=mysql_query("SELECT `id` FROM `doc_img` WHERE `name`='$nm'");
			if(mysql_num_rows($res))
			{
				$img_id=mysql_result($res,0,0);
				$tmpl->msg("Эта картинка найдена, N $img_id","info");
			}
			else
			{
				if($_FILES['userfile']['size']<=0)
					$tmpl->msg("Забыли выбрать картинку?");
				else
				{
					if($_FILES['userfile']['size']>$max_size*1024)
						$tmpl->msg("Слишком большой файл! Допустимо не более $max_size кб!");
					else
					{
						$iminfo=getimagesize($_FILES['userfile']['tmp_name']);						
						switch ($iminfo[2])
						{
							case IMAGETYPE_JPEG: $imtype='jpg'; break;
							case IMAGETYPE_PNG: $imtype='png'; break;
							case IMAGETYPE_GIF: $imtype='gif'; break;
							default: $imtype='';
						}
						if(!$imtype) $tmpl->msg("Файл - не картинка, или неверный формат файла. Рекомендуется PNG и JPG, допустим но не рекомендуется GIF.");
						else if(($iminfo[0]<$min_pix)||($iminfo[1]<$min_pix))
						$tmpl->msg("Слишком мелкая картинка! Минимальный размер - $min_pix пикселей!");
						else if(($iminfo[0]>$max_pix)||($iminfo[1]>$max_pix))
						$tmpl->msg("Слишком большая картинка! Максимальный размер - $max_pix пикселей!");
						else
						{	
							mysql_query("START TRANSACTION");
							mysql_query("INSERT INTO `doc_img` (`name`, `type`)	VALUES ('$nm', '$imtype')");
							$img_id=mysql_insert_id();
							if($img_id)
							if(move_uploaded_file($_FILES['userfile']['tmp_name'],$CONFIG['site']['var_data_fs'].'/pos/'.$img_id.'.'.$imtype))
							{
								mysql_query("COMMIT");
								$tmpl->msg("Файл загружен, $img_id.$imtype","info");
							}
							else
							{
								$tmpl->msg("Файл не загружен, $img_id.$imtype","info");
								$img_id=false;
							}
						}
					}
				}
			}
			if($img_id)	mysql_query("INSERT INTO `doc_base_img` (`pos_id`, `img_id`, `default`) VALUES ('$pos', '$img_id', '$set_def')");
		
		}
		else if($param=='i_d')
		{
			$img=rcv('img');
			mysql_query("DELETE FROM `doc_base_img` WHERE `pos_id`='$pos' AND `img_id`='$img'");
			if(mysql_errno())	throw new MysqlException("Не удалось удалить ассоциацию");
			$tmpl->msg("Ассоциация с изображением удалена! Для продолжения работы воспользуйтесь меню!","ok");
			
		
		}
		else if($param=='c')
		{
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
			$zp=rcv('zp');
			$res=mysql_query("SELECT `doc_base_params`.`id`, `doc_base_values`.`value` FROM `doc_base_params`
			LEFT JOIN `doc_base_values` ON `doc_base_values`.`param_id`=`doc_base_params`.`id` AND `doc_base_values`.`id`='$pos'
			WHERE `doc_base_params`.`param`='ZP'");
			if(mysql_errno())	throw new MysqlException("Не удалось выбрать доп.свойство товара");
			if(!mysql_num_rows($res))
			{
				mysql_query("INSERT INTO `doc_base_params` (`param`, `type`) VALUES ('ZP', 'double')");
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
			$max_size=100;
			$name=rcv('name');
			$desc=rcv('desc');
			$pid=rcv('pid');
			$hid=rcv('hid');
			$pname=rcv('pname');
			if($group)
				$res=mysql_query("UPDATE `doc_group` SET `name`='$name', `desc`='$desc', `pid`='$pid', `hidelevel`='$hid', `printname`='$pname' WHERE `id` = '$group'");
			else 
				$res=mysql_query("INSERT INTO `doc_group` (`name`, `desc`, `pid`, `hidelevel`, `printname`)
				VALUES ('$name', '$desc', '$pid', '$hid', '$pname')"); 
			if(mysql_errno())	throw new MysqlException("Не удалось сохранить информацию группы");
				
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
					else if(($iminfo[0]>150)||($iminfo[1]>150))
					throw new Exception("Слишком большая картинка! Максимальный размер - 150*150 пикселей!");
					if(!move_uploaded_file($_FILES['userfile']['tmp_name'], "{$CONFIG['site']['var_data_fs']}/category/$group.jpg"))
							throw new Exception("Не удалось записать изображение. Проверьте права доступа к директории {$CONFIG['site']['var_data_fs']}/category/");
				}
			}
			$tmpl->msg("Сохранено!");
		}
		else if($param=='gid')
		{
			if(!file_exists("{$CONFIG['site']['var_data_fs']}/category/$group.jpg"))
				throw new Exception("Изображение не найдено");
			if(!unlink("{$CONFIG['site']['var_data_fs']}/category/$group.jpg"))
				throw new Exception("Не удалось удалить изображение! Проверьте права доступа!");
			$tmpl->msg("Изображение удалено!","ok");
		}
		else if($param=='gc')
		{
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
		<div onclick='tree_toggle(arguments[0])'>
		<div><a href='' title='$nxt[2]' onclick=\"EditThis('/docs.php?mode=srv&amp;opt=pl&amp;g=0','sklad'); return false;\" >Группы</a>  (<a href='/docs.php?l=sklad&mode=edit&param=g&g=0'><img src='/img/i_add.png' alt=''></a>)</div>
		<ul class='Container'>".$this->draw_level($select,0)."</ul></div>
		Или отбор:<input type='text' id='sklsearch' onkeydown=\"DelayedSave('/docs.php?mode=srv&amp;opt=pl','sklad', 'sklsearch'); return true;\" >
		");
	
	}

	function ViewSklad($group=0,$s='')
	{
		global $tmpl;
		
		$sklad=$_SESSION['sklad_num'];
		
		if($group)
		{
			$res=mysql_query("SELECT `desc` FROM `doc_group` WHERE `id`='$group'");
			$g_desc=mysql_result($res,0,0);		
			if($g_desc) $tmpl->AddText("<h4>$g_desc</h4>");
		}
        
		$sql="SELECT `doc_base`.`id`,`doc_base`.`group`,`doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`likvid`, `doc_base`.`cost`, `doc_base`.`cost_date`,
		`doc_base_dop`.`koncost`,  `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`,
		`doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt`, 
		(SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`)
		FROM `doc_base`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$sklad'
		WHERE `doc_base`.`group`='$group'
		ORDER BY `doc_base`.`name`";

		$lim=50;
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

		if(mysql_num_rows($res))
		{
			$tmpl->AddText("$pagebar<table width='100%' cellspacing='1' cellpadding='2'><tr>
			<th>№<th>Наименование<th>Производитель<th>Цена, р.<th>Ликв.<th>Рыноч.цена, р.<th>Аналог<th>Тип<th>d<th>D<th>B
			<th>Масса<th><img src='/img/i_lock.png' alt='В резерве'><th><img src='/img/i_alert.png' alt='Под заказ'><th><img src='/img/i_truck.png' alt='В пути'><th>Склад<th>Всего<th>Место");
			$i=0;
			$this->DrawSkladTable($res,$s);
			$tmpl->AddText("</table>$pagebar");

		}
		else $tmpl->msg("В выбранной группе товаров не найдено!");
		$tmpl->AddText("
		<a href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;pos=0&amp;g=$group'><img src='/img/i_add.png' alt=''> Добавить</a> |
		<a href='/docs.php?l=sklad&amp;mode=edit&amp;param=g&amp;g=$group'><img src='/img/i_edit.png' alt=''> Правка группы</a> |
		<a href='/docs.php?l=sklad&amp;mode=search'><img src='/img/i_find.png' alt=''> Расширенный поиск</a>");
	}
	
	function ViewSkladS($group=0,$s)
	{
		global $tmpl;
		$sf=0;
		$sklad=$_SESSION['sklad_num'];
		$tmpl->AddText("<b>Показаны наименования изо всех групп!</b><br>");
		$tmpl->AddText("<table width='100%' cellspacing='1' cellpadding='2'><tr>
		<th>№<th>Наименование<th>Производитель<th>Цена, р.<th>Ликв.<th>Рыноч.цена, р.<th>Аналог<th>Тип<th>d<th>D<th>B
        <th>Масса<th><img src='/img/i_lock.png' alt='В резерве'><th><img src='/img/i_alert.png' alt='Под заказ'><th><img src='/img/i_truck.png' alt='В пути'><th>Склад<th>Всего<th>Место");
		
        
		$sql="SELECT `doc_base`.`id`,`doc_base`.`group`,`doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`likvid`, `doc_base`.`cost`, `doc_base`.`cost_date`,
		`doc_base_dop`.`koncost`,  `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`,
		`doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt`, (SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`)";
        	 
		$sqla=$sql."FROM `doc_base`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$sklad'
        LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
        WHERE `doc_base`.`name` LIKE '$s%' ORDER BY `doc_base`.`name` LIMIT 100";
		$res=mysql_query($sqla);
		if($cnt=mysql_num_rows($res))
		{
			$tmpl->AddText("<tr class='lin0'><th colspan='18' align='center'>Поиск по названию, начинающемуся на $s: найдено $cnt");
			$this->DrawSkladTable($res,$s);
			$sf=1;
		}
		
		$sqla=$sql."FROM `doc_base`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$sklad'
        LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
        WHERE `doc_base`.`name` LIKE '%$s%' AND `doc_base`.`name` NOT LIKE '$s%' ORDER BY `doc_base`.`name` LIMIT 30";
		$res=mysql_query($sqla);
		if($cnt=mysql_num_rows($res))
		{
			$tmpl->AddText("<tr class='lin0'><th colspan='18' align='center'>Поиск по названию, содержащему $s: найдено $cnt");
			$this->DrawSkladTable($res,$s);
			$sf=1;
		}
		
		$sqla=$sql."FROM `doc_base`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$sklad'
        LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
		WHERE `doc_base_dop`.`analog` LIKE '%$s%' AND `doc_base`.`name` NOT LIKE '%$s%' ORDER BY `doc_base`.`name` LIMIT 30";
		$res=mysql_query($sqla);
		if($cnt=mysql_num_rows($res))
		{
			$tmpl->AddText("<tr class='lin0'><th colspan='18' align='center'>Поиск аналога, для $s: найдено $cnt");
			$this->DrawSkladTable($res,$s);
			$sf=1;
		}
		
		$tmpl->AddText("</table><a href='/docs.php?mode=srv&amp;opt=ep&amp;pos=0&amp;g=$group'><img src='/img/i_add.gif' alt=''> Добавить</a>");
		
		if($sf==0)
			$tmpl->msg("По данным критериям товаров не найдено!");
	}
	
	function Search()
	{
		global $tmpl;
		$opt=rcv("opt");
		if($opt=='')
		{
			doc_menu();
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
			<td><input type='text' name='name'><br><label><input type='checkbox' name='analog' value='1'>И аналог</label>
			<td>От: <input type='text' name='li_min'><br>до: <input type='text' name='li_max'>
			<td><input type='text' id='proizv' name='proizv' value='' onkeydown=\"return AutoFill('/docs.php?mode=search&amp;opt=pop_proizv','proizv','proizv_p')\"><br>
			<div id='proizv_p' class='dd'></div>
			<td><select name='type' id='pos_type'>");
			$res=mysql_query("SELECT `id`, `name` FROM `doc_base_dop_type` ORDER BY `id`");
			while($nx=mysql_fetch_row($res))
			{
				$ii="";
				if($nx[0]==0) $ii=" selected";
				$tmpl->AddText("<option value='$nx[0]' $ii>$nx[0] - $nx[1]</option>");
			}

			$tmpl->AddText("</select>
			<td><input type='text' name='mesto'>
			
			<tr>
			<th>Внутренний диаметр
			<th>Внешний диаметр
			<th>Высота
			<th>Масса
			<th>Цена
			<tr class='lin1'>
			<td>От: <input type='text' name='di_min'><br>до: <input type='text' name='di_max'>
			<td>От: <input type='text' name='de_min'><br>до: <input type='text' name='de_max'>
			<td>От: <input type='text' name='size_min'><br>до: <input type='text' name='size_max'>
			<td>От: <input type='text' name='m_min'><br>до: <input type='text' name='m_max'>
			<td>От: <input type='text' name='cost_min'><br>до: <input type='text' name='cost_max'>
			
			<tr>
			<td colspan='5' align='center'><input type='submit' value='Найти'>
			</table>
			</form>");
		}
		else if($opt=='pop_proizv')
		{
			$tmpl->ajax=1;
			$s=rcv('s');
			$res=mysql_query("SELECT `proizv` FROM `doc_base` WHERE LOWER(`proizv`) LIKE LOWER('%$s%') GROUP BY `proizv`  ORDER BY `proizv`LIMIT 20");
			$row=mysql_numrows($res);
			$tmpl->AddText("<div class='pointer' onclick=\"return AutoFillClick('proizv','','proizv_p');\">-- Убрать --</div>");
			while($nxt=mysql_fetch_row($res))
			{
				$i=1;
				$tmpl->AddText("<div class='pointer' onclick=\"return AutoFillClick('proizv','$nxt[0]','proizv_p');\">$nxt[0]</div>");
			}
			if(!$i) $tmpl->AddText("<b>Искомая комбинация не найдена!");
		}	
		else if($opt=='s')
		{
			doc_menu();
			$tmpl->AddText("<h1>Результаты</h1>");
			$name=rcv('name');
			$analog=rcv('analog');
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
			$type=rcv('type');
			$sklad=$_SESSION['sklad_num'];
			
			$sql="SELECT `doc_base`.`id`,`doc_base`.`group`,`doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`likvid`, `doc_base`.`cost`, `doc_base`.`cost_date`,
			`doc_base_dop`.`koncost`,  `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`,
			`doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt`, (SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`)
			FROM `doc_base`
			LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$sklad'
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
			WHERE 1 ";
			
			if($name)
			{		
				if(!$analog) 	$sql.="AND `doc_base`.`name` LIKE '%$name%'";
				else $sql.="AND (`doc_base_dop`.`analog` LIKE '%$name%' OR `doc_base`.`name` LIKE '%$name%')";
					
			}
			if($proizv)		$sql.="AND `doc_base`.`proizv` LIKE '%$proizv%'";
			if($mesto)		$sql.="AND `doc_base_cnt`.`mesto` LIKE '$mesto'";
			if($di_min)		$sql.="AND `doc_base_dop`.`d_int` >= '$di_min'";
			if($di_max)		$sql.="AND `doc_base_dop`.`d_int` <= '$di_max'";
			if($li_min)		$sql.="AND `doc_base`.`likvid` >= '$li_min'";
			if($li_max)		$sql.="AND `doc_base`.`likvid` <= '$li_max'";
			if($de_min)		$sql.="AND `doc_base_dop`.`d_ext` >= '$de_min'";
			if($de_max)		$sql.="AND `doc_base_dop`.`d_ext` <= '$de_max'";
			if($size_min)	$sql.="AND `doc_base_dop`.`size` >= '$size_min'";
			if($size_max)	$sql.="AND `doc_base_dop`.`size` <= '$size_max'";
			if($m_min)		$sql.="AND `doc_base_dop`.`mass` >= '$m_min'";
			if($m_max)		$sql.="AND `doc_base_dop`.`mass` <= '$m_max'";
			if($cost_min)	$sql.="AND `doc_base`.`cost` >= '$cost_min'";
			if($cost_max)	$sql.="AND `doc_base`.`cost` <= '$cost_max'";
			if($type)	$sql.="AND `doc_base_dop`.`type` = '$type'";
			

			$sql.="ORDER BY `doc_base`.`name`";
			
			
			$tmpl->AddText("<table width='100%' cellspacing='1' cellpadding='2'><tr>
			<th>№<th>Наименование<th>Производитель<th>Цена, р.<th>Ликв.<th>Рыноч.цена, р.<th>Аналог<th>Тип<th>d<th>D<th>B
			<th>Масса<th><img src='/img/i_lock.png' alt='В резерве'><th><img src='/img/i_alert.png' alt='Под заказ'><th><img src='/img/i_truck.png' alt='В пути'><th>Склад<th>Всего<th>Место");
			
			$res=mysql_query($sql);
			echo mysql_error();
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
	global $tmpl;
	$i=0;
	while($nxt=mysql_fetch_row($res))
	{
		$rezerv=DocRezerv($nxt[0],$doc);
		$pod_zakaz=DocPodZakaz($nxt[0],$doc);
		$v_puti=DocVPuti($nxt[0],$doc);
		
		if($rezerv)	$rezerv="<a onclick=\"OpenW('/docs.php?l=inf&mode=srv&opt=rezerv&pos=$nxt[0]'); return false;\"  title='Отобразить документы' href='/docs.php?l=inf&mode=srv&opt=p_zak&pos=$nxt[0]'>$rezerv</a>";
	
		if($pod_zakaz)	$pod_zakaz="<a onclick=\"ShowPopupWin('/docs.php?l=inf&mode=srv&opt=p_zak&pos=$nxt[0]'); return false;\"  title='Отобразить документы' href='/docs.php?l=inf&mode=srv&opt=p_zak&pos=$nxt[0]'>$pod_zakaz</a>";

		if($v_puti)	$v_puti="<a onclick=\"ShowPopupWin('/docs.php?l=inf&mode=srv&opt=vputi&pos=$nxt[0]'); return false;\"  title='Отобразить документы' href='/docs.php?l=inf&mode=srv&opt=vputi&pos=$nxt[0]'>$v_puti</a>";
		
		if($nxt[16]>0)
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
		
		

					
		$nxt[2]=SearchHilight($nxt[2],$s);				
		$nxt[8]=SearchHilight($nxt[8],$s);	
		$i=1-$i;
		$cost_p=sprintf("%0.2f",$nxt[5]);
		$cost_r=sprintf("%0.2f",$nxt[7]);
		
		$tmpl->AddText("<tr class='lin$i pointer' oncontextmenu=\"ShowContextMenu('/docs.php?mode=srv&opt=menu&doc=0&pos=$nxt[0]'); return false;\">
		<td><a href='/docs.php?mode=srv&amp;opt=ep&amp;pos=$nxt[0]'>$nxt[0]</a> <a href='' onclick=\"ShowContextMenu('/docs.php?mode=srv&amp;opt=menu&amp;doc=0&amp;pos=$nxt[0]'); return false;\" title='Меню' accesskey=\"S\"><img src='img/i_menu.png' alt='Меню' border='0'></a> 
		<td align=left>$nxt[2]<td>$nxt[3]<td $cc>$cost_p<td>$nxt[4]%<td>$cost_r<td>$nxt[8]<td>$nxt[9]<td>$nxt[10]<td>$nxt[11]<td>$nxt[12]<td>$nxt[13]<td>$rezerv<td>$pod_zakaz<td>$v_puti<td>$nxt[15]<td>$nxt[16]<td>$nxt[14]");
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
		global $tmpl;
		$sel=array();
		if($param=='')	$param='v';
		$sel[$param]="class='selected'";
		
		$res=mysql_query("SELECT `doc_base`.`name` FROM `doc_base` WHERE `doc_base`.`id`='$pos'");
		if(mysql_errno())	throw new Exception("Не удалось получить наименование позиции!");
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
		<li><a {$sel['i']} href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=i&amp;pos=$pos'>Изображения</a></li>
		<li><a {$sel['c']} href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=c&amp;pos=$pos'>Цены</a></li>
		<li><a {$sel['k']} href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=k&amp;pos=$pos'>Комплектующие</a></li>
		<li><a {$sel['l']} href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=l&amp;pos=$pos'>Связи</a></li>
		<li><a {$sel['h']} href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=h&amp;pos=$pos'>История</a></li>
		</ul>");
	}
	
};


?>
