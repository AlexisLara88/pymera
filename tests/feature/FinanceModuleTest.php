<?php

declare(strict_types=1);

use App\Domain\BusinessRoleCatalog;
use App\Models\AuditEventModel;
use App\Models\BusinessModel;
use App\Models\BusinessProfileModel;
use App\Models\BusinessUserModel;
use App\Models\FinancialDailyEntryModel;
use App\Services\FinanceService;
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
final class FinanceModuleTest extends CIUnitTestCase
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

    public function testTestingRegistersOnlyTheExpectedFinanceMethods(): void
    {
        $routes     = service('routes');
        $getRoutes  = array_keys($routes->getRoutes('GET'));
        $postRoutes = array_keys($routes->getRoutes('POST'));

        $this->assertContains('app/finanzas', $getRoutes);
        $this->assertContains('app/finanzas', $postRoutes);
        $this->assertContains('app/finanzas/([0-9]+)', $postRoutes);
        $this->assertCount(1, array_filter(
            $getRoutes,
            static fn (string $route): bool => str_starts_with($route, 'app/finanzas'),
        ));
    }

    public function testAnonymousAccountCannotOpenFinances(): void
    {
        $this->get('/app/finanzas')->assertRedirectTo('/login');
    }

    public function testAccountWithoutMembershipReceivesSafeDenial(): void
    {
        $this->actingAs($this->createUser('without-business'));

        $result = $this
            ->withSession($_SESSION)
            ->get('/app/finanzas');

        $result->assertStatus(403);
        $result->assertSee('No pudimos abrir este negocio');
        $this->assertStringNotContainsString('BusinessAccessException', $result->getBody());
    }

    public function testValidCreationUsesSessionBusinessAndCreatesAuditEvent(): void
    {
        $user       = $this->createUser('create');
        $businessId = $this->createBusiness('Negocio autorizado');
        $otherId    = $this->createBusiness('Negocio ajeno');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $result = $this
            ->withSession($_SESSION)
            ->post('/app/finanzas', [
                ...$this->financePayload(),
                'business_id' => (string) $otherId,
                csrf_token()  => csrf_hash(),
            ]);

        $result->assertRedirectTo('/app/finanzas?period=2026-08');
        $this->seeInDatabase('financial_daily_entries', [
            'business_id'             => $businessId,
            'operation_date'          => '2026-08-06',
            'income_amount'           => '150.25',
            'fixed_expense_amount'    => '20.10',
            'variable_expense_amount' => '30.05',
            'administrative_expense_amount' => '10.15',
            'status'                  => 'recorded',
        ]);
        $this->dontSeeInDatabase('financial_daily_entries', [
            'business_id'    => $otherId,
            'operation_date' => '2026-08-06',
        ]);

        $entry = (new FinancialDailyEntryModel())
            ->where('business_id', $businessId)
            ->first();
        $this->seeInDatabase('audit_events', [
            'business_id' => $businessId,
            'user_id'     => $user->id,
            'entity_type' => 'financial_daily_entry',
            'entity_id'   => $entry['id'],
            'action'      => 'created',
        ]);
    }

    public function testInvalidAndEmptyAmountsDoNotPersistOrAudit(): void
    {
        $user       = $this->createUser('invalid');
        $businessId = $this->createBusiness('Negocio autorizado');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $invalid = $this
            ->withSession($_SESSION)
            ->post('/app/finanzas', [
                ...$this->financePayload(),
                'income_amount' => '-12.00',
                csrf_token()    => csrf_hash(),
            ]);
        $invalid->assertStatus(422);
        $invalid->assertSee('Ingresá un total de ventas válido');

        $empty = $this
            ->withSession($_SESSION)
            ->post('/app/finanzas', [
                ...$this->financePayload(),
                'income_amount'           => '0',
                'fixed_expense_amount'    => '0',
                'variable_expense_amount' => '0',
                'administrative_expense_amount' => '0',
                csrf_token()              => csrf_hash(),
            ]);
        $empty->assertStatus(422);
        $empty->assertSee('Ingresá al menos un monto mayor que cero');
        $this->assertSame(0, (new FinancialDailyEntryModel())->countAllResults());
        $this->assertSame(0, (new AuditEventModel())->countAllResults());
    }

    public function testDuplicateBusinessDateIsRejected(): void
    {
        $user       = $this->createUser('duplicate');
        $businessId = $this->createBusiness('Negocio autorizado');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);
        $service = new FinanceService();

        $service->create($this->financePayload());

        $result = $this
            ->withSession($_SESSION)
            ->post('/app/finanzas', [
                ...$this->financePayload(),
                csrf_token() => csrf_hash(),
            ]);

        $result->assertStatus(422);
        $result->assertSee('Ya existe un registro agregado para esa fecha');
        $this->assertSame(1, (new FinancialDailyEntryModel())->countAllResults());
        $this->assertSame(1, (new AuditEventModel())->countAllResults());
    }

    public function testCrossBusinessUpdateIsDeniedAndCannotMoveOwnership(): void
    {
        $user             = $this->createUser('cross-business');
        $businessId       = $this->createBusiness('Negocio autorizado');
        $otherBusinessId  = $this->createBusiness('Negocio ajeno');
        $foreignEntryId   = $this->createEntry($otherBusinessId, '2026-08-06', 'recorded');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $result = $this
            ->withSession($_SESSION)
            ->post('/app/finanzas/' . $foreignEntryId, [
                ...$this->financePayload(),
                'business_id' => (string) $businessId,
                csrf_token()  => csrf_hash(),
            ]);

        $result->assertStatus(403);
        $entry = (new FinancialDailyEntryModel())->find($foreignEntryId);
        $this->assertSame($otherBusinessId, (int) $entry['business_id']);
        $this->assertSame(100.0, (float) $entry['income_amount']);
        $this->assertSame(0, (new AuditEventModel())->countAllResults());
    }

    public function testOverviewUsesExactCentsAndExcludesDraftsFromTotals(): void
    {
        $user       = $this->createUser('totals');
        $businessId = $this->createBusiness('Negocio con cierres');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);
        $this->createEntry($businessId, '2026-08-05', 'recorded', '0.10', '0.20', '0.30', '0.40');
        $this->createEntry($businessId, '2026-08-06', 'recorded', '100.25', '20.05', '30.10', '10.15');
        $this->createEntry($businessId, '2026-08-07', 'draft', '999.99', '1.00', '1.00', '1.00');
        $this->createEntry($businessId, '2026-09-01', 'recorded', '500.00', '0.00', '0.00', '0.00');

        $overview = (new FinanceService())->overview('2026-08');

        $this->assertCount(3, $overview['entries']);
        $this->assertSame(10035, $overview['totals']['sales_cents']);
        $this->assertSame(3040, $overview['totals']['cost_of_sales_cents']);
        $this->assertSame(6995, $overview['totals']['gross_profit_cents']);
        $this->assertSame(2025, $overview['totals']['operating_expense_cents']);
        $this->assertSame(1055, $overview['totals']['administrative_expense_cents']);
        $this->assertSame(3915, $overview['totals']['ebitda_cents']);
        $this->assertSame(6120, $overview['finance_summary']['costs_and_expenses_cents']);
        $this->assertSame(10035, $overview['sales_breakdown']['manual_sales_cents']);
        $this->assertSame(0, $overview['sales_breakdown']['crm_sales_cents']);
        $this->assertSame(10035, $overview['sales_breakdown']['total_sales_cents']);
        $this->assertSame(2, $overview['finance_summary']['recorded_entry_count']);
        $this->assertSame(6995, $overview['finance_indicators']['contribution_margin_cents']);
        $this->assertSame(69.71, $overview['finance_indicators']['contribution_margin_percentage']);
        $this->assertSame(3080, $overview['finance_indicators']['fixed_costs_cents']);
        $this->assertSame(4419, $overview['finance_indicators']['break_even_sales_cents']);
        $this->assertSame('available', $overview['finance_indicators']['break_even_status']);
        $this->assertCount(2, $overview['chart_entries']);
        $this->assertSame('05/08', $overview['chart_entries'][0]['label']);
    }

    public function testBreakEvenIsUnavailableWithoutSalesOrPositiveContribution(): void
    {
        $user       = $this->createUser('break-even-unavailable');
        $businessId = $this->createBusiness('Negocio sin margen');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);
        $this->createEntry($businessId, '2026-08-05', 'recorded', '0.00', '10.00', '0.00', '0.00');

        $withoutSales = (new FinanceService())->overview('2026-08');

        $this->assertNull($withoutSales['finance_indicators']['contribution_margin_percentage']);
        $this->assertNull($withoutSales['finance_indicators']['break_even_sales_cents']);
        $this->assertSame('no_sales', $withoutSales['finance_indicators']['break_even_status']);

        $this->createEntry($businessId, '2026-08-06', 'recorded', '100.00', '10.00', '100.00', '0.00');

        $withoutMargin = (new FinanceService())->overview('2026-08');

        $this->assertSame(0.0, $withoutMargin['finance_indicators']['contribution_margin_percentage']);
        $this->assertNull($withoutMargin['finance_indicators']['break_even_sales_cents']);
        $this->assertSame('non_positive_margin', $withoutMargin['finance_indicators']['break_even_status']);
    }

    public function testViewIsPeriodIsolatedEscapesNotesAndStatesPreliminaryScope(): void
    {
        $user       = $this->createUser('reader');
        $businessId = $this->createBusiness('Negocio propio');
        $otherId    = $this->createBusiness('Negocio ajeno');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);
        $this->createEntry(
            $businessId,
            '2026-08-06',
            'recorded',
            notes: '<script>alert("nota")</script>',
        );
        $this->createEntry($businessId, '2026-09-01', 'recorded', notes: 'Otro período');
        $this->createEntry($otherId, '2026-08-06', 'recorded', notes: 'Nota ajena');

        $result = $this
            ->withSession($_SESSION)
            ->get('/app/finanzas?period=2026-08');

        $result->assertOK();
        $this->assertStringContainsString('module-header module-header-compact', $result->getBody());
        $result->assertDontSee('Paso 4 de 4');
        $result->assertSee('&lt;script&gt;alert("nota")&lt;/script&gt;');
        $result->assertSee('EBITDA');
        $result->assertDontSee('EBITDA provisional');
        $result->assertSee('Punto de equilibrio');
        $result->assertDontSee('Punto de equilibrio estimado');
        $result->assertSee('Ventas totales');
        $result->assertSee('Registradas manualmente');
        $result->assertSee('Provenientes del CRM');
        $this->assertStringContainsString('<dt>Total</dt>', $result->getBody());
        $result->assertDontSee('Total usado en los cálculos');
        $result->assertSee('USD 18,75');
        $result->assertSee('Venta mínima estimada del período');
        $this->assertStringNotContainsString('<script>alert("nota")</script>', $result->getBody());
        $this->assertStringNotContainsString('Otro período', $result->getBody());
        $this->assertStringNotContainsString('Nota ajena', $result->getBody());
        $result->assertDontSee('Criterio financiero utilizado');
        $result->assertDontSee('Metodología del cliente');
        $result->assertDontSee('Sujeto a validación');
        $result->assertDontSee('Fórmula no confirmada');
        $this->assertStringNotContainsString('Recuperación de inversión', $result->getBody());
        $this->assertStringNotContainsString('<small>ROI</small>', $result->getBody());
    }

    public function testFinanceMutationWithoutCsrfIsRejectedBeforePersistence(): void
    {
        $user       = $this->createUser('csrf');
        $businessId = $this->createBusiness('Negocio protegido');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $this->expectException(SecurityException::class);

        $this
            ->withSession($_SESSION)
            ->post('/app/finanzas', $this->financePayload());
    }

    private function createUser(string $suffix): User
    {
        $users = auth()->getProvider();
        $user  = new User([
            'username' => "finance-{$suffix}",
            'email'    => "{$suffix}@finance.test",
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
            'what_it_does'      => 'Negocio utilizado en pruebas financieras.',
            'customers_served'  => 'Clientes de prueba',
            'products_offered'  => 'Productos de prueba',
            'objectives_summary' => 'Validar el módulo financiero',
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

    private function createEntry(
        int $businessId,
        string $date,
        string $status,
        string $income = '100.00',
        string $fixed = '10.00',
        string $variable = '20.00',
        string $administrative = '5.00',
        ?string $notes = null,
    ): int {
        $entryId = (new FinancialDailyEntryModel())->insert([
            'business_id'             => $businessId,
            'operation_date'          => $date,
            'income_amount'           => $income,
            'fixed_expense_amount'    => $fixed,
            'variable_expense_amount' => $variable,
            'administrative_expense_amount' => $administrative,
            'status'                  => $status,
            'notes'                   => $notes,
        ], true);

        $this->assertNotFalse($entryId);

        return (int) $entryId;
    }

    /**
     * @return array<string, string>
     */
    private function financePayload(): array
    {
        return [
            'operation_date'          => '2026-08-06',
            'income_amount'           => '150,25',
            'fixed_expense_amount'    => '20.10',
            'variable_expense_amount' => '30.05',
            'administrative_expense_amount' => '10.15',
            'status'                  => 'recorded',
            'notes'                   => 'Cierre de caja del día.',
        ];
    }
}
