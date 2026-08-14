<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Controls whether the functional alpha surface is registered.
 *
 * The HTTP alpha is an explicit, reversible product decision. It remains
 * independent of runtime secrets and does not require changes to .env.
 */
class AlphaAccess extends BaseConfig
{
    public bool $publicAlphaEnabled = true;
    public bool $authenticationRoutesEnabled;
    public bool $functionalRoutesEnabled;

    public function __construct()
    {
        parent::__construct();

        $nonPublicEnvironment = in_array(
            ENVIRONMENT,
            ['development', 'testing'],
            true,
        );
        $enabled = $nonPublicEnvironment
            || (ENVIRONMENT === 'production' && $this->publicAlphaEnabled);

        $this->authenticationRoutesEnabled = $enabled;
        $this->functionalRoutesEnabled     = $enabled;
    }
}
