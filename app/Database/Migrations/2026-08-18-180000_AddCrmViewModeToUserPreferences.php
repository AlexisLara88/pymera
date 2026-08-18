<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddCrmViewModeToUserPreferences extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('user_preferences', [
            'crm_view_mode' => [
                'type'       => 'VARCHAR',
                'constraint' => 16,
                'default'    => 'combined',
                'null'       => false,
                'after'      => 'appearance_theme',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('user_preferences', 'crm_view_mode');
    }
}
