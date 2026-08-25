'use strict';

document.addEventListener('DOMContentLoaded', () => {
    initializeOwnerDialog();
    initializeDisabledFeatureNotice();
    initializeOwnerPasswordForm();
    initializeStatusConfirmation();
    initializeAccountSearch();
});

function initializeStatusConfirmation() {
    const dialog = document.querySelector('[data-platform-confirm-dialog]');
    const triggers = Array.from(document.querySelectorAll('[data-platform-status-trigger]'));

    if (! dialog || triggers.length === 0) {
        return;
    }

    const title = dialog.querySelector('[data-platform-confirm-title]');
    const description = dialog.querySelector('[data-platform-confirm-description]');
    const confirmButton = dialog.querySelector('[data-platform-confirm-submit]');
    const cancelButtons = dialog.querySelectorAll('[data-platform-confirm-cancel]');
    const primaryCancelButton = dialog.querySelector('.platform-confirm-actions [data-platform-confirm-cancel]');
    let pendingForm = null;
    let opener = null;

    if (! title || ! description || ! confirmButton) {
        return;
    }

    const copyFor = (form) => {
        const scope = form.dataset.platformStatusScope;
        const action = form.dataset.platformStatusAction;
        const user = form.dataset.platformStatusUser || 'esta persona';
        const business = form.dataset.platformStatusBusiness || 'este negocio';

        if (scope === 'account' && action === 'deactivate') {
            return {
                title: `¿Desactivar la cuenta “${user}”?`,
                description: 'La cuenta quedará inactiva, pero sus negocios y datos no se eliminarán.',
                label: 'Desactivar cuenta',
                danger: true,
            };
        }

        if (scope === 'account') {
            return {
                title: `¿Activar la cuenta “${user}”?`,
                description: 'La cuenta volverá a quedar activa. Sus accesos a negocios conservarán su estado actual.',
                label: 'Activar cuenta',
                danger: false,
            };
        }

        if (action === 'pause') {
            return {
                title: `¿Pausar el acceso de “${user}” a “${business}”?`,
                description: 'Sólo se suspenderá este acceso. La cuenta, el negocio y sus datos se conservarán.',
                label: 'Pausar acceso',
                danger: true,
            };
        }

        return {
            title: `¿Activar el acceso de “${user}” a “${business}”?`,
            description: 'La relación con este negocio volverá a quedar activa sin modificar los demás accesos de la cuenta.',
            label: 'Activar acceso',
            danger: false,
        };
    };

    const openDialog = (form, button) => {
        const copy = copyFor(form);

        pendingForm = form;
        opener = button;
        title.textContent = copy.title;
        description.textContent = copy.description;
        confirmButton.textContent = copy.label;
        confirmButton.classList.toggle('button-primary', ! copy.danger);
        confirmButton.classList.toggle('platform-confirm-danger', copy.danger);
        confirmButton.disabled = false;

        if (typeof dialog.showModal === 'function') {
            if (! dialog.open) {
                dialog.showModal();
            }
        } else {
            dialog.setAttribute('open', '');
            dialog.classList.add('is-fallback-open');
        }

        primaryCancelButton?.focus({ preventScroll: true });
    };

    const closeDialog = () => {
        if (typeof dialog.close === 'function' && dialog.open) {
            dialog.close();
        } else {
            dialog.removeAttribute('open');
            dialog.classList.remove('is-fallback-open');
            opener?.focus({ preventScroll: true });
            pendingForm = null;
        }
    };

    triggers.forEach((button) => {
        button.addEventListener('click', () => {
            const form = button.closest('[data-platform-status-form]');

            if (form) {
                openDialog(form, button);
            }
        });
    });

    cancelButtons.forEach((button) => button.addEventListener('click', closeDialog));

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            closeDialog();
        }
    });

    dialog.addEventListener('close', () => {
        opener?.focus({ preventScroll: true });
        pendingForm = null;
    });

    confirmButton.addEventListener('click', () => {
        if (! pendingForm) {
            return;
        }

        confirmButton.disabled = true;
        pendingForm.requestSubmit();
    });
}

function initializeAccountSearch() {
    const input = document.querySelector('[data-platform-account-search]');
    const accounts = Array.from(document.querySelectorAll('[data-platform-account]'));
    const count = document.querySelector('[data-platform-account-count]');
    const empty = document.querySelector('[data-platform-account-empty]');

    if (! input || accounts.length === 0 || ! count || ! empty) {
        return;
    }

    const normalize = (value) => value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase('es')
        .trim();

    const filterAccounts = () => {
        const query = normalize(input.value);
        let visibleAccounts = 0;

        accounts.forEach((account) => {
            const haystack = normalize(account.dataset.platformAccountSearchValue || '');
            const matches = query === '' || haystack.includes(query);

            account.hidden = ! matches;
            visibleAccounts += matches ? 1 : 0;
        });

        count.textContent = visibleAccounts === 1 ? '1 cuenta' : `${visibleAccounts} cuentas`;
        empty.hidden = visibleAccounts !== 0;
    };

    input.addEventListener('input', filterAccounts);
    input.addEventListener('search', filterAccounts);
    filterAccounts();
}

