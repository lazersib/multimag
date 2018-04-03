function var_dump(obj) {
    var out = "";
    if(obj && typeof(obj) == "object"){
        out = JSON.stringify(obj);
    } else {
        out = obj;
    }
    alert(out);
}


var mm_api = function () {
    var mm_api = new Object();
    mm_api.agent = new Object();
    mm_api.document = new Object();
    mm_api.multiquery = new Object();
    
    function makeHttpReqest(url, method, data, successCallback, errorCallback) {    
        var req;

        function dispatchRequest(xhr) {
            if(xhr.readyState != 4) return;
            try {
                if (xhr.status == 200) {
                    if(successCallback) {
                        successCallback(xhr.responseText);
                    }
                }
                else {
                    if(errorCallback) {
                        var ret = {
                            errortype: 'RequestError',
                            errormessage: xhr.statusText,
                            errorcode: xhr.status,
                            msg: xhr.responseText,
                        };
                        try {
                            var json = JSON.parse(xhr.responseText);
                            if(json.response=='error') {
                                json.http = ret;
                                ret = json;
                            }
                        }
                        finally {
                            errorCallback(ret);
                        }                        
                    }
                }
            }
            catch (e) {
                if(errorCallback) {
                    var ret = {
                        errortype: e.name,
                        errormessage: e.message,
                        errorcode: xhr.status,
                        msg: xhr.responseText,
                    };
                    errorCallback(ret);
                }
            }
        }

        if (window.XMLHttpRequest) {
            req = new XMLHttpRequest();
        }
        if (!req) {
            return false;
        }
        req.timeout = 15000;
        req.ontimeout = function() {
            if(errorCallback) {
                var ret = {
                    errortype: 'Timeout',
                    errormessage: 'Время ожидания ответа истекло',
                    errorcode: 0,
                };
                errorCallback(ret);
            }
        }
        req.onreadystatechange = function () {
            dispatchRequest(req);
        };
        if(method=='GET' || method=='get') {
            req.open('GET', url + '?' + data, true);
            req.send(null);
        } else if(method=='POST' || method=='post') {
            req.open('POST', url, true);
            req.setRequestHeader("Content-type","application/x-www-form-urlencoded");
            req.send(data);
        } else {
            return false;
        }
        return true;
    }
    
    function dataReceiver(msg, data, object, action, ok_callback, err_callback) {
        try {
            var json = JSON.parse(msg);
            if (json.response == 'error') {
                if(err_callback) {
                    err_callback(json, data);
                }
            }
            else if (json.response == 'success') {
                if(ok_callback) {
                    ok_callback(json, data);
                }
            }
            else {
                if(err_callback) {
                    var ret = {
                        object: object,
                        action: action,
                        errortype: 'ResponseError',
                        errormessage: 'Неизвестный ответ',
                        errorcode: 0,
                        msg: msg,
                        parsed: json
                    };
                    err_callback(ret, data);
                }               
            }
        }
        catch (e) {
            if(err_callback) {
                var ret = {
                    object: object,
                    action: action,
                    errortype: 'ParseError',
                    errormessage: 'Ошибка обработки ответа '+ e.name + ": " + e.message,
                    errorcode: 0,
                    msg: msg,
                };
                err_callback(ret, data);
            }
        }
    }
    
    mm_api.callApi = function(object, action, data, ok_callback, err_callback) {
        var json_data = encodeURIComponent(JSON.stringify(data));
        makeHttpReqest('/api.php', 'POST', 'object='+encodeURIComponent(object)+'&action='+encodeURIComponent(action)+'&data=' + json_data,
            function (msg) {
                dataReceiver(msg, data, object, action, ok_callback, err_callback);
            },
            function(errData) {
                err_callback(errData, data);
            }
        );
    }    
    
    /// Agent
    mm_api.agent.create = function(data, ok_callback, err_callback) {
        mm_api.callApi('agent', 'create', data, ok_callback, err_callback);
    };
    
    /// Document
    mm_api.document.get = function(data, ok_callback, err_callback) {
        mm_api.callApi('document', 'get', data, ok_callback, err_callback);
    };
    
    mm_api.document.apply = function(data, ok_callback, err_callback) {
        mm_api.callApi('document', 'apply', data, ok_callback, err_callback);
    };
    
    mm_api.document.cancel = function(data, ok_callback, err_callback) {
        mm_api.callApi('document', 'cancel', data, ok_callback, err_callback);
    };
       
    mm_api.document.update = function(data, ok_callback, err_callback) {
        mm_api.callApi('document', 'update', data, ok_callback, err_callback);
    };
    
    mm_api.document.markForDelete = function(data, ok_callback, err_callback) {
        mm_api.callApi('document', 'markfordelete', data, ok_callback, err_callback);
    };
    
    mm_api.document.unMarkDelete = function(data, ok_callback, err_callback) {
        mm_api.callApi('document', 'unmarkdelete', data, ok_callback, err_callback);
    };
    
    mm_api.document.getPrintFormList = function(data, ok_callback, err_callback) {
        mm_api.callApi('document', 'getprintformlist', data, ok_callback, err_callback);
    };
    
    mm_api.document.sendFax = function(data, ok_callback, err_callback) {
        mm_api.callApi('document', 'sendfax', data, ok_callback, err_callback);
    };
    
    mm_api.document.sendEmail = function(data, ok_callback, err_callback) {
        mm_api.callApi('document', 'sendemail', data, ok_callback, err_callback);
    };
    
    mm_api.document.subordinate = function(data, ok_callback, err_callback) {
        mm_api.callApi('document', 'subordinate', data, ok_callback, err_callback);
    };
    
    mm_api.document.getMorphList = function(data, ok_callback, err_callback) {
        mm_api.callApi('document', 'getmorphlist', data, ok_callback, err_callback);
    };
    
    mm_api.document.morph = function(data, ok_callback, err_callback) {
        mm_api.callApi('document', 'morph', data, ok_callback, err_callback);
    };
    
    /// Multiquery
    mm_api.multiquery.run = function(data, ok_callback, err_callback) {
        mm_api.callApi('multiquery', 'run', data, ok_callback, err_callback);
    };
    
    return mm_api;
}();
