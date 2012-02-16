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
include_once("include/sitemap.inc.php");
$tmpl->SetTitle("Карта сайта");

if($mode=='xml')
{
	$tmpl->ajax=1;
	header("Content-type: text/xml");
	$map=new SiteMap('xml');
	$tmpl->SetText('');
	$tmpl->AddText($map->getMap());
}
else if($mode=='robots')
{
	$tmpl->ajax=1;
	header("Content-Type: text/plain");
	echo"User-Agent: *
Disallow: /adv_search
Disallow: /img
Disallow: /kcaptcha
Disallow: /login
Disallow: /search
Disallow: /user
Disallow: /fpdf
Disallow: /basket
Disallow: *basket
Disallow: *korz
Host: ".$CONFIG['site']['name'];
	exit();
}
else if($mode=='favicon')
{
	header("Location: /skins/".$CONFIG['site']['skin']."/favicon.ico", true, 301);
	exit();
}
else
{
	$tmpl->SetText("<h1 id='page-title'>Карта сайта</h1>");
	$map=new SiteMap();
	$tmpl->AddText($map->getMap());

}
$tmpl->write();
?>