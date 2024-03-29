// javascript module for document system
// This is part of "MultiMag" system
// Copyright 2009-2015, TND Project
// This file distributed under GPLv3 license

var old_provodki='';

function ApplyDoc(doc) {
    var httpRequest;
    if (window.XMLHttpRequest) {
        httpRequest = new XMLHttpRequest();
    }
    if (!httpRequest) {
        return false;
    }

    var provodki = document.getElementById("provodki");
    old_provodki = provodki.innerHTML;
    provodki.innerHTML = "<img src='/img/icon_load.gif'> Загрузка...";

    var url = '/doc.php?mode=applyj&doc=' + doc;
    httpRequest.onreadystatechange = function () {
        DocProcessRequest(httpRequest, doc);
    };
    httpRequest.open('GET', url, true);
    httpRequest.send(null);
}

function MarkDelDoc(doc) {
    var httpRequest;
    if (window.XMLHttpRequest) {
        httpRequest = new XMLHttpRequest();
    }
    if (!httpRequest) {
        return false;
    }

    var provodki = document.getElementById("provodki");
    old_provodki = provodki.innerHTML;
    provodki.innerHTML = "<img src='/img/icon_load.gif'> Загрузка...";

    var url = '/doc.php?mode=srv&opt=jdeldoc&doc=' + doc;
    httpRequest.onreadystatechange = function () {
        DocProcessRequest(httpRequest, doc);
    };
    httpRequest.open('GET', url, true);
    httpRequest.send(null);
}

function unMarkDelDoc(doc) {
    var httpRequest;
    if (window.XMLHttpRequest) {
        httpRequest = new XMLHttpRequest();
    }
    if (!httpRequest) {
        return false;
    }

    var provodki = document.getElementById("provodki");
    old_provodki = provodki.innerHTML;
    provodki.innerHTML = "<img src='/img/icon_load.gif'> Загрузка...";

    var url = '/doc.php?mode=srv&opt=jundeldoc&doc=' + doc;
    httpRequest.onreadystatechange = function () {
        DocProcessRequest(httpRequest, doc);
    };
    httpRequest.open('GET', url, true);
    httpRequest.send(null);
}

function CancelDoc(doc) {
    var httpRequest;
    if (window.XMLHttpRequest) {
        httpRequest = new XMLHttpRequest();
    }
    if (!httpRequest) {
        return false;
    }

    var provodki = document.getElementById("provodki");
    old_provodki = provodki.innerHTML;
    provodki.innerHTML = "<img src='/img/icon_load.gif'> Загрузка...";

    var url = '/doc.php?mode=cancelj&doc=' + doc;
    httpRequest.onreadystatechange = function () {
        DocProcessRequest(httpRequest, doc);
    };
    httpRequest.open('GET', url, true);
    httpRequest.send(null);

}



function DocProcessRequest(httpRequest, doc) {
    var req;
    try {
        var provodki = document.getElementById("provodki");
        if (httpRequest.readyState == 4) {
            if (httpRequest.status == 200) {
                req = httpRequest.responseText;
                var json = eval('(' + httpRequest.responseText + ')');
                if (json.response == 0) {
                    jAlert(json.message, "Ошибка", null, 'icon_err');
                    provodki.innerHTML = old_provodki;
                }
                else if (json.response == 1) {	// Проведение                
                    if (json.message)
                        jAlert(json.message, "Сделано!", null);
                    if (json.buttons)
                        provodki.innerHTML = json.buttons;
                    else
                        provodki.innerHTML = old_provodki;

                    if (json.sklad_view) {
                        var sklad_view = document.getElementById("storeview_container");
                        var poslist = document.getElementById('poslist');
                        var pladd = document.getElementById('pladd');
                        if (sklad_view) {
                            if (json.sklad_view === 'show') {
                                sklad_view.style.display = 'block';
                                poslist.editable = 1;
                                poslist.refresh();
                                pladd.style.display = 'table-row';
                            }
                            else {
                                sklad_view.style.display = 'none';
                                pladd.style.display = 'none';
                                poslist.editable = 0;
                                poslist.refresh();
                            }
                        }
                    }
                    var statusblock = document.getElementById("statusblock");
                    if (json.statusblock && statusblock)
                        statusblock.innerHTML = json.statusblock;
                }
                else
                    provodki.innerHTML = old_provodki;
            }
            else {
                jAlert("Документ не проведён!" + httpRequest.responseText, "Ошибка " + httpRequest.status, null, 'icon_err');
                provodki.innerHTML = old_provodki;
            }
        }
    }
    catch (e) {
        jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!" +
                "<br><br><i>Информация об ошибке</i>:<br>" + e.name + ": " + e.message + "<br>json:<br>" + req, "Критическая ошибка", null, 'icon_err');
    }
    //else jAlert("Документ не проведён!","Ошибка "+httpRequest.readyState, {}, 'icon_err');
}


// Установка / снятие связи
function DocConnect(doc, p_doc) {
    jPrompt("Укажите <b>системный</b> номер документа,<br>потомком которого должен стать<br>текущий документ:",
        p_doc,"Связываение документов",  function(result) { DocConnectCallback(doc, result); });
}

function DocConnectCallback(doc, result) {
    if(result===null)	return;
    $.ajax({
        type:   'POST',
        url:    '/doc.php',
        data:   'doc='+doc+'&mode=conn&p_doc='+result,
        success: function(msg) { DocConnectProcess(msg); },
        error:   function() { jAlert('Ошибка соединения!','Связываение документов',null,'icon_err'); }
    });
}

function DocConnectProcess(msg) {
    try {
        var json = JSON.parse(msg);
        if(json.response==='error') {
            jAlert(json.message,"Ошибка", null, 'icon_err');
        }
        else if(json.response==='connect_ok') {	// Связывание
            if(json.message)	jAlert(json.message,"Связываение документов", null);
            else			jAlert("Сделано!","Связываение документов", null);
        }
    }
    catch(e) {
        jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!"+
        "<br><br><i>Информация об ошибке</i>:<br>"+e.name+": "+e.message, "Связываение документов", null,  'icon_err');
    }
}


function hlThisRow(event) {
    var obj = event.target;
    while (obj != 'undefined' && obj != 'null') {
        if (obj.tagName == 'TR') {
            if (!obj.marked) {
                obj.style.backgroundColor = '#8f8';
                obj.marked = 1;
            }
            else {
                obj.style.backgroundColor = '';
                obj.marked = 0;
            }
            return;
        }
        obj = obj.parentNode;
    }
}

