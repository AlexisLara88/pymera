<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class OpportunityModel extends Model
{
    protected $table          = 'opportunities';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'business_id',
        'contact_id',
        'need',
        'status',
        'estimated_value',
        'next_follow_up_date',
        'notes',
    ];
    protected $validationRules = [
        'business_id'         => 'required|is_natural_no_zero',
        'contact_id'          => 'required|is_natural_no_zero',
        'need'                => 'required|max_length[180]',
        'status'              => 'required|in_list[new,contacted,proposal_sent,negotiation,won,lost]',
        'estimated_value'     => 'permit_empty|decimal|greater_than_equal_to[0]',
        'next_follow_up_date' => 'permit_empty|valid_date[Y-m-d]',
        'notes'               => 'permit_empty|max_length[2000]',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function findForBusiness(int $businessId): array
    {
        return $this
            ->where('business_id', $businessId)
            ->orderBy('next_follow_up_date', 'ASC')
            ->orderBy('updated_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findForContact(int $contactId, int $businessId): array
    {
        return $this
            ->where('contact_id', $contactId)
            ->where('business_id', $businessId)
            ->orderBy('updated_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOwned(int $opportunityId, int $businessId): ?array
    {
        return $this
            ->where('id', $opportunityId)
            ->where('business_id', $businessId)
            ->first();
    }
}
