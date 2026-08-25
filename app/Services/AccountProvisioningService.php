<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\BusinessRoleCatalog;
use App\Models\BusinessModel;
use App\Models\BusinessUserModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Shield\Entities\User;
use RuntimeException;
use Throwable;

final class AccountProvisioningService
{
    public function __construct(
        private ?BaseConnection $database = null,
        private ?PlatformAuditRecorder $audit = null,
    ) {
        $this->database ??= db_connect();
        $this->audit    ??= new PlatformAuditRecorder();
    }

    /**
     * @return array{user_id: int, business_id: int, membership_id: int}
     */
    public function createOwnerWithBusiness(
        string $email,
        string $username,
        string $password,
        string $businessName,
        string $currencyCode = 'USD',
        string $timezone = 'America/Guayaquil',
        ?int $actorUserId = null,
    ): array {
        [$email, $username, $password] = $this->validatedIdentity($email, $username, $password);
        [$businessName, $currencyCode, $timezone] = $this->validatedBusiness(
            $businessName,
            $currencyCode,
            $timezone,
        );

        return $this->transaction(function () use (
            $email,
            $username,
            $password,
            $businessName,
            $currencyCode,
            $timezone,
            $actorUserId,
        ): array {
            $userId = $this->createUser($email, $username, $password, 'alpha');
            $businessId = $this->createBusiness($businessName, $currencyCode, $timezone);
            $membershipId = $this->createMembership(
                $userId,
                $businessId,
                BusinessRoleCatalog::OWNER,
            );

            $this->audit->record('user', $userId, 'created', $actorUserId);
            $this->audit->record('business', $businessId, 'created', $actorUserId);
            $this->audit->record(
                'business_membership',
                $membershipId,
                'membership_created',
                $actorUserId,
            );

            return [
                'user_id'       => $userId,
                'business_id'   => $businessId,
                'membership_id' => $membershipId,
            ];
        });
    }

    /**
     * @return array{user_id: int, business_id: int, membership_id: int}
     */
    public function createOwnerForBusiness(
        string $email,
        string $username,
        string $password,
        int $businessId,
        ?int $actorUserId = null,
    ): array {
        [$email, $username, $password] = $this->validatedIdentity($email, $username, $password);

        if ($businessId < 1) {
            throw new RuntimeException('Seleccioná un negocio activo válido.');
        }

        return $this->transaction(function () use (
            $email,
            $username,
            $password,
            $businessId,
            $actorUserId,
        ): array {
            $business = model(BusinessModel::class)->activeById($businessId);

            if ($business === null) {
                throw new RuntimeException('El negocio seleccionado no existe o no está activo.');
            }

            $userId = $this->createUser($email, $username, $password, 'alpha');
            $membershipId = $this->createMembership(
                $userId,
                $businessId,
                BusinessRoleCatalog::OWNER,
            );

            $this->audit->record('user', $userId, 'created', $actorUserId);
            $this->audit->record(
                'business_membership',
                $membershipId,
                'membership_created',
                $actorUserId,
            );

            return [
                'user_id'       => $userId,
                'business_id'   => $businessId,
                'membership_id' => $membershipId,
            ];
        });
    }

    public function createPlatformAdministrator(
        string $email,
        string $username,
        string $password,
        ?int $actorUserId = null,
    ): int {
        [$email, $username, $password] = $this->validatedIdentity($email, $username, $password);

        return $this->transaction(function () use (
            $email,
            $username,
            $password,
            $actorUserId,
        ): int {
            $userId = $this->createUser($email, $username, $password, 'platform_admin');
            $this->audit->record('user', $userId, 'created', $actorUserId);

            return $userId;
        });
    }

    public function setUserActive(int $userId, bool $active, ?int $actorUserId = null): void
    {
        $this->transaction(function () use ($userId, $active, $actorUserId): void {
            $users = auth()->getProvider();
            $user  = $users->findById($userId);

            if (! $user instanceof User) {
                throw new RuntimeException('La cuenta indicada no existe.');
            }

            $user->active = $active ? 1 : 0;

            if (! $users->save($user)) {
                throw new RuntimeException('No fue posible actualizar el estado de la cuenta.');
            }

            $this->audit->record(
                'user',
                $userId,
                $active ? 'activated' : 'deactivated',
                $actorUserId,
            );
        });
    }