function DocHeadInit()
{
    var doc_left_block = document.getElementById("doc_left_block");
    var doc_menu_container = document.getElementById("doc_menu_container");
    var reset_cost = document.getElementById("reset_cost");

    var lock_blur = 0;
    var oldbg = doc_left_block.style.backgroundColor;

    doc_left_block.changing = 0;

    doc_left_block.Save = function () {
        doc_left_block.style.backgroundColor = '#ff8';
        $.ajax({
            type: 'POST',
            url: '/doc.php',
            data: $('#doc_head_form').serialize(),
            success: function (msg) {
                rcvDataSuccess(msg);
            },
            error: function () {
                jAlert('Ошибка соединения!', 'Сохранение данных', null, 'icon_err');
            }
        });
    };

    doc_left_block.StartEdit = function () {
        doc_left_block.changing = 1;
        doc_menu_container.style.display = 'none';
        if (reset_cost)
            reset_cost.style.display = 'none';
        if (doc_left_block.timeout)
            window.clearTimeout(doc_left_block.timeout);
    };

    doc_left_block.FinistEdit = function () {
        doc_left_block.changing = 0;
        doc_menu_container.style.display = '';
        if (reset_cost)
            reset_cost.style.display = '';
    };

    function rcvDataSuccess(msg) {
        try {
            if (doc_left_block.timeout)
                window.clearTimeout(doc_left_block.timeout);
            var alfa = 255;
            doc_left_block.timeout = window.setTimeout(function () {
                doc_left_block.style.backgroundColor = ''
            }, 2000)
            var json = eval('(' + msg + ')');
            if (json.response == 'err')
            {
                doc_left_block.style.backgroundColor = '#f00'
                var errdiv = document.createElement('div')
                doc_left_block.insertBefore(errdiv, doc_left_block.firstChild)
                errdiv.className = 'err'
                errdiv.style.backgroundColor = '#000'
                errdiv.innerHTML = '<b>Ошибка сохранения</b><br>' + json.text
            }
            else if (json.response == 'ok')
            {
                doc_left_block.style.backgroundColor = '#bfa'
                var agent_balance_info = document.getElementById("agent_balance_info")
                if (agent_balance_info)
                {
                    agent_balance_info.innerHTML = json.agent_balance
                    if (json.agent_balance > 0)
                        agent_balance_info.style.color = '#f00'
                    else if (json.agent_balance < 0)
                        agent_balance_info.style.color = '#080'
                    else
                        agent_balance_info.style.color = ''
                }
            }
            else
            {
                doc_left_block.style.backgroundColor = '#f00'
                jAlert("Обработка полученного сообщения не реализована<br>" + msg, "Изменение списка товаров", null, 'icon_err');
            }
            doc_left_block.FinistEdit()
        }
        catch (e)
        {
            doc_left_block.style.backgroundColor = '#f00'
            jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!" +
                    "<br><br><i>Информация об ошибке</i>:<br>" + e.name + ": " + e.message + "<br>" + msg, "Сохранение заголовка", null, 'icon_err');
        }
    }

    function obj_onclick(event) {
        doc_left_block.StartEdit()
        doc_left_block.timeout = window.setTimeout(doc_left_block.Save, 30000) // на всякий случай
    }

    function obj_onmousedown(event)
    {
        doc_left_block.StartEdit()
        doc_left_block.timeout = window.setTimeout(doc_left_block.Save, 30000) // на всякий случай
        // Хак для предотвращения отправки формы по onblur, если фокус готовится быть переданным на select и др элемент
        lock_blur = 1
        window.setTimeout(function () {
            lock_blur = 0
        }, 60)
    }

    obj_onblur = function (event)
    {
        if (lock_blur)
            return
        if (doc_left_block.timeout)
            window.clearTimeout(doc_left_block.timeout)
        doc_left_block.timeout = window.setTimeout(doc_left_block.Save, 500)
    }
    obj_onkeyup = function (event)
    {
        if (doc_left_block.timeout)
            window.clearTimeout(doc_left_block.timeout)
        //doc_left_block.timeout=window.setTimeout(doc_left_block.Save, 3000)
    }

    doc_left_block.SetEvents = function (obj)
    {
        obj.addEventListener('mousedown', obj_onmousedown, false)
        obj.addEventListener('click', obj_onclick, false)
        obj.addEventListener('blur', obj_onblur, false)
        obj.addEventListener('keyup', obj_onkeyup, false)
    }

    var fields = doc_left_block.getElementsByTagName('input')
    for (var i = 0; i < fields.length; i++)
        doc_left_block.SetEvents(fields[i])
    var fields = doc_left_block.getElementsByTagName('select')
    for (var i = 0; i < fields.length; i++)
        doc_left_block.SetEvents(fields[i])
    var fields = doc_left_block.getElementsByTagName('textarea')
    for (var i = 0; i < fields.length; i++)
        doc_left_block.SetEvents(fields[i])

    initCalendar("datetime", true);

    if (supports_html5_storage())
    {
        if (localStorage['doc_left_block_hidden'] == 'hidden')
        {
            var doc_left_block = document.getElementById("doc_left_block")
            var doc_main_block = document.getElementById("doc_main_block")
            var doc_left_arrow = document.getElementById("doc_left_arrow")
            doc_left_block.style.display = 'none'
            doc_main_block.oldmargin = doc_main_block.style.marginLeft
            doc_main_block.style.marginLeft = 0
            doc_left_arrow.src = '/img/i_rightarrow.png'
        }
    }
    
    function globalKeyListener(event) {
        var e = event || window.event;
        if (e.keyCode == 27) {
            window.close();
        } else if (e.keyCode == 112) {
            window.open("http://multimag.tndproject.org/wiki/userdoc");
        }
    }
    addEventListener('keyup', globalKeyListener, false);
}

function DocLeftToggle(_this)
{
	var doc_left_block=document.getElementById("doc_left_block")
	var doc_main_block=document.getElementById("doc_main_block")
	var doc_left_arrow=document.getElementById("doc_left_arrow")
	var state
	if(doc_left_block.style.display!='none')
	{
		doc_left_block.style.display='none'
		doc_main_block.oldmargin=doc_main_block.style.marginLeft
		doc_main_block.style.marginLeft=0
		doc_left_arrow.src='/img/i_rightarrow.png'
		state='hidden'
	}
	else
	{
		doc_left_block.style.display=''
		doc_main_block.style.marginLeft=doc_main_block.oldmargin
		doc_left_arrow.src='/img/i_leftarrow.png'
		state='show'
	}
	if(supports_html5_storage())
	{
		localStorage['doc_left_block_hidden']=state
	}
}


