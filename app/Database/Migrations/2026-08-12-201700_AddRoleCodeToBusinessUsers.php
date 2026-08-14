<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddRoleCodeToBusinessUsers extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('business_users', [
            'role_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'owner',
                'null'       => false,
                'after'      => 'business_id',
            ],
        ]);

        $this->db->table('business_users')->update(['role_code' => 'owner']);
    }

    public function down(): void
    {
        $this->forge->dropColumn('business_users', 'role_code');
    }
}
