(function (host) {
    'use strict';
    const REPEAT_MS = 180000;
    function createTracker(storage, key, repeatMs = REPEAT_MS) {
        let history = {};
        function read() {
            try {
                const saved = JSON.parse(storage.getItem(key) || '{}');
                if (saved && typeof saved === 'object' && !Array.isArray(saved)) history = saved;
            } catch (_) { /* Keep this page working when storage is unavailable. */ }
        }
        function save() { try { storage.setItem(key, JSON.stringify(history)); } catch (_) {} }
        return {
            due(ids, now) {
                read();
                const active = new Set(ids.map(String));
                Object.keys(history).forEach(id => { if (!active.has(id)) delete history[id]; });
                save();
                return [...active].filter(id => !Number.isFinite(history[id]) || now < history[id] || now - history[id] >= repeatMs);
            },
            mark(ids, now) {
                read();
                ids.forEach(id => { history[String(id)] = now; });
                save();
            },
        };
    }
    if (typeof module === 'object' && module.exports) module.exports = {createTracker, REPEAT_MS};
    if (!host.document) return;
    const script = host.document.currentScript;
    if (!script?.dataset.pollUrl) return;
    const document = host.document;
    let storage;
    try { storage = host.localStorage; } catch (_) { storage = null; }
    const tracker = createTracker(storage, script.dataset.storageKey);
    const warning = document.getElementById('order-audio-warning');
    const message = document.getElementById('order-audio-message');
    const enable = document.getElementById('order-audio-enable');
    const modal = document.getElementById('order-modal');
    let audio;
    let audioUrl = '';
    let pending = [];
    let timer;
    let busy = false;
    let soundIssue = '';
    const soundKey = script.dataset.storageKey + ':sound';
    try { soundIssue = storage.getItem(soundKey) || ''; } catch (_) {}
    function saveSoundIssue(value) {
        soundIssue = value;
        try { storage.setItem(soundKey, value); } catch (_) {}
    }
    function warn(text, retry) {
        message.textContent = text;
        enable.hidden = !retry;
        warning.hidden = false;
    }
    function showSoundIssue() {
        warn(soundIssue === 'blocked'
            ? 'A böngésző letiltotta a rendelési hangot. A bekapcsoláshoz kattints a gombra.'
            : 'A rendelési hangot nem sikerült lejátszani. Ellenőrizd a hangerőt és a beállított hangfájlt.', true);
    }
    async function play() {
        if (!pending.length) return;
        if (!audioUrl) {
            warn('Nincs rendelési hang beállítva. Válassz hangot az általános beállításokban.', false);
            return;
        }
        try {
            if (!audio || audio.src !== audioUrl) {
                audio = new host.Audio(audioUrl);
                audio.preload = 'auto';
                audio.volume = 1;
            }
            audio.currentTime = 0;
            await audio.play();
            saveSoundIssue('');
            warning.hidden = true;
        } catch (error) {
            if (!pending.length) return;
            saveSoundIssue(error.name === 'NotAllowedError' ? 'blocked' : 'failed');
            showSoundIssue();
        }
    }
    enable.addEventListener('click', play);
    async function poll() {
        if (busy) return;
        busy = true;
        const controller = new host.AbortController();
        const timeout = host.setTimeout(() => controller.abort(), 15000);
        try {
            const response = await host.fetch(script.dataset.pollUrl, {
                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                signal: controller.signal,
            });
            if (!response.ok) throw new Error('Order polling failed');
            const data = await response.json();
            if (!Array.isArray(data.orders)) throw new Error('Invalid order response');
            pending = data.orders.map(String);
            const badge = document.getElementById('notificationcount');
            if (badge) badge.textContent = pending.length > 9 ? pending.length + '+' : String(pending.length);
            audioUrl = data.noti ? new URL(script.dataset.audioBase + encodeURIComponent(data.noti), host.location.href).href : '';
            if (pending.length && soundIssue) showSoundIssue();
            const due = tracker.due(pending, Date.now());
            if (!pending.length) {
                warning.hidden = true;
                if (audio) { audio.pause(); audio.currentTime = 0; }
                host.bootstrap.Modal.getInstance(modal)?.hide();
            } else if (due.length) {
                tracker.mark(due, Date.now());
                host.bootstrap.Modal.getOrCreateInstance(modal).show();
                await play();
            }
        } catch (_) {
            // Retry after transport errors too; stale order data must not trigger a sound.
        } finally {
            host.clearTimeout(timeout);
            busy = false;
            timer = host.setTimeout(poll, 5000);
        }
    }
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) { host.clearTimeout(timer); poll(); }
    });
    poll();
})(typeof window !== 'undefined' ? window : globalThis);
