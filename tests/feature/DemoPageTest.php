<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class DemoPageTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testRootShowsTheNavigableDemo(): void
    {
        $result = $this->get('/');

        $result->assertStatus(200);
        $result->assertSee('PyMe');
        $result->assertSee('ERP-LITE');
        $result->assertSee('Dulce Barrio');
        $result->assertSee('Paso 1 de 6');
        $body = $result->response()->getBody();
        $this->assertStringContainsString('/assets/css/erp-lite-demo.css', $body);
        $this->assertStringContainsString('/assets/js/erp-lite-demo.js', $body);
    }

    public function testDemoRouteContainsTheCompleteJourney(): void
    {
        $result = $this->get('/demo');

        $result->assertStatus(200);
        $result->assertSee('Paso 1 de 6');
        $result->assertSee('Paso 2 de 6');
        $result->assertSee('Paso 3 de 6');
        $result->assertSee('Paso 4 de 6');
        $result->assertSee('Paso 5 de 6');
        $result->assertSee('Paso 6 de 6');
        $result->assertSee('Datos ficticios');
        $result->assertSee('no guarda información');
        $result->assertSee('Ver recorrido');
        $result->assertSee('Recorrido del negocio');
        $result->assertSee('Etapa del recorrido');
        $result->assertSee('Reproducir automáticamente');
        $result->assertSee('6 segundos de lectura por sector');
        $result->assertSee('Utilidad bruta');
        $result->assertSee('EBITDA provisional');
    }
}
