<?php

namespace Tests\Unit;

use Tests\TestCase;

class SecurityTest extends TestCase
{
    public function testGenerateCsrfTokenProduces64HexChars(): void
    {
        $token = \Security::generateCsrfToken();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    public function testValidateCsrfTokenWithMatchReturnsTrue(): void
    {
        $_SESSION['csrf_token'] = 'abc123testtoken';
        $this->assertTrue(\Security::validateCsrfToken('abc123testtoken'));
    }

    public function testValidateCsrfTokenWithMismatchReturnsFalse(): void
    {
        $_SESSION['csrf_token'] = 'abc123testtoken';
        $this->assertFalse(\Security::validateCsrfToken('different'));
    }

    public function testEscapeHtmlEscapesScriptTag(): void
    {
        $result = \Security::escapeHtml('<script>alert("xss")</script>');
        $this->assertStringNotContainsString('<script>alert', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    public function testEscapeHtmlEscapesDoubleQuotes(): void
    {
        $result = \Security::escapeHtml('"quoted"');
        $this->assertStringContainsString('&quot;', $result);
    }

    public function testEscapeHtmlEscapesSingleQuote(): void
    {
        $result = \Security::escapeHtml("it's");
        $this->assertStringContainsString('&#039;', $result);
    }

    public function testEscapeHtmlEscapesAmpersand(): void
    {
        $result = \Security::escapeHtml('A & B');
        $this->assertStringContainsString('&amp;', $result);
    }

    public function testEscapeAttributePreventsInjection(): void
    {
        $result = \Security::escapeAttribute('" onmouseover="alert(1)"');
        $this->assertStringContainsString('&quot;', $result);
    }

    public function testHashPasswordReturnsHashedString(): void
    {
        $hash = \Security::hashPassword('test1234');
        $this->assertTrue(str_starts_with($hash, '$2y$') || str_starts_with($hash, '$argon2id$'));
    }

    public function testVerifyPasswordWithCorrectPasswordReturnsTrue(): void
    {
        $hash = \Security::hashPassword('securepass');
        $this->assertTrue(\Security::verifyPassword('securepass', $hash));
    }

    public function testVerifyPasswordWithWrongPasswordReturnsFalse(): void
    {
        $hash = \Security::hashPassword('securepass');
        $this->assertFalse(\Security::verifyPassword('wrongpass', $hash));
    }

    public function testGetCsrfTokenFieldReturnsHiddenInput(): void
    {
        $field = \Security::getCsrfTokenField();
        $this->assertStringContainsString('<input type="hidden"', $field);
        $this->assertStringContainsString('name="csrf_token"', $field);
        $this->assertStringContainsString('value="', $field);
    }

    public function testValidateCsrfTokenWithNoSessionTokenReturnsFalse(): void
    {
        unset($_SESSION['csrf_token']);
        $this->assertFalse(\Security::validateCsrfToken('anytoken'));
    }

    public function testGenerateCsrfTokenProducesUniqueTokens(): void
    {
        $tokens = [];
        for ($i = 0; $i < 10; $i++) {
            $tokens[] = \Security::generateCsrfToken();
        }
        $this->assertCount(10, array_unique($tokens));
    }

    public function testHashPasswordProducesDifferentHashesForSameInput(): void
    {
        $hash1 = \Security::hashPassword('samepass');
        $hash2 = \Security::hashPassword('samepass');
        $this->assertNotSame($hash1, $hash2);
    }
}
