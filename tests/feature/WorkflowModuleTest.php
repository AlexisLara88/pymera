<?php

declare(strict_types=1);

use App\Domain\BusinessRoleCatalog;
use App\Exceptions\BusinessAccessException;
use App\Models\ActivityModel;
use App\Models\AuditEventModel;
use App\Models\BusinessModel;
use App\Models\BusinessProfileModel;
use App\Models\BusinessUserModel;
use App\Models\ObjectiveModel;
use App\Services\ActivityService;
use App\Services\ObjectiveService;
use CodeIgniter\Config\Services;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class WorkflowModuleTest extends CIUnitTestCase
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

    public function testTestingRegistersOnlyTheExpectedWorkflowMethods(): void
    {
        $routes     = service('routes');
        $getRoutes  = array_keys($routes->getRoutes('GET'));
        $postRoutes = array_keys($routes->getRoutes('POST'));

        $this->assertContains('app/objetivos', $getRoutes);
        $this->assertContains('app/prioridades', $getRoutes);
        $this->assertContains('app/objetivos', $postRoutes);
        $this->assertContains('app/objetivos/([0-9]+)', $postRoutes);
        $this->assertContains('app/objetivos/([0-9]+)/actividades', $postRoutes);
        $this->assertContains('app/actividades/([0-9]+)', $postRoutes);
        $this->assertNotContains('app/prioridades', $postRoutes);
    }

    public function testAnonymousAccountCannotOpenObjectivesOrPriorities(): void
    {
        $this->get('/app/objetivos')->assertRedirectTo('/login');
        $this->get('/app/prioridades')->assertRedirectTo('/login');
    }

    public function testAccountWithoutMembershipReceivesSafeDenial(): void
    {
        $this->actingAs($this->createUser('without-business'));

        $result = $this
            ->withSession($_SESSION)
            ->get('/app/objetivos');

        $result->assertStatus(403);
        $result->assertSee('No pudimos abrir este negocio');
        $this->assertStringNotContainsString('BusinessAccessException', $result->getBody());
    }

    public function testObjectiveViewIsIsolatedAndEscapesStoredContent(): void
    {
        $user         = $this->createUser('reader');
        $businessId   = $this->createBusiness('Negocio propio');
        $otherId      = $this->createBusiness('Negocio ajeno');
        $objectiveId  = $this->createObjective($businessId, '<script>alert("objetivo")</script>');
        $otherJourney = $this->createObjective($otherId, 'Objetivo ajeno');
        $this->createActivity($objectiveId, '<img src=x onerror=alert(1)>', true, true);
        $this->createActivity($otherJourney, 'Actividad ajena', false, false);
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $result = $this
            ->withSession($_SESSION)
            ->get('/app/objetivos');

        $result->assertOK();
        $this->assertStringContainsString('module-header module-header-compact', $result->getBody());
        $result->assertDontSee('Paso 2 de 4');
        $result->assertSee('&lt;script&gt;alert("objetivo")&lt;/script&gt;');
        $result->assertSee('&lt;img src=x onerror=alert(1)&gt;');
        $this->assertStringNotContainsString('<script>alert("objetivo")</script>', $result->getBody());
        $this->assertStringNotContainsString('Objetivo ajeno', $result->getBody());
        $this->assertStringNotContainsString('Actividad ajena', $result->getBody());
    }

    public function testValidObjectiveCreationUsesSessionBusinessAndCreatesAuditEvent(): void
    {
        $user       = $this->createUser('create-objective');
        $businessId = $this->createBusiness('Negocio autorizado');
        $otherId    = $this->createBusiness('Negocio ajeno');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $result = $this
            ->withSession($_SESSION)
            ->post('/app/objetivos', [
                ...$this->objectivePayload(),
                'business_id' => (string) $otherId,
                csrf_token()  => csrf_hash(),
            ]);

        $result->assertRedirectTo('/app/objetivos');
        $this->seeInDatabase('objectives', [
            'business_id' => $businessId,
            'title'       => 'Reducir reclamos de entrega',
            'category'    => 'improvement',
            'status'      => 'active',
            'start_date'  => '2026-08-06',
            'target_date' => '2026-09-06',
        ]);
        $this->dontSeeInDatabase('objectives', [
            'business_id' => $otherId,
            'title'       => 'Reducir reclamos de entrega',
        ]);

        $objective = (new ObjectiveModel())
            ->where('business_id', $businessId)
            ->first();
        $this->assertNotNull($objective);
        $this->seeInDatabase('audit_events', [
            'business_id' => $businessId,
            'user_id'     => $user->id,
            'entity_type' => 'objective',
            'entity_id'   => $objective['id'],
            'action'      => 'created',
        ]);
    }

    public function testInvalidObjectiveDatesDoNotPersistOrAudit(): void
    {
        $user       = $this->createUser('invalid-objective');
        $businessId = $this->createBusiness('Negocio autorizado');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $result = $this
            ->withSession($_SESSION)
            ->post('/app/objetivos', [
                ...$this->objectivePayload(),
                'start_date'  => '2026-09-07',
                'target_date' => '2026-09-06',
                csrf_token()  => csrf_hash(),
            ]);

        $result->assertStatus(422);
        $result->assertSee('La fecha objetivo no puede ser anterior');
        $this->assertSame(0, (new ObjectiveModel())->countAllResults());
        $this->assertSame(0, (new AuditEventModel())->countAllResults());
    }

    public function testObjectiveLifecycleSetsAndClearsCompletionTimestamp(): void
    {
        $user        = $this->createUser('objective-state');
        $businessId  = $this->createBusiness('Negocio autorizado');
        $objectiveId = $this->createObjective($businessId, 'Objetivo con estados');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);
        $service = new ObjectiveService();

        $service->update($objectiveId, [
            ...$this->objectivePayload(),
            'title'  => 'Objetivo completado',
            'status' => 'completed',
        ]);
        $completed = (new ObjectiveModel())->find($objectiveId);
        $this->assertNotEmpty($completed['completed_at']);

        $service->update($objectiveId, [
            ...$this->objectivePayload(),
            'title'  => 'Objetivo reabierto',
            'status' => 'active',
        ]);
        $reopened = (new ObjectiveModel())->find($objectiveId);
        $this->assertNull($reopened['completed_at']);
    }

    public function testActivityRequiresAnObjectiveOwnedByTheSessionBusiness(): void
    {
        $user             = $this->createUser('cross-business');
        $businessId       = $this->createBusiness('Negocio autorizado');
        $otherBusinessId  = $this->createBusiness('Negocio ajeno');
        $foreignObjective = $this->createObjective($otherBusinessId, 'Objetivo ajeno');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $result = $this
            ->withSession($_SESSION)
            ->post('/app/objetivos/' . $foreignObjective . '/actividades', [
                ...$this->activityPayload(),
                csrf_token() => csrf_hash(),
            ]);

        $result->assertStatus(403);
        $this->assertSame(0, (new ActivityModel())->countAllResults());
        $this->assertSame(0, (new AuditEventModel())->countAllResults());
    }

    public function testAllEisenhowerQuadrantsAreDerivedFromStoredActivityFlags(): void
    {
        $user        = $this->createUser('quadrants');
        $businessId  = $this->createBusiness('Negocio autorizado');
        $objectiveId = $this->createObjective($businessId, 'Objetivo matriz');
        $this->createActivity($objectiveId, 'Resolver hoy', true, true);
        $this->createActivity($objectiveId, 'Planificar campaña', false, true);
        $this->createActivity($objectiveId, 'Delegar llamada', true, false);
        $this->createActivity($objectiveId, 'Eliminar tarea', false, false);
        $this->createMembership($user, $businessId);
        $this->actingAs($user);
        $overview = (new ObjectiveService())->overview();

        $result = $this
            ->withSession($_SESSION)
            ->get('/app/prioridades');

        $result->assertOK();
        $this->assertStringContainsString('module-header module-header-compact', $result->getBody());
        $result->assertDontSee('Paso 3 de 4');
        $result->assertSee('Hacer ahora');
        $result->assertSee('Planificar');
        $result->assertSee('Delegar');
        $result->assertSee('Eliminar');
        $result->assertSee('Resolver hoy');
        $result->assertSee('Planificar campaña');
        $result->assertSee('Delegar llamada');
        $result->assertSee('Eliminar tarea');
        $this->assertSame(4, substr_count($result->getBody(), 'class="quadrant-items"'));
        $this->assertSame(4, substr_count($result->getBody(), 'tabindex="0"'));
        $this->assertSame(4, substr_count($result->getBody(), 'role="region"'));
        $this->assertSame(4, substr_count($result->getBody(), 'class="priority-card"'));
        $this->assertSame(1, $overview['workflow_summary']['active_objectives']);
        $this->assertSame(4, $overview['workflow_summary']['activities']);
        $this->assertSame(0, $overview['workflow_summary']['in_progress']);
        $this->assertSame('Objetivo matriz', $overview['featured_objective']['title']);
    }

    public function testActivityUpdateKeepsItsObjectiveAndHandlesCompletionState(): void
    {
        $user             = $this->createUser('activity-state');
        $businessId       = $this->createBusiness('Negocio autorizado');
        $objectiveId      = $this->createObjective($businessId, 'Objetivo propio');
        $anotherObjective = $this->createObjective($businessId, 'Otro objetivo propio');
        $activityId       = $this->createActivity($objectiveId, 'Actividad original', false, false);
        $this->createMembership($user, $businessId);
        $this->actingAs($user);
        $service = new ActivityService();

        $service->update($activityId, [
            ...$this->activityPayload(),
            'title'        => 'Actividad completada',
            'status'       => 'completed',
            'objective_id' => (string) $anotherObjective,
            'is_urgent'    => '1',
            'is_important' => '1',
        ]);
        $completed = (new ActivityModel())->find($activityId);
        $this->assertSame($objectiveId, (int) $completed['objective_id']);
        $this->assertSame(1, (int) $completed['is_urgent']);
        $this->assertSame(1, (int) $completed['is_important']);
        $this->assertNotEmpty($completed['completed_at']);

        $service->update($activityId, [
            ...$this->activityPayload(),
            'title'  => 'Actividad reabierta',
            'status' => 'in_progress',
        ]);
        $reopened = (new ActivityModel())->find($activityId);
        $this->assertNull($reopened['completed_at']);
    }

    public function testArchivingUsesSoftDeletesRetainsHistoryAndCreatesAuditEvents(): void
    {
        $user        = $this->createUser('archive');
        $businessId  = $this->createBusiness('Negocio autorizado');
        $objectiveId = $this->createObjective($businessId, 'Objetivo archivable');
        $activityId  = $this->createActivity($objectiveId, 'Actividad archivable', true, true);
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        (new ActivityService())->archive($activityId);
        $this->assertNull((new ActivityModel())->find($activityId));
        $this->assertNotNull((new ActivityModel())->withDeleted()->find($activityId));
        $this->seeInDatabase('audit_events', [
            'business_id' => $businessId,
            'entity_type' => 'activity',
            'entity_id'   => $activityId,
            'action'      => 'deleted',
        ]);

        $retainedActivityId = $this->createActivity(
            $objectiveId,
            'Actividad retenida con objetivo',
            false,
            true,
        );
        (new ObjectiveService())->archive($objectiveId);
        $this->assertNull((new ObjectiveModel())->find($objectiveId));
        $this->assertNotNull((new ObjectiveModel())->withDeleted()->find($objectiveId));
        $this->assertNotNull((new ActivityModel())->find($retainedActivityId));
        $this->assertSame([], (new ActivityModel())->findForBusiness($businessId));
        $this->seeInDatabase('audit_events', [
            'business_id' => $businessId,
            'entity_type' => 'objective',
            'entity_id'   => $objectiveId,
            'action'      => 'deleted',
        ]);
    }

    public function testWorkflowMutationWithoutCsrfIsRejectedBeforePersistence(): void
    {
        $user       = $this->createUser('csrf');
        $businessId = $this->createBusiness('Negocio protegido');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $this->expectException(SecurityException::class);

        $this
            ->withSession($_SESSION)
            ->post('/app/objetivos', $this->objectivePayload());
    }

    private function createUser(string $suffix): User
    {
        $users = auth()->getProvider();
        $user  = new User([
            'username' => "workflow-{$suffix}",
            'email'    => "{$suffix}@workflow.test",
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

    private function createMembership(User $user, int $businessId): void
    {
        $profileId = (new BusinessProfileModel())->insert([
            'business_id'       => $businessId,
            'what_it_does'      => 'Negocio utilizado en pruebas del flujo de trabajo.',
            'customers_served'  => 'Clientes de prueba',
            'products_offered'  => 'Productos de prueba',
            'objectives_summary' => 'Validar objetivos y prioridades',
        ], true);
        $this->assertNotFalse($profileId);

        $membershipId = (new BusinessUserModel())->insert([
            'user_id'     => $user->id,
            'business_id' => $businessId,
            'role_code'   => BusinessRoleCatalog::OWNER,
            'status'      => 'active',
        ], true);

        $this->assertNotFalse($membershipId);
    }

    private function createObjective(int $businessId, string $title): int
    {
        $objectiveId = (new ObjectiveModel())->insert([
            'business_id' => $businessId,
            'title'       => $title,
            'status'      => 'active',
        ], true);

        $this->assertNotFalse($objectiveId);

        return (int) $objectiveId;
    }

    private function createActivity(
        int $objectiveId,
        string $title,
        bool $urgent,
        bool $important,
    ): int {
        $activityId = (new ActivityModel())->insert([
            'objective_id' => $objectiveId,
            'title'        => $title,
            'status'       => 'pending',
            'is_urgent'    => $urgent ? 1 : 0,
            'is_important' => $important ? 1 : 0,
        ], true);

        $this->assertNotFalse($activityId);

        return (int) $activityId;
    }

    /**
     * @return array<string, string>
     */
    private function objectivePayload(): array
    {
        return [
            'title'       => 'Reducir reclamos de entrega',
            'description' => 'Crear un procedimiento de aceptación.',
            'category'    => 'improvement',
            'status'      => 'active',
            'start_date'  => '2026-08-06',
            'target_date' => '2026-09-06',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function activityPayload(): array
    {
        return [
            'title'        => 'Preparar muestra de sabor',
            'description'  => 'Validar el pedido antes de producir.',
            'status'       => 'pending',
            'due_date'     => '2026-08-12',
            'is_urgent'    => '0',
            'is_important' => '1',
        ];
    }
}