    public function setMembershipStatus(
        int $membershipId,
        string $status,
        ?int $actorUserId = null,
    ): void {
        if (! in_array($status, ['active', 'inactive'], true)) {
            throw new RuntimeException('El estado de la relación no es válido.');
        }

        $this->transaction(function () use ($membershipId, $status, $actorUserId): void {
            $memberships = model(BusinessUserModel::class);
            $membership  = $memberships->find($membershipId);

            if ($membership === null) {
                throw new RuntimeException('La relación indicada no existe.');
            }

            if (! $memberships->update($membershipId, ['status' => $status])) {
                throw new RuntimeException('No fue posible actualizar la relación con el negocio.');
            }

            $this->audit->record(
                'business_membership',
                $membershipId,
                $status === 'active' ? 'membership_activated' : 'membership_deactivated',
                $actorUserId,
            );
        });
    }

    /**
     * @return array{string, string, string}
     */
    private function validatedIdentity(string $email, string $username, string $password): array
    {
        $email    = strtolower(trim($email));
        $username = trim($username);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('El correo de la cuenta no es válido.');
        }

        if ($username === '' || $password === '') {
            throw new RuntimeException('La cuenta requiere usuario y contraseña.');
        }

        $users = auth()->getProvider();
        $duplicateEmail = $users->findByCredentials(['email' => $email]) instanceof User;
        $duplicateUsername = $users->where('username', $username)->first() instanceof User;

        if ($duplicateEmail || $duplicateUsername) {
            throw new RuntimeException('El correo o nombre de usuario ya está registrado.');
        }

        return [$email, $username, $password];
    }

    /**
     * @return array{string, string, string}
     */
    private function validatedBusiness(
        string $businessName,
        string $currencyCode,
        string $timezone,
    ): array {
        $businessName = trim($businessName);
        $currencyCode = strtoupper(trim($currencyCode));
        $timezone     = trim($timezone);

        if ($businessName === '') {
            throw new RuntimeException('El negocio requiere un nombre.');
        }

        if (preg_match('/^[A-Z]{3}$/', $currencyCode) !== 1) {
            throw new RuntimeException('La moneda del negocio no es válida.');
        }

        if (! in_array($timezone, timezone_identifiers_list(), true)) {
            throw new RuntimeException('La zona horaria del negocio no es válida.');
        }

        return [$businessName, $currencyCode, $timezone];
    }

    private function createUser(
        string $email,
        string $username,
        string $password,
        string $group,
    ): int {
        $users = auth()->getProvider();
        $user = new User([
            'username' => $username,
            'email'    => $email,
            'password' => $password,
            'active'   => 1,
        ]);

        if (! $users->save($user)) {
            throw new RuntimeException('No fue posible crear la cuenta.');
        }

        $savedUser = $users->findById($users->getInsertID());

        if (! $savedUser instanceof User) {
            throw new RuntimeException('No fue posible recuperar la cuenta creada.');
        }

        $savedUser->addGroup($group);

        return (int) $savedUser->id;
    }

    private function createBusiness(string $name, string $currencyCode, string $timezone): int
    {
        $businessId = model(BusinessModel::class)->insert([
            'name'          => $name,
            'currency_code' => $currencyCode,
            'timezone'      => $timezone,
            'status'        => 'active',
        ], true);

        if ($businessId === false) {
            throw new RuntimeException('No fue posible crear el negocio.');
        }

        return (int) $businessId;
    }

    private function createMembership(int $userId, int $businessId, string $roleCode): int
    {
        $membershipId = model(BusinessUserModel::class)->insert([
            'user_id'     => $userId,
            'business_id' => $businessId,
            'role_code'   => $roleCode,
            'status'      => 'active',
        ], true);

        if ($membershipId === false) {
            throw new RuntimeException('No fue posible asociar la cuenta con el negocio.');
        }

        return (int) $membershipId;
    }

    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    private function transaction(callable $operation): mixed
    {
        $this->database->transException(true);

        try {
            if (! $this->database->transBegin()) {
                throw new RuntimeException('No fue posible iniciar la operación administrativa.');
            }

            $result = $operation();

            if (! $this->database->transCommit()) {
                throw new RuntimeException('No fue posible confirmar la operación administrativa.');
            }

            return $result;
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }
}
