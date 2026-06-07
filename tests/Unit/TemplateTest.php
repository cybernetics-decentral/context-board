<?php

namespace Tests\Unit;

use Tests\TestCase;

class TemplateTest extends TestCase
{
    private string $templateDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templateDir = $this->tempDir . '/templates';
        mkdir($this->templateDir, 0755, true);

        // Create a test layout
        file_put_contents($this->templateDir . '/layout.php',
            '<!DOCTYPE html><html><head><title>' .
            '<?= htmlspecialchars($pageTitle ?? "Default", ENT_QUOTES, "UTF-8") ?></title>' .
            '</head><body><?= $content ?></body></html>'
        );

        // Create a simple template
        file_put_contents($this->templateDir . '/simple.php',
            'Hello, <?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?>!'
        );

        // Create a template that uses layout
        file_put_contents($this->templateDir . '/with_layout.php',
            '<?php $layout = "layout"; ?>' . "\n" .
            '<main><?= htmlspecialchars($msg, ENT_QUOTES, "UTF-8") ?></main>'
        );

        // Create a template with auto-refresh
        file_put_contents($this->templateDir . '/with_refresh.php',
            '<?php $layout = "layout"; ?>' . "\n" .
            'Refresh: <?= $autoRefresh ? "yes" : "no" ?>'
        );
    }

    // UT-1.6.1: render simple template
    public function testRenderSimpleTemplate(): void
    {
        $tpl = new \Template($this->templateDir);
        $result = $tpl->render('simple', ['name' => 'Zoo']);
        $this->assertStringContainsString('Hello, Zoo!', $result);
    }

    // UT-1.6.2: render with layout
    public function testRenderWithLayout(): void
    {
        $tpl = new \Template($this->templateDir);
        $result = $tpl->render('with_layout', [
            'msg' => 'World',
            'pageTitle' => 'Test Page',
        ]);
        $this->assertStringContainsString('<main>World</main>', $result);
        $this->assertStringContainsString('<title>Test Page</title>', $result);
        $this->assertStringContainsString('<!DOCTYPE html>', $result);
    }

    // UT-1.6.3: template with auto-refresh data
    public function testRenderWithAutoRefresh(): void
    {
        $tpl = new \Template($this->templateDir);
        $result = $tpl->render('with_refresh', [
            'autoRefresh' => true,
        ]);
        $this->assertStringContainsString('Refresh: yes', $result);
    }

    // UT-1.6.4: template without auto-refresh
    public function testRenderWithoutAutoRefresh(): void
    {
        $tpl = new \Template($this->templateDir);
        $result = $tpl->render('with_refresh', [
            'autoRefresh' => false,
        ]);
        $this->assertStringContainsString('Refresh: no', $result);
    }

    // UT-1.6.5: pageTitle in data
    public function testRenderSetsPageTitle(): void
    {
        $tpl = new \Template($this->templateDir);
        $result = $tpl->render('with_layout', [
            'msg' => 'test',
            'pageTitle' => 'Custom Title',
        ]);
        $this->assertStringContainsString('<title>Custom Title</title>', $result);
    }

    // UT-1.6.6: non-existent template throws RuntimeException
    public function testRenderNonExistentTemplateThrowsException(): void
    {
        $tpl = new \Template($this->templateDir);
        $this->expectException(\RuntimeException::class);
        $tpl->render('does_not_exist');
    }

    // Default pageTitle when not provided
    public function testRenderUsesDefaultPageTitle(): void
    {
        $tpl = new \Template($this->templateDir);
        $result = $tpl->render('with_layout', ['msg' => 'test']);
        $this->assertStringContainsString('<title>Default</title>', $result);
    }
}
