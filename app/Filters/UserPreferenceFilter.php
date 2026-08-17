<?php

declare(strict_types=1);

namespace App\Filters;

use App\Services\UserPreferenceService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

final class UserPreferenceFilter implements FilterInterface
{
    public function __construct(private ?UserPreferenceService $preferences = null)
    {
        $this->preferences ??= new UserPreferenceService();
    }

    public function before(RequestInterface $request, $arguments = null): ?ResponseInterface
    {
        try {
            $this->preferences->hydrateSession();
        } catch (Throwable $exception) {
            log_message(
                'error',
                'User preferences could not be loaded for the authenticated session: {exceptionClass}.',
                ['exceptionClass' => $exception::class],
            );
        }

        return null;
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null,
    ): ?ResponseInterface {
        return null;
    }
}
