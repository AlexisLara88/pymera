<?php

declare(strict_types=1);

namespace App\Libraries;

final class CrmReturnLocation
{
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
        $section = $input['return_section'] ?? null;

        if (! is_string($section)
            || ! in_array($section, self::SECTIONS, true)) {
            return $baseUrl;
        }

        return $baseUrl . '?' . http_build_query([
            'section' => $section,
        ]);
    }
}
