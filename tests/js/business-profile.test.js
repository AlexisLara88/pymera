'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');

const projectRoot = path.resolve(__dirname, '../..');
const view = fs.readFileSync(
    path.join(projectRoot, 'app/Views/business/profile.php'),
    'utf8',
);
const script = fs.readFileSync(
    path.join(projectRoot, 'public/assets/js/business/profile.js'),
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

test('the business form keeps security and persistence decisions on the server', () => {
    assert.match(view, /method="post"/);
    assert.match(view, /<\?= csrf_field\(\) \?>/);
    assert.match(view, /<\?= \$fieldValue\(/);
    assert.doesNotMatch(view, /business_id/);
    assert.doesNotMatch(script, /fetch\(|XMLHttpRequest|localStorage|sessionStorage/);
});

test('Vue owns profile interaction while the server form remains authoritative', () => {
    assert.match(view, /businessProfileApp/);
    assert.match(view, /<div class="business-shell">/);
    assert.doesNotMatch(view, /<div[\s\S]{0,120}class="business-shell"[\s\S]{0,120}id="businessProfileApp"/);
    assert.match(view, /class="business-profile-app"[\s\S]*?id="businessProfileApp"/);
    assert.match(view, /data-open-business-editor/);
    assert.match(view, /@input="updateCharacterCount"/);
    assert.match(view, /@submit="startSubmitting"/);
    assert.match(script, /window\.Vue\.createApp/);
    assert.match(script, /enhanceProfileWithoutVue/);
    assert.match(script, /catch \(error\)/);
    assert.doesNotMatch(script, /this\.\$el/);
    assert.match(script, /businessProfile\.openEditor\(\)/);
    assert.match(script, /editor\.open = true/);
    assert.match(script, /\[data-character-count\]/);
    assert.match(script, /characterCounts/);
    assert.match(script, /submitting: false/);
    assert.match(view, /alpha-business-overview/);
    assert.match(view, /businessEditor/);
    assert.match(script, /editorOpen = true/);
    assert.match(view, /\$isOnboarding/);
    assert.match(view, /Configurar negocio/);
    assert.match(view, /Perfil del negocio/);
    assert.doesNotMatch(view, /Concepto futuro|Análisis guiado con IA|alpha-future-card/);
    assert.match(view, /'onboarding'\s*=>\s*\$isOnboarding/);
    assert.match(frontendHead, /bootstrap@5\.3\.7/);
    assert.match(frontendScripts, /vue@3\.5\.41/);
    assert.match(frontendScripts, /integrity="sha384-/);
    assert.match(view, /'business\/profile\.css'/);
});

test('profile Vue directives do not overwrite server fallback children', () => {
    const textDirectives = [...view.matchAll(
        /<([a-z][a-z0-9-]*)[^>]*v-text="[^"]+"[^>]*>([\s\S]*?)<\/\1>/gi,
    )];

    assert.ok(textDirectives.length >= 2);
    textDirectives.forEach((match) => assert.equal(match[2].trim(), ''));
    assert.match(view, /data-submit-default/);
    assert.match(view, /data-submit-progress/);
});

test('minimum business profile labels are written as complete questions', () => {
    assert.match(view, /¿Qué hace el negocio\?/);
    assert.match(view, /¿A quién atiende\?/);
    assert.match(view, /¿Qué productos o servicios ofrece\?/);
    assert.match(view, /¿Qué objetivos persigue\?/);
});
