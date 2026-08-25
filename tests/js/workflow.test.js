'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');

const projectRoot = path.resolve(__dirname, '../..');
const objectiveView = fs.readFileSync(
    path.join(projectRoot, 'app/Views/objectives/index.php'),
    'utf8',
);
const objectiveCreatorView = fs.readFileSync(
    path.join(projectRoot, 'app/Views/objectives/_creator_modal.php'),
    'utf8',
);
const objectiveMarkup = `${objectiveView}\n${objectiveCreatorView}`;
const priorityView = fs.readFileSync(
    path.join(projectRoot, 'app/Views/priorities/index.php'),
    'utf8',
);
const sidebarView = fs.readFileSync(
    path.join(projectRoot, 'app/Views/layouts/alpha_sidebar.php'),
    'utf8',
);
const script = fs.readFileSync(
    path.join(projectRoot, 'public/assets/js/workflow/index.js'),
    'utf8',
);
const styles = fs.readFileSync(
    path.join(projectRoot, 'public/assets/css/workflow/index.css'),
    'utf8',
);
const shellStyles = fs.readFileSync(
    path.join(projectRoot, 'public/assets/css/alpha-shell.css'),
    'utf8',
);
const themeStyles = fs.readFileSync(
    path.join(projectRoot, 'public/assets/css/theme.css'),
    'utf8',
);
const frontendHead = fs.readFileSync(
    path.join(projectRoot, 'app/Views/layouts/alpha_frontend_head.php'),
    'utf8',
);
const frontendScripts = fs.readFileSync(
    path.join(projectRoot, 'app/Views/layouts/alpha_frontend_scripts.php'),
    'utf8',
);
const functionalViews = [
    objectiveMarkup,
    priorityView,
    fs.readFileSync(path.join(projectRoot, 'app/Views/business/profile.php'), 'utf8'),
    fs.readFileSync(path.join(projectRoot, 'app/Views/finances/index.php'), 'utf8'),
];

