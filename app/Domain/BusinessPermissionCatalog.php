<?php

declare(strict_types=1);

namespace App\Domain;

final class BusinessPermissionCatalog
{
    public const BUSINESS_VIEW = 'business.view';
    public const BUSINESS_UPDATE = 'business.update';
    public const OBJECTIVES_VIEW = 'objectives.view';
    public const OBJECTIVES_MANAGE = 'objectives.manage';
    public const PRIORITIES_VIEW = 'priorities.view';
    public const FINANCES_VIEW = 'finances.view';
    public const FINANCES_MANAGE = 'finances.manage';
    public const CRM_VIEW = 'crm.view';
    public const CRM_MANAGE = 'crm.manage';

    /** @var list<string> */
    public const ALL = [
        self::BUSINESS_VIEW,
        self::BUSINESS_UPDATE,
        self::OBJECTIVES_VIEW,
        self::OBJECTIVES_MANAGE,
        self::PRIORITIES_VIEW,
        self::FINANCES_VIEW,
        self::FINANCES_MANAGE,
        self::CRM_VIEW,
        self::CRM_MANAGE,
    ];
}
