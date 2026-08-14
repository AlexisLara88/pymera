<?php

/**
 * @var array<string, mixed>       $business
 * @var string                     $period
 * @var string                     $period_label
 * @var string                     $today
 * @var list<array<string, mixed>> $entries
 * @var array<string, int>         $totals
 * @var array<string, int>         $finance_summary
 * @var array<string, int>         $sales_breakdown
 * @var array<string, int|float|string|null> $finance_indicators
 * @var list<array<string, int|string>> $chart_entries
 * @var array<string, string>      $financeStatuses
 * @var array<string, mixed>       $submitted
 * @var array<string, string>      $errors
 * @var string|null                $formKey
 * @var string|null                $operationError
 * @var string|null                $success
 */

$currency = esc((string) ($business['currency_code'] ?? 'USD'));
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
$money = static function (int $cents) use ($currency): string {
    $sign = $cents < 0 ? '−' : '';
    $absolute = abs($cents);

    return $sign . $currency . ' ' . number_format($absolute / 100, 2, ',', '.');
};
$resultClass = static fn (int $cents): string => $cents < 0 ? 'is-negative' : 'is-positive';
$breakEvenSales = $finance_indicators['break_even_sales_cents'];
$breakEvenStatus = (string) $finance_indicators['break_even_status'];
$breakEvenDescription = match ($breakEvenStatus) {
    'available'           => 'Venta mínima estimada del período',
    'non_positive_margin' => 'Los costos variables igualan o superan las ventas',
    default               => 'Registrá ventas para obtener la estimación',
};
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Finanzas básicas funcionales de PyMERA">
    <title>Finanzas — PyMERA</title>
    <?= view('layouts/alpha_frontend_head', ['styles' => [
        'business/profile.css',
        'finances/index.css',
        'alpha-shell.css',
    ]]) ?>
