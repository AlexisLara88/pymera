'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');

const projectRoot = path.resolve(__dirname, '../..');
const view = fs.readFileSync(path.join(projectRoot, 'app/Views/demo/erp_lite.php'), 'utf8');
const script = fs.readFileSync(path.join(projectRoot, 'public/assets/js/erp-lite-demo.js'), 'utf8');
const styles = fs.readFileSync(path.join(projectRoot, 'public/assets/css/erp-lite-demo.css'), 'utf8');

const views = ['business', 'objectives', 'priorities', 'finances', 'crm', 'summary'];

test('the demo exposes the six approved screens', () => {
    views.forEach((viewName) => {
        assert.match(view, new RegExp(`data-view="${viewName}"`));
        assert.match(view, new RegExp(`data-view-target="${viewName}"`));
    });
});

test('every modal trigger references a modal present in the view', () => {
    const triggerMatches = [...view.matchAll(/data-modal-open="([^"]+)"/g)];
    const modalIds = new Set([...view.matchAll(/id="([^"]+Modal)"/g)].map((match) => match[1]));

    assert.ok(triggerMatches.length > 0);
    triggerMatches.forEach((match) => {
        assert.ok(modalIds.has(match[1]), `Missing modal #${match[1]}`);
    });
});

test('the demo is self-contained and does not persist or call remote services', () => {
    assert.doesNotMatch(script, /\bfetch\s*\(/);
    assert.doesNotMatch(script, /\blocalStorage\b/);
    assert.doesNotMatch(script, /\bsessionStorage\b/);
    assert.doesNotMatch(view, /https?:\/\//);
    assert.match(view, /base_url\('assets\/css\/erp-lite-demo\.css/);
    assert.match(view, /base_url\('assets\/js\/erp-lite-demo\.js/);
});

test('the prototype labels its simulated and future capabilities', () => {
    assert.match(view, /Datos ficticios/);
    assert.match(view, /no guarda información ni realiza cálculos definitivos/);
    assert.match(view, /Visión futura/);
    assert.match(view, /No se está ejecutando inteligencia artificial/);
});

test('the finance screen follows the provisional client formula', () => {
    assert.match(view, /Ventas del día/);
    assert.match(view, /Costo de ventas/);
    assert.match(view, /Utilidad bruta/);
    assert.match(view, /EBITDA provisional/);
    assert.match(view, /Según fórmula del cliente/);
    assert.match(view, /Punto de equilibrio/);
    assert.doesNotMatch(view, /recuperación de inversión|<small>ROI<\/small>/i);
    assert.match(script, /grossProfit - operatingExpenses - administrativeExpenses/);
});

test('the guided tour covers the complete business journey', () => {
    const tourViews = [...script.matchAll(/view: '([^']+)',\s+title:/g)].map((match) => match[1]);
    const highlightTargets = [...script.matchAll(/\{ target: '([^']+)', description:/g)];

    assert.deepEqual(tourViews, views);
    assert.equal(highlightTargets.length, 27);
    highlightTargets.forEach((match) => {
        assert.match(match[1], /^\[data-view="/);
    });
    assert.match(view, /id="startTour"/);
    assert.match(view, /id="tourPanel"/);
    assert.match(view, /id="tourCard"/);
    assert.match(view, /id="tourSpotlight"/);
    assert.match(view, /id="tourFocusDescription"/);
    assert.match(view, /id="previousTourStep"/);
    assert.match(view, /id="nextTourStep"/);
    assert.match(view, /id="toggleTourPlayback"/);

    [
        'diagnosis-panel',
        'objective-hero',
        'eisenhowerGrid',
        'finance-grid',
        'crm-table-panel',
        'summary-hero'
    ].forEach((target) => {
        assert.match(view, new RegExp(target));
    });
});

test('the spotlight uses one composited layer without curtain seams', () => {
    assert.doesNotMatch(view, /data-tour-curtain/);
    assert.doesNotMatch(script, /tourCurtains/);
    assert.doesNotMatch(styles, /\.tour-curtain/);
    assert.match(styles, /0 0 0 9999px rgba\(8, 26, 31, \.76\)/);
    assert.match(styles, /will-change: top, left, width, height/);
});

test('the tour supports automatic playback, keyboard exit and reduced motion', () => {
    assert.match(script, /const tourHighlightReadingDuration = 6000/);
    assert.match(script, /const tourCardTransitionDuration = 450/);
    assert.match(script, /\}, tourHighlightReadingDuration\)/);
    assert.doesNotMatch(script, /tourHighlightTimer/);
    assert.doesNotMatch(script, /\}, 7000\)/);
    assert.doesNotMatch(script, /\}, 2200\)/);
    assert.match(script, /event\.key === 'Escape'/);
    assert.match(script, /event\.key === 'ArrowRight'/);
    assert.match(script, /prefers-reduced-motion: reduce/);
    assert.match(script, /trapTourFocus/);
    assert.match(script, /positionTourSpotlight/);
    assert.match(script, /positionTourCard/);
    assert.match(script, /renderTourHighlight/);
});

test('the completed tour ends once and opens the validation questions', () => {
    assert.equal((script.match(/renderTourStep\(0\)/g) || []).length, 1);
    assert.match(script, /function completeTour\(\) \{\s*stopTour\(\);\s*openModal\('validationModal'\);/);
    assert.match(script, /tourIndex >= tourSteps\.length - 1[\s\S]*completeTour\(\)/);
    assert.match(view, /id="validationModal"/);
    assert.match(view, /Preguntas para cerrar la presentación/);
});

test('each screen announces its chapter while the sidebar stays visible', () => {
    assert.match(view, /id="tourChapter"/);
    assert.match(view, /id="tourChapterTitle"/);
    ['Mi negocio', 'Objetivos', 'Prioridades', 'Finanzas', 'Clientes y ventas', 'Resumen'].forEach((title) => {
        assert.match(script, new RegExp(title));
    });
    assert.match(script, /const tourChapterDuration = 2200/);
    assert.match(script, /showTourChapter\(step\.view\)/);
    assert.match(styles, /@keyframes tour-chapter-pulse/);
    assert.match(styles, /body\.tour-is-active \.sidebar[\s\S]*z-index: 323/);
    assert.match(styles, /body\.tour-is-active \.nav-item:not\(\.is-active\)[\s\S]*filter: blur\(1\.25px\)/);
    assert.match(styles, /margin-top: -9vh/);
});

test('the explanation card is separate from the compact non-modal controls', () => {
    assert.match(view, /<article class="tour-card"[^>]+role="status"/);
    assert.match(view, /<section class="tour-panel"[^>]+role="region"/);
    assert.doesNotMatch(view, /class="tour-panel"[^>]+aria-modal="true"/);
    assert.match(script, /tourCard\.classList\.add\('is-leaving'\)/);
});
