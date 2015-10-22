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

namespace Modules\Site;

/// Класс новостного модуля. Формирует ленты новостей. Предоставляет средства для добавления новостей и рассылки уведомлений.
class News extends \IModule {

    public function __construct() {
        parent::__construct();
        $this->acl_object_name = 'generic.news';
    }

    // Получить название модуля
    /// @return Строка с именем
    public function getName() {
        return 'Новостная лента';
    }
    
    /// Получить описание модуля
    /// @return Строка с описанием
    public function getDescription() {
        return 'Просмотр, написание, и рассылка новостей';  
    }
    
    /// Запустить модуль на исполнение
    public function run() {
        global $tmpl;
        $tmpl->setTitle("Новости");
        if(!$this->ProbeRecode()) {
            $this->ExecMode(request('mode'));
        }
    }

    /// Проверка и исполнение recode-запроса
    public function ProbeRecode() {
        global $tmpl;
        /// Обрабатывает запросы-ссылки  вида http://example.com/news/news.html
        /// Возвращает false в случае неудачи.
        $arr = explode('/', $_SERVER['REQUEST_URI']);
        if (!is_array($arr))
            return false;
        if (count($arr) < 3)
            return false;
        $mode = @explode('.', $arr[2]);
        $query = @explode('.', $arr[3]);
        if (is_array($mode))
            $mode = $mode[0];
        else
            $mode = $arr[2];
        if (is_array($query))
            $query = $query[0];
        else
            $query = $arr[3];
        if ($mode == 'read') {
            if ($this->isAllow())
                $this->View($query);
            return true;
        }
        else if ($mode == 'all' || $mode == 'news' || $mode == 'stocks' || $mode == 'events') {
            if (\acl::testAccess($this->acl_object_name, \acl::CREATE, 1)) {
                $tmpl->addContent("<a href='{$this->link_prefix}&amp;mode=add&amp;opt=$mode'>Добавить новость</a><br>");
            }
            if ($this->isAllow()) {
                $this->ShowList($mode);
            }
            return true;
        } else {
            throw new \NotFoundException('Новость не найдена! Воспользуйтесь списком новостей.');
        }
        return false;
    }

