<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class ContactModel extends Model
{
    protected $table          = 'contacts';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'business_id',
        'display_name',
        'contact_kind',
        'lifecycle_stage',
        'acquisition_channel',
        'email',
        'phone',
        'notes',
    ];
    protected $validationRules = [
        'business_id'         => 'required|is_natural_no_zero',
        'display_name'        => 'required|max_length[160]',
        'contact_kind'        => 'required|in_list[person,organization]',
        'lifecycle_stage'     => 'required|in_list[prospect,client]',
        'acquisition_channel' => 'permit_empty|in_list[instagram,whatsapp,referral,local_search,direct,other]',
        'email'               => 'permit_empty|valid_email|max_length[254]',
        'phone'               => 'permit_empty|max_length[40]',
        'notes'               => 'permit_empty|max_length[2000]',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function findForBusiness(int $businessId): array
    {
        return $this
            ->where('business_id', $businessId)
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    /**
     * Includes archived contacts so historical opportunities retain their
     * commercial reference without returning those contacts as active.
     *
     * @return list<array<string, mixed>>
     */
    public function findReferencesForBusiness(int $businessId): array
    {
        return $this
            ->withDeleted()
            ->where('business_id', $businessId)
            ->findAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOwned(int $contactId, int $businessId): ?array
    {
        return $this
            ->where('id', $contactId)
            ->where('business_id', $businessId)
            ->first();
    }

    /**
     * Includes an archived contact when it is referenced by historical data.
     *
     * @return array<string, mixed>|null
     */
    public function findOwnedReference(int $contactId, int $businessId): ?array
    {
        return $this
            ->withDeleted()
            ->where('id', $contactId)
            ->where('business_id', $businessId)
            ->first();
    }
}
