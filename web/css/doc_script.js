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
					if(json.message)	jAlert(json.message,"Сделано!", {});
					if(json.buttons)	provodki.innerHTML=json.buttons;
					else			provodki.innerHTML=old_provodki;
					
					if(json.sklad_editor)
					{
						
						var sklad_editor=document.getElementById("sklad_editor");
						if(sklad_editor)
						{
							if(json.sklad_editor=='show')	sklad_editor.style.display='table';
							else				sklad_editor.style.display='none';	
						}
					}
					var statusblock=document.getElementById("statusblock");
					if( json.statusblock && statusblock) statusblock.innerHTML=json.statusblock;
					
					// заглушка.
					var poslist=document.getElementById("poslist");
					if(json.poslist == 'refresh' && poslist)
						EditThis('/doc.php?mode=srv&opt=poslist&doc='+doc+'&pos=0','poslist');
					
				}
				else provodki.innerHTML=old_provodki;
			}
			else
			{
				jAlert("Документ не проведён!","Ошибка "+httpRequest.status, {}, 'icon_err');
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

// Редактор серийных номеров
function ShowSnEditor(doc, line)
{
	$.ajax({ 
		type:   'GET', 
		url:    '/doc.php', 
		data:   'doc='+doc+'&mode=srv&opt=sn&doc='+doc+'&pos='+line,
		success: function(msg) { ShowSnEditorSuccess(msg, doc, line); }, 
		error:   function() { jAlert('Ошибка!','Редактор серийного номера',{},'icon_err'); }, 
	});
}

function ShowSnEditorSuccess(msg, doc, line)
{
	jAlert(msg,"Редактор серийных номеров", function() { EditThis('/doc.php?mode=srv&opt=poslist&doc='+doc,'poslist'); });
	
	$("#sn").autocomplete("/doc.php", {
		delay:300,
		minChars:1,
		matchSubset:1,
		autoFill:false,
		selectFirst:true,
		matchContains:1,
		cacheLength:10,
		maxItemsToShow:15, 
		extraParams:{'mode':'srv','opt':'sns', 'doc': doc, 'pos': line}
	});

}

function DocSnAdd(doc,pos_id)
{
	var sn=document.getElementById("sn");
	$.ajax({ 
		type:   'GET', 
		url:    '/doc.php', 
		data:   'doc='+doc+'&mode=srv&opt=sns&doc='+doc+'&pos='+pos_id+'&sn='+sn.value, 
		success: function(msg) { DocAddSnSuccess(msg); }, 
		error:   function() { jAlert('Ошибка!','Добавление серийного номера',{},'icon_err'); }, 
	});

}

function DocAddSnSuccess(msg)
{
	try
	{
		var json=eval('('+msg+')')
		if(json.response==0)
			jAlert(json.message,"Ошибка", {}, 'icon_err')
		else if(json.response==1)	// Добавлено
		{
			var sn_list=document.getElementById("sn_list")
			var row=document.createElement('tr')
			row.id='snl'+json.sn_id
			row.innerHTML="<td><img src='/img/i_del.png' alt='"+json.sn_id+"'></td><td>"+json.sn+"</td>"
			sn_list.appendChild(row)
		}	
	}
	catch(e)
	{
		jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!"+
		"<br><br><i>Информация об ошибке</i>:<br>"+e.name+": "+e.message, "Добавление серийного номера", {},  'icon_err');
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


