<?php

/**
 * Resolves the visual theme before the document is painted and then loads the
 * shared controller. The OS only supplies the initial value on a first visit.
 */
?>
<link rel="stylesheet" href="<?= base_url('assets/css/theme.css?v=' . filemtime(FCPATH . 'assets/css/theme.css')) ?>">
<script>
(() => {
    const root = document.documentElement;
    const storageKey = 'pyme_erp_lite_theme';
    const allowed = new Set(['light', 'dark']);
    const systemIsDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches === true;
    let preference = systemIsDark ? 'dark' : 'light';

    try {
        const stored = window.localStorage.getItem(storageKey);
        preference = allowed.has(stored) ? stored : preference;

        if (! allowed.has(stored)) {
            window.localStorage.setItem(storageKey, preference);
        }
    } catch (error) {
        // The resolved first-visit theme still applies to the current page.
    }

    root.dataset.themePreference = preference;
    root.dataset.theme = preference;
    root.style.colorScheme = preference === 'light' ? 'only light' : 'only dark';
})();
</script>
<script src="<?= base_url('assets/js/theme.js?v=' . filemtime(FCPATH . 'assets/js/theme.js')) ?>" defer></script>
