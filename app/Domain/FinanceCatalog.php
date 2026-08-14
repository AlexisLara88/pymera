<?php

declare(strict_types=1);

namespace App\Domain;

final class FinanceCatalog
{
    public const STATUSES = [
        'recorded' => 'Registrado',
        'draft'    => 'Borrador',
    ];
}
