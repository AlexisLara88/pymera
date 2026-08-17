<?php

declare(strict_types=1);

use App\Domain\BusinessRoleCatalog;
use App\Models\AuditEventModel;
use App\Models\BusinessModel;
use App\Models\BusinessProfileModel;
use App\Models\BusinessUserModel;
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
final class BusinessModuleTest extends CIUnitTestCase
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

    public function testTestingRegistersOnlyTheProtectedBusinessModuleMethods(): void
    {
        $routes     = service('routes');
        $getRoutes  = array_keys($routes->getRoutes('GET'));
        $postRoutes = array_keys($routes->getRoutes('POST'));

        $this->assertContains('app', $getRoutes);
        $this->assertContains('app/mi-negocio', $getRoutes);
        $this->assertContains('app/mi-negocio', $postRoutes);
        $this->assertNotContains('app', $postRoutes);
    }

    public function testAnonymousAccountCannotOpenTheBusinessModule(): void
    {
        $this->get('/app/mi-negocio')->assertRedirectTo('/login');
    }

    public function testAuthenticatedAccountWithoutMembershipReceivesSafeDenial(): void
    {
        $this->actingAs($this->createUser('without-business'));

        $result = $this
            ->withSession($_SESSION)
            ->get('/app/mi-negocio');

        $result->assertStatus(403);
        $result->assertSee('No pudimos abrir este negocio');
        $this->assertStringNotContainsString('BusinessAccessException', $result->getBody());
    }

    public function testAuthenticatedAccountReadsOnlyItsBusinessAndEscapesStoredContent(): void
    {
        $user         = $this->createUser('reader');
        $businessId   = $this->createBusiness('<script>alert("propio")</script>');
        $otherId      = $this->createBusiness('Negocio ajeno');
        $profileId    = $this->createProfile($businessId, 'Perfil propio');
        $otherProfile = $this->createProfile($otherId, 'Perfil ajeno');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $result = $this
            ->withSession($_SESSION)
            ->get('/app/mi-negocio');

        $result->assertOK();
        $result->assertSee('Perfil del negocio');
        $result->assertSee('44%');
        $result->assertSee('Perfil del negocio');
        $this->assertSame(5, substr_count($result->getBody(), 'data-context-help>'));
        $this->assertSame(5, substr_count($result->getBody(), 'class="context-help-trigger"'));
        $this->assertStringContainsString('&iquest;Cu&aacute;ndo est&aacute; listo mi perfil?', $result->getBody());
        $this->assertStringNotContainsString(']) ?&gt;', $result->getBody());
        $this->assertStringContainsString('>Perfil propio</textarea>', $result->getBody());
        $this->assertStringContainsString(
            '&lt;script&gt;alert("propio")&lt;/script&gt;',
            $result->getBody(),
        );
        $this->assertStringNotContainsString('<script>alert("propio")</script>', $result->getBody());
        $this->assertStringNotContainsString('Perfil ajeno', $result->getBody());
        $this->assertNotSame($profileId, $otherProfile);
    }

    public function testValidUpdatePersistsAtomicallyAttributesEventsAndIgnoresForeignBusinessId(): void
    {
        $user       = $this->createUser('editor');
        $businessId = $this->createBusiness('Negocio propio');
        $otherId    = $this->createBusiness('Negocio ajeno');
        $profileId  = $this->createProfile($businessId, 'Descripción anterior');
        $this->createProfile($otherId, 'No debe cambiar');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $result = $this
            ->withSession($_SESSION)
            ->post('/app/mi-negocio', [
                ...$this->validPayload(),
                'name'        => 'Negocio actualizado',
                'business_id' => (string) $otherId,
                csrf_token()  => csrf_hash(),
            ]);

        $result->assertRedirectTo('/app/mi-negocio');
        $this->seeInDatabase('businesses', [
            'id'   => $businessId,
            'name' => 'Negocio actualizado',
        ]);
        $this->seeInDatabase('businesses', [
            'id'   => $otherId,
            'name' => 'Negocio ajeno',
        ]);
        $this->seeInDatabase('business_profiles', [
            'id'                     => $profileId,
            'business_id'            => $businessId,
            'what_it_does'           => 'Pastelería artesanal',
            'acquisition_channels'   => 'Recomendaciones y redes sociales',
        ]);

        $events = (new AuditEventModel())
            ->where('business_id', $businessId)
            ->orderBy('id', 'ASC')
            ->findAll();

        $this->assertCount(2, $events);
        $this->assertSame(['business', 'business_profile'], array_column($events, 'entity_type'));
        $this->assertSame(
            [(int) $user->id, (int) $user->id],
            array_map(static fn (array $event): int => (int) $event['user_id'], $events),
        );
        $this->assertSame(['updated', 'updated'], array_column($events, 'action'));
    }

    public function testValidUpdateCreatesTheProfileWhenItDoesNotExist(): void
    {
        $user       = $this->createUser('new-profile');
        $businessId = $this->createBusiness('Negocio sin perfil');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $result = $this
            ->withSession($_SESSION)
            ->post('/app/mi-negocio', [
                ...$this->validPayload(),
                csrf_token() => csrf_hash(),
            ]);

        $result->assertRedirectTo('/app');
        $this->seeInDatabase('business_profiles', [
            'business_id'    => $businessId,
            'what_it_does'   => 'Pastelería artesanal',
            'differentiator' => 'Recetas personalizadas',
        ]);
        $this->seeInDatabase('audit_events', [
            'business_id' => $businessId,
            'entity_type' => 'business_profile',
            'action'      => 'created',
        ]);

        $dashboard = $this
            ->withSession($_SESSION)
            ->get('/app');

        $dashboard->assertOK();
        $dashboard->assertSee('El perfil inicial del negocio quedó configurado');
    }

    public function testInvalidUpdateReturnsFieldErrorsWithoutChangingDataOrAudit(): void
    {
        $user       = $this->createUser('invalid');
        $businessId = $this->createBusiness('Nombre original');
        $this->createProfile($businessId, 'Descripción original');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $result = $this
            ->withSession($_SESSION)
            ->post('/app/mi-negocio', [
                ...$this->validPayload(),
                'name'          => '',
                'timezone'      => 'Zona inventada',
                'what_it_does'  => '',
                'currency_code' => '12',
                csrf_token()    => csrf_hash(),
            ]);

        $result->assertStatus(422);
        $result->assertSee('Revisá los campos señalados');
        $result->assertSee('Ingresá el nombre del negocio');
        $result->assertSee('Seleccioná una zona horaria IANA válida');
        $this->seeInDatabase('businesses', [
            'id'   => $businessId,
            'name' => 'Nombre original',
        ]);
        $this->seeInDatabase('business_profiles', [
            'business_id'  => $businessId,
            'what_it_does' => 'Descripción original',
        ]);
        $this->assertSame(0, (new AuditEventModel())->countAllResults());
    }

    public function testUpdateWithoutCsrfIsRejectedBeforePersistence(): void
    {
        $user       = $this->createUser('csrf');
        $businessId = $this->createBusiness('Negocio protegido');
        $this->createProfile($businessId, 'Perfil protegido');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $this->expectException(SecurityException::class);

        $this
            ->withSession($_SESSION)
            ->post('/app/mi-negocio', $this->validPayload());
    }

    private function createUser(string $suffix): User
    {
        $users = auth()->getProvider();
        $user  = new User([
            'username' => "business-{$suffix}",
            'email'    => "{$suffix}@business.test",
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

    private function createProfile(int $businessId, string $description): int
    {
        $profileId = (new BusinessProfileModel())->insert([
            'business_id'       => $businessId,
            'what_it_does'      => $description,
            'customers_served'  => 'Familias y empresas',
            'products_offered'  => 'Pasteles y postres',
            'objectives_summary' => 'Mejorar entregas',
        ], true);

        $this->assertNotFalse($profileId);

        return (int) $profileId;
    }

    private function createMembership(User $user, int $businessId): void
    {
        $membershipId = (new BusinessUserModel())->insert([
            'user_id'     => $user->id,
            'business_id' => $businessId,
            'role_code'   => BusinessRoleCatalog::OWNER,
            'status'      => 'active',
        ], true);

        $this->assertNotFalse($membershipId);
    }

    /**
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'name'                     => 'Dulce Barrio',
            'currency_code'            => 'usd',
            'timezone'                 => 'America/Guayaquil',
            'what_it_does'             => 'Pastelería artesanal',
            'customers_served'         => 'Familias y pequeños eventos',
            'products_offered'         => 'Pasteles y postres personalizados',
            'objectives_summary'       => 'Reducir reclamos y aumentar ventas',
            'differentiator'            => 'Recetas personalizadas',
            'differentiation_delivery' => 'Validación previa del diseño',
            'customer_outcome'         => 'Una celebración confiable',
            'purchase_reason'          => 'Atención cercana',
            'acquisition_channels'     => 'Recomendaciones y redes sociales',
        ];
    }
}
