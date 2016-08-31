<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2016, BlackLight, TND Team, http://tndproject.org
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

try {
    if ($mode == "") {
        doc_menu();
        $doc_types = \document::getListTypes();
        $doc_names = array();
        foreach ($doc_types as $id => $type) {
            if (!\acl::testAccess('doc.'.$type, \acl::CREATE)) {
                continue;
            }
            $doc = \document::getInstanceFromType($id);
            $doc_names["$id"] = $doc->getViewName();
        }
        asort($doc_names);
        $tmpl->addContent("<h1>Создание нового документа</h1>"
                . "<ul>");
        foreach ($doc_names as $id => $viewname) {
            $tmpl->addContent("<li><a href='?mode=new&amp;type=$id'>" . html_out($viewname) . "</a></li>");
        }
        $tmpl->addContent("</ul>");
    } else if ($mode == 'new') {
        $type = rcvint('type');
        $document = document::getInstanceFromType($type);
        $document->head();
    } else if ($mode == "heads") {
        if (!$doc) {
            $type = request('type');
            $document = document::getInstanceFromType($type);
        } else {
            $document = document::getInstanceFromDb($doc);
        }
        $document->head_submit($doc);
    }
    else if ($mode == "jheads") {
        if (!$doc) {
            $type = request('type');
            $document = document::getInstanceFromType($type);
        } else {
            $document = document::getInstanceFromDb($doc);
        }
        $document->json_head_submit($doc);
    }
    else if ($mode == "ehead") {
        $document = document::getInstanceFromDb($doc);
        $document->head($doc);
    } else if ($mode == "body") {
        $document = document::getInstanceFromDb($doc);
        $document->body($doc);
    } else if ($mode == "srv") {
        $document = document::getInstanceFromDb($doc);
        $document->Service($doc);
    } else if ($mode == 'applyj') {
        $document = document::getInstanceFromDb($doc);
        $tmpl->ajax = 1;
        $tmpl->setContent($document->ApplyJson());
    } else if ($mode == 'cancelj') {
        $document = document::getInstanceFromDb($doc);
        $tmpl->ajax = 1;
        $tmpl->setContent($document->CancelJson());
    } else if ($mode == 'conn') {
        $document = document::getInstanceFromDb($doc);
        $tmpl->ajax = 1;
        $p_doc = rcvint('p_doc');
        $tmpl->setContent($document->ConnectJson($p_doc));
    } else if ($mode == 'forcecancel') {
        $document = document::getInstanceFromDb($doc);
        $document->ForceCancel();
    } else if ($mode == 'print') {
        $document = document::getInstanceFromDb($doc);
        $opt = request('opt');
        $document->PrintForm($opt);
    } else if ($mode == 'fax') {
        $document = document::getInstanceFromDb($doc);
        $opt = request('opt');
        $document->SendFax($opt);
    } else if ($mode == 'email') {
        $document = document::getInstanceFromDb($doc);
        $opt = request('opt');
        $document->SendEmail($opt);
    } else if ($mode == 'morphto') {
        $document = document::getInstanceFromDb($doc);
        $target_type = request('tt');
        $document->MorphTo($target_type);
    } else if ($mode == "incnum") {
        $tmpl->ajax = 1;
        $type = request('type');
        $sub = request('sub');
        $date = rcvdate('date');
        $firm = rcvint('firm');
        if (!$doc) {
            $document = document::getInstanceFromType($type);
            $altnum = $document->getNextAltNum($type, $sub, $date, $firm);
        } else {
            $document = document::getInstanceFromDb($doc);
            $altnum = $document->getNextAltNum($type, $sub, $date, $firm);
            $document->setDocData('altnum', $altnum);
        }
        echo "$altnum";
        exit(0);
    } else if ($mode == 'log') {
        $document = document::getInstanceFromDb($doc);
        $document->showLog();
    } else if ($mode == 'tree') {
        $document = document::getInstanceFromDb($doc);
        $document->viewDocumentTree();
    } else {
        $tmpl->msg("ERROR $mode", "err");
    }
} catch (AccessException $e) {
    $tmpl->ajax = 0;
    $tmpl->msg($e->getMessage(), 'err', "Нет доступа");
} catch (mysqli_sql_exception $e) {
    $id = writeLogException($e);
    if ($tmpl->ajax) {
        $ret_data = array('response' => 'err',
            'message' => "Ошибка в базе данных! Порядковый номер ошибки: $id. Сообщение об ошибке занесено в журнал.");
        $tmpl->setContent(json_encode($ret_data, JSON_UNESCAPED_UNICODE));
    } else {
        $tmpl->msg("Порядковый номер ошибки: $id<br>Сообщение об ошибке занесено в журнал", 'err', "Ошибка в базе данных");
    }
} catch (Exception $e) {
    $id = writeLogException($e);
    if ($tmpl->ajax) {
        $ret_data = array('response' => 'err',
            'message' => "Общая ошибка! " . $e->getMessage());
        $tmpl->setContent(json_encode($ret_data, JSON_UNESCAPED_UNICODE));
    } else {
        $tmpl->msg($e->getMessage(), 'err', "Общая ошибка");
    }
}

$tmpl->write();
