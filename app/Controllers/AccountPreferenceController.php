<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\UserPreferenceValidationException;
use App\Services\UserPreferenceService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

final class AccountPreferenceController extends BaseController
{
    public function __construct(private ?UserPreferenceService $preferences = null)
    {
        $this->preferences ??= new UserPreferenceService();
    }

    public function index(): ResponseInterface
    {
        $user = auth()->user();

        if ($user === null) {
            return $this->response->setStatusCode(403)->setBody(view('business/access_denied'));
        }

        try {
            $canConfigureCrm = $user->can('app.access');

            return $this->response->setBody(view('account/preferences', [
                'username'        => (string) $user->username,
                'email'           => (string) $user->email,
                'theme'           => $this->preferences->currentTheme(),
                'crmView'         => $this->preferences->currentCrmView(),
                'canConfigureCrm' => $canConfigureCrm,
                'returnUrl'       => $user->inGroup('platform_admin') ? site_url('admin') : site_url('app'),
                'success'         => session()->getFlashdata('success'),
                'error'           => session()->getFlashdata('error'),
                'passwordSuccess' => session()->getFlashdata('password_success'),
                'passwordError'   => session()->getFlashdata('password_error'),
            ]));
        } catch (Throwable $exception) {
            $this->logFailure($exception);

            return $this->response
                ->setStatusCode(500)
                ->setBody(view('business/unavailable'));
        }
    }

    public function update(): RedirectResponse
    {
        try {
            $user = auth()->user();

            if ($user === null) {
                throw new UserPreferenceValidationException(
                    'La cuenta autenticada no está disponible.',
                );
            }

            $crmView = $user->can('app.access')
                ? $this->request->getPost('crm_view_mode')
                : $this->preferences->currentCrmView();

            $this->preferences->updatePreferences(
                $this->request->getPost('appearance_theme'),
                $crmView,
            );

            return redirect()
                ->to(site_url('account/preferences'))
                ->with('success', 'Tus preferencias se guardaron correctamente.');
        } catch (UserPreferenceValidationException $exception) {
            return redirect()
                ->to(site_url('account/preferences'))
                ->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            $this->logFailure($exception);

            return redirect()
                ->to(site_url('account/preferences'))
                ->with('error', 'No pudimos guardar tus preferencias. Intentá nuevamente.');
        }
    }

    private function logFailure(Throwable $exception): void
    {
        log_message(
            'error',
            'Account preferences failed with {exceptionClass}.',
            ['exceptionClass' => $exception::class],
        );
    }
}
