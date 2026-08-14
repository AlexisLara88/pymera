<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\BusinessAccessException;
use App\Exceptions\CrmValidationException;
use App\Libraries\CrmReturnLocation;
use App\Services\OpportunityService;
use App\Services\OpportunityStatusService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

final class OpportunityController extends BaseController
{
    private OpportunityService $opportunities;
    private OpportunityStatusService $statuses;

    public function __construct()
    {
        $this->opportunities = new OpportunityService();
        $this->statuses      = new OpportunityStatusService();
    }

    public function create(): RedirectResponse|ResponseInterface
    {
        return $this->execute(
            fn (array $input): int => $this->opportunities->create($input),
            'La oportunidad se creó correctamente.',
            'create-opportunity',
        );
    }

    public function update(int $opportunityId): RedirectResponse|ResponseInterface
    {
        return $this->execute(
            function (array $input) use ($opportunityId): void {
                $this->opportunities->update($opportunityId, $input);
            },
            'La oportunidad se actualizó correctamente.',
            'opportunity-' . $opportunityId,
        );
    }

    public function archive(int $opportunityId): RedirectResponse|ResponseInterface
    {
        return $this->execute(
            function (array $_input) use ($opportunityId): void {
                $this->opportunities->archive($opportunityId);
            },
            'La oportunidad se archivó sin eliminar su historial.',
            'opportunity-' . $opportunityId,
        );
    }

    public function changeStatus(int $opportunityId): RedirectResponse|ResponseInterface
    {
        $input = $this->request->getPost();
        $returnLocation = CrmReturnLocation::fromInput($input);

        try {
            $result = $this->statuses->change($opportunityId, $input);
            $success = match (true) {
                $result['finance_recorded'] => 'La oportunidad quedó ganada y la venta se agregó a Finanzas.',
                $result['finance_reversed'] => 'El estado se actualizó y la venta vinculada se revirtió en Finanzas.',
                $result['status_changed']   => 'El estado de la oportunidad se actualizó correctamente.',
                default                    => 'La oportunidad ya tenía ese estado.',
            };

            return redirect()->to($returnLocation)->with('success', $success);
        } catch (CrmValidationException $exception) {
            return redirect()
                ->to($returnLocation)
                ->with('operationError', implode(' ', array_values($exception->errors())));
        } catch (BusinessAccessException) {
            return $this->accessDenied();
        } catch (Throwable $exception) {
            $this->logFailure($exception);

            return redirect()
                ->to($returnLocation)
                ->with('operationError', 'No pudimos actualizar el estado. Intentá nuevamente.');
        }
    }

    /**
     * @param callable(array<string, mixed>): mixed $operation
     */
    private function execute(
        callable $operation,
        string $success,
        string $formKey,
    ): RedirectResponse|ResponseInterface {
        $input = $this->request->getPost();
        $returnLocation = CrmReturnLocation::fromInput($input);

        try {
            $operation($input);

            return redirect()->to($returnLocation)->with('success', $success);
        } catch (CrmValidationException $exception) {
            return redirect()
                ->to($returnLocation)
                ->with('crmErrors', $exception->errors())
                ->with('crmSubmitted', $input)
                ->with('crmFormKey', $formKey);
        } catch (BusinessAccessException) {
            return $this->accessDenied();
        } catch (Throwable $exception) {
            $this->logFailure($exception);

            return redirect()
                ->to($returnLocation)
                ->with('operationError', 'No pudimos completar la operación. Intentá nuevamente.');
        }
    }

    private function accessDenied(): ResponseInterface
    {
        return $this->response
            ->setStatusCode(403)
            ->setBody(view('business/access_denied'));
    }

    private function logFailure(Throwable $exception): void
    {
        log_message(
            'error',
            'CRM opportunity operation failed with {exceptionClass}.',
            ['exceptionClass' => $exception::class],
        );
    }
}
