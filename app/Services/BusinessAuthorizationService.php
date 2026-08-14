<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\BusinessPermissionCatalog;
use App\Domain\BusinessRoleCatalog;
use App\Exceptions\BusinessAccessException;

final class BusinessAuthorizationService
{
    /** @var array<string, list<string>> */
    private const ROLE_PERMISSIONS = [
        BusinessRoleCatalog::OWNER => BusinessPermissionCatalog::ALL,
        BusinessRoleCatalog::COACH => [],
        BusinessRoleCatalog::COLLABORATOR => [],
    ];

    public function __construct(private ?AlphaBusinessContext $context = null)
    {
        $this->context ??= new AlphaBusinessContext();
    }

    public function can(string $permission): bool
    {
        if (! in_array($permission, BusinessPermissionCatalog::ALL, true)) {
            return false;
        }

        $role = $this->context->membership()['role_code'];

        return in_array($permission, self::ROLE_PERMISSIONS[$role] ?? [], true);
    }

    public function require(string $permission): void
    {
        if (! $this->can($permission)) {
            throw BusinessAccessException::missingPermission();
        }
    }
}
