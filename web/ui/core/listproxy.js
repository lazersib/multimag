define(['core/cache'],
function (cache) {
    var listproxy = function() {
        var proxy = new Object;
        var callbacks = new Object;
        var in_process = new Object;

        function callbacksCaller(key, value) {
            //console.log("callback "+key);
            if(callbacks[key]!==undefined) {
                for(var i in callbacks[key]) {
                    callItem(callbacks[key][i], key, value);
                }
            }
        }

        function callItem(item, key, value) {
            if(item.context!==undefined)
                item.method.call(item.context, key, value);
            else
                item.method(key, value);
        }

        function onStorage(event) {
            //console.log("LS event:"+event.key);
            callbacksCaller(event.key, event.newValue);
        }

        function onReceive(json, data) {
            if(json.object==='multiquery') {
                for(var i in json.content) {
                    cache.set(i, json.content[i]);
                    callbacksCaller(i, json.content[i]);
                    in_process[i] = undefined;
                }
            }
            else {
                var i = json.object+'.'+json.action;
                cache.set(i, json.content);
                callbacksCaller(i, json.content);
                in_process[i] = undefined;
            }
        }

        function onError(json, data) {
            for(var i in data.query) {
                in_process[data.query[i]] = undefined;
            }
            alert("ListProxy error: "+json.errormessage);
        }

        /// Prefetch data from server
        proxy.prefetch = function(objects) {
            var data = {
                query: []
            };
            for(var i in objects) {
                var object = objects[i];
                var fc = cache.get(object);
                if(fc===undefined) {
                    in_process[objects[i]] = true;
                    data.query.push(object);
                }
            }
            if(data.query.length>0) {
                mm_api.callApi('multiquery', 'run', data, onReceive, onError);
            }
        };

        proxy.bind = function(objectName, callback, context) {
            proxy.subscribe(objectName, callback, context);
            var data = cache.get(objectName);
            if(data!==undefined) {
                callItem({
                    method: callback,
                    context: context
                },objectName, data);
                return;
            }
            proxy.refresh(objectName);
        };

        proxy.subscribe = function(objectName, callback, context) {
            if(callback!==undefined) {
                if(callbacks[objectName]===undefined) {
                    callbacks[objectName] = new Array;
                }
                callbacks[objectName].push({
                    method: callback,
                    context: context
                });
            }
        }

        proxy.unbind = function(objectName, callback, context) {
            if(callbacks[objectName]!==undefined && callbacks[objectName].length > 0) {
                for(var i=0;i<callbacks[objectName].length;i++) {
                    var obj = callbacks[objectName][i];
                    if(obj.context === context && obj.method === callback) {
                        callbacks[objectName].splice(i, 1);
                    }
                }
            }
        };


        proxy.get = function(object) {
            return cache.get(object);
        };

        proxy.refresh  = function(object) {
            if(in_process[object]===undefined) {
                var s = object.split('.',2);
                mm_api.callApi(s[0], s[1], null, onReceive, onError);
            }
        };

        /// Сброс кеша по F5
        function onkeydown(event) {
            if(event.keyCode===116) {
                localStorage.removeItem('__EXPIRES__');
            }
        }
        window.addEventListener('keydown', onkeydown);

        window.addEventListener('storage', onStorage);
        return proxy;
    };
    return listproxy();
});