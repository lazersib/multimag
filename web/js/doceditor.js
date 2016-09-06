function doceditor(doc_container_id, menu_container_id) {
    var doc = new Object;
    var container = document.getElementById(doc_container_id);
    var left_block;
    var cache = getCacheObject();
    doc.agentnames = cache.get('agentnames');
    doc.element_classname = 'item';
    doc.label_classname = 'label';
    doc.input_id_prefix = 'dochead_';
    
    function onLoadError(name, data) {
        alert("Ошибка:\n"+name+"\nСообщение:"+data.errorMessage);
    }
        
    function onLoadSuccess(response) {
        if(response.object == 'document') {
            if(response.action=='get') {
                doc.fillHeader(response.content.header);
            }
            else {
                //alert('document:action: '+response.action);
            }
        } else if(response.object == 'reset_prices') {
            var up = response.updated?' UPDATED':' NOT updated';
            alert('Reset prices: '+up);
        }
        else alert("Обработчик не задан:\n"+response);
    }  
    
    function insertOptionsArray(select_elem, data, selected_id, not_select_item) {
        var value;
        if(not_select_item) {
            var opt = newElement('option', select_elem, '', '--не задано--');
            opt.value='null';
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
            opt.value='null';
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
            opt.value='null';
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
            opt.value='null';
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
            opt.value='null';
            opt.selected=true;
            opt.className="error";
            select_elem.className="error";
        }
    }
    
    function initAgentField() {
        function agSelectItem() {
            var agent_id = document.getElementById('dochead_agent_name').value_id
            document.getElementById('dochead_agent_id').value = agent_id;
            document.getElementById('ag_edit_link').href='/docs.php?l=agent&mode=srv&opt=ep&pos='+agent_id;
            onChangeHeaderField();
        }
        autoCompleteField('dochead_agent_name', doc.agentnames, agSelectItem);    
        doc.i_agent_id = document.getElementById('dochead_agent_id'); 
        doc.i_agent_id.value = doc.header.agent_id;
        doc.i_agent_name = document.getElementById('dochead_agent_name'); 
        doc.i_agent_name.value = doc.header.agent_info.name;
        doc.l_agent_balance_info = document.getElementById('agent_balance_info'); 
        doc.l_agent_balance_info.innerHTML = doc.header.agent_info.balance + "р. / "+doc.header.agent_info.bonus +"б.";
        doc.l_dochead_dishonest_info = document.getElementById('dochead_dishonest_info'); 
        if(doc.header.agent_info.dishonest!="0") {
            doc.l_dochead_dishonest_info.style.display = "block";
        } else {
            doc.l_dochead_dishonest_info.style.display = "none";
        }
        document.getElementById('ag_edit_link').href='/docs.php?l=agent&mode=srv&opt=ep&pos='+doc.header.agent_id;
        
        doc.i_contract_id = document.getElementById('dochead_contract_id'); 
        insertContractList(doc.i_contract_id, doc.header.agent_info.contract_list, doc.header.contract_id, true);
    }
    
    function onChangeHeaderField() {
        doc_left_block.style.backgroundColor = '#ff8';
        var fstruct = formToArray();
        delete fstruct['agent_name'];
        mm_api.document.update(fstruct,onLoadSuccess, onLoadError);
        
        //var data = $('#doc_head_form').serialize();        
        //httpReq('/api.php', 'POST', data, onLoadSuccess, onLoadError);
    }
    
    function onUpdateFirmId() {
        doc.header.firm_id = doc.i_firm_id.value;
        initBankSelect();
        initStoreSelect();
        onChangeHeaderField();
    }
    
    function onChangeBankField() {
        var select_elem = doc.i_bank_id;
        if(select_elem.value>0) {
            select_elem.className="";
            doc.header.bank_id = select_elem.value;
            initBankSelect();
        }
        onChangeHeaderField();
    }
    
    function formToArray() {
        var obj = new Object();
        var elems = left_block.getElementsByTagName('input');
        for (var i = 0; i < elems.length; i++) {
            var input = elems[i];
            obj[input.name] = input.value;
        }
        elems = left_block.getElementsByTagName('select');
        for (var i = 0; i < elems.length; i++) {
            var input = elems[i];
            obj[input.name] = input.value;
        }
        elems = left_block.getElementsByTagName('textarea');
        for (var i = 0; i < elems.length; i++) {
            var input = elems[i];
            obj[input.name] = input.value;
        }
        elems = left_block.getElementsByTagName('checkbox');
        for (var i = 0; i < elems.length; i++) {
            var input = elems[i];            
            obj[input.name] = input.checked;
        }
        
        return obj;
    }
    
       
    doc.init = function(doc_id) {
        doc.id = doc_id;
        container.innerHTML = '';
        container.doc = doc;
        left_block = newElement('div', container, '', '');
        left_block.id = 'doc_left_block';
        mm_api.document.get({id:doc_id},onLoadSuccess, onLoadError);
    };
    
    function newTextElement(name, value, options) {
        var rootElement = document.createElement('div');
        rootElement.className = doc.element_classname;
        var labelElement = document.createElement('div');
        labelElement.className = doc.label_classname;
        var label = document.createTextNode(options.label+':'); 
        labelElement.appendChild(label);
        rootElement.appendChild(labelElement); 
        var inputElement = document.createElement('input');
        inputElement.name = name;
        inputElement.type = 'text';
        inputElement.id = doc.input_id_prefix+name;
        if(options.maxlength>0) {
            inputElement.maxLength = options.maxlength;
        }
        inputElement.value = value;
        rootElement.appendChild(inputElement);        
        doc.head_form.appendChild(rootElement);
        rootElement.input = inputElement;
        inputElement.label = labelElement;
        return rootElement;
    }
       
    function initExtFields(data) {
        var ext_fields = data.ext_fields;
        var i;
        for(i in ext_fields) { 
            var field = ext_fields[i];
            switch(field.type) {
                case 'text':
                    var element = newTextElement(i, data[i], field);
                    doc.head_form.appendChild(element);
                    element.input.onchange = onChangeHeaderField;
                    doc['i_'.i] = element.input;
                    break;
            }
        }
    }
    
    doc.fillHeader = function(data) {
        var tmp;
        doc.header = data;
        var template = "<input type='hidden' name='id' id='dochead_doc_id' value=''>"
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
		+ "Агент: <a href='#' id='ag_edit_link' target='_blank'><img src='/img/i_edit.png'></a>"
		+ "<a href='/docs.php?l=agent&mode=srv&opt=ep' target='_blank'><img src='/img/i_add.png'></a><br>"
		+ "<input type='hidden' name='agent_id' id='dochead_agent_id' value=''>"
		+ "<input type='text' name='agent_name' id='dochead_agent_name' value=''>"
		+ "<div id='dochead_dishonest_info'>Был выбран недобросовестный агент!</div>",
            agent_contract: "<div>Договор с агентом:</div>"
		+ "<select name='contract_id' id='dochead_contract_id'></select>",
            comment: "<div>Комментарий:</div><textarea id='dochead_comment' name='comment'></textarea>",
        };
        doc.head_form = newElement('form', left_block, '', template);
        doc.head_form.id = 'doc_head_form';
        
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
        insertOptionsArray(doc.i_firm_id, data.firm_names, data.firm_id);
        doc.i_firm_id.onchange = onUpdateFirmId;        
        
        var value;
        for(var i=0;i<data.header_fields.length;i++) { 
            switch(data.header_fields[i]) {
                case 'price':
                case 'cena':
                    var tmp = newElement('div', doc.head_form, 'item', templates.price);
                    doc.i_price_id = document.getElementById('dochead_price_id'); 
                    insertOptionsList(doc.i_price_id, data.price_list, data.price_id, true);
                    doc.i_price_id.onchange = onChangeHeaderField;
                    break;
                case 'store':
                case 'sklad':
                    var tmp = newElement('div', doc.head_form, 'item', templates.store);
                    doc.i_store_id = document.getElementById('dochead_store_id'); 
                    initStoreSelect();
                    doc.i_store_id.onchange = onChangeHeaderField;
                    break;
                case 'cash':
                case 'kassa':
                    var tmp = newElement('div', doc.head_form, 'item', templates.cash);
                    doc.i_cash_id = document.getElementById('dochead_cash_id'); 
                    insertOptionsList(doc.i_cash_id, data.cash_list, data.cash_id, true);
                    doc.i_cash_id.onchange = onChangeHeaderField;
                    break;
                case 'bank':
                    var tmp = newElement('div', doc.head_form, 'item', templates.bank);
                    doc.i_bank_id = document.getElementById('dochead_bank_id'); 
                    initBankSelect();
                    doc.i_bank_id.onchange = onChangeBankField;
                    break;
                case 'agent':
                    newElement('div', doc.head_form, 'item', templates.agent);
                    newElement('div', doc.head_form, 'item', templates.agent_contract);                    
                    initAgentField();
                    doc.i_contract_id.onchange = onChangeHeaderField;
                    break;
                case 'separator':
                    var tmp = newElement('div', doc.head_form, 'item', '<hr>');
                    break;
            }
        }
        initExtFields(data, templates);
        newElement('div', doc.head_form, 'item', templates.comment);
        doc.i_comment = document.getElementById('dochead_comment');
        doc.i_comment.value = doc.header.comment;
        doc.i_comment.onchange  = onChangeHeaderField;
    };
    
    return doc;
};
