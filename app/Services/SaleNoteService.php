<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\BusinessPermissionCatalog;
use App\Exceptions\BusinessAccessException;
use App\Exceptions\CrmValidationException;
use App\Exceptions\SaleNoteUnavailableException;
use App\Models\ContactModel;
use App\Models\CrmFinancialPostingModel;
use App\Models\OpportunityModel;

final class SaleNoteService
{
    public function __construct(
        private ?AlphaBusinessContext $context = null,
        private ?BusinessAuthorizationService $authorization = null,
        private ?AuthorizedBusinessReader $reader = null,
        private ?OpportunityModel $opportunities = null,
        private ?ContactModel $contacts = null,
        private ?CrmFinancialPostingModel $postings = null,
        private ?ContactService $contactService = null,
    ) {
        $this->context       ??= new AlphaBusinessContext();
        $this->authorization ??= new BusinessAuthorizationService($this->context);
        $this->reader        ??= new AuthorizedBusinessReader($this->context);
        $this->opportunities ??= model(OpportunityModel::class);
        $this->contacts      ??= model(ContactModel::class);
        $this->postings      ??= model(CrmFinancialPostingModel::class);
        $this->contactService ??= new ContactService();
    }

    /**
     * @return array{
     *     business_name: string,
     *     currency_code: string,
     *     customer_name: string,
     *     customer_email: string|null,
     *     customer_phone: string|null,
     *     customer_identity_document: string,
     *     description: string,
     *     sale_date: string,
     *     amount: string
     * }
     */
    public function forOpportunity(int $opportunityId): array
    {
        $source = $this->sourceForOpportunity($opportunityId);
        $contact = $source['contact'];
        $identityDocument = trim((string) ($contact['identity_document'] ?? ''));

        if ($identityDocument === '') {
            throw new SaleNoteUnavailableException(
                'Completá el DNI/CI del contacto para descargar la nota de venta.',
            );
        }

        return [
            'business_name'   => (string) $source['business']['name'],
            'currency_code'   => (string) $source['business']['currency_code'],
            'customer_name'   => (string) $contact['display_name'],
            'customer_email'  => $contact['email'] === null ? null : (string) $contact['email'],
            'customer_phone'  => $contact['phone'] === null ? null : (string) $contact['phone'],
            'customer_identity_document' => $identityDocument,
            'description'     => (string) $source['opportunity']['need'],
            'sale_date'       => (string) $source['posting']['sale_date'],
            'amount'          => $this->normalizeAmount((string) $source['posting']['amount']),
        ];
    }

    /**
     * @return array{
     *     business_name: string,
     *     currency_code: string,
     *     customer_name: string,
     *     customer_email: string|null,
     *     customer_phone: string|null,
     *     customer_identity_document: string,
     *     description: string,
     *     sale_date: string,
     *     amount: string
     * }
     *
     * @throws CrmValidationException
     */
    public function completeIdentityAndBuild(
        int $opportunityId,
        mixed $identityDocument,
    ): array {
        $source = $this->sourceForOpportunity($opportunityId);

        $this->contactService->updateIdentityDocumentForSaleNote(
            (int) $source['contact']['id'],
            $identityDocument,
        );

        return $this->forOpportunity($opportunityId);
    }

    /**
     * @return array{
     *     business: array<string, mixed>,
     *     contact: array<string, mixed>,
     *     opportunity: array<string, mixed>,
     *     posting: array<string, mixed>
     * }
     */
    private function sourceForOpportunity(int $opportunityId): array
    {
        $this->authorization->require(BusinessPermissionCatalog::CRM_VIEW);
        $this->authorization->require(BusinessPermissionCatalog::FINANCES_VIEW);

        $businessId  = $this->context->businessId();
        $opportunity = $this->opportunities->findOwned($opportunityId, $businessId);

        if ($opportunity === null) {
            throw BusinessAccessException::unauthorizedEntity();
        }

        if ($opportunity['status'] !== 'won') {
            throw new SaleNoteUnavailableException(
                'La nota de venta sólo está disponible para oportunidades ganadas.',
            );
        }

        $posting = $this->postings->findForOpportunity($opportunityId, $businessId);

        if ($posting === null || $posting['status'] !== 'recorded') {
            throw new SaleNoteUnavailableException(
                'Registrá primero la venta en Finanzas para descargar su nota.',
            );
        }

        $contact = $this->contacts->findOwnedReference(
            (int) $opportunity['contact_id'],
            $businessId,
        );

        if ($contact === null) {
            throw new SaleNoteUnavailableException(
                'No encontramos el contacto asociado a esta venta.',
            );
        }

        $business = $this->reader->business();

        return compact('business', 'contact', 'opportunity', 'posting');
    }

    private function normalizeAmount(string $amount): string
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return $whole . '.' . str_pad(substr($fraction, 0, 2), 2, '0');
    }
}
