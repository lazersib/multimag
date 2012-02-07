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


$doc_types[8]="Перемещение товара";

class doc_Peremeshenie extends doc_Nulltype
{
	// Создание нового документа или редактирование заголовка старого
	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				=8;
		$this->doc_name				='peremeshenie';
		$this->doc_viewname			='Перемещение товара со склада на склад';
		$this->sklad_editor_enable		=true;
		$this->sklad_modify			=0;
		$this->header_fields			='cena separator sklad';
		settype($this->doc,'int');
	}
	
	function DopHead()
	{
		global $tmpl;
		$tmpl->AddText("На склад:<br>
		<select name='nasklad'>");
		$res=mysql_query("SELECT `id`,`name` FROM `doc_sklady` ORDER BY `name`");
		while($nxt=mysql_fetch_row($res))
		{
			if($nxt[0]==$this->dop_data['na_sklad'])
				$tmpl->AddText("<option value='$nxt[0]' selected>$nxt[1]</option>");
			else
				$tmpl->AddText("<option value='$nxt[0]'>$nxt[1]</option>");
		}
		$tmpl->AddText("</select>");	
	}

	function DopSave()
	{
		$nasklad=rcv('nasklad');
		$res=mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)
		VALUES ('{$this->doc}','na_sklad','$nasklad')");
		if(!$res)		throw new MysqlException("Не удалось установить склад назначения в поступлении!");
	}
	
	function DopBody()
	{
		global $tmpl;
        	$res=mysql_query("SELECT `doc_sklady`.`name` FROM `doc_sklady`
		WHERE `doc_sklady`.`id`='{$this->dop_data['na_sklad']}'");
			
        	$nxt=mysql_fetch_row($res);
		$tmpl->AddText("<b>На склад:</b> $nxt[0]");
	}
	
	function DocApply($silent=0)
	{
		$tim=time();
		$nasklad=$this->dop_data['na_sklad'];
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`, `doc_sklady`.`dnc`
		FROM `doc_list`
		LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
		WHERE `doc_list`.`id`='{$this->doc}'");		
		if(!$res)	throw new MysqlException('Ошибка выборки данных документа при проведении!');
		$nx=@mysql_fetch_assoc($res);
		if(!$nx)	throw new Exception('Документ не найден!');
		if( $nx['ok'] && (!$silent) )	throw new Exception('Документ уже был проведён!');

		$res=mysql_query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`pos_type`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$nx['sklad']}'
		WHERE `doc_list_pos`.`doc`='{$this->doc}'");
		if(!$res)	throw new MysqlException('Ошибка получения списка товара в документе!');
		while($nxt=mysql_fetch_row($res))
		{
			if($nxt[5]>0)		throw new Exception("Перемещение услуги '$nxt[3]:$nxt[4]' недопустимо!");
			if(!$nx['dnc'])
			{
				if($nxt[1]>$nxt[2])	throw new Exception("Недостаточно ($nxt[1]) товара '$nxt[3]:$nxt[4]' на складе($nxt[2])!");			
			}
			mysql_query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`-'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='{$nx['sklad']}'");
			if(mysql_error()) 	throw new MysqlException('Ошибка изменения количества на исходном складе!');
			mysql_query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`+'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='$nasklad'");
			if(mysql_error()) 	throw new MysqlException('Ошибка изменения количества на складе назначения!');
			// Если это первое поступление
			if(mysql_affected_rows()==0) mysql_query("INSERT INTO `doc_base_cnt` (`id`, `sklad`, `cnt`)
			VALUES ('$nxt[0]', '$nasklad', '$nxt[1]')");
			if(mysql_error()) 	throw new MysqlException('Ошибка изменения количества на складе назначения!');
			
			if( (!$nx['dnc']) && (!$silent))
			{
				$budet=getStoreCntOnDate($nxt[0], $nx['sklad']);
				if($budet<0)	
					throw new Exception("Невозможно, т.к. будет недостаточно ($budet) товара '$nxt[3]:$nxt[4]' !");
			}
			
		}
		if($silent)	return;
		$res=mysql_query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->doc}'");
		if(!$res)	throw new MysqlException('Ошибка установки даты проведения документа!');
		
	}
	
	function DocCancel()
	{
		global $uid;
		$tim=time();
		$nasklad=$this->dop_data['na_sklad'];
		
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`, `doc_sklady`.`dnc`
		FROM `doc_list`
		LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
		WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка выборки данных документа!');
		if(! ($nx=@mysql_fetch_assoc($res)))	throw new Exception('Документ не найден!');
		if(!$nx['ok'])				throw new Exception('Документ не проведён!');	
		$res=mysql_query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка установки флага!');
		$res=mysql_query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt`, `doc_base`.`name`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$nx['sklad']}'
		WHERE `doc_list_pos`.`doc`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка выборки товаров документа!');
		while($nxt=mysql_fetch_row($res))
		{
			mysql_query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`-'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='$nasklad'");
			if(mysql_error())		throw new Exception("Ошибка проведения, ошибка изменения количества на складе $nasklad!");
			mysql_query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`+'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='{$nx['sklad']}'");
			if(mysql_error())		throw new Exception("Ошибка проведения, ошибка изменения количества на складе {$nx['sklad']}!");
			if(!$nx['dnc'])
			{
				$budet=getStoreCntOnDate($nxt[0], $nx['sklad']);
				if($budet<0)			throw new Exception("Невозможно, т.к. будет недостаточно ($budet) товара '$nxt[3]' !");
			}
		}
	}

	function PrintForm($doc, $opt='')
	{
		if($opt=='')
		{
			global $tmpl;
			$tmpl->ajax=1;
			$tmpl->AddText("<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=prn'\">Накладная</div>");
		}
 		else $this->PrintNakl($doc);

	}
	// Формирование другого документа на основании текущего
	function MorphTo($doc, $target_type)
	{
		global $tmpl;

		$tmpl->ajax=1;
		$tmpl->AddText("Не поддерживается для данного типа документа");

	}
   	
	function Service($doc)
	{
		$tmpl->ajax=1;
		$opt=rcv('opt');
		$pos=rcv('pos');
		parent::_Service($opt,$pos);
	}
//	================== Функции только этого класса ======================================================

// -- Обычная накладная --------------
	function PrintNakl($doc)
	{
		get_docdata($doc);
		global $tmpl;
		global $uid;

		$tmpl->LoadTemplate('print');
		$dt=date("d.m.Y",$this->doc_data[5]);

		$tmpl->AddText("<h1>Накладная перемещения N {$this->doc_data[9]}{$this->doc_data[10]}, от $dt </h1>
		<b>Поставщик: </b>".$this->firm_vars['firm_name']."<br><br>");

		$tmpl->AddText("
		<table width=800 cellspacing=0 cellpadding=0>
		<tr><th>№</th><th width=450>Наименование<th>Кол-во<th>Стоимость<th>Сумма</tr>");
		$res=mysql_query("SELECT `doc_group`.`name`,`doc_base`.`name`,`doc_base`.`proizv` ,`doc_list_pos`.`cnt`,`doc_list_pos`.`cost`
		FROM `doc_list_pos`,`doc_base`,`doc_group`
		WHERE `doc_list_pos`.`doc`='{$this->doc}' AND `doc_list_pos`.`tovar`=`doc_base`.`id` AND `doc_group`.`id`=`doc_base`.`group`");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список наименований");
		$i=0;
		$ii=1;
		$sum=0;
		while($nxt=mysql_fetch_row($res))
		{
			$sm=$nxt[3]*$nxt[4];
			$cost = sprintf("%01.2f руб.", $nxt[4]);
			$cost2 = sprintf("%01.2f руб.", $sm);
			$tmpl->AddText("<tr align=right><td>$ii</td><td align=left>$nxt[0] $nxt[1] / $nxt[2]<td>$nxt[3]<td>$cost<td>$cost2");
			$i=1-$i;
			$ii++;
			$sum+=$sm;
		}
		$ii--;
		$cost = sprintf("%01.2f руб.", $sum);

		$tmpl->AddText("</table>
		<p>Всего <b>$ii</b> наименований на сумму <b>$cost</b></p>
		<p class=mini>Товар получил, претензий к качеству товара и внешнему виду не имею.</p>
		<p>Кладовщик:_____________________________________</p>");

	}



};
?>