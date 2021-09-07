/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   15.12.2020
 * Zeit:    10:54
 * Datei:   jquery.carfinder.js
 * @package
 */
;(function ($, window){
    'use strict';
    
    $.plugin('itswCarFinder', {
        defaults: {
            basepath: null,
            placeholder: 'Wählen ...',
            trigger: null,
            baseUrl: '/widgets/carfinder',
            carGetter: '/get-cars',
            getter: null,
            setter: null,
            manufacturer: null,
            model: null,
            type: null,
            car: null,
            hsn: null,
            tsn: null
        },
        
        init: function () {
            var me = this;
            
            me._on(me.$el, 'init', $.proxy(me.onInit, me));
            me._on(me.$el, 'change', $.proxy(me.onChange, me));
            me._on(me.$el, 'empty', $.proxy(me.onEmpty, me));
            me._on(me.$el, 'click', $.proxy(me.onClick, me));
            
            $.publish('plugin/itswCarFinder/onInit', me);
        },
        
        onInit: function () {
            var me = this;
            
            me.itswApplyDataAttributes();
            
            if (me.opts.manufacturer) {
                me.opts.getter = me.opts.getter + '/manufacturer/' + me.opts.manufacturer;
            }
            if (me.opts.model) {
                me.opts.getter = me.opts.getter + '/model/' + me.opts.model;
            }
            if (me.opts.type) {
                me.opts.getter = me.opts.getter + '/type/' + me.opts.type;
            }
            
            //console.log(me.opts.getter);
            
            $.ajax({
                url: me.opts.getter,
                dataType: 'json',
                cache: true
            }).done(function (response) {
                console.log(response);
                if (response.success === true) {
                    me.$el.html(response.data).prop('disabled', false).select2({
                        allowClear: false,
                        width: 'calc(100% - 105px)',
                        placeholder: me.opts.placeholder,
                        templateResult: me.renderData
                    });
                    if (me.$el.val()) {
                        me.$el.trigger('change');
                    }
                }
            });
        },
        
        onClick: function () {
            var me = this;
            
            if (me.$el.is('button')) {
                me.itswApplyDataAttributes();
                console.log(me.opts);
                var url = me.opts.baseUrl + me.opts.carGetter +
                    '/hsn/' + me.opts.hsn +
                    '/tsn/' + me.opts.tsn;
                $.ajax({
                    url: url,
                    dataType: 'json'
                }).done(function (response) {
                    var content = response.data;
                    var currState = StateManager.getCurrentState();
                    var width = 900;
                    
                    switch (currState) {
                        case 'xs':
                        case 's' :
                        case 'm' : width = 'calc(100% - 15px)'; break;
                        default: width = 900;
                    }
                    $.modal.open(content, {
                        title: 'Fahrzugauswahl',
                        sizing: 'content',
                        width: width
                    })
                });
            }
        },
        
        onChange: function () {
            var me = this;
    
            me.itswApplyDataAttributes();
            me.opts[me.$el.attr('name')] = me.$el.val();
            
            if (me.opts.trigger) {
                $('#' + me.opts.trigger).
                attr('data-itsw-manufacturer', me.opts.manufacturer).
                attr('data-itsw-model', me.opts.model).
                attr('data-itsw-type', me.opts.type).
                attr('data-itsw-car', me.opts.car).
                trigger('empty');
            }
    
            if (me.opts.manufacturer) {
                me.opts.setter = me.opts.setter + '/manufacturer/' + me.opts.manufacturer;
            }
            if (me.opts.model) {
                me.opts.setter = me.opts.setter + '/model/' + me.opts.model;
            }
            if (me.opts.type) {
                me.opts.setter = me.opts.setter + '/type/' + me.opts.type;
            }
            if (me.opts.car) {
                me.opts.setter = me.opts.setter + '/car/' + me.opts.car;
            }
            
            //console.log(me.opts);
            
            $.ajax({
                url: me.opts.setter,
                dataType: 'json'
            }).done(function (response) {
                if (response.success === true) {
                    $('#' + me.opts.trigger).trigger('init');
                    if (response.car) {
                        var url = me.opts.baseUrl + me.opts.carGetter +
                            '/manufacturer/' + me.opts.manufacturer +
                            '/model/' + me.opts.model +
                            '/type/' + me.opts.type +
                            '/car/' + response.car;
                        $.ajax({
                            url: url,
                            dataType: 'json'
                        }).done(function (response) {
                            var content = response.data;
                            var currState = StateManager.getCurrentState();
                            var width = 900;
                            
                            switch (currState) {
                                case 'xs':
                                case 's' :
                                case 'm' : width = 'calc(100% - 15px)'; break;
                                default: width = 900;
                            }
                            $.modal.open(content, {
                                title: 'Fahrzugauswahl',
                                sizing: 'content',
                                width: width
                            })
                        });
                    }
                }
            });
        },
        
        onEmpty: function () {
            var me = this;
            
            me.$el.empty();
            
            if (me.opts.trigger) {
                $('#' + me.opts.trigger).trigger('empty');
            }
        },
        
        destroy: function () {
            var me = this;
            
            $.unsubscribe('plugin/swModal/onClose.carfinder');
            me._destroy();
        },
        
        setSearchPlaceholder: function () {
            var searchField = $('[class^=main-search--field]');
            if (searchField.length) {
                var manufacturerDisplay = $.trim($('#manufacturer-select option:selected').text());
                var modelDisplay = $.trim($('#model-select option:selected').text());
                var typeDisplay = $.trim($('#type-select option:selected').text());
                var text = '';
                if (typeDisplay.length && modelDisplay.length) {
                    text = modelDisplay + ' ' + typeDisplay;
                } else if (modelDisplay.length) {
                    text = modelDisplay;
                } else if (manufacturerDisplay.length) {
                    text = manufacturerDisplay;
                }
                if (text.length) {
                    searchField.attr('placeholder', 'Suche eingeschränkt auf: ' + text);
                }
            }
        },
    
        itswApplyDataAttributes: function (ignoreList) {
            var me = this, attr;
            ignoreList = ignoreList || [];
        
            $.each(me.opts, function (key) {
                if (ignoreList.indexOf(key) !== -1) {
                    return;
                }
            
                attr = me.$el.attr('data-itsw-' + key);
            
                if (typeof attr === 'undefined') {
                    attr = $('option:selected', me.$el).attr('data-' + key + 'id');
                    if (typeof attr === 'undefined') {
                        return true;
                    }
                }
            
                me.opts[key] = attr;
                
                //console.log(key + ':' + attr);
            
                return true;
            });
            
            if (me.opts.basepath) {
                me.opts.baseUrl = me.opts.basepath + me.opts.baseUrl;
            }
            
            me.opts.hsn = $.trim($('.hsn-tsn--container #hsn').val());
            me.opts.tsn = $.trim($('.hsn-tsn--container #tsn').val());
            
            me.opts.getter = me.opts.baseUrl + '/get-' + me.$el.attr('name');
            me.opts.setter = me.opts.baseUrl + '/set-' + me.$el.attr('name');
            
            $.publish('plugin/' + me._name + '/onDataAttributes', [ me.$el, me.opts ]);
        
            return me.opts;
        },
        
        renderData: function(state) {
            var renderData = $(state.element).attr('data-renderdata');
            if (typeof renderData !== typeof undefined && renderData !== false) {
                return $(
                    '<span class="select2-result__text">' + state.text + '</span>' +
                    '<span class="select2-result__renderdata">' + renderData + '</span>'
                );
            }
            return state.text;
        }
    });
})(jQuery, window);