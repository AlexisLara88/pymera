<?php

declare(strict_types=1);

namespace App\Domain;

final class CrmCatalog
{
    public const CONTACT_KINDS = [
        'person'       => 'Persona',
        'organization' => 'Organización',
    ];

    public const LIFECYCLE_STAGES = [
        'prospect' => 'Prospecto',
        'client'   => 'Cliente',
    ];

    public const ACQUISITION_CHANNELS = [
        'instagram'    => 'Instagram',
        'whatsapp'     => 'WhatsApp',
        'referral'     => 'Recomendación',
        'local_search' => 'Búsqueda local',
        'direct'       => 'Directo',
        'other'        => 'Otro',
    ];

    public const OPPORTUNITY_STATUSES = [
        'new'           => 'Nueva',
        'contacted'     => 'Contactado',
        'proposal_sent' => 'Propuesta enviada',
        'negotiation'   => 'Negociación',
        'won'           => 'Ganada',
        'lost'          => 'Perdida',
    ];

    public const OPEN_OPPORTUNITY_STATUSES = [
        'new',
        'contacted',
        'proposal_sent',
        'negotiation',
    ];

    public const CLOSED_OPPORTUNITY_STATUSES = [
        'won',
        'lost',
    ];
}
