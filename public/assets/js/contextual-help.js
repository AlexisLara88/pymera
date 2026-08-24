'use strict';

const contextualHelps = [...document.querySelectorAll('[data-context-help]')];
const moduleMain = document.querySelector('.module-main');
const backdrop = document.createElement('div');
const closeTransitionDuration = 220;
const viewportGap = 12;
let openContextualHelp = null;
let activeFocusTarget = null;
let manuallyPositionedHelp = null;
let dragState = null;

backdrop.className = 'context-help-backdrop';
backdrop.setAttribute('aria-hidden', 'true');

if (contextualHelps.length > 0) {
    document.body.append(backdrop);
}

const helpTrigger = (help) => help.querySelector('.context-help-trigger');
const helpCard = (help) => help.querySelector('.context-help-card');
const clamp = (value, minimum, maximum) => Math.min(Math.max(value, minimum), maximum);

const positionBounds = (card) => {
    const mainRect = moduleMain?.getBoundingClientRect();
    const minimumLeft = Math.max(viewportGap, (mainRect?.left ?? 0) + viewportGap);
    const rightBoundary = Math.min(
        window.innerWidth - viewportGap,
        (mainRect?.right ?? window.innerWidth) - viewportGap,
    );

    return {
        minimumLeft,
        maximumLeft: Math.max(minimumLeft, rightBoundary - card.offsetWidth),
        minimumTop: viewportGap,
        maximumTop: Math.max(viewportGap, window.innerHeight - card.offsetHeight - viewportGap),
    };
};

const setHelpPosition = (help, left, top) => {
    help.style.setProperty('--context-help-left', `${Math.round(left)}px`);
    help.style.setProperty('--context-help-top', `${Math.round(top)}px`);
};

const setExpanded = (help, expanded) => {
    helpTrigger(help)?.setAttribute('aria-expanded', expanded ? 'true' : 'false');
};

const positionHelp = (help) => {
    const trigger = helpTrigger(help);
    const card = helpCard(help);

    if (!trigger || !card || !help.open) {
        return;
    }

    const triggerRect = trigger.getBoundingClientRect();
    const cardRect = card.getBoundingClientRect();
    const bounds = positionBounds(card);
    const sideTop = clamp(triggerRect.top - 8, bounds.minimumTop, bounds.maximumTop);
    const centeredLeft = triggerRect.left + (triggerRect.width / 2) - (cardRect.width / 2);
    const candidates = [
        { left: triggerRect.right + viewportGap, top: sideTop },
        { left: triggerRect.left - cardRect.width - viewportGap, top: sideTop },
        { left: centeredLeft, top: triggerRect.bottom + viewportGap },
        { left: centeredLeft, top: triggerRect.top - cardRect.height - viewportGap },
    ];
    const candidateFits = (candidate) => (
        candidate.left >= bounds.minimumLeft
        && candidate.left <= bounds.maximumLeft
        && candidate.top >= bounds.minimumTop
        && candidate.top <= bounds.maximumTop
    );
    const candidate = candidates.find(candidateFits) ?? candidates[0];

    setHelpPosition(
        help,
        clamp(candidate.left, bounds.minimumLeft, bounds.maximumLeft),
        clamp(candidate.top, bounds.minimumTop, bounds.maximumTop),
    );

    window.requestAnimationFrame(() => {
        if (help.open) {
            help.classList.add('is-positioned');
        }
    });
};

const constrainHelpPosition = (help) => {
    const card = helpCard(help);

    if (!card || !help.open) {
        return;
    }

    const cardRect = card.getBoundingClientRect();
    const bounds = positionBounds(card);

    setHelpPosition(
        help,
        clamp(cardRect.left, bounds.minimumLeft, bounds.maximumLeft),
        clamp(cardRect.top, bounds.minimumTop, bounds.maximumTop),
    );
};

const stopHelpDrag = () => {
    if (!dragState) {
        return;
    }

    const { handle, help, pointerId } = dragState;

    if (handle.hasPointerCapture?.(pointerId)) {
        handle.releasePointerCapture(pointerId);
    }

    help.classList.remove('is-dragging');
    dragState = null;
};

const startHelpDrag = (event, help, handle) => {
    if ((event.pointerType === 'mouse' && event.button !== 0)
        || event.target.closest('[data-context-help-close]')) {
        return;
    }

    const card = helpCard(help);

    if (!card || !help.open) {
        return;
    }

    const cardRect = card.getBoundingClientRect();

    dragState = {
        help,
        handle,
        pointerId: event.pointerId,
        offsetX: event.clientX - cardRect.left,
        offsetY: event.clientY - cardRect.top,
    };
    manuallyPositionedHelp = help;
    help.classList.add('is-dragging');
    handle.setPointerCapture?.(event.pointerId);
    event.preventDefault();
};

const moveHelp = (event) => {
    if (!dragState || dragState.pointerId !== event.pointerId) {
        return;
    }

    const card = helpCard(dragState.help);

    if (!card) {
        stopHelpDrag();
        return;
    }

    const bounds = positionBounds(card);
    const left = clamp(event.clientX - dragState.offsetX, bounds.minimumLeft, bounds.maximumLeft);
    const top = clamp(event.clientY - dragState.offsetY, bounds.minimumTop, bounds.maximumTop);

    setHelpPosition(dragState.help, left, top);
    event.preventDefault();
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

    if (dragState?.help === help) {
        stopHelpDrag();
    }

    if (manuallyPositionedHelp === help) {
        manuallyPositionedHelp = null;
    }
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
        manuallyPositionedHelp = null;
        const explicitTargetId = help.dataset.contextHelpTarget;

        activeFocusTarget = explicitTargetId
            ? document.getElementById(explicitTargetId)
            : help.closest('[data-context-help-focus-target]');
        activeFocusTarget?.classList.add('is-context-help-focus');
        document.body.classList.add('context-help-is-open');
        positionHelp(help);
    });

    help.querySelector('[data-context-help-close]')?.addEventListener('click', () => {
        closeHelp(help, true);
    });

    const dragHandle = help.querySelector('[data-context-help-drag-handle]');

    dragHandle?.addEventListener('pointerdown', (event) => startHelpDrag(event, help, dragHandle));
    dragHandle?.addEventListener('pointermove', moveHelp);
    dragHandle?.addEventListener('pointerup', stopHelpDrag);
    dragHandle?.addEventListener('pointercancel', stopHelpDrag);
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
        if (manuallyPositionedHelp === openContextualHelp) {
            constrainHelpPosition(openContextualHelp);
        } else {
            positionHelp(openContextualHelp);
        }
    }
};

window.addEventListener('resize', repositionOpenHelp);
window.addEventListener('scroll', () => {
    if (openContextualHelp && manuallyPositionedHelp !== openContextualHelp) {
        positionHelp(openContextualHelp);
    }
}, { passive: true });
