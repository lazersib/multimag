<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2017, BlackLight, TND Team, http://tndproject.org
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

namespace Widgets;

class ProductInfo extends \IWidget {

    protected $pos_id;      //< ID товарного наименования

    public function getName() {
        return 'Простой товарный виджет';
    }

    public function getDescription() {
        return 'Позволяет вставить в текст мини-карточку товара (плитка с изображением и названием товара, со ссылкой на страницу товара).'
            . ' Параметр - id товара.';
    }

    public function setParams($param_str) {
        $this->pos_id = intval($param_str);
        return $this->pos_id;
    }

    public function getHTML() {
        global $db;
        $res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`vc`, `doc_base`.`eol`, `doc_base`.`proizv`
                , `doc_img`.`id` AS `img_id`, `doc_img`.`type` AS `img_type`
                , `class_unit`.`rus_name1` AS `units`
                , `doc_group`.`printname`
	    FROM `doc_base`
            LEFT JOIN `doc_group` ON `doc_base`.`group`=`doc_group`.`id`
            LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
	    LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id` AND `doc_base_img`.`default`='1'
	    LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
	    WHERE `doc_base`.`id`={$this->pos_id}");
        if (!$res->num_rows) {
            return '<i>{{Widget PRODUCTINFO: product not found!}}</i>';
        }
        $pc = \PriceCalc::getInstance(); 
        $product_data = $res->fetch_assoc();
        $price = $pc->getPosDefaultPriceValue($product_data['id']);
        if ($price <= 0) {
            $price = 'уточняйте';
        }

        if (\cfg::get('site', 'rewrite_enable')) {
            $link = "/vitrina/ip/{$this->pos_id}.html";
        } else {
            $link = "/vitrina.php?mode=product&amp;p={$this->pos_id}";
        }

        if ($product_data['img_id']) {
            $miniimg = new \ImageProductor($product_data['img_id'], 'p', $product_data['img_type']);
            $miniimg->SetX(135);
            $miniimg->SetY(180);
            $img = "<img src='" . $miniimg->GetURI() . "' style='float: left; margin-right: 10px;' alt='" . html_out($product_data['name']) . "'>";
        } else {
            if (file_exists(\cfg::get('site', 'location') . '/skins/' . \cfg::get('site', 'skin') . '/no_photo.png')) {
                $img_url = '/skins/' . \cfg::get('site', 'skin') . '/no_photo.png';
            } else {
                $img_url = '/img/no_photo.png';
            }
            $img = "<img src='$img_url' alt='no photo' style='float: left; margin-right: 10px; width: 135px;' alt='no photo'>";
        }
        $name = $product_data['printname']?$product_data['printname'].' '.$product_data['name']:$product_data['name'];
        $code = $product_data['vc']?"<b>Код:</b> " . html_out($product_data['vc']) . "<br>":'';
        $eol = $product_data['eol']?"<b>Товар снят с поставки</b><br>":'';
        $buy = (!$product_data['eol'] || $product_data['count']>0)?"<a rel='nofollow' href='/vitrina.php?mode=korz_add&amp;p={$product_data['id']}&amp;cnt=1'"
                    . " onclick=\"return ShowPopupWin('/vitrina.php?mode=korz_add&amp;p={$product_data['id']}&amp;cnt=1','popwin');\" rel='nofollow'>В корзину!</a>":'';
        return "<div class='pitem'>
		<a href='$link'>$img</a>
                <div class='info'>
		<a href='$link'>" . html_out($name) . "</a><br>
		$code
		<b>Цена:</b> <span>$price руб.</span> / {$product_data['units']}<br>
		<b>Производитель:</b> " . html_out($product_data['proizv']) . "<br>
		$eol<br>$buy</div></div>";
        
        //return "<div class='pitem'><a href='$link'>$img</a><a href='$link'>" . html_out($product_data['name']) . "</a><br>"
        //    . "<b>Код:</b> " . html_out($product_data['vc']) . "</div>";
    }

}
