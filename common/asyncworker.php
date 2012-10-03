<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2012, BlackLight, TND Team, http://tndproject.org
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
class AsyncWorker
{
	var $mail_text;
	var $starttime;
	var $task_id;

	function __construct($task_id)
	{
		$this->mail_text='';
		$this->starttime=time();
		$this->task_id=$task_id;
	}

	/// Устанавливает статус исполнения обработчика в процентах для отображения в интерфейсе
	/// Расчитывает примерное время исполнения
	function SetStatus($status)
	{
		$remains=(time()-$this->starttime)*(100/$status-1);
		$remainm=round($remains/60);
		$remains%=60;
		if($remainm)	$text="Выполнено $status% (осталось не менее $remainm мин. $remains сек.)";
		else		$text="Выполнено $status% (осталось не менее $remains сек.)";
		$this->SetStatusText($text);
	}

	function SetStatusText($text)
	{
		echo "\r$text             ";
		/// Добавить код записи в базу данных
		mysql_query("UPDATE `async_workers_tasks` SET `textstatus`='$text' WHERE `id`='{$this->task_id}'");
		if(mysql_errno())	throw new MysqlException("Не удалось обновить статус");
	}
	/// Устанавливает статус окончания исполнения
	function end()
	{
		$this->SetStatusText("Выполнено");
	}

	/// Осовобождает ресурсы
	function finalize()
	{
		return;
	}

};


?>