<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\BusinessAccessException;
use App\Services\CrmOverviewService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

final class CrmController extends BaseController
{
    private CrmOverviewService $crm;

    public function __construct()
    {
        $this->crm = new CrmOverviewService();
    }

    public function index(): ResponseInterface
    {
        try {
            return $this->response->setBody(view('crm/index', [
                ...$this->crm->overview(),
                'success'        => session()->getFlashdata('success'),
                'operationError' => session()->getFlashdata('operationError'),
                'crmErrors'      => session()->getFlashdata('crmErrors') ?? [],
                'crmSubmitted'   => session()->getFlashdata('crmSubmitted') ?? [],
                'crmFormKey'     => session()->getFlashdata('crmFormKey'),
            ]));
        } catch (BusinessAccessException) {
            return $this->response
                ->setStatusCode(403)
                ->setBody(view('business/access_denied'));
        } catch (Throwable $exception) {
            log_message(
                'error',
                'CRM module failed with {exceptionClass}.',
                ['exceptionClass' => $exception::class],
            );

            return $this->response
                ->setStatusCode(500)
                ->setBody(view('business/unavailable'));
        }
    }
}
