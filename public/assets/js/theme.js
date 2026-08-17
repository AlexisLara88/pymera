'use strict';

(() => {
    const storageKey = 'pyme_erp_lite_theme';
    const allowedPreferences = new Set(['light', 'dark']);
    const root = document.documentElement;
    const systemPreference = window.matchMedia('(prefers-color-scheme: dark)');

    const readPreference = () => {
        try {
            const stored = window.localStorage.getItem(storageKey);

            return allowedPreferences.has(stored)
                ? stored
                : (systemPreference.matches ? 'dark' : 'light');
        } catch (error) {
            return systemPreference.matches ? 'dark' : 'light';
        }
    };

    const applyTheme = (theme) => {
        const safeTheme = allowedPreferences.has(theme)
            ? theme
            : (systemPreference.matches ? 'dark' : 'light');

        root.dataset.themePreference = safeTheme;
        root.dataset.theme = safeTheme;
        root.style.colorScheme = safeTheme === 'light' ? 'only light' : 'only dark';

        return safeTheme;
    };

    const saveTheme = (theme) => {
        const safeTheme = allowedPreferences.has(theme)
            ? theme
            : (systemPreference.matches ? 'dark' : 'light');

        try {
            window.localStorage.setItem(storageKey, safeTheme);
        } catch (error) {
            // The selected theme still applies for the current document.
        }

        return applyTheme(safeTheme);
    };

    window.addEventListener('storage', (event) => {
        if (event.key === storageKey) {
            applyTheme(readPreference());
        }
    });

    const initialTheme = readPreference();

    try {
        window.localStorage.setItem(storageKey, initialTheme);
    } catch (error) {
        // Local persistence is optional; the current page remains usable.
    }

    applyTheme(initialTheme);
    root.dataset.themeReady = 'true';

    window.PymeTheme = {
        applyTheme,
        readPreference,
        saveTheme,
    };
})();
