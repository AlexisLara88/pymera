<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\BusinessPermissionCatalog;
use App\Domain\CrmCatalog;
use App\Exceptions\BusinessAccessException;
use App\Exceptions\CrmOperationException;
use App\Exceptions\CrmValidationException;
use App\Models\CrmFinancialPostingModel;
use App\Models\FinancialDailyEntryModel;
use App\Models\OpportunityModel;
use CodeIgniter\Database\BaseConnection;
use DateTimeImmutable;
use Throwable;

final class OpportunityStatusService
{
    public function __construct(
        private ?AlphaBusinessContext $context = null,
        private ?BusinessAuthorizationService $authorization = null,
        private ?AuditEventRecorder $audit = null,
        private ?OpportunityModel $opportunities = null,
        private ?FinancialDailyEntryModel $entries = null,
        private ?CrmFinancialPostingModel $postings = null,
        private ?BaseConnection $database = null,
    ) {
        $this->context       ??= new AlphaBusinessContext();
        $this->authorization ??= new BusinessAuthorizationService($this->context);
        $this->audit         ??= new AuditEventRecorder($this->context);
        $this->opportunities ??= model(OpportunityModel::class);
        $this->entries       ??= model(FinancialDailyEntryModel::class);
        $this->postings      ??= model(CrmFinancialPostingModel::class);
        $this->database      ??= db_connect();
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array{status_changed: bool, finance_recorded: bool, finance_reversed: bool}
     */
    public function change(int $opportunityId, array $input): array
    {
        $this->authorization->require(BusinessPermissionCatalog::CRM_MANAGE);

        $businessId  = $this->context->businessId();
        $opportunity = $this->ownedOpportunity($opportunityId, $businessId);
        $data        = $this->validatedData($input);
        $posting     = $this->postings->findForOpportunity($opportunityId, $businessId);
        $isRecorded  = ($posting['status'] ?? null) === 'recorded';

        if ($data['finance_action'] === 'record') {
            $this->authorization->require(BusinessPermissionCatalog::FINANCES_MANAGE);

            if ($data['status'] !== 'won') {
                throw new CrmValidationException([
                    'status' => 'La venta sólo puede registrarse cuando la oportunidad queda ganada.',
                ]);
            }

            if ($isRecorded) {
                throw new CrmValidationException([
                    'finance_action' => 'Esta oportunidad ya está registrada en Finanzas.',
                ]);
            }
        }

        if ($isRecorded && $data['status'] !== 'won') {
            $this->authorization->require(BusinessPermissionCatalog::FINANCES_MANAGE);

            if ($data['finance_action'] !== 'reverse') {
                throw new CrmValidationException([
                    'finance_action' => 'Confirmá la reversión de la venta vinculada antes de cambiar el estado.',
                ]);
            }
        }

        if ($data['finance_action'] === 'reverse' && ! $isRecorded) {
            throw new CrmValidationException([
                'finance_action' => 'La oportunidad no tiene una venta activa para revertir.',
            ]);
        }

        if ($data['finance_action'] === 'reverse' && $data['status'] === 'won') {
            throw new CrmValidationException([
                'status' => 'Elegí un estado distinto de Ganada para revertir la venta.',
            ]);
        }

        return $this->transaction(function () use (
            $businessId,
            $opportunityId,
            $opportunity,
            $data,
            $posting,
        ): array {
            $statusChanged   = (string) $opportunity['status'] !== $data['status'];
            $financeRecorded = false;
            $financeReversed = false;

            if ($data['finance_action'] === 'record') {
                $this->recordSale($businessId, $opportunityId, $data, $posting);
                $financeRecorded = true;
            } elseif ($data['finance_action'] === 'reverse' && $posting !== null) {
                $this->reverseSale($businessId, $posting);
                $financeReversed = true;
            }

            if ($statusChanged) {
                if (! $this->opportunities->update($opportunityId, ['status' => $data['status']])) {
                    throw new CrmOperationException('No fue posible actualizar el estado de la oportunidad.');
                }

                $this->audit->record('opportunity', $opportunityId, 'status_changed');
            }

            return [
                'status_changed'   => $statusChanged,
                'finance_recorded' => $financeRecorded,
                'finance_reversed' => $financeReversed,
            ];
        });
    }

    /**
     * @param array{status: string, finance_action: string, sale_amount: string|null, sale_date: string|null} $data
     * @param array<string, mixed>|null $posting
     */
    private function recordSale(
        int $businessId,
        int $opportunityId,
        array $data,
        ?array $posting,
    ): void {
        $amount = $data['sale_amount'];
        $date   = $data['sale_date'];

        if ($amount === null || $date === null) {
            throw new CrmValidationException([
                'sale_amount' => 'Ingresá el monto y la fecha de la venta.',
            ]);
        }

        $entry = $this->entries->findForDate($businessId, $date);

        if ($entry !== null && $entry['status'] === 'draft' && ! $this->isZeroEntry($entry)) {
            throw new CrmValidationException([
                'sale_date' => 'Existe un cierre en borrador para esa fecha. Confirmalo antes de vincular la venta.',
            ]);
        }

        $entryCreated = false;

        if ($entry === null) {
            $entryId = $this->entries->insert([
                'business_id'                  => $businessId,
                'operation_date'               => $date,
                'income_amount'                => $amount,
                'fixed_expense_amount'         => '0.00',
                'variable_expense_amount'      => '0.00',
                'administrative_expense_amount' => '0.00',
                'status'                       => 'recorded',
                'notes'                        => null,
            ], true);

            if ($entryId === false) {
                throw new CrmOperationException('No fue posible crear el cierre financiero de la venta.');
            }

            $entryId      = (int) $entryId;
            $entryCreated = true;
        } else {
            $entryId = (int) $entry['id'];

            if (! $this->entries->addIncomeAmount($entryId, $businessId, $amount)) {
                throw new CrmOperationException('No fue posible sumar la venta al cierre financiero.');
            }
        }

        $postingData = [
            'business_id'             => $businessId,
            'opportunity_id'          => $opportunityId,
            'financial_daily_entry_id' => $entryId,
            'sale_date'               => $date,
            'amount'                  => $amount,
            'status'                  => 'recorded',
        ];

        if ($posting === null) {
            $postingId = $this->postings->insert($postingData, true);

            if ($postingId === false) {
                throw new CrmOperationException('No fue posible vincular la venta con Finanzas.');
            }

            $postingId = (int) $postingId;
        } else {
            $postingId = (int) $posting['id'];

            if (! $this->postings->update($postingId, $postingData)) {
                throw new CrmOperationException('No fue posible reactivar la venta vinculada.');
            }
        }

        $this->audit->record(
            'financial_daily_entry',
            $entryId,
            $entryCreated ? 'created' : 'updated',
        );
        $this->audit->record('crm_financial_posting', $postingId, 'recorded');
    }

    /** @param array<string, mixed> $posting */
    private function reverseSale(int $businessId, array $posting): void
    {
        $entryId = (int) $posting['financial_daily_entry_id'];
        $amount  = $this->normalizeMoney((string) $posting['amount']);

        if ($amount === null
            || ! $this->entries->subtractIncomeAmount($entryId, $businessId, $amount)) {
            throw new CrmOperationException('No fue posible revertir el monto financiero vinculado.');
        }

        $postingId = (int) $posting['id'];

        if (! $this->postings->update($postingId, ['status' => 'reversed'])) {
            throw new CrmOperationException('No fue posible cerrar el vínculo financiero.');
        }

        $entry = $this->entries->findOwned($entryId, $businessId);

        if ($entry !== null && $this->isZeroEntry($entry)) {
            if (! $this->entries->update($entryId, ['status' => 'draft'])) {
                throw new CrmOperationException('No fue posible actualizar el cierre financiero revertido.');
            }
        }

        $this->audit->record('financial_daily_entry', $entryId, 'updated');
        $this->audit->record('crm_financial_posting', $postingId, 'reversed');
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array{status: string, finance_action: string, sale_amount: string|null, sale_date: string|null}
     */
    private function validatedData(array $input): array
    {
        $status = is_string($input['status'] ?? null) ? trim($input['status']) : '';
        $action = is_string($input['finance_action'] ?? null)
            ? trim($input['finance_action'])
            : 'none';
        $date   = is_string($input['sale_date'] ?? null) ? trim($input['sale_date']) : '';
        $errors = [];

        if (! array_key_exists($status, CrmCatalog::OPPORTUNITY_STATUSES)) {
            $errors['status'] = 'Seleccioná un estado de oportunidad válido.';
        }

        if (! in_array($action, ['none', 'record', 'reverse'], true)) {
            $errors['finance_action'] = 'La acción financiera recibida no es válida.';
        }

        try {
            $amount = $this->normalizeMoney($input['sale_amount'] ?? null);
        } catch (CrmValidationException) {
            $amount = null;
            $errors['sale_amount'] = 'Ingresá un monto de venta válido, mayor que cero y con hasta dos decimales.';
        }

        if ($action === 'record') {
            if ($amount === null || $this->decimalToCents($amount) <= 0) {
                $errors['sale_amount'] = 'Ingresá un monto de venta mayor que cero.';
            }

            if (! $this->isValidDate($date)) {
                $errors['sale_date'] = 'Ingresá una fecha de venta válida.';
            }
        }

        if ($errors !== []) {
            throw new CrmValidationException($errors);
        }

        return [
            'status'         => $status,
            'finance_action' => $action,
            'sale_amount'    => $action === 'record' ? $amount : null,
            'sale_date'      => $action === 'record' ? $date : null,
        ];
    }

    /** @return array<string, mixed> */
    private function ownedOpportunity(int $opportunityId, int $businessId): array
    {
        if ($opportunityId < 1) {
            throw BusinessAccessException::unauthorizedEntity();
        }

        $opportunity = $this->opportunities->findOwned($opportunityId, $businessId);

        if ($opportunity === null) {
            throw BusinessAccessException::unauthorizedEntity();
        }

        return $opportunity;
    }

    /** @param array<string, mixed> $entry */
    private function isZeroEntry(array $entry): bool
    {
        foreach ([
            'income_amount',
            'fixed_expense_amount',
            'variable_expense_amount',
            'administrative_expense_amount',
        ] as $field) {
            if ($this->decimalToCents((string) ($entry[$field] ?? '0')) !== 0) {
                return false;
            }
        }

        return true;
    }

    private function normalizeMoney(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value) && ! is_int($value)) {
            throw new CrmValidationException([]);
        }

        $value = str_replace(',', '.', trim((string) $value));

        if (! preg_match('/^\d{1,12}(?:\.\d{1,2})?$/', $value)) {
            throw new CrmValidationException([]);
        }

        return $this->centsToDecimal($this->decimalToCents($value));
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    private function decimalToCents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $fraction = str_pad(substr($fraction, 0, 2), 2, '0');

        return ((int) $whole * 100) + (int) $fraction;
    }

    private function centsToDecimal(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }

    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    private function transaction(callable $operation): mixed
    {
        $this->database->transException(true);

        try {
            if (! $this->database->transBegin()) {
                throw new CrmOperationException('No fue posible iniciar la operación comercial.');
            }

            $result = $operation();

            if (! $this->database->transCommit()) {
                throw new CrmOperationException('No fue posible confirmar la operación comercial.');
            }

            return $result;
        } catch (Throwable $exception) {
            $this->database->transRollback();

            if ($exception instanceof CrmOperationException
                || $exception instanceof CrmValidationException
                || $exception instanceof BusinessAccessException) {
                throw $exception;
            }

            throw new CrmOperationException(
                'No fue posible actualizar el estado de la oportunidad.',
                previous: $exception,
            );
        }
    }
}
