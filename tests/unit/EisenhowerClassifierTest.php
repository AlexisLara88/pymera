<?php

use App\Services\EisenhowerClassifier;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @internal
 */
final class EisenhowerClassifierTest extends CIUnitTestCase
{
    /**
     * @return iterable<string, array{bool, bool, string}>
     */
    public static function quadrantProvider(): iterable
    {
        yield 'hacer ahora' => [true, true, EisenhowerClassifier::DO_NOW];
        yield 'planificar' => [false, true, EisenhowerClassifier::SCHEDULE];
        yield 'delegar' => [true, false, EisenhowerClassifier::DELEGATE];
        yield 'eliminar' => [false, false, EisenhowerClassifier::ELIMINATE];
    }

    #[DataProvider('quadrantProvider')]
    public function testClassifiesEveryQuadrant(bool $urgent, bool $important, string $expected): void
    {
        $classifier = new EisenhowerClassifier();

        $this->assertSame($expected, $classifier->classify($urgent, $important));
    }
}
