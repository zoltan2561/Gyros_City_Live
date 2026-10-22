(() => {
    'use strict';
    const root = document.documentElement;
    const key = document.currentScript?.dataset.storageKey;
    if (!key) return;
    let collapsed = false;
    try { collapsed = localStorage.getItem(key) === '1'; } catch (_) {}
    root.classList.toggle('admin-sidebar-collapsed', collapsed);
    function init() {
        const button = document.getElementById('admin-sidebar-toggle');
        if (!button) return;
        function render() {
            root.classList.toggle('admin-sidebar-collapsed', collapsed);
            button.setAttribute('aria-expanded', String(!collapsed));
            const label = collapsed ? 'Oldalmenü megjelenítése' : 'Oldalmenü elrejtése';
            button.setAttribute('aria-label', label);
            button.title = label;
        }
        button.addEventListener('click', () => {
            collapsed = !collapsed;
            try { localStorage.setItem(key, collapsed ? '1' : '0'); } catch (_) {}
            render();
        });
        render();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, {once: true});
    else init();
})();
