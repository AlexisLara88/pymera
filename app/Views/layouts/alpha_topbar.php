<?php

/**
 * @var array<string, mixed> $business
 * @var string               $eyebrow
 * @var string               $title
 */

$businessName = (string) ($business['name'] ?? 'Dulce Barrio');
$businessInitial = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($businessName, 0, 1))
    : strtoupper(substr($businessName, 0, 1));
?>
<header class="module-topbar">
    <button
        class="mobile-menu"
        type="button"
        data-toggle-alpha-menu
        aria-controls="alphaSidebar"
        aria-expanded="false"
        aria-label="Abrir menú"
    >☰</button>
    <div class="topbar-heading">
        <p class="eyebrow"><?= esc($eyebrow) ?></p>
        <h1><?= esc($title) ?></h1>
    </div>
    <div class="topbar-actions">
        <?= view('layouts/theme_selector') ?>
        <div class="user-profile">
            <span class="user-avatar" aria-hidden="true"><?= esc($businessInitial) ?></span>
            <span>
                <strong><?= esc($businessName) ?></strong>
                <small>Cuenta del negocio</small>
            </span>
        </div>
    </div>
</header>
