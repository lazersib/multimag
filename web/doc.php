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

$tmpl->HideBlock('left');

$GLOBALS['m_left']=0;
$mode=rcv('mode');
$doc=rcv("doc");
$document=AutoDocument($doc);

$tmpl->AddTMenu("
<script src='/css/jquery/jquery.js' type='text/javascript'></script>
<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
<script type='text/javascript' src='/css/doc_script.js'></script>

<!-- Core files -->
<script src='/css/jquery/jquery.alerts.js' type='text/javascript'></script>
<link href='/css/jquery/jquery.alerts.css' rel='stylesheet' type='text/css' media='screen' />
");

try
{
if($mode=="")
{
	doc_menu();
	$tmpl->AddText("<h1>Создание нового документа</h1><h3>Выберите тип документа</h3><ul>");
	$res=mysql_query("SELECT `id`, `name` FROM `doc_types` ORDER BY `name`");
	while($nxt=mysql_fetch_row($res))
	{
		$tmpl->AddText("<li><a href='?mode=new&amp;type=$nxt[0]'>$nxt[1]</a></li>");
	}
	$tmpl->AddText("</ul>");
}
else if($mode=='new')
{
	$type=rcv('type');
	$document=AutoDocumentType($type, 0);
	$document->head();
}
else if($mode=="heads")
{
	if(!$doc)
	{
		$type=rcv('type');
		$document=AutoDocumentType($type, 0);
	}
	$document->head_submit($doc);
}
else if($mode=="jheads")
{
	if(!$doc)
	{
		$type=rcv('type');
		$document=AutoDocumentType($type, 0);
	}
	$document->json_head_submit($doc);
}
else if($mode=="ehead")
{
	$document->head($doc);
}
else if($mode=="body")
{
	$document->body($doc);
}
else if($mode=="srv")
{
	$document->Service($doc);
}
else if($mode=='applyj')
{
	$tmpl->ajax=1;
	$tmpl->SetText($document->ApplyJson());
}
else if($mode=='cancelj')
{
	$tmpl->ajax=1;
	$tmpl->SetText($document->CancelJson());
}
else if($mode=='conn')
{
	$tmpl->ajax=1;
	$p_doc=rcv('p_doc');
	$tmpl->SetText($document->ConnectJson($p_doc));
}
else if($mode=='forcecancel')
{
	$document->ForceCancel();
}
else if($mode=='print')
{
	$opt=rcv('opt');
	$document->PrintForm($doc, $opt);
}
else if($mode=='morphto')
{
	$target_type=rcv('tt');
	$document->MorphTo($doc, $target_type);
}
// Это переделать !!!!!!!!!!!!!!!!!!
else if($mode=="incnum")
{
	$tmpl->ajax=1;
	$type=rcv('type');
	$subtype=rcv('s');
	if($doc)
	{
		$res=mysql_query("SELECT `type`,`subtype`,`altnum` FROM `doc_list` WHERE `id`='$doc'");
		$nxt=mysql_fetch_row($res);
		$type=$nxt[0];
	}
	$altnum=GetNextAltNum($type,$subtype,$nxt[2]);
	echo "$altnum";
	exit(0);
}
else $tmpl->msg("ERROR $mode","err");
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
