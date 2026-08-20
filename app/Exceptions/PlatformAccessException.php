<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class PlatformAccessException extends RuntimeException
{
    public static function denied(): self
    {
        return new self('La cuenta no tiene autorización para esta operación administrativa.');
    }

    public static function selfDeactivation(): self
    {
        return new self('El administrador no puede desactivar su propia cuenta.');
    }

    public static function protectedAdministrator(): self
    {
        return new self('Las cuentas administradoras de plataforma no pueden desactivarse desde este panel.');
    }

    public static function administratorCreationDisabled(): self
    {
        return new self('La creación de nuevos administradores está temporalmente deshabilitada.');
    }
}
