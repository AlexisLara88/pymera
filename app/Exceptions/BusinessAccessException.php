<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class BusinessAccessException extends RuntimeException
{
    public static function unauthenticated(): self
    {
        return new self('Se requiere una cuenta autenticada.');
    }

    public static function missingMembership(): self
    {
        return new self('La cuenta no tiene un negocio activo autorizado.');
    }

    public static function ambiguousMembership(): self
    {
        return new self('La cuenta requiere seleccionar un negocio activo.');
    }

    public static function unavailableBusiness(): self
    {
        return new self('El negocio autorizado no está disponible.');
    }

    public static function unauthorizedEntity(): self
    {
        return new self('La entidad no pertenece al negocio autorizado.');
    }

    public static function invalidRole(): self
    {
        return new self('La cuenta tiene un rol de negocio no reconocido.');
    }

    public static function missingPermission(): self
    {
        return new self('El rol de la cuenta no permite realizar esta operación.');
    }
}