function ResetCost(doc)
{
	$.ajax({
		type:   'GET',
		url:    '/doc.php',
		data:   'mode=srv&peopt=jrc&doc='+doc,
		success: function(msg) { document.getElementById('poslist').refresh(); jAlert('Цены обновлены успешно!',"Сделано!", function() {}); },
		error:   function() { jAlert('Ошибка соединения!','Сохранение данных',null,'icon_err'); },
	});
}


function UpdateContractInfo(doc, firm_id, agent_id)
{
	function rcvDataSuccess(msg)
	{
		try
		{
 			var json=eval('('+msg+')');
			if(json.response=='err')
			{
				doc_left_block.style.backgroundColor='#f00'
				jAlert(json.text,"Ошибка", {}, 'icon_err');
			}
			else if(json.response=='contract_list')
			{
				var agent_contract=document.getElementById("agent_contract")
				var str=''
				var cnt=0
				for(var i=0;i<json.content.length;i++)
				{
					str=str+"<option value='"+json.content[i].id+"'>N"+json.content[i].id+":"+json.content[i].name+"</option>"
					cnt++
				}
				if(cnt)
				{
					agent_contract.innerHTML="Договор:<br><select name='contract' id='contract_select'>"+str+"</select>"
					document.getElementById("doc_left_block").SetEvents(document.getElementById("contract_select"))
				}
				else agent_contract.innerHTML=''
			}
			else
			{
				doc_left_block.style.backgroundColor='#f00'
				jAlert("Обработка полученного сообщения не реализована<br>"+msg, "Изменение списка товаров", null,  'icon_err');
			}
			doc_left_block.FinistEdit()
		}
		catch(e)
		{
			doc_left_block.style.backgroundColor='#f00'
			jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!"+
			"<br><br><i>Информация об ошибке</i>:<br>"+e.name+": "+e.message+"<br>"+msg, "Сохранение", null,  'icon_err');
		}
	}

	$.ajax({
		type:   'GET',
		url:    '/docs.php',
		data:   'l=agent&mode=srv&opt=jgetcontracts&firm_id='+firm_id+'&agent_id='+agent_id,
		success: function(msg) { rcvDataSuccess(msg) },
		error:   function() { jAlert('Ошибка соединения!','Сохранение данных',null,'icon_err'); },
	});
}

function PrintMenu(event,doc)
{
	var menu=CreateContextMenu(event);
	function pickItem(event)
	{
		var fname=event.target.fname;
		menu.parentNode.removeChild(menu);
		window.location="/doc.php?mode=print&doc="+doc+"&opt="+fname;
	}

	function rcvDataSuccess(msg)
	{
		try
		{
			var json=eval('('+msg+')');
			if(json.response=='err')
			{
				jAlert(json.text,"Ошибка", {}, 'icon_err');
				menu.parentNode.removeChild(menu);
			}
			else if(json.response=='item_list')
			{
				menu.innerHTML=''
				for(var i=0;i<json.content.length;i++) {
					var elem = document.createElement('div');
                                        if(json.content[i].mime) {
                                            var mime = json.content[i].mime.replace('/', '-');
                                            elem.style.backgroundImage = "url('/img/mime/22/"+mime+".png')";
                                        }
					elem.innerHTML=json.content[i].desc;
					elem.fname=json.content[i].name;
					elem.onclick=pickItem;
					menu.appendChild(elem);
				}
			}
			else
			{
				jAlert("Обработка полученного сообщения не реализована<br>"+msg, "Печать", {},  'icon_err');
				menu.parentNode.removeChild(menu)
			}
		}
		catch(e)
		{
			jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!"+
			"<br><br><i>Информация об ошибке</i>:<br>"+e.name+": "+e.message+"<br>"+msg, "Печать", {},  'icon_err');
			menu.parentNode.removeChild(menu)
		}
	}

	$.ajax({
		type:   'GET',
	       url:    '/doc.php',
	       data:   'mode=print&doc='+doc,
	       success: function(msg) { rcvDataSuccess(msg) },
	       error:   function() { jAlert('Ошибка соединения!','Печать',{},'icon_err'); menu.parentNode.removeChild(menu);},
	});
	return false
}

function FaxMenu(event, doc) {
    var menu = CreateContextMenu(event)
    var fax_number = ''
    function pickItem(event) {
        var obj = event.target
        menu.innerHTML = ''
        menu.morphToDialog();
        var elem = document.createElement('div')
        elem.innerHTML = 'Номер факса:<br><small>В международном формате +XXXXXXXXXXX...<br>без дефисов, пробелов, и пр.символов</small>'
        menu.appendChild(elem)
        var ifax = document.createElement('input')
        ifax.type = 'text'
        ifax.value = fax_number
        ifax.style.width = '200px'
        menu.appendChild(ifax)
        elem = document.createElement('br')
        menu.appendChild(elem)
        var bcancel = document.createElement('button')
        bcancel.innerHTML = 'Отменить'
        bcancel.onclick = function () {
            menu.parentNode.removeChild(menu)
        }
        menu.appendChild(bcancel)
        var bsend = document.createElement('button')
        bsend.innerHTML = 'Отправить'
        menu.appendChild(bsend)
        bsend.onclick = function () {
            $.ajax({
                type: 'GET',
                url: '/doc.php',
                data: 'mode=fax&doc=' + doc + '&opt=' + event.target.fname + '&faxnum=' + encodeURIComponent(ifax.value),
                success: function (msg) {
                    rcvDataSuccess(msg)
                },
                error: function () {
                    jAlert('Ошибка соединения!', 'Отправка факса', null, 'icon_err');
                    menu.parentNode.removeChild(menu);
                },
            });
            menu.innerHTML = '<img src="/img/icon_load.gif" alt="отправка">Отправка факса...'
        }
        function validate_fax() {
            var regexp = /^\+\d{8,15}$/
            if (!regexp.test(ifax.value)) {
                ifax.style.color = "#f00"
                bsend.disabled = true
            } else {
                ifax.style.color = ""
                bsend.disabled = false
            }
        }
        ifax.onkeyup = validate_fax
        validate_fax()
    }

    function rcvDataSuccess(msg) {
        try {
            var json = eval('(' + msg + ')');
            if (json.response == 'err') {
                jAlert(json.text, "Ошибка", {}, 'icon_err');
                menu.parentNode.removeChild(menu);
            } 
            else if (json.response == 'item_list') {
                menu.innerHTML = ''
                fax_number = json.faxnum
                for (var i = 0; i < json.content.length; i++)
                {
                    var elem = document.createElement('div')
                    if (json.content[i].mime) {
                        var mime = json.content[i].mime.replace('/', '-');
                        elem.style.backgroundImage = "url('/img/mime/22/" + mime + ".png')";
                    }
                    elem.innerHTML = json.content[i].desc
                    elem.fname = json.content[i].name
                    elem.onclick = pickItem
                    menu.appendChild(elem)
                }
            } 
            else if (json.response == 'send') {
                jAlert('Факс успешно отправлен на сервер! Вы получите уведомление по email c результатом отправки получателю!', "Выполнено", {});
                menu.parentNode.removeChild(menu)
            } 
            else {
                jAlert("Обработка полученного сообщения не реализована<br>" + msg, "Отправка факса", {}, 'icon_err');
                menu.parentNode.removeChild(menu)
            }
        } 
        catch (e) {
            jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!" +
                    "<br><br><i>Информация об ошибке</i>:<br>" + e.name + ": " + e.message + "<br>" + msg, "Отправка факса", {}, 'icon_err');
            menu.parentNode.removeChild(menu)
        }
    }

    $.ajax({
        type: 'GET',
        url: '/doc.php',
        data: 'mode=fax&doc=' + doc,
        success: function (msg) {
            rcvDataSuccess(msg)
        },
        error: function () {
            jAlert('Ошибка соединения!', 'Отправка факса', {}, 'icon_err');
            menu.parentNode.removeChild(menu);
        },
    });
    return false
}

