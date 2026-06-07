<?php

/**
 * Validator — centralized input validation and sanitization.
 *
 * SDD Reference: Section 3.10
 */
class Validator
{
    /**
     * Sanitize a message string.
     */
    public static function sanitizeMessage(string $raw): string
    {
        // Strip NULL bytes
        $text = str_replace("\0", '', $raw);
        // Normalize line endings
        $text = str_replace("\r\n", "\n", $text);
        $text = str_replace("\r", "\n", $text);
        // Trim whitespace
        $text = trim($text);
        // Truncate to max length
        if (mb_strlen($text) > 10000) {
            $text = mb_substr($text, 0, 10000);
        }
        return $text;
    }

    /**
     * Sanitize a subject string.
     */
    public static function sanitizeSubject(string $raw): string
    {
        $text = str_replace("\0", '', $raw);
        $text = trim($text);
        if (mb_strlen($text) > 200) {
            $text = mb_substr($text, 0, 200);
        }
        return $text;
    }

    /**
     * Validate board ID pattern.
     */
    public static function isValidBoardId(string $id): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]{0,30}[a-zA-Z0-9]$/', $id);
    }

    /**
     * Validate thread ID pattern.
     */
    public static function isValidThreadId(string $id): bool
    {
        return (bool) preg_match('/^[0-9]+\.[0-9]+\.[a-f0-9]+$/', $id);
    }

    /**
     * Validate post ID pattern (same as thread ID).
     */
    public static function isValidPostId(string $id): bool
    {
        return self::isValidThreadId($id);
    }

    /**
     * Validate IP address (IPv4 or IPv6).
     */
    public static function isValidIp(string $ip): bool
    {
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6);
    }

    /**
     * Validate message length. Returns error string or null.
     */
    public static function validateMessageLength(string $message): ?string
    {
        if (mb_strlen($message) === 0) {
            return 'A message is required.';
        }
        if (mb_strlen($message) > 10000) {
            return 'Message exceeds maximum length of 10,000 characters.';
        }
        return null;
    }

    /**
     * Validate subject length. Returns error string or null.
     */
    public static function validateSubjectLength(string $subject): ?string
    {
        if (mb_strlen($subject) > 200) {
            return 'Subject exceeds maximum length of 200 characters.';
        }
        return null;
    }
}
