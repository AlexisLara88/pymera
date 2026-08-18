<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');
$routes->get('demo', 'Home::index');

$alphaAccess = config('AlphaAccess');

if ($alphaAccess->authenticationRoutesEnabled) {
    $routes->get('demolite', 'AlphaController::entry', ['as' => 'alpha.entry']);
    $routes->get('entry', 'AlphaController::entry', ['as' => 'account.entry']);

    service('auth')->routes($routes, ['only' => ['login']]);

    $routes->post(
        'logout',
        '\CodeIgniter\Shield\Controllers\LoginController::logoutAction',
        ['as' => 'logout', 'filter' => 'session'],
    );
}

if ($alphaAccess->functionalRoutesEnabled) {
    $routes->group('account', ['filter' => ['session', 'user-preferences']], static function (RouteCollection $routes): void {
        $routes->get('preferences', 'AccountPreferenceController::index', ['as' => 'account.preferences']);
        $routes->post('preferences', 'AccountPreferenceController::update', ['as' => 'account.preferences.update']);
    });

    $routes->group('admin', ['filter' => ['session', 'permission:platform.access', 'user-preferences']], static function (RouteCollection $routes): void {
        $routes->get('', 'PlatformAdminController::index', ['as' => 'platform.index']);
        $routes->post(
            'accounts/owner',
            'PlatformAdminController::createOwner',
            ['as' => 'platform.accounts.owner', 'filter' => 'permission:platform.accounts.create'],
        );
        $routes->post(
            'accounts/platform-admin',
            'PlatformAdminController::createAdministrator',
            ['as' => 'platform.accounts.admin', 'filter' => 'permission:platform.accounts.create'],
        );
        $routes->post(
            'accounts/(:num)/status',
            'PlatformAdminController::setUserStatus/$1',
            ['as' => 'platform.accounts.status', 'filter' => 'permission:platform.accounts.disable'],
        );
        $routes->post(
            'memberships/(:num)/status',
            'PlatformAdminController::setMembershipStatus/$1',
            ['as' => 'platform.memberships.status', 'filter' => 'permission:platform.memberships.manage'],
        );
    });

    $routes->group('app', ['filter' => ['session', 'permission:app.access', 'user-preferences']], static function (RouteCollection $routes): void {
        $routes->get('mi-negocio', 'BusinessController::show', ['as' => 'business.show']);
        $routes->post('mi-negocio', 'BusinessController::update', ['as' => 'business.update']);

        $routes->group('', ['filter' => 'business-onboarding'], static function (RouteCollection $routes): void {
            $routes->get('', 'DashboardController::index', ['as' => 'app']);

            $routes->get('objetivos', 'ObjectiveController::index', ['as' => 'objectives.index']);
            $routes->post('objetivos', 'ObjectiveController::create', ['as' => 'objectives.create']);
            $routes->post('objetivos/(:num)', 'ObjectiveController::update/$1', ['as' => 'objectives.update']);
            $routes->post('objetivos/(:num)/archivar', 'ObjectiveController::archive/$1', ['as' => 'objectives.archive']);
            $routes->post('objetivos/(:num)/actividades', 'ActivityController::create/$1', ['as' => 'activities.create']);
            $routes->post('actividades/(:num)', 'ActivityController::update/$1', ['as' => 'activities.update']);
            $routes->post('actividades/(:num)/archivar', 'ActivityController::archive/$1', ['as' => 'activities.archive']);
            $routes->get('prioridades', 'PriorityController::index', ['as' => 'priorities.index']);

            $routes->get('finanzas', 'FinanceController::index', ['as' => 'finances.index']);
            $routes->post('finanzas', 'FinanceController::create', ['as' => 'finances.create']);
            $routes->post('finanzas/(:num)', 'FinanceController::update/$1', ['as' => 'finances.update']);

            $routes->get('clientes', 'CrmController::index', ['as' => 'crm.index']);
            $routes->post('clientes/contactos', 'ContactController::create', ['as' => 'crm.contacts.create']);
            $routes->post('clientes/contactos/(:num)', 'ContactController::update/$1', ['as' => 'crm.contacts.update']);
            $routes->post('clientes/contactos/(:num)/convertir', 'ContactController::convert/$1', ['as' => 'crm.contacts.convert']);
            $routes->post('clientes/contactos/(:num)/archivar', 'ContactController::archive/$1', ['as' => 'crm.contacts.archive']);
            $routes->post('clientes/oportunidades', 'OpportunityController::create', ['as' => 'crm.opportunities.create']);
            $routes->post('clientes/oportunidades/(:num)', 'OpportunityController::update/$1', ['as' => 'crm.opportunities.update']);
            $routes->post('clientes/oportunidades/(:num)/estado', 'OpportunityController::changeStatus/$1', ['as' => 'crm.opportunities.status']);
            $routes->get('clientes/oportunidades/(:num)/nota-venta', 'SaleNoteController::download/$1', ['as' => 'crm.opportunities.sale-note']);
            $routes->post('clientes/oportunidades/(:num)/nota-venta', 'SaleNoteController::completeAndDownload/$1', ['as' => 'crm.opportunities.sale-note.complete']);
            $routes->post('clientes/oportunidades/(:num)/archivar', 'OpportunityController::archive/$1', ['as' => 'crm.opportunities.archive']);
        });
    });
}
