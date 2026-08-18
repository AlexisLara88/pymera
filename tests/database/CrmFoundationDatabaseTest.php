<?php

declare(strict_types=1);

use App\Models\BusinessModel;
use App\Models\ContactModel;
use App\Models\OpportunityModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class CrmFoundationDatabaseTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = [
        'CodeIgniter\Shield',
        'App',
    ];

    public function testCrmFoundationSchemaIsCreatedWithoutDemoData(): void
    {
        $this->assertTrue($this->db->tableExists('contacts'));
        $this->assertTrue($this->db->tableExists('opportunities'));
        $this->assertSame(0, $this->db->table('contacts')->countAllResults());
        $this->assertSame(0, $this->db->table('opportunities')->countAllResults());

        $contactFields = array_column($this->db->getFieldData('contacts'), 'name');
        $opportunityFields = array_column($this->db->getFieldData('opportunities'), 'name');

        $this->assertContains('lifecycle_stage', $contactFields);
        $this->assertContains('acquisition_channel', $contactFields);
        $this->assertContains('identity_document', $contactFields);
        $this->assertContains('contact_id', $opportunityFields);
        $this->assertContains('estimated_value', $opportunityFields);
        $this->assertContains('next_follow_up_date', $opportunityFields);
    }

    public function testContactModelPersistsAndSoftDeletesAValidContact(): void
    {
        $businessId = $this->createBusiness('Negocio con contacto');
        $model = new ContactModel();
        $contactId = $model->insert([
            'business_id'         => $businessId,
            'display_name'        => 'María Pérez',
            'contact_kind'        => 'person',
            'lifecycle_stage'     => 'prospect',
            'acquisition_channel' => 'referral',
            'email'               => 'maria@example.test',
            'phone'               => '+593 99 000 0000',
            'identity_document'   => 'CI-1712345678',
            'notes'               => 'Busca un pastel de boda.',
        ], true);

        $this->assertNotFalse($contactId);

        $contact = $model->findOwned((int) $contactId, $businessId);
        $this->assertNotNull($contact);
        $this->assertSame('María Pérez', $contact['display_name']);
        $this->assertSame('prospect', $contact['lifecycle_stage']);
        $this->assertSame('referral', $contact['acquisition_channel']);
        $this->assertSame('CI-1712345678', $contact['identity_document']);

        $this->assertTrue($model->delete($contactId));
        $this->assertNull($model->find($contactId));
        $this->assertNotNull($model->withDeleted()->find($contactId));
    }

    public function testContactModelRejectsUnknownCatalogValuesAndInvalidEmail(): void
    {
        $businessId = $this->createBusiness('Negocio con validación');
        $model = new ContactModel();

        $result = $model->insert([
            'business_id'         => $businessId,
            'display_name'        => 'Contacto inválido',
            'contact_kind'        => 'robot',
            'lifecycle_stage'     => 'subscriber',
            'acquisition_channel' => 'telepathy',
            'email'               => 'not-an-email',
            'identity_document'   => str_repeat('1', 41),
        ]);

        $this->assertFalse($result);
        $this->assertArrayHasKey('contact_kind', $model->errors());
        $this->assertArrayHasKey('lifecycle_stage', $model->errors());
        $this->assertArrayHasKey('acquisition_channel', $model->errors());
        $this->assertArrayHasKey('email', $model->errors());
        $this->assertArrayHasKey('identity_document', $model->errors());
    }

    public function testOpportunityModelPersistsAndSoftDeletesAValidOpportunity(): void
    {
        $businessId = $this->createBusiness('Negocio con oportunidad');
        $contactId = $this->createContact($businessId, 'Empresa Andina');
        $model = new OpportunityModel();
        $opportunityId = $model->insert([
            'business_id'         => $businessId,
            'contact_id'          => $contactId,
            'need'                => 'Cumpleaños corporativo',
            'status'              => 'proposal_sent',
            'estimated_value'     => '420.00',
            'next_follow_up_date' => '2026-08-12',
            'notes'               => 'Propuesta enviada por correo.',
        ], true);

        $this->assertNotFalse($opportunityId);

        $opportunity = $model->findOwned((int) $opportunityId, $businessId);
        $this->assertNotNull($opportunity);
        $this->assertSame($contactId, (int) $opportunity['contact_id']);
        $this->assertSame('proposal_sent', $opportunity['status']);
        $this->assertSame(
            '420.00',
            number_format((float) $opportunity['estimated_value'], 2, '.', ''),
        );
        $this->assertSame('2026-08-12', $opportunity['next_follow_up_date']);

        $this->assertTrue($model->delete($opportunityId));
        $this->assertNull($model->find($opportunityId));
        $this->assertNotNull($model->withDeleted()->find($opportunityId));
    }

    public function testOpportunityModelRejectsInvalidStatusAmountAndDate(): void
    {
        $businessId = $this->createBusiness('Negocio con oportunidad inválida');
        $contactId = $this->createContact($businessId, 'Contacto válido');
        $model = new OpportunityModel();

        $result = $model->insert([
            'business_id'         => $businessId,
            'contact_id'          => $contactId,
            'need'                => 'Solicitud inválida',
            'status'              => 'forgotten',
            'estimated_value'     => '-1.00',
            'next_follow_up_date' => 'mañana',
        ]);

        $this->assertFalse($result);
        $this->assertArrayHasKey('status', $model->errors());
        $this->assertArrayHasKey('estimated_value', $model->errors());
        $this->assertArrayHasKey('next_follow_up_date', $model->errors());
    }

    public function testModelsOnlyReturnRecordsFromTheRequestedBusiness(): void
    {
        $firstBusinessId = $this->createBusiness('Negocio uno');
        $secondBusinessId = $this->createBusiness('Negocio dos');
        $firstContactId = $this->createContact($firstBusinessId, 'Contacto uno');
        $secondContactId = $this->createContact($secondBusinessId, 'Contacto dos');
        $firstOpportunityId = $this->createOpportunity(
            $firstBusinessId,
            $firstContactId,
            'Pastel de boda',
        );
        $secondOpportunityId = $this->createOpportunity(
            $secondBusinessId,
            $secondContactId,
            'Mesa dulce',
        );

        $contacts = (new ContactModel())->findForBusiness($firstBusinessId);
        $opportunities = (new OpportunityModel())->findForBusiness($firstBusinessId);
        $contactOpportunities = (new OpportunityModel())
            ->findForContact($firstContactId, $firstBusinessId);

        $this->assertSame([$firstContactId], $this->ids($contacts));
        $this->assertSame([$firstOpportunityId], $this->ids($opportunities));
        $this->assertSame([$firstOpportunityId], $this->ids($contactOpportunities));
        $this->assertNotContains($secondContactId, $this->ids($contacts));
        $this->assertNotContains($secondOpportunityId, $this->ids($opportunities));
        $this->assertNull((new ContactModel())->findOwned($secondContactId, $firstBusinessId));
        $this->assertNull((new OpportunityModel())->findOwned($secondOpportunityId, $firstBusinessId));
    }

    public function testContactsAreOrderedByCreationDateAndNotByLastEdition(): void
    {
        $businessId = $this->createBusiness('Negocio ordenado');
        $olderId = $this->createContact($businessId, 'Contacto anterior');
        $newerId = $this->createContact($businessId, 'Contacto reciente');

        $this->db->table('contacts')->where('id', $olderId)->update([
            'created_at' => '2026-08-01 10:00:00',
            'updated_at' => '2026-08-13 10:00:00',
        ]);
        $this->db->table('contacts')->where('id', $newerId)->update([
            'created_at' => '2026-08-12 10:00:00',
            'updated_at' => '2026-08-12 10:00:00',
        ]);

        $this->assertSame(
            [$newerId, $olderId],
            $this->ids((new ContactModel())->findForBusiness($businessId)),
        );
    }

    public function testCompositeForeignKeyRejectsAContactFromAnotherBusiness(): void
    {
        $firstBusinessId = $this->createBusiness('Negocio autorizado');
        $secondBusinessId = $this->createBusiness('Negocio ajeno');
        $foreignContactId = $this->createContact($secondBusinessId, 'Contacto ajeno');
        $now = Time::now('UTC')->toDateTimeString();

        $this->expectException(DatabaseException::class);

        $this->db->table('opportunities')->insert([
            'business_id'         => $firstBusinessId,
            'contact_id'          => $foreignContactId,
            'need'                => 'No debe persistirse',
            'status'              => 'new',
            'estimated_value'     => '100.00',
            'next_follow_up_date' => '2026-08-12',
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);
    }

    public function testForeignKeyPreventsPhysicalDeletionOfAReferencedContact(): void
    {
        $businessId = $this->createBusiness('Negocio con referencia');
        $contactId = $this->createContact($businessId, 'Contacto referenciado');
        $this->createOpportunity($businessId, $contactId, 'Servicio activo');

        $this->expectException(DatabaseException::class);

        $this->db
            ->table('contacts')
            ->where('id', $contactId)
            ->delete();
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

    private function createContact(int $businessId, string $name): int
    {
        $contactId = (new ContactModel())->insert([
            'business_id'     => $businessId,
            'display_name'    => $name,
            'contact_kind'    => 'person',
            'lifecycle_stage' => 'prospect',
        ], true);

        $this->assertNotFalse($contactId);

        return (int) $contactId;
    }

    private function createOpportunity(int $businessId, int $contactId, string $need): int
    {
        $opportunityId = (new OpportunityModel())->insert([
            'business_id'     => $businessId,
            'contact_id'      => $contactId,
            'need'            => $need,
            'status'          => 'new',
            'estimated_value' => '100.00',
        ], true);

        $this->assertNotFalse($opportunityId);

        return (int) $opportunityId;
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<int>
     */
    private function ids(array $rows): array
    {
        return array_map(
            static fn (array $row): int => (int) $row['id'],
            $rows,
        );
    }
}
