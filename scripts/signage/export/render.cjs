const fs = require('node:fs');
const path = require('node:path');
const { execFile } = require('node:child_process');
const { promisify } = require('node:util');
const { chromium } = require('playwright-core');

const signage = path.resolve(__dirname, '../../../public/signage');
const viewport = { width: 1920, height: 1080 };
const origin = 'http://signage.invalid';

async function record(page, scene, products, filename) {
    const duration = await page.evaluate(async ({ scene, products }) => {
        const stage = document.querySelector('main');
        GCSignage.render(stage, scene, products);
        await Promise.all([...stage.querySelectorAll('img')].map(img => img.decode()));
        await document.fonts.ready;
        for (const video of stage.querySelectorAll('video')) {
            await video.play();
            video.currentTime = 0;
        }
        if (scene.play_to_end && scene.media?.duration) return scene.media.duration;
        return scene.duration;
    }, { scene, products });
    await page.screencast.start({ path: filename, size: viewport, fps: 25 });
    await page.waitForTimeout(duration * 1000);
    await page.screencast.stop();
    return duration;
}

async function main() {
    const output = process.argv[2];
    const { scenes, products, assets, ffmpeg, progress_file, timeout = 7200 } = JSON.parse(fs.readFileSync(0, 'utf8'));
    const abort = new AbortController();
    const deadline = Date.now() + timeout * 1000;
    let browser;
    const stop = () => {
        abort.abort();
        browser?.close().catch(() => {});
        // A normal exit also runs Playwright's browser-process-group cleanup.
        setTimeout(() => process.exit(1), 5000).unref();
    };
    process.on('SIGTERM', stop);
    process.on('SIGINT', stop);
    const encode = args => promisify(execFile)(ffmpeg, ['-nostdin','-hide_banner','-loglevel','error', ...args],
        { windowsHide:true, signal:abort.signal, timeout:Math.max(1, deadline - Date.now()), maxBuffer:256 * 1024 });
    const progress = scene => {
        if (!progress_file) return;
        fs.writeFileSync(progress_file + '.tmp', JSON.stringify({scene}));
        fs.renameSync(progress_file + '.tmp', progress_file);
    };
    await encode(['-version']);
    browser = await chromium.launch(process.platform === 'win32' ? { channel: 'msedge' } : {});
    const clips = [];
    let seconds = 0;
    try {
        const page = await browser.newPage({ viewport, serviceWorkers: 'block' });
        // Serve only the renderer and the files selected by Laravel. No session or network access is needed.
        await page.route('**/*', async route => {
            const url = new URL(route.request().url());
            if (url.origin !== origin) return route.abort();
            if (url.pathname === '/') return route.fulfill({ contentType: 'text/html; charset=utf-8',
                body: '<!doctype html><html lang="hu"><meta charset="utf-8"><link rel="stylesheet" href="/signage/player.css"><main id="signage-stage"></main><script src="/signage/renderer.js"></script></html>' });
            const file = assets[url.pathname];
            if (file) return route.fulfill({ path: file });
            if (/^\/signage\/(renderer\.js|(?:player|modern|backgrounds)\.css|reference-assets\/[\w-]+\.(?:ttf|webp|svg))$/.test(url.pathname)) {
                return route.fulfill({ path: path.join(signage, url.pathname.slice('/signage/'.length)) });
            }
            return route.abort();
        });
        await page.goto(origin);
        for (const [index, scene] of scenes.entries()) {
            progress(index + 1);
            const webm = path.resolve(index + '.webm');
            const clip = index + '.mp4';
            const duration = await record(page, scene, products, webm);
            await encode(['-i', webm, '-filter_threads','2','-vf','fps=25,tpad=stop_mode=clone:stop_duration=2','-t',String(duration),
                '-an','-c:v','libx264','-preset','veryfast','-crf','21','-maxrate','6M','-bufsize','12M',
                '-threads','2','-pix_fmt','yuv420p','-profile:v','high','-level','4.0',clip]);
            fs.unlinkSync(webm);
            clips.push("file '" + clip + "'");
            seconds += duration;
        }
    } finally {
        await browser.close();
    }
    const cycles = Math.ceil(600 / seconds);
    fs.writeFileSync('playlist.txt', Array(cycles).fill(clips.join('\n')).join('\n'));
    await encode(['-f','concat','-safe','1','-i','playlist.txt','-c','copy','-movflags','+faststart',output]);
}

main().catch(error => { console.error(error.message); process.exitCode = 1; });
