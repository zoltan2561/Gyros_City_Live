const {test} = require('node:test');
const assert = require('node:assert/strict');
const {createTracker, REPEAT_MS} = require('../../public/city/admin-order-alerts.js');
const storage = () => {
    const data = new Map();
    return {getItem: key => data.get(key) ?? null, setItem: (key, value) => data.set(key, value)};
};

test('new orders alert immediately and repeat only after three minutes', () => {
    const tracker = createTracker(storage(), 'test');
    assert.equal(REPEAT_MS, 180000);
    assert.deepEqual(tracker.due([1], 1000), ['1']);
    tracker.mark([1], 1000);
    assert.deepEqual(tracker.due([1], 180999), []);
    assert.deepEqual(tracker.due([1], 181000), ['1']);
    tracker.mark([1], 181000);
    assert.deepEqual(tracker.due([1], 361000), ['1']);
});

test('closing the popup or reloading the admin does not cancel an outstanding reminder', () => {
    const shared = storage();
    createTracker(shared, 'admin').mark([7], 1000);
    const reloaded = createTracker(shared, 'admin');
    assert.deepEqual(reloaded.due([7], 10000), []);
    assert.deepEqual(reloaded.due([7], 181000), ['7']);
});

test('accepting an order removes its reminder and does not affect another order', () => {
    const shared = storage();
    const tracker = createTracker(shared, 'admin');
    tracker.mark([1, 2], 1000);
    assert.deepEqual(tracker.due([2], 181000), ['2']);
    assert.deepEqual(JSON.parse(shared.getItem('admin')), {'2': 1000});
    assert.deepEqual(tracker.due([], 200000), []);
    assert.deepEqual(JSON.parse(shared.getItem('admin')), {});
});

test('a replacement order alerts even when the total count is unchanged', () => {
    const tracker = createTracker(storage(), 'admin');
    tracker.mark([1], 1000);
    assert.deepEqual(tracker.due([2], 2000), ['2']);
});

test('a new order does not postpone an older unaccepted order', () => {
    const tracker = createTracker(storage(), 'admin');
    tracker.mark([1], 1000);
    assert.deepEqual(tracker.due([1, 2], 91000), ['2']);
    tracker.mark([2], 91000);
    assert.deepEqual(tracker.due([1, 2], 181000), ['1']);
});

test('another admin tab reads the last alert time instead of immediately duplicating it', () => {
    const shared = storage();
    const first = createTracker(shared, 'admin');
    const second = createTracker(shared, 'admin');
    first.mark([1], 1000);
    assert.deepEqual(second.due([1], 1001), []);
});

test('blocked or malformed local storage does not disable reminders on the current page', () => {
    const blocked = {getItem() {throw new Error('Blocked');}, setItem() {throw new Error('Quota');}};
    const tracker = createTracker(blocked, 'admin');
    tracker.mark([1], 1000);
    assert.deepEqual(tracker.due([1], 1001), []);
    assert.deepEqual(tracker.due([1], 181000), ['1']);
    const broken = createTracker({getItem: () => '{invalid', setItem() {}}, 'admin');
    assert.deepEqual(broken.due([1], 1000), ['1']);
});

test('a clock reset cannot leave an order permanently silent', () => {
    const tracker = createTracker(storage(), 'admin');
    tracker.mark([1], 200000);
    assert.deepEqual(tracker.due([1], 1000), ['1']);
});
