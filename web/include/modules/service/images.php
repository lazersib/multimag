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

namespace modules\service;

/// Управление изображениями
class images extends \IModule {
    
    public function __construct() {
        parent::__construct();
        $this->acl_object_name = 'service.images';
    }

    public function getName() {
        return 'Управление изображениями';
    }
    
    public function getDescription() {
        return 'Замена и переименование изображений товаров';  
    }
    
    protected function viewImagesList() {
        global $tmpl, $db;
        $tmpl->addStyle(".fl {float: left; padding: 5px; margin: 10px; border: 1px solid #00f; width: 120px; height: 150px; text-align: center; background: #888;}");
            $res = $db->query("SELECT * FROM `doc_img` ORDER BY `id`");
            while ($line = $res->fetch_assoc()) {
                $img = new ImageProductor($line['id'], 'p', $line['type']);
                $img->SetY(120);
                $img->SetX(100);

                $tmpl->addContent("<div class='fl'><a href='{$this->link_prefix}&amp;sect=edit&amp;img={$line['id']}'><img src=\"" . $img->GetURI() . "\"></a>
                    <br>" . html_out($line['name']) . "<br><b>{$line['id']}.{$line['type']}</b></div>");
            }
            $tmpl->addContent("<div style='clear: both'></div>");
    }
    
    protected function viewImageEdit($image_id) {
        global $db, $tmpl, $CONFIG;
        settype($image_id, 'int');
        $res = $db->query("SELECT * FROM `doc_img` WHERE `id`=$image_id");
        if ($res->num_rows == 0) {
            throw new NotFoundException("Изображение не найдено");
        }
        $line = $res->fetch_assoc();
        $max_fs = get_max_upload_filesize();
        $max_fs_size = formatRoundedFileSize($max_fs);

        $o_link = "{$CONFIG['site']['var_data_web']}/pos/{$line['id']}.{$line['type']}";
        $tmpl->msg("Замена файла очистит кеш изображений!", "err", "Внимание");
        $tmpl->addContent("<form method='post' enctype='multipart/form-data'>
		<input type='hidden' name='mode' value='cimage'>
		<input type='hidden' name='save' value='ok'>
		<input type='hidden' name='img' value='$image_id'>
		Новое название:<br>
		<input type='text' name='name' value='" . html_out($line['name']) . "'><br>
		Новый файл изображения:<br>
		<input type='hidden' name='MAX_FILE_SIZE' value='$max_fs'><input name='userfile' type='file'><br>
		<b>Форматы</b>: Не более $max_fs_size, форматы JPG, PNG, допустим, но не рекомендуется GIF<br>
		<button>Сохранить</button>
		</form><br>
		<a href='$o_link'>Скачать оригинал изображения</a><br><br>");
        $image_id = new ImageProductor($line['id'], 'p', $line['type']);
        $image_id->SetNoEnlarge(1);
        $image_id->SetY(800);

        $tmpl->addContent("<img src=\"" . $image_id->GetURI() . "\">");
    }

    public function run() {
        global $CONFIG, $tmpl, $db;
        $tmpl->addBreadcrumb($this->getName(), $this->link_prefix);
        $sect = request('sect');
        switch ($sect) {
            case '':
                $tmpl->addBreadcrumb($this->getName(), '');
                $this->viewImagesList();
                break;
            case 'edit':
                $image_id = rcvint('img');
                $this->viewImageEdit($image_id);
                break;
            case 'queue_summary':
                $this->renderSummary();
                break;
            case 'cdr':
                $this->renderCDR();
                break;
            case 'audio':
                $callid = request('callid');
                if(!$callid) {
                    throw new \NotFoundException("Данные не найдены");
                }
                if(isset($CONFIG['service_cdr']['file_path'])) {
                    $file_dir = $CONFIG['service_cdr']['file_path'];
                } else {
                    $file_dir = '/var/spool/asterisk/monitor';
                }
                if(isset($CONFIG['service_cdr']['file_path'])) {
                    $file_ext= $CONFIG['service_cdr']['file_ext'];
                } else {
                    $file_ext = 'wav';
                }
                $file = $file_dir . '/' . $callid . '.' . $file_ext;
                $send = new \sendFile;
                $send->Path = $file;                
                $send->send();
                exit;
                break;
            default:
                throw new \NotFoundException("Секция не найдена");
        }
    }

}
