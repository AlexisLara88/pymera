<?php

/**
 * @var array<string, mixed>                         $business
 * @var list<array<string, mixed>>                   $objectives
 * @var array<string, list<array<string, mixed>>>    $quadrants
 * @var array<string, string>                        $quadrantLabels
 */

$objectiveTitles = [];

foreach ($objectives as $objective) {
    $objectiveTitles[(int) $objective['id']] = (string) $objective['title'];
}

$quadrantDescriptions = [
    'do_now'    => 'Urgente + importante',
    'schedule'  => 'Importante + no urgente',
    'delegate'  => 'Urgente + menos importante',
    'eliminate' => 'No urgente + bajo impacto',
];
$businessName = (string) ($business['name'] ?? 'Negocio');
$businessInitial = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($businessName, 0, 1))
    : strtoupper(substr($businessName, 0, 1));
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Matriz de prioridades de PyMERA">
    <title>Prioridades — PyMERA</title>
    <?= view('layouts/alpha_project_head', ['styles' => [
        'business/profile.css',
        'workflow/index.css',
        'alpha-shell.css',
    ]]) ?>
</head>
<body class="business-module-body">
<div class="business-shell">
    <?= view('layouts/alpha_sidebar', [
        'business'     => $business,
        'activeModule' => 'priorities',
    ]) ?>

    <main class="module-main workflow-main priorities-main">
        <?= view('layouts/alpha_topbar', [
            'business' => $business,
            'eyebrow'  => 'Matriz de Eisenhower',
            'title'    => 'Prioridades',
        ]) ?>

        <header class="module-header module-header-compact">
            <div>
                <h2>Decidí dónde enfocar tu tiempo</h2>
                <p>Las actividades se organizan según su urgencia e importancia sin duplicar los datos originales.</p>
            </div>
            <div class="module-header-actions">
                <a class="button button-secondary" href="<?= site_url('app/objetivos') ?>">Editar actividades</a>
                <a class="button button-primary" href="<?= site_url('app/finanzas') ?>">Ver finanzas →</a>
            </div>
        </header>

        <div class="eisenhower-legend" aria-label="Leyenda de prioridades">
            <span><i class="priority-dot priority-do_now"></i> Hacer ahora</span>
            <span><i class="priority-dot priority-schedule"></i> Planificar</span>
            <span><i class="priority-dot priority-delegate"></i> Delegar</span>
            <span><i class="priority-dot priority-eliminate"></i> Eliminar</span>
            <a href="#matrixHelp">¿Cómo se clasifica?</a>
        </div>

        <section class="priority-matrix" aria-label="Matriz de Eisenhower">
            <?php foreach ($quadrantLabels as $quadrant => $label): ?>
                <article class="quadrant quadrant-<?= esc($quadrant) ?>">
                    <header>
                        <span><?= esc($quadrantDescriptions[$quadrant] ?? 'Clasificación derivada') ?></span>
                        <strong><?= esc($label) ?></strong>
                        <em><?= count($quadrants[$quadrant] ?? []) ?> tarea<?= count($quadrants[$quadrant] ?? []) === 1 ? '' : 's' ?></em>
                    </header>

                    <div
                        class="quadrant-items"
                        role="region"
                        aria-label="Tareas de <?= esc($label) ?>"
                        tabindex="0"
                    >
                        <?php if (($quadrants[$quadrant] ?? []) === []): ?>
                            <p class="quadrant-empty">No hay actividades en este cuadrante.</p>
                        <?php endif ?>

                        <?php foreach ($quadrants[$quadrant] ?? [] as $activity): ?>
                            <div class="priority-card">
                                <span class="task-type"><?= esc($activity['status_label']) ?></span>
                                <strong><?= esc((string) $activity['title']) ?></strong>
                                <p><?= esc($objectiveTitles[(int) $activity['objective_id']] ?? 'Objetivo') ?></p>
                                <footer>
                                    <span class="priority-owner"><?= esc($businessInitial) ?></span>
                                    <?php if (! empty($activity['due_date'])): ?>
                                        <time datetime="<?= esc((string) $activity['due_date']) ?>">
                                            <?= esc((string) $activity['due_date']) ?>
                                        </time>
                                    <?php endif ?>
                                </footer>
                            </div>
                        <?php endforeach ?>
                    </div>
                </article>
            <?php endforeach ?>
        </section>

        <aside class="matrix-note" id="matrixHelp">
            <strong>Cómo se calcula</strong>
            <p>Urgente + importante: Hacer ahora. Importante: Planificar. Urgente: Delegar. Sin ambas: Eliminar.</p>
        </aside>
    </main>
</div>
<script src="<?= base_url('assets/js/alpha-shell.js?v=' . filemtime(FCPATH . 'assets/js/alpha-shell.js')) ?>" defer></script>
</body>
</html>
