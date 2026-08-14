<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\BusinessPermissionCatalog;
use App\Domain\FinanceCatalog;
use App\Exceptions\BusinessAccessException;
use App\Exceptions\FinanceOperationException;
use App\Exceptions\FinanceValidationException;
use App\Models\FinancialDailyEntryModel;
use App\Models\CrmFinancialPostingModel;
use CodeIgniter\Database\BaseConnection;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

final class FinanceService
{
    public function __construct(
        private ?AlphaBusinessContext $context = null,
        private ?BusinessAuthorizationService $authorization = null,
        private ?AuthorizedBusinessReader $reader = null,
        private ?AuditEventRecorder $audit = null,
        private ?FinancialDailyEntryModel $entries = null,
        private ?CrmFinancialPostingModel $postings = null,
        private ?BaseConnection $database = null,
    ) {
        $this->context  ??= new AlphaBusinessContext();
        $this->authorization ??= new BusinessAuthorizationService($this->context);
        $this->reader   ??= new AuthorizedBusinessReader($this->context);
        $this->audit    ??= new AuditEventRecorder($this->context);
        $this->entries  ??= model(FinancialDailyEntryModel::class);
        $this->postings ??= model(CrmFinancialPostingModel::class);
        $this->database ??= db_connect();
    }

    /**
     * @return array{
     *     business: array<string, mixed>,
     *     period: string,
     *     period_label: string,
     *     today: string,
     *     entries: list<array<string, mixed>>,
     *     totals: array<string, int>,
     *     finance_summary: array<string, int>,
     *     sales_breakdown: array{manual_sales_cents: int, crm_sales_cents: int, total_sales_cents: int},
     *     finance_indicators: array{
     *         contribution_margin_cents: int,
     *         contribution_margin_percentage: float|null,
     *         fixed_costs_cents: int,
     *         break_even_sales_cents: int|null,
     *         break_even_status: string
     *     },
     *     chart_entries: list<array<string, int|string>>
     * }
     */
    public function overview(?string $requestedPeriod = null): array
    {
        $this->authorization->require(BusinessPermissionCatalog::FINANCES_VIEW);

        $business  = $this->reader->business();
        $period    = $requestedPeriod ?: $this->currentPeriod($business);
        [$start, $end] = $this->periodBounds($period);
        $entries   = $this->entries->findForBusinessPeriod(
            $this->context->businessId(),
            $start,
            $end,
        );
        $recordedPostings = $this->postings->findRecordedForBusinessPeriod(
            $this->context->businessId(),
            $start,
            $end,
        );
        $totals = [
            'sales_cents'                  => 0,
            'cost_of_sales_cents'          => 0,
            'gross_profit_cents'           => 0,
            'operating_expense_cents'      => 0,
            'administrative_expense_cents' => 0,
            'ebitda_cents'                 => 0,
        ];

        foreach ($entries as &$entry) {
            $sales          = $this->decimalToCents((string) $entry['income_amount']);
            $costOfSales    = $this->decimalToCents((string) $entry['variable_expense_amount']);
            $operating      = $this->decimalToCents((string) $entry['fixed_expense_amount']);
            $administrative = $this->decimalToCents((string) $entry['administrative_expense_amount']);
            $grossProfit    = $sales - $costOfSales;
            $ebitda         = $grossProfit - $operating - $administrative;

            $entry['sales_cents']                  = $sales;
            $entry['cost_of_sales_cents']          = $costOfSales;
            $entry['gross_profit_cents']           = $grossProfit;
            $entry['operating_expense_cents']      = $operating;
            $entry['administrative_expense_cents'] = $administrative;
            $entry['ebitda_cents']                 = $ebitda;
            $entry['status_label']           = FinanceCatalog::STATUSES[$entry['status']]
                ?? (string) $entry['status'];

            if ($entry['status'] === 'recorded') {
                $totals['sales_cents'] += $sales;
                $totals['cost_of_sales_cents'] += $costOfSales;
                $totals['operating_expense_cents'] += $operating;
                $totals['administrative_expense_cents'] += $administrative;
            }
        }
        unset($entry);

        $totals['gross_profit_cents'] = $totals['sales_cents']
            - $totals['cost_of_sales_cents'];
        $totals['ebitda_cents'] = $totals['gross_profit_cents']
            - $totals['operating_expense_cents']
            - $totals['administrative_expense_cents'];
        $financeIndicators = $this->financialIndicators($totals);
        $crmSalesCents = array_sum(array_map(
            fn (array $posting): int => $this->decimalToCents((string) $posting['amount']),
            $recordedPostings,
        ));
        $recordedEntries = array_values(array_filter(
            $entries,
            static fn (array $entry): bool => $entry['status'] === 'recorded',
        ));
        $chartSource = array_slice(array_reverse($recordedEntries), -7);
        $chartMaximum = 1;

        foreach ($chartSource as $entry) {
            $chartMaximum = max(
                $chartMaximum,
                (int) $entry['sales_cents'],
                (int) $entry['cost_of_sales_cents']
                    + (int) $entry['operating_expense_cents']
                    + (int) $entry['administrative_expense_cents'],
            );
        }

        $chartEntries = array_map(
            static function (array $entry) use ($chartMaximum): array {
                $costsAndExpenses = (int) $entry['cost_of_sales_cents']
                    + (int) $entry['operating_expense_cents']
                    + (int) $entry['administrative_expense_cents'];

                return [
                    'date'            => (string) $entry['operation_date'],
                    'label'           => (new DateTimeImmutable((string) $entry['operation_date']))->format('d/m'),
                    'sales_cents'     => (int) $entry['sales_cents'],
                    'costs_cents'     => $costsAndExpenses,
                    'sales_percent'   => (int) round(((int) $entry['sales_cents'] / $chartMaximum) * 100),
                    'costs_percent'   => (int) round(($costsAndExpenses / $chartMaximum) * 100),
                ];
            },
            $chartSource,
        );

        $periodDate = DateTimeImmutable::createFromFormat('!Y-m', $period);

        return [
            'business'     => $business,
            'period'       => $period,
            'period_label' => $periodDate === false ? $period : $periodDate->format('m/Y'),
            'today'        => $this->today($business),
            'entries'      => $entries,
            'totals'       => $totals,
            'finance_summary' => [
                'costs_and_expenses_cents' => $totals['cost_of_sales_cents']
                    + $totals['operating_expense_cents']
                    + $totals['administrative_expense_cents'],
                'recorded_entry_count' => count($recordedEntries),
            ],
            'sales_breakdown' => [
                'manual_sales_cents' => $totals['sales_cents'] - $crmSalesCents,
                'crm_sales_cents'    => $crmSalesCents,
                'total_sales_cents'  => $totals['sales_cents'],
            ],
            'finance_indicators' => $financeIndicators,
            'chart_entries' => $chartEntries,
        ];
    }

