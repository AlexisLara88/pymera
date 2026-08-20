'use strict';

document.addEventListener('DOMContentLoaded', () => {
    initializeOwnerDialog();
    initializeDisabledFeatureNotice();
    initializeOwnerPasswordForm();
});

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
