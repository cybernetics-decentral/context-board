<?php

/**
 * Base TestCase for Context Board tests.
 *
 * Provides an isolated temporary data directory for each test.
 */

namespace Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/context-board-test/' . uniqid('test_', true) . '/';
        mkdir($this->tempDir, 0755, true);

        // Create essential subdirectories
        mkdir($this->tempDir . 'boards', 0755, true);
        mkdir($this->tempDir . 'tmp', 0755, true);
        mkdir($this->tempDir . 'logs', 0755, true);
        mkdir($this->tempDir . 'ip_logs', 0755, true);
    }

    protected function tearDown(): void
    {
        $this->recursiveDelete($this->tempDir);
        parent::tearDown();
    }

    protected function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->recursiveDelete($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
