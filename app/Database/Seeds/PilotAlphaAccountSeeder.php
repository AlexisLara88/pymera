<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Services\PilotAccountProvisioner;
use CodeIgniter\Database\Seeder;

/** Creates the empty-but-usable account for guided alpha validation. */
final class PilotAlphaAccountSeeder extends Seeder
{
    public const PILOT_EMAIL = 'piloto@demo.com';
    public const PILOT_PASSWORD = 'piloto12345';
    public const PILOT_BUSINESS = 'Negocio piloto';

    public function run(): void
    {
        (new PilotAccountProvisioner($this->db))->provision(
            email: self::PILOT_EMAIL,
            username: 'pilotopyme',
            password: self::PILOT_PASSWORD,
            businessName: self::PILOT_BUSINESS,
            currencyCode: 'USD',
            timezone: 'America/Guayaquil',
        );
    }
}
