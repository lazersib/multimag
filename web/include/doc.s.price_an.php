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


class doc_s_Price_an
{
	function View()
	{
		global $tmpl;
		doc_menu(0,0);
		if(!isAccess('list_price_an','view'))	throw new AccessException("");
		$tmpl->AddStyle("
		.tlist{border: 1px solid #bbb; width: 100%; border-collapse: collapse;}
		.tlist tr:nth-child(2n) {background: #e0f0ff; } 
		.tlist td{border: 1px solid #bbb;}
		");
		$tmpl->AddText("<table width='100%'><tr><td width='300'><h1>Анализатор прайсов</h1>
		<td align='right'>
		</table>
		<table width='100%'><tr><td id='groups' width='200' valign='top' class='lin0'>");
		$this->draw_groups(0);
		$tmpl->AddText("<td id='sklad' valign='top' >");
		$this->ViewBase();
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
			if($s)
				$this->ViewBaseS($g,$s);
			else
				$this->ViewBase($g);
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
		else if($opt=='ceni')
		{
			$pos=rcv('pos');
			$tmpl->ajax=1;
			$res=mysql_query("SELECT `firm_info`.`name`, `parsed_price`.`cost`, `parsed_price`.`nal`, `parsed_price`.`selected`, `firm_info`.`delivery_info` FROM `parsed_price`
			LEFT JOIN `firm_info` ON `firm_info`.`id`=`parsed_price`.`firm`
			WHERE `pos`='$pos'");
			$tmpl->AddText("<table width='100%'><tr><th>Фирма<th>Цена<th>Наличие<th>Доставка");
			while(@$nxt=mysql_fetch_row($res))
			{
				$sel=$nxt[3]?"style='background-color: #cfc'":'';
				$tmpl->AddText("<tr $sel><td>$nxt[0]<td>$nxt[1]<td>$nxt[2]<td>$nxt[4]");
			}	
			$tmpl->AddText("</table>");
		}
		else $tmpl->msg("Неверный режим!");
	}
		
// Служебные функции класса
	function Edit()
	{
		global $tmpl;		
		
		$pos=rcv('pos');
		$param=rcv('param');
		$group=rcv('g');
		
		if($param!='ss')	doc_menu();

		if( ($pos!=0) )
		{
			$this->PosMenu($pos, $param);
		}
		
		if($param=='')
		{
			$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`proizv`,
			`seekdata`.`sql`, `seekdata`.`regex`, `seekdata`.`regex_neg`
			FROM `doc_base`
			LEFT JOIN `seekdata` ON `seekdata`.`id`=`doc_base`.`id`
			WHERE `doc_base`.`id`='$pos'");
			$nxt=@mysql_fetch_row($res);

			$tmpl->AddText("<form action='' method='post'><table cellpadding='0' width='100%'>
			<input type='hidden' name='mode' value='esave'>
			<input type='hidden' name='l' value='pran'>
			<input type='hidden' name='pos' value='$pos'>
        		<tr class='lin0'><td align='right' width='20%'>Наименование
        		<td><input type='text' value='$nxt[1]' disabled style='width: 95%'>
        		<tr class='lin0'><td align='right'>Производитель
			<td><input type='text' value='$nxt[2]' disabled style='width: 95%'>
	
			<tr class='lin1'><td align='right'><b style='color: #f00;'>*</b> Строка поиска совпадений:<td><input type='text' name='sql' value='$nxt[3]' style='width: 95%' id='str' onkeydown=\"PriceRegTest('/docs.php?l=pran&amp;mode=edit&amp;param=ss');\">
			<tr class='lin0'><td align='right'>Регулярное выражение поиска:<td><input type='text' name='regex' value='$nxt[4]' style='width: 95%'
			 id='regex' onkeydown=\"PriceRegTest('/docs.php?l=pran&amp;mode=edit&amp;param=ss');\" >
			<tr class='lin1'><td align='right'>Регулярное выражение отрицания:<td><input type='text' name='regex_neg' value='$nxt[5]' style='width: 95%'
			 id='regex_neg' onkeydown=\"PriceRegTest('/docs.php?l=pran&amp;mode=edit&amp;param=ss');\">
			<tr class='lin0'><td><td><input type='submit' value='Сохранить'>			
			</table></form>
			<div id='regex_result'></div>");

		}
		else if($param=='ss')
		{
			$tmpl->ajax=1;
			
			$str=@$_GET['str'];
			$regex=@$_GET['regex'];
			$regex_neg=@$_GET['regex_neg'];

			$res=mysql_query("SELECT `id`, `search_str`, `replace_str` FROM `prices_replaces`");
			if(mysql_errno())	throw new MysqlException('Не удалось получить данные подстановки!');
			while($nxt=mysql_fetch_row($res))
			{
				$regex=str_replace("{{{$nxt[1]}}}", $nxt[2], $regex);
				$regex_neg=str_replace("{{{$nxt[1]}}}", $nxt[2], $regex_neg);
			}
	
			if($str=='') 
				$tmpl->msg("Строка поиска совпадений пуста!","err","Ошибка введённых данных!");
			else if(@preg_match("/$regex/",'abc')===FALSE)
				$tmpl->msg("Регулярное выражение поиска составлено неверно!","err","Ошибка в регулярном выражении!");
			else if(@preg_match("/$regex_neg/",'abc')===FALSE)
				$tmpl->msg("Регулярное выражение отрицания составлено неверно!","err","Ошибка в регулярном выражении!");
			else
			{
				$costar=array();
				
				$str_array=preg_split("/( OR | AND )/",$str,-1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
				$i=1;
				$sql_add='';
				$conn='';
				foreach($str_array as $str_l)
				{
					if($i)	$sql_add.=" $conn (`price`.`name` LIKE '%$str_l%' OR `price`.`art` LIKE '%$str_l%')";
					else	$conn=$str_l;
					$i=1-$i;
				}

				$res=@mysql_query("SELECT `price`.`id`, `price`.`name`, `firm_info`.`name`, `price`.`cost`, `price`.`art` FROM `price`
				LEFT JOIN `firm_info` ON `firm_info`.`id`=`price`.`firm`
				WHERE $sql_add");
				$cnt=mysql_num_rows($res);
				echo mysql_error();
				
				$tmpl->AddText("<b>Результаты отбора - $cnt совпадений со строкой *$str*:</b>
				<br>Выражение поиска:</b> $regex<br><b>Выражение отрицания:</b> $regex_neg<br>");
				$tmpl->AddText("<table width=100%><tr><th>Price_id<th>Что<th>Где<th>Цена<th>Артикул");
				
				$i=$cnt=0;
	
				while(@$nxt=mysql_fetch_row($res))
				{
					$name_style=$art_style=$style='';
					if($regex)
					{
						$ns=0;
						if( preg_match("/$regex/",$nxt[1]) ) 
						{
							$name_style='background-color: #afa; ';
							$ns=1;
						}
						if( preg_match("/$regex/",$nxt[4]) ) 
						{
							$art_style='background-color: #afa; ';
							$ns=1;
						}
						if(!$ns)	continue;
					}
						
					if($regex_neg)
					{
						$ns=0;
						if( preg_match("/$regex_neg/",$nxt[1]) ) 
						{
							$name_style.='text-decoration: line-through;';
							$ns=1;
						}
						if( preg_match("/$regex_neg/",$nxt[4]) ) 
						{
							$art_style.='text-decoration: line-through;';
							$ns=1;
						}
						if($ns)	$style='background-color: #faa; ';
					}
					//if($style)
					{
						$tmpl->AddText("<tr style='$style'><td>$nxt[0]<td  style='$name_style'>$nxt[1]<td>$nxt[2]<td>$nxt[3]<td  style='$art_style'>$nxt[4]");
						//$cnt++;
						if($cnt>100)
						{
							$tmpl->AddText("<tr><th colspan='5'>Слишком много данных! Некоторые данные не отображены!");
							break;
						}
						
					}
					$i=1-$i;
				}
				$tmpl->AddText("</table>");
			}
		}
		else $tmpl->msg("Неизвестная закладка");
	}
	function ESave()
	{
		global $tmpl;		
		doc_menu();
		$pos=rcv('pos');
		$param=rcv('param');
		$group=rcv('g');
		if(!isAccess('list_price_an','edit'))	throw new AccessException("");
		if($pos!=0)
		{
			$this->PosMenu($pos, $param);
		}

		if($param=='')
		{
			$sql=rcv('sql');
			$regex=@$_POST['regex'];
			$regex_neg=@$_POST['regex_neg'];
			if($sql=='') 
				$tmpl->msg("Строка поиска совпадений пуста!","err","Ошибка введённых данных!");
			else if(preg_match("/$regex/",'abc')===FALSE)
				$tmpl->msg("Регулярное выражение поиска составлено неверно!","err","Ошибка в регулярном выражении!");
			else if(preg_match("/$regex_neg/",'abc')===FALSE)
				$tmpl->msg("Регулярное выражение отрицания составлено неверно!","err","Ошибка в регулярном выражении!");
			else
			{
				$res=mysql_query("REPLACE `seekdata` (`id`, `sql`, `regex`, `regex_neg`) VALUES ('$pos', '$sql', '$regex', '$regex_neg')");
				
				if($res) $tmpl->msg("Данные сохранены!");
				else $tmpl->msg("Ошибка сохранения!".mysql_error(),"err");
			}
		}
		else if($param=='g')
		{
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
			if($res) $tmpl->msg("Сохранено!");
			else $tmpl->msg("Ошибка!","err");
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
			$item="<a href='' title='$nxt[2]' onclick=\"EditThis('/docs.php?l=pran&amp;mode=srv&amp;opt=pl&amp;g=$nxt[0]','sklad'); return false;\" >$nxt[1]</a>";
	
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
		<div><a href='' title='$nxt[2]' onclick=\"EditThis('/docs.php?l=pran&amp;mode=srv&amp;opt=pl&amp;g=0','sklad'); return false;\" >Группы</a></div>
		<ul class='Container'>".$this->draw_level($select,0)."</ul></div>
		Или отбор:<input type='text' id='sklsearch' onkeydown=\"DelayedSave('/docs.php?l=pran&amp;mode=srv&amp;opt=pl','sklad', 'sklsearch'); return true;\" >
		");
	
	}

	function ViewBase($group=0,$s='')
	{
		global $tmpl;
	        if($group)
		{
			$res=mysql_query("SELECT `desc` FROM `doc_group` WHERE `id`='$group'");
			$g_desc=mysql_result($res,0,0);		
			if($g_desc) $tmpl->AddText("<h4>$g_desc</h4>");
		}
        
		$sql="SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`cost`, `parsed_price`.`cost`, `parsed_price`.`nal`,
		`firm_info`.`name`, `firm_info`.`coeff`, `currency`.`coeff`, `price`.`name`, `price`.`cost`, `price`.`art`
		FROM `doc_base`
		LEFT JOIN `parsed_price` ON `doc_base`.`id`=`parsed_price`.`pos`
		LEFT JOIN `firm_info` ON `firm_info`.`id`=`parsed_price`.`firm`
		LEFT JOIN `currency` ON `firm_info`.`currency`=`currency`.`id`
		LEFT JOIN `price` ON `price`.`id`=`parsed_price`.`from`
		WHERE  `doc_base`.`group`='$group'
		ORDER BY `doc_base`.`id`, `parsed_price`.`cost`";

		$lim=50;
		$page=rcv('p');
		$res=mysql_query($sql);
		$row=mysql_num_rows($res);
		echo mysql_error();
		if($row>$lim)
		{
			$dop="g=$group";
			if($page<1) $page=1;
			if($page>1)
			{
				$i=$page-1;
				link_sklad($doc, "$dop&amp;p=$i","&lt;&lt;");
			}
			$cp=$row/$lim;
			for($i=1;$i<($cp+1);$i++)
			{
				if($i==$page) $tmpl->AddText(" <b>$i</b> ");
				else $tmpl->AddText("<a href='' onclick=\"EditThis('/docs.php?l=pran&amp;mode=srv&amp;opt=pl&amp;$dop&amp;p=$i','sklad'); return false;\">$i</a> ");
			}
			if($page<$cp)
			{
				$i=$page+1;
				link_sklad($doc, "$dop&amp;p=$i","&gt;&gt;");
			}
			$tmpl->AddText("<br>");
			$sl=($page-1)*$lim;
	
			$res=mysql_query("$sql LIMIT $sl,$lim");
		}

		if(mysql_num_rows($res))
		{

			$tmpl->AddText("<table class='tlist' cellspacing='1'><tr>
			<th>№<th>Наименование<th>Наша цена<th>Цена<th>Наличие<th>Фирма");
			$i=0;
			$this->DrawSkladTable($res,$s);
			$tmpl->AddText("</table>");

		}
		else $tmpl->msg("В выбранной группе товаров не найдено!");
	}
	
	function ViewBaseS($group=0,$s)
	{
		global $tmpl;
		$sf=0;
		$sklad=$_SESSION['sklad_num'];
		$tmpl->AddText("<b>Показаны наименования изо всех групп!</b><br>");
		$tmpl->AddText("<table width='100%' cellspacing='1' cellpadding='2' border=1><tr>
		<th>№<th>Наименование<th>Наша цена<th>Цена<th>Наличие<th>Фирма");

		
		$sql="SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`cost`, `parsed_price`.`cost`, `parsed_price`.`nal`,
		`firm_info`.`name`, `firm_info`.`coeff`, `currency`.`coeff`, `price`.`name`, `price`.`cost`, `price`.`art`";
        	
        	$limit=100;
		$sqla=$sql."FROM `doc_base`
		LEFT JOIN `parsed_price` ON `doc_base`.`id`=`parsed_price`.`pos`
		LEFT JOIN `firm_info` ON `firm_info`.`id`=`parsed_price`.`firm`
		LEFT JOIN `currency` ON `firm_info`.`currency`=`currency`.`id`
		LEFT JOIN `price` ON `price`.`id`=`parsed_price`.`from`
		WHERE `doc_base`.`name` LIKE '$s%'
		ORDER BY `doc_base`.`id`, `parsed_price`.`cost`
 		LIMIT $limit";
		$res=mysql_query($sqla);
		
		if($cnt=mysql_num_rows($res))
		{
			$tmpl->AddText("<tr class='lin0'><th colspan='18' align='center'>Поиск по названию, начинающемуся на $s: найдено $cnt, $limit максимум");
			$this->DrawSkladTable($res,$s);
			$sf=1;
		}
		$limit=30;
		$sqla=$sql."FROM `doc_base`
		LEFT JOIN `parsed_price` ON `doc_base`.`id`=`parsed_price`.`pos`
		LEFT JOIN `firm_info` ON `firm_info`.`id`=`parsed_price`.`firm`
		LEFT JOIN `currency` ON `firm_info`.`currency`=`currency`.`id`
		LEFT JOIN `price` ON `price`.`id`=`parsed_price`.`from`
        	WHERE `doc_base`.`name` LIKE '%$s%' AND `doc_base`.`name` NOT LIKE '$s%' ORDER BY `doc_base`.`name` LIMIT $limit";
		$res=mysql_query($sqla);
		if($cnt=mysql_num_rows($res))
		{
			$tmpl->AddText("<tr class='lin0'><th colspan='18' align='center'>Поиск по названию, содержащему $s: найдено $cnt, $limit максимум");
			$this->DrawSkladTable($res,$s);
			$sf=1;
		}
		
		$sqla=$sql."FROM `doc_base`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
		LEFT JOIN `parsed_price` ON `doc_base`.`id`=`parsed_price`.`pos`
		LEFT JOIN `firm_info` ON `firm_info`.`id`=`parsed_price`.`firm`
		LEFT JOIN `currency` ON `firm_info`.`currency`=`currency`.`id`
		LEFT JOIN `price` ON `price`.`id`=`parsed_price`.`from`
		WHERE `doc_base_dop`.`analog` LIKE '%$s%' AND `doc_base`.`name` NOT LIKE '%$s%' ORDER BY `doc_base`.`name` LIMIT $limit";
		$res=mysql_query($sqla);
		echo mysql_error();
		if($cnt=mysql_num_rows($res))
		{
			$tmpl->AddText("<tr class='lin0'><th colspan='18' align='center'>Поиск аналога, для $s: найдено $cnt, $limit максимум");
			$this->DrawSkladTable($res,$s);
			$sf=1;
		}
		
		if($sf==0)
			$tmpl->msg("По данным критериям товаров не найдено!");
	}
	
	function Search()
	{
		
	}
	

function DrawSkladTable($res,$s)
{
	global $tmpl;
	$i=$c=0;
	$old_id=$old_cost=0;
	$lin=$old_name='';
	while($nxt=mysql_fetch_row($res))
	{
		$rezerv=DocRezerv($nxt[0],$doc);
		$pod_zakaz=DocPodZakaz($nxt[0],$doc);
		$v_puti=DocVPuti($nxt[0],$doc);
		
		if($rezerv)	$rezerv="<a onclick=\"OpenW('/docs.php?l=inf&mode=srv&opt=rezerv&pos=$nxt[0]'); return false;\"  title='Отобразить документы' href='/docs.php?l=inf&mode=srv&opt=p_zak&pos=$nxt[0]'>$rezerv</a>";
	
		if($pod_zakaz)	$pod_zakaz="<a onclick=\"ShowPopupWin('/docs.php?l=inf&mode=srv&opt=p_zak&pos=$nxt[0]'); return false;\"  title='Отобразить документы' href='/docs.php?l=inf&mode=srv&opt=p_zak&pos=$nxt[0]'>$pod_zakaz</a>";

		if($v_puti)	$v_puti="<a onclick=\"ShowPopupWin('/docs.php?l=inf&mode=srv&opt=vputi&pos=$nxt[0]'); return false;\"  title='Отобразить документы' href='/docs.php?l=inf&mode=srv&opt=p_zak&pos=$nxt[0]'>$v_puti</a>";
		
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

		if($nxt[0]!=$old_id)
		{	
			$i=1-$i;
			if($old_id)
			$tmpl->AddText("<tr>
			<td rowspan='$c'><a href='/docs.php?mode=srv&amp;l=pran&amp;opt=ep&amp;pos=$old_id'>$old_id</a><td align=left rowspan='$c'>$old_name<td rowspan='$c'>$old_cost $lin");
			$old_id=$nxt[0];
			$old_cost=$nxt[2];
			$lin='';
			$c=0;
			$old_name=$nxt[1];			
		}
		
		if($lin) $lin.="<tr>";
		if($nxt[6]==0) $nxt[6]=1;
		if($nxt[7]==0) $nxt[7]=1;
		$coeff=$nxt[6]*$nxt[7];
		if($nxt[9]!='')
			$lin.="<td title='$nxt[8]'>$nxt[3] ($nxt[9]*$coeff)<td>$nxt[4]<td>$nxt[5] ($nxt[10])";
		else	$lin.="<td>-<td>-<td>-";
		$c++;
	}	
	

	{	
		$i=1-$i;
		if($old_id)
		$tmpl->AddText("<tr>
		<td rowspan='$c'><a href='/docs.php?l=pran&amp;mode=srv&amp;opt=ep&amp;pos=$old_id'>$old_id</a><td align=left rowspan='$c'>$old_name<td rowspan='$c'>$old_cost $lin");
		$old_id=$nxt[0];
		$lin='';
		$c=0;
		$old_name=$nxt[1];			
	}
	
}
	
	
	function PosMenu($pos, $param)
	{
			global $tmpl;

			$tmpl->AddText("<table cellspacing='0' cellpadding='5' border='0' width='100%'><tr>");
// 			if($param=='')	$tmpl->AddText("<td class='lin1'>Основные");
// 			else 
			$tmpl->AddText("<td class='lin0'><a href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;pos=$pos'>Основные</a>");
			
			if($param=='d')	$tmpl->AddText("<td class='lin1'>Дополнительные");
			else $tmpl->AddText("<td class='lin0'><a href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=d&amp;pos=$pos'>Дополнительные</a>");
			
			$tmpl->AddText("<td class='lin1'>Анализатор");
			
			if($param=='s')	$tmpl->AddText("<td class='lin1'>Состояние складов");
			else $tmpl->AddText("<td class='lin0'><a href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=s&amp;pos=$pos'>Состояние складов</a>");
			
			if($param=='i')	$tmpl->AddText("<td class='lin1'>Изображения");
			else $tmpl->AddText("<td class='lin0'><a href='/docs.php?l=sklad&amp;mode=srv&amp;opt=ep&amp;param=i&amp;pos=$pos'>Изображения</a>");
			
			$tmpl->AddText("<td class='lin0'>&nbsp;&nbsp;&nbsp;");	
			$tmpl->AddText("</table>");
	
	}
	
};


?>
