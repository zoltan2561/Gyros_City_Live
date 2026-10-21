(() => {
    'use strict';
    const root = document.getElementById('products-admin');
    if (!root || !window.jQuery?.fn.DataTable) return;
    document.body.classList.add('products-admin-page');
    const $ = window.jQuery;
    const tableElement = document.getElementById('itemsTable');
    const form = document.getElementById('product-editor-form');
    const editor = document.getElementById('product-editor');
    const controls = {q: document.getElementById('product-search'), category: document.getElementById('product-category'),
        state: document.getElementById('product-state'), featured: document.getElementById('product-featured'), sort: document.getElementById('product-sort')};
    const feedback = document.getElementById('products-feedback');
    let params = new URL(location.href).searchParams;
    const stateKey = 'gycity:product-list:' + location.pathname;
    try {
        if (![...params].length) params = new URLSearchParams(sessionStorage.getItem(stateKey) || '');
    } catch (_) { /* URL-based filters work without session storage. */ }
    let reordering = false;
    let reorderSnapshot = [];
    let submitting = false;
    let opener = null;
    let focusState = false;
    let previousView = {sort: 'menu', page: 0};
    Object.entries(controls).forEach(([name, control]) => {
        const value = params.get(name) ?? (name === 'q' ? params.get('dtsearch') ?? params.get('search') : null);
        if (value !== null && (control.tagName !== 'SELECT' || [...control.options].some(option => option.value === value))) control.value = value;
    });
    function notify(message, error = false) {
        feedback.textContent = message;
        feedback.hidden = false;
        feedback.classList.toggle('is-error', error);
    }
    try {
        const message = sessionStorage.getItem('gycity:product-message');
        if (message) { notify(message); sessionStorage.removeItem('gycity:product-message'); }
    } catch (_) { /* Storage may be blocked; editing still works. */ }
    function remember() {
        const url = new URL(location.href);
        ['dtsearch', 'search', 'option'].forEach(key => url.searchParams.delete(key));
        Object.entries(controls).forEach(([name, control]) => {
            if (control.value && !(name === 'sort' && control.value === 'menu')) url.searchParams.set(name, control.value);
            else url.searchParams.delete(name);
        });
        const page = table.page();
        if (page > 0) url.searchParams.set('page', String(page + 1)); else url.searchParams.delete('page');
        history.replaceState(null, '', url);
        try { sessionStorage.setItem(stateKey, url.searchParams.toString()); } catch (_) { /* Optional navigation memory. */ }
    }
    $.fn.dataTable.ext.search.push((settings, data, index) => {
        if (settings.nTable !== tableElement) return true;
        const row = settings.aoData[index].nTr;
        return (!controls.category.value || row.dataset.category === controls.category.value)
            && (!controls.state.value || row.dataset.state === controls.state.value)
            && (!controls.featured.value || row.dataset.featured === controls.featured.value);
    });
    const table = $(tableElement).DataTable({
        dom: 't<"row"<"col-md-5"i><"col-md-7"p>>', pageLength: 20, order: [[0, 'asc']], autoWidth: false,
        columnDefs: [{targets: [0, 7], visible: false}, {targets: [4, 5, 6], orderable: false}, {targets: [0, 6, 7], searchable: false}],
        language: {emptyTable: 'Még nincs termék. Az Új termék gombbal veheted fel az elsőt.',
            zeroRecords: 'Nincs találat. Módosítsd vagy töröld a szűrőket.', info: '_START_–_END_ / _TOTAL_ termék',
            infoEmpty: 'Nincs megjeleníthető termék', infoFiltered: '(_MAX_ termékből)',
            paginate: {previous: 'Előző', next: 'Következő'}, aria: {sortAscending: ': növekvő rendezés', sortDescending: ': csökkenő rendezés'}},
    });
    const exportOptions = {columns: [1, 2, 3, 4, 5], modifier: {search: 'applied', order: 'applied'},
        format: {body: (data, row, column, node) => {
            const product = JSON.parse(node.closest('tr').dataset.product);
            if (column === 1) return /^[=+\-@\t\r]/.test(product.item_name) ? "'" + product.item_name : product.item_name;
            if (column === 3) return Number(product.price);
            return node.textContent.trim().replace(/\s+/g, ' ');
        }}};
    new $.fn.dataTable.Buttons(table, {buttons: [{extend: 'excelHtml5', text: 'Excel', title: 'Gyros City – termékek', exportOptions},
        {extend: 'pdfHtml5', text: 'PDF', title: 'Gyros City – termékek', orientation: 'landscape', exportOptions}]}).container().appendTo('#products-export');
    function updateCounters() {
        const info = table.page.info();
        document.getElementById('products-result-count').textContent = info.recordsDisplay + ' találat / ' + info.recordsTotal + ' termék';
        root.querySelectorAll('[data-filter-state]').forEach(button => {
            const selected = button.dataset.filterState === controls.state.value;
            button.classList.toggle('is-selected', selected);
            button.setAttribute('aria-pressed', String(selected));
        });
        const filtered = controls.q.value.trim() || controls.category.value || controls.state.value || controls.featured.value;
        const reorderButton = document.getElementById('products-reorder');
        reorderButton.disabled = Boolean(filtered) || !info.recordsTotal;
        reorderButton.title = filtered ? 'Sorrendezéshez előbb töröld a szűrőket.' : 'A teljes étlap sorrendjének módosítása';
        document.getElementById('products-reorder-reason').hidden = !filtered;
    }
    function apply() {
        const orders = {menu: [0, 'asc'], name: [1, 'asc'], 'price-asc': [3, 'asc'], 'price-desc': [3, 'desc'], updated: [7, 'desc']};
        table.search(controls.q.value).order([orders[controls.sort.value] || orders.menu]).draw();
        updateCounters(); remember();
    }
    Object.entries(controls).forEach(([name, control]) => control.addEventListener(name === 'q' ? 'input' : 'change', apply));
    document.getElementById('products-filters').addEventListener('submit', event => { event.preventDefault(); apply(); });
    document.getElementById('products-filters').addEventListener('reset', () => {
        Object.values(controls).forEach(control => { control.value = control === controls.sort ? 'menu' : ''; });
        apply();
    });
    root.querySelectorAll('[data-filter-state]').forEach(button => button.addEventListener('click', () => {
        if (reordering) return;
        controls.state.value = button.dataset.filterState; apply();
    }));
    table.on('draw', () => { updateCounters(); if (!reordering) remember(); });
    apply();
    const initialPage = Math.max(0, Number(params.get('page') || 1) - 1);
    if (initialPage < table.page.info().pages) table.page(initialPage).draw('page');
    editor.addEventListener('shown.bs.modal', () => (focusState ? form.elements.availability : form.elements.item_name).focus());
    editor.addEventListener('hidden.bs.modal', () => { if (opener?.isConnected) opener.focus(); });
    root.addEventListener('click', event => {
        const trigger = event.target.closest('[data-quick-edit], [data-edit-state]');
        if (trigger && !reordering) {
            const product = JSON.parse(trigger.closest('tr').dataset.product);
            opener = trigger; focusState = trigger.hasAttribute('data-edit-state');
            form.reset();
            ['id', 'item_name', 'cat_id', 'price', 'availability', 'version'].forEach(key => { form.elements[key].value = product[key]; });
            form.elements.original_price.value = Number(product.original_price) > 0 ? product.original_price : '';
            form.elements.is_featured.checked = product.is_featured;
            document.getElementById('product-full-edit').href = product.edit_url;
            document.getElementById('product-editor-error').hidden = true;
            bootstrap.Modal.getOrCreateInstance(editor).show();
        }
        const deletion = event.target.closest('[data-delete-url]');
        if (deletion && !reordering) {
            if (root.dataset.demo === '1') return notify('Bemutató módban nem törölhető.', true);
            window.Delete(deletion.closest('tr').dataset.id, deletion.dataset.deleteUrl);
        }
        const move = event.target.closest('[data-move]');
        if (move && reordering && !submitting) {
            const row = move.closest('tr');
            const sibling = Number(move.dataset.move) < 0 ? row.previousElementSibling : row.nextElementSibling;
            if (sibling) { if (Number(move.dataset.move) < 0) sibling.before(row); else sibling.after(row); move.focus(); }
        }
    });
    async function post(url, data) {
        const abort = new AbortController();
        const timer = setTimeout(() => abort.abort(), 20000);
        try {
            const response = await fetch(url, {method: 'POST', headers: {'Content-Type': 'application/json',
                'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                body: JSON.stringify(data), signal: abort.signal});
            const result = await response.json().catch(() => ({}));
            if (!response.ok || !(result.success || result.status === 1)) {
                const error = new Error(result.errors ? Object.values(result.errors).flat().join('\n')
                    : response.status === 419 ? 'Lejárt a munkamenet. Frissítsd az oldalt, majd jelentkezz be újra.'
                    : result.message || 'Nem sikerült menteni. A megadott adatok megmaradtak.');
                throw error;
            }
            return result;
        } finally { clearTimeout(timer); }
    }
    function saved(message) {
        try { sessionStorage.setItem('gycity:product-message', message); } catch (_) { /* Optional feedback only. */ }
        location.reload();
    }
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (submitting || !form.reportValidity()) return;
        const error = document.getElementById('product-editor-error');
        if (root.dataset.demo === '1') { error.textContent = 'Bemutató módban nem menthető.'; error.hidden = false; return; }
        const data = Object.fromEntries(new FormData(form));
        data.is_featured = form.elements.is_featured.checked;
        data.original_price = data.original_price === '' ? 0 : data.original_price;
        submitting = true;
        const button = document.getElementById('product-save');
        button.disabled = true; button.textContent = 'Mentés…'; error.hidden = true;
        try { await post(root.dataset.saveUrl, data); saved('A termék módosításait mentettük.'); }
        catch (failure) {
            error.textContent = failure.name === 'AbortError' ? 'A mentés válasza nem érkezett meg. Frissítés után ellenőrizd a terméket, mielőtt újra mentenél.'
                : failure.message;
            error.hidden = false; error.focus();
        } finally { submitting = false; button.disabled = false; button.textContent = 'Módosítások mentése'; }
    });
    editor.addEventListener('hide.bs.modal', event => { if (submitting) event.preventDefault(); });
    const body = document.getElementById('productRows');
    tableElement.addEventListener('click', event => {
        if (reordering && event.target.closest('thead')) { event.preventDefault(); event.stopImmediatePropagation(); }
    }, true);
    tableElement.addEventListener('keydown', event => {
        if (reordering && event.target.closest('thead')) { event.preventDefault(); event.stopImmediatePropagation(); }
    }, true);
    function finishReorder() {
        if ($(body).hasClass('ui-sortable')) $(body).sortable('destroy');
        reordering = false; root.classList.remove('is-reordering');
        document.getElementById('products-reorder-help').hidden = true;
        Object.values(controls).forEach(control => { control.disabled = false; });
        root.querySelectorAll('.products-filters button').forEach(button => { button.disabled = false; });
        table.page.len(20).draw();
    }
    document.getElementById('products-reorder').addEventListener('click', () => {
        if (reordering) return;
        previousView = {sort: controls.sort.value, page: table.page()};
        controls.sort.value = 'menu';
        reordering = true; table.order([[0, 'asc']]).page.len(-1).draw();
        remember();
        reorderSnapshot = [...body.children];
        root.classList.add('is-reordering'); document.getElementById('products-reorder-help').hidden = false;
        Object.values(controls).forEach(control => { control.disabled = true; });
        root.querySelectorAll('.products-filters button').forEach(button => { button.disabled = true; });
        if ($.fn.sortable) $(body).sortable({items: 'tr[data-id]', handle: '.products-drag', placeholder: 'products-row-placeholder'});
        document.getElementById('products-reorder-save').focus();
    });
    document.getElementById('products-reorder-cancel').addEventListener('click', () => {
        if (submitting) return;
        reorderSnapshot.forEach(row => body.appendChild(row)); finishReorder();
        controls.sort.value = previousView.sort; apply(); table.page(previousView.page).draw('page');
        document.getElementById('products-reorder').focus();
    });
    document.getElementById('products-reorder-save').addEventListener('click', async event => {
        if (submitting) return;
        if (root.dataset.demo === '1') return notify('Bemutató módban nem menthető.', true);
        submitting = true; event.currentTarget.disabled = true;
        if ($(body).hasClass('ui-sortable')) $(body).sortable('disable');
        try {
            const order = [...body.querySelectorAll('tr[data-id]')].map((row, index) => ({id: row.dataset.id, position: index + 1}));
            await post(root.dataset.reorderUrl, {order}); saved('Az étlap sorrendjét mentettük.');
        } catch (failure) { notify(failure.name === 'AbortError' ? 'Nem érkezett válasz. Frissítés után ellenőrizd a mentett sorrendet.' : failure.message, true); }
        finally {
            submitting = false; document.getElementById('products-reorder-save').disabled = false;
            if ($(body).hasClass('ui-sortable')) $(body).sortable('enable');
        }
    });
})();
