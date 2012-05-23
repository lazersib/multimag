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
	input.style.cssText='border-radius: 1px; margin: 0px; margin-bottom: 5px; width: 300px;'
	var div = document.createElement('div')
	div.innerHTML='test<br>123'
	div.style.cssText='position: absolute; width: 200px; height: 200px; border: 1px solid #000; background-color: #f95;'
	div.style.width=input.style.width
	input.parentNode.appendChild(div)
	return input
}