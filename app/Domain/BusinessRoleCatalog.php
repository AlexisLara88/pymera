<?php

declare(strict_types=1);

namespace App\Domain;

final class BusinessRoleCatalog
{
    public const OWNER = 'owner';
    public const COACH = 'coach';
    public const COLLABORATOR = 'collaborator';

    /** @var array<string, string> */
    public const LABELS = [
        self::OWNER        => 'Propietario',
        self::COACH        => 'Coach',
        self::COLLABORATOR => 'Colaborador',
    ];

    public static function isValid(string $role): bool
    {
        return array_key_exists($role, self::LABELS);
    }
}
