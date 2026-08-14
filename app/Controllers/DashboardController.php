<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\BusinessAccessException;
use App\Services\DashboardService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

final class DashboardController extends BaseController
{
    private DashboardService $dashboard;

    public function __construct()
    {
        $this->dashboard = new DashboardService();
    }

    public function index(): ResponseInterface|RedirectResponse
    {
        try {
            $overview = $this->dashboard->overview();

            if ($overview['requires_onboarding']) {
                return redirect()
                    ->to(site_url('app/mi-negocio') . '#businessEditor')
                    ->with(
                        'onboarding',
                        'Completá las cuatro respuestas mínimas de Mi negocio para habilitar la vista general.',
                    );
            }

            return $this->response->setBody(view('dashboard/index', [
                ...$overview,
                'success' => session()->getFlashdata('success'),
            ]));
        } catch (BusinessAccessException) {
            return $this->response
                ->setStatusCode(403)
                ->setBody(view('business/access_denied'));
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Dashboard module failed with {exceptionClass}.',
                ['exceptionClass' => $exception::class],
            );

            return $this->response
                ->setStatusCode(500)
                ->setBody(view('business/unavailable'));
        }
    }
}
