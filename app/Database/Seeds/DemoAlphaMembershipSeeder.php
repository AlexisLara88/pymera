<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Domain\BusinessRoleCatalog;
use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;
use RuntimeException;
use Throwable;

/**
 * Associates the pre-existing demo account with Dulce Barrio.
 *
 * This seeder never creates users or credentials.
 */
class DemoAlphaMembershipSeeder extends Seeder
{
    public function run(): void
    {
        $identity = $this->db
            ->table('auth_identities')
            ->select('user_id')
            ->where('type', 'email_password')
            ->where('secret', DemoAlphaAccountSeeder::DEMO_EMAIL)
            ->get()
            ->getRowArray();
        $business = $this->db
            ->table('businesses')
            ->select('id')
            ->where('name', 'Dulce Barrio')
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if ($identity === null) {
            throw new RuntimeException('La cuenta demostrativa todavía no existe.');
        }

        if ($business === null) {
            throw new RuntimeException('Dulce Barrio todavía no fue cargado.');
        }

        $userId = (int) $identity['user_id'];
        $businessId = (int) $business['id'];
        $now = Time::now('UTC')->toDateTimeString();
        $existing = $this->db
            ->table('business_users')
            ->select('id')
            ->where('user_id', $userId)
            ->where('business_id', $businessId)
            ->get()
            ->getRowArray();
        $payload = [
            'user_id'     => $userId,
            'business_id' => $businessId,
            'role_code'   => BusinessRoleCatalog::OWNER,
            'status'      => 'active',
            'updated_at'  => $now,
            'deleted_at'  => null,
        ];

        $this->db->transException(true);

        try {
            if (! $this->db->transBegin()) {
                throw new RuntimeException('No fue posible iniciar la asociación demostrativa.');
            }

            $builder = $this->db->table('business_users');

            if ($existing === null) {
                $payload['created_at'] = $now;

                if (! $builder->insert($payload)) {
                    throw new RuntimeException('No fue posible crear la asociación demostrativa.');
                }
            } elseif (! $builder->where('id', $existing['id'])->update($payload)) {
                throw new RuntimeException('No fue posible actualizar la asociación demostrativa.');
            }

            if (! $this->db->transCommit()) {
                throw new RuntimeException('No fue posible confirmar la asociación demostrativa.');
            }
        } catch (Throwable $exception) {
            $this->db->transRollback();

            throw $exception;
        }
    }
}
