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
    public const SESSION_LOADED = 'pymera_preferences_loaded';
    public const SESSION_THEME  = 'pymera_appearance_theme';

    private const THEMES = ['light', 'dark'];

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

        $theme = $this->preferences->themeForUser($this->authenticatedUserId());

        $this->session->set(self::SESSION_LOADED, true);

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

    public function updateTheme(mixed $theme): string
    {
        if (! is_string($theme) || ! in_array($theme, self::THEMES, true)) {
            throw new UserPreferenceValidationException(
                'Seleccioná el modo claro o el modo oscuro.',
            );
        }

        if (! $this->preferences->saveThemeForUser($this->authenticatedUserId(), $theme)) {
            throw new RuntimeException('No fue posible guardar la preferencia de apariencia.');
        }

        $this->session->set([
            self::SESSION_LOADED => true,
            self::SESSION_THEME  => $theme,
        ]);

        return $theme;
    }

    private function sessionTheme(): ?string
    {
        $theme = $this->session->get(self::SESSION_THEME);

        return is_string($theme) && in_array($theme, self::THEMES, true)
            ? $theme
            : null;
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
