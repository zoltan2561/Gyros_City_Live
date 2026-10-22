const {test} = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');

function setup(saved = null, blocked = false) {
    const classes = new Set(), attrs = {}, events = {};
    const store = new Map(saved === null ? [] : [['sidebar',saved]]);
    const button = {setAttribute: (key,value) => {attrs[key]=value;}, addEventListener: (_,fn) => {events.click=fn;}};
    vm.runInNewContext(fs.readFileSync('public/city/admin-sidebar.js','utf8'), {
        document: {currentScript:{dataset:{storageKey:'sidebar'}}, readyState:'loading',
            documentElement:{classList:{toggle: (name,on) => on ? classes.add(name) : classes.delete(name)}},
            getElementById: () => button, addEventListener: (_,fn) => {events.ready=fn;}},
        localStorage: {getItem: key => {if(blocked)throw new Error('blocked');return store.get(key);},
            setItem: (key,value) => {if(blocked)throw new Error('blocked');store.set(key,value);}}
    });
    return {classes,attrs,events,store};
}
test('saved collapse applies before rendering and remains reversible with accessible labels', () => {
    const ui=setup('1');
    assert.ok(ui.classes.has('admin-sidebar-collapsed'));
    ui.events.ready();
    assert.equal(ui.attrs['aria-expanded'],'false');
    assert.equal(ui.attrs['aria-label'],'Oldalmenü megjelenítése');
    ui.events.click();
    assert.equal(ui.classes.has('admin-sidebar-collapsed'),false);
    assert.equal(ui.attrs['aria-expanded'],'true');
    assert.equal(ui.store.get('sidebar'),'0');
});
test('open by default, then remembers collapse for the next admin page', () => {
    const ui=setup();ui.events.ready();ui.events.click();
    assert.equal(ui.store.get('sidebar'),'1');
    assert.ok(setup(ui.store.get('sidebar')).classes.has('admin-sidebar-collapsed'));
});
test('blocked browser storage still allows opening and closing', () => {
    const ui=setup(null,true);ui.events.ready();ui.events.click();
    assert.equal(ui.attrs['aria-expanded'],'false');
    ui.events.click();assert.equal(ui.attrs['aria-expanded'],'true');
});
