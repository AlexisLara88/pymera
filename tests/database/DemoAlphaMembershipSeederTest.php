<?php

declare(strict_types=1);

use App\Database\Seeds\DemoAlphaMembershipSeeder;
use App\Database\Seeds\DemoAlphaAccountSeeder;
use App\Database\Seeds\DemoAlphaSeeder;
use App\Domain\BusinessRoleCatalog;
use App\Models\BusinessModel;
use App\Models\BusinessUserModel;
use CodeIgniter\Config\Services;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class DemoAlphaMembershipSeederTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = [
        'CodeIgniter\Settings',
        'CodeIgniter\Shield',
        'App',
    ];
    protected $seed = DemoAlphaSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        Services::resetSingle('auth');
    }

    public function testMembershipSeederRequiresThePreExistingDemoAccount(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('La cuenta demostrativa todavía no existe.');

        $this->seed(DemoAlphaMembershipSeeder::class);
    }

    public function testMembershipSeederCreatesOneRepairableAssociation(): void
    {
        $this->seed(DemoAlphaAccountSeeder::class);
        $user = auth()->getProvider()->findByCredentials([
            'email' => DemoAlphaAccountSeeder::DEMO_EMAIL,
        ]);
        $this->assertInstanceOf(User::class, $user);
        $business = (new BusinessModel())->where('name', 'Dulce Barrio')->first();

        $this->seed(DemoAlphaMembershipSeeder::class);
        $this->seed(DemoAlphaMembershipSeeder::class);

        $memberships = (new BusinessUserModel())
            ->where('user_id', $user->id)
            ->findAll();

        $this->assertCount(1, $memberships);
        $this->assertSame((int) $business['id'], (int) $memberships[0]['business_id']);
        $this->assertSame(BusinessRoleCatalog::OWNER, $memberships[0]['role_code']);
        $this->assertSame('active', $memberships[0]['status']);
    }
}
