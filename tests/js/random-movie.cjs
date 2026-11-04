const assert = require('node:assert/strict');
const fs = require('node:fs');
const { JSDOM, VirtualConsole } = require('jsdom');

const bundle = fs.readFileSync('public/js/app.js', 'utf8');
const errors = [];
const virtualConsole = new VirtualConsole();
virtualConsole.on('jsdomError', error => errors.push(error));
virtualConsole.on('error', error => errors.push(error));
const dom = new JSDOM('<!doctype html><html><head><meta name="csrf-token" content="test"></head><body><div id="app"></div></body></html>', {
    url: 'http://localhost/home', runScripts: 'outside-only', virtualConsole,
});

function sourceMethod(component, method) {
    const source = fs.readFileSync(`resources/js/components/${component}.vue`, 'utf8');
    const body = source.match(new RegExp(`            ${method}\\(\\) \\{([\\s\\S]*?)\\n            \\},`))[1];
    return `function() {${body}\n}`;
}

const options = component => component.options || component;

try {
    dom.window.alert = () => assert.fail('Empty results should use the styled modal');
    dom.window.eval(bundle);
    const Vue = dom.window.Vue;
    assert.equal(typeof Vue.component, 'function');

    for (const [grid, card, collection, guest] of [
        ['ScratchCardGrid', 'ScratchCard', 'all_movies', false],
        ['ScratchCardGridNonAuth', 'ScratchCardNonAuth', 'movies', true],
    ]) {
        const gridOptions = options(Vue.options.components[guest ? 'scratch-card-grid-non-auth' : 'scratch-card-grid']);
        const cardOptions = options(guest ? gridOptions.components.scratchCardNonAuth : Vue.options.components['scratch-card']);
        for (const target of ['source', 'bundle']) {
            const pick = target === 'source'
                ? new Function('window', `return (${sourceMethod(grid, 'pickMovie')});`)(dom.window)
                : gridOptions.methods.pickMovie;
            const update = target === 'source'
                ? new Function(`return (${sourceMethod(card, 'movieStatusChanged')});`)()
                : cardOptions.methods.movieStatusChanged;
            const initial = Array.from({ length: 100 }, (_, id) => ({ id, watched: 1 }));
            const extra = { id: 101, name: 'Loaded by Show more movies', watched: '0' };
            const context = { movies: initial, all_movies: initial.concat(extra), randomMovieComponentKey: 0 };
            if (collection === 'movies') context.movies = initial.concat(extra);
            pick.call(context);
            assert.equal(context.randomMovie, extra, `${target}: includes movies after the first 100`);
            assert.equal(context.activeModal, 1);
            if (!guest) assert.equal(context.randomMovieComponentKey, 1);

            let emitted;
            const cardContext = {
                movie: extra, hasWatched: true,
                $set(object, key, value) { object[key] = value; },
                $emit(event, value) { emitted = [event, value]; },
            };
            update.call(cardContext);
            assert.deepEqual(emitted, ['movieStatusChanged', true]);
            pick.call(context);
            assert.equal(context.randomMovie, null, `${target}: newly watched movie is excluded`);
            assert.equal(context.activeModal, 1);
            cardContext.hasWatched = false;
            update.call(cardContext);
            pick.call(context);
            assert.equal(context.randomMovie, extra, `${target}: unchecking makes the movie eligible again`);
            context[collection] = [];
            pick.call(context);
            assert.equal(context.randomMovie, null);
            assert.equal(context.activeModal, 1);
            process.stdout.write(`PASS ${grid} (${target})\n`);
        }

        const Modal = Vue.extend(options(gridOptions.components.randomMovieModal));
        for (const movie of [null, { name: 'Test movie', year: 2026, on_watch_list: 0 }]) {
            const instance = new Modal({ propsData: { movie, auth: guest ? 0 : 1 } }).$mount();
            try {
                assert.ok(instance.$el.classList.contains('modal'));
                assert.ok(instance.$el.textContent.includes('Close'));
                if (movie) {
                    assert.ok(instance.$el.textContent.includes(movie.name));
                    assert.ok(instance.$el.querySelector('#chosenMovieImage'));
                } else {
                    assert.ok(instance.$el.textContent.includes('No unwatched movies'));
                    assert.ok(instance.$el.textContent.includes('There are no unwatched movies among the movies currently shown.'));
                    assert.equal(instance.$el.querySelector('#chosenMovieImage'), null);
                }
            } finally {
                instance.$destroy();
            }
        }
        process.stdout.write(`PASS ${grid} movie and empty-state modal rendering\n`);
    }
    assert.deepEqual(errors, [], 'The shipped bundle renders without browser errors');
} finally {
    dom.window.close();
}
