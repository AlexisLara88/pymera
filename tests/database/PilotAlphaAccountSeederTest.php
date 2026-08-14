<?php

declare(strict_types=1);

use App\Database\Seeds\PilotAlphaAccountSeeder;
use App\Domain\BusinessRoleCatalog;
use App\Models\BusinessProfileModel;
use CodeIgniter\Config\Services;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/** @internal */
final class PilotAlphaAccountSeederTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;

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

    public function testSeederCreatesAUsableAccountWithAnEmptyBusinessJourney(): void
    {
        $this->seed(PilotAlphaAccountSeeder::class);

        $user = auth()->getProvider()->findByCredentials([
            'email' => PilotAlphaAccountSeeder::PILOT_EMAIL,
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertTrue($user->active);
        $this->assertTrue($user->inGroup('alpha'));
        $this->assertTrue(service('passwords')->verify(
            PilotAlphaAccountSeeder::PILOT_PASSWORD,
            $user->password_hash,
        ));

        $business = $this->db
            ->table('businesses')
            ->where('name', PilotAlphaAccountSeeder::PILOT_BUSINESS)
            ->get()
            ->getRowArray();

        $this->assertNotNull($business);
        $this->seeInDatabase('business_users', [
            'user_id'     => $user->id,
            'business_id' => $business['id'],
            'role_code'   => BusinessRoleCatalog::OWNER,
            'status'      => 'active',
            'deleted_at'  => null,
        ]);
        $this->assertNull((new BusinessProfileModel())
            ->where('business_id', $business['id'])
            ->first());
        $this->dontSeeInDatabase('objectives', ['business_id' => $business['id']]);
        $this->dontSeeInDatabase('financial_daily_entries', ['business_id' => $business['id']]);

        $this->actingAs($user);
        $this->withSession($_SESSION)
            ->get('/app')
            ->assertRedirectTo('/app/mi-negocio#businessEditor');
    }

    public function testSeederIsIdempotentAndKeepsOneBusinessMembership(): void
    {
        $this->seed(PilotAlphaAccountSeeder::class);
        $this->seed(PilotAlphaAccountSeeder::class);

        $identity = $this->db
            ->table('auth_identities')
            ->where('type', 'email_password')
            ->where('secret', PilotAlphaAccountSeeder::PILOT_EMAIL)
            ->get()
            ->getRowArray();
        $business = $this->db
            ->table('businesses')
            ->where('name', PilotAlphaAccountSeeder::PILOT_BUSINESS)
            ->get()
            ->getRowArray();

        $this->assertNotNull($identity);
        $this->assertNotNull($business);
        $this->assertSame(1, $this->db
            ->table('business_users')
            ->where('user_id', $identity['user_id'])
            ->where('business_id', $business['id'])
            ->countAllResults());
    }
}
