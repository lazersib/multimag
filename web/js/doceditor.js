function doceditor(doc_container_id, menu_container_id) {
    var doc = new Object;
    var container = document.getElementById(doc_container_id);
    var left_block;
    var base_url = '/doc.php';
    var base_data = 'mode=service';
    
    function onLoadError(code, message) {
        alert("Ошибка:\nКод:"+code+"\nСообщение:"+message);
    }
    
    function onLoadSuccess(response) {
        try {
            var json = JSON.parse(response);
            if(json.response=='err') {
                onLoadError('requestError', json.message);
                return;
            } else if(json.response == 'docheader') {
                doc.fillHeader(json.data);
            } else if(json.response == 'reset_prices') {
                var up = json.updated?' UPDATED':' NOT updated';
                alert('Reset prices: '+up);
            }
            else alert("Обработчик не задан:\n"+response);
        } catch(e) {
            onLoadError(e.name, e.message+response);
        }
    }  
    
    function insertOptionsArray(select_elem, data, selected_id, not_select_item) {
        var value;
        if(not_select_item) {
            var opt = newElement('option', select_elem, '', '--не задано--');
        }
        for(value in data) { 
            var opt = newElement('option', select_elem, '', data[value]);
            opt.value = value;
            if(value==selected_id) {
                opt.selected=true;
            }
        }
    }
    
    function insertOptionsList(select_elem, data, selected_id, not_select_item) {
        var i;
        if(not_select_item) {
            var opt = newElement('option', select_elem, '', '--не задано--');
        }
        for(i in data) { 
            var opt = newElement('option', select_elem, '', data[i].name);
            opt.value = data[i].id;
            if(data[i].id==selected_id) {
                opt.selected=true;
            }
        }
    }
    function insertContractList(select_elem, data, selected_id, not_select_item) {
        var i;
        if(not_select_item) {
            var opt = newElement('option', select_elem, '', '--не задано--');
        }
        for(i in data) { 
            var str = data[i].name + " N:" + data[i].altnum + data[i].subtype + ", от " + data[i].date;
            var opt = newElement('option', select_elem, '', str);
            opt.value = data[i].id;
            if(data[i].id==selected_id) {
                opt.selected=true;
            }
        }
    }
    
    function initStoreSelect() {
        var i;
        var firm_id = doc.i_firm_id.value;
        var select_elem = doc.i_store_id;
        var selected = false;
        select_elem.innerHTML = '';
        
        for(i in doc.header.store_list) {
            var line = doc.header.store_list[i];
            if(line.firm_id>0 && line.firm_id!=firm_id) {
                continue;
            }
            var opt = newElement('option', select_elem, '', line.name);
            opt.value = line.id;
            if(line.id==doc.header.store_id) {
                opt.selected = true;
                selected = true;
            }
        }
        if( (!doc.header.store_id) || (!selected)) {            
            var opt = newElement('option', select_elem, '', '--не задано--');
            opt.selected=true;
        }
    }
    
    function initBankSelect() {
        var i;
        var firm_id = doc.i_firm_id.value;
        var select_elem = doc.i_bank_id;
        var selected = false;
        select_elem.innerHTML = '';
        
        for(i in doc.header.bank_list) {
            var line = doc.header.bank_list[i];
            if(line.firm_id>0 && line.firm_id!=firm_id) {
                continue;
            }
            var opt = newElement('option', select_elem, '', line.name);
            opt.value = line.id;
            if(line.id==doc.header.bank_id) {
                opt.selected = true;
                selected = true;
            }
        }
        if( (!doc.header.bank_id) || (!selected)) {            
            var opt = newElement('option', select_elem, '', '--не задано--');
            opt.selected=true;
        }
    }
    
    function updateHeaderField() {
        doc_left_block.style.backgroundColor = '#ff8';
        
        var data = $('#doc_head_form').serialize();
        httpReq('/doc.php', 'POST', data, onLoadSuccess, onLoadError);
    }
    
    function updateFirmId() {
        doc.header.firm_id = doc.i_firm_id.value;
        initBankSelect();
        initStoreSelect();
        updateHeaderField();
    }
    
    
       
    doc.init = function(doc_id) {
        doc.id = doc_id;
        container.innerHTML = '';
        container.doc = doc;
        left_block = newElement('div', container, '', '');
        left_block.id = 'doc_left_block';
        var data = base_data + "&mode=srv&peopt=getheader&doc="+doc_id;
        httpReq(base_url, 'GET', data, onLoadSuccess, onLoadError);
    };
    
    doc.fillHeader = function(data) {
        var tmp;
        doc.header = data;
        var doc_name = newElement('h1', left_block, '', data.viewname);
        var template = "<input type='hidden' name='mode' value='jheads'>"
            + "<input type='hidden' name='doc' id='dochead_doc_id' value=''>"
            + "<input type='hidden' name='type' id='dochead_doc_type_id' value=''>"
            + "<div class='item'>"
            + "<img id='dochead_plus_altnum' src='/img/i_add.png' alt='Новый номер'></a>"
            + "<input type='text' name='altnum' id='dochead_altnum'>"
            + "<input type='text' name='subtype' id='dochead_subtype'>"
            + "<input type='text' name='datetime' id='dochead_datetime'>"
            + "</div>"
            + "<div class='item'>"
            + "<div>Организация:</div>"
            + "<select name='firm_id' id='dochead_firm_id'></select>"
            + "</div>";
        var templates = {
            price: "<div>Цена: <img src='/img/i_reload.png' id='dochead_reset_prices'></a></div>"
                + "<select name='price_id' id='dochead_price_id'></select>",
            store: "<div>Склад:</div>"
                + "<select name='store_id' id='dochead_store_id'></select>",
            cash: "<div>Касса:</div>"
                + "<select name='cash_id' id='dochead_cash_id'></select>",
            bank: "<div>Банк:</div>"
                + "<select name='bank_id' id='dochead_bank_id'></select>",
            agent: "<div style='float: right; $col' id='agent_balance_info'></div>"
		+ "Агент: <a href='/docs.php?l=agent&mode=srv&opt=ep&pos={$this->doc_data['agent']}' id='ag_edit_link' target='_blank'><img src='/img/i_edit.png'></a>"
		+ "<a href='/docs.php?l=agent&mode=srv&opt=ep' target='_blank'><img src='/img/i_add.png'></a><br>"
		+ "<input type='hidden' name='agent_id' id='dochead_agent_id' value=''>"
		+ "<input type='text' name='agent_name' id='dochead_agent_name' value=''>"
		+ "<div id='dochead_dishonest_info'>Был выбран недобросовестный агент!</div>",
            agent_contract: "<div>Договор с агентом:</div>"
		+ "<select name='contract_id' id='dochead_contract_id'></select>",
            comment: "<div>Комментарий:</div><textarea id='dochead_comment' name='comment'></textarea>"
        };
        var doc_head_form = newElement('form', left_block, '', template);
        doc_head_form.id = 'doc_head_form';
        
        tmp = document.getElementById('dochead_doc_id');
        tmp.value = data.id;        
        tmp = document.getElementById('dochead_doc_type_id');
        tmp.value = data.type;
        
        doc.i_altnum = document.getElementById('dochead_altnum');
        doc.i_altnum.value = data.altnum;        
        doc.i_subtype = document.getElementById('dochead_subtype');  
        doc.i_subtype.value = data.subtype;        
        doc.i_datetime = document.getElementById('dochead_datetime');  
        doc.i_datetime.value = data.date;        
        doc.i_firm_id = document.getElementById('dochead_firm_id');  
        insertOptionsArray(doc.i_firm_id, data.firm_list, data.firm_id);
        doc.i_firm_id.onchange = updateFirmId;
        
        var value;
        for(var i=0;i<data.header_fields.length;i++) { 
            switch(data.header_fields[i]) {
                case 'price':
                case 'cena':
                    var tmp = newElement('div', doc_head_form, 'item', templates.price);
                    doc.i_price_id = document.getElementById('dochead_price_id'); 
                    insertOptionsList(doc.i_price_id, data.price_list, data.price_id, true);
                    doc.i_price_id.onchange = updateHeaderField;
                    break;
                case 'store':
                case 'sklad':
                    var tmp = newElement('div', doc_head_form, 'item', templates.store);
                    doc.i_store_id = document.getElementById('dochead_store_id'); 
                    initStoreSelect();
                    doc.i_store_id.onchange = updateHeaderField;
                    break;
                case 'cash':
                case 'kassa':
                    var tmp = newElement('div', doc_head_form, 'item', templates.cash);
                    doc.i_cash_id = document.getElementById('dochead_cash_id'); 
                    insertOptionsList(doc.i_cash_id, data.cash_list, data.cash_id, true);
                    doc.i_cash_id.onchange = updateHeaderField;
                    break;
                case 'bank':
                    var tmp = newElement('div', doc_head_form, 'item', templates.bank);
                    doc.i_bank_id = document.getElementById('dochead_bank_id'); 
                    initBankSelect();
                    doc.i_bank_id.onchange = updateHeaderField;
                    break;
                case 'agent':
                    newElement('div', doc_head_form, 'item', templates.agent);
                    newElement('div', doc_head_form, 'item', templates.agent_contract);
                    $(document).ready(function(){
                        function agliFormat (row, i, num) {
                            var result = row[0];
                            return result;
                        }

                        function agselectItem(li) {
                            var sValue;
                            if( li == null ) sValue = "Ничего не выбрано!";
                            if( !!li.extra ) sValue = li.extra[0];
                            else sValue = li.selectValue;
                            document.getElementById('dochead_agent_id').value = sValue;
                            document.getElementById('ag_edit_link').href='/docs.php?l=agent&mode=srv&opt=ep&pos='+sValue;
                            var firm_id_elem = document.getElementById('firm_id');
                            var firm_id = 0;
                            if(firm_id_elem) {
                                firm_id = firm_id_elem.value;
                            }
                            updateHeaderField();
                        }
			$("#dochead_agent_name").autocomplete("/docs.php", {
				delay:300,
				minChars:1,
				matchSubset:1,
				autoFill:false,
				selectFirst:true,
				matchContains:1,
				cacheLength:10,
				maxItemsToShow:15,
				formatItem:agliFormat,
				onItemSelect:agselectItem,
				extraParams:{'l':'agent','mode':'srv','opt':'ac'}
			});
                        
                    });
                    doc.i_contract_id = document.getElementById('dochead_contract_id'); 
                    insertContractList(doc.i_contract_id, data.agent_info.contract_list, data.contract_id, true);
                    doc.i_contract_id.onchange = updateHeaderField;
                    break;
                case 'separator':
                    var tmp = newElement('div', doc_head_form, 'item', '<hr>');
                    break;
            }
        }
        newElement('div', doc_head_form, 'item', templates.comment);
        doc.i_comment = document.getElementById('dochead_comment');

    };
    
    
    
    
    return doc;
};
