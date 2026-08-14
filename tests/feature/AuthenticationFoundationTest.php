<?php

declare(strict_types=1);

use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Shield\Authentication\Authenticators\Session as ShieldSession;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\AlphaAccess;
use Config\App;
use Config\Auth;
use Config\AuthGroups;
use Config\Filters;
use Config\Security;
use Config\Services;
use Config\Session;

/**
 * @internal
 */
final class AuthenticationFoundationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use AuthenticationTesting;

    protected $namespace = [
        'CodeIgniter\Settings',
        'CodeIgniter\Shield',
        'App',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Services::resetSingle('auth');
    }

    public function testAlphaUsesOnlyClosedSessionAuthentication(): void
    {
        $auth = config(Auth::class);

        $this->assertSame(['session' => ShieldSession::class], $auth->authenticators);
        $this->assertSame(['session'], $auth->authenticationChain);
        $this->assertSame('session', $auth->defaultAuthenticator);
        $this->assertFalse($auth->allowRegistration);
        $this->assertFalse($auth->allowMagicLinkLogins);
        $this->assertFalse($auth->sessionConfig['allowRemembering']);
        $this->assertSame(['email'], $auth->validFields);
    }

    public function testFunctionalAlphaIsExplicitlyEnabledAndHasStableEntryPoint(): void
    {
        $alphaAccess = config(AlphaAccess::class);

        $this->assertTrue($alphaAccess->publicAlphaEnabled);
        $this->assertTrue($alphaAccess->authenticationRoutesEnabled);
        $this->assertTrue($alphaAccess->functionalRoutesEnabled);
        $this->assertSame('', config(App::class)->indexPage);

        $this->get('/demolite')->assertRedirectTo('/login');
    }

    public function testGlobalGroupsAndPermissionsRemainSeparateFromBusinessRoles(): void
    {
        $groups = config(AuthGroups::class);

        $this->assertSame('alpha', $groups->defaultGroup);
        $this->assertSame(['alpha', 'platform_admin'], array_keys($groups->groups));
        $this->assertArrayHasKey('app.access', $groups->permissions);
        $this->assertArrayHasKey('platform.access', $groups->permissions);
        $this->assertSame(['app.access'], $groups->matrix['alpha']);
        $this->assertContains('platform.accounts.create', $groups->matrix['platform_admin']);
        $this->assertNotContains('app.access', $groups->matrix['platform_admin']);
    }

    public function testSessionSecurityAndAuthRateFilterAreEnabled(): void
    {
        $security = config(Security::class);
        $session  = config(Session::class);
        $filters  = config(Filters::class);

        $this->assertSame('session', $security->csrfProtection);
        $this->assertTrue($security->regenerate);
        $this->assertSame(7200, $session->expiration);
        $this->assertSame('pyme_erp_lite_session', $session->cookieName);
        $this->assertTrue($session->regenerateDestroy);
        $this->assertSame(['before' => ['login']], $filters->filters['auth-rates']);
    }

    public function testTestingExposesOnlyLoginAndPostLogoutRoutes(): void
    {
        $login = $this->get('/login');

        $login->assertStatus(200);
        $login->assertSee('Ingresar');
        $login->assertSee('Correo electrónico');
        $login->assertSee('PyMERA. Todos los derechos reservados.');
        $login->assertDontSee('Entorno alfa de validación');
        $login->assertDontSee('recuperación automática');
        $login->assertDontSee('cuenta demostrativa asignada');
        $login->assertSee('csrf_test_name');
        $this->assertSame('auth/login', config(Auth::class)->views['login']);

        $routes     = service('routes');
        $getRoutes  = array_keys($routes->getRoutes('GET'));
        $postRoutes = array_keys($routes->getRoutes('POST'));

        $this->assertContains('login', $getRoutes);
        $this->assertContains('demolite', $getRoutes);
        $this->assertNotContains('register', $getRoutes);
        $this->assertNotContains('login/magic-link', $getRoutes);
        $this->assertNotContains('logout', $getRoutes);
        $this->assertContains('login', $postRoutes);
        $this->assertContains('logout', $postRoutes);
        $this->assertNotContains('register', $postRoutes);
        $this->assertNotContains('login/magic-link', $postRoutes);
    }

    public function testInvalidLoginIsRejectedAndRecorded(): void
    {
        $result = $this->post('/login', [
            'email'      => 'unknown@example.test',
            'password'   => 'NotThePassword1!',
            csrf_token() => csrf_hash(),
        ]);

        $result->assertRedirectTo('/login');
        $result->assertSessionMissing('user');
        $this->seeInDatabase('auth_logins', [
            'identifier' => 'unknown@example.test',
            'success'    => 0,
        ]);
    }

    public function testLoginRouteLimitsRepeatedRequests(): void
    {
        Services::resetSingle('throttler');
        service('cache')->clean();

        try {
            for ($attempt = 1; $attempt <= 10; $attempt++) {
                $this->get('/login')->assertStatus(200);
            }

            $this->get('/login')->assertStatus(429);
        } finally {
            service('cache')->clean();
            Services::resetSingle('throttler');
        }
    }

    public function testLoginAndLogoutUseTheSessionAndCsrf(): void
    {
        $user = $this->createTestUser();

        $login = $this->post('/login', [
            'email'      => 'alpha@example.test',
            'password'   => 'Safe-Test-Password-42!',
            csrf_token() => csrf_hash(),
        ]);

        $login->assertRedirectTo('/entry');
        $login->assertSessionHas('user', ['id' => $user->id]);
        $this->seeInDatabase('auth_logins', [
            'user_id' => $user->id,
            'success' => 1,
        ]);

        $logout = $this
            ->withSession($_SESSION)
            ->post('/logout', [
                csrf_token() => csrf_hash(),
            ]);

        $logout->assertRedirectTo('/login');
        $logout->assertSessionMissing('user');
    }

    public function testEntrySeparatesProductAndPlatformAccounts(): void
    {
        $productUser = $this->createTestUser();
        $this->actingAs($productUser);

        $this->withSession($_SESSION)
            ->get('/entry')
            ->assertRedirectTo('/app');

        auth()->logout();

        $admin = $this->createUserWithGroup(
            'platform-admin',
            'platform-admin@example.test',
            'platform_admin',
        );
        $this->actingAs($admin);

        $this->withSession($_SESSION)
            ->get('/entry')
            ->assertRedirectTo('/admin');
    }

    public function testProductAccountCannotOpenPlatformAdministration(): void
    {
        $this->actingAs($this->createTestUser());

        $this->withSession($_SESSION)
            ->get('/admin')
            ->assertRedirectTo(rtrim(site_url('/'), '/'));
    }

    public function testPlatformAdministratorCannotOpenBusinessApplicationImplicitly(): void
    {
        $admin = $this->createUserWithGroup(
            'platform-admin',
            'platform-admin@example.test',
            'platform_admin',
        );
        $this->actingAs($admin);

        $adminResponse = $this->withSession($_SESSION)->get('/admin');
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Administración de plataforma');

        $this->withSession($_SESSION)
            ->get('/app')
            ->assertRedirectTo(rtrim(site_url('/'), '/'));
    }

    public function testLogoutWithoutCsrfIsRejectedBeforeItCanMutateTheSession(): void
    {
        $user = $this->createTestUser();
        $this->actingAs($user);

        $this->expectException(SecurityException::class);

        $this
            ->withSession($_SESSION)
            ->post('/logout');
    }

    private function createTestUser(): User
    {
        $users = auth()->getProvider();
        $user  = new User([
            'username' => 'alpha-test',
            'email'    => 'alpha@example.test',
            'password' => 'Safe-Test-Password-42!',
            'active'   => 1,
        ]);

        $this->assertTrue($users->save($user));

        $savedUser = $users->findById($users->getInsertID());
        $this->assertInstanceOf(User::class, $savedUser);
        $users->addToDefaultGroup($savedUser);

        return $savedUser;
    }

    private function createUserWithGroup(string $username, string $email, string $group): User
    {
        $users = auth()->getProvider();
        $user  = new User([
            'username' => $username,
            'email'    => $email,
            'password' => 'Safe-Test-Password-42!',
            'active'   => 1,
        ]);

        $this->assertTrue($users->save($user));

        $savedUser = $users->findById($users->getInsertID());
        $this->assertInstanceOf(User::class, $savedUser);
        $savedUser->addGroup($group);

        return $savedUser;
    }
}
