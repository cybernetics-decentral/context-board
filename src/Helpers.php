<?php

/**
 * Helpers — stateless utility functions.
 *
 * SDD Reference: Section 3.13
 */
class Helpers
{
    /**
     * Format Unix timestamp as relative time string.
     */
    public static function relativeTime(int $timestamp): string
    {
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'just now';
        }
        if ($diff < 3600) {
            $mins = (int)($diff / 60);
            return $mins === 1 ? '1 minute ago' : "{$mins} minutes ago";
        }
        if ($diff < 86400) {
            $hrs = (int)($diff / 3600);
            return $hrs === 1 ? '1 hour ago' : "{$hrs} hours ago";
        }
        if ($diff < 2592000) {
            $days = (int)($diff / 86400);
            return $days === 1 ? '1 day ago' : "{$days} days ago";
        }
        if ($diff < 31536000) {
            $months = (int)($diff / 2592000);
            return $months === 1 ? '1 month ago' : "{$months} months ago";
        }
        $years = (int)($diff / 31536000);
        return $years === 1 ? '1 year ago' : "{$years} years ago";
    }

    /**
     * Format Unix timestamp as absolute date string.
     */
    public static function absoluteTime(int $timestamp): string
    {
        return date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * Generate message excerpt — first N chars, newlines → spaces.
     */
    public static function excerpt(string $message, int $length = 150): string
    {
        $text = str_replace(["\r\n", "\r", "\n"], ' ', $message);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        $truncated = mb_substr($text, 0, $length);
        $lastSpace = mb_strrpos($truncated, ' ');

        if ($lastSpace !== false && $lastSpace > $length * 0.5) {
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }

        return $truncated . '…';
    }

    /**
     * Generate unique ID: {unix_seconds}.{microseconds}.{8_random_hex}
     */
    public static function generateId(): string
    {
        $microtime = microtime(true);
        $parts = explode('.', (string)$microtime);
        $seconds = $parts[0];
        $microseconds = $parts[1] ?? '000000';
        $randomHex = bin2hex(random_bytes(4));

        return "{$seconds}.{$microseconds}.{$randomHex}";
    }
}
