(() => {
    'use strict';
    const root = document.getElementById('orders-live-results');
    const statistics = document.getElementById('orders-statistics');
    const feedback = document.getElementById('orders-arrival-feedback');
    const url = document.currentScript?.dataset.refreshUrl;
    if (!root || !statistics || !feedback || !url) return;
    const initialIds = [...root.querySelectorAll('tr[id^="dataid"]')].map(row => row.id.slice(6));
    const seen = new Set(initialIds);
    const maxInitial = Math.max(0, ...initialIds.map(Number));
    const query = new URL(url).searchParams;
    const defaultList = !query.get('q') && !query.get('status') && Number(query.get('page') || 1) === 1;
    let baseline = false;
    let refreshing = false;
    const queued = new Set();
    const highlights = new Set();
    let flashTimer;
    function announce(text) {
        feedback.hidden = false;
        feedback.querySelector('span').textContent = text;
    }
    function flash() {
        if (document.hidden || !highlights.size) return;
        clearTimeout(flashTimer);
        highlights.forEach(id => {
            const row = document.getElementById('dataid' + id);
            if (row) { row.classList.remove('order-row-new'); void row.offsetWidth; row.classList.add('order-row-new'); }
        });
        flashTimer = setTimeout(() => {
            highlights.forEach(id => document.getElementById('dataid' + id)?.classList.remove('order-row-new'));
            highlights.clear();
        }, 8000);
    }
    function editing() {
        return document.querySelector('.modal.show') || root.querySelector('select:disabled')
            || document.activeElement?.matches('.order-status-select');
    }
    async function refresh() {
        if (refreshing || !queued.size || editing()) return;
        refreshing = true;
        const arrivals = [...queued];
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 15000);
        try {
            const response = await fetch(url, {credentials: 'same-origin', cache: 'no-store', signal: controller.signal,
                headers: {'Accept':'text/html','X-Requested-With':'XMLHttpRequest','X-Orders-Refresh':'1'}});
            if (!response.ok) throw new Error('refresh');
            const html = new DOMParser().parseFromString(await response.text(), 'text/html');
            const next = html.getElementById('orders-live-results');
            const nextStats = html.getElementById('orders-statistics');
            if (!next || !nextStats) throw new Error('invalid response');
            // A select or payment dialog might have opened while the request was in flight.
            if (editing()) return;
            const scroll = root.querySelector('.table-responsive')?.scrollLeft || 0;
            root.replaceChildren(...next.childNodes);
            statistics.replaceChildren(...nextStats.childNodes);
            const table = root.querySelector('.table-responsive');
            if (table) table.scrollLeft = scroll;
            arrivals.forEach(id => { queued.delete(id); highlights.add(id); });
            announce(arrivals.length + ' új rendelés érkezett.');
            flash();
        } catch (_) {
            announce('Új rendelés érkezett. A listát nem sikerült frissíteni, újrapróbáljuk.');
        } finally {
            clearTimeout(timeout);
            refreshing = false;
        }
    }
    document.addEventListener('admin:orders-poll', event => {
        const ids = event.detail?.ids;
        if (!Array.isArray(ids)) return;
        ids.map(String).filter(id => /^\d+$/.test(id)).forEach(id => {
            if (!seen.has(id) && (baseline || (defaultList && Number(id) > maxInitial))) queued.add(id);
            seen.add(id);
        });
        baseline = true;
        if (queued.size) announce(queued.size + ' új rendelés érkezett.');
        void refresh();
    });
    document.addEventListener('focusout', () => { setTimeout(refresh, 0); });
    document.addEventListener('hidden.bs.modal', refresh);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) { flash(); void refresh(); } });
})();
