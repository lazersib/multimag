//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2016, BlackLight, TND Team, http://tndproject.org
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

// Виджет *автодополнение*
// Позволяет организовать ввод с автодополнением. Поддерживает событие onselect, onerror
// отличительная особенность - отображает сразу весь объём данных, с прокруткой
// Планируется поддержка html5 хранилища для кеширования данных

function initAutocomplete(input_id, ac_url)
{
	var input = document.getElementById(input_id)
	input.style.cssText='border-radius: 1px; margin: 0px; width: 300px;'
	var input_offset=getOffset(input)
	var div = document.createElement('div')
	document.getElementsByTagName('body')[0].appendChild(div)
	div.className='autocomplete'

	div.style.width=input.style.width
	div.style.display='none'

	var cursor=0
	var old_cursor=0

	function requestData()
	{
		var httpRequest
		if (window.XMLHttpRequest) httpRequest = new XMLHttpRequest()
		if (!httpRequest)  return false
		var url=ac_url+'&s='+encodeURIComponent(input.value)
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

	function parseReceived(data)
	{
		try{
			var json=eval('('+data+')')
			switch(json.response)
			{
				case 'err':
					jAlert(json.message,"Ошибка", {}, 'icon_err');
					break;
				case 'data':
					updateDd(json);
					break;
				default:
					jAlert("Обработка полученного сообщения не реализована", "Изменение списка товаров", null,  'icon_err');
			}
		}catch(e)
		{
			jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!"+
			"<br><br><i>Информация об ошибке</i>:<br>"+e.name+": "+e.message+"<br>", "Автодополнение", null,  'icon_err');
		}
	}

	function updateDd(json)
	{
		div.innerHTML=''
		var str=''
		var ul=document.createElement('ul')
		div.appendChild(ul)
		for(var i=0;i<json.content.length;i++)
		{
			if(json.content[i].name.indexOf(input.value)<0)	continue;
			var li=document.createElement('li')
			ul.appendChild(li)
			li.innerText=json.content[i].name+':'+json.content[i].vendor+' ('+json.content[i].vc+')'
			li.line_id=json.content[i].id
		}
		cursor=0
		highlight()
	}

	function highlight()
	{
		var elems=div.getElementsByTagName('li')
		if(cursor<0)		cursor=0
		if(cursor>=elems.length)	cursor=elems.length-1
		if(old_cursor<(elems.length-1))	elems[old_cursor].className=''
		if(elems.length)		elems[cursor].className='cursor'
		old_cursor=cursor
	}
	function scroll()
	{
		var elem_size=0
		var elems=div.getElementsByTagName('li')
		if(elems.length>0)
			elem_size=elems[0].offsetHeight
		if(cursor>5 && elem_size>0 && elems.length>10)
			div.scrollTop=(cursor-5)*elem_size
		else	div.scrollTop=0
	}

	input.onkeyup=function (event)
	{
		if(input.timer_id) window.clearTimeout(input.timer_id)
		switch(event.keyCode)
		{
			case 40:	cursor++;	highlight();	scroll();	break;	// Down
			case 38:	cursor--;	highlight();	scroll();	break;	// Up
			case 34:	cursor+=10;	highlight();	scroll();	break;	// PageDown
			case 33:	cursor-=10;	highlight();	scroll();	break;	// PageUp
			case 13:{
					if(div.onselect)	div.onselect(cursor)
					var elems=div.getElementsByTagName('li')
					input.value=elems[cursor].innerText
					div.style.display='none'
				}
			default:	input.timer_id=window.setTimeout(requestData, 300);
		}
	}

	div.onmousemove=function(event)
	{
		if(event.target.tagName=='LI')
		{
			var elems=div.getElementsByTagName('li')
			for(i=0;i<elems.length;i++)
			{
				if(elems[i]==event.target)
				{
					cursor=i
					highlight()
					break
				}
			}
		}
	}

	div.onclick=function(event)
	{
		if(div.onselect)	div.onselect(event.target.line_id)
		input.value=event.target.innerText
		div.style.display='none'
	}

	input.onfocus=function()
	{
		var input_offset=getOffset(input)
		div.style.left=input_offset.left+'px'
		div.style.top=(input_offset.top+20)+'px'
		div.style.display=''
		return input
	}

	input.onblur=function()
	{
		//div.style.display='none'
	}




}