function initializeOwnerDialog() {
    const dialog = document.querySelector('[data-platform-dialog]');

    if (! dialog) {
        return;
    }

    let opener = null;

    const openDialog = (button = null) => {
        opener = button;

        if (typeof dialog.showModal === 'function') {
            if (! dialog.open) {
                dialog.showModal();
            }
        } else {
            dialog.setAttribute('open', '');
            dialog.classList.add('is-fallback-open');
        }

        const firstField = dialog.querySelector('input:not([type="hidden"])');
        firstField?.focus({ preventScroll: true });
    };

    const closeDialog = () => {
        if (typeof dialog.close === 'function' && dialog.open) {
            dialog.close();
        } else {
            dialog.removeAttribute('open');
            dialog.classList.remove('is-fallback-open');
        }

        opener?.focus({ preventScroll: true });
    };

    document.querySelectorAll('[data-platform-dialog-open]').forEach((button) => {
        button.addEventListener('click', () => openDialog(button));
    });

    dialog.querySelectorAll('[data-platform-dialog-close]').forEach((button) => {
        button.addEventListener('click', closeDialog);
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            closeDialog();
        }
    });

    dialog.addEventListener('close', () => {
        opener?.focus({ preventScroll: true });
    });

    if (dialog.dataset.autoOpen === 'true') {
        openDialog();
    }
}

function initializeDisabledFeatureNotice() {
    const button = document.querySelector('[data-platform-disabled-feature]');
    const notice = document.querySelector('[data-platform-feature-notice]');

    if (! button || ! notice) {
        return;
    }

    let hideTimer = null;

    button.addEventListener('click', () => {
        window.clearTimeout(hideTimer);
        notice.hidden = false;

        hideTimer = window.setTimeout(() => {
            notice.hidden = true;
        }, 4200);
    });
}

function initializeOwnerPasswordForm() {
    const form = document.querySelector('[data-owner-creation-form]');

    if (! form) {
        return;
    }

    const password = form.querySelector('#ownerPassword');
    const confirmation = form.querySelector('#ownerPasswordConfirmation');
    const passwordFeedback = form.querySelector('#ownerPasswordFeedback');
    const confirmationFeedback = form.querySelector('#ownerPasswordConfirmationFeedback');

    if (! password || ! confirmation) {
        return;
    }

    const setState = (input, feedback, state, message) => {
        input.classList.toggle('is-valid', state === 'valid');
        input.classList.toggle('is-invalid', state === 'invalid');

        if (state === 'invalid') {
            input.setAttribute('aria-invalid', 'true');
        } else {
            input.removeAttribute('aria-invalid');
        }

        if (feedback) {
            feedback.classList.toggle('is-valid', state === 'valid');
            feedback.classList.toggle('is-invalid', state === 'invalid');
            feedback.textContent = message;
        }
    };

    const validatePassword = () => {
        const characterCount = Array.from(password.value).length;
        const minimumLength = password.minLength > 0 ? password.minLength : 8;

        if (password.value === '') {
            password.setCustomValidity('');
            setState(password, passwordFeedback, 'neutral', '');

            return false;
        }

        if (characterCount < minimumLength) {
            const remaining = minimumLength - characterCount;
            const message = remaining === 1
                ? 'Falta 1 carácter para completar el mínimo.'
                : `Faltan ${remaining} caracteres para completar el mínimo.`;

            password.setCustomValidity(message);
            setState(password, passwordFeedback, 'invalid', message);

            return false;
        }

        password.setCustomValidity('');
        setState(
            password,
            passwordFeedback,
            'valid',
            'Cumple la longitud mínima. La validación final se realizará al guardar.',
        );

        return true;
    };

    const validateConfirmation = (passwordIsValid) => {
        if (confirmation.value === '') {
            confirmation.setCustomValidity('');
            setState(confirmation, confirmationFeedback, 'neutral', '');

            return false;
        }

        if (confirmation.value !== password.value) {
            const message = 'Las contraseñas no coinciden.';

            confirmation.setCustomValidity(message);
            setState(confirmation, confirmationFeedback, 'invalid', message);

            return false;
        }

        if (! passwordIsValid) {
            const message = 'Primero corregí la contraseña.';

            confirmation.setCustomValidity(message);
            setState(confirmation, confirmationFeedback, 'invalid', message);

            return false;
        }

        confirmation.setCustomValidity('');
        setState(confirmation, confirmationFeedback, 'valid', 'Las contraseñas coinciden.');

        return true;
    };

    const validatePair = () => validateConfirmation(validatePassword());

    [password, confirmation].forEach((input) => {
        input.addEventListener('input', validatePair);
        input.addEventListener('blur', validatePair);
    });

    form.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const target = form.querySelector(`#${button.dataset.passwordToggle}`);

        if (! target) {
            return;
        }

        const showLabel = button.getAttribute('aria-label') || 'Mostrar contraseña';
        const hideLabel = showLabel.replace(/^Mostrar/, 'Ocultar');

        button.addEventListener('click', () => {
            const willShow = target.type === 'password';
            const label = willShow ? hideLabel : showLabel;

            target.type = willShow ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(willShow));
            button.setAttribute('aria-label', label);
            button.setAttribute('title', label);
            target.focus({ preventScroll: true });
        });
    });

    form.addEventListener('submit', (event) => {
        const confirmationIsValid = validatePair();

        if (! confirmationIsValid || ! form.checkValidity()) {
            event.preventDefault();
            form.reportValidity();
        }
    });
}
