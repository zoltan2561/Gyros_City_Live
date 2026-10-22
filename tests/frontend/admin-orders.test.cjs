const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function setup({type = '2', accept = true, fetcher} = {}) {
    let handler;
    const calls = [], classes = new Set();
    const feedback = {textContent: '', classList: {add: x => classes.add(x), remove: x => classes.delete(x)}};
    const select = {value: '11', disabled: false, dataset: {orderId: '7', current: '10', url: '/admin/orders/update'},
        selectedOptions: [{dataset: {type}}], parentElement: {querySelector: () => feedback}};
    let reloads = 0;
    vm.runInNewContext(fs.readFileSync('public/city/admin-orders.js', 'utf8'), {
        document: {addEventListener: (_, fn) => {handler = fn;}, querySelector: () => ({content: 'csrf-test'})},
        window: {confirm: () => accept, location: {reload: () => reloads++}},
        fetch: async (...args) => {calls.push(args); return fetcher ? fetcher(...args) : {ok: true, text: async () => '1'};},
        AbortController, URLSearchParams, setTimeout, clearTimeout
    });
    return {select, feedback, calls, classes, change: () => handler({target: {closest: () => select}}), reloads: () => reloads};
}

test('status saves the order and type with CSRF and refreshes the current filtered URL', async () => {
    const ui = setup();
    await ui.change();
    assert.equal(ui.calls.length, 1);
    assert.equal(ui.calls[0][0], '/admin/orders/update');
    const options = ui.calls[0][1];
    assert.equal(options.headers['X-CSRF-TOKEN'], 'csrf-test');
    assert.equal(options.body.toString(), 'id=7&status=11&statustype=2');
    assert.equal(ui.reloads(), 1);
    assert.equal(ui.feedback.textContent, 'Mentve.');
});

test('cancelling the cancellation confirmation sends no request and restores the selection', async () => {
    const ui = setup({type: '4', accept: false});
    await ui.change();
    assert.equal(ui.calls.length, 0);
    assert.equal(ui.select.value, '10');
    assert.equal(ui.select.disabled, false);
});

test('a second change while saving cannot submit a duplicate update', async () => {
    let release;
    const ui = setup({fetcher: () => new Promise(resolve => {release = resolve;})});
    const pending = ui.change();
    await ui.change();
    assert.equal(ui.calls.length, 1);
    assert.equal(ui.select.disabled, true);
    release({ok: true, text: async () => '1'});
    await pending;
});

for (const failure of ['network', 'server', 'expired-login']) {
    test(`${failure} restores the control and reports that saving was not confirmed`, async () => {
        const ui = setup({fetcher: async () => {
            if (failure === 'network') throw new Error('offline');
            return {ok: failure === 'expired-login', text: async () => '<html>Login</html>'};
        }});
        await ui.change();
        assert.equal(ui.reloads(), 0);
        assert.equal(ui.select.value, '10');
        assert.equal(ui.select.disabled, false);
        assert.ok(ui.classes.has('text-danger'));
        assert.match(ui.feedback.textContent, /nem igazolható/);
    });
}
