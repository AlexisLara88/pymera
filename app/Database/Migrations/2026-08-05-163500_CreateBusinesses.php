<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBusinesses extends Migration
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
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 160,
            ],
            'currency_code' => [
                'type'       => 'CHAR',
                'constraint' => 3,
            ],
            'timezone' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'active',
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
        $this->forge->addKey('status');
        $this->forge->addKey('deleted_at');
        $this->forge->createTable('businesses', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('businesses', true);
    }
}
