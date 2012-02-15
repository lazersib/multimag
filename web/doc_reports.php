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
	$tmpl->msg($e->getMessage(),'err',"Нет доступа");
}
catch(MysqlException $e)
{
	$tmpl->msg($e->getMessage()."<br>Сообщение передано администратору",'err',"Ошибка в базе данных");
}
catch (Exception $e)
{
	$tmpl->msg($e->getMessage(),'err',"Общая ошибка");
}


$tmpl->write();
?>