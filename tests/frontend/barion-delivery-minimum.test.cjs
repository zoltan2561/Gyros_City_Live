const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { execFileSync } = require('node:child_process');

const root = path.resolve(__dirname, '../..');
const view = fs.readFileSync(path.join(root, 'resources/views/web/checkout/checkout.blade.php'), 'utf8');
const bands = JSON.parse(execFileSync('php', [
    '-r', 'require "app/Services/DeliveryMinimum.php"; echo json_encode(\\App\\Services\\DeliveryMinimum::BANDS);',
], { cwd: root, encoding: 'utf8' }));
const start = view.indexOf("document.addEventListener('DOMContentLoaded'");
const script = view.slice(start, view.indexOf('</script>', start))
    .replace(/@json\([^)]*\)/g, JSON.stringify(bands))
    .replace(/\{\{[\s\S]*?\}\}/g, '/test-endpoint');

async function clickCheckout(orderType, charge, total, serverAllows = true, hiddenType = orderType) {
    let click;
    let redirect;
    const requests = [];
    const errors = [];
    const fields = {};
    for (const [id, value] of Object.entries({
        first_name: 'Test', last_name: 'Customer', email: 'test@example.test', mobile: '0612345678',
        new_address: 'Test utca 10', new_city: 'Budapest', deliverytime: '12:00',
        grand_total: String(total), shipping_charge: String(charge), buynow: '0',
        // The actual bug: the old delivery_charge input remained zero.
        delivery_charge_value: '0', order_type: String(hiddenType),
    })) {
        fields[id] = { value, focus() {} };
    }
    fields.delivery_area = {
        value: String(charge), selectedIndex: 0,
        options: [{ dataset: { charge: String(charge) } }], focus() {},
    };
    fields.terms = { checked: true, classList: { remove() {} } };
    fields.delivery_charge = {}; // Summary div, not an amount input.
    const button = {
        getAttribute: () => "isopenclose('/open','1','1000')",
        removeAttribute() {},
        addEventListener: (name, handler) => { click = handler; },
    };
    const context = {
        console: { error() {} }, URLSearchParams,
        toastr: { clear() {}, error: msg => errors.push(msg) },
        location: { set href(url) { redirect = url; } },
        document: {
            body: {},
            addEventListener: (name, handler) => handler(),
            getElementById: id => fields[id] || null,
            querySelector: selector => {
                if (selector === '#place-order-btn') return button;
                if (selector === 'input[name="transaction_type"]:checked') return { value: '16' };
                if (selector === 'input[name="order_type"]:checked') return { value: String(orderType) };
                if (selector === '.delivery_pickup_date') return { value: '2026-10-07' };
                if (selector === 'input#delivery_charge') return { value: '0' };
                if (selector.startsWith('input#')) return fields[selector.slice(6)] || null;
                return null;
            },
        },
        fetch: async (url, options) => {
            requests.push({ url, options });
            return {
                ok: true,
                json: async () => requests.length === 1 ? { status: 3 } : serverAllows
                    ? { ok: true, redirect: 'https://example.test/pay' }
                    : { ok: false, msg: 'Minimum not met' },
            };
        },
    };
    context.window = context;
    vm.runInNewContext(script, context);
    await click({ preventDefault() {}, stopPropagation() {} });
    return { requests, errors, redirect };
}

for (const [charge, minimum] of [[760, 5900], [2200, 7900], [2400, 11900]]) {
    test(`delivery charge ${charge}: minimum minus 1 never fetches or redirects`, async () => {
        const result = await clickCheckout(1, charge, minimum - 1 + charge);
        assert.equal(result.requests.length, 0);
        assert.equal(result.redirect, undefined);
        assert.equal(result.errors.length, 1);
        assert.ok(result.errors[0].includes(`${minimum} Ft`));
    });
    for (const extra of [0, 1]) {
        test(`delivery charge ${charge}: minimum plus ${extra} starts with the selected area`, async () => {
            const result = await clickCheckout(1, charge, minimum + extra + charge);
            assert.equal(result.errors.length, 0);
            assert.equal(result.requests.length, 2);
            const payload = JSON.parse(result.requests[1].options.body);
            assert.equal(payload.delivery_area, String(charge));
            assert.equal(payload.delivery_charge, charge);
            assert.equal(payload.order_type, '1');
            assert.equal(result.redirect, 'https://example.test/pay');
        });
    }
}

test('pickup ignores a previously selected far delivery area', async () => {
    const result = await clickCheckout(2, 2400, 500);
    assert.equal(result.errors.length, 0);
    assert.equal(JSON.parse(result.requests[1].options.body).delivery_charge, 0);
});

test('a server rejection cannot redirect to Barion', async () => {
    const result = await clickCheckout(1, 760, 6660, false);
    assert.equal(result.redirect, undefined);
    assert.deepEqual(result.errors, ['Minimum not met']);
});

test('a stale hidden pickup value cannot bypass a selected delivery minimum', async () => {
    const result = await clickCheckout(1, 2400, 3400, true, 2);
    assert.equal(result.requests.length, 0);
    assert.equal(result.redirect, undefined);
    assert.equal(result.errors.length, 1);
});
