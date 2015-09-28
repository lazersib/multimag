// Основной javascript файл Multimag
// После переработки javascript библиотеки все методы перенести сюда
// В финальной версии убрать зависимость от jquery

function httpReq(url, method, data, successCallback, errorCallback) {    
    var req;
    
    function processRequest(httpRequest) {
        try {
            if (httpRequest.readyState == 4) {
                if (httpRequest.status == 200) {
                    successCallback(httpRequest.responseText);
                }
                else {
                    errorCallback(httpRequest.status, httpRequest.responseText);
                }
            }
        }
        catch (e) {
            errorCallback(e.name, e.message);
        }
    }
    
    if (window.XMLHttpRequest) {
        req = new XMLHttpRequest();
    }
    if (!req) {
        return false;
    }
    req.timeout = 15000;
    req.ontimeout = function() {
        errorCallback('timeout', 'timeout');
    }
    req.onreadystatechange = function () {
        processRequest(req);
    };
    if(method=='GET' || method=='get') {
        req.open('GET', url + '?' + data, true);
        req.send(null);
    } else if(method=='POST' || method=='post') {
        req.open('POST', url, true);
        req.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        req.send(data);
    } else {
        return false;
    }
    return true;
}

function supports_html5_storage() {
	try {
		return 'localStorage' in window && window['localStorage'] !== null;
	} catch (e) {
		return false;
	}
}

function newElement(tagName, parent, className, innerHTML) {
    var element = document.createElement(tagName);
    element.className = className;
    parent.appendChild(element);
    if (innerHTML)
        element.innerHTML = innerHTML;
    return element;
}

function newElementAfter(tagName, target, className, innerHTML) {
    var element = document.createElement(tagName);
    element.className = className;
    if (target.nextSibling)
         target.parentNode.insertBefore(element, target.nextSibling);
    else target.parentNode.appendChild(element);
    if (innerHTML)
        element.innerHTML = innerHTML;
    return element;
}

// Получение координат элемента на странице
function getOffset(elem) {
	if (elem.getBoundingClientRect)
		return getOffsetRect(elem)
	else
		return getOffsetSum(elem)

	function getOffsetSum(elem) {
		var top=0, left=0
		while(elem) {
			top = top + parseInt(elem.offsetTop)
			left = left + parseInt(elem.offsetLeft)
			elem = elem.offsetParent
		}
		return {top: top, left: left}
	}

	function getOffsetRect(elem) {
		var box = elem.getBoundingClientRect()
		var body = document.body
		var docElem = document.documentElement
		var scrollTop = window.pageYOffset || docElem.scrollTop || body.scrollTop
		var scrollLeft = window.pageXOffset || docElem.scrollLeft || body.scrollLeft
		var clientTop = docElem.clientTop || body.clientTop || 0
		var clientLeft = docElem.clientLeft || body.clientLeft || 0
		var top  = box.top +  scrollTop - clientTop
		var left = box.left + scrollLeft - clientLeft
		return { top: Math.round(top), left: Math.round(left) }
	}
}

// Создать контекстное автоубирающееся меню по данному событию мыши
function CreateContextMenu(e)
{
	e = e || window.event
	var menu=document.createElement('div')
	menu.className='contextmenu'
	menu.innerHTML='<img src="/img/icon_load.gif" alt="Загрузка">'
	var scrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop
	var scrollLeft = window.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft
	menu.style.left=(e.clientX+scrollLeft)+'px'
	menu.style.top=(e.clientY+scrollTop)+'px'
	document.getElementsByTagName('body')[0].appendChild(menu)

	menu.onmouseover=function() { if(menu.waitHideTimer) window.clearTimeout(menu.waitHideTimer); menu.waitHideTimer=window.setTimeout(AnimateHideMenu, 5000) }
	menu.onmouseout=ContextMenuOut
	menu.waitHideTimer=window.setTimeout(AnimateHideMenu, 5000)
	menu.opacity=1


	function ContextMenuOut()
	{
		if(menu.waitHideTimer) window.clearTimeout(menu.waitHideTimer)
		menu.waitHideTimer=window.setTimeout(AnimateHideMenu, 500)
	}

	function AnimateHideMenu()
	{
		menu.opacity-=0.15
		menu.style.opacity=menu.opacity
		if(menu.opacity<=0)
			menu.parentNode.removeChild(menu)
		else	menu.animHideTimer=window.setTimeout(AnimateHideMenu, 50)
	}
	return menu
}

