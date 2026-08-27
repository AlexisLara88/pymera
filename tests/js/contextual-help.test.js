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
const objectives = fs.readFileSync(
    path.join(projectRoot, 'app/Views/objectives/index.php'),
    'utf8',
);
const objectiveCreator = fs.readFileSync(
    path.join(projectRoot, 'app/Views/objectives/_creator_modal.php'),
    'utf8',
);
const priorities = fs.readFileSync(
    path.join(projectRoot, 'app/Views/priorities/index.php'),
    'utf8',
);
const finances = fs.readFileSync(
    path.join(projectRoot, 'app/Views/finances/index.php'),
    'utf8',
);
const crm = fs.readFileSync(
    path.join(projectRoot, 'app/Views/crm/index.php'),
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
    assert.match(component, /data-context-help-anchor/);
    assert.match(component, /data-context-help-placement/);
    assert.match(component, /data-context-help-align/);
    assert.match(component, /esc\(\$title/);
    assert.match(component, /esc\(\$paragraph\)/);
    assert.match(component, /esc\(\$item\)/);
    assert.match(component, /esc\(\$example\)/);
    assert.doesNotMatch(component, /\{!{2}|innerHTML/);
});

test('Mi negocio owns block and question-level contextual explanations', () => {
    const instances = profile.match(/\$contextualHelp\(\[/g) || [];
    const focusedForms = profile.match(/<section class="form-card"[^>]*data-context-help-focus-target/g) || [];

    assert.equal(instances.length, 7);
    assert.match(profile, /\['saveData' => false\]/);
    assert.match(component, /\$contextualHelp\['targetId'\] \?\? null/);
    assert.equal(focusedForms.length, 3);
    assert.match(profile, /business-help-purpose/);
    assert.match(profile, /'targetId' => 'businessDiagnosisPanel'/);
    assert.match(profile, /'targetId' => 'businessCompletionCard'/);
    assert.match(profile, /'targetId' => 'businessGeneralDataFormCard'/);
    assert.match(profile, /'targetId' => 'businessMinimumProfileFormCard'/);
    assert.match(profile, /'targetId' => 'businessGuidedDiagnosisFormCard'/);
    assert.equal((profile.match(/'anchor' => 'target'/g) || []).length, 7);
    assert.equal((profile.match(/'placement' => 'top'/g) || []).length, 6);
    assert.equal((profile.match(/'align' => 'center'/g) || []).length, 7);
    assert.equal((profile.match(/'placement' => 'left'/g) || []).length, 1);
    assert.match(profile, /id="businessDiagnosisPanel" data-context-help-focus-target/);
    assert.match(profile, /business-help-completion/);
    assert.match(profile, /business-help-general-data/);
    assert.match(profile, /business-help-minimum-profile/);
    assert.match(profile, /business-help-diagnosis/);
    assert.match(profile, /business-help-what-it-does/);
    assert.match(profile, /business-help-customers-served/);
    assert.match(profile, /business-help-products-offered/);
    assert.match(profile, /business-help-objectives-summary/);
    assert.match(profile, /business-help-differentiator/);
    assert.match(profile, /business-help-differentiation-delivery/);
    assert.match(profile, /business-help-customer-outcome/);
    assert.match(profile, /business-help-purchase-reason/);
    assert.match(profile, /business-help-acquisition-channels/);
    assert.match(profile, /field-question-label/);
    assert.doesNotMatch(profile, /business-help-(?:name|currency|timezone)/);
    assert.match(profile, /data-context-help-focus-target/);
    assert.match(profile, /'contextual-help\.css'/);
    assert.match(profile, /assets\/js\/contextual-help\.js/);
    assert.ok(
        profile.indexOf('assets/js/business/profile.js') < profile.indexOf('assets/js/contextual-help.js'),
        'Vue must mount before contextual help binds to the final editor nodes',
    );
});

test('Objetivos explains its workflow without placing contextual help inside the Vue modal', () => {
    const instances = objectives.match(/\$contextualHelp\(\[/g) || [];

    assert.equal(instances.length, 3);
    assert.match(objectives, /id="objectiveWorkflowContent" data-context-help-focus-target/);
    assert.match(objectives, /id="objectiveConceptHeader"/);
    assert.doesNotMatch(objectives, /id="objectiveConceptHeader" data-context-help-focus-target/);
    assert.match(objectives, /id="featuredObjectiveCard" data-context-help-focus-target/);
    assert.match(objectives, /class="objective-card" id="<\?= esc\(\$objectiveKey\) \?>" data-context-help-focus-target/);
    assert.doesNotMatch(objectives, /id="objectiveManagementHeading"|workflow-activity-guide/);
    assert.match(objectives, /objectives-help-concept/);
    assert.match(objectives, /objectives-help-progress/);
    assert.match(objectives, /objectives-help-card-/);
    assert.match(objectives, /'targetId'\s*=>\s*\$objectiveKey/);
    assert.equal((objectives.match(/'anchor'\s*=>\s*'target'/g) || []).length, 2);
    assert.equal((objectives.match(/'anchor'\s*=>\s*'trigger'/g) || []).length, 1);
    assert.equal((objectives.match(/'placement'\s*=>\s*'top'/g) || []).length, 2);
    assert.equal((objectives.match(/'placement'\s*=>\s*'right'/g) || []).length, 1);
    assert.equal((objectives.match(/'align'\s*=>\s*'center'/g) || []).length, 2);
    assert.equal((objectives.match(/'align'\s*=>\s*'start'/g) || []).length, 1);
    assert.match(objectives, /'contextual-help\.css'/);
    assert.match(objectives, /assets\/js\/contextual-help\.js/);
    assert.ok(
        objectives.indexOf('assets/js/workflow/index.js') < objectives.indexOf('assets/js/contextual-help.js'),
        'Vue must mount before contextual help binds to the final objective nodes',
    );
    assert.doesNotMatch(objectiveCreator, /contextualHelp|data-context-help/);
});

test('Prioridades explains the matrix and highlights each quadrant independently', () => {
    const instances = priorities.match(/\$contextualHelp\(\[/g) || [];

    assert.equal(instances.length, 2);
    assert.match(priorities, /priorities-help-matrix/);
    assert.match(priorities, /¿Cómo funciona la Matriz de Eisenhower\?/);
    assert.match(priorities, /id="priorityMatrix"/);
    assert.match(priorities, /'targetId'\s*=>\s*'priorityMatrix'/);
    assert.match(priorities, /'anchor'\s*=>\s*'trigger'/);
    assert.match(priorities, /'placement'\s*=>\s*'right'/);
    assert.match(priorities, /'align'\s*=>\s*'start'/);
    assert.match(priorities, /foreach \(\$quadrantLabels as \$quadrant => \$label\)/);
    assert.match(priorities, /id="<\?= esc\(\$quadrantTargetId, 'attr'\) \?>"/);
    assert.match(priorities, /data-context-help-focus-target/);
    assert.match(priorities, /priorities-help-/);
    assert.match(priorities, /¿Qué va en Hacer ahora\?/);
    assert.match(priorities, /¿Qué va en Planificar\?/);
    assert.match(priorities, /¿Qué va en Delegar\?/);
    assert.match(priorities, /¿Qué va en Eliminar\?/);
    assert.match(priorities, /'targetId'\s*=>\s*\$quadrantTargetId/);
    assert.match(priorities, /'anchor'\s*=>\s*'target'/);
    assert.match(priorities, /'placement'\s*=>\s*'top'/);
    assert.match(priorities, /'align'\s*=>\s*'center'/);
    assert.doesNotMatch(priorities, /href="#matrixHelp"|id="matrixHelp"/);
    assert.match(priorities, /'contextual-help\.css'/);
    assert.match(priorities, /assets\/js\/contextual-help\.js/);
});

test('Finanzas explains the screen, its five metrics and its operational blocks', () => {
    const instances = finances.match(/\$contextualHelp\(\[/g) || [];

    assert.equal(instances.length, 10);
    assert.match(finances, /finances-help-concept/);
    assert.match(finances, /finances-help-period/);
    assert.match(finances, /finances-help-total-sales/);
    assert.match(finances, /finances-help-cost-of-sales/);
    assert.match(finances, /finances-help-gross-profit/);
    assert.match(finances, /finances-help-ebitda/);
    assert.match(finances, /finances-help-break-even/);
    assert.match(finances, /finances-help-daily-entry/);
    assert.match(finances, /finances-help-evolution/);
    assert.match(finances, /finances-help-history/);
    assert.match(finances, /class="metric-row finance-metrics"/);
    assert.match(finances, /id="financeWorkflowContent"[\s\S]*?data-context-help-focus-target/);
    assert.match(finances, /id="financeTotalSalesCard"[\s\S]*?data-context-help-focus-target/);
    assert.match(finances, /id="financeCostOfSalesCard"[\s\S]*?data-context-help-focus-target/);
    assert.match(finances, /id="financeGrossProfitCard"[\s\S]*?data-context-help-focus-target/);
    assert.match(finances, /id="financeEbitdaCard"[\s\S]*?data-context-help-focus-target/);
    assert.match(finances, /id="financeBreakEvenCard"[\s\S]*?data-context-help-focus-target/);
    assert.match(finances, /'placement'\s*=>\s*'bottom'/);
    assert.match(finances, /'align'\s*=>\s*'end'/);
    assert.match(finances, /id="create-entry"/);
    assert.match(finances, /id="financeEvolutionPanel" data-context-help-focus-target/);
    assert.match(finances, /id="financeHistoryPanel"[\s\S]*?data-context-help-focus-target/);
    assert.match(finances, /'contextual-help\.css'/);
    assert.match(finances, /assets\/js\/contextual-help\.js/);
    assert.doesNotMatch(finances, /finances-help-indicators/);
    assert.doesNotMatch(finances, /finance-overview|finance-metrics-heading|Resumen del período/);
    assert.doesNotMatch(finances, /finance-metric-help-wrapper/);
    assert.doesNotMatch(finances, /alpha-future-indicators|<section class="indicator-row"/);
});

test('CRM explains its commercial flow once across both visual compositions', () => {
    const instances = crm.match(/\$contextualHelp\(\[/g) || [];
    const helpIds = [
        'crm-help-workflow',
        'crm-help-prospects',
        'crm-help-clients',
        'crm-help-open-opportunities',
        'crm-help-estimated-value',
        'crm-help-overdue-follow-ups',
        'crm-help-contacts',
        'crm-help-opportunities',
        'crm-help-status',
        'crm-help-follow-up',
        'crm-help-finances',
    ];

    assert.equal(instances.length, 11);
    helpIds.forEach((helpId) => assert.equal((crm.match(new RegExp(helpId, 'g')) || []).length, 1));
    assert.match(crm, /id="crmWorkflowContent" data-context-help-focus-target/);
    assert.match(crm, /id="crmProspectsMetric" data-context-help-focus-target/);
    assert.match(crm, /id="crmClientsMetric" data-context-help-focus-target/);
    assert.match(crm, /id="crmOpenOpportunitiesMetric" data-context-help-focus-target/);
    assert.match(crm, /id="crmEstimatedValueMetric" data-context-help-focus-target/);
    assert.match(crm, /id="crmOverdueFollowUpsMetric" data-context-help-focus-target/);
    assert.doesNotMatch(crm, /crm-help-summary|crm-metrics-heading|id="crmMetrics"/);
    assert.match(crm, /id="crmOpportunitiesTable" data-context-help-focus-target/);
    assert.match(crm, /'targetId'\s*=>\s*'crmContactsPanel'/);
    assert.match(crm, /'targetId'\s*=>\s*'crmOpportunitiesPanel'/);
    assert.equal((crm.match(/'targetId'\s*=>\s*'crmOpportunitiesTable'/g) || []).length, 3);
    assert.match(crm, /crm-table-heading-help/);
    assert.match(crm, /'contextual-help\.css'/);
    assert.match(crm, /assets\/js\/contextual-help\.js/);
    assert.ok(
        crm.indexOf('assets/js/crm/index.js') < crm.indexOf('assets/js/contextual-help.js'),
        'Vue must mount before contextual help binds to the final CRM nodes',
    );

    const opportunityRows = crm.match(/<tbody data-crm-opportunity-rows>[\s\S]*?<\/tbody>/)?.[0] || '';
    const contactCards = crm.match(/<div class="crm-contact-list">[\s\S]*?<\/div>\s*<div class="crm-contact-search-empty"/)?.[0] || '';
    assert.doesNotMatch(opportunityRows, /\$contextualHelp|data-context-help/);
    assert.doesNotMatch(contactCards, /\$contextualHelp|data-context-help/);
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
    assert.match(script, /configuredPosition/);
    assert.match(script, /help\.dataset\.contextHelpAnchor === 'target'/);
    assert.match(script, /activeFocusTarget\.getBoundingClientRect/);
    assert.match(script, /help\.dataset\.contextHelpPlacement/);
    assert.match(script, /help\.dataset\.contextHelpAlign/);
    assert.match(script, /case 'inside-right'/);
    assert.match(script, /case 'inside-left'/);
    assert.match(script, /case 'left'/);
    assert.match(script, /case 'top'/);
    assert.match(script, /case 'bottom'/);
    assert.doesNotMatch(script, /rectangleContainsPoint|rectanglesOverlap|candidateFits/);
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
    assert.match(styles, /\.context-help-close > span::before/);
    assert.match(styles, /\.context-help-close > span::after/);
    assert.match(styles, /\.context-help-close:hover > span\s*\{[\s\S]*?transform:\s*rotate\(90deg\)/);
    assert.match(styles, /@media \(prefers-reduced-motion:\s*reduce\)[\s\S]*?\.context-help-close > span/);
    assert.match(styles, /@media \(max-width:\s*680px\)/);
    assert.match(styles, /@media \(prefers-reduced-motion:\s*reduce\)/);
    assert.match(styles, /var\(--paper\)/);
    assert.match(styles, /var\(--line\)/);

    const openBraces = (styles.match(/{/g) || []).length;
    const closeBraces = (styles.match(/}/g) || []).length;
    assert.equal(openBraces, closeBraces);
});
