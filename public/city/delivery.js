(function (root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    if (root.document) {
        root.CityDelivery = api;
        if (root.document.readyState === 'loading') root.document.addEventListener('DOMContentLoaded', () => api.init(root.document));
        else api.init(root.document);
    }
})(typeof window !== 'undefined' ? window : globalThis, function () {
    'use strict';
    function normalize(value) {
        return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase()
            .replace(/^\s*\d{4}[\s,–—-]*/, '').replace(/[\s,]+\d{4}\s*$/, '')
            .replace(/[–—-]/g, ' ').replace(/\s+/g, ' ').trim();
    }
    function resolve(city, areas, selectedId = '') {
        const key = normalize(city);
        if (!key) return {status: 'empty', area: null};
        const candidates = areas.filter(area => area.cities.some(name => normalize(name) === key));
        if (!candidates.length) return {status: 'unmatched', area: null};
        if (candidates.length === 1) return {status: 'matched', area: candidates[0]};
        const selected = candidates.find(area => String(area.id) === String(selectedId));
        return selected ? {status: 'matched', area: selected} : {status: 'ambiguous', area: null};
    }
    function init(doc) {
        const input = doc.getElementById('new_city');
        const select = doc.getElementById('delivery_area');
        const status = doc.getElementById('city-delivery-status');
        if (!input || !select || !status || input.dataset.cityDeliveryReady) return;
        input.dataset.cityDeliveryReady = 'true';
        const areas = Array.from(select.options).filter(option => option.value).map(option => ({
            id: option.value, label: option.textContent.trim(), charge: Number(option.dataset.charge) || 0,
            cities: JSON.parse(option.dataset.cities || '[]')
        }));
        let lastCity = input.value;
        let updating = false;
        const isDelivery = () => (doc.querySelector('input[type=radio][name=order_type]:checked')?.value || doc.getElementById('order_type')?.value || '1') === '1';
        function reprice() {
            const area = areas.find(area => String(area.id) === select.value);
            const charge = isDelivery() && area ? area.charge : 0;
            const number = id => Number(doc.getElementById(id)?.value) || 0;
            const total = number('sub_total') + number('totaltaxamount') + charge - number('discount');
            const shipping = doc.getElementById('shipping_charge');
            const grand = doc.getElementById('grand_total');
            if (shipping) shipping.value = charge;
            if (grand) grand.value = total;
            const format = value => typeof window.currency_format === 'function' ? window.currency_format(value) : value.toLocaleString('hu-HU') + ' Ft';
            const fee = doc.getElementById('delivery_amount');
            const amount = doc.getElementById('total_amount');
            if (fee) fee.textContent = format(charge);
            if (amount) amount.textContent = format(total);
        }
        function setArea(value) {
            if (select.value !== value) {
                updating = true;
                select.value = value;
                select.dispatchEvent(new Event('change', {bubbles: true}));
                updating = false;
            }
            reprice();
        }
        function syncCity() {
            const result = resolve(input.value, areas, select.value);
            setArea(result.area ? String(result.area.id) : '');
            status.dataset.state = result.status;
            const message = result.status === 'matched' ? 'Felismert kiszállítási terület: ' + result.area.label
                : result.status === 'ambiguous' ? 'Ehhez a településhez több díj tartozik. Válaszd ki a kiszállítási területet lent.'
                : result.status === 'unmatched' ? 'Még nincs egyezés. Válassz a településjavaslatokból vagy az alábbi területek közül.'
                : 'Írd be a települést, és automatikusan kiválasztjuk a kiszállítási területet.';
            if (status.textContent !== message) status.textContent = message;
            lastCity = input.value;
        }
        input.addEventListener('input', syncCity);
        input.addEventListener('change', syncCity);
        select.addEventListener('change', () => {
            if (updating) return;
            const area = areas.find(area => String(area.id) === select.value);
            if (area && !area.cities.some(name => normalize(name) === normalize(input.value))) input.value = area.cities[0] || '';
            syncCity();
        });
        doc.querySelectorAll('input[type=radio][name=order_type]').forEach(radio => radio.addEventListener('change', reprice));
        if (window.jQuery) window.jQuery(doc).ajaxComplete(() => { if (input.value !== lastCity) syncCity(); });
        window.addEventListener('pageshow', syncCity);
        syncCity();
    }
    return {normalize, resolve, init};
});