function MailMenu(event,doc)
{
	var menu=CreateContextMenu(event)
	var email=''
	function pickItem(event)
	{
		var obj=event.target
		menu.innerHTML=''
		menu.className='contextlayer'
		menu.onmouseover=menu.onmouseout=function() {  }
		if(menu.waitHideTimer) window.clearTimeout(menu.waitHideTimer)
		var elem=document.createElement('div')
		elem.innerHTML='Адрес электронной почты:'
		menu.appendChild(elem)
		var imail=document.createElement('input')
		imail.type='tel'
		imail.value=email
		imail.style.width='200px'
		menu.appendChild(imail)
		elem=document.createElement('div')
		elem.innerHTML='Комментарий:'
		menu.appendChild(elem)
		var mailtext=document.createElement('textarea')
		menu.appendChild(mailtext)
		menu.appendChild(document.createElement('br'))
		var bcancel=document.createElement('button')
		bcancel.innerHTML='Отменить'
		bcancel.onclick=function() {menu.parentNode.removeChild(menu)}
		menu.appendChild(bcancel)
		var bsend=document.createElement('button')
		bsend.innerHTML='Отправить'
		menu.appendChild(bsend)
		bsend.onclick=function()
		{
			$.ajax({
				type:   'GET',
				url:    '/doc.php',
				data:   'mode=email&doc='+doc+'&opt='+event.target.fname+'&email='+encodeURIComponent(imail.value)+'&comment='+encodeURIComponent(mailtext.value),
				success: function(msg) { rcvDataSuccess(msg) },
				error:   function() { jAlert('Ошибка соединения!','Отправка email сообщения',null,'icon_err'); menu.parentNode.removeChild(menu);},
			});
			menu.innerHTML='<img src="/img/icon_load.gif" alt="отправка">Отправка email сообщения...'
		}
	}

	function rcvDataSuccess(msg)
	{
		try
		{
			var json=eval('('+msg+')');
			if(json.response=='err')
			{
				jAlert(json.text,"Ошибка", {}, 'icon_err');
				menu.parentNode.removeChild(menu);
			}
			else if(json.response=='item_list')
			{
				menu.innerHTML=''
				email=json.email
				for(var i=0;i<json.content.length;i++)
				{
					var elem=document.createElement('div')
                                        if(json.content[i].mime) {
                                            var mime = json.content[i].mime.replace('/', '-');
                                            elem.style.backgroundImage = "url('/img/mime/22/"+mime+".png')";
                                        }
					elem.innerHTML=json.content[i].desc
					elem.fname=json.content[i].name
					elem.onclick=pickItem
					menu.appendChild(elem)
				}
			}
			else if(json.response=='send')
			{
				jAlert('Сообщение успешно отправлено!',"Выполнено", {});
				menu.parentNode.removeChild(menu)
			}
			else
			{
				jAlert("Обработка полученного сообщения не реализована<br>"+msg, "Отправка email сообщения", {},  'icon_err');
				menu.parentNode.removeChild(menu)
			}
		}
		catch(e)
		{
			jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!"+
			"<br><br><i>Информация об ошибке</i>:<br>"+e.name+": "+e.message+"<br>"+msg, "Отправка email сообщения", {},  'icon_err');
			menu.parentNode.removeChild(menu)
		}
	}

	$.ajax({
		type:   'GET',
	       url:    '/doc.php',
	       data:   'mode=email&doc='+doc,
	       success: function(msg) { rcvDataSuccess(msg) },
	       error:   function() { jAlert('Ошибка соединения!','Отправка email сообщения',{},'icon_err'); menu.parentNode.removeChild(menu);},
	});
	return false
}

