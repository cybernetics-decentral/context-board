<?php

namespace Tests\Unit;

use Tests\TestCase;

class ValidatorTest extends TestCase
{
    // UT-1.2.1: sanitizeMessage strips NULL bytes
    public function testSanitizeMessageStripsNullBytes(): void
    {
        $result = \Validator::sanitizeMessage("hello\0world");
        $this->assertSame('helloworld', $result);
    }

    // UT-1.2.2: sanitizeMessage normalizes line endings
    public function testSanitizeMessageNormalizesLineEndings(): void
    {
        $result = \Validator::sanitizeMessage("a\r\nb");
        $this->assertSame("a\nb", $result);
    }

    // UT-1.2.3: sanitizeMessage trims whitespace
    public function testSanitizeMessageTrimsWhitespace(): void
    {
        $result = \Validator::sanitizeMessage("  hi  ");
        $this->assertSame('hi', $result);
    }

    // UT-1.2.4: sanitizeMessage truncates to 10000
    public function testSanitizeMessageTruncatesToMaxLength(): void
    {
        $long = str_repeat('x', 12000);
        $result = \Validator::sanitizeMessage($long);
        $this->assertSame(10000, mb_strlen($result));
    }

    // UT-1.2.5: sanitizeSubject trims and truncates
    public function testSanitizeSubjectTruncatesTo200(): void
    {
        $long = str_repeat('s', 300);
        $result = \Validator::sanitizeSubject($long);
        $this->assertSame(200, mb_strlen($result));
    }

    // UT-1.2.6: isValidBoardId valid
    public function testIsValidBoardIdWithValidId(): void
    {
        $this->assertTrue(\Validator::isValidBoardId('general'));
    }

    // UT-1.2.7: isValidBoardId with space
    public function testIsValidBoardIdWithSpaceReturnsFalse(): void
    {
        $this->assertFalse(\Validator::isValidBoardId('gen eral'));
    }

    // UT-1.2.8: isValidBoardId starts with hyphen
    public function testIsValidBoardIdStartingWithHyphenReturnsFalse(): void
    {
        $this->assertFalse(\Validator::isValidBoardId('-invalid'));
    }

    // UT-1.2.9: isValidBoardId ends with hyphen
    public function testIsValidBoardIdEndingWithHyphenReturnsFalse(): void
    {
        $this->assertFalse(\Validator::isValidBoardId('invalid-'));
    }

    // UT-1.2.10: isValidBoardId empty
    public function testIsValidBoardIdWithEmptyStringReturnsFalse(): void
    {
        $this->assertFalse(\Validator::isValidBoardId(''));
    }

    // UT-1.2.11: isValidBoardId too long (33 chars)
    public function testIsValidBoardIdTooLongReturnsFalse(): void
    {
        $this->assertFalse(\Validator::isValidBoardId('abcdefghijklmnopqrstuvwxyz1234567'));
    }

    // UT-1.2.12: isValidBoardId mixed valid
    public function testIsValidBoardIdWithMixedChars(): void
    {
        $this->assertTrue(\Validator::isValidBoardId('a_b-c'));
    }

    // UT-1.2.13: isValidThreadId valid
    public function testIsValidThreadIdWithValidFormat(): void
    {
        $this->assertTrue(\Validator::isValidThreadId('1717700123.456789.ab12cd34'));
    }

    // UT-1.2.14: isValidThreadId invalid hex
    public function testIsValidThreadIdWithInvalidHexReturnsFalse(): void
    {
        $this->assertFalse(\Validator::isValidThreadId('123.456.ghijklmn'));
    }

    // UT-1.2.15: isValidThreadId missing segment
    public function testIsValidThreadIdMissingSegmentReturnsFalse(): void
    {
        $this->assertFalse(\Validator::isValidThreadId('123.456'));
    }

    // UT-1.2.16: isValidPostId same as thread
    public function testIsValidPostIdValidatesSamePattern(): void
    {
        $this->assertTrue(\Validator::isValidPostId('1717700400.111111.ab12cd34'));
        $this->assertFalse(\Validator::isValidPostId('bad-post-id'));
    }

    // UT-1.2.17: isValidIp IPv4
    public function testIsValidIpWithIpv4(): void
    {
        $this->assertTrue(\Validator::isValidIp('192.168.1.1'));
    }

    // UT-1.2.18: isValidIp IPv6
    public function testIsValidIpWithIpv6(): void
    {
        $this->assertTrue(\Validator::isValidIp('2001:0db8:85a3:0000:0000:8a2e:0370:7334'));
    }

    // UT-1.2.19: isValidIp invalid
    public function testIsValidIpWithInvalidIpReturnsFalse(): void
    {
        $this->assertFalse(\Validator::isValidIp('not.an.ip'));
    }

    // UT-1.2.20: validateMessageLength empty
    public function testValidateMessageLengthEmptyReturnsError(): void
    {
        $this->assertSame('A message is required.', \Validator::validateMessageLength(''));
    }

    // UT-1.2.21: validateMessageLength valid
    public function testValidateMessageLengthValidReturnsNull(): void
    {
        $this->assertNull(\Validator::validateMessageLength('hello'));
    }

    // UT-1.2.22: validateMessageLength too long
    public function testValidateMessageLengthTooLongReturnsError(): void
    {
        $this->assertNotNull(\Validator::validateMessageLength(str_repeat('x', 10001)));
    }

    // UT-1.2.23: sanitizeMessage preserves emoji
    public function testSanitizeMessagePreservesEmoji(): void
    {
        $result = \Validator::sanitizeMessage("🌟 test");
        $this->assertSame("🌟 test", $result);
    }

    // UT-1.2.24: sanitizeMessage preserves UTF-8
    public function testSanitizeMessagePreservesUtf8(): void
    {
        $result = \Validator::sanitizeMessage("café résumé");
        $this->assertSame("café résumé", $result);
    }

    // Additional: empty string after trim
    public function testSanitizeMessageAllWhitespaceBecomesEmpty(): void
    {
        $result = \Validator::sanitizeMessage("   \n  \r\n  ");
        $this->assertSame('', $result);
    }

    // Additional: single character board ID
    public function testIsValidBoardIdSingleCharacterReturnsFalse(): void
    {
        $this->assertFalse(\Validator::isValidBoardId('a'));
    }

    public function testIsValidBoardIdTwoCharsMinimum(): void
    {
        $this->assertTrue(\Validator::isValidBoardId('ab'));
    }
}
