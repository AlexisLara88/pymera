'use strict';

document.querySelectorAll('[data-character-count]').forEach((field) => {
    const output = field.parentElement?.querySelector('[data-character-output]');

    if (!output) {
        return;
    }

    const updateCount = () => {
        const maximum = Number(field.getAttribute('maxlength')) || 5000;
        output.textContent = `${field.value.length} / ${maximum}`;
    };

    field.addEventListener('input', updateCount);
    updateCount();
});

document.querySelectorAll('.workflow-form, .archive-form').forEach((form) => {
    form.addEventListener('submit', () => {
        const submit = form.querySelector('button[type="submit"]');

        if (!submit) {
            return;
        }

        submit.disabled = true;
        submit.setAttribute('aria-busy', 'true');

        if (submit.classList.contains('text-danger-button')) {
            submit.textContent = 'Archivando…';
        } else {
            submit.textContent = 'Guardando…';
        }
    });
});

const activeForm = document.body.dataset.activeForm;

if (activeForm && activeForm !== 'create-objective') {
    const target = document.getElementById(activeForm);

    target?.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
    });
}

const objectiveCreatorRoot = document.getElementById('objectiveCreatorApp');
let objectiveCreator = null;

if (objectiveCreatorRoot && window.Vue) {
    objectiveCreator = window.Vue.createApp({
        data() {
            return {
                isOpen: false,
                returnFocusTo: null,
            };
        },
        mounted() {
            if (objectiveCreatorRoot.dataset.initialOpen === 'true') {
                this.openCreator();
                objectiveCreatorRoot.dataset.initialOpen = 'false';
            }
        },
        beforeUnmount() {
            this.releaseBackground();
        },
        methods: {
            backgroundElements() {
                return document.querySelectorAll([
                    '.business-shell > :not(.module-main)',
                    '.module-main > :not(.objective-creator-app)',
                ].join(', '));
            },
            openCreator(trigger = null) {
                if (this.isOpen) {
                    return;
                }

                this.returnFocusTo = trigger;
                this.isOpen = true;
                document.body.classList.add('objective-modal-is-open');
                this.backgroundElements().forEach((element) => {
                    element.inert = true;
                });
                this.$nextTick(() => {
                    this.$refs.dialogPanel
                        ?.querySelector('input, select, textarea')
                        ?.focus();
                });
            },
            closeCreator() {
                if (!this.isOpen) {
                    return;
                }

                this.isOpen = false;
                this.releaseBackground();

                if (window.location.hash === '#objectiveCreator') {
                    window.history.replaceState(null, '', window.location.pathname + window.location.search);
                }

                this.$nextTick(() => {
                    this.returnFocusTo?.focus();
                    this.returnFocusTo = null;
                });
            },
            handleKeydown(event) {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    this.closeCreator();

                    return;
                }

                if (event.key !== 'Tab') {
                    return;
                }

                const focusable = [...this.$refs.dialogPanel.querySelectorAll([
                    'a[href]',
                    'button:not([disabled])',
                    'input:not([disabled])',
                    'select:not([disabled])',
                    'textarea:not([disabled])',
                    '[tabindex]:not([tabindex="-1"])',
                ].join(', '))];

                if (focusable.length === 0) {
                    event.preventDefault();
                    this.$refs.dialogPanel.focus();

                    return;
                }

                const first = focusable[0];
                const last = focusable[focusable.length - 1];

                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            },
            releaseBackground() {
                document.body.classList.remove('objective-modal-is-open');
                this.backgroundElements().forEach((element) => {
                    element.inert = false;
                });
            },
        },
    }).mount(objectiveCreatorRoot);
}

document.querySelectorAll('[data-open-objective-creator]').forEach((button) => {
    button.addEventListener('click', (event) => {
        if (!objectiveCreator) {
            return;
        }

        event.preventDefault();
        objectiveCreator.openCreator(button);
    });
});
