<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class PlatformAuditEventModel extends Model
{
    protected $table         = 'platform_audit_events';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'actor_user_id',
        'subject_type',
        'subject_id',
        'action',
        'occurred_at',
    ];
    protected $validationRules = [
        'actor_user_id' => 'permit_empty|is_natural_no_zero',
        'subject_type'  => 'required|max_length[40]|in_list[user,business,business_membership]',
        'subject_id'    => 'required|is_natural_no_zero',
        'action'        => 'required|max_length[40]|in_list[created,activated,deactivated,membership_created,membership_activated,membership_deactivated,role_changed]',
        'occurred_at'   => 'required|valid_date[Y-m-d H:i:s]',
    ];

    /** @return list<array<string, mixed>> */
    public function recentWithActor(int $limit = 30): array
    {
        return $this->select(
            'platform_audit_events.id, platform_audit_events.subject_type, '
            . 'platform_audit_events.subject_id, platform_audit_events.action, '
            . 'platform_audit_events.occurred_at, users.username AS actor_username',
        )
            ->join('users', 'users.id = platform_audit_events.actor_user_id', 'left')
            ->orderBy('platform_audit_events.id', 'DESC')
            ->findAll($limit);
    }
}
