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
    protected $files_fn = ''; /// имя таблицы базы данных с списком прикреплённых файлов
    
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
        $this->ExecMode(request('sect'));
    } 
    
    public function execMode($sect='') {
        global $tmpl;
        \acl::accessGuard($this->acl_object_name, \acl::VIEW);
        $tmpl->addBreadcrumb($this->getName(), $this->link_prefix);
        switch($sect) {
            case '':
                if($this->page_name == '' ) {
                    $this->viewIndexPage();
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
            case 'fileinfo':
                $file_id = rcvint('file_id');
                $this->viewFileInfo($file_id);
                break;
            case 'attachfile':
                $this->attachFile();
                break;
            case 'savefile':
                $file_id = rcvint('file_id');
                $this->saveFile($file_id);
                break;
            case 'getfile':
                $file_id = rcvint('file_id');
                $this->getFile($file_id);
                break;
            case 'removefile':
                $file_id = rcvint('file_id');
                $this->removeFile($file_id);
                break;
            default:
                throw new \NotFoundException('Страница с таким параметром не найдена');
        }
    }
    
    protected function attachFile() {
        global $tmpl;
        \acl::accessGuard($this->acl_object_name, \acl::UPDATE);
        $wikiparser = new \WikiParser();
        $wikiparser->reference_wiki = $this->link_prefix;  
        $ptitle = html_out($this->page_name);
        $title = "Прикрепление файла к: ".$ptitle;
        $tmpl->addContent("<h1>$title</h1>");
        $tmpl->addBreadcrumb($ptitle, $wikiparser->constructWikiLink($this->page_name));
        $tmpl->addBreadcrumb("Прикрепление файла", '');
        $tmpl->setTitle(strip_tags($title));
        $tmpl->addContent( $this->getAttachFileForm($this->page_name));
    }
    
    protected function saveFile($file_id) {
        global $tmpl;
        \acl::accessGuard($this->acl_object_name, \acl::UPDATE);
        $wikiparser = new \WikiParser();
        $wikiparser->reference_wiki = $this->link_prefix;  
        $ptitle = html_out($this->page_name);
        $title = "Прикрепление файла к: ".$ptitle;
        $tmpl->addContent("<h1>$title</h1>");
        $tmpl->addBreadcrumb($ptitle, $wikiparser->constructWikiLink($this->page_name));
        $tmpl->addBreadcrumb("Прикрепление файла", '');
        $tmpl->setTitle(strip_tags($title));
        if(!$file_id) {
            $att = new \attachments($this->files_fn);
            if($att->upload($_FILES['userfile'], $this->table_name.':'.$this->page_name)) {
                $tmpl->msg('Файл загружен');
                $this->viewPage();
            }
        }
    }
    
    protected function removeFile($file_id) {
        global $tmpl;
        \acl::accessGuard($this->acl_object_name, \acl::DELETE);
        $yes = rcvint('yes');
        if(!$yes) {
            $title = "Удаление файла";
            $tmpl->addContent("<h1>$title</h1>");
            $wikiparser = new \WikiParser();
            $wikiparser->reference_wiki = $this->link_prefix;  
            $tmpl->addBreadcrumb($this->page_name, $wikiparser->constructWikiLink($this->page_name));
            $tmpl->addBreadcrumb($title, '');
            $tmpl->addContent( $this->getRemoveFileForm($this->page_name, $file_id));
        }
        else {
            $att = new \attachments($this->files_fn);
            if($att->remove($file_id)) {
                $tmpl->msg('Файл удалён');
                $this->viewPage();
            }
            else {
                $tmpl->errormessage('Не удалось удалить файл');
                $this->viewPage();
            }
        }
    }
    
    protected function getFile($file_id) {
        $att = new \attachments($this->files_fn);
        $att->download($file_id);
    }

    /// отображение интексной страницы
    protected function viewIndexPage() {
        $page_names = ['index', 'site:index', 'kb:index'];
        foreach ($page_names as $page_name) {
            $p_info = $this->getPageData($page_name);
            if($p_info) {
                $this->page_name = $page_name;
                $this->viewPage();
                return;
            }
        }
        $this->viewList();
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
    
    protected function getAttachedFilesBlock($page_name) {        
        $att = new \attachments($this->files_fn);
        $files = $att->getFilesList($this->table_name.':'.$page_name);
        if(!count($files)) {
            return '';
        }
        $wikiparser = new \WikiParser();
        $wikiparser->reference_wiki = $this->link_prefix;
        $ret = "<div class='attachments'><h2>Прикреплённые файлы</h2><ul>";
        foreach($files as $id => $data) {
                $link = $wikiparser->constructWikiLink($page_name, 'sect=fileinfo&amp;file_id='.$id);
                $get_link = $wikiparser->constructWikiLink($page_name, 'sect=getfile&amp;file_id='.$id);
                $ret .="<li><a class='wiki' href='$link'>".html_out($data['original_filename'])."</a>";
                if($att->testExists($id)) {
                    $ret .=" <a href='$get_link'><img src='/img/16x16/download.png' alt='Download'></a>"; 
                }   
                else {
                    $ret .=" <img src='/img/16x16/error.png' alt='Not found'>"; 
                }
                $ret .= " (<span class='size' title='{$data['size']} bytes'>".\webcore::toStrDataSizeInaccurate($data['size'])."</title>) - ";
                $ret .=" добавил ".html_out($data['user_name']).", {$data['date']}."; 
                if($data['description']) {
                    $ret .= " &quot;".html_out($data['description'])."&quot;";
                }               
                $ret .="</li>";
        }
        $ret .="</ul></div>";
        return $ret;
    }
    
    protected function viewFileInfo($file_id) {
        global $db, $tmpl;
        settype($file_id, 'int');
        $att = new \attachments($this->files_fn);
        $data = $att->getFileInfo($file_id);
        if(!$data) {
            throw new \NotFoundException("Файл не найден!");
        }
        $wikiparser = new \WikiParser();
        $wikiparser->reference_wiki = $this->link_prefix;
        $title = "Информация о файле: ".$data['original_filename'];
        $tmpl->addBreadcrumb($this->page_name, $wikiparser->constructWikiLink($this->page_name));
        $tmpl->addContent("<h1 id='page-info'>$title</h1>");
        $tmpl->setTitle($title);
        $tmpl->addBreadcrumb($title, '');
        $tmpl->addContent("<ul>");
        $tmpl->addContent("<li><b>Размер:</b> ".\webcore::toStrDataSizeInaccurate($data['size'])." ({$data['size']} байт)</li>");
        $tmpl->addContent("<li><b>Загрузил:</b> ".  html_out($data['user_name'])."</li>");
        $tmpl->addContent("<li><b>Дата загрузки:</b> ".  html_out($data['date'])."</li>");
        $size = $att->getSize($file_id);
        if($size) {
            settype($size, 'int');
            $tmpl->addContent("<li><b>Фактический размер:</b> ".\webcore::toStrDataSizeInaccurate($size)." ({$data['size']} байт)</li>");
        }
        else {
            $tmpl->addContent("<li><b>Файл пуст или не доступен для чтения</b></li>");
        }
        if($data['description']) {
            $tmpl->addContent("<li><b>Описание:</b> ".html_out($data['description'])."</li>");
        }
        if($att->testExists($file_id)) {            
            $get_link = $wikiparser->constructWikiLink($this->page_name, 'sect=getfile&amp;file_id='.$file_id);
            $tmpl->addContent("<li><a href='$get_link'><img src='/img/16x16/download.png' alt='Download'>&nbsp;Загрузить</a></li>"); 
        } 
        else {
            $tmpl->addContent("<li><b>Внимание! Файл отсутствует в хранилище!</b></li>");
        }
        if (\acl::testAccess($this->acl_object_name, \acl::DELETE, true)) {
            $remove_link = $wikiparser->constructWikiLink($this->page_name, 'sect=removefile&amp;file_id='.$file_id);
            $tmpl->addContent("<li><a href='$remove_link'><img src='/img/i_del.png' alt='Remove'>&nbsp;Удалить</a></li>");
        }   
        $tmpl->addContent("</ul>");
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
    
    public function getEditLink($page_name) {
        return \webcore::concatLink($this->link_prefix, "p=" . html_out($page_name) . "&amp;sect=edit");
    }

        /** Отобразить страницу
     * 
     * @param type $p_info  Данные страницы
     * @param type $title   Отображаемый заголовок страницы
     * @param type $text    Отображаемый текст страницы (в HTML)
     */
    protected function showPage($p_info, $title, $text) {
        global $tmpl;
        $tmpl->addBreadCrumb($title, '');
        $tmpl->setTitle($title);
        $edit_link = $this->getEditLink($p_info['article_name']);
        $attach_link = \webcore::concatLink($this->link_prefix, "p=" . html_out($p_info['article_name']) . "&amp;sect=attachfile");
        $ch = $p_info['editor_name']?", последнее изменение - {$p_info['editor_name']}, date {$p_info['changed']}":'';
        if ($p_info['type'] == 0 || $p_info['type'] == 2) {
            $tmpl->addContent("<h1 id='page-title'>$title</h1>");
            if (@$_SESSION['uid']) {
                $tmpl->addContent("<div id='page-info'>Создал: {$p_info['author_name']}, date: {$p_info['date']} $ch");
                if (\acl::testAccess($this->acl_object_name, \acl::UPDATE, true)) {
                    $tmpl->addContent(", <a href='$edit_link'>Редактировать</a>");
                }
                $tmpl->addContent("</div>");
            }
        }
        else if (\acl::testAccess($this->acl_object_name, \acl::UPDATE, true)) {
            $tmpl->addContent("<div id='page-info'>Создал: {$p_info['author_name']}, date: {$p_info['date']} $ch");
            $tmpl->addContent(", <a href='$edit_link'>Исправить</a>");
            $tmpl->addContent("</div>");
        }
        $tmpl->addContent("<div class='article_text'>$text</div>"); 
        $tmpl->addContent($this->getAttachedFilesBlock($p_info['article_name'])); 
        if (\acl::testAccess($this->acl_object_name, \acl::UPDATE, true)) {
            $tmpl->addContent("<div class='article_text'>");
            $tmpl->addContent("<a href='$edit_link'>Редактировать статью</a> | <a href='$attach_link'>Прикрепить файл</a>");
            $tmpl->addContent("</div>");
        }
        
    }
    
    /// Отобразить список статей
    protected function viewList() {
        global $tmpl, $db;
        $tmpl->addBreadcrumb($this->getName(), '');
        $res = $db->query("SELECT `name`, `text` FROM `{$this->table_name}` ORDER BY `name`");
        if ($res->num_rows) {
            $wikiparser = new \WikiParser();
            $wikiparser->reference_wiki = $this->link_prefix;
            $tmpl->addContent("<ul class='items'>");
            while ($nxt = $res->fetch_row()) {
                $text = $wikiparser->parse($nxt[1]);
                $title = $wikiparser->getTitle();
                if($title) {
                    $title .= ' / ' . $wikiparser->unwiki_link($nxt[0]);
                }
                else {
                    $title = $wikiparser->unwiki_link($nxt[0]);
                }
                $link = $wikiparser->constructWikiLink($nxt[0]);
                $tmpl->addContent("<li><a class='wiki' href='$link'>$title</a></li>");
            }
            $tmpl->addContent("</ul>");
        } else {
            $tmpl->msg("Здесь пока нет ни одной статьи", "notify");
        }
        if (\acl::testAccess($this->acl_object_name, \acl::UPDATE, true) | \acl::testAccess($this->acl_object_name, \acl::CREATE, true)) {
            $tmpl->addContent("<form action='{$this->link_prefix}' method='get'>"
                . "<input type='hidden' name='sect' value='edit'><fieldset><legend>Создать/править статью</legend>"
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
            <input type='hidden' name='sect' value='save'>
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
            <textarea class='wikieditor big' name='text' rows='12' cols='80'>$article_text</textarea><br>
            <button type='submit'>Сохранить</button>
            </form>
            <script type='text/javascript'>tinymce_toggle('select_type', 'tme');</script>
            <br><a href='/wikiphoto.php'>Галерея изображений</a><br>";
        //$ret .= $this->getAttachFileForm($page_name);
        $ret .= $this->getExamples();
        return $ret;
    }
    
    protected function getRemoveFileForm($page_name, $file_id) {
        global $tmpl;
        settype($file_id, 'int');
        $ret = '';
        $ret .= "
            <form action='{$this->link_prefix}' method='post'>
            <input type='hidden' name='sect' value='removefile'>
            <input type='hidden' name='yes' value='1'>
            <input type='hidden' name='file_id' value='$file_id'>
            <input type='hidden' name='p' value='" . html_out($page_name) . "'>
            <button type='submit'>Подтверждаю удаление файла ID:$file_id</button>
            </form>";
        return $ret;
    }
    
    protected function getAttachFileForm($page_name, $file_id=0, $description='') {
        global $tmpl;
        $max_fs = \webcore::getMaxUploadFileSize();
        $max_fs_size = \webcore::toStrDataSizeInaccurate($max_fs);
        $ret = '';
        $req = $file_id?' required':'';
        $ret .= "
            <form action='{$this->link_prefix}' method='post' enctype='multipart/form-data'>
            <input type='hidden' name='sect' value='savefile'>
            <input type='hidden' name='p' value='" . html_out($page_name) . "'>
            <table cellpadding='0' class='list'>
            <tr><td><b style='color:#f00;'>*</b>Выберите файл:</td>
                <td><input type='hidden' name='MAX_FILE_SIZE' value='$max_fs'>
                    <input name='userfile' type='file'{$req} placeholder='Выберите файл'>
                    <br><small>Не более $max_fs_size</small></td></tr>
            <tr><td><b style='color:#f00;'>*</b>Описание файла (до 128 символов)</td>
                <td><input type='text' name='description' placeholder='Вложение' maxlength='128' required value='".html_out($description)."'>
            <tr><td colspan='2' align='center'>
            <input type='submit' value='Сохранить'>
            </table>
            </form>";
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
        $ret = "<h3>Примеры wiki разметки</h3><table width='100%' class='list'><tr><th>Что писать</th><th>Что получится</th></tr>";
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
