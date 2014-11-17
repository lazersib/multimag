<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2014, BlackLight, TND Team, http://tndproject.org
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
$tmpl->setTitle("Карта сайта");

$mode = request('mode');
if ($mode == 'xml') {
    $tmpl->ajax=1;
	header("Content-type: application/xml");
    $map = new SiteMap('xml');
    $tmpl->setContent($map->getMap());
} elseif ($mode == 'robots') {
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
Disallow: *html?order=
Disallow: *html?op=
Disallow: *html?view=

Host: ".$CONFIG['site']['name'];
    exit();
} elseif ($mode == 'favicon') {
    $skin = $CONFIG['site']['skin'] ? $CONFIG['site']['skin'] : 'default';
    header("Location: /skins/" . $skin . "/favicon.ico", true, 301);
    exit();
} else {
    $tmpl->setContent("<h1 id='page-title'>Карта сайта</h1>");
    $map = new SiteMap();
    $tmpl->addContent($map->getMap());
}
$tmpl->write();
