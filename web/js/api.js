
var mm_api = function () {
    var mm_api = new Object();
    mm_api.agent = new Object();
    
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
            }, function () {
                err_callback('request error', data);
            }
        );
    }    
    
    mm_api.agent.create = function(data, ok_callback, err_callback) {
        mm_api.callApi('agent', 'create', data, ok_callback, err_callback);
    };
    
    return mm_api;
}();
