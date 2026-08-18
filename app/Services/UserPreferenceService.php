<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\UserPreferenceValidationException;
use App\Models\UserPreferenceModel;
use CodeIgniter\Session\Session;
use RuntimeException;

/**
 * Manages personal preferences for the authenticated account.
 */
final class UserPreferenceService
{
    public const SESSION_LOADED   = 'pymera_preferences_loaded';
    public const SESSION_THEME    = 'pymera_appearance_theme';
    public const SESSION_CRM_VIEW = 'pymera_crm_view_mode';

    private const THEMES           = ['light', 'dark'];
    private const CRM_VIEWS        = ['combined', 'tabs'];
    private const DEFAULT_CRM_VIEW = 'combined';

    public function __construct(
        private ?UserPreferenceModel $preferences = null,
        private ?Session $session = null,
    ) {
        $this->preferences ??= model(UserPreferenceModel::class);
        $this->session     ??= service('session');
    }

    public function hydrateSession(): ?string
    {
        if ($this->session->get(self::SESSION_LOADED) === true) {
            return $this->sessionTheme();
        }

        $preference = $this->preferences->preferencesForUser($this->authenticatedUserId());
        $theme = $preference === null ? null : (string) $preference['appearance_theme'];
        $crmView = $preference === null
            ? self::DEFAULT_CRM_VIEW
            : $this->normalizeCrmView($preference['crm_view_mode'] ?? null);

        $this->session->set([
            self::SESSION_LOADED   => true,
            self::SESSION_CRM_VIEW => $crmView,
        ]);

        if ($theme === null) {
            $this->session->remove(self::SESSION_THEME);
        } else {
            $this->session->set(self::SESSION_THEME, $theme);
        }

        return $theme;
    }

    public function currentTheme(): ?string
    {
        return $this->hydrateSession();
    }

    public function currentCrmView(): string
    {
        $this->hydrateSession();

        return $this->normalizeCrmView($this->session->get(self::SESSION_CRM_VIEW));
    }

    /** @return array{theme: string, crm_view: string} */
    public function updatePreferences(mixed $theme, mixed $crmView): array
    {
        if (! is_string($theme) || ! in_array($theme, self::THEMES, true)) {
            throw new UserPreferenceValidationException(
                'Seleccioná el modo claro o el modo oscuro.',
            );
        }

        if (! is_string($crmView) || ! in_array($crmView, self::CRM_VIEWS, true)) {
            throw new UserPreferenceValidationException(
                'Seleccioná la vista conjunta o la vista por pestañas.',
            );
        }

        if (! $this->preferences->saveForUser(
            $this->authenticatedUserId(),
            $theme,
            $crmView,
        )) {
            throw new RuntimeException('No fue posible guardar las preferencias personales.');
        }

        $this->session->set([
            self::SESSION_LOADED   => true,
            self::SESSION_THEME    => $theme,
            self::SESSION_CRM_VIEW => $crmView,
        ]);

        return [
            'theme'    => $theme,
            'crm_view' => $crmView,
        ];
    }

    private function sessionTheme(): ?string
    {
        $theme = $this->session->get(self::SESSION_THEME);

        return is_string($theme) && in_array($theme, self::THEMES, true)
            ? $theme
            : null;
    }

    private function normalizeCrmView(mixed $crmView): string
    {
        return is_string($crmView) && in_array($crmView, self::CRM_VIEWS, true)
            ? $crmView
            : self::DEFAULT_CRM_VIEW;
    }

    private function authenticatedUserId(): int
    {
        $userId = auth()->id();

        if (! is_int($userId) || $userId <= 0) {
            throw new RuntimeException('La cuenta autenticada no está disponible.');
        }

        return $userId;
    }
}
