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
include_once("include/doc.s.nulltype.php");
need_auth();

SafeLoadTemplate($CONFIG['site']['inner_skin']);

$l = request('l');
$mode = request('mode');

if($l=='agent')
	$sprav=new doc_s_Agent();
else if($l=='dov')
	$sprav=new doc_s_Agent_dov();
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


$tmpl->write();
?>
