<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBusinessProfiles extends Migration
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
            'what_it_does' => [
                'type' => 'TEXT',
            ],
            'customers_served' => [
                'type' => 'TEXT',
            ],
            'products_offered' => [
                'type' => 'TEXT',
            ],
            'objectives_summary' => [
                'type' => 'TEXT',
            ],
            'differentiator' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'differentiation_delivery' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'customer_outcome' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'purchase_reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'acquisition_channels' => [
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
        $this->forge->addUniqueKey('business_id');
        $this->forge->addKey('deleted_at');
        $this->forge->addForeignKey('business_id', 'businesses', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('business_profiles', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('business_profiles', true);
    }
}
