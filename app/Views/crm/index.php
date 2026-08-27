<?php

/**
 * @var array<string, mixed>       $business
 * @var list<array<string, mixed>> $contacts
 * @var list<array<string, mixed>> $opportunities
 * @var array<string, int>         $crm_summary
 * @var array<string, array<string, string>> $crm_catalogs
 * @var string|null                $success
 * @var string|null                $operationError
 * @var array<string, string>      $crmErrors
 * @var array<string, mixed>       $crmSubmitted
 * @var string|null                $crmFormKey
 * @var string                     $today
 * @var string                     $crmView
 */

$currency = (string) ($business['currency_code'] ?? 'USD');
$crmView = $crmView === 'tabs' ? 'tabs' : 'combined';
$money = static fn (int $cents): string => $currency . ' '
    . number_format($cents / 100, 2, ',', '.');
$date = static function (?string $value): string {
    if ($value === null || $value === '') {
        return 'Sin fecha';
    }

    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

    return $parsed === false ? $value : $parsed->format('d/m/Y');
};
$jsonAttribute = static fn (array $value): string => esc(
    json_encode(
        $value,
        JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE,
    ) ?: '{}',
    'attr',
);
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
    <meta name="description" content="Clientes, prospectos y oportunidades de <?= esc((string) $business['name'], 'attr') ?>">
    <title>Clientes y ventas — PyMERA</title>
    <?= view('layouts/alpha_frontend_head', ['styles' => [
        'business/profile.css',
        'crm/index.css',
        'alpha-shell.css',
        'contextual-help.css',
    ]]) ?>
