const assert = require('node:assert/strict');
const fs = require('node:fs');
const {JSDOM, VirtualConsole} = require('jsdom');

(async () => {
    const dom = new JSDOM('<meta name="csrf-token" content="test"><div id="app"></div>', {url: 'http://localhost/tv-shows', runScripts: 'outside-only', virtualConsole: new VirtualConsole()});
    try {
        dom.window.eval(fs.readFileSync('public/js/app.js', 'utf8'));
        let otherScrollCalls = 0;
        dom.window.onscroll = () => otherScrollCalls++;
        const Component = dom.window.Vue.options.components['back-to-top'];
        const vm = new Component().$mount();
        dom.window.document.body.appendChild(vm.$el);
        assert.equal(vm.$el.style.display, 'none');
        dom.window.document.documentElement.scrollTop = 200;
        dom.window.dispatchEvent(new dom.window.Event('scroll'));
        await dom.window.Vue.nextTick();
        assert.equal(vm.$el.style.display, 'none', 'same 200px threshold as movies');
        dom.window.document.documentElement.scrollTop = 201;
        dom.window.dispatchEvent(new dom.window.Event('scroll'));
        await dom.window.Vue.nextTick();
        assert.notEqual(vm.$el.style.display, 'none');
        const style = dom.window.getComputedStyle(vm.$el);
        assert.equal(style.position, 'fixed');
        assert.equal(style.bottom, '20px');
        assert.equal(style.right, '30px');
        assert.equal(vm.$el.textContent, 'Back To Top');
        dom.window.document.body.scrollTop = 250;
        vm.$el.click();
        await dom.window.Vue.nextTick();
        assert.equal(dom.window.document.body.scrollTop, 0);
        assert.equal(dom.window.document.documentElement.scrollTop, 0);
        assert.equal(vm.$el.style.display, 'none');
        vm.$destroy();
        dom.window.document.documentElement.scrollTop = 400;
        dom.window.dispatchEvent(new dom.window.Event('scroll'));
        assert.equal(vm.shown, false, 'destroyed component removes its scroll listener');
        assert.equal(otherScrollCalls, 3, 'other scroll handlers remain intact');
        const restored = new Component().$mount();
        await dom.window.Vue.nextTick();
        assert.notEqual(restored.$el.style.display, 'none', 'initialises correctly when restoring a scrolled page');
        restored.$destroy();
        process.stdout.write('Shared movie/TV Back To Top checks passed.\n');
    } finally {dom.window.close();}
})().catch(error => {console.error(error); process.exitCode = 1;});
