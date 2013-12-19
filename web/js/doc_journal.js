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

function autoCompleteField(input_id, data) {
	var ac_input = document.getElementById(input_id);
	var ac_result = document.createElement('div');
	ac_input.parentNode.insertBefore(ac_result, ac_input.nextSibling);
	//ac_result.style.cssText = "width: 200px; height: 200px; border: 1px #000 solid; background-color: #fff; position: relative; z-index: 999; overflow-x: hidden; overflow-y: auto;"
	ac_result.className = 'cac_results';
	var s='<ul>';
	for (var i in data) {
		s += "<li value='" + 1 + i + "'";
		s += ">" + data[i] + "</li>";
	}
	ac_result.innerHTML = s + '</ul>';
}

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
	var httpRequest = new XMLHttpRequest();;
	var deffer_timer;
	var docj_list_body = document.getElementById('docj_list_body');
	
	var cache = getCacheObject();
	
	var doc_types = cache.get('doctypes');
	var agentnames = cache.get('agentnames');
	var usernames = cache.get('usernames');
	var skladnames = cache.get('skladnames');
	var kassnames = cache.get('kassnames');
	var banknames = cache.get('banknames');
	var firmnames = cache.get('firmnames');
	

	function buildFilterQuery() {
		filter_request = '';
		var datepicker_f = document.getElementById('datepicker_f');
		if (datepicker_f.value.length)
			filter_request += '&doclist[df]=' + encodeURIComponent(datepicker_f.value);
		var datepicker_t = document.getElementById('datepicker_t');
		if (datepicker_t.value.length)
			filter_request += '&doclist[dt]=' + encodeURIComponent(datepicker_t.value);
		var altnum = document.getElementById('altnum');
		if (altnum.value.length)
			filter_request += '&doclist[an]=' + encodeURIComponent(altnum.value);
		var subtype = document.getElementById('subtype');
		if (subtype.value.length)
			filter_request += '&doclist[st]=' + encodeURIComponent(subtype.value);
		var firm_id = document.getElementById('firm_id');
		if (firm_id.value!=0)
			filter_request += '&doclist[fi]=' + encodeURIComponent(firm_id.value);
		var bk_id = document.getElementById('bk_id');
		if (bk_id.value!=0)
			filter_request += '&doclist[bk]=' + encodeURIComponent(bk_id.value);
		var store_id = document.getElementById('store_id');
		if (store_id.value!=0)
			filter_request += '&doclist[sk]=' + encodeURIComponent(store_id.value);
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
		if (!doc_types)
			componetns = componetns + ',doctypes';
		if (!agentnames)
			componetns = componetns + ',agentnames';
		if (!usernames)
			componetns = componetns + ',usernames';
		if (!skladnames)
			componetns = componetns + ',skladnames';
		if (!kassnames)
			componetns = componetns + ',kassnames';
		if (!banknames)
			componetns = componetns + ',banknames';
		if (!firmnames)
			componetns = componetns + ',firmnames';
		
		var url = '/json.php?c=' + componetns+filter_request;
		
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
			var start = new Date();
			var json = JSON.parse(data);
			var end = new Date();
			json.eval = end.getTime() - start.getTime();
			doc_list_status.innerHTML = "Готово...";
			if (json.result == 'ok') {
				//alert('exec_time: '+json.exec_time)
				var iff = 0;
				if(!doc_types) {
					doc_types = json.doctypes;
					cache.set('doctypes', json.doctypes);
					iff = 1;
				}
				if(!agentnames) {
					agentnames = json.agentnames;
					cache.set('agentnames', json.agentnames);
					iff = 1;
				}
				if(!usernames) {
					usernames = json.usernames;
					cache.set('usernames', json.usernames);
					iff = 1;
				}
				if(!skladnames) {
					skladnames = json.skladnames;
					cache.set('skladnames', json.skladnames);
					iff = 1;
				}
				if(!kassnames) {
					kassnames = json.kassnames;
					cache.set('kassnames', json.kassnames);
					iff = 1;
				}
				if(!banknames) {
					banknames = json.banknames;
					cache.set('banknames', json.banknames);
					iff = 1;
				}
				if(!firmnames) {
					firmnames = json.firmnames;
					cache.set('firmnames', json.firmnames);
					iff = 1;
				}
				if(iff)
					initFilter(doc_list_filter);
				render(json, part);
			}
			else	alert(json.error)
		}
		catch (e){
			alert(e.message);
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
						window.setTimeout(appendChunk, 5);
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
	
	function infoCell(name, value) {
		return "<div class='ic'>" + name + ":</div> " + value;
	}

	function newLine(line, user_id)
	{
		var tr = docj_list_body.insertRow(-1);
		var tr_class = 'pointer';
		if (line.author_id == user_id)
			tr_class += ' lin11';
		var source = '';
		var target = '';
		switch(parseInt(line.type)) {
			case 1:	source = infoCell("Агент", agentnames[line.agent_id]);
				target =  infoCell("Склад", skladnames[line.sklad_id]);
				break;
			case 2: 
			case 20:source = infoCell("Склад", skladnames[line.sklad_id]);
				target = infoCell("Агент", agentnames[line.agent_id]);
				break;
			case 3:	
			case 11:
			case 12:source = infoCell("Агент", agentnames[line.agent_id]);
				target = infoCell("Фирма", firmnames[line.firm_id]);
				break;
			case 4:	source = infoCell("Агент", agentnames[line.agent_id]);
				target = infoCell("Банк", banknames[line.bank_id]);
				break;
			case 5:	source = infoCell("Банк", banknames[line.bank_id]);
				target = infoCell("Агент", agentnames[line.agent_id]);
				break;
			case 6:	source = infoCell("Агент", agentnames[line.agent_id]);
				target = infoCell("Касса", kassnames[line.kassa_id]);
				break;
			case 7:	source = infoCell("Касса", kassnames[line.kassa_id]);
				target = infoCell("Агент", agentnames[line.agent_id]);
				break;
			case 8:	source = infoCell("Склад", skladnames[line.sklad_id]);
				target = infoCell("Склад", skladnames[line.nasklad_id]);
				break;
			case 9:	source = infoCell("Касса", kassnames[line.kassa_id]);
				target = infoCell("Касса", kassnames[line.vkassu_id]);
				break;
			case 10:
			case 18:
			case 19:source = infoCell("Фирма", firmnames[line.firm_id]);
				target = infoCell("Агент", agentnames[line.agent_id]);
				break;
			case 13:
			case 14:
			case 15:
			case 16:source = infoCell("Фирма", firmnames[line.firm_id]);
				target = infoCell("Агент", agentnames[line.agent_id]);
				break;
			case 17:source = infoCell("Склад", skladnames[line.sklad_id]);
				target = infoCell("Склад", skladnames[line.sklad_id]);
				break;
			case 21:source = infoCell("Фирма", firmnames[line.firm_id]);
				target = infoCell("Склад", skladnames[line.sklad_id]);
				break;
		}
		
		
		var html = "<td style='text-align: right;' class='" + line.num_highlight + "' onclick=\"window.open('/doc.php?mode=body&amp;doc=" + line.id + "'); return false;\">" + line.altnum + line.subtype + "</td><td onclick=\"window.open('/docj.php?mode=tree&amp;doc=" + line.id + "'); return false;\"><img src='img/i_tree.png' alt='Связи'></td><td>" + line.id + "</td><td>";
		if (line.ok > 0)
			html += "<img src='/img/i_suc.png' alt='Проведен'>";
		if (line.mark_del > 0)
			html += "<img src='/img/i_alert.png' alt='Помечен на удаление'>";

		html += "</td><td>" + doc_types[line.type] + "</td><td>" + source + "</td><td>" + target + "</td><td style='text-align: right;'>" + line.sum + "</td><td>" + line.date + "</td><td onclick=\"window.open('/adm_users.php?mode=view&amp;id=" + line.author_id + "'); return false;\">" + usernames[line.author_id] + "</td>";
		tr.innerHTML = html;
	}

	function initFilter(filter) {
		function getSelectOptions(array, def_value, val_prefix) {
			var s = "";
			for (var i in array) {
				s += "<option value='" + val_prefix + i + "'";
				if(i == def_value)
					s += " selected";
				s += ">" + array[i] + "</option>";
			}
			return s;
		}
		
		var s = "<div class='bf'><input type='text' class='half' name='date_from' id='datepicker_f' value='' placeholder='Дата от'>-<input type='text' class='half' name='date_to' id='datepicker_t' value='' placeholder='Дата до'></td></tr></table></div>";
		s += "<div class='bf'><input type='text' name='type' placeholder='Тип документа' disabled></div>";
		s += "<div class='bf'><input type='text' class='half' id='altnum' value='' placeholder='Альт. номер'>, <input type='text' class='half' id='subtype' value='' placeholder='Подтип'></div>";
		s += "<div class='bf'><input type='text' name='agent' id='agent_filter' placeholder='Агент'></div>";
		s += "<div class='bf'><input type='text' name='position' placeholder='Наименование' disabled></div>";
		s += "<div class='bf'><select id='firm_id'><option value='0'>- фирма не выбрана -</option>";
		s += getSelectOptions(firmnames, 0, '');
		s += "</select></div>";
		s += "<div class='bf'><select id='bk_id'><option value='0'>- касса / банк не выбраны -</option>";
		s += getSelectOptions(banknames, 0, 'b');
		s += getSelectOptions(kassnames, 0, 'k');
		s += "</select></div>";
		s += "<div class='bf'><select id='store_id'><option value='0'>- склад не выбран -</option>";
		s += getSelectOptions(skladnames, 0, '');
		s += "</select></div>";
		s += "<div class='bf'><input type='text' name='author' placeholder='Автор' disabled></div>";

		
		filter.innerHTML = s;
		var input = initCalendar('datepicker_f', false);
		input.addEventListener('blur', beginDefferedRequest, false);
		//input.updateCallback = beginDefferedRequest;
		input = initCalendar('datepicker_t', false);
		input.addEventListener('blur', beginDefferedRequest, false);
		//input.updateCallback = beginDefferedRequest;
		input = document.getElementById('altnum');
		input.addEventListener('blur', beginDefferedRequest, false);
		input.addEventListener('keyup', beginDefferedRequest, false);
		input = document.getElementById('subtype');
		input.addEventListener('blur', beginDefferedRequest, false);
		input.addEventListener('keyup', beginDefferedRequest, false);
		input = document.getElementById('firm_id');
		input.addEventListener('blur', beginDefferedRequest, false);
		input.addEventListener('change', beginDefferedRequest, false);
		input = document.getElementById('bk_id');
		input.addEventListener('blur', beginDefferedRequest, false);
		input.addEventListener('change', beginDefferedRequest, false);
		input = document.getElementById('store_id');
		input.addEventListener('blur', beginDefferedRequest, false);
		input.addEventListener('change', beginDefferedRequest, false);
		
		autoCompleteField('agent_filter', agentnames);
	}

	requestData(0);
	initFilter(doc_list_filter);
}

initDocJournal(document.getElementById('docj_list_body'));

