<?php

declare(strict_types=1);

use App\Database\Seeds\DemoAlphaAccountSeeder;
use CodeIgniter\Config\Services;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class DemoAlphaAccountSeederTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = [
        'CodeIgniter\Settings',
        'CodeIgniter\Shield',
        'App',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Services::resetSingle('auth');
    }

    public function testSeederCreatesTheFixedValidationAccount(): void
    {
        $this->seed(DemoAlphaAccountSeeder::class);

        $user = auth()->getProvider()->findByCredentials([
            'email' => DemoAlphaAccountSeeder::DEMO_EMAIL,
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertTrue($user->active);
        $this->assertTrue(service('passwords')->verify(
            DemoAlphaAccountSeeder::DEMO_PASSWORD,
            $user->password_hash,
        ));
        $this->assertTrue($user->inGroup('alpha'));
    }

    public function testSeederRepairsTheLegacyIdentityWithoutDuplicatingTheUser(): void
    {
        $users = auth()->getProvider();
        $legacy = new User([
            'username' => 'demodulcebarrio',
            'email'    => 'demo.dulcebarrio@erp-lite.test',
            'password' => 'Safe-Test-Password-42!',
            'active'   => 1,
        ]);
        $this->assertTrue($users->save($legacy));
        $legacyId = (int) $users->getInsertID();

        $this->seed(DemoAlphaAccountSeeder::class);
        $this->seed(DemoAlphaAccountSeeder::class);

        $updated = $users->findByCredentials([
            'email' => DemoAlphaAccountSeeder::DEMO_EMAIL,
        ]);

        $this->assertInstanceOf(User::class, $updated);
        $this->assertSame($legacyId, (int) $updated->id);
        $this->assertSame(1, $users->countAllResults());
        $this->assertNull($users->findByCredentials([
            'email' => 'demo.dulcebarrio@erp-lite.test',
        ]));
        $this->assertTrue(service('passwords')->verify(
            DemoAlphaAccountSeeder::DEMO_PASSWORD,
            $updated->password_hash,
        ));
    }
}
