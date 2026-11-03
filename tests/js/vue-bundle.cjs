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

(async () => {
    for (const role of ['admin', 'user', 'guest']) await check(role);
    const manifest = JSON.parse(fs.readFileSync('public/mix-manifest.json', 'utf8'));
    assert.match(manifest['/js/app.js'], /\?id=[a-f0-9]+$/, 'The tested bundle has a cache-busting URL');
    process.stdout.write('Compiled Vue bundle mounts and renders all ten streaming badges for admin, member and guest views.\n');
})().catch(error => { console.error(error); process.exitCode = 1; });
