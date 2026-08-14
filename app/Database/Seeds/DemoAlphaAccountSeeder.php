<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;
use RuntimeException;

/**
 * Creates or repairs the public validation account.
 *
 * These credentials are intentionally shareable test data, not a secret.
 */
class DemoAlphaAccountSeeder extends Seeder
{
    public const DEMO_EMAIL = 'dulce@demo.com';
    public const DEMO_PASSWORD = 'demo12345';

    private const LEGACY_EMAIL = 'demo.dulcebarrio@erp-lite.test';
    private const DEMO_USERNAME = 'demodulcebarrio';

    public function run(): void
    {
        $users = auth()->getProvider();
        $user = $users->findByCredentials(['email' => self::DEMO_EMAIL])
            ?? $users->findByCredentials(['email' => self::LEGACY_EMAIL]);
        $created = false;

        if (! $user instanceof User) {
            $user = new User([
                'username' => self::DEMO_USERNAME,
                'active'   => 1,
            ]);
            $created = true;
        }

        $passwordHash = service('passwords')->hash(self::DEMO_PASSWORD);

        if (! is_string($passwordHash) || $passwordHash === '') {
            throw new RuntimeException('No fue posible proteger la contraseña demostrativa.');
        }

        $user->email = self::DEMO_EMAIL;
        $user->password_hash = $passwordHash;
        $user->active = 1;

        if (! $users->save($user)) {
            throw new RuntimeException('No fue posible guardar la cuenta demostrativa.');
        }

        if ($created) {
            $savedUser = $users->findById($users->getInsertID());

            if (! $savedUser instanceof User) {
                throw new RuntimeException('No fue posible recuperar la cuenta demostrativa.');
            }

            $users->addToDefaultGroup($savedUser);
        }
    }
}
