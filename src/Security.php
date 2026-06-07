<?php

/**
 * Security — CSRF tokens, HTML escaping, password hashing.
 *
 * SDD Reference: Section 3.11
 */
class Security
{
    /**
     * Generate a CSRF token (64 hex chars = 32 random bytes).
     */
    public static function generateCsrfToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Return an <input type="hidden"> field with CSRF token.
     */
    public static function getCsrfTokenField(): string
    {
        $token = $_SESSION['csrf_token'] ?? self::generateCsrfToken();
        $_SESSION['csrf_token'] = $token;
        return '<input type="hidden" name="csrf_token" value="' . self::escapeAttribute($token) . '">';
    }

    /**
     * Validate a CSRF token against the session token.
     */
    public static function validateCsrfToken(string $token): bool
    {
        if (empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Escape a string for HTML text content.
     */
    public static function escapeHtml(string $raw): string
    {
        return htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Escape a string for HTML attribute values.
     */
    public static function escapeAttribute(string $raw): string
    {
        return htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Hash a password.
     */
    public static function hashPassword(string $password): string
    {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        if ($algo === PASSWORD_ARGON2ID) {
            $options = ['memory_cost' => 65536, 'time_cost' => 4];
            // threads > 1 not supported on all builds
            if (defined('PASSWORD_ARGON2_PROVIDER')) {
                $options['threads'] = 1;
            }
        } else {
            $options = ['cost' => 12];
        }
        return password_hash($password, $algo, $options);
    }

    /**
     * Verify a password against a hash.
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check that the admin session is authenticated. Redirects to login if not.
     */
    public static function requireAdminSession(): void
    {
        if (empty($_SESSION['admin_authenticated'])) {
            header('Location: /admin/login', true, 302);
            exit;
        }
        self::checkSessionTimeout();
    }

    /**
     * Check admin session timeout. Destroys session if expired.
     */
    public static function checkSessionTimeout(): void
    {
        if (empty($_SESSION['admin_login_time'])) {
            return;
        }
        $timeout = 3600; // 1 hour
        if (time() - $_SESSION['admin_login_time'] > $timeout) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params['path'], $params['domain'],
                    $params['secure'], $params['httponly']);
            }
            session_destroy();
            header('Location: /admin/login?expired=1', true, 302);
            exit;
        }
        // Extend session
        $_SESSION['admin_login_time'] = time();
    }

    /**
     * Send security-related HTTP headers.
     */
    public static function sendSecurityHeaders(): void
    {
        header("Content-Security-Policy: default-src 'self'; script-src 'none'; style-src 'self' 'unsafe-inline';");
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: same-origin');
    }
}
