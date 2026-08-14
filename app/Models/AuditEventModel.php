<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class AuditEventModel extends Model
{
    protected $table      = 'audit_events';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'business_id',
        'user_id',
        'entity_type',
        'entity_id',
        'action',
        'occurred_at',
    ];
    protected $validationRules = [
        'business_id' => 'required|is_natural_no_zero',
        'user_id'     => 'required|is_natural_no_zero',
        'entity_type' => 'required|in_list[business,business_profile,objective,activity,financial_daily_entry,contact,opportunity,crm_financial_posting]',
        'entity_id'   => 'required|is_natural_no_zero',
        'action'      => 'required|in_list[created,updated,deleted,status_changed,recorded,reversed]',
        'occurred_at' => 'required|valid_date[Y-m-d H:i:s]',
    ];
}
