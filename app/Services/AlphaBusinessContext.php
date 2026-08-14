<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\BusinessRoleCatalog;
use App\Exceptions\BusinessAccessException;
use App\Models\BusinessModel;
use App\Models\BusinessUserModel;

/**
 * Resolves authorization context exclusively from the authenticated account.
 *
 * During the alpha a person must have exactly one active business. The table
 * itself permits future multi-business membership, but ambiguity is rejected
 * until an explicit and server-validated selector exists.
 */
final class AlphaBusinessContext
{
    public function __construct(
        private ?BusinessUserModel $memberships = null,
        private ?BusinessModel $businesses = null,
    ) {
        $this->memberships ??= model(BusinessUserModel::class);
        $this->businesses  ??= model(BusinessModel::class);
    }

    public function actorId(): int
    {
        $userId = auth()->id();

        if (! is_int($userId) && ! (is_string($userId) && ctype_digit($userId))) {
            throw BusinessAccessException::unauthenticated();
        }

        $userId = (int) $userId;

        if ($userId < 1) {
            throw BusinessAccessException::unauthenticated();
        }

        return $userId;
    }

    public function businessId(): int
    {
        return $this->membership()['business_id'];
    }

    /**
     * @return array{id: int, user_id: int, business_id: int, role_code: string, status: string}
     */
    public function membership(): array
    {
        $memberships = $this->memberships
            ->where('user_id', $this->actorId())
            ->where('status', 'active')
            ->findAll(2);

        if ($memberships === []) {
            throw BusinessAccessException::missingMembership();
        }

        if (count($memberships) !== 1) {
            throw BusinessAccessException::ambiguousMembership();
        }

        $businessId = (int) $memberships[0]['business_id'];
        $business   = $this->businesses
            ->where('id', $businessId)
            ->where('status', 'active')
            ->first();

        if ($business === null) {
            throw BusinessAccessException::unavailableBusiness();
        }

        $roleCode = (string) ($memberships[0]['role_code'] ?? '');

        if (! BusinessRoleCatalog::isValid($roleCode)) {
            throw BusinessAccessException::invalidRole();
        }

        return [
            'id'          => (int) $memberships[0]['id'],
            'user_id'     => (int) $memberships[0]['user_id'],
            'business_id' => $businessId,
            'role_code'   => $roleCode,
            'status'      => (string) $memberships[0]['status'],
        ];
    }

}
