<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class RequireExplicitBusinessUserRole extends Migration
{
    public function up(): void
    {
        if ($this->db->DBDriver === 'SQLite3') {
            return;
        }

        $this->forge->modifyColumn('business_users', [
            'role_code' => [
                'name'       => 'role_code',
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => false,
            ],
        ]);
    }

    public function down(): void
    {
        if ($this->db->DBDriver === 'SQLite3') {
            return;
        }

        $this->forge->modifyColumn('business_users', [
            'role_code' => [
                'name'       => 'role_code',
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'owner',
                'null'       => false,
            ],
        ]);
    }
}
