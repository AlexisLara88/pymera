<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\AccountPasswordValidationException;
use App\Services\AccountPasswordService;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

final class AccountPasswordController extends BaseController
{
    public function __construct(private ?AccountPasswordService $passwords = null)
    {
        $this->passwords ??= new AccountPasswordService();
    }

    public function update(): RedirectResponse
    {
        try {
            $this->passwords->changePassword(
                $this->request->getPost('current_password'),
                $this->request->getPost('new_password'),
                $this->request->getPost('new_password_confirmation'),
            );

            return redirect()
                ->to(site_url('account/preferences'))
                ->with('password_success', 'Tu contraseña se actualizó correctamente.');
        } catch (AccountPasswordValidationException $exception) {
            return redirect()
                ->to(site_url('account/preferences'))
                ->with('password_error', $exception->getMessage());
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Account password update failed with {exceptionClass}.',
                ['exceptionClass' => $exception::class],
            );

            return redirect()
                ->to(site_url('account/preferences'))
                ->with('password_error', 'No pudimos actualizar tu contraseña. Intentá nuevamente.');
        }
    }
}
