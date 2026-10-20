(function (host) {
    'use strict';
    function quantity(value) { return Math.min(100, Math.max(1, Math.trunc(Number(value)) || 1)); }
    function total(base, addons, extras, count) {
        const cents = value => Math.round((Number(value) || 0) * 100);
        const unit = [base, ...addons, ...extras].reduce((sum, value) => sum + cents(value), 0);
        return {unit: unit / 100, total: unit * quantity(count) / 100};
    }
    function selection(required, min, max, count) {
        const missing = required ? Math.max(0, Number(min) - count) : 0;
        return {missing, valid: missing === 0 && count <= Number(max), limited: count >= Number(max)};
    }
    if (typeof module === 'object' && module.exports) module.exports = {quantity, total, selection};
    if (!host.document) return;
    const document = host.document;
    const states = new WeakMap();
    const checked = (root, selector) => [...root.querySelectorAll(`${selector}:checked`)];
    const money = (root, value) => {
        const currency = root.dataset.currency || 'HUF';
        try { return new Intl.NumberFormat('hu-HU', {style: 'currency', currency, maximumFractionDigits: currency === 'HUF' ? 0 : 2}).format(value); }
        catch (_) { return `${new Intl.NumberFormat('hu-HU', {maximumFractionDigits: 2}).format(value)} ${currency}`; }
    };
    let toast;
    let toastTimer;
    function feedback(message, cartUrl, detail = '') {
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'city-cart-feedback';
            toast.hidden = true;
            toast.innerHTML = '<div role="status" aria-live="polite"><strong></strong><span></span></div><a>Kosár megnyitása</a><button type="button" aria-label="Visszajelzés bezárása">×</button>';
            toast.querySelector('button').addEventListener('click', () => { toast.hidden = true; });
            document.body.append(toast);
        }
        toast.querySelector('strong').textContent = message;
        toast.querySelector('span').textContent = detail;
        const link = toast.querySelector('a');
        link.hidden = !cartUrl;
        if (cartUrl) link.href = cartUrl;
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { if (!toast.matches(':focus-within')) toast.hidden = true; }, 12000);
    }
    function update(root) {
        const state = states.get(root);
        if (!state) return;
        let missingGroups = 0;
        root.querySelectorAll('[data-builder-group]').forEach(group => {
            const inputs = [...group.querySelectorAll('input')];
            const count = inputs.filter(input => input.checked).length;
            const required = group.dataset.required === 'true';
            const rule = selection(required, group.dataset.min, group.dataset.max, count);
            if (!rule.valid) missingGroups++;
            group.classList.toggle('is-complete', required && rule.valid);
            group.classList.toggle('is-invalid', state.tried && !rule.valid);
            group.querySelector('[data-group-badge]').textContent = required ? (rule.valid ? 'Kész ✓' : 'Kötelező') : (count ? `${count} kiválasztva` : 'Opcionális');
            group.querySelector('[data-group-clear]').hidden = required || count === 0;
            const hint = group.querySelector('[data-group-feedback]');
            hint.textContent = state.tried && rule.missing
                ? `Válassz még ${rule.missing} lehetőséget ebből a csoportból.`
                : (rule.limited && inputs[0]?.type === 'checkbox' ? 'Elérted a választható mennyiséget. Másikhoz előbb vegyél ki egyet.' : '');
            inputs.forEach(input => {
                input.disabled = state.busy || !state.orderable || (input.type === 'checkbox' && !input.checked && rule.limited);
                input.setAttribute('aria-invalid', String(state.tried && !rule.valid));
            });
        });
        root.querySelectorAll('[data-builder-extra], [data-group-clear]').forEach(input => { input.disabled = state.busy || !state.orderable; });
        root.querySelectorAll('.city-builder-choice').forEach(label => {
            const input = label.querySelector('input');
            label.classList.toggle('is-selected', input.checked);
            label.classList.toggle('is-disabled', input.disabled && !input.checked);
        });
        const addons = checked(root, '[data-builder-addon]');
        const extras = checked(root, '[data-builder-extra]');
        const amount = total(state.item.price, addons.map(x => x.dataset.price), extras.map(x => x.dataset.price), state.qty);
        root.querySelector('[data-builder-total]').textContent = money(root, amount.total);
        root.querySelector('[data-builder-unit]').textContent = `${state.qty} adag · ${money(root, amount.unit)} / adag`;
        root.querySelector('[data-builder-quantity]').textContent = state.qty;
        root.querySelectorAll('[data-builder-qty]').forEach(button => {
            const delta = Number(button.dataset.builderQty);
            button.disabled = state.busy || !state.orderable || (delta < 0 ? state.qty <= 1 : state.qty >= 100);
        });
        root.querySelectorAll('[data-builder-submit]').forEach(button => { button.disabled = state.busy || !state.orderable; });
        const choices = [...addons, ...extras];
        root.querySelector('[data-selection-count]').textContent = choices.length;
        root.querySelector('[data-selection-summary]').textContent = choices.length ? choices.map(x => x.dataset.name).join(' · ') : 'Még nem választottál kiegészítőt.';
        const progress = root.querySelector('[data-builder-progress]');
        progress.textContent = !state.orderable ? 'Ez az étel jelenleg nem elérhető.'
            : state.busy ? 'Kosárba helyezés…'
            : missingGroups ? `Még ${missingGroups} kötelező csoportból választanod kell.` : 'Összeállítva. Mehet a kosárba!';
        progress.dataset.ready = String(missingGroups === 0);
        return missingGroups === 0;
    }
    function init(root) {
        if (states.has(root)) return;
        if (root.classList.contains('city-builder-page')) document.body.classList.add('city-has-builder');
        states.set(root, {item: JSON.parse(root.dataset.builderItem), qty: 1, tried: false, busy: false, orderable: root.dataset.orderable === 'true'});
        update(root);
    }
    function issue(root, text) {
        const error = root.querySelector('[data-builder-error]');
        error.textContent = text;
        error.hidden = !text;
    }
    async function submit(root, buynow) {
        const state = states.get(root);
        if (!state || state.busy || !state.orderable) return;
        state.tried = true;
        issue(root, '');
        if (!update(root)) {
            const group = root.querySelector('.is-invalid');
            group?.querySelector('input:not(:disabled)')?.focus({preventScroll: true});
            group?.scrollIntoView({behavior: host.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center'});
            return;
        }
        const addons = checked(root, '[data-builder-addon]');
        const extras = checked(root, '[data-builder-extra]');
        const join = (items, key) => items.map(x => key === 'id' ? x.value : x.dataset[key]).join('| ');
        const payload = new URLSearchParams({
            slug: state.item.slug, item_name: state.item.name, item_type: state.item.type,
            image_name: state.item.image, tax: state.item.tax ?? '', item_price: state.item.price,
            qty: state.qty, buynow,
            addons_id: join(addons, 'id'), addons_name: join(addons, 'name'), addons_price: join(addons, 'price'),
            extras_id: join(extras, 'id'), extras_name: join(extras, 'name'), extras_price: join(extras, 'price'),
        });
        state.busy = true;
        root.setAttribute('aria-busy', 'true');
        const button = root.querySelector(`[data-builder-submit="${buynow}"]`);
        const label = button.querySelector('[data-button-label]');
        const originalLabel = label.textContent;
        label.textContent = 'Hozzáadás…';
        update(root);
        try {
            const response = await fetch(root.dataset.cartUrl, {method: 'POST', body: payload, headers: {
                'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            }});
            const result = await response.json();
            if (!response.ok || Number(result.status) !== 1) throw new Error(result.message || 'Nem sikerült kosárba tenni. Próbáld újra.');
            document.querySelectorAll('.cart-badge, .qut_counter, .cart-modal .count').forEach(x => { x.textContent = result.data; });
            const modal = document.getElementById('modalitemdetails');
            host.bootstrap.Modal.getInstance(modal)?.hide();
            if (buynow === '1') {
                if (root.dataset.loginPrompt === 'true') {
                    const guest = document.querySelector('#useroption .btn-primary');
                    guest.href = `${root.dataset.checkoutUrl}?buynow=1`;
                    guest.removeAttribute('onclick');
                    guest.removeAttribute('target');
                    host.bootstrap.Modal.getOrCreateInstance(document.getElementById('useroption')).show();
                }
                else host.location.assign(`${root.dataset.checkoutUrl}?buynow=1`);
            } else {
                feedback('Kosárba került', root.dataset.cartViewUrl, `${state.qty} × ${state.item.name}`);
                document.dispatchEvent(new CustomEvent('city:cart-changed'));
            }
        } catch (error) {
            issue(root, error instanceof TypeError || error instanceof SyntaxError ? 'Nem sikerült kapcsolódni. A választásaid megmaradtak, próbáld újra.' : error.message);
        } finally {
            state.busy = false;
            label.textContent = originalLabel;
            root.removeAttribute('aria-busy');
            update(root);
        }
    }
    document.addEventListener('change', event => {
        const root = event.target.closest('[data-city-builder]');
        if (root) { issue(root, ''); update(root); }
    });
    document.addEventListener('click', event => {
        const root = event.target.closest('[data-city-builder]');
        if (!root) return;
        const state = states.get(root);
        if (!state || state.busy) return;
        const step = event.target.closest('[data-builder-qty]');
        const clear = event.target.closest('[data-group-clear]');
        const action = event.target.closest('[data-builder-submit]');
        if (step) { state.qty = quantity(state.qty + Number(step.dataset.builderQty)); update(root); }
        if (clear) { clear.closest('[data-builder-group]').querySelectorAll('input').forEach(input => { input.checked = false; }); update(root); }
        if (action) submit(root, action.dataset.builderSubmit);
    });
    let opening = 0;
    // Listing cards keep their existing handler, with the mobile builder owning its lifecycle.
    host.showitem = async function (slug, url) {
        const requestId = ++opening;
        const buttons = [...document.querySelectorAll(`.addon_modal_${CSS.escape(slug)}`)];
        buttons.forEach(button => { button.disabled = true; button.setAttribute('aria-busy', 'true'); });
        try {
            const endpoint = new URL(url, host.location.href);
            endpoint.searchParams.set('slug', slug);
            const response = await fetch(endpoint, {headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
            const result = await response.json();
            if (!response.ok || Number(result.status) !== 1) throw new Error(result.message || 'Az összeállítót most nem sikerült betölteni.');
            if (requestId !== opening) return;
            document.getElementById('modalitem_body').innerHTML = result.output;
            document.querySelectorAll('#modalitem_body [data-city-builder]').forEach(init);
            host.bootstrap.Modal.getOrCreateInstance(document.getElementById('modalitemdetails')).show();
        } catch (error) { feedback(error instanceof TypeError || error instanceof SyntaxError ? 'Az összeállítót most nem sikerült betölteni. Próbáld újra.' : error.message); }
        finally { buttons.forEach(button => { button.disabled = false; button.removeAttribute('aria-busy'); }); }
    };
    document.querySelectorAll('[data-city-builder]').forEach(init);
    host.addEventListener('pageshow', () => document.querySelectorAll('[data-city-builder]').forEach(root => { init(root); update(root); }));
})(typeof window !== 'undefined' ? window : globalThis);
