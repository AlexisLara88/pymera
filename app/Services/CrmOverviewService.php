<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\BusinessPermissionCatalog;
use App\Domain\CrmCatalog;
use App\Models\ContactModel;
use App\Models\CrmFinancialPostingModel;
use App\Models\OpportunityModel;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

final class CrmOverviewService
{
    public function __construct(
        private ?AlphaBusinessContext $context = null,
        private ?BusinessAuthorizationService $authorization = null,
        private ?AuthorizedBusinessReader $reader = null,
        private ?ContactModel $contacts = null,
        private ?OpportunityModel $opportunities = null,
        private ?CrmFinancialPostingModel $postings = null,
    ) {
        $this->context       ??= new AlphaBusinessContext();
        $this->authorization ??= new BusinessAuthorizationService($this->context);
        $this->reader        ??= new AuthorizedBusinessReader($this->context);
        $this->contacts      ??= model(ContactModel::class);
        $this->opportunities ??= model(OpportunityModel::class);
        $this->postings      ??= model(CrmFinancialPostingModel::class);
    }

    /**
     * @return array{
     *     business: array<string, mixed>,
     *     contacts: list<array<string, mixed>>,
     *     opportunities: list<array<string, mixed>>,
     *     crm_summary: array<string, int>,
     *     crm_catalogs: array<string, array<string, string>>,
     *     today: string
     * }
     */
    public function overview(): array
    {
        $this->authorization->require(BusinessPermissionCatalog::CRM_VIEW);

        $businessId   = $this->context->businessId();
        $business     = $this->reader->business();
        $contacts     = $this->contacts->findForBusiness($businessId);
        $contactReferences = $this->contacts->findReferencesForBusiness($businessId);
        $opportunities = $this->opportunities->findForBusiness($businessId);
        $financialPostings = $this->postings->findForBusiness($businessId);
        $postingMap = [];
        $contactMap   = [];

        foreach ($financialPostings as $posting) {
            $posting['amount_cents'] = $this->decimalToCents((string) $posting['amount']);
            $postingMap[(int) $posting['opportunity_id']] = $posting;
        }

        foreach ($contacts as &$contact) {
            $contact = $this->presentContact($contact);
        }
        unset($contact);

        foreach ($contactReferences as $contactReference) {
            $presented = $this->presentContact($contactReference);
            $contactMap[(int) $presented['id']] = $presented;
        }

        $today = $this->today($business);
        $openOpportunityCount = 0;
        $openValueCents = 0;
        $overdueFollowUpCount = 0;

        foreach ($opportunities as &$opportunity) {
            $isOpen = in_array(
                $opportunity['status'],
                CrmCatalog::OPEN_OPPORTUNITY_STATUSES,
                true,
            );
            $estimatedValueCents = $this->decimalToCents(
                $opportunity['estimated_value'] === null
                    ? '0'
                    : (string) $opportunity['estimated_value'],
            );

            $opportunity['status_label'] = CrmCatalog::OPPORTUNITY_STATUSES[$opportunity['status']]
                ?? (string) $opportunity['status'];
            $opportunity['estimated_value_cents'] = $estimatedValueCents;
            $opportunity['contact'] = $contactMap[(int) $opportunity['contact_id']] ?? null;
            $opportunity['finance_posting'] = $postingMap[(int) $opportunity['id']] ?? null;
            $opportunity['is_open'] = $isOpen;
            $opportunity['is_follow_up_overdue'] = $isOpen
                && $opportunity['next_follow_up_date'] !== null
                && $opportunity['next_follow_up_date'] < $today;

            if ($isOpen) {
                $openOpportunityCount++;
                $openValueCents += $estimatedValueCents;
            }

            if ($opportunity['is_follow_up_overdue']) {
                $overdueFollowUpCount++;
            }
        }
        unset($opportunity);

        return [
            'business'      => $business,
            'today'         => $today,
            'contacts'      => $contacts,
            'opportunities' => $opportunities,
            'crm_summary'   => [
                'prospect_count' => count(array_filter(
                    $contacts,
                    static fn (array $contact): bool => $contact['lifecycle_stage'] === 'prospect',
                )),
                'client_count' => count(array_filter(
                    $contacts,
                    static fn (array $contact): bool => $contact['lifecycle_stage'] === 'client',
                )),
                'open_opportunity_count'  => $openOpportunityCount,
                'open_value_cents'        => $openValueCents,
                'overdue_follow_up_count' => $overdueFollowUpCount,
            ],
            'crm_catalogs' => [
                'contact_kinds'       => CrmCatalog::CONTACT_KINDS,
                'lifecycle_stages'    => CrmCatalog::LIFECYCLE_STAGES,
                'acquisition_channels' => CrmCatalog::ACQUISITION_CHANNELS,
                'opportunity_statuses' => CrmCatalog::OPPORTUNITY_STATUSES,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $business
     */
    private function today(array $business): string
    {
        try {
            $timezone = new DateTimeZone((string) ($business['timezone'] ?? 'UTC'));
        } catch (Throwable) {
            $timezone = new DateTimeZone('UTC');
        }

        return (new DateTimeImmutable('now', $timezone))->format('Y-m-d');
    }

    /**
     * @param array<string, mixed> $contact
     *
     * @return array<string, mixed>
     */
    private function presentContact(array $contact): array
    {
        $contact['contact_kind_label'] = CrmCatalog::CONTACT_KINDS[$contact['contact_kind']]
            ?? (string) $contact['contact_kind'];
        $contact['lifecycle_stage_label'] = CrmCatalog::LIFECYCLE_STAGES[$contact['lifecycle_stage']]
            ?? (string) $contact['lifecycle_stage'];
        $contact['acquisition_channel_label'] = $contact['acquisition_channel'] === null
            ? 'Sin especificar'
            : (CrmCatalog::ACQUISITION_CHANNELS[$contact['acquisition_channel']]
                ?? (string) $contact['acquisition_channel']);
        $contact['is_archived'] = $contact['deleted_at'] !== null;

        return $contact;
    }

    private function decimalToCents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $fraction = str_pad(substr($fraction, 0, 2), 2, '0');

        return ((int) $whole * 100) + (int) $fraction;
    }
}
