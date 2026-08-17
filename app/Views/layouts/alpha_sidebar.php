<?php

/**
 * @var array<string, mixed> $business
 * @var string               $activeModule
 * @var bool|null            $onboarding
 */

$businessName = (string) ($business['name'] ?? 'Dulce Barrio');
$words = preg_split('/\s+/', trim($businessName)) ?: [];
$businessInitial = '';

foreach (array_slice($words, 0, 2) as $word) {
    $businessInitial .= function_exists('mb_substr')
        ? mb_strtoupper(mb_substr($word, 0, 1))
        : strtoupper(substr($word, 0, 1));
}

$businessInitial = $businessInitial !== '' ? $businessInitial : 'N';
$isOnboarding = isset($onboarding) && $onboarding === true;
$navigation = $isOnboarding
    ? [
        'business' => ['Configurar negocio', 'Paso inicial', 'app/mi-negocio', true],
    ]
    : [
        'dashboard'  => ['Inicio', 'Vista general', 'app', true],
        'objectives' => ['Objetivos', 'Plan de acción', 'app/objetivos', true],
        'priorities' => ['Prioridades', 'Matriz Eisenhower', 'app/prioridades', true],
        'finances'   => ['Finanzas', 'Salud del negocio', 'app/finanzas', true],
        'crm'        => ['Clientes y ventas', 'Seguimiento comercial', 'app/clientes', true],
    ];
?>
<aside class="module-sidebar" id="alphaSidebar" aria-label="Navegación principal">
    <a class="brand" href="<?= site_url('app') ?>" aria-label="Inicio de PyMERA">
        <span class="brand-mark" aria-hidden="true">
            <img src="<?= base_url('assets/brand/pymera-symbol.svg') ?>" alt="">
        </span>
        <span><strong>PyMERA</strong><small>Gestión simple</small></span>
    </a>

    <nav class="main-nav" aria-label="Módulos del negocio">
        <?php foreach ($navigation as $module => [$label, $description, $route, $enabled]): ?>
            <?php if ($enabled): ?>
                <a
                    class="module-nav-item<?= $activeModule === $module ? ' is-active' : '' ?>"
                    href="<?= site_url($route) ?>"
                    <?= $activeModule === $module ? 'aria-current="page"' : '' ?>
                >
                    <span><strong><?= esc($label) ?></strong><small><?= esc($description) ?></small></span>
                </a>
            <?php else: ?>
                <span class="module-nav-item is-future" aria-disabled="true">
                    <span><strong><?= esc($label) ?></strong><small><?= esc($description) ?></small></span>
                </span>
            <?php endif ?>
        <?php endforeach ?>
    </nav>

    <div class="sidebar-bottom">
        <a
            class="business-switcher<?= $activeModule === 'business' ? ' is-current' : '' ?>"
            href="<?= site_url('app/mi-negocio') ?>"
            aria-label="Abrir el perfil de <?= esc($businessName) ?>"
            <?= $activeModule === 'business' ? 'aria-current="page"' : '' ?>
        >
            <span class="business-avatar" aria-hidden="true"><?= esc($businessInitial) ?></span>
            <span>
                <strong><?= esc($businessName) ?></strong>
                <small><?= $isOnboarding ? 'Configuración inicial' : 'Perfil del negocio' ?></small>
            </span>
            <span class="demo-chip"><?= $isOnboarding ? 'Inicio' : 'Perfil' ?></span>
        </a>

        <a
            class="account-preferences-link"
            href="<?= site_url('account/preferences') ?>"
        >
            <span aria-hidden="true">⚙</span>
            <span><strong>Mi cuenta</strong><small>Preferencias personales</small></span>
        </a>

        <form class="sidebar-session" action="<?= site_url('logout') ?>" method="post">
            <?= csrf_field() ?>
            <span>Sesión protegida</span>
            <button type="submit">Cerrar sesión</button>
        </form>
    </div>
</aside>
<button class="sidebar-scrim" type="button" data-close-alpha-menu aria-label="Cerrar menú" tabindex="-1"></button>
