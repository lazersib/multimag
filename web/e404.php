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
header("ErrorDocument 404");

$tmpl->logger("404: Not found",1);


$tmpl->SetTitle("<h1>Страница не найдена</h1>");

$tmpl->SetText("<h1>Не найдено!</h1>
<p id=text>
Страница, запрашиваемая Вами, не найдена на нашем сервере! Возможно она была перемещена в другое место, или не существует больше! Если Вы пришли с другого сервера, значит Вам дали неверную ссылку! Если же вы перешли по ссылке, размещенной на нашем сервере, эта информация уже записана в лог, и администратор разберётся с проблемой в ближайшее время.
</p>
<p id=text>
А сейчас мы предлагаем воспользоваться главным меню, чтобы найти нужную Вам страницу!
</p>");

$tmpl->write();

?>