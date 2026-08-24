'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');

const projectRoot = path.resolve(__dirname, '../..');
const view = fs.readFileSync(
    path.join(projectRoot, 'app/Views/dashboard/index.php'),
    'utf8',
);
const stylesheet = fs.readFileSync(
    path.join(projectRoot, 'public/assets/css/dashboard/index.css'),
    'utf8',
);

test('dashboard is a read-only synthesis of the four functional modules', () => {
    for (const route of ['app/mi-negocio', 'app/objetivos', 'app/prioridades', 'app/finanzas']) {
        assert.match(view, new RegExp(route));
    }

    for (const concept of [
        'Objetivos activos',
        'Avance de actividades',
        'Ventas del período',
        'EBITDA',
        'Últimos cierres confirmados',
        'Acciones que requieren atención',
        'Prioridades abiertas',
        'Perfil del negocio',
    ]) {
        assert.match(view, new RegExp(concept));
    }

    assert.doesNotMatch(view, /method="post"|csrf_field\(\)|fetch\(|XMLHttpRequest/);
    assert.doesNotMatch(view, /EBITDA provisional/);
    assert.doesNotMatch(view, /Según la fórmula validada con el cliente/);
    assert.doesNotMatch(view, />Vista general</);
    assert.doesNotMatch(view, /window\.Vue|createApp\(|v-if|v-for|v-model/);
    assert.match(view, /Editar perfil del negocio/);
});

test('dashboard adds eight focused contextual helps without turning the page into a Vue app', () => {
    assert.equal((view.match(/\$contextualHelp\(\[/g) || []).length, 8);
    assert.equal((view.match(/data-context-help-focus-target/g) || []).length, 8);
    assert.equal((view.match(/'anchor'\s*=>\s*'target'/g) || []).length, 8);
    assert.equal((view.match(/'placement'\s*=>\s*'top'/g) || []).length, 8);
    assert.equal((view.match(/'align'\s*=>\s*'center'/g) || []).length, 8);
    assert.match(view, /dashboard-help-active-objectives/);
    assert.match(view, /dashboard-help-progress/);
    assert.match(view, /dashboard-help-period-sales/);
    assert.match(view, /dashboard-help-ebitda/);
    assert.match(view, /dashboard-help-finances/);
    assert.match(view, /dashboard-help-featured-objective/);
    assert.match(view, /dashboard-help-priorities/);
    assert.match(view, /dashboard-help-next-actions/);
    assert.match(view, /'contextual-help\.css'/);
    assert.match(view, /assets\/js\/contextual-help\.js/);
    assert.doesNotMatch(view, /dashboard-help-profile/);
});

test('dashboard escapes stored business content and formats derived values server-side', () => {
    assert.match(view, /esc\(\$businessName\)/);
    assert.match(view, /esc\(\(string\) \$featured_objective\['title'\]\)/);
    assert.match(view, /esc\(\(string\) \$action\['title'\]\)/);
    assert.match(view, /number_format\(abs\(\$cents\) \/ 100/);
    assert.match(view, /profile_completion/);
    assert.doesNotMatch(view, /localStorage|sessionStorage/);
});

test('dashboard layout is responsive, compact and keeps balanced CSS structure', () => {
    assert.match(stylesheet, /grid-template-columns:\s*repeat\(4/);
    assert.match(stylesheet, /grid-template-columns:\s*minmax\(0,\s*0\.92fr\)/);
    assert.match(stylesheet, /min-height:\s*132px/);
    assert.doesNotMatch(stylesheet, /\.dashboard-focus\s*{[^}]*min-height:\s*320px/s);
    assert.match(stylesheet, /\.dashboard-chart\s*{/);
    assert.match(stylesheet, /\.dashboard-column\s*{/);
    assert.match(stylesheet, /\.dashboard-finance-visual\s*{/);
    assert.match(stylesheet, /\.dashboard-metric-label\s*{/);
    assert.match(stylesheet, /\.dashboard-focus\.is-context-help-focus\s*{/);
    assert.match(stylesheet, /\.dashboard-metric\s*{[\s\S]*?overflow:\s*hidden/);
    assert.doesNotMatch(stylesheet, /\.dashboard-metric\.is-context-help-focus\s*{[\s\S]*?overflow:\s*visible/);
    assert.doesNotMatch(view, /max\(4,\s*\(int\) \$chartEntry/);
    assert.ok(
        view.indexOf('dashboard-finances-overview') < view.indexOf('dashboard-grid'),
        'the financial overview must remain above the operational panel grid',
    );
    assert.match(stylesheet, /@media \(max-width:\s*1180px\)/);
    assert.match(stylesheet, /@media \(max-width:\s*720px\)/);
    assert.match(stylesheet, /prefers-reduced-motion/);

    const openBraces = (stylesheet.match(/{/g) || []).length;
    const closeBraces = (stylesheet.match(/}/g) || []).length;
    assert.equal(openBraces, closeBraces);
});
