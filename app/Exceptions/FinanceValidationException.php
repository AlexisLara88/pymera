<?php

declare(strict_types=1);

namespace App\Exceptions;

use InvalidArgumentException;

final class FinanceValidationException extends InvalidArgumentException
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(private array $errors)
    {
        parent::__construct('Los datos financieros no son válidos.');
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
