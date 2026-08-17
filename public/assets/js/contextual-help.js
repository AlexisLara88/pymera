'use strict';

const contextualHelps = [...document.querySelectorAll('[data-context-help]')];
const moduleMain = document.querySelector('.module-main');
const backdrop = document.createElement('div');
const closeTransitionDuration = 220;
let openContextualHelp = null;
let activeFocusTarget = null;

backdrop.className = 'context-help-backdrop';
backdrop.setAttribute('aria-hidden', 'true');

if (contextualHelps.length > 0) {
    document.body.append(backdrop);
}

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

    const viewportGap = 12;
    const triggerRect = trigger.getBoundingClientRect();
    const cardRect = card.getBoundingClientRect();
    const mainRect = moduleMain?.getBoundingClientRect();
    const minimumLeft = Math.max(viewportGap, (mainRect?.left ?? 0) + viewportGap);
    const rightBoundary = Math.min(
        window.innerWidth - viewportGap,
        (mainRect?.right ?? window.innerWidth) - viewportGap,
    );
    const centeredLeft = triggerRect.left + (triggerRect.width / 2) - (cardRect.width / 2);
    const maximumLeft = Math.max(minimumLeft, rightBoundary - cardRect.width);
    const left = Math.min(Math.max(centeredLeft, minimumLeft), maximumLeft);
    const below = triggerRect.bottom + viewportGap;
    const above = triggerRect.top - cardRect.height - viewportGap;
    const maximumTop = Math.max(viewportGap, window.innerHeight - cardRect.height - viewportGap);
    const top = below + cardRect.height <= window.innerHeight - viewportGap
        ? below
        : Math.min(Math.max(above, viewportGap), maximumTop);

    help.style.setProperty('--context-help-left', `${Math.round(left)}px`);
    help.style.setProperty('--context-help-top', `${Math.round(top)}px`);

    window.requestAnimationFrame(() => {
        if (help.open) {
            help.classList.add('is-positioned');
        }
    });
};

const clearFocusTarget = () => {
    activeFocusTarget?.classList.remove('is-context-help-focus');
    activeFocusTarget = null;
};

const closeHelp = (help, restoreFocus = false, immediately = false) => {
    if (!help) {
        return;
    }

    help.classList.remove('is-positioned');
    setExpanded(help, false);

    if (openContextualHelp === help) {
        openContextualHelp = null;
        document.body.classList.remove('context-help-is-open');
        clearFocusTarget();
    }

    const finishClosing = () => {
        help.open = false;

        if (restoreFocus) {
            helpTrigger(help)?.focus();
        }
    };

    if (immediately || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        finishClosing();
        return;
    }

    window.setTimeout(finishClosing, closeTransitionDuration);
};

contextualHelps.forEach((help) => {
    help.classList.add('is-enhanced');
    setExpanded(help, help.open);

    helpTrigger(help)?.addEventListener('click', (event) => {
        if (help.open) {
            event.preventDefault();
            closeHelp(help, true);
        }
    });

    help.addEventListener('toggle', () => {
        setExpanded(help, help.open);

        if (!help.open) {
            if (openContextualHelp === help) {
                openContextualHelp = null;
            }

            return;
        }

        if (openContextualHelp && openContextualHelp !== help) {
            closeHelp(openContextualHelp, false, true);
        }

        openContextualHelp = help;
        activeFocusTarget = help.closest('[data-context-help-focus-target]');
        activeFocusTarget?.classList.add('is-context-help-focus');
        document.body.classList.add('context-help-is-open');
        positionHelp(help);
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
