'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');

const projectRoot = path.resolve(__dirname, '../..');
const view = fs.readFileSync(
    path.join(projectRoot, 'app/Views/crm/index.php'),
    'utf8',
);
const editors = fs.readFileSync(
    path.join(projectRoot, 'app/Views/crm/_editors.php'),
    'utf8',
);
const script = fs.readFileSync(
    path.join(projectRoot, 'public/assets/js/crm/index.js'),
    'utf8',
);
const styles = fs.readFileSync(
    path.join(projectRoot, 'public/assets/css/crm/index.css'),
    'utf8',
);
const sidebar = fs.readFileSync(
    path.join(projectRoot, 'app/Views/layouts/alpha_sidebar.php'),
    'utf8',
);
const saleNoteView = fs.readFileSync(
    path.join(projectRoot, 'app/Views/crm/sale_note_pdf.php'),
    'utf8',
);
const saleNoteRenderer = fs.readFileSync(
    path.join(projectRoot, 'app/Libraries/SaleNotePdfRenderer.php'),
    'utf8',
);

test('CRM writes remain CSRF protected and business ownership stays server-side', () => {
    const forms = `${view}\n${editors}`.match(/<form[\s\S]*?<\/form>/g) || [];

    assert.ok(forms.length >= 4);
    forms.forEach((form) => {
        assert.match(form, /method="post"/);
        assert.match(form, /<\?= csrf_field\(\) \?>/);
        assert.doesNotMatch(form, /name="business_id"/);
    });
});

test('CRM is published as a functional MVP module with clear empty states', () => {
    assert.match(sidebar, /'crm'\s*=>\s*\['Clientes y ventas', 'Seguimiento comercial', 'app\/clientes', true\]/);
    assert.match(sidebar, /Seguimiento comercial/);
    assert.match(view, /Clientes y ventas/);
    assert.match(view, /Empezá por tu primer contacto/);
    assert.match(view, /Todavía no hay oportunidades/);
    assert.match(view, /Seguimientos vencidos/);
    assert.match(view, /acquisition_channels/);
    assert.doesNotMatch(view, /experimental|provisional|próxima etapa|concepto futuro/i);
    assert.match(styles, /\.crm-stage-prospect\s*\{\s*color:\s*var\(--blue\);\s*background:\s*var\(--blue-soft\);\s*\}/);
});

