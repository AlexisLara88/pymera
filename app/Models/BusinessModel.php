<?php

namespace App\Models;

use CodeIgniter\Model;

class BusinessModel extends Model
{
    protected $table            = 'businesses';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'name',
        'currency_code',
        'timezone',
        'status',
    ];
    protected $validationRules  = [
        'name'          => 'required|max_length[160]',
        'currency_code' => 'required|exact_length[3]|alpha',
        'timezone'      => 'required|max_length[64]',
        'status'        => 'required|in_list[active,inactive]',
    ];
    protected $validationMessages = [];

    /** @return array<string, mixed>|null */
    public function activeById(int $businessId): ?array
    {
        if ($businessId < 1) {
            return null;
        }

        return $this->where('id', $businessId)
            ->where('status', 'active')
            ->first();
    }

    /** @return list<array<string, mixed>> */
    public function activeOptions(): array
    {
        return $this->select('id, name, currency_code')
            ->where('status', 'active')
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    /** @return list<array<string, mixed>> */
    public function administrativeOverview(): array
    {
        return $this->select(
            'businesses.id, businesses.name, businesses.currency_code, businesses.timezone, '
            . 'businesses.status, COUNT(membership.id) AS member_count',
        )
            ->join(
                'business_users membership',
                'membership.business_id = businesses.id AND membership.deleted_at IS NULL',
                'left',
            )
            ->groupBy(
                'businesses.id, businesses.name, businesses.currency_code, businesses.timezone, businesses.status',
            )
            ->orderBy('businesses.name', 'ASC')
            ->findAll();
    }
}
