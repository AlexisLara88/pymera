<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\BusinessAccessException;
use App\Exceptions\CrmValidationException;
use App\Libraries\CrmReturnLocation;
use App\Services\ContactService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

final class ContactController extends BaseController
{
    private ContactService $contacts;

    public function __construct()
    {
        $this->contacts = new ContactService();
    }

    public function create(): RedirectResponse|ResponseInterface
    {
        return $this->execute(
            fn (array $input): int => $this->contacts->create($input),
            'El contacto se creó correctamente.',
            'create-contact',
        );
    }

    public function update(int $contactId): RedirectResponse|ResponseInterface
    {
        return $this->execute(
            function (array $input) use ($contactId): void {
                $this->contacts->update($contactId, $input);
            },
            'El contacto se actualizó correctamente.',
            'contact-' . $contactId,
        );
    }

    public function convert(int $contactId): RedirectResponse|ResponseInterface
    {
        return $this->execute(
            function (array $_input) use ($contactId): void {
                $this->contacts->convertToClient($contactId);
            },
            'El prospecto se convirtió en cliente.',
            'contact-' . $contactId,
        );
    }

    public function archive(int $contactId): RedirectResponse|ResponseInterface
    {
        return $this->execute(
            function (array $_input) use ($contactId): void {
                $this->contacts->archive($contactId);
            },
            'El contacto se archivó sin eliminar su historial.',
            'contact-' . $contactId,
        );
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
            'CRM contact operation failed with {exceptionClass}.',
            ['exceptionClass' => $exception::class],
        );
    }
}
