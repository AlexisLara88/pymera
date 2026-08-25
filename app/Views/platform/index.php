<?php

/**
 * @var list<array<string, mixed>> $accounts
 * @var list<array<string, mixed>> $businesses
 * @var list<array<string, mixed>> $active_businesses
 * @var list<array<string, mixed>> $audit_events
 * @var bool                       $administrator_creation_enabled
 * @var string|null                $success
 * @var string|null                $error
 * @var string|null                $initialDialog
 */

$groupLabels = [
    'alpha'          => 'Producto',
    'platform_admin' => 'Administrador de plataforma',
];
$roleLabels = [
    'owner'        => 'Propietario',
    'coach'        => 'Coach',
    'collaborator' => 'Colaborador',
];
$auditLabels = [
    'created'                 => 'Creación',
    'activated'               => 'Cuenta activada',
    'deactivated'             => 'Cuenta desactivada',
    'membership_created'      => 'Acceso creado',
    'membership_activated'    => 'Acceso activado',
    'membership_deactivated'  => 'Acceso desactivado',
    'role_changed'            => 'Rol modificado',
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Administración de cuentas y negocios de PyMERA">
    <title>Administración | PyMERA</title>
    <?= view('layouts/alpha_frontend_head', ['styles' => [
        'alpha-shell.css',
        'platform/index.css',
    ]]) ?>
</head>
<body class="business-module-body">
<main class="platform-shell">
    <header class="platform-topbar">
        <div>
            <span class="eyebrow">PyMERA</span>
            <h1>Administración de plataforma</h1>
            <p>Cuentas, negocios y accesos globales. Este panel no abre información operativa de las PyMEs.</p>
        </div>
        <div class="platform-actions">
            <a class="button button-secondary" href="<?= esc(site_url('account/preferences'), 'attr') ?>">Mi cuenta</a>
            <form action="<?= esc(site_url('logout'), 'attr') ?>" method="post">
                <?= csrf_field() ?>
                <button class="button button-secondary" type="submit">Cerrar sesión</button>
            </form>
        </div>
    </header>

    <?php if ($success !== null): ?>
        <div class="platform-alert is-success" role="status"><?= esc($success) ?></div>
    <?php endif; ?>
    <?php if ($error !== null): ?>
        <div class="platform-alert is-error" role="alert"><?= esc($error) ?></div>
    <?php endif; ?>

    <section class="platform-summary" aria-label="Resumen administrativo">
        <article><span>Cuentas</span><strong><?= count($accounts) ?></strong></article>
        <article><span>Negocios</span><strong><?= count($businesses) ?></strong></article>
        <article><span>Eventos recientes</span><strong><?= count($audit_events) ?></strong></article>
    </section>

    <section class="platform-grid platform-create-grid" aria-label="Creación de cuentas">
        <article class="platform-panel platform-create-card">
            <div>
                <span class="eyebrow">Cuenta de producto</span>
                <h2>Nueva cuenta propietaria</h2>
                <p>Asocia la cuenta con un negocio actual o crea uno nuevo dentro de la misma operación.</p>
            </div>
            <button class="button button-primary" type="button" data-platform-dialog-open="ownerCreationDialog">
                Abrir formulario
            </button>
        </article>

        <article class="platform-panel platform-create-card is-disabled" aria-disabled="true">
            <div>
                <span class="eyebrow">Cuenta de plataforma</span>
                <h2>Nuevo administrador</h2>
                <p>La capacidad permanece visible, pero no admite nuevas altas desde el panel en este momento.</p>
            </div>
            <button
                class="button platform-disabled-action"
                type="button"
                aria-disabled="<?= $administrator_creation_enabled ? 'false' : 'true' ?>"
                data-platform-disabled-feature
            >
                Funcionalidad deshabilitada
            </button>
        </article>
    </section>

    <div class="platform-feature-notice" role="status" aria-live="polite" data-platform-feature-notice hidden>
        La creación de nuevos administradores está temporalmente deshabilitada.
    </div>

    <dialog
        class="platform-dialog"
        id="ownerCreationDialog"
        aria-labelledby="ownerCreationTitle"
        aria-describedby="ownerCreationDescription"
        data-platform-dialog
        data-auto-open="<?= $initialDialog === 'owner' ? 'true' : 'false' ?>"
    >
        <div class="platform-dialog-card">
            <header class="platform-dialog-header">
                <div>
                    <span class="eyebrow">Cuenta de producto</span>
                    <h2 id="ownerCreationTitle">Nueva cuenta propietaria</h2>
                </div>
                <button
                    class="platform-dialog-close"
                    type="button"
                    aria-label="Cerrar formulario"
                    title="Cerrar formulario"
                    data-platform-dialog-close
                ><span aria-hidden="true">×</span></button>
            </header>

            <p id="ownerCreationDescription">
                Completa la identidad y elige el negocio al que tendrá acceso como Propietario.
            </p>

            <form
                class="platform-form"
                action="<?= esc(site_url('admin/accounts/owner'), 'attr') ?>"
                method="post"
                autocomplete="off"
                data-owner-creation-form
                data-1p-ignore
                data-lpignore="true"
                data-bwignore="true"
            >
                <?= csrf_field() ?>
                <label>Correo<input name="email" type="email" required autocomplete="off"></label>
                <label>Usuario<input name="username" type="text" required minlength="3" maxlength="30" autocomplete="off"></label>

                <div class="platform-form-field is-wide">
                    <label for="ownerBusinessSelection">Negocio</label>
                    <select
                        id="ownerBusinessSelection"
                        name="business_id"
                        required
                        aria-describedby="ownerBusinessSelectionHint"
                        data-owner-business-select
                    >
                        <option value="" disabled<?= $active_businesses !== [] ? ' selected' : '' ?>>Seleccioná un negocio</option>
                        <?php foreach ($active_businesses as $business): ?>
                            <option value="<?= (int) $business['id'] ?>">
                                <?= esc($business['name']) ?> · <?= esc($business['currency_code']) ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="new"<?= $active_businesses === [] ? ' selected' : '' ?>>Crear un negocio nuevo</option>
                    </select>
                    <small id="ownerBusinessSelectionHint" class="platform-field-hint" aria-live="polite" data-owner-business-hint>
                        Seleccioná un negocio activo o creá uno nuevo.
                    </small>
                </div>

                <section
                    class="platform-new-business is-wide"
                    aria-labelledby="newBusinessFieldsTitle"
                    data-owner-new-business
                    <?= $active_businesses !== [] ? 'hidden' : '' ?>
                >
                    <header>
                        <strong id="newBusinessFieldsTitle">Datos del nuevo negocio</strong>
                        <small>Estos datos sólo se solicitan cuando el negocio todavía no existe.</small>
                    </header>
                    <div class="platform-new-business-grid">
                        <label class="is-wide">
                            Nombre
                            <input
                                name="business_name"
                                type="text"
                                required
                                maxlength="120"
                                <?= $active_businesses !== [] ? 'disabled' : '' ?>
                                data-owner-new-business-field
                            >
                        </label>
                        <label>
                            Moneda
                            <input
                                name="currency_code"
                                type="text"
                                value="USD"
                                required
                                minlength="3"
                                maxlength="3"
                                <?= $active_businesses !== [] ? 'disabled' : '' ?>
                                data-owner-new-business-field
                            >
                        </label>
                        <label>
                            Zona horaria
                            <input
                                name="timezone"
                                type="text"
                                value="America/Guayaquil"
                                required
                                <?= $active_businesses !== [] ? 'disabled' : '' ?>
                                data-owner-new-business-field
                            >
                        </label>
                    </div>
                </section>

                <div class="platform-form-field">
                    <label for="ownerPassword">Contraseña</label>
                    <div class="platform-password-field">
                        <input
                            id="ownerPassword"
                            name="password"
                            type="password"
                            required
                            minlength="8"
                            maxlength="72"
                            autocomplete="off"
                            data-1p-ignore
                            data-lpignore="true"
                            data-bwignore="true"
                            aria-describedby="ownerPasswordFeedback"
                        >
                        <button
                            class="platform-password-visibility"
                            type="button"
                            data-password-toggle="ownerPassword"
                            aria-controls="ownerPassword"
                            aria-label="Mostrar contraseña"
                            aria-pressed="false"
                            title="Mostrar contraseña"
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
                    <small class="platform-password-feedback" id="ownerPasswordFeedback" aria-live="polite"></small>
                </div>

                <div class="platform-form-field">
                    <label for="ownerPasswordConfirmation">Confirmación</label>
                    <div class="platform-password-field">
                        <input
                            id="ownerPasswordConfirmation"
                            name="password_confirmation"
                            type="password"
                            required
                            minlength="8"
                            maxlength="72"
                            autocomplete="off"
                            data-1p-ignore
                            data-lpignore="true"
                            data-bwignore="true"
                            aria-describedby="ownerPasswordConfirmationFeedback"
                        >
                        <button
                            class="platform-password-visibility"
                            type="button"
                            data-password-toggle="ownerPasswordConfirmation"
                            aria-controls="ownerPasswordConfirmation"
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
                    <small class="platform-password-feedback" id="ownerPasswordConfirmationFeedback" aria-live="polite"></small>
                </div>

                <footer class="platform-dialog-actions is-wide">
                    <button class="button button-secondary" type="button" data-platform-dialog-close>Cancelar</button>
                    <button class="button button-primary" type="submit">Crear cuenta</button>
                </footer>
            </form>
        </div>
    </dialog>

    <dialog
        class="platform-dialog platform-confirm-dialog"
        id="platformStatusConfirmationDialog"
        aria-labelledby="platformStatusConfirmationTitle"
        aria-describedby="platformStatusConfirmationDescription"
        data-platform-confirm-dialog
    >
        <div class="platform-dialog-card platform-confirm-dialog-card">
            <header class="platform-dialog-header">
                <div>
                    <span class="eyebrow">Confirmar acción</span>
                    <h2 id="platformStatusConfirmationTitle" data-platform-confirm-title>Confirmar cambio</h2>
                </div>
                <button
                    class="platform-dialog-close"
                    type="button"
                    aria-label="Cerrar confirmación"
                    title="Cerrar confirmación"
                    data-platform-confirm-cancel
                ><span aria-hidden="true">×</span></button>
            </header>
            <p id="platformStatusConfirmationDescription" data-platform-confirm-description></p>
            <footer class="platform-dialog-actions platform-confirm-actions">
                <button class="button button-secondary" type="button" data-platform-confirm-cancel>Cancelar</button>
                <button class="button button-primary platform-confirm-submit" type="button" data-platform-confirm-submit>
                    Confirmar
                </button>
            </footer>
        </div>
    </dialog>

    <section class="platform-panel">
        <header class="platform-account-panel-heading">
            <div>
                <span class="eyebrow">Identidades</span>
                <h2>Cuentas y accesos</h2>
            </div>
            <label class="platform-account-search">
                <span class="visually-hidden">Buscar por usuario, correo o negocio</span>
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m16.2 16.2 4.3 4.3"></path>
                </svg>
                <input
                    type="search"
                    placeholder="Buscar por usuario, correo o negocio"
                    autocomplete="off"
                    aria-controls="platformAccountList"
                    data-platform-account-search
                >
            </label>
        </header>
        <div class="platform-account-search-status" aria-live="polite">
            <span data-platform-account-count><?= count($accounts) ?> <?= count($accounts) === 1 ? 'cuenta' : 'cuentas' ?></span>
        </div>
        <div class="platform-account-list" id="platformAccountList" data-platform-account-list>
            <?php foreach ($accounts as $account): ?>
                <?php $isProtectedAdministrator = $account['active'] && in_array('platform_admin', $account['groups'], true); ?>
                <?php
                $accountSearchValues = [(string) $account['username'], (string) $account['email']];
                foreach ($account['memberships'] as $membership) {
                    $accountSearchValues[] = (string) $membership['business_name'];
                }
                ?>
                <article
                    class="platform-account"
                    data-platform-account
                    data-platform-account-search-value="<?= esc(implode(' ', $accountSearchValues), 'attr') ?>"
                >
                    <div class="platform-account-main">
                        <span class="platform-avatar"><?= esc(strtoupper(substr((string) $account['username'], 0, 1))) ?></span>
                        <div>
                            <strong><?= esc($account['username']) ?></strong>
                            <small><?= esc($account['email']) ?></small>
                        </div>
                    </div>
                    <div class="platform-tags">
                        <?php foreach ($account['groups'] as $group): ?>
                            <span><?= esc($groupLabels[$group] ?? $group) ?></span>
                        <?php endforeach; ?>
                        <span class="<?= $account['active'] ? 'is-active' : 'is-inactive' ?>">
                            <?= $account['active'] ? 'Activa' : 'Inactiva' ?>
                        </span>
                    </div>
                    <div class="platform-memberships">
                        <?php if ($account['memberships'] === []): ?>
                            <small>Sin negocio asociado</small>
                        <?php endif; ?>
                        <?php foreach ($account['memberships'] as $membership): ?>
                            <div>
                                <span>
                                    <strong><?= esc($membership['business_name']) ?></strong>
                                    <small><?= esc($roleLabels[$membership['role_code']] ?? $membership['role_code']) ?> · <?= esc($membership['status']) ?></small>
                                </span>
                                <form
                                    action="<?= esc(site_url('admin/memberships/' . $membership['id'] . '/status'), 'attr') ?>"
                                    method="post"
                                    data-platform-status-form
                                    data-platform-status-scope="membership"
                                    data-platform-status-action="<?= $membership['status'] === 'active' ? 'pause' : 'activate' ?>"
                                    data-platform-status-user="<?= esc((string) $account['username'], 'attr') ?>"
                                    data-platform-status-business="<?= esc((string) $membership['business_name'], 'attr') ?>"
                                >
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="<?= $membership['status'] === 'active' ? 'inactive' : 'active' ?>">
                                    <button
                                        class="button button-secondary"
                                        type="button"
                                        aria-haspopup="dialog"
                                        aria-controls="platformStatusConfirmationDialog"
                                        data-platform-status-trigger
                                    >
                                        <?= $membership['status'] === 'active' ? 'Pausar acceso' : 'Activar acceso' ?>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($isProtectedAdministrator): ?>
                        <button
                            class="button button-secondary platform-account-action is-protected"
                            type="button"
                            disabled
                            aria-disabled="true"
                            title="Las cuentas administradoras no se desactivan desde este panel"
                        >
                            Desactivar cuenta
                        </button>
                    <?php else: ?>
                        <form
                            action="<?= esc(site_url('admin/accounts/' . $account['id'] . '/status'), 'attr') ?>"
                            method="post"
                            data-platform-status-form
                            data-platform-status-scope="account"
                            data-platform-status-action="<?= $account['active'] ? 'deactivate' : 'activate' ?>"
                            data-platform-status-user="<?= esc((string) $account['username'], 'attr') ?>"
                        >
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="<?= $account['active'] ? 'inactive' : 'active' ?>">
                            <button
                                class="button button-secondary platform-account-action"
                                type="button"
                                aria-haspopup="dialog"
                                aria-controls="platformStatusConfirmationDialog"
                                data-platform-status-trigger
                            >
                                <?= $account['active'] ? 'Desactivar cuenta' : 'Activar cuenta' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
        <p class="platform-account-empty" data-platform-account-empty hidden>
            No encontramos cuentas que coincidan con la búsqueda.
        </p>
    </section>

    <section class="platform-grid">
        <article class="platform-panel">
            <header><span class="eyebrow">Tenants</span><h2>Negocios</h2></header>
            <div class="platform-compact-list">
                <?php foreach ($businesses as $business): ?>
                    <div>
                        <span><strong><?= esc($business['name']) ?></strong><small><?= esc($business['currency_code']) ?> · <?= esc($business['timezone']) ?></small></span>
                        <em><?= (int) $business['member_count'] ?> acceso(s)</em>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="platform-panel">
            <header><span class="eyebrow">Trazabilidad</span><h2>Actividad administrativa</h2></header>
            <div class="platform-compact-list">
                <?php if ($audit_events === []): ?><p>Todavía no hay operaciones administrativas registradas.</p><?php endif; ?>
                <?php foreach ($audit_events as $event): ?>
                    <div>
                        <span>
                            <strong><?= esc($auditLabels[$event['action']] ?? $event['action']) ?></strong>
                            <small><?= esc($event['subject_type']) ?> #<?= (int) $event['subject_id'] ?> · <?= esc($event['actor_username'] ?? 'Consola') ?></small>
                        </span>
                        <time datetime="<?= esc($event['occurred_at'], 'attr') ?>"><?= esc($event['occurred_at']) ?> UTC</time>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    </section>
</main>
<script src="<?= esc(base_url('assets/js/platform/index.js?v=' . filemtime(FCPATH . 'assets/js/platform/index.js')), 'attr') ?>" defer></script>
</body>
</html>
