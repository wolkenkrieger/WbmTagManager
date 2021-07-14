/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   15.12.2020
 * Zeit:    10:55
 * Datei:   main.js
 * @package
 */
function makeConsole() {
    window.console = window.console || (function () {
        var c = {};
        c.log = c.warn = c.debug = c.info = c.error = c.time = c.dir = c.profile = c.clear = c.exception = c.trace = c.assert = function (s) {
        };
        return c;
    })();
}

function triggerCarFinder() {
    if ($('.itsw #manufacturers').length) {
        $('.itsw #manufacturers').trigger('init');
    }
}

function unsetCar() {

}

$(document).ready(function () {
    makeConsole();
    triggerCarFinder();
});