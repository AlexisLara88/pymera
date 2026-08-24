<?php

declare(strict_types=1);

use App\Domain\BusinessRoleCatalog;
use App\Database\Seeds\DemoAlphaSeeder;
use App\Models\BusinessModel;
use App\Models\BusinessProfileModel;
use App\Models\BusinessUserModel;
use App\Models\FinancialDailyEntryModel;
use App\Models\ObjectiveModel;
use App\Services\DashboardService;
use CodeIgniter\Config\Services;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class DashboardModuleTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;

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

    public function testDashboardIsTheOnlyProtectedGetEntryPointAtAppRoot(): void
    {
        $routes     = service('routes');
        $getRoutes  = array_keys($routes->getRoutes('GET'));
        $postRoutes = array_keys($routes->getRoutes('POST'));

        $this->assertContains('app', $getRoutes);
        $this->assertNotContains('app', $postRoutes);
        $this->get('/app')->assertRedirectTo('/login');
    }

    public function testIncompleteMinimumProfileRedirectsToOpenBusinessEditor(): void
    {
        $user       = $this->createUser('onboarding');
        $businessId = $this->createBusiness('Negocio por completar');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        foreach (['/app', '/app/objetivos', '/app/prioridades', '/app/finanzas'] as $route) {
            $result = $this
                ->withSession($_SESSION)
                ->get($route);

            $result->assertRedirect();
            $this->assertStringEndsWith(
                '/app/mi-negocio#businessEditor',
                $result->response()->getHeaderLine('Location'),
            );
        }

        $profile = $this
            ->withSession($_SESSION)
            ->get('/app/mi-negocio');

        $profile->assertOK();
        $profile->assertSee('Configurar negocio');
        $this->assertStringNotContainsString('data-context-help-placement="target-side"', $profile->getBody());
        $this->assertStringNotContainsString(
            'Completá las cuatro respuestas mínimas para configurar el perfil inicial de tu negocio.',
            $profile->getBody(),
        );
        $this->assertStringNotContainsString('Objetivos', $this->primaryNavigation($profile->getBody()));
        $this->assertStringContainsString(
            'id="businessEditor" open',
            $profile->getBody(),
        );
    }

    public function testSeededBusinessSeesAReadOnlyIntegratedDashboard(): void
    {
        $user       = $this->createUser('reader');
        $businessId = $this->seededBusinessId();
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $result = $this
            ->withSession($_SESSION)
            ->get('/app');

        $result->assertOK();
        $this->assertStringNotContainsString(
            '<span class="step-pill">Vista general</span>',
            $result->getBody(),
        );
        $result->assertSee('Así está Dulce Barrio');
        $result->assertSee('Objetivos activos');
        $result->assertSee('Acciones que requieren atención');
        $result->assertSee('Utilidad bruta');
        $result->assertSee('EBITDA');
        $result->assertDontSee('EBITDA provisional');
        $result->assertSee('Últimos cierres confirmados');
        $result->assertSee('USD 8.650,00');
        $result->assertSee('USD 2.470,00');
        $this->assertStringNotContainsString('<form', $this->dashboardContent($result->getBody()));
        $this->assertStringNotContainsString('Mi negocio', $this->primaryNavigation($result->getBody()));
        $this->assertStringContainsString('Perfil del negocio', $result->getBody());
        $this->assertGreaterThanOrEqual(2, substr_count($result->getBody(), '/app/mi-negocio'));
    }

    public function testOptionalDiagnosisDoesNotBlockTheOperationalJourney(): void
    {
        $user       = $this->createUser('minimum-complete');
        $businessId = $this->createBusiness('Negocio con perfil mínimo');
        $this->createCompleteProfile($businessId, 'Descripción mínima suficiente');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        foreach (['/app', '/app/objetivos', '/app/prioridades', '/app/finanzas'] as $route) {
            $this
                ->withSession($_SESSION)
                ->get($route)
                ->assertOK();
        }
    }

    public function testAccountWithoutMembershipStillReceivesTheSafeDashboardDenial(): void
    {
        $this->actingAs($this->createUser('without-membership'));

        $result = $this
            ->withSession($_SESSION)
            ->get('/app');

        $result->assertStatus(403);
        $result->assertSee('No pudimos abrir este negocio');
        $this->assertStringNotContainsString('BusinessAccessException', $result->getBody());
    }

    public function testDashboardServiceBuildsMetricsFromExistingModules(): void
    {
        $user       = $this->createUser('service');
        $businessId = $this->seededBusinessId();
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $overview = (new DashboardService())->overview();

        $this->assertFalse($overview['requires_onboarding']);
        $this->assertTrue($overview['minimum_profile_complete']);
        $this->assertSame(100, $overview['profile_completion']);
        $this->assertSame(3, $overview['workflow_summary']['active_objectives']);
        $this->assertSame(865000, $overview['finance_totals']['sales_cents']);
        $this->assertSame(247000, $overview['finance_totals']['ebitda_cents']);
        $this->assertNotEmpty($overview['finance_chart_entries']);
        $this->assertLessThanOrEqual(7, count($overview['finance_chart_entries']));
        $this->assertArrayHasKey('sales_cents', $overview['finance_chart_entries'][0]);
        $this->assertArrayHasKey('costs_cents', $overview['finance_chart_entries'][0]);
        $this->assertLessThanOrEqual(5, count($overview['next_actions']));
        $this->assertSame(
            $overview['workflow_summary']['open_activities'],
            array_sum($overview['priority_summary']),
        );
    }

    public function testDashboardDoesNotExposeAnotherBusinessData(): void
    {
        $user       = $this->createUser('isolation');
        $businessId = $this->seededBusinessId();
        $this->createMembership($user, $businessId);

        $otherId = $this->createBusiness('Negocio ajeno secreto');
        $this->createCompleteProfile($otherId, 'Perfil ajeno que no debe aparecer');
        (new ObjectiveModel())->insert([
            'business_id' => $otherId,
            'title'       => 'Objetivo secreto de otro negocio',
            'category'    => 'commercial',
            'status'      => 'active',
        ]);
        (new FinancialDailyEntryModel())->insert([
            'business_id'                    => $otherId,
            'operation_date'                 => '2026-08-05',
            'income_amount'                  => '999999.00',
            'fixed_expense_amount'           => '0.00',
            'variable_expense_amount'        => '0.00',
            'administrative_expense_amount'  => '0.00',
            'status'                         => 'recorded',
        ]);
        $this->actingAs($user);

        $result = $this
            ->withSession($_SESSION)
            ->get('/app');

        $result->assertOK();
        $this->assertStringNotContainsString('Negocio ajeno secreto', $result->getBody());
        $this->assertStringNotContainsString('Objetivo secreto', $result->getBody());
        $this->assertStringNotContainsString('999.999', $result->getBody());
    }

    private function createUser(string $suffix): User
    {
        $users = auth()->getProvider();
        $user  = new User([
            'username' => "dashboard-{$suffix}",
            'email'    => "{$suffix}@dashboard.test",
            'password' => 'Safe-Test-Password-42!',
            'active'   => 1,
        ]);

        $this->assertTrue($users->save($user));

        $savedUser = $users->findById($users->getInsertID());
        $this->assertInstanceOf(User::class, $savedUser);
        $users->addToDefaultGroup($savedUser);

        return $savedUser;
    }

    private function seededBusinessId(): int
    {
        $business = (new BusinessModel())->where('name', 'Dulce Barrio')->first();
        $this->assertNotNull($business);

        return (int) $business['id'];
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

    private function createCompleteProfile(int $businessId, string $description): void
    {
        $profileId = (new BusinessProfileModel())->insert([
            'business_id'       => $businessId,
            'what_it_does'      => $description,
            'customers_served'  => 'Clientes ajenos',
            'products_offered'  => 'Oferta ajena',
            'objectives_summary' => 'Objetivos ajenos',
        ], true);
        $this->assertNotFalse($profileId);
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

    private function dashboardContent(string $body): string
    {
        $start = strpos($body, '<main');
        $end   = strpos($body, '</main>');

        if ($start === false || $end === false) {
            return $body;
        }

        return substr($body, $start, $end - $start);
    }

    private function primaryNavigation(string $body): string
    {
        $start = strpos($body, '<nav class="main-nav"');
        $end   = strpos($body, '</nav>', $start === false ? 0 : $start);

        if ($start === false || $end === false) {
            return $body;
        }

        return substr($body, $start, $end - $start);
    }
}
