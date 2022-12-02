/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   15.12.2020
 * Zeit:    10:57
 * Datei:   states.js
 * @package
 */
(function($, window) {
    window.StateManager
        .addPlugin('select[data-itsw-car-select="true"]', 'itswCarFinder', {
            baseUrl: '/widgets/carfinder'
        })
        .addPlugin('.hsn-tsn--container button', 'itswCarFinder', {
            baseUrl: '/widgets/carfinder'
        })
        .addPlugin('.forms--inner-form #forms_by', 'swDatePicker', {
            allowInput: true,
            minDate: '1960-01-01'
        })
    ;
    
    $(function($) {
        function unsetCar() {
            var basePath = $('.itsw #manufacturers').attr('data-itsw-car-basepath');
            var baseUrl = '/widgets/carfinder';
            
            if (basePath) {
                 baseUrl = basePath + baseUrl;
            }
            
            $.ajax({
                url: baseUrl + '/unset-car/redirect/0' ,
                dataType: 'json'
            }).done(function (response) {
                if (response.success === true) {
                    $('.itsw #models').removeAttr('data-itsw-car-manufacturer');
                    $('.itsw #types').removeAttr('data-itsw-car-manufacturer').removeAttr('data-itsw-car-model').removeAttr('data-itsw-car-type');
                    
                    if ($('.itsw #manufacturers').hasClass("select2-hidden-accessible")) {
                        $('.itsw #manufacturers').select2('destroy');
                    }
                    $('.itsw #manufacturers').prop('disabled', true).trigger('init');
                    
                    if ($('.itsw #models').hasClass("select2-hidden-accessible")) {
                        $('.itsw #models').select2('destroy');
                    }
                    $('.itsw #models').prop('disabled', true).trigger('empty');
                    
                    if ($('.itsw #types').hasClass("select2-hidden-accessible")) {
                        $('.itsw #types').select2('destroy');
                    }
                    $('.itsw #types').prop('disabled', true).trigger('empty');
                }
            });
        }
        
        $.subscribe('plugin/swModal/onClose.itswCarFinder', unsetCar);
    });
    
})(jQuery, window);
