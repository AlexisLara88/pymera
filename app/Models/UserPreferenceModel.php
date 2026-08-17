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
    ];
    protected $validationRules = [
        'user_id'          => 'required|is_natural_no_zero',
        'appearance_theme' => 'required|in_list[light,dark]',
    ];

    public function themeForUser(int $userId): ?string
    {
        $preference = $this->where('user_id', $userId)->first();

        return $preference === null
            ? null
            : (string) $preference['appearance_theme'];
    }

    public function saveThemeForUser(int $userId, string $theme): bool
    {
        $preference = $this->where('user_id', $userId)->first();

        if ($preference === null) {
            return $this->insert([
                'user_id'          => $userId,
                'appearance_theme' => $theme,
            ], false) !== false;
        }

        return $this->update((int) $preference['id'], [
            'appearance_theme' => $theme,
        ]);
    }
}
