<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Shield\Authentication\AuthenticatorInterface;
use CodeIgniter\Shield\Authentication\Authenticators\Session;
use CodeIgniter\Shield\Config\Auth as ShieldAuth;

/**
 * Authentication policy for the controlled alpha.
 *
 * The alpha deliberately supports only named accounts authenticated by
 * server-side sessions. Account provisioning remains an administrative
 * operation outside the public interface.
 */
class Auth extends ShieldAuth
{
    public function __construct()
    {
        parent::__construct();

        $this->views['login'] = 'auth/login';
    }

    public array $redirects = [
        'register'          => '/',
        'login'             => '/entry',
        'logout'            => 'login',
        'force_reset'       => '/',
        'permission_denied' => '/',
        'group_denied'      => '/',
    ];

    /**
     * @var array<string, class-string<AuthenticatorInterface>>
     */
    public array $authenticators = [
        'session' => Session::class,
    ];

    public string $defaultAuthenticator = 'session';

    /**
     * @var list<string>
     */
    public array $authenticationChain = [
        'session',
    ];

    public bool $allowRegistration = false;

    public bool $allowMagicLinkLogins = false;

    /**
     * @var array<string, bool|int|string>
     */
    public array $sessionConfig = [
        'field'              => 'user',
        'allowRemembering'   => false,
        'rememberCookieName' => 'remember',
        'rememberLength'     => 0,
    ];

    /**
     * @var list<string>
     */
    public array $validFields = [
        'email',
    ];
}
