<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\BusinessAccessException;
use App\Exceptions\SaleNoteUnavailableException;
use App\Libraries\SaleNotePdfRenderer;
use App\Services\SaleNoteService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

final class SaleNoteController extends BaseController
{
    private SaleNoteService $saleNotes;
    private SaleNotePdfRenderer $renderer;

    public function __construct()
    {
        $this->saleNotes = new SaleNoteService();
        $this->renderer  = new SaleNotePdfRenderer();
    }

    public function download(int $opportunityId): ResponseInterface|RedirectResponse
    {
        try {
            $contents = $this->renderer->render(
                $this->saleNotes->forOpportunity($opportunityId),
            );

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'attachment; filename="nota-de-venta.pdf"')
                ->setHeader('Cache-Control', 'private, no-store, max-age=0')
                ->setHeader('X-Content-Type-Options', 'nosniff')
                ->setBody($contents);
        } catch (SaleNoteUnavailableException $exception) {
            return redirect()
                ->to(site_url('app/clientes') . '?view=tabs&section=opportunities')
                ->with('operationError', $exception->getMessage());
        } catch (BusinessAccessException) {
            return $this->response
                ->setStatusCode(403)
                ->setBody(view('business/access_denied'));
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Sale note generation failed with {exceptionClass}.',
                ['exceptionClass' => $exception::class],
            );

            return $this->response
                ->setStatusCode(500)
                ->setBody(view('business/unavailable'));
        }
    }
}
