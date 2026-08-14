<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\WorkflowCatalog;
use App\Exceptions\BusinessAccessException;
use App\Exceptions\WorkflowValidationException;
use App\Services\ActivityService;
use App\Services\ObjectiveService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

final class ActivityController extends BaseController
{
    private ActivityService $activities;
    private ObjectiveService $objectives;

    public function __construct()
    {
        $this->activities = new ActivityService();
        $this->objectives = new ObjectiveService();
    }

    public function create(int $objectiveId): ResponseInterface|RedirectResponse
    {
        $input = $this->request->getPost();

        try {
            $this->activities->create($objectiveId, $input);

            return redirect()
                ->to(site_url('app/objetivos'))
                ->with('success', 'La actividad se creó y ya aparece en prioridades.');
        } catch (WorkflowValidationException $exception) {
            return $this->renderValidation(
                $input,
                $exception->errors(),
                'create-activity-' . $objectiveId,
            );
        } catch (BusinessAccessException) {
            return $this->accessDenied();
        } catch (Throwable $exception) {
            return $this->operationFailure($exception);
        }
    }

    public function update(int $activityId): ResponseInterface|RedirectResponse
    {
        $input = $this->request->getPost();

        try {
            $this->activities->update($activityId, $input);

            return redirect()
                ->to(site_url('app/objetivos'))
                ->with('success', 'La actividad se actualizó correctamente.');
        } catch (WorkflowValidationException $exception) {
            return $this->renderValidation(
                $input,
                $exception->errors(),
                'activity-' . $activityId,
            );
        } catch (BusinessAccessException) {
            return $this->accessDenied();
        } catch (Throwable $exception) {
            return $this->operationFailure($exception);
        }
    }

    public function archive(int $activityId): ResponseInterface|RedirectResponse
    {
        try {
            $this->activities->archive($activityId);

            return redirect()
                ->to(site_url('app/objetivos'))
                ->with('success', 'La actividad se archivó correctamente.');
        } catch (BusinessAccessException) {
            return $this->accessDenied();
        } catch (Throwable $exception) {
            return $this->operationFailure($exception);
        }
    }

    /**
     * @param array<string, mixed>  $submitted
     * @param array<string, string> $errors
     */
    private function renderValidation(
        array $submitted,
        array $errors,
        string $formKey,
    ): ResponseInterface {
        try {
            return $this->render($submitted, $errors, $formKey, 422);
        } catch (BusinessAccessException) {
            return $this->accessDenied();
        } catch (Throwable $exception) {
            return $this->unavailable($exception);
        }
    }

    /**
     * @param array<string, mixed>  $submitted
     * @param array<string, string> $errors
     */
    private function render(
        array $submitted = [],
        array $errors = [],
        ?string $formKey = null,
        int $status = 200,
        ?string $operationError = null,
    ): ResponseInterface {
        return $this->response
            ->setStatusCode($status)
            ->setBody(view('objectives/index', [
                ...$this->objectives->overview(),
                'objectiveCategories' => WorkflowCatalog::OBJECTIVE_CATEGORIES,
                'objectiveStatuses'   => WorkflowCatalog::OBJECTIVE_STATUSES,
                'activityStatuses'    => WorkflowCatalog::ACTIVITY_STATUSES,
                'submitted'           => $submitted,
                'errors'              => $errors,
                'formKey'             => $formKey,
                'operationError'      => $operationError,
                'success'             => session()->getFlashdata('success'),
            ]));
    }

    private function operationFailure(Throwable $exception): ResponseInterface
    {
        $this->logFailure($exception);

        try {
            return $this->render(
                status: 500,
                operationError: 'No pudimos completar la operación. Intentá nuevamente.',
            );
        } catch (Throwable $renderException) {
            return $this->unavailable($renderException);
        }
    }

    private function accessDenied(): ResponseInterface
    {
        return $this->response
            ->setStatusCode(403)
            ->setBody(view('business/access_denied'));
    }

    private function unavailable(Throwable $exception): ResponseInterface
    {
        $this->logFailure($exception);

        return $this->response
            ->setStatusCode(500)
            ->setBody(view('business/unavailable'));
    }

    private function logFailure(Throwable $exception): void
    {
        log_message(
            'error',
            'Activity module failed with {exceptionClass}.',
            ['exceptionClass' => $exception::class],
        );
    }
}
