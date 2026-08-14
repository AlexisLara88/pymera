<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BusinessModel;
use App\Models\BusinessUserModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Shield\Entities\User;
use RuntimeException;
use Throwable;

/**
 * Creates a usable alpha account together with its initial business context.
 *
 * The business is intentionally structural only: profile, objectives, financial
 * entries and other operational records are left empty for the guided journey.
 */
final class PilotAccountProvisioner
{
    public function __construct(private ?BaseConnection $database = null)
    {
        $this->database ??= db_connect();
    }

    /**
     * @return array{user_id: int, business_id: int}
     */
    public function provision(
        string $email,
        string $username,
        string $password,
        string $businessName,
        string $currencyCode = 'USD',
        string $timezone = 'America/Guayaquil',
    ): array {
        $email        = strtolower(trim($email));
        $username     = trim($username);
        $businessName = trim($businessName);
        $currencyCode = strtoupper(trim($currencyCode));
        $timezone     = trim($timezone);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('El correo de la cuenta piloto no es válido.');
        }

        if ($username === '' || $password === '' || $businessName === '') {
            throw new RuntimeException('La cuenta piloto requiere usuario, contraseña y negocio.');
        }

        if (preg_match('/^[A-Z]{3}$/', $currencyCode) !== 1) {
            throw new RuntimeException('La moneda de la cuenta piloto no es válida.');
        }

        if (! in_array($timezone, timezone_identifiers_list(), true)) {
            throw new RuntimeException('La zona horaria de la cuenta piloto no es válida.');
        }

        $this->database->transException(true);

        try {
            if (! $this->database->transBegin()) {
                throw new RuntimeException('No fue posible iniciar la creación de la cuenta piloto.');
            }

            $userId     = $this->upsertUser($email, $username, $password);
            $businessId = $this->upsertBusiness($businessName, $currencyCode, $timezone);

            $this->ensureSingleMembership($userId, $businessId);

            if (! $this->database->transCommit()) {
                throw new RuntimeException('No fue posible confirmar la cuenta piloto.');
            }

            return [
                'user_id'     => $userId,
                'business_id' => $businessId,
            ];
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    private function upsertUser(string $email, string $username, string $password): int
    {
        $users = auth()->getProvider();
        $user  = $users->findByCredentials(['email' => $email]);

        if (! $user instanceof User) {
            $user = new User([
                'username' => $username,
                'active'   => 1,
            ]);
        }

        $passwordHash = service('passwords')->hash($password);

        if (! is_string($passwordHash) || $passwordHash === '') {
            throw new RuntimeException('No fue posible proteger la contraseña piloto.');
        }

        $user->username      = $username;
        $user->email         = $email;
        $user->password_hash = $passwordHash;
        $user->active        = 1;

        if (! $users->save($user)) {
            throw new RuntimeException('No fue posible guardar la cuenta piloto.');
        }

        $savedUser = $users->findByCredentials(['email' => $email]);

        if (! $savedUser instanceof User) {
            throw new RuntimeException('No fue posible recuperar la cuenta piloto.');
        }

        if (! $savedUser->inGroup('alpha')) {
            $users->addToDefaultGroup($savedUser);
        }

        return (int) $savedUser->id;
    }

    private function upsertBusiness(string $name, string $currencyCode, string $timezone): int
    {
        $businesses = new BusinessModel();
        $business   = $businesses
            ->withDeleted()
            ->where('name', $name)
            ->first();
        $payload = [
            'name'          => $name,
            'currency_code' => $currencyCode,
            'timezone'      => $timezone,
            'status'        => 'active',
            'deleted_at'    => null,
        ];

        if ($business === null) {
            $businessId = $businesses->insert($payload, true);
        } else {
            $businessId = (int) $business['id'];
            $saved      = $businesses->update($businessId, $payload);

            if (! $saved) {
                $businessId = false;
            }
        }

        if ($businessId === false) {
            throw new RuntimeException('No fue posible guardar el negocio piloto.');
        }

        return (int) $businessId;
    }

    private function ensureSingleMembership(int $userId, int $businessId): void
    {
        $memberships = new BusinessUserModel($this->database);

        foreach ($memberships->activeForUser($userId) as $membership) {
            if ((int) $membership['business_id'] !== $businessId) {
                throw new RuntimeException(
                    'La cuenta piloto ya está asociada a otro negocio activo.',
                );
            }
        }

        if (! $memberships->activateOwnerMembership($userId, $businessId)) {
            throw new RuntimeException('No fue posible asociar la cuenta con su negocio piloto.');
        }
    }
}
