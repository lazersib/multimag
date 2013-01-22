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


class Report_Agent
{
	function getName($short=0)
	{
		if($short)	return "По агенту";
		else		return "Отчёт по агенту";
	}
	

	function Form()
	{
		global $tmpl;
		$date_st=date("Y-m-01");
		$date_end=date("Y-m-d");
		$tmpl->AddText("<h1>".$this->getName()."</h1>
		<script src='/css/jquery/jquery.js' type='text/javascript'></script>
		<script src='/css/jquery/jquery.alerts.js' type='text/javascript'></script>
		<link href='/css/jquery/jquery.alerts.css' rel='stylesheet' type='text/css' media='screen'>
		<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='agent'>
		<input type='hidden' name='opt' value='make'>
		Агент-партнёр:<br>
		<input type='hidden' name='agent' id='agent_id' value=''>
		<input type='text' id='ag' name='agent_name' style='width: 400px;' value=''><br>
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
		
		</script>");	
	}
	
	function MakeHTML()
	{
		global $tmpl;
		$agent=rcv('agent');
		$tmpl->LoadTemplate('print');
		$res=mysql_query("SELECT `name` FROM `doc_agent` WHERE `id`='$agent'");
		$ag_name=mysql_result($res,0,0);
		$tmpl->SetText("<h1>Отчет по: $ag_name</h1>
		<table><tr>
		<th>Документ<th>Приход<th>Расход<th>Остаток");
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`sum`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`, `doc_types`.`name` AS `doc_type`
		FROM `doc_list`
		LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		WHERE `doc_list`.`ok`>'0' AND `doc_list`.`mark_del`='0' AND `doc_list`.`agent`='$agent'
		ORDER BY `doc_list`.`date`");
		$sum=0;
		echo mysql_error();
		while($nxt=mysql_fetch_row($res))
		{
			$prix=$rasx=$tovar=0;
			switch($nxt[1])
			{
				case 1: $prix=$nxt[2]; $tovar=1; break;
				case 2: $rasx=$nxt[2]; $tovar=1; break;
				case 4: $prix=$nxt[2]; break;
				case 5: $rasx=$nxt[2]; break;
				case 6: $prix=$nxt[2]; break;
				case 7: $rasx=$nxt[2]; break;			
				case 18:
				{
					if($nxt[2]>0)
						$rasx=$nxt[2];
					else
						$prix=abs($nxt[2]);
				}
				break;
			}
			$sum=round($sum+$prix-$rasx);
			$sum_p=$prix_p=$rasx_p='';
			if($sum) $sum_p=sprintf("%0.2f",$sum);
			if($prix) $prix_p=sprintf("%0.2f",$prix);
			if($rasx) $rasx_p=sprintf("%0.2f",$rasx);
			$dt=date("d.m.Y H:i:s",$nxt[5]);
			
			if($tovar)
			{
				$tovar='';
				$rs=mysql_query("SELECT `doc_base`.`name`, `doc_base`.`proizv`,  `doc_list_pos`.`cnt`
				FROM `doc_list_pos`
				LEFT JOIN `doc_base` ON `doc_base`.`id`= `doc_list_pos`.`tovar`
				WHERE `doc_list_pos`.`doc`='$nxt[0]'");
				while($nx=mysql_fetch_row($rs))
				{
					if(!$tovar) $tovar="$nx[0]/$nx[1]:$nx[2]";
					else $tovar.=", $nx[0]/$nx[1]:$nx[2]";
				}
				$tovar="<br>Товары: $tovar";
			}
			else $tovar='';
			
			$tmpl->AddText("<tr><td>$nxt[6] N{$nxt[3]}{$nxt[4]} ($nxt[0])<br>от $dt $tovar<td>$prix_p<td>$rasx_p<td>$sum_p");
		}
		$tmpl->AddText("</table>");
	}
	
	function Run($opt)
	{
		if($opt=='')	$this->Form();
		else		$this->MakeHTML();	
	}
};

?>

