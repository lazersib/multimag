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

// Работа с журналом документов
// Экспериментально!

function initDocJournal(container_id)
{
	var container=document.getElementById(container_id)
	function requestData()
	{
		var httpRequest
		if (window.XMLHttpRequest) httpRequest = new XMLHttpRequest()
		if (!httpRequest)  return false
		var url='/docj_new.php'
		httpRequest.onreadystatechange = receiveDataProcess
		httpRequest.open('GET', url, true)
		httpRequest.setRequestHeader('X-Ajax', 'true')
		httpRequest.send(null)
		function receiveDataProcess()
		{
			if (httpRequest.readyState == 4)
			{
				if (httpRequest.status == 200)
				{
					parseReceived(httpRequest.responseText)
				}
				else alert('ошибка '+httpRequest.status)
					
			}
			else if (httpRequest.readyState == 2)
			{
				
			}
			else if (httpRequest.readyState == 3)
			{
				//status.innerHTML="Обработка...";
			}
			//else {}
		}
	}
	
	var docj_list_body=document.getElementById('docj_list_body');
	var doc_list_status=document.getElementById('doc_list_status');
	function parseReceived(data)
	{
		try
		{
			//alert(data)
			var json=eval('('+data+')')
			if(json.result=='ok')
			{
				//alert('exec_time: '+json.exec_time)
				//var just = new JUST({ root : '/tpl', ext : '.html' });
				
// 				just.render('doc_list', { title: 'Hello, World!', data: json.doc_list, user_id: json.user_id }, function(error, html) {
// 					form_container.innerHTML=html;
// 				});
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
		doc_list_status.innerHTML="запрос выполнен за:"+data.exec_time;
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
				if(i%10)
				if( ((new Date)-date_start) > 100)
				{
					window.setTimeout(appendChunk, 60);
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
		else html+="<img src='/img/i_del.png' alt='Удалить'>";
		html+="</td><td>"+line.doc_name+"</td><td>"+line.data1+"</td><td>"+line.agent_name+"</td><td style='text-align: right;'>"+line.sum+"</td><td>"+line.date+"</td><td onclick=\"window.open('/adm_users.php?mode=view&amp;id="+line.author_id+"'); return false;\">"+line.author_name+"</td>";
		tr.innerHTML=html;
		//return tr;
	}
	
	requestData();
}

initDocJournal(document.getElementById('docj_list_body'))

