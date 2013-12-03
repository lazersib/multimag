//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2013, BlackLight, TND Team, http://tndproject.org
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

// Работа с журналом документов
// Экспериментально!

function getCacheObject() {
	var mmCacheObject = new Object;
	mmCacheObject.storage = new Array;
	var ls_flag = 0;
	if(typeof(localStorage) != undefined )
		ls_flag = 1;
		
	mmCacheObject.set = function (name, object) {
		try {
			if(ls_flag)
				localStorage.setItem(name, JSON.stringify(object) );
			else	mmCacheObject.storage[name] = object;
		}
		catch (e) {
			if (e == QUOTA_EXCEEDED_ERR)
				alert('Место в локальном хранилище исчерпано');
		}
	}
	
	mmCacheObject.get = function (name) {
		try {
			if(ls_flag)
				return JSON.parse(localStorage.getItem(name));
			else	return mmCacheObject.storage[name];
		}
		catch (e) {
			return undefined;
		}
	};
	
	mmCacheObject.unset = function (name) {
		if(ls_flag)
			localStorage.removeItem(name);
		else	mmCacheObject.storage[name] = null;
	};
	
	return mmCacheObject;
}

function initDocJournal(container_id) {
	var container = document.getElementById(container_id);
	var doc_list_status = document.getElementById('doc_list_status');
	var doc_list_filter = document.getElementById('doc_list_filter');
	var stop = 0;
	var filter_request = '';
	var old_filter_request = '';
	var httpRequest;
	var deffer_timer;
	httpRequest = new XMLHttpRequest();
	var docj_list_body = document.getElementById('docj_list_body');
	
	cache = getCacheObject();
	
	var doc_types = cache.get('doctypes');

	function buildFilterQuery() {
		filter_request = '';
		var datepicker_f = document.getElementById('datepicker_f');
		if (datepicker_f.value.length)
			filter_request += '&df=' + encodeURIComponent(datepicker_f.value);
		var datepicker_t = document.getElementById('datepicker_t');
		if (datepicker_t.value.length)
			filter_request += '&dt=' + encodeURIComponent(datepicker_t.value);
		var altnum = document.getElementById('altnum');
		if (altnum.value.length)
			filter_request += '&an=' + encodeURIComponent(altnum.value);
		var subtype = document.getElementById('subtype');
		if (subtype.value.length)
			filter_request += '&st=' + encodeURIComponent(subtype.value);
		var firm_id = document.getElementById('firm_id');
		if (firm_id.value.length)
			filter_request += '&fi=' + encodeURIComponent(firm_id.value);
	}

	function restartRequest() {
		buildFilterQuery();
		if (old_filter_request != filter_request) {
			old_filter_request = filter_request;
			requestData(0);
		}
	}

	function beginDefferedRequest() {
		if (deffer_timer)
			clearTimeout(deffer_timer);
		stop = 1;
		httpRequest.abort();
		deffer_timer = window.setTimeout(restartRequest, 300);
	}

	function requestData(part) {
		doc_list_status.innerHTML = "Запрос...";
		//var url='/docj_new.php?mode=get&p='+part+filter_request;
		var componetns = 'doclist';
		if (!doc_types) {
			componetns = componetns + ',doctypes';
			alert('not doctypes');
		}
		
		var url = '/json.php?c=' + componetns;
		
		httpRequest.abort();
		httpRequest.onreadystatechange = receiveDataProcess;
		httpRequest.open('GET', url, true);
		httpRequest.send(null);

		function receiveDataProcess()
		{
			if (httpRequest.readyState == 4)
			{
				if (httpRequest.status == 200)
				{
					doc_list_status.innerHTML = "Ответ...";
					stop = 0;
					if (part == 0)
						docj_list_body.innerHTML = '';
					parseReceived(httpRequest.responseText, part)
				}
				else if (httpRequest.status)
					alert('ошибка ' + httpRequest.status)
			}
		}
	}



	function parseReceived(data, part) {
		try {
			//alert(data)
			doc_list_status.innerHTML = "Парсим...";
			var start = new Date()
			var json = JSON.parse(data);
			var end = new Date()
			json.eval = end.getTime() - start.getTime();
			doc_list_status.innerHTML = "Готово...";
			if (json.result == 'ok') {
				//alert('exec_time: '+json.exec_time)
				if(!doc_types) {
					doc_types = json.doctypes;
					cache.set('doctypes', json.doctypes);
				}
				render(json, part);
			}
			else
				alert(json.error)
		}
		catch (e)
		{
			alert(e.message)
		}
	}

	function render(data, part)
	{
		i = 0;
		var render_start_date = new Date
		doc_list_status.innerHTML = "обработано за " + data.eval + ", запрос выполнен за:" + data.exec_time;
		window.setTimeout(appendChunk, 0);
		//appendChunk();
		var pr_sum = 0
		var ras_sum = 0
		function appendChunk()
		{
			var date_start = new Date
			for (var c = 0; i < data.doclist.length; c++)
			{
				if (data.doclist[i].ok > 0)
				{
					switch (parseInt(data.doclist[i].type))
					{
						case 1:
						case 4:
						case 6:
							pr_sum += parseFloat(data.doclist[i].sum);
							break;
						case 2:
						case 5:
						case 7:
						case 18:
							ras_sum += parseFloat(data.doclist[i].sum);
							break;
					}
				}

				newLine(data.doclist[i], data.user_id)
				i++;
				if (i % 40)
					if (((new Date) - date_start) > 200)
					{
						window.setTimeout(appendChunk, 55);
						return;
					}
				if (stop)
					return;
			}
			if (!data.end) {
				function execRequest() {
					requestData(part + 1)
				}
				//window.setTimeout(execRequest, 120);
			}
			doc_list_status.innerHTML = "Итого: приход: " + pr_sum.toFixed(2) + ", расход: " + ras_sum.toFixed(2) + ". Баланс: " + (pr_sum - ras_sum).toFixed(2) + ", запрос выполнен за:" + data.exec_time + "сек, отображено за: " + ((new Date - render_start_date) / 1000).toFixed(2) + " сек";
		}

		//form_container.appendChild(fragment)
	}

	function newLine(line, user_id)
	{
		var tr = docj_list_body.insertRow(-1);
		var tr_class = 'pointer';
		if (line.author_id == 1)
			tr_class += ' lin11';
		//tr.className=tr_class;
		var html = "<td style='text-align: right;' class='" + line.num_highlight + "' onclick=\"window.open('/doc.php?mode=body&amp;doc=" + line.id + "'); return false;\">" + line.altnum + line.subtype + "</td><td onclick=\"window.open('/docj.php?mode=tree&amp;doc=" + line.id + "'); return false;\"><img src='img/i_tree.png' alt='Связи'></td><td>" + line.id + "</td><td>";
		if (line.ok > 0)
			html += "<img src='/img/i_suc.png' alt='Проведен'>";
		if (line.mark_del > 0)
			html += "<img src='/img/i_alert.png' alt='Помечен на удаление'>";

		html += "</td><td>" + doc_types[line.type] + "</td><td>" + line.data1 + "</td><td>" + line.agent_name + "</td><td style='text-align: right;'>" + line.sum + "</td><td>" + line.date + "</td><td onclick=\"window.open('/adm_users.php?mode=view&amp;id=" + line.author_id + "'); return false;\">" + line.author_name + "</td>";
		tr.innerHTML = html;
	}

	function initFilter(filter) {
		var s = "<div class='bf'><input type='text' class='half' name='date_from' id='datepicker_f' value='' placeholder='Дата от'>-<input type='text' class='half' name='date_to' id='datepicker_t' value='' placeholder='Дата до'></td></tr></table></div>";
		s += "<div class='bf'><input type='text' name='type' placeholder='Тип документа' disabled></div>";
		s += "<div class='bf'><input type='text' class='half' id='altnum' value='' placeholder='Альт. номер'>, <input type='text' class='half' id='subtype' value='' placeholder='Подтип'></div>";
		s += "<div class='bf'><input type='text' name='agent' id='agent_filter' placeholder='Агент'></div>";
		s += "<div class='bf'><input type='text' name='position' placeholder='Наименование' disabled></div>";
		s += "<div class='bf'><select id='firm_id'><option>Фирма 1</option></select></div>";
		s += "<div class='bf'><select id='bank_id'><option>Банк 1</option><option>Касса 1</option></select></div>";
		s += "<div class='bf'><select id='store_id'><option>Склад 1</option><option>Склад 2</option></select></div>";
		s += "<div class='bf'><input type='text' name='author' placeholder='Автор' disabled></div>";

		filter.innerHTML = s;
		var input = initCalendar('datepicker_f', false);
		input.addEventListener('blur', beginDefferedRequest, false);
		input.updateCallback = beginDefferedRequest;
		input = initCalendar('datepicker_t', false);
		input.addEventListener('blur', beginDefferedRequest, false);
		input.updateCallback = beginDefferedRequest;
		input = document.getElementById('altnum');
		input.addEventListener('blur', beginDefferedRequest, false);
		input.addEventListener('keyup', beginDefferedRequest, false);
		input = document.getElementById('subtype');
		input.addEventListener('blur', beginDefferedRequest, false);
		input.addEventListener('keyup', beginDefferedRequest, false);
		input = document.getElementById('firm_id');
		input.addEventListener('blur', beginDefferedRequest, false);
		input.addEventListener('change', beginDefferedRequest, false);
	}

	requestData(0);
	initFilter(doc_list_filter);
}

initDocJournal(document.getElementById('docj_list_body'));

