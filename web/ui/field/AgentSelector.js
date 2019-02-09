define([
    'external/vue',
    'field/InputSelector'
],
function (Vue, InputSelector) {
    'use strict';

    return Vue.component('AgentSelector', InputSelector.extend({
        props: {
            sourceName: {
                default: 'agent.shortlist'
            }
        },
        methods: {
            checkItem: function(item, substr) {
                delete item._sub;
                if(item.name.toLowerCase().indexOf(substr) !== -1)
                    return true;
                if(item.inn.toLowerCase().indexOf(substr) !== -1) {
                    item._sub = {
                        name: 'Инн:',
                        value: item.inn
                    }
                    return true;
                }
                return false;
            },
        }
    }));
});