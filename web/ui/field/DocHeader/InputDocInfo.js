define([
    'external/vue',
    'core/listproxy'
],
function (Vue, ListProxy) {
    'use strict';
    return Vue.component('DocHeader-InputDocInfo', {
        props: ['value', 'label'],
        data: function() {
            return {
                items: [],
                num: 0,
                date: ''
            }
        },
        beforeMount: function() {
            if(this.value) {
                this.num = this.value.num;
                this.date = this.value.date;
            }
        },
        beforeUpdate: function() {
            if(this.value) {
                this.num = this.value.num;
                this.date = this.value.date;
            }
        },
        template:
            '<div class="headerItem">' +
                '<div>{{label}}</div>' +
                '<div class="flexbox">' +
                    '<div class="flexbox">' +
                        '<div>№:</div>' +
                        '<input type="text" :value="num" @input="$emit(\'input\', value)">' +
                    '</div>' +
                '<div class="flexbox">' +
                    '<div>Дата:</div>' +
                    '<input type="text" :value="date" @input="$emit(\'input\', value)">' +
                '</div>' +
            '</div>'
    });
});
