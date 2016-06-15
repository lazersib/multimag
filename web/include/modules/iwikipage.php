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

namespace modules;

/// Страница со статьёй в wiki формате
abstract class IWikiPage extends \IModule {
    protected $page_name = '';  /// Имя записи текущей страницы
    protected $table_name = ''; /// имя таблицы базы данных со статьями
    
    public function __construct() {
        parent::__construct();
        $this->acl_object_name = 'unknown.articles';    // Заведомо несуществующий объект
        $this->table_name = 'articles';
    }
    
    /**
     * Задать имя записи текущей страницы
     * @param type $page_name Имя записи текущей страницы
     */
    public function setPageName($page_name) {
        $this->page_name = $page_name;
    }


    public function run() {
        $this->ExecMode(request('mode'));
    } 
    
    public function execMode($mode='') {
        switch($mode) {
            case '':
                if($this->page_name == '' ) {
                    $this->viewList();
                }
                else {
                   $this->viewPage(); 
                }
                break;
            case 'view':
                $this->viewPage();
                break;
            case 'edit':
                $this->editPage();
                break;
            case 'save':
                $this->savePage();
                break;
            case 'list':
                $this->viewList();
                break;
            default:
                throw new \NotFoundException('Страница с таким параметром не найдена');
        }
    }
    
    
    /// Редактирование текущей страницы
    protected function editPage() {
        global $tmpl;
        $p_info = $this->getPageData($this->page_name);
        $title = html_out($this->page_name);
        if($p_info) {
            \acl::accessGuard($this->acl_object_name, \acl::UPDATE);  
            $tmpl->addContent("<h1>Правим статью: $title</h1>");
            $tmpl->addContent($this->getEditForm($this->page_name, $p_info['text'], $p_info['type']));
        }
        else {
            \acl::accessGuard($this->acl_object_name, \acl::CREATE);  
            $tmpl->addContent("<h1>Создаём статью: $title</h1>");
            $tmpl->addContent($this->getEditForm($this->page_name));
        }
    }
    
    
    /// Сохранить страницу
    protected function savePage() {
        global $db, $tmpl;        
        $type = rcvint('type');
        if ($type < 0 || $type > 2) {
            $type = 0;
        }
        $text = $db->real_escape_string(@$_REQUEST['text']);
        $uid = intval($_SESSION['uid']);
        $pn_escaped = $db->real_escape_string($this->page_name);
        $p_info = $this->getPageData($this->page_name);
        if($p_info) {
            \acl::accessGuard($this->acl_object_name, \acl::UPDATE);
            $db->query("UPDATE `{$this->table_name}` SET `changeautor`='$uid', `changed`=NOW() ,`text`='$text', `type`='$type'
            WHERE `name` = '$pn_escaped'");
            $tmpl->msg("Страница отредактирована успешно", "ok");
        }
        else {
            \acl::accessGuard($this->acl_object_name, \acl::CREATE);
            $db->query("INSERT INTO `{$this->table_name}` (`type`, `name`,`autor`,`date`,`text`)
                VALUES ('$type', '$pn_escaped','$uid', NOW(), '$text')");
            $tmpl->msg("Страница создана успешно", "ok");
        }
        $this->viewPage();
    }

    /// Просмотр текущей страницы
    protected function viewPage() {
        global $tmpl;
        $p_info = $this->getPageData($this->page_name);
        if(!$p_info) {
            throw new \NotFoundException('Статья не найдена');
        }
        $title = $meta_description = $meta_keywords = '';
        $text = $p_info['text'];
        if ($p_info['type'] == 0) {
            $text = strip_tags($text, '<nowiki>');
        }
        if ($p_info['type'] == 0 || $p_info['type'] == 2) {
            $wikiparser = new \WikiParser();
            $wikiparser->reference_wiki = $this->link_prefix;
            $text = $wikiparser->parse($text);
            $title = $wikiparser->title;
            if(isset($wikiparser->definitions['meta_description'])) {
                $meta_description = $wikiparser->definitions['meta_description'];  
            }
            if(isset($wikiparser->definitions['meta_keywords'])) {
                $meta_keywords = $wikiparser->definitions['meta_keywords'];  
            }
        }

        if (!$title) {
            $title = explode(":", $this->page_name, 2);
            if (isset($title[1]) && $title[1]) {
                $title = $wikiparser->unwiki_link($title[1]);
            } else {
                $title = html_out($wikiparser->unwiki_link($this->page_name));
            }
        }
        
        $tmpl->setTitle(strip_tags($title));
        $tmpl->setMetaKeywords($meta_keywords);
        $tmpl->setMetaDescription($meta_description);
        $this->showPage($p_info, $title, $text);
    }
    
    /** Отобразить страницу
     * 
     * @param type $p_info  Данные страницы
     * @param type $title   Отображаемый заголовок страницы
     * @param type $text    Отображаемый текст страницы (в HTML)
     */
    protected function showPage($p_info, $title, $text) {
        global $tmpl;
        $ch = $p_info['editor_name']?", последнее изменение - {$p_info['editor_name']}, date {$p_info['changed']}":'';
        if ($p_info['type'] == 0 || $p_info['type'] == 2) {
            $tmpl->addContent("<h1 id='page-title'>$title</h1>");
            if (@$_SESSION['uid']) {
                $tmpl->addContent("<div id='page-info'>Создал: {$p_info['author_name']}, date: {$p_info['date']} $ch");
                if (\acl::testAccess($this->acl_object_name, \acl::UPDATE)) {
                    $tmpl->addContent(", <a href='{$this->link_prefix}" . html_out($p_info['article_name']) . ".html?mode=edit'>Исправить</a>");
                }
                $tmpl->addContent("</div>");
            }
        }
        else if (\acl::testAccess($this->acl_object_name, \acl::UPDATE)) {
            $tmpl->addContent("<div id='page-info'>Создал: {$p_info['author_name']}, date: {$p_info['date']} $ch");
            $tmpl->addContent(", <a href='{$this->link_prefix}" . html_out($p_info['article_name']) . "?mode=edit'>Исправить</a>");
            $tmpl->addContent("</div>");
        }
        $tmpl->addContent("<div class='article_text'>$text</div>");        
    }
    
    /// Отобразить список статей
    protected function viewList() {
        global $tmpl, $db;
        $res = $db->query("SELECT `name`, `text` FROM `{$this->table_name}` ORDER BY `name`");
        if ($res->num_rows) {
            $wikiparser = new \WikiParser();
            $wikiparser->reference_wiki = $this->link_prefix;
            $tmpl->addContent("<ul class='items'>");
            while ($nxt = $res->fetch_row()) {
                $wikiparser->title = '';
                $text = $wikiparser->parse($nxt[1]);
                $h = $wikiparser->title . ' ( ' . $wikiparser->unwiki_link($nxt[0]) . ' )';
                $tmpl->addContent("<li><a class='wiki' href='{$this->link_prefix}".html_out($nxt[0]).".html'>$h</a></li>");
            }
            $tmpl->addContent("</ul>");
        } else {
            $tmpl->msg("Здесь пока нет ни одной статьи", "notify");
        }
        if (\acl::testAccess($this->acl_object_name, \acl::UPDATE) | \acl::testAccess($this->acl_object_name, \acl::CREATE)) {
            $tmpl->addContent("<form action='{$this->link_prefix}' method='get'>"
                . "<input type='hidden' name='mode' value='edit'><fieldset><legend>Создать/править статью</legend>"
                . "Имя статьи:<br><input type='text' name='p'><br><button type='submit'>Далее</button></form></fieldset>");
        }
    }

    /** Получить форму редактирования статьи
     * 
     * @param string $page_name     Имя страницы статьи
     * @param string $article_text  Текст статьи
     * @param int $markup_type      Тип разметки статьи
     * @return string               HTML код формы статьи
     */
    protected function getEditForm($page_name, $article_text = '', $markup_type = 0) {
        global $tmpl;
        $ret = '';
        $html_enable = \cfg::get('wiki', 'html_enable', true);
        $mce_enable = \cfg::get('wiki', 'tinymce_enable', true);
        $types = array(0 => 'Wiki (Простая и безопасная разметка, рекомендуется)', 1 => 'HTML (Для профессионалов. Может быть небезопасно.)', 2 => 'Wiki+HTML');
        $pref = \pref::getInstance();
        if($html_enable) {
            if($mce_enable) {
                $ret .= "<script type='text/javascript' src='/js/tiny_mce/tiny_mce.js'></script>";
            }
            $ret .= "<script type='text/javascript' src='/js/modules/wikipage.js'></script>";
        }
        
        $ret .= "
            <form action='{$this->link_prefix}' method='post'>
            <input type='hidden' name='mode' value='save'>
            <input type='hidden' name='p' value='" . html_out($page_name) . "'>";
            if($html_enable) {
                $ret .= "Тип разметки:<br>
                <select name='type' id='select_type' onchange=\"tinymce_toggle('select_type', 'tme')\">";
                foreach ($types AS $id => $name) {
                    $s = ($id == $markup_type) ? 'selected' : '';
                    $ret .= "<option value='$id'{$s}>$name</option>";
                }
                $ret .= "</select><br>";
                if($mce_enable) {
                    $ret .= "<label><input type='checkbox' id='tme' onclick=\"tinymce_toggle('select_type', 'tme')\">Визуальный редактор</label><br>";
                }
            }
            else {
                $ret .= "<input type='hidden' name='type' value='0'>";
            }
            
        $article_text = html_out($article_text);
        $ret .= "
            <textarea class='wikieditor' name='text' rows='12' cols='80'>$article_text</textarea><br>
            <button type='submit'>Сохранить</button>
            </form>
            <script type='text/javascript'>tinymce_toggle('select_type', 'tme');</script>
            <br><a href='/wikiphoto.php'>Галерея изображений</a><br>";
        $ret .= $this->getExamples();
        return $ret;
    }
    
    /** Получить данные старницы
     * 
     * @param type $page_name   Имя статьи
     * @return type             Ассоциативный массив с данными статьи
     */
    protected function getPageData($page_name) {
        global $db;
        $pn_escaped = $db->real_escape_string($page_name);
        $at = $this->table_name;
        $res = $db->query("SELECT `$at`.`name` AS `article_name`, `$at`.`date`, `$at`.`changed`, `$at`.`text`, `$at`.`type`
            , `users_a`.`name` AS `author_name`
            , `users_c`.`name` AS `editor_name`
        FROM `$at`
        LEFT JOIN `users` AS `users_a` ON `users_a`.`id`=`$at`.`autor`
        LEFT JOIN `users` AS `users_c` ON `users_c`.`id`=`$at`.`changeautor`
        WHERE `$at`.`name` = '$pn_escaped'");
        if(!$res->num_rows) {
            return null;
        }
        return $res->fetch_assoc();
    }
    
    /** Получить примеры wiki разметки
     * 
     * @return string   HTML код с примерами
     */
    protected function getExamples() {
        $examples = array(
            '(:title Название статьи:)' => 'Указать название статьи.',
            '(:meta_keywords значение:)' => 'Задать значение meta тэга keywords для статьи.',
            '(:meta_description значение:)' => 'Задать значение meta тэга description для статьи.',
            '=Раздел=' => '',
            '==Подраздел 1==' => '',
            '===Подраздел 2===' => '',
            '====Подраздел 3====' => '',
            '----' => 'Горизонтальная линия',
            "'''Жирный текст'''" => '',
            "''Курсив''" => '',
            "'''''Жирный курсив'''''" => '',
            '{{CURRENTDAY}}/{{CURRENTMONTH}}/{{CURRENTYEAR}}' => 'Отобразить значения переменных',
            "[[Image:1]]" => 'Вставить изображение. Подробнее <a href="/wikiphoto.php">тут</a>',
            "[[namespace:link target|Текст ссылки]], 
[[site:vitrina.php|Ссылка на витрину]], 
[[contactinfo]]" => 'Виды внутренних ссылок',
            "[http://tndproject.org], [http://multimag.tndproject.org], 
[http://multimag.tndproject.org Торговая система мультимаг]" => 'Виды внешних ссылок',
            " Totally preformatted 01234    o o
 Again, this is preformatted    b    <-- It's a face
 Again, this is preformatted   ---'" => 'Предварительно отформатированный, моноширный текст',
            "* One bullet
* Another '''bullet'''
*# a list item
*# another list item
*#* unordered, ordered, unordered
*#* again
*# back down one"   => 'Списки',
            "Normal
: indented woo
: more indentation
Done." => "Отступы",
            "; yes : opposite of no
; no : opposite of yes
; maybe
: somewhere in between yes and no
Done."  => "Список терминов",
            "{{WIDGET:CBOX:E82:20}}, {{WIDGET:PRODUCTINFO:1}}, {{WIDGET:PRICEINFO:bv}} " => 'Различные виджеты (неполный список)'
            //
        );        
        $ret = "<h3>Примеры wiki разметки</h3><table width='100%'><tr><th>Что писать</th><th>Что получится</th></tr>";
        foreach($examples as $wiki => $desc) {
            $wikiparser = new \WikiParser();
            $wikiparser->reference_wiki = $this->link_prefix;
            $text = $wikiparser->parse($wiki);
            if($desc) {
                $ret .= '<tr><td colspan=2>'.$desc.'</td></tr>';
            }
            $ret .= "<tr><td><pre>$wiki</pre></td><td>$text</td></tr>";
        }        
        $ret .= "</table>";
        return $ret;
    }
}
