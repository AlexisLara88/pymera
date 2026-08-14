<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAdministrativeExpenseToFinancialDailyEntries extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('financial_daily_entries', [
            'administrative_expense_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,2',
                'default'    => 0,
                'after'      => 'variable_expense_amount',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('financial_daily_entries', 'administrative_expense_amount');
    }
}
