<?php

namespace Tests\Unit;

use Tests\TestCase;

class IpLoggerTest extends TestCase
{
    private \IpLogger $logger;
    private string $ipLogsDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ipLogsDir = $this->tempDir . '/ip_logs';
        mkdir($this->ipLogsDir, 0755, true);
        $this->logger = new \IpLogger($this->ipLogsDir);
    }

    // UT-1.4.1: log writes one JSON line
    public function testLogWritesOneJsonLine(): void
    {
        $this->logger->log('general', 'tid.123.abc', 'pid.456.def', '192.168.1.1', 'new_thread');
        $date = gmdate('Y-m-d');
        $logPath = $this->ipLogsDir . '/' . $date . '.log';
        $this->assertFileExists($logPath);

        $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->assertCount(1, $lines);

        $entry = json_decode($lines[0], true);
        $this->assertSame('general', $entry['board_id']);
        $this->assertSame('192.168.1.1', $entry['ip']);
    }

    // UT-1.4.2: two log calls produce two lines
    public function testTwoLogCallsProduceTwoLines(): void
    {
        $this->logger->log('a', 't1.1.a', 'p1.1.a', '10.0.0.1', 'new_thread');
        $this->logger->log('a', 't1.1.a', 'p2.2.b', '10.0.0.1', 'reply');

        $date = gmdate('Y-m-d');
        $logPath = $this->ipLogsDir . '/' . $date . '.log';
        $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->assertCount(2, $lines);
    }

    // UT-1.4.3: log file name is UTC date
    public function testLogFileNameIsUtcDate(): void
    {
        $this->logger->log('b', 't.1.a', 'p.1.a', '::1', 'new_thread');
        $expectedFile = gmdate('Y-m-d') . '.log';
        $this->assertFileExists($this->ipLogsDir . '/' . $expectedFile);
    }

    // UT-1.4.4: action new_thread
    public function testLogActionNewThread(): void
    {
        $this->logger->log('c', 't.1.a', 'p.1.a', '10.0.0.1', 'new_thread');
        $date = gmdate('Y-m-d');
        $line = file_get_contents($this->ipLogsDir . '/' . $date . '.log');
        $this->assertStringContainsString('"action":"new_thread"', $line);
    }

    // UT-1.4.5: action reply
    public function testLogActionReply(): void
    {
        $this->logger->log('d', 't.1.a', 'p.1.a', '10.0.0.1', 'reply');
        $date = gmdate('Y-m-d');
        $line = file_get_contents($this->ipLogsDir . '/' . $date . '.log');
        $this->assertStringContainsString('"action":"reply"', $line);
    }

    // UT-1.4.6: IPv4 address stored
    public function testLogStoresIpv4Address(): void
    {
        $this->logger->log('e', 't.1.a', 'p.1.a', '192.168.1.100', 'new_thread');
        $date = gmdate('Y-m-d');
        $line = file_get_contents($this->ipLogsDir . '/' . $date . '.log');
        $this->assertStringContainsString('"ip":"192.168.1.100"', $line);
    }

    // UT-1.4.7: IPv6 address stored
    public function testLogStoresIpv6Address(): void
    {
        $this->logger->log('f', 't.1.a', 'p.1.a', '2001:db8::1', 'reply');
        $date = gmdate('Y-m-d');
        $line = file_get_contents($this->ipLogsDir . '/' . $date . '.log');
        $this->assertStringContainsString('"ip":"2001:db8::1"', $line);
    }

    // log creates directory if needed
    public function testLogCreatesDirectoryIfNeeded(): void
    {
        $newDir = $this->tempDir . '/new_logs';
        $logger = new \IpLogger($newDir);
        $logger->log('g', 't.1.a', 'p.1.a', '10.0.0.1', 'new_thread');
        $this->assertTrue(is_dir($newDir));
    }
}
