define([
    'external/vue',
    'core/listproxy',
    'field/HighlightDecorator',
    'css!field/InputSelector'
],
function (Vue, ListProxy) {
    'use strict';

    var keys = {
        ENTER: 13,
        ESCAPE: 27,
        DOWN: 40,
        UP: 38
    };

    return Vue.component('InputSelector', {
        props: {
            value: null,
            limit: {
                type: Number,
                default: 20
            },
            sourceName: {
                required: true
            }
        },
        data: function() {
            return {
                items: [],
                filteredItems: [],
                isFilteredPartial: true,
                searchString: '',
                hintShow: false
            }
        },
        methods: {
            updateListNames: function (_, data) {
                this.items = data;
                this.updateViewname();
            },
            updateViewname: function() {
                for(var k in this.items) {
                    if(this.items[k].id==this.value) {
                        this.searchString = this.items[k].name;
                        break;
                    }
                }
            },
            onKeyup: function(e) {
                if (!this.hintShow) {
                    if (e.keyCode === keys.DOWN) {
                        this.refillFilteredItems();
                        this.showList();
                    }
                    e.stopPropagation();
                }
                else {
                    if (e.keyCode === keys.DOWN) {
                        if (this.filteredItems.length-1 > this.selIndex) {
                            this.selIndex++;
                            if(this.selIndex>0)
                                this.$refs.hintDiv.scrollTop += 18;
                        }
                        e.stopPropagation();
                        return false;
                    }
                    else if (e.keyCode === keys.UP) {
                        if (this.selIndex>0) {
                            this.selIndex--;
                            this.$refs.hintDiv.scrollTop -= 18;
                        }
                        e.stopPropagation();
                        return false;
                    }
                    else if (e.keyCode === keys.ENTER) {
                        if(this.filteredItems.length === 0)
                            return;
                        if(this.selIndex === -1)
                            this.selIndex = 0;
                        this.$emit('input', this.filteredItems[this.selIndex].id);
                        this.searchString = this.filteredItems[this.selIndex].name;
                        this.selIndex = 0;
                        this.hideList();
                        this.$refs.input.blur();
                    }
                    else if (e.keyCode === keys.ESCAPE) {
                        this.hideList();
                        this.$refs.input.blur();
                    }
                    else {
                        this.selIndex = -1;
                        this.$refs.hintDiv.scrollTop = 0;
                    }
                }
                this.refillFilteredItems();
            },
            onFocus :function () {
                this.refillFilteredItems();
                this.showList();
                this.selIndex = -1;
            },
            onBlur: function () {
                var self = this;
                this.hideTimer = window.setTimeout(function () { self.hideList(); }, 1000000);
            },
            checkItem: function(item, substr) {
                return item.name.toLowerCase().indexOf(substr) !== -1;
            },
            refillFilteredItems: function () {
                var substr = this.searchString.toLowerCase();
                var limit = this.limit;
                var newItems = [];

                if (substr.length === 0) {
                    for (var i in this.items) {
                        newItems.push(this.items[i]);
                        if(--limit === 0)
                            break;
                    }
                    this.oldSearchString = '';
                }
                else if(!this.isFilteredPartial && this.oldSearchString.length > 0 && substr.indexOf(this.oldSearchString) === 0) {
                    for (var i in this.filteredItems) {
                        var item = this.filteredItems[i];
                        if (!this.checkItem(item, substr))
                            continue;
                        newItems.push(item);
                        if(--limit === 0)
                            break;
                    }
                    this.oldSearchString = substr;
                }
                else {
                    for (var i in this.items) {
                        var item = this.items[i];
                        if (!this.checkItem(item, substr))
                            continue;
                        newItems.push(item);
                        if(--limit === 0) {
                            break;
                        }
                    }
                    this.oldSearchString = substr;
                }
                this.filteredItems = newItems;
                this.isFilteredPartial = limit === 0;
            },
            hideList: function () {
                if (this.hideTimer) {
                    window.clearTimeout(this.hideTimer);
                    this.hideTimer = null;
                }
                this.hintShow = false;
                this.updateViewname();
            },
            showList: function() {
                this.$refs.hintDiv.style.width = (this.$refs.inputDiv.offsetWidth-2) + 'px';
                this.hintShow = true;
            },
            clear: function () {
                this.$emit('input', null);
                this.searchString = '';
            },
            clickEdit: function() {
	        },
	        onClick: function(item) {
		        this.$emit('input', item.id);
		        this.searchString = item.name;
		        this.hideList();
		        this.$refs.input.blur();
	        }
        },
        created: function() {
            ListProxy.bind(this.sourceName, this.updateListNames, this);
            this.oldSelectedItem = null;
            this.selIndex = -1;
            this.oldValue = 0;
            this.oldSearchString = '';
        },
        mounted: function() {
            this.oldValue = this.value;
        },
        updated: function() {
            if(this.oldValue != this.value) {
                this.updateViewname();
                this.oldValue = this.value;
            }
        },
        beforeDestroy: function() {
            ListProxy.unbind(this.sourceName, this.updateListNames, this);
        },
        template:
        '<div class="inputSelector">' +
            '<div class="isDivInput" ref="inputDiv">' +
                '<input ref="input" class="isInput" :value="searchString" @input="searchString = $event.target.value" ' +
                    '@keyup="onKeyup($event)" @focus="onFocus()" @blur="onBlur()">' +
                '<div class="isClear"v-show="value > 0 || searchString.length > 0" @click="clear"></div>' +
                '<div class="isEdit" v-show="value > 0" @click="clickEdit()"></div>' +
            '</div>' +
            '<div v-show="hintShow" class="isResult" ref="hintDiv">' +
                '<div v-for="(item, index) in filteredItems" ' +
                    'v-if="item !== undefined" ' +
                    ':key="index" ' +
                    'v-bind:value="index" ' +
	                '@click="onClick(item)"' +
                    ':class="[{isItemSelected: selIndex===index}, \'isListItem\' ]" >' +
                    '<div class="isMainItem">' +
                        '<HighlightDecorator :substr="searchString">{{item.name}}</HighlightDecorator>' +
                    '</div>' +
                    '<div v-if="item._sub" class="isSubItem flexbox">' +
                        '<div>{{item._sub.name}}</div>' +
                        '<div><HighlightDecorator :substr="searchString">{{item._sub.value}}</HighlightDecorator></div>' +
                    '</div>' +
                '</div>' +
                //'<li v-if="isFilteredPartial" v-bind:value="0" class="asListItem showMore">ещё&nbsp;&gt;&gt;&gt;</li>' +
            '</div>' +
        '</div>'
    });
});