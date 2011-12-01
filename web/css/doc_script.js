// javascript module for document system
// This is part of "MultiMag" system
// Copyright 2009, TND Project
// This file distributed under GPLv3 license

var old_provodki='';

function ApplyDoc(doc)
{
	var httpRequest;
	if (window.XMLHttpRequest)  {  httpRequest = new XMLHttpRequest(); }
	if (!httpRequest) { return false; }

	var provodki=document.getElementById("provodki");
	old_provodki=provodki.innerHTML;
	provodki.innerHTML="<img src='/img/icon_load.gif'> Загрузка...";
	
	var url='/doc.php?mode=applyj&doc='+doc;
	httpRequest.onreadystatechange = function() { DocProcessRequest(httpRequest, doc); };
	httpRequest.open('GET', url, true);
	httpRequest.send(null);
}

// function CancelDoc(doc)
// {
// 	ShowPopupWin('/doc.php?mode=cancel&doc='+doc);
// }

function CancelDoc(doc)
{
	var httpRequest;
	if (window.XMLHttpRequest)  {  httpRequest = new XMLHttpRequest(); }
	if (!httpRequest) { return false; }

	var provodki=document.getElementById("provodki");
	old_provodki=provodki.innerHTML;
	provodki.innerHTML="<img src='/img/icon_load.gif'> Загрузка...";

	var url='/doc.php?mode=cancelj&doc='+doc;
	httpRequest.onreadystatechange = function() { DocProcessRequest(httpRequest, doc); };
	httpRequest.open('GET', url, true);
	httpRequest.send(null);
	
}



function DocProcessRequest(httpRequest, doc)
{
	var req;
	try
	{
		var provodki=document.getElementById("provodki");	
		if (httpRequest.readyState == 4)
		{
			if (httpRequest.status == 200)
			{
				req=httpRequest.responseText;
				var json=eval('('+httpRequest.responseText+')');
				if(json.response==0)
				{
					jAlert(json.message,"Ошибка", {}, 'icon_err');
					provodki.innerHTML=old_provodki;
				}
				else if(json.response==1)	// Проведение
				{
					if(json.message)	jAlert(json.message,"Сделано!", function() {});
					if(json.buttons)	provodki.innerHTML=json.buttons;
					else			provodki.innerHTML=old_provodki;
					
					if(json.sklad_view)
					{
						
						var sklad_view=document.getElementById("sklad_view")
						var poslist=document.getElementById('poslist')
						var pladd=document.getElementById('pladd')
						if(sklad_view)
						{
							if(json.sklad_view=='show')
							{
								sklad_view.style.display='table'
								poslist.editable=1
								poslist.refresh()
								pladd.style.display='table-row'
							}
							else
							{
								sklad_view.style.display='none'
								pladd.style.display='none'
								poslist.editable=0
								poslist.refresh()
							}
						}
					}
					var statusblock=document.getElementById("statusblock");
					if( json.statusblock && statusblock) statusblock.innerHTML=json.statusblock;

					
				}
				else provodki.innerHTML=old_provodki;
			}
			else
			{
				jAlert("Документ не проведён!"+httpRequest.responseText,"Ошибка "+httpRequest.status, {}, 'icon_err');
				provodki.innerHTML=old_provodki;
			}
		}
	}
	catch(e)
	{
		jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!"+
		"<br><br><i>Информация об ошибке</i>:<br>"+e.name+": "+e.message+ "<br>json:<br>"+req, "Критическая ошибка", {},  'icon_err');
	}
	
	//else jAlert("Документ не проведён!","Ошибка "+httpRequest.readyState, {}, 'icon_err');
}


// Установка / снятие связи
function DocConnect(doc, p_doc)
{
	jPrompt("Укажите <b>системный</b> номер документа,<br>потомком которого должен стать<br>текущий документ:",p_doc,"Связываение документов",  function(result) { DocConnectCallback(doc, result); });
}

function DocConnectCallback(doc, result)
{
	if(result==null)	return;	
	$.ajax({ 
		type:   'POST', 
		url:    '/doc.php', 
		data:   'doc='+doc+'&mode=conn&p_doc='+result, 
		success: function(msg) { DocConnectProcess(msg); }, 
		error:   function() { jAlert('Ошибка соединения!','Связываение документов',{},'icon_err'); }, 
	});
}

function DocConnectProcess(msg)
{
	try
	{
		var json=eval('('+msg+')');
		if(json.response==0)
			jAlert(json.message,"Ошибка", {}, 'icon_err');
		else if(json.response==1)	// Проведение
		{
			if(json.message)	jAlert(json.message,"Связываение документов", {});
			else			jAlert("Сделано!","Связываение документов", {});
		}	
	}
	catch(e)
	{
		jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!"+
		"<br><br><i>Информация об ошибке</i>:<br>"+e.name+": "+e.message, "Связываение документов", {},  'icon_err');
	}

}

