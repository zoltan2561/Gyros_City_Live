const {test} = require('node:test');
const assert = require('node:assert/strict');
const {quantity, total, selection} = require('../../public/city/builder.js');

test('total includes a paid drink and extras for every portion', () => {
    assert.deepEqual(total(2490, [0, 100], [230], 2), {unit: 2820, total: 5640});
});
test('removing a supplement restores the base price', () => {
    assert.equal(total(2490, [0], [], 1).total, 2490);
});
test('prices retain cents without floating point accumulation', () => {
    assert.deepEqual(total(0.1, [0.2], [0.1], 3), {unit: 0.4, total: 1.2});
});
test('quantity stays within the server accepted range', () => {
    assert.equal(quantity(0), 1);
    assert.equal(quantity(101), 100);
    assert.equal(quantity('invalid'), 1);
    assert.equal(quantity(2.5), 2);
});
test('a required choice remains incomplete until its minimum is met', () => {
    assert.deepEqual(selection(true, 2, 3, 1), {missing: 1, valid: false, limited: false});
    assert.equal(selection(true, 2, 3, 2).valid, true);
});
test('optional choices allow none but never exceed their configured limit', () => {
    assert.equal(selection(false, 1, 2, 0).valid, true);
    assert.equal(selection(false, 0, 2, 2).limited, true);
    assert.equal(selection(false, 0, 2, 3).valid, false);
});
