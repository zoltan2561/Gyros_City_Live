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
    let worker;
    let playing = false;
    let playStarted = 0;
    let playGeneration = 0;
    let soundReady = false;
    const originalTitle = document.title;
    let soundIssue = '';
    let soundEnabled = false;
    const soundKey = script.dataset.storageKey + ':sound';
    try { soundIssue = storage.getItem(soundKey) || ''; soundEnabled = storage.getItem(soundKey + ':enabled') === '1'; } catch (_) {}
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
    function prepareAudio() {
        if (audioUrl && (!audio || audio.src !== audioUrl)) {
            if (audio) audio.pause();
            audio = new host.Audio(audioUrl);
            audio.preload = 'auto';
            audio.volume = 1;
            audio.load();
        }
    }
    function soundState() {
        if (soundIssue) showSoundIssue();
        else if (!soundReady && !soundEnabled) warn('Kapcsold be a rendelési hangot. A gomb rövid próbahangot játszik le.', true);
        else warning.hidden = true;
    }
    async function play(ids = pending, preview = false, quiet = false) {
        if (playing || (!ids.length && !preview)) return false;
        if (!audioUrl) {
            warn('Nincs rendelési hang beállítva. Válassz hangot az általános beállításokban.', false);
            return false;
        }
        const generation = ++playGeneration;
        const announced = [...ids];
        playing = true;
        playStarted = Date.now();
        try {
            prepareAudio();
            audio.currentTime = 0;
            audio.volume = quiet ? 0 : 1;
            await audio.play();
            if (generation !== playGeneration) return false;
            if (quiet) { audio.pause(); audio.currentTime = 0; audio.volume = 1; }
            if (generation !== playGeneration) return false;
            // A blocked or deferred play must never postpone the next alert.
            tracker.mark(announced.filter(id => pending.includes(id)), Date.now());
            soundReady = true;
            soundEnabled = true;
            try { storage.setItem(soundKey + ':enabled', '1'); } catch (_) {}
            saveSoundIssue('');
            warning.hidden = true;
            return true;
        } catch (error) {
            if (generation !== playGeneration) return false;
            if (audio) audio.volume = 1;
            saveSoundIssue(error.name === 'NotAllowedError' ? 'blocked' : 'failed');
            showSoundIssue();
            return false;
        } finally {
            if (generation === playGeneration) playing = false;
        }
    }
    enable.addEventListener('click', () => {
        // Cancel an old deferred attempt; call play directly in this user gesture.
        ++playGeneration;
        playing = false;
        if (audio) audio.pause();
        void play(pending, true);
    });
    // A saved preference hides the repeated setup prompt, but actual playback errors
    // still show it. A fresh document primes its own audio element on user interaction.
    function primeSound() {
        if (soundEnabled && !soundReady && !playing && audioUrl) void play([], true, true);
    }
    document.addEventListener('pointerdown', primeSound);
    document.addEventListener('keydown', primeSound);
    if (script.dataset.audioFile) {
        audioUrl = new URL(script.dataset.audioBase + encodeURIComponent(script.dataset.audioFile), host.location.href).href;
        prepareAudio();
    }
    soundState();
    async function poll() {
        if (busy) return;
        if (playing && Date.now() - playStarted >= 15000) {
            ++playGeneration;
            playing = false;
            audio?.pause();
            saveSoundIssue('failed');
        }
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
            document.dispatchEvent(new host.CustomEvent('admin:orders-poll', {detail: {ids: [...pending]}}));
            const badge = document.getElementById('notificationcount');
            if (badge) badge.textContent = pending.length > 9 ? pending.length + '+' : String(pending.length);
            audioUrl = data.noti ? new URL(script.dataset.audioBase + encodeURIComponent(data.noti), host.location.href).href : '';
            prepareAudio();
            document.title = pending.length ? '(' + pending.length + ') Új rendelés · ' + originalTitle : originalTitle;
            const due = tracker.due(pending, Date.now());
            if (!pending.length) {
                ++playGeneration;
                playing = false;
                if (audio) { audio.pause(); audio.currentTime = 0; }
                host.bootstrap.Modal.getInstance(modal)?.hide();
                soundState();
            } else if (due.length) {
                if (!document.getElementById('orders-live-results')) host.bootstrap.Modal.getOrCreateInstance(modal).show();
                // Some browsers defer background play(). Do not hold up fresh polls.
                void play(due);
            }
        } catch (_) {
            // Retry after transport errors too; stale order data must not trigger a sound.
        } finally {
            host.clearTimeout(timeout);
            busy = false;
            if (!worker) timer = host.setTimeout(poll, 5000);
        }
    }
    function refresh() { host.clearTimeout(timer); void poll(); }
    // Keep the poll clock separate from the hidden page's window timers.
    // A frozen/discarded tab or sleeping device can still suspend its worker.
    if (host.Worker && script.dataset.workerUrl) {
        try {
            worker = new host.Worker(script.dataset.workerUrl);
            worker.onmessage = event => { if (event.data === 'poll') void poll(); };
            worker.onerror = () => {
                worker?.terminate();
                worker = null;
                refresh();
            };
        } catch (_) { worker = null; }
    }
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) refresh();
    });
    host.addEventListener('focus', refresh);
    host.addEventListener('online', refresh);
    host.addEventListener('pageshow', refresh);
    poll();
})(typeof window !== 'undefined' ? window : globalThis);
