<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class UserPreferenceModel extends Model
{
    protected $table         = 'user_preferences';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'user_id',
        'appearance_theme',
        'crm_view_mode',
    ];
    protected $validationRules = [
        'user_id'          => 'required|is_natural_no_zero',
        'appearance_theme' => 'required|in_list[light,dark]',
        'crm_view_mode'     => 'required|in_list[combined,tabs]',
    ];

    /** @return array<string, mixed>|null */
    public function preferencesForUser(int $userId): ?array
    {
        return $this->where('user_id', $userId)->first();
    }

    public function saveForUser(int $userId, string $theme, string $crmView): bool
    {
        $preference = $this->preferencesForUser($userId);
        $values = [
            'appearance_theme' => $theme,
            'crm_view_mode'    => $crmView,
        ];

        if ($preference === null) {
            return $this->insert([
                'user_id'  => $userId,
                ...$values,
            ], false) !== false;
        }

        return $this->update((int) $preference['id'], $values);
    }
}
