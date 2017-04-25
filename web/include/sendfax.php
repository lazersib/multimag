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


/// Отправка факсов через API http://www.virtualofficetools.ru/ / http://www.votbox.ru/
/// требует php-libcurl
/// использует параметры конфигурации ['sendfax']
/// @brief отправка факсов
class faxSender {

    var $file_str;
    var $faxnumber;
    var $notifymail;

    /// Выбрать файл для отправки
    /// @param buf - содержимое отправляемого файла
    function setFileBuf($buf) {
        $this->file_str = $buf;
    }

    /// Установить номер факса получателя
    /// @param num - номер факса
    function setFaxNumber($num) {
        if (preg_match('/^\+7\d{1,}$/', $num)) {
            $num = '8' . substr($num, 2);
        } else if (preg_match('/^\+\d{1,}$/', $num)) {
            $num = '810' . substr($num, 1);
        }
        $this->faxnumber = $num;
    }

    /// Установить email для уведомлений
    /// @param email - email адрес, на который будет отправлено уведомление об отправке факса
    function setNotifyMail($email) {
        $this->notifymail = $email;
    }

    /// Отправить факс
    function send() {
        global $CONFIG;
        if (!isset($CONFIG['sendfax'])) {
            throw new Exception("Работа с факсами не настроена!");
        }
        if ($this->file_str == '') {
            throw new Exception("Не указан передаваемый файл!");
        }
        if ($this->faxnumber == '') {
            throw new Exception("Не указан номер факса получателя!");
        }
        if ($this->notifymail == '') {
            throw new Exception("Не указан email адрес для уведомлений!");
        }
        $fn = tempnam('/tmp', 'mmag_fax');
        $fn .= '.pdf';
        $fd = fopen($fn, 'wb');
        if (!$fd) {
            throw new \Exception("Не удалось создать временный файл!");
        }
        fwrite($fd, $this->file_str);
        fclose($fd);
        $postdata = array(
            'username' => \cfg::get('sendfax', 'username'),
            'password' => MD5(\cfg::get('sendfax', 'password')),
            'Phone' => $this->faxnumber,
            //'userfile' => "@" . $fn,
            'userfile' => new CURLFile($fn, null /* или mime-type */, 'file.pdf'),
            'Attempts' => \cfg::get('sendfax', 'attempts'),
            'Delay' => \cfg::get('sendfax', 'delay'),
            'NotifyOnOk' => 1,
            'NotifyOnError' => 1,
            'NotifyEMail' => $this->notifymail);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, \cfg::get('sendfax', 'url', 'http://www.votbox.ru/API/fax.send.api.php') );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
        $output = curl_exec($ch);
        unlink($fn);
        if (curl_errno($ch)) {
            throw new Exception("Ошибка передачи: " . curl_error());
        }
        curl_close($ch);

        $doc = new DOMDocument();
        $doc->loadXML($output);
        $errnode = $doc->getElementsByTagName('response')->item(0)->getElementsByTagName('error')->item(0);
        if ($errnode->getAttribute('code') != 0) {
            throw new Exception("Ошибка от сервиса передачи: " . html_out($errnode->getAttribute('message')) );
        }
        return $output;
    }

}
