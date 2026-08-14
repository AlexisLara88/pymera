<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateContacts extends Migration
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
            'display_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 160,
            ],
            'contact_kind' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'lifecycle_stage' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'prospect',
            ],
            'acquisition_channel' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'null'       => true,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 254,
                'null'       => true,
            ],
            'phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'null'       => true,
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
        $this->forge->addUniqueKey(['business_id', 'id']);
        $this->forge->addKey(['business_id', 'lifecycle_stage']);
        $this->forge->addKey(['business_id', 'acquisition_channel']);
        $this->forge->addKey('deleted_at');
        $this->forge->addForeignKey('business_id', 'businesses', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('contacts', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('contacts', true);
    }
}
