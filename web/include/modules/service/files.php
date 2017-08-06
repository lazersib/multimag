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

namespace modules\service;

/// Страница со статьёй внутренней базы знаний в wiki формате
class files extends \IModule {
    
    public function __construct() {
        parent::__construct();
        $this->acl_object_name = 'service.files';
        $this->table_name = 'intfiles';
    }

    public function getName() {
        return 'Прикреплённые файлы (служебные)';
    }
    
    public function getDescription() {
        return 'Модуль для просмотра и редактирования служебных прикреплённых файлов';  
    }
   
    public function run() {
        global $tmpl;
        $tmpl->addBreadcrumb($this->getName(), $this->link_prefix);
        $sect = request('sect');
        switch ($sect) {
            case '':
                $tmpl->addBreadcrumb($this->getName(), '');
                $tmpl->addContent("<p>".$this->getDescription()."</p>"
                    . "<ul>"
                    . "<li><a href='" . $this->link_prefix . "&amp;sect=list'>Смотреть список</li>"
                    . "</ul>");
                break;
            case 'attachto':
                $attach_to = request('attachto');
                $this->attachToPage($attach_to);
                break;
            default:
                throw new \NotFoundException("Секция не найдена");
        }
    }

    public function attachToPage($object_name) {
        global $tmpl;
        \acl::accessGuard($this->acl_object_name, \acl::CREATE | \acl::UPDATE);
        $tmpl->addBreadcrumb('Прикрепление файла к '.$object_name, '');
        $tmpl->addContent($this->getAttachFileForm($object_name));
    }


    protected function getAttachFileForm($attach_to) {
        $max_fs = \webcore::getMaxUploadFileSize();
        $max_fs_size = \webcore::toStrDataSizeInaccurate($max_fs);
        $ret = "
            <form action='{$this->link_prefix}' method='post' enctype='multipart/form-data'>
            <input type='hidden' name='sect' value='filesave'>
            <input type='hidden' name='attachto' value='" . html_out($attach_to) . "'>
            <table cellpadding='0' class='list'>
            <tr><td><b style='color:#f00;'>*</b>Выберите файл:</td>
                <td><input type='hidden' name='MAX_FILE_SIZE' value='$max_fs'>
                    <input name='userfile' type='file' required placeholder='Выберите файл'>
                    <br><small>Не более $max_fs_size</small></td></tr>
            <tr><td><b style='color:#f00;'>*</b>Описание файла (до 128 символов)</td>
                <td><input type='text' name='description' placeholder='Вложение' maxlength='128' required>
            <tr><td colspan='2' align='center'>
            <input type='submit' value='Сохранить'>
            </table>";
        return $ret;
    }
}
