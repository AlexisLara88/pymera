<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\WorkflowCatalog;
use App\Exceptions\BusinessAccessException;
use App\Services\ObjectiveService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

final class PriorityController extends BaseController
{
    private ObjectiveService $objectives;

    public function __construct()
    {
        $this->objectives = new ObjectiveService();
    }

    public function index(): ResponseInterface
    {
        try {
            return $this->response->setBody(view('priorities/index', [
                ...$this->objectives->overview(),
                'quadrantLabels' => WorkflowCatalog::QUADRANTS,
            ]));
        } catch (BusinessAccessException) {
            return $this->response
                ->setStatusCode(403)
                ->setBody(view('business/access_denied'));
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Priority module failed with {exceptionClass}.',
                ['exceptionClass' => $exception::class],
            );

            return $this->response
                ->setStatusCode(500)
                ->setBody(view('business/unavailable'));
        }
    }
}
