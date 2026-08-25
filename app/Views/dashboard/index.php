<?php

/**
 * @var array<string, mixed>      $business
 * @var array<string, mixed>|null $profile
 * @var int                       $profile_completion
 * @var array<string, int>        $workflow_summary
 * @var array<string, int>        $priority_summary
 * @var list<array<string, mixed>> $next_actions
 * @var array<string, mixed>|null $featured_objective
 * @var string                    $finance_period_label
 * @var array<string, int>        $finance_totals
 * @var array<string, int>        $finance_summary
 * @var list<array<string, int|string>> $finance_chart_entries
 * @var string|null               $success
 */

$businessName = (string) ($business['name'] ?? 'Negocio');
$currency = esc((string) ($business['currency_code'] ?? 'USD'));
$money = static function (int $cents) use ($currency): string {
    $sign = $cents < 0 ? '−' : '';

    return $sign . $currency . ' ' . number_format(abs($cents) / 100, 2, ',', '.');
};
$date = static function (?string $value): string {
    if ($value === null || $value === '') {
        return 'Sin fecha';
    }

    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

    return $parsed === false ? $value : $parsed->format('d/m/Y');
};
$contextualHelp = static fn (array $configuration): string => view(
    'components/contextual_help',
    ['contextualHelp' => $configuration],
    ['saveData' => false],
);
$profile = $profile ?? [];
$featuredProgress = (int) ($featured_objective['progress_percent'] ?? 0);
$featuredCompleted = (int) ($featured_objective['completed_activity_count'] ?? 0);
$featuredProgressTotal = (int) ($featured_objective['progress_activity_count'] ?? 0);
$priorityLabels = [
    'do_now'    => ['Hacer ahora', 'Urgente e importante'],
    'schedule'  => ['Planificar', 'Importante, no urgente'],
    'delegate'  => ['Delegar', 'Urgente, menor impacto'],
    'eliminate' => ['Eliminar', 'Bajo impacto'],
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Vista general funcional de PyMERA">
    <title>Inicio — PyMERA</title>
    <?= view('layouts/alpha_project_head', ['styles' => [
        'business/profile.css',
        'dashboard/index.css',
        'alpha-shell.css',
        'contextual-help.css',
    ]]) ?>
</head>
<body class="business-module-body">
<div class="business-shell">
    <?= view('layouts/alpha_sidebar', [
        'business'     => $business,
        'activeModule' => 'dashboard',
    ]) ?>

    <main class="module-main dashboard-main">
        <?= view('layouts/alpha_topbar', [
            'business' => $business,
            'eyebrow'  => 'Vista integral',
            'title'    => 'Inicio',
        ]) ?>

        <header class="module-header dashboard-header">
            <div>
                <h2>Así está <?= esc($businessName) ?></h2>
                <p>Un resumen para decidir dónde enfocar el trabajo y revisar el resultado del negocio.</p>
            </div>
            <div class="module-header-actions">
                <a class="button button-secondary" href="<?= site_url('app/objetivos') ?>">Ver plan de acción</a>
                <a class="button button-primary" href="<?= site_url('app/finanzas') ?>">Registrar cierre →</a>
            </div>
        </header>

        <?php if ($success !== null): ?>
            <div class="module-alert module-alert-success" role="status"><?= esc($success) ?></div>
        <?php endif ?>

        <section class="dashboard-metrics" aria-label="Indicadores generales">
            <article class="dashboard-metric dashboard-metric-objectives" id="dashboardActiveObjectives" data-context-help-focus-target>
                <span class="metric-icon" aria-hidden="true">01</span>
                <div>
                    <div class="dashboard-metric-label">
                        <small>Objetivos activos</small>
                        <?= $contextualHelp([
                            'id'        => 'dashboard-help-active-objectives',
                            'title'     => '¿Qué incluyen estos valores?',
                            'targetId'  => 'dashboardActiveObjectives',
                            'anchor'    => 'target',
                            'placement' => 'top',
                            'align'     => 'center',
                            'paragraphs' => [
                                'Cuenta los objetivos en estado activo. Las actividades abiertas son sus tareas pendientes o en curso; no incluye las completadas ni canceladas.',
                            ],
                        ]) ?>
                    </div>
                    <strong><?= esc((string) $workflow_summary['active_objectives']) ?></strong>
                    <p><?= esc((string) $workflow_summary['open_activities']) ?> actividades abiertas</p>
                </div>
            </article>
            <article class="dashboard-metric dashboard-metric-progress" id="dashboardActivityProgress" data-context-help-focus-target>
                <span class="metric-icon" aria-hidden="true">02</span>
                <div>
                    <div class="dashboard-metric-label">
                        <small>Avance de actividades</small>
                        <?= $contextualHelp([
                            'id'        => 'dashboard-help-progress',
                            'title'     => '¿Cómo se calcula este avance?',
                            'targetId'  => 'dashboardActivityProgress',
                            'anchor'    => 'target',
                            'placement' => 'top',
                            'align'     => 'center',
                            'paragraphs' => [
                                'Cuenta las actividades completadas de objetivos activos y las divide entre sus actividades no canceladas.',
                            ],
                        ]) ?>
                    </div>
                    <strong><?= esc((string) $workflow_summary['progress_percent']) ?>%</strong>
                    <p><?= esc((string) $workflow_summary['completed_activities']) ?> completadas</p>
                </div>
            </article>
            <article class="dashboard-metric dashboard-metric-sales" id="dashboardPeriodSales" data-context-help-focus-target>
                <span class="metric-icon" aria-hidden="true">03</span>
                <div>
                    <div class="dashboard-metric-label">
                        <small>Ventas del período</small>
                        <?= $contextualHelp([
                            'id'        => 'dashboard-help-period-sales',
                            'title'     => '¿Qué ventas forman este total?',
                            'targetId'  => 'dashboardPeriodSales',
                            'anchor'    => 'target',
                            'placement' => 'top',
                            'align'     => 'center',
                            'paragraphs' => [
                                'Suma las ventas de los cierres registrados del período mostrado. Incluye las ventas manuales y las provenientes del CRM que ya fueron confirmadas en Finanzas; los borradores no participan.',
                            ],
                        ]) ?>
                    </div>
                    <strong><?= $money($finance_totals['sales_cents']) ?></strong>
                    <p><?= esc($finance_period_label) ?> · <?= esc((string) $finance_summary['recorded_entry_count']) ?> cierres</p>
                </div>
            </article>
            <article class="dashboard-metric dashboard-metric-ebitda" id="dashboardEbitda" data-context-help-focus-target>
                <span class="metric-icon" aria-hidden="true">04</span>
                <div>
                    <div class="dashboard-metric-label">
                        <small>EBITDA</small>
                        <?= $contextualHelp([
                            'id'        => 'dashboard-help-ebitda',
                            'title'     => '¿Cómo se calcula el EBITDA?',
                            'targetId'  => 'dashboardEbitda',
                            'anchor'    => 'target',
                            'placement' => 'top',
                            'align'     => 'center',
                            'paragraphs' => [
                                'Primero resta el costo de ventas a las ventas para obtener la utilidad bruta. Después resta los gastos operativos o fijos y los gastos administrativos.',
                            ],
                        ]) ?>
                    </div>
                    <strong class="<?= $finance_totals['ebitda_cents'] < 0 ? 'is-negative' : 'is-positive' ?>">
                        <?= $money($finance_totals['ebitda_cents']) ?>
                    </strong>
                    <p>Resultado operativo del período</p>
                </div>
            </article>
        </section>

        <section class="dashboard-panel dashboard-finances dashboard-finances-overview" id="dashboardFinancesPanel" aria-labelledby="dashboardFinancesTitle" data-context-help-focus-target>
            <header class="dashboard-panel-heading">
                <div>
                    <span class="section-kicker">Resultado del período</span>
                    <div class="context-help-heading">
                        <h3 id="dashboardFinancesTitle">Evolución financiera</h3>
                        <?= $contextualHelp([
                            'id'        => 'dashboard-help-finances',
                            'title'     => '¿Qué información muestra esta gráfica?',
                            'targetId'  => 'dashboardFinancesPanel',
                            'anchor'    => 'target',
                            'placement' => 'top',
                            'align'     => 'center',
                            'paragraphs' => [
                                'Reúne hasta siete cierres registrados del período y compara las ventas con el costo de ventas y los gastos. Los borradores no participan.',
                            ],
                        ]) ?>
                    </div>
                </div>
                <span class="period-chip"><?= esc($finance_period_label) ?></span>
            </header>
            <div class="dashboard-finance-visual">
                <div>
                    <div class="dashboard-chart-heading">
                        <span>Últimos cierres confirmados</span>
                        <span class="dashboard-chart-legend"><i></i> Ventas <i></i> Costos y gastos</span>
                    </div>
                    <?php if ($finance_chart_entries === []): ?>
                        <div class="dashboard-chart-empty">
                            <strong>La evolución aparecerá con el primer cierre.</strong>
                            <p>Registrá ventas, costos y gastos para construir la lectura del período.</p>
                        </div>
                    <?php else: ?>
                        <p class="dashboard-chart-hint">Seleccioná una fecha para ver sus valores.</p>
                        <div class="dashboard-chart" aria-label="Evolución de ventas, costos y gastos de los últimos cierres">
                            <?php foreach ($finance_chart_entries as $chartEntry): ?>
                                <div
                                    class="dashboard-chart-column"
                                    role="group"
                                    tabindex="0"
                                    aria-label="<?= esc((string) $chartEntry['label']) ?>: ventas <?= esc($money((int) $chartEntry['sales_cents'])) ?>; costos y gastos <?= esc($money((int) $chartEntry['costs_cents'])) ?>"
                                >
                                    <div class="dashboard-chart-tooltip" aria-hidden="true">
                                        <strong><?= esc((string) $chartEntry['label']) ?></strong>
                                        <dl>
                                            <div>
                                                <dt><i class="is-sales"></i>Ventas</dt>
                                                <dd><?= esc($money((int) $chartEntry['sales_cents'])) ?></dd>
                                            </div>
                                            <div>
                                                <dt><i class="is-costs"></i>Costos y gastos</dt>
                                                <dd><?= esc($money((int) $chartEntry['costs_cents'])) ?></dd>
                                            </div>
                                        </dl>
                                    </div>
                                    <div class="dashboard-chart-bars" aria-hidden="true">
                                        <i style="height: <?= esc((string) $chartEntry['sales_percent']) ?>%"></i>
                                        <b style="height: <?= esc((string) $chartEntry['costs_percent']) ?>%"></b>
                                    </div>
                                    <span><?= esc((string) $chartEntry['label']) ?></span>
                                </div>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>
                </div>
                <dl class="dashboard-finance-totals">
                    <div><dt>Ventas del período</dt><dd><?= $money($finance_totals['sales_cents']) ?></dd></div>
                    <div><dt>Utilidad bruta</dt><dd><?= $money($finance_totals['gross_profit_cents']) ?></dd></div>
                    <div class="finance-total"><dt>EBITDA</dt><dd><?= $money($finance_totals['ebitda_cents']) ?></dd></div>
                </dl>
            </div>
            <a class="dashboard-panel-link" href="<?= site_url('app/finanzas') ?>">Revisar movimientos →</a>
        </section>

        <div class="dashboard-grid">
            <div class="dashboard-column dashboard-column-work">
                <section class="dashboard-panel dashboard-focus" id="dashboardFocusPanel" aria-labelledby="dashboardFocusTitle" data-context-help-focus-target>
                    <header class="dashboard-panel-heading">
                        <div>
                            <span class="section-kicker">Foco actual</span>
                            <div class="context-help-heading">
                                <h3 id="dashboardFocusTitle">Objetivo destacado</h3>
                                <?= $contextualHelp([
                                    'id'        => 'dashboard-help-featured-objective',
                                    'title'     => '¿Por qué aparece este objetivo?',
                                    'targetId'  => 'dashboardFocusPanel',
                                    'anchor'    => 'target',
                                    'placement' => 'top',
                                    'align'     => 'center',
                                    'paragraphs' => [
                                        'Muestra el objetivo activo actualizado más recientemente. Su avance utiliza únicamente las actividades no canceladas.',
                                    ],
                                ]) ?>
                            </div>
                        </div>
                        <a href="<?= site_url('app/objetivos') ?>">Ver objetivos →</a>
                    </header>

                    <?php if ($featured_objective === null): ?>
                        <div class="dashboard-empty">
                            <strong>Todavía no hay objetivos</strong>
                            <p>Creá el primero para convertir el diagnóstico del negocio en un plan de acción.</p>
                            <a class="button button-primary" href="<?= site_url('app/objetivos') ?>">Crear objetivo</a>
                        </div>
                    <?php else: ?>
                        <div class="focus-meta">
                            <span><?= esc((string) $featured_objective['category_label']) ?></span>
                            <span><?= esc((string) $featured_objective['status_label']) ?></span>
                            <time datetime="<?= esc((string) ($featured_objective['target_date'] ?? '')) ?>">
                                Hasta <?= esc($date($featured_objective['target_date'] ?? null)) ?>
                            </time>
                        </div>
                        <h4><?= esc((string) $featured_objective['title']) ?></h4>
                        <p><?= esc((string) ($featured_objective['description'] ?? '')) ?></p>
                        <div class="focus-progress" aria-label="<?= esc((string) $featuredProgress) ?> por ciento completado">
                            <div>
                                <span>Avance del plan</span>
                                <strong><?= esc((string) $featuredProgress) ?>%</strong>
                            </div>
                            <span class="progress-track" aria-hidden="true">
                                <i style="--progress: <?= esc((string) $featuredProgress) ?>%"></i>
                            </span>
                            <small><?= esc((string) $featuredCompleted) ?> de <?= esc((string) $featuredProgressTotal) ?> actividades no canceladas completadas</small>
                        </div>
                    <?php endif ?>
                </section>

                <section class="dashboard-panel dashboard-priorities" id="dashboardPrioritiesPanel" aria-labelledby="dashboardPrioritiesTitle" data-context-help-focus-target>
                    <header class="dashboard-panel-heading">
                        <div>
                            <span class="section-kicker">Carga de trabajo</span>
                            <div class="context-help-heading">
                                <h3 id="dashboardPrioritiesTitle">Prioridades abiertas</h3>
                                <?= $contextualHelp([
                                    'id'        => 'dashboard-help-priorities',
                                    'title'     => '¿Cómo se distribuyen estas prioridades?',
                                    'targetId'  => 'dashboardPrioritiesPanel',
                                    'anchor'    => 'target',
                                    'placement' => 'top',
                                    'align'     => 'center',
                                    'paragraphs' => [
                                        'Clasifica las actividades pendientes o en curso de objetivos activos según su urgencia e importancia. Los valores se actualizan desde las tareas.',
                                    ],
                                    'items' => [
                                        'Hacer ahora: urgente e importante.',
                                        'Planificar: importante y no urgente.',
                                        'Delegar: urgente y de menor impacto.',
                                        'Eliminar: no urgente y de bajo impacto.',
                                    ],
                                ]) ?>
                            </div>
                        </div>
                    </header>
                    <div class="priority-summary">
                        <?php foreach ($priorityLabels as $key => [$label, $description]): ?>
                            <article class="priority-summary-item priority-summary-<?= esc($key) ?>">
                                <span><?= esc((string) ($priority_summary[$key] ?? 0)) ?></span>
                                <div><strong><?= esc($label) ?></strong><small><?= esc($description) ?></small></div>
                            </article>
                        <?php endforeach ?>
                    </div>
                    <a class="dashboard-panel-link" href="<?= site_url('app/prioridades') ?>">Organizar prioridades →</a>
                </section>
            </div>

            <div class="dashboard-column dashboard-column-insight">
                <section class="dashboard-panel dashboard-actions" id="dashboardActionsPanel" aria-labelledby="dashboardActionsTitle" data-context-help-focus-target>
                    <header class="dashboard-panel-heading">
                        <div>
                            <span class="section-kicker">Próximos pasos</span>
                            <div class="context-help-heading">
                                <h3 id="dashboardActionsTitle">Acciones que requieren atención</h3>
                                <?= $contextualHelp([
                                    'id'        => 'dashboard-help-next-actions',
                                    'title'     => '¿Cómo se ordenan estas acciones?',
                                    'targetId'  => 'dashboardActionsPanel',
                                    'anchor'    => 'target',
                                    'placement' => 'top',
                                    'align'     => 'center',
                                    'paragraphs' => [
                                        'Muestra hasta cinco actividades abiertas de objetivos activos: primero las vencidas, luego el cuadrante de prioridad y finalmente la fecha.',
                                    ],
                                ]) ?>
                            </div>
                        </div>
                        <a href="<?= site_url('app/prioridades') ?>">Ver matriz →</a>
                    </header>

                    <?php if ($next_actions === []): ?>
                        <div class="dashboard-empty dashboard-empty-compact">
                            <strong>No hay actividades abiertas</strong>
                            <p>El plan de acción no tiene tareas pendientes o en curso.</p>
                        </div>
                    <?php else: ?>
                        <ol class="next-action-list">
                            <?php foreach ($next_actions as $action): ?>
                                <li>
                                    <span class="action-priority priority-<?= esc((string) $action['quadrant']) ?>" aria-hidden="true"></span>
                                    <div>
                                        <strong><?= esc((string) $action['title']) ?></strong>
                                        <p><?= esc((string) $action['quadrant_label']) ?> · <?= esc((string) $action['status_label']) ?></p>
                                    </div>
                                    <time class="<?= $action['is_overdue'] ? 'is-overdue' : '' ?>" datetime="<?= esc((string) ($action['due_date'] ?? '')) ?>">
                                        <?= $action['is_overdue'] ? 'Vencida · ' : '' ?><?= esc($date($action['due_date'] ?? null)) ?>
                                    </time>
                                </li>
                            <?php endforeach ?>
                        </ol>
                    <?php endif ?>
                </section>

                <section class="dashboard-panel dashboard-business" aria-labelledby="dashboardBusinessTitle">
                    <header class="dashboard-panel-heading">
                        <div>
                            <span class="section-kicker">Contexto compartido</span>
                            <h3 id="dashboardBusinessTitle">Perfil del negocio</h3>
                        </div>
                        <span class="completion-chip"><?= esc((string) $profile_completion) ?>% completo</span>
                    </header>
                    <p><?= esc((string) ($profile['what_it_does'] ?? '')) ?></p>
                    <div class="business-context-grid">
                        <div><small>Clientes</small><strong><?= esc((string) ($profile['customers_served'] ?? 'Sin completar')) ?></strong></div>
                        <div><small>Oferta</small><strong><?= esc((string) ($profile['products_offered'] ?? 'Sin completar')) ?></strong></div>
                    </div>
                    <a class="dashboard-panel-link" href="<?= site_url('app/mi-negocio') ?>">Editar perfil del negocio →</a>
                </section>
            </div>
        </div>
    </main>
</div>
<script src="<?= base_url('assets/js/alpha-shell.js?v=' . filemtime(FCPATH . 'assets/js/alpha-shell.js')) ?>" defer></script>
<script src="<?= base_url('assets/js/contextual-help.js?v=' . filemtime(FCPATH . 'assets/js/contextual-help.js')) ?>" defer></script>
</body>
</html>
