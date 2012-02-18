//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2010, BlackLight, TND Team, http://tndproject.org
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

// Виджет *календарь*
// Позволяет установить дату и/или время в ассоциированном с ним текстовом поле ввода

function initCalendar(input_id, selectTime)
{
	function isLeapYear(year) {
		return (((year % 4)==0) && ((year % 100)!=0) || ((year % 400)==0));
	}
	
	function getDaysInMonth(month,year) {
		var days;
		if (month==1 || month==3 || month==5 || month==7 || month==8 || month==10 || month==12) 
										days = 31
		else if (month==4 || month==6 || month==9 || month==11)		days = 30
		else if (month==2 && isLeapYear(year))				days = 29
		else 								days = 28
		return days
	}
	
	function pad(value) {
		return value>9?value:'0'+value
	}

	function updateInput()	{
		var val=date.getFullYear()+'-'+pad(date.getMonth()+1)+'-'+pad(date.getDate())
		if(input.selectTime)
			val+=' '+pad(date.getHours())+':'+pad(date.getMinutes())+':'+pad(date.getSeconds())
		input.value=val
	}
	
	function updateHeader()	{
		head_text.data=months[date.getMonth()]+' '+date.getFullYear()
	}
	
	function selectNow() {
		nowdate=new Date()
		date.setFullYear(nowdate.getFullYear())
		date.setMonth(nowdate.getMonth())
		date.setDate(nowdate.getDate())
		updateHeader()
		updateInput()
		draw()
	}
	
	function draw()	{
		var nowdate=new Date()
		while (tbody.hasChildNodes()) tbody.removeChild(tbody.lastChild)
		var startingPos = new Date(date.getFullYear(), date.getMonth(), 1).getDay()-1
		if(startingPos<0)	startingPos=6
		var days=getDaysInMonth(date.getMonth()+1,date.getFullYear())
		var rows=0
		var i=0
		var tableRow = tbody.insertRow(rows++)
		for (i = 0; i < startingPos; i++) {
			var cell = newElement('td', tableRow, 'none')
			cell.innerHTML='&nbsp;' 
		}
		
		var currentDay = 1;
		for (i = startingPos; currentDay <= days; i++) {
			if (i%7 == 0 && currentDay != 1) {
				tableRow = tbody.insertRow(rows++)
			}
			var curClass=''
			if(nowdate.getFullYear()==date.getFullYear() && nowdate.getMonth()==date.getMonth() && nowdate.getDate() == currentDay ) curClass='now'
			if(date.getDate() == currentDay ) curClass='current'
			var cell = newElement('td', tableRow, curClass, currentDay)
			cell.datevalue=currentDay
			currentDay++;
		}
		for(;i%7 != 0;i++) {
			var cell = newElement('td', tableRow, 'none')
			cell.innerHTML='&nbsp;' 
		}
	}
	
	function bodyClick(event) {
		if(event.target.datevalue>0)
		{
			date.setDate(event.target.datevalue)
			updateInput()
			draw()
		}
		return false
	}
	
	function retFalse() { return false }
	
	var input = document.getElementById(input_id)
	var date = new Date(input.value)
	if(!date.getFullYear()) date=new Date()
	input.oldstyle=input.style
	input.selectTime = selectTime	// Возможность выбрать время
	
	var months=Array('Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сертябрь','Октябрь','Ноябрь','Декабрь')
	var days=Array('Вс','Пн','Вт','Чт','Пт','Сб')
	
	var calendar=newElement('div', input.parentNode, 'calendar')
	calendar.onmousedown=retFalse
	// header
	var cal_header=newElement('div', calendar, 'header')
	var yback=newElement('div', cal_header, 'button yback')
	var mback=newElement('div', cal_header, 'button mback')
	var yfw=newElement('div', cal_header, 'button yfw')
	var mfw=newElement('div', cal_header, 'button mfw')	
	var head_text = document.createTextNode('')	
	cal_header.appendChild(head_text)	
	
	yback.onclick=function() {
		date.setFullYear(date.getFullYear()-1)
		updateHeader()
		draw()
	}
	yfw.onclick=function() {
		date.setFullYear(date.getFullYear()+1)
		updateHeader()
		draw()
	}
	mback.onclick=function() {
		date.setMonth(date.getMonth()-1)
		updateHeader()
		draw()
	}
	mfw.onclick=function() {
		date.setMonth(date.getMonth()+1)
		updateHeader()
		draw()
	}
	
	updateHeader()
	
	newElement('div', cal_header, 'clear')
	// body
	var table=newElement('table', calendar, 'main')
	newElement('thead', table, '').insertRow(0).innerHTML='<th>Пн</th><th>Вт</th><th>Ср</th><th>Чт</th><th>Пт</th><th>Сб</th><th>Вс</th>'
	var tbody=newElement('tbody', table, '')
	tbody.onclick=bodyClick	
	tbody.onmousedown=retFalse
	
	if(input.selectTime)
	{
		var table=newElement('table', calendar, 'time')
		var tableRow = table.insertRow(0)
		var time_now = newElement('td', tableRow, '', 'Сейчас')
		var time_6 = newElement('td', tableRow, '', '6:00')
		var time_12 = newElement('td', tableRow, '', '12:00')
		var time_18 = newElement('td', tableRow, '', '18:00')
		var time_21 = newElement('td', tableRow, '', '21:00')
		
		time_6.onclick=function() {
			date.setHours(6)
			date.setMinutes(0)
			date.setSeconds(0)
			updateInput()
		}
		time_12.onclick=function() {
			date.setHours(12)
			date.setMinutes(0)
			date.setSeconds(0)
			updateInput()
		}
		time_18.onclick=function() {
			date.setHours(18)
			date.setMinutes(0)
			date.setSeconds(0)
			updateInput()
		}
		time_21.onclick=function() {
			date.setHours(21)
			date.setMinutes(0)
			date.setSeconds(0)
			updateInput()
		}
		time_now.onclick=function() {
			nowdate=new Date()
			date.setHours(nowdate.getHours())
			date.setMinutes(nowdate.getMinutes())
			date.setSeconds(nowdate.getSeconds())
			updateInput()
		}
	}
	
	
	var cal_footer=newElement('div', calendar, 'footer')
	var cal_nowday=newElement('div', cal_footer, 'button left', 'Сегодня')
	var cal_close=newElement('div', cal_footer, 'button right', 'Закрыть')

	cal_nowday.onclick=selectNow

	function input_onfocus(event) {
		calendar.style.display='block'
	}
	
	function calendar_close(event) {
		calendar.style.display='none'
	}
	
	function input_onkeyup(event)
	{
		var newdate = new Date(input.value)
		if(newdate.getFullYear())
		{
			date=newdate
			input.style=input.oldstyle
			updateHeader()
			draw()
		}
		else
		{
			input.style.color='#f22'
			head_text.data='Не верно'
			
		}
	}
	
	draw()
	
	cal_close.onclick=calendar_close	
	input.addEventListener( 'focus', input_onfocus, false)
	input.addEventListener( 'blur', calendar_close, false)
	input.addEventListener( 'keyup', input_onkeyup, false)
	
	return input
}