<?php

/**
 * @var string      $username
 * @var string      $email
 * @var string|null $theme
 * @var string      $crmView
 * @var bool        $canConfigureCrm
 * @var string      $returnUrl
 * @var string|null $success
 * @var string|null $error
 */

$initial = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($username, 0, 1))
    : strtoupper(substr($username, 0, 1));
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Preferencias personales de PyMERA">
    <title>Mi cuenta | PyMERA</title>
    <?= view('layouts/alpha_frontend_head', ['styles' => [
        'alpha-shell.css',
        'account/preferences.css',
    ]]) ?>
    <script
        src="<?= base_url('assets/js/account/preferences.js?v=' . filemtime(FCPATH . 'assets/js/account/preferences.js')) ?>"
        defer
    ></script>
</head>
<body class="business-module-body">
<main class="preferences-shell">
    <header class="preferences-topbar">
        <a class="preferences-brand" href="<?= esc($returnUrl, 'attr') ?>" aria-label="Volver a PyMERA">
            <span aria-hidden="true"><img src="<?= base_url('assets/brand/pymera-symbol.svg') ?>" alt=""></span>
            <span><strong>PyMERA</strong><small>Gestión simple</small></span>
        </a>
        <div class="preferences-topbar-actions">
            <a class="button button-secondary" href="<?= esc($returnUrl, 'attr') ?>">Volver</a>
        </div>
    </header>

    <section class="preferences-heading">
        <div class="preferences-avatar" aria-hidden="true"><?= esc($initial) ?></div>
        <div>
            <p class="eyebrow">Mi cuenta</p>
            <h1>Preferencias personales</h1>
            <p>Estas opciones acompañan a tu cuenta y no modifican la configuración del negocio.</p>
        </div>
    </section>

    <?php if ($success !== null): ?>
        <div class="preferences-alert is-success" role="status"><?= esc($success) ?></div>
    <?php endif ?>
    <?php if ($error !== null): ?>
        <div class="preferences-alert is-error" role="alert"><?= esc($error) ?></div>
    <?php endif ?>

    <div class="preferences-grid">
        <aside class="preferences-profile" aria-label="Datos de la cuenta">
            <span class="section-kicker">Perfil</span>
            <h2><?= esc($username) ?></h2>
            <p><?= esc($email) ?></p>
            <small>Tus preferencias pertenecen a esta cuenta, aunque accedas a otro negocio en el futuro.</small>
        </aside>

        <section class="preferences-panel" aria-labelledby="appearanceTitle">
            <header>
                <span class="section-kicker">Apariencia</span>
                <h2 id="appearanceTitle">Elegí cómo visualizar PyMERA</h2>
                <p>La elección se aplicará a PyMERA cada vez que ingreses con esta cuenta.</p>
            </header>

            <form action="<?= esc(site_url('account/preferences'), 'attr') ?>" method="post" data-preferences-form>
                <?= csrf_field() ?>
                <fieldset class="appearance-options">
                    <legend class="visually-hidden">Tema de apariencia</legend>
                    <label class="appearance-option">
                        <input
                            type="radio"
                            name="appearance_theme"
                            value="light"
                            <?= $theme === 'light' ? 'checked' : '' ?>
                            required
                        >
                        <span class="appearance-preview is-light" aria-hidden="true">
                            <i></i><i></i><i></i>
                        </span>
                        <span><strong>Claro</strong><small>Superficies luminosas y contraste suave.</small></span>
                    </label>
                    <label class="appearance-option">
                        <input
                            type="radio"
                            name="appearance_theme"
                            value="dark"
                            <?= $theme === 'dark' ? 'checked' : '' ?>
                            required
                        >
                        <span class="appearance-preview is-dark" aria-hidden="true">
                            <i></i><i></i><i></i>
                        </span>
                        <span><strong>Oscuro</strong><small>Fondos profundos para reducir el brillo.</small></span>
                    </label>
                </fieldset>

                <?php if ($canConfigureCrm): ?>
                    <section class="preferences-subsection" aria-labelledby="crmViewTitle">
                        <header>
                            <span class="section-kicker">Clientes y ventas</span>
                            <h2 id="crmViewTitle">Elegí cómo organizar el CRM</h2>
                            <p>Esta será la vista que encontrarás cada vez que ingreses al módulo.</p>
                        </header>

                        <fieldset class="crm-layout-options">
                            <legend class="visually-hidden">Visualización del CRM</legend>
                            <label class="appearance-option crm-layout-option">
                                <input
                                    type="radio"
                                    name="crm_view_mode"
                                    value="combined"
                                    <?= $crmView === 'combined' ? 'checked' : '' ?>
                                    required
                                >
                                <span class="crm-layout-preview is-combined" aria-hidden="true">
                                    <i></i><i></i><i></i>
                                </span>
                                <span><strong>Vista conjunta</strong><small>Contactos y oportunidades en una misma pantalla.</small></span>
                            </label>
                            <label class="appearance-option crm-layout-option">
                                <input
                                    type="radio"
                                    name="crm_view_mode"
                                    value="tabs"
                                    <?= $crmView === 'tabs' ? 'checked' : '' ?>
                                    required
                                >
                                <span class="crm-layout-preview is-tabs" aria-hidden="true">
                                    <b></b><b></b><i></i>
                                </span>
                                <span><strong>Vista por pestañas</strong><small>Contactos y oportunidades en espacios separados.</small></span>
                            </label>
                        </fieldset>
                    </section>
                <?php endif ?>

                <div class="preferences-actions">
                    <button class="button button-primary" type="submit">Guardar preferencias</button>
                </div>
            </form>
        </section>
    </div>
</main>
</body>
</html>