function addNomMenu(event, doc, pdoc_id) {
    var menu = CreateContextMenu(event);
    function showDialog() {
        var obj = event.target;
        menu.innerHTML = "Введите ID документа, из которго нужно<br>загрузить номенклатнурную таблицу:<br>" +
            "<input type='text' id='doc_num_field' value=''><br>" +
            "<fieldset><legend>или выберите из списка</legend><div id='menu_link_div'></div></fieldset>" +
            "<label><input type='checkbox' id='p_clear_cb'> Предварительно очистить текущий документ</label><br>" +
            "<label><input type='checkbox' id='nsum_cb'> Не суммировать количество</label><br><button id='bcancel'>Отменить</button>" +
            "<button id='bok'>Выполнить</button>";
        menu.className = 'contextlayer';
        menu.onmouseover = menu.onmouseout = function () {};
        if (menu.waitHideTimer) {
            window.clearTimeout(menu.waitHideTimer);
        }
        var odoc_num_field = document.getElementById('doc_num_field');
        var op_clear_cb = document.getElementById('p_clear_cb');
        var onsum_cb = document.getElementById('nsum_cb');
        var obok = document.getElementById('bok');
        var obcancel = document.getElementById('bcancel');

        obcancel.onclick = function () {
            menu.parentNode.removeChild(menu);
        };
        obok.onclick = function ()
        {
            var f_clear = op_clear_cb.checked ? 1 : 0;
            var f_sum = onsum_cb.checked ? 1 : 0;
            $.ajax({
                type: 'POST',
                url: '/doc.php',
                data: 'mode=srv&opt=merge&doc=' + doc + '&from_doc=' + odoc_num_field.value + '&clear=' + f_clear + '&no_sum=' + f_sum,
                success: function (msg) {
                    rcvDataSuccess(msg);
                },
                error: function () {
                    jAlert('Ошибка соединения!', 'Объединение номенклатурных таблиц', null, 'icon_err');
                    menu.parentNode.removeChild(menu);
                }
            });
            menu.innerHTML = '<img src="/img/icon_load.gif" alt="Загрузка">Загрузка...';
        };
        
        $.ajax({
            type: 'POST',
            url: '/doc.php',
            data: 'mode=srv&opt=link_info&doc=' + doc,
            success: function (msg) {
                rcvDataSuccess(msg);
            },
            error: function () {
                jAlert('Ошибка соединения!', 'Объединение номенклатурных таблиц', null, 'icon_err');
                menu.parentNode.removeChild(menu);
            }
        });
    }
    
    function selectNum(event) {
        var odoc_num_field = document.getElementById('doc_num_field');
        odoc_num_field.value = event.target.doc_id;
    }

    function rcvDataSuccess(msg) {
        try {
            var json = JSON.parse(msg);
            if (json.response == 'err') {
                jAlert(json.text.json.message, "Ошибка", {}, 'icon_err');
                menu.parentNode.removeChild(menu);
            }
            else if (json.response == 'merge_ok') {
                jAlert('Таблица загружена', "Выполнено", {});
                menu.parentNode.removeChild(menu)
                poslist.refresh();
            }
            else if(json.response == 'link_info') {
                var menu_link_div = document.getElementById('menu_link_div');
                menu_link_div.innerHTML = '';
                if(json.parent) {
                    var elem=document.createElement('a');
                    elem.href='#';
                    elem.doc_id = json.parent.id;
                    elem.innerHTML = 'От: ' + json.parent.name + ' ' + json.parent.altnum + json.parent.subtype + ' от ' + json.parent.vdate + ' на сумму ' +  json.parent.sum;
                    elem.onclick = selectNum;
                    elem.style.display = 'block';
                    menu_link_div.appendChild(elem);
                }
                if(json.childs) {
                    for(var i=0;i<json.childs.length;i++) {
                        var doc_info = json.childs[i];
                        var elem=document.createElement('a');
                        elem.href='#';
                        elem.doc_id = doc_info.id;
                        elem.innerHTML = 'К: ' + doc_info.name + ' ' + doc_info.altnum + doc_info.subtype + ' от ' + doc_info.vdate + ' на сумму ' +  doc_info.sum;
                        elem.onclick = selectNum;
                        elem.style.display = 'block';
                        menu_link_div.appendChild(elem);
                    }
                }
            }
            else {
                jAlert("Обработка полученного сообщения не реализована<br>" + msg, "Отправка сообщения", {}, 'icon_err');
                menu.parentNode.removeChild(menu)
            }
        }
        catch (e) {
            jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!" +
                "<br><br><i>Информация об ошибке</i>:<br>" + e.name + ": " + e.message + "<br>" + msg, "Объединение номенклатурных таблиц", {}, 'icon_err');
            menu.parentNode.removeChild(menu)
        }
    }

    showDialog();
    
    return false;
}

function deliveryMenu(event, doc) {
    var menu=CreateContextMenu(event);
    menu.innerHTML = '';
    var elem = document.createElement('div');
    elem.innerHTML = 'Отправка';
    elem.onclick = pickItemShipment;
    menu.appendChild(elem);
    elem = document.createElement('div');
    elem.innerHTML = 'Отгрузка самовывозом';
    elem.onclick = pickItemSelf;
    menu.appendChild(elem);
    
    function pickItemShipment(event) {
        menu.parentNode.removeChild(menu);
        addShipDataDialog(event, doc);
    }

    function pickItemSelf(event) {
        $.ajax({
            type: 'POST',
            url: '/doc.php',
            data: 'mode=srv&doc=' + doc + '&opt=selfship',
            success: function (msg) {
                docScriptsServerDataReceiver(msg, menu);
            },
            error: function () {
                jAlert('Ошибка соединения!', 'Сохранение', null, 'icon_err');
                menu.parentNode.removeChild(menu);
            }
        });
        menu.innerHTML = '<img src="/img/icon_load.gif" alt="Сохранение">Сохранение...';
    }
    return false;
}