test('workflow writes remain server-owned and CSRF protected', () => {
    const postForms = objectiveMarkup.match(/<form[\s\S]*?<\/form>/g) || [];

    assert.ok(postForms.length >= 4);

    postForms.forEach((form) => {
        assert.match(form, /method="post"/);
        assert.match(form, /<\?= csrf_field\(\) \?>/);
    });

    assert.doesNotMatch(objectiveMarkup, /name="business_id"/);
    assert.doesNotMatch(objectiveMarkup, /name="objective_id"/);
    assert.doesNotMatch(script, /fetch\(|XMLHttpRequest|localStorage|sessionStorage/);
});

test('priorities are a read-only derived view', () => {
    assert.match(priorityView, /Matriz de Eisenhower/);
    assert.match(priorityView, /foreach \(\$quadrantLabels/);
    const forms = priorityView.match(/<form[\s\S]*?<\/form>/g) || [];
    const sidebarForms = sidebarView.match(/<form[\s\S]*?<\/form>/g) || [];

    assert.equal(forms.length, 0);
    assert.equal(sidebarForms.length, 1);
    assert.match(sidebarForms[0], /site_url\('logout'\)/);
    assert.match(sidebarForms[0], /csrf_field\(\)/);
    assert.doesNotMatch(sidebarForms[0], /business_id|objective_id|activity_id/);
    assert.match(priorityView, /class="quadrant-items"[\s\S]*?role="region"[\s\S]*?tabindex="0"/);
    assert.match(priorityView, /'workflow\/index\.css'/);
    assert.match(shellStyles, /\.priorities-main \.priority-matrix\s*\{[\s\S]*?grid-template-rows:\s*repeat\(2, minmax\(0, 1fr\)\)/);
    assert.match(shellStyles, /\.priorities-main \.quadrant\s*\{[\s\S]*?overflow:\s*hidden/);
    assert.match(shellStyles, /\.priorities-main \.quadrant-items\s*\{[\s\S]*?overflow-y:\s*auto/);
    assert.match(shellStyles, /scrollbar-gutter:\s*stable/);
    assert.match(shellStyles, /\.quadrant-items::-webkit-scrollbar/);
    assert.match(shellStyles, /\.priorities-main \.priority-matrix\s*\{[\s\S]*?padding:\s*10px[\s\S]*?background:\s*transparent[\s\S]*?box-shadow:\s*none/);
    assert.match(shellStyles, /\.priorities-main \.priority-card\s*\{[\s\S]*?padding:\s*8px 9px[\s\S]*?border-width:\s*1px 1px 1px 3px/);
    assert.match(shellStyles, /\.quadrant-do_now \.priority-card:nth-child\(even\)/);
    assert.match(shellStyles, /\.quadrant-schedule \.priority-card:nth-child\(even\)/);
    assert.match(shellStyles, /\.quadrant-delegate \.priority-card:nth-child\(even\)/);
    assert.match(shellStyles, /\.quadrant-eliminate \.priority-card:nth-child\(even\)/);
    assert.match(shellStyles, /\.quadrant-do_now\s*\{\s*border-top-color:\s*var\(--blue\)/);
    assert.match(shellStyles, /\.quadrant-schedule\s*\{\s*border-top-color:\s*var\(--green\)/);
    assert.match(shellStyles, /\.quadrant-delegate\s*\{\s*border-top-color:\s*var\(--accent\)/);
    assert.match(shellStyles, /\.quadrant-eliminate\s*\{\s*border-top-color:\s*var\(--red\)/);
    assert.match(shellStyles, /\.quadrant-do_now > header\s*\{\s*background:\s*#9fc6dc/);
    assert.match(shellStyles, /\.quadrant-schedule > header\s*\{\s*background:\s*#acd6c1/);
    assert.match(shellStyles, /\.quadrant-delegate > header\s*\{\s*background:\s*#e8bd98/);
    assert.match(shellStyles, /\.quadrant-eliminate > header\s*\{\s*background:\s*#dfa5a2/);
    assert.match(shellStyles, /\.priorities-main \.priority-card\s*\{[\s\S]*?background-image:\s*none/);
    assert.match(shellStyles, /html\[data-theme="dark"\] \.priorities-main \.quadrant-do_now > header\s*\{\s*background:\s*#11364d/);
    assert.match(shellStyles, /html\[data-theme="dark"\] \.priorities-main \.quadrant-eliminate > header\s*\{\s*background:\s*#4d2023/);
    assert.doesNotMatch(shellStyles, /@media \(prefers-color-scheme:\s*dark\)/);
    assert.match(themeStyles, /html\[data-theme="dark"\]/);
    assert.match(styles, /\.priority-do_now\s*\{[\s\S]*?background:\s*#3879a5/);
    assert.match(styles, /\.priority-schedule\s*\{[\s\S]*?background:\s*#2c8c68/);
    assert.match(styles, /\.priority-delegate\s*\{[\s\S]*?background:\s*#d77a35/);
    assert.match(styles, /\.priority-eliminate\s*\{[\s\S]*?background:\s*#bd5f59/);
});

test('objective creation uses the approved Vue modal without moving business rules', () => {
    assert.match(script, /\[data-character-count\]/);
    assert.match(script, /form\.addEventListener\('submit'/);
    assert.match(script, /submit\.disabled = true/);
    assert.match(script, /scrollIntoView/);
    assert.doesNotMatch(objectiveMarkup, /workflow-metrics|aria-label="Resumen de objetivos"/);
    assert.match(objectiveView, /objective-hero/);
    assert.match(objectiveView, /view\('objectives\/_creator_modal'/);
    assert.match(objectiveCreatorView, /id="objectiveCreatorApp"/);
    assert.match(objectiveCreatorView, /class="objective-modal-layer[\s\S]*?id="objectiveCreator"/);
    assert.match(objectiveCreatorView, /role="dialog"/);
    assert.match(objectiveCreatorView, /aria-modal="true"/);
    assert.match(objectiveCreatorView, /class="objective-modal-close"/);
    assert.match(objectiveCreatorView, /aria-label="Cerrar modal"/);
    assert.match(objectiveView, /href="#objectiveCreator"/);
    assert.match(objectiveView, /view\('layouts\/alpha_frontend_head'/);
    assert.match(objectiveView, /view\('layouts\/alpha_frontend_scripts'\)/);
    assert.doesNotMatch(objectiveView, /objective-create-disclosure/);
    assert.match(priorityView, /eisenhower-legend/);
    assert.match(script, /window\.Vue\.createApp/);
    assert.match(script, /element\.inert = true/);
    assert.match(script, /event\.key === 'Escape'/);
    assert.match(script, /event\.key !== 'Tab'/);
    assert.match(script, /closeCreator\(\)/);
    assert.doesNotMatch(script, /creator\.classList\.add\('is-open'\)/);
    assert.match(styles, /\.objective-modal-layer\s*\{[\s\S]*?position:\s*fixed/);
    assert.match(styles, /background:\s*rgb\(4 27 29 \/ 78%\)/);
    assert.match(styles, /backdrop-filter:\s*blur\(8px\)/);
    assert.match(styles, /body\.objective-modal-is-open[\s\S]*?pointer-events:\s*none/);
    assert.match(styles, /\.objective-modal-content\s*\{[\s\S]*?border:\s*0/);
    assert.match(styles, /\.objective-modal-close\s*\{[\s\S]*?position:\s*absolute/);
    assert.match(styles, /top:\s*1\.15rem[\s\S]*?right:\s*1\.15rem/);
    assert.match(styles, /\.objective-modal-close span::before/);
    assert.match(styles, /translate\(-50%, -50%\) rotate\(45deg\)/);
    assert.match(styles, /\.objective-modal-close:hover span[\s\S]*?rotate\(90deg\)/);
    assert.match(styles, /prefers-reduced-motion:\s*reduce/);
    assert.match(frontendHead, /bootstrap@5\.3\.7/);
    assert.match(frontendScripts, /vue@3\.5\.41/);
    assert.match(frontendScripts, /bootstrap@5\.3\.7/);
    assert.match(frontendHead, /integrity="sha384-/);
    assert.match(frontendScripts, /integrity="sha384-/);
    assert.match(objectiveView, /'workflow\/index\.css'/);
    assert.match(objectiveView, /'contextual-help\.css'/);
    assert.match(objectiveView, /assets\/js\/contextual-help\.js/);
    assert.match(styles, /\.workflow-activity-guide\s*\{/);
    assert.match(styles, /\.progress-value \.context-help-trigger > span\s*\{/);
});

test('functional views use the shared shell without technical environment labels', () => {
    for (const view of functionalViews) {
        assert.match(view, /view\('layouts\/alpha_sidebar'/);
        assert.match(view, /view\('layouts\/alpha_topbar'/);
        assert.match(view, /'alpha-shell\.css'/);
        assert.doesNotMatch(view, /Alfa local|sólo está habilitado en desarrollo y pruebas/);
    }

    assert.doesNotMatch(sidebarView, /Alfa de validación|alpha-environment/);
    assert.match(objectiveView, /module-header module-header-compact/);
    assert.match(priorityView, /module-header module-header-compact/);
    assert.doesNotMatch(objectiveView, /Paso 2 de 4/);
    assert.doesNotMatch(priorityView, /Paso 3 de 4/);
});

test('Vue text directives never overwrite server-rendered fallback content', () => {
    for (const view of functionalViews) {
        const textDirectives = [...view.matchAll(
            /<([a-z][a-z0-9-]*)[^>]*v-text="[^"]+"[^>]*>([\s\S]*?)<\/\1>/gi,
        )];

        textDirectives.forEach((match) => assert.equal(match[2].trim(), ''));
    }
});
