<?php

/**
 * @var array<string, mixed>                    $business
 * @var list<array<string, mixed>>              $objectives
 * @var array<string, string>                   $objectiveCategories
 * @var array<string, string>                   $objectiveStatuses
 * @var array<string, string>                   $activityStatuses
 * @var array<string, mixed>|null               $featured_objective
 * @var array<string, mixed>                    $submitted
 * @var array<string, string>                   $errors
 * @var string|null                             $formKey
 * @var string|null                             $operationError
 * @var string|null                             $success
 */

$isActiveForm = static fn (string $key): bool => $formKey === $key;
$formValue = static function (
    string $key,
    string $field,
    mixed $fallback = '',
) use ($submitted, $isActiveForm): string {
    if ($isActiveForm($key)
        && array_key_exists($field, $submitted)
        && is_string($submitted[$field])) {
        return esc($submitted[$field]);
    }

    return esc((string) ($fallback ?? ''));
};
$formError = static fn (string $key, string $field): ?string => $isActiveForm($key)
    ? ($errors[$field] ?? null)
    : null;
$isChecked = static function (
    string $key,
    string $field,
    mixed $fallback,
) use ($submitted, $isActiveForm): bool {
    if ($isActiveForm($key)) {
        return ($submitted[$field] ?? '0') === '1';
    }

    return (bool) $fallback;
};
$contextualHelp = static fn (array $configuration): string => view(
    'components/contextual_help',
    ['contextualHelp' => $configuration],
    ['saveData' => false],
);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Objetivos y actividades de PyMERA">
    <title>Objetivos — PyMERA</title>
    <?= view('layouts/alpha_frontend_head', ['styles' => [
        'business/profile.css',
        'workflow/index.css',
        'alpha-shell.css',
        'contextual-help.css',
    ]]) ?>
