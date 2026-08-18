<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\BusinessAccessException;
use App\Exceptions\CrmValidationException;
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
            return $this->pdfResponse(
                $this->saleNotes->forOpportunity($opportunityId),
            );
        } catch (SaleNoteUnavailableException $exception) {
            return redirect()
                ->to(site_url('app/clientes') . '?section=opportunities')
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

    public function completeAndDownload(int $opportunityId): ResponseInterface|RedirectResponse
    {
        try {
            return $this->pdfResponse(
                $this->saleNotes->completeIdentityAndBuild(
                    $opportunityId,
                    $this->request->getPost('identity_document'),
                ),
            );
        } catch (CrmValidationException $exception) {
            return redirect()
                ->to(site_url('app/clientes') . '?section=opportunities')
                ->with(
                    'operationError',
                    $exception->errors()['identity_document']
                        ?? 'Revisá el DNI/CI antes de generar la nota.',
                );
        } catch (SaleNoteUnavailableException $exception) {
            return redirect()
                ->to(site_url('app/clientes') . '?section=opportunities')
                ->with('operationError', $exception->getMessage());
        } catch (BusinessAccessException) {
            return $this->response
                ->setStatusCode(403)
                ->setBody(view('business/access_denied'));
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Sale note completion failed with {exceptionClass}.',
                ['exceptionClass' => $exception::class],
            );

            return $this->response
                ->setStatusCode(500)
                ->setBody(view('business/unavailable'));
        }
    }

    /**
     * @param array<string, mixed> $saleNote
     */
    private function pdfResponse(array $saleNote): ResponseInterface
    {
        $contents = $this->renderer->render($saleNote);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="nota-de-venta.pdf"')
            ->setHeader('Cache-Control', 'private, no-store, max-age=0')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($contents);
    }
}
