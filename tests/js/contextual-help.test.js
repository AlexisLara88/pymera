'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');

const projectRoot = path.resolve(__dirname, '../..');
const component = fs.readFileSync(
    path.join(projectRoot, 'app/Views/components/contextual_help.php'),
    'utf8',
);
const profile = fs.readFileSync(
    path.join(projectRoot, 'app/Views/business/profile.php'),
    'utf8',
);
const script = fs.readFileSync(
    path.join(projectRoot, 'public/assets/js/contextual-help.js'),
    'utf8',
);
const styles = fs.readFileSync(
    path.join(projectRoot, 'public/assets/css/contextual-help.css'),
    'utf8',
);

test('contextual help is server-rendered, escaped and progressively enhanced', () => {
    assert.match(component, /<details class="context-help" data-context-help>/);
    assert.match(component, /<summary[\s\S]*aria-controls=/);
    assert.match(component, /aria-expanded="false"/);
    assert.match(component, /role="region"/);
    assert.match(component, /aria-labelledby=/);
    assert.match(component, /data-context-help-close/);
    assert.match(component, /esc\(\$title/);
    assert.match(component, /esc\(\$paragraph\)/);
    assert.match(component, /esc\(\$item\)/);
    assert.match(component, /esc\(\$example\)/);
    assert.doesNotMatch(component, /\{!{2}|innerHTML/);
});

test('Mi negocio owns five independent contextual explanations', () => {
    const instances = profile.match(/view\('components\/contextual_help'/g) || [];

    assert.equal(instances.length, 5);
    assert.match(profile, /business-help-purpose/);
    assert.match(profile, /business-help-completion/);
    assert.match(profile, /business-help-general-data/);
    assert.match(profile, /business-help-diagnosis/);
    assert.match(profile, /business-help-differentiation/);
    assert.match(profile, /'contextual-help\.css'/);
    assert.match(profile, /assets\/js\/contextual-help\.js/);
});

test('the interaction keeps one help open and supports keyboard, outside click and viewport changes', () => {
    assert.match(script, /openContextualHelp/);
    assert.match(script, /openContextualHelp !== help/);
    assert.match(script, /closeHelp\(openContextualHelp\)/);
    assert.match(script, /event\.key === 'Escape'/);
    assert.match(script, /addEventListener\('pointerdown'/);
    assert.match(script, /addEventListener\('resize'/);
    assert.match(script, /addEventListener\('scroll'/);
    assert.match(script, /requestAnimationFrame/);
    assert.match(script, /aria-expanded/);
    assert.match(script, /restoreFocus/);
    assert.doesNotMatch(script, /window\.Vue|fetch\(|XMLHttpRequest|localStorage|sessionStorage|innerHTML/);
});

test('the visual component is anchored, responsive and respects reduced motion', () => {
    assert.match(styles, /\.context-help-card\s*\{[\s\S]*?position:\s*absolute/);
    assert.match(styles, /\.context-help\.is-enhanced \.context-help-card\s*\{[\s\S]*?position:\s*fixed/);
    assert.match(styles, /max-height:\s*min\(28rem, calc\(100vh - 2rem\)\)/);
    assert.match(styles, /overflow:\s*auto/);
    assert.match(styles, /\.context-help-trigger:focus-visible/);
    assert.match(styles, /@media \(max-width:\s*680px\)/);
    assert.match(styles, /@media \(prefers-reduced-motion:\s*reduce\)/);
    assert.match(styles, /var\(--paper\)/);
    assert.match(styles, /var\(--line\)/);

    const openBraces = (styles.match(/{/g) || []).length;
    const closeBraces = (styles.match(/}/g) || []).length;
    assert.equal(openBraces, closeBraces);
});
