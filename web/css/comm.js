// Register javascript
//
// $Id: register.js,v 1.11 2008/03/06 14:23:56 torubarov Exp $


function getXmlHttp()
{
	var xmlhttp;
	try
	{
		xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	}
	catch (e)
	{
		try
		{
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
		catch (E)
		{
			xmlhttp = false;
		}
	}
	if (!xmlhttp && typeof XMLHttpRequest!='undefined')
	{
		xmlhttp = new XMLHttpRequest();
	}
	return xmlhttp;
}


var suggestlogin=Array();
var loginTimeout = 0;
var loginPopupFlag = 0;
var checklogin = '';
var checklogin_status = '';
var checkingPage = '';

var reqid;
var req_iname;
var req_fname;
var req_login;

var prev_iname;
var prev_fname;
var prev_login;

var mousX;
var mousY;

var timer_id;
var url_id;
var id_id;
var val_id;



// ======================= Popup =========================================
function MakePopup(txt)
{
	var scrollLeft,scrollTop;
	if (window.pageYOffset)
			scrollTop = window.pageYOffset 
	else if(document.documentElement && document.documentElement.scrollTop)
			scrollTop = document.documentElement.scrollTop; 
	else if(document.body)
			scrollTop = document.body.scrollTop; 
		
	if(window.pageXOffset)
			scrollLeft=window.pageXOffset 
	else if(document.documentElement && document.documentElement.scrollLeft)
			scrollLeft=document.documentElement.scrollLeft; 
	else if(document.body)
			scrollLeft=document.body.scrollLeft; 
	
	
	var popup=document.createElement('div');
	document.getElementsByTagName('body')[0].appendChild(popup);
	popup.style.cssText="border: 1px solid #aaa; min-width: 250px; max-width: 80%; background-color: #eee;  text-align: left; margin:0px; position: absolute;  display: block; padding: 0px; left: 0px; top: 0px;";
		
	var windowWidth,windowHeight; // frame width & height

	if(window.innerWidth)
			windowWidth=window.innerWidth; 
	else if(document.documentElement && document.documentElement.clientWidth)
			windowWidth=document.documentElement.clientWidth; 
	else if(document.body)
			windowWidth=document.body.offsetWidth; 
	
	if(window.innerHeight)
			windowHeight=window.innerHeight; 
	else if(document.documentElement && document.documentElement.clientHeight)
			windowHeight=document.documentElement.clientHeight; 
	else if(document.body)
			windowHeight=document.body.clientHeight; 
	
	if((mousX+300)>windowWidth)
			popup.style.left=(windowWidth-300+scrollLeft)+'px';
	else		popup.style.left=(mousX-5+scrollLeft)+'px';
	if((mousY+150)>windowHeight)
			popup.style.top=(windowHeight-150+scrollTop)+'px';
	else		popup.style.top=(mousY-5+scrollTop)+'px';
	
 	popup.id="pup"+(Math.floor(Math.random()*1000));
	popup.innerHTML=txt;
	return popup;
}


function MakeModal(txt, base)
{
	var popup=document.createElement('div');
	base.appendChild(popup);

	popup.style.cssText="border: 2px outset #aaa; min-width: 250px; max-width: 80%; background-color: #eee;  text-align: left; margin:0px; position: fixed;  display: block; padding: 3px;  opacity: 1.0;";

	popup.style.left=mousX-5;
	popup.style.top=mousY-5;


 	popup.id="pup"+(Math.floor(Math.random()*1000));
	popup.innerHTML=txt;
	return popup;
}

function HeadPopup(popup,dt)
{
	return "<div style='background-color: #aaa; color:#fff; width: 100%-6px; text-align: right; padding: 3px;'><b>"+dt+"</b> <a onclick=\"KillPopup('"+popup.id+"'); return false;\" href=''><img src='/img/i_del.png' border=0></a></div>";
}

function FreePopup(popup)
{
	if(popup)
	{
		var body = document.getElementsByTagName('body');
		body[0].removeChild(popup);
	}
}

function KillPopup(id)
{
	var popup=document.getElementById(id);
	FreePopup(popup);
}

// ======================= Edit ===================================================
function EditThis(url,id)
{
	var obj = document.getElementById(id);
    var httpRequest;
    if (window.XMLHttpRequest)  {
        httpRequest = new XMLHttpRequest(); }

    if (!httpRequest) { return false; }
    var popup=MakePopup("<img src='/img/icon_load.gif'> Загрузка...");
    httpRequest.onreadystatechange = function() { EditThisGet(httpRequest,id,popup); };
    httpRequest.open('GET', url, true);
    httpRequest.send(null);
}

function EditThisSave(url,id, val)
{
	var obj = document.getElementById(id);
	url=url+"&s="+encodeURIComponent(document.getElementById(val).value);
    var httpRequest;
    if (window.XMLHttpRequest)  {
        httpRequest = new XMLHttpRequest(); }

    if (!httpRequest) { return false; }
    var popup=MakePopup("<img src='/img/icon_load.gif'> Загрузка...");
    httpRequest.onreadystatechange = function() { EditThisGet(httpRequest,id,popup); };
    httpRequest.open('GET', url, true);
    httpRequest.send(null);
}

function DelayedSave(url,id,val)
{
	if(timer_id) window.clearTimeout(timer_id);
	url_id=url;
	id_id=id;
	val_id=val;
	timer_id=window.setTimeout("EditThisSave(url_id,id_id, val_id)", 700);
}

function EditThisGet(httpRequest, id, popup)
{
	var txt=document.getElementById(id);
	//popup.style.display="block";
	if (httpRequest.readyState == 4)
	{
		if (httpRequest.status == 200)
		{
		txt.innerHTML=httpRequest.responseText;
		FreePopup(popup);
		
		}
		else popup.innerHTML=HeadPopup(popup,'')+" "+httpRequest.status;
	}
	else if (httpRequest.readyState == 2)
	{
		popup.innerHTML=HeadPopup(popup,'')+"<img src='/img/icon_load.gif'> Загрузка...";
	}
	else if (httpRequest.readyState == 3)
	{
		popup.innerHTML=HeadPopup(popup,'')+"Обработка..."+id;
	}
	else popup.innerHTML=HeadPopup(popup,'')+"state "+httpRequest.readyState;
}


// ======================= Расширенное автозаполнение =============================
function AutoFill(url,id,dropdown)
{
	if(timer_id) window.clearTimeout(timer_id);
	url_id=url;
	id_id=id;
	dropdown_id=dropdown;
	timer_id=window.setTimeout("AutoFillNow(url_id, id_id, dropdown_id)", 1000);
}

function AutoFillNow(url,id,dropdown)
{
	url=url+"&s="+encodeURIComponent(document.getElementById(id).value);
	return RequestAutoFill(url,dropdown);
}

function RequestAutoFill(url,dropdown) {
    var httpRequest;
    if (window.XMLHttpRequest)  {
        httpRequest = new XMLHttpRequest(); }

    if (!httpRequest) { return false; }

    httpRequest.onreadystatechange = function() { AutoFillProcess(httpRequest ,dropdown); };
    httpRequest.open('GET', url, true);
    httpRequest.send(null);
}

function AutoFillProcess(httpRequest ,_dropdown)
{
	var dropdown=document.getElementById(_dropdown);
    dropdown.style.display="block";
    if (httpRequest.readyState == 4)
    {
        if (httpRequest.status == 200)
        {
            dropdown.innerHTML = httpRequest.responseText;
            dropdown.style.display="block";
        }
        else dropdown.innerHTML="Ошибка "+httpRequest.status;
        
    }
    else if (httpRequest.readyState == 2)
    {
        dropdown.innerHTML="<img src='/img/icon_load.gif'> Загрузка...";
    }
    else if (httpRequest.readyState == 3)
    {
        //status.innerHTML="Обработка...";
    }
    else dropdown.innerHTML="state "+httpRequest.readyState;
}

function AutoFillClick(sd,dat,dd_id)
{
	document.getElementById(sd).value=dat;
	document.getElementById(dd_id).style.display="none";
}


// ======================= Автозаполнение =========================================
function SubmitData(dat,id)
{
	document.getElementById('sdata').value=dat;
	document.getElementById('sid').value=id;
	document.getElementById('popup').style.display="none";
}

function RequestDataNow(url)
{
	url=url+"&s="+encodeURIComponent(document.getElementById("sdata").value);
	return makeRequest(url);
}

function RequestData(url)
{
	if(timer_id) window.clearTimeout(timer_id);
	url_id=url;
	timer_id=window.setTimeout("RequestDataNow(url_id)", 700);
}

function makeRequest(url) {
    var httpRequest;
    if (window.XMLHttpRequest)  {
        httpRequest = new XMLHttpRequest(); }

    if (!httpRequest) { return false; }
    //var popup=MakePopup("<img src='img/icon_load.gif'> Загрузка...");
    httpRequest.onreadystatechange = function() { processContents(httpRequest); };
    httpRequest.open('GET', url, true);
    httpRequest.send(null);
}

function processContents(httpRequest)
{
    var popup=document.getElementById("popup");
    popup.style.display="block";
    if (httpRequest.readyState == 4)
    {
        if (httpRequest.status == 200)
        {
            popup.innerHTML = httpRequest.responseText;
            popup.style.display="block";
        }
        else popup.innerHTML="Ошибка "+httpRequest.status;
    }
    else if (httpRequest.readyState == 2)
    {
        popup.innerHTML="<img src='/img/icon_load.gif'> Загрузка...";
    }
    else if (httpRequest.readyState == 3)
    {
        //status.innerHTML="Обработка...";
    }
    else popup.innerHTML="state "+httpRequest.readyState;
}


// ========================= Запрос номера =====================================

function GetValue(url,id,sub,date,firm) {
    var httpRequest;
    url=url+"&sub="+encodeURIComponent(document.getElementById(sub).value)+"&date="+encodeURIComponent(document.getElementById(date).value)+"&firm="+encodeURIComponent(document.getElementById(firm).value);
    if (window.XMLHttpRequest)  {
        httpRequest = new XMLHttpRequest(); }

    if (!httpRequest) { return false; }
    var popup=MakePopup("<img src='/img/icon_load.gif'> Загрузка...");
    httpRequest.onreadystatechange = function() { processGet(httpRequest,id,popup); };
    httpRequest.open('GET', url, true);
    httpRequest.send(null);
}

function processGet(httpRequest, id, popup)
{
    var txt=document.getElementById(id);
    popup.style.display="block";
    if (httpRequest.readyState == 4)
    {
        if (httpRequest.status == 200)
        {
            txt.value=httpRequest.responseText;
            FreePopup(popup);
        }
        else popup.innerHTML=HeadPopup(popup,'')+"Ошибка "+httpRequest.status;
    }
    else if (httpRequest.readyState == 2)
    {
        popup.innerHTML="<img src='/img/icon_load.gif'> Загрузка...";
    }
    else if (httpRequest.readyState == 3)
    {
        //status.innerHTML="Обработка...";
    }
    else popup.innerHTML="state "+httpRequest.readyState;
}

// ============================== Динамическое окно ===================================
function ShowPopupWin(url)
{
    var httpRequest;
    if (window.XMLHttpRequest)  {
        httpRequest = new XMLHttpRequest(); }

    if (!httpRequest) { return true; }

    var popup=MakePopup("<img src='/img/icon_load.gif'> Загрузка...");

    httpRequest.onreadystatechange = function() { popupReqWin(httpRequest,popup); };
    httpRequest.open('GET', url, true);
	httpRequest.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    httpRequest.send(null);
    return false;
}

function popupReqWin(httpRequest,popup)
	{
	if (httpRequest.readyState == 4)
	{
		if (httpRequest.status == 200)
		{
			var str=httpRequest.responseText;
			var re = /(.*)(<h1>)(.*)(<\/h1>)(.*)(\s|$)/
			var str1 = str.match(re);
				var str = str.replace(re, '$1'+'$5');
			popup.innerHTML = HeadPopup(popup, '')+str;
		}
		else popup.innerHTML=HeadPopup(popup,'')+"Ошибка "+httpRequest.status;
	}
	else if (httpRequest.readyState == 2)
	{
		popup.innerHTML="<img src='/img/icon_load.gif'> Загрузка...";
	}
	else if (httpRequest.readyState == 3)
	{
		//popup.innerHTML="Обработка...";
	}
}

// ============================== Динамическое модальное окно ===================================
function ShowPopupModal(url)
{
    var httpRequest;
    if (window.XMLHttpRequest)  {
        httpRequest = new XMLHttpRequest(); }

    if (!httpRequest) { return false; }

	var dis=document.createElement('div');
    var body = document.getElementsByTagName('body');
    body[0].appendChild(dis);
    dis.style.cssText="width: 2000px; height: 2000px; background-color: #555; text-align: left; margin:0px; position: fixed; display: block; left: 0px; top: 0px; opacity: 0.9; filter:alpha(opacity=90);";
    dis.id="pup"+(Math.floor(Math.random()*1000));

    var popup=MakeModal("<img src='/img/icon_load.gif'> Загрузка...",dis);


    //httpRequest.onreadystatechange = function() { procReqWin(httpRequest,id); };
    httpRequest.onreadystatechange = function() { popupReqModal(httpRequest,popup, dis); };
    httpRequest.open('GET', url, true);
    httpRequest.send(null);
}

function popupReqModal(httpRequest,popup, disa)
{
    if (httpRequest.readyState == 4)
    {
        if (httpRequest.status == 200)
        {

			var str=httpRequest.responseText;
			var re = /(.*)(<h1>)(.*)(<\/h1>)(.*)(\s|$)/
        	var str1 = str.match(re);
			var str = str.replace(re, '$1'+'$5');
            popup.innerHTML = HeadPopup(disa,'')+str;

            //popup.innerHTML = HeadPopup(disa)+httpRequest.responseText;
        }
        else popup.innerHTML=HeadPopup(disa)+"Ошибка "+httpRequest.status;
    }
    else if (httpRequest.readyState == 2)
    {
        //popup.innerHTML="<img src='img/icon_load.gif'> Загрузка...";
    }
    else if (httpRequest.readyState == 3)
    {
        //popup.innerHTML="Обработка...";
    }
    else popup.innerHTML=HeadPopup(disa)+"state "+httpRequest.readyState;
}



function OnEnterBlur(e)
{ 
	
	/* firefox uses reserved object e for event */ 
	evt = e || window.event; 
	var pressedkey = evt.which || evt.keyCode; 
	var srcel  = (evt.srcElement)? evt.srcElement: evt.target; 
	if (((pressedkey==13)||(pressedkey==9))) 
	{ 
		 srcel.blur();
	} 

	return true 
}



// ================= дерево ==================================================

function tree_toggle(event) {
        event = event || window.event
        var clickedElem = event.target || event.srcElement

        if (!hasClass(clickedElem, 'Expand')) {
                return // клик не там
        }

        // Node, на который кликнули
        var node = clickedElem.parentNode
        if (hasClass(node, 'ExpandLeaf')) {
                return // клик на листе
        }

        // определить новый класс для узла
        var newClass = hasClass(node, 'ExpandOpen') ? 'ExpandClosed' : 'ExpandOpen'
        // заменить текущий класс на newClass
        // регексп находит отдельно стоящий open|close и меняет на newClass
        var re =  /(^|\s)(ExpandOpen|ExpandClosed)(\s|$)/
        node.className = node.className.replace(re, '$1'+newClass+'$3')
}


function hasClass(elem, className) {
        return new RegExp("(^|\\s)"+className+"(\\s|$)").test(elem.className)
}


// function procReqWin(httpRequest,id)
// {
//     var status=document.getElementById("status");
//     var popup=document.getElementById(id);
//     status.style.display="block";
//     if (httpRequest.readyState == 4)
//     {
//         if (httpRequest.status == 200) {
//
//             popup.innerHTML = "<div width=100% align=right style='background-color: 666699;'><a onclick=\"ShowPopup('"+id+"'); return false;\" href=''><img src='img/icon_del.gif' border=0></a></div>"+httpRequest.responseText;
//             if(mousX<100) popup.style.left=mousX-5;
//             else popup.style.left=50;
// 			popup.style.top=mousY-5;
//             status.style.display="none";
//             popup.style.display="block";
//         }
//         else status.innerHTML="Ошибка "+httpRequest.status;
//     }
//     else if (httpRequest.readyState == 2)
//     {
//         status.innerHTML="<img src='img/icon_load.gif'> Загрузка...";
//     }
//     else if (httpRequest.readyState == 3)
//     {
//         //status.innerHTML="Обработка...";
//     }
//     else status.innerHTML="state "+httpRequest.readyState;
// }

function ShowHide(elID)
{
   obj=document.getElementById(elID);
   if((obj.style.display=='none')||(obj.style.display==''))
   {
       obj.style.display='block';
   }
   else
   {
       obj.style.display='none';
   }
}

function ShowPopup(elID)
{
	obj=document.getElementById(elID);
	if((obj.style.display=='none')||(obj.style.display==''))
	{
		obj.style.left=mousX-5;
		obj.style.top=mousY-5;
		obj.style.display='block';
	}
	else
	{
		obj.style.display='none';
	}
}

function ClearText(elID)
{
	document.getElementById(elID).value='';
}


// ======================= Context menu =========================================
// Переписано и перенесено в core.js



function PriceRegTest(url)
{
	if(timer_id) window.clearTimeout(timer_id);
	url_id=url;
	timer_id=window.setTimeout("PriceRegTestEx(url_id)", 700);
}

function PriceRegTestEx(url)
{	
	var str=	encodeURIComponent(document.getElementById('str').value);
	var regex=	encodeURIComponent(document.getElementById('regex').value);
	var regex_neg=	encodeURIComponent(document.getElementById('regex_neg').value);
	url=url+"&str="+str+"&regex="+regex+"&regex_neg="+regex_neg;
	
	var httpRequest = new XMLHttpRequest();
	
	if (!httpRequest) { return false; }
	var popup=MakePopup("<img src='/img/icon_load.gif'> Загрузка...");
	httpRequest.onreadystatechange = function() { EditThisGet(httpRequest, 'regex_result', popup); };
	httpRequest.open('GET', url, true);
	httpRequest.send(null);

}

function getCoor( event ) {
	if(!event)		return;
	if(event=='undefined')	return;
	if(event.clientX)	mousX = event.clientX;
	if(event.clientX)	mousY = event.clientY;
}

document.onmousemove = getCoor;