function addShipDataDialog(event, doc) {
    var menu = CreateContextMenu(event);
    var cc_name = document.getElementById('cc_name');
    var cc_num = document.getElementById('cc_num');
    var cc_price = document.getElementById('cc_price');
    var cc_date = document.getElementById('cc_date');
    var cc_mass = document.getElementById('cc_mass');
    var cc_volume = document.getElementById('cc_volume');
    
    function showDialog() {
        var obj = event.target;
        menu.innerHTML = "<fieldset><legend>Оповещение об отправке</legend>" +
            "Транспортная компания:<br>" +
            "<input type='text' id='cc_name' value=''><br>" +
            "Номер транспортной накладной:<br>" +
            "<input type='text' id='cc_num' value=''><br>" +
            "Масса (кг), по транспортной накладной:<br>" +
            "<input type='text' id='cc_mass' value=''><br>" +
            "Объём (М<sup>3</sup>), по транспортной накладной:<br>" +
            "<input type='text' id='cc_volume' value=''><br>" +
            "Стоимость доставки:<br>" +
            "<input type='text' id='cc_price' value=''><br>" +
            "Дата отправки:<br>" +
            "<input type='text' id='cc_date' value=''></fieldset><br>" +
            "<button id='bcancel'>Отменить</button>" + 
            "<button id='bok'>Сохраниить и сменить статус</button>";
        menu.className = 'contextlayer';
        menu.onmouseover = menu.onmouseout = function () {};
        if (menu.waitHideTimer) {
            window.clearTimeout(menu.waitHideTimer);
        }
        initCalendar("cc_date", false);
        
        cc_name = document.getElementById('cc_name');
        cc_num = document.getElementById('cc_num');
        cc_price = document.getElementById('cc_price');
        cc_date = document.getElementById('cc_date');
        cc_mass = document.getElementById('cc_mass');
        cc_volume = document.getElementById('cc_volume');
        
        var obcancel = document.getElementById('bcancel');
        var obok = document.getElementById('bok');
        

        obcancel.onclick = function () {
            menu.parentNode.removeChild(menu);
        };
        obok.onclick = function () {
            var url_data = '&cc_name='+encodeURIComponent(cc_name.value) + 
                    '&cc_num='+encodeURIComponent(cc_num.value) + 
                    '&cc_price='+encodeURIComponent(cc_price.value) + 
                    '&cc_mass='+encodeURIComponent(cc_mass.value) + 
                    '&cc_volume='+encodeURIComponent(cc_volume.value) + 
                    '&cc_date='+encodeURIComponent(cc_date.value);
            $.ajax({
                type: 'POST',
                url: '/doc.php',
                data: 'mode=srv&opt=ship_enter&doc=' + doc + url_data,
                success: function (msg) {
                    rcvDataSuccess(msg);
                },
                error: function () {
                    jAlert('Ошибка соединения!', 'Сохранение транспортной информации', null, 'icon_err');
                    menu.parentNode.removeChild(menu);
                }
            });
            menu.innerHTML = '<img src="/img/icon_load.gif" alt="Загрузка">Загрузка...';
        };
        $.ajax({
            type: 'POST',
            url: '/doc.php',
            data: 'mode=srv&opt=ship_info&doc=' + doc,
            success: function (msg) {
                rcvDataSuccess(msg);
            },
            error: function () {
                jAlert('Ошибка соединения!', 'Сохранение транспортной информации', null, 'icon_err');
                menu.parentNode.removeChild(menu);
            }
        });
    }
  
    function rcvDataSuccess(msg) {
        try {
            var json = JSON.parse(msg);
            if (json.response == 'err') {
                jAlert(json.text.json.message, "Ошибка", {}, 'icon_err');
                menu.parentNode.removeChild(menu);
            }
            else if (json.response == 'ship_enter') {
                jAlert('Информация сохранена', "Выполнено", {});
                menu.parentNode.removeChild(menu)
            }
            else if(json.response == 'ship_info') {
                cc_name.value = json.name;
                cc_num.value = json.num;
                cc_price.value = json.price;
                cc_date.value = json.date;
                cc_mass.value = json.mass;
                cc_volume.value = json.volume;
            }
            else {
                jAlert("Обработка полученного сообщения не реализована<br>" + msg, "Отправка сообщения", {}, 'icon_err');
                menu.parentNode.removeChild(menu)
            }
        }
        catch (e) {
            jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!" +
                    "<br><br><i>Информация об ошибке</i>:<br>" + e.name + ": " + e.message + "<br>" + msg, "Объединение номенклатурных таблиц", {}, 'icon_err');
            menu.parentNode.removeChild(menu)
        }
    }

    showDialog();
    
    return false;
}

function msgMenu(event, doc) {
    var menu = CreateContextMenu(event);
    function showDialog() {
        var obj = event.target
        menu.innerHTML = "<div>Текст сообщения:</div><textarea id='mailtext'></textarea><br><label><input type='checkbox' id='sendmail' checked> Отправить по email</label><br><label><input type='checkbox' id='sendsms' checked> Отправить по sms</label><br><button id='bcancel'>Отменить</button><button id='bsend'>Отправить</button>";
        menu.className = 'contextlayer';
        menu.onmouseover = menu.onmouseout = function () {
        }
        if (menu.waitHideTimer)
            window.clearTimeout(menu.waitHideTimer)
        var otext = document.getElementById('mailtext');
        var ocmail = document.getElementById('sendmail');
        var ocsms = document.getElementById('sendsms');
        var obsend = document.getElementById('bsend');
        var obcancel = document.getElementById('bcancel');

        obcancel.onclick = function () {
            menu.parentNode.removeChild(menu);
        };
        obsend.onclick = function () {
            var mail = ocmail.checked ? 1 : 0;
            var sms = ocsms.checked ? 1 : 0;
            $.ajax({
                type: 'POST',
                url: '/doc.php',
                data: 'mode=srv&doc=' + doc + '&opt=pmsg&mail=' + mail + '&sms=' + sms + '&text=' + encodeURIComponent(otext.value),
                success: function (msg) {
                    docScriptsServerDataReceiver(msg, menu);
                },
                error: function () {
                    jAlert('Ошибка соединения!', 'Отправка сообщения', null, 'icon_err');
                    menu.parentNode.removeChild(menu);
                }
            });
            menu.innerHTML = '<img src="/img/icon_load.gif" alt="отправка">Отправка сообщения...';
        };
    }
    showDialog();
    return false;
}

function sendPie(event, doc) {
    var menu = CreateContextMenu(event);
    menu.className = 'contextlayer';
    menu.onmouseover = menu.onmouseout = function () {
    };
    if (menu.waitHideTimer)
        window.clearTimeout(menu.waitHideTimer);
    $.ajax({
        type: 'POST',
        url: '/doc.php',
        data: 'mode=srv&doc=' + doc + '&opt=pie',
        success: function (msg) {
            docScriptsServerDataReceiver(msg, menu);
        },
        error: function () {
            jAlert('Ошибка соединения!', 'Отправка сообщения', null, 'icon_err');
            menu.parentNode.removeChild(menu);
        }
    });
    menu.innerHTML = '<img src="/img/icon_load.gif" alt="отправка">Отправка сообщения...';
    return false;
}

function petitionMenu(event, doc) {
    $.alerts._hide();
    var menu = CreateContextMenu(event);
    function showDialog() {
        menu.innerHTML = "<fieldset><legend>Запрос на отмену документа</legend><div>Причина запроса:</div><textarea id='mailtext'></textarea><br><br><button id='bcancel'>Отменить</button><button id='bsend'>Отправить</button></fieldset>";
        menu.className = 'contextlayer';
        menu.onmouseover = menu.onmouseout = function () {
        };
        if (menu.waitHideTimer) {
            window.clearTimeout(menu.waitHideTimer);
        }
        var otext = document.getElementById('mailtext');
        var obsend = document.getElementById('bsend');
        var obcancel = document.getElementById('bcancel');

        obcancel.onclick = function () {
            menu.parentNode.removeChild(menu);
        };
        obsend.onclick = function () {
            $.ajax({
                type: 'POST',
                url: '/doc.php',
                data: 'mode=srv&doc=' + doc + '&opt=petition&text=' + encodeURIComponent(otext.value),
                success: function (msg) {
                    docScriptsServerDataReceiver(msg, menu);
                },
                error: function () {
                    jAlert('Ошибка соединения!', 'Отправка сообщения', null, 'icon_err');
                    menu.parentNode.removeChild(menu);
                }
            });
            menu.innerHTML = '<img src="/img/icon_load.gif" alt="отправка">Отправка сообщения...';
        };
    }
    showDialog();
    return false;
}

