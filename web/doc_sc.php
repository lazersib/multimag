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
include_once("include/doc.nulltype.php");

need_auth();

SafeLoadTemplate($CONFIG['site']['inner_skin']);

$GLOBALS['m_left']=0;
$mode=rcv('mode');
$doc=rcv("doc");
$document=AutoDocument($doc);

$tmpl->AddTMenu("<script type='text/javascript' src='/css/doc_script.js'></script>
<script src='/css/jquery/jquery.js' type='text/javascript'></script>
<!-- Core files -->
<script src='/css/jquery/jquery.alerts.js' type='text/javascript'></script>
<link href='/css/jquery/jquery.alerts.css' rel='stylesheet' type='text/css' media='screen' />");

$dir=$CONFIG['site']['location'].'/include/doc_scripts/';

try
{

if(isAccess('doc_scripts','view'))
{
	doc_menu();
	$tmpl->SetTitle("Сценарии и операции");
	if($mode=='')
	{
		$tmpl->AddText("<h1>Сценарии и операции</h1>");
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
						$class_name='ds_'.$cn[0];
						$class=new $class_name;
						$nm=$class->getName();
						$tmpl->AddText("<li><a href='/doc_sc.php?mode=view&amp;sn=$cn[0]'>$nm</a></li>");
					}
				}
				closedir($dh);
			}
		}
		$tmpl->AddText("</ul>");
	}
	else
	{
		$sn=rcv('sn');
		$fn=$dir.$sn.'.php';
		if(file_exists($fn))
		{
			include_once($fn);
			$cn=explode('.',$sn);
			$class_name='ds_'.$sn;
			$class=new $class_name;
			$class->Run($mode);
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
