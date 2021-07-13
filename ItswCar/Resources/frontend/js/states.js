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
        .addPlugin('select[data-itsw-select="true"]', 'itswCarFinder', {
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
})(jQuery, window);