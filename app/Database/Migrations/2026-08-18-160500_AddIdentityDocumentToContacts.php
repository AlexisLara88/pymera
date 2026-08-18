<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddIdentityDocumentToContacts extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('contacts', [
            'identity_document' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'null'       => true,
                'after'      => 'phone',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('contacts', 'identity_document');
    }
}
