<?php

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class CsrfProtectionTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testPostWithoutCsrfTokenIsRejected(): void
    {
        $this->expectException(SecurityException::class);

        $this
            ->withRoutes($this->probeRoutes())
            ->post('_test/csrf-probe', ['value' => 'blocked']);
    }

    public function testPostWithCsrfTokenIsAccepted(): void
    {
        $tokenName = csrf_token();
        $tokenHash = csrf_hash();

        $result = $this
            ->withRoutes($this->probeRoutes())
            ->post('_test/csrf-probe', [
                'value'    => 'accepted',
                $tokenName => $tokenHash,
            ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['ok' => true]);
    }

    /**
     * @return array<int, array{string, string, Closure(): ResponseInterface}>
     */
    private function probeRoutes(): array
    {
        return [
            [
                'POST',
                '_test/csrf-probe',
                static fn (): ResponseInterface => service('response')->setJSON(['ok' => true]),
            ],
        ];
    }
}
