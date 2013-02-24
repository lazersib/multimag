//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2013, BlackLight, TND Team, http://tndproject.org
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

// Работа с журналом документов
// Экспериментально!

function initDocJournal(container_id)
{
	var container=document.getElementById(container_id)
	var doc_list_status=document.getElementById('doc_list_status');
	var doc_list_filter=document.getElementById('doc_list_filter');
	
	function requestData()
	{
		doc_list_status.innerHTML="Запрос...";
		var httpRequest
		if (window.XMLHttpRequest) httpRequest = new XMLHttpRequest()
		if (!httpRequest)  return false
		var url='/docj_new.php?mode=get'
		httpRequest.onreadystatechange = receiveDataProcess
		httpRequest.open('GET', url, true)
		httpRequest.send(null)
		function receiveDataProcess()
		{
			if (httpRequest.readyState == 4)
			{
				if (httpRequest.status == 200)
				{
					doc_list_status.innerHTML="Ответ...";
					parseReceived(httpRequest.responseText)
				}
				else alert('ошибка '+httpRequest.status)
					
			}
// 			else if (httpRequest.readyState == 2)
// 			{
// 				
// 			}
// 			else if (httpRequest.readyState == 3)
// 			{
// 				doc_list_status.innerHTML="Обработка...";
// 			}
			//else {}
		}
	}
	
	var docj_list_body=document.getElementById('docj_list_body');
	
	function parseReceived(data)
	{
		try
		{
			//alert(data)
			doc_list_status.innerHTML="Парсим...";
			var start = new Date()
			var json=eval('('+data+')')
			var end= new Date()
			json.eval=end.getTime()-start.getTime();
			doc_list_status.innerHTML="Готово...";
			if(json.result=='ok')
			{
				//alert('exec_time: '+json.exec_time)
				//var just = new JUST({ root : '/tpl', ext : '.html' });
				render(json)
			}
			else alert(json.error)
		}
		catch(e)
		{
			alert(e.message)
		}
	}
	
	function render(data)
	{
		i=0;
		var render_start_date=new Date
		doc_list_status.innerHTML="обработано за "+data.eval+", запрос выполнен за:"+data.exec_time;
		window.setTimeout(appendChunk, 0);
		var pr_sum=0
		var ras_sum=0
		function appendChunk()
		{
			var date_start=new Date
			for (var c = 0; i < data.doc_list.length; c++)
			{
				if(data.doc_list[i].ok>0)
				{
					switch(parseInt(data.doc_list[i].type))
					{
						case 1:
						case 4:
						case 6:	pr_sum+=parseFloat(data.doc_list[i].sum);
							break;
						case 2:
						case 5:
						case 7:
						case 18:ras_sum+=parseFloat(data.doc_list[i].sum);
							break;
					}
				}
				
				newLine(data.doc_list[i], data.user_id)
				i++;
				if(i%40)
				if( ((new Date)-date_start) > 200)
				{
					window.setTimeout(appendChunk, 120);
					return;
				}
			}
			//alert('done!');
			doc_list_status.innerHTML="Итого: приход: "+pr_sum.toFixed(2)+", расход: "+ras_sum.toFixed(2)+". Баланс: "+(pr_sum-ras_sum).toFixed(2)+", запрос выполнен за:"+data.exec_time+"сек, отображено за: "+((new Date-render_start_date)/1000).toFixed(2)+" сек";
		}
		
		//form_container.appendChild(fragment)
	}
	
	function newLine(line, user_id)
	{
		var tr=docj_list_body.insertRow(-1);
		var tr_class='pointer';
		if(line.author_id==1) tr_class+=' lin11';
		tr.className=tr_class;
		var html="<td style='text-align: right;' class='"+line.num_highlight+"' onclick=\"window.open('/doc.php?mode=body&amp;doc="+line.id+"'); return false;\">"+line.altnum+line.subtype+"</td><td onclick=\"window.open('/docj.php?mode=tree&amp;doc="+line.id+"'); return false;\"><img src='img/i_tree.png' alt='Связи'></td><td>"+line.id+"</td><td>";
		if(line.ok>0) html+="<img src='/img/i_suc.png' alt='Проведен'>";
		if(line.mark_del>0) html+="<img src='/img/i_alert.png' alt='Помечен на удаление'>";
		
		html+="</td><td>"+line.doc_name+"</td><td>"+line.data1+"</td><td>"+line.agent_name+"</td><td style='text-align: right;'>"+line.sum+"</td><td>"+line.date+"</td><td onclick=\"window.open('/adm_users.php?mode=view&amp;id="+line.author_id+"'); return false;\">"+line.author_name+"</td>";
		tr.innerHTML=html;
	}
	
	function initFilter(filter)
	{
		var s="<div><table width='100%'><tr><td>Дата от:</td><td align='right'>Дата до:</td></tr><tr><td><input type='text' class='half' name='date_from' id='datepicker_f' value='2012-12-12'></td><td align='right'><input type='text' class='half' name='date_to' id='datepicker_t' value='2012-12-12'></td></tr></table></div>";
		s+="<div><span>Тип документа:</span><input type='text' name='type'></div>"
		s+="<div><table width='100%'><tr><td>Альт. номер:</td><td align='right'>Подтип:</td></tr><tr><td><input type='text' class='half' name='date_from' value=''></td><td align='right'><input type='text' class='half' name='date_to' value=''></td></tr></table></div>";
		s+="<div><span>Агент:</span><input type='text' name='agent'></div>"
		s+="<div><span>Наименование:</span><input type='text' name='position'></div>"
		s+="<div><span>Организация:</span><select name='firm_id'><option>Фирма 1</option></select></div>"
		s+="<div><span>Банк, касса:</span><select name='bk_id'><option>Банк 1</option><option>Касса 1</option></select></div>"
		s+="<div><span>Склад:</span><select name='store_id'><option>Склад 1</option><option>Склад 2</option></select></div>"
		s+="<div><span>Автор:</span><input type='text' name='author'></div>"
		s+="<div><span>Статус проведения:</span><table width='100%'><tr><td><input type='radio' name='stat_ok' value='all' checked>Любой</td><td><input type='radio' name='stat_ok' value='ok'>Да</td><td><input type='radio' name='stat_ok' value='no'>Нет</td></tr></table></div>"
		
		filter.innerHTML=s
	}
	
	requestData();
	initFilter(doc_list_filter);
}

initDocJournal(document.getElementById('docj_list_body'))

