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
 * @var string|null $passwordSuccess
 * @var string|null $passwordError
 */

$initial = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($username, 0, 1))
    : strtoupper(substr($username, 0, 1));
$initialSettingsTab = $passwordSuccess !== null || $passwordError !== null
    ? 'security'
    : 'appearance';
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
            <h1>Configuración de la cuenta</h1>
            <p>Administrá tus preferencias personales y la seguridad de tu acceso.</p>
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

        <section
            class="preferences-panel preferences-settings"
            aria-label="Configuración personal"
            data-account-settings
            data-initial-tab="<?= esc($initialSettingsTab, 'attr') ?>"
        >
            <div class="preferences-tabs" role="tablist" aria-label="Secciones de configuración">
                <button
                    class="preferences-tab"
                    id="appearanceTab"
                    type="button"
                    role="tab"
                    aria-controls="appearancePanel"
                    aria-selected="<?= $initialSettingsTab === 'appearance' ? 'true' : 'false' ?>"
                    tabindex="<?= $initialSettingsTab === 'appearance' ? '0' : '-1' ?>"
                    data-settings-tab="appearance"
                >
                    Apariencia
                </button>
                <button
                    class="preferences-tab"
                    id="securityTab"
                    type="button"
                    role="tab"
                    aria-controls="securityPanel"
                    aria-selected="<?= $initialSettingsTab === 'security' ? 'true' : 'false' ?>"
                    tabindex="<?= $initialSettingsTab === 'security' ? '0' : '-1' ?>"
                    data-settings-tab="security"
                >
                    Seguridad
                </button>
            </div>

            <section
                class="preferences-tab-panel"
                id="appearancePanel"
                role="tabpanel"
                aria-labelledby="appearanceTab"
                data-settings-panel="appearance"
            >
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

            <section
                class="preferences-tab-panel preferences-security"
                id="securityPanel"
                role="tabpanel"
                aria-labelledby="securityTab"
                data-settings-panel="security"
            >
            <header>
                <span class="section-kicker">Seguridad</span>
                <h2 id="securityTitle">Cambiá tu contraseña</h2>
                <p>Para proteger tu cuenta, primero confirmá la contraseña que utilizás actualmente.</p>
            </header>

            <?php if ($passwordSuccess !== null): ?>
                <div class="preferences-alert is-success" role="status"><?= esc($passwordSuccess) ?></div>
            <?php endif ?>
            <?php if ($passwordError !== null): ?>
                <div class="preferences-alert is-error" role="alert"><?= esc($passwordError) ?></div>
            <?php endif ?>

            <form
                action="<?= esc(site_url('account/password'), 'attr') ?>"
                method="post"
                autocomplete="off"
                data-password-form
                data-1p-ignore
                data-lpignore="true"
                data-bwignore="true"
            >
                <?= csrf_field() ?>
                <div class="security-fields">
                    <div class="security-field">
                        <label for="currentPassword">Contraseña actual</label>
                        <div class="password-control">
                            <input
                                id="currentPassword"
                                name="current_password"
                                type="password"
                                autocomplete="off"
                                data-1p-ignore
                                data-lpignore="true"
                                data-bwignore="true"
                                maxlength="72"
                                required
                            >
                            <button
                                class="password-visibility"
                                type="button"
                                data-password-toggle="currentPassword"
                                aria-controls="currentPassword"
                                aria-label="Mostrar contraseña actual"
                                aria-pressed="false"
                                title="Mostrar contraseña actual"
                            >
                                <svg class="password-eye-show" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path>
                                    <circle cx="12" cy="12" r="2.6"></circle>
                                </svg>
                                <svg class="password-eye-hide" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M3 3l18 18"></path>
                                    <path d="M10.7 6.1A10.8 10.8 0 0 1 12 6c6 0 9.5 6 9.5 6a15.8 15.8 0 0 1-3.1 3.7M6.1 6.2C3.8 8 2.5 12 2.5 12s3.5 6 9.5 6a9.8 9.8 0 0 0 3-.5M9.9 9.8A3 3 0 0 0 14.2 14"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="security-field">
                        <label for="newPassword">Nueva contraseña</label>
                        <div class="password-control">
                            <input
                                id="newPassword"
                                name="new_password"
                                type="password"
                                autocomplete="off"
                                data-1p-ignore
                                data-lpignore="true"
                                data-bwignore="true"
                                minlength="8"
                                maxlength="72"
                                aria-describedby="passwordRequirements newPasswordFeedback"
                                required
                            >
                            <button
                                class="password-visibility"
                                type="button"
                                data-password-toggle="newPassword"
                                aria-controls="newPassword"
                                aria-label="Mostrar nueva contraseña"
                                aria-pressed="false"
                                title="Mostrar nueva contraseña"
                            >
                                <svg class="password-eye-show" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path>
                                    <circle cx="12" cy="12" r="2.6"></circle>
                                </svg>
                                <svg class="password-eye-hide" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M3 3l18 18"></path>
                                    <path d="M10.7 6.1A10.8 10.8 0 0 1 12 6c6 0 9.5 6 9.5 6a15.8 15.8 0 0 1-3.1 3.7M6.1 6.2C3.8 8 2.5 12 2.5 12s3.5 6 9.5 6a9.8 9.8 0 0 0 3-.5M9.9 9.8A3 3 0 0 0 14.2 14"></path>
                                </svg>
                            </button>
                        </div>
                        <small class="password-feedback" id="newPasswordFeedback" aria-live="polite"></small>
                    </div>
                    <div class="security-field">
                        <label for="newPasswordConfirmation">Confirmar nueva contraseña</label>
                        <div class="password-control">
                            <input
                                id="newPasswordConfirmation"
                                name="new_password_confirmation"
                                type="password"
                                autocomplete="off"
                                data-1p-ignore
                                data-lpignore="true"
                                data-bwignore="true"
                                minlength="8"
                                maxlength="72"
                                aria-describedby="newPasswordConfirmationFeedback"
                                required
                            >
                            <button
                                class="password-visibility"
                                type="button"
                                data-password-toggle="newPasswordConfirmation"
                                aria-controls="newPasswordConfirmation"
                                aria-label="Mostrar confirmación de contraseña"
                                aria-pressed="false"
                                title="Mostrar confirmación de contraseña"
                            >
                                <svg class="password-eye-show" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path>
                                    <circle cx="12" cy="12" r="2.6"></circle>
                                </svg>
                                <svg class="password-eye-hide" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M3 3l18 18"></path>
                                    <path d="M10.7 6.1A10.8 10.8 0 0 1 12 6c6 0 9.5 6 9.5 6a15.8 15.8 0 0 1-3.1 3.7M6.1 6.2C3.8 8 2.5 12 2.5 12s3.5 6 9.5 6a9.8 9.8 0 0 0 3-.5M9.9 9.8A3 3 0 0 0 14.2 14"></path>
                                </svg>
                            </button>
                        </div>
                        <small class="password-feedback" id="newPasswordConfirmationFeedback" aria-live="polite"></small>
                    </div>
                </div>

                <p class="security-requirements" id="passwordRequirements">
                    Usá al menos 8 caracteres y evitá contraseñas comunes o basadas en tu correo o usuario.
                </p>

                <div class="preferences-actions">
                    <button class="button button-primary" type="submit">Cambiar contraseña</button>
                </div>
            </form>
            </section>
        </section>
    </div>
</main>
</body>
</html>
