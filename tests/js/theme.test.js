'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const projectRoot = path.resolve(__dirname, '../..');
const script = fs.readFileSync(
    path.join(projectRoot, 'public/assets/js/theme.js'),
    'utf8',
);
const styles = fs.readFileSync(
    path.join(projectRoot, 'public/assets/css/theme.css'),
    'utf8',
);
const head = fs.readFileSync(
    path.join(projectRoot, 'app/Views/layouts/theme_head.php'),
    'utf8',
);

const makeBrowser = ({ systemDark = false, stored = null } = {}) => {
    const values = new Map();
    const listeners = {};

    if (stored !== null) {
        values.set('pyme_erp_lite_theme', stored);
    }

    const root = { dataset: {}, style: {} };
    const media = {
        matches: systemDark,
    };
    const document = {
        documentElement: root,
    };
    const window = {
        addEventListener(name, callback) {
            listeners[name] = callback;
        },
        localStorage: {
            getItem(key) {
                return values.has(key) ? values.get(key) : null;
            },
            setItem(key, value) {
                values.set(key, value);
            },
        },
        matchMedia() {
            return media;
        },
    };

    vm.runInNewContext(script, { document, Set, window });

    return { listeners, media, root, values, window };
};

test('explicit selection wins over the operating-system preference and persists', () => {
    const browser = makeBrowser({ systemDark: true, stored: 'light' });

    assert.equal(browser.root.dataset.themePreference, 'light');
    assert.equal(browser.root.dataset.theme, 'light');
    assert.equal(browser.root.style.colorScheme, 'only light');
    browser.media.matches = false;
    assert.equal(browser.root.dataset.theme, 'light');

    browser.window.PymeTheme.saveTheme('dark');
    assert.equal(browser.values.get('pyme_erp_lite_theme'), 'dark');
    assert.equal(browser.root.dataset.theme, 'dark');
    assert.equal(browser.root.style.colorScheme, 'only dark');
});

test('the system chooses only the first theme and preferences can replace it', () => {
    const browser = makeBrowser({ systemDark: true });

    assert.equal(browser.root.dataset.themePreference, 'dark');
    assert.equal(browser.root.dataset.theme, 'dark');
    assert.equal(browser.values.get('pyme_erp_lite_theme'), 'dark');

    browser.media.matches = false;
    assert.equal(browser.root.dataset.theme, 'dark');

    browser.window.PymeTheme.saveTheme('light');
    assert.equal(browser.root.dataset.theme, 'light');
    assert.equal(browser.values.get('pyme_erp_lite_theme'), 'light');
});

test('a legacy system value is converted into a concrete saved theme', () => {
    const browser = makeBrowser({ systemDark: false, stored: 'system' });

    assert.equal(browser.root.dataset.theme, 'light');
    assert.equal(browser.root.dataset.themePreference, 'light');
    assert.equal(browser.values.get('pyme_erp_lite_theme'), 'light');
});

test('theme controller remains an account appearance concern', () => {
    assert.match(head, /pyme_erp_lite_theme/);
    assert.match(head, /document\.documentElement/);
    assert.match(head, /prefers-color-scheme:\s*dark/);
    assert.match(styles, /html\[data-theme="light"\]/);
    assert.match(styles, /html\[data-theme="dark"\]/);
    assert.match(styles, /color-scheme:\s*only light/);
    assert.doesNotMatch(script, /allowedPreferences[^\n]*system/);
    assert.doesNotMatch(script, /addEventListener\?\.\('change'/);
    assert.doesNotMatch(styles, /forced-color-adjust:\s*none/);
    assert.doesNotMatch(script, /theme-preference-endpoint|data-theme-toggle|fetch\(/);
    assert.doesNotMatch(styles, /theme-switcher|theme-toggle-icon|auth-theme-control/);
    assert.doesNotMatch(script, /XMLHttpRequest|window\.Vue|business_id|user_id/);

    const openBraces = (styles.match(/{/g) || []).length;
    const closeBraces = (styles.match(/}/g) || []).length;
    assert.equal(openBraces, closeBraces);
});
