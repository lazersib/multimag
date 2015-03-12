<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2015, BlackLight, TND Team, http://tndproject.org
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

$tmpl->hideBlock('left');
$mode = request('mode');
$doc = rcvint("doc");

$tmpl->addTop("
<script type='text/javascript' src='/css/jquery/jquery.js'></script>
<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
<script type='text/javascript' src='/css/jquery/jquery.alerts.js'></script>
<script type='text/javascript' src='/css/doc_script.js'></script>
<link href='/css/jquery/jquery.alerts.css' rel='stylesheet' type='text/css' media='screen'>
");

try
{
	if($mode=="")
	{
		doc_menu();
		$tmpl->addContent("<h1>Создание нового документа</h1><h3>Выберите тип документа</h3><ul>");
		$res=$db->query("SELECT `id`, `name` FROM `doc_types` ORDER BY `name`");
		while($nxt=$res->fetch_row())
		{
			$tmpl->addContent("<li><a href='?mode=new&amp;type=$nxt[0]'>".html_out($nxt[1])."</a></li>");
		}
		$tmpl->addContent("</ul>");
	}
	else if($mode=='new')
	{
		$type=rcvint('type');
		$document=AutoDocumentType($type, 0);
		$document->head();
	}
	else if($mode=="heads")
	{
		if(!$doc)
		{
			$type= request('type');
			$document = AutoDocumentType($type, 0);
		}
		else	$document=AutoDocument($doc);
		$document->head_submit($doc);
	}
	else if($mode=="jheads")
	{
		if(!$doc)
		{
			$type= request('type');
			$document=AutoDocumentType($type, 0);
		}
		else	$document=AutoDocument($doc);
		$document->json_head_submit($doc);
	}
	else if($mode=="ehead")
	{
		$document=AutoDocument($doc);
		$document->head($doc);
	}
	else if($mode=="body")
	{
		$document=AutoDocument($doc);
		$document->body($doc);
	}
	else if($mode=="srv")
	{
		$document=AutoDocument($doc);
		$document->Service($doc);
	}
	else if($mode=='applyj')
	{
		$document=AutoDocument($doc);
		$tmpl->ajax=1;
		$tmpl->setContent($document->ApplyJson());
	}
	else if($mode=='cancelj')
	{
		$document=AutoDocument($doc);
		$tmpl->ajax=1;
		$tmpl->setContent($document->CancelJson());
	}
	else if($mode=='conn')
	{
		$document=AutoDocument($doc);
		$tmpl->ajax=1;
		$p_doc=rcvint('p_doc');
		$tmpl->setContent($document->ConnectJson($p_doc));
	}
	else if($mode=='forcecancel')
	{
		$document=AutoDocument($doc);
		$document->ForceCancel();
	}
	else if($mode=='print')
	{
		$document=AutoDocument($doc);
		$opt=request('opt');
		$document->PrintForm($opt);
	}
	else if($mode=='fax')
	{
		$document=AutoDocument($doc);
		$opt=request('opt');
		$document->SendFax($opt);
	}
	else if($mode=='email')
	{
		$document=AutoDocument($doc);
		$opt=request('opt');
		$document->SendEmail($opt);
	}
	else if($mode=='morphto')
	{
		$document=AutoDocument($doc);
		$target_type=request('tt');
		$document->MorphTo($target_type);
	}
	else if($mode=='getinfo')
	{
		$document=AutoDocument($doc);
		$document->GetInfo();
	}
	else if($mode=="incnum")
	{
		$tmpl->ajax=1;
		$type=request('type');
		$sub=request('sub');
		$date=rcvdate('date');
		$firm=rcvint('firm');
		if(!$doc)
		{
			$document=AutoDocumentType($type, 0);
			$altnum=$document->getNextAltNum($type,$sub,$date,$firm);
		}
		else {
			$document=AutoDocument($doc);
			$altnum=$document->getNextAltNum($type,$sub,$date,$firm);
			$document->setDocData('altnum', $altnum);
		}

		
		echo "$altnum";
		exit(0);
	}
        else if($mode=='log') {
            $document=AutoDocument($doc);
            $document->showLog();
        }
	else $tmpl->msg("ERROR $mode","err");
}
catch(AccessException $e) {
	$tmpl->ajax=0;
	$tmpl->msg($e->getMessage(),'err',"Нет доступа");
}
catch(mysqli_sql_exception $e) {
	$id = writeLogException($e);
	if($tmpl->ajax) {
		$ret_data = array('response'=>'err',
			'message' => "Ошибка в базе данных! Порядковый номер ошибки: $id. Сообщение передано администратору.");
		$tmpl->setContent(json_encode($ret_data, JSON_UNESCAPED_UNICODE));
	}
	else	$tmpl->msg("Порядковый номер ошибки: $id<br>Сообщение передано администратору", 'err', "Ошибка в базе данных");
}
catch (Exception $e) {
    $id = writeLogException($e);
    if($tmpl->ajax) {
            $ret_data = array('response'=>'err',
                    'message' => "Общая ошибка! ".$e->getMessage());
            $tmpl->setContent(json_encode($ret_data, JSON_UNESCAPED_UNICODE));
    }
    else	$tmpl->msg($e->getMessage(),'err',"Общая ошибка");
}

$tmpl->write();
