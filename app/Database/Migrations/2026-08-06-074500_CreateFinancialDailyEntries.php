<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinancialDailyEntries extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'business_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'operation_date' => [
                'type' => 'DATE',
            ],
            'income_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,2',
                'default'    => 0,
            ],
            'fixed_expense_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,2',
                'default'    => 0,
            ],
            'variable_expense_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,2',
                'default'    => 0,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'recorded',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['business_id', 'operation_date']);
        $this->forge->addKey(['business_id', 'operation_date', 'status']);
        $this->forge->addForeignKey('business_id', 'businesses', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('financial_daily_entries', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('financial_daily_entries', true);
    }
}
