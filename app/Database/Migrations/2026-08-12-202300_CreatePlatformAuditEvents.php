<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class CreatePlatformAuditEvents extends Migration
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
            'actor_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'subject_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
            ],
            'subject_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
            ],
            'occurred_at' => [
                'type' => 'DATETIME',
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['actor_user_id', 'occurred_at'], false, false, 'idx_platform_audit_actor_time');
        $this->forge->addKey(['subject_type', 'subject_id'], false, false, 'idx_platform_audit_subject');
        $this->forge->addKey('occurred_at', false, false, 'idx_platform_audit_occurred_at');
        $this->forge->addForeignKey('actor_user_id', 'users', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('platform_audit_events', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('platform_audit_events', true);
    }
}
