function doceditor(doc_container_id, menu_container_id) {
    var doc = new Object;
    var container = document.getElementById(doc_container_id);
    var left_block, main_block, v_separator;
    //var cache = getCacheObject();
    var listproxy = getListProxy();
    var hltimer;
    var mim = mainInternalMenu();
    //doc.agentnames = cache.get('agentnames');
    doc.element_classname = 'item';
    doc.label_classname = 'label';
    doc.input_id_prefix = 'dochead_';
    listproxy.prefetch(['agent.shortlist', 'firm.listnames', 'mybank.shortlist', 'store.shortlist', 'price.listnames', 'deliverytype.listnames',
        'deliveryregion.getlist', 'deliveryregion.listnames', 'worker.listnames']);
    
    function clearHighlight() {
        left_block.style.backgroundColor = '';
    }
    
    function onLoadError(response, data) {
        if(response.errortype=='AccessException') {
            if(response.object=='document' && response.action=='cancel') {
                jAlert(response.errormessage+"<br><br>Вы можете <a href='#' onclick=\"return petitionMenu(event, '"+doc.id+"')\""+
                    ">попросить руководителя</a> выполнить отмену этого документа.", "Не достаточно привилегий!", null, 'icon_err');
                doc.updateMainMenu();
            }
            else {
                alert(response.errormessage);
                doc.updateMainMenu();
            }
        }
        else if(response.errortype=='InvalidArgumentException') {
            jAlert("Ошибка:\n"+response.errortype+"\nСообщение:"+response.errormessage);
        }
        else {
            alert("Общая ошибка:\n"+response.errortype+"\nСообщение:"+response.errormessage);
            doc.updateMainMenu();
        }        
    }
        
    function onLoadSuccess(response) {
        if(response.object == 'document') {
            if(response.action=='get') {
                doc.data = response.content;
                doc.fillHeader(response.content.header);
                doc.fillBody(response.content);
                doc.updateMainMenu();
            }
            else if(response.action=='update') {
                left_block.style.backgroundColor = '#cfc';
                if (hltimer) {
                    window.clearTimeout(hltimer);
                }
                hltimer = window.setTimeout(clearHighlight, 500);
                if(response.content.header) {
                    doc.header = response.content.header;
                }
            }
            else if(response.action=='apply' || response.action=='cancel' ) {
                doc.header = response.content.header;
                doc.updateMainMenu();
                updateStoreView();
                alert('Документ успешно '+((response.action=='apply')?'проведён':'отменён'));
            }
            else if(response.action=='markfordelete') {                
                doc.header.mark_del = response.content.result;
                doc.updateMainMenu();
                alert('Документ помечен на удаление');
            }
            else if(response.action=='unmarkdelete') {                
                doc.header.mark_del = 0;
                doc.updateMainMenu();    
                alert('Отметка об удалении снята');
            }
            else {
                alert('document:action: '+response.action);
            }
        }
        else alert("Обработчик не задан:\n"+response);
    }  
    
    function updateStoreView() {
        var store_view = document.getElementById("storeview_container");
        var poslist = document.getElementById('poslist');
        var pladd = document.getElementById('pladd');
        if (store_view) {            
            if (doc.header.ok==0) {
                store_view.style.display = 'block';
                poslist.editable = 1;
                poslist.refresh();
                pladd.style.display = 'table-row';
            }
            else {
                store_view.style.display = 'none';
                pladd.style.display = 'none';
                poslist.editable = 0;
                poslist.refresh();
            }
        }
}
    
    function updateOptionsArray(select_elem, data, selected_id, not_select_item) {
        var value;
        select_elem.innerHTML = '';
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
    
    function updateOptionsList(select_elem, data, selected_id, not_select_item) {
        var i;
        select_elem.innerHTML = '';
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
    function updateContractList(select_elem, data, selected_id, not_select_item) {
        var i;
        select_elem.innerHTML = '';
        var found = 0;
        var selected = 0;
        for(i in data) { 
            var str = "N:" + data[i].altnum + data[i].subtype + ", от " + data[i].date;
            if(data[i].name!=null) {
                str = data[i].name + " " + str;
            }
            var opt = newElement('option', select_elem, '', str);
            opt.value = data[i].id;
            if(data[i].id==selected_id) {
                opt.selected=true;
                selected = 1;
            }
            found = 1;
        }        
        if(not_select_item) {
            var opt = newElement('option', select_elem, '', '--не задано--');
            opt.value='null';
        }
    }
    
    function initStoreSelect() {
        var i;
        var firm_id = doc.i_firm_id.value;
        var select_elem = doc.i_store_id;
        var store_list;
        
        function refill() {
            var selected = false;
            select_elem.innerHTML = '';
            for(i in store_list) {
                var line = store_list[i];
                if(line.firm_id>0 && line.firm_id!=firm_id) {
                    console.log("lfi:"+line.firm_id+",fi:"+firm_id);
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
        function onNewData(key, value) {
            store_list = value;
            refill();
        }
        if(select_elem.setbind == undefined) {
            listproxy.bind('store.shortlist', onNewData);
        }
        else {
            refill();
        }
    }
    
    function initBankSelect() {   
        var select_elem = doc.i_bank_id;
        var bank_list;
        function refill() {            
            var i;
            var firm_id = doc.i_firm_id.value;            
            var selected = false;
            select_elem.innerHTML = '';        
            for(i in bank_list) {
                var line = bank_list[i];
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
        function onNewData(key, value) {
            bank_list = value;
            refill();
        }
        if(select_elem.setbind == undefined) {
            listproxy.bind('mybank.shortlist', onNewData);
        }
        else {
            refill();
        }
        
    }
    
    function initAgentField() {
        function agSelectItem() {
            var agent_id = document.getElementById('dochead_agent_name').value_id
            document.getElementById('dochead_agent_id').value = agent_id;
            document.getElementById('ag_edit_link').href='/docs.php?l=agent&mode=srv&opt=ep&pos='+agent_id;
            onChangeHeaderField();
        }
        var ac_agent = autoCompleteField('dochead_agent_name', [], agSelectItem);
        ac_agent.buildList = function () {
            var substr = ac_agent.value.toLowerCase();
            var limit = ac_agent.max_limit;
            ac_agent.ac_list.innerHTML = '';
            var s = '';
            var subs_len = substr.length;
            if (substr == '') {
                ac_agent.old_seeked = new Array;
                for (var i in ac_agent.list_data) {
                    var line = ac_agent.list_data[i];
                    s += "<li value='" + i + "'>" + line.name + "</li>";
                    ac_agent.old_seeked[i] = line;
                    limit--;
                    if(limit==0) {
                        break;
                    }
                }
                ac_agent.old_value = '';
                ac_agent.ac_list.innerHTML = s;                
            }
            else if (ac_agent.old_value != '' && substr.indexOf(ac_agent.old_value) == 0) {
                var cp = new Array;                
                for (var i in ac_agent.old_seeked) {
                    var line = ac_agent.old_seeked[i];
                    var name_pos = line.name.toLowerCase().indexOf(substr);
                    var inn_pos = line.inn.indexOf(substr);
                    if (name_pos === -1 &&  inn_pos === -1) {
                        continue;
                    }                    
                    var name = line.name;
                    if(name_pos !== -1) {
                        name = ac_agent.hlSubstrIndex(line.name, name_pos, subs_len); 
                    }
                    var li_inner = name;
                    if(inn_pos !== -1) {
                        var inn = ac_agent.hlSubstrIndex(line.inn, inn_pos, subs_len);
                        li_inner =  name + "<div class='info'>ИНН: " + inn + "</div>";
                    }
                    var li = newElement('li', ac_agent.ac_list, '', li_inner);
                    li.value = i;
                    li.dataValue = line.name;
                    cp[i] = line;                    
                }
                ac_agent.old_seeked = cp;
                ac_agent.old_value = substr;
            }
            else {
                ac_agent.old_seeked = new Array;
                for (var i in ac_agent.list_data) {
                    var line = ac_agent.list_data[i];
                    var name_pos = line.name.toLowerCase().indexOf(substr);
                    var inn_pos = line.inn.indexOf(substr);
                    if (name_pos === -1 &&  inn_pos === -1) {
                        continue;
                    } 
                    var name = line.name;
                    if(name_pos !== -1) {
                        name = ac_agent.hlSubstrIndex(line.name, name_pos, subs_len); 
                    }
                    var li_inner = name;
                    if(inn_pos !== -1) {
                        var inn = ac_agent.hlSubstrIndex(line.inn, inn_pos, subs_len);
                        li_inner = name + "<div class='info'>ИНН: " + inn + "</div>";
                    }
                    var li = newElement('li', ac_agent.ac_list, '', li_inner);
                    li.value = i;
                    li.dataValue = line.name;
                    ac_agent.old_seeked[i] = line;
                    limit--;
                    if(limit==0) {
                        break;
                    }
                }
                
                ac_agent.old_value = substr;
            }
            if(limit==0) {
                var li = newElement('li', ac_agent.ac_list, '', '-отобразить ещё--');
            }
            ac_agent.old_hl = ac_agent.ac_list.firstChild;
            if (ac_agent.old_hl)
                ac_agent.old_hl.className = 'cac_over';
            ac_agent.ac_result.scrollTop = 0;
        }
        
        
        function onNewData(key, data) {
            ac_agent.updateData(data);
        }      
        listproxy.bind('agent.shortlist', onNewData);       
        
        doc.i_agent_id = document.getElementById('dochead_agent_id'); 
        doc.i_agent_id.value = doc.header.agent_id;
        doc.i_agent_name = document.getElementById('dochead_agent_name'); 
        doc.i_agent_name.value = doc.header.agent_info.name;
        doc.l_agent_balance_info = document.getElementById('agent_balance_info'); 
        doc.l_agent_balance_info.innerHTML = doc.header.agent_info.balance + "р. / "+doc.header.agent_info.bonus +"р.";
        doc.l_dochead_dishonest_info = document.getElementById('dochead_dishonest_info'); 
        if(doc.header.agent_info.dishonest!="0") {
            doc.l_dochead_dishonest_info.style.display = "block";
        } else {
            doc.l_dochead_dishonest_info.style.display = "none";
        }
        document.getElementById('ag_edit_link').href='/docs.php?l=agent&mode=srv&opt=ep&pos='+doc.header.agent_id;
        document.getElementById('contract_edit_link').href='/test_doc.php?doc_id='+doc.header.contract_id;
        doc.i_contract_id = document.getElementById('dochead_contract_id'); 
        updateContractList(doc.i_contract_id, doc.header.agent_info.contract_list, doc.header.contract_id, true);
    }
    
    function onChangeHeaderField() {
        left_block.style.backgroundColor = '#ffc';
        var fstruct = formToArray();
        delete fstruct['agent_name'];
        mm_api.document.update(fstruct,onLoadSuccess, onLoadError);
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
    
    function showBuyerEditor(event) {
        alert("В настоящее время эта информация не редактируется!");
    }
    
    function showDeliveryEditor(event) {
        var headStr = "Доставка";
        var dialogStr = 
            "<table width='100%'><tr><td>Вид доставки:<br><select id='de_delivery_id'></select></td>" +
            "<td>Дата доставки:<br><input type='text' id='de_date'></td>" +
            "<td>Регион доставки:<br><select id='de_delivery_region'></select></td></tr>" +
            "<tr><td colspan=3>Адрес доставки:<br><textarea id='de_delivery_address' style='width:95%'><option>Ggg</textarea></td></tr>" +
            "<tr><td><button id='bcancel'>Отменить</button></td><td></td>" +
            "<td style='text-align:right'><button id='bok'>Выполнить</button></td></table>";
        
        var menu = createModalLayer(headStr, dialogStr);
        var s_dtype = document.getElementById('de_delivery_id');
        var s_dregion = document.getElementById('de_delivery_region');
        var i_ddate = document.getElementById('de_date');
        var i_daddress = document.getElementById('de_delivery_address');
        
        var bok = document.getElementById('bok');
        var bcancel = document.getElementById('bcancel');
         
        var regions = null;
        var selected_region = doc.header.delivery_region;
        i_ddate.value = doc.header.delivery_date;
        i_daddress.value = doc.header.delivery_address;
        
        bcancel.onclick = function() {
            menu.destroy();
        }
        
        function onDeliveryLoadSuccess(response) {
            doc.data = response.content;
            doc.fillHeader(response.content.header);
            doc.updateMainMenu();
        }
        
        bok.onclick = function() {
            var fstruct = {
                id: doc.id,
                delivery: s_dtype.value,
                delivery_region: s_dregion.value,
                delivery_date: i_ddate.value,
                delivery_address: i_daddress.value,
            };
            mm_api.document.update(fstruct, onDeliveryLoadSuccess, onLoadError);
            menu.destroy();
        }
        function refillDR(data) {            
            var selected = false;
            s_dregion.innerHTML = '';    
            var i;
            var type = s_dtype.value;
            for(i in data) {
                var line = data[i];
                if(line.delivery_type>0 && line.delivery_type!=type) {
                    continue;
                }
                var opt = newElement('option', s_dregion, '', line.name);
                opt.value = line.id;
                if(line.id==selected_region) {
                    opt.selected = true;
                    selected = true;
                }
            }
            if( (!selected_region) || (!selected)) {            
                var opt = newElement('option', s_dregion, '', '--не задано--');
                opt.value='null';
                opt.selected=true;
                opt.className="error";
                s_dregion.className="error";
            }
        }
        function onChangeR(event)  {
            if(s_dregion.value!=0 && s_dregion.value!=null) {
                s_dregion.className="";
                selected_region = s_dregion.value;
                refillDR(regions);
            }
        }
        function onNewDTData(key, data) {
            updateOptionsArray(s_dtype, data, doc.header.delivery, true);
        }     
        function onNewDRData(key, data) {
            regions = data;
            refillDR(data);
        } 
        function onChangeD(event) {
            refillDR(regions);
        }
        listproxy.bind('deliverytype.listnames', onNewDTData);
        listproxy.bind('deliveryregion.getlist', onNewDRData);
        s_dtype.onchange = onChangeD;
        s_dregion.onchange = onChangeR;
    }
       
    doc.init = function(doc_id) {
        doc.id = doc_id;
        container.innerHTML = '';
        container.doc = doc;
        left_block = newElement('div', container, '', '');
        left_block.id = 'doc_left_block';
        mm_api.document.get({id:doc_id},onLoadSuccess, onLoadError);
        v_separator = newElement('div', container, '', '');
        v_separator.id = 'doc_v_separator';
        main_block = newElement('div', container, '', '');
        main_block.id = 'doc_main_block';
        v_separator.addEventListener('click', leftBlockToggle, false);
        if (supports_html5_storage()) {
            if (localStorage['doc_left_block_hidden'] == 'hidden') {
                lb_hide();
            }
        }
        function lb_show() {
            left_block.style.display = '';
            main_block.style.marginLeft = main_block.oldmargin;
            v_separator.style.backgroundImage = "url('/img/left_separator.png')";

        };
        function lb_hide() {
            left_block.style.display = 'none';
            main_block.oldmargin = main_block.style.marginLeft;
            main_block.style.marginLeft = 10+"px";
            v_separator.style.backgroundImage = "url('/img/right_separator.png')";

        }    
        function leftBlockToggle() {
            var state;
            if (left_block.style.display != 'none') {
                lb_hide();
                state = 'hidden';
            }
            else {
                lb_show();
                state = 'show';
            }
            if (supports_html5_storage()) {
                localStorage['doc_left_block_hidden'] = state;
            }
        }
        history.replaceState({doc_id: doc_id}, null, window.href);
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
    
    function newSelectElement(name, options) {
        var rootElement = document.createElement('div');
        rootElement.className = doc.element_classname;
        var labelElement = document.createElement('div');
        labelElement.className = doc.label_classname;
        var label = document.createTextNode(options.label+':'); 
        labelElement.appendChild(label);
        rootElement.appendChild(labelElement); 
        var selectElement = document.createElement('select');
        selectElement.name = name;
        selectElement.id = doc.input_id_prefix+name;
        rootElement.appendChild(selectElement);        
        doc.head_form.appendChild(rootElement);
        rootElement.select = selectElement;
        selectElement.label = labelElement;
        return rootElement;
    }
       
    function initExtFields(data) {
        var ext_fields = data.ext_fields;
        var i;
        var dtypes = listproxy.get('deliverytype.listnames');
        var dregions = listproxy.get('deliveryregion.listnames');
        for(i in ext_fields) { 
            var field = ext_fields[i];
            switch(field.type) {
                case 'select':
                    var element = newSelectElement(i, field);
                    var cb = function() {
                        var e = element;
                        var value = data[i];
                        function refillSelect(key, info) {
                            updateOptionsArray(e.select, info, value, true);
                        };
                        return refillSelect;
                    }();
                    listproxy.bind(field.data_source, cb);
                    element.select.onchange = onChangeHeaderField;
                    break;
                case 'text':
                    var element = newTextElement(i, data[i], field);
                    doc.head_form.appendChild(element);
                    element.input.onchange = onChangeHeaderField;
                    doc['i_'.i] = element.input;
                    break;
                case 'label':
                    if(data[i]) {
                        newElement('div', doc.head_form, 'item label', "<div class='name'>" + field.label + "</div>" + data[i]);
                    }
                break;
                case 'label_flag':
                    if(data[i]) {
                        newElement('div', doc.head_form, 'item label_flag', field.label);
                    }
                    break;
                case 'buyer_info':
                    var text = '';
                    if(data.buyer_rname != undefined && data.buyer_rname.length>0) {
                        text = text + "<div class='infoline'><div class='name'>Покупатель:</div> "+data.buyer_rname+"</div><div class='clear'></div>";
                    }
                    if(data.buyer_phone != undefined && data.buyer_phone.length>0) {
                        text = text + "<div class='infoline'><div class='name'>Телефон:</div> "+data.buyer_phone+"</div><div class='clear'></div>";
                    }
                    if(data.buyer_email != undefined && data.buyer_email.length>0) {
                        text = text + "<div class='infoline'><div class='name'>Email:</div> "+data.buyer_email+"</div><div class='clear'></div>";
                    }
                    if(data.buyer_ip != undefined && data.buyer_ip.length>0) {
                        text = text + "<div class='infoline'><div class='name'>IP:</div> "+data.buyer_ip+"</div><div class='clear'></div>";
                    }
                    if(text!='') {
                        var element = newElement('div', doc.head_form, 'item infoblock', text);
                        element.onclick = showBuyerEditor;
                    }
                    break;
                case 'delivery_info':
                    var text = '';    
                    if(data.delivery == undefined || data.delivery == 0 || data.delivery == null ) {
                        text = text + "<div class='infoline'><div class='name'>Доставка:</div> Не требуется</div><div class='clear'></div>";
                    }
                    else {
                        text = text + "<div class='infoline'><div class='name'>Требуется доставка:</div></div><div class='clear'></div>";
                        var type = data.delivery;
                        if(dtypes != undefined && dtypes[type]!=undefined) {
                            type = dtypes[type];
                        }
                        text = text + "<div class='infoline'><div class='name'>Вид:</div> " + type + "</div><div class='clear'></div>";
                        
                        if(data.delivery_region != undefined && data.delivery_region.length>0) {
                            var region = data.delivery_region;
                            if(dregions != undefined && dregions[region]!=undefined) {
                                region = dregions[region];
                            }
                            text = text + "<div class='infoline'><div class='name'>Регион:</div> " + region + "</div><div class='clear'></div>";
                        }
                        if(data.delivery_date != undefined && data.delivery_date.length>0) {
                            text = text + "<div class='infoline'><div class='name'>Дата:</div> " + data.delivery_date + "</div><div class='clear'></div>";
                        }
                        if(data.delivery_address != undefined && data.delivery_address.length>0) {
                            text = text + "<div class='infoline'><div class='name'>Адрес:</div> " + data.delivery_address + "</div><div class='clear'></div>";
                        }
                    }                        
                                        
                    var element = newElement('div', doc.head_form, 'item infoblock', text);
                    element.onclick = showDeliveryEditor;
                    break;
            }
        }
    }
    
    function onNewData(key, data) {
        switch(key) {
            case 'firm.listnames':
                updateOptionsArray(doc.i_firm_id, data, doc.header.firm_id);
                break;
            case 'cash.shortlist':
                var obj = document.getElementById('dochead_cash_id'); 
                updateOptionsList(obj, data, doc.header.cash_id, true);
                break;
            case 'price.listnames':
                updateOptionsArray(doc.i_price_id, data, doc.header.price_id, true);
                break;   
        }

    } 
    
    doc.fillBody = function(content) {        
        if(content.pe_config) {
            var poseditor_div = newElement('div', main_block, '', '');
            poseditor_div.id = 'poseditor_div';            
            var storeview_container = newElement('div', main_block, '', '');
            storeview_container.id = 'storeview_container';
            var poseditor = PosEditorInit(content.pe_config);
        }
        
    };
    
    
    doc.fillHeader = function(data) {
        var tmp;
        left_block.innerHTML = '';
        doc.header = data;
        var template = "<h1 id='label_document_viewname'></h1>"
            + "<input type='hidden' name='id' id='dochead_doc_id' value=''>"
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
            agent_contract: "<div>Договор с агентом: <a href='#' id='contract_edit_link' target='_blank'><img src='/img/i_edit.png'></a></div>"
		+ "<select name='contract_id' id='dochead_contract_id'></select>",
            comment: "<div>Комментарий:</div><textarea id='dochead_comment' name='comment'></textarea>",
        };
        doc.head_form = newElement('form', left_block, '', template);
        doc.head_form.id = 'doc_head_form';
        
        doc.l_viewname = document.getElementById('label_document_viewname');
        doc.l_viewname.innerHTML = data.viewname;
        tmp = document.getElementById('dochead_doc_id');
        tmp.value = data.id;        
        tmp = document.getElementById('dochead_doc_type_id');
        tmp.value = data.type;
        document.title = data.viewname + ' ' + doc.id;
        
        doc.i_altnum = document.getElementById('dochead_altnum');
        doc.i_altnum.value = data.altnum;        
        doc.i_subtype = document.getElementById('dochead_subtype');  
        doc.i_subtype.value = data.subtype;        
        doc.i_datetime = document.getElementById('dochead_datetime');  
        doc.i_datetime.value = data.date;        
        doc.i_firm_id = document.getElementById('dochead_firm_id');  
        
        doc.i_firm_id.onchange = onUpdateFirmId;                
        listproxy.bind('firm.listnames', onNewData); 

        for(var i=0;i<data.header_fields.length;i++) { 
            switch(data.header_fields[i]) {
                case 'price':
                case 'cena':
                    var tmp = newElement('div', doc.head_form, 'item', templates.price);
                    doc.i_price_id = document.getElementById('dochead_price_id');                     
                    doc.i_price_id.onchange = onChangeHeaderField;
                    listproxy.bind('price.listnames', onNewData);
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
                    doc.i_cash_id.onchange = onChangeHeaderField;
                    listproxy.bind('cash.shortlist', onNewData);
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
        v_separator.style.height = Math.max(left_block.clientHeight, main_block.clientHeight)+"px";        
    };  
    
    doc.updateMainMenu = function() {
        // Убираем старые
        mim.contextPanel.clear();
        doc.contextPanel = new Object;
        // История
        doc.contextPanel.hist = mim.contextPanel.addButton({
            icon:"i_log.png",
            caption:"История изменений документа",
            link:"/doc.php?mode=log&doc="+doc.id
        });
        if(doc.header.mark_del>0) {
            doc.contextPanel.del = mim.contextPanel.addButton({
                icon:"i_trash_undo.png",
                caption:"Отменить удаление",
                accesskey: "U",
                onclick: doc.unMarkDelete
            });
        } 
        else if(doc.header.ok>0) {
            doc.contextPanel.cancel = mim.contextPanel.addButton({
                icon:"i_revert.png",
                caption:"Отменить проведение документа",
                accesskey: "С",
                onclick: doc.cancel
            });
        }
        else {
            doc.contextPanel.apply = mim.contextPanel.addButton({
                icon:"i_ok.png",
                caption:"Провести документ",
                accesskey: "A",
                onclick: doc.apply
            });
            doc.contextPanel.del = mim.contextPanel.addButton({
                icon:"i_trash.png",
                caption:"Отметьть для удаления",
                accesskey: "D",
                onclick: doc.markForDelete
            });
        }
        
        mim.contextPanel.addSeparator();
        
        doc.contextPanel.print = mim.contextPanel.addButton({
            icon:"i_print.png",
            caption:"Печатные формы",
            accesskey: "P",
            onclick: doc.printForms
        });
        
        doc.contextPanel.sendFaxForm = mim.contextPanel.addButton({
            icon:"i_fax.png",
            caption:"Отправить по факсу",
            onclick: doc.faxForms
        });
        
        doc.contextPanel.sendEmailForm = mim.contextPanel.addButton({
            icon:"i_mailsend.png",
            caption:"Отправить по email",
            onclick: doc.emailForms
        });
        
        mim.contextPanel.addSeparator();  
        
        doc.contextPanel.connect = mim.contextPanel.addButton({
            icon:"i_conn.png",
            caption:"Подчинить",
            onclick: doc.subordinateDialog
        });
        
        doc.contextPanel.morphto = mim.contextPanel.addButton({
            icon:"i_to_new.png",
            caption:"Создать подчинённый документ",
            onclick: doc.morphToMenu
        });
        
        mim.contextPanel.addSeparator(); 
        
        doc.contextPanel.refillNomenclature = mim.contextPanel.addButton({
            icon:"i_addnom.png",
            caption:"Перезаполнить номенклатуру",
            onclick: doc.refillNomenclatureForm
        });
        
        if(doc.header.typename=='zayavka') {
            if(doc.header.reserved==0) {
                doc.contextPanel.reserves = mim.contextPanel.addButton({
                    icon:"22x22/object-unlocked.png",
                    caption:"Разрешить резервы",
                    onclick: doc.reservesToggle
                });
            }
            else {
                doc.contextPanel.reserves = mim.contextPanel.addButton({
                    icon:"22x22/object-locked.png",
                    caption:"Снять резервы",
                    onclick: doc.reservesToggle
                });
            }
        }
    };
    
    doc.apply = function(event) {
        event.preventDefault();
        mm_api.document.apply({id:doc.id}, onLoadSuccess, onLoadError);
        if(doc.contextPanel.apply) {
            mim.contextPanel.updateButton(doc.contextPanel.apply, {
                icon:"icon_load.gif",
                caption:"Проведение...",
                accesskey: "",
                onclick: function(){}
            });
        }
        return false;
    };
    
    doc.cancel = function(event) {
        event.preventDefault();
        mm_api.document.cancel({id:doc.id}, onLoadSuccess, onLoadError);
        if(doc.contextPanel.cancel) {
            mim.contextPanel.updateButton(doc.contextPanel.cancel, {
                icon:"icon_load.gif",
                caption:"Отмена...",
                accesskey: "",
                onclick: function(){}
            });
        }
    };
    
    doc.markForDelete = function(event) {
        event.preventDefault();
        mm_api.document.markForDelete({id:doc.id}, onLoadSuccess, onLoadError);
        if(doc.contextPanel.del) {
            mim.contextPanel.updateButton(doc.contextPanel.del, {
                icon:"icon_load.gif",
                caption:"Ставим пометку...",
                accesskey: "",
                onclick: function(){}
            });
        }
    };
    
    doc.unMarkDelete = function() {
        event.preventDefault();
        mm_api.document.unMarkDelete({id:doc.id}, onLoadSuccess, onLoadError);
        if(doc.contextPanel.del) {
            mim.contextPanel.updateButton(doc.contextPanel.del, {
                icon:"icon_load.gif",
                caption:"Снимаем пометку...",
                accesskey: "",
                onclick: function(){}
            });
        }
    };
    
    doc.reservesToggle = function(event) {
        event.preventDefault();
        if(doc.contextPanel.reserves) {
            mim.contextPanel.updateButton(doc.contextPanel.reserves, {
                icon:"icon_load.gif",
                caption:"Переключение...",
                accesskey: "",
                onclick: function(){}
            });
        }
        var fstruct = { id: doc.id, reserved: doc.header.reserved?0:1};
        mm_api.document.update(fstruct, onLoadSuccess, onLoadError);
    }
    
    doc.printForms = function(event) {
        event.preventDefault();
        var menu = CreateContextMenu(event);
        function pickItem(event) {
            var fname = event.target.fname;
            menu.parentNode.removeChild(menu);
            var data = {id: doc.id, name: fname};
            window.location = "/api.php?object=document&action=getprintform&data="+encodeURIComponent(JSON.stringify(data));
        }

        function onLoadPFLError(response, data) {
            jAlert(response.errortype + ': ' + response.errormessage, 'Печать', null, 'icon_err');
            menu.parentNode.removeChild(menu);
        }
        function onLoadPFLSuccess(response) {
            menu.innerHTML = ''
            var printforms = response.content.printforms;
            for (var i = 0; i < printforms.length; i++) {
                var elem = document.createElement('div');
                if (printforms[i].mime) {
                    var mime = printforms[i].mime.replace('/', '-');
                    elem.style.backgroundImage = "url('/img/mime/22/" + mime + ".png')";
                }
                elem.innerHTML = printforms[i].desc;
                elem.fname = printforms[i].name;
                elem.onclick = pickItem;
                menu.appendChild(elem);
            }
        }  
        mm_api.document.getPrintFormList({id:doc.id},onLoadPFLSuccess, onLoadPFLError);

        return false;
    }
    
    doc.faxForms = function(event) {
        event.preventDefault();
        var menu = CreateContextMenu(event);
        function pickItem(event) {
            var fname = event.target.fname;

            menu.innerHTML = '';
            menu.morphToDialog();
            var elem = document.createElement('div');
            elem.innerHTML = 'Номер факса:<br><small>В международном формате +XXXXXXXXXXX...<br>без дефисов, пробелов, и пр.символов</small>';
            menu.appendChild(elem);
            var ifax = document.createElement('input');
            ifax.type = 'text';
            //ifax.value = fax_number;
            ifax.style.width = '200px';
            menu.appendChild(ifax);
            ifax.onkeyup = function() {
                var regexp = /^\+\d{8,15}$/;
                if (!regexp.test(ifax.value)) {
                    ifax.style.color = "#f00";
                    bsend.disabled = true;
                } else {
                    ifax.style.color = "";
                    bsend.disabled = false;
                }
            };
            
            elem = document.createElement('br');
            menu.appendChild(elem);
            var bcancel = document.createElement('button');
            bcancel.innerHTML = 'Отменить';
            bcancel.onclick = function () {
                menu.parentNode.removeChild(menu);
            };
            menu.appendChild(bcancel);
            var bsend = document.createElement('button');
            bsend.innerHTML = 'Отправить';
            menu.appendChild(bsend);
            bsend.onclick = function () {
                mm_api.document.sendFax({id:doc.id, faxnum: ifax.value, name: fname}, onLoadPFLSuccess, onLoadPFLError);
                menu.innerHTML = '<img src="/img/icon_load.gif" alt="отправка">Отправка факса...';
            };
            ifax.onkeyup();
        }

        function onLoadPFLError(response, data) {
            jAlert(response.errortype + ': ' + response.errormessage, 'Отправка факса', null, 'icon_err');
            menu.parentNode.removeChild(menu);
        }
        function onLoadPFLSuccess(response) {
            if(response.action == 'getprintformlist') {
                menu.innerHTML = '';
                var printforms = response.content.printforms;
                for (var i = 0; i < printforms.length; i++) {
                    var elem = document.createElement('div');
                    if (printforms[i].mime) {
                        var mime = printforms[i].mime.replace('/', '-');
                        elem.style.backgroundImage = "url('/img/mime/22/" + mime + ".png')";
                    }
                    elem.innerHTML = printforms[i].desc;
                    elem.fname = printforms[i].name;
                    elem.onclick = pickItem;
                    menu.appendChild(elem);
                }
            }
            else if(response.action == 'sendfax') {
                jAlert('Факс успешно отправлен на сервер! Вы получите уведомление по email c результатом отправки получателю!', "Выполнено");
                menu.parentNode.removeChild(menu);
            } 
            else {
                jAlert("Обработка полученного сообщения не реализована", "Отправка факса", null, 'icon_err');
                menu.parentNode.removeChild(menu);
            }
        }  
        mm_api.document.getPrintFormList({id:doc.id},onLoadPFLSuccess, onLoadPFLError);

        return false;
    };
    
    doc.emailForms = function(event) {
        event.preventDefault();
        var menu = CreateContextMenu(event);
        var email = '';
        function pickItem(event) {
            var fname = event.target.fname;

            menu.innerHTML = '';
            menu.morphToDialog();
            var elem=document.createElement('div');
            elem.innerHTML='Адрес электронной почты:';
            menu.appendChild(elem);
            var imail=document.createElement('input');
            imail.type='tel';
            imail.value=email;
            imail.style.width='200px';
            menu.appendChild(imail);
            elem=document.createElement('div');
            elem.innerHTML='Комментарий:';
            menu.appendChild(elem);
            var mailtext=document.createElement('textarea');
            menu.appendChild(mailtext);
            menu.appendChild(document.createElement('br'));

            
            elem = document.createElement('br');
            menu.appendChild(elem);
            var bcancel = document.createElement('button');
            bcancel.innerHTML = 'Отменить';
            bcancel.onclick = function () {
                menu.parentNode.removeChild(menu);
            };
            menu.appendChild(bcancel);
            var bsend = document.createElement('button');
            bsend.innerHTML = 'Отправить';
            menu.appendChild(bsend);
            bsend.onclick = function () {
                mm_api.document.sendEmail({id:doc.id, email: imail.value, name: fname, text: mailtext.value}, onLoadPFLSuccess, onLoadPFLError);
                menu.innerHTML = '<img src="/img/icon_load.gif" alt="отправка">Отправка email...';
            };
            
        }

        function onLoadPFLError(response, data) {
            jAlert(response.errortype + ': ' + response.errormessage, 'Отправка email', null, 'icon_err');
            menu.parentNode.removeChild(menu);
        }
        function onLoadPFLSuccess(response) {
            if(response.action == 'getprintformlist') {
                menu.innerHTML = '';
                var printforms = response.content.printforms;
                for (var i = 0; i < printforms.length; i++) {
                    var elem = document.createElement('div');
                    if (printforms[i].mime) {
                        var mime = printforms[i].mime.replace('/', '-');
                        elem.style.backgroundImage = "url('/img/mime/22/" + mime + ".png')";
                    }
                    elem.innerHTML = printforms[i].desc;
                    elem.fname = printforms[i].name;
                    elem.onclick = pickItem;
                    menu.appendChild(elem);
                }
            }
            else if(response.action == 'sendemail') {
                jAlert('Сообщение успешно отправлено!', "Выполнено");
                menu.parentNode.removeChild(menu);
            } 
            else {
                jAlert("Обработка полученного сообщения не реализована", "Отправка email", null, 'icon_err');
                menu.parentNode.removeChild(menu);
            }
        }  
        mm_api.document.getPrintFormList({id:doc.id},onLoadPFLSuccess, onLoadPFLError);

        return false;
    };
    
    doc.subordinateDialog = function(event) {
        event.preventDefault();
        var p_doc_tmp = 0;
        function onEnterData(result) {
            if(result!==null) {
                mm_api.document.subordinate({id:doc.id, p_doc: result},onLoadSuccess, onLoadError);
                p_doc_tmp = result;
            }
        }

        function onLoadError(response, data) {
            jAlert(response.errortype + ': ' + response.errormessage, 'Подчинение документа', null, 'icon_err');
        }
        function onLoadSuccess(response) {
            doc.header.p_doc = p_doc_tmp;
            jAlert('Документ '+doc.id+' успешно подчинён документу '+p_doc_tmp, 'Подчинение документа');
        }  
        
        jPrompt("Укажите <b>системный</b> номер документа,<br> к которому привязать <br>текущий документ:",
            doc.header.p_doc, "Подчинение документа",  onEnterData);        
        return false;
    }
    
    doc.morphToMenu = function(event) {
        event.preventDefault();
        var menu = CreateContextMenu(event);
        
        function onLoadMMError(response, data) {
            menu.destroy();
            jAlert(response.errortype + ': ' + response.errormessage, 'Морфинг', null, 'icon_err');            
        }
        function onLoadMMSuccess(response) {
            menu.innerHTML = ''
            var morphlist = response.content.morphlist;
            var i, c = 0;
            
            for(i in morphlist) {
                var elem = document.createElement('div');
                var docfname = morphlist[i].document.replace('/', '-');
                elem.style.backgroundImage = "url('/img/doc/" + docfname + ".png')";
                elem.innerHTML = morphlist[i].viewname;
                elem.fname = morphlist[i].name;
                elem.onclick = pickItem;
                menu.appendChild(elem);
                c++;
            }
            if(c==0) {
                menu.destroy();
                jAlert('На основе этого документа нельзя создать ни один другой документ.', 'Морфинг', null, 'icon_err');  
            }
        }
        function onMorphSuccess(response) {
            var newdoc_id = response.content.newdoc_id;
            history.pushState({doc_id: newdoc_id}, null, '/test_doc.php?doc_id='+newdoc_id);
            //history.replaceState({doc_id: doc_id}, null, window.href);
            doc.init(newdoc_id);            
        }
        function pickItem(event) {
            var fname = event.target.fname;
            menu.destroy();
            var data = {id: doc.id, target: fname};
            mm_api.document.morph(data,onMorphSuccess, onLoadMMError);
            //window.location = "/api.php?object=document&action=getprintform&data="+encodeURIComponent(JSON.stringify(data));
        }
        mm_api.document.getMorphList({id:doc.id},onLoadMMSuccess, onLoadMMError);
        return false;
    }
    
    doc.refillNomenclatureForm = function(event) {
        //var menu = CreateContextMenu(event);
        //menu.morphToDialog();
        var headStr = "Перезапись номенклатурной таблицы";
        var selected_row = null;
        
        function selectRow(event) {
            var obj = event.target;
            while (obj != 'undefined' && obj != 'null') {
                if (obj.tagName == 'TR') {
                    if (!obj.marked) {
                        obj.style.backgroundColor = '#8f8';
                        obj.marked = 1;
                        if(selected_row) {
                            selected_row.style.backgroundColor = '';
                            selected_row.marked = 0;
                        }
                        selected_row = obj;
                        doc_id_refill.value = obj.doc_id;
                    }
                    else {
                        obj.style.backgroundColor = '';
                        obj.marked = 0;
                        selected_row = null;
                        doc_id_refill.value = '';
                    }
                    return;
                }
                obj = obj.parentNode;
            }
        }
        
        var dialogStr = "<table width='100%' class='list'><thead><tr><th colspan='4'>Заполнить из документа</th></tr></thead><tbody id='doc_sel_table_body'>";
        dialogStr = dialogStr + "</tbody></table>" +
            "<table width='100%'><tr><td><label><input type='checkbox' id='p_clear_cb'> Предочистка</label></td><td>Док.id:<input type='text' id='doc_id_refill'></td>" +
            "<td><label><input type='checkbox' id='nsum_cb'> Не суммировать</label></td></tr><tr><td><button id='bcancel'>Отменить</button></td>" +
            "<td></td><td style='text-align:right'><button id='bok'>Выполнить</button></td></table>";
        
        var menu = createModalLayer(headStr, dialogStr);

        var doc_sel_table_body = document.getElementById('doc_sel_table_body');
        var op_clear_cb = document.getElementById('p_clear_cb');
        var onsum_cb = document.getElementById('nsum_cb');
        var obok = document.getElementById('bok');
        var obcancel = document.getElementById('bcancel');
        var doc_id_refill = document.getElementById('doc_id_refill');

        doc_sel_table_body.onclick = selectRow;
        for(i in doc.data.sub_info) {
            var tr = doc_sel_table_body.insertRow(-1);
            var sub_info = doc.data.sub_info[i];
            var str = "<td>" + sub_info.id + "</td><td>" + sub_info.viewname + "</td><td>" + sub_info.altnum + sub_info.subtype + "</td><td>" + sub_info.date + "</td>";
            tr.innerHTML = str;
            tr.doc_id = sub_info.id;
            tr.style.cursor = 'pointer';
        }

        obcancel.onclick = function () {
            menu.destroy();
        };
        obok.onclick = function () {
            if(!doc_id_refill.value) {
                return false;
            }
            var data = {
                id: doc.id,
                from_doc_id: doc_id_refill.value,
                preclear: op_clear_cb.checked ? 1 : 0,
                no_sum: onsum_cb.checked ? 1 : 0
            };
            mm_api.document.refillPosList(data, refillSuccess, refillError);
            menu.innerHTML = '<img src="/img/icon_load.gif" alt="Загрузка">Загрузка...';
        };

        function selectNum(event) {
            var odoc_num_field = document.getElementById('doc_num_field');
            odoc_num_field.value = event.target.doc_id;
        }

        function refillError(response, data) {
            menu.destroy();
            jAlert(response.errortype + ': ' + response.errormessage, headStr, null, 'icon_err');
        }

        function refillSuccess(response, data) {
            try {
                if (response.response == 'success') {
                    jAlert('Таблица загружена', "Выполнено", {});
                    menu.destroy();
                    poslist.refresh();
                }
                else {
                    jAlert("Обработка полученного сообщения не реализована<br>" + response, headStr, {}, 'icon_err');
                    menu.destroy();
                }
            }
            catch (e) {
                jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!" +
                    "<br><br><i>Информация об ошибке</i>:<br>" + e.name + ": " + e.message + "<br>" + response, headStr, {}, 'icon_err');
                menu.destroy();
            }
        }
        return false;
    }
    
    window.addEventListener("popstate", function(e) {
        if(e.state!= null) {
            doc.init(e.state.doc_id);
        }
        else {
            //history.go(0);
        }
    }, false)
    
    return doc;
};
