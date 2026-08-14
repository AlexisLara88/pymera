<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityModel;
use App\Models\BusinessModel;
use App\Models\BusinessProfileModel;
use App\Models\ObjectiveModel;

/**
 * Read-only facade that never accepts a business identifier from its caller.
 */
final class AuthorizedBusinessReader
{
    public function __construct(
        private ?AlphaBusinessContext $context = null,
        private ?BusinessModel $businesses = null,
        private ?BusinessProfileModel $profiles = null,
        private ?ObjectiveModel $objectives = null,
        private ?ActivityModel $activities = null,
    ) {
        $this->context    ??= new AlphaBusinessContext();
        $this->businesses ??= model(BusinessModel::class);
        $this->profiles   ??= model(BusinessProfileModel::class);
        $this->objectives ??= model(ObjectiveModel::class);
        $this->activities ??= model(ActivityModel::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function business(): array
    {
        return $this->businesses->find($this->context->businessId());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function profile(): ?array
    {
        return $this->profiles
            ->where('business_id', $this->context->businessId())
            ->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function objectives(): array
    {
        return $this->objectives
            ->where('business_id', $this->context->businessId())
            ->findAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function activities(): array
    {
        return $this->activities->findForBusiness(
            $this->context->businessId(),
        );
    }
}
