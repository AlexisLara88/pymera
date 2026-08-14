<?php

/**
 * @var list<array<string, mixed>> $contacts
 * @var array<string, array<string, string>> $crm_catalogs
 * @var array<string, string>      $crmErrors
 * @var array<string, mixed>       $crmSubmitted
 * @var string|null                $crmFormKey
 * @var string                     $today
 * @var string                     $currency
 */

$contactFields = [
    'display_name',
    'contact_kind',
    'lifecycle_stage',
    'acquisition_channel',
    'email',
    'phone',
    'notes',
];
$opportunityFields = [
    'contact_id',
    'need',
    'status',
    'estimated_value',
    'next_follow_up_date',
    'notes',
];
$initialType = str_starts_with((string) $crmFormKey, 'contact-')
    || $crmFormKey === 'create-contact'
        ? 'contact'
        : (str_starts_with((string) $crmFormKey, 'opportunity-')
            || $crmFormKey === 'create-opportunity'
                ? 'opportunity'
                : '');
$initialId = preg_match('/^(?:contact|opportunity)-(\d+)$/', (string) $crmFormKey, $matches) === 1
    ? (int) $matches[1]
    : 0;
$allowedInitialFields = $initialType === 'contact' ? $contactFields : $opportunityFields;
$initialPayload = array_intersect_key($crmSubmitted, array_flip($allowedInitialFields));
$initialConfig = [
    'type'    => $initialType,
    'id'      => $initialId,
    'payload' => $initialPayload,
];
$initialJson = esc(
    json_encode(
        $initialConfig,
        JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE,
    ) ?: '{}',
    'attr',
);
$formError = static fn (string $field, string $type): ?string => $initialType === $type
    ? ($crmErrors[$field] ?? null)
    : null;
$submittedValue = static function (string $field, string $type, string $fallback = '') use (
    $initialType,
    $initialPayload,
): string {
    return $initialType === $type
        ? esc((string) ($initialPayload[$field] ?? $fallback))
        : esc($fallback);
};
?>
<div
    class="crm-editor-app"
    id="crmEditorApp"
    data-initial-editor="<?= $initialJson ?>"
    data-contact-create-url="<?= esc(site_url('app/clientes/contactos'), 'attr') ?>"
    data-opportunity-create-url="<?= esc(site_url('app/clientes/oportunidades'), 'attr') ?>"
