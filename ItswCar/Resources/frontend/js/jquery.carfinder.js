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
            placeholder: 'Wählen ...',
            trigger: null,
            baseUrl: '/widgets/carfinder',
            carGetter: '/get-cars',
            getter: null,
            setter: null,
            manufacturer: null,
            model: null,
            type: null
        },
        
        init: function () {
            var me = this;
            
            me._on(me.$el, 'init', $.proxy(me.onInit, me));
            me._on(me.$el, 'change', $.proxy(me.onChange, me));
            me._on(me.$el, 'empty', $.proxy(me.onEmpty, me));
            
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
            console.log(me.opts.getter);
            $.ajax({
                url: me.opts.getter,
                dataType: 'json',
                cache: true
            }).done(function (response) {
                if (response.success === true) {
                    me.$el.html(response.data).prop('disabled', false).select2({
                        allowClear: true,
                        width: '50%'
                    });
                    if (me.$el.val()) {
                        me.$el.trigger('change');
                    }
                }
            });
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
            console.log(me.opts.setter);
            
            $.ajax({
                url: me.opts.setter,
                dataType: 'json'
            }).done(function (response) {
                if (response.success === true) {
                    $('#' + me.opts.trigger).trigger('init');
                    
                    if (response.url) {
                        var url = me.opts.baseUrl + me.opts.carGetter +
                            '/manufacturer/' + me.opts.manufacturer +
                            '/model/' + me.opts.model +
                            '/type/' + me.opts.type;
                        $.ajax({
                            url: url,
                            dataType: 'json'
                        }).done(function (response) {
                           var content = response.data;
                           console.log(content);
                           $.modal.open(content, {
                               title: 'Fahrezugauswahl',
                               sizing: 'content'
                           })
                        });
                    }
                }
            });
        },
        
        onEmpty: function () {
            var me = this;
            
            console.log('onEmpty');
            
            me.$el.empty();
            if (me.opts.trigger) {
                $('#' + me.opts.trigger).trigger('empty');
            }
        },
        
        destroy: function () {
            var me = this;
            
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
                    return true;
                }
            
                me.opts[key] = attr;
            
                return true;
            });
            
            me.opts.getter = me.opts.baseUrl + '/get-' + me.$el.attr('name');
            me.opts.setter = me.opts.baseUrl + '/set-' + me.$el.attr('name');
            
            $.publish('plugin/' + me._name + '/onDataAttributes', [ me.$el, me.opts ]);
        
            return me.opts;
        }
    });
})(jQuery, window);