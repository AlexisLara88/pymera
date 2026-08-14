<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;

class BusinessUserModel extends Model
{
    protected $table          = 'business_users';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'user_id',
        'business_id',
        'role_code',
        'status',
    ];
    protected $validationRules = [
        'user_id'     => 'required|is_natural_no_zero',
        'business_id' => 'required|is_natural_no_zero',
        'role_code'   => 'required|in_list[owner,coach,collaborator]',
        'status'      => 'required|in_list[active,inactive]',
    ];

    /** @return list<array<string, mixed>> */
    public function activeForUser(int $userId): array
    {
        return $this->where('user_id', $userId)
            ->where('status', 'active')
            ->findAll();
    }

    /** @return list<array<string, mixed>> */
    public function administrativeOverview(): array
    {
        return $this->select(
            'business_users.id, business_users.user_id, business_users.business_id, '
            . 'business_users.role_code, business_users.status, businesses.name AS business_name',
        )
            ->join('businesses', 'businesses.id = business_users.business_id')
            ->orderBy('businesses.name', 'ASC')
            ->findAll();
    }

    public function activateOwnerMembership(int $userId, int $businessId): bool
    {
        $existing = $this->withDeleted()
            ->where('user_id', $userId)
            ->where('business_id', $businessId)
            ->first();
        $now = Time::now('UTC')->toDateTimeString();
        $payload = [
            'user_id'     => $userId,
            'business_id' => $businessId,
            'role_code'   => 'owner',
            'status'      => 'active',
        ];

        if ($existing === null) {
            return $this->insert($payload, false) !== false;
        }

        return $this->builder()
            ->where('id', $existing['id'])
            ->update([
                ...$payload,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);
    }
}
