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

/// Отчёт по агентам без движения
class Report_Agent_NoSells extends BaseGSReport
{
	/// Получить имя отчёта
	public function getName($short=0)
	{
		if($short)	return "По агентам без движения";
		else		return "Отчёт по агентам без движения";
	}
	
	/// Запустить отчёт
	public function Run($opt)
	{
		if($opt=='')	$this->Form();
		else		$this->Make($opt);	
	}
	
	/// Отобразить форму
	protected function Form()
	{
		global $tmpl;
		$d_t=date("Y-m-d");
		$d_f=date("Y-m-d",time()-60*60*24*31);
		$tmpl->AddText("<h1>".$this->getName()."</h1>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='agent_nosells'>
		<input type='hidden' name='opt' value='make'>
		<p class='datetime'>
		Группа агентов:<br>
		".selectAgentGroup('ag_group',0,1)."<br>		
		<fieldset><legend>Дата</legend>
		С:<input type=text id='dt_f' name='dt_f' value='$d_f'><br>
		По:<input type=text id='dt_t' name='dt_t' value='$d_t'>
		</fieldset>
		<label><input type='checkbox' name='fix' value='1'>Только с назначенным ответственным лицом</label><br>
		Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
		<button type='submit'>Сформировать отчёт</button></form>");	
	}
	
	/// Сформировать отчёт
	protected function Make($engine)
	{
		global $CONFIG;
		$this->loadEngine($engine);		

		$dt_f=strtotime(rcv('dt_f'));
		$dt_t=strtotime(rcv('dt_t')." 23:59:59");
		$ag_group=rcv('ag_group');
		settype($ag_group,"int");
		
		$print_df=date('Y-m-d', $dt_f);
		$print_dt=date('Y-m-d', $dt_t);
		
		$sql_add= (rcv('fix')==1) ? " AND `doc_agent`.`responsible`>'0' " : '';
		if($ag_group>0)	$sql_add.=" AND `doc_agent`.`group`='$ag_group' ";
		$this->header($this->getName()." с $print_df по $print_dt");

		$widths=array(5,67,12,16);
		$headers=array('ID','Агент','Телефон','Ответственный');
		$this->tableBegin($widths);
		$this->tableHeader($headers);
		
		$res=mysql_query("SELECT `doc_agent`.`id`, `doc_agent`.`name`, `doc_agent`.`responsible` AS `user_id`, `users_worker_info`.`worker_real_name`, `users`.`name` AS `user_name`, `doc_agent`.`tel`, `doc_agent`.`group` FROM `doc_agent`
		LEFT JOIN `users` ON `users`.`id`=`doc_agent`.`responsible`
		LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`doc_agent`.`responsible`

		WHERE `doc_agent`.`id` NOT IN (SELECT `agent` FROM `doc_list` WHERE `date`>='$dt_f' AND `date`<='$dt_t'  ) $sql_add
		ORDER BY `doc_agent`.`id`");
		while($nxt=mysql_fetch_assoc($res))
		{
			if($nxt['worker_real_name'])			$resp=$nxt['worker_real_name'];
			else if($nxt['user_id']>0 && $nxt['user_name'])	$resp=$nxt['user_name'];
			else if($nxt['user_id']>0)			$resp="==удалён (id:{$nxt['user_id']})==";
			else						$resp="**не назначен**";
			$this->tableRow(array($nxt['id'], $nxt['name'], $nxt['tel'], $resp));
		}
		$this->tableEnd();
		$this->output();
		exit(0);
	}
};

?>

