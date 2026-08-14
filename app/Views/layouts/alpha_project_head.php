<?php

/**
 * Project-owned styles for the functional alpha.
 *
 * This partial is the only place that resolves cache versions and the global
 * theme after module styles. Bootstrap, when needed, must be loaded before it.
 *
 * @var list<string> $styles Relative paths below public/assets/css.
 */
$styles = $styles ?? [];
?>
<link rel="icon" href="<?= base_url('assets/brand/pymera-symbol.svg') ?>" type="image/svg+xml">
<?php foreach ($styles as $style): ?>
    <?php $absoluteStyle = FCPATH . 'assets/css/' . $style; ?>
    <link
        rel="stylesheet"
        href="<?= base_url('assets/css/' . $style . '?v=' . filemtime($absoluteStyle)) ?>"
    >
<?php endforeach ?>
<?= view('layouts/theme_head') ?>
