const assert = require('node:assert/strict');
const fs = require('node:fs');
const {JSDOM, VirtualConsole} = require('jsdom');

(async () => {
    for (const media of ['movie', 'tv']) {
        const dom = new JSDOM(`<meta name="csrf-token" content="test"><section id="uk-catalogues" data-media="${media}" data-start-url="/start" data-step-url="/step"><button data-catalogue-update="netflix" data-label="Netflix" data-configured="true"><span data-catalogue-label>Update Netflix UK</span></button></section><div id="movie-import-notification"><span id="movie-import-notification-text"></span></div><div id="catalogue-progress"></div><div id="catalogue-progress-text"></div><div id="catalogue-progress-bar"></div><details id="catalogue-skipped"><summary id="catalogue-skipped-title"></summary><ul id="catalogue-skipped-list"></ul></details>`, {url: 'https://example.test/admin', runScripts: 'outside-only', virtualConsole: new VirtualConsole()});
        const requests = [];
        try {
            dom.window.fetch = async (url, options) => {
                requests.push({url, body: JSON.parse(options.body)});
                return {ok: true, json: async () => ({token: 'run', service: 'netflix', total: 1, processed: 1, complete: true, result: {verified: 1, available: 1, added: 1, removed: 0, skipped: 0, duplicates_removed: 0}, skipped: []})};
            };
            dom.window.eval(fs.readFileSync('public/js/admin-catalogues.js', 'utf8'));
            dom.window.document.querySelector('button').click();
            await new Promise(resolve => setImmediate(resolve));
            assert.equal(requests[0].body.media, media);
            assert.equal(requests[0].body.service, 'netflix');
            assert.match(dom.window.document.getElementById('movie-import-notification-text').textContent, media === 'tv' ? /TV shows checked/ : /movies checked/);
            assert.equal(dom.window.document.querySelector('button').disabled, false);
        } finally {dom.window.close();}
    }
    process.stdout.write('Admin catalogue context checks passed.\n');
})().catch(error => {console.error(error); process.exitCode = 1;});
