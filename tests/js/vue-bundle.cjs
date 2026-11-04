const assert = require('node:assert/strict');
const fs = require('node:fs');
const { JSDOM, VirtualConsole } = require('jsdom');

const bundle = fs.readFileSync('public/js/app.js', 'utf8');
const attribute = value => JSON.stringify(value).replace(/&/g, '&amp;').replace(/"/g, '&quot;');
const services = ['netflix', 'amazon', 'nowtv', 'disney_plus', 'hbo_max', 'apple_tv', 'paramount_plus', 'bbc_iplayer', 'itvx', 'channel4'];
const movie = available => ({
    id: available ? 1 : 2, name: available ? 'Available film' : 'Unavailable film', year: 2000,
    rating: 8, index: 1, top250_rank: 1, watched: 0, favourite: 0, on_watch_list: 0,
    ...Object.fromEntries(services.map(service => ['on_'+service, available ? '1' : '0'])),
    image_url_small_extended: '/test-poster.jpg', imdb_id: 'tt0111161',
});

async function check(role) {
    const movies = [movie(true), movie(false)];
    const user = { id: 1, role, friendsCount: 0 };
    const cards = role === 'guest'
        ? `<scratch-card-grid-non-auth :movies="${attribute(movies)}" :watch_list="[]"></scratch-card-grid-non-auth>`
        : movies.map(item => `<scratch-card :movie="${attribute(item)}" :user="${attribute(user)}"></scratch-card>`).join('');
    const errors = [];
    const console = new VirtualConsole();
    console.on('jsdomError', error => errors.push(error));
    console.on('error', error => errors.push(error));
    const dom = new JSDOM(`<!doctype html><html><head><meta name="csrf-token" content="test"></head><body><div id="app">${cards}</div></body></html>`, {
        url: 'http://localhost/home', runScripts: 'outside-only', pretendToBeVisual: true, virtualConsole: console,
    });
    try {
        dom.window.eval(bundle);
        assert.equal(typeof dom.window.Vue, 'function', `${role}: Vue is the constructor, not an ES module namespace`);
        assert.equal(typeof dom.window.Vue.component, 'function', `${role}: component registration is available`);
        await new Promise(resolve => dom.window.Vue.nextTick(resolve));
        assert.deepEqual(errors, [], `${role}: compiled bundle starts without browser errors`);
        assert.ok(dom.window.document.querySelector('#app').__vue__, `${role}: application mounts`);
        const rendered = dom.window.document.querySelectorAll('.movieCard');
        assert.equal(rendered.length, 2, `${role}: both movie cards render`);
        for (const service of services) {
            const available = rendered[0].querySelector('.platform.'+service);
            assert.ok(available, `${role}: ${service} icon renders for an available film`);
            assert.equal(available.classList.contains('dimmed'), false);
            const unavailable = rendered[1].querySelector('.platform.'+service);
            if (role === 'admin') {
                assert.ok(unavailable, `Admins can see the unavailable ${service} control`);
                assert.ok(unavailable.classList.contains('dimmed'));
            } else {
                assert.equal(unavailable, null, `${role}: unavailable ${service} is hidden`);
            }
        }
    } finally {
        dom.window.close();
    }
}

function checkStreamingSelection(initialCount) {
    let submissions = 0;
    const options = services.map((service, index) => `<input type="checkbox" id="${service}_only_checkbox" ${index < initialCount ? 'checked' : ''}>`).join('');
    const dom = new JSDOM(`<!doctype html><html><head><meta name="csrf-token" content="test"></head><body><div id="app"><input type="checkbox" id="english_only_checkbox" checked><div id="streamCard"><div>${options}</div><div><button type="button" id="streaming-select-all">Select all</button> | <button type="button" id="streaming-unselect-all">Unselect all</button></div></div></div></body></html>`, {
        url: 'http://localhost/home', runScripts: 'outside-only', virtualConsole: new VirtualConsole(),
    });
    try {
        dom.window.changeSelection = () => { submissions++; };
        dom.window.eval(bundle);
        const select = dom.window.document.getElementById('streaming-select-all');
        const unselect = dom.window.document.getElementById('streaming-unselect-all');
        const checkboxes = services.map(service => dom.window.document.getElementById(service+'_only_checkbox'));
        assert.equal(dom.window.document.querySelectorAll('#streamCard input[type="checkbox"]').length, services.length, 'There is no bulk-selection checkbox');
        assert.equal(submissions, 0, 'Initial state does not submit the filter form');
        select.click();
        assert.ok(checkboxes.every(checkbox => checkbox.checked));
        assert.equal(submissions, 1, 'Select all submits exactly once');
        unselect.click();
        assert.ok(checkboxes.every(checkbox => !checkbox.checked));
        assert.equal(submissions, 2, 'Unselect all submits exactly once');
        assert.equal(dom.window.document.getElementById('english_only_checkbox').checked, true, 'Other filters are preserved');
    } finally {
        dom.window.close();
    }
}

async function checkRepeatedWatchedClicks() {
    const dom = new JSDOM(`<!doctype html><html><head><meta name="csrf-token" content="test"></head><body><div id="app"><scratch-card :movie="${attribute(movie(true))}" :user="${attribute({ id: 1, role: 'user', friendsCount: 0 })}"></scratch-card></div></body></html>`, {
        url: 'http://localhost/home', runScripts: 'outside-only', virtualConsole: new VirtualConsole(),
    });
    try {
        dom.window.eval(bundle);
        const calls = [];
        const pending = [];
        dom.window.axios.post = (url, payload) => {
            calls.push({ url, payload });
            return new Promise((resolve, reject) => pending.push({ resolve, reject }));
        };
        const card = dom.window.document.querySelector('.movieCard');
        const component = card.__vue__;
        card.click();
        card.click();
        card.click();
        assert.equal(calls.length, 1, 'Repeated clicks while saving send only one watched request');
        assert.equal(calls[0].payload.watched, true);
        pending[0].resolve({ data: { watched: true } });
        await new Promise(resolve => setTimeout(resolve, 0));
        assert.equal(component.hasWatched, true);
        assert.equal(component.savingWatched, false);
        card.click();
        assert.equal(calls.length, 2, 'A later intentional click can unwatch the film');
        assert.equal(calls[1].payload.watched, false);
        pending[1].resolve({ data: { watched: false } });
        await new Promise(resolve => setTimeout(resolve, 0));
        assert.equal(component.hasWatched, false);
        card.click();
        pending[2].reject(new Error('Network failure'));
        await new Promise(resolve => setTimeout(resolve, 0));
        assert.equal(component.hasWatched, false, 'A failed save leaves the watched state unchanged');
        assert.equal(component.savingWatched, false, 'A failed save permits retrying');
    } finally {
        dom.window.close();
    }
}

(async () => {
    for (const role of ['admin', 'user', 'guest']) await check(role);
    for (const count of [0, 2, services.length]) checkStreamingSelection(count);
    await checkRepeatedWatchedClicks();
    const manifest = JSON.parse(fs.readFileSync('public/mix-manifest.json', 'utf8'));
    assert.match(manifest['/js/app.js'], /\?id=[a-f0-9]+$/, 'The tested bundle has a cache-busting URL');
    process.stdout.write('Compiled Vue bundle mounts and renders all ten badges and supports bulk filter selection and prevents duplicate watched clicks.\n');
})().catch(error => { console.error(error); process.exitCode = 1; });