>
    <div
        class="crm-modal-layer"
        id="crmContactEditor"
        :class="{ 'is-open': contactOpen }"
        :aria-hidden="contactOpen ? 'false' : 'true'"
        @click.self="closeContact"
        @keydown="handleKeydown($event, 'contact')"
    >
        <section class="crm-modal" role="dialog" aria-modal="true" aria-labelledby="crmContactEditorTitle" tabindex="-1" ref="contactDialog">
            <header class="crm-modal-header">
                <div>
                    <span class="section-kicker">Contacto comercial</span>
                    <h2 id="crmContactEditorTitle" ref="contactTitle">Nuevo contacto</h2>
                    <p>Guardá la información necesaria para reconocerlo y darle continuidad.</p>
                </div>
                <a class="crm-modal-close" href="#" aria-label="Cerrar" @click.prevent="closeContact"><span aria-hidden="true"></span></a>
            </header>

            <form
                class="crm-editor-form"
                action="<?= site_url('app/clientes/contactos') ?>"
                method="post"
                ref="contactForm"
                data-crm-return-context
                novalidate
            >
                <?= csrf_field() ?>
                <div class="crm-form-grid">
                    <label class="is-wide">
                        <span>Nombre de la persona o empresa</span>
                        <input class="form-control" name="display_name" maxlength="160" required v-model="contact.display_name" value="<?= $submittedValue('display_name', 'contact') ?>" aria-invalid="<?= $formError('display_name', 'contact') !== null ? 'true' : 'false' ?>">
                        <?php if ($formError('display_name', 'contact') !== null): ?><small class="field-error"><?= esc($formError('display_name', 'contact')) ?></small><?php endif ?>
                    </label>
                    <label>
                        <span>Tipo</span>
                        <select class="form-control" name="contact_kind" v-model="contact.contact_kind">
                            <?php foreach ($crm_catalogs['contact_kinds'] as $value => $label): ?>
                                <option value="<?= esc($value, 'attr') ?>" <?= $submittedValue('contact_kind', 'contact', 'person') === esc($value) ? 'selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach ?>
                        </select>
                    </label>
                    <label>
                        <span>Etapa</span>
                        <select class="form-control" name="lifecycle_stage" v-model="contact.lifecycle_stage">
                            <?php foreach ($crm_catalogs['lifecycle_stages'] as $value => $label): ?>
                                <option value="<?= esc($value, 'attr') ?>" <?= $submittedValue('lifecycle_stage', 'contact', 'prospect') === esc($value) ? 'selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach ?>
                        </select>
                    </label>
                    <label>
                        <span>Canal de llegada</span>
                        <select class="form-control" name="acquisition_channel" v-model="contact.acquisition_channel">
                            <option value="">Sin especificar</option>
                            <?php foreach ($crm_catalogs['acquisition_channels'] as $value => $label): ?>
                                <option value="<?= esc($value, 'attr') ?>" <?= $submittedValue('acquisition_channel', 'contact') === esc($value) ? 'selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach ?>
                        </select>
                    </label>
                    <label>
                        <span>Correo</span>
                        <input class="form-control" name="email" type="email" maxlength="254" v-model="contact.email" value="<?= $submittedValue('email', 'contact') ?>" aria-invalid="<?= $formError('email', 'contact') !== null ? 'true' : 'false' ?>">
                        <?php if ($formError('email', 'contact') !== null): ?><small class="field-error"><?= esc($formError('email', 'contact')) ?></small><?php endif ?>
                    </label>
                    <label>
                        <span>Teléfono</span>
                        <input class="form-control" name="phone" maxlength="40" v-model="contact.phone" value="<?= $submittedValue('phone', 'contact') ?>">
                    </label>
                    <label class="is-wide">
                        <span>Notas</span>
                        <textarea class="form-control" name="notes" rows="3" maxlength="2000" v-model="contact.notes"></textarea>
                    </label>
                </div>
                <footer class="crm-modal-actions">
                    <a class="button button-ghost" href="#" @click.prevent="closeContact">Cancelar</a>
                    <button class="button button-primary" type="submit" ref="contactSubmit">Crear contacto</button>
                </footer>
            </form>
        </section>
    </div>

    <div
        class="crm-modal-layer"
        id="crmOpportunityEditor"
        :class="{ 'is-open': opportunityOpen }"
        :aria-hidden="opportunityOpen ? 'false' : 'true'"
        @click.self="closeOpportunity"
        @keydown="handleKeydown($event, 'opportunity')"
    >
        <section class="crm-modal" role="dialog" aria-modal="true" aria-labelledby="crmOpportunityEditorTitle" tabindex="-1" ref="opportunityDialog">
            <header class="crm-modal-header">
                <div>
                    <span class="section-kicker">Seguimiento de venta</span>
                    <h2 id="crmOpportunityEditorTitle" ref="opportunityTitle">Nueva oportunidad</h2>
                    <p>Registrá qué necesita el contacto, el valor posible y cuándo retomarlo.</p>
                </div>
                <a class="crm-modal-close" href="#" aria-label="Cerrar" @click.prevent="closeOpportunity"><span aria-hidden="true"></span></a>
            </header>

            <form
                class="crm-editor-form"
                action="<?= site_url('app/clientes/oportunidades') ?>"
                method="post"
                ref="opportunityForm"
                data-crm-return-context
                novalidate
            >
                <?= csrf_field() ?>
                <div class="crm-form-grid">
                    <label class="is-wide">
                        <span>Contacto</span>
                        <select class="form-control" name="contact_id" required v-model="opportunity.contact_id" aria-invalid="<?= $formError('contact_id', 'opportunity') !== null ? 'true' : 'false' ?>">
                            <option value="">Seleccioná un contacto</option>
                            <?php foreach ($contacts as $contact): ?>
                                <option value="<?= (int) $contact['id'] ?>" <?= $submittedValue('contact_id', 'opportunity') === (string) $contact['id'] ? 'selected' : '' ?>><?= esc((string) $contact['display_name']) ?></option>
                            <?php endforeach ?>
                        </select>
                        <?php if ($formError('contact_id', 'opportunity') !== null): ?><small class="field-error"><?= esc($formError('contact_id', 'opportunity')) ?></small><?php endif ?>
                    </label>
                    <label class="is-wide">
                        <span>Necesidad o servicio de interés</span>
                        <input class="form-control" name="need" maxlength="180" required v-model="opportunity.need" value="<?= $submittedValue('need', 'opportunity') ?>" aria-invalid="<?= $formError('need', 'opportunity') !== null ? 'true' : 'false' ?>">
                        <?php if ($formError('need', 'opportunity') !== null): ?><small class="field-error"><?= esc($formError('need', 'opportunity')) ?></small><?php endif ?>
                    </label>
                    <label>
                        <span>Estado</span>
                        <select class="form-control" name="status" v-model="opportunity.status" :disabled="opportunity.id > 0" data-opportunity-status-field>
                            <?php foreach ($crm_catalogs['opportunity_statuses'] as $value => $label): ?>
                                <option value="<?= esc($value, 'attr') ?>" <?= $submittedValue('status', 'opportunity', 'new') === esc($value) ? 'selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach ?>
                        </select>
                        <small v-if="opportunity.id > 0">El estado se actualiza desde la lista de oportunidades.</small>
                    </label>
                    <label>
                        <span>Valor estimado</span>
                        <input class="form-control" name="estimated_value" inputmode="decimal" placeholder="0,00" v-model="opportunity.estimated_value" value="<?= $submittedValue('estimated_value', 'opportunity') ?>" aria-invalid="<?= $formError('estimated_value', 'opportunity') !== null ? 'true' : 'false' ?>">
                        <?php if ($formError('estimated_value', 'opportunity') !== null): ?><small class="field-error"><?= esc($formError('estimated_value', 'opportunity')) ?></small><?php endif ?>
                    </label>
                    <label>
                        <span>Próximo seguimiento</span>
                        <input class="form-control" name="next_follow_up_date" type="date" v-model="opportunity.next_follow_up_date" value="<?= $submittedValue('next_follow_up_date', 'opportunity') ?>" aria-invalid="<?= $formError('next_follow_up_date', 'opportunity') !== null ? 'true' : 'false' ?>">
                        <?php if ($formError('next_follow_up_date', 'opportunity') !== null): ?><small class="field-error"><?= esc($formError('next_follow_up_date', 'opportunity')) ?></small><?php endif ?>
                    </label>
                    <label class="is-wide">
                        <span>Notas</span>
                        <textarea class="form-control" name="notes" rows="3" maxlength="2000" v-model="opportunity.notes"></textarea>
                    </label>
                </div>
                <footer class="crm-modal-actions">
                    <a class="button button-ghost" href="#" @click.prevent="closeOpportunity">Cancelar</a>
                    <button class="button button-primary" type="submit" ref="opportunitySubmit">Crear oportunidad</button>
                </footer>
            </form>
        </section>
    </div>