</head>
<body class="business-module-body" data-active-crm-form="<?= esc((string) ($crmFormKey ?? ''), 'attr') ?>">
<div class="business-shell">
    <?= view('layouts/alpha_sidebar', [
        'business'     => $business,
        'activeModule' => 'crm',
    ]) ?>

    <main class="module-main crm-main">
        <?= view('layouts/alpha_topbar', [
            'business' => $business,
            'eyebrow'  => 'Seguimiento comercial',
            'title'    => 'Clientes y ventas',
        ]) ?>

        <div class="crm-workflow-content" id="crmWorkflowContent" data-context-help-focus-target>
        <header class="module-header module-header-compact crm-header">
            <div>
                <div class="context-help-heading crm-main-help-heading">
                    <h2>Convertí cada consulta en una oportunidad atendida</h2>
                    <?= $contextualHelp([
                        'id' => 'crm-help-workflow',
                        'title' => '¿Cómo se organiza el seguimiento comercial?',
                        'paragraphs' => [
                            'Primero registrás el contacto. Después creás sus oportunidades, actualizás el estado y definís cuándo retomarlas.',
                            'Una oportunidad ganada afecta Finanzas solamente cuando confirmás el registro de la venta.',
                        ],
                        'targetId' => 'crmWorkflowContent',
                        'anchor' => 'trigger',
                        'placement' => 'right',
                        'align' => 'start',
                    ]) ?>
                </div>
                <p>Organizá contactos, próximos seguimientos y ventas posibles desde un mismo lugar.</p>
            </div>
        </header>

        <?php if ($success !== null): ?>
            <div class="module-alert module-alert-success" role="status"><?= esc($success) ?></div>
        <?php endif ?>

        <?php if ($operationError !== null): ?>
            <div class="module-alert module-alert-error" role="alert"><?= esc($operationError) ?></div>
        <?php endif ?>

        <section class="crm-metrics" id="crmMetrics" data-context-help-focus-target aria-labelledby="crmMetricsTitle">
            <header class="crm-metrics-heading">
                <span class="section-kicker" id="crmMetricsTitle">Resumen comercial</span>
                <?= $contextualHelp([
                    'id' => 'crm-help-summary',
                    'title' => '¿Qué muestran estos indicadores?',
                    'paragraphs' => [
                        'Prospectos y clientes cuentan contactos activos. Las oportunidades abiertas y su valor consideran ventas que todavía siguen en proceso.',
                        'Los seguimientos vencidos usan la fecha del negocio. El valor estimado orienta el seguimiento, pero todavía no es una venta confirmada.',
                    ],
                    'targetId' => 'crmMetrics',
                    'anchor' => 'target',
                    'placement' => 'top',
                    'align' => 'center',
                ]) ?>
            </header>
            <article class="crm-metric crm-metric-prospects">
                <small>Prospectos</small>
                <strong><?= (int) $crm_summary['prospect_count'] ?></strong>
                <span>Contactos por convertir</span>
            </article>
            <article class="crm-metric crm-metric-clients">
                <small>Clientes</small>
                <strong><?= (int) $crm_summary['client_count'] ?></strong>
                <span>Relaciones activas</span>
            </article>
            <article class="crm-metric crm-metric-open">
                <small>Oportunidades abiertas</small>
                <strong><?= (int) $crm_summary['open_opportunity_count'] ?></strong>
                <span>En seguimiento</span>
            </article>
            <article class="crm-metric crm-metric-value">
                <small>Valor estimado</small>
                <strong><?= esc($money((int) $crm_summary['open_value_cents'])) ?></strong>
                <span>Oportunidades abiertas</span>
            </article>
            <article class="crm-metric crm-metric-overdue">
                <small>Seguimientos vencidos</small>
                <strong><?= (int) $crm_summary['overdue_follow_up_count'] ?></strong>
                <span>Requieren atención</span>
            </article>
        </section>

        <?php if ($contacts === []): ?>
            <section class="panel crm-welcome-empty">
                <span class="crm-empty-icon" aria-hidden="true">C</span>
                <div>
                    <h3>Empezá por tu primer contacto</h3>
                    <p>Registrá una persona o empresa interesada. Después podrás crear oportunidades y programar su próximo seguimiento.</p>
                </div>
                <a class="button button-primary" href="#crmContactEditor" data-open-contact-editor>Crear primer contacto</a>
            </section>
        <?php else: ?>
            <section class="crm-view-switcher" data-crm-view-switcher data-crm-view="<?= esc($crmView, 'attr') ?>" aria-label="Información comercial">
                <div class="crm-section-tabs" role="tablist" aria-label="Secciones de clientes y ventas" data-crm-section-tabs hidden>
                    <button
                        class="crm-section-tab is-active"
                        id="crmContactsTab"
                        type="button"
                        role="tab"
                        aria-selected="true"
                        aria-controls="crmContactsPanel"
                        tabindex="0"
                        data-crm-section-tab="contacts"
                    >
                        Contactos <span><?= count($contacts) ?></span>
                    </button>
                    <button
                        class="crm-section-tab"
                        id="crmOpportunitiesTab"
                        type="button"
                        role="tab"
                        aria-selected="false"
                        aria-controls="crmOpportunitiesPanel"
                        tabindex="-1"
                        data-crm-section-tab="opportunities"
                    >
                        Oportunidades <span><?= count($opportunities) ?></span>
                    </button>
                </div>

                <div class="crm-workspace">
                <section
                    class="panel crm-opportunities"
                    id="crmOpportunitiesPanel"
                    role="tabpanel"
                    aria-labelledby="crmOpportunitiesTab"
                    data-crm-section-panel="opportunities"
                    data-crm-filter-app
                >
                    <header class="crm-panel-heading">
                        <div>
                            <span class="section-kicker">Ventas posibles</span>
                            <div class="crm-panel-title-row">
                                <h3>Oportunidades</h3>
                                <?= $contextualHelp([
                                    'id' => 'crm-help-opportunities',
                                    'title' => '¿Qué representa una oportunidad?',
                                    'paragraphs' => [
                                        'Es una venta posible asociada a un contacto. Un mismo contacto puede tener varias necesidades u oportunidades.',
                                        'El valor es una estimación comercial. Archivar quita la oportunidad del seguimiento activo sin borrar su historial.',
                                    ],
                                    'targetId' => 'crmOpportunitiesPanel',
                                    'anchor' => 'target',
                                    'placement' => 'top',
                                    'align' => 'center',
                                ]) ?>
                                <span class="crm-count"><?= count($opportunities) ?> registradas</span>
                            </div>
                        </div>
                        <div class="crm-panel-heading-actions">
                            <a
                                class="button button-primary crm-panel-action"
                                href="#crmOpportunityEditor"
                                data-open-opportunity-editor
                            >+ Nueva oportunidad</a>
                        </div>
                    </header>

                    <div class="crm-filters" aria-label="Filtros de oportunidades">
                        <label class="crm-search">
                            <span>Buscar</span>
                            <input class="form-control" type="search" placeholder="Contacto o necesidad" v-model="search" @input="applyFilters">
                        </label>
                        <label>
                            <span>Etapa</span>
                            <select class="form-control" v-model="stage" @change="applyFilters">
                                <option value="">Todas</option>
                                <?php foreach ($crm_catalogs['lifecycle_stages'] as $value => $label): ?>
                                    <option value="<?= esc($value, 'attr') ?>"><?= esc($label) ?></option>
                                <?php endforeach ?>
                            </select>
                        </label>
                        <label>
                            <span>Estado</span>
                            <select class="form-control" v-model="status" @change="applyFilters">
                                <option value="">Todos</option>
                                <?php foreach ($crm_catalogs['opportunity_statuses'] as $value => $label): ?>
                                    <option value="<?= esc($value, 'attr') ?>"><?= esc($label) ?></option>
                                <?php endforeach ?>
                            </select>
                        </label>
                        <label>
                            <span>Canal</span>
                            <select class="form-control" v-model="channel" @change="applyFilters">
                                <option value="">Todos</option>
                                <?php foreach ($crm_catalogs['acquisition_channels'] as $value => $label): ?>
                                    <option value="<?= esc($value, 'attr') ?>"><?= esc($label) ?></option>
                                <?php endforeach ?>
                            </select>
                        </label>
                        <label>
                            <span>Seguimiento</span>
                            <select class="form-control" v-model="followUp" @change="applyFilters">
                                <option value="">Todos</option>
                                <option value="overdue">Vencido</option>
                                <option value="scheduled">Programado</option>
                                <option value="none">Sin fecha</option>
                            </select>
                        </label>
                    </div>

                    <?php if ($opportunities === []): ?>
                        <div class="crm-table-empty">
                            <strong>Todavía no hay oportunidades</strong>
                            <p>Creá una oportunidad para registrar qué necesita el contacto y cuándo retomarlo.</p>
                        </div>
                    <?php else: ?>
                        <div class="crm-table-wrap" id="crmOpportunitiesTable" data-context-help-focus-target>
                            <table class="crm-table">
                                <thead>
                                <tr>
                                    <th>Contacto</th>
                                    <th>Necesidad</th>
                                    <th>
                                        <span class="crm-table-heading-help">
                                            <span>Estado</span>
                                            <?= $contextualHelp([
                                                'id' => 'crm-help-status',
                                                'title' => '¿Cómo cambia el estado de una oportunidad?',
                                                'paragraphs' => [
                                                    'Podés avanzar entre Nueva, Contactada, Propuesta enviada, Negociación, Ganada o Perdida desde el selector.',
                                                    'El cambio se confirma antes de guardarse. Al marcar Ganada podés registrar la venta; si luego salís de ese estado, también se confirma la reversión financiera.',
                                                ],
                                                'targetId' => 'crmOpportunitiesTable',
                                                'anchor' => 'target',
                                                'placement' => 'top',
                                                'align' => 'center',
                                            ]) ?>
                                        </span>
                                    </th>
                                    <th>
                                        <span class="crm-table-heading-help">
                                            <span>Finanzas</span>
                                            <?= $contextualHelp([
                                                'id' => 'crm-help-finances',
                                                'title' => '¿Cuándo una oportunidad afecta Finanzas?',
                                                'paragraphs' => [
                                                    'Al registrar una oportunidad ganada confirmás el monto y la fecha. Desde ese momento aparece como Incluida y participa en los cálculos financieros.',
                                                    'La nota de venta es un comprobante comercial no fiscal generado al descargar. Si falta el DNI o CI, se solicita y se guarda en el contacto.',
                                                ],
                                                'targetId' => 'crmOpportunitiesTable',
                                                'anchor' => 'target',
                                                'placement' => 'top',
                                                'align' => 'center',
                                            ]) ?>
                                        </span>
                                    </th>
                                    <th>Valor</th>
                                    <th>
                                        <span class="crm-table-heading-help">
                                            <span>Seguimiento</span>
                                            <?= $contextualHelp([
                                                'id' => 'crm-help-follow-up',
                                                'title' => '¿Para qué sirve la fecha de seguimiento?',
                                                'paragraphs' => [
                                                    'Indica cuándo conviene retomar una oportunidad abierta y permite filtrarla como programada, vencida o sin fecha.',
                                                    'Por ahora organiza el trabajo comercial; todavía no envía recordatorios ni guarda un historial de conversaciones.',
                                                ],
                                                'targetId' => 'crmOpportunitiesTable',
                                                'anchor' => 'target',
                                                'placement' => 'top',
                                                'align' => 'center',
                                            ]) ?>
                                        </span>
                                    </th>
                                    <th><span class="visually-hidden">Acciones</span></th>
                                </tr>
                                </thead>
                                <tbody data-crm-opportunity-rows>
                                <?php foreach ($opportunities as $opportunity): ?>
                                    <?php
                                    $contact = $opportunity['contact'];
                                    $contactName = (string) ($contact['display_name'] ?? 'Contacto no disponible');
                                    $channel = (string) ($contact['acquisition_channel'] ?? '');
                                    $stage = (string) ($contact['lifecycle_stage'] ?? '');
                                    $isArchivedContact = (bool) ($contact['is_archived'] ?? false);
                                    $search = mb_strtolower($contactName . ' ' . $opportunity['need']);
                                    $followUp = $opportunity['next_follow_up_date'] === null
                                        ? 'none'
                                        : ($opportunity['is_follow_up_overdue'] ? 'overdue' : 'scheduled');
                                    ?>
                                    <tr
                                        data-crm-opportunity
                                        data-search="<?= esc($search, 'attr') ?>"
                                        data-stage="<?= esc($stage, 'attr') ?>"
                                        data-status="<?= esc((string) $opportunity['status'], 'attr') ?>"
                                        data-channel="<?= esc($channel, 'attr') ?>"
                                        data-follow-up="<?= esc($followUp, 'attr') ?>"
                                    >
                                        <td>
                                            <strong><?= esc($contactName) ?></strong>
                                            <small>
                                                <?= esc((string) ($contact['acquisition_channel_label'] ?? 'Sin especificar')) ?>
                                                <?= $isArchivedContact ? ' · Archivado' : '' ?>
                                            </small>
                                        </td>
                                        <td><?= esc((string) $opportunity['need']) ?></td>
                                        <td>
                                            <form
                                                class="crm-status-quick-form"
                                                action="<?= site_url('app/clientes/oportunidades/' . $opportunity['id'] . '/estado') ?>"
                                                method="post"
                                                data-crm-status-form
                                                data-crm-return-context
                                                data-opportunity="<?= $jsonAttribute($opportunity) ?>"
                                            >
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="finance_action" value="none">
                                                <label>
                                                    <span class="visually-hidden">Estado de <?= esc((string) $opportunity['need']) ?></span>
                                                    <select
                                                        class="crm-status-select crm-status-<?= esc((string) $opportunity['status'], 'attr') ?>"
                                                        name="status"
                                                        data-current-status="<?= esc((string) $opportunity['status'], 'attr') ?>"
                                                        aria-label="Cambiar estado de <?= esc((string) $opportunity['need'], 'attr') ?>"
                                                    >
                                                        <?php foreach ($crm_catalogs['opportunity_statuses'] as $value => $label): ?>
                                                            <option value="<?= esc($value, 'attr') ?>" <?= $opportunity['status'] === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                                                        <?php endforeach ?>
                                                    </select>
                                                </label>
                                                <noscript><button type="submit">Guardar</button></noscript>
                                            </form>
                                        </td>
                                        <td class="crm-finance-cell">
                                            <?php if (($opportunity['finance_posting']['status'] ?? null) === 'recorded'): ?>
                                                <small class="crm-finance-link" aria-label="Venta incluida en Finanzas">Incluida</small>
                                            <?php elseif ($opportunity['status'] === 'won'): ?>
                                                <button class="crm-finance-register" type="button" data-crm-register-sale>Registrar venta</button>
                                            <?php else: ?>
                                                <span class="crm-finance-empty" title="Sin vínculo financiero" aria-label="Sin vínculo financiero">—</span>
                                            <?php endif ?>
                                        </td>
                                        <td><?= esc($money((int) $opportunity['estimated_value_cents'])) ?></td>
                                        <td>
                                            <time class="<?= $opportunity['is_follow_up_overdue'] ? 'is-overdue' : '' ?>" datetime="<?= esc((string) ($opportunity['next_follow_up_date'] ?? ''), 'attr') ?>">
                                                <?= esc($date($opportunity['next_follow_up_date'])) ?>
                                            </time>
                                        </td>
                                        <td>
                                            <div class="crm-row-actions">
                                                <?php if (! $isArchivedContact): ?>
                                                    <a
                                                        class="crm-icon-action crm-icon-action-edit"
                                                        href="#crmOpportunityEditor"
                                                        title="Editar"
                                                        aria-label="Editar oportunidad"
                                                        data-edit-opportunity="<?= $jsonAttribute($opportunity) ?>"
                                                    >
                                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4l11-11a2.8 2.8 0 0 0-4-4L4 16v4Z"></path><path d="m13.5 6.5 4 4"></path></svg>
                                                        <span class="visually-hidden">Editar</span>
                                                    </a>
                                                <?php endif ?>
                                                <?php if ($opportunity['status'] === 'won' && ($opportunity['finance_posting']['status'] ?? null) === 'recorded'): ?>
                                                    <a
                                                        class="crm-icon-action crm-icon-action-download"
                                                        href="<?= site_url('app/clientes/oportunidades/' . $opportunity['id'] . '/nota-venta') ?>"
                                                        title="Descargar"
                                                        aria-label="Descargar nota de venta"
                                                        data-crm-sale-note
                                                        data-sale-note="<?= $jsonAttribute([
                                                            'opportunity_id' => (int) $opportunity['id'],
                                                            'contact_name' => $contactName,
                                                            'identity_document' => (string) ($contact['identity_document'] ?? ''),
                                                        ]) ?>"
                                                    >
                                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v11"></path><path d="m7.5 10 4.5 4.5 4.5-4.5"></path><path d="M5 20h14"></path></svg>
                                                        <span class="visually-hidden">Descargar</span>
                                                    </a>
                                                <?php endif ?>
                                                <form
                                                    action="<?= site_url('app/clientes/oportunidades/' . $opportunity['id'] . '/archivar') ?>"
                                                    method="post"
                                                    data-confirm-form
                                                    data-crm-return-context
                                                    data-confirm-title="Archivar oportunidad"
                                                    data-confirm-message="¿Querés archivar la oportunidad <?= esc((string) $opportunity['need'], 'attr') ?>? Dejará de aparecer en el seguimiento activo."
                                                    data-confirm-label="Sí, archivar"
                                                >
                                                    <?= csrf_field() ?>
                                                    <button class="crm-icon-action crm-icon-action-archive" type="submit" title="Archivar" aria-label="Archivar oportunidad">
                                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v13H4V7Z"></path><path d="M3 4h18v3H3V4Z"></path><path d="M9 11h6"></path></svg>
                                                        <span class="visually-hidden">Archivar</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="crm-filter-empty" data-crm-filter-empty hidden>
                            <strong>No hay oportunidades con esos filtros.</strong>
                            <button type="button" @click="clearFilters">Limpiar filtros</button>
                        </div>
                    <?php endif ?>
                </section>

                <aside
                    class="panel crm-contacts"
                    id="crmContactsPanel"
                    role="tabpanel"
                    aria-labelledby="crmContactsTab crmContactsTitle"
                    data-crm-section-panel="contacts"
                    data-crm-contact-search-app
                >
                    <header class="crm-panel-heading">
                        <div>
                            <span class="section-kicker">Directorio</span>
                            <div class="crm-panel-title-row">
                                <h3 id="crmContactsTitle">Contactos</h3>
                                <?= $contextualHelp([
                                    'id' => 'crm-help-contacts',
                                    'title' => '¿Cómo se clasifica un contacto?',
                                    'paragraphs' => [
                                        'Persona o empresa describe qué tipo de contacto es. Prospecto o cliente indica la etapa de la relación comercial.',
                                        'Convertir conserva sus datos y oportunidades. Archivar lo quita del directorio activo y se bloquea si todavía tiene oportunidades abiertas.',
                                    ],
                                    'targetId' => 'crmContactsPanel',
                                    'anchor' => 'target',
                                    'placement' => 'top',
                                    'align' => 'center',
                                ]) ?>
                                <span class="crm-count"><?= count($contacts) ?></span>
                            </div>
                        </div>
                        <div class="crm-panel-heading-actions">
                            <a
                                class="button button-secondary crm-panel-action"
                                href="#crmContactEditor"
                                data-open-contact-editor
                            >+ Nuevo contacto</a>
                        </div>
                    </header>
                    <div class="crm-contact-search">
                        <span class="crm-contact-search-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false">
                                <circle cx="11" cy="11" r="6.5"></circle>
                                <path d="m16 16 4 4"></path>
                            </svg>
                        </span>
                        <input
                            class="form-control"
                            type="search"
                            placeholder="Buscar"
                            aria-label="Buscar contacto por nombre"
                            autocomplete="off"
                            v-model="contactSearch"
                            @input="applyContactSearch"
                        >
                    </div>
                    <div class="crm-contact-list">
                        <?php foreach ($contacts as $contact): ?>
                            <article
                                class="crm-contact-card"
                                data-crm-contact-card
                                data-contact-name="<?= esc((string) $contact['display_name'], 'attr') ?>"
                            >
                                <header>
                                    <span class="crm-contact-avatar" aria-hidden="true"><?= esc(mb_strtoupper(mb_substr((string) $contact['display_name'], 0, 1))) ?></span>
                                    <div>
                                        <strong><?= esc((string) $contact['display_name']) ?></strong>
                                        <small><?= esc((string) $contact['contact_kind_label']) ?> · <?= esc((string) $contact['acquisition_channel_label']) ?></small>
                                    </div>
                                    <span class="crm-stage crm-stage-<?= esc((string) $contact['lifecycle_stage'], 'attr') ?>"><?= esc((string) $contact['lifecycle_stage_label']) ?></span>
                                </header>
                                <?php if ($contact['email'] !== null || $contact['phone'] !== null): ?>
                                    <p><?= esc((string) ($contact['email'] ?? $contact['phone'])) ?></p>
                                <?php endif ?>
                                <footer>
                                    <a class="crm-contact-action crm-contact-action-edit" href="#crmContactEditor" data-edit-contact="<?= $jsonAttribute($contact) ?>">Editar</a>
                                    <?php if ($contact['lifecycle_stage'] === 'prospect'): ?>
                                        <form
                                            class="crm-contact-action-form"
                                            action="<?= site_url('app/clientes/contactos/' . $contact['id'] . '/convertir') ?>"
                                            method="post"
                                            data-confirm-form
                                            data-crm-return-context
                                            data-confirm-title="Convertir en cliente"
                                            data-confirm-message="¿Querés convertir a <?= esc((string) $contact['display_name'], 'attr') ?> en cliente? Sus datos y oportunidades se conservarán."
                                            data-confirm-label="Sí, convertir"
                                        >
                                            <?= csrf_field() ?>
                                            <button class="crm-contact-action crm-contact-action-convert" type="submit">Convertir en cliente</button>
                                        </form>
                                    <?php endif ?>
                                    <form
                                        class="crm-contact-action-form"
                                        action="<?= site_url('app/clientes/contactos/' . $contact['id'] . '/archivar') ?>"
                                        method="post"
                                        data-confirm-form
                                        data-crm-return-context
                                        data-confirm-title="Archivar contacto"
                                        data-confirm-message="¿Querés archivar a <?= esc((string) $contact['display_name'], 'attr') ?>? Dejará de aparecer en el directorio sin eliminar su historial."
                                        data-confirm-label="Sí, archivar"
                                    >
                                        <?= csrf_field() ?>
                                        <button class="crm-contact-action crm-contact-action-archive" type="submit">Archivar</button>
                                    </form>
                                </footer>
                            </article>
                        <?php endforeach ?>
                    </div>
                    <div class="crm-contact-search-empty" data-crm-contact-search-empty hidden>
                        <strong>No encontramos ese contacto.</strong>
                        <button type="button" @click="clearContactSearch">Limpiar búsqueda</button>
                    </div>
                </aside>
                </div>
            </section>
        <?php endif ?>
        </div>
    </main>
</div>

<?= view('crm/_editors', [
    'contacts'     => $contacts,
    'crm_catalogs' => $crm_catalogs,
    'crmErrors'    => $crmErrors,
    'crmSubmitted' => $crmSubmitted,
    'crmFormKey'   => $crmFormKey,
    'today'        => $today,
    'currency'     => $currency,
]) ?>

<?= view('layouts/alpha_frontend_scripts') ?>
<script src="<?= base_url('assets/js/crm/index.js?v=' . filemtime(FCPATH . 'assets/js/crm/index.js')) ?>" defer></script>
<script src="<?= base_url('assets/js/contextual-help.js?v=' . filemtime(FCPATH . 'assets/js/contextual-help.js')) ?>" defer></script>
<script src="<?= base_url('assets/js/alpha-shell.js?v=' . filemtime(FCPATH . 'assets/js/alpha-shell.js')) ?>" defer></script>
</body>
</html>
