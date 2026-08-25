<?php

declare(strict_types=1);

use App\Domain\BusinessRoleCatalog;
use App\Models\BusinessModel;
use App\Services\AccountProvisioningService;
use CodeIgniter\Config\Services;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/** @internal */
final class AccountProvisioningTest extends CIUnitTestCase
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

    public function testOwnerAccountBusinessAndMembershipAreCreatedTogether(): void
    {
        $result = (new AccountProvisioningService())->createOwnerWithBusiness(
            'owner@example.test',
            'owner-test',
            'Safe-Test-Password-42!',
            'Negocio propietario',
            'USD',
            'America/Guayaquil',
        );

        $user = auth()->getProvider()->findById($result['user_id']);

        $this->assertInstanceOf(User::class, $user);
        $this->assertTrue($user->active);
        $this->assertTrue($user->inGroup('alpha'));
        $this->assertFalse($user->inGroup('platform_admin'));
        $this->seeInDatabase('businesses', [
            'id'     => $result['business_id'],
            'name'   => 'Negocio propietario',
            'status' => 'active',
        ]);
        $this->seeInDatabase('business_users', [
            'id'          => $result['membership_id'],
            'user_id'     => $result['user_id'],
            'business_id' => $result['business_id'],
            'role_code'   => BusinessRoleCatalog::OWNER,
            'status'      => 'active',
        ]);
        $this->assertSame(3, $this->db->table('platform_audit_events')->countAllResults());
    }

    public function testPlatformAdministratorIsCreatedWithoutBusinessAccess(): void
    {
        $userId = (new AccountProvisioningService())->createPlatformAdministrator(
            'admin@example.test',
            'admin-test',
            'Safe-Test-Password-42!',
        );

        $user = auth()->getProvider()->findById($userId);

        $this->assertInstanceOf(User::class, $user);
        $this->assertTrue($user->inGroup('platform_admin'));
        $this->assertFalse($user->inGroup('alpha'));
        $this->dontSeeInDatabase('business_users', ['user_id' => $userId]);
        $this->seeInDatabase('platform_audit_events', [
            'subject_type' => 'user',
            'subject_id'   => $userId,
            'action'       => 'created',
        ]);
    }

    public function testOwnerCanBeCreatedForAnExistingActiveBusinessWithoutDuplicatingIt(): void
    {
        $businessId = (new BusinessModel())->insert([
            'name'          => 'Negocio compartido',
            'currency_code' => 'USD',
            'timezone'      => 'America/Guayaquil',
            'status'        => 'active',
        ], true);
        $this->assertIsInt($businessId);
        $businessCount = $this->db->table('businesses')->countAllResults();

        $result = (new AccountProvisioningService())->createOwnerForBusiness(
            'shared-owner@example.test',
            'shared-owner',
            'Safe-Test-Password-42!',
            $businessId,
        );

        $this->assertSame($businessId, $result['business_id']);
        $this->assertSame($businessCount, $this->db->table('businesses')->countAllResults());
        $this->seeInDatabase('business_users', [
            'user_id'     => $result['user_id'],
            'business_id' => $businessId,
            'role_code'   => BusinessRoleCatalog::OWNER,
            'status'      => 'active',
        ]);
        $this->assertSame(2, $this->db->table('platform_audit_events')->countAllResults());
        $this->dontSeeInDatabase('platform_audit_events', [
            'subject_type' => 'business',
            'action'       => 'created',
        ]);
    }

    public function testInactiveBusinessRejectsNewOwnerWithoutLeavingPartialAccount(): void
    {
        $businessId = (new BusinessModel())->insert([
            'name'          => 'Negocio pausado',
            'currency_code' => 'USD',
            'timezone'      => 'America/Guayaquil',
            'status'        => 'inactive',
        ], true);
        $this->assertIsInt($businessId);

        try {
            (new AccountProvisioningService())->createOwnerForBusiness(
                'inactive-owner@example.test',
                'inactive-owner',
                'Safe-Test-Password-42!',
                $businessId,
            );
            $this->fail('An inactive business should reject a new owner.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'El negocio seleccionado no existe o no está activo.',
                $exception->getMessage(),
            );
        }

        $this->assertNull(auth()->getProvider()->findByCredentials([
            'email' => 'inactive-owner@example.test',
        ]));
        $this->dontSeeInDatabase('business_users', ['business_id' => $businessId]);
        $this->assertSame(0, $this->db->table('platform_audit_events')->countAllResults());
    }

    public function testSeveralOwnersCanShareOneBusinessWithoutGivingAnyAccountTwoActiveBusinesses(): void
    {
        $businessId = (new BusinessModel())->insert([
            'name'          => 'Negocio con varios propietarios',
            'currency_code' => 'USD',
            'timezone'      => 'America/Guayaquil',
            'status'        => 'active',
        ], true);
        $this->assertIsInt($businessId);
        $service = new AccountProvisioningService();

        $first = $service->createOwnerForBusiness(
            'first-owner@example.test',
            'first-owner',
            'Safe-Test-Password-42!',
            $businessId,
        );
        $second = $service->createOwnerForBusiness(
            'second-owner@example.test',
            'second-owner',
            'Safe-Test-Password-43!',
            $businessId,
        );

        $this->assertNotSame($first['user_id'], $second['user_id']);
        $this->assertSame(2, $this->db->table('business_users')
            ->where('business_id', $businessId)
            ->where('status', 'active')
            ->countAllResults());
        $this->assertSame(1, $this->db->table('business_users')
            ->where('user_id', $first['user_id'])
            ->where('status', 'active')
            ->countAllResults());
        $this->assertSame(1, $this->db->table('business_users')
            ->where('user_id', $second['user_id'])
            ->where('status', 'active')
            ->countAllResults());
    }

    public function testDuplicateIdentityDoesNotCreatePartialBusinessData(): void
    {
        $service = new AccountProvisioningService();
        $service->createOwnerWithBusiness(
            'duplicate@example.test',
            'duplicate-test',
            'Safe-Test-Password-42!',
            'Primer negocio',
        );

        $businessCount = $this->db->table('businesses')->countAllResults();
        $membershipCount = $this->db->table('business_users')->countAllResults();

        try {
            $service->createOwnerWithBusiness(
                'duplicate@example.test',
                'another-user',
                'Another-Test-Password-42!',
                'Negocio que no debe existir',
            );
            $this->fail('A duplicate email should be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'El correo o nombre de usuario ya está registrado.',
                $exception->getMessage(),
            );
        }

        $this->assertSame($businessCount, $this->db->table('businesses')->countAllResults());
        $this->assertSame($membershipCount, $this->db->table('business_users')->countAllResults());
        $this->dontSeeInDatabase('businesses', ['name' => 'Negocio que no debe existir']);
    }

    public function testGlobalAndContextualDeactivationRemainIndependent(): void
    {
        $service = new AccountProvisioningService();
        $result = $service->createOwnerWithBusiness(
            'status@example.test',
            'status-test',
            'Safe-Test-Password-42!',
            'Negocio con estados',
        );

        $service->setMembershipStatus($result['membership_id'], 'inactive');
        $this->seeInDatabase('business_users', [
            'id'     => $result['membership_id'],
            'status' => 'inactive',
        ]);
        $this->assertTrue(auth()->getProvider()->findById($result['user_id'])->active);

        $service->setUserActive($result['user_id'], false);
        $this->assertFalse(auth()->getProvider()->findById($result['user_id'])->active);
        $this->seeInDatabase('platform_audit_events', [
            'subject_type' => 'business_membership',
            'subject_id'   => $result['membership_id'],
            'action'       => 'membership_deactivated',
        ]);
        $this->seeInDatabase('platform_audit_events', [
            'subject_type' => 'user',
            'subject_id'   => $result['user_id'],
            'action'       => 'deactivated',
        ]);
    }
}