</div>

<div
    class="crm-status-app"
    id="crmStatusApp"
    data-status-base-url="<?= esc(site_url('app/clientes/oportunidades'), 'attr') ?>"
    data-today="<?= esc($today, 'attr') ?>"
    data-currency="<?= esc($currency, 'attr') ?>"
>
    <div
        class="crm-modal-layer crm-status-modal-layer"
        :class="{ 'is-open': open }"
        :aria-hidden="open ? 'false' : 'true'"
        @click.self="close"
        @keydown="handleKeydown"
    >
        <section class="crm-modal crm-status-modal" role="dialog" aria-modal="true" aria-labelledby="crmStatusTitle" aria-describedby="crmStatusDescription" tabindex="-1" ref="dialog">
            <header class="crm-modal-header">
                <div>
                    <span class="section-kicker">Seguimiento comercial</span>
                    <h2 id="crmStatusTitle" v-text="dialogTitle"></h2>
                    <p id="crmStatusDescription" v-text="dialogDescription"></p>
                </div>
                <button class="crm-modal-close" type="button" aria-label="Cerrar" @click="close"><span aria-hidden="true"></span></button>
            </header>

            <form action="" method="post" ref="form" data-crm-return-context @submit="submitting = true">
                <?= csrf_field() ?>
                <input type="hidden" name="status" :value="targetStatus">
                <input type="hidden" name="finance_action" :value="financeAction">

                <div class="crm-status-change-summary">
                    <span v-text="contactName"></span>
                    <strong v-text="opportunityNeed"></strong>
                    <p><span v-text="currentStatusLabel"></span><b aria-hidden="true">→</b><span v-text="targetStatusLabel"></span></p>
                </div>

                <div v-if="canOfferFinance" class="crm-finance-choice">
                    <label class="crm-finance-toggle">
                        <input type="checkbox" v-model="recordInFinance">
                        <span>
                            <strong>Registrar también en Finanzas</strong>
                            <small>La venta se sumará al cierre de la fecha indicada.</small>
                        </span>
                    </label>
                    <div v-if="recordInFinance" class="crm-finance-fields">
                        <label>
                            <span>Monto final de la venta (<?= esc($currency) ?>)</span>
                            <input class="form-control" name="sale_amount" inputmode="decimal" pattern="\d{1,12}([.,]\d{1,2})?" :required="recordInFinance" v-model="saleAmount">
                        </label>
                        <label>
                            <span>Fecha de la venta</span>
                            <input class="form-control" name="sale_date" type="date" :required="recordInFinance" v-model="saleDate">
                        </label>
                    </div>
                    <p class="crm-finance-note">El valor estimado sirve como referencia. Confirmá el monto realmente vendido antes de guardar.</p>
                </div>

                <div v-if="requiresReversal" class="crm-reversal-warning" role="note">
                    <strong>Esta venta ya está incluida en Finanzas.</strong>
                    <p>Al confirmar el nuevo estado, el monto vinculado se restará del cierre correspondiente sin borrar el historial.</p>
                </div>

                <footer class="crm-modal-actions">
                    <button class="button button-ghost" type="button" @click="close">Cancelar</button>
                    <button class="button button-primary" type="submit" :disabled="submitting">
                        <span v-text="submitting ? 'Guardando…' : confirmLabel"></span>
                    </button>
                </footer>
            </form>
        </section>
    </div>
</div>

<div class="crm-confirm-layer" id="crmConfirmLayer" aria-hidden="true">
    <section class="crm-confirm-dialog" role="alertdialog" aria-modal="true" aria-labelledby="crmConfirmTitle" aria-describedby="crmConfirmMessage" tabindex="-1">
        <span class="crm-confirm-icon" aria-hidden="true">?</span>
        <div>
            <h2 id="crmConfirmTitle">Confirmar acción</h2>
            <p id="crmConfirmMessage">Revisá la acción antes de continuar.</p>
        </div>
        <footer>
            <button class="button button-ghost" type="button" data-confirm-cancel>Cancelar</button>
            <button class="button button-primary" type="button" data-confirm-accept>Confirmar</button>
        </footer>
    </section>
</div>
