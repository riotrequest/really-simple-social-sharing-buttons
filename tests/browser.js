// No packages: exercise the actual enhancement script with a small DOM boundary.
'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../social-sharing.js'), 'utf8');

function setup(navigator = {}, secure = true, actions = ['copy', 'native']) {
    const status = { textContent: '' };
    const fallback = { hidden: true, focus() { this.focused = true; }, select() { this.selected = true; } };
    const buttons = actions.map(action => ({
        dataset: { rssbAction: action }, hidden: true, disabled: false,
        classList: { add() {}, remove() {} }, closest: () => group,
    }));
    const group = {
        hidden: true,
        dataset: { url: 'https://example.com/story/?a=1&b=2', title: 'Story', copied: 'Copied', copyHelp: 'Copy manually', shareError: 'Try another button' },
        querySelectorAll: () => buttons,
        querySelector: selector => selector === '.rssb-status' ? status : selector === '.rssb-copy-fallback' ? fallback : buttons.find(button => !button.hidden),
    };
    let handler;
    vm.runInNewContext(source, {
        navigator,
        window: { isSecureContext: secure, setTimeout() {} },
        document: { querySelectorAll: () => [group], addEventListener(event, callback) { handler = callback; } },
    });
    return { group, buttons, status, fallback, click: index => handler({ target: { closest: () => buttons[index] } }) };
}

(async () => {
    let copied;
    const copy = setup({ clipboard: { async writeText(text) { copied = text; } } });
    assert.equal(copy.buttons[0].hidden, false);
    assert.equal(copy.buttons[1].hidden, true);
    await copy.click(0);
    assert.equal(copied, copy.group.dataset.url);
    assert.equal(copy.status.textContent, 'Copied');
    assert.equal(copy.buttons[0].disabled, false);

    for (const navigator of [{}, { clipboard: { async writeText() { throw new Error('Denied'); } } }]) {
        const denied = setup(navigator);
        await denied.click(0);
        assert.equal(denied.fallback.hidden, false);
        assert.equal(denied.fallback.focused, true);
        assert.equal(denied.fallback.selected, true);
        assert.equal(denied.status.textContent, 'Copy manually');
        assert.equal(denied.buttons[0].disabled, false);
    }

    let shared;
    const native = setup({ async share(data) { shared = data; } });
    assert.equal(native.buttons[1].hidden, false);
    await native.click(1);
    assert.equal(shared.url, native.group.dataset.url);
    assert.equal(shared.title, 'Story');
    assert.equal(native.status.textContent, '');
    assert.equal(setup({ share() {} }, false).buttons[1].hidden, true);
    assert.equal(setup({}, true, ['native']).group.hidden, true);

    for (const name of ['AbortError', 'NotAllowedError']) {
        const failure = setup({ async share() { const error = new Error(); error.name = name; throw error; } });
        await failure.click(1);
        assert.equal(failure.status.textContent, name === 'AbortError' ? '' : 'Try another button');
        assert.equal(failure.buttons[1].disabled, false);
    }

    let finish;
    let calls = 0;
    const pending = setup({ clipboard: { writeText() { calls++; return new Promise(resolve => { finish = resolve; }); } } });
    const first = pending.click(0);
    await pending.click(0);
    assert.equal(calls, 1);
    finish();
    await first;
    assert.equal(pending.buttons[0].disabled, false);
    console.log('PASS: Clipboard success, denial/missing API fallback, native availability, cancellation, errors, and double-click protection.');
})().catch(error => { console.error(error); process.exitCode = 1; });
