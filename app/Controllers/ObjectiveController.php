<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\WorkflowCatalog;
use App\Exceptions\BusinessAccessException;
use App\Exceptions\WorkflowValidationException;
use App\Services\ObjectiveService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

final class ObjectiveController extends BaseController
{
    private ObjectiveService $objectives;

    public function __construct()
    {
        $this->objectives = new ObjectiveService();
    }

    public function index(): ResponseInterface
    {
        try {
            return $this->render();
        } catch (BusinessAccessException) {
            return $this->accessDenied();
        } catch (Throwable $exception) {
            return $this->unavailable($exception);
        }
    }

    public function create(): ResponseInterface|RedirectResponse
    {
        $input = $this->request->getPost();

        try {
            $this->objectives->create($input);

            return redirect()
                ->to(site_url('app/objetivos'))
                ->with('success', 'El objetivo se creó correctamente.');
        } catch (WorkflowValidationException $exception) {
            return $this->renderValidation(
                $input,
                $exception->errors(),
                'create-objective',
            );
        } catch (BusinessAccessException) {
            return $this->accessDenied();
        } catch (Throwable $exception) {
            return $this->operationFailure($exception);
        }
    }

    public function update(int $objectiveId): ResponseInterface|RedirectResponse
    {
        $input = $this->request->getPost();

        try {
            $this->objectives->update($objectiveId, $input);

            return redirect()
                ->to(site_url('app/objetivos'))
                ->with('success', 'El objetivo se actualizó correctamente.');
        } catch (WorkflowValidationException $exception) {
            return $this->renderValidation(
                $input,
                $exception->errors(),
                'objective-' . $objectiveId,
            );
        } catch (BusinessAccessException) {
            return $this->accessDenied();
        } catch (Throwable $exception) {
            return $this->operationFailure($exception);
        }
    }

    public function archive(int $objectiveId): ResponseInterface|RedirectResponse
    {
        try {
            $this->objectives->archive($objectiveId);

            return redirect()
                ->to(site_url('app/objetivos'))
                ->with('success', 'El objetivo se archivó sin eliminar su historial.');
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
            'Objective module failed with {exceptionClass}.',
            ['exceptionClass' => $exception::class],
        );
    }
}
