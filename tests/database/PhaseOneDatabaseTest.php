<?php

use App\Database\Seeds\DemoAlphaSeeder;
use App\Models\ActivityModel;
use App\Models\BusinessModel;
use App\Models\BusinessProfileModel;
use App\Models\FinancialDailyEntryModel;
use App\Models\ObjectiveModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class PhaseOneDatabaseTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = [
        'CodeIgniter\Shield',
        'App',
    ];
    protected $basePath  = APPPATH . 'Database';
    protected $seed      = DemoAlphaSeeder::class;

    public function testPhaseOneSchemaIsCreated(): void
    {
        foreach ([
            'businesses',
            'business_profiles',
            'objectives',
            'activities',
            'financial_daily_entries',
        ] as $table) {
            $this->assertTrue($this->db->tableExists($table), "Missing table: {$table}");
        }

        $this->assertTrue($this->db->fieldExists(
            'administrative_expense_amount',
            'financial_daily_entries',
        ));
    }

    public function testDemoSeederCreatesTheCompleteInitialJourney(): void
    {
        $business = (new BusinessModel())->where('name', 'Dulce Barrio')->first();

        $this->assertNotNull($business);
        $this->assertSame('USD', $business['currency_code']);
        $this->assertSame('America/Guayaquil', $business['timezone']);

        $profile = (new BusinessProfileModel())
            ->where('business_id', $business['id'])
            ->first();
        $objectives = (new ObjectiveModel())
            ->where('business_id', $business['id'])
            ->findAll();
        $activities = (new ActivityModel())->findForBusiness((int) $business['id']);
        $entries = (new FinancialDailyEntryModel())
            ->where('business_id', $business['id'])
            ->orderBy('operation_date', 'ASC')
            ->findAll();

        $this->assertNotNull($profile);
        $this->assertStringContainsString('Pastelería artesanal de Quito', $profile['what_it_does']);
        $this->assertCount(4, $objectives);
        $this->assertCount(16, $activities);
        $this->assertCount(6, $entries);
        $categories = array_values(array_unique(array_column($objectives, 'category')));
        $statuses = array_values(array_unique(array_column($activities, 'status')));
        sort($categories);
        sort($statuses);

        $this->assertSame(
            ['commercial', 'financial', 'improvement', 'operational'],
            $categories,
        );
        $this->assertSame(
            ['cancelled', 'completed', 'in_progress', 'pending'],
            $statuses,
        );
        $this->assertSame('2026-08-01', $entries[0]['operation_date']);
        $this->assertSame('2026-08-06', $entries[5]['operation_date']);
    }

    public function testDemoSeederCreatesCoherentFinancialTotals(): void
    {
        $entries = (new FinancialDailyEntryModel())
            ->where('status', 'recorded')
            ->findAll();
        $sales = $this->sumCents($entries, 'income_amount');
        $operating = $this->sumCents($entries, 'fixed_expense_amount');
        $costOfSales = $this->sumCents($entries, 'variable_expense_amount');
        $administrative = $this->sumCents($entries, 'administrative_expense_amount');
        $grossProfit = $sales - $costOfSales;
        $ebitda = $grossProfit - $operating - $administrative;

        $this->assertSame(865000, $sales);
        $this->assertSame(348000, $costOfSales);
        $this->assertSame(517000, $grossProfit);
        $this->assertSame(209000, $operating);
        $this->assertSame(61000, $administrative);
        $this->assertSame(247000, $ebitda);
    }

    public function testDemoSeederDoesNotDuplicateItsJourney(): void
    {
        $this->seed(DemoAlphaSeeder::class);

        $this->assertSame(1, (new BusinessModel())->where('name', 'Dulce Barrio')->countAllResults());
        $this->assertSame(1, (new BusinessProfileModel())->countAllResults());
        $this->assertSame(4, (new ObjectiveModel())->countAllResults());
        $this->assertSame(16, (new ActivityModel())->countAllResults());
        $this->assertSame(6, (new FinancialDailyEntryModel())->countAllResults());
        $this->assertSame(0, $this->db->table('business_users')->countAllResults());
        $this->assertSame(0, $this->db->table('audit_events')->countAllResults());
    }

    public function testDemoSeederRepairsMissingAndSoftDeletedCanonicalData(): void
    {
        $business = (new BusinessModel())->where('name', 'Dulce Barrio')->first();
        $objective = (new ObjectiveModel())
            ->where('business_id', $business['id'])
            ->where('category', 'commercial')
            ->first();

        $this->assertTrue((new ObjectiveModel())->delete($objective['id']));
        $this->db
            ->table('financial_daily_entries')
            ->where('business_id', $business['id'])
            ->where('operation_date', '2026-08-03')
            ->delete();
        $this->db
            ->table('business_profiles')
            ->where('business_id', $business['id'])
            ->update(['what_it_does' => 'Contenido incompleto']);

        $this->seed(DemoAlphaSeeder::class);

        $restoredObjective = (new ObjectiveModel())
            ->where('business_id', $business['id'])
            ->where('category', 'commercial')
            ->first();
        $profile = (new BusinessProfileModel())
            ->where('business_id', $business['id'])
            ->first();

        $this->assertNotNull($restoredObjective);
        $this->assertStringContainsString('Pastelería artesanal de Quito', $profile['what_it_does']);
        $this->assertSame(4, (new ObjectiveModel())->countAllResults());
        $this->assertSame(16, (new ActivityModel())->countAllResults());
        $this->assertSame(6, (new FinancialDailyEntryModel())->countAllResults());
    }

    public function testObjectiveSoftDeletePreservesTheRow(): void
    {
        $model = new ObjectiveModel();
        $objective = $model->first();

        $this->assertNotNull($objective);
        $this->assertTrue($model->delete($objective['id']));
        $this->assertNull($model->find($objective['id']));
        $this->assertNotNull($model->withDeleted()->find($objective['id']));
    }

    public function testObjectiveRejectsAnUnknownStatus(): void
    {
        $business = (new BusinessModel())->first();
        $model = new ObjectiveModel();

        $result = $model->insert([
            'business_id' => $business['id'],
            'title'       => 'Objetivo inválido',
            'status'      => 'unknown',
        ]);

        $this->assertFalse($result);
        $this->assertArrayHasKey('status', $model->errors());
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function sumCents(array $rows, string $field): int
    {
        $total = 0;

        foreach ($rows as $row) {
            $amount = number_format((float) $row[$field], 2, '.', '');
            [$whole, $fraction] = explode('.', $amount);
            $total += ((int) $whole * 100) + (int) $fraction;
        }

        return $total;
    }
}
