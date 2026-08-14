<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\BusinessPermissionCatalog;
use App\Domain\CrmCatalog;
use App\Exceptions\BusinessAccessException;
use App\Exceptions\CrmOperationException;
use App\Exceptions\CrmValidationException;
use App\Models\ContactModel;
use App\Models\OpportunityModel;
use CodeIgniter\Database\BaseConnection;
use DateTimeImmutable;
use Throwable;

final class OpportunityService
{
    public function __construct(
        private ?AlphaBusinessContext $context = null,
        private ?BusinessAuthorizationService $authorization = null,
        private ?AuditEventRecorder $audit = null,
        private ?ContactModel $contacts = null,
        private ?OpportunityModel $opportunities = null,
        private ?BaseConnection $database = null,
    ) {
        $this->context       ??= new AlphaBusinessContext();
        $this->authorization ??= new BusinessAuthorizationService($this->context);
        $this->audit         ??= new AuditEventRecorder($this->context);
        $this->contacts      ??= model(ContactModel::class);
        $this->opportunities ??= model(OpportunityModel::class);
        $this->database      ??= db_connect();
    }

    /**
     * @param array<string, mixed> $input
     */
    public function create(array $input): int
    {
        $this->authorization->require(BusinessPermissionCatalog::CRM_MANAGE);

        $businessId = $this->context->businessId();
        $data       = $this->validatedData($input);
        $this->ownedContact($data['contact_id'], $businessId);
        $data['business_id'] = $businessId;

        return $this->transaction(function () use ($data): int {
            $opportunityId = $this->opportunities->insert($data, true);

            if ($opportunityId === false) {
                throw new CrmOperationException('No fue posible crear la oportunidad.');
            }

            $this->audit->record('opportunity', (int) $opportunityId, 'created');

            return (int) $opportunityId;
        });
    }

    /**
     * @param array<string, mixed> $input
     */
    public function update(int $opportunityId, array $input): void
    {
        $this->authorization->require(BusinessPermissionCatalog::CRM_MANAGE);

        $businessId = $this->context->businessId();
        $opportunity = $this->ownedOpportunity($opportunityId, $businessId);
        // El estado se administra mediante OpportunityStatusService para que
        // cualquier efecto financiero sea explícito, atómico y auditable.
        $input['status'] = (string) $opportunity['status'];
        $data = $this->validatedData($input, $opportunity);
        $this->ownedContact($data['contact_id'], $businessId);

        $this->transaction(function () use ($opportunityId, $data): void {
            if (! $this->opportunities->update($opportunityId, $data)) {
                throw new CrmOperationException('No fue posible actualizar la oportunidad.');
            }

            $this->audit->record('opportunity', $opportunityId, 'updated');
        });
    }

    public function archive(int $opportunityId): void
    {
        $this->authorization->require(BusinessPermissionCatalog::CRM_MANAGE);

        $businessId = $this->context->businessId();
        $this->ownedOpportunity($opportunityId, $businessId);

        $this->transaction(function () use ($opportunityId): void {
            $this->audit->record('opportunity', $opportunityId, 'deleted');

            if (! $this->opportunities->delete($opportunityId)) {
                throw new CrmOperationException('No fue posible archivar la oportunidad.');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function ownedContact(int $contactId, int $businessId): array
    {
        if ($contactId < 1) {
            throw BusinessAccessException::unauthorizedEntity();
        }

        $contact = $this->contacts->findOwned($contactId, $businessId);

        if ($contact === null) {
            throw BusinessAccessException::unauthorizedEntity();
        }

        return $contact;
    }

    /**
     * @return array<string, mixed>
     */
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

    /**
     * @param array<string, mixed>      $input
     * @param array<string, mixed>|null $existing
     *
     * @return array{
     *     contact_id: int,
     *     need: string,
     *     status: string,
     *     estimated_value: string|null,
     *     next_follow_up_date: string|null,
     *     notes: string|null
     * }
     */
    private function validatedData(array $input, ?array $existing = null): array
    {
        $defaults = [
            'contact_id'          => '',
            'need'                => '',
            'status'              => 'new',
            'estimated_value'     => '',
            'next_follow_up_date' => '',
            'notes'               => '',
        ];
        $source = $existing === null ? $defaults : [...$defaults, ...$existing];
        $raw    = [];
        $errors = [];

        foreach (array_keys($defaults) as $field) {
            $raw[$field] = array_key_exists($field, $input) ? $input[$field] : $source[$field];
        }

        if (! array_key_exists('estimated_value', $input)
            && $raw['estimated_value'] !== null) {
            $raw['estimated_value'] = (string) $raw['estimated_value'];
        }

        $contactId = $this->positiveInteger($raw['contact_id']);

        if ($contactId === null) {
            $errors['contact_id'] = 'Seleccioná un contacto válido.';
            $contactId = 0;
        }

        foreach (['need', 'status', 'next_follow_up_date', 'notes'] as $field) {
            if ($raw[$field] !== null && ! is_string($raw[$field])) {
                $errors[$field] = 'El formato recibido no es válido.';
                $raw[$field] = '';
            }

            $raw[$field] = trim((string) $raw[$field]);
        }

        if ($raw['need'] === '') {
            $errors['need'] = 'Ingresá la necesidad o servicio de interés.';
        } elseif (mb_strlen($raw['need']) > 180) {
            $errors['need'] = 'La necesidad no puede superar los 180 caracteres.';
        }

        if (! array_key_exists($raw['status'], CrmCatalog::OPPORTUNITY_STATUSES)) {
            $errors['status'] = 'Seleccioná un estado de oportunidad válido.';
        }

        try {
            $estimatedValue = $this->normalizeMoney($raw['estimated_value']);
        } catch (CrmValidationException) {
            $errors['estimated_value'] = 'Ingresá un valor estimado válido, con hasta dos decimales.';
            $estimatedValue = null;
        }

        if ($raw['next_follow_up_date'] !== ''
            && ! $this->isValidDate($raw['next_follow_up_date'])) {
            $errors['next_follow_up_date'] = 'Ingresá una fecha de seguimiento válida.';
        }

        if (mb_strlen($raw['notes']) > 2000) {
            $errors['notes'] = 'Las notas no pueden superar los 2000 caracteres.';
        }

        if ($errors !== []) {
            throw new CrmValidationException($errors);
        }

        return [
            'contact_id'          => $contactId,
            'need'                => $raw['need'],
            'status'              => $raw['status'],
            'estimated_value'     => $estimatedValue,
            'next_follow_up_date' => $raw['next_follow_up_date'] === ''
                ? null
                : $raw['next_follow_up_date'],
            'notes' => $raw['notes'] === '' ? null : $raw['notes'],
        ];
    }

    private function positiveInteger(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (! is_string($value) || ! ctype_digit($value)) {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
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

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return ltrim($whole, '0') === ''
            ? '0.' . str_pad($fraction, 2, '0')
            : ltrim($whole, '0') . '.' . str_pad($fraction, 2, '0');
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
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
                || $exception instanceof BusinessAccessException) {
                throw $exception;
            }

            throw new CrmOperationException(
                'No fue posible guardar la oportunidad.',
                previous: $exception,
            );
        }
    }
}
