<?php

declare(strict_types=1);

use App\Exceptions\BusinessAccessException;
use App\Domain\BusinessPermissionCatalog;
use App\Domain\BusinessRoleCatalog;
use App\Models\ActivityModel;
use App\Models\AuditEventModel;
use App\Models\BusinessModel;
use App\Models\BusinessProfileModel;
use App\Models\BusinessUserModel;
use App\Models\ObjectiveModel;
use App\Services\AlphaBusinessContext;
use App\Services\AuditEventRecorder;
use App\Services\AuthorizedBusinessReader;
use App\Services\BusinessAuthorizationService;
use CodeIgniter\Config\Services;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class BusinessAccessIsolationTest extends CIUnitTestCase
{
    use AuthenticationTesting;
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

    public function testPhaseTwoSchemaIsAvailableWithoutRealData(): void
    {
        $this->assertTrue($this->db->tableExists('business_users'));
        $this->assertTrue($this->db->tableExists('audit_events'));
        $this->assertSame(0, $this->db->table('business_users')->countAllResults());
        $this->assertSame(0, $this->db->table('audit_events')->countAllResults());
        $this->assertTrue($this->db->fieldExists('role_code', 'business_users'));

        $membershipId = (new BusinessUserModel())->insert([
            'user_id'     => 1,
            'business_id' => 1,
            'status'      => 'active',
        ], true);

        $this->assertFalse($membershipId, 'Every application insert must declare role_code.');
    }

    public function testAnonymousContextIsRejected(): void
    {
        $this->expectException(BusinessAccessException::class);
        $this->expectExceptionMessage('Se requiere una cuenta autenticada.');

        (new AlphaBusinessContext())->businessId();
    }

    public function testAccountWithoutMembershipIsRejected(): void
    {
        $this->actingAs($this->createUser('without-membership'));

        $this->expectException(BusinessAccessException::class);
        $this->expectExceptionMessage('La cuenta no tiene un negocio activo autorizado.');

        (new AlphaBusinessContext())->businessId();
    }

    public function testContextResolvesTheOnlyActiveBusinessFromTheSession(): void
    {
        $user       = $this->createUser('single');
        $businessId = $this->createBusiness('Negocio autorizado');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $context = new AlphaBusinessContext();

        $this->assertSame((int) $user->id, $context->actorId());
        $this->assertSame($businessId, $context->businessId());
        $this->assertSame(BusinessRoleCatalog::OWNER, $context->membership()['role_code']);
    }

    public function testOwnerReceivesCurrentBusinessPermissions(): void
    {
        $user = $this->createUser('owner');
        $this->createMembership($user, $this->createBusiness('Negocio propietario'));
        $this->actingAs($user);

        $authorization = new BusinessAuthorizationService();

        $this->assertTrue($authorization->can(BusinessPermissionCatalog::BUSINESS_UPDATE));
        $this->assertTrue($authorization->can(BusinessPermissionCatalog::FINANCES_MANAGE));
        $this->assertTrue($authorization->can(BusinessPermissionCatalog::CRM_MANAGE));
    }

    public function testUnconfirmedRolesDoNotReceiveInferredPermissions(): void
    {
        $user = $this->createUser('coach');
        $this->createMembership(
            $user,
            $this->createBusiness('Negocio acompañado'),
            BusinessRoleCatalog::COACH,
        );
        $this->actingAs($user);

        $authorization = new BusinessAuthorizationService();

        $this->assertFalse($authorization->can(BusinessPermissionCatalog::BUSINESS_VIEW));

        $this->expectException(BusinessAccessException::class);
        $this->expectExceptionMessage('El rol de la cuenta no permite realizar esta operación.');
        $authorization->require(BusinessPermissionCatalog::BUSINESS_VIEW);
    }

    public function testMultipleActiveMembershipsRemainStructurallyPossibleButAreRejectedByTheAlpha(): void
    {
        $user = $this->createUser('multi');
        $this->createMembership($user, $this->createBusiness('Negocio uno'));
        $this->createMembership($user, $this->createBusiness('Negocio dos'));
        $this->actingAs($user);

        $this->expectException(BusinessAccessException::class);
        $this->expectExceptionMessage('La cuenta requiere seleccionar un negocio activo.');

        (new AlphaBusinessContext())->businessId();
    }

    public function testReaderNeverReturnsAnotherBusinessData(): void
    {
        $user              = $this->createUser('reader');
        $authorizedId      = $this->createBusiness('Negocio autorizado');
        $otherBusinessId   = $this->createBusiness('Negocio ajeno');
        $authorizedJourney = $this->createJourney($authorizedId, 'Autorizado');
        $this->createJourney($otherBusinessId, 'Ajeno');
        $this->createMembership($user, $authorizedId);
        $this->actingAs($user);

        $reader = new AuthorizedBusinessReader();

        $this->assertSame('Negocio autorizado', $reader->business()['name']);
        $this->assertSame(
            $authorizedId,
            (int) $reader->profile()['business_id'],
        );
        $this->assertSame(
            [$authorizedJourney['objective_id']],
            array_map(
                static fn (array $objective): int => (int) $objective['id'],
                $reader->objectives(),
            ),
        );
        $this->assertSame(
            [$authorizedJourney['activity_id']],
            array_map(
                static fn (array $activity): int => (int) $activity['id'],
                $reader->activities(),
            ),
        );
    }

    public function testAuditEventUsesTheSessionActorAndRejectsCrossBusinessEntities(): void
    {
        $user            = $this->createUser('audit');
        $authorizedId    = $this->createBusiness('Negocio auditable');
        $otherBusinessId = $this->createBusiness('Otro negocio');
        $authorized      = $this->createJourney($authorizedId, 'Propio');
        $other           = $this->createJourney($otherBusinessId, 'Ajeno');
        $this->createMembership($user, $authorizedId);
        $this->actingAs($user);

        $recorder = new AuditEventRecorder();
        $eventId  = $recorder->record(
            'objective',
            $authorized['objective_id'],
            'updated',
        );

        $event = (new AuditEventModel())->find($eventId);
        $this->assertSame($authorizedId, (int) $event['business_id']);
        $this->assertSame((int) $user->id, (int) $event['user_id']);
        $this->assertSame('objective', $event['entity_type']);
        $this->assertSame('updated', $event['action']);

        try {
            $recorder->record('objective', $other['objective_id'], 'updated');
            $this->fail('A cross-business audit event should be rejected.');
        } catch (BusinessAccessException $exception) {
            $this->assertSame(
                'La entidad no pertenece al negocio autorizado.',
                $exception->getMessage(),
            );
        }

        $this->assertSame(1, (new AuditEventModel())->countAllResults());
    }

    private function createUser(string $suffix): User
    {
        $users = auth()->getProvider();
        $user  = new User([
            'username' => "alpha-{$suffix}",
            'email'    => "{$suffix}@example.test",
            'password' => 'Safe-Test-Password-42!',
            'active'   => 1,
        ]);

        $this->assertTrue($users->save($user));

        $savedUser = $users->findById($users->getInsertID());
        $this->assertInstanceOf(User::class, $savedUser);
        $users->addToDefaultGroup($savedUser);

        return $savedUser;
    }

    private function createBusiness(string $name): int
    {
        $businessId = (new BusinessModel())->insert([
            'name'          => $name,
            'currency_code' => 'USD',
            'timezone'      => 'America/Guayaquil',
            'status'        => 'active',
        ], true);

        $this->assertNotFalse($businessId);

        return (int) $businessId;
    }

    private function createMembership(
        User $user,
        int $businessId,
        string $roleCode = BusinessRoleCatalog::OWNER,
    ): void
    {
        $membershipId = (new BusinessUserModel())->insert([
            'user_id'     => $user->id,
            'business_id' => $businessId,
            'role_code'   => $roleCode,
            'status'      => 'active',
        ], true);

        $this->assertNotFalse($membershipId);
    }

    /**
     * @return array{objective_id: int, activity_id: int}
     */
    private function createJourney(int $businessId, string $label): array
    {
        $profileId = (new BusinessProfileModel())->insert([
            'business_id'       => $businessId,
            'what_it_does'      => "Actividad {$label}",
            'customers_served'  => "Clientes {$label}",
            'products_offered'  => "Productos {$label}",
            'objectives_summary' => "Objetivos {$label}",
        ], true);
        $this->assertNotFalse($profileId);

        $objectiveId = (new ObjectiveModel())->insert([
            'business_id' => $businessId,
            'title'       => "Objetivo {$label}",
            'status'      => 'active',
        ], true);
        $this->assertNotFalse($objectiveId);

        $activityId = (new ActivityModel())->insert([
            'objective_id' => $objectiveId,
            'title'        => "Actividad {$label}",
            'status'       => 'pending',
            'is_urgent'    => 1,
            'is_important' => 1,
        ], true);
        $this->assertNotFalse($activityId);

        return [
            'objective_id' => (int) $objectiveId,
            'activity_id'  => (int) $activityId,
        ];
    }
}
