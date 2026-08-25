'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');

const projectRoot = path.resolve(__dirname, '../..');
const view = fs.readFileSync(
    path.join(projectRoot, 'app/Views/platform/index.php'),
    'utf8',
);
const script = fs.readFileSync(
    path.join(projectRoot, 'public/assets/js/platform/index.js'),
    'utf8',
);
const styles = fs.readFileSync(
    path.join(projectRoot, 'public/assets/css/platform/index.css'),
    'utf8',
);
const shellStyles = fs.readFileSync(
    path.join(projectRoot, 'public/assets/css/alpha-shell.css'),
    'utf8',
);

test('platform owner creation is exposed through an accessible native dialog', () => {
    assert.match(view, /<dialog[\s\S]*data-platform-dialog/);
    assert.match(view, /data-platform-dialog-open="ownerCreationDialog"/);
    assert.match(view, /aria-labelledby="ownerCreationTitle"/);
    assert.match(view, /aria-describedby="ownerCreationDescription"/);
    assert.match(view, /data-owner-creation-form/);
    assert.match(view, /action="<\?= esc\(site_url\('admin\/accounts\/owner'\)/);
    assert.match(view, /csrf_field\(\)/);
    assert.match(script, /dialog\.showModal\(\)/);
    assert.match(script, /dialog\.close\(\)/);
    assert.match(script, /event\.target === dialog/);
});

test('new web administrators remain visibly and operationally disabled', () => {
    assert.match(view, /Nuevo administrador/);
    assert.match(view, /data-platform-disabled-feature/);
    assert.match(view, /Funcionalidad deshabilitada/);
    assert.match(view, /data-platform-feature-notice/);
    assert.doesNotMatch(view, /action="<\?= esc\(site_url\('admin\/accounts\/platform-admin'\)/);
    assert.match(script, /initializeDisabledFeatureNotice/);
    assert.match(script, /notice\.hidden = false/);
    assert.match(script, /window\.setTimeout/);
});

test('owner passwords have visibility controls and live matching feedback', () => {
    assert.match(view, /id="ownerPassword"/);
    assert.match(view, /id="ownerPasswordConfirmation"/);
    assert.match(view, /data-password-toggle="ownerPassword"/);
    assert.match(view, /data-password-toggle="ownerPasswordConfirmation"/);
    assert.match(view, /aria-pressed="false"/);
    assert.match(view, /ownerPasswordFeedback/);
    assert.match(view, /ownerPasswordConfirmationFeedback/);
    assert.match(view, /data-owner-creation-form[\s\S]*autocomplete="off"|autocomplete="off"[\s\S]*data-owner-creation-form/);
    assert.doesNotMatch(view, /autocomplete="new-password"/);
    assert.match(view, /data-1p-ignore/);
    assert.match(view, /data-lpignore="true"/);
    assert.match(view, /data-bwignore="true"/);
    assert.match(script, /addEventListener\('input', validatePair\)/);
    assert.match(script, /addEventListener\('blur', validatePair\)/);
    assert.match(script, /setCustomValidity/);
    assert.match(script, /Array\.from\(password\.value\)\.length/);
    assert.match(script, /confirmation\.value !== password\.value/);
    assert.match(script, /target\.type = willShow \? 'text' : 'password'/);
    assert.doesNotMatch(script, /fetch\(|window\.Vue|business_id|user_id/);
});

test('platform modal and disabled state preserve the visual system', () => {
    assert.match(styles, /\.platform-dialog::backdrop/);
    assert.match(styles, /backdrop-filter: blur\(7px\)/);
    assert.match(styles, /\.platform-dialog-close:hover span/);
    assert.match(styles, /rotate\(180deg\)/);
    assert.match(styles, /\.platform-disabled-action/);
    assert.match(styles, /html\[data-theme="dark"\] \.platform-disabled-action/);
    assert.match(styles, /\.platform-password-field input\.is-valid/);
    assert.match(styles, /\.platform-password-field input\.is-invalid/);

    const openBraces = (styles.match(/{/g) || []).length;
    const closeBraces = (styles.match(/}/g) || []).length;
    assert.equal(openBraces, closeBraces);
});

test('primary platform actions preserve readable contrast in the light theme', () => {
    assert.match(view, /class="button button-primary"[\s\S]*Abrir formulario/);
    assert.match(shellStyles, /\.button-primary\s*\{[\s\S]*?color:\s*#fff;[\s\S]*?background:\s*var\(--brand\);/);
    assert.match(shellStyles, /\.button-primary:hover,[\s\S]*?\.button-primary:focus-visible\s*\{[\s\S]*?color:\s*#fff;/);
});
