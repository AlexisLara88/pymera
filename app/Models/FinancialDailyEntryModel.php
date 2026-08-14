<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\I18n\Time;
use CodeIgniter\Model;

class FinancialDailyEntryModel extends Model
{
    protected $table         = 'financial_daily_entries';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'business_id',
        'operation_date',
        'income_amount',
        'fixed_expense_amount',
        'variable_expense_amount',
        'administrative_expense_amount',
        'status',
        'notes',
    ];
    protected $validationRules = [
        'business_id'             => 'required|is_natural_no_zero',
        'operation_date'          => 'required|valid_date[Y-m-d]',
        'income_amount'           => 'required|decimal|greater_than_equal_to[0]',
        'fixed_expense_amount'    => 'required|decimal|greater_than_equal_to[0]',
        'variable_expense_amount' => 'required|decimal|greater_than_equal_to[0]',
        'administrative_expense_amount' => 'required|decimal|greater_than_equal_to[0]',
        'status'                  => 'required|in_list[recorded,draft]',
        'notes'                   => 'permit_empty|max_length[1000]',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function findForBusinessPeriod(
        int $businessId,
        string $periodStart,
        string $periodEnd,
    ): array {
        return $this
            ->where('business_id', $businessId)
            ->where('operation_date >=', $periodStart)
            ->where('operation_date <=', $periodEnd)
            ->orderBy('operation_date', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOwned(int $entryId, int $businessId): ?array
    {
        return $this
            ->where('id', $entryId)
            ->where('business_id', $businessId)
            ->first();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForDate(int $businessId, string $operationDate): ?array
    {
        return $this
            ->where('business_id', $businessId)
            ->where('operation_date', $operationDate)
            ->first();
    }

    public function addIncomeAmount(int $entryId, int $businessId, string $amount): bool
    {
        $builder = $this->builder();
        $builder
            ->set('income_amount', 'income_amount + ' . $this->db->escape($amount), false)
            ->set('status', 'recorded')
            ->set('updated_at', Time::now('UTC')->toDateTimeString())
            ->where('id', $entryId)
            ->where('business_id', $businessId)
            ->update();

        return $this->db->affectedRows() === 1;
    }

    public function subtractIncomeAmount(int $entryId, int $businessId, string $amount): bool
    {
        $builder = $this->builder();
        $builder
            ->set('income_amount', 'income_amount - ' . $this->db->escape($amount), false)
            ->set('updated_at', Time::now('UTC')->toDateTimeString())
            ->where('id', $entryId)
            ->where('business_id', $businessId)
            ->where('income_amount >=', $amount)
            ->update();

        return $this->db->affectedRows() === 1;
    }
}