</head>
<body class="business-module-body" data-active-form="<?= esc($formKey ?? '') ?>">
<div class="business-shell">
    <?= view('layouts/alpha_sidebar', [
        'business'     => $business,
        'activeModule' => 'objectives',
    ]) ?>

    <main class="module-main workflow-main">
        <?= view('layouts/alpha_topbar', [
            'business' => $business,
            'eyebrow'  => 'Objetivos y plan de acción',
            'title'    => 'Objetivos',
        ]) ?>

        <header class="module-header module-header-compact" id="objectiveConceptHeader" data-context-help-focus-target>
            <div>
                <div class="context-help-heading">
                    <h2>Convertí problemas en objetivos concretos</h2>
                    <?= $contextualHelp([
                        'id'        => 'objectives-help-concept',
                        'title'     => '¿Qué diferencia hay entre un objetivo y una actividad?',
                        'targetId'  => 'objectiveConceptHeader',
                        'anchor'    => 'target',
                        'placement' => 'top',
                        'align'     => 'center',
                        'paragraphs' => [
                            'El objetivo expresa el resultado que querés alcanzar. Las actividades son las acciones concretas necesarias para conseguirlo.',
                        ],
                    ]) ?>
                </div>
                <p>Organizá acciones de mejora y revisá su avance sin perder el contexto del negocio.</p>
            </div>
            <a class="button button-primary" href="#objectiveCreator" data-open-objective-creator>
                + Nuevo objetivo
            </a>
        </header>

        <?php if ($success !== null): ?>
            <div class="module-alert module-alert-success" role="status"><?= esc($success) ?></div>
        <?php endif ?>

        <?php if ($operationError !== null): ?>
            <div class="module-alert module-alert-error" role="alert"><?= esc($operationError) ?></div>
        <?php endif ?>

        <?php if ($errors !== []): ?>
            <div class="module-alert module-alert-error" role="alert">
                Revisá los campos señalados antes de guardar.
            </div>
        <?php endif ?>

        <?php if ($featured_objective !== null): ?>
            <article class="panel objective-hero">
                <div class="objective-main">
                    <div class="objective-meta">
                        <span class="category-tag"><?= esc($featured_objective['category_label']) ?></span>
                        <span class="status-tag status-progress"><?= esc($featured_objective['status_label']) ?></span>
                    </div>
                    <h3><?= esc((string) $featured_objective['title']) ?></h3>
                    <p><?= esc((string) ($featured_objective['description'] ?: 'Objetivo listo para organizar su plan de acción.')) ?></p>
                    <div class="objective-details">
                        <span><small>Fecha objetivo</small><strong><?= esc((string) ($featured_objective['target_date'] ?: 'Sin definir')) ?></strong></span>
                        <span><small>Actividades</small><strong><?= count($featured_objective['activities']) ?> registradas</strong></span>
                        <span><small>Origen</small><strong>Plan del negocio</strong></span>
                    </div>
                </div>
                <div class="progress-block" id="objectiveProgressSummary" data-context-help-focus-target>
                    <div class="progress-value">
                        <strong><?= esc((string) $featured_objective['progress_percent']) ?>%</strong>
                        <div class="context-help-heading">
                            <span>completado</span>
                            <?= $contextualHelp([
                                'id'        => 'objectives-help-progress',
                                'title'     => '¿Cómo se calcula el avance?',
                                'targetId'  => 'objectiveProgressSummary',
                                'anchor'    => 'target',
                                'placement' => 'top',
                                'align'     => 'center',
                                'paragraphs' => [
                                    'Cada actividad no cancelada tiene el mismo peso. El porcentaje divide las actividades completadas entre el total considerado.',
                                ],
                            ]) ?>
                        </div>
                    </div>
                    <div class="progress-track">
                        <span style="width: <?= esc((string) $featured_objective['progress_percent']) ?>%"></span>
                    </div>
                    <small><?= esc((string) $featured_objective['completed_activity_count']) ?> de <?= esc((string) $featured_objective['progress_activity_count']) ?> actividades no canceladas completadas</small>
                </div>
            </article>
        <?php endif ?>

        <?= view('objectives/_creator_modal', [
            'formKey'             => $formKey,
            'formValue'           => $formValue,
            'formError'           => $formError,
            'objectiveCategories' => $objectiveCategories,
            'objectiveStatuses'   => $objectiveStatuses,
        ]) ?>

        <section class="objectives-list" aria-labelledby="currentObjectivesTitle">
            <div class="section-heading-row" id="objectiveManagementHeading" data-context-help-focus-target>
                <div>
                    <p class="eyebrow">Seguimiento</p>
                    <div class="context-help-heading">
                        <h2 id="currentObjectivesTitle">Objetivos actuales</h2>
                        <?= $contextualHelp([
                            'id'        => 'objectives-help-archive',
                            'title'     => '¿Qué sucede al archivar?',
                            'targetId'  => 'objectiveManagementHeading',
                            'anchor'    => 'target',
                            'placement' => 'top',
                            'align'     => 'center',
                            'paragraphs' => [
                                'Archivar retira el objetivo o la actividad del trabajo activo. No lo marca como completado ni modifica el avance alcanzado.',
                            ],
                        ]) ?>
                    </div>
                </div>
                <span class="count-badge"><?= count($objectives) ?> activos</span>
            </div>
            <div class="section-flow-action">
                <a class="button button-ghost" href="<?= site_url('app/prioridades') ?>">Ver en prioridades →</a>
            </div>

            <?php if ($objectives === []): ?>
                <div class="empty-state">
                    <strong>Todavía no hay objetivos.</strong>
                    <p>Creá el primero para comenzar a ordenar el plan de acción.</p>
                </div>
            <?php endif ?>

            <?php foreach ($objectives as $objective): ?>
                <?php
                $objectiveId = (int) $objective['id'];
                $objectiveKey = 'objective-' . $objectiveId;
                ?>
                <article class="objective-card" id="<?= esc($objectiveKey) ?>">
                    <header class="objective-card-header">
                        <div>
                            <span class="category-chip"><?= esc($objective['category_label']) ?></span>
                            <h3><?= esc((string) $objective['title']) ?></h3>
                            <p>
                                <?= esc($objective['status_label']) ?>
                                <?php if (! empty($objective['target_date'])): ?>
                                    · Meta <?= esc((string) $objective['target_date']) ?>
                                <?php endif ?>
                            </p>
                        </div>
                        <span class="activity-count"><?= count($objective['activities']) ?> actividades</span>
                    </header>

                    <details class="edit-panel" <?= $isActiveForm($objectiveKey) ? 'open' : '' ?>>
                        <summary>Editar objetivo</summary>
                        <form
                            class="workflow-form compact-form"
                            action="<?= site_url('app/objetivos/' . $objectiveId) ?>"
                            method="post"
                            novalidate
                        >
                            <?= csrf_field() ?>
                            <div class="workflow-form-grid">
                                <div class="field-group field-wide">
                                    <label for="objectiveTitle<?= $objectiveId ?>">Título</label>
                                    <input
                                        class="form-control"
                                        id="objectiveTitle<?= $objectiveId ?>"
                                        name="title"
                                        maxlength="180"
                                        value="<?= $formValue($objectiveKey, 'title', $objective['title']) ?>"
                                        aria-invalid="<?= $formError($objectiveKey, 'title') !== null ? 'true' : 'false' ?>"
                                        required
                                    >
                                    <?php if ($formError($objectiveKey, 'title') !== null): ?>
                                        <p class="field-error"><?= esc($formError($objectiveKey, 'title')) ?></p>
                                    <?php endif ?>
                                </div>

                                <div class="field-group">
                                    <label for="objectiveCategory<?= $objectiveId ?>">Categoría</label>
                                    <?php $selectedCategory = $formValue($objectiveKey, 'category', $objective['category']); ?>
                                    <select class="form-control" id="objectiveCategory<?= $objectiveId ?>" name="category">
                                        <option value="">Sin categoría</option>
                                        <?php foreach ($objectiveCategories as $value => $label): ?>
                                            <option value="<?= esc($value) ?>" <?= $selectedCategory === esc($value) ? 'selected' : '' ?>>
                                                <?= esc($label) ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                </div>

                                <div class="field-group">
                                    <label for="objectiveStatus<?= $objectiveId ?>">Estado</label>
                                    <?php $selectedStatus = $formValue($objectiveKey, 'status', $objective['status']); ?>
                                    <select class="form-control" id="objectiveStatus<?= $objectiveId ?>" name="status">
                                        <?php foreach ($objectiveStatuses as $value => $label): ?>
                                            <option value="<?= esc($value) ?>" <?= $selectedStatus === esc($value) ? 'selected' : '' ?>>
                                                <?= esc($label) ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                </div>

                                <div class="field-group field-wide">
                                    <label for="objectiveDescription<?= $objectiveId ?>">Descripción</label>
                                    <textarea
                                        class="form-control"
                                        id="objectiveDescription<?= $objectiveId ?>"
                                        name="description"
                                        rows="3"
                                        maxlength="5000"
                                        data-character-count
                                    ><?= $formValue($objectiveKey, 'description', $objective['description']) ?></textarea>
                                    <small data-character-output>0 / 5000</small>
                                </div>

                                <div class="field-group">
                                    <label for="objectiveStart<?= $objectiveId ?>">Inicio</label>
                                    <input
                                        class="form-control"
                                        id="objectiveStart<?= $objectiveId ?>"
                                        name="start_date"
                                        type="date"
                                        value="<?= $formValue($objectiveKey, 'start_date', $objective['start_date']) ?>"
                                    >
                                </div>

                                <div class="field-group">
                                    <label for="objectiveTarget<?= $objectiveId ?>">Meta</label>
                                    <input
                                        class="form-control"
                                        id="objectiveTarget<?= $objectiveId ?>"
                                        name="target_date"
                                        type="date"
                                        value="<?= $formValue($objectiveKey, 'target_date', $objective['target_date']) ?>"
                                        aria-invalid="<?= $formError($objectiveKey, 'target_date') !== null ? 'true' : 'false' ?>"
                                    >
                                    <?php if ($formError($objectiveKey, 'target_date') !== null): ?>
                                        <p class="field-error"><?= esc($formError($objectiveKey, 'target_date')) ?></p>
                                    <?php endif ?>
                                </div>
                            </div>
                            <button class="button button-primary" type="submit">Guardar objetivo</button>
                        </form>

                        <form
                            class="archive-form"
                            action="<?= site_url('app/objetivos/' . $objectiveId . '/archivar') ?>"
                            method="post"
                        >
                            <?= csrf_field() ?>
                            <button class="text-danger-button" type="submit">Archivar objetivo</button>
                        </form>
                    </details>

                    <section class="activities-section" aria-label="Actividades de <?= esc((string) $objective['title']) ?>">
                        <div
                            class="workflow-activity-guide"
                            id="objectiveActivityGuide<?= $objectiveId ?>"
                            data-context-help-focus-target
                        >
                            <span>Plan de actividades</span>
                            <?= $contextualHelp([
                                'id'        => 'objectives-help-activities-' . $objectiveId,
                                'title'     => '¿Cómo se organizan las actividades?',
                                'targetId'  => 'objectiveActivityGuide' . $objectiveId,
                                'anchor'    => 'target',
                                'placement' => 'top',
                                'align'     => 'center',
                                'paragraphs' => [
                                    'El estado indica si la actividad está pendiente, en curso, completada o cancelada. Las opciones Urgente e Importante determinan automáticamente su cuadrante en Prioridades.',
                                ],
                            ]) ?>
                        </div>
                        <?php foreach ($objective['activities'] as $activity): ?>
                            <?php
                            $activityId = (int) $activity['id'];
                            $activityKey = 'activity-' . $activityId;
                            ?>
                            <details class="activity-row" id="<?= esc($activityKey) ?>" <?= $isActiveForm($activityKey) ? 'open' : '' ?>>
                                <summary>
                                    <span class="priority-dot priority-<?= esc($activity['quadrant']) ?>"></span>
                                    <span>
                                        <strong><?= esc((string) $activity['title']) ?></strong>
                                        <small><?= esc($activity['quadrant_label']) ?> · <?= esc($activity['status_label']) ?></small>
                                    </span>
                                    <?php if (! empty($activity['due_date'])): ?>
                                        <time datetime="<?= esc((string) $activity['due_date']) ?>"><?= esc((string) $activity['due_date']) ?></time>
                                    <?php endif ?>
                                </summary>

                                <form
                                    class="workflow-form compact-form activity-edit-form"
                                    action="<?= site_url('app/actividades/' . $activityId) ?>"
                                    method="post"
                                    novalidate
                                >
                                    <?= csrf_field() ?>
                                    <div class="workflow-form-grid">
                                        <div class="field-group field-wide">
                                            <label for="activityTitle<?= $activityId ?>">Actividad</label>
                                            <input
                                                class="form-control"
                                                id="activityTitle<?= $activityId ?>"
                                                name="title"
                                                maxlength="180"
                                                value="<?= $formValue($activityKey, 'title', $activity['title']) ?>"
                                                aria-invalid="<?= $formError($activityKey, 'title') !== null ? 'true' : 'false' ?>"
                                                required
                                            >
                                            <?php if ($formError($activityKey, 'title') !== null): ?>
                                                <p class="field-error"><?= esc($formError($activityKey, 'title')) ?></p>
                                            <?php endif ?>
                                        </div>

                                        <div class="field-group">
                                            <label for="activityStatus<?= $activityId ?>">Estado</label>
                                            <?php $selectedActivityStatus = $formValue($activityKey, 'status', $activity['status']); ?>
                                            <select class="form-control" id="activityStatus<?= $activityId ?>" name="status">
                                                <?php foreach ($activityStatuses as $value => $label): ?>
                                                    <option value="<?= esc($value) ?>" <?= $selectedActivityStatus === esc($value) ? 'selected' : '' ?>>
                                                        <?= esc($label) ?>
                                                    </option>
                                                <?php endforeach ?>
                                            </select>
                                        </div>

                                        <div class="field-group">
                                            <label for="activityDue<?= $activityId ?>">Fecha límite</label>
                                            <input
                                                class="form-control"
                                                id="activityDue<?= $activityId ?>"
                                                name="due_date"
                                                type="date"
                                                value="<?= $formValue($activityKey, 'due_date', $activity['due_date']) ?>"
                                            >
                                        </div>

                                        <div class="field-group field-wide">
                                            <label for="activityDescription<?= $activityId ?>">Descripción</label>
                                            <textarea
                                                class="form-control"
                                                id="activityDescription<?= $activityId ?>"
                                                name="description"
                                                rows="2"
                                                maxlength="5000"
                                            ><?= $formValue($activityKey, 'description', $activity['description']) ?></textarea>
                                        </div>

                                        <div class="priority-flags field-wide">
                                            <label>
                                                <input type="hidden" name="is_urgent" value="0">
                                                <input
                                                    type="checkbox"
                                                    name="is_urgent"
                                                    value="1"
                                                    <?= $isChecked($activityKey, 'is_urgent', $activity['is_urgent']) ? 'checked' : '' ?>
                                                >
                                                Urgente
                                            </label>
                                            <label>
                                                <input type="hidden" name="is_important" value="0">
                                                <input
                                                    type="checkbox"
                                                    name="is_important"
                                                    value="1"
                                                    <?= $isChecked($activityKey, 'is_important', $activity['is_important']) ? 'checked' : '' ?>
                                                >
                                                Importante
                                            </label>
                                        </div>
                                    </div>
                                    <button class="button button-primary" type="submit">Guardar actividad</button>
                                </form>

                                <form
                                    class="archive-form"
                                    action="<?= site_url('app/actividades/' . $activityId . '/archivar') ?>"
                                    method="post"
                                >
                                    <?= csrf_field() ?>
                                    <button class="text-danger-button" type="submit">Archivar actividad</button>
                                </form>
                            </details>
                        <?php endforeach ?>

                        <?php $createActivityKey = 'create-activity-' . $objectiveId; ?>
                        <details class="new-activity-panel" id="<?= esc($createActivityKey) ?>" <?= $isActiveForm($createActivityKey) ? 'open' : '' ?>>
                            <summary>+ Agregar actividad</summary>
                            <form
                                class="workflow-form compact-form"
                                action="<?= site_url('app/objetivos/' . $objectiveId . '/actividades') ?>"
                                method="post"
                                novalidate
                            >
                                <?= csrf_field() ?>
                                <div class="workflow-form-grid">
                                    <div class="field-group field-wide">
                                        <label for="newActivityTitle<?= $objectiveId ?>">Actividad</label>
                                        <input
                                            class="form-control"
                                            id="newActivityTitle<?= $objectiveId ?>"
                                            name="title"
                                            maxlength="180"
                                            value="<?= $formValue($createActivityKey, 'title') ?>"
                                            aria-invalid="<?= $formError($createActivityKey, 'title') !== null ? 'true' : 'false' ?>"
                                            required
                                        >
                                        <?php if ($formError($createActivityKey, 'title') !== null): ?>
                                            <p class="field-error"><?= esc($formError($createActivityKey, 'title')) ?></p>
                                        <?php endif ?>
                                    </div>

                                    <div class="field-group">
                                        <label for="newActivityStatus<?= $objectiveId ?>">Estado</label>
                                        <?php $newActivityStatus = $formValue($createActivityKey, 'status', 'pending'); ?>
                                        <select class="form-control" id="newActivityStatus<?= $objectiveId ?>" name="status">
                                            <?php foreach ($activityStatuses as $value => $label): ?>
                                                <option value="<?= esc($value) ?>" <?= $newActivityStatus === esc($value) ? 'selected' : '' ?>>
                                                    <?= esc($label) ?>
                                                </option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>

                                    <div class="field-group">
                                        <label for="newActivityDue<?= $objectiveId ?>">Fecha límite</label>
                                        <input
                                            class="form-control"
                                            id="newActivityDue<?= $objectiveId ?>"
                                            name="due_date"
                                            type="date"
                                            value="<?= $formValue($createActivityKey, 'due_date') ?>"
                                        >
                                    </div>

                                    <div class="field-group field-wide">
                                        <label for="newActivityDescription<?= $objectiveId ?>">Descripción</label>
                                        <textarea
                                            class="form-control"
                                            id="newActivityDescription<?= $objectiveId ?>"
                                            name="description"
                                            rows="2"
                                            maxlength="5000"
                                        ><?= $formValue($createActivityKey, 'description') ?></textarea>
                                    </div>

                                    <div class="priority-flags field-wide">
                                        <label>
                                            <input type="hidden" name="is_urgent" value="0">
                                            <input
                                                type="checkbox"
                                                name="is_urgent"
                                                value="1"
                                                <?= $isChecked($createActivityKey, 'is_urgent', false) ? 'checked' : '' ?>
                                            >
                                            Urgente
                                        </label>
                                        <label>
                                            <input type="hidden" name="is_important" value="0">
                                            <input
                                                type="checkbox"
                                                name="is_important"
                                                value="1"
                                                <?= $isChecked($createActivityKey, 'is_important', false) ? 'checked' : '' ?>
                                            >
                                            Importante
                                        </label>
                                    </div>
                                </div>
                                <button class="button button-primary" type="submit">Crear actividad</button>
                            </form>
                        </details>
                    </section>
                </article>
            <?php endforeach ?>
        </section>
    </main>
</div>

<?= view('layouts/alpha_frontend_scripts') ?>
<script src="<?= base_url('assets/js/workflow/index.js?v=' . filemtime(FCPATH . 'assets/js/workflow/index.js')) ?>" defer></script>
<script src="<?= base_url('assets/js/alpha-shell.js?v=' . filemtime(FCPATH . 'assets/js/alpha-shell.js')) ?>" defer></script>
<script src="<?= base_url('assets/js/contextual-help.js?v=' . filemtime(FCPATH . 'assets/js/contextual-help.js')) ?>" defer></script>
</body>
</html>
