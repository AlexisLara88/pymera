'use strict';

const contextualHelps = [...document.querySelectorAll('[data-context-help]')];
let openContextualHelp = null;

const helpTrigger = (help) => help.querySelector('.context-help-trigger');
const helpCard = (help) => help.querySelector('.context-help-card');

const setExpanded = (help, expanded) => {
    helpTrigger(help)?.setAttribute('aria-expanded', expanded ? 'true' : 'false');
};

const positionHelp = (help) => {
    const trigger = helpTrigger(help);
    const card = helpCard(help);

    if (!trigger || !card || !help.open) {
        return;
    }

    const viewportGap = 10;
    const triggerRect = trigger.getBoundingClientRect();
    const cardRect = card.getBoundingClientRect();
    const centeredLeft = triggerRect.left + (triggerRect.width / 2) - (cardRect.width / 2);
    const maximumLeft = Math.max(viewportGap, window.innerWidth - cardRect.width - viewportGap);
    const left = Math.min(Math.max(centeredLeft, viewportGap), maximumLeft);
    const below = triggerRect.bottom + viewportGap;
    const above = triggerRect.top - cardRect.height - viewportGap;
    const maximumTop = Math.max(viewportGap, window.innerHeight - cardRect.height - viewportGap);
    const top = below + cardRect.height <= window.innerHeight - viewportGap
        ? below
        : Math.min(Math.max(above, viewportGap), maximumTop);

    help.style.setProperty('--context-help-left', `${Math.round(left)}px`);
    help.style.setProperty('--context-help-top', `${Math.round(top)}px`);
};

const closeHelp = (help, restoreFocus = false) => {
    if (!help) {
        return;
    }

    help.open = false;
    setExpanded(help, false);

    if (openContextualHelp === help) {
        openContextualHelp = null;
    }

    if (restoreFocus) {
        helpTrigger(help)?.focus();
    }
};

contextualHelps.forEach((help) => {
    help.classList.add('is-enhanced');
    setExpanded(help, help.open);

    help.addEventListener('toggle', () => {
        setExpanded(help, help.open);

        if (!help.open) {
            if (openContextualHelp === help) {
                openContextualHelp = null;
            }

            return;
        }

        if (openContextualHelp && openContextualHelp !== help) {
            closeHelp(openContextualHelp);
        }

        openContextualHelp = help;
        window.requestAnimationFrame(() => positionHelp(help));
    });

    help.querySelector('[data-context-help-close]')?.addEventListener('click', () => {
        closeHelp(help, true);
    });
});

document.addEventListener('pointerdown', (event) => {
    if (openContextualHelp && !openContextualHelp.contains(event.target)) {
        closeHelp(openContextualHelp);
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && openContextualHelp) {
        event.preventDefault();
        closeHelp(openContextualHelp, true);
    }
});

const repositionOpenHelp = () => {
    if (openContextualHelp) {
        positionHelp(openContextualHelp);
    }
};

window.addEventListener('resize', repositionOpenHelp);
window.addEventListener('scroll', repositionOpenHelp, { passive: true });