function ShowContextMenu(event, url)
{
	var menu=CreateContextMenu(event)
	$.ajax({
		type:   'GET',
	        url:    url,
	        success: function(msg) { if(menu) menu.innerHTML=msg; },
	        error:   function() { if(menu) menu.innerHTML='Ошибка получения данных!'; },
	});
	return false
}

function ShowAgentContextMenu(event, agent_id, addition)
{
	var menu=CreateContextMenu(event)
	var dt = new Date()
	if(!addition || addition=='undefined')	addition=''
	var nowdate=dt.getFullYear()+'-'+((dt.getMonth()<9)?('0'+(dt.getMonth()+1)):(dt.getMonth()+1))+'-'+((dt.getDate()<10)?('0'+dt.getDate()):dt.getDate())
	menu.innerHTML=	"<div onclick=\"window.open('/docj_new.php?agent_id="+agent_id+"')\">Агент в журнале</div>"+
	"<div onclick=\"window.open('/docs.php?mode=srv&amp;l=agent&amp;opt=ep&amp;pos="+agent_id+"')\">Редактирование агента</div>"+addition
	return menu
}

function ShowPosContextMenu(event, pos_id, addition)
{
    var menu = CreateContextMenu(event);
    var dt = new Date();
    var nowdate = dt.getFullYear() + '-' + ((dt.getMonth() < 9) ? ('0' + (dt.getMonth() + 1)) : (dt.getMonth() + 1)) + '-' + ((dt.getDate() < 10) ? ('0' + dt.getDate()) : dt.getDate());
    menu.innerHTML = "<div onclick=\"window.open('/docj_new.php?pos_id=" + pos_id + "')\">Товар в журнале</div>" +
        "<div onclick=\"window.open('/doc_reports.php?mode=sales&amp;w_docs=1&amp;sel_type=pos&amp;opt=pdf&amp;dt_t=" + nowdate + "&amp;pos_id=" + pos_id + "')\">Отчёт по движению</div>" +
        "<div onclick=\"window.open('/docs.php?mode=srv&amp;opt=ep&amp;pos=" + pos_id + "')\">Редактирование товара</div>" +
        "<div onclick=\"ShowPopupWin('/docs.php?l=pran&amp;mode=srv&amp;opt=ceni&amp;pos=" + pos_id + "'); return false;\" >Где и по чём</div>" +
        "<div onclick=\"window.open('/docs.php?mode=srv&amp;opt=ep&amp;param=n&amp;pos=" + pos_id + "')\">Аналоги</div>" +
        addition;
    return menu;
}

