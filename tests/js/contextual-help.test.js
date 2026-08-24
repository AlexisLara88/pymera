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
    assert.match(component, /class="context-help"[\s\S]*data-context-help/);
    assert.match(component, /<summary[\s\S]*aria-controls=/);
    assert.match(component, /aria-expanded="false"/);
    assert.match(component, /aria-haspopup="dialog"/);
    assert.match(component, /role="dialog"/);
    assert.match(component, /aria-labelledby=/);
    assert.match(component, /data-context-help-close/);
    assert.match(component, /data-context-help-drag-handle/);
    assert.match(component, /data-context-help-target/);
    assert.match(component, /esc\(\$title/);
    assert.match(component, /esc\(\$paragraph\)/);
    assert.match(component, /esc\(\$item\)/);
    assert.match(component, /esc\(\$example\)/);
    assert.doesNotMatch(component, /\{!{2}|innerHTML/);
});

test('Mi negocio owns five concise contextual explanations', () => {
    const instances = profile.match(/\$contextualHelp\(\[/g) || [];
    const focusedForms = profile.match(/<section class="form-card"[^>]*data-context-help-focus-target/g) || [];

    assert.equal(instances.length, 5);
    assert.match(profile, /\['saveData' => false\]/);
    assert.match(component, /\$contextualHelp\['targetId'\] \?\? null/);
    assert.equal(focusedForms.length, 3);
    assert.match(profile, /business-help-purpose/);
    assert.match(profile, /'targetId' => 'businessDiagnosisPanel'/);
    assert.match(profile, /id="businessDiagnosisPanel" data-context-help-focus-target/);
    assert.match(profile, /business-help-completion/);
    assert.match(profile, /business-help-general-data/);
    assert.match(profile, /business-help-minimum-profile/);
    assert.match(profile, /business-help-diagnosis/);
    assert.doesNotMatch(profile, /business-help-differentiation/);
    assert.match(profile, /data-context-help-focus-target/);
    assert.match(profile, /'contextual-help\.css'/);
    assert.match(profile, /assets\/js\/contextual-help\.js/);
    assert.ok(
        profile.indexOf('assets/js/business/profile.js') < profile.indexOf('assets/js/contextual-help.js'),
        'Vue must mount before contextual help binds to the final editor nodes',
    );
});

test('the interaction builds a focused backdrop without affecting business state', () => {
    assert.match(script, /openContextualHelp/);
    assert.match(script, /openContextualHelp !== help/);
    assert.match(script, /closeHelp\(openContextualHelp/);
    assert.match(script, /document\.createElement\('div'\)/);
    assert.match(script, /context-help-backdrop/);
    assert.match(script, /context-help-is-open/);
    assert.match(script, /is-context-help-focus/);
    assert.match(script, /closest\('\[data-context-help-focus-target\]'\)/);
    assert.match(script, /moduleMain\?\.getBoundingClientRect/);
    assert.match(script, /triggerRect\.right \+ viewportGap/);
    assert.match(script, /triggerRect\.left - cardRect\.width - viewportGap/);
    assert.match(script, /triggerRect\.bottom \+ viewportGap/);
    assert.match(script, /triggerRect\.top - cardRect\.height - viewportGap/);
    assert.match(script, /candidates\.find\(candidateFits\) \?\? candidates\[0\]/);
    assert.doesNotMatch(script, /rectangleContainsPoint|rectanglesOverlap|contextHelpPlacement|focusRect/);
    assert.match(script, /help\.dataset\.contextHelpTarget/);
    assert.match(script, /document\.getElementById\(explicitTargetId\)/);
    assert.match(script, /is-positioned/);
    assert.match(script, /startHelpDrag/);
    assert.match(script, /manuallyPositionedHelp/);
    assert.match(script, /setPointerCapture/);
    assert.match(script, /releasePointerCapture/);
    assert.match(script, /addEventListener\('pointermove'/);
    assert.match(script, /addEventListener\('pointerup'/);
    assert.match(script, /addEventListener\('pointercancel'/);
    assert.match(script, /positionBounds/);
    assert.match(script, /constrainHelpPosition/);
    assert.match(script, /event\.key === 'Escape'/);
    assert.match(script, /addEventListener\('pointerdown'/);
    assert.match(script, /addEventListener\('resize'/);
    assert.match(script, /addEventListener\('scroll'/);
    assert.match(script, /requestAnimationFrame/);
    assert.match(script, /aria-expanded/);
    assert.match(script, /restoreFocus/);
    assert.doesNotMatch(script, /window\.Vue|fetch\(|XMLHttpRequest|localStorage|sessionStorage|innerHTML/);
});

test('the visual component spotlights its target without altering the sidebar', () => {
    assert.match(styles, /\.context-help-card\s*\{[\s\S]*?position:\s*absolute/);
    assert.match(styles, /\.context-help\.is-enhanced \.context-help-card\s*\{[\s\S]*?position:\s*fixed/);
    assert.match(styles, /\.context-help-backdrop\s*\{[\s\S]*?backdrop-filter:\s*blur\(9px\)/);
    assert.match(styles, /body\.context-help-is-open \.module-sidebar\s*\{[\s\S]*?z-index:\s*140/);
    assert.match(styles, /\.is-context-help-focus\s*\{[\s\S]*?z-index:\s*90/);
    assert.match(styles, /\.form-card\.is-context-help-focus/);
    assert.match(styles, /cursor:\s*grab/);
    assert.match(styles, /cursor:\s*grabbing/);
    assert.match(styles, /touch-action:\s*none/);
    assert.match(styles, /\.context-help\.is-dragging \.context-help-card/);
    assert.match(styles, /\.context-help\.is-enhanced \.context-help-card\s*\{[\s\S]*?visibility:\s*hidden/);
    assert.match(styles, /\.context-help\.is-enhanced\.is-positioned \.context-help-card/);
    assert.match(styles, /max-height:\s*min\(24rem, calc\(100vh - 2rem\)\)/);
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
