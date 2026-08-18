'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');

const projectRoot = path.resolve(__dirname, '../..');
const view = fs.readFileSync(
    path.join(projectRoot, 'app/Views/account/preferences.php'),
    'utf8',
);
const script = fs.readFileSync(
    path.join(projectRoot, 'public/assets/js/account/preferences.js'),
    'utf8',
);
const styles = fs.readFileSync(
    path.join(projectRoot, 'public/assets/css/account/preferences.css'),
    'utf8',
);

test('account preferences offer only concrete light and dark choices', () => {
    assert.match(view, /name="appearance_theme"/);
    assert.match(view, /value="light"/);
    assert.match(view, /value="dark"/);
    assert.doesNotMatch(view, /value="system"/);
    assert.match(view, /csrf_field\(\)/);
    assert.match(view, /Estas opciones acompañan a tu cuenta/);
    assert.match(view, /no modifican la configuración del negocio/);
});

test('business accounts can choose one persistent CRM composition', () => {
    assert.match(view, /name="crm_view_mode"/);
    assert.match(view, /value="combined"/);
    assert.match(view, /value="tabs"/);
    assert.match(view, /Vista conjunta/);
    assert.match(view, /Vista por pestañas/);
    assert.match(view, /if \(\$canConfigureCrm\)/);
    assert.doesNotMatch(script, /crm_view_mode|localStorage/);
});

test('the small enhancement previews and commits through the shared theme controller', () => {
    assert.match(script, /PymeTheme\?\.readPreference/);
    assert.match(script, /PymeTheme\?\.applyTheme/);
    assert.match(script, /PymeTheme\?\.saveTheme/);
    assert.doesNotMatch(script, /fetch\(|window\.Vue|business_id|user_id/);
});

test('preference styles support selection, keyboard focus and both visual previews', () => {
    assert.match(styles, /\.appearance-option:has\(input:checked\)/);
    assert.match(styles, /\.appearance-option:has\(input:focus-visible\)/);
    assert.match(styles, /\.appearance-preview\.is-light/);
    assert.match(styles, /\.appearance-preview\.is-dark/);
    assert.match(styles, /\.crm-layout-preview\.is-combined/);
    assert.match(styles, /\.crm-layout-preview\.is-tabs/);

    const openBraces = (styles.match(/{/g) || []).length;
    const closeBraces = (styles.match(/}/g) || []).length;
    assert.equal(openBraces, closeBraces);
});