// Модуль просмотра картинок для витрины
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function tripleView(object_id, no_expand)
{
	var midiview=document.getElementById(object_id)
	var body = document.getElementsByTagName('body')[0];
	var midi_urls=new Array()
	var full_urls=new Array()
	var origin_urls=new Array()
	var last_id=0
	var container_size
	var cont_padding=5
	var photo_count=0

	var first=1
	var last=0

	function createPopup()
	{
		var overlay=document.createElement('div')
		body.appendChild(overlay)
		overlay.style.cssText="z-index: 998; background-color: #1c1d1b; opacity: 0.8; width: 100%; height: 100%; position: fixed; padding: 0; margin: 0; left: 0; top: 0;";
		midiview.overlay=overlay

		var container=document.createElement('div')
		midiview.container=container
		container.style.cssText="z-index: 999; border: #6babfb 2px solid; background-color: #fff; opacity: 1; width: 32px; height: 32px; position: fixed; padding: "+cont_padding+"px; margin: 0; left: 0; top: 0; overflow: hidden; text-align: center; vertical-align: middle; border-radius: 10px; -moz-border-radius: 10px;";
		body.appendChild(container)
		container.style.left=( (overlay.clientWidth-32)/2)+"px"
		container.style.top=( (overlay.clientHeight-32)/2)+"px"

		container.destroy=function()
		{
			body.removeChild(container)
			body.removeChild(overlay)
		}

		overlay.onclick=container.destroy

		var left_arrow=document.createElement('div')
		left_arrow.style.cssText="width: 57px; height: 47px; position: absolute;";
		left_arrow.innerHTML="<img src='/img/prettyPhoto/facebook/btnPrevious.png' alt='Previous'>"


		var right_arrow=document.createElement('div')
		right_arrow.style.cssText="width: 57px; height: 47px; position: absolute;";
		right_arrow.innerHTML="<img src='/img/prettyPhoto/facebook/btnNext.png' alt='Next'>"

		var close_btn=document.createElement('div')
		close_btn.style.cssText="width: 22px; height: 22px; position: absolute;";
		close_btn.innerHTML="<img src='/img/prettyPhoto/facebook/btnClose.png' alt='Close'>"
		close_btn.onclick=container.destroy

		var expand_btn=document.createElement('div')
		expand_btn.style.cssText="width: 22px; height: 22px; position: absolute;";
		expand_btn.innerHTML="<img src='/img/prettyPhoto/facebook/btnExpand.png' alt='Expand'>"
		if(no_expand) {
			expand_btn.style.display = 'none';
		}

		var image=document.createElement('img')
		container.appendChild(image)
		image.src='/img/icon_load.gif'

		var temp_img=document.createElement('img')
		temp_img.src=full_urls[last_id]
		temp_img.onload=function()
		{
			image.src=temp_img.src
		}

		var firstload=1

		image.onload=function(event)
		{
			if(firstload)
			{
				if(photo_count>1)
				{
					container.appendChild(right_arrow)
					container.appendChild(left_arrow)
				}
				container.appendChild(close_btn)
				container.appendChild(expand_btn)
				firstload=0
			}
			if( (temp_img.width>(window.innerWidth-64)) || (temp_img.height>(window.innerHeight-64)))
			{
				var cw=temp_img.width
				var ch=temp_img.height
				var scalew=cw/(window.innerWidth-64)
				var scaleh=ch/(window.innerHeight-64)
				var scale=(scalew>scaleh)?scalew:scaleh

				image.width=Math.ceil(cw/scale)
				image.height=Math.ceil(ch/scale)
			}
			else
			{
				image.width=temp_img.width
				image.height=temp_img.height
			}

			var cw=image.clientWidth
			container.style.width=cw+"px"
			var ch=image.clientHeight
			container.style.height=ch+"px"
			var cl=container.style.left=( (overlay.clientWidth-image.clientWidth)/2)+"px"
			var ct=container.style.top=( (overlay.clientHeight-image.clientHeight)/2)+"px"

			if(photo_count>1)
			{
				left_arrow.style.left=(-7)+"px"
				left_arrow.style.top=(cont_padding+(ch-22)/2)+"px"

				right_arrow.style.left=(cont_padding*2+cw-50)+"px"
				right_arrow.style.top=(cont_padding+(ch-22)/2)+"px"

				if(first>0)	left_arrow.style.display='none';
				else		left_arrow.style.display='';
				if(last>0)	right_arrow.style.display='none';
				else		right_arrow.style.display='';
			}

			close_btn.style.left=(cont_padding*2+cw-30)+"px"
			close_btn.style.top=(cont_padding+4)+"px"

			expand_btn.style.left=(cont_padding*2+cw-60)+"px"
			expand_btn.style.top=(cont_padding+4)+"px"

			expand_btn.onclick=function()
			{
				window.open(origin_urls[last_id])
			}

		}

		right_arrow.onclick=function(event)
		{
			var flag=0
			first=0
			last=1
			for(var i in full_urls)
			{
				if (!full_urls.hasOwnProperty(i)) continue;
				if(flag==2)
				{
					last=0
					break
				}
				if(flag==1)
				{
					last_id=i
					flag=2
				}
				if((last_id==i) && (flag==0))	flag=1
			}
			//image.src=full_urls[last_id]
			temp_img.src=full_urls[last_id]
		}
		left_arrow.onclick=function(event)
		{
			var old_last=last_id
			first=2
			last=0
			for(var i in full_urls)
			{
				if (!full_urls.hasOwnProperty(i)) continue;
				if(old_last==i)	break
				last_id=i
				first--;
			}
			//image.src=full_urls[last_id]
			temp_img.src=full_urls[last_id]
		}

		return container
	}

	midiview.onclick=function(event)
	{
		var popup=createPopup()
	}

	midiview.appendImage=function(id,midi_url,full_url,origin_url)
	{
		midi_urls[id]=midi_url
		full_urls[id]=full_url
		origin_urls[id]=origin_url
		if(!last_id)	last_id=id
		photo_count++;
	}

	midiview.setPhoto=function (id)
	{
		last_id=id
		first=last=0
		midiview.src='/img/icon_load.gif'
		midiview.src=midi_urls[id]
		return false;
	}

	return midiview
}
