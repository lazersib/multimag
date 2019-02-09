define([
    'external/vue',
    'core/listproxy'
],
function (Vue, ListProxy) {
    'use strict';
    return Vue.component('FirmSelector', {
        data: function() {
            return {
                items: []
            }
        },
        methods: {
            onChange: function(value) {
                this.$emit('input', value);
                this.firmLockNotify(value);
            },
            firmLockNotify: function(value) {
                if(value>0 && this.items[value]) {
                    let item = this.items[value];
                    this.$emit('firm-locks', {
                        bank: item.bank_lock,
                        store: item.store_lock,
                        cashbox: item.cashbox_lock
                    });
                }
            },
            updateListNames: function (_, data) {
                this.items = data;
            }
        },
        created: function() {
            ListProxy.bind('firm.shortlist', this.updateListNames, this);
        },
        updated: function() {
            this.firmLockNotify(this.value);
        },
        beforeDestroy: function() {
            ListProxy.unbind(this.sourceName, this.updateListNames, this);
        },
        props: ['value'],
        template: '<select :value="value" @change="onChange($event.target.value)">' +
            '<option v-for="item in items" :value="item.id" :key="item.id">{{item.id}}: {{item.name}}</option>' +
            '</select>'
    });
});