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
use Throwable;

final class ContactService
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

        $data = $this->validatedData($input);
        $data['business_id'] = $this->context->businessId();

        return $this->transaction(function () use ($data): int {
            $contactId = $this->contacts->insert($data, true);

            if ($contactId === false) {
                throw new CrmOperationException('No fue posible crear el contacto.');
            }

            $this->audit->record('contact', (int) $contactId, 'created');

            return (int) $contactId;
        });
    }

    /**
     * @param array<string, mixed> $input
     */
    public function update(int $contactId, array $input): void
    {
        $this->authorization->require(BusinessPermissionCatalog::CRM_MANAGE);

        $businessId = $this->context->businessId();
        $contact    = $this->ownedContact($contactId, $businessId);
        $data       = $this->validatedData($input, $contact);

        $this->transaction(function () use ($contactId, $data): void {
            if (! $this->contacts->update($contactId, $data)) {
                throw new CrmOperationException('No fue posible actualizar el contacto.');
            }

            $this->audit->record('contact', $contactId, 'updated');
        });
    }

    public function convertToClient(int $contactId): void
    {
        $this->authorization->require(BusinessPermissionCatalog::CRM_MANAGE);

        $businessId = $this->context->businessId();
        $contact    = $this->ownedContact($contactId, $businessId);

        if ($contact['lifecycle_stage'] === 'client') {
            return;
        }

        $this->transaction(function () use ($contactId): void {
            if (! $this->contacts->update($contactId, ['lifecycle_stage' => 'client'])) {
                throw new CrmOperationException('No fue posible convertir el prospecto en cliente.');
            }

            $this->audit->record('contact', $contactId, 'updated');
        });
    }

    public function archive(int $contactId): void
    {
        $this->authorization->require(BusinessPermissionCatalog::CRM_MANAGE);

        $businessId = $this->context->businessId();
        $this->ownedContact($contactId, $businessId);

        foreach ($this->opportunities->findForContact($contactId, $businessId) as $opportunity) {
            if (in_array($opportunity['status'], CrmCatalog::OPEN_OPPORTUNITY_STATUSES, true)) {
                throw new CrmValidationException([
                    'contact' => 'Cerrá o archivá las oportunidades abiertas antes de archivar el contacto.',
                ]);
            }
        }

        $this->transaction(function () use ($contactId): void {
            $this->audit->record('contact', $contactId, 'deleted');

            if (! $this->contacts->delete($contactId)) {
                throw new CrmOperationException('No fue posible archivar el contacto.');
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
     * @param array<string, mixed>      $input
     * @param array<string, mixed>|null $existing
     *
     * @return array<string, string|null>
     */
    private function validatedData(array $input, ?array $existing = null): array
    {
        $defaults = [
            'display_name'        => '',
            'contact_kind'        => 'person',
            'lifecycle_stage'     => 'prospect',
            'acquisition_channel' => '',
            'email'               => '',
            'phone'               => '',
            'notes'               => '',
        ];
        $source = $existing === null ? $defaults : [...$defaults, ...$existing];
        $data   = [];
        $errors = [];

        foreach (array_keys($defaults) as $field) {
            $value = array_key_exists($field, $input) ? $input[$field] : $source[$field];

            if ($value !== null && ! is_string($value)) {
                $errors[$field] = 'El formato recibido no es válido.';
                $data[$field]   = '';

                continue;
            }

            $data[$field] = trim((string) $value);
        }

        if ($data['display_name'] === '') {
            $errors['display_name'] = 'Ingresá el nombre de la persona u organización.';
        } elseif (mb_strlen($data['display_name']) > 160) {
            $errors['display_name'] = 'El nombre no puede superar los 160 caracteres.';
        }

        if (! array_key_exists($data['contact_kind'], CrmCatalog::CONTACT_KINDS)) {
            $errors['contact_kind'] = 'Seleccioná un tipo de contacto válido.';
        }

        if (! array_key_exists($data['lifecycle_stage'], CrmCatalog::LIFECYCLE_STAGES)) {
            $errors['lifecycle_stage'] = 'Seleccioná una etapa comercial válida.';
        }

        if ($data['acquisition_channel'] !== ''
            && ! array_key_exists(
                $data['acquisition_channel'],
                CrmCatalog::ACQUISITION_CHANNELS,
            )) {
            $errors['acquisition_channel'] = 'Seleccioná un canal de adquisición válido.';
        }

        if ($data['email'] !== ''
            && (mb_strlen($data['email']) > 254
                || filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false)) {
            $errors['email'] = 'Ingresá un correo electrónico válido.';
        }

        if (mb_strlen($data['phone']) > 40) {
            $errors['phone'] = 'El teléfono no puede superar los 40 caracteres.';
        }

        if (mb_strlen($data['notes']) > 2000) {
            $errors['notes'] = 'Las notas no pueden superar los 2000 caracteres.';
        }

        if ($errors !== []) {
            throw new CrmValidationException($errors);
        }

        return [
            'display_name'        => $data['display_name'],
            'contact_kind'        => $data['contact_kind'],
            'lifecycle_stage'     => $data['lifecycle_stage'],
            'acquisition_channel' => $data['acquisition_channel'] === ''
                ? null
                : $data['acquisition_channel'],
            'email' => $data['email'] === '' ? null : mb_strtolower($data['email']),
            'phone' => $data['phone'] === '' ? null : $data['phone'],
            'notes' => $data['notes'] === '' ? null : $data['notes'],
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
                'No fue posible guardar el contacto.',
                previous: $exception,
            );
        }
    }
}
