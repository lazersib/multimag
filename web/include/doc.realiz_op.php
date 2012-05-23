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


$doc_types[15]="Опер. реализация";

class doc_Realiz_op extends doc_Realizaciya
{
	// Создание нового документа или редактирование заголовка старого

	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				=15;
		$this->doc_name				='realiz_op';
		$this->doc_viewname			='Реализация товара (опер)';
		$this->sklad_editor_enable		=true;
		$this->sklad_modify			=0;
		$this->header_fields			='sklad cena separator agent';
		$this->dop_menu_buttons			="<a href='' onclick=\"ShowPopupWin('/doc.php?mode=srv&amp;opt=dov&amp;doc=$doc'); return false;\" title='Доверенное лицо'><img src='img/i_users.png' alt='users'></a>";
		settype($this->doc,'int');
	}

	function DopHead()
	{
		global $tmpl;

		$cur_agent=$this->doc_data['agent'];
		if(!$cur_agent)		$cur_agent=1;

		if(!$this->dop_data['platelshik'])	$this->dop_data['platelshik']=$cur_agent;
		if(!$this->dop_data['gruzop'])		$this->dop_data['gruzop']=$cur_agent;

		$res=mysql_query("SELECT `name` FROM `doc_agent` WHERE `id`='{$this->dop_data['platelshik']}'");
		if(mysql_errno())	throw new MysqlException('Ошибка выборки имени плательщика');
		$plat_name=mysql_result($res,0,0);

		$res=mysql_query("SELECT `name` FROM `doc_agent` WHERE `id`='{$this->dop_data['gruzop']}'");
		if(mysql_errno())	throw new MysqlException('Ошибка выборки имени грузополучателя');
		$gruzop_name=mysql_result($res,0,0);

		$tmpl->AddText("<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		Плательщик:<br>
		<input type='hidden' name='plat_id' id='plat_id' value='{$this->dop_data['platelshik']}'>
		<input type='text' id='plat'  style='width: 100%;' value='$plat_name'><br>
		Грузополучатель:<br>
		<input type='hidden' name='gruzop_id' id='gruzop_id' value='{$this->dop_data['gruzop']}'>
		<input type='text' id='gruzop'  style='width: 100%;' value='$gruzop_name'><br>
		<script type=\"text/javascript\">
		$(document).ready(function(){
			$(\"#plat\").autocomplete(\"/docs.php\", {
			delay:300,
			minChars:1,
			matchSubset:1,
			autoFill:false,
			selectFirst:true,
			matchContains:1,
			cacheLength:10,
			maxItemsToShow:15,
			formatItem:agliFormat,
			onItemSelect:platselectItem,
			extraParams:{'l':'agent','mode':'srv','opt':'ac'}
			});
			$(\"#gruzop\").autocomplete(\"/docs.php\", {
			delay:300,
			minChars:1,
			matchSubset:1,
			autoFill:false,
			selectFirst:true,
			matchContains:1,
			cacheLength:10,
			maxItemsToShow:15,
			formatItem:agliFormat,
			onItemSelect:gruzopselectItem,
			extraParams:{'l':'agent','mode':'srv','opt':'ac'}
			});
		});

		function platselectItem(li) {
		if( li == null ) var sValue = \"Ничего не выбрано!\";
		if( !!li.extra ) var sValue = li.extra[0];
		else var sValue = li.selectValue;
		document.getElementById('plat_id').value=sValue;
		}

		function gruzopselectItem(li) {
		if( li == null ) var sValue = \"Ничего не выбрано!\";
		if( !!li.extra ) var sValue = li.extra[0];
		else var sValue = li.selectValue;
		document.getElementById('gruzop_id').value=sValue;
		}
		</script>
		");
		$checked=$this->dop_data['received']?'checked':'';
		$tmpl->AddText("<label><input type='checkbox' name='received' value='1' $checked>Документы подписаны и получены</label><br>");
		$checked=$this->dop_data['return']?'checked':'';
		$tmpl->AddText("<label><input type='checkbox' name='return' value='1' $checked>Возвратный документ</label><br>");
	}

	function DopSave()
	{
		$plat_id=rcv('plat_id');
		$gruzop_id=rcv('gruzop_id');
		$received=rcv('received');
		$return=rcv('return');

		$doc=$this->doc;
		mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)
		VALUES ( '{$this->doc}' ,'platelshik','$plat_id'),
		( '{$this->doc}' ,'gruzop','$gruzop_id'),
		( '{$this->doc}' ,'received','$received'),
		( '{$this->doc}' ,'return','$return')");
	}

	function DopBody()
	{
		global $tmpl;
		if(isset($this->dop_data['received']))
			if($this->dop_data['received'])
				$tmpl->AddText("<br><b>Документы подписаны и получены</b><br>");
	}
	
	function DocApply($silent=0)
	{
		$tim=time();

		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if( !($nx=@mysql_fetch_row($res) ) )	throw new MysqlException('Ошибка выборки данных документа при проведении!');
		if( $nx[4] && ( !$silent) )		throw new Exception('Документ уже был проведён!');

		if($silent)	return;
		$res=mysql_query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->doc}'");
		if( !$res )				throw new MysqlException('Ошибка проведения, ошибка установки даты проведения!');
	}
	
	function DocCancel()
	{
		global $uid;
		$tmpl->ajax=1;

		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка выборки данных документа!');
		if(! ($nx=@mysql_fetch_row($res)))	throw new Exception('Документ не найден!');
		if(! $nx[4])				throw new Exception('Документ НЕ проведён!');

		$res=mysql_query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка установки флага!');
	}

};
?>