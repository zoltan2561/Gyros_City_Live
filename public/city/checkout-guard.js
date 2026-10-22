(function ($) {
    'use strict';
    if (!$) return;
    $.ajaxPrefilter(function (options) {
        if (!/\/placeorder(?:\?|$)/.test(options.url)) return;
        const data = new URLSearchParams(options.data || '');
        data.set('terms', document.getElementById('terms')?.checked ? '1' : '0');
        data.set('delivery_area', document.getElementById('delivery_area')?.value || '');
        options.data = data.toString();
    });
})(window.jQuery);
