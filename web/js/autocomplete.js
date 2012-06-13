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

	function requestData(str_query)
	{
		var httpRequest
		if (window.XMLHttpRequest) httpRequest = new XMLHttpRequest()
		if (!httpRequest)  return false
		var url=ac_url+'&s='+encodeURIComponent(str_query)
		httpRequest.onreadystatechange = receiveDataProcess
		httpRequest.open('GET', url, true)
		httpRequest.send(null)

		function receiveDataProcess()
		{
			if (httpRequest.readyState == 4)
			{
				if (httpRequest.status == 200)
				{

				}
				//else {}

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

	}

	input.onfocus=function()
	{
		var input_offset=getOffset(input)
		var dd=document.createElement('div')
		input.parentNode.appendChild(dd)
		dd.style.cssText='position: absolute; width: 20px; height: 20px; background-color: #f00;'
		dd.style.left=(input_offset.left+parseInt(input.style.width)-16)+'px'
		dd.style.top=input_offset.top+'px'


		div.className='autocomplete'
		div.style.left=input_offset.left+'px'
		div.style.top=(input_offset.top+20)+'px'
		div.innerHTML='<ul><li>efgerg</li><li>efgerg</li><li>etr hrtj hrth rthi rtjhrthrt  rth rh  th rhtr h rthrth hrth trhrth srth o rtjho rjfgerg</li><li>efgerg</li><li>efgerg</li><li>efgerg</li><li>efgerg</li><li>efgerg</li><li>efge rthrth rth rth rth rthrthrtrg</li><li>efgerg</li><li>efgerg</li><li>efgerg</li><li>efgerg</li><li>efgerg</li><li>efgerg</li><li>efgerg</li><li>efgerg</li><li>efgerg</li></ul>'
		div.style.width=input.style.width

		return input
	}




}