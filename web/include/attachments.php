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
//

/// Класс для работы с прикреплёнными файлами
class attachments {
    protected $storage;
    
    public function __construct($storage) {
        $this->storage = $storage;
    }
    
    public function getFileInfo($file_id) {
        global $db;
        settype($file_id, 'int');
        $tn = $db->real_escape_string($this->storage);
        
        $res = $db->query("SELECT `$tn`.`id`, `$tn`.`description`, `$tn`.`original_filename`,`$tn`. `size`, `$tn`.`date`, `$tn`.`user_id`"
            . ", `users`.`name` AS `user_name`"
            . " FROM `$tn`"
            . " LEFT JOIN `users` ON `users`.`id`=`$tn`.`user_id`"
            . " WHERE `$tn`.`id`=$file_id");
        if (!$res) {
            return false;
        }
        if (!$res->num_rows) {
            return 0;
        }
        return $res->fetch_assoc();
    }
    
    public function getFilesList($object_name) {
        global $db;
        $ret = array();
        $object_name = $db->real_escape_string($object_name);
        $tn = $db->real_escape_string($this->storage);
        $res = $db->query("SELECT `$tn`.`id`, `$tn`.`description`, `$tn`.`original_filename`,`$tn`. `size`, `$tn`.`date`, `$tn`.`user_id`"
            . ", `users`.`name` AS `user_name`"
            . " FROM `$tn`"
            . " LEFT JOIN `users` ON `users`.`id`=`$tn`.`user_id`"            
            . " WHERE `$tn`.`attached_to`='$object_name'");
        while($line = $res->fetch_assoc()) {
            $ret[$line['id']] = $line;
        }
        return $ret;
    }
    
    public function testExists($file_id) {
        settype($file_id, 'int');
        $storage_dir = \cfg::get('site', 'var_data_fs');
        if(!$storage_dir) {
            return false;
        }
        $storage_dir .= '/attachments/'.$this->storage.'/';
        if (!file_exists($storage_dir . $file_id)) {
            return false;
        }
        return true;
    }
    
    public function getSize($file_id) {
        settype($file_id, 'int');
        $storage_dir = \cfg::get('site', 'var_data_fs');
        if(!$storage_dir) {
            return false;
        }
        $storage_dir .= '/attachments/'.$this->storage.'/';
        if (!is_readable($storage_dir . $file_id)) {
            return false;
        }
        return filesize($storage_dir . $file_id);
    }

    public function upload($file, $attached_to, $description='') {
        global $db;
        $storage_dir = \cfg::get('site', 'var_data_fs');
        if(!$storage_dir) {
            throw new \Exception('Не задан путь к хранилищу файлов в настройках');
        }
        $storage_dir .= '/attachments/'.$this->storage.'/';
        if ($file['size'] <= 0) {
            throw new \Exception("Файл не получен. Возможно он не был выбран, либо его размер превышает максимально допустимый сервером");
        }
        $filename = $file['name'];
        $search = array("#","$","%","^","&","*","?","'","\"","\\","/"," ",);
        $replace = array(".Hash.",".Dollar.",".Percent.","",".And.",".Ast.",".Quest.",".Apo.",".Quot.",".LSlash.",".RSlash.","_",);
        $filename = str_replace($search, $replace,  $filename);
        $fileline = array(
            'attached_to' => $attached_to,
            'description' => $description,
            'original_filename' => $filename,
            'user_id' => isset($_SESSION['uid'])?$_SESSION['uid']:null,
            'size' => filesize($file['tmp_name']),
            'date' => date("Y-m-d H:i:s"),
        );
        $file_id = $db->insertA($this->storage, $fileline);

        if (!$file_id) {
            throw new \Exception("Не удалось получить ID строки");
        }
        if (!file_exists($storage_dir)) {
            if (!mkdir($storage_dir, 0755, true)) {
                throw new \Exception("Не удалось создать директорию для прикреплённых файлов. Вероятно, права доступа установлены неверно.");
            }
        }
        if (!is_dir($storage_dir)) {
            throw new \Exception("Вместо директории для прикреплённых файлов обнаружен файл. Обратитесь к администратору.");
        }
        if(!is_writable ($storage_dir)) {
            throw new \Exception("Директория для прикреплённых файлов не доступна для записи. Обратитесь к администратору.");
        }
        if (!move_uploaded_file($file['tmp_name'], $storage_dir . $file_id)) {
            throw new \Exception("Не удалось сохранить файл");
        }
        return $file_id;
    }
    
    public function download($file_id) {
        $fi = $this->getFileInfo($file_id);
        if (!$fi) {
            throw new \NotFoundException("Файл не найден в базе");
        } 
        $storage_dir = \cfg::get('site', 'var_data_fs');
        if(!$storage_dir) {
            throw new \Exception('Не задан путь к хранилищу файлов в настройках');
        }
        $storage_dir .= '/attachments/'.$this->storage.'/';
        if (!file_exists($storage_dir . $fi['id'])) {
                throw new \NotFoundException("Файл не найден в хранилище");
        }
        
        $wait_length = 1000000 / (\cfg::get('site', 'dowload_attach_speed', 128) / 2);
        $filesize = filesize($storage_dir . $fi['id']);
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=".  html_out($fi['original_filename']));
        header("Content-Length: $filesize");
        $handle = fopen($storage_dir . $fi['id'], "rb");
        session_write_close();  // Чтобы не было зависаний других потоков
        while (!feof($handle) && !connection_aborted()) {
            echo fread($handle, 2048);
            flush();
            usleep($wait_length);
        }
        fclose($handle);
        exit(); 
    }
    
    public function remove($file_id) {
        global $db;
        $fi = $this->getFileInfo($file_id);
        if (!$fi) {
            throw new \NotFoundException("Файл не найден в базе");
        } 
        $storage_dir = \cfg::get('site', 'var_data_fs');
        if(!$storage_dir) {
            throw new \Exception('Не задан путь к хранилищу файлов в настройках');
        }
        if (!file_exists($storage_dir . $fi['id'])) {
            $db->delete($this->storage, $file_id);
            return true;
        }
        if(unlink($storage_dir . $fi['id'])) {
            $db->delete($this->storage, $file_id);
            return true;
        }
        return false;
    }
}
