<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;

/** @internal */
final class ArchitectureRulesTest extends CIUnitTestCase
{
    public function testControllersAndViewsDoNotAccessQueryBuilderDirectly(): void
    {
        foreach ($this->phpFiles(APPPATH . 'Controllers') as $file) {
            $source = (string) file_get_contents($file);

            $this->assertStringNotContainsString('->table(', $source, $file);
            $this->assertStringNotContainsString('db_connect(', $source, $file);
        }

        foreach ($this->phpFiles(APPPATH . 'Views') as $file) {
            $source = (string) file_get_contents($file);

            $this->assertStringNotContainsString('->table(', $source, $file);
            $this->assertStringNotContainsString('db_connect(', $source, $file);
        }
    }

    public function testServicesCoordinateModelsWithoutBuildingQueries(): void
    {
        foreach ($this->phpFiles(APPPATH . 'Services') as $file) {
            $this->assertStringNotContainsString(
                '->table(',
                (string) file_get_contents($file),
                $file,
            );
        }
    }

    public function testPlatformViewDoesNotLoadVueWithoutAStatefulIsland(): void
    {
        $source = (string) file_get_contents(APPPATH . 'Views/platform/index.php');

        $this->assertStringNotContainsString('alpha_frontend_scripts', $source);
        $this->assertStringContainsString('alpha_frontend_head', $source);
    }

    public function testCrmLoadsVueOnlyForScopedStatefulIslands(): void
    {
        $view = (string) file_get_contents(APPPATH . 'Views/crm/index.php');
        $editors = (string) file_get_contents(APPPATH . 'Views/crm/_editors.php');
        $script = (string) file_get_contents(FCPATH . 'assets/js/crm/index.js');

        $this->assertStringContainsString('data-crm-filter-app', $view);
        $this->assertStringContainsString('id="crmEditorApp"', $editors);
        $this->assertStringContainsString('id="crmStatusApp"', $editors);
        $this->assertStringContainsString('.mount(filterRoot)', $script);
        $this->assertStringContainsString('.mount(editorRoot)', $script);
        $this->assertStringContainsString('.mount(statusRoot)', $script);
        $this->assertStringNotContainsString('id="crmApp"', $view);
        $this->assertStringNotContainsString('fetch(', $script);
    }

    /** @return list<string> */
    private function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }
}
