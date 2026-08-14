<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOpportunities extends Migration
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
            'contact_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'need' => [
                'type'       => 'VARCHAR',
                'constraint' => 180,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'new',
            ],
            'estimated_value' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,2',
                'null'       => true,
            ],
            'next_follow_up_date' => [
                'type' => 'DATE',
                'null' => true,
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
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['business_id', 'status']);
        $this->forge->addKey(['contact_id', 'status']);
        $this->forge->addKey(['business_id', 'next_follow_up_date']);
        $this->forge->addKey('deleted_at');
        $this->forge->addForeignKey('business_id', 'businesses', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey(
            ['business_id', 'contact_id'],
            'contacts',
            ['business_id', 'id'],
            'RESTRICT',
            'RESTRICT',
        );
        $this->forge->createTable('opportunities', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('opportunities', true);
    }
}
