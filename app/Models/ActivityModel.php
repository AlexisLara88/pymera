<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityModel extends Model
{
    protected $table          = 'activities';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'objective_id',
        'title',
        'description',
        'status',
        'is_urgent',
        'is_important',
        'due_date',
        'completed_at',
    ];
    protected $validationRules = [
        'objective_id' => 'required|is_natural_no_zero',
        'title'        => 'required|max_length[180]',
        'status'       => 'required|in_list[pending,in_progress,completed,cancelled]',
        'is_urgent'    => 'in_list[0,1]',
        'is_important' => 'in_list[0,1]',
        'due_date'     => 'permit_empty|valid_date[Y-m-d]',
        'completed_at' => 'permit_empty|valid_date[Y-m-d H:i:s]',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function findForBusiness(int $businessId): array
    {
        return $this
            ->select('activities.*')
            ->join('objectives', 'objectives.id = activities.objective_id')
            ->where('objectives.business_id', $businessId)
            ->where('objectives.deleted_at', null)
            ->orderBy('activities.due_date', 'ASC')
            ->orderBy('activities.id', 'ASC')
            ->findAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOwned(int $activityId, int $businessId): ?array
    {
        return $this
            ->select('activities.*')
            ->join('objectives', 'objectives.id = activities.objective_id')
            ->where('activities.id', $activityId)
            ->where('objectives.business_id', $businessId)
            ->where('objectives.deleted_at', null)
            ->first();
    }

    public function belongsToBusiness(int $activityId, int $businessId): bool
    {
        return $this->findOwned($activityId, $businessId) !== null;
    }
}
