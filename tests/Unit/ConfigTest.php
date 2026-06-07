<?php

/**
 * ConfigTest — validates configuration values per SDD Section 3.3.
 */

namespace Tests\Unit;

use Tests\TestCase;

class ConfigTest extends TestCase
{
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();
        // Load config with a test ROOT_DIR
        $this->config = require ROOT_DIR . '/src/config.php';
    }

    public function testConfigReturnsArray(): void
    {
        $this->assertIsArray($this->config);
    }

    public function testRequiredKeysExist(): void
    {
        $requiredKeys = [
            'data_dir', 'boards_dir', 'ip_logs_dir', 'app_log_dir',
            'tmp_dir', 'template_dir',
        ];
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $this->config, "Missing config key: {$key}");
        }
    }

    public function testMaxMessageLengthIsPositiveInteger(): void
    {
        $this->assertIsInt($this->config['max_message_length']);
        $this->assertGreaterThan(0, $this->config['max_message_length']);
    }

    public function testRateLimitWindowIsPositiveInteger(): void
    {
        $this->assertIsInt($this->config['rate_limit_window']);
        $this->assertGreaterThan(0, $this->config['rate_limit_window']);
    }

    public function testPasswordAlgoIsValidConstant(): void
    {
        $this->assertContains(
            $this->config['password_algo'],
            [PASSWORD_ARGON2ID, PASSWORD_BCRYPT, PASSWORD_DEFAULT]
        );
    }

    public function testAutoRefreshSecondsIsPositive(): void
    {
        $this->assertGreaterThan(0, $this->config['auto_refresh_seconds']);
    }

    public function testThreadsPerPageIsPositive(): void
    {
        $this->assertGreaterThan(0, $this->config['threads_per_page']);
    }

    public function testMaxThreadFileSizeIsReasonable(): void
    {
        $this->assertGreaterThan(0, $this->config['max_thread_file_size']);
        $this->assertLessThan(10 * 1024 * 1024, $this->config['max_thread_file_size']); // < 10MB
    }
}
