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
include_once("include/doc.s.nulltype.php");
need_auth();
$tmpl->HideBlock('left');
SafeLoadTemplate($CONFIG['site']['inner_skin']);

$l=rcv('l');

try
{

if($l=='agent')
	$sprav=new doc_s_Agent();
else if($l=='dov')
	$sprav=new doc_s_Agent_dov();
else if($l=='inf')
	$sprav=new doc_s_Inform();
else if($l=='pran')
	$sprav=new doc_s_Price_an();
else
	$sprav=new doc_s_Sklad();

if($mode=='')
	$sprav->View();
else if($mode=='srv')
	$sprav->Service();
else if($mode=='edit')
	$sprav->Edit();
else if($mode=='esave')
	$sprav->ESave();
else if($mode=='search')
	$sprav->Search();

}
catch(MysqlException $e)
{
	mysql_query("ROLLBACK");
	$e->WriteLog();
	$tmpl->SetText('');
	$tmpl->msg($e->getMessage(),"err","Ошибка в базе данных!");
}
catch( Exception $e)
{
	mysql_query("ROLLBACK");
	$tmpl->SetText('');
	$tmpl->logger($e->getMessage());
}

$tmpl->write();



?>
