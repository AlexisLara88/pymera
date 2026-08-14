<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\BusinessPermissionCatalog;
use App\Domain\WorkflowCatalog;
use App\Exceptions\BusinessAccessException;
use App\Exceptions\WorkflowOperationException;
use App\Exceptions\WorkflowValidationException;
use App\Models\ActivityModel;
use App\Models\ObjectiveModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use Throwable;

final class ActivityService
{
    public function __construct(
        private ?AlphaBusinessContext $context = null,
        private ?BusinessAuthorizationService $authorization = null,
        private ?AuditEventRecorder $audit = null,
        private ?ObjectiveModel $objectives = null,
        private ?ActivityModel $activities = null,
        private ?BaseConnection $database = null,
    ) {
        $this->context    ??= new AlphaBusinessContext();
        $this->authorization ??= new BusinessAuthorizationService($this->context);
        $this->audit      ??= new AuditEventRecorder($this->context);
        $this->objectives ??= model(ObjectiveModel::class);
        $this->activities ??= model(ActivityModel::class);
        $this->database   ??= db_connect();
    }

    /**
     * @param array<string, mixed> $input
     */
    public function create(int $objectiveId, array $input): int
    {
        $this->authorization->require(BusinessPermissionCatalog::OBJECTIVES_MANAGE);

        $businessId = $this->context->businessId();
        $this->ownedObjective($objectiveId, $businessId);
        $data = $this->validatedData($input, null, 'pending');
        $data['objective_id'] = $objectiveId;

        return $this->transaction(function () use ($data): int {
            $activityId = $this->activities->insert($data, true);

            if ($activityId === false) {
                throw new WorkflowOperationException('No fue posible crear la actividad.');
            }

            $this->audit->record('activity', (int) $activityId, 'created');

            return (int) $activityId;
        });
    }

    /**
     * @param array<string, mixed> $input
     */
    public function update(int $activityId, array $input): void
    {
        $this->authorization->require(BusinessPermissionCatalog::OBJECTIVES_MANAGE);

        $businessId = $this->context->businessId();
        $activity   = $this->ownedActivity($activityId, $businessId);
        $data       = $this->validatedData($input, $activity, (string) $activity['status']);

        $this->transaction(function () use ($activityId, $data): void {
            if (! $this->activities->update($activityId, $data)) {
                throw new WorkflowOperationException('No fue posible actualizar la actividad.');
            }

            $this->audit->record('activity', $activityId, 'updated');
        });
    }

    public function archive(int $activityId): void
    {
        $this->authorization->require(BusinessPermissionCatalog::OBJECTIVES_MANAGE);

        $businessId = $this->context->businessId();
        $this->ownedActivity($activityId, $businessId);

        $this->transaction(function () use ($activityId): void {
            $this->audit->record('activity', $activityId, 'deleted');

            if (! $this->activities->delete($activityId)) {
                throw new WorkflowOperationException('No fue posible archivar la actividad.');
            }
        });
    }

    private function ownedObjective(int $objectiveId, int $businessId): void
    {
        if ($objectiveId < 1
            || $this->objectives->findOwned($objectiveId, $businessId) === null) {
            throw BusinessAccessException::unauthorizedEntity();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function ownedActivity(int $activityId, int $businessId): array
    {
        if ($activityId < 1) {
            throw BusinessAccessException::unauthorizedEntity();
        }

        $activity = $this->activities->findOwned($activityId, $businessId);

        if ($activity === null) {
            throw BusinessAccessException::unauthorizedEntity();
        }

        return $activity;
    }

    /**
     * @param array<string, mixed>      $input
     * @param array<string, mixed>|null $existing
     *
     * @return array<string, mixed>
     */
    private function validatedData(
        array $input,
        ?array $existing,
        string $defaultStatus,
    ): array {
        $fields = ['title', 'description', 'status', 'due_date'];
        $data   = [];
        $errors = [];

        foreach ($fields as $field) {
            $value = $input[$field] ?? ($field === 'status' ? $defaultStatus : '');

            if (! is_string($value)) {
                $errors[$field] = 'El formato recibido no es válido.';
                $data[$field]   = '';

                continue;
            }

            $data[$field] = trim($value);
        }

        if ($data['title'] === '') {
            $errors['title'] = 'Ingresá un título para la actividad.';
        } elseif (mb_strlen($data['title']) > 180) {
            $errors['title'] = 'El título no puede superar los 180 caracteres.';
        }

        if (mb_strlen($data['description']) > 5000) {
            $errors['description'] = 'La descripción no puede superar los 5000 caracteres.';
        }

        if (! array_key_exists($data['status'], WorkflowCatalog::ACTIVITY_STATUSES)) {
            $errors['status'] = 'Seleccioná un estado válido.';
        }

        if ($data['due_date'] !== '' && ! $this->isValidDate($data['due_date'])) {
            $errors['due_date'] = 'Ingresá una fecha válida.';
        }

        $urgent   = $this->validatedFlag($input, 'is_urgent', $errors);
        $important = $this->validatedFlag($input, 'is_important', $errors);

        if ($errors !== []) {
            throw new WorkflowValidationException($errors);
        }

        $completedAt = null;

        if ($data['status'] === 'completed') {
            $completedAt = $existing['completed_at'] ?? null;
            $completedAt = $completedAt ?: Time::now('UTC')->toDateTimeString();
        }

        return [
            'title'        => $data['title'],
            'description'  => $data['description'] === '' ? null : $data['description'],
            'status'       => $data['status'],
            'is_urgent'    => $urgent,
            'is_important' => $important,
            'due_date'     => $data['due_date'] === '' ? null : $data['due_date'],
            'completed_at' => $completedAt,
        ];
    }

    /**
     * @param array<string, mixed>  $input
     * @param array<string, string> $errors
     */
    private function validatedFlag(array $input, string $field, array &$errors): int
    {
        $value = $input[$field] ?? '0';

        if (! is_string($value) || ! in_array($value, ['0', '1'], true)) {
            $errors[$field] = 'Seleccioná una opción válida.';

            return 0;
        }

        return (int) $value;
    }

    private function isValidDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

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
                throw new WorkflowOperationException('No fue posible iniciar la operación.');
            }

            $result = $operation();

            if (! $this->database->transCommit()) {
                throw new WorkflowOperationException('No fue posible confirmar la operación.');
            }

            return $result;
        } catch (Throwable $exception) {
            $this->database->transRollback();

            if ($exception instanceof WorkflowOperationException
                || $exception instanceof BusinessAccessException) {
                throw $exception;
            }

            throw new WorkflowOperationException(
                'No fue posible guardar la actividad.',
                previous: $exception,
            );
        }
    }
}
