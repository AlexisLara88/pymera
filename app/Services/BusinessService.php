<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\BusinessPermissionCatalog;
use App\Exceptions\BusinessOperationException;
use App\Exceptions\BusinessValidationException;
use App\Models\BusinessModel;
use App\Models\BusinessProfileModel;
use CodeIgniter\Database\BaseConnection;
use Throwable;

/**
 * Handles the authenticated "Mi negocio" use case.
 */
final class BusinessService
{
    private const REQUIRED_PROFILE_FIELDS = [
        'what_it_does',
        'customers_served',
        'products_offered',
        'objectives_summary',
    ];

    private const OPTIONAL_PROFILE_FIELDS = [
        'differentiator',
        'differentiation_delivery',
        'customer_outcome',
        'purchase_reason',
        'acquisition_channels',
    ];

    public function __construct(
        private ?AlphaBusinessContext $context = null,
        private ?BusinessAuthorizationService $authorization = null,
        private ?AuthorizedBusinessReader $reader = null,
        private ?AuditEventRecorder $audit = null,
        private ?BusinessModel $businesses = null,
        private ?BusinessProfileModel $profiles = null,
        private ?BaseConnection $database = null,
    ) {
        $this->context    ??= new AlphaBusinessContext();
        $this->authorization ??= new BusinessAuthorizationService($this->context);
        $this->reader     ??= new AuthorizedBusinessReader($this->context);
        $this->audit      ??= new AuditEventRecorder($this->context);
        $this->businesses ??= model(BusinessModel::class);
        $this->profiles   ??= model(BusinessProfileModel::class);
        $this->database   ??= db_connect();
    }

