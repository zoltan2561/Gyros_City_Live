const {test} = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');

test('legacy cash requests include the actual consent and selected delivery zone', () => {
    let filter;
    const checkbox = {checked: true};
    const context = {URLSearchParams, window: {jQuery: {ajaxPrefilter: fn => filter = fn}},
        document: {getElementById: id => id === 'terms' ? checkbox : {value: '7'}}};
    vm.runInNewContext(fs.readFileSync('public/city/checkout-guard.js', 'utf8'), context);
    const request = {url: '/Gyros_City_Live/placeorder', data: 'qty=2'};
    filter(request);
    const body = new URLSearchParams(request.data);
    assert.equal(body.get('terms'), '1');
    assert.equal(body.get('delivery_area'), '7');
    assert.equal(body.get('qty'), '2');
    checkbox.checked = false;
    filter(request);
    assert.equal(new URLSearchParams(request.data).get('terms'), '0');
    const other = {url: '/profile/update', data: 'name=Test'};
    filter(other);
    assert.equal(other.data, 'name=Test');
});

test('ordering availability changes with POST and CSRF rather than URL navigation', async () => {
    let sent;
    let reloads = 0;
    const context = {URLSearchParams, window: {}, are_you_sure: 'Confirm', yes: 'Yes', no: 'No',
        swalWithBootstrapButtons: {fire: async () => ({isConfirmed: true})},
        document: {getElementById: () => ({checked: false}), querySelector: () => ({content: 'test-csrf'})},
        fetch: async (url, options) => {sent = {url, options}; return {ok: true};},
        location: {reload: () => reloads++}, toastr: {error: () => assert.fail('Unexpected error')}};
    vm.runInNewContext(fs.readFileSync('public/city/admin-ordering-switch.js', 'utf8'), context);
    await context.window.changeStatus(2, '/admin/change-status');
    assert.equal(sent.options.method, 'POST');
    assert.equal(sent.options.headers['X-CSRF-TOKEN'], 'test-csrf');
    assert.equal(sent.options.body.get('status'), '2');
    assert.equal(reloads, 1);
});
