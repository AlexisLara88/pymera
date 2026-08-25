<?php

declare(strict_types=1);

use App\Models\BusinessModel;
use CodeIgniter\Config\Services;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/** @internal */
final class PlatformAdministrationTest extends CIUnitTestCase
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

    public function testAdministratorCanReadTheSeparatedPlatformPanel(): void
    {
        $admin = $this->createAdministrator();
        $activeBusinessId = (new BusinessModel())->insert([
            'name'          => 'Negocio seleccionable',
            'currency_code' => 'USD',
            'timezone'      => 'America/Guayaquil',
            'status'        => 'active',
        ], true);
        $inactiveBusinessId = (new BusinessModel())->insert([
            'name'          => 'Negocio no seleccionable',
            'currency_code' => 'USD',
            'timezone'      => 'America/Guayaquil',
            'status'        => 'inactive',
        ], true);
        $this->assertIsInt($activeBusinessId);
        $this->assertIsInt($inactiveBusinessId);
        $this->actingAs($admin);

        $response = $this->withSession($_SESSION)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Administración de plataforma');
        $response->assertSee('Este panel no abre información operativa de las PyMEs.');
        $response->assertSee('admin@example.test');
        $response->assertSee('data-platform-dialog');
        $response->assertSee('data-owner-creation-form');
        $response->assertSee('Nueva cuenta propietaria');
        $response->assertSee('data-owner-business-select');
        $response->assertSee('Crear un negocio nuevo');
        $response->assertSee('data-owner-new-business');
        $response->assertSee('Crear cuenta');
        $response->assertSee('Nuevo administrador');
        $response->assertSee('Funcionalidad deshabilitada');
        $response->assertSee('data-platform-disabled-feature');
        $response->assertSee('data-platform-confirm-dialog');
        $response->assertSee('data-platform-account-search');
        $response->assertSee('Buscar por usuario, correo o negocio');
        $response->assertSee('data-platform-account-count');
        $response->assertSee('assets/js/platform/index.js');
        $response->assertSee('platform-account-action is-protected');
        $response->assertSee('disabled');
        $response->assertSee('csrf_test_name');

        $body = $response->getBody();
        $this->assertMatchesRegularExpression(
            '/<select[^>]+data-owner-business-select[\s\S]*?<\/select>/',
            $body,
        );
        preg_match('/<select[^>]+data-owner-business-select[\s\S]*?<\/select>/', $body, $matches);
        $businessSelector = $matches[0] ?? '';
        $this->assertStringContainsString('value="' . $activeBusinessId . '"', $businessSelector);
        $this->assertStringContainsString('Negocio seleccionable', $businessSelector);
        $this->assertStringNotContainsString('value="' . $inactiveBusinessId . '"', $businessSelector);
        $this->assertStringNotContainsString('Negocio no seleccionable', $businessSelector);
    }

    public function testAdministratorCreatesOwnerBusinessAndAuditedMembership(): void
    {
        $admin = $this->createAdministrator();
        $this->actingAs($admin);

        $response = $this->withSession($_SESSION)->post('/admin/accounts/owner', [
            'email'                 => 'new-owner@example.test',
            'username'              => 'new-owner',
            'business_id'           => 'new',
            'business_name'         => 'Negocio nuevo',
            'currency_code'         => 'USD',
            'timezone'              => 'America/Guayaquil',
            'password'              => 'Safe-Test-Password-42!',
            'password_confirmation' => 'Safe-Test-Password-42!',
            csrf_token()            => csrf_hash(),
        ]);

        $response->assertRedirectTo('/admin');
        $this->seeInDatabase('businesses', ['name' => 'Negocio nuevo']);
        $this->seeInDatabase('business_users', [
            'role_code' => 'owner',
            'status'    => 'active',
        ]);
        $this->seeInDatabase('platform_audit_events', [
            'actor_user_id' => $admin->id,
            'subject_type'  => 'business_membership',
            'action'        => 'membership_created',
        ]);
    }

    public function testAdministratorCreatesOwnerForAnExistingBusiness(): void
    {
        $admin = $this->createAdministrator();
        $businessId = (new BusinessModel())->insert([
            'name'          => 'Negocio existente',
            'currency_code' => 'USD',
            'timezone'      => 'America/Guayaquil',
            'status'        => 'active',
        ], true);
        $this->assertIsInt($businessId);
        $businessCount = $this->db->table('businesses')->countAllResults();
        $this->actingAs($admin);

        $response = $this->withSession($_SESSION)->post('/admin/accounts/owner', [
            'email'                 => 'existing-owner@example.test',
            'username'              => 'existing-owner',
            'business_id'           => (string) $businessId,
            'business_name'         => '',
            'password'              => 'Safe-Test-Password-42!',
            'password_confirmation' => 'Safe-Test-Password-42!',
            csrf_token()            => csrf_hash(),
        ]);

        $response->assertRedirectTo('/admin');
        $response->assertSessionHas(
            'success',
            'La cuenta propietaria se asoció correctamente con el negocio existente.',
        );
        $this->assertSame($businessCount, $this->db->table('businesses')->countAllResults());
        $this->seeInDatabase('business_users', [
            'business_id' => $businessId,
            'role_code'   => 'owner',
            'status'      => 'active',
        ]);
        $this->seeInDatabase('platform_audit_events', [
            'actor_user_id' => $admin->id,
            'subject_type'  => 'business_membership',
            'action'        => 'membership_created',
        ]);
    }

    public function testAdministratorCannotMixExistingAndNewBusinessInputs(): void
    {
        $admin = $this->createAdministrator();
        $businessId = (new BusinessModel())->insert([
            'name'          => 'Negocio existente',
            'currency_code' => 'USD',
            'timezone'      => 'America/Guayaquil',
            'status'        => 'active',
        ], true);
        $this->assertIsInt($businessId);
        $this->actingAs($admin);

        $response = $this->withSession($_SESSION)->post('/admin/accounts/owner', [
            'email'                 => 'ambiguous-owner@example.test',
            'username'              => 'ambiguous-owner',
            'business_id'           => (string) $businessId,
            'business_name'         => 'Otro negocio',
            'password'              => 'Safe-Test-Password-42!',
            'password_confirmation' => 'Safe-Test-Password-42!',
            csrf_token()            => csrf_hash(),
        ]);

        $response->assertRedirectTo('/admin');
        $response->assertSessionHas(
            'error',
            'Seleccioná un negocio existente o creá uno nuevo, no ambas opciones.',
        );
        $this->assertNull(auth()->getProvider()->findByCredentials([
            'email' => 'ambiguous-owner@example.test',
        ]));
    }

    public function testWebAdministratorCreationIsTemporarilyDisabledInBackend(): void
    {
        $admin = $this->createAdministrator();
        $this->actingAs($admin);

        $response = $this->withSession($_SESSION)->post('/admin/accounts/platform-admin', [
            'email'                 => 'second-admin@example.test',
            'username'              => 'second-admin',
            'password'              => 'Safe-Test-Password-42!',
            'password_confirmation' => 'Safe-Test-Password-42!',
            csrf_token()            => csrf_hash(),
        ]);

        $response->assertRedirectTo('/admin');
        $response->assertSessionHas(
            'error',
            'La creación de nuevos administradores está temporalmente deshabilitada.',
        );
        $created = auth()->getProvider()->findByCredentials([
            'email' => 'second-admin@example.test',
        ]);
        $this->assertNull($created);
        $this->dontSeeInDatabase('platform_audit_events', [
            'actor_user_id' => $admin->id,
            'subject_type'  => 'user',
            'action'        => 'created',
        ]);
    }

    public function testOwnerPasswordMismatchReturnsToTheOwnerDialog(): void
    {
        $admin = $this->createAdministrator();
        $this->actingAs($admin);

        $response = $this->withSession($_SESSION)->post('/admin/accounts/owner', [
            'email'                 => 'new-owner@example.test',
            'username'              => 'new-owner',
            'business_name'         => 'Negocio nuevo',
            'currency_code'         => 'USD',
            'timezone'              => 'America/Guayaquil',
            'password'              => 'Safe-Test-Password-42!',
            'password_confirmation' => 'Different-Test-Password-42!',
            csrf_token()            => csrf_hash(),
        ]);

        $response->assertRedirectTo('/admin');
        $response->assertSessionHas('error', 'Las contraseñas no coinciden.');
        $response->assertSessionHas('platformDialog', 'owner');
        $this->dontSeeInDatabase('businesses', ['name' => 'Negocio nuevo']);
    }

    public function testAdministratorCannotDeactivateItsOwnAccount(): void
    {
        $admin = $this->createAdministrator();
        $this->actingAs($admin);

        $response = $this->withSession($_SESSION)->post(
            '/admin/accounts/' . $admin->id . '/status',
            [
                'status'     => 'inactive',
                csrf_token() => csrf_hash(),
            ],
        );

        $response->assertRedirectTo('/admin');
        $response->assertSessionHas('error', 'El administrador no puede desactivar su propia cuenta.');
        $this->assertTrue(auth()->getProvider()->findById($admin->id)->active);
    }

    public function testAdministratorCannotDeactivateAnotherPlatformAdministrator(): void
    {
        $admin = $this->createAdministrator();
        $secondAdmin = $this->createAdministrator('second-admin@example.test', 'second-admin');
        $this->actingAs($admin);

        $response = $this->withSession($_SESSION)->post(
            '/admin/accounts/' . $secondAdmin->id . '/status',
            [
                'status'     => 'inactive',
                csrf_token() => csrf_hash(),
            ],
        );

        $response->assertRedirectTo('/admin');
        $response->assertSessionHas(
            'error',
            'Las cuentas administradoras de plataforma no pueden desactivarse desde este panel.',
        );
        $this->assertTrue(auth()->getProvider()->findById($secondAdmin->id)->active);
    }

    public function testAdministrativeMutationRequiresCsrf(): void
    {
        $admin = $this->createAdministrator();
        $this->actingAs($admin);

        $this->expectException(SecurityException::class);

        $this->withSession($_SESSION)->post('/admin/accounts/owner', [
            'email' => 'without-csrf@example.test',
        ]);
    }

    private function createAdministrator(
        string $email = 'admin@example.test',
        string $username = 'admin-test',
    ): User
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
        $saved->addGroup('platform_admin');

        return $saved;
    }
}
