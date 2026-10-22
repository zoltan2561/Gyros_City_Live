(() => {
    'use strict';
    document.addEventListener('change', async event => {
        const select = event.target.closest('.order-status-select');
        if (!select || select.disabled || select.value === select.dataset.current) return;
        const selected = select.selectedOptions[0];
        const feedback = select.parentElement.querySelector('.order-status-feedback');
        if (selected.dataset.type === '4' && !window.confirm('Biztosan törlöd ezt a rendelést?')) {
            select.value = select.dataset.current;
            return;
        }
        select.disabled = true;
        feedback.classList.remove('text-danger');
        feedback.textContent = 'Állapot mentése…';
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 20000);
        try {
            const response = await fetch(select.dataset.url, {
                method: 'POST', credentials: 'same-origin', signal: controller.signal,
                headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({id: select.dataset.orderId, status: select.value, statustype: selected.dataset.type})
            });
            if (!response.ok || (await response.text()).trim() !== '1') throw new Error('save');
            feedback.textContent = 'Mentve.';
            // Preserve search, pagination and status filters; refresh counters and payment actions too.
            window.location.reload();
        } catch (error) {
            select.value = select.dataset.current;
            select.disabled = false;
            feedback.classList.add('text-danger');
            feedback.textContent = 'A mentés nem igazolható. Frissítsd a listát, majd ellenőrizd az állapotot.';
        } finally {
            clearTimeout(timeout);
        }
    });
})();
