<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BusinessAccessException;
use App\Models\ActivityModel;
use App\Models\AuditEventModel;
use App\Models\BusinessModel;
use App\Models\BusinessProfileModel;
use App\Models\ContactModel;
use App\Models\CrmFinancialPostingModel;
use App\Models\FinancialDailyEntryModel;
use App\Models\ObjectiveModel;
use App\Models\OpportunityModel;
use CodeIgniter\I18n\Time;

/**
 * Appends actor-attributed events only for entities in the active business.
 */
final class AuditEventRecorder
{
    public function __construct(
        private ?AlphaBusinessContext $context = null,
        private ?AuditEventModel $events = null,
        private ?BusinessModel $businesses = null,
        private ?BusinessProfileModel $profiles = null,
        private ?ObjectiveModel $objectives = null,
        private ?ActivityModel $activities = null,
        private ?FinancialDailyEntryModel $financialEntries = null,
        private ?ContactModel $contacts = null,
        private ?OpportunityModel $opportunities = null,
        private ?CrmFinancialPostingModel $crmFinancialPostings = null,
    ) {
        $this->context    ??= new AlphaBusinessContext();
        $this->events     ??= model(AuditEventModel::class);
        $this->businesses ??= model(BusinessModel::class);
        $this->profiles   ??= model(BusinessProfileModel::class);
        $this->objectives ??= model(ObjectiveModel::class);
        $this->activities ??= model(ActivityModel::class);
        $this->financialEntries ??= model(FinancialDailyEntryModel::class);
        $this->contacts          ??= model(ContactModel::class);
        $this->opportunities     ??= model(OpportunityModel::class);
        $this->crmFinancialPostings ??= model(CrmFinancialPostingModel::class);
    }

    public function record(string $entityType, int $entityId, string $action): int
    {
        $businessId = $this->context->businessId();

        if (! $this->belongsToBusiness($entityType, $entityId, $businessId)) {
            throw BusinessAccessException::unauthorizedEntity();
        }

        $eventId = $this->events->insert([
            'business_id' => $businessId,
            'user_id'     => $this->context->actorId(),
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'action'      => $action,
            'occurred_at' => Time::now('UTC')->toDateTimeString(),
        ], true);

        if ($eventId === false) {
            throw new BusinessAccessException('No fue posible registrar el evento auditable.');
        }

        return (int) $eventId;
    }

    private function belongsToBusiness(
        string $entityType,
        int $entityId,
        int $businessId,
    ): bool {
        return match ($entityType) {
            'business' => $entityId === $businessId
                && $this->businesses->find($entityId) !== null,
            'business_profile' => $this->profiles
                ->where('id', $entityId)
                ->where('business_id', $businessId)
                ->first() !== null,
            'objective' => $this->objectives
                ->where('id', $entityId)
                ->where('business_id', $businessId)
                ->first() !== null,
            'activity' => $this->activities->belongsToBusiness(
                $entityId,
                $businessId,
            ),
            'financial_daily_entry' => $this->financialEntries
                ->where('id', $entityId)
                ->where('business_id', $businessId)
                ->first() !== null,
            'contact' => $this->contacts
                ->where('id', $entityId)
                ->where('business_id', $businessId)
                ->first() !== null,
            'opportunity' => $this->opportunities
                ->where('id', $entityId)
                ->where('business_id', $businessId)
                ->first() !== null,
            'crm_financial_posting' => $this->crmFinancialPostings
                ->where('id', $entityId)
                ->where('business_id', $businessId)
                ->first() !== null,
            default => false,
        };
    }
}
