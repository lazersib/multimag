define([
    'external/vue',
    'core/listproxy'
],
function (Vue, ListProxy) {
    'use strict';
    return Vue.component('Selector', {
        props: ['value', 'sourceName', 'showNotSelected'],
        data: function() {
            return {
                items: [],
                className: '',
                vvalue: null
            }
        },
        created: function() {
            if(this.sourceName)
                ListProxy.bind(this.sourceName, this.updateListNames, this);
        },
        beforeDestroy: function() {
            if(this.sourceName)
                ListProxy.unbind(this.sourceName, this.updateListNames, this);
        },
        beforeUpdate: function() {
            this.vvalue = this.value;
        },
        methods: {
            updateListNames: function (_, data) {
                var self = this;
                this.items = data;
                this.$nextTick(function () {
                    self.vvalue = self.value;
                });
            }
        },
        template: '<select :value="vvalue" @change="$emit(\'input\', $event.target.value)">' +
            '<option v-if="showNotSelected" value="0" :key="0">--не задано--</option>' +
                '<option v-for="(item, index) in items" :value="index" :key="index">{{index}}: {{item}}</option>' +
            '</select>'
    });
});