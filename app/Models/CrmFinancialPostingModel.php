<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class CrmFinancialPostingModel extends Model
{
    protected $table         = 'crm_financial_postings';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'business_id',
        'opportunity_id',
        'financial_daily_entry_id',
        'sale_date',
        'amount',
        'status',
    ];
    protected $validationRules = [
        'business_id'             => 'required|is_natural_no_zero',
        'opportunity_id'          => 'required|is_natural_no_zero',
        'financial_daily_entry_id' => 'required|is_natural_no_zero',
        'sale_date'               => 'required|valid_date[Y-m-d]',
        'amount'                  => 'required|decimal|greater_than[0]',
        'status'                  => 'required|in_list[recorded,reversed]',
    ];

    /** @return array<string, mixed>|null */
    public function findForOpportunity(int $opportunityId, int $businessId): ?array
    {
        return $this
            ->where('opportunity_id', $opportunityId)
            ->where('business_id', $businessId)
            ->first();
    }

    /** @return list<array<string, mixed>> */
    public function findRecordedForEntry(int $entryId, int $businessId): array
    {
        return $this
            ->where('financial_daily_entry_id', $entryId)
            ->where('business_id', $businessId)
            ->where('status', 'recorded')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /** @return list<array<string, mixed>> */
    public function findRecordedForBusinessPeriod(
        int $businessId,
        string $periodStart,
        string $periodEnd,
    ): array {
        return $this
            ->where('business_id', $businessId)
            ->where('sale_date >=', $periodStart)
            ->where('sale_date <=', $periodEnd)
            ->where('status', 'recorded')
            ->orderBy('sale_date', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    /** @return list<array<string, mixed>> */
    public function findForBusiness(int $businessId): array
    {
        return $this
            ->where('business_id', $businessId)
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}
