<?php

namespace App\Models;

use CodeIgniter\Model;

class ObjectiveModel extends Model
{
    protected $table          = 'objectives';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'business_id',
        'title',
        'description',
        'category',
        'status',
        'start_date',
        'target_date',
        'completed_at',
    ];
    protected $validationRules = [
        'business_id' => 'required|is_natural_no_zero',
        'title'       => 'required|max_length[180]',
        'category'    => 'permit_empty|in_list[commercial,financial,operational,improvement]',
        'status'      => 'required|in_list[draft,active,completed,paused]',
        'start_date'  => 'permit_empty|valid_date[Y-m-d]',
        'target_date' => 'permit_empty|valid_date[Y-m-d]',
        'completed_at' => 'permit_empty|valid_date[Y-m-d H:i:s]',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function findForBusiness(int $businessId): array
    {
        return $this
            ->where('business_id', $businessId)
            ->orderBy('updated_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOwned(int $objectiveId, int $businessId): ?array
    {
        return $this
            ->where('id', $objectiveId)
            ->where('business_id', $businessId)
            ->first();
    }
}
