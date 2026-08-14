<?php

declare(strict_types=1);

use App\Models\BusinessModel;
use App\Models\ContactModel;
use App\Models\CrmFinancialPostingModel;
use App\Models\FinancialDailyEntryModel;
use App\Models\OpportunityModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/** @internal */
final class CrmFinancialPostingDatabaseTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = [
        'CodeIgniter\Shield',
        'App',
    ];

    public function testPostingSchemaAndBusinessScopedQueries(): void
    {
        $this->assertTrue($this->db->tableExists('crm_financial_postings'));

        foreach ([
            'business_id',
            'opportunity_id',
            'financial_daily_entry_id',
            'sale_date',
            'amount',
            'status',
        ] as $field) {
            $this->assertTrue($this->db->fieldExists($field, 'crm_financial_postings'));
        }

        [$businessId, $opportunityId, $entryId] = $this->createContext('Propio');
        [$otherBusinessId] = $this->createContext('Ajeno');
        $model = new CrmFinancialPostingModel();
        $postingId = $model->insert([
            'business_id'             => $businessId,
            'opportunity_id'          => $opportunityId,
            'financial_daily_entry_id' => $entryId,
            'sale_date'               => '2026-08-14',
            'amount'                  => '125.50',
            'status'                  => 'recorded',
        ], true);

        $this->assertIsInt($postingId);
        $this->assertNotNull($model->findForOpportunity($opportunityId, $businessId));
        $this->assertNull($model->findForOpportunity($opportunityId, $otherBusinessId));
        $this->assertCount(1, $model->findRecordedForEntry($entryId, $businessId));
        $this->assertCount(1, $model->findRecordedForBusinessPeriod(
            $businessId,
            '2026-08-01',
            '2026-08-31',
        ));
    }

    public function testPostingRejectsInvalidStatusAndZeroAmount(): void
    {
        [$businessId, $opportunityId, $entryId] = $this->createContext('Validación');
        $model = new CrmFinancialPostingModel();

        $result = $model->insert([
            'business_id'             => $businessId,
            'opportunity_id'          => $opportunityId,
            'financial_daily_entry_id' => $entryId,
            'sale_date'               => '2026-08-14',
            'amount'                  => '0.00',
            'status'                  => 'unknown',
        ]);

        $this->assertFalse($result);
        $this->assertArrayHasKey('amount', $model->errors());
        $this->assertArrayHasKey('status', $model->errors());
    }

    /** @return array{0: int, 1: int, 2: int} */
    private function createContext(string $suffix): array
    {
        $businessId = (int) (new BusinessModel())->insert([
            'name'          => "Negocio {$suffix}",
            'currency_code' => 'USD',
            'timezone'      => 'America/Guayaquil',
            'status'        => 'active',
        ], true);
        $contactId = (int) (new ContactModel())->insert([
            'business_id'        => $businessId,
            'display_name'       => "Contacto {$suffix}",
            'contact_kind'       => 'person',
            'lifecycle_stage'    => 'client',
            'acquisition_channel' => 'direct',
        ], true);
        $opportunityId = (int) (new OpportunityModel())->insert([
            'business_id'     => $businessId,
            'contact_id'      => $contactId,
            'need'            => "Pedido {$suffix}",
            'status'          => 'won',
            'estimated_value' => '125.50',
        ], true);
        $entryId = (int) (new FinancialDailyEntryModel())->insert([
            'business_id'                    => $businessId,
            'operation_date'                 => '2026-08-14',
            'income_amount'                  => '125.50',
            'fixed_expense_amount'           => '0.00',
            'variable_expense_amount'        => '0.00',
            'administrative_expense_amount'  => '0.00',
            'status'                         => 'recorded',
        ], true);

        return [$businessId, $opportunityId, $entryId];
    }
}
