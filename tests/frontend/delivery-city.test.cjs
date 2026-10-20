const {test} = require('node:test');
const assert = require('node:assert/strict');
const {normalize, resolve} = require('../../public/city/delivery.js');

const areas = [
    {id: '1', charge: 340, cities: ['Vásárosnamény']},
    {id: '2', charge: 760, cities: ['Nagyvarsány']},
    {id: '3', charge: 1100, cities: ['Perényi-tanya']},
    {id: '4', charge: 2800, cities: ['Tarpa', 'gulács']},
    {id: '5', charge: 1600, cities: ['Aranyosapáti']},
    {id: '6', charge: 1700, cities: ['Aranyosapáti']},
];

test('city matching ignores accents, case, postal codes and extra spaces', () => {
    for (const city of ['Vásárosnamény', ' vasarosnameny ', 'VÁSÁROSNAMÉNY', '4800 Vásárosnamény', 'Vásárosnamény, 4800']) {
        assert.equal(resolve(city, areas).area.id, '1');
    }
});
test('hyphenated settlements remain complete and can be typed with a space', () => {
    assert.equal(normalize('Perényi-tanya'), normalize('Perenyi tanya'));
    assert.equal(resolve('Perenyi tanya', areas).area.charge, 1100);
});
test('each settlement in a shared delivery area maps to its configured fee', () => {
    assert.equal(resolve('Tarpa', areas).area.charge, 2800);
    assert.equal(resolve('Gulács', areas).area.id, '4');
});
test('partial or unsupported names never keep a previous fee', () => {
    for (const name of ['Vásáros', 'Budapest', 'Olcsvaapáti']) {
        assert.equal(resolve(name, areas, '1').area, null);
        assert.equal(resolve(name, areas, '1').status, 'unmatched');
    }
    assert.equal(resolve('', areas, '1').status, 'empty');
});
test('duplicate town names require manual choice rather than guessing a cheaper fee', () => {
    assert.equal(resolve('Aranyosapáti', areas).status, 'ambiguous');
    assert.equal(resolve('Aranyosapáti', areas, '6').area.charge, 1700);
    assert.equal(resolve('Aranyosapáti', areas, '1').area, null);
});
test('changing town replaces a previously selected area', () => {
    assert.equal(resolve('Nagyvarsány', areas, '1').area.id, '2');
});