    /// Отобразить страницу новостей
    /// @param mode: '' - список новостей
    public function ExecMode($mode = '') {
        global $tmpl, $CONFIG, $db;
        $tmpl->setContent("<div id='breadcrumbs'><a href='/'>Главная</a>Новости</div><h1>Новости сайта</h1>");
        $tmpl->setTitle("Новости сайта - " . $CONFIG['site']['display_name']);
        if ($mode == '') {
            if (\acl::testAccess($this->acl_object_name, \acl::CREATE, 1)) {
                $tmpl->addContent("<a href='{$this->link_prefix}&amp;mode=add&amp;opt=" . request('type') . "'>Добавить новость</a><br>");
            }
            if ($this->isAllow()) {
                $this->ShowList(request('type'));
            }
        } else if ($mode == 'read') {
            if ($this->isAllow()) {
                $this->View(rcvint('id'));
            }
        }
        else if ($mode == 'add') {
            if ($this->isAllow('create')) {
                $this->WriteForm(0, request('opt'));
            }
        }
        else if ($mode == 'edit') {
            if ($this->isAllow('edit')) {
                $id = rcvint('id');
                $res = $db->query("SELECT `news`.`id`, `news`.`text`, `news`.`date`, `users`.`name` AS `autor_name`, `news`.`ex_date`, `news`.`img_ext`,
                    `news`.`type`, `news`.`hidden`
                FROM `news`
                INNER JOIN `users` ON `users`.`id`=`news`.`autor`
                WHERE `news`.`id`='$id'");
                if ($res->num_rows) {
                    $line = $res->fetch_assoc();
                    $this->WriteForm($line['id'], $line['type'], $line['ex_date'], $line['text']);
                }
                else {
                    throw new \NotFoundException('Новость не найдена.');
                }
            }
        }
        else if ($mode == 'save') {
            if ($this->isAllow('create')) {
                $news_id = $this->Save();
                $this->View($news_id);
            }
        }
        else if ($mode == 'pub') {
            if ($this->isAllow('create')) {
                $id = rcvint('id');
                $this->Publish($id);
            }
        }
        else {
            throw new \NotFoundException("Неверный $mode");
        }
    }

/// Отобразить летну новостей заданного типа
/// @param type: '' - любые типы, news - только новости, stocks - только акции, events - только события
    protected function ShowList($type = '') {
        global $tmpl, $CONFIG, $db;
        switch ($type) {
            case 'news': $name = 'Новости';
                $where = "`news`.`type`='novelty'";
                break;
            case 'stocks': $name = 'Акции';
                $where = "`news`.`type`='stock'";
                break;
            case 'events': $name = 'События';
                $where = "`news`.`type`='event'";
                break;
            default: $type = '';
                $name = 'Новости, акции, события';
                $where = '1';
        }
        if (!\acl::testAccess($this->acl_object_name, \acl::UPDATE, true)) {
            $where .= " AND `hidden`=0";
        }
        $res = $db->query("SELECT `news`.`id`, `news`.`text`, `news`.`date`, `users`.`name` AS `autor_name`,
            `news`.`ex_date`, `news`.`img_ext`, `news`.`type`, `news`.`hidden`
        FROM `news`
	INNER JOIN `users` ON `users`.`id`=`news`.`autor`
	WHERE $where
	ORDER BY `date` DESC LIMIT 50");
        if ($res->num_rows) {
            $tmpl->setContent("<div id='breadcrumbs'><a href='/'>Главная</a>$name</div><h1>$name</h1>");
            $tmpl->setTitle("$name сайта - " . $CONFIG['site']['display_name']);
            if (\acl::testAccess($this->acl_object_name, \acl::CREATE, true)) {
                $tmpl->addContent("<a href='{$this->link_prefix}&amp;mode=add&amp;opt=$type'>Добавить новость</a><br>");
            }
            $wikiparser = new \WikiParser();
            while ($line = $res->fetch_assoc()) {
                $wikiparser->title = '';
                $tmpl->addContent("<div class='news-block'>");
                $text = $wikiparser->parse(html_out($line['text']));
                if ($line['img_ext']) {
                    $miniimg = new \ImageProductor($line['id'], 'n', $line['img_ext']);
                    $miniimg->SetX(48);
                    $miniimg->SetY(48);
                    $tmpl->addContent("<img src='" . $miniimg->GetURI() . "' style='float: left; margin-right: 10px;' alt=''>");
                }
                if ($line['type'] == 'stock') {
                    $do = "<br><i><u>Действует до:	{$line['ex_date']}</u></i>";
                } else if ($line['type'] == 'event') {
                    $do = "<br><i><u>Дата проведения:	{$line['ex_date']}</u></i>";
                } else {
                    $do = '';
                }
                $link = $this->GetNewsLink($line['id']);
                $hidden = $line['hidden'] ? '<b style="color: #f00;"> - не опубликовано</b>' : '';
                $tmpl->addContent("<h3><a href='$link'>{$wikiparser->title}</a>$hidden</h3><p>$text<br><i>{$line['date']}, {$line['autor_name']}</i>$do</p></div>");
                // <!--<br><a href='/forum.php'>Комментарии: 0</a>-->
            }
        } else {
            throw new \NotFoundException('Новость не найдена! Воспользуйтесь списком новостей.');
        }
    }

/// Отобразить заданную новость
    protected function View($id) {
        global $tmpl, $db;
        $res = $db->query("SELECT `news`.`id`, `news`.`text`, `news`.`date`, `users`.`name` AS `autor_name`, `news`.`ex_date`, `news`.`img_ext`,
            `news`.`type`, `news`.`hidden`
        FROM `news`
	INNER JOIN `users` ON `users`.`id`=`news`.`autor`
	WHERE `news`.`id`='$id'");
        if ($res->num_rows) {
            $news_info = $res->fetch_assoc();
            $edit_enable = false;
            
            if ($news_info['hidden']) {
                if (!\acl::testAccess($this->acl_object_name, \acl::UPDATE, true)) {
                    throw new \NotFoundException('Новость снята с публикации.');
                } else {
                    $edit_enable = true;
                    $hidden = '<b style="color: #f00;"> - не опубликовано</b>';
                }
            } else {
                $hidden = '';
            }

            $wikiparser = new \WikiParser();
            $wikiparser->title = '';
            $text = $wikiparser->parse(html_out($news_info['text']));
            
            if ($news_info['type'] == 'stock') {
                $do = "<div id='page-info'>Действует до: {$news_info['ex_date']}</div>";
            } else if ($news_info['type'] == 'event') {
                $do = "<div id='page-info'>Дата проведения: {$news_info['ex_date']}</div>";
            } else {
                $do = '';
            }

            $tmpl->setContent("<div id='breadcrumbs'><a href='/'>Главная</a><a href='{$this->link_prefix}'>Новости</a>{$wikiparser->title}</div>"
                . "<h1>{$wikiparser->title}$hidden</h1>" . $do
                . "<p>$text</p><p align='right'><i>{$news_info['date']}, {$news_info['autor_name']}</i></p>");
            // <a href='/forum.php'>Комментарии: 0</a>
            if($edit_enable) {
                $tmpl->addContent("<a href='{$this->link_prefix}&amp;mode=edit&amp;id=$id'>Изменить</a><br>"
                . "<fieldset><legend>Публикация</legend>"
                . "<form action='{$this->link_prefix}&amp;mode=pub&amp;id=$id' method='post'>"
                . "<label><input type='checkbox' name='send' value='1' checked>Выполнить рассылку</label><br>"
                . "<button type='submit'>Опубликовать</button>"
                . "</form>"
                . "</fieldset>");
            }
        }
        else {
            throw new \NotFoundException('Новость не найдена! Воспользуйтесь списком новостей.');
        }
    }

    /// Форма создания новости
    protected function WriteForm($id=0, $type='news', $ex_date='', $text='') {
        global $tmpl;
        $novelty_c = $stock_c = $event_c = '';
        switch ($type) {
            case 'news':
            case 'novelty':
                $novelty_c = ' checked';
                break;
            case 'stock': 
            case 'stocks':
                $stock_c = ' checked';
                break;
            case 'event': 
            case 'events': 
                $event_c = ' checked';
                break;
        }
        $tmpl->addContent("
	<form action='{$this->link_prefix}' method='post' enctype='multipart/form-data'>
	<h2>Добавление новости</h2>
	<input type='hidden' name='mode' value='save'>
        <input type='hidden' name='id' value='$id'>
	Класс новости:<br>
	<small>Определяет место её отображения</small><br>
	<label><input type='radio' name='type' value='novelty'$novelty_c>Обычная<br><small>Отображается только в ленте новостей</small></label><br>
	<label><input type='radio' name='type' value='stock'$stock_c>Акция<br><small>Отображается в ленте новостей и списке акций. Дата - дата окончания акции</small></label><br>
	<label><input type='radio' name='type' value='event'$event_c>Событие<br><small>Проведение выставки, распродажа, конурс, итд. Дата - дата наступления события</small></label><br>
	<br>
	Дата:<br>
	<input type='text' name='ex_date' value='".html_out($ex_date)."'><br><br>
	Текст новости:<br>
	<small>Можно использовать wiki-разметку. Заголовок будет взят из текста.</small><br>
	<textarea name='text' class='e_msg' rows='6' cols='80'>".html_out($text)."</textarea><br><br>
	Изображение для списка новостей (jpg, png, gif):<br>
	<small>Следите за пропорциями!</small><br>
	<input type='hidden' name='MAX_FILE_SIZE' value='8000000'>
	<input name='img' type='file'><br><br>
	<button type='submit'>Записать новость</button><br>
	<small>После записи новость нужно будет опубликовать</small>
	</form>");
    }

    /// Сохранить новость для публикации
    protected function Save() {
        global $tmpl, $CONFIG, $db;
        
        $id = rcvint('id');
        $text = strip_tags(request('text'));
        $type = request('type');
        $ex_date = date("Y-m-d", strtotime(request('ex_date')));
        
        $wikiparser = new \WikiParser();
        $wikiparser->parse(html_entity_decode($text, ENT_QUOTES, "UTF-8"));
        if (!isset($wikiparser->title)) {
            throw new Exception("Заголовок новости не задан");
        }
        $title = $wikiparser->title;
        

        if ($type != 'novelty' && $type != 'stock' && $type != 'event') {
            $type = 'novelty';
        }

        $db->startTransaction();
        
        $data = array(
            'type'  => $type,
            'title' => $title,
            'text'  => $text,
            'autor' => $_SESSION['uid'],
            'ex_date'=> $ex_date
        );
        
        if ($id) {
            $db->updateA('news', $id, $data);
            $news_id = $id;
        } else {
            $data['hidden'] = 1;
            $data['date'] = date("Y-m-d H:i:s");
            $news_id = $db->insertA('news', $data);
        }

        if (!$news_id) {
            throw new Exception("Не удалось получить ID новости");
        }

        if (is_uploaded_file($_FILES['img']['tmp_name'])) {
            $aa = getimagesize($_FILES['img']['tmp_name']);
            if (!$aa) {
                throw new Exception('Полученный файл не является изображением');
            }
            if(!is_array($aa)) {
                throw new Exception('Ошибка анализа заголовков изображения');
            }
            if (($aa[0] < 20) || ($aa[1] < 20)) {
                throw new Exception('Слишком мальенькое изображение');
            }
            switch ($aa[2]) {
                case IMAGETYPE_GIF: $ext = 'gif';
                    break;
                case IMAGETYPE_JPEG: $ext = 'jpg';
                    break;
                case IMAGETYPE_PNG: $ext = 'png';
                    break;
                default: throw new Exception('Формат изображения не поддерживается');
            }
            @mkdir($CONFIG['site']['var_data_fs'] . "/news/", 0755);
            $m_ok = move_uploaded_file($_FILES['img']['tmp_name'], $CONFIG['site']['var_data_fs'] . "/news/$news_id.$ext");
            if (!$m_ok) {
                throw new Exception("Не удалось записать изображение в хранилище");
            }
            $db->update('news', $news_id, 'img_ext', $ext);
        }
        $db->commit();
        $tmpl->msg("Новость добавлена!", "ok");
        return $news_id;
    }


    /// Запись новости в хранилище
    protected function Publish($id) {
        global $tmpl, $CONFIG, $db;
        $send = request('send');
        
        $res = $db->query("SELECT `news`.`id`, `news`.`text`, `news`.`date`, `users`.`name` AS `autor_name`, `news`.`ex_date`, `news`.`img_ext`,
            `news`.`type`, `news`.`hidden`
        FROM `news`
	INNER JOIN `users` ON `users`.`id`=`news`.`autor`
	WHERE `news`.`id`='$id'");
        if (!$res->num_rows) {
            throw new \NotFoundException('Новость не найдена.');
        }
        $news_info = $res->fetch_assoc();
        if(!$news_info['hidden'])   {
            throw new \Exception('Новость уже была опубликована ранее.');
        }
        
        $db->startTransaction();
        $db->update('news', $id, 'hidden', 0);
        
        if($send) {
            $wikiparser = new \WikiParser();
            $uwtext = $wikiparser->parse(html_entity_decode($news_info['text'], ENT_QUOTES, "UTF-8"));
            if (!isset($wikiparser->title)) {
                throw new Exception("Заголовок новости не задан");
            }
            $title = $wikiparser->title;
            $uwtext = strip_tags($uwtext);
            $uwtext = $title . "\n" . $uwtext;
            
            if ($news_info['type'] == 'stock') {
                $uwtext .= "\n\nАкция действует до: {$news_info['ex_date']}\n";
            } else if ($news_info['type'] == 'event') {
                $uwtext .= "\n\nСобытие пройдёт: {$news_info['ex_date']}\n";
            }

            $list_id = 'news' . $id . '.' . date("dmY") . '.' . $CONFIG['site']['name'];
            SendSubscribe($title, $title . " - новости сайта", $uwtext, $list_id);
            $tmpl->msg("Рассылка выполнена успешно.", "ok");           
        }
        $db->commit();
        $tmpl->msg("Новость опубликована!", "ok");
    }

    /// Получить ссылку на новость с заданным ID
    protected function GetNewsLink($id, $alt_param = '') {
        global $CONFIG;
        if ($CONFIG['site']['recode_enable']) {
            return "/news/read/$id.html" . ($alt_param ? "?$alt_param" : '');
        } else {
            return "{$this->link_prefix}&amp;mode=read&amp;id=$id" . ($alt_param ? "&amp;$alt_param" : '');
        }
    }

}