</head>
<body class="business-module-body" data-active-form="<?= esc($formKey ?? '') ?>">
<div class="business-shell">
    <?= view('layouts/alpha_sidebar', [
        'business'     => $business,
        'activeModule' => 'finances',
    ]) ?>

    <main class="module-main finance-main">
        <?= view('layouts/alpha_topbar', [
            'business' => $business,
            'eyebrow'  => 'Salud financiera',
            'title'    => 'Finanzas',
        ]) ?>

        <header class="module-header module-header-compact">
            <div>
                <h2>Revisá cómo cerró el negocio</h2>
                <p>Registrá ventas, costos y gastos agregados para seguir la utilidad bruta y el EBITDA.</p>
            </div>
            <form class="period-filter" action="<?= site_url('app/finanzas') ?>" method="get">
                <label for="period">Período</label>
                <div class="period-filter-controls">
                    <input class="period-filter-input" id="period" name="period" type="month" value="<?= esc($period) ?>" required>
                    <button class="button button-secondary" type="submit">Ver</button>
                </div>
                <?php if (isset($errors['period'])): ?>
                    <small class="field-error"><?= esc($errors['period']) ?></small>
                <?php endif ?>
            </form>
        </header>

        <?php if ($success !== null): ?>
            <div class="module-alert module-alert-success" role="status"><?= esc($success) ?></div>
        <?php endif ?>

        <?php if ($operationError !== null): ?>
            <div class="module-alert module-alert-error" role="alert"><?= esc($operationError) ?></div>
        <?php endif ?>

        <?php if ($errors !== [] && ! isset($errors['period'])): ?>
            <div class="module-alert module-alert-error" role="alert">
                Revisá los campos señalados antes de guardar.
            </div>
        <?php endif ?>

        <section class="metric-row finance-metrics" aria-label="Resumen financiero del período">
            <details class="metric-card accent-green finance-sales-card">
                <summary>
                    <span>
                        <span>Ventas totales</span>
                        <strong><?= $money($sales_breakdown['total_sales_cents']) ?></strong>
                        <small><?= esc($period_label) ?> · Ver desglose</small>
                    </span>
                    <i aria-hidden="true"></i>
                </summary>
                <dl class="finance-sales-breakdown">
                    <div>
                        <dt>Registradas manualmente</dt>
                        <dd><?= $money($sales_breakdown['manual_sales_cents']) ?></dd>
                    </div>
                    <div>
                        <dt>Provenientes del CRM</dt>
                        <dd><?= $money($sales_breakdown['crm_sales_cents']) ?></dd>
                    </div>
                    <div>
                        <dt>Total usado en los cálculos</dt>
                        <dd><?= $money($sales_breakdown['total_sales_cents']) ?></dd>
                    </div>
                </dl>
            </details>
            <article class="metric-card">
                <span>Costo de ventas</span>
                <strong><?= $money($totals['cost_of_sales_cents']) ?></strong>
                <small>Costos variables asociados</small>
            </article>
            <article class="metric-card accent-blue">
                <span>Utilidad bruta</span>
                <strong class="<?= $resultClass($totals['gross_profit_cents']) ?>"><?= $money($totals['gross_profit_cents']) ?></strong>
                <small>Ventas menos costo de ventas</small>
            </article>
            <article class="metric-card accent-warm">
                <span>EBITDA</span>
                <strong class="<?= $resultClass($totals['ebitda_cents']) ?>"><?= $money($totals['ebitda_cents']) ?></strong>
                <small>Según la fórmula del cliente</small>
            </article>
        </section>

        <div class="content-grid alpha-finance-workspace">
        <section class="finance-create" id="create-entry">
            <div>
                <p class="eyebrow">Nuevo registro diario</p>
                <h2>¿Cómo cerró el día?</h2>
                <p>Ingresá totales agregados. Podés dejar el registro como borrador para excluirlo del resumen.</p>
            </div>

            <div class="finance-form-app" data-finance-form-app data-currency="<?= $currency ?>">
            <form
                class="finance-form"
                action="<?= site_url('app/finanzas') ?>"
                method="post"
                novalidate
                data-finance-form
                data-form-key="create-entry"
                @input="updatePreview"
                @submit="startSubmitting"
            >
                <?= csrf_field() ?>
                <div class="finance-form-grid">
                    <div class="field-group">
                        <label for="newOperationDate">Fecha de operación</label>
                        <input id="newOperationDate" name="operation_date" type="date"
                            value="<?= $formValue('create-entry', 'operation_date', $today) ?>" required>
                        <?php if ($formError('create-entry', 'operation_date') !== null): ?>
                            <p class="field-error"><?= esc($formError('create-entry', 'operation_date')) ?></p>
                        <?php endif ?>
                    </div>
                    <div class="field-group">
                        <label for="newStatus">Estado</label>
                        <?php $newStatus = $formValue('create-entry', 'status', 'recorded'); ?>
                        <select id="newStatus" name="status" required>
                            <?php foreach ($financeStatuses as $value => $label): ?>
                                <option value="<?= esc($value) ?>" <?= $newStatus === esc($value) ? 'selected' : '' ?>>
                                    <?= esc($label) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <?php
                    $newMoneyFields = [
                        'income_amount'                  => 'Ventas del día',
                        'variable_expense_amount'        => 'Costo de ventas',
                        'fixed_expense_amount'           => 'Gastos operativos o fijos',
                        'administrative_expense_amount'  => 'Gastos administrativos',
                    ];
                    ?>
                    <?php foreach ($newMoneyFields as $field => $label): ?>
                        <div class="field-group">
                            <label for="new_<?= esc($field) ?>"><?= esc($label) ?> (<?= $currency ?>)</label>
                            <input id="new_<?= esc($field) ?>" name="<?= esc($field) ?>" type="text"
                                inputmode="decimal" value="<?= $formValue('create-entry', $field, '0,00') ?>"
                                pattern="\d{1,12}([.,]\d{1,2})?" required data-money-input>
                            <?php if ($formError('create-entry', $field) !== null): ?>
                                <p class="field-error"><?= esc($formError('create-entry', $field)) ?></p>
                            <?php endif ?>
                        </div>
                    <?php endforeach ?>
                    <div class="field-group field-wide">
                        <label for="newNotes">Nota opcional</label>
                        <textarea id="newNotes" name="notes" rows="2" maxlength="1000"
                            placeholder="Contexto breve del cierre diario"><?= $formValue('create-entry', 'notes') ?></textarea>
                        <?php if ($formError('create-entry', 'notes') !== null): ?>
                            <p class="field-error"><?= esc($formError('create-entry', 'notes')) ?></p>
                        <?php endif ?>
                    </div>
                </div>
                <div class="finance-form-footer">
                    <span>EBITDA: <strong data-result-preview v-text="formattedPreview"></strong></span>
                    <button class="button button-primary" type="submit" :disabled="submitting">
                        <span :hidden="submitting" data-submit-default>Guardar registro</span>
                        <span hidden :hidden="!submitting" data-submit-progress>Guardando…</span>
                    </button>
                </div>
            </form>
            </div>
        </section>

        <article class="panel chart-panel">
            <div class="panel-heading">
                <div>
                    <span class="section-kicker">Evolución real</span>
                    <h3>Ventas frente a costos y gastos</h3>
                </div>
                <span class="chart-legend"><i></i> Ventas <i></i> Costos y gastos</span>
            </div>
            <?php if ($chart_entries === []): ?>
                <div class="chart-empty">
                    <strong>Sin cierres confirmados.</strong>
                    <p>El gráfico aparecerá cuando registres movimientos del período.</p>
                </div>
            <?php else: ?>
                <div class="bar-chart" aria-label="Ventas, costos y gastos de los últimos cierres">
                    <?php foreach ($chart_entries as $chartEntry): ?>
                        <div class="chart-column">
                            <div>
                                <i style="height: <?= esc((string) max(4, (int) $chartEntry['sales_percent'])) ?>%"></i>
                                <b style="height: <?= esc((string) max(4, (int) $chartEntry['costs_percent'])) ?>%"></b>
                            </div>
                            <span><?= esc((string) $chartEntry['label']) ?></span>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
            <div class="monthly-breakdown">
                <div><span>Ventas acumuladas</span><strong><?= $money($totals['sales_cents']) ?></strong></div>
                <div><span>Costo de ventas</span><strong><?= $money($totals['cost_of_sales_cents']) ?></strong></div>
                <div><span>Utilidad bruta</span><strong><?= $money($totals['gross_profit_cents']) ?></strong></div>
                <div><span>Gastos operativos o fijos</span><strong><?= $money($totals['operating_expense_cents']) ?></strong></div>
                <div><span>Gastos administrativos</span><strong><?= $money($totals['administrative_expense_cents']) ?></strong></div>
                <div class="highlight"><span>EBITDA</span><strong><?= $money($totals['ebitda_cents']) ?></strong></div>
            </div>
        </article>
        </div>

        <section class="indicator-row alpha-future-indicators" aria-label="Indicadores financieros">
            <article class="indicator-card<?= $breakEvenSales === null ? ' pending' : '' ?>">
                <span class="indicator-icon">◫</span>
                <span>
                    <small>Punto de equilibrio estimado</small>
                    <strong><?= $breakEvenSales === null ? 'No disponible' : $money((int) $breakEvenSales) ?></strong>
                    <em><?= esc($breakEvenDescription) ?></em>
                </span>
            </article>
            <article class="indicator-card">
                <span class="indicator-icon">↗</span>
                <span><small>Utilidad bruta</small><strong><?= $money($totals['gross_profit_cents']) ?></strong><em>Ventas menos costo de ventas</em></span>
            </article>
            <article class="indicator-card">
                <span class="indicator-icon">◎</span>
                <span><small>EBITDA</small><strong><?= $money($totals['ebitda_cents']) ?></strong><em>Fórmula confirmada por el cliente</em></span>
            </article>
        </section>

        <section class="finance-history" aria-labelledby="historyTitle">
            <div class="history-heading">
                <div>
                    <p class="eyebrow">Historial del período</p>
                    <h2 id="historyTitle">Registros diarios</h2>
                </div>
                <span><?= count($entries) ?> registro<?= count($entries) === 1 ? '' : 's' ?></span>
            </div>

            <?php if ($entries === []): ?>
                <div class="finance-empty">
                    <strong>Aún no hay movimientos en <?= esc($period_label) ?>.</strong>
                    <p>Creá el primer cierre diario con el formulario anterior.</p>
                </div>
            <?php endif ?>

            <div class="finance-entry-list">
                <?php foreach ($entries as $entry): ?>
                    <?php
                    $entryId  = (int) $entry['id'];
                    $entryKey = 'entry-' . $entryId;
                    $status   = $formValue($entryKey, 'status', $entry['status']);
                    ?>
                    <details class="finance-entry" id="<?= esc($entryKey) ?>" <?= $isActiveForm($entryKey) ? 'open' : '' ?>>
                        <summary>
                            <div>
                                <time datetime="<?= esc((string) $entry['operation_date']) ?>">
                                    <?= esc((new DateTimeImmutable((string) $entry['operation_date']))->format('d/m/Y')) ?>
                                </time>
                                <span class="entry-status status-<?= esc((string) $entry['status']) ?>">
                                    <?= esc((string) $entry['status_label']) ?>
                                </span>
                            </div>
                            <div class="entry-amounts">
                                <span>Venta <strong><?= $money((int) $entry['sales_cents']) ?></strong></span>
                                <span>EBITDA
                                    <strong class="<?= $resultClass((int) $entry['ebitda_cents']) ?>">
                                        <?= $money((int) $entry['ebitda_cents']) ?>
                                    </strong>
                                </span>
                            </div>
                        </summary>

                        <div class="finance-form-app" data-finance-form-app data-currency="<?= $currency ?>">
                        <form class="finance-form finance-edit-form"
                            action="<?= site_url('app/finanzas/' . $entryId) ?>" method="post"
                            novalidate data-finance-form data-form-key="<?= esc($entryKey) ?>"
                            @input="updatePreview" @submit="startSubmitting">
                            <?= csrf_field() ?>
                            <div class="finance-form-grid">
                                <div class="field-group">
                                    <label for="date_<?= $entryId ?>">Fecha de operación</label>
                                    <input id="date_<?= $entryId ?>" name="operation_date" type="date"
                                        value="<?= $formValue($entryKey, 'operation_date', $entry['operation_date']) ?>" required>
                                    <?php if ($formError($entryKey, 'operation_date') !== null): ?>
                                        <p class="field-error"><?= esc($formError($entryKey, 'operation_date')) ?></p>
                                    <?php endif ?>
                                </div>
                                <div class="field-group">
                                    <label for="status_<?= $entryId ?>">Estado</label>
                                    <select id="status_<?= $entryId ?>" name="status" required>
                                        <?php foreach ($financeStatuses as $value => $label): ?>
                                            <option value="<?= esc($value) ?>" <?= $status === esc($value) ? 'selected' : '' ?>>
                                                <?= esc($label) ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                                <?php foreach ($newMoneyFields as $field => $label): ?>
                                    <div class="field-group">
                                        <label for="<?= esc($field) ?>_<?= $entryId ?>"><?= esc($label) ?> (<?= $currency ?>)</label>
                                        <input id="<?= esc($field) ?>_<?= $entryId ?>" name="<?= esc($field) ?>"
                                            type="text" inputmode="decimal"
                                            value="<?= $formValue($entryKey, $field, $entry[$field]) ?>"
                                            pattern="\d{1,12}([.,]\d{1,2})?" required data-money-input>
                                        <?php if ($formError($entryKey, $field) !== null): ?>
                                            <p class="field-error"><?= esc($formError($entryKey, $field)) ?></p>
                                        <?php endif ?>
                                    </div>
                                <?php endforeach ?>
                                <div class="field-group field-wide">
                                    <label for="notes_<?= $entryId ?>">Nota opcional</label>
                                    <textarea id="notes_<?= $entryId ?>" name="notes" rows="2"
                                        maxlength="1000"><?= $formValue($entryKey, 'notes', $entry['notes']) ?></textarea>
                                    <?php if ($formError($entryKey, 'notes') !== null): ?>
                                        <p class="field-error"><?= esc($formError($entryKey, 'notes')) ?></p>
                                    <?php endif ?>
                                </div>
                            </div>
                            <div class="finance-form-footer">
                                <span>EBITDA: <strong data-result-preview v-text="formattedPreview"></strong></span>
                                <button class="button button-primary" type="submit" :disabled="submitting">
                                    <span :hidden="submitting" data-submit-default>Guardar cambios</span>
                                    <span hidden :hidden="!submitting" data-submit-progress>Guardando…</span>
                                </button>
                            </div>
                        </form>
                        </div>
                    </details>
                <?php endforeach ?>
            </div>
        </section>
    </main>
</div>

<?= view('layouts/alpha_frontend_scripts') ?>
<script src="<?= base_url('assets/js/finances/index.js?v=' . filemtime(FCPATH . 'assets/js/finances/index.js')) ?>" defer></script>
<script src="<?= base_url('assets/js/alpha-shell.js?v=' . filemtime(FCPATH . 'assets/js/alpha-shell.js')) ?>" defer></script>
</body>
</html>
