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
$doc_id = rcvint('doc_id');

$tmpl->hideBlock('left');
doc_menu('');

$tmpl->addTop(
"
<script type='text/javascript' src='/ui/require.js'></script>
<script type='text/javascript' src='/js/api.js'></script>
<script type='text/javascript' src='/js/common.js'></script>
<script type='text/javascript' src='/css/doc_script.js'></script>
<script type='text/javascript' src='/css/jquery/jquery.js'></script>
<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
<script type='text/javascript' src='/css/jquery/jquery.alerts.js'></script>
<script type='text/javascript' src='/js/poseditor.js'></script>
<link href='/css/poseditor.css' rel='stylesheet' type='text/css' media='screen'></link>
<link href='/css/jquery/jquery.alerts.css' rel='stylesheet' type='text/css' media='screen'></link>
");

$tmpl->addContent("<div id='doc_container'></div>
<script type=\"text/javascript\">

require(['ui/require.config'], function() {
    require(['doceditor'], function(doceditor) {
        var doc = doceditor('doc_container', 'doc_menu');
        doc.init($doc_id);
    });
});
</script>
");

$tmpl->write();
