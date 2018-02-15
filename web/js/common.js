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

function autoCompleteField(input_id, data, update_callback, ac_options) {
    var old_hl = 0;
    var hidden = 1;
    if(!ac_options) {
        ac_options = new Object;
    }

    var ac_input = document.getElementById(input_id);
    ac_input.value_id = 0;
    var ac_clear = document.createElement('div');
    ac_input.parentNode.insertBefore(ac_clear, ac_input.nextSibling);
    ac_clear.className = 'cac_clear';

    var ac_result = document.createElement('div');
    ac_input.parentNode.insertBefore(ac_result, ac_input.nextSibling);

    var ac_list = document.createElement('ul');
    hideList();
    ac_result.appendChild(ac_list);

    ac_result.className = 'cac_results';

    var hide_timer = 0;
    var old_value;
    var old_seeked = new Array;

    function buildList() {
        var substr = ac_input.value.toLowerCase();
        var s = '';
        if (substr == '') {
            old_seeked = new Array;
            for (var i in data) {
                s += "<li value='" + i + "'";
                s += ">" + data[i] + "</li>";
                old_seeked[i] = data[i];
            }
            old_value = '';
        }
        else if (old_value != '' && substr.indexOf(old_value) == 0) {
            var cp = new Array;
            for (var i in old_seeked) {
                if (old_seeked[i].toLowerCase().indexOf(substr) == -1)
                    continue;
                s += "<li value='" + i + "'";
                s += ">" + old_seeked[i] + "</li>";
                cp[i] = old_seeked[i];
            }
            old_seeked = cp;
            old_value = substr;
        }
        else {
            old_seeked = new Array;
            for (var i in data) {
                if (data[i].toLowerCase().indexOf(substr) == -1)
                    continue;
                s += "<li value='" + i + "'";
                s += ">" + data[i] + "</li>";
                old_seeked[i] = data[i];
            }
            old_value = substr;
        }
        ac_list.innerHTML = s;
        old_hl = ac_list.firstChild;
        if (old_hl)
            old_hl.className = 'cac_over';
        ac_result.scrollTop = 0;
    }

    function showList() {
        buildList();
        ac_result.style.display = 'block';
        ac_clear.style.display = 'block';
        hidden = 0;
    }

    function hideList() {
        if (hide_timer)
            window.clearTimeout(hide_timer);
        ac_result.style.display = 'none';
        ac_clear.style.display = 'none';
        hidden = 1;
    }

    // События поля ввода
    ac_input.onkeyup = function (event) {
        if (hidden) {
            if (event.keyCode == 40)
                showList();
            return;
        }
        else {
            if (event.keyCode == 40) {
                if (!old_hl) {
                    old_hl = ac_list.firstChild;
                    old_hl.className = 'cac_over';
                }
                else if (old_hl.nextSibling) {
                    old_hl.nextSibling.className = 'cac_over';
                    old_hl.className = '';
                    old_hl = old_hl.nextSibling;
                    ac_result.scrollTop += 18;
                }
                return;
            }
            else if (event.keyCode == 38) {
                if (!old_hl) {
                    old_hl = ac_list.lastChild;
                    old_hl.className = 'cac_over';
                }
                else if (old_hl.previousSibling) {
                    old_hl.previousSibling.className = 'cac_over';
                    old_hl.className = '';
                    old_hl = old_hl.previousSibling;
                    ac_result.scrollTop -= 18;
                }
                return;
            }
            else if (event.keyCode == 13) {
                ac_input.value_id = old_hl.value;
                ac_input.value = old_hl.innerHTML;
                ac_input.blur();
                hideList();
                update_callback();
            }
            else if (event.keyCode == 27) {
                ac_input.blur();
                hideList();

            }

        }
        //alert(event.keyCode);
        buildList();
    };

    ac_input.onfocus = function (event) {
        showList();
    };

    ac_input.onblur = function (event) {
        hide_timer = window.setTimeout(hideList, 300);
    };
    
    ac_input.updateData = function(new_data) {
        data = new_data;
        if(ac_result.style.display == 'block') {
            buildList();
        }
    };

    // События списка
    ac_list.onmouseover = function (event) {
        if (old_hl == event.target)
            return;
        if (event.target.tagName == 'LI') {
            if (old_hl)
                old_hl.className = '';
            old_hl = event.target;
            old_hl.className = 'cac_over';
        }
    };

    ac_list.onclick = function (event) {
        if (hide_timer)
            window.clearTimeout(hide_timer);
        if (event.target.tagName != 'LI') {
            ac_input.focus();
            return;
        }
        var value = event.target.value;
        ac_input.value_id = value;
        ac_input.value = event.target.innerHTML;
        hideList();
        update_callback();
    };

    // Скролл блока
    ac_result.onscroll = function (event) {
        if (hide_timer)
            window.clearTimeout(hide_timer);
        ac_input.focus();
    };

    // События кнопки clear
    ac_clear.onclick = function () {
        if (hide_timer)
            window.clearTimeout(hide_timer);
        ac_input.value = '';
        ac_input.value_id = 0;
        ac_input.focus();
        update_callback();
    };
    
    
    return ac_input;
}
 