    /**
     * Calculates reusable period indicators without persisting derived values.
     *
     * The aggregate alpha treats cost of sales as variable cost and considers
     * operating/fixed plus administrative expenses as the fixed-cost base.
     *
     * @param array<string, int> $totals
     *
     * @return array{
     *     contribution_margin_cents: int,
     *     contribution_margin_percentage: float|null,
     *     fixed_costs_cents: int,
     *     break_even_sales_cents: int|null,
     *     break_even_status: string
     * }
     */
    private function financialIndicators(array $totals): array
    {
        $sales              = $totals['sales_cents'];
        $contributionMargin = $totals['gross_profit_cents'];
        $fixedCosts         = $totals['operating_expense_cents']
            + $totals['administrative_expense_cents'];
        $indicators = [
            'contribution_margin_cents'      => $contributionMargin,
            'contribution_margin_percentage' => null,
            'fixed_costs_cents'               => $fixedCosts,
            'break_even_sales_cents'          => null,
            'break_even_status'               => 'no_sales',
        ];

        if ($sales <= 0) {
            return $indicators;
        }

        if ($contributionMargin <= 0) {
            $indicators['contribution_margin_percentage'] = round(
                ($contributionMargin / $sales) * 100,
                2,
            );
            $indicators['break_even_status'] = 'non_positive_margin';

            return $indicators;
        }

        $contributionRatio = $contributionMargin / $sales;
        $indicators['contribution_margin_percentage'] = round(
            $contributionRatio * 100,
            2,
        );
        $indicators['break_even_sales_cents'] = (int) ceil(
            $fixedCosts / $contributionRatio,
        );
        $indicators['break_even_status'] = 'available';

        return $indicators;
    }

