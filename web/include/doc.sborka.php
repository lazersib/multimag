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


$doc_types[17]="Сборка изделия";

class doc_Sborka extends doc_Nulltype
{
	// Создание нового документа или редактирование заголовка старого
	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				=17;
		$this->doc_name				='sborka';
		$this->doc_viewname			='Сборка изделия';
		$this->sklad_editor_enable		=true;
		//$this->sklad_modify			=1;
		$this->header_fields			='agent cena sklad';
		settype($this->doc,'int');
		$this->dop_menu_buttons			="<a href='/doc_sc.php?mode=reopen&sn=sborka_zap&amp;doc=$doc&amp;' title='Передать в сценарий'><img src='img/i_launch.png' alt='users'></a>";
	}
	
// 	function head()
// 	{
// 		throw new Exception("Создание данного документа не поддерживается!");
// 	}


	public function DocApply($silent=0)
	{
		global $tmpl;
		$tim=time();		
		
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`, `doc_sklady`.`dnc`
		FROM `doc_list`
		LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
		WHERE `doc_list`.`id`='{$this->doc}'");
		if(mysql_errno())			throw new MysqlException('Ошибка выборки данных документа при проведении!'.mysql_error());
		$doc_info=mysql_fetch_array($res);
		if(!$doc_info)				throw new Exception("Документ {$this->doc} не найден!");
		if( $doc_info['ok'] && (!$silent) )	throw new Exception('Документ уже был проведён!');

		$res=mysql_query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt` AS `sklad_cnt`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`pos_type`, `doc_list_pos`.`id`, `doc_list_pos`.`page`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$doc_info['sklad']}'
		WHERE `doc_list_pos`.`doc`='{$this->doc}' AND `doc_base`.`pos_type`='0'");

		if(mysql_errno())			throw new MysqlException('Ошибка выборки номенклатуры документа при проведении!');
		while($doc_line=mysql_fetch_array($res))
		{
			$sign=$doc_line['page']?'-':'+';
			
			if($doc_line['page'])
			{
				if(!$doc_info['dnc'])
					if($doc_line[1]>$doc_line[2])	throw new Exception("Недостаточно ($doc_line[1]) товара '$doc_line[3]:$doc_line[4]($doc_line[0])': на складе только $doc_line[2] шт!");
				if(!$doc_info['dnc'] && (!$silent))
				{
					$budet=getStoreCntOnDate($doc_line[0], $doc_info['sklad']);
					if( $budet<0)		throw new Exception("Невозможно ($silent), т.к. будет недостаточно ($budet) товара '$doc_line[3]:$doc_line[4]($doc_line[0])'!");
				}
			}
			
			$r=mysql_query("UPDATE `doc_base_cnt` SET `cnt`=`cnt` $sign '{$doc_line['cnt']}' WHERE `id`='{$doc_line['tovar']}' AND `sklad`='{$doc_info['sklad']}'");
			if(!$r)	throw new MysqlException("Ошибка изменения количества товара $doc_line[0] ($doc_line[1]) на складе $doc_info[3] при проведении!");
			// Если это первое поступление
			if(mysql_affected_rows()==0) 
			{
				$rs=mysql_query("INSERT INTO `doc_base_cnt` (`id`, `sklad`, `cnt`) VALUES ('$doc_line[0]', '$doc_info[3]', '{$doc_line['cnt']}')");
				if(!$rs)	throw new MysqlException("Ошибка записи количества товара $doc_line[0] ({$doc_line['cnt']}) на складе $doc_info[3] при проведении!");
			}
		}
		if($silent)	return;
		mysql_query("UPDATE `doc_list` SET `ok`='$tim', `sum`='0' WHERE `id`='{$this->doc}'");
		if(mysql_errno())		throw new MysqlException('Ошибка установки даты проведения документа!');
	}
	
	function DocCancel()
	{
		global $tmpl;

		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`, `doc_sklady`.`dnc`
		FROM `doc_list`
		LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
		WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)				throw new MysqlException("Ошибка получения данных документа!");
		if(!($nx=@mysql_fetch_row($res)))	throw new Exception("Документ {$this->doc} не найден!");
		if(!$nx[4])				throw new Exception("Документ ещё не проведён!");

		$res=mysql_query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='{$this->doc}'");
		if(!$res)				throw new MysqlException("Ошибка установки даты проведения!");

		$sc="";
		if($nx[3]==2) $sc=2;
		$res=mysql_query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`pos_type`, `doc_list_pos`.`page`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$nx[3]'
		WHERE `doc_list_pos`.`doc`='{$this->doc}'");
		if(!$res)				throw new MysqlException("Ошибка получения номенклатуры документа!");
		while($nxt=mysql_fetch_row($res))
		{
			if($nxt[5]==0)
			{
				$sign=$nxt[6]?'+':'-';
				mysql_query("UPDATE `doc_base_cnt` SET `cnt`=`cnt` $sign '$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='$nx[3]'");
				if(mysql_error())	throw new Exception("Ошибка отмены проведения, ошибка изменения количества!");
			}
		}


	}

	function PrintForm($doc, $opt='')
	{
		global $tmpl;
		if($opt=='')
		{
			$tmpl->ajax=1;
			$tmpl->AddText("Не реализовано");
		}
	}
	

	// Выполнить удаление документа. Если есть зависимости - удаление не производится.
	function DelExec($doc)
	{
		$res=mysql_query("SELECT `ok` FROM `doc_list` WHERE `id`='$doc'");
		if(!mysql_result($res,0,0)) // Если проведён - нельзя удалять
		{
			$res=mysql_query("SELECT `id`, `mark_del` FROM `doc_list` WHERE `p_doc`='$doc'");
			if(!mysql_num_rows($res)) // Если есть потомки - нельзя удалять
			{
				mysql_query("DELETE FORM `doc_list_pos` WHERE `doc`='$doc'");
				mysql_query("DELETE FROM `doc_dopdata` WHERE `doc`='$doc'");
				mysql_query("DELETE FROM `doc_list` WHERE `id`='$doc'");
				doc_log("DELETE {$this->doc_name}",'','doc',$doc);
				return 0;
			}
		}
		return 1;
   	}
   	
	function Service($doc)
	{
		global $tmpl,$uid;
		$tmpl->ajax=1;
		$opt=rcv('opt');
		$pos=rcv('pos');
		include_once('include/doc.zapposeditor.php');
		$doc=$this->doc;
		$poseditor=new SZapPosEditor($this);
		$poseditor->cost_id=$this->dop_data['cena'];
		$poseditor->sklad_id=$this->doc_data['sklad'];		
		
		if( isAccess('doc_'.$this->doc_name,'view') )
		{

			// Json-вариант списка товаров
			if($opt=='jget')
			{				
				$doc_sum=DocSumUpdate($doc);
				$str="{ response: '2', content: [".$poseditor->GetAllContent()."], sum: '$doc_sum' }";			
				$tmpl->AddText($str);			
			}
			// Получение данных наименования
			else if($opt=='jgpi')
			{
				$pos=rcv('pos');
				$tmpl->AddText($poseditor->GetPosInfo($pos));
			}
			// Json вариант добавления позиции
			else if($opt=='jadd')
			{
				if(!isAccess('doc_sborka','edit'))	throw new AccessException("Недостаточно привилегий");
				$pos=rcv('pos');
				$tmpl->SetText($poseditor->AddPos($pos));
			}
			// Json вариант удаления строки
			else if($opt=='jdel')
			{
				if(!isAccess('doc_sborka','edit'))	throw new AccessException("Недостаточно привилегий");
				$line_id=rcv('line_id');
				$tmpl->SetText($poseditor->Removeline($line_id));
			}
			// Json вариант обновления
			else if($opt=='jup')
			{
				if(!isAccess('doc_sborka','edit'))	throw new AccessException("Недостаточно привилегий");
				$line_id=rcv('line_id');
				$value=rcv('value');
				$type=rcv('type');
				$tmpl->SetText($poseditor->UpdateLine($line_id, $type, $value));
			}
			// Получение номенклатуры выбранной группы
			else if($opt=='jsklad')
			{
				$group_id=rcv('group_id');
				$str="{ response: 'sklad_list', group: '$group_id',  content: [".$poseditor->GetSkladList($group_id)."] }";		
				$tmpl->SetText($str);			
			}
			// Поиск по подстроке по складу
			else if($opt=='jsklads')
			{
				$s=rcv('s');
				$str="{ response: 'sklad_list', content: [".$poseditor->SearchSkladList($s)."] }";			
				$tmpl->SetText($str);			
			}
			else if($opt=='jsn')
			{
				$action=rcv('a');
				$line_id=rcv('line');
				$data=rcv('data');
				$tmpl->SetText($poseditor->SerialNum($action, $line_id, $data) );	
			}
		}
	}

};


?>