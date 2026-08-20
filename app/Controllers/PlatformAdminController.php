<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\PlatformAccessException;
use App\Services\PlatformAdministrationService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;
use Throwable;

final class PlatformAdminController extends BaseController
{
    private PlatformAdministrationService $administration;

    public function __construct()
    {
        $this->administration = new PlatformAdministrationService();
    }

    public function index(): ResponseInterface|string
    {
        try {
            return view('platform/index', [
                ...$this->administration->overview(),
                'success'       => session()->getFlashdata('success'),
                'error'         => session()->getFlashdata('error'),
                'initialDialog' => session()->getFlashdata('platformDialog'),
            ]);
        } catch (PlatformAccessException) {
            return $this->response->setStatusCode(403)->setBody(view('business/access_denied'));
        } catch (Throwable $exception) {
            $this->logFailure($exception);

            return $this->response->setStatusCode(500)->setBody(view('business/unavailable'));
        }
    }

    public function createOwner(): RedirectResponse
    {
        $input = $this->request->getPost();

        if (($input['password'] ?? null) !== ($input['password_confirmation'] ?? null)) {
            return $this->backWithError('Las contraseñas no coinciden.', 'owner');
        }

        try {
            $this->administration->createOwner($input);

            return redirect()->to(site_url('admin'))->with(
                'success',
                'La cuenta propietaria y su negocio se crearon correctamente.',
            );
        } catch (PlatformAccessException|RuntimeException $exception) {
            $this->logFailure($exception);

            return $this->backWithError($exception->getMessage(), 'owner');
        } catch (Throwable $exception) {
            $this->logFailure($exception);

            return $this->backWithUnexpectedError('owner');
        }
    }

    public function createAdministrator(): RedirectResponse
    {
        try {
            $this->administration->createAdministrator($this->request->getPost());

            return redirect()->to(site_url('admin'))->with(
                'success',
                'El administrador de plataforma se creó correctamente.',
            );
        } catch (PlatformAccessException|RuntimeException $exception) {
            $this->logFailure($exception);

            return $this->backWithError($exception->getMessage());
        } catch (Throwable $exception) {
            $this->logFailure($exception);

            return $this->backWithUnexpectedError();
        }
    }

    public function setUserStatus(int $userId): RedirectResponse
    {
        try {
            $this->administration->setUserActive(
                $userId,
                $this->request->getPost('status') === 'active',
            );

            return redirect()->to(site_url('admin'))->with('success', 'El estado de la cuenta se actualizó.');
        } catch (PlatformAccessException|RuntimeException $exception) {
            $this->logFailure($exception);

            return $this->backWithError($exception->getMessage());
        } catch (Throwable $exception) {
            $this->logFailure($exception);

            return $this->backWithUnexpectedError();
        }
    }

    public function setMembershipStatus(int $membershipId): RedirectResponse
    {
        try {
            $this->administration->setMembershipStatus(
                $membershipId,
                (string) $this->request->getPost('status'),
            );

            return redirect()->to(site_url('admin'))->with('success', 'El acceso al negocio se actualizó.');
        } catch (PlatformAccessException|RuntimeException $exception) {
            $this->logFailure($exception);

            return $this->backWithError($exception->getMessage());
        } catch (Throwable $exception) {
            $this->logFailure($exception);

            return $this->backWithUnexpectedError();
        }
    }

    private function backWithError(string $message, ?string $dialog = null): RedirectResponse
    {
        $response = redirect()->to(site_url('admin'))->with('error', $message);

        if ($dialog !== null) {
            $response->with('platformDialog', $dialog);
        }

        return $response;
    }

    private function backWithUnexpectedError(?string $dialog = null): RedirectResponse
    {
        return $this->backWithError(
            'No fue posible completar la operación administrativa. Intentá nuevamente.',
            $dialog,
        );
    }

    private function logFailure(Throwable $exception): void
    {
        log_message(
            'error',
            'Platform administration failed with {exceptionClass}.',
            ['exceptionClass' => $exception::class],
        );
    }
}
