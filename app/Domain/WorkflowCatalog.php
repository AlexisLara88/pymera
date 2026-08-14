<?php

declare(strict_types=1);

namespace App\Domain;

use App\Services\EisenhowerClassifier;

final class WorkflowCatalog
{
    public const OBJECTIVE_CATEGORIES = [
        'commercial'  => 'Comercial',
        'financial'   => 'Financiero',
        'operational' => 'Operativo',
        'improvement' => 'Mejora',
    ];

    public const OBJECTIVE_STATUSES = [
        'draft'     => 'Borrador',
        'active'    => 'Activo',
        'completed' => 'Completado',
        'paused'    => 'Pausado',
    ];

    public const ACTIVITY_STATUSES = [
        'pending'     => 'Pendiente',
        'in_progress' => 'En curso',
        'completed'   => 'Completada',
        'cancelled'   => 'Cancelada',
    ];

    public const QUADRANTS = [
        EisenhowerClassifier::DO_NOW    => 'Hacer ahora',
        EisenhowerClassifier::SCHEDULE  => 'Planificar',
        EisenhowerClassifier::DELEGATE  => 'Delegar',
        EisenhowerClassifier::ELIMINATE => 'Eliminar',
    ];
}
