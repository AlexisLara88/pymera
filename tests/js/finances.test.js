'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');

const projectRoot = path.resolve(__dirname, '../..');
const view = fs.readFileSync(
    path.join(projectRoot, 'app/Views/finances/index.php'),
    'utf8',
);
const script = fs.readFileSync(
    path.join(projectRoot, 'public/assets/js/finances/index.js'),
    'utf8',
);
const styles = fs.readFileSync(
    path.join(projectRoot, 'public/assets/css/finances/index.css'),
    'utf8',
);
const frontendScripts = fs.readFileSync(
    path.join(projectRoot, 'app/Views/layouts/alpha_frontend_scripts.php'),
    'utf8',
);

test('finance writes are CSRF protected and business ownership is server-side', () => {
    const postForms = view
        .match(/<form[\s\S]*?method="post"[\s\S]*?<\/form>/g) || [];

    assert.ok(postForms.length >= 2);

    postForms.forEach((form) => {
        assert.match(form, /<\?= csrf_field\(\) \?>/);
        assert.doesNotMatch(form, /name="business_id"/);
    });
});

test('finance scope remains explicitly preliminary and aggregate', () => {
    assert.match(view, /module-header module-header-compact/);
    assert.doesNotMatch(view, /Paso 4 de 4/);
    assert.match(view, /class="period-filter-controls"/);
    assert.match(view, /class="period-filter-input"/);
    assert.match(styles, /\.period-filter\s*\{[\s\S]*?width:\s*14\.25rem/);
    assert.match(styles, /\.period-filter-controls\s*\{[\s\S]*?grid-template-columns:\s*minmax\(0, 1fr\) auto/);
    assert.match(styles, /\.period-filter \.period-filter-input\s*\{[\s\S]*?min-height:\s*2\.35rem/);
    assert.match(view, /Utilidad bruta/);
    assert.match(view, />EBITDA</);
    assert.doesNotMatch(view, /EBITDA provisional/);
    assert.doesNotMatch(view, /Alcance del cálculo|Criterio financiero utilizado|Metodología del cliente|finance-scope-summary/);
    assert.match(view, /totales agregados/);
    assert.doesNotMatch(view, /<small>ROI<\/small>|Recuperación de inversión/);
    assert.match(view, /Punto de equilibrio/);
    assert.match(view, /finance_indicators/);
    assert.match(view, /Venta mínima estimada del período/);
    assert.doesNotMatch(view, /Sujeto a validación|Fórmula no confirmada/);
    assert.match(view, /finance-metrics/);
    assert.match(view, /finance-break-even-card/);
    assert.doesNotMatch(view, /alpha-future-indicators|<section class="indicator-row"/);
    assert.match(styles, /\.finance-main \.finance-metrics\s*\{[\s\S]*?grid-template-columns:\s*repeat\(5, minmax\(0, 1fr\)\)/);
    assert.match(view, /alpha-finance-workspace/);
    assert.match(view, /bar-chart/);
});

test('finance JavaScript only provides progressive enhancement', () => {
    assert.doesNotMatch(view, /financeModuleApp|class="business-shell"[^>]*id=/);
    assert.match(view, /data-finance-form/);
    assert.match(view, /data-finance-form-app data-currency="<\?= \$currency \?>"/);
    assert.match(view, /@input="updatePreview"/);
    assert.match(view, /@submit="startSubmitting"/);
    assert.match(script, /window\.Vue\.createApp/);
    assert.match(script, /financeComponents\.forEach\(mountFinanceForm\)/);
    assert.match(script, /\.mount\(root\)/);
    assert.match(script, /root\.querySelector\('\[data-finance-form\]'\)/);
    assert.match(script, /enhanceFinanceFormWithoutVue/);
    assert.match(script, /catch \(error\)/);
    assert.doesNotMatch(script, /financeRoot|financeModuleApp|\.mount\(financeRoot\)|\.mount\(form\)/);
    assert.doesNotMatch(script, /this\.\$el/);
    assert.match(script, /data-result-preview/);
    assert.match(script, /Intl\.NumberFormat/);
    assert.match(script, /administrative_expense_amount/);
    assert.match(script, /amount\('income_amount'\)/);
    assert.match(script, /submitting: false/);
    assert.match(script, /submit\.disabled = true/);
    assert.match(frontendScripts, /vue@3\.5\.41/);
    assert.match(frontendScripts, /bootstrap@5\.3\.7/);
    assert.doesNotMatch(script, /fetch\(|XMLHttpRequest|localStorage|sessionStorage/);
});

test('finance keeps one total-sales KPI with a native collapsed origin breakdown', () => {
    assert.match(view, /<details class="metric-card accent-green finance-sales-card">/);
    assert.match(view, /Ventas totales/);
    assert.match(view, /Registradas manualmente/);
    assert.match(view, /Provenientes del CRM/);
    assert.match(view, /Total usado en los cálculos/);
    assert.match(view, /sales_breakdown\['manual_sales_cents'\]/);
    assert.match(view, /sales_breakdown\['crm_sales_cents'\]/);
    assert.match(styles, /\.finance-sales-card summary/);
    assert.doesNotMatch(script, /finance-sales-card|sales_breakdown/);
});

test('finance preview directives do not contain conflicting fallback children', () => {
    const previewElements = [...view.matchAll(
        /<strong[^>]*data-result-preview[^>]*v-text="[^"]+"[^>]*>([\s\S]*?)<\/strong>/g,
    )];

    assert.ok(previewElements.length >= 2);
    previewElements.forEach((match) => assert.equal(match[1].trim(), ''));
});
