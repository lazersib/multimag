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

require_once("core.php");
SafeLoadTemplate($CONFIG['site']['inner_skin']);
$tmpl->setTitle("Внутреннияя база знаний");
$tmpl->addBreadcrumb('ЛК', '/user.php');
$tmpl->addBreadcrumb('Внутреннияя база знаний', '/intkb.php');

$wikiparser = new \WikiParser();
if (!isset($_REQUEST['p'])) {
    $arr = explode('/', $_SERVER['REQUEST_URI']);
    $arr = explode('.', @$arr[2]);
    $p = urldecode(urldecode(@$arr[0]));
} else {
    $p = $_REQUEST['p'];
}

need_auth();
\acl::accessGuard('service.intkb', \acl::VIEW);

function articles_form($p, $text = '', $type = 0) {
    global $tmpl, $CONFIG;
    $pref = \pref::getInstance();
    $types = array(0 => 'Wiki (Простая и безопасная разметка, рекомендуется)', 1 => 'HTML (Для профессионалов. Может быть небезопасно.)', 2 => 'Wiki+HTML');
    $tmpl->addContent("
	<script type='text/javascript' src='/js/tiny_mce/tiny_mce.js'></script>
	<script type='text/javascript'>

function schange()
{
	var tme=document.getElementById('tme')
	if(tme.checked)
	{
		tinyMCE.init({
		theme : 'advanced',
		mode : 'specific_textareas',
		editor_selector : 'e_msg',
		plugins : 'fullscreen',
		force_hex_style_colors : true,
		theme_advanced_buttons1 : 'bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect',
		theme_advanced_buttons2 : 'cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor',
		theme_advanced_buttons3 : 'tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,iespell,advhr,|,fullscreen',
		theme_advanced_buttons4 : 'insertlayer,moveforward,movebackward,absolute,|,styleprops,spellchecker,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,blockquote,pagebreak,|,insertfile,insertimage',
		theme_advanced_toolbar_location : 'top',
		theme_advanced_toolbar_align : 'left',
		theme_advanced_statusbar_location : 'bottom',
		theme_advanced_resizing : true,
		document_base_url : 'http://{$pref->site_name}/intkb.php/',
		fullscreen_new_window : true,
		element_format : 'html',
		plugins : 'autolink,lists,spellchecker,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template',

	});
		tinyMCE.activeEditor.show();
	}
	else		tinyMCE.activeEditor.hide();
}
</script>
	<fieldset>
	<legend>Правка статьи во внутренней базе знаний</legend>
	<form action='/intkb.php' method='post'>
	<input type='hidden' name='mode' value='save'>
	<input type='hidden' name='p' value='" . html_out($p) . "'>
	Тип разметки:<br>
	<select name='type' id='select_type' onchange='schange()'>");
    foreach ($types AS $id => $name) {
        $s = ($id == $type) ? 'selected' : '';
        $tmpl->addContent("<option value='$id'{$s}>$name</option>");
    }
    $text = html_out($text);
    $tmpl->addContent("</select><label><input type='checkbox' id='tme' onclick='schange()'>Визуальный редактор</label><br>
	<textarea class='e_msg' name='text' rows='10' cols='80'>$text</textarea><br>
	<button type='submit'>Сохранить</button>
	</form><br><a href='/wikiphoto.php'>Галерея изображений</a><br>
	<h3>Примеры wiki разметки</h3>
	<table class='list' width='100%'>
	<th><tr>
	</table>");
}

try {
    $mode = request('mode');
    if ($p == "") {
        $tmpl->setContent("<ul><li><a href='http://multimag.tndproject.org/wiki/userdoc' style='color:#F00'>Общая справка по multimag</a></li></ul>");
        $tmpl->setTitle("Статьи");
        $res = $db->query("SELECT `name`, `text` FROM `intkb` ORDER BY `name`");

        $tmpl->addContent("<ul class='items'>");
        while ($nxt = $res->fetch_row()) {
            $text = $wikiparser->parse($nxt[1]);
            $h = $wikiparser->title . ' ( ' . $wikiparser->unwiki_link($nxt[0]) . ' )';
            $tmpl->addContent("<li><a class='wiki' href='/intkb.php?p=".html_out($nxt[0])."'>$h</a></li>");
        }
        $tmpl->addContent("</ul>");
        if (\acl::testAccess('service.intkb', \acl::UPDATE) || \acl::testAccess('service.intkb', \acl::CREATE)) {
            $tmpl->addContent("<form><input type='hidden' name='mode' value='edit'><fieldset><legend>Создать/править статью</legend>"
                . "Имя статьи:<br><input type='text' name='p'><br><button type='submit'>Далее</button></form></fieldset>");
        }
    } else {
        $page_escaped = $db->real_escape_string($p);
        $res = $db->query("SELECT `intkb`.`name` AS `article_name`, `a`.`name` AS `author_name`, `intkb`.`date`, `intkb`.`changed`, 
                `b`.`name` AS `editor_name`, `intkb`.`text`, `intkb`.`type`
            FROM `intkb`
            LEFT JOIN `users` AS `a` ON `a`.`id`=`intkb`.`autor`
            LEFT JOIN `users` AS `b` ON `b`.`id`=`intkb`.`changeautor`
            WHERE `intkb`.`name` LIKE '$page_escaped'");
        if ($res->num_rows) {
            $nxt = $res->fetch_assoc();
            $h = $meta_description = $meta_keywords = '';
            $text = $nxt['text'];
            //if($nxt['type']==0)		$text = html_out($text);
            if ($nxt['type'] == 0 || $nxt['type'] == 2) {
                $text = $wikiparser->parse($text);
                $h = $wikiparser->title;
                $meta_description = @$wikiparser->definitions['meta_description'];
                $meta_keywords = @$wikiparser->definitions['meta_keywords'];
            }

            if (!$h) {
                $h = explode(":", $p, 2);
                if (@$h[1])
                    $h = $wikiparser->unwiki_link($h[1]);
                else
                    $h = html_out($wikiparser->unwiki_link($p));
            }
            if ($mode == '') {
                $tmpl->addBreadcrumb('Внутреннияя база знаний', '/intkb.php');
                $tmpl->addBreadcrumb(strip_tags($h), '');
                $tmpl->setTitle(strip_tags($h));
                if ($nxt['editor_name'])
                    $ch = ", последнее изменение - {$nxt['editor_name']}, date {$nxt['changed']}";
                else
                    $ch = "";
                if ($nxt['type'] == 0 || $nxt['type'] == 2) {
                    $tmpl->addContent("<div id='page-info'>Создал: {$nxt['author_name']}, date: {$nxt['date']} $ch");
                }
                if (\acl::testAccess('service.intkb', \acl::UPDATE)) {
                    $tmpl->addContent(", <a href='intkb.php?p=" . html_out($nxt['article_name']) . "&amp;mode=edit'>Исправить</a>");
                }
                $tmpl->addContent("</div>");

                $tmpl->addContent("<div class='article_text'>$text</div>");
                $tmpl->setMetaKeywords($meta_keywords);
                $tmpl->setMetaDescription($meta_description);
            }
            else {
                if ($mode == 'edit') {
                    \acl::accessGuard('service.intkb', \acl::UPDATE);
                    $tmpl->addContent("<h1>Правим $h</h1>");
                    articles_form($p, $nxt['text'], $nxt['type']);
                }
                elseif ($mode == 'save') {
                    \acl::accessGuard('service.intkb', \acl::UPDATE);
                    $type = rcvint('type');
                    if ($type < 0 || $type > 2) {
                        $type = 0;
                    }
                    $text = $db->real_escape_string(@$_REQUEST['text']);
                    $uid = intval($_SESSION['uid']);
                    $res = $db->query("UPDATE `intkb` SET `changeautor`='$uid', `changed`=NOW() ,`text`='$text', `type`='$type'
                                                    WHERE `name` LIKE '$page_escaped'");

                    header("Location: intkb.php?p=" . $nxt['article_name']);
                    exit();
                } else {
                    throw new \NotFoundException('Неверный параметр');
                }
            }
        } else {
            if ($mode == '') {
                $res = $db->query("SELECT `name` FROM `intkb` WHERE `name` LIKE '$page_escaped:%' ORDER BY `name`");
                if ($res->num_rows) {
                    $tmpl->setContent("<h1>Раздел " . html_out($p) . "</h1>");
                    $tmpl->setTitle(strip_tags($p));
                    $tmpl->addContent("<ul>");
                    while ($nxt = $res->fetch_row()) {
                        $h = explode(":", $nxt[0], 2);
                        $h = $wikiparser->unwiki_link($h[1]);
                        $tmpl->addContent("<li><a href='/article/" . html_out($nxt[0]) . ".html'>$h</a></li>");
                    }
                    $tmpl->addContent("</ul>");
                } else {
                    $tmpl->msg("Извините, статья " . html_out($p) . " не найдена на нашем сайте. Возможно, вам дали неверную ссылку, либо статья была удалена или перемещена в другое место. Для того, чтобы найти интересующую Вас информацию, воспользуйтесь ", "info");
                    header('HTTP/1.0 404 Not Found');
                    header('Status: 404 Not Found');
                    if (\acl::testAccess('service.intkb', \acl::CREATE, true)) {
                        $tmpl->addContent("<a href='intkb.php?p=" . html_out(strip_tags($p)) . "&amp;mode=edit'>Создать</a>");
                    }
                }
            }
            else {
                if ($mode == 'edit') {
                    \acl::accessGuard('service.intkb', \acl::UPDATE);
                    $h = $wikiparser->unwiki_link($p);
                    $tmpl->addContent("<h1>Создаём " . html_out($h) . "</h1>");
                    articles_form($p);
                }
                else if ($mode == 'save') {
                    \acl::accessGuard('service.intkb', \acl::CREATE);
                    $type = rcvint('type');
                    $text = $db->real_escape_string($_REQUEST['text']);
                    $uid = (int)$_SESSION['uid'];
                    $res = $db->query("INSERT INTO `intkb` (`type`, `name`,`autor`,`date`,`text`)
					VALUES ('$type', '$p','$uid', NOW(), '$text')");
                    header("Location: intkb.php?p=" . $p);
                    exit();
                }
            }
        }
    }
} catch (mysqli_sql_exception $e) {
    $db->rollback();
    $tmpl->ajax = 0;
    $id = writeLogException($e);
    $tmpl->msg("Порядковый номер ошибки: $id<br>Сообщение передано администратору", 'err', "Ошибка в базе данных");
} catch (Exception $e) {
    $db->query("ROLLBACK");
    $tmpl->addContent("<br><br>");
    writeLogException($e);
    $tmpl->errorMessage($e->getMessage());
}

$tmpl->write();
