'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');

const projectRoot = path.resolve(__dirname, '../..');
const sidebar = fs.readFileSync(
    path.join(projectRoot, 'app/Views/layouts/alpha_sidebar.php'),
    'utf8',
);
const topbar = fs.readFileSync(
    path.join(projectRoot, 'app/Views/layouts/alpha_topbar.php'),
    'utf8',
);
const stylesheet = fs.readFileSync(
    path.join(projectRoot, 'public/assets/css/alpha-shell.css'),
    'utf8',
);
const shellScript = fs.readFileSync(
    path.join(projectRoot, 'public/assets/js/alpha-shell.js'),
    'utf8',
);
const themeHead = fs.readFileSync(
    path.join(projectRoot, 'app/Views/layouts/theme_head.php'),
    'utf8',
);
const projectHead = fs.readFileSync(
    path.join(projectRoot, 'app/Views/layouts/alpha_project_head.php'),
    'utf8',
);
const brandSymbol = fs.readFileSync(
    path.join(projectRoot, 'public/assets/brand/pymera-symbol.svg'),
    'utf8',
);
const brandCompact = fs.readFileSync(
    path.join(projectRoot, 'public/assets/brand/pymera-compact.svg'),
    'utf8',
);
const brandHorizontal = fs.readFileSync(
    path.join(projectRoot, 'public/assets/brand/pymera-horizontal.svg'),
    'utf8',
);
const moduleViews = [
    'app/Views/dashboard/index.php',
    'app/Views/business/profile.php',
    'app/Views/objectives/index.php',
    'app/Views/priorities/index.php',
    'app/Views/finances/index.php',
].map((file) => fs.readFileSync(path.join(projectRoot, file), 'utf8'));

test('every functional module uses the shared alpha shell and cache-busted stylesheet', () => {
    for (const view of moduleViews) {
        assert.match(view, /view\('layouts\/alpha_sidebar'/);
        assert.match(view, /view\('layouts\/alpha_topbar'/);
        assert.match(view, /'alpha-shell\.css'/);
        assert.match(view, /view\('layouts\/alpha_(?:frontend|project)_head'/);
    }

    assert.match(projectHead, /filemtime\(\$absoluteStyle\)/);
    assert.match(projectHead, /view\('layouts\/theme_head'\)/);
});

test('the shared sidebar exposes the complete functional route and a protected logout', () => {
    for (const route of ['app/mi-negocio', 'app/objetivos', 'app/prioridades', 'app/finanzas']) {
        assert.match(sidebar, new RegExp(route));
    }

    assert.doesNotMatch(sidebar, /Alfa de validación|alpha-environment/);
    assert.match(sidebar, /'dashboard'\s*=>\s*\['Inicio', 'Vista general', 'app', true\]/);
    assert.match(sidebar, /Configurar negocio/);
    assert.doesNotMatch(sidebar, /'Mi negocio'/);
    assert.match(sidebar, /class="business-switcher/);
    assert.match(sidebar, /href="<\?= site_url\('app\/mi-negocio'\) \?>"/);
    assert.match(sidebar, /Perfil del negocio/);
    assert.doesNotMatch(sidebar, /nav-icon|\['0[0-9]'/);
    assert.match(sidebar, /Clientes y ventas/);
    assert.doesNotMatch(sidebar, /'summary'/);
    assert.match(sidebar, /aria-disabled="true"/);
    assert.match(sidebar, /method="post"/);
    assert.match(sidebar, /site_url\('logout'\)/);
    assert.match(sidebar, /csrf_field\(\)/);
    assert.doesNotMatch(topbar, /Alfa funcional|plan-badge/);
    assert.match(topbar, /Cuenta del negocio/);
    assert.doesNotMatch(topbar, /notification-button|notification-icon|Notificaciones|Sin notificaciones/);
    assert.doesNotMatch(stylesheet, /\.notification-button|\.notification-icon/);
    assert.doesNotMatch(topbar, /theme_selector|data-theme-toggle/);
    assert.match(themeHead, /theme\.css\?v=/);
    assert.match(themeHead, /theme\.js\?v=/);
});

test('the functional shell preserves the approved demo design tokens and responsive layout', () => {
    for (const token of ['#123e45', '#1c666a', '#e7895c', '258px']) {
        assert.match(stylesheet, new RegExp(token));
    }

    assert.match(stylesheet, /position:\s*sticky/);
    assert.match(stylesheet, /width:\s*min\(1500px,\s*100%\)/);
    assert.match(stylesheet, /\.button-primary\s*{[\s\S]*background:\s*var\(--brand\)/);
    assert.match(stylesheet, /\.alpha-finance-workspace/);
    assert.match(stylesheet, /\.alpha-business-overview/);
    assert.match(stylesheet, /@media \(max-width:\s*820px\)/);
    assert.match(stylesheet, /@media \(max-width:\s*720px\)/);
    assert.match(stylesheet, /prefers-reduced-motion/);
    assert.doesNotMatch(stylesheet, /Georgia/);

    const openBraces = (stylesheet.match(/{/g) || []).length;
    const closeBraces = (stylesheet.match(/}/g) || []).length;
    assert.equal(openBraces, closeBraces);
});

test('PyMERA has a reusable modular SVG identity across the functional product', () => {
    assert.match(sidebar, /aria-label="Inicio de PyMERA"/);
    assert.match(sidebar, /assets\/brand\/pymera-symbol\.svg/);
    assert.match(sidebar, /<strong>PyMERA<\/strong>/);
    assert.match(sidebar, /<small>Gestión simple<\/small>/);
    assert.match(projectHead, /rel="icon"/);
    assert.match(projectHead, /pymera-symbol\.svg/);

    for (const asset of [brandSymbol, brandCompact, brandHorizontal]) {
        assert.match(asset, /<svg/);
        assert.match(asset, /#0f6f68/);
        assert.match(asset, /#17343a/);
        assert.match(asset, /#e87932/);
    }

    assert.match(brandCompact, /PyME/);
    assert.match(brandCompact, />RA</);
    assert.match(brandHorizontal, /Gestión simple para hacer avanzar tu negocio/);
});

test('the mobile shell uses an accessible drawer without client-side persistence', () => {
    assert.match(topbar, /data-toggle-alpha-menu/);
    assert.match(sidebar, /data-close-alpha-menu/);
    assert.match(shellScript, /alpha-menu-is-open/);
    assert.match(shellScript, /aria-expanded/);
    assert.match(shellScript, /event\.key === 'Escape'/);
    assert.doesNotMatch(shellScript, /fetch\(|XMLHttpRequest|localStorage|sessionStorage/);
});
