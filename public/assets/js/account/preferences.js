'use strict';

document.addEventListener('DOMContentLoaded', () => {
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
});
