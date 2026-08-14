<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class CreateCrmFinancialPostings extends Migration
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
            'opportunity_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'financial_daily_entry_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'sale_date' => [
                'type' => 'DATE',
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,2',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'recorded',
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('opportunity_id');
        $this->forge->addKey(['business_id', 'sale_date', 'status']);
        $this->forge->addKey(['financial_daily_entry_id', 'status']);
        $this->forge->addForeignKey('business_id', 'businesses', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('opportunity_id', 'opportunities', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey(
            'financial_daily_entry_id',
            'financial_daily_entries',
            'id',
            'RESTRICT',
            'RESTRICT',
        );
        $this->forge->createTable('crm_financial_postings', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('crm_financial_postings', true);
    }
}
