(() => {
    'use strict';
    const form = document.getElementById('playlist-export');
    if (!form) return;
    const button = form.querySelector('button');
    const status = form.querySelector('[role=status]');
    const download = form.querySelector('[data-export-download]');
    let timer, activeId;

    async function request(url, options) {
        const response = await fetch(url, {...options, headers:{Accept:'application/json'}, cache:'no-store'});
        const json = response.headers.get('Content-Type')?.includes('application/json');
        const data = json ? await response.json() : {};
        if (!response.ok || !json) {
            const error = new Error(Object.values(data.errors || {}).flat()[0] ||
                (response.status === 409 ? data.message : null) || 'A videókészítés nem elérhető. Ellenőrizd a bejelentkezést, majd próbáld újra.');
            error.stop = [401,403,404,419].includes(response.status) || !json;
            throw error;
        }
        return data;
    }

    function show(job) {
        clearTimeout(timer);
        if (!job) { button.disabled = false; return; }
        activeId = job.id;
        const running = ['pending','running'].includes(job.status);
        button.disabled = running;
        download.hidden = job.status !== 'complete';
        if (job.status === 'complete') {
            download.href = form.dataset.exportsUrl + '/' + encodeURIComponent(job.id) + '/download';
            download.download = job.filename;
            status.textContent = 'A videó elkészült. Letölthető 24 órán át; az árak a videóban nem frissülnek automatikusan.';
        } else if (job.status === 'failed') {
            status.textContent = 'A videó készítése megszakadt. Indítsd újra; ismételt hiba esetén jelezd az üzemeltetőnek.';
        } else {
            status.textContent = job.status === 'pending' ? 'A videókészítés indul. Ezt a lapot bezárhatod, később ide visszatérve letöltheted.' :
                'A videó készül: ' + job.scene + ' / ' + job.total_scenes + ' jelenet. Később is visszatérhetsz a letöltéshez.';
            timer = setTimeout(poll, 3000);
        }
    }

    async function poll() {
        try { show(await request(form.dataset.exportsUrl + '/' + encodeURIComponent(activeId))); }
        catch (error) {
            status.textContent = error.stop ? error.message : 'Az állapot most nem érhető el. Újra ellenőrizzük; a videókészítés folytatódhat.';
            if (error.stop) button.disabled = false;
            else timer = setTimeout(poll, 5000);
        }
    }

    button.disabled = true;
    request(form.action).then(data => show(data.job)).catch(error => {
        status.textContent = error.message;
        button.disabled = false;
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (button.disabled) return;
        button.disabled = true;
        download.hidden = true;
        status.textContent = 'A videókészítés indítása…';
        try { show(await request(form.action, {method:'POST', body:new FormData(form)})); }
        catch (error) {
            // A lost POST response must not create a second export on retry.
            try {
                const data = await request(form.action);
                if (data.job && ['pending','running'].includes(data.job.status)) { show(data.job); return; }
            } catch (_) { /* The next deliberate attempt is still protected by the server lock. */ }
            status.textContent = error.message;
            button.disabled = false;
        }
    });
})();
