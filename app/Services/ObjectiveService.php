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
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

final class ObjectiveService
{
    public function __construct(
        private ?AlphaBusinessContext $context = null,
        private ?BusinessAuthorizationService $authorization = null,
        private ?AuthorizedBusinessReader $reader = null,
        private ?AuditEventRecorder $audit = null,
        private ?ObjectiveModel $objectives = null,
        private ?ActivityModel $activities = null,
        private ?EisenhowerClassifier $classifier = null,
        private ?BaseConnection $database = null,
    ) {
        $this->context    ??= new AlphaBusinessContext();
        $this->authorization ??= new BusinessAuthorizationService($this->context);
        $this->reader     ??= new AuthorizedBusinessReader($this->context);
        $this->audit      ??= new AuditEventRecorder($this->context);
        $this->objectives ??= model(ObjectiveModel::class);
        $this->activities ??= model(ActivityModel::class);
        $this->classifier ??= new EisenhowerClassifier();
        $this->database   ??= db_connect();
    }

    /**
     * @return array{
     *     business: array<string, mixed>,
     *     objectives: list<array<string, mixed>>,
     *     activities: list<array<string, mixed>>,
     *     quadrants: array<string, list<array<string, mixed>>>,
     *     workflow_summary: array<string, int>,
     *     featured_objective: array<string, mixed>|null
     * }
     */
    public function overview(): array
    {
        $this->authorization->require(BusinessPermissionCatalog::OBJECTIVES_VIEW);

        $businessId = $this->context->businessId();
        $business   = $this->reader->business();
        $objectives = $this->objectives->findForBusiness($businessId);
        $activities = $this->activities->findForBusiness($businessId);
        $byObjective = [];
        $quadrants   = array_fill_keys(
            array_keys(WorkflowCatalog::QUADRANTS),
            [],
        );

        foreach ($activities as &$activity) {
            $quadrant = $this->classifier->classify(
                (bool) $activity['is_urgent'],
                (bool) $activity['is_important'],
            );

            $activity['quadrant']       = $quadrant;
            $activity['quadrant_label'] = WorkflowCatalog::QUADRANTS[$quadrant];
            $activity['status_label']   = WorkflowCatalog::ACTIVITY_STATUSES[$activity['status']]
                ?? (string) $activity['status'];
            $byObjective[(int) $activity['objective_id']][] = $activity;
            $quadrants[$quadrant][] = $activity;
        }
        unset($activity);

        foreach ($objectives as &$objective) {
            $category = (string) ($objective['category'] ?? '');

            $objective['category_label'] = WorkflowCatalog::OBJECTIVE_CATEGORIES[$category]
                ?? 'Sin categoría';
            $objective['status_label'] = WorkflowCatalog::OBJECTIVE_STATUSES[$objective['status']]
                ?? (string) $objective['status'];
            $objective['activities'] = $byObjective[(int) $objective['id']] ?? [];
            $completed = count(array_filter(
                $objective['activities'],
                static fn (array $activity): bool => $activity['status'] === 'completed',
            ));
            $total = count($objective['activities']);
            $objective['completed_activity_count'] = $completed;
            $objective['progress_percent'] = $total === 0
                ? 0
                : (int) round(($completed / $total) * 100);
        }
        unset($objective);

        try {
            $timezone = new DateTimeZone((string) ($business['timezone'] ?? 'UTC'));
        } catch (Throwable) {
            $timezone = new DateTimeZone('UTC');
        }

        $today = (new DateTimeImmutable('now', $timezone))->format('Y-m-d');
        $activeObjectives = array_values(array_filter(
            $objectives,
            static fn (array $objective): bool => $objective['status'] === 'active',
        ));
        $inProgress = count(array_filter(
            $activities,
            static fn (array $activity): bool => $activity['status'] === 'in_progress',
        ));
        $overdue = count(array_filter(
            $activities,
            static fn (array $activity): bool => ! empty($activity['due_date'])
                && $activity['due_date'] < $today
                && ! in_array($activity['status'], ['completed', 'cancelled'], true),
        ));

        return [
            'business'           => $business,
            'objectives'         => $objectives,
            'activities'         => $activities,
            'quadrants'          => $quadrants,
            'workflow_summary'   => [
                'active_objectives' => count($activeObjectives),
                'activities'        => count($activities),
                'in_progress'       => $inProgress,
                'overdue'           => $overdue,
            ],
            'featured_objective' => $activeObjectives[0] ?? $objectives[0] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $input
     */
    public function create(array $input): int
    {
        $this->authorization->require(BusinessPermissionCatalog::OBJECTIVES_MANAGE);

        $businessId = $this->context->businessId();
        $data       = $this->validatedData($input, null, 'draft');
        $data['business_id'] = $businessId;

        return $this->transaction(function () use ($data): int {
            $objectiveId = $this->objectives->insert($data, true);

            if ($objectiveId === false) {
                throw new WorkflowOperationException('No fue posible crear el objetivo.');
            }

            $this->audit->record('objective', (int) $objectiveId, 'created');

            return (int) $objectiveId;
        });
    }

    /**
     * @param array<string, mixed> $input
     */
    public function update(int $objectiveId, array $input): void
    {
        $this->authorization->require(BusinessPermissionCatalog::OBJECTIVES_MANAGE);

        $businessId = $this->context->businessId();
        $objective  = $this->ownedObjective($objectiveId, $businessId);
        $data       = $this->validatedData($input, $objective, (string) $objective['status']);

        $this->transaction(function () use ($objectiveId, $data): void {
            if (! $this->objectives->update($objectiveId, $data)) {
                throw new WorkflowOperationException('No fue posible actualizar el objetivo.');
            }

            $this->audit->record('objective', $objectiveId, 'updated');
        });
    }

    public function archive(int $objectiveId): void
    {
        $this->authorization->require(BusinessPermissionCatalog::OBJECTIVES_MANAGE);

        $businessId = $this->context->businessId();
        $this->ownedObjective($objectiveId, $businessId);

        $this->transaction(function () use ($objectiveId): void {
            $this->audit->record('objective', $objectiveId, 'deleted');

            if (! $this->objectives->delete($objectiveId)) {
                throw new WorkflowOperationException('No fue posible archivar el objetivo.');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function ownedObjective(int $objectiveId, int $businessId): array
    {
        if ($objectiveId < 1) {
            throw BusinessAccessException::unauthorizedEntity();
        }

        $objective = $this->objectives->findOwned($objectiveId, $businessId);

        if ($objective === null) {
            throw BusinessAccessException::unauthorizedEntity();
        }

        return $objective;
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
        $fields = [
            'title',
            'description',
            'category',
            'status',
            'start_date',
            'target_date',
        ];
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
            $errors['title'] = 'Ingresá un título para el objetivo.';
        } elseif (mb_strlen($data['title']) > 180) {
            $errors['title'] = 'El título no puede superar los 180 caracteres.';
        }

        if (mb_strlen($data['description']) > 5000) {
            $errors['description'] = 'La descripción no puede superar los 5000 caracteres.';
        }

        if ($data['category'] !== ''
            && ! array_key_exists($data['category'], WorkflowCatalog::OBJECTIVE_CATEGORIES)) {
            $errors['category'] = 'Seleccioná una categoría válida.';
        }

        if (! array_key_exists($data['status'], WorkflowCatalog::OBJECTIVE_STATUSES)) {
            $errors['status'] = 'Seleccioná un estado válido.';
        }

        foreach (['start_date', 'target_date'] as $dateField) {
            if ($data[$dateField] !== '' && ! $this->isValidDate($data[$dateField])) {
                $errors[$dateField] = 'Ingresá una fecha válida.';
            }
        }

        if (! isset($errors['start_date'], $errors['target_date'])
            && $data['start_date'] !== ''
            && $data['target_date'] !== ''
            && $data['start_date'] > $data['target_date']) {
            $errors['target_date'] = 'La fecha objetivo no puede ser anterior a la fecha de inicio.';
        }

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
            'category'     => $data['category'] === '' ? null : $data['category'],
            'status'       => $data['status'],
            'start_date'   => $data['start_date'] === '' ? null : $data['start_date'],
            'target_date'  => $data['target_date'] === '' ? null : $data['target_date'],
            'completed_at' => $completedAt,
        ];
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
                'No fue posible guardar el objetivo.',
                previous: $exception,
            );
        }
    }
}
