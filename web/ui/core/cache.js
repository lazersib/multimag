define(function () {
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
    };

    return getCacheObject();
});

