<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;

/**
 * Stable public entry point for the functional validation alpha.
 */
class AlphaController extends BaseController
{
    public function entry(): RedirectResponse
    {
        if (! auth()->loggedIn()) {
            return redirect()->to(site_url('login'));
        }

        $user = auth()->user();

        if ($user !== null && $user->can('platform.access')) {
            return redirect()->to(site_url('admin'));
        }

        if ($user !== null && $user->can('app.access')) {
            return redirect()->to(site_url('app'));
        }

        return redirect()
            ->to(site_url('/'))
            ->with('error', 'La cuenta no tiene un acceso habilitado para esta plataforma.');
    }
}
