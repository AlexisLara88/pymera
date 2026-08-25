<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso no disponible — PyMERA</title>
    <link rel="icon" href="<?= base_url('assets/brand/pymera-symbol.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= base_url('assets/css/business/profile.css') ?>">
    <?= view('layouts/theme_head') ?>
</head>
<body class="business-module-body">
<main class="access-message" aria-labelledby="accessTitle">
    <span class="brand-mark" aria-hidden="true"><img src="<?= base_url('assets/brand/pymera-symbol.svg') ?>" alt=""></span>
    <p class="eyebrow">PyMERA</p>
    <h1 id="accessTitle">No pudimos abrir este negocio</h1>
    <p>Tu cuenta no tiene un único negocio activo autorizado. Solicitá a la administración que revise la asociación.</p>
    <form action="<?= site_url('logout') ?>" method="post">
        <?= csrf_field() ?>
        <button class="button button-secondary" type="submit">Volver al inicio de sesión</button>
    </form>
</main>
</body>
</html>
