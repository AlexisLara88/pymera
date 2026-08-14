<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\BusinessPermissionCatalog;
use App\Exceptions\BusinessAccessException;
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
    ) {
        $this->context       ??= new AlphaBusinessContext();
        $this->authorization ??= new BusinessAuthorizationService($this->context);
        $this->reader        ??= new AuthorizedBusinessReader($this->context);
        $this->opportunities ??= model(OpportunityModel::class);
        $this->contacts      ??= model(ContactModel::class);
        $this->postings      ??= model(CrmFinancialPostingModel::class);
    }

    /**
     * @return array{
     *     business_name: string,
     *     currency_code: string,
     *     customer_name: string,
     *     customer_email: string|null,
     *     customer_phone: string|null,
     *     description: string,
     *     sale_date: string,
     *     amount: string
     * }
     */
    public function forOpportunity(int $opportunityId): array
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

        return [
            'business_name'   => (string) $business['name'],
            'currency_code'   => (string) $business['currency_code'],
            'customer_name'   => (string) $contact['display_name'],
            'customer_email'  => $contact['email'] === null ? null : (string) $contact['email'],
            'customer_phone'  => $contact['phone'] === null ? null : (string) $contact['phone'],
            'description'     => (string) $opportunity['need'],
            'sale_date'       => (string) $posting['sale_date'],
            'amount'          => $this->normalizeAmount((string) $posting['amount']),
        ];
    }

    private function normalizeAmount(string $amount): string
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return $whole . '.' . str_pad(substr($fraction, 0, 2), 2, '0');
    }
}
