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
        $response->assertSee('Preferencias personales');
        $response->assertSee('owner@example.test');
        $response->assertSee('appearance_theme');
        $response->assertDontSee('data-theme-toggle');
        $response->assertSee('csrf_test_name');
    }

    public function testThemeIsPersistedOnlyForTheAuthenticatedAccount(): void
    {
        $owner = $this->createAccount('owner', 'owner@example.test', 'alpha');
        $other = $this->createAccount('other', 'other@example.test', 'alpha');
        $this->actingAs($owner);

        $response = $this->withSession($_SESSION)->post('/account/preferences', [
            'appearance_theme' => 'dark',
            csrf_token()       => csrf_hash(),
        ]);

        $response->assertRedirectTo('/account/preferences');
        $this->seeInDatabase('user_preferences', [
            'user_id'          => $owner->id,
            'appearance_theme' => 'dark',
        ]);
        $this->dontSeeInDatabase('user_preferences', ['user_id' => $other->id]);
    }

    public function testInvalidThemeIsRejectedWithoutPersistence(): void
    {
        $user = $this->createAccount('owner', 'owner@example.test', 'alpha');
        $this->actingAs($user);

        $response = $this->withSession($_SESSION)->post('/account/preferences', [
            'appearance_theme' => 'system',
            csrf_token()       => csrf_hash(),
        ]);

        $response->assertRedirectTo('/account/preferences');
        $response->assertSessionHas('error', 'Seleccioná el modo claro o el modo oscuro.');
        $this->dontSeeInDatabase('user_preferences', ['user_id' => $user->id]);
    }

    public function testPreferenceMutationRequiresCsrf(): void
    {
        $this->actingAs($this->createAccount('owner', 'owner@example.test', 'alpha'));
        $this->expectException(SecurityException::class);

        $this->withSession($_SESSION)->post('/account/preferences', [
            'appearance_theme' => 'dark',
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
