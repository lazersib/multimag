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

try {

    if (file_exists($CONFIG['site']['location'] . '/skins/' . $CONFIG['site']['skin'] . '/index.tpl.php')) {
        include_once($CONFIG['site']['location'] . '/skins/' . $CONFIG['site']['skin'] . '/index.tpl.php');
    } 
    else {
        require_once("include/doc.core.php");
        require_once("include/comments.inc.php");
        $pref = \pref::getInstance();
        $tmpl->setTitle($pref->site_display_name);

        $pc = PriceCalc::getInstance();
        $pc->setFirmId($pref->site_default_firm_id);
        $wikiparser = new WikiParser();

        $res = $db->query("SELECT `news`.`id`, `news`.`text`, `news`.`date`, `news`.`ex_date`, `news`.`img_ext` FROM `news` LIMIT 1");
        if ($res->num_rows > 0) {
            $res->free();
            $tmpl->addContent("<table class='index-nsr'><tr>");

            $res = $db->query("SELECT `news`.`id`, `news`.`text`, `news`.`date`, `news`.`ex_date`, `news`.`img_ext` FROM `news`
		WHERE `news`.`type`='stock'
		ORDER BY `date` DESC LIMIT 3");
            if ($res->num_rows > 0) {
                $tmpl->addContent("<td><h3>Акции</h3>");
                while ($line = $res->fetch_assoc()) {
                    $wikiparser->title = '';
                    $text = $wikiparser->parse($line['text']);
                    if ($line['img_ext']) {
                        $miniimg = new ImageProductor($line['id'], 'n', $line['img_ext']);
                        $miniimg->SetX(50);
                        $miniimg->SetY(50);
                        $img = "<img src='" . $miniimg->GetURI() . "' alt=''>";
                    } else {
                        $img = '';
                    }
                    $text_a = mb_split("[.!?]", strip_tags($text), 2);
                    if (@$text_a) {
                        $text = $text_a[0] . "...";
                    }
                    $tmpl->addContent("<div class='news'><div class='image'><a href='/news.php?mode=read&amp;id={$line['id']}'>$img</a></div>
                        <div class='text'><p class='date'>{$line['date']}</p><p class='title'><a href='/news.php?mode=read&amp;id={$line['id']}'>{$wikiparser->title}</a></p><p>$text</p></div>
                        <div class='clear'></div>
                        </div>");
                }
            }
            $res->free();


            $res = $db->query("SELECT `name`, `date`, `text`, `img_ext`  FROM `articles`
		WHERE `name` LIKE 'review:%'
		ORDER BY `date` DESC LIMIT 3");
            if ($res->num_rows > 0) {
                $tmpl->addContent("<td><h3>Обзоры</h3>");
                while ($line = $res->fetch_assoc()) {
                    $wikiparser->title = '';
                    $text = $wikiparser->parse($line['text']);
                    if ($line['img_ext']) {
                        $miniimg = new ImageProductor($line['name'], 'a', $line['img_ext']);
                        $miniimg->SetX(50);
                        $miniimg->SetY(50);
                        $img = "<img src='" . $miniimg->GetURI() . "' alt=''>";
                    } else {
                        $img = '';
                    }
                    $text_a = mb_split("[.!?]", strip_tags($text), 2);
                    if (@$text_a) {
                        $text = $text_a[0] . "...";
                    }
                    $tmpl->addContent("<div class='news'><div class='image'><a href='/wiki/{$line['name']}'>$img</a></div>
                        <div class='text'><p class='date'>{$line['date']}</p><p class='title'><a href='/wiki/{$line['name']}'>{$wikiparser->title}</a></p><p>$text</p></div>
                        <div class='clear'></div>
                        </div>");
                }
            }
            $res->free();


            $res = $db->query("SELECT `news`.`id`, `news`.`text`, `news`.`date`, `news`.`ex_date`, `news`.`img_ext` FROM `news`
		WHERE `news`.`type`='novelty'
		ORDER BY `date` DESC LIMIT 3");
            if ($res->num_rows > 0) {
                $tmpl->addContent("<td><h3><a href='/news.php'>Новости</a></h3>");
                while ($line = $res->fetch_assoc()) {
                    $wikiparser->title = '';
                    $text = $wikiparser->parse($line['text']);
                    if ($line['img_ext']) {
                        $miniimg = new ImageProductor($line['id'], 'n', $line['img_ext']);
                        $miniimg->SetX(50);
                        $miniimg->SetY(50);
                        $img = "<img src='" . $miniimg->GetURI() . "' alt=''>";
                    } else {
                        $img = '';
                    }
                    $text_a = mb_split("[.!?]", strip_tags($text), 2);
                    if (@$text_a) {
                        $text = $text_a[0] . "...";
                    }
                    $tmpl->addContent("<div class='news'><div class='image'><a href='/news.php?mode=read&amp;id={$line['id']}'>$img</a></div>
                        <div class='text'><p class='date'>{$line['date']}</p><p class='title'><a href='/news.php?mode=read&amp;id={$line['id']}'>{$wikiparser->title}</a></p><p>$text</p></div>
                        <div class='clear'></div>
                        </div>");
                }
            }
            $res->free();
            $tmpl->addContent("</tr></table>");
        }


        $res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`desc`, `doc_base`.`cost`, `doc_img`.`id` AS `img_id`, `doc_img`.`type` AS `img_type`, `class_unit`.`rus_name1` AS `units`, `doc_group`.`printname` AS `group_name` FROM `doc_base`
	LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id` AND `doc_base_img`.`default`='1'
	LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
	LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
	LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
	WHERE `hidden`='0' AND `stock`!='0' LIMIT 12");
        if ($res->num_rows > 0) {
            $tmpl->addContent("<h1>Спецпредложения</h1>
		<div class='sales'>");

            while ($line = $res->fetch_assoc()) {
                /// TODO: надо бы из класса витрины брать данные
                if ($CONFIG['site']['rewrite_enable']) {
                    $link = "/vitrina/ip/{$line['id']}.html";
                } else {
                    $link = "/vitrina.php?mode=product&amp;p={$line['id']}";
                }
                if ($line['img_id']) {
                    $miniimg = new ImageProductor($line['img_id'], 'p', $line['img_type']);
                    $miniimg->SetX(135);
                    $miniimg->SetY(180);
                    $img = "<img src='" . $miniimg->GetURI() . "' style='float: left; margin-right: 10px;' alt='{$line['name']}'>";
                } else {
                    $img = "<img src='/img/no_photo.png' alt='no photo' style='float: left; margin-right: 10px;'>";
                }

                $cost = $pc->getPosDefaultPriceValue($line['id']);
                if ($cost <= 0) {
                    $cost = 'уточняйте';
                }
                $html_name = html_out($line['group_name'] . ' ' . $line['name']);

                $tmpl->addContent("<div class='pitem'>
                    <a href='$link'>$img</a>
                    <h2><a href='$link'>$html_name</a></h2>
                    <b>Цена:</b> $cost руб / {$line['units']}<br>
                    <a rel='nofollow' href='/vitrina.php?mode=korz_add&amp;p={$line['id']}&amp;cnt=1' onclick=\"return ShowPopupWin('/vitrina.php?mode=korz_add&amp;p={$line['id']}&amp;cnt=1','popwin');\" rel='nofollow'>В корзину!</a>
                    </div>");
            }
            $tmpl->addContent("<div class='clear'><br></div>
		</div>");
        }
        $res->free();

        $tmpl->addContent("<h1>Популярные товары</h1>");

        $res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`desc`, `doc_base`.`cost`, `doc_img`.`id` AS `img_id`, `doc_base`.`likvid`, `doc_img`.`type` AS `img_type`, ( SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base`.`id`) AS `count`, `class_unit`.`rus_name1` AS `units`, `doc_group`.`printname` AS `group_name` FROM `doc_base`
	INNER JOIN `doc_group` ON `doc_group`.`id`= `doc_base`.`group` AND `doc_group`.`hidelevel`='0'
	LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id` AND `doc_base_img`.`default`='1'
	LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
	LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
	WHERE `hidden`='0'
	ORDER BY `likvid` DESC
	LIMIT 12");
        $i = 1;
        while ($line = $res->fetch_assoc()) {
            if ($line['cost'] == 0) {
                continue;
            }
            /// TODO: тут тоже надо бы из класса витрины брать данные
            if ($CONFIG['site']['rewrite_enable']) {
                $link = "/vitrina/ip/{$line['id']}.html";
            } else {
                $link = "/vitrina.php?mode=product&amp;p={$line['id']}";
            }
            if ($line['img_id']) {
                $miniimg = new ImageProductor($line['img_id'], 'p', $line['img_type']);
                $miniimg->SetX(135);
                $miniimg->SetY(180);
                $img = "<img src='" . $miniimg->GetURI() . "' style='float: left; margin-right: 10px;' alt='{$line['name']}'>";
            } else {
                $img = "<img src='/img/no_photo.png' alt='no photo'  style='float: left; margin-right: 10px;'>";
            }
            $cost = $pc->getPosDefaultPriceValue($line['id']);
            if ($cost <= 0) {
                $cost = 'уточняйте';
            }
            $html_name = html_out($line['group_name'] . ' ' . $line['name']);
            $tmpl->addContent("<div class='pitem'>
		<a href='$link'>$img</a>
		<h2>$html_name</h2>
		<b>Цена:</b> $cost руб / {$line['units']}<br>
		<a rel='nofollow' href='/vitrina.php?mode=korz_add&amp;p={$line['id']}&amp;cnt=1' onclick=\"return ShowPopupWin('/vitrina.php?mode=korz_add&amp;p={$line['id']}&amp;cnt=1','popwin');\" rel='nofollow'>В корзину!</a>
		</div>");

            $i++;
        }
        $res->free();
        $tmpl->addContent("<div class='clear'><br></div>");
    }
} catch (Exception $e) {
    $tmpl->addContent("<br><br>");
    writeLogException($e);
    $tmpl->errorMessage($e->getMessage());
}

$tmpl->write();



