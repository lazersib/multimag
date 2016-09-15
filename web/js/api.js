
var mm_api = function () {
    var mm_api = new Object();
    mm_api.agent = new Object();
    mm_api.document = new Object();
    mm_api.multiquery = new Object();
    
    function dataReceiver(msg, data, ok_callback, err_callback) {
        try {
            var json = JSON.parse(msg);
            if (json.response == 'error') {
                if(err_callback) {
                    err_callback(json.errormessage, data);
                }
            }
            else if (json.response == 'success') {
                if(ok_callback) {
                    ok_callback(json, data);
                }
            }
            else {
                if(err_callback) {
                    err_callback('Неизвестный ответ', data);
                }               
            }
        }
        catch (e) {
            if(err_callback) {
                err_callback('Ошибка обращения к API: '+ e.name + ": " + e.message, data);
            }
        }
    }
    
    mm_api.callApi = function(object, action, data, ok_callback, err_callback) {
        var json_data = encodeURIComponent(JSON.stringify(data));
        httpReq('/api.php', 'POST', 'object='+encodeURIComponent(object)+'&action='+encodeURIComponent(action)+'&data=' + json_data, function (msg) {
                dataReceiver(msg, data, ok_callback, err_callback);
            }, function (status, data) {
                err_callback('request error:'+status, data);
            }
        );
    }    
    
    mm_api.agent.create = function(data, ok_callback, err_callback) {
        mm_api.callApi('agent', 'create', data, ok_callback, err_callback);
    };
    
    mm_api.document.get = function(data, ok_callback, err_callback) {
        mm_api.callApi('document', 'get', data, ok_callback, err_callback);
    };
    
    mm_api.document.update = function(data, ok_callback, err_callback) {
        mm_api.callApi('document', 'update', data, ok_callback, err_callback);
    };
    
    mm_api.multiquery.run = function(data, ok_callback, err_callback) {
        mm_api.callApi('multiquery', 'run', data, ok_callback, err_callback);
    };
    
    return mm_api;
}();
