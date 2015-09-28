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
            }
            doc.fillHeader(json.data);
            alert("Готово:\n"+response);
        } catch(e) {
            onLoadError(e.name, e.message);
        }
    }    
       
    doc.init = function(doc_id) {
        doc.id = doc_id;
        container.innerHTML = '';
        container.doc = doc;
        left_block = newElement('div', container, '', '');
        left_block.id = 'doc_left_block';
        var data = base_data + "&mode=srv&peopt=getheader&doc="+doc_id;
        httpReq(base_url, 'GET', data, onLoadSuccess, onLoadError);
    }
    
    doc.fillHeader = function(data) {
        var doc_head_form = newElement('form', left_block, '', '');
        //doc_head_form.id = 'doc_head_form';
        var doc_head_main = newElement('table', doc_head_form, '', '');       
        doc_head_main.id = 'doc_head_main';
        var dhm_head = newElement('tr', doc_head_main, '', "<td class='altnum'>А. номер</td><td class='subtype'>Подтип</td><td class='datetime'>Дата и время</td>");
        
    }
    
    
    
    
    return doc;
};
