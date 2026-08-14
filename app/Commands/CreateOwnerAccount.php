<?php

declare(strict_types=1);

namespace App\Commands;

use App\Services\AccountProvisioningService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

final class CreateOwnerAccount extends BaseCommand
{
    protected $group = 'PyMERA';
    protected $name = 'erp:user:create-owner';
    protected $description = 'Crea una cuenta propietaria, su negocio y la asociación owner.';
    protected $usage = 'erp:user:create-owner';

    public function run(array $params): int
    {
        $email = CLI::prompt('Correo', null, 'required|valid_email');
        $username = CLI::prompt('Nombre de usuario', null, 'required|min_length[3]|max_length[30]');
        $businessName = CLI::prompt('Nombre del negocio', null, 'required|max_length[120]');
        $currencyCode = CLI::prompt('Moneda ISO', null, 'required|exact_length[3]');
        $timezone = CLI::prompt('Zona horaria', null, 'required');
        $password = CLI::prompt('Contraseña', null, 'required|min_length[8]');
        $confirmation = CLI::prompt('Confirmar contraseña', null, 'required|min_length[8]');

        if ($password !== $confirmation) {
            CLI::error('Las contraseñas no coinciden.');

            return EXIT_ERROR;
        }

        try {
            $result = (new AccountProvisioningService())->createOwnerWithBusiness(
                $email,
                $username,
                $password,
                $businessName,
                $currencyCode,
                $timezone,
            );
        } catch (Throwable $exception) {
            CLI::error($exception->getMessage());

            return EXIT_ERROR;
        }

        CLI::write('Cuenta propietaria creada correctamente.', 'green');
        CLI::write('Usuario: ' . $result['user_id']);
        CLI::write('Negocio: ' . $result['business_id']);

        return EXIT_SUCCESS;
    }
}
