'use strict';

document.addEventListener('DOMContentLoaded', () => {
    initializeSettingsTabs();
    initializeThemePreferences();
    initializePasswordForm();
});

function initializeSettingsTabs() {
    const settings = document.querySelector('[data-account-settings]');

    if (! settings) {
        return;
    }

    const tabs = Array.from(settings.querySelectorAll('[data-settings-tab]'));
    const panels = Array.from(settings.querySelectorAll('[data-settings-panel]'));

    if (tabs.length === 0 || panels.length === 0) {
        return;
    }

    const availableTabs = tabs.map((tab) => tab.dataset.settingsTab);
    const requestedTab = settings.dataset.initialTab;
    const initialTab = availableTabs.includes(requestedTab)
        ? requestedTab
        : availableTabs[0];

    const activateTab = (tabName, moveFocus = false) => {
        if (! availableTabs.includes(tabName)) {
            return;
        }

        tabs.forEach((tab) => {
            const isActive = tab.dataset.settingsTab === tabName;

            tab.setAttribute('aria-selected', String(isActive));
            tab.tabIndex = isActive ? 0 : -1;

            if (isActive && moveFocus) {
                tab.focus({ preventScroll: true });
            }
        });

        panels.forEach((panel) => {
            panel.hidden = panel.dataset.settingsPanel !== tabName;
        });

        settings.dataset.activeTab = tabName;
    };

    settings.classList.add('is-enhanced');
    activateTab(initialTab);

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => {
            activateTab(tab.dataset.settingsTab);
        });

        tab.addEventListener('keydown', (event) => {
            let nextIndex = null;

            if (event.key === 'ArrowRight') {
                nextIndex = (index + 1) % tabs.length;
            } else if (event.key === 'ArrowLeft') {
                nextIndex = (index - 1 + tabs.length) % tabs.length;
            } else if (event.key === 'Home') {
                nextIndex = 0;
            } else if (event.key === 'End') {
                nextIndex = tabs.length - 1;
            }

            if (nextIndex === null) {
                return;
            }

            event.preventDefault();
            activateTab(tabs[nextIndex].dataset.settingsTab, true);
        });
    });
}

function initializeThemePreferences() {
    const form = document.querySelector('[data-preferences-form]');
    const options = Array.from(
        document.querySelectorAll('input[name="appearance_theme"]'),
    );
    const theme = window.PymeTheme?.readPreference();

    if (! form || options.length === 0) {
        return;
    }

    if (theme === 'light' || theme === 'dark') {
        const current = options.find((option) => option.value === theme);

        if (current) {
            current.checked = true;
        }
    }

    options.forEach((option) => {
        option.addEventListener('change', () => {
            if (option.checked) {
                window.PymeTheme?.applyTheme(option.value);
            }
        });
    });

    form.addEventListener('submit', () => {
        const selected = options.find((option) => option.checked);

        if (selected) {
            window.PymeTheme?.saveTheme(selected.value);
        }
    });
}

function initializePasswordForm() {
    const form = document.querySelector('[data-password-form]');

    if (! form) {
        return;
    }

    const currentPassword = form.querySelector('#currentPassword');
    const newPassword = form.querySelector('#newPassword');
    const confirmation = form.querySelector('#newPasswordConfirmation');
    const newPasswordFeedback = form.querySelector('#newPasswordFeedback');
    const confirmationFeedback = form.querySelector('#newPasswordConfirmationFeedback');

    if (! currentPassword || ! newPassword || ! confirmation) {
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

    const validateNewPassword = () => {
        const value = newPassword.value;
        const characterCount = Array.from(value).length;
        const minimumLength = newPassword.minLength > 0 ? newPassword.minLength : 8;
        let message = '';

        if (value === '') {
            newPassword.setCustomValidity('');
            setState(newPassword, newPasswordFeedback, 'neutral', '');

            return false;
        }

        if (characterCount < minimumLength) {
            const remaining = minimumLength - characterCount;
            message = remaining === 1
                ? 'Falta 1 carácter para completar el mínimo.'
                : `Faltan ${remaining} caracteres para completar el mínimo.`;
            newPassword.setCustomValidity(message);
            setState(newPassword, newPasswordFeedback, 'invalid', message);

            return false;
        }

        if (currentPassword.value !== '' && value === currentPassword.value) {
            message = 'La contraseña nueva debe ser diferente de la actual.';
            newPassword.setCustomValidity(message);
            setState(newPassword, newPasswordFeedback, 'invalid', message);

            return false;
        }

        newPassword.setCustomValidity('');
        setState(
            newPassword,
            newPasswordFeedback,
            'valid',
            'Cumple la longitud mínima. La validación final se realizará al guardar.',
        );

        return true;
    };

    const validateConfirmation = (newPasswordIsValid) => {
        const value = confirmation.value;
        let message = '';

        if (value === '') {
            confirmation.setCustomValidity('');
            setState(confirmation, confirmationFeedback, 'neutral', '');

            return false;
        }

        if (value !== newPassword.value) {
            message = 'Las contraseñas no coinciden.';
            confirmation.setCustomValidity(message);
            setState(confirmation, confirmationFeedback, 'invalid', message);

            return false;
        }

        if (! newPasswordIsValid) {
            message = 'Primero corregí la contraseña nueva.';
            confirmation.setCustomValidity(message);
            setState(confirmation, confirmationFeedback, 'invalid', message);

            return false;
        }

        confirmation.setCustomValidity('');
        setState(confirmation, confirmationFeedback, 'valid', 'Las contraseñas coinciden.');

        return true;
    };

    const validatePair = () => {
        const newPasswordIsValid = validateNewPassword();

        return validateConfirmation(newPasswordIsValid);
    };

    [currentPassword, newPassword, confirmation].forEach((input) => {
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
