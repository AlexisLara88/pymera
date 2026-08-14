<?php

declare(strict_types=1);

namespace App\Commands;

use App\Services\AccountProvisioningService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

final class CreatePlatformAdministrator extends BaseCommand
{
    protected $group = 'PyMERA';
    protected $name = 'erp:user:create-admin';
    protected $description = 'Crea una cuenta personal de administración de plataforma.';
    protected $usage = 'erp:user:create-admin';

    public function run(array $params): int
    {
        $email = CLI::prompt('Correo', null, 'required|valid_email');
        $username = CLI::prompt('Nombre de usuario', null, 'required|min_length[3]|max_length[30]');
        $password = CLI::prompt('Contraseña', null, 'required|min_length[8]');
        $confirmation = CLI::prompt('Confirmar contraseña', null, 'required|min_length[8]');

        if ($password !== $confirmation) {
            CLI::error('Las contraseñas no coinciden.');

            return EXIT_ERROR;
        }

        try {
            $userId = (new AccountProvisioningService())->createPlatformAdministrator(
                $email,
                $username,
                $password,
            );
        } catch (Throwable $exception) {
            CLI::error($exception->getMessage());

            return EXIT_ERROR;
        }

        CLI::write('Administrador de plataforma creado correctamente.', 'green');
        CLI::write('Usuario: ' . $userId);

        return EXIT_SUCCESS;
    }
}
