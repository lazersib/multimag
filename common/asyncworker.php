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

/// Родительский класс для ассинхронных обработчиков
class AsyncWorker {

    var $mail_text; // Информация, которую обработчик должен отправить администратору
    var $starttime; // Время старта обработчика
    var $task_id; // ID задачи для сохранения статуса
    var $db_link; // Объект класса MysqiExtended

    function __construct($task_id) {
        $this->mail_text = '';
        $this->starttime = time();
        $this->task_id = $task_id;
    }

    /// Устанавливает статус исполнения обработчика в процентах для отображения в интерфейсе
    /// Расчитывает примерное время исполнения
    function setStatus($status, $add_text = '') {
        $remains = $status ? (time() - $this->starttime) * (100 / $status) : 99;
        $remainm = round($remains / 60);
        $remains%=60;
        if ($remainm) {
            $text = "Выполнено $status% (осталось не менее $remainm мин. $remains сек.). ".$add_text;
        } else {
            $text = "Выполнено $status% (осталось не менее $remains сек.). ".$add_text;
        }
        $this->SetStatusText($text);
    }

    function setStatusText($text) {
        global $db;
        echo "\r$text             ";
        flush();
        /// Добавить код записи в базу данных
        if ($this->task_id) {
            $db->query("UPDATE `async_workers_tasks` SET `textstatus`='$text' WHERE `id`='{$this->task_id}'");
        }
    }

    /// Устанавливает статус окончания исполнения
    function end() {
        $this->SetStatusText("Выполнено");
    }

    /// Осовобождает ресурсы
    function finalize() {
        return;
    }

}
