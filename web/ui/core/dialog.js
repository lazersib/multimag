define(['external/vue', 'css!core/dialog'],
function (Vue) {

    var module = function(config) {
        var overlay = document.createElement('div');
        overlay.className = 'dialogModalOverlay';
        document.body.appendChild(overlay);
        overlay.style.opacity = 1;
        var container = document.createElement('div');
        document.body.appendChild(container);

        var vueData = {
            component: config.component,
            header: config.header,
            value: config.value
        };

        var vueHead = new Vue({
            el:  container,
            data: vueData,
            template:
                '<div class="dialogContainer">' +
                    '<div class="dialogPopup">' +
                        '<div class="dialogHeader"><div class="dialogClose" @click="close()">&nbsp;</div>{{header}}</div>' +
                        '<div class="dialogBody">' +
                            '<component v-bind:is="component" :value="value" @close="close()" @input="input($event)"></component>' +
                        '</div>' +
                    '</div>' +
                '</div>',
            methods: {
                close: function() {
                    if(overlay && overlay.parentNode) {
                        overlay.parentNode.removeChild(overlay);
                    }
                    if(this.$el && this.$el.parentNode) {
                        this.$el.parentNode.removeChild(this.$el);
                    }
                    delete this;
                },
                input: function (value) {
                    if(config.callback) {
                        config.callback(value);
                    }
                }
            }
        });
        return vueHead;
    };

    return module;
});