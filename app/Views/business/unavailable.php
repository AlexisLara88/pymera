<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Módulo no disponible — PyMERA</title>
    <link rel="icon" href="<?= base_url('assets/brand/pymera-symbol.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= base_url('assets/css/business/profile.css') ?>">
    <?= view('layouts/theme_head') ?>
</head>
<body class="business-module-body">
<main class="access-message" aria-labelledby="unavailableTitle">
    <span class="brand-mark" aria-hidden="true"><img src="<?= base_url('assets/brand/pymera-symbol.svg') ?>" alt=""></span>
    <p class="eyebrow">PyMERA</p>
    <h1 id="unavailableTitle">No pudimos cargar el módulo</h1>
    <p>La información no fue modificada. Intentá nuevamente o informá el inconveniente a la administración.</p>
    <a class="button button-secondary" href="<?= site_url('demolite') ?>">Volver a PyMERA</a>
</main>
</body>
</html>
