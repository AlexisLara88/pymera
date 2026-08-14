<?php

/**
 * Shared visual base for modules that use Bootstrap components.
 *
 * Bootstrap loads before project styles so the validated identity remains
 * authoritative. Versions and integrity hashes are deliberately pinned.
 *
 * @var list<string> $styles Relative paths below public/assets/css.
 */
$styles = $styles ?? [];
?>
<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
    integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr"
    crossorigin="anonymous"
>
<?= view('layouts/alpha_project_head', ['styles' => $styles]) ?>
