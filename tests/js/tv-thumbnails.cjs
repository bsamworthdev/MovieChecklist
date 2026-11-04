const assert = require('node:assert/strict');
const fs = require('node:fs');
const {JSDOM, VirtualConsole} = require('jsdom');
(async () => {
    const dom = new JSDOM('<meta name="csrf-token" content="test"><button id="generate-tv-thumbnails-button" data-url="/thumbnails"></button><button id="tv-import-preview"></button><button id="tv-import-apply" hidden></button><textarea id="tv-import-html"></textarea><div id="tv-import-result"></div><div id="movie-import-notification"><span id="movie-import-notification-text"></span></div>', {url: 'http://localhost/admin', runScripts: 'outside-only', virtualConsole: new VirtualConsole()});
    try {
        let requests = 0;
        dom.window.fetch = async url => {
            assert.equal(url, '/thumbnails'); requests++;
            return {ok: true, json: async () => requests === 1 ? {generated: 3, remaining: 2} : {generated: 2, remaining: 0}};
        };
        dom.window.eval(fs.readFileSync('public/js/admin-tv-import.js', 'utf8'));
        const button = dom.window.document.getElementById('generate-tv-thumbnails-button');
        button.click(); button.click();
        await new Promise(resolve => setImmediate(resolve));
        assert.equal(requests, 2, 'processes batches without duplicate clicks');
        assert.match(dom.window.document.getElementById('movie-import-notification-text').textContent, /Successfully generated 5/);
        assert.equal(button.disabled, false);
        process.stdout.write('TV thumbnail button checks passed.\n');
    } finally {dom.window.close();}
})().catch(error => {console.error(error); process.exitCode = 1;});
