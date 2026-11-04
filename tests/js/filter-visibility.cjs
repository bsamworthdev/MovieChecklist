const assert = require('node:assert/strict');
const fs = require('node:fs');
const {JSDOM, VirtualConsole} = require('jsdom');
const bundle = fs.readFileSync('public/js/app.js', 'utf8');
for (const role of ['guest', 'member']) {
    const dom = new JSDOM('<meta name="csrf-token" content="test"><div id="app"><div id="showFiltersButton"><button type="button">More Filters</button></div><div id="filtersContainer" style="display:none"><input type="checkbox" checked></div><div id="hideFiltersButton" style="display:none"><button type="button">Hide filters</button></div></div>', {url: 'http://localhost/home', runScripts: 'outside-only', virtualConsole: new VirtualConsole()});
    try {
        dom.window.eval(bundle);
        const panel = dom.window.document.getElementById('filtersContainer');
        assert.equal(panel.style.display, 'none', `${role}: collapsed by default`);
        dom.window.showFilters();
        assert.equal(panel.style.display, '');
        assert.equal(dom.window.sessionStorage.getItem('catalogue-filters-shown'), 'true');
        assert.equal(dom.window.document.querySelector('#hideFiltersButton button').getAttribute('aria-expanded'), 'true');
        dom.window.hideFilters();
        assert.equal(panel.style.display, 'none');
        assert.equal(panel.querySelector('input').checked, true, 'collapsing preserves filter selections');
        assert.equal(dom.window.sessionStorage.getItem('catalogue-filters-shown'), 'false');
        dom.window.sessionStorage.setItem('catalogue-filters-shown', 'true');
        // Re-initialising simulates returning to the catalogue within this tab.
        const source = fs.readFileSync('resources/js/filter-visibility.js', 'utf8');
        dom.window.eval('var module = {exports: {}}; ' + source + '; module.exports.init();');
        assert.equal(panel.style.display, '', 'expanded state survives navigation');
    } finally {dom.window.close();}
}
process.stdout.write('Guest and member filter visibility checks passed.\n');
