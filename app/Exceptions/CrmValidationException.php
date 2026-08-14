<?php

declare(strict_types=1);

namespace App\Exceptions;

use InvalidArgumentException;

final class CrmValidationException extends InvalidArgumentException
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('La información comercial contiene errores.');
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
