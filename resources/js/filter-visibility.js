const key = 'catalogue-filters-shown';

function get() {
    try { return window.sessionStorage.getItem(key) === 'true'; }
    catch (error) { return false; }
}

function set(shown) {
    try { window.sessionStorage.setItem(key, String(shown)); }
    catch (error) { /* The controls still work when browser storage is unavailable. */ }
}

function init() {
    const panel = document.getElementById('filtersContainer');
    if (!panel) return;
    const show = document.getElementById('showFiltersButton');
    const hide = document.getElementById('hideFiltersButton');
    const display = shown => {
        panel.style.display = shown ? '' : 'none';
        show.style.display = shown ? 'none' : '';
        hide.style.display = shown ? '' : 'none';
        [show, hide].forEach(row => row.querySelector('button').setAttribute('aria-expanded', String(shown)));
    };
    window.showFilters = () => { set(true); display(true); };
    window.hideFilters = () => { set(false); display(false); };
    display(get());
}

module.exports = {get, set, init};
