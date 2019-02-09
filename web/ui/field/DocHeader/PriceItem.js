define([
    'external/vue',
    'core/listproxy'
],
function (Vue, ListProxy) {
    'use strict';
    return Vue.component('DocHeader-PriceItem', {
        data: function() {
            return {
                items: [],
                vvalue: null
            }
        },
        created: function() {
            ListProxy.bind('price.listnames', this.updateListNames, this);
        },
        beforeDestroy: function() {
            ListProxy.unbind(this.sourceName, this.updateListNames, this);
        },
        beforeUpdate: function() {
            this.vvalue = this.value;
        },
        methods: {
            updateListNames: function (_, data) {
                var self = this;
                var items = [];
                for(var key in data) {
                    items.push({id:key, name:data[key]});
                }
                this.items = items;
                this.vvalue = null;
                this.$nextTick(function () {
                    self.vvalue = self.value;
                });
            }
        },
        props: ['value', 'firmId', "fixed"],
        template: '<div class="headerItem">' +
            '<div class="flexbox">' +
                '<div>Цена: {{vvalue}}</div>' +
            '<label>Зафиксировать<input type="checkbox" :checked="fixed" @change="$emit(\'fixed\', $event.target.checked)"></label>' +
            '</div>' +
            '<select :value="vvalue" @change="$emit(\'input\', $event.target.value)">' +
                '<option value="null">--автоматически--</option>' +
                '<option v-for="item in items" :value="item.id" :key="item.id">{{item.name}}</option>' +
            '</select></div>'
    });
});
