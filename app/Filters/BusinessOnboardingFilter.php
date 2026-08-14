<?php

declare(strict_types=1);

namespace App\Filters;

use App\Exceptions\BusinessAccessException;
use App\Services\BusinessService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

final class BusinessOnboardingFilter implements FilterInterface
{
    public function __construct(private ?BusinessService $businesses = null)
    {
        $this->businesses ??= new BusinessService();
    }

    public function before(RequestInterface $request, $arguments = null): ?ResponseInterface
    {
        try {
            $details = $this->businesses->details();
        } catch (BusinessAccessException) {
            // The destination controller preserves its specific safe denial.
            return null;
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Business onboarding guard failed with {exceptionClass}.',
                ['exceptionClass' => $exception::class],
            );

            return service('response')
                ->setStatusCode(500)
                ->setBody(view('business/unavailable'));
        }

        if ($details['minimum_profile_complete']) {
            return null;
        }

        return redirect()
            ->to(site_url('app/mi-negocio') . '#businessEditor')
            ->with(
                'onboarding',
                'Completá las cuatro respuestas mínimas para configurar el perfil inicial de tu negocio.',
            );
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null,
    ): ?ResponseInterface {
        return null;
    }
}