    /**
     * @return array{
     *     business: array<string, mixed>,
     *     profile: array<string, mixed>|null,
     *     profile_completion: int,
     *     minimum_profile_completion: int,
     *     minimum_profile_complete: bool
     * }
     */
    public function details(): array
    {
        $this->authorization->require(BusinessPermissionCatalog::BUSINESS_VIEW);

        $profile = $this->reader->profile();
        $completedFields = 0;
        $completedRequiredFields = 0;
        $profileFields = [
            ...self::REQUIRED_PROFILE_FIELDS,
            ...self::OPTIONAL_PROFILE_FIELDS,
        ];

        foreach ($profileFields as $field) {
            if (trim((string) ($profile[$field] ?? '')) !== '') {
                $completedFields++;
            }
        }

        foreach (self::REQUIRED_PROFILE_FIELDS as $field) {
            if (trim((string) ($profile[$field] ?? '')) !== '') {
                $completedRequiredFields++;
            }
        }

        return [
            'business'           => $this->reader->business(),
            'profile'            => $profile,
            'profile_completion' => (int) round(
                ($completedFields / count($profileFields)) * 100,
            ),
            'minimum_profile_completion' => (int) round(
                ($completedRequiredFields / count(self::REQUIRED_PROFILE_FIELDS)) * 100,
            ),
            'minimum_profile_complete' => $completedRequiredFields === count(self::REQUIRED_PROFILE_FIELDS),
        ];
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array{
     *     business: array<string, mixed>,
     *     profile: array<string, mixed>|null
     * }
     */
    public function update(array $input): array
    {
        $this->authorization->require(BusinessPermissionCatalog::BUSINESS_UPDATE);

        $data       = $this->validatedData($input);
        $businessId = $this->context->businessId();
        $profile    = $this->profiles
            ->where('business_id', $businessId)
            ->first();

        $this->database->transException(true);

        try {
            if (! $this->database->transBegin()) {
                throw new BusinessOperationException(
                    'No fue posible iniciar la actualización del negocio.',
                );
            }

            $businessUpdated = $this->businesses->update($businessId, [
                'name'          => $data['name'],
                'currency_code' => $data['currency_code'],
                'timezone'      => $data['timezone'],
            ]);

            if (! $businessUpdated) {
                throw new BusinessOperationException(
                    'No fue posible actualizar la identidad del negocio.',
                );
            }

            $profileData = [
                'business_id'              => $businessId,
                'what_it_does'             => $data['what_it_does'],
                'customers_served'         => $data['customers_served'],
                'products_offered'         => $data['products_offered'],
                'objectives_summary'       => $data['objectives_summary'],
                'differentiator'            => $data['differentiator'],
                'differentiation_delivery' => $data['differentiation_delivery'],
                'customer_outcome'         => $data['customer_outcome'],
                'purchase_reason'          => $data['purchase_reason'],
                'acquisition_channels'     => $data['acquisition_channels'],
            ];

            if ($profile === null) {
                $profileId = $this->profiles->insert($profileData, true);
                $action    = 'created';
            } else {
                $profileId = (int) $profile['id'];
                $saved     = $this->profiles->update($profileId, $profileData);
                $action    = 'updated';

                if (! $saved) {
                    $profileId = false;
                }
            }

            if ($profileId === false) {
                throw new BusinessOperationException(
                    'No fue posible actualizar el perfil del negocio.',
                );
            }

            $this->audit->record('business', $businessId, 'updated');
            $this->audit->record('business_profile', (int) $profileId, $action);

            if (! $this->database->transCommit()) {
                throw new BusinessOperationException(
                    'No fue posible confirmar la actualización del negocio.',
                );
            }
        } catch (Throwable $exception) {
            $this->database->transRollback();

            if ($exception instanceof BusinessOperationException) {
                throw $exception;
            }

            throw new BusinessOperationException(
                'No fue posible guardar la información del negocio.',
                previous: $exception,
            );
        }

        return $this->details();
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, string|null>
     */
    private function validatedData(array $input): array
    {
        $fields = [
            'name',
            'currency_code',
            'timezone',
            ...self::REQUIRED_PROFILE_FIELDS,
            ...self::OPTIONAL_PROFILE_FIELDS,
        ];
        $data   = [];
        $errors = [];

        foreach ($fields as $field) {
            $value = $input[$field] ?? '';

            if (! is_string($value)) {
                $errors[$field] = 'El formato recibido no es válido.';
                $data[$field]   = '';

                continue;
            }

            $data[$field] = trim($value);
        }

        if ($data['name'] === '') {
            $errors['name'] = 'Ingresá el nombre del negocio.';
        } elseif (mb_strlen($data['name']) > 160) {
            $errors['name'] = 'El nombre no puede superar los 160 caracteres.';
        }

        $data['currency_code'] = strtoupper($data['currency_code']);

        if (preg_match('/^[A-Z]{3}$/', $data['currency_code']) !== 1) {
            $errors['currency_code'] = 'Ingresá un código de moneda de tres letras, por ejemplo USD.';
        }

        if ($data['timezone'] === '') {
            $errors['timezone'] = 'Seleccioná la zona horaria del negocio.';
        } elseif (mb_strlen($data['timezone']) > 64
            || ! in_array($data['timezone'], timezone_identifiers_list(), true)) {
            $errors['timezone'] = 'Seleccioná una zona horaria IANA válida.';
        }

        foreach (self::REQUIRED_PROFILE_FIELDS as $field) {
            if ($data[$field] === '') {
                $errors[$field] = 'Completá esta respuesta para continuar.';
            } elseif (mb_strlen($data[$field]) > 5000) {
                $errors[$field] = 'La respuesta no puede superar los 5000 caracteres.';
            }
        }

        foreach (self::OPTIONAL_PROFILE_FIELDS as $field) {
            if (mb_strlen($data[$field]) > 5000) {
                $errors[$field] = 'La respuesta no puede superar los 5000 caracteres.';
            }

            if ($data[$field] === '') {
                $data[$field] = null;
            }
        }

        if ($errors !== []) {
            throw new BusinessValidationException($errors);
        }

        return $data;
    }
}