function getCacheObject() {
    var mmCacheObject = new Object;
    mmCacheObject.storage = new Array;

    function getExpires() {
        var expires = new Object;
        var expires_str = localStorage.getItem('__EXPIRES__');
        if (expires_str) {
            try {
                expires = JSON.parse(expires_str);
            }
            catch(e) {}
        }
        return expires;
    }

    function setTTL(name, ttl) {
        var expires = getExpires();
        expires[name] = new Date().getTime() + ttl;
        localStorage.setItem('__EXPIRES__', JSON.stringify(expires));
    }

    mmCacheObject.set = function (name, object, ttl) {
        if (!ttl) {
            ttl = 60000;	// miliseconds
        }
        else {
            ttl *= 1000;
        }
        //try {
        localStorage.setItem(name, JSON.stringify(object));
        setTTL(name, ttl);
        mmCacheObject.storage[name] = object;
        /*}
        catch (e) {
            if (e == QUOTA_EXCEEDED_ERR)
                alert('Место в локальном хранилище исчерпано');
        }*/
    };

    mmCacheObject.get = function (name) {
        try {
            var expires = getExpires();

            if (!expires[name]) {
                return undefined;
            }
            if (expires[name] < (new Date().getTime())) {
                localStorage.removeItem(name);
                expires[name] = null;
                localStorage.setItem('__EXPIRES__', JSON.stringify(expires));
                return undefined;
            }
            if (mmCacheObject.storage[name]) {
                return mmCacheObject.storage[name];
            }
            else {
                return JSON.parse(localStorage.getItem(name));
            }
        }
        catch (e) {
            return undefined;
        }
    };

    mmCacheObject.unset = function (name) {
        localStorage.removeItem(name);
        mmCacheObject.storage[name] = null;
    };
    return mmCacheObject;
}


function getListProxy() {
    var mmListProxy = new Object;
    var cache = getCacheObject();
    var callbacks = new Object;
    var in_process = new Object;
    
    function onStorage(event) {
        console.log("LS event:"+event.key);
        callbackCaller(event.key, event.newValue);
    }
    
    function callbackCaller(key, newValue) {
        console.log("callback "+key);
        if(callbacks[key]!==undefined) {
            for(var i in callbacks[key]) {
                callbacks[key][i](key, newValue);
            }
        }
    }        
    
    function onReceive(json, data) {
        if(json.object==='multiquery') {
            for(var i in json.content) {
                cache.set(i, json.content[i]);
                callbackCaller(i, json.content[i]);
                in_process[i] = undefined;
            }
        }
        else {
            var i = json.object+'.'+json.action;
            cache.set(i, json.content);
            callbackCaller(i, json.content);
            in_process[i] = undefined;
        }        
    }
    
    function onError(status, data) {
        for(var i in data.query) {
            in_process[data.query[i]] = undefined;
        }
        alert("ListProxy error "+status);        
    }
    
    /// Prefetch data from server
    mmListProxy.prefetch = function(objects) { 
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
    
    mmListProxy.bind = function(object, update_callback) {
        if(update_callback!==undefined) {
            if(callbacks[object]===undefined) {
               callbacks[object] = new Array; 
            }
            callbacks[object].push(update_callback);
        }
        var data = cache.get(object);
        if(data!==undefined) {
            update_callback(object, data);
            return;
        }
        mmListProxy.refresh(object);
    };
    
    mmListProxy.refresh  = function(object) {
        if(in_process[object]==undefined) {
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
    return mmListProxy;
}