test('CRM contextual help remains semantic and independent from rows and view preference', () => {
    const instances = view.match(/\$contextualHelp\(\[/g) || [];

    assert.equal(instances.length, 7);
    assert.match(view, /crm-help-workflow/);
    assert.match(view, /crm-help-summary/);
    assert.match(view, /crm-help-contacts/);
    assert.match(view, /crm-help-opportunities/);
    assert.match(view, /crm-help-status/);
    assert.match(view, /crm-help-follow-up/);
    assert.match(view, /crm-help-finances/);
    assert.match(view, /id="crmWorkflowContent" data-context-help-focus-target/);
    assert.match(view, /id="crmMetrics" data-context-help-focus-target/);
    assert.match(view, /id="crmOpportunitiesTable" data-context-help-focus-target/);
    assert.match(view, /'contextual-help\.css'/);
    assert.ok(view.indexOf('assets/js/crm/index.js') < view.indexOf('assets/js/contextual-help.js'));
    assert.match(styles, /\.crm-table-heading-help \.context-help-card/);
    assert.match(styles, /\.crm-table-wrap\.is-context-help-focus/);
});

test('CRM consumes the personal composition and keeps accessible tabs', () => {
    assert.match(view, /data-crm-view-switcher data-crm-view="<\?= esc\(\$crmView, 'attr'\) \?>"/);
    assert.doesNotMatch(view, /data-crm-view-option/);
    assert.doesNotMatch(view, /Organización de la pantalla|Vista conjunta|Vista por pestañas/);
    assert.match(view, /role="tablist"/);
    assert.match(view, /data-crm-section-tab="contacts"/);
    assert.match(view, /data-crm-section-tab="opportunities"/);
    assert.match(view, /data-crm-section-panel="contacts"/);
    assert.match(view, /data-crm-section-panel="opportunities"/);
    assert.match(script, /viewSwitcher\.dataset\.crmView === 'tabs'/);
    assert.match(script, /searchParams\.get\('section'\)/);
    assert.match(script, /selectView\(preferredView\)/);
    assert.match(script, /searchParams\.delete\('view'\)/);
    assert.match(script, /history\.replaceState/);
    assert.match(script, /syncCrmReturnContext/);
    assert.match(script, /return_section/);
    assert.doesNotMatch(script, /return_view/);
    assert.match(view, /data-crm-return-context/);
    assert.match(editors, /data-crm-return-context/);
    assert.match(script, /panel\.hidden = false/);
    assert.match(script, /event\.key === 'ArrowRight'/);
    assert.match(script, /event\.key === 'ArrowLeft'/);
    assert.match(styles, /data-crm-view="tabs"[\s\S]*?\.crm-workspace[\s\S]*?grid-template-columns:\s*minmax\(0, 1fr\)/);
    assert.doesNotMatch(script, /localStorage|sessionStorage/);
});

test('CRM creation actions belong to their contextual panels in both compositions', () => {
    const moduleHeader = view.match(/<header class="module-header[\s\S]*?<\/header>/)?.[0] || '';
    const opportunitiesStart = view.indexOf('data-crm-section-panel="opportunities"');
    const contactsStart = view.indexOf('data-crm-section-panel="contacts"');
    const contactsEnd = view.indexOf('</aside>', contactsStart);
    const opportunitiesPanel = view.slice(opportunitiesStart, contactsStart);
    const contactsPanel = view.slice(contactsStart, contactsEnd);

    assert.ok(opportunitiesStart > -1 && contactsStart > opportunitiesStart && contactsEnd > contactsStart);
    assert.doesNotMatch(moduleHeader, /data-open-contact-editor|data-open-opportunity-editor/);
    assert.match(opportunitiesPanel, /data-open-opportunity-editor/);
    assert.doesNotMatch(opportunitiesPanel, /data-open-contact-editor/);
    assert.match(contactsPanel, /data-open-contact-editor/);
    assert.doesNotMatch(contactsPanel, /data-open-opportunity-editor/);
    assert.match(opportunitiesPanel, /crm-panel-title-row[\s\S]*?Oportunidades[\s\S]*?crm-count/);
    assert.match(contactsPanel, /crm-panel-title-row[\s\S]*?Contactos[\s\S]*?crm-count/);
    assert.match(styles, /\.crm-panel-heading-actions/);
    assert.match(styles, /\.crm-panel-title-row/);
    assert.match(styles, /\.crm-panel-action/);
});

test('Vue enhances only the CRM filter, editor and status islands', () => {
    assert.match(view, /data-crm-filter-app/);
    assert.match(editors, /id="crmEditorApp"/);
    assert.match(editors, /id="crmStatusApp"/);
    assert.match(script, /window\.Vue\.createApp/);
    assert.match(script, /\.mount\(filterRoot\)/);
    assert.match(script, /\.mount\(editorRoot\)/);
    assert.match(script, /\.mount\(statusRoot\)/);
    assert.match(script, /enhanceFiltersWithoutVue/);
    assert.match(view, /data-crm-contact-search-app/);
    assert.match(view, /placeholder="Buscar"/);
    assert.match(view, /aria-label="Buscar contacto por nombre"/);
    assert.match(view, /class="crm-contact-search-icon"/);
    assert.doesNotMatch(view, />Buscar por nombre</);
    assert.match(view, /data-contact-name=/);
    assert.match(script, /applyContactSearch/);
    assert.match(script, /enhanceContactSearchWithoutVue/);
    assert.match(script, /\.mount\(contactSearchRoot\)/);
    assert.match(script, /updateFallbackForm/);
    assert.doesNotMatch(editors, /:action=/);
    assert.match(script, /this\.\$refs\.contactForm\.action = this\.contactAction/);
    assert.match(script, /this\.\$refs\.opportunityForm\.action = this\.opportunityAction/);
    assert.match(script, /this\.\$refs\.form\.action = this\.statusAction/);
    assert.match(script, /submit\.disabled = true/);
    assert.doesNotMatch(view, /class="business-shell"[^>]*id=/);
    assert.doesNotMatch(script, /fetch\(|XMLHttpRequest|localStorage|sessionStorage/);
});

test('opportunity status changes are explicit and can coordinate one financial sale', () => {
    assert.match(view, /data-crm-status-form/);
    assert.match(view, /\/estado/);
    assert.match(view, /name="finance_action" value="none"/);
    assert.match(editors, /Registrar también en Finanzas/);
    assert.match(editors, /Monto final de la venta/);
    assert.match(editors, /Fecha de la venta/);
    assert.match(script, /Cambiar estado y revertir venta/);
    assert.match(script, /financeAction\(\)/);
    assert.match(script, /return 'reverse'/);
    assert.match(script, /return this\.canOfferFinance && this\.recordInFinance \? 'record' : 'none'/);
    assert.match(script, /setAttribute\('inert', ''\)/);
    assert.doesNotMatch(script, /fetch\(|XMLHttpRequest/);
});

test('opportunity rows preserve native table layout in both CRM views', () => {
    assert.match(view, /<span>Estado<\/span>[\s\S]*?<span>Finanzas<\/span>[\s\S]*?<th>Valor<\/th>/);
    assert.match(view, /class="crm-finance-cell"/);
    assert.match(view, /aria-label="Venta incluida en Finanzas">Incluida<\/small>/);
    assert.match(view, /class="crm-finance-empty"/);
    assert.doesNotMatch(styles, /\.crm-table td:first-child\s*\{[^}]*display:\s*grid/);
    assert.match(styles, /\.crm-table td:first-child > strong,[\s\S]*?display:\s*block/);
    assert.match(styles, /\.crm-status-quick-form\s*\{[\s\S]*?display:\s*inline-flex/);
    assert.match(styles, /\.crm-finance-link\s*\{[\s\S]*?white-space:\s*nowrap/);
    assert.match(script, /trigger\.closest\('\[data-crm-opportunity\]'\)/);
    assert.match(script, /row\?\.querySelector\('\[data-crm-status-form\]'\)/);
});

test('won recorded opportunities expose compact accessible action icons', () => {
    assert.match(view, /crm-icon-action-edit/);
    assert.match(view, /crm-icon-action-download/);
    assert.match(view, /crm-icon-action-archive/);
    assert.match(view, /title="Editar"/);
    assert.match(view, /title="Descargar"/);
    assert.match(view, /title="Archivar"/);
    assert.match(view, /aria-label="Descargar nota de venta"/);
    assert.match(view, /data-crm-sale-note/);
    assert.match(view, /identity_document/);
    assert.match(view, /\/nota-venta/);
    assert.match(styles, /\.crm-icon-action-edit\s*\{[\s\S]*?var\(--blue-soft\)/);
    assert.match(styles, /\.crm-icon-action-download\s*\{[\s\S]*?var\(--green-soft\)/);
    assert.match(styles, /\.crm-icon-action-archive\s*\{[\s\S]*?var\(--red-soft\)/);
});

test('sale notes are non-fiscal PDFs generated on demand outside the project', () => {
    assert.match(saleNoteView, /NOTA DE VENTA/);
    assert.match(saleNoteView, /Comprobante comercial no fiscal/);
    assert.match(saleNoteView, /no constituye factura/i);
    assert.match(saleNoteView, /Generado con PyMERA/);
    assert.match(saleNoteView, /pymera-symbol\.svg/);
    assert.match(saleNoteView, /esc\(FCPATH \. 'assets\/brand\/pymera-symbol\.svg'\)/);
    assert.doesNotMatch(saleNoteView, /pymera-symbol\.svg', 'attr'/);
    assert.match(saleNoteView, /DNI\/CI/);
    assert.doesNotMatch(saleNoteView, /\bNIT\b|\bRUC\b|N[úu]mero|SUBTOTAL|\bIVA\b|Impuesto/i);
    assert.match(saleNoteRenderer, /sys_get_temp_dir\(\)/);
    assert.match(saleNoteRenderer, /Destination::STRING_RETURN/);
    assert.doesNotMatch(saleNoteRenderer, /OutputFile|Destination::FILE/);
});

test('missing DNI or CI opens a focused Vue form and preserves a native fallback', () => {
    assert.match(editors, /id="crmSaleNoteApp"/);
    assert.match(editors, /name="identity_document"/);
    assert.match(editors, /Guardar y descargar/);
    assert.match(editors, /<\?= csrf_field\(\) \?>/);
    assert.match(script, /saleNotePayload/);
    assert.match(script, /payload\.identity_document\.trim\(\) !== ''/);
    assert.match(script, /saleNoteApp\.show\(payload, trigger\)/);
    assert.match(script, /showSaleNoteFallback/);
    assert.match(script, /rememberSaleNoteIdentity/);
    assert.match(script, /trigger\.dataset\.saleNote = JSON\.stringify\(payload\)/);
    assert.match(script, /crm-modal-is-open/);
    assert.match(styles, /\.crm-sale-note-modal\s*\{[\s\S]*?width:\s*min\(540px, 100%\)/);
});

test('contact actions are visually distinct and share one responsive row', () => {
    assert.match(view, /crm-contact-action-edit/);
    assert.match(view, /crm-contact-action-convert/);
    assert.match(view, /crm-contact-action-archive/);
    assert.match(styles, /\.crm-contact-card footer\s*\{[\s\S]*?display:\s*flex[\s\S]*?flex-wrap:\s*wrap/);
    assert.match(styles, /\.crm-contact-action\s*\{[\s\S]*?width:\s*auto[\s\S]*?min-height:\s*28px/);
    assert.match(styles, /\.crm-contact-action\s*\{[\s\S]*?border-radius:\s*999px/);
    assert.match(styles, /\.crm-contact-action\s*\{[\s\S]*?white-space:\s*nowrap/);
    assert.match(styles, /\.crm-contact-action-convert\s*\{[\s\S]*?var\(--green-soft\)/);
    assert.match(styles, /\.crm-contact-action-archive\s*\{[\s\S]*?var\(--red-soft\)/);
});

test('contact search remains legible in the explicit dark theme', () => {
    const themeStyles = fs.readFileSync(
        path.join(projectRoot, 'public/assets/css/theme.css'),
        'utf8',
    );

    assert.match(styles, /\.crm-contact-search \.form-control\s*\{[\s\S]*?color:\s*var\(--ink\)[\s\S]*?caret-color:\s*var\(--brand-2\)/);
    assert.match(themeStyles, /html\[data-theme="dark"\] \.crm-contact-search \.form-control\s*\{[\s\S]*?color:\s*var\(--ink\)[\s\S]*?-webkit-text-fill-color:\s*var\(--ink\)/);
    assert.match(themeStyles, /html\[data-theme="dark"\] \.crm-contact-search \.form-control::placeholder\s*\{[\s\S]*?-webkit-text-fill-color:\s*var\(--muted\)/);
});

test('CRM dialogs block the page and preserve keyboard navigation', () => {
    assert.match(editors, /role="dialog"/);
    assert.match(editors, /aria-modal="true"/);
    assert.match(script, /event\.key === 'Escape'/);
    assert.match(script, /event\.key !== 'Tab'/);
    assert.match(script, /setAttribute\('inert', ''\)/);
    assert.match(script, /removeAttribute\('inert'\)/);
    assert.match(styles, /body\.crm-modal-is-open/);
    assert.match(styles, /backdrop-filter:\s*blur\(/);
    assert.match(view, /data-confirm-title="Convertir en cliente"/);
    assert.match(view, /data-confirm-title="Archivar contacto"/);
    assert.match(editors, /id="crmConfirmLayer"/);
    assert.match(editors, /role="alertdialog"/);
    assert.match(script, /pendingConfirmationForm/);
    assert.match(script, /form\.requestSubmit\(\)/);
    assert.match(styles, /\.crm-confirm-layer\.is-open/);
});

test('Vue text bindings do not overwrite server-rendered fallback content', () => {
    const markup = `${view}\n${editors}`;
    const textDirectives = [...markup.matchAll(
        /<([a-z][a-z0-9-]*)[^>]*v-text="[^"]+"[^>]*>([\s\S]*?)<\/\1>/gi,
    )];

    textDirectives.forEach((match) => assert.equal(match[2].trim(), ''));
});
