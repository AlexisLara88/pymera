<?php

/**
 * Resolves the visual theme before the document is painted and then loads the
 * shared controller. An authenticated account preference wins over the local
 * first-visit value.
 */
$accountTheme = session('pymera_appearance_theme');
$accountTheme = in_array($accountTheme, ['light', 'dark'], true) ? $accountTheme : null;
?>
<link rel="stylesheet" href="<?= base_url('assets/css/theme.css?v=' . filemtime(FCPATH . 'assets/css/theme.css')) ?>">
<script>
(() => {
    const root = document.documentElement;
    const storageKey = 'pyme_erp_lite_theme';
    const allowed = new Set(['light', 'dark']);
    const accountPreference = <?= json_encode($accountTheme, JSON_THROW_ON_ERROR) ?>;
    const systemIsDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches === true;
    let preference = systemIsDark ? 'dark' : 'light';

    try {
        const stored = window.localStorage.getItem(storageKey);
        preference = allowed.has(accountPreference)
            ? accountPreference
            : (allowed.has(stored) ? stored : preference);

        if (! allowed.has(stored) || allowed.has(accountPreference)) {
            window.localStorage.setItem(storageKey, preference);
        }
    } catch (error) {
        preference = allowed.has(accountPreference) ? accountPreference : preference;
    }

    root.dataset.themePreference = preference;
    root.dataset.theme = preference;
    root.style.colorScheme = preference === 'light' ? 'only light' : 'only dark';
})();
</script>
<script src="<?= base_url('assets/js/theme.js?v=' . filemtime(FCPATH . 'assets/js/theme.js')) ?>" defer></script>
