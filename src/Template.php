<?php

/**
 * Template — simple server-side PHP template renderer.
 *
 * SDD Reference: Section 3.12
 */
class Template
{
    private string $templateDir;

    public function __construct(string $templateDir)
    {
        $this->templateDir = rtrim($templateDir, '/');
    }

    /**
     * Render a template with data.
     */
    public function render(string $templateName, array $data = []): string
    {
        extract($data);
        ob_start();
        $templateFile = $this->templateDir . '/' . $templateName . '.php';
        if (!file_exists($templateFile)) {
            ob_end_clean();
            throw new \RuntimeException("Template not found: {$templateName}");
        }
        include $templateFile;
        $content = ob_get_clean();

        // If the template set a $layout, wrap content
        if (isset($layout)) {
            ob_start();
            include $this->templateDir . '/' . $layout . '.php';
            return ob_get_clean();
        }

        return $content;
    }
}
