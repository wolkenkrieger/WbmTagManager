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
            itsw_getter: null,
            itsw_setter: null,
            itsw_trigger: null,
            itsw_manufacturer: null,
            itsw_model: null,
            itsw_type: null
        },
        
        init: function () {
            var me = this;
            
            me.applyDataAttributes(true);
            me._on(me.$el, 'init', $.proxy(me.onInit, me));
            me._on(me.$el, 'change', $.proxy(me.onChange, me));
            me._on(me.$el, 'empty', $.proxy(me.onEmpty, me));
            
            $.publish('plugin/itswCarFinder/onInit', me);
        },
        
        onInit: function () {
            var me = this;
            console.log(me.opts.itsw_getter);
            $.ajax({
                url: me.opts.itsw_getter,
                dataType: 'json',
                cache: true
            }).done(function (response) {
                if (response.success === true) {
                    me.$el.prop("disabled", false);
                    if (me.opts.itsw_trigger) {
                        $('#' + me.opts.itsw_trigger).
                        attr('data-itsw_manufacturer', response.manufacturer).
                        attr('data-itsw_model', response.model).
                        trigger('init');
                    }
                    
                    me.$el.html(response.data).prop('disabled', false).select2({
                        allowClear: true,
                        width: '50%'
                    });
                }
            });
        },
        
        onChange: function () {
            var me = this;
            if (me.opts.itsw_trigger) {
                $('#' + me.opts.itsw_trigger).trigger('empty');
            }
            
            /*
            $.ajax({
                url: me.opts.setaction + '/m/' + me.$el.val(),
                dataType: 'json'
            }).done(function (response) {
                if (response.success === true) {
                    $('#' + me.opts.triggeronchange).attr('data-manufacturer-id', response.m);
                    $('#' + me.opts.triggeronchange).trigger('init');
                }
            });
            
             */
        },
        
        onEmpty: function () {
            var me = this;
            
            console.log('onEmpty');
            
            me.$el.empty();
            if (me.opts.itsw_trigger) {
                $('#' + me.opts.itsw_trigger).trigger('empty');
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
        }
    });
})(jQuery, window);