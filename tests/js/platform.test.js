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

test('the same owner dialog selects an active business or reveals fields for a new one', () => {
    assert.match(view, /Nueva cuenta/);
    assert.doesNotMatch(view, /Nueva cuenta propietaria/);
    assert.match(view, /name="business_id"/);
    assert.match(view, /data-owner-business-select/);
    assert.match(view, /Crear un negocio nuevo/);
    assert.match(view, /data-owner-new-business/);
    assert.match(view, /data-owner-new-business-field/);
    assert.match(view, />Crear cuenta</);
    assert.match(script, /initializeOwnerBusinessSelection\(\)/);
    assert.match(script, /select\.value === 'new'/);
    assert.match(script, /fieldsContainer\.hidden = ! createsBusiness/);
    assert.match(script, /field\.disabled = ! createsBusiness/);
    assert.match(styles, /\.platform-new-business\s*\{/);
    assert.match(styles, /\.platform-new-business\[hidden\]/);
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

test('account and membership status changes require an explicit contextual confirmation', () => {
    assert.match(view, /data-platform-confirm-dialog/);
    assert.match(view, /data-platform-confirm-title/);
    assert.match(view, /data-platform-confirm-description/);
    assert.match(view, /data-platform-status-scope="membership"/);
    assert.match(view, /data-platform-status-scope="account"/);
    assert.match(view, /data-platform-status-trigger/);
    assert.match(view, /aria-controls="platformStatusConfirmationDialog"/);
    assert.match(view, /aria-haspopup="dialog"/);
    assert.match(script, /initializeStatusConfirmation\(\)/);
    assert.match(script, /¿Desactivar la cuenta/);
    assert.match(script, /¿Pausar el acceso/);
    assert.match(script, /primaryCancelButton\?\.focus/);
    assert.match(script, /pendingForm\.requestSubmit\(\)/);
    assert.match(styles, /\.platform-confirm-danger/);
});

test('account directory can be filtered locally by user, email or associated business', () => {
    assert.match(view, /data-platform-account-search/);
    assert.match(view, /Buscar por usuario, correo o negocio/);
    assert.match(view, /data-platform-account-search-value/);
    assert.match(view, /data-platform-account-count/);
    assert.match(view, /data-platform-account-empty/);
    assert.match(view, /aria-controls="platformAccountList"/);
    assert.match(script, /initializeAccountSearch\(\)/);
    assert.match(script, /normalize\('NFD'\)/);
    assert.match(script, /haystack\.includes\(query\)/);
    assert.match(script, /account\.hidden = ! matches/);
    assert.doesNotMatch(script, /fetch\(|XMLHttpRequest|window\.Vue/);
    assert.match(styles, /\.platform-account-search\s*\{/);
});
