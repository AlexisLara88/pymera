<?php

/**
 * @var array<string, mixed>  $business
 * @var array<string, string> $form
 * @var array<string, string> $errors
 * @var string|null           $operationError
 * @var string|null           $success
 * @var string|null           $onboardingNotice
 * @var int                   $profileCompletion
 * @var bool                  $isOnboarding
 */

$fieldValue = static fn (string $field): string => esc($form[$field] ?? '');
$summaryValue = static fn (string $field): string => trim($form[$field] ?? '') !== ''
    ? esc($form[$field])
    : 'Todavía sin completar';
$fieldError = static fn (string $field): ?string => $errors[$field] ?? null;
$invalid    = static fn (string $field): string => isset($errors[$field]) ? 'true' : 'false';
$fieldLength = static fn (string $field): int => mb_strlen($form[$field] ?? '');
$contextualHelp = static fn (array $configuration): string => view(
    'components/contextual_help',
    ['contextualHelp' => $configuration],
    ['saveData' => false],
);
$businessName = (string) ($business['name'] ?? 'Negocio');
$initialWords = preg_split('/\s+/', trim($businessName)) ?: [];
$businessInitials = '';
$pageTitle = $isOnboarding ? 'Configurar negocio' : 'Perfil del negocio';

foreach (array_slice($initialWords, 0, 2) as $word) {
    $businessInitials .= function_exists('mb_substr')
        ? mb_strtoupper(mb_substr($word, 0, 1))
        : strtoupper(substr($word, 0, 1));
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Perfil funcional del negocio en PyMERA">
    <title><?= esc($pageTitle) ?> — PyMERA</title>
    <?= view('layouts/alpha_frontend_head', ['styles' => [
        'business/profile.css',
        'alpha-shell.css',
        'contextual-help.css',
    ]]) ?>
</head>
<body class="business-module-body">
<div class="business-shell">
    <?= view('layouts/alpha_sidebar', [
        'business'     => $business,
        'activeModule' => 'business',
        'onboarding'   => $isOnboarding,
    ]) ?>

    <main class="module-main">
        <?= view('layouts/alpha_topbar', [
            'business' => $business,
            'eyebrow'  => $isOnboarding ? 'Configuración inicial' : 'Identidad de la empresa',
            'title'    => $pageTitle,
        ]) ?>

        <header class="module-header">
            <div>
                <span class="step-pill"><?= $isOnboarding ? 'Primer paso' : 'Información de la empresa' ?></span>
                <div class="context-help-heading">
                    <h2><?= $isOnboarding ? 'Primero, configuremos tu negocio' : 'Así se presenta tu negocio' ?></h2>
                    <?= $contextualHelp([
                        'id'    => 'business-help-purpose',
                        'title' => '¿Para qué sirve este perfil?',
                        'targetId' => 'businessDiagnosisPanel',
                        'anchor' => 'target',
                        'placement' => 'top',
                        'align' => 'center',
                        'paragraphs' => [
                            'Describe tu negocio una vez y aporta contexto a tus objetivos, prioridades y finanzas.',
                        ],
                    ]) ?>
                </div>
                <p><?= $isOnboarding
                    ? 'Completá las cuatro respuestas mínimas para iniciar el recorrido operativo.'
                    : 'Este perfil aporta contexto a tus objetivos, prioridades, finanzas y capacidades futuras.' ?></p>
            </div>
            <div class="completion-card" id="businessCompletionCard" data-context-help-focus-target>
                <div class="completion-ring" style="--completion: <?= esc((string) $profileCompletion) ?>%">
                    <span><?= esc((string) $profileCompletion) ?>%</span>
                </div>
                <div>
                    <div class="context-help-completion-title">
                        <strong>Perfil del negocio</strong>
                        <?= $contextualHelp([
                            'id'    => 'business-help-completion',
                            'title' => '¿Cuándo está listo mi perfil?',
                            'targetId' => 'businessCompletionCard',
                            'anchor' => 'target',
                            'placement' => 'left',
                            'align' => 'center',
                            'paragraphs' => [
                                'Las primeras cuatro respuestas habilitan el recorrido. El diagnóstico complementario lleva el perfil al 100 %.',
                            ],
                        ]) ?>
                    </div>
                    <small>Información persistente</small>
                </div>
            </div>
        </header>

        <?php if ($success !== null): ?>
            <div class="module-alert module-alert-success" role="status">
                <?= esc($success) ?>
            </div>
        <?php endif ?>

        <?php if ($operationError !== null): ?>
            <div class="module-alert module-alert-error" role="alert">
                <?= esc($operationError) ?>
            </div>
        <?php endif ?>

        <?php if ($errors !== []): ?>
            <div class="module-alert module-alert-error" role="alert">
                Revisá los campos señalados antes de guardar.
            </div>
        <?php endif ?>

        <section class="content-grid alpha-business-overview" aria-label="Resumen del negocio">
            <article class="panel diagnosis-panel" id="businessDiagnosisPanel" data-context-help-focus-target>
                <div class="panel-heading">
                    <div>
                        <span class="section-kicker">Tu punto de partida</span>
                        <h3>Diagnóstico inicial</h3>
                    </div>
                    <button class="button button-ghost" type="button" data-open-business-editor>
                        <?= $isOnboarding ? 'Completar respuestas' : 'Editar perfil' ?>
                    </button>
                </div>

                <div class="question-list">
                    <?php
                    $diagnosisSummary = [
                        'differentiator'            => '¿Qué hace diferente al negocio frente a otras alternativas?',
                        'differentiation_delivery' => '¿Cómo produce o entrega esa diferencia?',
                        'customer_outcome'         => '¿Qué resultado obtiene el cliente?',
                        'purchase_reason'          => '¿Por qué considera que el cliente le compra?',
                        'acquisition_channels'     => '¿Por qué canales llegan actualmente los clientes?',
                    ];
                    ?>
                    <?php $diagnosisNumber = 0; ?>
                    <?php foreach ($diagnosisSummary as $field => $label): ?>
                        <?php $diagnosisNumber++; ?>
                        <article class="question-card">
                            <span class="question-number"><?= str_pad((string) $diagnosisNumber, 2, '0', STR_PAD_LEFT) ?></span>
                            <div class="question-content">
                                <strong><?= esc($label) ?></strong>
                                <p><?= $summaryValue($field) ?></p>
                            </div>
                        </article>
                    <?php endforeach ?>
                </div>

                <div class="panel-actions">
                    <a class="button button-primary" href="<?= site_url('app/objetivos') ?>">
                        Continuar con mis objetivos <span aria-hidden="true">→</span>
                    </a>
                </div>
            </article>

            <aside class="side-stack">
                <article class="panel business-profile-card">
                    <div class="profile-cover">
                        <span class="profile-logo"><?= esc($businessInitials ?: 'N') ?></span>
                    </div>
                    <h3><?= esc($businessName) ?></h3>
                    <p><?= $summaryValue('what_it_does') ?></p>
                    <dl class="profile-facts">
                        <div><dt>Moneda</dt><dd><?= $fieldValue('currency_code') ?></dd></div>
                        <div><dt>Zona</dt><dd><?= $fieldValue('timezone') ?></dd></div>
                        <div><dt>Clientes</dt><dd><?= $summaryValue('customers_served') ?></dd></div>
                        <div><dt>Oferta</dt><dd><?= $summaryValue('products_offered') ?></dd></div>
                    </dl>
                </article>

            </aside>
        </section>

        <div
            class="business-profile-app"
            id="businessProfileApp"
            data-initial-editor-open="<?= $isOnboarding || $errors !== [] || $onboardingNotice !== null ? 'true' : 'false' ?>"
        >
        <details
            class="business-editor"
            id="businessEditor"
            <?= $isOnboarding || $errors !== [] || $onboardingNotice !== null ? 'open' : '' ?>
            :open="editorOpen"
            @toggle="syncEditorState"
        >
            <summary>
                <span>
                    <small><?= $isOnboarding ? 'Configuración inicial' : 'Edición completa' ?></small>
                    <strong><?= $isOnboarding ? 'Crear perfil del negocio' : 'Actualizar perfil y diagnóstico' ?></strong>
                </span>
                <span class="button button-secondary">Abrir formulario</span>
            </summary>

        <form class="business-form" action="<?= site_url('app/mi-negocio') ?>" method="post" novalidate @submit="startSubmitting">
            <?= csrf_field() ?>

            <section class="form-card" id="businessGeneralDataFormCard" aria-labelledby="identityTitle" data-context-help-focus-target>
                <div class="form-card-heading">
                    <span class="section-number">01</span>
                    <div>
                        <p class="eyebrow">Identidad operativa</p>
                        <div class="context-help-heading">
                            <h2 id="identityTitle">Datos generales</h2>
                            <?= $contextualHelp([
                                'id'    => 'business-help-general-data',
                                'title' => '¿Para qué se utilizan la moneda y la zona horaria?',
                                'targetId' => 'businessGeneralDataFormCard',
                                'anchor' => 'target',
                                'placement' => 'top',
                                'align' => 'center',
                                'paragraphs' => [
                                    'La moneda presenta los importes. La zona horaria organiza las fechas y los cierres del negocio.',
                                ],
                            ]) ?>
                        </div>
                        <p>Estos valores acompañarán registros, fechas e indicadores.</p>
                    </div>
                </div>

                <div class="form-grid form-grid-three">
                    <div class="field-group field-span-two">
                        <label for="name">Nombre del negocio</label>
                        <input
                            class="form-control"
                            id="name"
                            name="name"
                            type="text"
                            maxlength="160"
                            value="<?= $fieldValue('name') ?>"
                            aria-invalid="<?= $invalid('name') ?>"
                            required
                        >
                        <?php if ($fieldError('name') !== null): ?>
                            <p class="field-error"><?= esc($fieldError('name')) ?></p>
                        <?php endif ?>
                    </div>

                    <div class="field-group">
                        <label for="currency_code">Moneda</label>
                        <input
                            class="form-control text-uppercase"
                            id="currency_code"
                            name="currency_code"
                            type="text"
                            maxlength="3"
                            value="<?= $fieldValue('currency_code') ?>"
                            aria-invalid="<?= $invalid('currency_code') ?>"
                            placeholder="USD"
                            required
                        >
                        <?php if ($fieldError('currency_code') !== null): ?>
                            <p class="field-error"><?= esc($fieldError('currency_code')) ?></p>
                        <?php endif ?>
                    </div>

                    <div class="field-group field-span-three">
                        <label for="timezone">Zona horaria del negocio</label>
                        <input
                            class="form-control"
                            id="timezone"
                            name="timezone"
                            type="text"
                            maxlength="64"
                            list="timezoneOptions"
                            value="<?= $fieldValue('timezone') ?>"
                            aria-invalid="<?= $invalid('timezone') ?>"
                            placeholder="America/Guayaquil"
                            required
                        >
                        <datalist id="timezoneOptions">
                            <option value="America/Guayaquil"></option>
                            <option value="America/Bogota"></option>
                            <option value="America/Mexico_City"></option>
                            <option value="America/Argentina/Buenos_Aires"></option>
                            <option value="UTC"></option>
                        </datalist>
                        <small>Se almacena como identificador IANA. Los eventos técnicos continúan en UTC.</small>
                        <?php if ($fieldError('timezone') !== null): ?>
                            <p class="field-error"><?= esc($fieldError('timezone')) ?></p>
                        <?php endif ?>
                    </div>
                </div>
            </section>

            <section class="form-card" id="businessMinimumProfileFormCard" aria-labelledby="profileTitle" data-context-help-focus-target>
                <div class="form-card-heading">
                    <span class="section-number">02</span>
                    <div>
                        <p class="eyebrow">Contexto mínimo</p>
                        <div class="context-help-heading">
                            <h2 id="profileTitle">Cómo funciona el negocio</h2>
                            <?= $contextualHelp([
                                'id'    => 'business-help-minimum-profile',
                                'title' => '¿Qué información necesito completar?',
                                'targetId' => 'businessMinimumProfileFormCard',
                                'anchor' => 'target',
                                'placement' => 'top',
                                'align' => 'center',
                                'paragraphs' => [
                                    'Indicá qué hace el negocio, a quién atiende, qué ofrece y qué busca lograr. Estas cuatro respuestas son necesarias para comenzar.',
                                ],
                            ]) ?>
                        </div>
                        <p>Las cuatro respuestas obligatorias forman el perfil mínimo acordado.</p>
                    </div>
                </div>

                <div class="form-grid">
                    <?php
                    $minimumQuestions = [
                        'what_it_does' => [
                            'label' => '¿Qué hace el negocio?',
                            'hint' => 'Describí brevemente su actividad principal.',
                            'help' => [
                                'id' => 'business-help-what-it-does',
                                'title' => '¿Qué conviene describir aquí?',
                                'targetId' => 'businessFieldWhatItDoes',
                                'paragraph' => 'Explicá la actividad principal del negocio de forma sencilla, sin hablar todavía de aquello que lo diferencia.',
                                'example' => 'Elaboramos pasteles y postres personalizados para celebraciones.',
                            ],
                        ],
                        'customers_served' => [
                            'label' => '¿A quién atiende?',
                            'hint' => 'Indicá los tipos de clientes que reciben la propuesta.',
                            'help' => [
                                'id' => 'business-help-customers-served',
                                'title' => '¿A quiénes conviene mencionar?',
                                'targetId' => 'businessFieldCustomersServed',
                                'paragraph' => 'Describí los grupos de personas o negocios que suelen comprar, sin necesidad de enumerar clientes individuales.',
                                'example' => 'Familias, parejas y pequeños negocios que organizan celebraciones.',
                            ],
                        ],
                        'products_offered' => [
                            'label' => '¿Qué productos o servicios ofrece?',
                            'hint' => 'Resumí la oferta actual.',
                            'help' => [
                                'id' => 'business-help-products-offered',
                                'title' => '¿Qué debería incluir en la oferta?',
                                'targetId' => 'businessFieldProductsOffered',
                                'paragraph' => 'Mencioná los principales productos o servicios que el negocio vende actualmente.',
                                'example' => 'Pasteles personalizados, cajas de postres y mesas dulces.',
                            ],
                        ],
                        'objectives_summary' => [
                            'label' => '¿Qué objetivos persigue?',
                            'hint' => 'Contá qué busca conseguir en esta etapa.',
                            'help' => [
                                'id' => 'business-help-objectives-summary',
                                'title' => '¿Qué tipo de objetivos van aquí?',
                                'targetId' => 'businessFieldObjectivesSummary',
                                'paragraph' => 'Resumí los resultados que el negocio quiere conseguir en esta etapa. Después podrán convertirse en objetivos con actividades y fechas.',
                                'example' => 'Aumentar los pedidos y reducir los reclamos por entregas.',
                            ],
                        ],
                    ];
                    ?>
                    <?php foreach ($minimumQuestions as $field => $question): ?>
                        <?php $help = $question['help']; ?>
                        <div
                            class="field-group"
                            id="<?= esc($help['targetId'], 'attr') ?>"
                            data-context-help-focus-target
                        >
                            <div class="field-question-label">
                                <label for="<?= esc($field) ?>"><?= esc($question['label']) ?></label>
                                <?= $contextualHelp([
                                    'id' => $help['id'],
                                    'title' => $help['title'],
                                    'targetId' => $help['targetId'],
                                    'anchor' => 'target',
                                    'placement' => 'top',
                                    'align' => 'center',
                                    'paragraphs' => [$help['paragraph']],
                                    'example' => $help['example'],
                                ]) ?>
                            </div>
                            <textarea
                                class="form-control"
                                id="<?= esc($field) ?>"
                                name="<?= esc($field) ?>"
                                rows="5"
                                maxlength="5000"
                                data-character-count
                                @input="updateCharacterCount"
                                aria-invalid="<?= $invalid($field) ?>"
                                required
                            ><?= $fieldValue($field) ?></textarea>
                            <div class="field-meta">
                                <small><?= esc($question['hint']) ?></small>
                                <small
                                    data-character-output
                                >
                                    <span :hidden="true"><?= $fieldLength($field) ?> / 5000</span>
                                    <span v-text="characterLabel('<?= esc($field) ?>', <?= $fieldLength($field) ?>)"></span>
                                </small>
                            </div>
                            <?php if ($fieldError($field) !== null): ?>
                                <p class="field-error"><?= esc($fieldError($field)) ?></p>
                            <?php endif ?>
                        </div>
                    <?php endforeach ?>
                </div>
            </section>

            <section class="form-card" id="businessGuidedDiagnosisFormCard" aria-labelledby="diagnosisTitle" data-context-help-focus-target>
                <div class="form-card-heading">
                    <span class="section-number">03</span>
                    <div>
                        <p class="eyebrow">Profundización gradual</p>
                        <div class="context-help-heading">
                            <h2 id="diagnosisTitle">Diagnóstico guiado</h2>
                            <?= $contextualHelp([
                                'id'    => 'business-help-diagnosis',
                                'title' => '¿Para qué se utiliza este diagnóstico?',
                                'targetId' => 'businessGuidedDiagnosisFormCard',
                                'anchor' => 'target',
                                'placement' => 'top',
                                'align' => 'center',
                                'paragraphs' => [
                                    'Profundiza por qué te eligen y cómo llegan tus clientes. Podés completarlo más adelante.',
                                ],
                            ]) ?>
                        </div>
                        <p>Podés completar estas respuestas ahora o profundizarlas con el cliente más adelante.</p>
                    </div>
                </div>

                <div class="form-grid">
                    <?php
                    $diagnosisQuestions = [
                        'differentiator' => [
                            'label' => '¿Qué hace diferente al negocio frente a otras alternativas?',
                            'help' => [
                                'id' => 'business-help-differentiator',
                                'title' => '¿Qué significa ser diferente?',
                                'targetId' => 'businessFieldDifferentiator',
                                'paragraph' => 'Explicá la razón concreta por la que un cliente podría reconocer o preferir este negocio frente a otras opciones.',
                                'example' => 'Personalizamos cada pedido y acompañamos al cliente durante la elección.',
                            ],
                        ],
                        'differentiation_delivery' => [
                            'label' => '¿Cómo produce o entrega esa diferencia?',
                            'help' => [
                                'id' => 'business-help-differentiation-delivery',
                                'title' => '¿Cómo se hace realidad esa diferencia?',
                                'targetId' => 'businessFieldDifferentiationDelivery',
                                'paragraph' => 'Contá qué acciones, procesos o formas de atención permiten cumplir aquello que hace especial al negocio.',
                                'example' => 'Realizamos una entrevista, confirmamos el diseño y ofrecemos muestras para pedidos especiales.',
                            ],
                        ],
                        'customer_outcome' => [
                            'label' => '¿Qué resultado obtiene el cliente?',
                            'help' => [
                                'id' => 'business-help-customer-outcome',
                                'title' => '¿Qué resultado debería describirse?',
                                'targetId' => 'businessFieldCustomerOutcome',
                                'paragraph' => 'Describí el beneficio o cambio que recibe el cliente, no solamente el producto que compra.',
                                'example' => 'Recibe un pastel acorde con su celebración y reduce la incertidumbre sobre el resultado final.',
                            ],
                        ],
                        'purchase_reason' => [
                            'label' => '¿Por qué considera que el cliente le compra?',
                            'help' => [
                                'id' => 'business-help-purchase-reason',
                                'title' => '¿Qué motivo de compra buscamos?',
                                'targetId' => 'businessFieldPurchaseReason',
                                'paragraph' => 'Indicá las razones que los clientes expresan o demuestran al elegir el negocio.',
                                'example' => 'Por la personalización, la atención cercana y las recomendaciones de otros clientes.',
                            ],
                        ],
                        'acquisition_channels' => [
                            'label' => '¿Por qué canales llegan actualmente los clientes?',
                            'help' => [
                                'id' => 'business-help-acquisition-channels',
                                'title' => '¿Qué canales hay que registrar?',
                                'targetId' => 'businessFieldAcquisitionChannels',
                                'paragraph' => 'Mencioná las vías por las que las personas descubren o contactan actualmente al negocio.',
                                'example' => 'Instagram, recomendaciones, ubicación del local y búsquedas en internet.',
                            ],
                        ],
                    ];
                    ?>
                    <?php foreach ($diagnosisQuestions as $field => $question): ?>
                        <?php $help = $question['help']; ?>
                        <div
                            class="field-group"
                            id="<?= esc($help['targetId'], 'attr') ?>"
                            data-context-help-focus-target
                        >
                            <div class="field-question-label">
                                <label for="<?= esc($field) ?>"><?= esc($question['label']) ?></label>
                                <?= $contextualHelp([
                                    'id' => $help['id'],
                                    'title' => $help['title'],
                                    'targetId' => $help['targetId'],
                                    'anchor' => 'target',
                                    'placement' => 'top',
                                    'align' => 'center',
                                    'paragraphs' => [$help['paragraph']],
                                    'example' => $help['example'],
                                ]) ?>
                            </div>
                            <textarea
                                class="form-control"
                                id="<?= esc($field) ?>"
                                name="<?= esc($field) ?>"
                                rows="4"
                                maxlength="5000"
                                data-character-count
                                @input="updateCharacterCount"
                                aria-invalid="<?= $invalid($field) ?>"
                            ><?= $fieldValue($field) ?></textarea>
                            <div class="field-meta">
                                <small>Respuesta opcional en esta fase.</small>
                                <small
                                    data-character-output
                                >
                                    <span :hidden="true"><?= $fieldLength($field) ?> / 5000</span>
                                    <span v-text="characterLabel('<?= esc($field) ?>', <?= $fieldLength($field) ?>)"></span>
                                </small>
                            </div>
                            <?php if ($fieldError($field) !== null): ?>
                                <p class="field-error"><?= esc($fieldError($field)) ?></p>
                            <?php endif ?>
                        </div>
                    <?php endforeach ?>
                </div>
            </section>

            <footer class="form-actions">
                <div>
                    <strong><?= esc((string) ($business['name'] ?? 'Negocio')) ?></strong>
                    <small>Los cambios se guardan de forma atómica y quedan atribuidos al usuario autenticado.</small>
                </div>
                <button
                    class="button button-primary"
                    type="submit"
                    :disabled="submitting"
                >
                    <span :hidden="submitting" data-submit-default>Guardar cambios</span>
                    <span hidden :hidden="!submitting" data-submit-progress>Guardando…</span>
                </button>
            </footer>
        </form>
        </details>
        </div>
    </main>
</div>

<?= view('layouts/alpha_frontend_scripts') ?>
<script src="<?= base_url('assets/js/business/profile.js?v=' . filemtime(FCPATH . 'assets/js/business/profile.js')) ?>" defer></script>
<script src="<?= base_url('assets/js/contextual-help.js?v=' . filemtime(FCPATH . 'assets/js/contextual-help.js')) ?>" defer></script>
<script src="<?= base_url('assets/js/alpha-shell.js?v=' . filemtime(FCPATH . 'assets/js/alpha-shell.js')) ?>" defer></script>
</body>
</html>
