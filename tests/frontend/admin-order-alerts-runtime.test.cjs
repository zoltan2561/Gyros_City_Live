const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const flush = () => new Promise(resolve => setImmediate(resolve));

function setup({orders = [7], play = () => Promise.resolve(), useWorker = true, inline = false} = {}) {
    const values = new Map(), events = {}, windowEvents = {}, timers = new Map(), audios = [], workers = [];
    let nextTimer = 0, now = 1000, fetches = 0, shows = 0;
    const buttons = {};
    const emitted = [];
    const nodes = Object.fromEntries(['order-audio-warning','order-audio-message','order-audio-enable','order-modal','notificationcount']
        .map(id => [id, {hidden: true, textContent: '', addEventListener: (event, fn) => {buttons[id + ':' + event] = fn;}}]));
    if (inline) nodes['orders-live-results'] = {};
    const doc = {hidden: true, title: 'Admin', currentScript: {dataset: {
        pollUrl: '/admin/getorder', audioBase: '/audio/', audioFile: 'notification.mp3', storageKey: 'qa', workerUrl: '/worker.js'
    }}, getElementById: id => nodes[id], addEventListener: (name, fn) => {events[name] = fn;}, dispatchEvent: event => {emitted.push(event); events[event.type]?.(event);}};
    const state = {orders, fail: false, play};
    const host = {
        document: doc, location: {href: 'http://localhost/admin/orders'},
        localStorage: {getItem: key => values.get(key) ?? null, setItem: (key, value) => values.set(key, value)},
        Audio: class {
            constructor(src) {this.src = src; this.pauses = 0; this.plays = 0; audios.push(this);}
            load() {this.loaded = true;}
            pause() {this.pauses++;}
            play() {this.plays++; return state.play();}
        },
        AbortController,
        CustomEvent: class { constructor(type, options) { this.type=type; this.detail=options.detail; } },
        setTimeout: (fn, ms) => {const id = ++nextTimer; timers.set(id, {fn, ms}); return id;},
        clearTimeout: id => timers.delete(id),
        fetch: async () => {fetches++; if (state.fail) throw new Error('offline'); return {ok: true, json: async () => ({orders: state.orders, noti: 'notification.mp3'})};},
        bootstrap: {Modal: {getInstance: () => ({hide() {}}), getOrCreateInstance: () => ({show() {shows++;}})}},
        addEventListener: (name, fn) => {windowEvents[name] = fn;}
    };
    if (useWorker) host.Worker = class {constructor() {workers.push(this);} terminate() {this.terminated = true;}};
    vm.runInNewContext(fs.readFileSync('public/city/admin-order-alerts.js','utf8'), {window: host, URL, Date: {now: () => now}});
    return {state, doc, nodes, values, audios, workers, events, windowEvents, emitted,
        fetches: () => fetches, shows: () => shows, advance: ms => {now += ms;},
        enable: async () => {buttons['order-audio-enable:click'](); await flush();},
        tick: async () => {
            if (workers.length && !workers[0].terminated) workers[0].onmessage({data:'poll'});
            else for (const [id, timer] of [...timers]) if (timer.ms === 5000) {timers.delete(id); timer.fn();}
            await flush();
        }};
}

test('orders workspace receives fresh pending IDs without a blocking arrival popup', async () => {
    const ui = setup({inline:true}); await flush();
    assert.equal(ui.shows(),0);
    assert.equal(ui.audios[0].plays,1);
    assert.equal(ui.emitted[0].type,'admin:orders-poll');
    assert.deepEqual(Array.from(ui.emitted[0].detail.ids),['7']);
    ui.state.orders=[7,8];await ui.tick();
    assert.deepEqual(Array.from(ui.emitted[1].detail.ids),['7','8']);
});

test('hidden admin plays new and three-minute repeat alerts and stops after acceptance', async () => {
    const ui = setup(); await flush();
    assert.equal(ui.doc.hidden, true);
    assert.equal(ui.audios[0].plays, 1);
    assert.deepEqual(JSON.parse(ui.values.get('qa')), {'7':1000});
    assert.match(ui.doc.title, /1.*Új rendelés/);
    ui.advance(180000); await ui.tick();
    assert.equal(ui.audios[0].plays, 2);
    ui.state.orders = []; await ui.tick();
    assert.equal(ui.doc.title, 'Admin');
    assert.equal(ui.audios[0].plays, 2);
    assert.ok(ui.audios[0].pauses > 0);
});

test('blocked background sound is not recorded as heard and can retry immediately', async () => {
    const ui = setup({play: () => Promise.reject(Object.assign(new Error('blocked'), {name:'NotAllowedError'}))});
    await flush();
    assert.deepEqual(JSON.parse(ui.values.get('qa')), {});
    assert.equal(ui.nodes['order-audio-warning'].hidden, false);
    ui.state.play = () => Promise.resolve();
    await ui.enable();
    assert.deepEqual(JSON.parse(ui.values.get('qa')), {'7':1000});
    assert.equal(ui.audios[0].plays, 2);
    assert.equal(ui.nodes['order-audio-warning'].hidden, true);
});

test('sound can be enabled with no order before moving the admin into the background', async () => {
    const ui = setup({orders:[]}); await flush();
    assert.equal(ui.audios.length, 1);
    assert.equal(ui.audios[0].loaded, true);
    assert.equal(ui.audios[0].plays, 0);
    assert.equal(ui.nodes['order-audio-warning'].hidden, false);
    await ui.enable();
    assert.equal(ui.audios[0].plays, 1);
    assert.deepEqual(JSON.parse(ui.values.get('qa')), {});
    ui.state.orders = [8]; await ui.tick();
    assert.equal(ui.audios.length, 1);
    assert.equal(ui.audios[0].plays, 2);
    assert.deepEqual(JSON.parse(ui.values.get('qa')), {'8':1000});
});

test('a browser-deferred play never blocks subsequent order checks or marks an accepted order later', async () => {
    let release;
    const ui = setup({play: () => new Promise(resolve => {release = resolve;})}); await flush();
    await ui.tick();
    assert.equal(ui.fetches(), 2);
    assert.equal(ui.audios[0].plays, 1);
    ui.state.orders = []; await ui.tick();
    release(); await flush();
    assert.deepEqual(JSON.parse(ui.values.get('qa')), {});
    assert.equal(ui.fetches(), 3);
});

test('a deferred sound attempt expires and can retry without waiting for foregrounding', async () => {
    const ui = setup({play: () => new Promise(() => {})}); await flush();
    ui.advance(16000); ui.state.play = () => Promise.resolve(); await ui.tick();
    assert.equal(ui.audios[0].plays, 2);
    assert.deepEqual(JSON.parse(ui.values.get('qa')), {'7':17000});
});

test('worker failure falls back to page timers and continues after a network error', async () => {
    const ui = setup({orders:[]}); await flush();
    ui.state.fail = true; ui.workers[0].onerror(); await flush();
    assert.equal(ui.workers[0].terminated, true);
    ui.state.fail = false; ui.state.orders = [9]; await ui.tick();
    assert.equal(ui.audios[0].plays, 1);
});

test('unsupported workers still poll and focus or reconnect checks immediately', async () => {
    const ui = setup({orders:[],useWorker:false}); await flush();
    await ui.tick(); assert.equal(ui.fetches(), 2);
    ui.state.orders = [10]; ui.windowEvents.online(); await flush();
    assert.equal(ui.audios[0].plays, 1);
    ui.windowEvents.focus(); await flush();
    assert.equal(ui.fetches(), 4);
});
