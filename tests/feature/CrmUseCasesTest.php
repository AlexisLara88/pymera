<?php

declare(strict_types=1);

use App\Domain\BusinessRoleCatalog;
use App\Exceptions\BusinessAccessException;
use App\Exceptions\CrmValidationException;
use App\Exceptions\FinanceValidationException;
use App\Exceptions\SaleNoteUnavailableException;
use App\Libraries\CrmReturnLocation;
use App\Libraries\SaleNotePdfRenderer;
use App\Models\AuditEventModel;
use App\Models\BusinessModel;
use App\Models\BusinessProfileModel;
use App\Models\BusinessUserModel;
use App\Models\ContactModel;
use App\Models\CrmFinancialPostingModel;
use App\Models\FinancialDailyEntryModel;
use App\Models\OpportunityModel;
use App\Models\UserPreferenceModel;
use App\Services\ContactService;
use App\Services\CrmOverviewService;
use App\Services\OpportunityService;
use App\Services\OpportunityStatusService;
use App\Services\FinanceService;
use App\Services\SaleNoteService;
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
final class CrmUseCasesTest extends CIUnitTestCase
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

    public function testProductionRoutesPublishTheProtectedCrmSurface(): void
    {
        $routes = service('routes');
        $registered = [
            ...array_keys($routes->getRoutes('GET')),
            ...array_keys($routes->getRoutes('POST')),
        ];

        $crmRoutes = array_values(array_filter(
            $registered,
            static fn (string $route): bool => str_starts_with($route, 'app/clientes'),
        ));

        $this->assertCount(11, $crmRoutes);
        $this->assertContains('app/clientes', $crmRoutes);
        $this->assertContains('app/clientes/contactos', $crmRoutes);
        $this->assertContains('app/clientes/oportunidades', $crmRoutes);
        $this->assertContains('app/clientes/oportunidades/([0-9]+)/estado', $crmRoutes);
        $this->assertContains('app/clientes/oportunidades/([0-9]+)/nota-venta', $crmRoutes);
        $this->get('/app/clientes')->assertRedirectTo('/login');
        $this->get('/app/clientes/oportunidades/1/nota-venta')->assertRedirectTo('/login');
        $this->post('/app/clientes/oportunidades/1/nota-venta', [
            csrf_token() => csrf_hash(),
        ])->assertRedirectTo('/login');
    }

    public function testOwnerCanReadTheFunctionalCrmWithRealAndEscapedData(): void
    {
        $user       = $this->createUser('crm-screen');
        $businessId = $this->createBusiness('Negocio autorizado');
        $this->createCompleteProfile($businessId);
        $this->createMembership($user, $businessId);
        $this->actingAs($user);
        $contacts = new ContactService();
        $opportunities = new OpportunityService();
        $contactId = $contacts->create([
            ...$this->contactPayload(),
            'display_name'        => '<script>alert("crm")</script>',
            'acquisition_channel' => 'whatsapp',
        ]);
        $opportunities->create([
            ...$this->opportunityPayload($contactId),
            'need' => 'Pedido corporativo',
        ]);

        $result = $this
            ->withSession($_SESSION)
            ->get('/app/clientes');

        $result->assertOK();
        $result->assertSee('Clientes y ventas');
        $result->assertSee('Oportunidades abiertas');
        $result->assertSee('Pedido corporativo');
        $result->assertSee('WhatsApp');
        $this->assertSame(7, substr_count($result->getBody(), 'class="context-help"'));
        $result->assertSee('¿Cómo se organiza el seguimiento comercial?');
        $result->assertSee('¿Cuándo una oportunidad afecta Finanzas?');
        $this->assertStringContainsString(
            '&lt;script&gt;alert("crm")&lt;/script&gt;',
            $result->getBody(),
        );
        $this->assertStringNotContainsString(
            '<script>alert("crm")</script>',
            $result->getBody(),
        );
        $result->assertDontSee('Concepto futuro');
        $result->assertDontSee('provisional');
    }

    public function testEmptyCrmGuidesTheOwnerToCreateTheFirstContact(): void
    {
        $user       = $this->createUser('crm-empty');
        $businessId = $this->createBusiness('Negocio nuevo');
        $this->createCompleteProfile($businessId);
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $result = $this
            ->withSession($_SESSION)
            ->get('/app/clientes');

        $result->assertOK();
        $result->assertSee('Empezá por tu primer contacto');
        $result->assertSee('Crear primer contacto');
        $result->assertDontSee('+ Nueva oportunidad');
        $this->assertSame(2, substr_count($result->getBody(), 'class="context-help"'));
        $result->assertSee('¿Cómo se organiza el seguimiento comercial?');
        $result->assertSee('¿Qué muestran estos indicadores?');
        $result->assertDontSee('¿Cómo se clasifica un contacto?');
    }

    public function testContactCreationUsesSessionBusinessAndCreatesAuditEvent(): void
    {
        $user       = $this->createUser('contact-create');
        $businessId = $this->createBusiness('Negocio autorizado');
        $otherId    = $this->createBusiness('Negocio ajeno');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $contactId = (new ContactService())->create([
            ...$this->contactPayload(),
            'business_id' => (string) $otherId,
            'email'       => 'MARIA@EXAMPLE.TEST',
        ]);

        $contact = (new ContactModel())->find($contactId);
        $this->assertSame($businessId, (int) $contact['business_id']);
        $this->assertSame('maria@example.test', $contact['email']);
        $this->dontSeeInDatabase('contacts', [
            'business_id' => $otherId,
            'display_name' => 'María Pérez',
        ]);
        $this->seeInDatabase('audit_events', [
            'business_id' => $businessId,
            'user_id'     => $user->id,
            'entity_type' => 'contact',
            'entity_id'   => $contactId,
            'action'      => 'created',
        ]);
    }

    public function testContactValidationConversionAndExplicitReversion(): void
    {
        $user       = $this->createUser('contact-lifecycle');
        $businessId = $this->createBusiness('Negocio autorizado');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);
        $service = new ContactService();

        try {
            $service->create([
                ...$this->contactPayload(),
                'display_name'        => '',
                'contact_kind'        => 'robot',
                'lifecycle_stage'     => 'subscriber',
                'acquisition_channel' => 'telepathy',
                'email'               => 'invalid',
                'identity_document'   => str_repeat('1', 41),
            ]);
            $this->fail('Invalid CRM contact data should be rejected.');
        } catch (CrmValidationException $exception) {
            $this->assertSame(
                [
                    'display_name',
                    'contact_kind',
                    'lifecycle_stage',
                    'acquisition_channel',
                    'email',
                    'identity_document',
                ],
                array_keys($exception->errors()),
            );
        }

        $this->assertSame(0, (new ContactModel())->countAllResults());
        $contactId = $service->create($this->contactPayload());
        $service->convertToClient($contactId);
        $service->convertToClient($contactId);
        $this->assertSame('client', (new ContactModel())->find($contactId)['lifecycle_stage']);
        $this->assertSame(2, (new AuditEventModel())->countAllResults());

        $service->update($contactId, ['lifecycle_stage' => 'prospect']);
        $this->assertSame('prospect', (new ContactModel())->find($contactId)['lifecycle_stage']);
        $this->assertSame(3, (new AuditEventModel())->countAllResults());
    }

    public function testOpportunityRejectsForeignContactAndInvalidCommercialData(): void
    {
        $user            = $this->createUser('opportunity-validation');
        $businessId      = $this->createBusiness('Negocio autorizado');
        $otherBusinessId = $this->createBusiness('Negocio ajeno');
        $foreignContact  = $this->createContact($otherBusinessId, 'Contacto ajeno');
        $ownContact      = $this->createContact($businessId, 'Contacto propio');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);
        $service = new OpportunityService();
        $statuses = new OpportunityStatusService();

        try {
            $service->create([
                ...$this->opportunityPayload($foreignContact),
                'business_id' => (string) $businessId,
            ]);
            $this->fail('A foreign contact should not be accepted.');
        } catch (BusinessAccessException $exception) {
            $this->assertSame(
                'La entidad no pertenece al negocio autorizado.',
                $exception->getMessage(),
            );
        }

        try {
            $service->create([
                ...$this->opportunityPayload($ownContact),
                'need'                => '',
                'status'              => 'forgotten',
                'estimated_value'     => '-1.00',
                'next_follow_up_date' => 'mañana',
            ]);
            $this->fail('Invalid opportunity data should be rejected.');
        } catch (CrmValidationException $exception) {
            $this->assertSame(
                ['need', 'status', 'estimated_value', 'next_follow_up_date'],
                array_keys($exception->errors()),
            );
        }

        $this->assertSame(0, (new OpportunityModel())->countAllResults());
        $this->assertSame(0, (new AuditEventModel())->countAllResults());
    }

    public function testOpportunityCreationUpdateAndArchiveAreAudited(): void
    {
        $user       = $this->createUser('opportunity-lifecycle');
        $businessId = $this->createBusiness('Negocio autorizado');
        $contactId  = $this->createContact($businessId, 'Empresa Andina');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);
        $service = new OpportunityService();
        $statuses = new OpportunityStatusService();

        $opportunityId = $service->create([
            ...$this->opportunityPayload($contactId),
            'estimated_value' => '420,5',
        ]);
        $created = (new OpportunityModel())->find($opportunityId);
        $this->assertSame(
            '420.50',
            number_format((float) $created['estimated_value'], 2, '.', ''),
        );

        $statuses->change($opportunityId, [
            'status'         => 'contacted',
            'finance_action' => 'none',
        ]);
        $preserved = (new OpportunityModel())->find($opportunityId);
        $this->assertSame(
            '420.50',
            number_format((float) $preserved['estimated_value'], 2, '.', ''),
        );

        $service->update($opportunityId, [
            'status'          => 'won',
            'estimated_value' => '500',
        ]);
        $statuses->change($opportunityId, [
            'status'         => 'won',
            'finance_action' => 'none',
        ]);
        $updated = (new OpportunityModel())->find($opportunityId);
        $this->assertSame('won', $updated['status']);
        $this->assertSame(
            '500.00',
            number_format((float) $updated['estimated_value'], 2, '.', ''),
        );

        $service->archive($opportunityId);
        $this->assertNull((new OpportunityModel())->find($opportunityId));
        $this->assertNotNull((new OpportunityModel())->withDeleted()->find($opportunityId));
        $this->assertSame(
            ['created', 'status_changed', 'updated', 'status_changed', 'deleted'],
            array_column(
                (new AuditEventModel())->orderBy('id', 'ASC')->findAll(),
                'action',
            ),
        );
    }

    public function testContactArchiveRequiresOpenOpportunitiesToBeClosedOrArchived(): void
    {
        $user       = $this->createUser('contact-archive');
        $businessId = $this->createBusiness('Negocio autorizado');
        $contactId  = $this->createContact($businessId, 'Contacto con oportunidad');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);
        $opportunities = new OpportunityService();
        $contacts = new ContactService();
        $opportunityId = $opportunities->create(
            $this->opportunityPayload($contactId),
        );

        try {
            $contacts->archive($contactId);
            $this->fail('A contact with an open opportunity should not be archived.');
        } catch (CrmValidationException $exception) {
            $this->assertArrayHasKey('contact', $exception->errors());
        }

        $this->assertNotNull((new ContactModel())->find($contactId));
        (new OpportunityStatusService())->change($opportunityId, [
            'status'         => 'lost',
            'finance_action' => 'none',
        ]);
        $contacts->archive($contactId);

        $this->assertNull((new ContactModel())->find($contactId));
        $this->assertNotNull((new ContactModel())->withDeleted()->find($contactId));
        $this->assertNotNull((new OpportunityModel())->find($opportunityId));
    }

    public function testWonOpportunityCanBePostedOnceAndReversedWithoutDuplicatingSales(): void
    {
        $user       = $this->createUser('crm-finance-link');
        $businessId = $this->createBusiness('Negocio vinculado');
        $contactId  = $this->createContact($businessId, 'Cliente vinculado', 'client');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);
        $opportunityId = (new OpportunityService())->create([
            ...$this->opportunityPayload($contactId),
            'estimated_value' => '250.00',
        ]);
        $entryId = (new FinancialDailyEntryModel())->insert([
            'business_id'                  => $businessId,
            'operation_date'               => '2026-08-10',
            'income_amount'                => '100.00',
            'fixed_expense_amount'         => '10.00',
            'variable_expense_amount'      => '20.00',
            'administrative_expense_amount' => '5.00',
            'status'                       => 'recorded',
        ], true);
        $this->assertNotFalse($entryId);
        $statuses = new OpportunityStatusService();

        $result = $statuses->change($opportunityId, [
            'status'         => 'won',
            'finance_action' => 'record',
            'sale_amount'    => '250,00',
            'sale_date'      => '2026-08-10',
        ]);

        $this->assertTrue($result['status_changed']);
        $this->assertTrue($result['finance_recorded']);
        $this->assertSame('won', (new OpportunityModel())->find($opportunityId)['status']);
        $this->seeInDatabase('financial_daily_entries', [
            'id'            => $entryId,
            'business_id'   => $businessId,
            'income_amount' => '350.00',
            'status'        => 'recorded',
        ]);
        $this->seeInDatabase('crm_financial_postings', [
            'business_id'             => $businessId,
            'opportunity_id'          => $opportunityId,
            'financial_daily_entry_id' => $entryId,
            'amount'                  => '250.00',
            'status'                  => 'recorded',
        ]);

        $overview = (new FinanceService())->overview('2026-08');
        $this->assertSame(10000, $overview['sales_breakdown']['manual_sales_cents']);
        $this->assertSame(25000, $overview['sales_breakdown']['crm_sales_cents']);
        $this->assertSame(35000, $overview['sales_breakdown']['total_sales_cents']);

        try {
            $statuses->change($opportunityId, [
                'status'         => 'won',
                'finance_action' => 'record',
                'sale_amount'    => '250.00',
                'sale_date'      => '2026-08-10',
            ]);
            $this->fail('The same opportunity must not be posted twice.');
        } catch (CrmValidationException $exception) {
            $this->assertArrayHasKey('finance_action', $exception->errors());
        }

        try {
            (new FinanceService())->update((int) $entryId, [
                'operation_date'               => '2026-08-10',
                'income_amount'                => '200.00',
                'fixed_expense_amount'         => '10.00',
                'variable_expense_amount'      => '20.00',
                'administrative_expense_amount' => '5.00',
                'status'                       => 'recorded',
                'notes'                        => '',
            ]);
            $this->fail('A linked CRM amount must not be overwritten from Finance.');
        } catch (FinanceValidationException $exception) {
            $this->assertArrayHasKey('income_amount', $exception->errors());
        }

        $reversed = $statuses->change($opportunityId, [
            'status'         => 'lost',
            'finance_action' => 'reverse',
        ]);

        $this->assertTrue($reversed['finance_reversed']);
        $this->seeInDatabase('financial_daily_entries', [
            'id'            => $entryId,
            'income_amount' => '100.00',
            'status'        => 'recorded',
        ]);
        $this->seeInDatabase('crm_financial_postings', [
            'opportunity_id' => $opportunityId,
            'status'         => 'reversed',
        ]);
        $this->assertSame(1, (new CrmFinancialPostingModel())->countAllResults());

        $afterReversal = (new FinanceService())->overview('2026-08');
        $this->assertSame(10000, $afterReversal['sales_breakdown']['manual_sales_cents']);
        $this->assertSame(0, $afterReversal['sales_breakdown']['crm_sales_cents']);
        $this->assertSame(10000, $afterReversal['sales_breakdown']['total_sales_cents']);
    }

    public function testRecordedWonOpportunityDownloadsANonFiscalSaleNoteOnDemand(): void
    {
        $user       = $this->createUser('sale-note-download');
        $businessId = $this->createBusiness('Dulce Barrio');
        $this->createCompleteProfile($businessId);
        $contactId = $this->createContact($businessId, 'María Pérez', 'client');
        $this->assertTrue((new ContactModel())->update($contactId, [
            'email' => 'maria@example.test',
            'phone' => '+593 99 000 0000',
            'identity_document' => 'CI-1712345678',
        ]));
        $this->createMembership($user, $businessId);
        $this->actingAs($user);
        $opportunityId = (new OpportunityService())->create([
            ...$this->opportunityPayload($contactId),
            'need'            => 'Pastel personalizado para celebración',
            'estimated_value' => '150.00',
        ]);
        (new OpportunityStatusService())->change($opportunityId, [
            'status'         => 'won',
            'finance_action' => 'record',
            'sale_amount'    => '165.50',
            'sale_date'      => '2026-08-14',
        ]);

        $saleNote = (new SaleNoteService())->forOpportunity($opportunityId);
        $this->assertSame('Dulce Barrio', $saleNote['business_name']);
        $this->assertSame('María Pérez', $saleNote['customer_name']);
        $this->assertSame('165.50', $saleNote['amount']);
        $this->assertSame('2026-08-14', $saleNote['sale_date']);
        $this->assertSame('CI-1712345678', $saleNote['customer_identity_document']);
        $this->assertArrayNotHasKey('number', $saleNote);
        $this->assertArrayNotHasKey('tax_id', $saleNote);
        $this->assertArrayNotHasKey('tax', $saleNote);

        $pdfContents = (new SaleNotePdfRenderer())->render($saleNote);
        $this->assertStringStartsWith('%PDF-', $pdfContents);

        $result = $this
            ->withSession($_SESSION)
            ->get('/app/clientes/oportunidades/' . $opportunityId . '/nota-venta');

        $result->assertOK();
        $result->assertHeader('Content-Type', 'application/pdf');
        $result->assertHeader(
            'Content-Disposition',
            'attachment; filename="nota-de-venta.pdf"',
        );
        $result->assertHeader('Cache-Control');
        $this->assertStringContainsString(
            'no-store',
            $result->response()->getHeaderLine('Cache-Control'),
        );
        // The feature-test response parser wraps binary bodies as HTML. The
        // renderer assertion above verifies the raw PDF signature separately.
        $this->assertStringContainsString('%PDF-', $result->getBody());
    }

    public function testMissingIdentityIsRequestedPersistedAndAuditedBeforePdfDownload(): void
    {
        $user       = $this->createUser('sale-note-identity');
        $businessId = $this->createBusiness('Negocio autorizado');
        $this->createCompleteProfile($businessId);
        $contactId = $this->createContact($businessId, 'Cliente sin documento', 'client');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);
        $opportunityId = (new OpportunityService())->create([
            ...$this->opportunityPayload($contactId),
            'need' => 'Pedido confirmado',
        ]);
        (new OpportunityStatusService())->change($opportunityId, [
            'status'         => 'won',
            'finance_action' => 'record',
            'sale_amount'    => '125.00',
            'sale_date'      => '2026-08-18',
        ]);

        $missingIdentity = $this
            ->withSession($_SESSION)
            ->get('/app/clientes/oportunidades/' . $opportunityId . '/nota-venta');
        $missingIdentity->assertRedirectTo(
            '/app/clientes?section=opportunities',
        );

        try {
            (new SaleNoteService())->forOpportunity($opportunityId);
            $this->fail('A sale note without DNI/CI must not be generated.');
        } catch (SaleNoteUnavailableException $exception) {
            $this->assertStringContainsString('DNI/CI', $exception->getMessage());
        }

        $result = $this
            ->withSession($_SESSION)
            ->post('/app/clientes/oportunidades/' . $opportunityId . '/nota-venta', [
                'identity_document' => '  CI-0912345678  ',
                csrf_token()        => csrf_hash(),
            ]);

        $result->assertOK();
        $result->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('%PDF-', $result->getBody());
        $this->seeInDatabase('contacts', [
            'id'                => $contactId,
            'business_id'       => $businessId,
            'identity_document' => 'CI-0912345678',
        ]);
        $this->seeInDatabase('audit_events', [
            'business_id' => $businessId,
            'user_id'     => $user->id,
            'entity_type' => 'contact',
            'entity_id'   => $contactId,
            'action'      => 'updated',
        ]);
    }

    public function testSaleNoteIdentityCompletionRejectsInvalidAndForeignData(): void
    {
        $user             = $this->createUser('sale-note-identity-guard');
        $businessId       = $this->createBusiness('Negocio autorizado');
        $otherBusinessId  = $this->createBusiness('Negocio ajeno');
        $this->createCompleteProfile($businessId);
        $ownContactId     = $this->createContact($businessId, 'Cliente propio', 'client');
        $foreignContactId = $this->createContact($otherBusinessId, 'Cliente ajeno', 'client');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);
        $ownOpportunityId = $this->createOpportunity(
            $businessId,
            $ownContactId,
            'Venta propia',
            'won',
        );
        $foreignOpportunityId = $this->createOpportunity(
            $otherBusinessId,
            $foreignContactId,
            'Venta ajena',
            'won',
        );
        (new OpportunityStatusService())->change($ownOpportunityId, [
            'status'         => 'won',
            'finance_action' => 'record',
            'sale_amount'    => '100.00',
            'sale_date'      => '2026-08-18',
        ]);

        $invalid = $this
            ->withSession($_SESSION)
            ->post('/app/clientes/oportunidades/' . $ownOpportunityId . '/nota-venta', [
                'identity_document' => '',
                csrf_token()        => csrf_hash(),
            ]);
        $invalid->assertRedirectTo('/app/clientes?section=opportunities');
        $this->assertNull((new ContactModel())->find($ownContactId)['identity_document']);

        $foreign = $this
            ->withSession($_SESSION)
            ->post('/app/clientes/oportunidades/' . $foreignOpportunityId . '/nota-venta', [
                'identity_document' => 'CI-EXTERNA',
                csrf_token()        => csrf_hash(),
            ]);
        $foreign->assertStatus(403);
        $this->assertNull((new ContactModel())->find($foreignContactId)['identity_document']);
    }

    public function testSaleNoteRequiresARecordedSaleAndRejectsAnotherBusiness(): void
    {
        $user             = $this->createUser('sale-note-guard');
        $businessId       = $this->createBusiness('Negocio autorizado');
        $otherBusinessId  = $this->createBusiness('Negocio ajeno');
        $this->createCompleteProfile($businessId);
        $ownContactId     = $this->createContact($businessId, 'Cliente propio', 'client');
        $foreignContactId = $this->createContact($otherBusinessId, 'Cliente ajeno', 'client');
        $ownOpportunityId = $this->createOpportunity(
            $businessId,
            $ownContactId,
            'Venta sin registrar',
            'won',
        );
        $foreignOpportunityId = $this->createOpportunity(
            $otherBusinessId,
            $foreignContactId,
            'Venta ajena',
            'won',
        );
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $missingPosting = $this
            ->withSession($_SESSION)
            ->get('/app/clientes/oportunidades/' . $ownOpportunityId . '/nota-venta');
        $missingPosting->assertRedirectTo(
            '/app/clientes?section=opportunities',
        );

        try {
            (new SaleNoteService())->forOpportunity($ownOpportunityId);
            $this->fail('A won opportunity without a recorded sale must not create a note.');
        } catch (SaleNoteUnavailableException $exception) {
            $this->assertStringContainsString('Finanzas', $exception->getMessage());
        }

        $foreign = $this
            ->withSession($_SESSION)
            ->get('/app/clientes/oportunidades/' . $foreignOpportunityId . '/nota-venta');
        $foreign->assertStatus(403);
    }

    public function testOverviewIsBusinessIsolatedAndUsesExactOpenValue(): void
    {
        $user            = $this->createUser('overview');
        $businessId      = $this->createBusiness('Negocio autorizado');
        $otherBusinessId = $this->createBusiness('Negocio ajeno');
        $prospectId      = $this->createContact($businessId, 'Prospecto');
        $clientId        = $this->createContact($businessId, 'Cliente', 'client');
        $foreignContact  = $this->createContact($otherBusinessId, 'Contacto ajeno');
        $this->createOpportunity(
            $businessId,
            $prospectId,
            'Pastel de boda',
            'new',
            '0.10',
            '2000-01-01',
        );
        $this->createOpportunity(
            $businessId,
            $clientId,
            'Mesa dulce',
            'negotiation',
            '100.25',
            '2099-01-01',
        );
        $this->createOpportunity(
            $businessId,
            $clientId,
            'Evento cerrado',
            'won',
            '999.99',
            '2000-01-01',
        );
        $this->createOpportunity(
            $otherBusinessId,
            $foreignContact,
            'Oportunidad ajena',
            'new',
            '500.00',
            '2000-01-01',
        );
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $overview = (new CrmOverviewService())->overview();

        $this->assertCount(2, $overview['contacts']);
        $this->assertCount(3, $overview['opportunities']);
        $this->assertSame(1, $overview['crm_summary']['prospect_count']);
        $this->assertSame(1, $overview['crm_summary']['client_count']);
        $this->assertSame(2, $overview['crm_summary']['open_opportunity_count']);
        $this->assertSame(10035, $overview['crm_summary']['open_value_cents']);
        $this->assertSame(1, $overview['crm_summary']['overdue_follow_up_count']);
        $this->assertContains(
            'Prospecto',
            array_column($overview['contacts'], 'lifecycle_stage_label'),
        );
        $this->assertContains(
            'Nueva',
            array_column($overview['opportunities'], 'status_label'),
        );
    }

    public function testOverviewKeepsArchivedContactReferenceForClosedOpportunity(): void
    {
        $user       = $this->createUser('archived-reference');
        $businessId = $this->createBusiness('Negocio autorizado');
        $contactId  = $this->createContact($businessId, 'Contacto histórico', 'client');
        $opportunityId = $this->createOpportunity(
            $businessId,
            $contactId,
            'Venta completada',
            'won',
        );
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        (new ContactService())->archive($contactId);

        $overview = (new CrmOverviewService())->overview();
        $opportunity = array_values(array_filter(
            $overview['opportunities'],
            static fn (array $row): bool => (int) $row['id'] === $opportunityId,
        ))[0];

        $this->assertSame([], $overview['contacts']);
        $this->assertSame('Contacto histórico', $opportunity['contact']['display_name']);
        $this->assertTrue($opportunity['contact']['is_archived']);
    }

    public function testProductionControllerCreatesContactThroughProtectedRoute(): void
    {
        $user       = $this->createUser('controller-create');
        $businessId = $this->createBusiness('Negocio autorizado');
        $this->createCompleteProfile($businessId);
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $result = $this
            ->withSession($_SESSION)
            ->post('/app/clientes/contactos', [
                ...$this->contactPayload(),
                csrf_token() => csrf_hash(),
            ]);

        $result->assertRedirectTo('/app/clientes');
        $this->seeInDatabase('contacts', [
            'business_id'  => $businessId,
            'display_name' => 'María Pérez',
        ]);
        $this->seeInDatabase('audit_events', [
            'business_id' => $businessId,
            'entity_type' => 'contact',
            'action'      => 'created',
        ]);
    }

    public function testProductionContactUpdateChangesTheSameRecordWithoutDuplicatingIt(): void
    {
        $user       = $this->createUser('controller-update');
        $businessId = $this->createBusiness('Negocio autorizado');
        $contactId  = $this->createContact($businessId, 'Estudio Nido Eventos', 'client');
        $this->createCompleteProfile($businessId);
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $result = $this
            ->withSession($_SESSION)
            ->post('/app/clientes/contactos/' . $contactId, [
                ...$this->contactPayload(),
                'display_name'    => 'Estudio Nido Eventos',
                'contact_kind'    => 'organization',
                'lifecycle_stage' => 'prospect',
                csrf_token()      => csrf_hash(),
            ]);

        $result->assertRedirectTo('/app/clientes');
        $this->assertSame(1, (new ContactModel())->countAllResults());
        $this->seeInDatabase('contacts', [
            'id'              => $contactId,
            'business_id'     => $businessId,
            'display_name'    => 'Estudio Nido Eventos',
            'lifecycle_stage' => 'prospect',
        ]);
        $this->seeInDatabase('audit_events', [
            'business_id' => $businessId,
            'entity_type' => 'contact',
            'entity_id'   => $contactId,
            'action'      => 'updated',
        ]);
    }

    public function testContactMutationReturnsToTheSelectedCrmTab(): void
    {
        $user       = $this->createUser('controller-tab-return');
        $businessId = $this->createBusiness('Negocio autorizado');
        $this->createCompleteProfile($businessId);
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $result = $this
            ->withSession($_SESSION)
            ->post('/app/clientes/contactos', [
                ...$this->contactPayload(),
                'return_section' => 'contacts',
                csrf_token()     => csrf_hash(),
            ]);

        $result->assertRedirectTo('/app/clientes?section=contacts');
        $this->seeInDatabase('contacts', [
            'business_id'  => $businessId,
            'display_name' => 'María Pérez',
        ]);
    }

    public function testCrmUsesThePersistedPersonalViewInsteadOfTheLegacyQuery(): void
    {
        $user       = $this->createUser('controller-personal-crm-view');
        $businessId = $this->createBusiness('Negocio autorizado');
        $this->createCompleteProfile($businessId);
        $this->createMembership($user, $businessId);
        $this->createContact($businessId, 'Contacto propio');
        $this->assertTrue((new UserPreferenceModel())->saveForUser(
            (int) $user->id,
            'light',
            'tabs',
        ));
        $this->actingAs($user);

        $response = $this
            ->withSession($_SESSION)
            ->get('/app/clientes?view=combined&section=opportunities');

        $response->assertStatus(200);
        $this->assertStringContainsString('data-crm-view="tabs"', $response->getBody());
        $this->assertStringNotContainsString('data-crm-view-option', $response->getBody());
    }

    public function testCrmDefaultsToCombinedAndIgnoresALegacyTabbedQuery(): void
    {
        $user       = $this->createUser('controller-default-crm-view');
        $businessId = $this->createBusiness('Negocio autorizado');
        $this->createCompleteProfile($businessId);
        $this->createMembership($user, $businessId);
        $this->createContact($businessId, 'Contacto propio');
        $this->actingAs($user);

        $response = $this
            ->withSession($_SESSION)
            ->get('/app/clientes?view=tabs&section=opportunities');

        $response->assertStatus(200);
        $this->assertStringContainsString('data-crm-view="combined"', $response->getBody());
    }

    public function testCrmReturnLocationRejectsUnknownNavigationValues(): void
    {
        $this->assertSame(
            site_url('app/clientes'),
            CrmReturnLocation::fromInput([
                'return_section' => 'https://example.test',
            ]),
        );
        $this->assertSame(
            site_url('app/clientes') . '?section=opportunities',
            CrmReturnLocation::fromInput([
                'return_section' => 'opportunities',
            ]),
        );
    }

    public function testPreparedControllerRejectsCrossBusinessOpportunityUpdate(): void
    {
        $user             = $this->createUser('controller-cross-business');
        $businessId       = $this->createBusiness('Negocio autorizado');
        $otherBusinessId  = $this->createBusiness('Negocio ajeno');
        $foreignContactId = $this->createContact($otherBusinessId, 'Contacto ajeno');
        $foreignId = $this->createOpportunity(
            $otherBusinessId,
            $foreignContactId,
            'Oportunidad ajena',
        );
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $result = $this
            ->withRoutes([
                [
                    'POST',
                    '_test/crm/oportunidades/(:num)',
                    'OpportunityController::update/$1',
                ],
            ])
            ->withSession($_SESSION)
            ->post('/_test/crm/oportunidades/' . $foreignId, [
                ...$this->opportunityPayload($foreignContactId),
                csrf_token() => csrf_hash(),
            ]);

        $result->assertStatus(403);
        $this->assertSame(
            'new',
            (new OpportunityModel())->find($foreignId)['status'],
        );
        $this->assertSame(0, (new AuditEventModel())->countAllResults());
    }

    public function testProtectedStatusRouteUsesSessionBusinessAndServerValidation(): void
    {
        $user             = $this->createUser('status-controller');
        $businessId       = $this->createBusiness('Negocio autorizado');
        $otherBusinessId  = $this->createBusiness('Negocio ajeno');
        $contactId        = $this->createContact($businessId, 'Contacto propio');
        $foreignContactId = $this->createContact($otherBusinessId, 'Contacto ajeno');
        $opportunityId    = $this->createOpportunity($businessId, $contactId, 'Pedido propio');
        $foreignId        = $this->createOpportunity($otherBusinessId, $foreignContactId, 'Pedido ajeno');
        $this->createCompleteProfile($businessId);
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $success = $this
            ->withSession($_SESSION)
            ->post('/app/clientes/oportunidades/' . $opportunityId . '/estado', [
                'status'         => 'contacted',
                'finance_action' => 'none',
                'return_section' => 'opportunities',
                csrf_token()     => csrf_hash(),
            ]);

        $success->assertRedirectTo('/app/clientes?section=opportunities');
        $this->assertSame('contacted', (new OpportunityModel())->find($opportunityId)['status']);

        $denied = $this
            ->withSession($_SESSION)
            ->post('/app/clientes/oportunidades/' . $foreignId . '/estado', [
                'status'         => 'won',
                'finance_action' => 'none',
                csrf_token()     => csrf_hash(),
            ]);

        $denied->assertStatus(403);
        $this->assertSame('new', (new OpportunityModel())->find($foreignId)['status']);
    }

    public function testStatusRouteWithoutCsrfIsRejectedBeforeAnyChange(): void
    {
        $user          = $this->createUser('status-csrf');
        $businessId    = $this->createBusiness('Negocio protegido');
        $contactId     = $this->createContact($businessId, 'Contacto propio');
        $opportunityId = $this->createOpportunity($businessId, $contactId, 'Pedido propio');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $this->expectException(SecurityException::class);

        $this
            ->withSession($_SESSION)
            ->post('/app/clientes/oportunidades/' . $opportunityId . '/estado', [
                'status'         => 'won',
                'finance_action' => 'record',
                'sale_amount'    => '100.00',
                'sale_date'      => '2026-08-14',
            ]);
    }

    public function testPreparedControllerMutationWithoutCsrfIsRejected(): void
    {
        $user       = $this->createUser('controller-csrf');
        $businessId = $this->createBusiness('Negocio protegido');
        $this->createMembership($user, $businessId);
        $this->actingAs($user);

        $this->expectException(SecurityException::class);

        $this
            ->withRoutes([
                ['POST', '_test/crm/contactos', 'ContactController::create'],
            ])
            ->withSession($_SESSION)
            ->post('/_test/crm/contactos', $this->contactPayload());
    }

    private function createUser(string $suffix): User
    {
        $users = auth()->getProvider();
        $user  = new User([
            'username' => "crm-{$suffix}",
            'email'    => "{$suffix}@crm.test",
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

    private function createCompleteProfile(int $businessId): void
    {
        $profileId = (new BusinessProfileModel())->insert([
            'business_id'        => $businessId,
            'what_it_does'       => 'Brinda servicios a pequeños negocios.',
            'customers_served'   => 'Comercios locales.',
            'products_offered'   => 'Servicios de acompañamiento.',
            'objectives_summary' => 'Mejorar ventas y seguimiento.',
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

    private function createContact(
        int $businessId,
        string $name,
        string $stage = 'prospect',
    ): int {
        $contactId = (new ContactModel())->insert([
            'business_id'     => $businessId,
            'display_name'    => $name,
            'contact_kind'    => 'person',
            'lifecycle_stage' => $stage,
        ], true);

        $this->assertNotFalse($contactId);

        return (int) $contactId;
    }

    private function createOpportunity(
        int $businessId,
        int $contactId,
        string $need,
        string $status = 'new',
        string $estimatedValue = '100.00',
        ?string $followUpDate = null,
    ): int {
        $opportunityId = (new OpportunityModel())->insert([
            'business_id'         => $businessId,
            'contact_id'          => $contactId,
            'need'                => $need,
            'status'              => $status,
            'estimated_value'     => $estimatedValue,
            'next_follow_up_date' => $followUpDate,
        ], true);

        $this->assertNotFalse($opportunityId);

        return (int) $opportunityId;
    }

    /**
     * @return array<string, string>
     */
    private function contactPayload(): array
    {
        return [
            'display_name'        => 'María Pérez',
            'contact_kind'        => 'person',
            'lifecycle_stage'     => 'prospect',
            'acquisition_channel' => 'referral',
            'email'               => 'maria@example.test',
            'phone'               => '+593 99 000 0000',
            'notes'               => 'Busca un pastel de boda.',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function opportunityPayload(int $contactId): array
    {
        return [
            'contact_id'          => (string) $contactId,
            'need'                => 'Pastel de boda',
            'status'              => 'new',
            'estimated_value'     => '420.00',
            'next_follow_up_date' => '2026-08-12',
            'notes'               => 'Solicitó una propuesta.',
        ];
    }
}
