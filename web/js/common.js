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
    
    ac_input.max_limit = 100;
    
    var ac_list = document.createElement('ul');
    hideList();
    ac_result.appendChild(ac_list);

    ac_result.className = 'cac_results';

    var hide_timer = 0;
    
    ac_input.old_seeked = new Array;    
    ac_input.list_data = data;
    ac_input.old_value = null;
    ac_input.old_hl = 0;
    
    ac_input.buildList = function () {
        var substr = ac_input.value.toLowerCase();
        var s = '';
        var limit = ac_input.max_limit;
        if (substr == '') {
            ac_input.old_seeked = new Array;
            for (var i in ac_input.list_data) {
                s += "<li value='" + i + "'>" + ac_input.list_data[i] + "</li>";
                ac_input.old_seeked[i] = ac_input.list_data[i];
                limit--;
                if(limit==0) {
                    break;
                }
            }
            ac_input.old_value = '';
        }
        else if (ac_input.old_value != '' && substr.indexOf(ac_input.old_value) == 0) {
            var cp = new Array;
            var subs_len = substr.length;
            for (var i in ac_input.old_seeked) {
                var line = ac_input.old_seeked[i];
                var name_pos = line.toLowerCase().indexOf(substr);
                if (name_pos == -1)
                    continue;
                var name = ac_input.hlSubstrIndex(line, name_pos, subs_len);
                s += "<li value='" + i + "'>" + name + "</li>";
                cp[i] = ac_input.old_seeked[i];
            }
            ac_input.old_seeked = cp;
            ac_input.old_value = substr;
        }
        else {
            ac_input.old_seeked = new Array;
            var subs_len = substr.length;
            for (var i in ac_input.list_data) {
                var line = ac_input.list_data[i];
                var name_pos = line.toLowerCase().indexOf(substr);
                if (name_pos == -1)
                    continue;
                var name = ac_input.hlSubstrIndex(line, name_pos, subs_len);
                s += "<li value='" + i + "'>" + name + "</li>";
                ac_input.old_seeked[i] = ac_input.list_data[i];
                limit--;
                if(limit==0) {
                    break;
                }
            }
            ac_input.old_value = substr;
        }
        if(limit==0) {
            s += "<li value='0'>--отобразить ещё--</li>";
        }
        ac_list.innerHTML = s;
        ac_input.old_hl = ac_list.firstChild;
        if (ac_input.old_hl)
            ac_input.old_hl.className = 'cac_over';
        ac_result.scrollTop = 0;
    }
    
    ac_input.hlSubstrIndex = function(str, startIndex, len) {
        return str.substr(0, startIndex) + '<span class="hl">' + str.substr(startIndex, len) + '</span>' + str.substring(startIndex+len);
    }

    function showList() {
        ac_input.buildList();
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
                if (!ac_input.old_hl) {
                    ac_input.old_hl = ac_list.firstChild;
                    ac_input.old_hl.className = 'cac_over';
                }
                else if (ac_input.old_hl.nextSibling) {
                    ac_input.old_hl.nextSibling.className = 'cac_over';
                    ac_input.old_hl.className = '';
                    ac_input.old_hl = ac_input.old_hl.nextSibling;
                    ac_result.scrollTop += 18;
                }
                return;
            }
            else if (event.keyCode == 38) {
                if (!ac_input.old_hl) {
                    ac_input.old_hl = ac_list.lastChild;
                    ac_input.old_hl.className = 'cac_over';
                }
                else if (ac_input.old_hl.previousSibling) {
                    ac_input.old_hl.previousSibling.className = 'cac_over';
                    ac_input.old_hl.className = '';
                    ac_input.old_hl = ac_input.old_hl.previousSibling;
                    ac_result.scrollTop -= 18;
                }
                return;
            }
            else if (event.keyCode == 13) {
                if(ac_input.old_hl.value) {
                    ac_input.value_id = ac_input.old_hl.value;
                    if(ac_input.old_hl.dataValue) {
                        ac_input.value = ac_input.old_hl.dataValue;
                    }
                    else {
                        ac_input.value = ac_input.old_hl.innerText;
                    }
                    ac_input.blur();
                    hideList();
                    if(update_callback) {
                        update_callback();
                    }
                }
                else {
                    ac_input.max_limit *= 10; 
                }
            }
            else if (event.keyCode == 27) {
                ac_input.blur();
                hideList();

            }

        }
        //alert(event.keyCode);
        ac_input.buildList();
    };

    ac_input.onfocus = function (event) {
        showList();
    };

    ac_input.onblur = function (event) {
        hide_timer = window.setTimeout(hideList, 300);
    };
    
    ac_input.updateData = function(new_data) {
        ac_input.list_data = new_data;
        if(ac_result.style.display == 'block') {
            ac_input.buildList();
        }
    };

    // События списка
    ac_list.onmouseover = function (event) {
        if (ac_input.old_hl == event.target)
            return;
        if (event.target.tagName == 'LI') {
            if (ac_input.old_hl)
                ac_input.old_hl.className = '';
            ac_input.old_hl = event.target;
            ac_input.old_hl.className = 'cac_over';
        }
    };

    ac_list.onclick = function (event) {
        if (hide_timer)
            window.clearTimeout(hide_timer);
        var item = event.target;
        if (item.tagName != 'LI') {
            if(item.parentNode.tagName=='LI') {
                item = item.parentNode;
            }
            else if(item.parentNode.parentNode.tagName=='LI') {
                item = item.parentNode.parentNode;
            }
            else {
                ac_input.focus();
                return;
            }
        }
        var value = item.value;
        if(value) {
            ac_input.value_id = value;
            if(item.dataValue) {
                ac_input.value = item.dataValue;
            }
            else {
                ac_input.value = item.innerText;
            }
            hideList();
            update_callback();
        }
        else {
            ac_input.max_limit *= 10; 
            ac_input.buildList();
        }
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
    
    ac_input.ac_result = ac_result;
    ac_input.ac_clear = ac_clear;
    ac_input.ac_list = ac_list;
        
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
            ttl = 600000;	// miliseconds
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

function createModalLayer(headerHtml, dataHtml) {
    var coverDiv = document.createElement('div');
    coverDiv.id = 'cover-div';
    document.body.appendChild(coverDiv);
    coverDiv.style.opacity = 1;
    
    var dialogContainer = document.createElement('div');
    dialogContainer.id = 'dialog-container';
    document.body.appendChild(dialogContainer);
    
    var cont2 = document.createElement('div');
    cont2.id = 'dialog-form';
    dialogContainer.appendChild(cont2);
    
    var header = document.createElement('div');
    header.id = 'dialog-header';
    cont2.appendChild(header);
    header.innerHTML = headerHtml;
    
    var mLayer = document.createElement('div');
    mLayer.id = 'dialog-body';
    cont2.appendChild(mLayer);
    mLayer.innerHTML = dataHtml;
    
    mLayer.destroy = function() {
        if(dialogContainer) {
            dialogContainer.parentNode.removeChild(dialogContainer);
        }
        if(coverDiv) {
            coverDiv.parentNode.removeChild(coverDiv);
        }
    }    
    return mLayer;
}


function debounce(func, wait, immediate) {
    var timeout;
    return function() {
        var context = this, args = arguments;
        var later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        var callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
};