'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');

const projectRoot = path.resolve(__dirname, '../..');
const view = fs.readFileSync(
    path.join(projectRoot, 'app/Views/auth/login.php'),
    'utf8',
);
const css = fs.readFileSync(
    path.join(projectRoot, 'public/assets/css/auth/login.css'),
    'utf8',
);
const themeCss = fs.readFileSync(
    path.join(projectRoot, 'public/assets/css/theme.css'),
    'utf8',
);

test('custom login remains closed, Spanish and CSRF protected', () => {
    assert.match(view, /lang="es"/);
    assert.match(view, /id="loginTitle">Ingresar</);
    assert.doesNotMatch(view, /Entorno alfa de validación|auth-footer/);
    assert.match(view, /Correo electrónico/);
    assert.match(view, /csrf_field\(\)/);
    assert.match(view, /autocomplete="current-password"/);
    assert.doesNotMatch(view, /register|magic-link|remember|recuperación automática|cuenta demostrativa asignada/);
    assert.doesNotMatch(view, /Este acceso está reservado para invitados/);
    assert.match(view, /class="auth-scope"/);
    assert.match(view, /© <\?= date\('Y'\) \?> PyMERA\. Todos los derechos reservados\./);
    assert.match(view, /Ingresar — PyMERA/);
    assert.match(view, /assets\/brand\/pymera-symbol\.svg/);
    assert.match(view, /<div class="auth-brand"/);
    assert.doesNotMatch(view, /<a class="auth-brand"|auth-brand[^>]+href=/);
    assert.match(view, /<strong>PyMERA<\/strong>/);
    assert.match(view, /Gestión simple para tu negocio/);
    assert.match(view, /prioridades y finanzas conectadas/);
    assert.doesNotMatch(view, /finanzas básicas conectadas/);
    assert.match(css, /\.auth-brand > span:first-child\s*\{[\s\S]*?width:\s*5\.25rem[\s\S]*?height:\s*5\.25rem/);
    assert.match(css, /\.auth-brand strong\s*\{[\s\S]*?font-size:\s*1\.65rem/);
});

test('custom login has responsive and reduced-motion rules', () => {
    assert.match(css, /@media \(max-width: 820px\)/);
    assert.match(css, /@media \(max-width: 560px\)/);
    assert.match(css, /prefers-reduced-motion/);
});

test('login credentials use the approved night indigo in both themes and autofill', () => {
    assert.match(css, /--color-night-indigo:\s*#1A365D/);
    assert.match(css, /\.auth-field input\s*\{[\s\S]*?background:\s*var\(--color-night-indigo\)/);
    assert.match(css, /\.auth-field input::placeholder/);
    assert.match(css, /\.auth-field input:-webkit-autofill/);
    assert.match(css, /box-shadow:\s*0 0 0 1000px var\(--color-night-indigo\) inset/);
    assert.match(themeCss, /html\[data-theme="dark"\] \.auth-field input\s*\{[\s\S]*?background:\s*var\(--color-night-indigo, #1A365D\)/);
});