function DocHeadInit()
{
	var doc_left_block=document.getElementById("doc_left_block")
	
	doc_left_block.doSave=function()
	{
		if(doc_left_block.timeout)	window.clearTimeout(doc_left_block.timeout)
		doc_left_block.timeout=window.setTimeout(doc_left_block.Save, 2000)
	}
	
	doc_left_block.Save=function()
	{
		doc_left_block.style.backgroundColor='#ff0'
		$.ajax({ 
			type:   'GET', 
			url:    '/doc.php', 
			data:   $('#doc_head_form').serialize(), 
			success: function(msg) { rcvDataSuccess(msg); }, 
			error:   function() { jAlert('Ошибка соединения!','Сохранение данных',null,'icon_err'); }, 
		});
	}
	
	function rcvDataSuccess(msg)
	{
		try
		{
			
			if(doc_left_block.timeout)	window.clearTimeout(doc_left_block.timeout)
			doc_left_block.timeout=window.setTimeout(function(){doc_left_block.style.backgroundColor=''}, 2000)
 			var json=eval('('+msg+')');
			if(json.response=='err')
			{
				doc_left_block.style.backgroundColor='#f00'
				jAlert(json.message,"Ошибка", {}, 'icon_err');
			}
			else if(json.response=='ok')
			{
				doc_left_block.style.backgroundColor='#0f0'
				document.getElementById("agent_balance_info").innerHTML=json.agent_balance
			}
			else
			{
				doc_left_block.style.backgroundColor='#f00'
				jAlert("Обработка полученного сообщения не реализована<br>"+msg, "Изменение списка товаров", null,  'icon_err');
			}
		}
		catch(e)
		{
			doc_left_block.style.backgroundColor='#f00'
			jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!"+
			"<br><br><i>Информация об ошибке</i>:<br>"+e.name+": "+e.message+"<br>"+msg, "Вставка строки в документ", null,  'icon_err');
		}	
	}
	
	function DocHeadFieldClick()
	{
		doc_left_block.doSave()

	}
	
	function SetEvents(obj)
	{
		obj.onclick=function(event)
		{
			if(doc_left_block.timeout)	window.clearTimeout(doc_left_block.timeout)
		}
		obj.onblur=function(event)
		{
			if(doc_left_block.timeout)	window.clearTimeout(doc_left_block.timeout)
			doc_left_block.timeout=window.setTimeout(doc_left_block.Save, 500)
		}
		obj.onсhange=function(event)
		{
			if(doc_left_block.timeout)	window.clearTimeout(doc_left_block.timeout)
			doc_left_block.timeout=window.setTimeout(doc_left_block.Save, 100)
		}
		obj.onkeyup=function(event)
		{
			if(doc_left_block.timeout)	window.clearTimeout(doc_left_block.timeout)
			doc_left_block.timeout=window.setTimeout(doc_left_block.Save, 5000)
		}
	}
	
	var fields=doc_left_block.getElementsByTagName('input')
	for(var i=0; i<fields.length; i++)	SetEvents(fields[i])
	var fields=doc_left_block.getElementsByTagName('select')
	for(var i=0; i<fields.length; i++)	SetEvents(fields[i])
	var fields=doc_left_block.getElementsByTagName('textarea')
	for(var i=0; i<fields.length; i++)	SetEvents(fields[i])
}

function DocLeftToggle(_this)
{
	var doc_left_block=document.getElementById("doc_left_block")
	var doc_main_block=document.getElementById("doc_main_block")
	var doc_left_arrow=document.getElementById("doc_left_arrow")
	if(doc_left_block.style.display!='none')
	{
		doc_left_block.style.display='none'
		doc_main_block.oldmargin=doc_main_block.style.marginLeft
		doc_main_block.style.marginLeft=0
		doc_left_arrow.src='/img/i_rightarrow.png'
	}
	else
	{
		doc_left_block.style.display=''
		doc_main_block.style.marginLeft=doc_main_block.oldmargin
		doc_left_arrow.src='/img/i_leftarrow.png'
	}
}


// Сообщения

// function MsgGet()
// {
// 	url='/message.php?mode=qmsgr';
// 	var httpRequest;
// 	if (window.XMLHttpRequest)  {
// 	httpRequest = new XMLHttpRequest(); }
// 	
// 	if (!httpRequest) { return false; }
// 	httpRequest.onreadystatechange = function() { MsgProcess(httpRequest); };
// 	httpRequest.open('GET', url, true);
// 	httpRequest.send(null);
// 	window.setTimeout("MsgGet()", 30000);
// }
// 
// function MsgProcess(httpRequest)
// {
// 	if (httpRequest.readyState == 4)
// 	{
// 		if (httpRequest.status == 200)
// 		{
// 			var json=eval('('+httpRequest.responseText+')');
// 			if(json.response==1)
// 			{
// 				jAlert(json.message, json.head, {} );	
// 			}
// 			
// 		}
// 	}
// }
// 
// 
// window.setTimeout("MsgGet()", 2500);


