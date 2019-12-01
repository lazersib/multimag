//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2018, BlackLight, TND Team, http://tndproject.org
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

function docTypeMultiSelect(div_id, data, update_callback) {
    var hidden = 1;

    var base_div = document.getElementById(div_id);
    base_div.values = new Array();

    var done_button = document.createElement('div');
    base_div.parentNode.insertBefore(done_button, base_div.nextSibling);
    done_button.className = 'multiselect_done';

    var clear_button = document.createElement('div');
    base_div.parentNode.insertBefore(clear_button, base_div.nextSibling);
    clear_button.className = 'multiselect_clear';

    var ac_result = document.createElement('div');
    base_div.parentNode.insertBefore(ac_result, base_div.nextSibling);

    hideList();
    buildList();
    ac_result.className = 'doctype_multiselect';

    var hide_timer = 0;

    function buildList() {
        var s = '';
        for (var i in data) {
            s += "<label><input type='checkbox' value='" + i + "'>" + i + ": " + data[i] + "</label><br>";
            base_div.values[i] = 0;
        }
        ac_result.innerHTML = s;
    }

    function showList() {
        ac_result.style.display = 'block';
        clear_button.style.display = 'block';
        hidden = 0;
    }

    function hideList() {
        if (hide_timer)
            window.clearTimeout(hide_timer);
        ac_result.style.display = 'none';
        clear_button.style.display = 'none';
        hidden = 1;
    }

    // События строки
    base_div.onclick = function (event) {
        if (hidden)
            showList();
        else {
            update_callback();
            hideList();
        }
    };

    // События списка
    ac_result.onclick = function (event) {
        if (hide_timer)
            window.clearTimeout(hide_timer);
        if (event.target.tagName != 'INPUT') {
            return;
        }

        if (event.target.checked)
            base_div.values[event.target.value] = 1;
        else
            base_div.values[event.target.value] = 0;
        update_callback();
    };

    // Скролл блока
    ac_result.onscroll = function (event) {
        if (hide_timer)
            window.clearTimeout(hide_timer);
    };

    // События кнопки clear
    clear_button.onclick = function () {
        if (hide_timer)
            window.clearTimeout(hide_timer);

        var item = ac_result.firstChild;
        while (item) {
            //alert(item.firstChild.tagName);
            if (item.tagName == 'LABEL')
                if (item.firstChild.tagName == 'INPUT')
                {
                    item.firstChild.checked = false;
                    base_div.values[item.firstChild.value] = 0;
                }

            item = item.nextSibling;
        }

        update_callback();
    };

    done_button.onclick = function (event) {
        hideList();
        update_callback();
    };
}

