<?php

declare(strict_types=1);

namespace App\Libraries;

final class CrmReturnLocation
{
    private const TABBED_VIEW = 'tabs';

    private const SECTIONS = [
        'contacts',
        'opportunities',
    ];

    /**
     * Builds an allowlisted return URL for CRM form submissions.
     *
     * @param array<string, mixed> $input
     */
    public static function fromInput(array $input): string
    {
        $baseUrl = site_url('app/clientes');
        $view    = $input['return_view'] ?? null;
        $section = $input['return_section'] ?? null;

        if ($view !== self::TABBED_VIEW
            || ! is_string($section)
            || ! in_array($section, self::SECTIONS, true)) {
            return $baseUrl;
        }

        return $baseUrl . '?' . http_build_query([
            'view'    => self::TABBED_VIEW,
            'section' => $section,
        ]);
    }
}
