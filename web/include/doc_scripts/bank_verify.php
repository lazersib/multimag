<?php

include_once($CONFIG['location']."/common/bank1c.php");

class ds_bank_verify
{

function Run($mode)
{
	global $tmpl;
	if($mode=='view')
	{
		$tmpl->AddText("<h1>".$this->getname()."</h1>
		<form action='' method='post' enctype='multipart/form-data'>
		<input type='hidden' name='mode' value='load'>
		<input type='hidden' name='param' value='i'>
		<input type='hidden' name='sn' value='bank_verify'>
		
		Файл банковской выписки:<br>
		<input type='hidden' name='MAX_FILE_SIZE' value='10000000'><input name='userfile' type='file'>
		<button type='submit'>Выполнить</button>
		</form>
		");
	}
	else if($mode=='load')
	{
		$tmpl->AddText("<h1>".$this->getname()."</h1>");
		if($_FILES['userfile']['size']<=0)	throw new Exception("Забыли выбрать файл?");
		$file=file($_FILES['userfile']['tmp_name']);
		$_SESSION['bankparser']=new Bank1CPasrser($file);
		$_SESSION['bankparser']->Parse();
		$_SESSION['bp']['parsed_data']=$_SESSION['bankparser']->parsed_data;
		$len=count($_SESSION['bankparser']->parsed_data);

		//var_dump($_SESSION['bp']['parsed_data']);
		$tmpl->AddText("<table width='100%'>
		<tr><th colspan='5'>В выписке<th colspan='5'>В базе
		<tr>
		<th>ID<th>Номер<th>Дата<th>Сумма<th>Счёт
		<th>ID<th>Номер<th>Дата<th>Сумма<th>Агент");
		
		foreach($_SESSION['bankparser']->parsed_data as $v_line)
		{
			$tmpl->AddText("<tr>
			<td>{$v_line['unique']}<td>{$v_line['docnum']}<td>{$v_line['date']}<td>{$v_line['debet']} / {$v_line['kredit']} <td>{$v_line['kschet']}");
		
			$res=mysql_query("SELECT `doc_list`.`id`, CONCAT(`doc_list`.`altnum`, `doc_list`.`subtype`) AS `num`, `doc_list`.`date`, `doc_list`.`sum`, `doc_agent`.`name` AS `agent_name`
			FROM `doc_dopdata`
			LEFT JOIN `doc_list` ON `doc_dopdata`.`doc`=`doc_list`.`id`
			LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
			WHERE `doc_dopdata`.`param`='unique' AND `doc_dopdata`.`value`='{$v_line['unique']}'");
			$doc_data=mysql_fetch_array($res);
			
			if($doc_data)
			{
				$date=date("d.m.Y H:i:s",$doc_data['date']);
				$tmpl->AddText("<td>{$doc_data['id']}<td>{$doc_data['num']}<td>$date<td>{$doc_data['sum']}<td>{$doc_data['agent_name']}");
			}
		}
		

		$tmpl->AddText("</table>");
	}	
}


function getName()
{
	return "Сверка банковских документов";
}

};

?>