function initDocJournal(container_id, default_filters, params) {
    var container = document.getElementById(container_id);
    var doc_list_status = document.getElementById('doc_list_status');
    var doc_list_filter = document.getElementById('doc_list_filter');
    var stop = 0;
    var filter_request = '';
    var old_filter_request = '';
    var httpRequest = new XMLHttpRequest();

    let sortableFields = [];
    let fieldsWithCnt = [
        {
            attributes: {
                width: 55
            },
            html: 'a.№'
        },
	    {
		    attributes: {
			    width: 20
		    },
		    html: ''
	    },
	    {
		    attributes: {
			    width: 20
		    },
		    html: ''
	    },
	    {
		    html: 'Тип'
	    },
	    {
		    html: 'Участник 1'
	    },
	    {
		    html: 'Участник 2'
	    },
	    {
		    html: 'Кол-во'
	    },
	    {
		    html: 'Цена'
	    },
	    {
		    attributes: {
		        class: 'js-order-filter'
		    },
            field: 'sum',
            order: 'asc',
		    html: 'Сумма'
	    },
	    {
		    attributes: {
			    class: 'js-order-filter'
		    },
		    field: 'date',
		    order: 'asc',
		    html: 'Дата'
	    },
	    {
		    html: 'Автор'
	    },
	    {
		    html: 'id'
	    },
    ];

	let fields = [
		{
			attributes: {
				width: 55
			},
			html: 'a.№'
		},
		{
			attributes: {
				width: 20
			},
			html: ''
		},
		{
			attributes: {
				width: 20
			},
			html: ''
		},
		{
			html: 'Тип'
		},
		{
			html: 'Участник 1'
		},
		{
			html: 'Участник 2'
		},
		{
			attributes: {
				class: 'js-order-filter'
			},
			field: 'sum',
			order: 'asc',
			html: 'Сумма'
		},
		{
			attributes: {
				class: 'js-order-filter'
			},
			field: 'date',
			order: 'asc',
			html: 'Дата'
		},
		{
			html: 'Автор'
		},
		{
			html: 'id'
		},
	];


    var deffer_timer;
    var docj_list_body = document.getElementById('docj_list_body');

    var cache = getCacheObject();

    var doc_types = cache.get('docnames');
    var agentnames = cache.get('agentnames');
    var usernames = cache.get('usernames');
    var skladnames = cache.get('skladnames');
    var kassnames = cache.get('kassnames');
    var banknames = cache.get('banknames');
    var firmnames = cache.get('firmnames');
    var posnames = cache.get('posnames');


    var pr_sum = 0;
    var ras_sum = 0;
    var show_count_column = 0;
    var pr_cnt = 0;
    var ras_cnt = 0;
    var selected_store = 0;
    var order_sum = 0;

    function buildFilterQuery() {
        filter_request = '';
        var datepicker_f = document.getElementById('datepicker_f');
        if (datepicker_f.value.length)
            filter_request += '&doclist[df]=' + encodeURIComponent(datepicker_f.value);

        var datepicker_t = document.getElementById('datepicker_t');
        if (datepicker_t.value.length)
            filter_request += '&doclist[dt]=' + encodeURIComponent(datepicker_t.value);

        var docnames_t = document.getElementById('doctype_filter');
        var f_values = '';
        var active_1 = 0;
        var active_0 = 0;
        for (var i = 1; i < docnames_t.values.length; i++) {
            f_values += '&doclist[dct][' + i + ']=' + encodeURIComponent(docnames_t.values[i]);
            if (docnames_t.values[i])
                active_1 = 1;
            else
                active_0 = 1;
        }
        if (active_1 & active_0)
            filter_request += f_values;

        var altnum = document.getElementById('altnum');
        if (altnum.value.length)
            filter_request += '&doclist[an]=' + encodeURIComponent(altnum.value);

        var subtype = document.getElementById('subtype');
        if (subtype.value.length)
            filter_request += '&doclist[st]=' + encodeURIComponent(subtype.value);

        var firm_id = document.getElementById('firm_id');
        if (firm_id.value != 0)
            filter_request += '&doclist[fi]=' + encodeURIComponent(firm_id.value);

        var bk_id = document.getElementById('bk_id');
        if (bk_id.value != 0)
            filter_request += '&doclist[bk]=' + encodeURIComponent(bk_id.value);

        var store_id = document.getElementById('store_id');
        if (store_id.value != 0)
            filter_request += '&doclist[sk]=' + encodeURIComponent(store_id.value);
        selected_store = store_id.value;

        var afilter_id = document.getElementById('agent_filter');
        if (afilter_id.value_id != 0)
            filter_request += '&doclist[ag]=' + encodeURIComponent(afilter_id.value_id);

        var posfilter_id = document.getElementById('pos_filter');
        if (posfilter_id.value_id != 0) {
            filter_request += '&doclist[pos]=' + encodeURIComponent(posfilter_id.value_id);
            show_count_column = 1;
        } else
            show_count_column = 0;

        var userfilter_id = document.getElementById('user_filter');
        if (userfilter_id.value_id != 0)
            filter_request += '&doclist[au]=' + encodeURIComponent(userfilter_id.value_id);

        var okfilter_id = document.getElementById('ok_filter');
        if (okfilter_id.value != '0')
            filter_request += '&doclist[ok]=' + encodeURIComponent(okfilter_id.value);

        if(sortableFields) {
            let orderQuery = [];
            sortableFields.forEach(function(item){
               if(item.hasOwnProperty('order') && item.hasOwnProperty('field')) {
                   orderQuery.push({
                       'field': item.field,
                       'order': item.order
                   });
               }
            });
	        filter_request += '&doclist[order]=' + encodeURIComponent(JSON.stringify(orderQuery));
        }
    }

    function restartRequest() {
        buildFilterQuery();
        if (old_filter_request != filter_request) {
            old_filter_request = filter_request;
            pr_sum = 0;
            ras_sum = 0;
            pr_cnt = 0;
            ras_cnt = 0;
            order_sum = 0;
            initTableHead();
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
            componetns = componetns + ',docnames';
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
        if (!posnames)
            componetns = componetns + ',posnames';

        var url = '/json.php?c=' + componetns + filter_request + '&p/doclist=' + part;

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
                if (!doc_types) {
                    doc_types = json.docnames;
                    cache.set('docnames', json.docnames, 3600);
                    iff = 1;
                }
                if (!agentnames) {
                    agentnames = json.agentnames;
                    cache.set('agentnames', json.agentnames, 30);
                    iff = 1;
                }
                if (!usernames) {
                    usernames = json.usernames;
                    cache.set('usernames', json.usernames, 60);
                    iff = 1;
                }
                if (!skladnames) {
                    skladnames = json.skladnames;
                    cache.set('skladnames', json.skladnames, 3600);
                    iff = 1;
                }
                if (!kassnames) {
                    kassnames = json.kassnames;
                    cache.set('kassnames', json.kassnames, 3600);
                    iff = 1;
                }
                if (!banknames) {
                    banknames = json.banknames;
                    cache.set('banknames', json.banknames, 3600);
                    iff = 1;
                }
                if (!firmnames) {
                    firmnames = json.firmnames;
                    cache.set('firmnames', json.firmnames, 3600);
                    iff = 1;
                }
                if (!posnames) {
                    posnames = json.posnames;
                    cache.set('posnames', json.posnames, 60);
                    iff = 1;
                }

                if (iff)
                    initFilter(doc_list_filter);
                render(json, part);
            }
            else
                alert(json.error)
        }
        catch (e) {
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

        function appendChunk()
        {
            var date_start = new Date
            for (var c = 0; i < data.doclist.length; c++)
            {
                if (parseFloat(data.doclist[i].ok) > 0) {
                    switch (parseInt(data.doclist[i].type)) {
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
                        case 3:
                            order_sum += parseFloat(data.doclist[i].sum);
                    }
                    if (show_count_column) {
                        switch (parseInt(data.doclist[i].type)) {
                            case 1:
                                pr_cnt += parseFloat(data.doclist[i].pos_cnt);
                                break;
                            case 2:
                                ras_cnt += parseFloat(data.doclist[i].pos_cnt);
                                break;
                            case 8:
                                if (selected_store) {
                                    if (selected_store == data.doclist[i].sklad_id)
                                        ras_cnt += parseFloat(data.doclist[i].pos_cnt);
                                    if (selected_store == data.doclist[i].nasklad_id)
                                        pr_cnt += parseFloat(data.doclist[i].pos_cnt);
                                }
                                break;
                            case 17:
                                if (data.doclist[i].pos_page == 0)
                                    pr_cnt += parseFloat(data.doclist[i].pos_cnt);
                                else
                                    ras_cnt += parseFloat(data.doclist[i].pos_cnt);
                                break;
                            case 25:
                                var tmpcnt = parseFloat(data.doclist[i].pos_cnt);
                                if(tmpcnt>0) {
                                    pr_cnt += tmpcnt;
                                }
                                else {
                                    ras_cnt += tmpcnt;
                                }                                
                        }
                    }
                }

                newLine(data.doclist[i], data.user_id);

                i++;
                if (i % 40)
                    if (((new Date) - date_start) > 50)
                    {
                        window.setTimeout(appendChunk, 5);
                        return;
                    }
                if (stop)
                    return;
            }
            if (!data.doclist_end) {
                function execRequest() {
                    //alert('req '+(part+1));
                    requestData(part + 1);
                }
                if (part < 40)
                    window.setTimeout(execRequest, 120);
            }
            var status_text = "<b>Итого</b>: приход: <b>" + pr_sum.toFixed(2) + "</b> р., расход: <b>" + ras_sum.toFixed(2) + "</b> р." +
                    "Баланс: <b>" + (pr_sum - ras_sum).toFixed(2) + "</b> р. Заявок покупателя на: <b>" + (order_sum).toFixed(2) + "</b> р.";
            if (show_count_column)
                status_text += "<br>Приход товара: <b>" + pr_cnt.toFixed(2) + "</b>, расход товара: <b>" + ras_cnt.toFixed(2) + "</b>. Разность: " + (pr_cnt - ras_cnt).toFixed(2);

            status_text += "<br>Запрос выполнен за:" + data.exec_time + "сек, отображено за: " + ((new Date - render_start_date) / 1000).toFixed(2) + " сек"
            doc_list_status.innerHTML = status_text;
        }

        //form_container.appendChild(fragment)
    }

    function infoCell(name, value) {
        return "<div class='ic'>" + name + ":</div> " + value;
    }

    function newLine(line, user_id) {
        var tr = docj_list_body.insertRow(-1);

        var tr_class = '';
        var num_class = '';
        if (line.author_id == user_id)
            tr_class += ' u';
        if (line.err_flag != 0)
            tr_class += ' f_red';
        else if (line.subtype == 'site')
            tr_class += ' f_green';

        var source = infoCell("Агент", agentnames[line.agent_id]);
        var target = '';
        switch (parseInt(line.type)) {
            case 1:
                target = infoCell("Склад", skladnames[line.sklad_id]);
                break;
            case 2:
                if (Number(line.sum) > 0) {
                    if (Number(line.pay_sum) > Number(line.sum))
                        num_class = 'f_purple';
                    else if (Number(line.pay_sum) == Number(line.sum))
                        num_class = 'f_green';
                    else if (Number(line.pay_sum) == 0)
                        num_class = 'f_red';
                    else
                        num_class = 'f_brown';
                }
                // тут не нужен break!
            case 20:
                target = infoCell("Склад", skladnames[line.sklad_id]);
                break;
            case 3:
                if (line.out_status == 'a')
                    num_class = 'f_green';
                else if (line.out_status == 'p')
                    num_class = 'f_brown';
                // тут не нужен break!
            case 11:
            case 12:
                target = infoCell("Фирма", firmnames[line.firm_id]);
                break;
            case 4:
                target = infoCell("Банк", banknames[line.bank_id]);
                break;
            case 5:
                target = infoCell("Банк", banknames[line.bank_id]);
                break;
            case 6:
                target = infoCell("Касса", kassnames[line.kassa_id]);
                break;
            case 7:
                target = infoCell("Касса", kassnames[line.kassa_id]);
                break;
            case 8:
                source = infoCell("Склад", skladnames[line.sklad_id]);
                target = infoCell("Склад", skladnames[line.nasklad_id]);
                break;
            case 9:
                source = infoCell("Касса", kassnames[line.kassa_id]);
                target = infoCell("Касса", kassnames[line.vkassu_id]);
                break;
            case 10:
            case 18:
            case 19:
                target = infoCell("Фирма", firmnames[line.firm_id]);
                break;
            case 13:
            case 14:
            case 15:
            case 16:
                target = infoCell("Фирма", firmnames[line.firm_id]);
                break;
            case 17:
                source = infoCell("Склад", skladnames[line.sklad_id]);
                target = infoCell("Склад", skladnames[line.sklad_id]);
                break;
            case 21:
                source = infoCell("Фирма", firmnames[line.firm_id]);
                target = infoCell("Склад", skladnames[line.sklad_id]);
                break;
        }

        var link = '';
        if (params.no_new_page)
            link = "window.location = '/doc.php?mode=body&amp;doc=" + line.id + "';";
        else
            link = "window.open('/doc.php?mode=body&amp;doc=" + line.id + "');";
        var html = "<td style='text-align: right;' class='" + num_class + "' onclick=\"" + link + " return false;\">" + line.altnum + line.subtype + "</td><td onclick=\"window.open('/doc.php?mode=tree&amp;doc=" + line.id + "'); return false;\"><img src='img/i_tree.png' alt='Связи'></td><td>";
        if (line.ok > 0)
            html += "<img src='/img/i_suc.png' alt='Проведен'>";
        if (line.mark_del > 0)
            html += "<img src='/img/i_alert.png' alt='Помечен на удаление'>";

        html += "</td><td>" + doc_types[line.type] + "</td><td>" + source + "</td><td>" + target + "</td>";
        if (show_count_column)
            html += "<td style='text-align: right;'>" + line.pos_cnt + " / " + line.pos_page + "<td style='text-align: right;'>" + line.pos_cost + "</td>";

        html += "<td style='text-align: right;'>" + line.sum + "</td><td>" + line.date + "</td><td onclick=\"window.open('/adm.php?mode=users&sect=view&user_id=" + line.author_id + "'); return false;\">" + usernames[line.author_id] + "</td><td>" + line.id + "</td>";
        tr.innerHTML = html;
        tr.className = tr_class;
    }

    function buldHeaderFields(data) {
        let tr = document.createElement("tr");
        data.forEach(function (item, i) {
	        let th = document.createElement("th");
	        th.innerText = item.html;
	        if(item.hasOwnProperty('attributes')) {
	            if(item.attributes.hasOwnProperty('class')){
		            th.classList = item.attributes.class ? item.attributes.class : '';
                }
            }
	        if(item.hasOwnProperty('order')) {
		        th.style = 'background: url(/img/i_orderarrows.png) 100% '
                    +(item.order === 'asc' ? '' : '10')
                    +'0% no-repeat transparent; background-color: #6488DC; cursor: pointer';
	        }
	        if(item.hasOwnProperty('field')) {
		        th.dataset.field = item.field;
	        }
	        th.dataset.fieldId = i;
	        tr.appendChild(th);
        });
	    return tr.innerHTML;
    }


    function initTableHead() {
        var head = document.getElementById('doc_list_head');
        if (show_count_column) {
            head.innerHTML = buldHeaderFields(fieldsWithCnt);
        }
        else {
            head.innerHTML = buldHeaderFields(fields);
        }
	    let orderFilter = function(e) {
            let data = show_count_column ? fieldsWithCnt : fields;
            let self = this;
		    sortableFields = sortableFields.filter(function(val){
			    return val.field !== data[self.dataset.fieldId].field;
		    });
		    sortableFields.unshift({
			    field: data[this.dataset.fieldId].field,
			    order: data[this.dataset.fieldId].order === 'asc' ? 'desc' : 'asc',
            });
		    data[this.dataset.fieldId].order = data[this.dataset.fieldId].order === 'asc' ? 'desc' : 'asc';
		    beginDefferedRequest();
	    };
	    document.querySelectorAll("th.js-order-filter").forEach(box => { box.addEventListener('click', orderFilter, false); });
    }

    function initFilter(filter) {
        function getSelectOptions(array, def_value, val_prefix) {
            var s = "";
            for (var i in array) {
                s += "<option value='" + val_prefix + i + "'";
                if (i == def_value)
                    s += " selected";
                s += ">" + array[i] + "</option>";
            }
            return s;
        }
        var date_from = '';
        var date_to = '';
        if (default_filters) {
            if (default_filters.dateFrom)
                date_from = default_filters.dateFrom;
            if (default_filters.dateTo)
                date_to = default_filters.dateTo;
        }

        var s = "<div class='bf'><input type='text' class='half' name='date_from' id='datepicker_f' value='" + date_from + "' placeholder='Дата от'>-<input type='text' class='half' name='date_to' id='datepicker_t' value='" + date_to + "' placeholder='Дата до'></td></tr></table></div>";
        s += "<div class='bf'><div class='input multiselect_div' id='doctype_filter' placeholder='Тип документа'>Тип документа</div></div>";
        s += "<div class='bf'><input type='text' class='half' id='altnum' value='' placeholder='Альт. номер'>, <input type='text' class='half' id='subtype' value='' placeholder='Подтип'></div>";
        s += "<div class='bf'><input type='text' name='agent' id='agent_filter' placeholder='Агент'></div>";
        s += "<div class='bf'><input type='text' name='position' id='pos_filter' placeholder='Наименование'></div>";
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
        s += "<div class='bf'><input type='text' id='user_filter' placeholder='Автор'></div>";
        s += "<div class='bf'><select id='ok_filter'><option value='0'>Все документы</option><option value='+'>Только проведённые</option><option value='-'>Только непроведённые</option></div>";

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
        input = document.getElementById('ok_filter');
        input.addEventListener('blur', beginDefferedRequest, false);
        input.addEventListener('change', beginDefferedRequest, false);

        autoCompleteField('agent_filter', agentnames, beginDefferedRequest);
        autoCompleteField('pos_filter', posnames, beginDefferedRequest);
        autoCompleteField('user_filter', usernames, beginDefferedRequest);
        docTypeMultiSelect('doctype_filter', doc_types, beginDefferedRequest);

        if (default_filters) {
            if (default_filters.agentId) {
                var agent_input = document.getElementById('agent_filter');
                agent_input.value = default_filters.agentName;
                agent_input.value_id = default_filters.agentId;
            }
            if (default_filters.posId) {
                var pos_input = document.getElementById('pos_filter');
                pos_input.value = default_filters.posName;
                pos_input.value_id = default_filters.posId;
            }
        }


    }

    container.print = function () {
        buildFilterQuery();
        window.open('/docj_new.php?mode=print&' + filter_request);
    };

    initFilter(doc_list_filter);
    buildFilterQuery();
    initTableHead();
    requestData(0);
    old_filter_request = filter_request;
    initFilter(doc_list_filter);
    return container;
}
