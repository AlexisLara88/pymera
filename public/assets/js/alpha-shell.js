'use strict';

const body = document.body;
const sidebar = document.getElementById('alphaSidebar');
const toggle = document.querySelector('[data-toggle-alpha-menu]');
const close = document.querySelector('[data-close-alpha-menu]');

const setMenuState = (isOpen) => {
    body.classList.toggle('alpha-menu-is-open', isOpen);
    toggle?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

    if (isOpen) {
        sidebar?.querySelector('a, button')?.focus();
    }
};

toggle?.addEventListener('click', () => {
    setMenuState(!body.classList.contains('alpha-menu-is-open'));
});

close?.addEventListener('click', () => setMenuState(false));

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        setMenuState(false);
    }
});

window.matchMedia('(min-width: 821px)').addEventListener('change', (event) => {
    if (event.matches) {
        setMenuState(false);
    }
});
