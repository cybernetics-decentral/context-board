<?php

namespace Tests\Unit;

use Tests\TestCase;

class HelpersTest extends TestCase
{
    // UT-1.3.1: relativeTime now
    public function testRelativeTimeJustNow(): void
    {
        $result = \Helpers::relativeTime(time());
        $this->assertSame('just now', $result);
    }

    // UT-1.3.2: relativeTime 5 minutes ago
    public function testRelativeTimeFiveMinutesAgo(): void
    {
        $result = \Helpers::relativeTime(time() - 300);
        $this->assertSame('5 minutes ago', $result);
    }

    // UT-1.3.3: relativeTime 2 hours ago
    public function testRelativeTimeTwoHoursAgo(): void
    {
        $result = \Helpers::relativeTime(time() - 7200);
        $this->assertSame('2 hours ago', $result);
    }

    // UT-1.3.4: relativeTime 3 days ago
    public function testRelativeTimeThreeDaysAgo(): void
    {
        $result = \Helpers::relativeTime(time() - 259200);
        $this->assertSame('3 days ago', $result);
    }

    // UT-1.3.5: absoluteTime format
    public function testAbsoluteTimeFormat(): void
    {
        $ts = mktime(14, 15, 23, 6, 7, 2026);
        $result = \Helpers::absoluteTime($ts);
        $this->assertSame('2026-06-07 14:15:23', $result);
    }

    // UT-1.3.6: excerpt short text unchanged
    public function testExcerptShortTextUnchanged(): void
    {
        $result = \Helpers::excerpt('Hello');
        $this->assertSame('Hello', $result);
    }

    // UT-1.3.7: excerpt long text truncated
    public function testExcerptLongTextTruncated(): void
    {
        $long = str_repeat('word ', 100);
        $result = \Helpers::excerpt($long, 50);
        $this->assertLessThanOrEqual(52, mb_strlen($result)); // 50 + …
        $this->assertStringEndsWith('…', $result);
    }

    // UT-1.3.8: excerpt breaks at word boundary
    public function testExcerptBreaksAtWordBoundary(): void
    {
        $text = 'hello world this is a test of the excerpt function';
        $result = \Helpers::excerpt($text, 12);
        // Should break at space after 'hello world'
        $this->assertStringContainsString('hello world', $result);
        $this->assertStringNotContainsString('this is', $result);
    }

    // UT-1.3.9: excerpt replaces newlines
    public function testExcerptReplacesNewlines(): void
    {
        $result = \Helpers::excerpt("line1\nline2");
        $this->assertStringContainsString('line1 line2', $result);
    }

    // UT-1.3.10: generateId format
    public function testGenerateIdFormat(): void
    {
        $id = \Helpers::generateId();
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.[a-f0-9]+$/', $id);
    }

    // UT-1.3.11: generateId uniqueness
    public function testGenerateIdUniqueness(): void
    {
        $ids = [];
        for ($i = 0; $i < 50; $i++) {
            $ids[] = \Helpers::generateId();
        }
        $this->assertCount(50, array_unique($ids));
    }

    // Additional: relativeTime 1 minute singular
    public function testRelativeTimeOneMinuteAgo(): void
    {
        $result = \Helpers::relativeTime(time() - 60);
        $this->assertSame('1 minute ago', $result);
    }

    // Additional: relativeTime 1 hour singular
    public function testRelativeTimeOneHourAgo(): void
    {
        $result = \Helpers::relativeTime(time() - 3600);
        $this->assertSame('1 hour ago', $result);
    }

    // Additional: relativeTime 1 day singular
    public function testRelativeTimeOneDayAgo(): void
    {
        $result = \Helpers::relativeTime(time() - 86400);
        $this->assertSame('1 day ago', $result);
    }

    // Additional: excerpt with exact length
    public function testExcerptExactLengthReturnsUnchanged(): void
    {
        $text = 'Exactly 30 characters here!!';
        $result = \Helpers::excerpt($text, 30);
        $this->assertSame($text, $result);
    }

    // Additional: excerpt collapses multiple spaces
    public function testExcerptCollapsesMultipleSpaces(): void
    {
        $result = \Helpers::excerpt("foo   bar");
        $this->assertStringContainsString('foo bar', $result);
    }
}
