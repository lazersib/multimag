define([
    'external/vue',
    'core/listproxy'
],
function (Vue, ListProxy) {
    'use strict';
    return Vue.component('SourcedLabel', {
        props: ['value', 'sourceName'],
        data: function() {
            return {
                items: []
            }
        },
        created: function() {
            ListProxy.bind(this.sourceName, this.updateListNames, this);
        },
        beforeDestroy: function() {
            ListProxy.unbind(this.sourceName, this.updateListNames, this);
        },
        methods: {
            updateListNames: function (_, data) {
                this.items = data;
            }
        },
        template: '<div v-if="items[value]">{{items[value]}}</div>'
    });
});