function toggleReserve(event, doc) {
    event.target.src = '/img/icon_load.gif';
    $.ajax({
        type: 'POST',
        url: '/doc.php',
        data: 'mode=srv&doc=' + doc + '&opt=togglereserve',
        success: function (msg) {
            docScriptsServerDataReceiver(msg, null, event);
        },
        error: function () {
            jAlert('Ошибка соединения!', 'Переключение резервов', null, 'icon_err');
        }
    });
    return false;
}

function docScriptsServerDataReceiver(msg, menu, obj) {
    try {
        var json = JSON.parse(msg);
        if (json.response == 'error') {
            jAlert(json.errormessage, "Ошибка", null, 'icon_err');
        }
        else if (json.response == 'success') {
            switch (json.object) {
                case 'send_message':
                    jAlert('Ваше сообщение успешно отправлено!', "Выполнено", {});
                    break;
                case 'send_pie':
                    jAlert('Сообщение-приглашение успешно отправлено!', "Выполнено", {});
                    break;
                case 'send_petition':
                    jAlert('Ваше сообщение успешно отправлено!<br>'+json.message, "Выполнено", {});
                    break;
                case 'selfship':
                    jAlert(json.message, "Выполнено", {});
                    break;
                case 'togglereserve':
                    jAlert(json.message, "Выполнено", {});
                    var un = '';
                    var alt = 'Разрешить резервы';
                    if(json.reserved == 0) {
                        un = 'un';
                        alt = 'Снять резервы';
                    }
                    obj.target.src = '/img/22x22/object-'+un+'locked.png';
                    obj.target.alt = alt;
                    obj.target.parentNode.title = alt;
                    var poslist=document.getElementById('poslist');
                    if(poslist) {
                        poslist.refresh();
                    }
                    break;
                case 'getheader':
                    obj.dispatchGetHeader(json.content);
                    break;
                default:
                    jAlert("Обработка полученного сообщения не реализована на стороне броузера!<br>"
                        + "Рекомендуется сообщить о проблеме администратору!<br>"
                        + msg, "Ошибка обработки "+json.object, {}, 'icon_err');
            }
        }
        else {
            jAlert("Получен неизвестный ответ!<br>" + msg, "Отправка сообщения", {}, 'icon_err');                
        }
        if(menu) {
            menu.parentNode.removeChild(menu);
        }
    }
    catch (e) {
        jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!" +
                "<br><br><i>Информация об ошибке</i>:<br>" + e.name + ": " + e.message + "<br>" + msg, "Ошибка", null, 'icon_err');
        if(menu) {
            menu.parentNode.removeChild(menu);
        }
    }
}

