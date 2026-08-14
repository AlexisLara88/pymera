<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\BusinessAccessException;
use App\Exceptions\BusinessValidationException;
use App\Services\BusinessService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

final class BusinessController extends BaseController
{
    private BusinessService $businessService;

    public function __construct()
    {
        $this->businessService = new BusinessService();
    }

    public function show(): ResponseInterface
    {
        try {
            return $this->renderForm($this->businessService->details());
        } catch (BusinessAccessException) {
            return $this->accessDenied();
        } catch (Throwable $exception) {
            return $this->unavailable($exception);
        }
    }

    public function update(): ResponseInterface|RedirectResponse
    {
        $input = $this->request->getPost();

        try {
            $wasOnboarding = ! $this->businessService->details()['minimum_profile_complete'];
            $this->businessService->update($input);

            if ($wasOnboarding) {
                return redirect()
                    ->to(site_url('app'))
                    ->with('success', 'El perfil inicial del negocio quedó configurado.');
            }

            return redirect()
                ->to(site_url('app/mi-negocio'))
                ->with('success', 'El perfil del negocio se guardó correctamente.');
        } catch (BusinessValidationException $exception) {
            try {
                return $this->renderForm(
                    $this->businessService->details(),
                    $input,
                    $exception->errors(),
                    422,
                );
            } catch (BusinessAccessException) {
                return $this->accessDenied();
            }
        } catch (BusinessAccessException) {
            return $this->accessDenied();
        } catch (Throwable $exception) {
            try {
                $this->logFailure($exception);

                return $this->renderForm(
                    $this->businessService->details(),
                    $input,
                    [],
                    500,
                    'No pudimos guardar los cambios. Intentá nuevamente.',
                );
            } catch (Throwable $renderException) {
                return $this->unavailable($renderException);
            }
        }
    }

    /**
     * @param array{
     *     business: array<string, mixed>,
     *     profile: array<string, mixed>|null,
     *     profile_completion: int,
     *     minimum_profile_completion: int,
     *     minimum_profile_complete: bool
     * } $details
     * @param array<string, mixed>|null $submitted
     * @param array<string, string>     $errors
     */
    private function renderForm(
        array $details,
        ?array $submitted = null,
        array $errors = [],
        int $status = 200,
        ?string $operationError = null,
    ): ResponseInterface {
        $form = $this->formData($details);

        if ($submitted !== null) {
            foreach (array_keys($form) as $field) {
                if (array_key_exists($field, $submitted) && is_string($submitted[$field])) {
                    $form[$field] = $submitted[$field];
                }
            }
        }

        return $this->response
            ->setStatusCode($status)
            ->setBody(view('business/profile', [
                'business'      => $details['business'],
                'form'          => $form,
                'profileCompletion' => $details['profile_completion'],
                'isOnboarding'      => ! $details['minimum_profile_complete'],
                'errors'        => $errors,
                'operationError' => $operationError,
                'success'       => session()->getFlashdata('success'),
                'onboardingNotice' => session()->getFlashdata('onboarding'),
            ]));
    }

    /**
     * @param array{
     *     business: array<string, mixed>,
     *     profile: array<string, mixed>|null,
     *     profile_completion: int,
     *     minimum_profile_completion: int,
     *     minimum_profile_complete: bool
     * } $details
     *
     * @return array<string, string>
     */
    private function formData(array $details): array
    {
        $profile = $details['profile'] ?? [];

        return [
            'name'                     => (string) ($details['business']['name'] ?? ''),
            'currency_code'            => (string) ($details['business']['currency_code'] ?? ''),
            'timezone'                 => (string) ($details['business']['timezone'] ?? ''),
            'what_it_does'             => (string) ($profile['what_it_does'] ?? ''),
            'customers_served'         => (string) ($profile['customers_served'] ?? ''),
            'products_offered'         => (string) ($profile['products_offered'] ?? ''),
            'objectives_summary'       => (string) ($profile['objectives_summary'] ?? ''),
            'differentiator'            => (string) ($profile['differentiator'] ?? ''),
            'differentiation_delivery' => (string) ($profile['differentiation_delivery'] ?? ''),
            'customer_outcome'         => (string) ($profile['customer_outcome'] ?? ''),
            'purchase_reason'          => (string) ($profile['purchase_reason'] ?? ''),
            'acquisition_channels'     => (string) ($profile['acquisition_channels'] ?? ''),
        ];
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
            'Business module failed with {exceptionClass}.',
            ['exceptionClass' => $exception::class],
        );
    }
}
