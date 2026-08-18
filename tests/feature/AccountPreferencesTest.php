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
final class AccountPreferencesTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;

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

    public function testAnonymousVisitorCannotOpenPersonalPreferences(): void
    {
        $this->get('/account/preferences')->assertRedirectTo('/login');
    }

    public function testAuthenticatedAccountCanOpenItsPersonalPreferences(): void
    {
        $user = $this->createAccount('owner', 'owner@example.test', 'alpha');
        $this->actingAs($user);

        $response = $this->withSession($_SESSION)->get('/account/preferences');

        $response->assertStatus(200);
        $response->assertSee('Configuración de la cuenta');
        $response->assertSee('owner@example.test');
        $response->assertSee('appearance_theme');
        $response->assertSee('crm_view_mode');
        $response->assertSee('Vista conjunta');
        $response->assertSee('Vista por pestañas');
        $response->assertDontSee('data-theme-toggle');
        $response->assertSee('csrf_test_name');
        $response->assertSee('Cambiá tu contraseña');
    }

    public function testThemeIsPersistedOnlyForTheAuthenticatedAccount(): void
    {
        $owner = $this->createAccount('owner', 'owner@example.test', 'alpha');
        $other = $this->createAccount('other', 'other@example.test', 'alpha');
        $this->actingAs($owner);

        $response = $this->withSession($_SESSION)->post('/account/preferences', [
            'appearance_theme' => 'dark',
            'crm_view_mode'    => 'tabs',
            csrf_token()       => csrf_hash(),
        ]);

        $response->assertRedirectTo('/account/preferences');
        $this->seeInDatabase('user_preferences', [
            'user_id'          => $owner->id,
            'appearance_theme' => 'dark',
            'crm_view_mode'    => 'tabs',
        ]);
        $this->dontSeeInDatabase('user_preferences', ['user_id' => $other->id]);
    }

    public function testInvalidThemeIsRejectedWithoutPersistence(): void
    {
        $user = $this->createAccount('owner', 'owner@example.test', 'alpha');
        $this->actingAs($user);

        $response = $this->withSession($_SESSION)->post('/account/preferences', [
            'appearance_theme' => 'system',
            'crm_view_mode'    => 'combined',
            csrf_token()       => csrf_hash(),
        ]);

        $response->assertRedirectTo('/account/preferences');
        $response->assertSessionHas('error', 'Seleccioná el modo claro o el modo oscuro.');
        $this->dontSeeInDatabase('user_preferences', ['user_id' => $user->id]);
    }

    public function testInvalidCrmViewIsRejectedWithoutPersistence(): void
    {
        $user = $this->createAccount('owner', 'owner@example.test', 'alpha');
        $this->actingAs($user);

        $response = $this->withSession($_SESSION)->post('/account/preferences', [
            'appearance_theme' => 'light',
            'crm_view_mode'    => 'external-url',
            csrf_token()       => csrf_hash(),
        ]);

        $response->assertRedirectTo('/account/preferences');
        $response->assertSessionHas(
            'error',
            'Seleccioná la vista conjunta o la vista por pestañas.',
        );
        $this->dontSeeInDatabase('user_preferences', ['user_id' => $user->id]);
    }

    public function testPlatformAdministratorDoesNotReceiveAnIrrelevantCrmPreference(): void
    {
        $admin = $this->createAccount('admin', 'admin@example.test', 'platform_admin');
        $this->actingAs($admin);

        $response = $this->withSession($_SESSION)->get('/account/preferences');

        $response->assertStatus(200);
        $response->assertSee('appearance_theme');
        $response->assertDontSee('crm_view_mode');
        $response->assertDontSee('Elegí cómo organizar el CRM');

        $updated = $this->withSession($_SESSION)->post('/account/preferences', [
            'appearance_theme' => 'dark',
            csrf_token()       => csrf_hash(),
        ]);

        $updated->assertRedirectTo('/account/preferences');
        $this->seeInDatabase('user_preferences', [
            'user_id'          => $admin->id,
            'appearance_theme' => 'dark',
            'crm_view_mode'    => 'combined',
        ]);
    }

    public function testPreferenceMutationRequiresCsrf(): void
    {
        $this->actingAs($this->createAccount('owner', 'owner@example.test', 'alpha'));
        $this->expectException(SecurityException::class);

        $this->withSession($_SESSION)->post('/account/preferences', [
            'appearance_theme' => 'dark',
            'crm_view_mode'    => 'tabs',
        ]);
    }

    private function createAccount(string $username, string $email, string $group): User
    {
        $users = auth()->getProvider();
        $user = new User([
            'username' => $username,
            'email'    => $email,
            'password' => 'Safe-Test-Password-42!',
            'active'   => 1,
        ]);
        $this->assertTrue($users->save($user));

        $saved = $users->findById($users->getInsertID());
        $this->assertInstanceOf(User::class, $saved);
        $saved->addGroup($group);

        return $saved;
    }
}
