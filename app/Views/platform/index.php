<?php

/**
 * @var list<array<string, mixed>> $accounts
 * @var list<array<string, mixed>> $businesses
 * @var list<array<string, mixed>> $audit_events
 * @var string|null                $success
 * @var string|null                $error
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

    <section class="platform-grid">
        <details class="platform-panel platform-create-disclosure" name="platform-account-create">
            <summary>
                <h2>Nuevo propietario y negocio</h2>
                <span class="platform-disclosure-icon" aria-hidden="true">+</span>
            </summary>
            <div class="platform-disclosure-content">
                <p>Crea identidad, negocio y rol Propietario dentro de una sola transacción.</p>
                <form class="platform-form" action="<?= esc(site_url('admin/accounts/owner'), 'attr') ?>" method="post">
                    <?= csrf_field() ?>
                    <label>Correo<input name="email" type="email" required autocomplete="off"></label>
                    <label>Usuario<input name="username" type="text" required minlength="3" maxlength="30" autocomplete="off"></label>
                    <label class="is-wide">Negocio<input name="business_name" type="text" required maxlength="120"></label>
                    <label>Moneda<input name="currency_code" type="text" value="USD" required minlength="3" maxlength="3"></label>
                    <label>Zona horaria<input name="timezone" type="text" value="America/Guayaquil" required></label>
                    <label>Contraseña<input name="password" type="password" required minlength="8" autocomplete="new-password"></label>
                    <label>Confirmación<input name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"></label>
                    <button class="button button-primary is-wide" type="submit">Crear propietario y negocio</button>
                </form>
            </div>
        </details>

        <details class="platform-panel platform-create-disclosure" name="platform-account-create">
            <summary>
                <h2>Nuevo administrador</h2>
                <span class="platform-disclosure-icon" aria-hidden="true">+</span>
            </summary>
            <div class="platform-disclosure-content">
                <p>Crea una cuenta administrativa sin asociarla con ningún negocio.</p>
                <form class="platform-form" action="<?= esc(site_url('admin/accounts/platform-admin'), 'attr') ?>" method="post">
                    <?= csrf_field() ?>
                    <label class="is-wide">Correo<input name="email" type="email" required autocomplete="off"></label>
                    <label class="is-wide">Usuario<input name="username" type="text" required minlength="3" maxlength="30" autocomplete="off"></label>
                    <label>Contraseña<input name="password" type="password" required minlength="8" autocomplete="new-password"></label>
                    <label>Confirmación<input name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"></label>
                    <button class="button button-primary is-wide" type="submit">Crear administrador</button>
                </form>
            </div>
        </details>
    </section>

    <section class="platform-panel">
        <header>
            <span class="eyebrow">Identidades</span>
            <h2>Cuentas y accesos</h2>
        </header>
        <div class="platform-account-list">
            <?php foreach ($accounts as $account): ?>
                <?php $isProtectedAdministrator = $account['active'] && in_array('platform_admin', $account['groups'], true); ?>
                <article class="platform-account">
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
                                <form action="<?= esc(site_url('admin/memberships/' . $membership['id'] . '/status'), 'attr') ?>" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="<?= $membership['status'] === 'active' ? 'inactive' : 'active' ?>">
                                    <button class="button button-secondary" type="submit">
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
                        <form action="<?= esc(site_url('admin/accounts/' . $account['id'] . '/status'), 'attr') ?>" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="<?= $account['active'] ? 'inactive' : 'active' ?>">
                            <button class="button button-secondary platform-account-action" type="submit">
                                <?= $account['active'] ? 'Desactivar cuenta' : 'Activar cuenta' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
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
</body>
</html>
