define([
        'external/vue',
        'core/listproxy',
        'field/FilteredSelector',
        'css!doceditor/DeliveryEditor'
    ],
    function (Vue, ListProxy) {
        'use strict';

        return Vue.component('DocEditor-DeliveryEditor', {
            props: {
                value: {
                    default: function(){ return {} }
                }
            },
            data: function() {
                return {
                    sourceName: 'agent.shortlist',
                    deliveryType: 0,
                    deliveryDate: '',
                    deliveryRegion: 0,
                    deliveryAddress: ''
                }
            },
            mounted: function() {
                if(this.value.type)
                    this.deliveryType = this.value.type;
                if(this.value.date)
                    this.deliveryDate = this.value.date;
                if(this.value.region)
                    this.deliveryRegion = this.value.region;
                if(this.value.address)
                    this.deliveryAddress = this.value.address;
            },
            methods: {
                save: function () {
                    this.$emit('input',
                        {
                            type: this.deliveryType,
                            date: this.deliveryDate,
                            region: this.deliveryRegion,
                            addres: this.deliveryAddress
                        });
                    this.$emit('close');
                },
            },
            template:
                '<div class="deliveryEditor">' +
                    '<div class="flexbox">' +
                        '<div class="deItem">' +
                            '<div class="deItemName">Вид доставки: {{deliveryType}}</div>' +
                            '<Selector v-model="deliveryType" sourceName="deliverytype.listnames"></Selector>' +
                        '</div>' +
                        '<div class="deItem">' +
                            '<div class="deItemName">Дата доставки: {{deliveryDate}}</div>' +
                            '<input type="text" v-model="deliveryDate">' +
                        '</div>' +
                        '<div class="deItem">' +
                            '<div class="deItemName">Регион доставки: {{deliveryRegion}}</div>' +
                            '<FilteredSelector v-model="deliveryRegion" sourceName="deliveryregion.getlist" ' +
                                ':filter="deliveryType" filterField="delivery_type"' +
                                '></FilteredSelector>' +
                        '</div>' +
                    '</div>' +
                    '<div class="deItemFull">' +
                        '<div class="deItemName">Адрес доставки:</div>' +
                        '<textarea v-model="deliveryAddress" class="deAddress"></textarea>' +
                    '</div>' +
                    '<div class="flexbox">' +
                        '<button @click="$emit(\'close\')">Отменить</button>' +
                        '<button @click="save()">Выполнить</button>' +
                    '</div>' +
                '</div>'
        });
    });