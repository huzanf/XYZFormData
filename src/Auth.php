<?php

declare(strict_types=1);

/**
 * A single shared portal password (no per-user accounts — this is a small
 * internal tool for a handful of staff). Set PORTAL_PASSWORD_HASH in .env
 * to enable it; leave it blank to disable login entirely (not recommended,
 * since this portal can display and export personal/financial data).
 */
final class Auth
{
    public static function requireLogin(array $config): void
    {
        self::ensureSession();

        if (($config['auth']['password_hash'] ?? '') === '') {
            return;
        }

        if (!empty($_SESSION['xyz_portal_authenticated'])) {
            return;
        }

        $target = $_SERVER['REQUEST_URI'] ?? 'index.php';
        header('Location: login.php?redirect=' . urlencode($target));
        exit;
    }

    public static function attempt(array $config, string $password): bool
    {
        self::ensureSession();

        $hash = $config['auth']['password_hash'] ?? '';
        if ($hash !== '' && password_verify($password, $hash)) {
            session_regenerate_id(true);
            $_SESSION['xyz_portal_authenticated'] = true;

            return true;
        }

        return false;
    }

    public static function isLoggedIn(): bool
    {
        self::ensureSession();

        return !empty($_SESSION['xyz_portal_authenticated']);
    }

    public static function logout(): void
    {
        self::ensureSession();
        $_SESSION = [];
        session_destroy();
    }

    /**
     * Only allow same-app relative redirect targets, to avoid an
     * open-redirect via a crafted ?redirect= value.
     */
    public static function safeRedirectTarget(string $target): string
    {
        if ($target === '' || str_starts_with($target, '//') || preg_match('#^[a-z][a-z0-9+.-]*://#i', $target)) {
            return 'index.php';
        }

        return $target;
    }

    private static function ensureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}
