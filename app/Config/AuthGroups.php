<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

/**
 * Global platform groups and permissions.
 *
 * Business roles live in business_users and must never be represented here.
 */
class AuthGroups extends ShieldAuthGroups
{
    public string $defaultGroup = 'alpha';

    /**
     * @var array<string, array<string, string>>
     */
    public array $groups = [
        'alpha' => [
            'title'       => 'Acceso alfa',
            'description' => 'Acceso técnico al producto funcional.',
        ],
        'platform_admin' => [
            'title'       => 'Administrador de plataforma',
            'description' => 'Administra cuentas, negocios y relaciones globales de la plataforma.',
        ],
    ];

    /**
     * @var array<string, string>
     */
    public array $permissions = [
        'app.access'                  => 'Ingresar al producto funcional.',
        'platform.access'             => 'Ingresar a la administración de plataforma.',
        'platform.accounts.view'      => 'Consultar cuentas de la plataforma.',
        'platform.accounts.create'    => 'Crear cuentas de la plataforma.',
        'platform.accounts.update'    => 'Actualizar cuentas de la plataforma.',
        'platform.accounts.disable'   => 'Desactivar cuentas de la plataforma.',
        'platform.businesses.view'    => 'Consultar negocios de la plataforma.',
        'platform.businesses.create'  => 'Crear negocios de la plataforma.',
        'platform.businesses.update'  => 'Actualizar negocios de la plataforma.',
        'platform.memberships.manage' => 'Administrar relaciones entre cuentas y negocios.',
        'platform.audit.view'         => 'Consultar la auditoría administrativa.',
    ];

    /**
     * @var array<string, list<string>>
     */
    public array $matrix = [
        'alpha' => [
            'app.access',
        ],
        'platform_admin' => [
            'platform.access',
            'platform.accounts.view',
            'platform.accounts.create',
            'platform.accounts.update',
            'platform.accounts.disable',
            'platform.businesses.view',
            'platform.businesses.create',
            'platform.businesses.update',
            'platform.memberships.manage',
            'platform.audit.view',
        ],
    ];
}
