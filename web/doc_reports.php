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
		
		doc_menu();
		
		$tmpl->SetTitle("Отчёты");
		if($mode=='')
		{
			
			$tmpl->AddText("<h1>Отчёты</h1>");
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
		else
		{
			$opt=rcv('opt');
			$fn=$dir.$mode.'.php';
			if(file_exists($fn))
			{
				include_once($fn);
				$class_name='Report_'.$mode;
				$class=new $class_name;
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
