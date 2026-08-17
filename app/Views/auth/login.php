<?php

/**
 * Custom closed-login view for the controlled alpha.
 */
$hasError = session('error') !== null || session('errors') !== null;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Acceso a PyMERA">
    <title>Ingresar — PyMERA</title>
    <link rel="icon" href="<?= base_url('assets/brand/pymera-symbol.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= base_url('assets/css/auth/login.css?v=' . filemtime(FCPATH . 'assets/css/auth/login.css')) ?>">
    <?= view('layouts/theme_head') ?>
</head>
<body class="auth-body">
<main class="auth-shell">
    <section class="auth-intro">
        <div class="auth-brand" aria-label="PyMERA">
            <span aria-hidden="true"><img src="<?= base_url('assets/brand/pymera-symbol.svg') ?>" alt=""></span>
            <span><strong>PyMERA</strong><small>Gestión simple para tu negocio</small></span>
        </div>

        <div>
            <h1>Ordená el negocio desde una sola mirada.</h1>
            <p>Contexto, objetivos, prioridades y finanzas conectadas con una única fuente de información.</p>
        </div>

        <p class="auth-scope">
            © <?= date('Y') ?> PyMERA. Todos los derechos reservados.
        </p>
    </section>

    <section class="auth-card" aria-labelledby="loginTitle">
        <div class="auth-card-heading">
            <p class="auth-eyebrow">Acceso protegido</p>
            <h2 id="loginTitle">Ingresar</h2>
        </div>

        <?php if ($hasError): ?>
            <div class="auth-alert auth-alert-error" role="alert">
                No pudimos iniciar sesión. Revisá el correo y la contraseña.
            </div>
        <?php endif ?>

        <?php if (session('message') !== null): ?>
            <div class="auth-alert auth-alert-success" role="status">
                La sesión se cerró correctamente.
            </div>
        <?php endif ?>

        <form class="auth-form" action="<?= url_to('login') ?>" method="post" novalidate>
            <?= csrf_field() ?>

            <div class="auth-field">
                <label for="loginEmail">Correo electrónico</label>
                <input
                    id="loginEmail"
                    name="email"
                    type="email"
                    inputmode="email"
                    autocomplete="email"
                    value="<?= esc((string) old('email')) ?>"
                    placeholder="nombre@ejemplo.com"
                    required
                    autofocus
                >
            </div>

            <div class="auth-field">
                <label for="loginPassword">Contraseña</label>
                <input
                    id="loginPassword"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    placeholder="Ingresá tu contraseña"
                    required
                >
            </div>

            <button class="auth-submit" type="submit">Ingresar</button>
        </form>
    </section>
</main>
</body>
</html>
