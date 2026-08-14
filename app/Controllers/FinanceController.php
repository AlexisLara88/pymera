<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\FinanceCatalog;
use App\Exceptions\BusinessAccessException;
use App\Exceptions\FinanceValidationException;
use App\Services\FinanceService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

final class FinanceController extends BaseController
{
    private FinanceService $finances;

    public function __construct()
    {
        $this->finances = new FinanceService();
    }

    public function index(): ResponseInterface
    {
        $period = $this->request->getGet('period');

        try {
            return $this->render(is_string($period) ? $period : null);
        } catch (FinanceValidationException $exception) {
            return $this->render(
                null,
                errors: $exception->errors(),
                status: 422,
            );
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
            $this->finances->create($input);

            return redirect()
                ->to($this->financeUrl($input['operation_date'] ?? null))
                ->with('success', 'El registro financiero se creó correctamente.');
        } catch (FinanceValidationException $exception) {
            return $this->renderValidation($input, $exception->errors(), 'create-entry');
        } catch (BusinessAccessException) {
            return $this->accessDenied();
        } catch (Throwable $exception) {
            return $this->operationFailure($exception);
        }
    }

    public function update(int $entryId): ResponseInterface|RedirectResponse
    {
        $input = $this->request->getPost();

        try {
            $this->finances->update($entryId, $input);

            return redirect()
                ->to($this->financeUrl($input['operation_date'] ?? null))
                ->with('success', 'El registro financiero se actualizó correctamente.');
        } catch (FinanceValidationException $exception) {
            return $this->renderValidation(
                $input,
                $exception->errors(),
                'entry-' . $entryId,
            );
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
        $operationDate = is_string($submitted['operation_date'] ?? null)
            ? $submitted['operation_date']
            : '';
        $period = preg_match('/^\d{4}-\d{2}-\d{2}$/', $operationDate)
            ? substr($operationDate, 0, 7)
            : null;

        try {
            return $this->render($period, $submitted, $errors, $formKey, 422);
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
        ?string $period = null,
        array $submitted = [],
        array $errors = [],
        ?string $formKey = null,
        int $status = 200,
        ?string $operationError = null,
    ): ResponseInterface {
        return $this->response
            ->setStatusCode($status)
            ->setBody(view('finances/index', [
                ...$this->finances->overview($period),
                'financeStatuses' => FinanceCatalog::STATUSES,
                'submitted'       => $submitted,
                'errors'          => $errors,
                'formKey'         => $formKey,
                'operationError'  => $operationError,
                'success'         => session()->getFlashdata('success'),
            ]));
    }

    private function financeUrl(mixed $operationDate): string
    {
        $url = site_url('app/finanzas');

        if (is_string($operationDate)
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $operationDate)) {
            return $url . '?period=' . rawurlencode(substr($operationDate, 0, 7));
        }

        return $url;
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
            'Finance module failed with {exceptionClass}.',
            ['exceptionClass' => $exception::class],
        );
    }
}
