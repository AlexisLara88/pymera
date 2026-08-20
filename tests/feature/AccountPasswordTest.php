<?php

declare(strict_types=1);

use CodeIgniter\Config\Services;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/** @internal */
final class AccountPasswordTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    private const CURRENT_PASSWORD = 'Safe-Test-Password-42!';
    private const NEW_PASSWORD     = 'Another-Safe-Password-84!';

    protected $namespace = [
        'CodeIgniter\Settings',
        'CodeIgniter\Shield',
        'App',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Services::resetSingle('auth');
        Services::resetSingle('throttler');
        service('cache')->clean();
    }

    protected function tearDown(): void
    {
        service('cache')->clean();
        Services::resetSingle('throttler');

        parent::tearDown();
    }

    public function testAccountPageOffersAnIndependentPasswordForm(): void
    {
        $this->actingAs($this->createAccount('owner', 'owner@example.test', 'alpha'));

        $response = $this->withSession($_SESSION)->get('/account/preferences');

        $response->assertStatus(200);
        $response->assertSee('Cambiá tu contraseña');
        $response->assertSee('current_password');
        $response->assertSee('new_password');
        $response->assertSee('new_password_confirmation');
        $this->assertStringContainsString('autocomplete="off"', $response->getBody());
        $this->assertStringNotContainsString('autocomplete="current-password"', $response->getBody());
        $this->assertStringNotContainsString('autocomplete="new-password"', $response->getBody());
        $this->assertStringContainsString(
            'action="' . site_url('account/password') . '"',
            $response->getBody(),
        );
        $this->assertSame(3, substr_count($response->getBody(), 'data-password-toggle='));
        $response->assertSee('newPasswordFeedback');
        $response->assertSee('newPasswordConfirmationFeedback');
    }

    public function testPlatformAdministratorCanUseTheSamePersonalSecurityForm(): void
    {
        $this->actingAs($this->createAccount('admin', 'admin@example.test', 'platform_admin'));

        $response = $this->withSession($_SESSION)->get('/account/preferences');

        $response->assertStatus(200);
        $response->assertSee('Cambiá tu contraseña');
        $response->assertSee('current_password');
    }

    public function testPasswordFeedbackOpensTheSecurityTab(): void
    {
        $this->actingAs($this->createAccount('owner', 'owner@example.test', 'alpha'));
        session()->setFlashdata('password_error', 'Revisá los datos ingresados.');

        $response = $this->withSession($_SESSION)->get('/account/preferences');

        $response->assertStatus(200);
        $response->assertSee('Revisá los datos ingresados.');
        $this->assertStringContainsString('data-initial-tab="security"', $response->getBody());
        $this->assertMatchesRegularExpression(
            '/id="securityTab".*?aria-selected="true"/s',
            $response->getBody(),
        );
    }

    public function testAuthenticatedAccountCanChangeOnlyItsOwnPassword(): void
    {
        $owner = $this->createAccount('owner', 'owner@example.test', 'alpha');
        $other = $this->createAccount('other', 'other@example.test', 'alpha');
        $this->actingAs($owner);

        $response = $this->withSession($_SESSION)->post('/account/password', [
            'current_password'          => self::CURRENT_PASSWORD,
            'new_password'              => self::NEW_PASSWORD,
            'new_password_confirmation' => self::NEW_PASSWORD,
            csrf_token()                => csrf_hash(),
        ]);

        $response->assertRedirectTo('/account/preferences');
        $response->assertSessionHas('password_success', 'Tu contraseña se actualizó correctamente.');

        $updatedOwner = auth()->getProvider()->findById($owner->id);
        $unchangedOther = auth()->getProvider()->findById($other->id);
        $this->assertInstanceOf(User::class, $updatedOwner);
        $this->assertInstanceOf(User::class, $unchangedOther);
        $this->assertFalse(service('passwords')->verify(self::CURRENT_PASSWORD, $updatedOwner->password_hash));
        $this->assertTrue(service('passwords')->verify(self::NEW_PASSWORD, $updatedOwner->password_hash));
        $this->assertTrue(service('passwords')->verify(self::CURRENT_PASSWORD, $unchangedOther->password_hash));
    }

    public function testWrongCurrentPasswordIsRejectedWithoutMutation(): void
    {
        $user = $this->createAccount('owner', 'owner@example.test', 'alpha');
        $this->actingAs($user);

        $response = $this->withSession($_SESSION)->post('/account/password', [
            'current_password'          => 'This-Is-Not-The-Password-1!',
            'new_password'              => self::NEW_PASSWORD,
            'new_password_confirmation' => self::NEW_PASSWORD,
            csrf_token()                => csrf_hash(),
        ]);

        $response->assertRedirectTo('/account/preferences');
        $response->assertSessionHas('password_error', 'La contraseña actual no es correcta.');
        $this->assertCurrentPasswordRemains($user);
    }

    public function testDifferentConfirmationIsRejectedWithoutMutation(): void
    {
        $user = $this->createAccount('owner', 'owner@example.test', 'alpha');
        $this->actingAs($user);

        $response = $this->withSession($_SESSION)->post('/account/password', [
            'current_password'          => self::CURRENT_PASSWORD,
            'new_password'              => self::NEW_PASSWORD,
            'new_password_confirmation' => 'Different-Safe-Password-85!',
            csrf_token()                => csrf_hash(),
        ]);

        $response->assertRedirectTo('/account/preferences');
        $response->assertSessionHas('password_error', 'Las contraseñas nuevas no coinciden.');
        $this->assertCurrentPasswordRemains($user);
    }

    public function testWeakPasswordIsRejectedByShieldWithoutMutation(): void
    {
        $user = $this->createAccount('owner', 'owner@example.test', 'alpha');
        $this->actingAs($user);

        $response = $this->withSession($_SESSION)->post('/account/password', [
            'current_password'          => self::CURRENT_PASSWORD,
            'new_password'              => 'short',
            'new_password_confirmation' => 'short',
            csrf_token()                => csrf_hash(),
        ]);

        $response->assertRedirectTo('/account/preferences');
        $response->assertSessionHas('password_error');
        $this->assertCurrentPasswordRemains($user);
    }

    public function testCurrentPasswordCannotBeReused(): void
    {
        $user = $this->createAccount('owner', 'owner@example.test', 'alpha');
        $this->actingAs($user);

        $response = $this->withSession($_SESSION)->post('/account/password', [
            'current_password'          => self::CURRENT_PASSWORD,
            'new_password'              => self::CURRENT_PASSWORD,
            'new_password_confirmation' => self::CURRENT_PASSWORD,
            csrf_token()                => csrf_hash(),
        ]);

        $response->assertRedirectTo('/account/preferences');
        $response->assertSessionHas(
            'password_error',
            'La contraseña nueva debe ser diferente de la actual.',
        );
        $this->assertCurrentPasswordRemains($user);
    }

    public function testPasswordMutationRequiresCsrf(): void
    {
        $this->actingAs($this->createAccount('owner', 'owner@example.test', 'alpha'));
        $this->expectException(SecurityException::class);

        $this->withSession($_SESSION)->post('/account/password', [
            'current_password'          => self::CURRENT_PASSWORD,
            'new_password'              => self::NEW_PASSWORD,
            'new_password_confirmation' => self::NEW_PASSWORD,
        ]);
    }

    public function testPasswordEndpointLimitsRepeatedAttempts(): void
    {
        $this->actingAs($this->createAccount('owner', 'owner@example.test', 'alpha'));

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $response = $this->withSession($_SESSION)->post('/account/password', [
                'current_password'          => '',
                'new_password'              => self::NEW_PASSWORD,
                'new_password_confirmation' => self::NEW_PASSWORD,
                csrf_token()                => csrf_hash(),
            ]);

            $response->assertRedirectTo('/account/preferences');
        }

        $this->withSession($_SESSION)->post('/account/password', [
            'current_password'          => '',
            'new_password'              => self::NEW_PASSWORD,
            'new_password_confirmation' => self::NEW_PASSWORD,
            csrf_token()                => csrf_hash(),
        ])->assertStatus(429);
    }

    private function createAccount(string $username, string $email, string $group): User
    {
        $users = auth()->getProvider();
        $user = new User([
            'username' => $username,
            'email'    => $email,
            'password' => self::CURRENT_PASSWORD,
            'active'   => 1,
        ]);
        $this->assertTrue($users->save($user));

        $saved = $users->findById($users->getInsertID());
        $this->assertInstanceOf(User::class, $saved);
        $saved->addGroup($group);

        return $saved;
    }

    private function assertCurrentPasswordRemains(User $user): void
    {
        $saved = auth()->getProvider()->findById($user->id);
        $this->assertInstanceOf(User::class, $saved);
        $this->assertTrue(service('passwords')->verify(self::CURRENT_PASSWORD, $saved->password_hash));
        $this->assertFalse(service('passwords')->verify(self::NEW_PASSWORD, $saved->password_hash));
    }
}
