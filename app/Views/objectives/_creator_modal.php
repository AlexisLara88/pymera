<?php

/**
 * @var string|null                        $formKey
 * @var Closure(string, string, mixed): string $formValue
 * @var Closure(string, string): ?string   $formError
 * @var array<string, string>              $objectiveCategories
 * @var array<string, string>              $objectiveStatuses
 */
?>
<div
    id="objectiveCreatorApp"
    class="objective-creator-app"
    data-initial-open="<?= $formKey === 'create-objective' ? 'true' : 'false' ?>"
>
    <div
        class="objective-modal-layer"
        id="objectiveCreator"
        :class="{ 'is-open': isOpen }"
        :aria-hidden="isOpen ? 'false' : 'true'"
        @click.self="closeCreator"
        @keydown="handleKeydown"
        ref="layer"
    >
        <section
            class="objective-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="objectiveCreatorTitle"
            tabindex="-1"
            ref="dialogPanel"
        >
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content objective-modal-content">
                    <header class="modal-header objective-modal-header">
                        <div>
                            <p class="eyebrow">Nuevo objetivo</p>
                            <h2 id="objectiveCreatorTitle">¿Qué querés conseguir?</h2>
                            <p>Empezá con un resultado claro. Después agregá las actividades necesarias.</p>
                        </div>
                        <button
                            class="objective-modal-close"
                            type="button"
                            aria-label="Cerrar modal"
                            @click="closeCreator"
                        ><span aria-hidden="true">×</span></button>
                    </header>

                    <section class="modal-body workflow-create-card" id="create-objective">
                        <form class="workflow-form" action="<?= site_url('app/objetivos') ?>" method="post" novalidate>
                            <?= csrf_field() ?>
                            <div class="workflow-form-grid">
                                <div class="field-group field-wide">
                                    <label for="newObjectiveTitle">Título</label>
                                    <input
                                        class="form-control"
                                        id="newObjectiveTitle"
                                        name="title"
                                        maxlength="180"
                                        value="<?= $formValue('create-objective', 'title') ?>"
                                        aria-invalid="<?= $formError('create-objective', 'title') !== null ? 'true' : 'false' ?>"
                                        required
                                    >
                                    <?php if ($formError('create-objective', 'title') !== null): ?>
                                        <p class="field-error"><?= esc($formError('create-objective', 'title')) ?></p>
                                    <?php endif ?>
                                </div>

                                <div class="field-group">
                                    <label for="newObjectiveCategory">Categoría</label>
                                    <select class="form-control" id="newObjectiveCategory" name="category">
                                        <option value="">Sin categoría</option>
                                        <?php foreach ($objectiveCategories as $value => $label): ?>
                                            <option
                                                value="<?= esc($value) ?>"
                                                <?= $formValue('create-objective', 'category') === esc($value) ? 'selected' : '' ?>
                                            ><?= esc($label) ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>

                                <div class="field-group">
                                    <label for="newObjectiveStatus">Estado</label>
                                    <select class="form-control" id="newObjectiveStatus" name="status">
                                        <?php foreach ($objectiveStatuses as $value => $label): ?>
                                            <?php $selectedStatus = $formValue('create-objective', 'status', 'draft'); ?>
                                            <option value="<?= esc($value) ?>" <?= $selectedStatus === esc($value) ? 'selected' : '' ?>>
                                                <?= esc($label) ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                </div>

                                <div class="field-group field-wide">
                                    <label for="newObjectiveDescription">Descripción</label>
                                    <textarea
                                        class="form-control"
                                        id="newObjectiveDescription"
                                        name="description"
                                        rows="3"
                                        maxlength="5000"
                                        data-character-count
                                    ><?= $formValue('create-objective', 'description') ?></textarea>
                                    <small data-character-output>0 / 5000</small>
                                </div>

                                <div class="field-group">
                                    <label for="newObjectiveStart">Fecha de inicio</label>
                                    <input
                                        class="form-control"
                                        id="newObjectiveStart"
                                        name="start_date"
                                        type="date"
                                        value="<?= $formValue('create-objective', 'start_date') ?>"
                                    >
                                </div>

                                <div class="field-group">
                                    <label for="newObjectiveTarget">Fecha objetivo</label>
                                    <input
                                        class="form-control"
                                        id="newObjectiveTarget"
                                        name="target_date"
                                        type="date"
                                        value="<?= $formValue('create-objective', 'target_date') ?>"
                                        aria-invalid="<?= $formError('create-objective', 'target_date') !== null ? 'true' : 'false' ?>"
                                    >
                                    <?php if ($formError('create-objective', 'target_date') !== null): ?>
                                        <p class="field-error"><?= esc($formError('create-objective', 'target_date')) ?></p>
                                    <?php endif ?>
                                </div>
                            </div>

                            <div class="objective-modal-actions">
                                <a class="button button-ghost" href="#" @click.prevent="closeCreator">Cancelar</a>
                                <button class="button button-primary" type="submit">Crear objetivo</button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </section>
    </div>
</div>
