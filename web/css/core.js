// Основной javascript файл Multimag
// После переработки javascript библиотеки все методы перенести сюда


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