    /**
     * @param array<string, mixed> $input
     */
    public function create(array $input): int
    {
        $this->authorization->require(BusinessPermissionCatalog::FINANCES_MANAGE);

        $businessId = $this->context->businessId();
        $data       = $this->validatedData($input);

        if ($this->entries->findForDate($businessId, $data['operation_date']) !== null) {
            throw new FinanceValidationException([
                'operation_date' => 'Ya existe un registro agregado para esa fecha.',
            ]);
        }

        $data['business_id'] = $businessId;

        return $this->transaction(function () use ($data): int {
            $entryId = $this->entries->insert($data, true);

            if ($entryId === false) {
                throw new FinanceOperationException('No fue posible crear el registro financiero.');
            }

            $this->audit->record('financial_daily_entry', (int) $entryId, 'created');

            return (int) $entryId;
        });
    }

    /**
     * @param array<string, mixed> $input
     */
    public function update(int $entryId, array $input): void
    {
        $this->authorization->require(BusinessPermissionCatalog::FINANCES_MANAGE);

        $businessId = $this->context->businessId();
        $existing = $this->ownedEntry($entryId, $businessId);
        $data = $this->validatedData($input);
        $sameDateEntry = $this->entries->findForDate($businessId, $data['operation_date']);

        if ($sameDateEntry !== null && (int) $sameDateEntry['id'] !== $entryId) {
            throw new FinanceValidationException([
                'operation_date' => 'Ya existe un registro agregado para esa fecha.',
            ]);
        }

        $recordedPostings = $this->postings->findRecordedForEntry($entryId, $businessId);

        if ($recordedPostings !== []) {
            $crmSalesCents = array_sum(array_map(
                fn (array $posting): int => $this->decimalToCents((string) $posting['amount']),
                $recordedPostings,
            ));
            $linkedErrors = [];

            if ($data['operation_date'] !== (string) $existing['operation_date']) {
                $linkedErrors['operation_date'] = 'La fecha no puede cambiar mientras existan ventas vinculadas desde el CRM.';
            }

            if ($data['status'] !== 'recorded') {
                $linkedErrors['status'] = 'El cierre debe permanecer confirmado mientras contenga ventas vinculadas desde el CRM.';
            }

            if ($this->decimalToCents((string) $data['income_amount']) < $crmSalesCents) {
                $linkedErrors['income_amount'] = 'Las ventas no pueden ser menores que el total vinculado desde el CRM.';
            }

            if ($linkedErrors !== []) {
                throw new FinanceValidationException($linkedErrors);
            }
        }

        $this->transaction(function () use ($entryId, $data): void {
            if (! $this->entries->update($entryId, $data)) {
                throw new FinanceOperationException('No fue posible actualizar el registro financiero.');
            }

            $this->audit->record('financial_daily_entry', $entryId, 'updated');
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function ownedEntry(int $entryId, int $businessId): array
    {
        if ($entryId < 1) {
            throw BusinessAccessException::unauthorizedEntity();
        }

        $entry = $this->entries->findOwned($entryId, $businessId);

        if ($entry === null) {
            throw BusinessAccessException::unauthorizedEntity();
        }

        return $entry;
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, string|null>
     */
    private function validatedData(array $input): array
    {
        $errors = [];
        $date   = is_string($input['operation_date'] ?? null)
            ? trim($input['operation_date'])
            : '';
        $status = is_string($input['status'] ?? null)
            ? trim($input['status'])
            : 'recorded';
        $notes  = is_string($input['notes'] ?? null)
            ? trim($input['notes'])
            : '';

        if (! $this->isValidDate($date)) {
            $errors['operation_date'] = 'Ingresá una fecha válida.';
        }

        if (! array_key_exists($status, FinanceCatalog::STATUSES)) {
            $errors['status'] = 'Seleccioná un estado válido.';
        }

        if (mb_strlen($notes) > 1000) {
            $errors['notes'] = 'La nota no puede superar los 1000 caracteres.';
        }

        $moneyFields = [
            'income_amount'                  => 'total de ventas',
            'variable_expense_amount'        => 'costo de ventas',
            'fixed_expense_amount'           => 'gasto operativo o fijo',
            'administrative_expense_amount'  => 'gasto administrativo',
        ];
        $amounts = [];

        foreach ($moneyFields as $field => $label) {
            try {
                $amounts[$field] = $this->normalizeMoney($input[$field] ?? null);
            } catch (FinanceValidationException) {
                $errors[$field] = "Ingresá un {$label} válido, con hasta dos decimales.";
                $amounts[$field] = '0.00';
            }
        }

        if ($errors === []
            && array_sum(array_map($this->decimalToCents(...), $amounts)) === 0) {
            $errors['income_amount'] = 'Ingresá al menos un monto mayor que cero.';
        }

        if ($errors !== []) {
            throw new FinanceValidationException($errors);
        }

        return [
            'operation_date'          => $date,
            'income_amount'           => $amounts['income_amount'],
            'fixed_expense_amount'    => $amounts['fixed_expense_amount'],
            'variable_expense_amount' => $amounts['variable_expense_amount'],
            'administrative_expense_amount' => $amounts['administrative_expense_amount'],
            'status'                  => $status,
            'notes'                   => $notes === '' ? null : $notes,
        ];
    }

    private function normalizeMoney(mixed $value): string
    {
        if (! is_string($value) && ! is_int($value)) {
            throw new FinanceValidationException([]);
        }

        $value = str_replace(',', '.', trim((string) $value));

        if (! preg_match('/^\d{1,12}(?:\.\d{1,2})?$/', $value)) {
            throw new FinanceValidationException([]);
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

    private function currentPeriod(array $business): string
    {
        return substr($this->today($business), 0, 7);
    }

    private function today(array $business): string
    {
        try {
            $timezone = new DateTimeZone((string) ($business['timezone'] ?? 'UTC'));
        } catch (Throwable) {
            $timezone = new DateTimeZone('UTC');
        }

        return (new DateTimeImmutable('now', $timezone))->format('Y-m-d');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function periodBounds(string $period): array
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m', $period);

        if ($date === false || $date->format('Y-m') !== $period) {
            throw new FinanceValidationException([
                'period' => 'Seleccioná un período mensual válido.',
            ]);
        }

        return [
            $date->format('Y-m-01'),
            $date->modify('last day of this month')->format('Y-m-d'),
        ];
    }

    /**
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     */
    private function transaction(callable $operation): mixed
    {
        $this->database->transException(true);

        try {
            if (! $this->database->transBegin()) {
                throw new FinanceOperationException('No fue posible iniciar la operación.');
            }

            $result = $operation();

            if (! $this->database->transCommit()) {
                throw new FinanceOperationException('No fue posible confirmar la operación.');
            }

            return $result;
        } catch (Throwable $exception) {
            $this->database->transRollback();

            if ($exception instanceof FinanceOperationException
                || $exception instanceof BusinessAccessException) {
                throw $exception;
            }

            throw new FinanceOperationException(
                'No fue posible guardar el registro financiero.',
                previous: $exception,
            );
        }
    }
}
