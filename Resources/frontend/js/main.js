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
        let c = {};
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

function readCookie(name) {
    name += '=';
    for (let ca = document.cookie.split(/;\s*/), i = ca.length - 1; i >= 0; i--) {
        if (!ca[i].indexOf(name)) {
            return ca[i].replace(name, '');
        }
    }
    
    return null;
}

$(document).ready(function () {
    makeConsole();
    triggerCarFinder();
});

