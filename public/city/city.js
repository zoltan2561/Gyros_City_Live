(() => {
    'use strict';
    const body = document.body;
    const url = body.dataset.giftUrl;
    if (!url) return;
    let version = 0;
    let lastPrompt = '';
    const buynow = body.dataset.buynow || '0';
    const modalElement = document.getElementById('city-gift-modal');
    function render(data, prompt) {
        const panel = document.getElementById('city-cart-gifts');
        const modalBody = document.getElementById('city-gift-modal-body');
        if (panel) panel.innerHTML = data.html;
        if (modalBody) modalBody.innerHTML = panel ? '' : data.html;
        const signature = data.pending ? data.html.match(/data-gift-promotion="\d+"|<option value="" selected|<select /g)?.join('') : '';
        if (data.pending && prompt && !panel && signature !== lastPrompt) {
            lastPrompt = signature;
            const show = () => bootstrap.Modal.getOrCreateInstance(modalElement).show();
            const productModal = document.getElementById('modalitemdetails');
            if (productModal?.classList.contains('show')) {
                productModal.addEventListener('hidden.bs.modal', show, {once: true});
                bootstrap.Modal.getOrCreateInstance(productModal).hide();
            } else show();
        } else if (!data.pending) {
            lastPrompt = '';
            bootstrap.Modal.getInstance(modalElement)?.hide();
        }
    }
    async function refresh(prompt = false) {
        const requestVersion = ++version;
        try {
            const response = await fetch(`${url}?buynow=${buynow}`, {headers: {'Accept': 'application/json'}});
            if (!response.ok) throw new Error('gift_fetch');
            const data = await response.json();
            if (requestVersion === version) render(data, prompt);
        } catch (error) {
            const panel = document.getElementById('city-cart-gifts');
            if (panel) panel.textContent = 'Az akciókat most nem sikerült betölteni. Frissítsd az oldalt.';
        }
    }
    document.addEventListener('submit', async event => {
        const form = event.target.closest('[data-gift-form]');
        if (!form) return;
        event.preventDefault();
        const button = form.querySelector('button[type=submit]');
        const error = form.querySelector('.city-gift-error');
        button.disabled = true; error.textContent = '';
        try {
            const response = await fetch(url, {method: 'POST', body: new FormData(form), headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json'
            }});
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Nem sikerült elmenteni az ajándékot.');
            version++;
            if (!data.pending) document.querySelector('[data-city-gift-issue="true"]')?.remove();
            render(data, false);
        } catch (e) { error.textContent = e.message; button.disabled = false; }
    });
    if (window.jQuery) {
        jQuery(document).ajaxComplete((event, xhr, settings) => {
            if (/addtocart|cart\/(add|qtyupdate|delete)/.test(settings.url) && xhr.responseJSON?.status === 1) refresh(true);
        });
    }
    // Existing cart removal uses native fetch instead of jQuery.
    document.addEventListener('city:cart-changed', () => refresh(true));
    refresh(true);
})();
