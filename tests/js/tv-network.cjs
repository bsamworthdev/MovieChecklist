const assert = require('node:assert/strict');
const fs = require('node:fs');
const {JSDOM, VirtualConsole} = require('jsdom');
const row = id => ({id, name: `Show ${id}`, year: 2000, imdb_id: `tt${1000000 + id}`, top250_rank: id, watched: 0, favourite: 0, watchlist: 0});
const page = (items, total = items.length, current = 1) => ({data: {data: items, current_page: current, last_page: Math.ceil(total / 100) || 1, total}});
const tick = () => new Promise(resolve => setImmediate(resolve));

(async () => {
    const errors = [];
    const output = new VirtualConsole();
    output.on('jsdomError', error => errors.push(error));
    output.on('error', error => errors.push(error));
    const dom = new JSDOM('<meta name="csrf-token" content="test"><div id="app"></div>', {url: 'http://localhost/tv-shows', runScripts: 'outside-only', virtualConsole: output});
    try {
        dom.window.eval(fs.readFileSync('public/js/app.js', 'utf8'));
        const pending = [];
        dom.window.axios.get = (url, options) => new Promise((resolve, reject) => pending.push({url, params: options.params, resolve, reject}));
        const Component = dom.window.Vue.options.components['tv-series-page'];
        const vm = new Component().$mount();
        dom.window.document.body.appendChild(vm.$el);
        pending.shift().resolve(page([row(1)]));
        await tick();

        vm.search = 'old'; const oldSearch = vm.load(1); const oldRequest = pending.shift();
        vm.search = 'new'; const newSearch = vm.load(1); const newRequest = pending.shift();
        assert.equal(newRequest.params.search, 'new');
        newRequest.resolve(page([row(2)])); await newSearch;
        oldRequest.resolve(page([row(3)])); await oldSearch;
        assert.equal(vm.items[0].id, 2, 'late response must not replace newer search results');
        assert.equal(vm.loading, false);

        const firstPage = Array.from({length: 100}, (_, index) => row(index + 1));
        vm.search = ''; const reset = vm.load(1);
        pending.shift().resolve(page(firstPage, 205)); await reset;
        const more = vm.showMore();
        vm.showMore();
        assert.equal(pending.length, 1, 'repeated Show more clicks send one request');
        assert.equal(pending[0].params.page, 2);
        pending.shift().reject(new Error('network unavailable')); await more;
        assert.equal(vm.items.length, 100, 'failed Show more preserves displayed cards');
        assert.equal(vm.loading, false);
        assert.match(vm.error, /Could not load/);
        const retry = vm.showMore();
        assert.equal(pending[0].params.page, 2, 'retry does not skip failed batch');
        pending.shift().resolve(page(Array.from({length: 100}, (_, index) => row(index + 101)), 205, 2)); await retry;
        assert.equal(vm.items.length, 200);
        assert.equal(new Set(vm.items.map(show => show.id)).size, 200);

        dom.window.axios.post = async () => {throw new Error('write failed');};
        await vm.save(vm.items[0], 'watched');
        assert.equal(vm.items[0].watched, 0, 'failed save never claims success');
        assert.equal(vm.saving[vm.items[0].id], undefined, 'failed save releases button guard');
        assert.match(vm.error, /Could not save/);

        vm.english = true; vm.selectedServices = ['netflix', 'prime'];
        const random = vm.pickShow();
        assert.equal(pending[0].params.random, 1);
        assert.equal(pending[0].params.english, 1);
        assert.equal(pending[0].params.services.join(','), 'netflix,prime');
        pending.shift().reject(new Error('random request failed')); await random; await tick();
        assert.match(vm.$el.querySelector('[aria-labelledby="tv-random-title"]').textContent, /Could not pick a TV show/);
        assert.equal(vm.randomLoading, false);
        const tryAgain = vm.pickShow();
        pending.shift().resolve({data: {show: row(101)}}); await tryAgain; await tick();
        assert.equal(vm.randomShow.id, 101);
        assert.equal(vm.randomError, '');
        vm.closeRandom();
        assert.equal(dom.window.document.body.style.overflow, '');
        assert.deepEqual(errors, []);
        vm.$destroy();
        process.stdout.write('TV stale-response, failed-save, retry and Show more network checks passed.\n');
    } finally {dom.window.close();}
})().catch(error => {console.error(error); process.exitCode = 1;});
