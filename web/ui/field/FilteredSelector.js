define([
    'external/vue',
    'core/listproxy'
],
function (Vue, ListProxy) {
    'use strict';
    return Vue.component('FilteredSelector', {
        props: {
            value: null,
            isFiltered: null,
            sourceName: null,
            filter: {
                required: true
            },
            filterField: {
                required: true
            }
        },
        data: function() {
            return {
                items: [],
                showNotSelected: true,
                className: ''
            }
        },
        created: function() {
            ListProxy.bind(this.sourceName, this.updateListNames, this);
        },
        beforeUpdate: function() {
            var valNotInList = true;
            if(!this.filter) {
                return;
            }
            this.showNotSelected = true;
            for(i in this.items) {
                if((this.isFiltered==0 || this.items[i][this.filterField] == this.filter)
                    && this.value == this.items[i].id) {
                    valNotInList = false;
                }
            }
            if(valNotInList) {
                if(this.value)
                    this.$emit('input', 0);
                this.className = 'errorRequiredInput';
            }
            else {
                this.className = '';
            }
        },
        beforeDestroy: function() {
            ListProxy.unbind(this.sourceName, this.updateListNames, this);
        },
        methods: {
            updateListNames: function (_, data) {
                this.items = data;
            }
        },
        template: '<select :class="className" :value="value" @change="$emit(\'input\', $event.target.value)">' +
            '<option v-if="value==0" value="0" class="errorOption" :key="0">--не задано--</option>' +
                '<option v-for="item in items" :value="item.id" :key="item.id"' +
                    ' v-if="filter==0 || isFiltered==0 || (item[filterField] == filter)">{{item.id}}: {{item.name}}</option>' +
            '</select>'
    });
});