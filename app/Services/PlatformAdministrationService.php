<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\PlatformAccessException;
use App\Models\BusinessModel;
use App\Models\BusinessUserModel;
use App\Models\PlatformAuditEventModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Shield\Entities\User;

final class PlatformAdministrationService
{
    private const WEB_ADMINISTRATOR_CREATION_ENABLED = false;

    public function __construct(
        private ?BaseConnection $database = null,
        private ?AccountProvisioningService $provisioning = null,
        private ?BusinessUserModel $memberships = null,
        private ?BusinessModel $businesses = null,
        private ?PlatformAuditEventModel $auditEvents = null,
    ) {
        $this->database     ??= db_connect();
        $this->provisioning ??= new AccountProvisioningService($this->database);
        $this->memberships  ??= new BusinessUserModel($this->database);
        $this->businesses   ??= new BusinessModel($this->database);
        $this->auditEvents  ??= new PlatformAuditEventModel($this->database);
    }

    /**
     * @return array{
     *   accounts: list<array<string, mixed>>,
     *   businesses: list<array<string, mixed>>,
     *   audit_events: list<array<string, mixed>>,
     *   administrator_creation_enabled: bool
     * }
     */
    public function overview(): array
    {
        $this->require('platform.accounts.view');
        $this->require('platform.businesses.view');
        $this->require('platform.audit.view');

        $users = auth()->getProvider()
            ->withIdentities()
            ->withGroups()
            ->orderBy('id', 'ASC')
            ->findAll();
        $membershipRows = $this->memberships->administrativeOverview();
        $membershipsByUser = [];

        foreach ($membershipRows as $membership) {
            $membershipsByUser[(int) $membership['user_id']][] = $membership;
        }

        $accounts = [];

        foreach ($users as $user) {
            if (! $user instanceof User) {
                continue;
            }

            $accounts[] = [
                'id'          => (int) $user->id,
                'username'    => (string) $user->username,
                'email'       => (string) $user->email,
                'active'      => (bool) $user->active,
                'groups'      => $user->getGroups(),
                'memberships' => $membershipsByUser[(int) $user->id] ?? [],
            ];
        }

        return [
            'accounts'     => $accounts,
            'businesses'   => $this->businesses->administrativeOverview(),
            'audit_events' => $this->auditEvents->recentWithActor(),
            'administrator_creation_enabled' => self::WEB_ADMINISTRATOR_CREATION_ENABLED,
        ];
    }

    /** @param array<string, mixed> $input */
    public function createOwner(array $input): array
    {
        $actorId = $this->require('platform.accounts.create');
        $this->require('platform.businesses.create');
        $this->require('platform.memberships.manage');

        return $this->provisioning->createOwnerWithBusiness(
            (string) ($input['email'] ?? ''),
            (string) ($input['username'] ?? ''),
            (string) ($input['password'] ?? ''),
            (string) ($input['business_name'] ?? ''),
            (string) ($input['currency_code'] ?? 'USD'),
            (string) ($input['timezone'] ?? 'America/Guayaquil'),
            $actorId,
        );
    }

    /** @param array<string, mixed> $input */
    public function createAdministrator(array $input): int
    {
        if (! self::WEB_ADMINISTRATOR_CREATION_ENABLED) {
            throw PlatformAccessException::administratorCreationDisabled();
        }

        $actorId = $this->require('platform.accounts.create');

        return $this->provisioning->createPlatformAdministrator(
            (string) ($input['email'] ?? ''),
            (string) ($input['username'] ?? ''),
            (string) ($input['password'] ?? ''),
            $actorId,
        );
    }

    public function setUserActive(int $userId, bool $active): void
    {
        $actorId = $this->require('platform.accounts.disable');

        if (! $active && $actorId === $userId) {
            throw PlatformAccessException::selfDeactivation();
        }

        $target = auth()->getProvider()->findById($userId);

        if (! $active && $target instanceof User && $target->inGroup('platform_admin')) {
            throw PlatformAccessException::protectedAdministrator();
        }

        $this->provisioning->setUserActive($userId, $active, $actorId);
    }

    public function setMembershipStatus(int $membershipId, string $status): void
    {
        $actorId = $this->require('platform.memberships.manage');
        $this->provisioning->setMembershipStatus($membershipId, $status, $actorId);
    }

    private function require(string $permission): int
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->can($permission)) {
            throw PlatformAccessException::denied();
        }

        return (int) $user->id;
    }
}
