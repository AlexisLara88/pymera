<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;

/** @internal */
final class BusinessFallbackViewTest extends CIUnitTestCase
{
    public function testUnavailableModuleReturnsToTheFunctionalProductInsteadOfTheFrozenDemo(): void
    {
        $body = view('business/unavailable');

        $this->assertStringContainsString('Volver a PyMERA', $body);
        $this->assertStringContainsString('href="' . site_url('demolite') . '"', $body);
        $this->assertStringNotContainsString('Volver a la demostración', $body);
        $this->assertStringNotContainsString('href="' . site_url('demo') . '"', $body);
    }
}
