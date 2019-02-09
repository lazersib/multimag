define([
        'external/vue'
    ],
    function (Vue) {
        'use strict';

        return Vue.component('HighlightDecorator', Vue.extend({
            functional: true,
            props: ['substr'],
            render: function (createElement, context) {
                var str = context.slots().default[0].text.toString();
                var substr = context.props.substr;
                if(!substr || str.toLowerCase().indexOf(substr.toLowerCase()) === -1)
                    return createElement('span', str);
                var startIndex = str.toLowerCase().indexOf(substr.toLowerCase());
                if(startIndex === -1)
                    return str;
                return createElement('span', [
                    str.substr(0, startIndex),
                    createElement('span', {
                        attrs: {
                            class: 'searchHl'
                        }},
                        str.substr(startIndex, substr.length)
                    ),
                    str.substring(startIndex+substr.length)
                ]);
            }
        }));
    });