function newDynamicDocHeader(container_id, doc_id) {
    var dh = new Object;
    var container = document.getElementById(container_id);
    httpReq('/doc.php', 'GET', 'mode=srv&doc=' + doc_id + '&peopt=getheader', function (msg) {
            docScriptsServerDataReceiver(msg, null, dh);
        }, function () {
            jAlert('Ошибка соединения!', 'Загрузка шапки', null, 'icon_err');
        }
    );
    
    dh.dispatchGetHeader = function(data) {
        container.innerHTML = 
            "<div class='doc_name'>" + "<div class='idnum'>ID:" + data.id +"</div>" + data.viewname +"</div>"
            + "<div class='doc_numdate'>"
            + "<div class='doc_number'><label for='doc_number'>Номер:</label><br><input type='text' id='doc_number' value='"+data.altnum+"'></div>"
            + "<div class='doc_subtype'>Постфикс:<br><input type='text' value='"+data.subtype+"'></div>"
            + "<span class='doc_date'>Дата:<br><input type='text' value='"+data.date+"'></span>"
            + "</div>"
            + "<div class='item'><label for='agent_name'>Агент:</label><br><input type='text' id='agent_name' value='"+data.agent_info.name+"'></div>"
           
            
             + "<div class='err'>TEST Block</div>";
    };
    
    return dh;
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

function createAgentFromPhoneMenu(event, phone) {
    var menu = CreateContextMenu(event);
    function showDialog() {
        menu.innerHTML = "<fieldset><legend>Создание агента для номера</legend>"
                +"<div>Краткое наименование</div><input type='text' id='agentname'>"
                +"<div>Полное наименование</div><input type='text' id='agentfullname'>"
                +"<div>Группа</div><input type='text' id='agentgroup_id' value='1'>"
                +"<div>Тип</div><select id='agenttype'><option value='fl'>Физическое лицо</option><option value='fl'>Юридическое лицо</option></select>"
                +"<div>Номер телефона (+7XXXXXXXXXX)</div><input type='text' id='contactphone' value='"+phone+"'>"
                +"<div>Имя контакта</div><input type='text' id='contactname'>"
                +"<div>Должность контакта</div><input type='text' id='contactpost'>"
                +"<br><br><button id='bcancel'>Отменить</button><button id='bsend'>Отправить</button></fieldset>";
        menu.className = 'contextlayer';
        menu.onmouseover = menu.onmouseout = function () {
        };
        if (menu.waitHideTimer) {
            window.clearTimeout(menu.waitHideTimer);
        }
        var agentname = document.getElementById('agentname');
        var agentfullname = document.getElementById('agentfullname');
        var agentgroup_id = document.getElementById('agentgroup_id');
        var agenttype = document.getElementById('agenttype');
        var contactphone = document.getElementById('contactphone');
        var contactname = document.getElementById('contactname');
        var contactpost = document.getElementById('contactpost');
        
        var obsend = document.getElementById('bsend');
        var obcancel = document.getElementById('bcancel');

        obcancel.onclick = function () {
            menu.parentNode.removeChild(menu);
        };
        obsend.onclick = function () {
            var agent = {
                name: agentname.value,
                fullname: agentfullname.value,
                type:  agenttype.value,
                group_id:  agentgroup_id.value,
                contacts: {
                    '0': {
                        type: 'phone',
                        value: contactphone.value,
                        person_name: contactname.value,
                        person_post: contactpost.value,
                    }
                }
            };
            mm_api.agent.create(agent, 
                function () {
                        location.reload();
                    },
                function (msg, data) {
                        alert('Ошибка:'+msg);
                        //menu.parentNode.removeChild(menu);
                    }
            );
            //menu.innerHTML += '<img src="/img/icon_load.gif" alt="отправка">Отправка сообщения...';
        };
    }
    showDialog();
    return false;
}


function contractTextSaverInit(doc_id, textarea_id, button_id) {
    var textarea = document.getElementById(textarea_id);
    var button = document.getElementById(button_id);
    var dialog_caption = "Сохранение текста договора";
    var lock_blur = 0;
    var oldbg = textarea.style.backgroundColor;
    textarea.changing = 0;

    textarea.Save = function () {
        textarea.style.backgroundColor = '#ffc';
        
        $.ajax({
            type: 'POST',
            url: '/doc.php',
            data: 'mode=srv&doc='+doc_id+'&opt=jcts&text='+encodeURIComponent(textarea.value),
            success: function (msg) {
                textarea.rcvDataSuccess(msg);
            },
            error: function () {
                jAlert('Ошибка соединения!', dialog_caption, null, 'icon_err');
            }
        });
    };

    textarea.StartEdit = function () {
        textarea.changing = 1;
        button.style.display = '';
        if (textarea.timeout)
            window.clearTimeout(textarea.timeout);
    };

    textarea.FinistEdit = function () {
        textarea.changing = 0;
        button.style.display = 'none';
    };

    textarea.rcvDataSuccess = function (msg) {
        try {
            if (textarea.timeout)
                window.clearTimeout(textarea.timeout);
            var alfa = 255;
            textarea.timeout = window.setTimeout(function () {
                textarea.style.backgroundColor = ''
            }, 2000)
            var json = eval('(' + msg + ')');
            if (json.status == 'err') {
                textarea.style.backgroundColor = '#f00';
                jAlert('<b>Ошибка сохранения</b><br>' + json.text, dialog_caption, null, 'icon_err');
            }
            else if (json.response == 'jcts') {
                if(json.status=='ok') {
                    textarea.style.backgroundColor = '#bfa';
                }
                else {
                    textarea.style.backgroundColor = '#f00';
                    jAlert('<b>Ошибка сохранения</b><br>' + json.text, dialog_caption, null, 'icon_err');
                }
            }
            else {
                textarea.style.backgroundColor = '#f00';
                jAlert("Обработка полученного сообщения не реализована<br>" + msg, dialog_caption, null, 'icon_err');
            }
            textarea.FinistEdit();
        }
        catch (e) {
            textarea.style.backgroundColor = '#f00';
            jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!" +
                    "<br><br><i>Информация об ошибке</i>:<br>" + e.name + ": " + e.message + "<br>" + msg, dialog_caption, null, 'icon_err');
        }
    }

    function obj_onclick(event) {
        textarea.StartEdit();
        textarea.timeout = window.setTimeout(textarea.Save, 30000) // на всякий случай
    }

    function obj_onmousedown(event) {
        textarea.StartEdit();
        textarea.timeout = window.setTimeout(textarea.Save, 30000) // на всякий случай
        // Хак для предотвращения отправки формы по onblur, если фокус готовится быть переданным на select и др элемент
        lock_blur = 1;
        window.setTimeout(function () {
            lock_blur = 0;
        }, 60);
    }

    obj_onblur = function (event) {
        if (lock_blur)
            return;
        if (textarea.timeout)
            window.clearTimeout(textarea.timeout);
        textarea.timeout = window.setTimeout(textarea.Save, 500);
    }
    obj_onkeyup = function (event) {
        if (textarea.timeout)
            window.clearTimeout(textarea.timeout);
        //doc_left_block.timeout=window.setTimeout(doc_left_block.Save, 3000)
    }

    textarea.addEventListener('mousedown', obj_onmousedown, false);
    textarea.addEventListener('click', obj_onclick, false);
    textarea.addEventListener('blur', obj_onblur, false);
    textarea.addEventListener('keyup', obj_onkeyup, false);

}

function mainInternalMenu() {
    var mainMenu = document.getElementById("doc_menu");
    if(mainMenu.mim!=undefined) {
        return mainMenu.mim;
    }    
    var mim = new Object;
    mim.contextPanel = document.createElement('div');
    mim.contextPanel.id = "main_menu_context_panel";
    mainMenu.mim = mim;
    mim.leftDiv = document.getElementById("main_menu_left");
    mim.leftDiv.appendChild(mim.contextPanel);
    mim.rightDiv = document.getElementById("main_menu_right");
    
    
    mim.contextPanel.clear = function() {
        mim.contextPanel.innerHTML = '';
    };
    
    mim.contextPanel.addButton = function(data) {
        var a = document.createElement('a');
        var img = document.createElement('img');
        if(data.link) {
            a.href = data.link;
        }
        else {
            a.href = "#";
        }
        if(data.onclick) {
            a.oldclickevent = data.onclick;
            a.addEventListener('click', data.onclick, false);
        }
        if(data.caption) {
            a.title = img.alt = data.caption;
        }
        if(data.accesskey) {
            a.accesskey = data.accesskey;
            img.alt += " ("+data.accesskey+")";
            a.title += " ("+data.accesskey+")";
        }
        if(data.icon) {
            img.src = "/img/"+data.icon;
        }
        a.appendChild(img);
        mim.contextPanel.appendChild(a);
        return a;
    };
    
    mim.contextPanel.addSeparator = function() {
        var img = document.createElement('img');
        img.src = "/img/i_separator.png";
        mim.contextPanel.appendChild(img);
        return img;
    };
    
    mim.contextPanel.updateButton = function(button, data) {
        var a = button;
        var img = button.firstChild;
        if(data.link) {
            a.href = data.link;
        }
        if(data.onclick) {
            a.removeEventListener('click', a.oldclickevent);
            a.oldclickevent = data.onclick;
            a.addEventListener('click', data.onclick, false);
        }
        if(data.caption) {
            a.title = img.alt = data.caption;
        }
        if(data.accesskey) {
            a.accesskey = data.accesskey;
            img.alt += " ("+data.accesskey+")";
            a.title += " ("+data.accesskey+")";
        }
        if(data.icon) {
            img.src = "/img/"+data.icon;
        }
        return a;
    };
    
    return mim;
}