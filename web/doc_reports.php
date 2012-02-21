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

include_once("core.php");
include_once("include/doc.core.php");
need_auth();
SafeLoadTemplate($CONFIG['site']['inner_skin']);


class BaseGSReport
{
	function draw_groups_tree($level)
	{
		$ret='';
		$res=mysql_query("SELECT `id`, `name`, `desc` FROM `doc_group` WHERE `pid`='$level' AND `hidelevel`='0' ORDER BY `name`");
		$i=0;
		$r='';
		if($level==0) $r='IsRoot';
		$cnt=mysql_num_rows($res);
		while($nxt=mysql_fetch_row($res))
		{
			if($nxt[0]==0) continue;
			$item="<label><input type='checkbox' name='g[]' value='$nxt[0]' id='cb$nxt[0]' class='cb' checked onclick='CheckCheck($nxt[0])'>$nxt[1]</label>";
			if($i>=($cnt-1)) $r.=" IsLast";
			$tmp=$this->draw_groups_tree($nxt[0]); // рекурсия
			if($tmp)
				$ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div><ul class='Container' id='cont$nxt[0]'>".$tmp.'</ul></li>';
			else
				$ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div></li>";
			$i++;
		}
		return $ret;
	}


	function GroupSelBlock()
	{
		global $tmpl;
		$tmpl->AddStyle(".scroll_block
		{
			max-height:		250px;
			overflow:		auto;	
		}
		
		div#sb
		{
			display:		none;
			border:			1px solid #888;
		}
		
		.selmenu
		{
			background-color:	#888;
			width:			auto;
			font-weight:		bold;
			padding-left:		20px;
		}
		
		.selmenu a
		{
			color:			#fff;
			cursor:			pointer;	
		}
		
		.cb
		{
			width:			14px;
			height:			14px;
			border:			1px solid #ccc;
		}
		
		");
		$tmpl->AddText("<script type='text/javascript'>
		function gstoggle()
		{
			var gs=document.getElementById('cgs').checked;
			if(gs==true)
				document.getElementById('sb').style.display='block';
			else	document.getElementById('sb').style.display='none';
		}
		
		function SelAll(flag)
		{
			var elems = document.getElementsByName('g[]');
			var l = elems.length;
			for(var i=0; i<l; i++)
			{
				elems[i].checked=flag;
				if(flag)	elems[i].disabled = false;
			}
		}
		
		function CheckCheck(ids)
		{
			var cb = document.getElementById('cb'+ids);
			var cont=document.getElementById('cont'+ids);
			if(!cont)	return;
			var elems=cont.getElementsByTagName('input');
			var l = elems.length;
			for(var i=0; i<l; i++)
			{
				if(!cb.checked)		elems[i].checked=false;
				elems[i].disabled =! cb.checked;
			}
		}
		
		</script>
		<label><input type=checkbox name='gs' id='cgs' value='1' onclick='gstoggle()'>Выбрать группы</label><br>
		<div class='scroll_block' id='sb'>
		<ul class='Container'>
		<div class='selmenu'><a onclick='SelAll(true)'>Выбрать всё<a> | <a onclick='SelAll(false)'>Снять всё</a></div>
		".$this->draw_groups_tree(0)."</ul></div>");
	}
}






$tmpl->HideBlock('left');
$mode=rcv('mode');

$dir=$CONFIG['site']['location'].'/include/reports/';

try
{
	if(isAccess('doc_reports','view'))
	{
			
		if($mode=='')
		{
			doc_menu();	
			$tmpl->SetTitle("Отчёты");
			$tmpl->AddText("<h1>Отчёты</h1>
			<p>Внимание! Отчёты создают высокую нагрузку на сервер, поэтому не рекомендуеся генерировать отчёты во время интенсивной работы с базой данных, а так же не рекомендуется частое использование генератора отчётов по этой же причине!</p>");
			$tmpl->AddText("<ul>");
			if (is_dir($dir))
			{
				if ($dh = opendir($dir))
				{
					while (($file = readdir($dh)) !== false)
					{
						if( preg_match('/.php$/',$file) )
						{
							include_once("$dir/$file");
							$cn=explode('.',$file);
							$class_name='Report_'.$cn[0];
							$class=new $class_name;
							$nm=$class->getName();
							$tmpl->AddText("<li><a href='/doc_reports.php?mode=$cn[0]'>$nm</a></li>");
						}
					}
					closedir($dh);
				}
			}
			$tmpl->AddText("</ul>");
		}
		else if($mode=='pmenu')
		{
			$tmpl->ajax=1;
			$tmpl->SetText("");
			if (is_dir($dir))
			{
				if ($dh = opendir($dir))
				{
					while (($file = readdir($dh)) !== false)
					{
						if( preg_match('/.php$/',$file) )
						{
							include_once("$dir/$file");
							$cn=explode('.',$file);
							$class_name='Report_'.$cn[0];
							$class=new $class_name;
							$nm=$class->getName(1);
							$tmpl->AddText("<div onclick='window.location=\"/doc_reports.php?mode=$cn[0]\"'>$nm</div>");
						}
					}
					closedir($dh);
				}
			}
			$tmpl->AddText("<hr><div onclick='window.location=\"/doc_reports.php\"'>Подробнее</div>");
		}
		else
		{
			doc_menu();
			$tmpl->SetTitle("Отчёты");
			$opt=rcv('opt');
			$fn=$dir.$mode.'.php';
			if(file_exists($fn))
			{
				include_once($fn);
				$class_name='Report_'.$mode;
				$class=new $class_name;
				$tmpl->SetTitle($class->getName());
				$class->Run($opt);
			}
			else $tmpl->msg("Сценарий $fn не найден!","err");	
		}
	}
	else $tmpl->msg("Недостаточно привилегий для выполнения операции!","err");
}
catch(AccessException $e)
{
	$tmpl->SetText('');
	$tmpl->msg($e->getMessage(),'err',"Нет доступа");
}
catch(MysqlException $e)
{
	$tmpl->SetText('');
	$tmpl->msg($e->getMessage()."<br>Сообщение передано администратору",'err',"Ошибка в базе данных");
}
catch (Exception $e)
{
	$tmpl->SetText('');
	$tmpl->msg($e->getMessage(),'err',"Общая ошибка");
}


$tmpl->write();
?>
