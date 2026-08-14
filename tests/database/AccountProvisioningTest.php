<?php

declare(strict_types=1);

use App\Domain\BusinessRoleCatalog;
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
